#!/usr/bin/env bash
set -Eeuo pipefail

# Manual backup/restore of the three current Souq block-theme records.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_DIR="${SOUQ_COMPOSE_DIR:-$(cd "$ROOT/.." && pwd)}"
BACKUP_ROOT="${SOUQ_BACKUP_DIR:-$COMPOSE_DIR/deploy-backups}"
MODE="${1:-}"
if [[ "$MODE" != "--backup" && "$MODE" != "--restore" ]]; then
  echo "Usage: bash scripts/theme-records.sh --backup | --restore SNAPSHOT_DIR" >&2
  exit 2
fi
cd "$COMPOSE_DIR"
container="$(docker compose ps -q wordpress)"
[[ -n "$container" ]] || { echo "WordPress container unavailable" >&2; exit 2; }
stage="/tmp/souq-theme-records-$$"
docker exec "$container" mkdir -p "$stage"
trap 'docker exec "$container" rm -rf "$stage" >/dev/null 2>&1 || true' EXIT
docker cp "$ROOT/scripts/theme-records.php" "$container:$stage/run.php"
docker exec "$container" php -l "$stage/run.php" >/dev/null

if [[ "$MODE" == "--backup" ]]; then
  umask 077
  mkdir -p "$BACKUP_ROOT"
  dest="$(mktemp -d "$BACKUP_ROOT/theme-records-XXXXXXXX")"
  for name in header navigation global-styles; do
    cp "$ROOT/wordpress/exports/$name.json" "$dest/$name.json"
    docker cp "$dest/$name.json" "$container:$stage/$name.json"
  done
  docker exec "$container" php "$stage/run.php" backup "$stage"
  for name in header navigation global-styles; do
    docker cp "$container:$stage/$name.json" "$dest/$name.json"
  done
  echo "Backup directory: $dest"
  exit 0
fi

source_dir="${2:-}"
[[ -n "$source_dir" && -d "$source_dir" ]] || { echo "Provide an existing backup directory" >&2; exit 2; }
# Prevent restoring arbitrary exports without an operator confirmation.
read -r -p "Type RESTORE to overwrite the three live theme records: " answer
[[ "$answer" == RESTORE ]] || { echo "Cancelled"; exit 1; }
for name in header navigation global-styles; do
  [[ -f "$source_dir/$name.json" ]] || { echo "Missing $name.json" >&2; exit 2; }
  docker cp "$source_dir/$name.json" "$container:$stage/$name.json"
done
docker exec "$container" php "$stage/run.php" restore "$stage"
