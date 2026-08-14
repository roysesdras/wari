FROM php:8.2-apache

# Installation des dépendances système (pour intl, gmp et ModSecurity WAF)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libgmp-dev \
    libapache2-mod-security2 \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql intl bcmath gmp

# Configuration Apache & ModSecurity WAF
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf 2>/dev/null || true \
    && sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/g' /etc/modsecurity/modsecurity.conf 2>/dev/null || true \
    && a2enmod rewrite headers security2