FROM php:8.2-apache

# Instalar dependencias del sistema + Composer
RUN apt-get update && apt-get install -y libpq-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar PHP limits para uploads grandes (200MB)
RUN echo "upload_max_filesize = 200M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 200M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 600" >> /usr/local/etc/php/conf.d/uploads.ini

# Copiar composer.json y composer.lock PRIMERO (para cache de capas)
COPY composer.json composer.lock* /var/www/html/

# Instalar dependencias PHP (cacheable si no cambian composer.json/lock)
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar el resto del proyecto
COPY . /var/www/html/

# Eliminar BOM (Byte Order Mark) de archivos PHP si existe
RUN find /var/www/html -type f -name "*.php" -exec sed -i '1s/^\xEF\xBB\xBF//' {} \;

# Dar permisos
RUN chown -R www-data:www-data /var/www/html