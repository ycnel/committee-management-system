FROM php:8.2-apache

# PHP extensions + Apache modules the app uses (expires/deflate/filter power .htaccess)
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev unzip mariadb-server \
    && docker-php-ext-install pdo_mysql mysqli opcache zip \
    && a2enmod rewrite expires deflate filter headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

# Production PHP + OPcache; .htaccess honored under the docroot
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT\ndisplay_errors = Off\nlog_errors = On\nopcache.enable=1\nopcache.memory_consumption=192\n' > "$PHP_INI_DIR/conf.d/cmas.ini" \
    && printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/cmas.conf \
    && a2enconf cmas \
    && chown -R www-data:www-data /var/www/html

COPY database/committee_management_db.sql database/migration_perf_indexes.sql /docker-db/

COPY docker-entrypoint.sh /usr/local/bin/cmas-entrypoint
RUN chmod +x /usr/local/bin/cmas-entrypoint

EXPOSE 80
ENTRYPOINT ["cmas-entrypoint"]
CMD ["apache2-foreground"]
