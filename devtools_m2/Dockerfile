FROM php:8.4-apache-bookworm

# Avoid apt `composer` (pulls default php-cli and breaks PHP 8.4); use official Composer binary.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV APACHE_PID_FILE=/var/run/apache2/apache2.pid
ENV APACHE_RUN_DIR=/var/run/apache2
ENV APACHE_LOCK_DIR=/var/lock/apache2

RUN mkdir -p $APACHE_RUN_DIR $APACHE_LOCK_DIR $APACHE_LOG_DIR

# Extensions aligned with typical Magento / Adobe Commerce requirements (no Ubuntu PPA).
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates git wget curl unzip \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
    libicu-dev libxml2-dev libxslt1-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath ftp gd intl mysqli pdo_mysql soap xsl zip sockets opcache \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Temporary fix for Magento 2.4.4
# https://github.com/magento/magento2/issues/35325
#COPY docker/magento_2.4.4_js_const.patch /

EXPOSE 80

RUN mkdir -p /var/www/html/magento && chown -R www-data:www-data /var/www/html/magento

USER www-data
RUN git clone --depth 1 --branch 2.4.8 https://github.com/magento/magento2.git /var/www/html/magento
# RUN cd /var/www/html/magento && patch -p1 < /magento_2.4.4_js_const.patch
RUN cd /var/www/html/magento && composer install --no-interaction --prefer-dist

USER root

COPY docker/apache-vhost.conf /etc/apache2/sites-enabled/000-default.conf
COPY docker/php-magento.ini /usr/local/etc/php/conf.d/zz-magento.ini

CMD ["apache2-foreground"]

WORKDIR /var/www/html/magento
