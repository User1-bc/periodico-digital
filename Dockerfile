FROM php:8.2-apache

# Instalar extensiones necesarias para PostgreSQL y PDO
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar los archivos del proyecto al servidor web de Apache
COPY . /var/www/html/

# Eliminar BOM (Byte Order Mark) de archivos PHP si existe
RUN find /var/www/html -type f -name "*.php" -exec sed -i '1s/^\xEF\xBB\xBF//' {} \;

# Dar permisos
RUN chown -R www-data:www-data /var/www/html