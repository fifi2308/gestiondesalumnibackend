FROM php:8.2-fpm

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring gd

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dossier de travail
WORKDIR /var/www

# Copier tout le projet Laravel
COPY . .

# Remplacer le .env par .env.docker
RUN rm -f .env && cp .env.docker .env

# Installer dépendances PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Donner droits corrects
RUN chmod -R 777 storage bootstrap/cache

# Vider le cache
RUN php artisan config:clear || true
RUN php artisan cache:clear || true

# Exposer le port de Laravel
EXPOSE 9000

# Commande de démarrage
CMD php artisan config:clear && php artisan cache:clear && php artisan serve --host=0.0.0.0 --port=9000