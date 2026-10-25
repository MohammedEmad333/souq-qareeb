# WordPress block-theme customization snapshots

These files are read-only snapshots of the production site's WordPress database records, exported on 2026-10-25 for version control and review.

- `header.json`: WordPress `wp_template_part`, slug `header`.
- `navigation.json`: WordPress `wp_navigation`, slug `navigation`.
- `global-styles.json`: WordPress `wp_global_styles`, slug `wp-global-styles-twentytwentythree`.

## Important limitations

These are **not** active template files or an automatic deployment mechanism. Changes to these snapshots do not change the live site.

The header contains `<!-- wp:navigation {"ref":4} /-->` which references a production-specific database record. The navigation links have production-specific page IDs (`8`, `9`, `10`, `11`) and absolute URLs. Such IDs may be different in another environment. Do not import these records blindly or overwrite edits made in the WordPress editor.

Check all content carefully before importing. Real content, media, user data, database secrets and backup archives should never be committed to this public repository.

The next step is to build a reviewed, reversible, environment-aware synchronization process with backups and drift checks, or migrate reusable layout to a versioned child/block theme.
