import os
import json
import re
from anthropic import Anthropic, AuthenticationError, APIConnectionError, APIError
from dotenv import load_dotenv

load_dotenv()

client = Anthropic(api_key=os.getenv("ANTHROPIC_API_KEY"))

SYSTEM_PROMPT = """
Você é um assistente de segurança que analisa trechos de código em busca de
vulnerabilidades comuns (SQL Injection, XSS, falhas de autenticação, etc).

Essa conversa pode ter mensagens anteriores — use esse contexto quando fizer
sentido (por exemplo, se o usuário perguntar algo sobre um código já enviado
antes, ou enviar uma versão corrigida do mesmo código).

Responda SEMPRE e SOMENTE com um JSON válido, sem nenhum texto antes ou depois,
sem marcação markdown, seguindo exatamente este formato:

{
  "linguagem": "nome da linguagem detectada no código (ex: PHP, Python, JavaScript)",
  "resumo": "resumo geral em 1-2 frases sobre o estado de segurança do código",
  "vulnerabilidades": [
    {
      "titulo": "nome curto da vulnerabilidade",
      "severidade": "alta, media ou baixa",
      "explicacao": "por que isso é uma vulnerabilidade, de forma didática",
      "sugestao": "como corrigir, sem reescrever o código inteiro"
    }
  ]
}

Se não encontrar nenhuma vulnerabilidade, retorne "vulnerabilidades": [] e um
resumo dizendo que o código parece seguro dentro do que foi analisado.

CÓDIGO CORRIGIDO (campo opcional "codigo_corrigido"):
- Por padrão NÃO inclua esse campo: nas "sugestao" explique como corrigir sem
  reescrever o código inteiro.
- Se o usuário pedir explicitamente o código corrigido, o arquivo inteiro
  corrigido, a versão corrigida ou algo equivalente, adicione ao JSON o campo
  "codigo_corrigido" com o código COMPLETO já corrigido (não só os trechos
  alterados), mantendo a estrutura, a lógica e os nomes do original e
  corrigindo apenas o que for necessário para eliminar as vulnerabilidades.
- O valor de "codigo_corrigido" é uma string JSON: escape corretamente as
  quebras de linha (\n), aspas e barras, e NÃO use blocos markdown (```).
- Se o usuário enviou vários arquivos (.zip), inclua todos os arquivos
  corrigidos em "codigo_corrigido", cada um precedido de uma linha de
  comentário com o nome do arquivo, no formato: // ==== Arquivo: nome ====
- Se o pedido de código corrigido vier sem nenhum código na conversa para
  corrigir, explique isso no "resumo" e não inclua o campo.
- Nos demais campos ("resumo", "vulnerabilidades") continue explicando o que
  foi encontrado e o que mudou na versão corrigida.
"""


def _montar_historico(historico: list | None) -> list:
    """Transforma o histórico salvo no banco em mensagens user/assistant
    alternadas, no formato que a API do Claude espera."""
    mensagens = []

    if not historico:
        return mensagens

    for turno in historico:
        entrada = turno.get("entrada") or ""
        resposta_anterior = dict(turno.get("resposta") or {})

        # Não reenvia o código corrigido inteiro a cada turno (gasta token à toa)
        if resposta_anterior.get("codigo_corrigido"):
            resposta_anterior["codigo_corrigido"] = "(código corrigido enviado anteriormente)"

        if not entrada:
            continue

        mensagens.append({"role": "user", "content": entrada})
        mensagens.append({
            "role": "assistant",
            "content": json.dumps(resposta_anterior, ensure_ascii=False)
        })

    return mensagens


def _extrair_texto(resposta) -> str:
    """Pega o texto da resposta olhando o tipo de cada bloco, em vez de
    assumir que content[0] é sempre texto — o Claude pode devolver um
    bloco de 'thinking' (raciocínio interno) antes do bloco de texto."""
    partes = []

    for bloco in resposta.content:
        if bloco.type == "text":
            partes.append(bloco.text)

    return "".join(partes).strip()


def _erro_amigavel(resumo: str) -> dict:
    """Monta uma resposta no mesmo formato de uma análise normal, só que
    com o resumo explicando o erro — assim o chat mostra uma mensagem
    útil em vez do PHP cair no genérico 'Resposta inesperada do serviço
    de IA'."""
    return {
        "linguagem": None,
        "resumo": resumo,
        "vulnerabilidades": []
    }


def analisar_codigo(
    texto: str,
    arquivo_conteudo: str | None = None,
    arquivo_nome: str | None = None,
    historico: list | None = None,
) -> dict:
    mensagens = _montar_historico(historico)

    partes = []
    if texto:
        partes.append(texto)
    if arquivo_conteudo:
        partes.append(f"\n\nArquivo enviado ({arquivo_nome}):\n{arquivo_conteudo}")

    conteudo_completo = "\n".join(partes)
    mensagens.append({"role": "user", "content": conteudo_completo})

    try:
        resposta = client.messages.create(
            model="claude-sonnet-5",
            max_tokens=8000,
            system=SYSTEM_PROMPT,
            messages=mensagens
        )
    except AuthenticationError:
        return _erro_amigavel(
            "Não foi possível autenticar com a API da Anthropic. "
            "Verifique se ANTHROPIC_API_KEY em api/.env é uma chave válida "
            "(gerada em console.anthropic.com)."
        )
    except APIConnectionError as e:
        return _erro_amigavel(f"Não foi possível conectar à API da Anthropic: {e}")
    except APIError as e:
        return _erro_amigavel(f"A API da Anthropic retornou um erro: {e}")

    texto_resposta = _extrair_texto(resposta)

    # Resposta cortada no limite de tokens = JSON incompleto
    if resposta.stop_reason == "max_tokens":
        return _erro_amigavel(
            "A resposta ficou grande demais e foi cortada. Tente pedir o código "
            "corrigido de um arquivo por vez ou de um trecho menor."
        )

    if not texto_resposta:
        return _erro_amigavel(
            "A IA não retornou uma resposta em texto dessa vez. Tente novamente."
        )

    # Remove blocos de markdown, caso o modelo insista em incluir ```json
    texto_resposta = re.sub(r"^```(json)?|```$", "", texto_resposta, flags=re.MULTILINE).strip()

    try:
        return json.loads(texto_resposta)
    except json.JSONDecodeError:
        # Se por algum motivo não veio JSON válido, ainda assim devolve algo utilizável
        return _erro_amigavel(texto_resposta)