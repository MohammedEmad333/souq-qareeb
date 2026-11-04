#!/usr/bin/env bash
# Safely collect approved Souq Qareeb theme images from an existing local copy.
# Usage: bash scripts/collect-brand-assets.sh "/absolute/path/to/assets/images"
set -euo pipefail

src="${1:-}"
if [[ -z "$src" || ! -d "$src" ]]; then
  echo 'Usage: bash scripts/collect-brand-assets.sh "/absolute/path/to/assets/images"' >&2
  exit 2
fi

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
target="$repo_root/wordpress/themes/souq-qareeb/assets/images"
mkdir -p "$target"
missing=0

for name in logo-header.png hero-mobile-image.webp sell.webp exchange.webp donate.webp; do
  source="$src/$name"
  if [[ ! -f "$source" || ! -s "$source" ]]; then
    echo "MISSING: $name"
    missing=$((missing + 1))
    continue
  fi
  mime="$(file --brief --mime-type "$source")"
  case "$name:$mime" in
    *.png:image/png|*.webp:image/webp) ;;
    *) echo "INVALID IMAGE: $name ($mime)" >&2; missing=$((missing + 1)); continue ;;
  esac
  if [[ -f "$target/$name" ]] && cmp -s "$source" "$target/$name"; then
    echo "UNCHANGED: $name"
  else
    cp -p "$source" "$target/$name"
    echo "COPIED: $name"
  fi
done

echo 'SHA256 inventory (for files present):'
for name in logo-header.png hero-mobile-image.webp sell.webp exchange.webp donate.webp; do
  if [[ -f "$target/$name" ]]; then sha256sum "$target/$name"; fi
done
echo "Missing/invalid: $missing"
if [[ "$missing" -ne 0 ]]; then
  echo 'Original assets are incomplete. Do not claim the deployable brand package is complete.' >&2
  exit 1
fi
echo 'Asset copy complete. Review license and git diff/status before committing.'
