import os
import re
from collections import defaultdict
from datetime import datetime
from fastapi.middleware.cors import CORSMiddleware

from dotenv import load_dotenv
from fastapi import FastAPI, Header, HTTPException

from database import get_connection

load_dotenv()

app = FastAPI(title="Relatórios - Sistema de Solicitações Internas")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:5173", "http://127.0.0.1:5173"],
    allow_methods=["*"],
    allow_headers=["*"],
)

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