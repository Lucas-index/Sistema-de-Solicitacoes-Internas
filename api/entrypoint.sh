#!/bin/sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

sed -i "s/^ *DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
sed -i "s/^ *DB_PORT=.*/DB_PORT=${DB_PORT}/" .env
sed -i "s/^ *DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
sed -i "s/^ *DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
sed -i "s/^ *DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env

php artisan config:clear
php artisan key:generate --force

until php artisan migrate --force; do
  echo "Aguardando o banco de dados..."
  sleep 3
done

php artisan storage:link || true

php artisan serve --host=0.0.0.0 --port=8000