#!/bin/bash

set -e

docker-compose exec web tail -f -n100 /var/www/html/magento/var/log/drip.log
