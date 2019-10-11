FROM ubuntu:19.04

ENV DEBIAN_FRONTEND=noninteractive

# https://itsecureadmin.com/2018/03/running-apache-2-under-ubuntu-16-04-on-docker/
ENV APACHE_RUN_USER  www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR   /var/log/apache2
ENV APACHE_PID_FILE  /var/run/apache2/apache2.pid
ENV APACHE_RUN_DIR   /var/run/apache2
ENV APACHE_LOCK_DIR  /var/lock/apache2
RUN mkdir -p $APACHE_RUN_DIR $APACHE_LOCK_DIR $APACHE_LOG_DIR

RUN apt-get update && apt-get install -y software-properties-common git wget
RUN add-apt-repository ppa:ondrej/php
RUN add-apt-repository ppa:ondrej/apache2
RUN apt-get update && apt-get install -y apache2 unzip php7.2 php7.2-bcmath php7.2-gd php7.2-intl php7.2-pdo-mysql php7.2-soap php7.2-xsl php7.2-zip php7.2-curl php7.2-mbstring
RUN wget -O /usr/bin/composer https://getcomposer.org/download/1.9.0/composer.phar && chmod +x /usr/bin/composer
RUN a2enmod rewrite


RUN echo "ErrorLog /dev/stderr\nCustomLog /dev/stdout combined\n<VirtualHost *:80>\nServerAdmin webmaster@localhost\nDocumentRoot /var/www/html/magento\n<Directory /var/www/html/magento>\nAllowOverride All\n</Directory>\n</VirtualHost>" > /etc/apache2/sites-enabled/000-default.conf

EXPOSE 80

# Set up Magento
RUN mkdir -p /var/www/html/magento
RUN chown -R www-data:www-data /var/www/html/magento
USER www-data
RUN git clone --depth 1 --branch 2.3.2 https://github.com/magento/magento2.git /var/www/html/magento
RUN cd /var/www/html/magento && composer install

USER root
CMD [ "/usr/sbin/apache2", "-DFOREGROUND" ]

# Make life a little nicer when we log into the container.
WORKDIR /var/www/html/magento
