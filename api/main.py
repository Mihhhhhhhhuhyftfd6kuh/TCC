from typing import Literal

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

from services.claude_service import analisar_codigo

app = FastAPI()


class Mensagem(BaseModel):
    role: Literal["user", "assistant"]
    content: str


class CodigoRequest(BaseModel):
    codigo: str
    historico: list[Mensagem] = []


@app.get("/")
def home():
    return {"status": "ok"}


@app.post("/analisar")
def analisar(request: CodigoRequest):
    try:
        resultado = analisar_codigo(
            request.codigo,
            [m.model_dump() for m in request.historico],
        )
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"Falha ao analisar: {e}")

    return {"resultado": resultado}