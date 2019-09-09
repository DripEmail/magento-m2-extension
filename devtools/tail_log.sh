#!/bin/bash

set -e

docker-compose -p m2devtools exec web tail -f /var/www/html/magento/var/log/drip.log
