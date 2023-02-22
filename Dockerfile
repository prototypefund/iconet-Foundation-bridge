FROM ubuntu:latest

# Environment variable necessary to not be prompted during setup
ENV DEBIAN_FRONTEND="noninteractive" TZ="Etc/UTC"

RUN \
    apt update && \
    apt upgrade -y && \
    apt install -y apache2 mysql-server composer php php-mysql php-dom php-curl curl

WORKDIR /var/www/bridge

COPY docker/apache2.conf /etc/apache2/sites-available/bridge.conf

RUN \
    a2enmod rewrite && \
    a2enmod headers && \
    a2dissite 000-default.conf && \
    a2ensite bridge.conf

RUN  \
    service mysql start && \
    mysql -e "CREATE USER 'bridge'@'localhost' IDENTIFIED BY 'j@cVPeXEOR6P)j8-'; GRANT ALL ON *.* TO 'bridge'@'localhost' WITH GRANT OPTION;" && \
    mysql -e "CREATE DATABASE bridge;"

# To execute when running docker run.
ENTRYPOINT \
    cp .env.docker .env && \
    service apache2 start && \
    service mysql start && \
    composer install && \
    php artisan migrate && \
    echo "SUCCESS! Traefik will redirect https://bridge.localhost to this container" && \
    /bin/bash

EXPOSE 80 8001
