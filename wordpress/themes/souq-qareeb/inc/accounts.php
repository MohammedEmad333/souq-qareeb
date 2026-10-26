<?php
/**
 * Unified Souq Qareeb customer accounts and moderated "My Listings".
 *
 * WooCommerce owns authentication, password resets, account editing and orders.
 * No duplicate passwords, new user roles or public user-management endpoints.
 *
 * @package SouqQareeb
 */
defined( 'ABSPATH' ) || exit;

/** Add a WooCommerce account tab. Requires a one-time permalink flush after deployment. */
add_action( 'init', function () {
    if ( class_exists( 'WooCommerce' ) ) {
        add_rewrite_endpoint( 'my-listings', EP_ROOT | EP_PAGES );
    }
} );

add_filter( 'woocommerce_get_query_vars', function ( $vars ) {
    $vars['my-listings'] = 'my-listings';
    return $vars;
} );

/** Place "My Listings" immediately after the account dashboard. */
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
    $updated = array();
    foreach ( $items as $key => $label ) {
        $updated[ $key ] = $label;
        if ( 'dashboard' === $key ) {
            $updated['my-listings'] = 'إعلاناتي';
        }
    }
    if ( ! isset( $updated['my-listings'] ) ) {
        $updated['my-listings'] = 'إعلاناتي';
    }
    return $updated;
}, 20 );

