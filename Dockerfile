# Camagru — image web (PHP 8.3 + Apache)
FROM php:8.3-apache

# Extensions : GD (superposition d'images) + pdo_mysql + ssmtp (mail() → MailHog)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libonig-dev \
        ssmtp \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Apache : DocumentRoot → public/ (front controller)
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# PHP : config applicative
COPY docker/php/camagru.ini /usr/local/etc/php/conf.d/camagru.ini

# Mail : envoyer via MailHog (dev)
COPY docker/ssmtp/ssmtp.conf /etc/ssmtp/ssmtp.conf

RUN a2enmod rewrite

WORKDIR /var/www/html

# Dossier d'uploads : doit être inscriptible par www-data
# (recréé/chmodé aussi au démarrage dans docker-compose, car le
# volume monté depuis l'hôte peut appartenir à un autre utilisateur).
RUN mkdir -p /var/www/html/public/uploads \
    && chmod 777 /var/www/html/public/uploads
