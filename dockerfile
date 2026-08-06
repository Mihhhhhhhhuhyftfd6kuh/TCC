FROM php:8.2-apache

# Instala extensões necessárias (pgsql, pdo_pgsql etc)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia os arquivos do projeto
WORKDIR /var/www/html
COPY . .

# Instala as dependências do PHP (isso cria a pasta vendor/)
RUN composer install --no-dev --optimize-autoloader

# Configuração do Apache (se necessário)
# ...