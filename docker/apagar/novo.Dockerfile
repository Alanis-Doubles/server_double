FROM debian:11
LABEL maintainer="bjverde@yahoo.com.br"

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=America/Sao_Paulo
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data

# Install update
RUN apt-get update && apt-get upgrade -y

# Install facilitators
RUN apt-get -y install locate mlocate wget apt-utils curl apt-transport-https lsb-release \
             ca-certificates software-properties-common zip unzip vim rpl apt-utils
# Fix add-apt-repository command not found
RUN apt-get install software-properties-common

## ------------- Install Apache2 + PHP 8.1  x86_64 ------------------
#Thread Safety      disabled
#PHP Modules : calendar,Core,ctype,date,exif,fileinfo,filter,ftp,gettext,hash,iconv,json,libxml
#PHP Modules : ,openssl,pcntl,pcre,PDO,Phar,posix,readline,Reflection,session,shmop,sockets,SPL,standard
#PHP Modules : ,sysvmsg,sysvsem,sysvshm,tokenizer,Zend OPcache,zlib

RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list

# Install update
RUN apt-get update

# Set Timezone
RUN ln -fs /usr/share/zoneinfo/${TZ} /etc/localtime \
    && apt-get update \
    && apt-get install -y --no-install-recommends tzdata \
    && dpkg-reconfigure --frontend noninteractive tzdata

# Install Apache + PHP
RUN apt-get -y install apache2 libapache2-mod-php8.1 php8.1 php8.1-cli php8.1-common php8.1-opcache

# PHP Install CURL
RUN apt-get -y install curl php8.1-curl

# PHP Install DOM, Json, XML e Zip
RUN apt-get -y install php8.1-dom php8.1-xml php8.1-zip php8.1-soap php8.1-intl php8.1-xsl

# PHP Install MbString
RUN apt-get -y install php8.1-mbstring

# PHP Install GD
RUN apt-get -y install php8.1-gd

# PHP Install PDO SqLite
RUN apt-get -y install php8.1-pdo php8.1-pdo-sqlite php8.1-sqlite3

# PHP Install PDO MySQL
RUN apt-get -y install php8.1-pdo php8.1-pdo-mysql php8.1-mysql

# PHP Install PDO PostGress
RUN apt-get -y install php8.1-pdo php8.1-pgsql

## -------- Config Apache ----------------
RUN a2dismod mpm_event
RUN a2dismod mpm_worker
RUN a2enmod mpm_prefork
RUN a2enmod rewrite
RUN a2enmod php8.1

# Enable SSL module and SSL site
RUN a2enmod ssl
RUN a2ensite default-ssl.conf

# Setting VHost for app.profitbot.digital
RUN sed -i 's/<VirtualHost _default_:443>/<VirtualHost _default_:443>\n    ServerName app.profitbot.digital\n    ServerAlias www.app.profitbot.digital\n    DocumentRoot \/var\/www\/html/' /etc/apache2/sites-available/default-ssl.conf

# Enable .htaccess reading
RUN LANG="en_US.UTF-8" rpl "AllowOverride None" "AllowOverride All" /etc/apache2/apache2.conf
# Set apache user and group
RUN sed -i "s/APACHE_RUN_USER=.*/APACHE_RUN_USER=${APACHE_RUN_USER}/" /etc/apache2/envvars && \
    sed -i "s/APACHE_RUN_GROUP=.*/APACHE_RUN_GROUP=${APACHE_RUN_GROUP}/" /etc/apache2/envvars


## ------------- LDAP ------------------
# PHP Install LDAP
RUN apt-get -y install php8.1-ldap

# Apache2 enable LDAP
RUN a2enmod authnz_ldap
RUN a2enmod ldap

## ------------- Add-ons ------------------
# Install GIT
RUN apt-get -y install git-core

# PHP Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# PHP Install PHPUnit
RUN wget -O /usr/local/bin/phpunit-9.phar https://phar.phpunit.de/phpunit-9.phar; chmod +x /usr/local/bin/phpunit-9.phar; \
    ln -s /usr/local/bin/phpunit-9.phar /usr/local/bin/phpunit

##------------ Install Precondition for Drive SQL Server -----------
RUN apt-get -y install php8.1-dev php8.1-xml php8.1-intl

ENV ACCEPT_EULA=Y

RUN curl -s https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl -s https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list

RUN apt-get update

RUN apt-get install -y --no-install-recommends \
        locales \
        apt-transport-https \
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen

# install MS ODBC 17
RUN apt-get -y --no-install-recommends install msodbcsql17 mssql-tools

RUN echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile
RUN echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc
RUN /bin/bash -c "source ~/.bashrc"

RUN apt-get -y install unixodbc unixodbc-dev
RUN apt-get -y install gcc g++ make autoconf libc-dev pkg-config

##------------ Install Drive 5.10.0 for SQL Server -----------
RUN pecl install sqlsrv-5.10.0
RUN pecl install pdo_sqlsrv-5.10.0

RUN echo "extension=pdo_sqlsrv.so" >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/30-pdo_sqlsrv.ini
RUN echo "extension=sqlsrv.so"   >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/20-sqlsrv.ini

RUN echo "extension=pdo_sqlsrv.so" >> /etc/php/8.1/apache2/conf.d/30-pdo_sqlsrv.ini
RUN echo "extension=sqlsrv.so"   >> /etc/php/8.1/apache2/conf.d/20-sqlsrv.ini

# PHP Install Mongodb ext
RUN apt-get -y install php8.1-mongodb

##------------ Config security settings Apache -----------
RUN apt-get -y install libapache2-mod-evasive
RUN a2enmod evasive
RUN rm /etc/apache2/mods-enabled/evasive.conf

RUN echo '<IfModule mod_evasive20.c>'                >> /etc/apache2/mods-enabled/evasive.conf \
    && echo '  DOSHashTableSize 2048'                >> /etc/apache2/mods-enabled/evasive.conf \
    && echo '  DOSPageCount 10'                      >> /etc/apache2/mods-enabled/evasive.conf \
    && echo '  DOSSiteCount 200'                     >> /etc/apache2/mods-enabled/evasive.conf \
    && echo '  DOSPageInterval 2'                    >> /etc/apache2/mods-enabled/evasive.conf \
    && echo '  DOSSiteInterval 2'                    >> /etc/apache2/mods-enabled/evasive.conf \
    && echo '  DOSBlockingPeriod 10'                 >> /etc/apache2/mods-enabled/evasive.conf \
    && echo '  DOSLogDir "/var/log/apache2/evasive"' >> /etc/apache2/mods-enabled/evasive.conf \
    && echo '</IfModule>'                            >> /etc/apache2/mods-enabled/evasive.conf


##------------ Install supervisor -----------
RUN apt-get -y install supervisor
RUN mkdir -p /var/log/supervisor

# copy index.php
COPY index.php /var/www/html/index.php

## ------------- Finishing ------------------
RUN apt-get clean

# Creating index of files
RUN updatedb
# Set apache user and group
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
EXPOSE 443

# Copy entrypoint script
COPY ./entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Start Apache and Supervisor
CMD ["/entrypoint.sh"]
