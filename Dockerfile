FROM docker.io/bitnami/laravel:latest

COPY . .
RUN composer install
CMD php artisan serve --host=0.0.0.0
EXPOSE 443
