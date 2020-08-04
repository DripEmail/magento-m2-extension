#!/bin/bash
set -e

# Nuke all existing cron runs for this plugin and create a new one.
docker-compose exec -T db mysql -u magento -pmagento magento -e "DELETE FROM cron_schedule WHERE job_code IN ('drip_connect_sync_customers', 'drip_connect_sync_customers');"
docker-compose exec -T db mysql -u magento -pmagento magento -e "INSERT INTO cron_schedule (job_code, status, created_at, scheduled_at) VALUES ('drip_connect_sync_customers', 'pending', NOW(), NOW()), ('drip_connect_sync_orders', 'pending', NOW(), NOW());"

docker-compose exec -T -u www-data web /bin/bash -c "cd /var/www/html/magento/ && bin/magento cron:run --group default"
