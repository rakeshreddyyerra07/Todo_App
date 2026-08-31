FROM php:8.3-apache

# The application uses MySQLi for its database connection.
RUN docker-php-ext-install mysqli pdo_mysql

COPY . /var/www/html/

WORKDIR /var/www/html

# PHP's Apache module requires prefork; ensure it is the only active MPM.
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite

EXPOSE 80
