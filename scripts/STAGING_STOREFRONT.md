# Souq Qareeb — isolated staging preview

The staging Compose file is **standalone** and does not share the production database, media, or WordPress volume. It binds to localhost:8096 (production uses localhost:8095). It does not publish a public domain, initialize WordPress, activate the theme, or install WooCommerce.

The Souq Qareeb theme and the complete tracked `wordpress/mu-plugins/` directory are mounted read-only from this checkout. This includes the demo checkout guard, catalog taxonomies, Arabic labels, filters, design, product details and product-location helpers. The WordPress core, database and uploaded media remain in staging-only named volumes.

## Launch (on the project server)

Use a **separate detached worktree from the current main branch**; never switch or modify the production checkout. The detached worktree avoids depending on a feature branch that may be deleted after its PR is merged:

```bash
cd ~/souq-qareeb
git fetch origin main
git worktree add --detach ../souq-qareeb-staging origin/main
cd ../souq-qareeb-staging
umask 077
cat > .env.staging <<'EOF'
STAGING_DB_PASSWORD=REPLACE_WITH_LONG_RANDOM_PASSWORD
STAGING_DB_ROOT_PASSWORD=REPLACE_WITH_ANOTHER_LONG_RANDOM_PASSWORD
EOF
chmod 600 .env.staging
python3 scripts/test-staging-isolation.py
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml config --quiet
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml up -d
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml ps
```

Replace both placeholder passwords with independent, randomly generated secrets. Do not commit `.env.staging`. The workflow validates the Compose definition and asserts that the theme and MU plugins are mounted read-only, the staging port is loopback-only, and staging uses its own named volumes.

To refresh the staging source after changes are merged to `main`, stop the staging stack first, then update only its detached worktree:

```bash
cd ~/souq-qareeb-staging
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml down
git fetch origin main
git checkout --detach origin/main
python3 scripts/test-staging-isolation.py
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml up -d
```

To reach the non-public staging from a laptop, use SSH forwarding (change the host to your server):

```bash
ssh -L 8096:127.0.0.1:8096 ubuntu@YOUR_SERVER
```

Then open http://localhost:8096 and complete the **new staging WordPress** setup. Install/activate WooCommerce only in staging, create sample products without sensitive real customer data, then activate *Souq Qareeb* in Appearance → Themes. In Settings → Reading, select an appropriate homepage. The mounted MU plugins load automatically; do not copy them into the production container or disable the checkout guard.

## Acceptance checks

Test homepage RTL, search, product archive/detail, cart, checkout with Stripe test mode only, My Account, menus and mobile responsiveness. Create two disposable customer accounts and verify that each account can only edit or withdraw its own listings. Verify listing submission remains pending until moderator approval. Test password-reset email only after a staging SMTP provider is configured. Verify Stripe test checkout and webhook delivery using test credentials; do not use real cards or production webhook secrets.

A database restore test must use a disposable staging database and a matching `wp-content` backup. Do not copy live customer orders or passwords into a publicly reachable environment.

For rollback, stop the staging stack and keep its data for investigation:

```bash
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml down
```

Never use `down -v` unless intentionally deleting all staging data. Never point staging at the production database. Staging is localhost-only; no reverse proxy or public exposure is configured.
