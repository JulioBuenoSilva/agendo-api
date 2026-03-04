#!/bin/sh

# Se existir variável base64, recria o JSON
if [ -n "$FIREBASE_CREDENTIALS_BASE64" ]; then
  echo "$FIREBASE_CREDENTIALS_BASE64" | base64 -d > /var/www/storage/app/firebase/firebase-auth.json
fi

# Rodar migrations automaticamente no deploy
php artisan migrate --force

php artisan config:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache

# Conecta o diretório de storage para servir arquivos públicos
php artisan storage:link || true

exec "$@"