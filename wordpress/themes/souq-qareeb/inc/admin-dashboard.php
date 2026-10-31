<?php
/**
 * Dedicated, permission-checked Souq Qareeb moderator screen in wp-admin.
 * Never exposes pending ad content through a public page.
 */
defined( 'ABSPATH' ) || exit;

function sq_moderation_url( $args = array() ) {
    return add_query_arg( $args, admin_url( 'admin.php?page=sq-moderation' ) );
}

add_action( 'admin_menu', function () {
    add_menu_page(
        'إدارة سوق قريب',
        'سوق قريب',
        'edit_products',
        'sq-moderation',
        'sq_render_moderation',
        'dashicons-store',
        25
    );
} );

add_action( 'admin_post_sq_moderate_listing', function () {
    if ( ! current_user_can( 'edit_products' ) ) {
        wp_die( 'لا تملك صلاحية إدارة الإعلانات.', '', array( 'response' => 403 ) );
    }
    $id = isset( $_POST['listing_id'] ) && is_scalar( $_POST['listing_id'] ) ? absint( wp_unslash( $_POST['listing_id'] ) ) : 0;
    $decision = isset( $_POST['decision'] ) && is_scalar( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
    check_admin_referer( 'sq_moderate_' . $id, 'sq_moderate_nonce' );
    if ( ! $id || ! in_array( $decision, array( 'approve', 'reject' ), true )
        || ! current_user_can( 'edit_post', $id ) ) {
        wp_die( 'العملية غير مسموحة.', '', array( 'response' => 403 ) );
    }
    $post = get_post( $id );
    if ( ! $post || 'product' !== $post->post_type || 'pending' !== $post->post_status
        || ! in_array( get_post_meta( $id, '_sq_listing_type', true ), array( 'sell', 'swap', 'donate' ), true ) ) {
        wp_die( 'الإعلان غير متاح للمراجعة.', '', array( 'response' => 409 ) );
    }
    $result = wp_update_post( array(
        'ID' => $id,
        'post_status' => 'approve' === $decision ? 'publish' : 'draft',
    ), true );
    $notice = is_wp_error( $result ) ? 'error' : ( 'approve' === $decision ? 'approved' : 'rejected' );
    wp_safe_redirect( sq_moderation_url( array( 'sq_notice' => $notice ) ) );
    exit;
} );

function sq_render_moderation() {
    if ( ! current_user_can( 'edit_products' ) ) {
        wp_die( 'غير مصرح لك بالدخول.', '', array( 'response' => 403 ) );
    }
    $status = isset( $_GET['sq_status'] ) && is_scalar( $_GET['sq_status'] ) ? sanitize_key( wp_unslash( $_GET['sq_status'] ) ) : 'pending';
    if ( ! in_array( $status, array( 'pending', 'publish', 'draft' ), true ) ) {
        $status = 'pending';
    }
    $page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
    $labels = array( 'pending' => 'بانتظار المراجعة', 'publish' => 'المنشورة', 'draft' => 'المرفوضة / المسودات' );
    $notice = isset( $_GET['sq_notice'] ) && is_scalar( $_GET['sq_notice'] ) ? sanitize_key( wp_unslash( $_GET['sq_notice'] ) ) : '';
    $notices = array(
        'approved' => 'تمت الموافقة على الإعلان ونشره.',
        'rejected' => 'تم نقل الإعلان إلى المسودات ولن يظهر للزوار.',
        'error' => 'تعذّر تحديث الإعلان. حاول مجددًا.',
    );
    $query = new WP_Query( array(
        'post_type' => 'product',
        'post_status' => $status,
        'posts_per_page' => 12,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array( array(
            'key' => '_sq_listing_type',
            'value' => array( 'sell', 'swap', 'donate' ),
            'compare' => 'IN',
        ) ),
    ) );
    echo '<div class="wrap sq-admin" dir="rtl"><header class="sq-admin-hero">';
    echo '<span>Souq Qareeb · الإدارة</span><h1>لوحة إدارة سوق قريب</h1><p>مراجعة الإعلانات وإدارتها من واجهة عربية متجاوبة.</p>';
    echo '<div class="sq-admin-links"><a href="' . esc_url( admin_url( 'users.php' ) ) . '">المستخدمون</a>';
    echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=product' ) ) . '">جميع المنتجات</a>';
    echo '<a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ) . '">التصنيفات</a>';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">عرض الموقع</a></div></header>';
    if ( isset( $notices[ $notice ] ) ) {
        echo '<div class="notice ' . ( 'error' === $notice ? 'notice-error' : 'notice-success' ) . ' inline"><p>' . esc_html( $notices[ $notice ] ) . '</p></div>';
    }
    echo '<nav class="sq-admin-tabs" aria-label="حالات الإعلانات">';
    foreach ( $labels as $key => $name ) {
        echo '<a class="' . ( $key === $status ? 'active' : '' ) . '" href="' . esc_url( sq_moderation_url( array( 'sq_status' => $key ) ) ) . '">' . esc_html( $name ) . '</a>';
    }
    echo '</nav>';
    echo '<p class="sq-admin-count">عدد الإعلانات في هذه الحالة: ' . esc_html( number_format_i18n( $query->found_posts ) ) . '</p>';
    if ( ! $query->have_posts() ) {
        echo '<div class="sq-admin-empty">لا توجد إعلانات في هذه القائمة.</div>';
    } else {
        echo '<div class="sq-admin-grid">';
        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();
            $owner = get_userdata( (int) get_post_field( 'post_author', $id ) );
            $type = get_post_meta( $id, '_sq_listing_type', true );
            $types = array( 'sell' => 'بيع', 'swap' => 'تبديل', 'donate' => 'تبرع' );
            $city = get_post_meta( $id, '_sq_item_city', true );
            $condition = get_post_meta( $id, '_sq_item_condition', true );
            $conditions = array( 'excellent' => 'ممتازة', 'good' => 'جيدة', 'fair' => 'مقبولة' );
            $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false;
            echo '<article class="sq-admin-card">';
            echo '<div class="sq-admin-image">' . get_the_post_thumbnail( $id, 'medium', array( 'alt' => '', 'loading' => 'lazy' ) ) . '</div>';
            echo '<div class="sq-admin-card-body"><span class="sq-admin-type">' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : '' ) . '</span>';
            echo '<h2>' . esc_html( get_the_title() ) . '</h2>';
            echo '<dl><div><dt>صاحب الإعلان</dt><dd>' . esc_html( $owner ? $owner->display_name : 'غير معروف' ) . '</dd></div>';
            if ( $city ) { echo '<div><dt>المنطقة</dt><dd>' . esc_html( $city ) . '</dd></div>'; }
            if ( isset( $conditions[ $condition ] ) ) { echo '<div><dt>الحالة</dt><dd>' . esc_html( $conditions[ $condition ] ) . '</dd></div>'; }
            if ( $product ) { echo '<div><dt>السعر</dt><dd>' . wp_kses_post( $product->get_price_html() ) . '</dd></div>'; }
            echo '</dl><p class="sq-admin-description">' . esc_html( wp_trim_words( get_the_content(), 35 ) ) . '</p>';
            echo '<div class="sq-admin-actions"><a href="' . esc_url( get_edit_post_link( $id ) ) . '">عرض التفاصيل والتحرير</a>';
            if ( 'pending' === $status && current_user_can( 'edit_post', $id ) ) {
                foreach ( array( 'approve' => 'الموافقة والنشر', 'reject' => 'رفض الإعلان' ) as $decision => $label ) {
                    echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
                    echo '<input type="hidden" name="action" value="sq_moderate_listing">';
                    echo '<input type="hidden" name="listing_id" value="' . esc_attr( $id ) . '">';
                    echo '<input type="hidden" name="decision" value="' . esc_attr( $decision ) . '">';
                    wp_nonce_field( 'sq_moderate_' . $id, 'sq_moderate_nonce' );
                    echo '<button class="' . ( 'approve' === $decision ? 'sq-admin-approve' : 'sq-admin-reject' ) . '" type="submit">' . esc_html( $label ) . '</button></form>';
                }
            }
            echo '</div></div></article>';
        }
        echo '</div>';
        if ( $query->max_num_pages > 1 ) {
            echo '<nav class="sq-admin-pages" aria-label="صفحات الإعلانات">';
            for ( $n = 1; $n <= min( (int) $query->max_num_pages, 30 ); $n++ ) {
                echo '<a ' . ( $n === $page ? 'aria-current="page"' : '' ) . ' href="' . esc_url( sq_moderation_url( array( 'sq_status' => $status, 'paged' => $n ) ) ) . '">' . esc_html( $n ) . '</a>';
            }
            echo '</nav>';
        }
    }
    wp_reset_postdata();
    echo '</div>';
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'toplevel_page_sq-moderation' !== $hook ) {
        return;
    }
    $path = get_template_directory() . '/assets/css/admin-dashboard.css';
    wp_enqueue_style( 'sq-admin-dashboard', get_template_directory_uri() . '/assets/css/admin-dashboard.css', array(), file_exists( $path ) ? filemtime( $path ) : '1.0' );
} );
