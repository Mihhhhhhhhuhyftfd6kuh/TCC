FROM php:8.2-apache

# Instala dependências do sistema, incluindo unzip (necessário pro Composer)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Instala as dependências do PHP (isso cria a pasta vendor/)
RUN composer install --no-dev --optimize-autoloader

# Configuração do Apache (se necessário)