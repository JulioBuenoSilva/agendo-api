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


# 1. Forçar a remoção do link antigo para não ter conflito
# O -f garante que não dê erro se o arquivo não existir
rm -f /var/www/public/storage

# 2. Recriar o link simbólico
# O Laravel vai ligar /var/www/public/storage -> /var/www/storage/app/public
php artisan storage:link --force || true

# 3. Garantir que as subpastas existam dentro do Volume
# Como o Volume vem vazio, o Laravel precisa de permissão para criar essas pastas
mkdir -p /var/www/storage/app/public/images/perfis
mkdir -p /var/www/storage/app/public/images/estabelecimentos
mkdir -p /var/www/storage/app/public/images/servicos

# 4. Ajustar as permissões para o usuário do servidor (www-data ou root)
# Sem isso, o upload via PHP vai dar "Permission Denied"
chmod -R 775 /var/www/storage/app/public/images
chown -R www-data:www-data /var/www/storage/app/public/images 2>/dev/null || true

exec "$@"