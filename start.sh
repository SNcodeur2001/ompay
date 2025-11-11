#!/bin/bash

echo "🌐 Starting deployment script..."

# Installer les dépendances si nécessaire
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Vérifier si on est en production
if [ "$APP_ENV" != "production" ]; then
    # Créer .env en local si inexistant
    if [ ! -f ".env" ]; then
        echo "Creating .env file..."
        cp .env.example .env
    fi
fi

# Générer la clé de l'application si nécessaire
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Installer les clés Passport si nécessaires
if [ ! -f "app/secrets/oauth/oauth-private.key" ]; then
    echo "Installing Passport keys..."
    php artisan passport:install --force
fi

# Définir les permissions sur les clés Passport
chmod 600 app/secrets/oauth/oauth-private.key
chmod 600 app/secrets/oauth/oauth-public.key

# Exécuter les migrations
echo "Running database migrations..."
php artisan migrate --force

# Seeders seulement si la base est vide
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force
else
    echo "Database already seeded, skipping..."
fi

# Clear & cache Laravel config/routes/views
echo "Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan view:clear
php artisan view:cache

# Scheduler
echo "Starting Laravel scheduler..."
php artisan schedule:work &

# Démarrer le serveur principal
echo "🌐 Starting main process..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-9000}
