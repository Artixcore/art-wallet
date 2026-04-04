#!/usr/bin/env bash
# Fail if native browser dialog APIs are used in first-party JS (use SweetAlert2 / Swal instead).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"
if ! command -v rg >/dev/null 2>&1; then
  echo "rg (ripgrep) not found; skipping no-browser-dialogs check"
  exit 0
fi
# Match alert(, confirm(, prompt( as global calls (not property names like .alert).
if rg --glob '*.js' '\b(alert|confirm|prompt)\s*\(' resources/js; then
  echo "ERROR: Use SweetAlert2 (Swal) instead of alert/confirm/prompt in resources/js"
  exit 1
fi
echo "OK: no native alert/confirm/prompt in resources/js"
