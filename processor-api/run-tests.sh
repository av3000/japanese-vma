#!/usr/bin/env sh

set -eu

cd "$(dirname "$0")"

cleanup() {
    if [ "${KEEP_STACK:-0}" != "1" ]; then
        docker compose -f docker-compose.test.yml down -v --remove-orphans
    else
        echo "KEEP_STACK=1 -> stack left running (project jvma-test)."
    fi
}

trap cleanup EXIT

docker compose -f docker-compose.test.yml up -d --wait
docker compose -f docker-compose.test.yml exec -T test-runner composer test:prepare
docker compose -f docker-compose.test.yml exec -T test-runner composer test -- "$@"
