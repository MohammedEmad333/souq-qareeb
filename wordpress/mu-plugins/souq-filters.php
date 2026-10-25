<?php
/**
 * Plugin Name: Souq Qareeb Filters
 * Description: Search and filter WooCommerce products.
 * Version: 2.1.0
 */
if (!defined('ABSPATH')) exit;

function souq_filters_input($key) {
    if (!isset($_GET[$key]) || !is_string($_GET[$key])) {
        return '';
    }
    return sanitize_text_field(wp_unslash($_GET[$key]));
}

add_filter('render_block', function ($content, $block) {
    if (!function_exists('is_shop') || !is_shop()) {
        return $content;
    }

    if (($block['blockName'] ?? '') !== 'woocommerce/product-collection') {
        return $content;
    }

    static $shown = false;
    if ($shown) return $content;
    $shown = true;

    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ]);

    $deals = get_terms([
        'taxonomy' => 'souq_deal_type',
        'hide_empty' => false,
    ]);

    $areas = get_terms([
        'taxonomy' => 'souq_area',
        'hide_empty' => false,
    ]);

    ob_start();
    ?>
    <form class="souq-filters" method="get"
      action="<?php echo esc_url(get_post_type_archive_link('product')); ?>">

      <label>
        <span>ابحث عن منتج</span>
        <input type="search" name="sq_search"
          placeholder="مثلاً: كرسي أو حقيبة"
          value="<?php echo esc_attr(souq_filters_input('sq_search')); ?>">
      </label>

      <?php
      $groups = [
          ['sq_cat', 'نوع المنتج', 'جميع الأنواع', $categories],
          ['sq_deal', 'طريقة التعامل', 'جميع الطرق', $deals],
          ['sq_area', 'المنطقة', 'جميع المناطق', $areas],
      ];

      foreach ($groups as [$key, $label, $all, $terms]):
          if (is_wp_error($terms)) $terms = [];
      ?>
        <label>
          <span><?php echo esc_html($label); ?></span>
          <select name="<?php echo esc_attr($key); ?>">
            <option value=""><?php echo esc_html($all); ?></option>
            <?php foreach ($terms as $term):
              if ($term->slug === 'uncategorized') continue; ?>
              <option value="<?php echo (int) $term->term_id; ?>"
                <?php selected(souq_filters_input($key), (string) $term->term_id); ?>>
                <?php echo esc_html($term->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endforeach; ?>

      <button type="submit">بحث وتصفية</button>
      <a class="souq-clear"
        href="<?php echo esc_url(get_post_type_archive_link('product')); ?>">
        مسح الفلاتر
      </a>
    </form>
    <?php
    return ob_get_clean() . $content;
}, 9, 2);

add_action('pre_get_posts', function ($query) {
    if (
        is_admin() ||
        !$query->is_main_query() ||
        !$query->is_post_type_archive('product')
    ) {
        return;
    }

    $search = souq_filters_input('sq_search');
    if ($search !== '') {
        $query->set('s', $search);
    }

    $tax_query = $query->get('tax_query');
    $tax_query = is_array($tax_query) ? $tax_query : [];

    foreach ([
        'sq_cat' => 'product_cat',
        'sq_deal' => 'souq_deal_type',
        'sq_area' => 'souq_area',
    ] as $key => $taxonomy) {
        $value = souq_filters_input($key);
        if ($value === '') continue;

        $id = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        $term = $id ? get_term($id, $taxonomy) : null;

        if (!$term instanceof WP_Term) {
            $query->set('post__in', [0]);
            return;
        }

        $tax_query[] = [
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => [$id],
        ];
    }

    if ($tax_query) {
        $query->set('tax_query', $tax_query);
    }
}, 999);

add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_shop') || !is_shop()) return;

    wp_register_style('souq-filters', false, [], '2.0.0');
    wp_enqueue_style('souq-filters');

    wp_add_inline_style('souq-filters', '
      .souq-filters {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:12px;
        align-items:end;
        margin:20px auto 28px;
        padding:18px;
        background:#f4f8f6;
        border-radius:14px;
      }
      .souq-filters label {
        display:flex;
        flex-direction:column;
        gap:6px;
        font-size:14px;
        font-weight:600;
      }
      .souq-filters input,
      .souq-filters select {
        box-sizing:border-box;
        width:100%;
        min-width:0;
        padding:11px;
        background:white;
        border:1px solid #cbd8d1;
        border-radius:8px;
        font:inherit;
      }
      .souq-filters button {
        background:#176b55;
        color:white;
        border:0;
        border-radius:8px;
        padding:12px 16px;
        font:inherit;
        cursor:pointer;
      }
      .souq-clear {
        color:#176b55;
        text-align:center;
        padding:12px;
      }
      @media(max-width:700px) {
        .souq-filters {
          grid-template-columns:1fr;
          padding:14px;
        }
      }
    ');
});
