#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_DIR="${SOUQ_COMPOSE_DIR:-$(cd "$ROOT/.." && pwd)}"
MODE="${1:---dry-run}"
[[ "$MODE" == --dry-run || "$MODE" == --apply ]] || { echo "Use --dry-run or --apply" >&2; exit 2; }
cd "$COMPOSE_DIR"
container="$(docker compose ps -q wordpress)"
[[ -n "$container" ]] || { echo "WordPress unavailable" >&2; exit 2; }
stage="/tmp/souq-theme-publish-$$"
docker exec "$container" mkdir -p "$stage/baseline" "$stage/desired"
trap 'docker exec "$container" rm -rf "$stage" >/dev/null 2>&1 || true' EXIT
docker cp "$ROOT/scripts/publish-block-theme.php" "$container:$stage/run.php"
docker exec "$container" php -l "$stage/run.php" >/dev/null
for name in header navigation global-styles; do
  docker cp "$ROOT/wordpress/baseline/$name.json" "$container:$stage/baseline/$name.json"
  docker cp "$ROOT/wordpress/exports/$name.json" "$container:$stage/desired/$name.json"
done
if [[ "$MODE" == --dry-run ]]; then
  docker exec "$container" php "$stage/run.php" check "$stage"
  exit $?
fi
echo "Performing read-only preflight check..."
docker exec "$container" php "$stage/run.php" check "$stage"
echo "Creating required backup..."
# Must succeed before any write; uses existing manual backup utility.
bash "$ROOT/scripts/theme-records.sh" --backup
echo "Review the backup path above before proceeding."
read -r -p "Type APPLY to publish the reviewed theme changes: " answer
[[ "$answer" == APPLY ]] || { echo "Cancelled"; exit 1; }
docker exec "$container" php "$stage/run.php" apply "$stage"
