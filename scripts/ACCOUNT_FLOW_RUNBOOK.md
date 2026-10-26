# Souq Qareeb — Unified Customer Accounts Rollout

## Scope
- WordPress/WooCommerce user accounts only; new registrants use WooCommerce customer role.
- My Account login, registration, lost password, address, profile and orders remain native WooCommerce.
- User-supplied first and last name are validated and saved as customer billing identity.
- New Woo endpoint: `my-listings`. Only authored listings with `_sq_listing_type` meta appear.
- Users can withdraw their own published/pending listings to Draft. Only moderators can publish.
- No unbuilt "favorites" or "messages" features are advertised.

## Production deployment
1. Back up **all modified theme files** and the production database. Confirm the production WordPress container is healthy.
2. Deploy `functions.php`, `inc/accounts.php` and `assets/css/storefront.css` from a green merged PR; keep original brand images.
3. `php -l` for both PHP files and reload the /my-account/ page while registration is still disabled.
4. In WordPress context, run `flush_rewrite_rules(false)` once. Visit /my-account/my-listings/ while logged in; ensure 200 and no other user's ads.
5. Check outgoing mail before enabling public registration, including password reset and native WooCommerce welcome emails. Set appropriate mail transport and bot/rate protection. WooCommerce > Settings > Accounts & Privacy > Allow customers to create an account on My account page.
6. Optionally generate usernames from email and **choose** the Woo password setup flow according to mail readiness. Do not enable WordPress general "Anyone can register" as a workaround.
7. Exercise the registration flow with a fresh **non-admin** account in a private browser. Ensure created WP role is customer, not administrator/editor/shop_manager.
8. Submit a test listing with a photo; assert status Pending and correct post_author. Test another customer cannot view or withdraw it.
9. Admin approves one test listing. Verify it appears on the shop and in its author's My Listings tab.
10. Withdraw it as its owner; verify draft is not discoverable in catalog. Re-submit separately for editorial update (no front-end editing implemented yet).
11. Verify native account edit, logout/login, lost password, and existing cart/account flows still work.

## Explicit exclusions / security notes
- Admin approval is required; no automatic publishing of user ads.
- No file-upload modifications are part of this PR.
- No own-listing editing yet; withdrawing is supported. Editing requires a separate owner-safe edit flow.
- Public self-registration should not be enabled before mail and abuse-protection checks.
- A permalink rewrite flush is necessary for a new endpoint if it returns 404.

## Rollback
Restore the three theme paths from the deployment's timestamped backup. If account registration was turned on during rollout, set `woocommerce_enable_myaccount_registration` back to `no` if necessary; existing accounts are not deleted. Flushing rewrite rules again is optional after restoring the theme.
