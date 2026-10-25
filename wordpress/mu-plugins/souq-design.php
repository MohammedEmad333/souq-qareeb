<?php
/**
 * Plugin Name: Souq Qareeb Design
 * Description: Responsive Arabic marketplace design system.
 * Version: 3.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    wp_register_style('souq-design', false, [], '3.0.0');
    wp_enqueue_style('souq-design');
    $css = <<<'CSS'
:root {
  --sq-green: #176b55;
  --sq-green-dark: #10513f;
  --sq-ink: #213b30;
  --sq-muted: #64756e;
  --sq-background: #f7f6f1;
  --sq-surface: #ffffff;
  --sq-outline: #e3e8e1;
  --sq-accent: #eba45c;
  --sq-radius: 18px;
  --sq-shadow: 0 8px 32px rgba(26, 55, 43, .065);
}
body {
  color: var(--sq-ink);
  background: var(--sq-background);
  font-family: Tahoma, "Segoe UI", Arial, sans-serif;
}
body .wp-site-blocks { overflow-x: clip; }
body a { text-underline-offset: 4px; }
body a:focus-visible, body button:focus-visible, body input:focus-visible,
body select:focus-visible { outline: 3px solid var(--sq-accent); outline-offset: 3px; }
body .wp-block-button__link, body button[type="submit"] {
  border-radius: 11px;
  transition: background .2s ease, transform .2s ease;
}
body .wp-block-button__link:hover, body button[type="submit"]:hover {
  background: var(--sq-green-dark);
}
body .wp-block-site-logo img { max-width: 100%; height: auto; }
body .wp-block-navigation a { font-weight: 600; }
body .wp-block-navigation a:hover { color: var(--sq-green); }
body.woocommerce-shop .wp-site-blocks > main {
  margin-block-start: 0 !important;
  padding-block-start: 0 !important;
}
.souq-hero {
  max-width: 1240px;
  margin: 18px auto 26px;
  padding: clamp(26px, 5vw, 58px);
  background: linear-gradient(125deg, #155d49, #23795e);
  border-radius: 24px;
  color: #fff;
  position: relative;
  overflow: hidden;
  isolation: isolate;
}
.souq-hero:after {
  content: "";
  position: absolute;
  inset-inline-end: -70px;
  top: -130px;
  width: 330px;
  height: 330px;
  border: 56px solid rgba(255,255,255,.065);
  border-radius: 50%;
  z-index: -1;
}
.souq-hero__eyebrow {
  display: inline-block;
  background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.22);
  padding: 7px 14px;
  border-radius: 30px;
  font-size: .88rem;
  margin-bottom: 16px;
}
.souq-hero h2 {
  margin: 0 0 12px;
  color: #fff;
  font-size: clamp(1.8rem, 4.4vw, 3.1rem);
  line-height: 1.3;
  letter-spacing: -.02em;
  max-width: 680px;
}
.souq-hero p { color: #eaf5ee; max-width: 600px; margin: 0 0 21px; font-size: 1.06rem; }
.souq-hero__links { display: flex; flex-wrap: wrap; gap: 11px; }
.souq-hero__links a {
  padding: 11px 19px;
  border-radius: 11px;
  font-weight: 700;
  text-decoration: none;
}
.souq-hero__links a:first-child { background: #fff; color: var(--sq-green-dark); }
.souq-hero__links a:last-child { border: 1px solid rgba(255,255,255,.6); color: #fff; }
body.woocommerce-shop .souq-filters {
  max-width: 1240px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  padding: 24px;
  gap: 16px;
  border: 1px solid var(--sq-outline);
  background: var(--sq-surface);
  box-shadow: var(--sq-shadow);
  border-radius: var(--sq-radius);
}
body.woocommerce-shop .souq-filters input,
body.woocommerce-shop .souq-filters select {
  border-radius: 11px;
  border-color: var(--sq-outline);
  min-height: 47px;
}
body.woocommerce-shop .souq-filters button {
  border-radius: 11px;
  min-height: 47px;
  font-weight: 700;
}
body.woocommerce-shop .wp-block-woocommerce-product-collection ul.wc-block-product-template {
  display: grid !important;
  grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
  gap: clamp(14px, 2.3vw, 25px) !important;
  width: 100% !important;
}
body.woocommerce-shop ul.wc-block-product-template > li.wc-block-product {
  min-width: 0 !important;
  max-width: none !important;
  width: 100% !important;
  margin: 0 !important;
  float: none !important;
  text-align: start;
  background: var(--sq-surface);
  border: 1px solid var(--sq-outline);
  border-radius: var(--sq-radius);
  box-shadow: var(--sq-shadow);
  overflow: hidden;
  padding: 12px !important;
  transition: transform .2s ease, box-shadow .2s ease;
}
body.woocommerce-shop ul.wc-block-product-template > li.wc-block-product:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 38px rgba(26,55,43,.11);
}
body.woocommerce-shop ul.wc-block-product-template li.wc-block-product img {
  width: 100%;
  aspect-ratio: 4/3;
  object-fit: cover;
  border-radius: 12px;
}
body.woocommerce-shop .wp-block-post-title { line-height: 1.45; font-weight: 700; }
body.woocommerce-shop .wc-block-components-product-price { font-weight: 800; color: var(--sq-green); }
body.woocommerce-shop .souq-product-location {
  margin-inline: 0;
  border-radius: 30px;
  font-weight: 600;
}
body.woocommerce-shop .wp-block-button__link {
  border-radius: 11px;
  padding: 11px 15px;
  font-size: .92rem;
  font-weight: 700;
}
@media (max-width: 900px) {
  body.woocommerce-shop .wp-block-woocommerce-product-collection ul.wc-block-product-template {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }
}
@media (max-width: 700px) {
  .souq-hero { margin: 10px 12px 20px; border-radius: 18px; padding: 26px 20px; }
  .souq-hero:after { width: 200px; height: 200px; top: -95px; }
  .souq-hero h2 { font-size: 1.7rem; }
  body.woocommerce-shop .souq-filters { grid-template-columns: 1fr; padding: 16px; margin: 16px 12px 24px; }
  body.woocommerce-shop .wp-block-woocommerce-product-collection ul.wc-block-product-template {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 12px !important;
  }
  body.woocommerce-shop ul.wc-block-product-template > li.wc-block-product { padding: 9px !important; border-radius: 13px; }
  body.woocommerce-shop ul.wc-block-product-template li.wc-block-product img { aspect-ratio: 1 / 1; }
  body.woocommerce-shop .wp-block-woocommerce-product-collection ul.wc-block-product-template:has(> li:only-child) {
    grid-template-columns: minmax(0, 1fr) !important;
    max-width: 390px;
    margin-inline: auto !important;
  }
  body.woocommerce-shop h1 { font-size: clamp(1.6rem, 7vw, 2rem); }
}
@media (prefers-reduced-motion: reduce) {
  body.woocommerce-shop ul.wc-block-product-template > li.wc-block-product { transition: none; }
}
CSS;
    wp_add_inline_style('souq-design', $css);
}, 20);

/* Render one accessible storefront introduction before the existing search form. */
add_filter('render_block_woocommerce/product-collection', function ($content) {
    if (!function_exists('is_shop') || !is_shop()) {
        return $content;
    }
    static $displayed = false;
    if ($displayed) {
        return $content;
    }
    $displayed = true;
    $shop = get_post_type_archive_link('product');
    $intro = '<section class="souq-hero" dir="rtl" aria-labelledby="souq-hero-title">'
        . '<span class="souq-hero__eyebrow">سوق قريب — من أهل غزة ولأهلها</span>'
        . '<h2 id="souq-hero-title">أغراض مفيدة، وفرص أقرب إليك</h2>'
        . '<p>اكتشف أغراضًا مستعملة بأسعار مناسبة، وابحث حسب المنطقة وطريقة التعامل: بيع أو تبديل أو تبرع.</p>'
        . '<div class="souq-hero__links">'
        . '<a href="' . esc_url($shop) . '#souq-products">تصفح المنتجات</a>'
        . '<a href="' . esc_url($shop) . '#souq-products">اكتشف المعروض</a>'
        . '</div></section>';
    return $intro . $content;
}, 11);

/* Hide default WordPress credit without affecting custom footer content. */
add_filter('render_block', function ($content, $block) {
    if (($block['blockName'] ?? '') === 'core/paragraph'
        && stripos($content, 'Proudly powered by') !== false
        && stripos($content, 'wordpress.org') !== false) {
        return '';
    }
    return $content;
}, 10, 2);
