#!/usr/bin/env bash
# CI/local guardrails: repository hygiene + Laravel ops validation.
set -euo pipefail
cd "$(dirname "$0")/../.."

if ! command -v git >/dev/null 2>&1; then
  echo "git not found; skipping tracked-file checks" >&2
else
  if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    tracked="$(git ls-files --cached -- .env 2>/dev/null || true)"
    if [[ -n "${tracked}" ]]; then
      echo "error: .env must not be tracked by git" >&2
      exit 1
    fi
  fi
fi

php artisan ops:validate --ci "$@"
