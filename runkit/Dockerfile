FROM php:7.4-cli
RUN pecl install runkit7-4.0.0a2
RUN docker-php-ext-enable runkit7
COPY docker-php-ext-runkit7.ini /usr/local/etc/php/conf.d/docker-php-ext-runkit7.ini
