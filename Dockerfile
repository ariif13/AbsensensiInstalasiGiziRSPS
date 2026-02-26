FROM php:8.3-fpm
LABEL "language"="php"
LABEL "framework"="laravel"

WORKDIR /var/www

RUN apt-get update && apt-get install -y curl git unzip libpq-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev libicu-dev && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip bcmath intl exif

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN curl -fsSL https://bun.sh/install | bash
RUN /root/.bun/bin/bun install
RUN /root/.bun/bin/bun run build

RUN php artisan package:discover --ansi

RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www/storage && chmod -R 755 /var/www/bootstrap/cache

EXPOSE 8080

CMD sh -c "php artisan optimize:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"
