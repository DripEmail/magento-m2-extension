#!/bin/bash
set -e

docker-compose exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "DROP DATABASE magento; CREATE DATABASE magento"
docker-compose exec -T -e MYSQL_PWD=magento db mysql -u magento magento < db_data/dump.sql

# Force the ids to be different so that we don't risk a test that passes because the IDs ended up matching.
docker-compose exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "ALTER TABLE store_website AUTO_INCREMENT = 100"
docker-compose exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "ALTER TABLE store_group AUTO_INCREMENT = 200"
docker-compose exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "ALTER TABLE store AUTO_INCREMENT = 300"

./flush_caches.sh
