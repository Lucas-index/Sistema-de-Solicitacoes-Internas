import time
import joblib
from pydantic import BaseModel
import os
import re
from collections import defaultdict
from datetime import datetime
from fastapi.middleware.cors import CORSMiddleware

from dotenv import load_dotenv
from fastapi import FastAPI, Header, HTTPException

from database import get_connection

import io
import pandas as pd
from fastapi.responses import StreamingResponse

load_dotenv()

app = FastAPI(title="Relatórios - Sistema de Solicitações Internas")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:5173", "http://127.0.0.1:5173"],
    allow_methods=["*"],
    allow_headers=["*"],
)

MODEL_VERSION = "ticket-classifier-v1"

vectorizer = joblib.load("modelo/vectorizer.joblib")
modelo_categoria = joblib.load("modelo/categoria_modelo.joblib")
modelo_prioridade = joblib.load("modelo/prioridade_modelo.joblib")


class ChamadoParaClassificar(BaseModel):
    ticket_id: int
    title: str
    description: str


STOPWORDS = {
    "de", "da", "do", "das", "dos", "a", "o", "as", "os", "um", "uma",
    "com", "para", "por", "em", "no", "na", "nos", "nas", "e", "que",
    "está", "esta", "foi", "ser", "não", "sem", "novo", "nova",
    "solicitação", "solicito", "preciso", "quebrou", "quebrado", "quebrada",
    "defeito", "problema", "com", "meu", "minha",
}


def verificar_api_key(x_api_key: str = Header(...)):
    if x_api_key != os.getenv("SERVICE_API_KEY"):
        raise HTTPException(status_code=401, detail="Chave de API inválida")


@app.get("/relatorios/tempo-medio")
def tempo_medio_resolucao(x_api_key: str = Header(...)):
    verificar_api_key(x_api_key)

    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT
                    c.nome AS categoria,
                    s.id AS solicitacao_id,
                    s.created_at AS criado_em,
                    h.created_at AS concluido_em
                FROM solicitacoes s
                JOIN categorias c ON c.id = s.categoria_id
                JOIN historico_status h ON h.solicitacao_id = s.id AND h.status_novo = 'concluida'
            """)
            linhas = cursor.fetchall()
    finally:
        conn.close()

    por_categoria = defaultdict(list)
    for linha in linhas:
        horas = (linha["concluido_em"] - linha["criado_em"]).total_seconds() / 3600
        por_categoria[linha["categoria"]].append(horas)

    resultado = []
    for categoria, tempos in por_categoria.items():
        resultado.append({
            "categoria": categoria,
            "total_concluidos": len(tempos),
            "tempo_medio_horas": round(sum(tempos) / len(tempos), 1),
        })

    return sorted(resultado, key=lambda x: x["tempo_medio_horas"], reverse=True)


@app.get("/relatorios/volume")
def volume_por_setor_status(x_api_key: str = Header(...)):
    verificar_api_key(x_api_key)

    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT
                    st.nome AS setor,
                    s.status AS status,
                    COUNT(*) AS total
                FROM solicitacoes s
                JOIN categorias c ON c.id = s.categoria_id
                JOIN setores st ON st.id = c.setor_responsavel_id
                GROUP BY st.nome, s.status
                ORDER BY st.nome, total DESC
            """)
            return cursor.fetchall()
    finally:
        conn.close()


@app.get("/relatorios/recorrencia")
def recorrencia_e_deterioracao(x_api_key: str = Header(...)):
    verificar_api_key(x_api_key)

    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT titulo, created_at FROM solicitacoes")
            linhas = cursor.fetchall()
    finally:
        conn.close()

    ocorrencias_por_palavra = defaultdict(list)

    for linha in linhas:
        palavras = re.findall(r"[a-zà-ú]+", linha["titulo"].lower())
        palavras_relevantes = {p for p in palavras if p not in STOPWORDS and len(p) > 3}
        for palavra in palavras_relevantes:
            ocorrencias_por_palavra[palavra].append(linha["created_at"])

    resultado = []
    for palavra, datas in ocorrencias_por_palavra.items():
        if len(datas) < 2:
            continue

        datas_ordenadas = sorted(datas)
        intervalos_dias = [
            (datas_ordenadas[i] - datas_ordenadas[i - 1]).total_seconds() / 86400
            for i in range(1, len(datas_ordenadas))
        ]
        media_intervalo = sum(intervalos_dias) / len(intervalos_dias)

        resultado.append({
            "item": palavra,
            "ocorrencias": len(datas),
            "media_dias_entre_ocorrencias": round(media_intervalo, 1),
            "primeira_ocorrencia": datas_ordenadas[0].isoformat(),
            "ultima_ocorrencia": datas_ordenadas[-1].isoformat(),
        })

    return sorted(resultado, key=lambda x: x["ocorrencias"], reverse=True)


@app.get("/relatorios/exportar-excel")
def exportar_excel(x_api_key: str = Header(...)):
    verificar_api_key(x_api_key)

    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT
                    s.id, s.titulo, s.status, s.prioridade,
                    c.nome AS categoria, st.nome AS setor,
                    u.name AS solicitante, s.created_at, s.updated_at
                FROM solicitacoes s
                JOIN categorias c ON c.id = s.categoria_id
                JOIN setores st ON st.id = c.setor_responsavel_id
                JOIN users u ON u.id = s.usuario_id
            """)
            chamados = cursor.fetchall()
    finally:
        conn.close()

    df_chamados = pd.DataFrame(chamados)
    df_tempo_medio = pd.DataFrame(tempo_medio_resolucao(x_api_key))
    df_volume = pd.DataFrame(volume_por_setor_status(x_api_key))
    df_recorrencia = pd.DataFrame(recorrencia_e_deterioracao(x_api_key))

    buffer = io.BytesIO()
    with pd.ExcelWriter(buffer, engine="openpyxl") as writer:
        df_chamados.to_excel(writer, sheet_name="Chamados", index=False)
        df_tempo_medio.to_excel(writer, sheet_name="Tempo medio", index=False)
        df_volume.to_excel(writer, sheet_name="Volume", index=False)
        df_recorrencia.to_excel(writer, sheet_name="Recorrencia", index=False)
    buffer.seek(0)

    return StreamingResponse(
        buffer,
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={"Content-Disposition": "attachment; filename=relatorio_solicitacoes.xlsx"},
    )


@app.post("/classificar")
def classificar(dados: ChamadoParaClassificar, x_api_key: str = Header(...)):
    verificar_api_key(x_api_key)

    inicio = time.perf_counter()

    texto = f"{dados.title}. {dados.description}"
    texto_vetorizado = vectorizer.transform([texto])

    categoria_prevista = modelo_categoria.predict(texto_vetorizado)[0]
    categoria_confianca = max(modelo_categoria.predict_proba(texto_vetorizado)[0])

    prioridade_prevista = modelo_prioridade.predict(texto_vetorizado)[0]
    prioridade_confianca = max(modelo_prioridade.predict_proba(texto_vetorizado)[0])

    tempo_processamento_ms = round((time.perf_counter() - inicio) * 1000)

    return {
        "category": categoria_prevista,
        "priority": prioridade_prevista,
        "category_confidence": round(float(categoria_confianca), 3),
        "priority_confidence": round(float(prioridade_confianca), 3),
        "model_version": MODEL_VERSION,
        "processing_time_ms": tempo_processamento_ms,
    }