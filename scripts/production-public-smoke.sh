#!/usr/bin/env bash
# READ-ONLY guest visibility smoke for the actual Souq Qareeb production URL.
# No credentials, POST requests, cookies, purchases, payments, or server changes.
set -uo pipefail

url="https://souq.edubridge.win"
tmp="$(mktemp)"
trap 'rm -f "$tmp"' EXIT
failed=0

for path in / /shop/ /cart/ /checkout/ /my-account/ /add-listing/; do
  # Do not persist response bodies; only the expected public pages are requested.
  response="$(curl --silent --show-error --location --proto '=https' --proto-redir '=https' \
    --max-redirs 5 --connect-timeout 12 --max-time 40 --retry 1 \
    --output "$tmp" --write-out '%{http_code}|%{url_effective}|%{content_type}' "$url$path")"
  rc=$?
  IFS='|' read -r status final mime <<< "$response"
  if [[ "$rc" -ne 0 || "$status" != "200" || "$mime" != text/html* \
    || ( "$final" != "$url" && "$final" != "$url/" && "$final" != "$url/"* ) ]]; then
    printf 'FAIL: %s status=%s rc=%s content_type=%s final_host_ok=%s\n' \
      "$path" "${status:-000}" "$rc" "${mime:-none}" \
      "$([[ "$final" == "$url/"* || "$final" == "$url" ]] && echo yes || echo no)"
    failed=$((failed + 1))
    continue
  fi
  if grep -qiE 'error establishing a database connection|there has been a critical error on this website' "$tmp"; then
    printf 'FAIL: %s WordPress database/critical error visible\n' "$path"
    failed=$((failed + 1))
    continue
  fi
  printf 'PASS: %s HTTP 200 HTML via TLS\n' "$path"
done

if [[ "$failed" -ne 0 ]]; then
  printf 'FAIL: %s public production checks failed\n' "$failed"
  exit 1
fi
printf '%s\n' 'PASS: public production pages available to guests.'
printf '%s\n' 'LIMIT: this does not prove login, Stripe Test Mode, checkout, payments, or stock behavior.'
