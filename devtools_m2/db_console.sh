#!/bin/bash

set -e

docker-compose exec db mysql -u magento -pmagento magento
