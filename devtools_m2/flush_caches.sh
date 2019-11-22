#!/bin/bash
set -e

./docker_compose.sh exec -T -u www-data web /bin/bash -c "cd /var/www/html/magento/ && ./bin/magento cache:clean && ./bin/magento cache:flush"
./docker_compose.sh exec -T -u www-data web /bin/bash -c "cd /var/www/html/magento/ && ./bin/magento cache:clean && ./bin/magento indexer:reindex"
