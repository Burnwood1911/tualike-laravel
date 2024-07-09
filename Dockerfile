FROM docker.io/bitnami/laravel:latest

USER root
RUN install_packages libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql

# Switch back to the non-root user
USER 1001


COPY . .
RUN composer install
CMD php artisan serve --host=0.0.0.0
EXPOSE 443
