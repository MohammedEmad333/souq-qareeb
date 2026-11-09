/** Actual HTTP uploads and owner/CSRF checks against disposable loopback WordPress. */
import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync, mkdirSync } from 'node:fs';
const editURL = process.env.QA_PHOTO_EDIT_URL;
assert.equal(new URL(editURL).hostname, '127.0.0.1');
const root = new URL(editURL).origin;
const browser = await chromium.launch({ headless: true });
const owner = await browser.newContext();
const other = await browser.newContext();
const page = await owner.newPage();
async function login(context, username) {
  const loginPage = await context.newPage();
  await loginPage.goto(root + '/wp-login.php');
  await loginPage.locator('#user_login').fill(username);
  await loginPage.locator('#user_pass').fill('disposable-photo-qa-only');
  await loginPage.locator('#wp-submit').click();
  await loginPage.close();
}
function verify(phase) {
  execFileSync('wp', ['eval-file', process.env.QA_PHOTO_SCRIPT, '--path=' + process.env.QA_WP_PATH], {
    env: { ...process.env, SQ_PHOTO_PHASE: phase, WP_CLI_ALLOW_ROOT: '1' }, stdio: 'inherit', timeout: 60000,
  });
}
async function fields() {
  return await page.locator('#sq-listing-form').evaluate(form => Object.fromEntries(
    Array.from(new FormData(form)).filter(([, value]) => typeof value === 'string')
  ));
}
try {
  await login(owner, 'qa_photo_owner');
  await login(other, 'qa_photo_other');
  await page.goto(editURL);
  const file = page.locator('#sq-listing-photos');
  assert.equal(await file.getAttribute('required'), null, 'Replacement is optional');
  assert.equal(await page.locator('[aria-label="صور الإعلان الحالية"] img').count(), 1);
  const originalFields = await fields();
  const endpoint = root + '/wp-admin/admin-post.php';
  const denied = await other.request.post(endpoint, { form: originalFields });
  assert.equal(denied.status(), 403, 'Other customer cannot edit owner listing');
  const csrf = await owner.request.post(endpoint, { form: { ...originalFields, sq_listing_nonce: 'invalid' } });
  assert.equal(csrf.status(), 403, 'Invalid nonce is rejected');
  verify('unchanged');
  // No replacement: all original media remain attached.
  await page.locator('#sq-listing-form button[type="submit"]').click();
  await page.waitForURL(url => url.searchParams.get('sq_notice') === 'updated');
  verify('unchanged');
  await page.goto(editURL);
  const validFields = await fields();
  const rejected = await owner.request.post(endpoint, { multipart: { ...validFields,
    'sq_photos[]': { name: 'fake.png', mimeType: 'image/png', buffer: Buffer.from('not an image') },
  } });
  assert.equal(new URL(rejected.url()).searchParams.get('sq_listing_status'), 'photos', 'Server rejects spoofed image');
  verify('unchanged');
  // Clear an invalid selection: optional input must recover its validity.
  await file.setInputFiles({ name: 'wrong.txt', mimeType: 'text/plain', buffer: Buffer.from('invalid') });
  assert.equal(await file.evaluate(el => el.validity.customError), true);
  await file.setInputFiles([]);
  assert.equal(await file.evaluate(el => el.validity.valid), true);
  await file.setInputFiles([
    { name: 'qa-new-featured.webp', mimeType: 'image/webp', buffer: readFileSync('wordpress/themes/souq-qareeb/assets/images/hero-mobile-image.webp') },
    { name: 'qa-new-gallery.webp', mimeType: 'image/webp', buffer: readFileSync('wordpress/themes/souq-qareeb/assets/images/exchange.webp') },
  ]);
  assert.equal(await page.locator('#sq-listing-photo-previews img').count(), 2);
  mkdirSync('listing-photo-evidence', { recursive: true });
  for (const width of [375, 430, 768, 1366, 1920]) {
    await page.setViewportSize({ width, height: 900 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
    await page.screenshot({ path: `listing-photo-evidence/edit-${width}.png`, fullPage: true });
  }
  await page.locator('#sq-listing-form button[type="submit"]').click();
  await page.waitForURL(url => url.searchParams.get('sq_notice') === 'updated');
  verify('replaced');
  await page.goto(editURL);
  assert.equal(await page.locator('[aria-label="صور الإعلان الحالية"] img').count(), 2);
  console.log('PASS: optional/replacement uploads, original preservation, MIME rejection, owner/CSRF checks and 5 viewports');
} finally { await owner.close(); await other.close(); await browser.close(); }
