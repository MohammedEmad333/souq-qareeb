#!/usr/bin/env bash
set -Eeuo pipefail

# Read-only WordPress block-theme drift check.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_DIR="${SOUQ_COMPOSE_DIR:-$(cd "$ROOT/.." && pwd)}"
EXPORTS="$ROOT/wordpress/exports"
CHECKER="$ROOT/scripts/compare-block-theme.php"

for file in header.json navigation.json global-styles.json; do
  test -f "$EXPORTS/$file" || { echo "Missing snapshot: $file" >&2; exit 2; }
done

cd "$COMPOSE_DIR"
container="$(docker compose ps -q wordpress)"
test -n "$container" || { echo "WordPress container unavailable" >&2; exit 2; }
stage="/tmp/souq-block-theme-check-$$"
docker exec "$container" mkdir -p "$stage"
trap 'docker exec "$container" rm -rf "$stage" >/dev/null 2>&1 || true' EXIT
docker cp "$CHECKER" "$container:$stage/compare.php"
docker exec "$container" php -l "$stage/compare.php" >/dev/null
for file in header.json navigation.json global-styles.json; do
  docker cp "$EXPORTS/$file" "$container:$stage/$file"
done
docker exec "$container" php "$stage/compare.php" "$stage"
