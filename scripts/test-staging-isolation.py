#!/usr/bin/env python3
"""Static safety checks for the isolated Souq Qareeb staging Compose stack."""
from pathlib import Path
import re
import sys

root = Path(__file__).resolve().parents[1]
compose_path = root / "compose.staging.yaml"
plugin_dir = root / "wordpress" / "mu-plugins"
compose = compose_path.read_text(encoding="utf-8")

required = {
    "loopback-only staging port": r'127\.0\.0\.1:8096:80',
    "staging database name": r'WORDPRESS_DB_NAME:\s*souq_staging',
    "staging database host": r'WORDPRESS_DB_HOST:\s*database:3306',
    "separate WordPress volume": r'wp_staging:/var/www/html',
    "separate database volume": r'db_staging:/var/lib/mysql',
    "read-only theme mount": r'\./wordpress/themes/souq-qareeb:/var/www/html/wp-content/themes/souq-qareeb:ro',
    "read-only MU-plugin mount": r'\./wordpress/mu-plugins:/var/www/html/wp-content/mu-plugins:ro',
}
errors = []
for label, pattern in required.items():
    if not re.search(pattern, compose):
        errors.append(f"Missing or unsafe setting: {label}")

plugins = sorted(plugin_dir.glob("souq-*.php"))
if len(plugins) != 7:
    errors.append(f"Expected 7 tracked Souq MU plugins, found {len(plugins)}")
if not (plugin_dir / "souq-demo-checkout.php").is_file():
    errors.append("Demo checkout guard is missing from the staging source")

for forbidden in ("souq-qareeb-database", "production_db", "prod_database", "8095:"):
    if forbidden in compose:
        errors.append(f"Production reference found in staging Compose: {forbidden}")

if errors:
    print("Staging isolation checks FAILED:")
    for error in errors:
        print(f" - {error}")
    sys.exit(1)

print(f"Staging isolation checks passed ({len(plugins)} MU plugins, isolated volumes, loopback port).")
