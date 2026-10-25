# Block-theme snapshot drift checking

The `wordpress/exports/*.json` files represent a read-only snapshot of production records. They are **not** automatically loaded by WordPress.

From the Oracle server, after a reviewed Git pull:

```bash
cd ~/souq-qareeb/source
bash scripts/check-block-theme.sh
```

This script copies a temporary PHP checker and the three snapshots into the running **Souq** WordPress container, then compares the `post_title` and exact `post_content` of records selected by post type and slug. It performs no database writes and removes temporary files when finished.

Exit codes: `0` all match, `1` differences or missing/ambiguous records, `2` configuration or snapshot error. It intentionally reports no raw page content, credentials or personal information.

**Limitations:** database record IDs, navigation links and absolute URLs are environment-specific. A matching snapshot doesn't guarantee front-end rendering or runtime behavior. Global styles may be rewritten or normalized by WordPress and trigger intentional drift. Never import these JSON files blindly. A separate reviewed procedure with backup and rollback is required for deployment.

This is a detection tool only. Existing site configuration and all other Docker services remain untouched.
