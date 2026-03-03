#!/bin/bash
# Script de déploiement — Boost Manager
# Usage : bash deploy.sh
# À exécuter sur le serveur après chaque git pull

set -e

echo "=== Déploiement Boost Manager ==="

# 1. Pull dernières modifications
git pull origin main

# 2. Dépendances PHP
composer install --no-dev --optimize-autoloader

# 3. Migrations (sans wipe storage)
php artisan migrate --force

# 4. Vider les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Recréer le symlink storage (si absent)
if [ ! -L public/storage ]; then
    php artisan storage:link
    echo "Symlink storage recréé."
else
    echo "Symlink storage OK."
fi

# 6. S'assurer que le dossier avatars existe et est accessible
mkdir -p storage/app/public/avatars
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

echo "=== Déploiement terminé ==="
