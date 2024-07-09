FROM docker.io/bitnami/laravel:latest

USER root
RUN install_packages libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql pgsql

USER 1001

COPY . .
RUN composer install
CMD php artisan serve --host=0.0.0.0
EXPOSE 443
