#!/bin/bash
(apt-get update &&
apt-get install -y libxml2-dev libxslt-dev &&
docker-php-ext-install soap &&
docker-php-ext-install xsl &&
            
# Composer install
echo "{\"http-basic\":{\"repo.magento.com\":{\"password\":\"$MAGENTO_COMPOSER_PASSWORD\",\"username\":\"$MAGENTO_COMPOSER_USERNAME\"}}}" > auth.json &&
composer install --quiet && 
            
# Run tests
vendor/bin/phpcs --config-set installed_paths vendor/magento/marketplace-eqp &&
vendor/bin/phpcs . --ignore=vendor/*  --standard=MEQP2 --severity=10 &&
vendor/bin/phpunit --configuration phpunit.xml) || exit 1 