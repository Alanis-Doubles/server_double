FROM php:8.1-apache

# Instala o Supervisor
RUN apt-get update && apt-get install -y supervisor

# Instala as extensões PHP necessárias: mysqli e pdo_mysql (para sessões em banco de dados)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilita a extensão pdo_mysql no PHP (já deve estar habilitada por padrão, mas para garantir)
RUN docker-php-ext-enable pdo_mysql

# Copia a configuração customizada do php.ini
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Copia a configuração do Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY atualizar_supervisor.sh /etc/supervisor/conf.d/atualizar_supervisor.sh

# Expõe a porta 80 (já está exposta por padrão na imagem php:apache, mas para clareza)
EXPOSE 80

# Define o diretório de trabalho padrão do Apache
WORKDIR /var/www/html

# Inicia o Supervisor para gerenciar o Apache
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
CMD ["chmod", "+x", "/etc/supervisor/conf.d/atualizar_supervisor.sh"]
