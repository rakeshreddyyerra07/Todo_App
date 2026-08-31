FROM php:8.3-apache

# The application uses MySQLi for its database connection.
RUN docker-php-ext-install mysqli pdo_mysql

COPY . /var/www/html/

WORKDIR /var/www/html

RUN a2enmod rewrite

EXPOSE 80
