"""
Treina os classificadores de categoria e prioridade a partir do dataset de chamados.

Uso:
    python treinar.py

Gera, na pasta modelo/:
    - vectorizer.joblib
    - categoria_modelo.joblib
    - prioridade_modelo.joblib
    - metrics.json

Este script NUNCA roda dentro do processo do FastAPI (main.py) — treino e
inferência ficam propositalmente separados. O main.py só carrega os arquivos
gerados aqui.
"""

import json
import os
from datetime import datetime, timezone

import joblib
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split
from sklearn.naive_bayes import MultinomialNB
from sklearn.metrics import classification_report, accuracy_score

MODEL_VERSION = "ticket-classifier-v1"
PASTA_MODELO = "modelo"

os.makedirs(PASTA_MODELO, exist_ok=True)

df = pd.read_csv("dataset_chamados.csv")
df["texto"] = df["titulo"] + ". " + df["descricao"]

print(f"Total de exemplos: {len(df)}")
print(df["categoria"].value_counts())
print(df["prioridade"].value_counts())

X_train, X_test, y_cat_train, y_cat_test, y_pri_train, y_pri_test = train_test_split(
    df["texto"], df["categoria"], df["prioridade"],
    test_size=0.2, random_state=42, stratify=df["categoria"],
)

vectorizer = TfidfVectorizer(max_features=3000, ngram_range=(1, 2))
X_train_vec = vectorizer.fit_transform(X_train)
X_test_vec = vectorizer.transform(X_test)

# --- Categoria: comparação entre Logistic Regression e Naive Bayes ---
modelo_cat_lr = LogisticRegression(max_iter=1000, C=150)
modelo_cat_lr.fit(X_train_vec, y_cat_train)
pred_cat_lr = modelo_cat_lr.predict(X_test_vec)
acc_cat_lr = accuracy_score(y_cat_test, pred_cat_lr)

modelo_cat_nb = MultinomialNB()
modelo_cat_nb.fit(X_train_vec, y_cat_train)
pred_cat_nb = modelo_cat_nb.predict(X_test_vec)
acc_cat_nb = accuracy_score(y_cat_test, pred_cat_nb)

print(f"\nAccuracy categoria — Logistic Regression: {acc_cat_lr:.3f}")
print(f"Accuracy categoria — Naive Bayes: {acc_cat_nb:.3f}")

# Escolhe o melhor dos dois para a categoria
if acc_cat_lr >= acc_cat_nb:
    modelo_categoria = modelo_cat_lr
    pred_categoria = pred_cat_lr
    algoritmo_categoria = "LogisticRegression"
else:
    modelo_categoria = modelo_cat_nb
    pred_categoria = pred_cat_nb
    algoritmo_categoria = "MultinomialNB"

relatorio_categoria = classification_report(y_cat_test, pred_categoria, output_dict=True)
print("\nRelatório de categoria (modelo escolhido: " + algoritmo_categoria + "):")
print(classification_report(y_cat_test, pred_categoria))

# --- Prioridade: mesmo processo ---
modelo_pri_lr = LogisticRegression(max_iter=1000, C=150)
modelo_pri_lr.fit(X_train_vec, y_pri_train)
pred_pri_lr = modelo_pri_lr.predict(X_test_vec)
acc_pri_lr = accuracy_score(y_pri_test, pred_pri_lr)

modelo_pri_nb = MultinomialNB()
modelo_pri_nb.fit(X_train_vec, y_pri_train)
pred_pri_nb = modelo_pri_nb.predict(X_test_vec)
acc_pri_nb = accuracy_score(y_pri_test, pred_pri_nb)

print(f"\nAccuracy prioridade — Logistic Regression: {acc_pri_lr:.3f}")
print(f"Accuracy prioridade — Naive Bayes: {acc_pri_nb:.3f}")

if acc_pri_lr >= acc_pri_nb:
    modelo_prioridade = modelo_pri_lr
    pred_prioridade = pred_pri_lr
    algoritmo_prioridade = "LogisticRegression"
else:
    modelo_prioridade = modelo_pri_nb
    pred_prioridade = pred_pri_nb
    algoritmo_prioridade = "MultinomialNB"

relatorio_prioridade = classification_report(y_pri_test, pred_prioridade, output_dict=True)
print("\nRelatório de prioridade (modelo escolhido: " + algoritmo_prioridade + "):")
print(classification_report(y_pri_test, pred_prioridade))

# --- Salvar artefatos ---
joblib.dump(vectorizer, f"{PASTA_MODELO}/vectorizer.joblib")
joblib.dump(modelo_categoria, f"{PASTA_MODELO}/categoria_modelo.joblib")
joblib.dump(modelo_prioridade, f"{PASTA_MODELO}/prioridade_modelo.joblib")

metrics = {
    "model_version": MODEL_VERSION,
    "trained_at": datetime.now(timezone.utc).isoformat(),
    "dataset_size": len(df),
    "categoria": {
        "algorithm": algoritmo_categoria,
        "accuracy": acc_cat_lr if algoritmo_categoria == "LogisticRegression" else acc_cat_nb,
        "report": relatorio_categoria,
    },
    "prioridade": {
        "algorithm": algoritmo_prioridade,
        "accuracy": acc_pri_lr if algoritmo_prioridade == "LogisticRegression" else acc_pri_nb,
        "report": relatorio_prioridade,
    },
}

with open(f"{PASTA_MODELO}/metrics.json", "w", encoding="utf-8") as f:
    json.dump(metrics, f, ensure_ascii=False, indent=2)

print(f"\nModelo '{MODEL_VERSION}' salvo em {PASTA_MODELO}/")
print("Treinamento concluído.")
