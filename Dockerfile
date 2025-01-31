FROM php:apache

RUN apt-get update
RUN apt-get install -y libpq-dev
RUN docker-php-ext-install pgsql pdo_pgsql pdo

COPY ./src/* /var/www/html
