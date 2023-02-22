#!/usr/bin/env bash

set -e

build=

while [ "$1" != "" ]; do
    case $1 in
        -b | --build )    build=1
    esac
    shift
done

# Add --build when you need to rebuild the Dockerfile.
if [ "$build" = "1" ]; then
  echo "Spinning up a new instance of Magento with build"
  docker-compose up -d --build
else
  echo "Spinning up a new instance of Magento"
  docker-compose up -d
fi

port=$(docker-compose port web 80 | cut -d':' -f2)

echo "Waiting for the db to come up..."
docker-compose exec -T db /bin/bash -c 'while ! mariadb --protocol TCP -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" -e "show databases;" > /dev/null 2>&1; do sleep 1; done'

echo "Installing a few extra packages on db"
docker-compose exec -T db /bin/bash -c "apt update -y > /dev/null 2>&1 && apt install -y procps vim > /dev/null 2>&1 || true"

magento_setup_script=$(cat <<SCRIPT
cd /var/www/html/magento/ && \
MAGE_MODE=developer ./bin/magento setup:install \
--enable-debug-logging=true \
--db-host=db \
--db-name=magento \
--db-user=magento \
--db-password=magento \
--elasticsearch-host=opensearch \
--elasticsearch-username=admin \
--elasticsearch-password=admin \
--base-url='http://main.magento.localhost:$port' \
--use-rewrites=1 \
--admin-user=admin \
--admin-password=abc1234567890 \
--admin-email='admin@example.com' \
--admin-firstname=FIRST_NAME \
--admin-lastname=LAST_NAME && \
./bin/magento setup:config:set -n --backend-frontname='admin_123' && \
./bin/magento config:set admin/security/admin_account_sharing 1 && \
./bin/magento config:set catalog/frontend/flat_catalog_product 1 && \
./bin/magento config:set admin/security/use_form_key 0 && \
./bin/magento config:set oauth/consumer/enable_integration_as_bearer 1 && \
./bin/magento config:set dev/js/merge_files 1 && \
./bin/magento config:set dev/js/enable_js_bundling 1 && \
./bin/magento config:set dev/js/minify_files 1 && \
./bin/magento config:set dev/css/merge_css_files 1 && \
./bin/magento config:set dev/css/minify_files 1 && \
./bin/magento config:set dripconnect_general/log_settings/is_enabled 1 && \
./bin/magento setup:static-content:deploy -f && \
./bin/magento deploy:mode:set production
SCRIPT
)

echo "Installing Magento"
docker-compose exec -T -u www-data web /bin/bash -c "$magento_setup_script"

echo "Backing up database for later reset"
mkdir -p db_data
touch db_data/dump.sql
docker-compose exec -e MYSQL_PWD=magento db mysqldump -u magento magento > db_data/dump.sql

echo "Done with setup"
