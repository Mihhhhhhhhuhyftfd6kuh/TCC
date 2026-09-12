from fastapi import FastAPI
from pydantic import BaseModel
from services.claude_service import analisar_codigo

app = FastAPI()

class CodigoRequest(BaseModel):
    codigo: str

@app.get("/")
def home():
    return {"status": "ok"}

@app.post("/analisar")
def analisar(request: CodigoRequest):
    resultado = analisar_codigo(request.codigo)
    return {"resultado": resultado}