#!/usr/bin/env bash
# Clone artixcore/artgate into packages/artgate so Composer path "packages/artgate" resolves.
# Usage:
#   ARTGATE_GIT_URL=https://github.com/Artixcore/artgate.git ./scripts/ensure-artgate.sh
# Optional: ARTGATE_GIT_REF=main (branch or tag; default: repo default branch)
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ARTGATE_DIR="$APP_DIR/packages/artgate"

if [[ -f "$ARTGATE_DIR/composer.json" ]]; then
    echo "artgate already present at $ARTGATE_DIR"
    exit 0
fi

if [[ -z "${ARTGATE_GIT_URL:-}" ]]; then
    echo "error: Composer requires artgate at: $ARTGATE_DIR" >&2
    echo "  (path repository packages/artgate from $APP_DIR)" >&2
    echo "" >&2
    echo "Clone your artgate package (must contain composer.json at repo root), e.g.:" >&2
    echo "  ARTGATE_GIT_URL=https://github.com/Artixcore/artgate.git $0" >&2
    echo "" >&2
    echo "Or manually:" >&2
    echo "  git clone <url> \"$ARTGATE_DIR\"" >&2
    exit 1
fi

mkdir -p "$APP_DIR/packages"

if [[ -n "${ARTGATE_GIT_REF:-}" ]]; then
    git clone --depth 1 --branch "$ARTGATE_GIT_REF" "$ARTGATE_GIT_URL" "$ARTGATE_DIR"
else
    git clone --depth 1 "$ARTGATE_GIT_URL" "$ARTGATE_DIR"
fi

echo "Cloned artgate to $ARTGATE_DIR"
