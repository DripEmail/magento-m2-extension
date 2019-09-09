#!/bin/bash
set -e

docker-compose exec -u www-data web /bin/bash -c "cd /var/www/html/magento/ && bin/magento cron:run"
