import json
import os
import re
import sys

from anthropic import Anthropic, AuthenticationError, APIConnectionError, APIError
from dotenv import load_dotenv

load_dotenv()

client = Anthropic(api_key=os.getenv("ANTHROPIC_API_KEY"))

# Dá pra trocar o modelo pelo .env (ANTHROPIC_MODEL) sem mexer no código.
# Pra gastar ainda menos, use um modelo menor, ex: claude-haiku-4-5-20251001
MODELO = os.getenv("ANTHROPIC_MODEL", "claude-sonnet-5")

MARCADOR_CODIGO = "###CODIGO###"

# --- limites pra controlar o gasto de tokens ---------------------------------
MAX_CHARS_ARQUIVO = 60_000      # acima disso o arquivo é cortado
MAX_TURNOS_HISTORICO = 4        # só as últimas 4 trocas vão pra IA
MAX_CHARS_ENTRADA_HIST = 600    # cada pergunta antiga é cortada em 600 caracteres
MAX_TOKENS_ANALISE = 2500       # análise sem código: curta
MAX_TOKENS_CODIGO = 32_000      # teto quando o usuário pede o código corrigido

# --- prompt enxuto -----------------------------------------------------------
PROMPT_BASE = """Você analisa código em busca de vulnerabilidades de segurança.
Responda SOMENTE com JSON válido (sem markdown, sem texto fora do JSON):
{"linguagem":"","resumo":"","vulnerabilidades":[{"titulo":"","severidade":"alta|media|baixa","explicacao":"","sugestao":""}]}
Regras: seja direto. "resumo": 1 frase. "explicacao" e "sugestao": no máximo 1 frase cada. Agrupe falhas do mesmo tipo em um único item (cite as rotas/trechos na explicação). Sem falhas: "vulnerabilidades":[].
Use o histórico quando fizer sentido. Se o usuário falar de um código que não está na conversa, peça no "resumo" para reenviar o arquivo."""

PROMPT_CODIGO = """
O usuário pediu o código corrigido. Logo depois do JSON, em uma nova linha, escreva exatamente ###CODIGO### e em seguida o arquivo COMPLETO já corrigido, em texto puro (sem ``` e sem explicações), mantendo estrutura, lógica e nomes e alterando só o necessário. Segredos fixos no código devem passar a vir de variável de ambiente. Se houver vários arquivos, preceda cada um com a linha: // ==== Arquivo: nome ====. Não repita o código dentro do JSON."""

_PEDIDO_CODIGO = re.compile(
    r"corrij|corrig|consert|arrum|devolv|reescrev|aplique|vers[aã]o (segura|nova)|\bfix\b|\bpatch\b",
    re.IGNORECASE,
)


# --- preparo da entrada ------------------------------------------------------
def _compactar(codigo: str) -> str:
    """Tira espaços no fim das linhas e linhas em branco repetidas.
    Não muda o código, só economiza token."""
    codigo = codigo.replace("\r\n", "\n").replace("\r", "\n")
    linhas = [linha.rstrip() for linha in codigo.split("\n")]
    codigo = "\n".join(linhas)
    return re.sub(r"\n{3,}", "\n\n", codigo).strip()


def _montar_historico(historico: list | None) -> list:
    """Histórico compacto: de cada turno antigo vai só a pergunta (cortada) e
    um JSON mínimo (linguagem, resumo, título+severidade das falhas). A
    explicação, a sugestão e o código corrigido NÃO são reenviados."""
    mensagens = []

    for turno in (historico or [])[-MAX_TURNOS_HISTORICO:]:
        entrada = (turno.get("entrada") or "").strip()
        if not entrada:
            continue

        arquivo = turno.get("arquivo")
        if len(entrada) > MAX_CHARS_ENTRADA_HIST:
            entrada = entrada[:MAX_CHARS_ENTRADA_HIST] + "…"
        if arquivo and arquivo not in entrada:
            entrada = f"[arquivo: {arquivo}] {entrada}"

        anterior = turno.get("resposta") or {}
        resumo = anterior.get("resumo") or ""
        if anterior.get("codigo_corrigido"):
            resumo += " (código corrigido já entregue)"

        compacto = {
            "linguagem": anterior.get("linguagem"),
            "resumo": resumo,
            "vulnerabilidades": [
                {"titulo": v.get("titulo", ""), "severidade": v.get("severidade", "")}
                for v in (anterior.get("vulnerabilidades") or [])[:15]
            ],
        }

        mensagens.append({"role": "user", "content": entrada})
        mensagens.append({
            "role": "assistant",
            "content": json.dumps(compacto, ensure_ascii=False, separators=(",", ":")),
        })

    return mensagens


# --- leitura da resposta -----------------------------------------------------
def _extrair_texto(resposta) -> str:
    return "".join(b.text for b in resposta.content if b.type == "text").strip()


def _tirar_cercas(texto: str) -> str:
    """Remove ``` só no começo e no fim (nunca de dentro do código)."""
    texto = re.sub(r"^\s*```[a-zA-Z]*\s*\n", "", texto)
    texto = re.sub(r"\n\s*```\s*$", "", texto)
    return texto.strip()


def _reparar_json(s: str) -> dict | None:
    """Tenta aproveitar um JSON que veio cortado no meio."""
    # 1) cortado logo depois de uma chave: {"a":"b", "
    tentativa = re.sub(r',\s*"[^"]*$', "", s) + "}"
    try:
        return json.loads(tentativa)
    except json.JSONDecodeError:
        pass

    # 2) cortado no meio da lista: volta até o último objeto completo
    pos = len(s)
    for _ in range(60):
        pos = s.rfind("}", 0, pos)
        if pos == -1:
            break
        for fim in ("", "]}"):
            try:
                return json.loads(s[: pos + 1] + fim)
            except json.JSONDecodeError:
                continue
    return None


