FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libicu-dev \
        libzip-dev \
        libxml2-dev \
        libsodium-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd intl zip mysqli pdo_mysql soap exif opcache sodium \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Extensions marked "required" for Moodle 5.2 in public/admin/environment.xml.
RUN php -r '$missing = array_filter(["iconv","mbstring","curl","openssl","ctype","zip","zlib","gd","simplexml","spl","pcre","dom","xml","xmlreader","intl","json","hash","fileinfo","sodium","filter","mysqli"], fn($e) => !extension_loaded($e)); if ($missing) { fwrite(STDERR, "Missing PHP extensions: " . implode(", ", $missing) . "\n"); exit(1); }' \
    && command -v setpriv \
    && command -v curl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/moodle.ini
COPY docker/entrypoint.sh /usr/local/bin/moodle-entrypoint
RUN chmod +x /usr/local/bin/moodle-entrypoint

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-autoloader --no-scripts

COPY --chown=www-data:www-data . /var/www/html
COPY --chown=www-data:www-data docker/moodle-config.php /var/www/html/config.php

RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && mkdir -p /var/moodledata /var/cache/moodle-local \
    && chown www-data:www-data /var/moodledata /var/cache/moodle-local

EXPOSE 80
ENTRYPOINT ["moodle-entrypoint"]
