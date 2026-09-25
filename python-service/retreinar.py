"""
Retreina o modelo combinando o dataset original com as correções humanas exportadas.

Uso:
    python retreinar.py --versao=ticket-classifier-v2

Nunca ativa o modelo novo automaticamente — apenas salva os artefatos em
modelo/<versao>/ e registra as métricas em metrics_<versao>.json.
A ativação é feita manualmente via endpoint PATCH /modelo/ativar.
"""

import argparse
import json
import os
from datetime import datetime, timezone

import joblib
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.model_selection import train_test_split

parser = argparse.ArgumentParser()
parser.add_argument("--versao", default="ticket-classifier-v2")
args = parser.parse_args()

VERSAO = args.versao
PASTA_MODELO = f"modelo/{VERSAO}"
os.makedirs(PASTA_MODELO, exist_ok=True)

df_original = pd.read_csv("dataset_chamados.csv")
print(f"Dataset original: {len(df_original)} exemplos")

if os.path.exists("correcoes_exportadas.csv"):
    df_correcoes = pd.read_csv("correcoes_exportadas.csv")
    print(f"Correções humanas: {len(df_correcoes)} exemplos")
    df = pd.concat([df_original, df_correcoes], ignore_index=True)
else:
    print("Nenhuma correção encontrada — retreinando só com dataset original")
    df = df_original

df["texto"] = df["titulo"] + ". " + df["descricao"]
print(f"Total combinado: {len(df)} exemplos")

X_train, X_test, y_cat_train, y_cat_test, y_pri_train, y_pri_test = train_test_split(
    df["texto"], df["categoria"], df["prioridade"],
    test_size=0.2, random_state=42, stratify=df["categoria"],
)

vectorizer = TfidfVectorizer(max_features=3000, ngram_range=(1, 2))
X_train_vec = vectorizer.fit_transform(X_train)
X_test_vec = vectorizer.transform(X_test)

modelo_cat = LogisticRegression(max_iter=1000, C=150)
modelo_cat.fit(X_train_vec, y_cat_train)
pred_cat = modelo_cat.predict(X_test_vec)
acc_cat = accuracy_score(y_cat_test, pred_cat)

modelo_pri = LogisticRegression(max_iter=1000, C=150)
modelo_pri.fit(X_train_vec, y_pri_train)
pred_pri = modelo_pri.predict(X_test_vec)
acc_pri = accuracy_score(y_pri_test, pred_pri)

print(f"\nAccuracy categoria: {acc_cat:.3f}")
print(f"Accuracy prioridade: {acc_pri:.3f}")
print(classification_report(y_cat_test, pred_cat))

joblib.dump(vectorizer, f"{PASTA_MODELO}/vectorizer.joblib")
joblib.dump(modelo_cat, f"{PASTA_MODELO}/categoria_modelo.joblib")
joblib.dump(modelo_pri, f"{PASTA_MODELO}/prioridade_modelo.joblib")

metrics = {
    "model_version": VERSAO,
    "trained_at": datetime.now(timezone.utc).isoformat(),
    "dataset_size": len(df),
    "correcoes_incluidas": len(df) - len(df_original),
    "categoria": {
        "algorithm": "LogisticRegression",
        "accuracy": acc_cat,
        "classes": list(modelo_cat.classes_),
        "confusion_matrix": confusion_matrix(y_cat_test, pred_cat, labels=list(modelo_cat.classes_)).tolist(),
        "report": classification_report(y_cat_test, pred_cat, output_dict=True),
    },
    "prioridade": {
        "algorithm": "LogisticRegression",
        "accuracy": acc_pri,
        "classes": list(modelo_pri.classes_),
        "confusion_matrix": confusion_matrix(y_pri_test, pred_pri, labels=list(modelo_pri.classes_)).tolist(),
        "report": classification_report(y_pri_test, pred_pri, output_dict=True),
    },
}

with open(f"{PASTA_MODELO}/metrics.json", "w", encoding="utf-8") as f:
    json.dump(metrics, f, ensure_ascii=False, indent=2)

print(f"\nModelo '{VERSAO}' salvo em {PASTA_MODELO}/")
print("Para ativar: PATCH /modelo/ativar com {\"versao\": \"" + VERSAO + "\"}") 