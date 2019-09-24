#!/bin/bash
set -e

# Spin up a new instance of Magento
# Add --build when you need to rebuild the Dockerfile.
./docker_compose.sh up -d

port=$(./docker_compose.sh port web 80 | cut -d':' -f2)
web_container=$(./docker_compose.sh ps -q web)

# Wait for the DB to be up.
./docker_compose.sh exec -T db /bin/bash -c 'while ! mysql --protocol TCP -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "show databases;" > /dev/null 2>&1; do sleep 1; done'

magento_setup_script=$(cat <<SCRIPT
cd /var/www/html/magento/ && \
MAGE_MODE=developer ./bin/magento setup:install \
--enable-debug-logging=true \
--db-host=db \
--db-name=magento \
--db-user=magento \
--db-password=magento \
--base-url='http://main.magento.localhost:$port' \
--use-rewrites=1 \
--admin-user=admin \
--admin-password=abc1234567890 \
--admin-email='admin@example.com' \
--admin-firstname=FIRST_NAME \
--admin-lastname=LAST_NAME && \
./bin/magento setup:config:set --backend-frontname='admin_123' && \
./bin/magento deploy:mode:set developer
SCRIPT
)

./docker_compose.sh exec -T -u www-data web /bin/bash -c "$magento_setup_script"

# Backup for reset.
mkdir -p db_data
./docker_compose.sh exec -e MYSQL_PWD=magento db mysqldump -u magento magento > db_data/dump.sql
