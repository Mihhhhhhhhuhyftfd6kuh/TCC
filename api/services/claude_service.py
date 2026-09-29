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

Você tem acesso ao histórico desta conversa (código e análises anteriores).
Use esse histórico para manter o contexto: se o usuário fizer uma pergunta de
acompanhamento (por exemplo, pedir para explicar melhor uma vulnerabilidade
apontada antes, ou comparar com um código enviado anteriormente), responda
levando em conta o que já foi dito.

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

Se a mensagem do usuário for uma pergunta e não um código novo, coloque a
resposta completa no campo "resumo", use "linguagem": null e deixe
"vulnerabilidades" vazio (ou liste apenas as que forem relevantes para a pergunta).
"""


def analisar_codigo(codigo: str, historico: list[dict] | None = None) -> dict:
    mensagens = []

    for m in historico or []:
        if m["content"].strip():
            mensagens.append({"role": m["role"], "content": m["content"]})

    # A API exige que a primeira mensagem seja do usuário
    while mensagens and mensagens[0]["role"] != "user":
        mensagens.pop(0)

    mensagens.append({"role": "user", "content": codigo})

    resposta = client.messages.create(
        model="claude-sonnet-5",
        max_tokens=4096,
        system=SYSTEM_PROMPT,
        messages=mensagens,
    )

    # A resposta pode ter blocos de "thinking" antes do texto:
    # pega só os blocos do tipo "text" e junta.
    texto_resposta = "".join(
        bloco.text for bloco in resposta.content if bloco.type == "text"
    ).strip()

    # Remove blocos de markdown, caso o modelo insista em incluir ```json
    texto_resposta = re.sub(r"^```(json)?|```$", "", texto_resposta, flags=re.MULTILINE).strip()

    try:
        return json.loads(texto_resposta)
    except json.JSONDecodeError:
        # Se por algum motivo não veio JSON válido, ainda assim devolve algo utilizável
        return {
            "linguagem": None,
            "resumo": texto_resposta,
            "vulnerabilidades": [],
        }