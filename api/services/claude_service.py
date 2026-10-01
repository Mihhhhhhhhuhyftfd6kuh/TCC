import os
import json
import re
from anthropic import Anthropic
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
"""


def _montar_historico(historico: list | None) -> list:
    """Transforma o histórico salvo no banco em mensagens user/assistant
    alternadas, no formato que a API do Claude espera."""
    mensagens = []

    if not historico:
        return mensagens

    for turno in historico:
        entrada = turno.get("entrada") or ""
        resposta_anterior = turno.get("resposta") or {}

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

    resposta = client.messages.create(
        model="claude-sonnet-5",
        max_tokens=1500,
        system=SYSTEM_PROMPT,
        messages=mensagens
    )

    texto_resposta = _extrair_texto(resposta)

    if not texto_resposta:
        return {
            "linguagem": None,
            "resumo": "A IA não retornou uma resposta em texto dessa vez. Tente novamente.",
            "vulnerabilidades": []
        }

    # Remove blocos de markdown, caso o modelo insista em incluir ```json
    texto_resposta = re.sub(r"^```(json)?|```$", "", texto_resposta, flags=re.MULTILINE).strip()

    try:
        return json.loads(texto_resposta)
    except json.JSONDecodeError:
        # Se por algum motivo não veio JSON válido, ainda assim devolve algo utilizável
        return {
            "linguagem": None,
            "resumo": texto_resposta,
            "vulnerabilidades": []
        }