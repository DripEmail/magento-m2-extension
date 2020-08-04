#!/bin/bash
set -e

./setup.sh
CYPRESS_RETRIES=2 $(npm bin)/cypress run --browser chrome #--record
docker-compose down
