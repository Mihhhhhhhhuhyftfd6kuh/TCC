# Usa imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala dependências do PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev

# Instala extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Copia os arquivos do projeto para o servidor Apache
COPY . /var/www/html/

# Expõe a porta padrão do Apache
EXPOSE 80

# Inicia o Apache
CMD ["apache2-foreground"]