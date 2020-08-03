#!/usr/bin/env bash

set -e

if [[ "$(aws --version)" = *"aws-cli/2."* ]]; then
  aws ecr get-login-password --region us-east-1 \
  | docker login \
      --username AWS \
      --password-stdin 648846177135.dkr.ecr.us-east-1.amazonaws.com
else
  eval "$(aws ecr get-login-password --no-include-email --registry-ids 648846177135 --region us-east-1)"
fi

# Spin up a new instance of Magento
# Add --build when you need to rebuild the Dockerfile.
./docker_compose.sh up -d

port=$(./docker_compose.sh port web 80 | cut -d':' -f2)
web_container=$(./docker_compose.sh ps -q web)

# Wait for the DB to be up.
./docker_compose.sh exec -T db /bin/bash -c 'while ! mysql --protocol TCP -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "show databases;" > /dev/null 2>&1; do sleep 1; done'

# Install a couple nice-to-haves on db
./docker_compose.sh exec -T db /bin/bash -c "apt update -y > /dev/null 2>&1 && apt install -y procps vim > /dev/null 2>&1"

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
./bin/magento config:set admin/security/admin_account_sharing 1 && \
./bin/magento config:set catalog/frontend/flat_catalog_product 1 && \
./bin/magento config:set admin/security/use_form_key 0 && \
./bin/magento config:set dev/js/merge_files 1 && \
./bin/magento config:set dev/js/enable_js_bundling 1 && \
./bin/magento config:set dev/js/minify_files 1 && \
./bin/magento config:set dev/css/merge_css_files 1 && \
./bin/magento config:set dev/css/minify_files 1 && \
./bin/magento setup:static-content:deploy -f && \
./bin/magento deploy:mode:set developer
SCRIPT
)

./docker_compose.sh exec -T -u www-data web /bin/bash -c "$magento_setup_script"

echo "Backing up database for later reset"
mkdir -p db_data
./docker_compose.sh exec -e MYSQL_PWD=magento db mysqldump -u magento magento > db_data/dump.sql
