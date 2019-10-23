#!/bin/bash
set -e

DRIP_COMPOSE_ENV=test ./setup.sh
CYPRESS_RETRIES=2 $(npm bin)/cypress run --browser chrome #--record
DRIP_COMPOSE_ENV=test ./docker_compose.sh down
