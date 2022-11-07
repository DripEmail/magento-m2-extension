FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_RUN_USER  www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR   /var/log/apache2
ENV APACHE_PID_FILE  /var/run/apache2/apache2.pid
ENV APACHE_RUN_DIR   /var/run/apache2
ENV APACHE_LOCK_DIR  /var/lock/apache2

RUN mkdir -p $APACHE_RUN_DIR $APACHE_LOCK_DIR $APACHE_LOG_DIR

RUN apt-get update && apt-get install -y software-properties-common git wget curl
RUN add-apt-repository ppa:ondrej/php
RUN add-apt-repository ppa:ondrej/apache2
RUN apt-get update && apt-get install -y apache2 unzip php8.1 php8.1-bcmath php8.1-gd php8.1-intl php8.1-pdo-mysql php8.1-soap php8.1-xsl php8.1-zip php8.1-curl php8.1-mbstring php8.1-dev php8.1-cli php8.1-xml composer
RUN a2enmod rewrite

# Temporary fix for Magento 2.4.4
# https://github.com/magento/magento2/issues/35325
COPY docker/magento_2.4.4_js_const.patch /

EXPOSE 80

# Set up Magento
RUN mkdir -p /var/www/html/magento
RUN chown -R www-data:www-data /var/www/html/magento
USER www-data
RUN git clone --depth 1 --branch 2.4.4 https://github.com/magento/magento2.git /var/www/html/magento
RUN cd /var/www/html/magento && patch -p1 < /magento_2.4.4_js_const.patch
RUN cd /var/www/html/magento && composer install

USER root

COPY docker/apache-vhost.conf /etc/apache2/sites-enabled/000-default.conf

CMD [ "/usr/sbin/apache2", "-DFOREGROUND" ]

# Make life a little nicer when we log into the container.
WORKDIR /var/www/html/magento
