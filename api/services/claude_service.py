import os
from anthropic import Anthropic
from dotenv import load_dotenv

load_dotenv()

client = Anthropic(api_key=os.getenv("ANTHROPIC_API_KEY"))

SYSTEM_PROMPT = """
Você é um assistente de segurança que analisa trechos de codigos em busca de
vulnerabilidades comuns (SQL Injection, XSS, falhas de autenticação, etc).
Para cada problema encontrado, explique de forma didática por que ele é uma
vulnerabilidade e sugira a correção, sem reescrever o código inteiro sem explicação.
Alem disso, você deve sugerir boas práticas de segurança para o código analisado.
"""

def analisar_codigo(texto: str, arquivo: str) -> str:
    resposta = client.messages.create(
        model="claude-sonnet-5",
        max_tokens=1024,
        system=SYSTEM_PROMPT,
        messages=[
            {"role": "user", "content": [
                    {"type": "text", "text": texto},
                    {"type": "file", "source": {"type": "path", "path": arquivo}}
                ]}
        ]
    )
    return resposta.content[0].text