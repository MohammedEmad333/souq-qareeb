# Souq Qareeb — isolated staging preview

The staging Compose file is **standalone** and does not share the production database, media, or WordPress volume. It binds to localhost:8096 (production uses localhost:8095). It does not publish a public domain, initialize WordPress, activate the theme, or install WooCommerce.

## Launch (on the project server)

```bash
cd ~/souq-qareeb/source
git pull --ff-only origin main
umask 077
cat > .env.staging <<'EOF'
STAGING_DB_PASSWORD=REPLACE_WITH_LONG_RANDOM_PASSWORD
STAGING_DB_ROOT_PASSWORD=REPLACE_WITH_ANOTHER_LONG_RANDOM_PASSWORD
EOF
chmod 600 .env.staging
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml config --quiet
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml up -d
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml ps
```

Replace both placeholder passwords with independent, randomly generated secrets. Do not commit `.env.staging`. If the staging changes are still in a PR, check out the feature branch **in a separate worktree**; do not switch the production checkout.

To reach the non-public staging from a laptop, use SSH forwarding (change the host to your server):

```bash
ssh -L 8096:127.0.0.1:8096 ubuntu@YOUR_SERVER
```

Then open http://localhost:8096 and complete the **new staging WordPress** setup. Install/activate WooCommerce only in staging, create sample products without sensitive real customer data, then activate *Souq Qareeb* in Appearance → Themes. In Settings → Reading, select an appropriate homepage. Do **not** copy live customer orders or passwords.

Test homepage RTL, search, product archive/detail, cart, checkout with test/sandbox payments, My Account, menus and mobile responsiveness. For rollback, activate the previous theme within staging. To stop, use:

```bash
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml down
```

Never use `down -v` unless intentionally deleting all staging data. Never point staging at the production database. Staging is localhost-only; no reverse proxy or public exposure is configured.
