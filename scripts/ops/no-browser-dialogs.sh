#!/usr/bin/env bash
# Fail if native browser dialog APIs are used in first-party JS (use SweetAlert2 / Swal instead).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"
PATTERN='\b(alert|confirm|prompt)\s*\('
if command -v rg >/dev/null 2>&1; then
  if rg --glob '*.js' "$PATTERN" resources/js; then
    echo "ERROR: Use SweetAlert2 (Swal) instead of alert/confirm/prompt in resources/js"
    exit 1
  fi
elif grep -rE --include='*.js' "$PATTERN" resources/js 2>/dev/null; then
  echo "ERROR: Use SweetAlert2 (Swal) instead of alert/confirm/prompt in resources/js"
  exit 1
else
  :
fi
echo "OK: no native alert/confirm/prompt in resources/js"
