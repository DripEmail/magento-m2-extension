#!/bin/bash
set -e

docker-compose -p m2devtools exec -u www-data web /bin/bash -c "cd /var/www/html/magento/ && bin/magento cron:run"
