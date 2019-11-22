#!/bin/bash
set -e

./docker_compose.sh exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "DROP DATABASE magento; CREATE DATABASE magento"
./docker_compose.sh exec -T -e MYSQL_PWD=magento db mysql -u magento magento < db_data/dump.sql
./flush_caches.sh
