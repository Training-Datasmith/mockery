#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

log() {
  printf '%s\n' "$*" >&2
}

export MOCKERY_ROOT="${ROOT}"

mapfile -t MISMATCHES < <(python3 <<'PY'
import os
import re
from pathlib import Path

root = Path(os.environ["MOCKERY_ROOT"])
roots = [root / "library", root / "tests"]
pattern = re.compile(
    r"^(?:abstract\s+|final\s+)?(?:class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_]*)",
    re.MULTILINE,
)

mismatches = []
for base in roots:
    if not base.is_dir():
        continue
    for dirpath, dirnames, filenames in os.walk(base):
        rel_dir = Path(dirpath).relative_to(root)
        if "Fixture" in rel_dir.parts:
            continue
        if "/vendor/" in f"/{rel_dir}/":
            continue
        for filename in filenames:
            if not filename.endswith(".php"):
                continue
            path = Path(dirpath) / filename
            text = path.read_text(encoding="utf-8", errors="ignore")
            names = pattern.findall(text)
            if not names:
                continue
            expected_base = path.stem
            for declared in names:
                if declared != expected_base:
                    mismatches.append(
                        f"{path.relative_to(root)}: declared {declared}, file base {expected_base}"
                    )

for line in sorted(mismatches):
    print(line)
PY
)

if ((${#MISMATCHES[@]} > 0)); then
  log "check-psr4-class-file-names: class/file name mismatches:"
  for line in "${MISMATCHES[@]}"; do
    log "  ${line}"
  done
  exit 1
fi

log "check-psr4-class-file-names: passed"
exit 0
