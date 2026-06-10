FROM php:8.2-cli

WORKDIR /app

COPY composer.json ./
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer && rm composer-setup.php
RUN apt-get update && apt-get install -y zip unzip libzip-dev && docker-php-ext-install zip && rm -rf /var/lib/apt/lists/*
RUN composer install --no-interaction --prefer-dist

COPY . /app

CMD ["php", "public/index.php"]
