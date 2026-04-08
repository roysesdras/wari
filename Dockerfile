FROM php:8.2-apache

# Installation des dépendances système (pour intl et gmp)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libgmp-dev \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql intl bcmath gmp

# Configuration Apache
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite