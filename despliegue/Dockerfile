# Imagen de Puntada para el VPS compartido. Se construye desde la raíz del repositorio con despliegue/docker-compose.yml.
# Por qué Docker y no el PHP del servidor: docs/04-especificacion-tecnica/07-despliegue-y-operacion.md

# 1. Dependencias de producción, sin las de desarrollo (PHPUnit, Pint, Larastan). Las extensiones se comprueban en la etapa 2.
FROM composer:2 AS dependencias
WORKDIR /app
COPY sistema/composer.json sistema/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --ignore-platform-reqs

# 2. PHP 8.4 con Apache: la versión con la que se prueba el sistema
FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" exif gd intl opcache pcntl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# upload_max_filesize alcanza para una foto del celular antes de reducirla; post_max_size, para las fotos de una orden (RNF-03)
RUN { \
        echo 'expose_php = Off'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /dev/stderr'; \
        echo 'memory_limit = 512M'; \
        echo 'upload_max_filesize = 10M'; \
        echo 'post_max_size = 64M'; \
        echo 'max_file_uploads = 30'; \
        echo 'date.timezone = America/Bogota'; \
        echo 'opcache.validate_timestamps = 0'; \
    } > /usr/local/etc/php/conf.d/taller.ini

# Apache sirve solo sistema/public
ENV APACHE_DOCUMENT_ROOT=/var/www/taller/sistema/public
# Las cabeceras de seguridad y el resto de la configuración propia, versionadas en despliegue/apache/taller.conf
COPY despliegue/apache/taller.conf /etc/apache2/conf-available/taller.conf
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enconf taller \
    && a2enmod rewrite headers

WORKDIR /var/www/taller
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=dependencias /app/vendor sistema/vendor
COPY sistema sistema

RUN cd sistema \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && composer check-platform-reqs --no-dev \
    && mkdir -p storage/app/privado storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY despliegue/entrypoint.sh /usr/local/bin/taller-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/taller-entrypoint && chmod +x /usr/local/bin/taller-entrypoint

WORKDIR /var/www/taller/sistema
EXPOSE 80
ENTRYPOINT ["taller-entrypoint"]
CMD ["apache2-foreground"]
