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

def analisar_codigo(texto: str, arquivo_conteudo: str | None = None, arquivo_nome: str | None = None) -> dict:
    partes = []
    if texto:
        partes.append(texto)
    if arquivo_conteudo:
        partes.append(f"\n\nArquivo enviado ({arquivo_nome}):\n{arquivo_conteudo}")

    conteudo_completo = "\n".join(partes)

    resposta = client.messages.create(
        model="claude-sonnet-5",
        max_tokens=1500,
        system=SYSTEM_PROMPT,
        messages=[{"role": "user", "content": conteudo_completo}]
    )

    texto_resposta = resposta.content[0].text.strip()

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