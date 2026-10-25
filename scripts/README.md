# Safe MU-plugin deployment (manual)

This script **only** copies the seven tracked `souq-*.php` MU-plugin files into the running Souq WordPress container. It does not deploy the block-theme JSON exports, alter Docker configuration, touch the database, or interact with EduBridge.

Run from the Oracle host, after pulling a reviewed commit:

```bash
cd ~/souq-qareeb/source
bash scripts/deploy-mu-plugins.sh --dry-run
bash scripts/deploy-mu-plugins.sh --deploy
```

Defaults:
- Compose directory: parent of repository (`~/souq-qareeb`).
- Backups: `~/souq-qareeb/deploy-backups` outside the public Git repository.
- Overridable with `SOUQ_COMPOSE_DIR` and `SOUQ_BACKUP_DIR`.

Rollback a snapshot:

```bash
bash scripts/deploy-mu-plugins.sh --rollback /home/ubuntu/souq-qareeb/deploy-backups/mu-plugins-XXXXXXXX
```

## Safeguards and limits

- Dry-run is the default; it validates PHP 8.3 syntax and displays SHA-256 equality without modifying the website.
- Deployment requires `--deploy`, validates each candidate, creates a snapshot of all seven installed plugin files, then copies them.
- A copy failure triggers best-effort restoration of the snapshot.
- This is **not transactional**. PHP runtime errors, application behaviour, web traffic during file replacement, and filesystem permission changes may require manual rollback. Always test the live site afterward, including the demo checkout guard; keep original backups.
- Never commit credentials, `.env`, database dumps, actual uploads, backups or SSH private keys.
- Do not run `docker compose up` solely as part of updating these plugins. Existing WordPress and database Docker volumes are left untouched.
