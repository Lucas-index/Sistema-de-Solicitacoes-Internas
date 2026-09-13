FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8001

CMD ["sh", "-c", "if [ ! -f .env ]; then cp .env.example .env; fi && php artisan config:clear && php artisan key:generate --force && until php artisan migrate --force; do echo 'Aguardando o banco de dados...'; sleep 3; done && (php artisan storage:link || true) && php artisan serve --host=0.0.0.0 --port=8000"]
