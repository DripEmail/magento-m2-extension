#!/bin/bash

set -e

docker-compose -p m2devtools exec db mysql -u magento -pmagento magento
