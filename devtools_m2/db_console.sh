#!/bin/bash

set -e

./docker_compose.sh exec db mysql -u magento -pmagento magento
