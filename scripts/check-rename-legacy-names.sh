#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

log() {
  printf '%s\n' "$*" >&2
}

# Pre-rename repo-owned identifiers and paths that must not remain in source or docs.
PATTERNS=(
  'mockery_camel_to_snake'
  'CountValidator/'
  'StringManipulation/'
  'Mockery\\CountValidator\\'
  'Mockery\\Generator\\StringManipulation\\'
  'Mockery\\Generator\\MockConfiguration[^_]'
  'Mockery\\Loader\\EvalLoader'
  'Mockery\\Loader\\RequireLoader'
  '::fetchMock\('
  'function fetchMock'
  'getMockConfiguration'
  'Mockery::setLoader'
  'Allow_Dynamic_Properties'
  'Return_Type_Will_Change'
  'Mockery\\Exception\\BadMethodCallException'
  'Mockery\\Exception\\InvalidCountException'
)

SEARCH_PATHS=(
  "${ROOT}/library"
  "${ROOT}/tests"
  "${ROOT}/docs"
  "${ROOT}/README.md"
  "${ROOT}/ARCHITECTURE.md"
  "${ROOT}/CONTRIBUTING.md"
  "${ROOT}/psalm-baseline.xml"
  "${ROOT}/phpunit.xml.dist"
  "${ROOT}/composer.json"
  "${ROOT}/scripts/allowed-skipped-tests.txt"
)

failed=0
for pattern in "${PATTERNS[@]}"; do
  if rg -n --pcre2 "${pattern}" "${SEARCH_PATHS[@]}" 2>/dev/null; then
    log "Legacy rename pattern still present: ${pattern}"
    failed=1
  fi
done

if [[ "${failed}" -ne 0 ]]; then
  log "check-rename-legacy-names: failed"
  exit 1
fi

log "check-rename-legacy-names: passed"
exit 0
