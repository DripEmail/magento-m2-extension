#!/bin/bash
set -e

./flush_caches.sh
docker-compose exec -T web /bin/bash -c "cd /var/www/html/magento/ && rm -rf generated/metadata generated/code"
