FROM php:8.2-apache

# Dependências de sistema: unzip/libzip pro Composer e pra extensão zip do PHP,
# python3 + venv pro serviço de IA — tudo na mesma imagem
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    python3 \
    python3-venv \
    python3-pip \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Instala as dependências do PHP (cria a pasta vendor/)
RUN composer install --no-dev --optimize-autoloader

# Cria o ambiente virtual do Python e instala as dependências dentro dele
RUN python3 -m venv /var/www/html/api/venv \
    && /var/www/html/api/venv/bin/pip install --no-cache-dir -r /var/www/html/api/requirements.txt

# O PHP fala com o Python sempre por localhost, já que os dois vivem no mesmo container
ENV IA_API_URL=http://localhost:8000/analisar

# Script que sobe os dois processos juntos na hora do container iniciar
COPY start.sh /start.sh
RUN sed -i 's/\r$//' /start.sh && chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]