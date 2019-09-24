#!/bin/bash
set -e

DRIP_COMPOSE_ENV2=${DRIP_COMPOSE_ENV:-"dev"}

docker-compose -p "devtools_m2_${DRIP_COMPOSE_ENV2}" -f docker-compose.base.yml -f "docker-compose.${DRIP_COMPOSE_ENV2}.yml" "$@"
