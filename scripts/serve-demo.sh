#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PUBLIC_DIR="${ROOT_DIR}/web/public"
PORT="${STATGUARD_DEMO_PORT:-8080}"
HOST="${STATGUARD_DEMO_HOST:-127.0.0.1}"
PHP_IMAGE="${STATGUARD_PHP_IMAGE:-php:8.3-cli}"

install_vendor_local() {
    if [ -d "${ROOT_DIR}/vendor" ]; then return; fi
    if ! command -v composer >/dev/null 2>&1; then
        echo "error: vendor/ missing and composer not installed" >&2
        exit 1
    fi
    echo "→ installing composer dependencies..."
    (cd "${ROOT_DIR}" && composer install --no-interaction --prefer-dist)
}

install_vendor_docker() {
    if [ -d "${ROOT_DIR}/vendor" ]; then return; fi
    echo "→ installing composer dependencies via docker..."
    docker run --rm -v "${ROOT_DIR}:/app" -w /app composer:2 \
        composer install --no-interaction --prefer-dist --no-progress
}

if command -v php >/dev/null 2>&1; then
    install_vendor_local
    echo "→ StatGuard demo (native php) at http://${HOST}:${PORT}"
    echo "  Ctrl+C to stop."
    exec php -S "${HOST}:${PORT}" -t "${PUBLIC_DIR}"
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "error: neither php nor docker are available" >&2
    exit 1
fi

install_vendor_docker

TTY_FLAGS=()
if [ -t 0 ] && [ -t 1 ]; then TTY_FLAGS=(-it); fi

echo "→ StatGuard demo (docker ${PHP_IMAGE}) at http://${HOST}:${PORT}"
echo "  Ctrl+C to stop."
exec docker run --rm "${TTY_FLAGS[@]}" \
    -p "${HOST}:${PORT}:8080" \
    -v "${ROOT_DIR}:/app" \
    -w /app \
    "${PHP_IMAGE}" \
    php -S "0.0.0.0:8080" -t web/public
