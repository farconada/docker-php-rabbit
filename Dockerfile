FROM php:7.2-zts
RUN docker-php-ext-install bcmath  && docker-php-ext-enable bcmath
