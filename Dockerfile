FROM docker.io/bitnami/laravel:latest

USER root
RUN install_packages libpq-dev php-dev
RUN pecl install pdo_pgsql pgsql
RUN echo "extension=pdo_pgsql.so" > /opt/bitnami/php/etc/php.ini
RUN echo "extension=pgsql.so" >> /opt/bitnami/php/etc/php.ini

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*


COPY . .
RUN composer install
CMD php artisan serve --host=0.0.0.0
EXPOSE 443
