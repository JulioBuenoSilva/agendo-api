#!/bin/sh

# Se existir variável base64, recria o JSON
if [ -n "$FIREBASE_CREDENTIALS_BASE64" ]; then
  echo "$FIREBASE_CREDENTIALS_BASE64" | base64 -d > /var/www/storage/app/firebase/firebase-auth.json
fi

# Rodar migrations automaticamente no deploy
php artisan migrate --force

# Limpar caches antigos se necessário
php artisan cache:clear

exec "$@"