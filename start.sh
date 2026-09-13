#!/bin/bash
set -e

# Ajusta a porta do Apache pra bater com a que o Render define em tempo de execução
if [ -n "$PORT" ]; then
    sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf
    sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf
fi

# Sobe o serviço Python em segundo plano — só acessível de dentro do próprio container
cd /var/www/html/api
./venv/bin/uvicorn main:app --host 127.0.0.1 --port 8000 &

# Sobe o Apache em primeiro plano — é ele que mantém o container vivo
cd /var/www/html
exec apache2-foreground
