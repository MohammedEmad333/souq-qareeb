/**
 * Ephemeral GitHub Actions browser smoke: no production, staging, Stripe credentials or real payments.
 * QA_PRODUCT_URL, QA_CART_URL, QA_CHECKOUT_URL, QA_PRODUCT_ID and QA_ACCOUNT_URL originate from disposable WP.
 */
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';

const root = process.env.QA_SITE_ROOT;
const productURL = process.env.QA_PRODUCT_URL;
const cartURL = process.env.QA_CART_URL;
const checkoutURL = process.env.QA_CHECKOUT_URL;
const accountURL = process.env.QA_ACCOUNT_URL;
const wordpressPath = process.env.QA_WP_PATH;
const testScript = process.env.QA_FIXTURE_SCRIPT;

for (const [name, value] of Object.entries({ root, productURL, cartURL, checkoutURL, accountURL, wordpressPath, testScript })) {
  assert.ok(value, `Missing ${name}`);
}
for (const url of [root, productURL, cartURL, checkoutURL, accountURL]) {
  assert.equal(new URL(url).hostname, '127.0.0.1', 'Only loopback browser tests allowed');
}

const browser = await chromium.launch({ headless: true });
const buyerA = await browser.newContext();
const buyerB = await browser.newContext();
const pageA = await buyerA.newPage();
const pageB = await buyerB.newPage();
try {
  // Real Stripe PHP renderer in a static, isolated fixture: no payment provider request.
  await pageA.goto(new URL('/sq-stripe-copy-qa.html', root).toString());
  const translated = 'حفظ بيانات الدفع في حسابي لاستخدامها في عمليات الشراء القادمة.';
  const save = pageA.getByRole('checkbox', { name: translated, exact: true });
  assert.equal(await save.isChecked(), false, 'Saving must remain opt-in');
  await save.check();
  assert.equal(await save.isChecked(), true, 'Translated label stays associated with the checkbox');
  await save.uncheck();
  assert.equal(await save.isChecked(), false);
  mkdirSync('stripe-copy-evidence', { recursive: true });
  for (const width of [375, 430, 768, 1366, 1920]) {
    await pageA.setViewportSize({ width, height: 900 });
    assert.equal(await save.isVisible(), true);
    const overflow = await pageA.evaluate(() => document.documentElement.scrollWidth > innerWidth);
    assert.equal(overflow, false, 'Arabic save-card label must fit the viewport');
    await pageA.screenshot({ path: `stripe-copy-evidence/stripe-copy-${width}.png` });
  }
  console.log('PASS: real Stripe checkbox Arabic label, opt-in, association and 5 viewports');

  // Anonymous user sees the live-rendered WordPress product and can add it to cart.
  await pageA.goto(productURL, { waitUntil: 'domcontentloaded' });
  await assert.doesNotReject(() => pageA.getByText('QA Unique Item - Browser Test').first().waitFor({ timeout: 12000 }));
  const add = pageA.locator('button.single_add_to_cart_button');
  await add.waitFor({ timeout: 12000 });
  assert.equal(await add.isDisabled(), false, 'Available one-off item should be purchasable');
  await add.click();
  await pageA.goto(cartURL, { waitUntil: 'domcontentloaded' });
  await pageA.getByText('QA Unique Item - Browser Test').first().waitFor({ timeout: 12000 });
  console.log('PASS: browser A added unique item to persistent WooCommerce cart');

  // Guest checkout can render, but the demo safeguard must not offer payment gateways.
  await pageA.goto(checkoutURL, { waitUntil: 'domcontentloaded' });
  await pageA.locator('body').waitFor();
  assert.equal(await pageA.locator('input[name="payment_method"][value="stripe"]').count(), 0, 'Guest must never see Stripe');
  assert.equal(await pageA.locator('input[name="payment_method"][value="cod"]').count(), 0, 'Guest must never see COD');
  console.log('PASS: guest checkout does not expose Stripe/COD payment method controls');

  // Execute a real WooCommerce payment_complete() in WP-CLI, WITHOUT a payment provider.
  execFileSync('wp', ['eval-file', testScript, '--path=' + wordpressPath], {
    env: { ...process.env, SQ_QA_PHASE: 'complete', WP_CLI_ALLOW_ROOT: '1' },
    stdio: 'inherit', timeout: 60000,
  });

  // A separate browser session cannot buy the exhausted one-off product.
  const soldPage = await pageB.goto(productURL, { waitUntil: 'domcontentloaded' });
  assert.ok([200, 404].includes(soldPage.status()), 'Sold product may be viewable or absent, never purchasable');
  const soldAdd = pageB.locator('button.single_add_to_cart_button');
  assert.ok((await soldAdd.count()) === 0 || await soldAdd.isDisabled(), 'Sold item has no enabled add-to-cart button');

  // Also attempt a direct add-to-cart URL, bypassing the product page UI.
  const productID = Number(process.env.QA_PRODUCT_ID);
  assert.ok(Number.isSafeInteger(productID) && productID > 0, 'Synthetic product id required');
  await pageB.goto(new URL('/?add-to-cart=' + productID, root).toString(), { waitUntil: 'domcontentloaded' });
  await pageB.goto(cartURL, { waitUntil: 'domcontentloaded' });
  assert.equal(await pageB.locator('.woocommerce-cart-form .cart_item').count(), 0, 'Buyer B cannot add sold item even via direct URL');
  console.log('PASS: browser B cannot purchase exhausted item, including direct add-to-cart');

  await pageB.goto(accountURL, { waitUntil: 'domcontentloaded' });
  await pageB.locator('body').waitFor();
  console.log('PASS: My Account page served in second browser session');
  console.log('PASS: all disposable browser and WooCommerce smoke checks complete');
} finally {
  await buyerA.close();
  await buyerB.close();
  await browser.close();
}
