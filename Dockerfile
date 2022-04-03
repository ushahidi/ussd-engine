FROM ushahidi/php-fpm-nginx:php-7.4

WORKDIR /var/www
ADD composer.* ./
RUN composer install --no-autoloader --no-scripts

COPY . .

COPY docker/run.sh /run.sh
RUN $DOCKERCES_MANAGE_UTIL add /run.sh

ENV VHOST_ROOT=/var/www/public \
    VHOST_INDEX=index.php \
    PHP_EXEC_TIME_LIMIT=60 \
    ENABLE_NGINX=true \
    ENABLE_PHPFPM=true
