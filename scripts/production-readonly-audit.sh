#!/usr/bin/env bash
# Souq Qareeb production audit (read-only).
# No deployments, backup creation, database updates, email sending or checkout attempts.
# Run on the Oracle host; requires Docker and curl.
set -uo pipefail

if [[ "${1:-}" == "--help" ]]; then
  printf '%s\n' 'Usage: bash scripts/production-readonly-audit.sh' \
    'Optional: SOUQ_URL=https://souq.edubridge.win' \
    '          SOUQ_WP_CONTAINER=souq-qareeb-wordpress-1' \
    '          SOUQ_DB_CONTAINER=souq-qareeb-database-1' \
    '          SOUQ_BACKUP_DIR=$HOME/souq-qareeb/deploy-backups'
  exit 0
fi

url="${SOUQ_URL:-https://souq.edubridge.win}"
url="${url%/}"
wp_container="${SOUQ_WP_CONTAINER:-souq-qareeb-wordpress-1}"
db_container="${SOUQ_DB_CONTAINER:-souq-qareeb-database-1}"
backup_dir="${SOUQ_BACKUP_DIR:-$HOME/souq-qareeb/deploy-backups}"
failed=0
warnings=0

fail() { printf 'FAIL: %s\n' "$*"; failed=$((failed + 1)); }
warn() { printf 'CHECK: %s\n' "$*"; warnings=$((warnings + 1)); }
ok() { printf 'OK: %s\n' "$*"; }

for dep in docker curl; do
  if ! command -v "$dep" >/dev/null 2>&1; then
    printf 'ERROR: Missing executable: %s\n' "$dep" >&2
    exit 2
  fi
done

printf '%s\n' '=== Souq Qareeb production preflight (READ ONLY) ==='
printf 'Target: %s\n' "$url"
printf '%s\n' '=== Containers ==='
for item in "$wp_container" "$db_container"; do
  if docker inspect --format '{{.State.Running}}' "$item" 2>/dev/null | grep -qx true; then
    ok "$item running"
  else
    fail "$item not running or inaccessible"
  fi
done

printf '%s\n' '=== Public HTTP pages ==='
for path in / /shop/ /cart/ /checkout/ /my-account/ /add-listing/; do
  status="$(curl --silent --show-error --location --connect-timeout 8 --max-time 25 \
    --output /dev/null --write-out '%{http_code}' "$url$path")"
  rc=$?
  if [[ "$rc" -eq 0 && "$status" == 200 ]]; then
    ok "$path HTTP 200"
  else
    fail "$path HTTP ${status:-000} (curl exit $rc)"
  fi
done

printf '%s\n' '=== Approved theme images (HTTP only) ==='
for img in logo-header.png hero-mobile-image.webp sell.webp exchange.webp donate.webp; do
  full="$url/wp-content/themes/souq-qareeb/assets/images/$img"
  response="$(curl --silent --show-error --location --connect-timeout 8 --max-time 25 \
    --output /dev/null --write-out '%{http_code} %{content_type}' "$full")"
  rc=$?
  read -r status mime <<< "$response"
  if [[ "$rc" -eq 0 && "$status" == 200 && "$mime" == image/* ]]; then
    ok "$img HTTP 200 ($mime)"
  else
    warn "$img unavailable / unexpected response: ${status:-000} ${mime:-unknown}"
  fi
done

printf '%s\n' '=== WordPress/WooCommerce settings ==='
if docker inspect --format '{{.State.Running}}' "$wp_container" 2>/dev/null | grep -qx true; then
  if docker exec -i -u www-data "$wp_container" php <<'PHP'
<?php
define('DISABLE_WP_CRON', true);
require '/var/www/html/wp-load.php';
printf("Site: %s\n", esc_url_raw(home_url('/')));
printf("Theme: %s\n", get_stylesheet());
printf("WooCommerce: %s\n", class_exists('WooCommerce') ? 'active' : 'NOT ACTIVE');
printf("Currency: %s\n", get_option('woocommerce_currency', '(not set)'));
printf("Price decimal digits: %s\n", get_option('woocommerce_price_num_decimals', '(not set)'));
$guard = WPMU_PLUGIN_DIR . '/souq-demo-checkout.php';
printf("Sandbox checkout guard file: %s\n", is_file($guard) ? 'present' : 'MISSING');
$settings = get_option('woocommerce_stripe_settings', array());
if (!is_array($settings)) { $settings = array(); }
printf("Stripe gateway enabled: %s\n", $settings['enabled'] ?? '(unset)');
printf("Stripe TEST mode: %s\n", $settings['testmode'] ?? '(unset)');
printf("WooCommerce customer registration: %s\n", get_option('woocommerce_enable_myaccount_registration', '(unset)'));
$plugins = (array) get_option('active_plugins', array());
$mail = array_values(array_filter($plugins, static function ($p) {
    return is_string($p) && preg_match('/smtp|mail|postmark|sendgrid/i', $p);
}));
printf("Mail-related plugins active: %s\n", $mail ? implode(', ', $mail) : '(none detected)');
echo "Mail delivery: NOT TESTED (no email sent by audit)\n";
PHP
  then
    ok "WordPress settings probe completed (no private keys printed)"
  else
    fail "Unable to read WordPress settings inside container"
  fi
else
  warn "WordPress settings probe skipped (container down)"
fi

printf '%s\n' '=== Backup inventory (metadata only) ==='
if [[ -d "$backup_dir" ]]; then
  total="$(find "$backup_dir" -type f \( -iname '*.dump' -o -iname '*.dump.gz' -o -iname '*.sql' -o -iname '*.sql.gz' \) | wc -l | tr -d ' ')"
  snapshots="$(find "$backup_dir" -type f | wc -l | tr -d ' ')"
  printf 'Potential database dump files: %s\n' "$total"
  printf 'Total files in backup directory: %s\n' "$snapshots"
  if [[ "$total" -eq 0 ]]; then
    warn "No database dumps detected in $backup_dir (may be stored elsewhere)"
  else
    ok "Database backup candidates present; restoration NOT tested"
  fi
else
  warn "Backup directory not present at configured path; check actual backup storage"
fi
echo 'Note: File presence does not prove a backup can be restored.'
echo 'Note: HTTP 200 does not prove functional/security correctness.'
printf 'SUMMARY: failures=%s warnings=%s\n' "$failed" "$warnings"
if [[ "$failed" -gt 0 ]]; then exit 1; fi
exit 0
