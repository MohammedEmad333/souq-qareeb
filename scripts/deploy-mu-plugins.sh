#!/usr/bin/env bash
set -Eeuo pipefail

# Deploy only the seven souq-*.php must-use plugins.
# Usage: bash scripts/deploy-mu-plugins.sh [--dry-run|--deploy|--rollback SNAPSHOT_DIR]
# Run from the host with Docker Compose installed.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_DIR="${SOUQ_COMPOSE_DIR:-$(cd "$ROOT/.." && pwd)}"
SRC="$ROOT/wordpress/mu-plugins"
DEST="/var/www/html/wp-content/mu-plugins"
BACKUP_ROOT="${SOUQ_BACKUP_DIR:-$COMPOSE_DIR/deploy-backups}"
MODE="${1:---dry-run}"

plugins=(
  souq-arabic-labels.php souq-catalog.php souq-demo-checkout.php
  souq-design.php souq-filters.php souq-product-details.php
  souq-product-location.php
)

cd "$COMPOSE_DIR"
CONTAINER="$(docker compose ps -q wordpress)"
[[ -n "$CONTAINER" ]] || { echo "WordPress container unavailable" >&2; exit 1; }
docker inspect -f '{{.State.Running}}' "$CONTAINER" | grep -qx true

for file in "${plugins[@]}"; do
  [[ -f "$SRC/$file" ]] || { echo "Missing source: $file" >&2; exit 1; }
  docker exec -i "$CONTAINER" php -l < "$SRC/$file" >/dev/null
done
echo "PHP syntax passed for all ${#plugins[@]} plugin files."

if [[ "$MODE" == "--dry-run" ]]; then
  for file in "${plugins[@]}"; do
    live_sum="$(docker exec "$CONTAINER" sh -c "test -f '$DEST/$file' && sha256sum '$DEST/$file' | cut -d' ' -f1" 2>/dev/null || true)"
    src_sum="$(sha256sum "$SRC/$file" | cut -d' ' -f1)"
    if [[ "$live_sum" == "$src_sum" ]]; then echo "UNCHANGED $file"; else echo "DIFFERS $file"; fi
  done
  exit 0
fi

if [[ "$MODE" == "--rollback" ]]; then
  SNAPSHOT="${2:-}"
  [[ -n "$SNAPSHOT" && -d "$SNAPSHOT" ]] || { echo "Provide existing snapshot directory" >&2; exit 1; }
  for file in "${plugins[@]}"; do
    [[ -f "$SNAPSHOT/$file" ]] || { echo "Incomplete snapshot: $file" >&2; exit 1; }
  done
  for file in "${plugins[@]}"; do docker cp "$SNAPSHOT/$file" "$CONTAINER:$DEST/$file"; done
  echo "Rollback files restored from $SNAPSHOT. Verify WordPress manually."
  exit 0
fi

[[ "$MODE" == "--deploy" ]] || { echo "Unsupported mode: $MODE" >&2; exit 1; }
umask 077
mkdir -p "$BACKUP_ROOT"
SNAPSHOT="$(mktemp -d "$BACKUP_ROOT/mu-plugins-XXXXXXXX")"
for file in "${plugins[@]}"; do
  docker cp "$CONTAINER:$DEST/$file" "$SNAPSHOT/$file" || {
    echo "Backup failed; no deployment attempted" >&2
    exit 1
  }
done
echo "Backup created: $SNAPSHOT"

# Stage every candidate in the container and validate before replacing live files.
STAGE="/tmp/souq-mu-deploy-$$"
docker exec "$CONTAINER" mkdir -p "$STAGE"
trap 'docker exec "$CONTAINER" rm -rf "$STAGE" >/dev/null 2>&1 || true' EXIT
for file in "${plugins[@]}"; do
  docker cp "$SRC/$file" "$CONTAINER:$STAGE/$file"
  docker exec "$CONTAINER" php -l "$STAGE/$file" >/dev/null
done

rc=0
for file in "${plugins[@]}"; do
  if ! docker exec "$CONTAINER" cp "$STAGE/$file" "$DEST/$file"; then
    rc=1
    break
  fi
done
if (( rc != 0 )); then
  echo "Deployment failed; attempting automatic file rollback." >&2
  for file in "${plugins[@]}"; do docker cp "$SNAPSHOT/$file" "$CONTAINER:$DEST/$file" || true; done
  exit 1
fi
echo "Deployment completed. Backup: $SNAPSHOT"
echo "IMPORTANT: verify site health and checkout guard; rollback manually if necessary."
