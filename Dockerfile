FROM php:8.3.1-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd intl opcache

# Installation de l'extension MongoDB (pour plus tard)
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration PHP pour Symfony
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini \
    && echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/docker-php-uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/docker-php-uploads.ini

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . .

# Installer les dépendances Symfony
RUN composer install --no-interaction --optimize-autoloader --no-scripts

# Permissions
RUN chown -R www-data:www-data /var/www/html/var \
    && mkdir -p /var/www/html/public/uploads/avatars \
    && chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 9000

CMD ["php-fpm"]