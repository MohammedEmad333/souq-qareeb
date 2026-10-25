# Souq Qareeb custom storefront (initial scaffold)

This classic WordPress theme is intentionally **not activated automatically**.

## Review and preview

1. Back up the production MariaDB database and WordPress uploads before any activation.
2. Copy `wordpress/themes/souq-qareeb/` into the persistent WordPress data volume at `/var/www/html/wp-content/themes/souq-qareeb/`. The current Compose mounts a **named volume**, not the repository's theme directory.
3. Prefer testing on a staging WordPress instance first; in WP Admin → Appearance → Themes, preview/activate **Souq Qareeb** on staging.
4. Check the home page, mobile RTL navigation, shop/catalog, product detail, cart, checkout, My Account, login, and saved WordPress pages.
5. Assign a menu to **Primary navigation** in Appearance → Menus. WooCommerce must remain active to render the featured product grid.
6. Roll back by reactivating the previous theme; restore from backups only when needed.

No migration of theme editor templates or global styles occurs here. Existing `wp_template`, `wp_navigation`, and `wp_global_styles` records are preserved. Custom features (listing types, regions, swap/donation flows) require a dedicated plugin and are **not yet implemented**.

## Development status

Initial RTL storefront scaffold, not a production-ready replacement. Follow-up work: product card styling, storefront search/filter UX, footer navigation, accessibility and WooCommerce integration checks.