def _ler_json(parte: str) -> dict | None:
    parte = _tirar_cercas(parte)
    try:
        dados = json.loads(parte)
    except json.JSONDecodeError:
        ini, fim = parte.find("{"), parte.rfind("}")
        dados = None
        if ini != -1 and fim > ini:
            try:
                dados = json.loads(parte[ini : fim + 1])
            except json.JSONDecodeError:
                pass
        if dados is None:
            dados = _reparar_json(parte[ini:] if ini != -1 else parte)
    return dados if isinstance(dados, dict) else None


def _normalizar(dados: dict) -> dict:
    """Garante o formato que o front espera (e severidade sem acento)."""
    vulns = []
    for v in dados.get("vulnerabilidades") or []:
        if not isinstance(v, dict):
            continue
        sev = str(v.get("severidade", "baixa")).lower().replace("é", "e").replace("á", "a")
        v["severidade"] = sev if sev in ("alta", "media", "baixa") else "baixa"
        vulns.append(v)
    dados["vulnerabilidades"] = vulns
    dados["resumo"] = dados.get("resumo") or ""
    dados.setdefault("linguagem", None)
    return dados


def _erro_amigavel(resumo: str) -> dict:
    return {"linguagem": None, "resumo": resumo, "vulnerabilidades": []}


# --- função principal --------------------------------------------------------
def analisar_codigo(
    texto: str,
    arquivo_conteudo: str | None = None,
    arquivo_nome: str | None = None,
    historico: list | None = None,
) -> dict:
    texto = (texto or "").strip()
    pede_codigo = bool(_PEDIDO_CODIGO.search(texto))

    partes = [texto] if texto else []
    if arquivo_conteudo:
        codigo = _compactar(arquivo_conteudo)
        aviso = ""
        if len(codigo) > MAX_CHARS_ARQUIVO:
            codigo = codigo[:MAX_CHARS_ARQUIVO]
            aviso = " (cortado por tamanho: só o início foi enviado)"
        partes.append(f"Arquivo ({arquivo_nome}){aviso}:\n{codigo}")

    conteudo = "\n\n".join(partes)
    if not conteudo:
        return _erro_amigavel("Nada para analisar. Cole um código ou envie um arquivo.")

    mensagens = _montar_historico(historico)
    mensagens.append({"role": "user", "content": conteudo})

    system = PROMPT_BASE + (PROMPT_CODIGO if pede_codigo else "")
    max_tokens = (
        min(MAX_TOKENS_CODIGO, 2500 + int(len(conteudo) / 3 * 1.4))
        if pede_codigo
        else MAX_TOKENS_ANALISE
    )

    try:
        # stream evita timeout em respostas grandes; o resultado final é o mesmo
        with client.messages.stream(
            model=MODELO,
            max_tokens=max_tokens,
            system=system,
            messages=mensagens,
        ) as stream:
            resposta = stream.get_final_message()
    except AuthenticationError:
        return _erro_amigavel(
            "Não foi possível autenticar com a API da Anthropic. Verifique "
            "ANTHROPIC_API_KEY em api/.env (chave gerada em console.anthropic.com)."
        )
    except APIConnectionError as e:
        return _erro_amigavel(f"Não foi possível conectar à API da Anthropic: {e}")
    except APIError as e:
        return _erro_amigavel(f"A API da Anthropic retornou um erro: {e}")

    motivo = resposta.stop_reason
    print(
        f"[IA] modelo={MODELO} parada={motivo} "
        f"tokens_entrada={resposta.usage.input_tokens} "
        f"tokens_saida={resposta.usage.output_tokens} pede_codigo={pede_codigo}",
        file=sys.stderr,
        flush=True,
    )

    bruto = _extrair_texto(resposta)
    parte_json, _, parte_codigo = bruto.partition(MARCADOR_CODIGO)

    dados = _ler_json(parte_json)

    if dados is None:
        if motivo == "refusal":
            return _erro_amigavel(
                "A IA recusou processar esse conteúdo (filtro de segurança do modelo). "
                "Tente enviar um arquivo por vez ou um trecho menor."
            )
        if motivo == "max_tokens":
            return _erro_amigavel(
                "A resposta foi cortada por tamanho. Tente enviar um arquivo por vez."
            )
        return _erro_amigavel(bruto or "A IA não retornou uma resposta em texto. Tente novamente.")

    dados = _normalizar(dados)

    codigo_corrigido = _tirar_cercas(parte_codigo) if parte_codigo.strip() else ""

    if motivo == "max_tokens":
        if codigo_corrigido:
            # código cortado no meio é perigoso de copiar: não entrega
            codigo_corrigido = ""
            dados["resumo"] += " (O código corrigido ficou grande demais e foi cortado; peça um arquivo por vez.)"
        else:
            dados["resumo"] += " (Resposta cortada por tamanho: a lista pode estar incompleta.)"
    elif motivo == "refusal":
        codigo_corrigido = ""
        dados["resumo"] += " (A IA interrompeu a resposta por um filtro de segurança; tente um arquivo por vez.)"
    elif pede_codigo and not codigo_corrigido:
        dados["resumo"] += " (A IA não devolveu o código corrigido; peça novamente.)"

    if codigo_corrigido:
        dados["codigo_corrigido"] = codigo_corrigido

    return dados