/** Link to a custom account endpoint without creating a second account system. */
function sq_account_listings_url() {
    if ( ! function_exists( 'wc_get_endpoint_url' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
        return home_url( '/' );
    }
    return wc_get_endpoint_url( 'my-listings', '', wc_get_page_permalink( 'myaccount' ) );
}

/** Show contextual guidance above WooCommerce's native login/registration forms. */
add_action( 'woocommerce_before_customer_login_form', function () {
    $enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration', 'no' );
    echo '<div class="sq-account-welcome">';
    echo '<span>سوق قريب — حساب واحد لكل شيء</span>';
    echo '<h2>' . esc_html( $enabled ? 'سجّل دخولك أو أنشئ حسابك' : 'مرحبًا بعودتك إلى سوق قريب' ) . '</h2>';
    echo '<p>تصفح المنتجات، تابع مشترياتك، وأرسل إعلانات البيع والتبديل والتبرع للمراجعة من حساب واحد.</p>';
    if ( ! $enabled ) {
        echo '<p class="sq-account-registration-note">إنشاء الحسابات الجديدة غير مفعّل حاليًا.</p>';
    }
    echo '</div>';
} );

/**
 * A customer name is collected by the existing Woo registration form.
 * No custom password handling or account-role escalation.
 */
add_action( 'woocommerce_register_form_start', function () {
    $first = isset( $_POST['sq_first_name'] ) && is_scalar( $_POST['sq_first_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['sq_first_name'] ) ) : '';
    $last  = isset( $_POST['sq_last_name'] ) && is_scalar( $_POST['sq_last_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['sq_last_name'] ) ) : '';
    echo '<p class="form-row form-row-first sq-register-name"><label for="sq_first_name">الاسم الأول <span class="required">*</span></label>';
    echo '<input type="text" class="input-text" name="sq_first_name" id="sq_first_name" maxlength="60" autocomplete="given-name" value="' . esc_attr( $first ) . '" required></p>';
    echo '<p class="form-row form-row-last sq-register-name"><label for="sq_last_name">اسم العائلة <span class="required">*</span></label>';
    echo '<input type="text" class="input-text" name="sq_last_name" id="sq_last_name" maxlength="60" autocomplete="family-name" value="' . esc_attr( $last ) . '" required></p>';
    echo '<div class="clear"></div>';
} );

add_filter( 'woocommerce_registration_errors', function ( $errors ) {
    // Checkout registration has a separate field layout: never block it for
    // lacking fields rendered only on the My Account form.
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
        return $errors;
    }
    foreach ( array( 'sq_first_name' => 'الاسم الأول', 'sq_last_name' => 'اسم العائلة' ) as $key => $label ) {
        $value = isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] )
            ? trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : '';
        $length = function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
        if ( $length < 2 || $length > 60 ) {
            $errors->add( 'sq_' . $key . '_invalid', sprintf( '%s مطلوب ويجب أن يتكوّن من حرفين إلى 60 حرفًا.', $label ) );
        }
    }
    return $errors;
}, 10, 1 );

add_action( 'woocommerce_created_customer', function ( $customer_id ) {
    $first = isset( $_POST['sq_first_name'] ) && is_scalar( $_POST['sq_first_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['sq_first_name'] ) ) : '';
    $last = isset( $_POST['sq_last_name'] ) && is_scalar( $_POST['sq_last_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['sq_last_name'] ) ) : '';
    if ( '' === $first || '' === $last ) {
        return;
    }
    update_user_meta( $customer_id, 'first_name', $first );
    update_user_meta( $customer_id, 'last_name', $last );
    update_user_meta( $customer_id, 'billing_first_name', $first );
    update_user_meta( $customer_id, 'billing_last_name', $last );
    wp_update_user( array(
        'ID'           => $customer_id,
        'display_name' => trim( $first . ' ' . $last ),
    ) );
}, 10, 1 );

/** Account homepage: only real links, no fake favorites/messages screens. */
add_action( 'woocommerce_account_dashboard', function () {
    if ( ! is_user_logged_in() ) {
        return;
    }
    $user = wp_get_current_user();
    $add_page = get_page_by_path( 'add-listing' );
    $cards = array(
        array( 'label' => 'إعلاناتي', 'text' => 'شاهد إعلاناتك وحالة المراجعة.', 'url' => sq_account_listings_url(), 'icon' => '▤' ),
        array( 'label' => 'طلباتي', 'text' => 'راجع مشترياتك السابقة.', 'url' => wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ), 'icon' => '▣' ),
        array( 'label' => 'بيانات الحساب', 'text' => 'غيّر اسمك وبريدك وكلمة المرور.', 'url' => wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ), 'icon' => '♙' ),
    );
    if ( $add_page && 'publish' === $add_page->post_status ) {
        array_unshift( $cards, array( 'label' => 'أضف إعلانك', 'text' => 'بيع أو بدّل أو تبرّع بعد المراجعة.', 'url' => get_permalink( $add_page ), 'icon' => '+' ) );
    }
    echo '<section class="sq-account-dashboard" dir="rtl">';
    echo '<h2>أهلًا، ' . esc_html( $user->display_name ?: $user->user_login ) . '</h2>';
    echo '<p>يمكنك إدارة حسابك وإعلاناتك من هنا.</p>';
    echo '<div class="sq-account-shortcuts">';
    foreach ( $cards as $card ) {
        echo '<a class="sq-account-shortcut" href="' . esc_url( $card['url'] ) . '"><span aria-hidden="true">' . esc_html( $card['icon'] ) . '</span><strong>' . esc_html( $card['label'] ) . '</strong><small>' . esc_html( $card['text'] ) . '</small></a>';
    }
    echo '</div></section>';
}, 5 );

/**
 * Visible-only-to-owner product listings, including pending submissions.
 * Products not created through the marketplace form are intentionally excluded.
 */
add_action( 'woocommerce_account_my-listings_endpoint', function () {
    if ( ! is_user_logged_in() ) {
        echo '<p>يرجى تسجيل الدخول لعرض إعلاناتك.</p>';
        return;
    }
    $page = isset( $_GET['sq_page'] ) && is_scalar( $_GET['sq_page'] )
        ? max( 1, absint( wp_unslash( $_GET['sq_page'] ) ) ) : 1;
    $query = new WP_Query( array(
        'post_type'           => 'product',
        'post_status'         => array( 'publish', 'pending', 'draft' ),
        'author'              => get_current_user_id(),
        'posts_per_page'      => 8,
        'paged'               => $page,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => false,
        'meta_query'          => array(
            array( 'key' => '_sq_listing_type', 'compare' => 'EXISTS' ),
        ),
    ) );
    $states = array(
        'publish' => array( 'label' => 'منشور', 'class' => 'published' ),
        'pending' => array( 'label' => 'بانتظار المراجعة', 'class' => 'pending' ),
        'draft'   => array( 'label' => 'مسحوب / مسودة', 'class' => 'draft' ),
    );
    $types = array( 'sell' => 'بيع', 'swap' => 'تبديل', 'donate' => 'تبرّع' );
    $add_page = get_page_by_path( 'add-listing' );
    echo '<section class="sq-my-listings" dir="rtl"><div class="sq-my-listings-heading"><div><h2>إعلاناتي</h2><p>تابع حالة الإعلانات التي أرسلتها للمراجعة.</p></div>';
    if ( $add_page && 'publish' === $add_page->post_status ) {
        echo '<a class="sq-button" href="' . esc_url( get_permalink( $add_page ) ) . '">+ أضف إعلانًا</a>';
    }
    echo '</div>';
    if ( ! $query->have_posts() ) {
        echo '<div class="sq-account-empty"><h3>لا توجد إعلانات بعد</h3><p>بعد إرسال إعلانك الأول، ستظهر حالة مراجعته هنا.</p></div></section>';
        wp_reset_postdata();
        return;
    }
    echo '<div class="sq-listings-owned-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $id = get_the_ID();
        $status = get_post_status( $id );
        $state = isset( $states[ $status ] ) ? $states[ $status ] : $states['draft'];
        $type = get_post_meta( $id, '_sq_listing_type', true );
        $city = get_post_meta( $id, '_sq_item_city', true );
        echo '<article class="sq-owned-listing">';
        echo '<div class="sq-owned-photo">' . get_the_post_thumbnail( $id, 'medium', array( 'loading' => 'lazy', 'alt' => '' ) ) . '</div>';
        echo '<div class="sq-owned-body">';
        echo '<span class="sq-owned-state sq-owned-state--' . esc_attr( $state['class'] ) . '">' . esc_html( $state['label'] ) . '</span>';
        echo '<h3>' . esc_html( get_the_title() ) . '</h3>';
        echo '<p>' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : '' ) . ( $city ? ' · ' . esc_html( $city ) : '' ) . '</p>';
        echo '<div class="sq-owned-actions">';
        if ( 'publish' === $status ) {
            echo '<a class="sq-owned-view" href="' . esc_url( get_permalink( $id ) ) . '">عرض الإعلان</a>';
        }
        if ( in_array( $status, array( 'publish', 'pending' ), true ) ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="sq_withdraw_listing">';
            echo '<input type="hidden" name="listing_id" value="' . esc_attr( $id ) . '">';
            wp_nonce_field( 'sq_withdraw_listing_' . $id, 'sq_withdraw_nonce' );
            echo '<button type="submit" class="sq-owned-withdraw">سحب الإعلان</button></form>';
        }
        echo '</div></div></article>';
    }
    echo '</div>';
    if ( $query->max_num_pages > 1 ) {
        echo '<nav class="sq-my-listings-pagination" aria-label="صفحات إعلاناتي">';
        for ( $n = 1; $n <= min( 50, (int) $query->max_num_pages ); $n++ ) {
            $url = add_query_arg( 'sq_page', $n, sq_account_listings_url() );
            echo '<a href="' . esc_url( $url ) . '" ' . ( $n === $page ? 'aria-current="page"' : '' ) . '>' . esc_html( $n ) . '</a>';
        }
        echo '</nav>';
    }
    echo '</section>';
    wp_reset_postdata();
} );

/** Withdraw only the current user's own community listing; never delete orders. */
add_action( 'admin_post_sq_withdraw_listing', function () {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
        exit;
    }
    $id = isset( $_POST['listing_id'] ) && is_scalar( $_POST['listing_id'] )
        ? absint( wp_unslash( $_POST['listing_id'] ) ) : 0;
    if ( ! $id || ! isset( $_POST['sq_withdraw_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sq_withdraw_nonce'] ) ), 'sq_withdraw_listing_' . $id ) ) {
        wp_die( 'تعذّر التحقق من طلبك.', '', array( 'response' => 403 ) );
    }
    $post = get_post( $id );
    if ( ! $post || 'product' !== $post->post_type
         || (int) $post->post_author !== get_current_user_id()
         || ! get_post_meta( $id, '_sq_listing_type', true )
         || ! in_array( $post->post_status, array( 'publish', 'pending' ), true ) ) {
        wp_die( 'هذا الإعلان غير متاح للتعديل.', '', array( 'response' => 403 ) );
    }
    $updated = wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ), true );
    $status = is_wp_error( $updated ) ? 'failed' : 'withdrawn';
    wp_safe_redirect( add_query_arg( 'sq_notice', $status, sq_account_listings_url() ) );
    exit;
} );

