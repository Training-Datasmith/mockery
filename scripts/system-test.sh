#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT}"

USE_SUDO_DOCKER="${USE_SUDO_DOCKER:-0}"
DOCKER=(docker)
if [[ "${USE_SUDO_DOCKER}" == "1" ]]; then
  DOCKER=(sudo docker)
fi

PHP_IMAGE='php:7.3.33-cli-bullseye@sha256:b65d1b38434e586d73200b2feba4415da57eb2d5fe415e1ec3143df274abdaf0'

ALLOWLIST="${ROOT}/scripts/allowed-skipped-tests.txt"
OUTPUT_LOG="${ROOT}/.system-test/phpunit.out"
mkdir -p "${ROOT}/.system-test"

# shellcheck source=scripts/validate-phpunit-output.sh
source "${ROOT}/scripts/validate-phpunit-output.sh"

log() {
  printf '%s\n' "$*" >&2
}

chmod +x "${ROOT}/scripts/system-test-inner.sh" "${ROOT}/scripts/check-rename-legacy-names.sh" "${ROOT}/scripts/check-psr4-class-file-names.sh"

if ! bash "${ROOT}/scripts/check-rename-legacy-names.sh"; then
  exit 1
fi

if ! bash "${ROOT}/scripts/check-psr4-class-file-names.sh"; then
  exit 1
fi

set +e
CONTAINER_OUT="$("${DOCKER[@]}" run --rm \
  -v "${ROOT}:/app" \
  -w /app \
  -e COMPOSER_ALLOW_SUPERUSER=1 \
  "${PHP_IMAGE}" \
  bash /app/scripts/system-test-inner.sh 2>&1)"
CONTAINER_STATUS=$?
set -e

printf '%s\n' "${CONTAINER_OUT}"

if [[ "${CONTAINER_STATUS}" -ne 0 ]]; then
  log "Container exited with status ${CONTAINER_STATUS}"
  exit "${CONTAINER_STATUS}"
fi

PHPREDIS_BUILT=0
PHPREDIS_REASON=""
while IFS= read -r line; do
  if [[ "${line}" == PHPREDIS_BUILT=* ]]; then
    PHPREDIS_BUILT="${line#PHPREDIS_BUILT=}"
  elif [[ "${line}" == PHPREDIS_REASON=* ]]; then
    PHPREDIS_REASON="${line#PHPREDIS_REASON=}"
  fi
done <<< "${CONTAINER_OUT}"

GATE_ALLOWLIST="${ALLOWLIST}"
EXPECTED_SKIPS=5

if [[ ! -f "${OUTPUT_LOG}" ]]; then
  log "Missing ${OUTPUT_LOG}"
  exit 1
fi

if ! validate_mockery_phpunit_output "${OUTPUT_LOG}" "${GATE_ALLOWLIST}" "${EXPECTED_SKIPS}"; then
  exit 1
fi

log "Strict PHPUnit summary: $(grep -E '^Tests: ' "${OUTPUT_LOG}" | tail -n 1)"
log "phpredis built: ${PHPREDIS_BUILT}"
if [[ "${PHPREDIS_BUILT}" -ne 1 ]]; then
  log "phpredis missing (allowlist still 5): ${PHPREDIS_REASON:-unknown}"
  exit 1
fi

log "system-test passed."
