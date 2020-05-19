FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive
ARG CONTINUOUS_INTEGRATION

RUN apt update && apt install -y software-properties-common git wget curl gnupg2 ca-certificates lsb-release vim nginx fcgiwrap unzip
RUN add-apt-repository ppa:ondrej/php
RUN apt install -y php7.2 php7.2-fpm php7.2-bcmath php7.2-gd php7.2-intl php7.2-pdo-mysql php7.2-soap php7.2-xsl php7.2-zip php7.2-curl php7.2-mbstring php7.2-dev php7.2-cli php7.2-xml composer
RUN test "true" = "${CONTINUOUS_INTEGRATION}" && echo "skipping install of php-xdebug" || pecl install xdebug
RUN apt purge -y apache2 && apt autoremove
RUN service nginx stop;service php7.2-fpm stop;echo "nginx and php-fpm stopped"
# https://github.com/nginxinc/docker-nginx/blob/8921999083def7ba43a06fabd5f80e4406651353/mainline/jessie/Dockerfile#L21-L23
RUN ln -sf /dev/stdout /var/log/nginx/magento-access.log && ln -sf /dev/stderr /var/log/nginx/magento-error.log

# Configure nginx and php
ADD nginx*magento /etc/nginx/sites-available/magento
ADD --chown=root nginx*startup /startup.sh
COPY php*opcache.ini /etc/php/7.2/mods-available/opcache.patch
COPY nginx*fpm_patch /etc/php/7.2/fpm/pool.d/www.conf.patch
COPY php*xdebug.ini /etc/php/7.2/mods-available/xdebug.ini
COPY nginx*nginx.patch /etc/nginx/nginx.patch
RUN rm -f /etc/php/7.2/fpm/conf.d/20-xdebug.ini;test "true" = "${CONTINUOUS_INTEGRATION}" && echo "skipping php-xdebug configuration" || ln -s /etc/php/7.2/mods-available/xdebug.ini /etc/php/7.2/fpm/conf.d/20-xdebug.ini
RUN ln -s /etc/nginx/sites-available/magento /etc/nginx/sites-enabled/magento
RUN cd /etc/php/7.2/mods-available && /usr/bin/patch < opcache.patch && rm opcache.patch && cd
RUN chmod 700 /startup.sh
RUN cd /etc/php/7.2/fpm/pool.d && /usr/bin/patch < www.conf.patch && rm www.conf.patch && cd
RUN cd /etc/nginx && /usr/bin/patch < nginx.patch && rm nginx.patch && cd
EXPOSE 80

# Set up Magento
RUN mkdir -p /var/www/html/magento
RUN chown -R www-data:www-data /var/www/html/magento
USER www-data
RUN git clone --depth 1 --branch 2.3.2 https://github.com/magento/magento2.git /var/www/html/magento
COPY nginx*conf.sample_patch /var/www/html/magento/nginx.conf.sample.patch
RUN cd /var/www/html/magento && /usr/bin/patch < nginx.conf.sample.patch && rm nginx.conf.sample.patch && cd
RUN cd /var/www/html/magento && composer install

USER root
CMD [ "/startup.sh" ]

# Make life a little nicer when we log into the container.
WORKDIR /var/www/html/magento
