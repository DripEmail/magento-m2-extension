#!/bin/bash
set -e

echo "[DEPRECATED] ./docker_compose.sh is deprecated in favor of using docker-compose directly."

docker-compose "$@"
