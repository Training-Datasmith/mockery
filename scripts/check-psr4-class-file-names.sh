#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

log() {
  printf '%s\n' "$*" >&2
}

PHP_IMAGE="${MOCKERY_CHECK_PHP_IMAGE:-php:7.3.33-cli-bullseye@sha256:b65d1b38434e586d73200b2feba4415da57eb2d5fe415e1ec3143df274abdaf0}"

run_php() {
  local script_name="$1"
  shift

  if command -v php >/dev/null 2>&1; then
    MOCKERY_ROOT="${ROOT}" php "${ROOT}/scripts/${script_name}" "$@"
    return
  fi

  local docker=(docker)
  if ! docker info >/dev/null 2>&1; then
    if [[ "${USE_SUDO_DOCKER:-0}" == "1" ]]; then
      docker=(sudo docker)
    fi
  fi

  "${docker[@]}" run --rm \
    -v "${ROOT}:/app" \
    -w /app \
    -e "MOCKERY_ROOT=/app" \
    "${PHP_IMAGE}" \
    php "/app/scripts/${script_name}" "$@"
}

if ! run_php check-psr4-class-file-names-selftest.php; then
  exit 1
fi

if ! run_php check-psr4-class-file-names.php; then
  exit 1
fi

log "check-psr4-class-file-names: passed"
exit 0
