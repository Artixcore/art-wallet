#!/usr/bin/env bash
# Verify an encrypted backup artifact exists and is non-empty (run after encrypt + before upload).
# Usage: verify-backup-age.sh /path/to/backup-2026-04-04.sql.age
set -euo pipefail
path="${1:-}"
if [[ -z "$path" ]]; then
  echo "usage: $0 <backup-file>" >&2
  exit 2
fi
if [[ ! -f "$path" ]]; then
  echo "backup file missing: $path" >&2
  exit 1
fi
if [[ ! -s "$path" ]]; then
  echo "backup file is empty: $path" >&2
  exit 1
fi
echo "ok: $path ($(wc -c < "$path") bytes)"
