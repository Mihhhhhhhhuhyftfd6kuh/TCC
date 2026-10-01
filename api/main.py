from fastapi import FastAPI
from pydantic import BaseModel
from typing import Optional
from services.claude_service import analisar_codigo

app = FastAPI()

class AnaliseRequest(BaseModel):
    texto: str = ""
    arquivo_conteudo: Optional[str] = None
    arquivo_nome: Optional[str] = None
    historico: list = []

@app.get("/")
def home():
    return {"status": "ok"}

@app.post("/analisar")
def analisar(request: AnaliseRequest):
    resultado = analisar_codigo(
        request.texto,
        request.arquivo_conteudo,
        request.arquivo_nome,
        request.historico,
    )
    return {"resultado": resultado}