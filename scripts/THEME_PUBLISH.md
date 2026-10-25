# Guarded block-theme publishing

**No automatic deployment.** This tool only manages three existing Souq WordPress block-theme database records. It is independent from the seven MU-plugin deployment files.

The `wordpress/baseline/` directory holds frozen copies of the production records as of 2026-10-25. `wordpress/exports/` contains desired values. To propose a layout change, edit the desired export, not the baseline. Deployments must stop if the live content no longer matches the baseline.

```bash
cd ~/souq-qareeb/source
bash scripts/publish-block-theme.sh --dry-run
# Only after PR review, CI and a separate database backup:
bash scripts/publish-block-theme.sh --apply
```

Before writing, `--apply` runs a drift check, creates a private backup using the existing recovery tool, then requires typing `APPLY`. PHP rechecks all three records before writing and attempts best-effort rollback on partial update failure.

**Limitations:** The update is not a database transaction. Navigation references use database-specific IDs and links. Check header/menu behavior, checkout guard and site immediately afterward. Recovery utility: `bash scripts/theme-records.sh --restore BACKUP_DIRECTORY`. Also keep a full DB backup. Updates made through WordPress admin will block future deployments until a reviewer updates the baseline through a new PR.

Never commit backups, credentials, user data, database dumps or media to this public repo.
