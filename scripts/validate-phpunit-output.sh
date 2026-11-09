#!/usr/bin/env bash

# shellcheck disable=SC2034
validate_mockery_phpunit_output() {
  local output_file="$1"
  local allowlist_path="$2"
  local expected_skip_count="$3"

  local stripped
  stripped="$(mktemp)"
  sed -E 's/\x1b\[[0-9;]*[[:alpha:]]//g' "${output_file}" >"${stripped}"

  if grep -Eq '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}"; then
    printf '%s\n' "PHPUnit output contains Warning, Deprecated, or Notice"
    grep -Eq '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}"
    rm -f "${stripped}"
    return 1
  fi

  if grep -Eq '^(FAILURES!|ERRORS!|WARNINGS!)' "${stripped}"; then
    printf '%s\n' "PHPUnit output contains failure banner"
    rm -f "${stripped}"
    return 1
  fi

  if ! grep -Fxq 'OK, but incomplete, skipped, or useless tests!' "${stripped}"; then
    printf '%s\n' 'PHPUnit output missing: OK, but incomplete, skipped, or useless tests!'
    rm -f "${stripped}"
    return 1
  fi

  local summary_line
  summary_line="$(grep -E '^Tests: [0-9]+, Assertions: [0-9]+, Skipped: [0-9]+\.$' "${stripped}" | tail -n 1 || true)"
  if [[ -z "${summary_line}" ]]; then
    printf '%s\n' 'PHPUnit output missing Tests summary line'
    rm -f "${stripped}"
    return 1
  fi

  local skip_count
  skip_count="$(printf '%s\n' "${summary_line}" | sed -n 's/^Tests: [0-9]*, Assertions: [0-9]*, Skipped: \([0-9]*\)\.$/\1/p')"
  if [[ "${skip_count}" != "${expected_skip_count}" ]]; then
    printf '%s\n' "Expected Skipped: ${expected_skip_count}, got ${skip_count} (${summary_line})"
    rm -f "${stripped}"
    return 1
  fi

  local -a allowed=()
  while IFS= read -r line || [[ -n "${line}" ]]; do
    line="${line%%#*}"
    line="$(echo "${line}" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    if [[ -n "${line}" ]]; then
      allowed+=("${line}")
    fi
  done <"${allowlist_path}"

  if [[ "${#allowed[@]}" -ne "${expected_skip_count}" ]]; then
    printf '%s\n' "Allowlist size ${#allowed[@]} does not match expected skip count ${expected_skip_count}"
    rm -f "${stripped}"
    return 1
  fi

  local -a seen_skips=()
  mapfile -t seen_skips < <(grep -E '^S ' "${stripped}" | sed -E 's/^S +//' | sed -E 's/ \([0-9.]+s\)$//' || true)

  if [[ "${#seen_skips[@]}" -ne "${expected_skip_count}" ]]; then
    printf '%s\n' "Expected ${expected_skip_count} S lines, found ${#seen_skips[@]}"
    rm -f "${stripped}"
    return 1
  fi

  local id
  for id in "${seen_skips[@]}"; do
    local ok=0
    local allowed_id
    for allowed_id in "${allowed[@]}"; do
      if [[ "${id}" == "${allowed_id}" ]]; then
        ok=1
        break
      fi
    done
    if [[ "${ok}" -ne 1 ]]; then
      printf '%s\n' "Unexpected skipped test: ${id}"
      rm -f "${stripped}"
      return 1
    fi
  done

  for allowed_id in "${allowed[@]}"; do
    local found=0
  for id in "${seen_skips[@]}"; do
      if [[ "${id}" == "${allowed_id}" ]]; then
        found=1
        break
      fi
    done
    if [[ "${found}" -ne 1 ]]; then
      printf '%s\n' "Allowlisted skip not seen in output: ${allowed_id}"
      rm -f "${stripped}"
      return 1
    fi
  done

  if grep -E 'Failures: [1-9]|Errors: [1-9]|Warnings: [1-9]|Deprecations: [1-9]|PHPUnit Deprecations: [1-9]|Notices: [1-9]|Risky: [1-9]' "${stripped}" >/dev/null; then
    printf '%s\n' 'PHPUnit summary reports a non-zero issue counter'
    rm -f "${stripped}"
    return 1
  fi

  rm -f "${stripped}"
  printf '%s\n' "${summary_line}"
  return 0
}
