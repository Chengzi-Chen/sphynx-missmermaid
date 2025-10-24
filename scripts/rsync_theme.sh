#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONTAINER_ID="$(docker compose ps -q wordpress-sphynx || true)"

if [ -z "$CONTAINER_ID" ]; then
  echo "WordPress container is not running. Start the stack before syncing." >&2
  exit 1
fi

THEME_SRC="$PROJECT_ROOT/themes/mm-sphynx"
if [ ! -d "$THEME_SRC" ]; then
  echo "Theme source directory not found at $THEME_SRC" >&2
  exit 1
fi

docker run --rm \
  --volumes-from "$CONTAINER_ID" \
  -v "$THEME_SRC:/src:ro" \
  instrumentisto/rsync-ssh \
  rsync -av --delete /src/ /var/www/html/wp-content/themes/mm-sphynx/

echo "Theme synchronised into the container volume."
