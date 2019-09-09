#!/bin/bash
set -e

# Reset cron
docker-compose exec db mysql -u magento -pmagento magento -e "DELETE FROM cron_schedule WHERE job_code LIKE 'drip_%' AND status = 'running';"

# Force cron runs to now.
docker-compose exec db mysql -u magento -pmagento magento -e "UPDATE cron_schedule SET scheduled_at = NOW() WHERE schedule_id = (SELECT schedule_id FROM (SELECT * FROM cron_schedule) AS inner_cron_schedule WHERE job_code = 'drip_connect_sync_customers' AND status = 'pending' ORDER BY scheduled_at ASC LIMIT 1);"
docker-compose exec db mysql -u magento -pmagento magento -e "UPDATE cron_schedule SET scheduled_at = NOW() WHERE schedule_id = (SELECT schedule_id FROM (SELECT * FROM cron_schedule) AS inner_cron_schedule WHERE job_code = 'drip_connect_sync_orders' AND status = 'pending' ORDER BY scheduled_at ASC LIMIT 1);"

docker-compose exec -u www-data web /bin/bash -c "cd /var/www/html/magento/ && bin/magento cron:run"