add_action( 'woocommerce_account_my-listings_endpoint', function () {
    $notice = isset( $_GET['sq_notice'] ) && is_scalar( $_GET['sq_notice'] )
        ? sanitize_key( wp_unslash( $_GET['sq_notice'] ) ) : '';
    if ( 'withdrawn' === $notice ) {
        wc_print_notice( 'تم سحب إعلانك ولن يظهر للزوار.', 'success' );
    } elseif ( 'failed' === $notice ) {
        wc_print_notice( 'تعذّر سحب الإعلان. حاول مرة أخرى.', 'error' );
    }
}, 1 );

/**
 * Translate WooCommerce's stock English registration notice, while preserving
 * any merchant-customized privacy text and the actual privacy-policy link.
 */
add_filter( 'woocommerce_get_privacy_policy_text', function ( $text, $type ) {
    if ( 'registration' !== $type || ! is_string( $text ) ) {
        return $text;
    }

    $english_default = 'Your personal data will be used to support your experience throughout this website';
    if ( false === strpos( $text, $english_default ) ) {
        return $text;
    }

    return 'سنستخدم بياناتك الشخصية لتوفير خدمات سوق قريب وإدارة الوصول إلى حسابك، وللأغراض الأخرى الموضّحة في [privacy_policy].';
}, 20, 2 );
