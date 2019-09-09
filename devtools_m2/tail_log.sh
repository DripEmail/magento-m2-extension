#!/bin/bash

set -e

docker-compose exec web tail -f /var/www/html/magento/var/log/drip.log
