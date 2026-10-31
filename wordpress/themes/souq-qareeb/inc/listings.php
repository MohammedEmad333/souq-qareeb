<?php
/**
 * Moderated community listings. Never let a frontend user publish directly.
 *
 * @package SouqQareeb
 */
defined( 'ABSPATH' ) || exit;

/** Read a scalar form field; arrays are invalid input. */
function sq_listing_field( $key ) {
    if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
        return '';
    }
    return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
}

function sq_listing_return_url( $status ) {
    $page = get_page_by_path( 'add-listing' );
    $url  = $page ? get_permalink( $page ) : home_url( '/' );
    return add_query_arg( 'sq_listing_status', $status, $url );
}

/** Called only from an authenticated admin-post submission. */
add_action( 'admin_post_sq_submit_listing', function () {
    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) || ! class_exists( 'WC_Product_Simple' ) ) {
        wp_safe_redirect( sq_listing_return_url( 'unavailable' ) );
        exit;
    }
    check_admin_referer( 'sq_submit_listing_action', 'sq_listing_nonce' );

    $type      = sq_listing_field( 'sq_type' );
    $name      = sq_listing_field( 'sq_name' );
    $content   = isset( $_POST['sq_description'] ) && is_scalar( $_POST['sq_description'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['sq_description'] ) ) : '';
    $category  = absint( sq_listing_field( 'sq_category' ) );
    $condition = sq_listing_field( 'sq_condition' );
    $city      = sq_listing_field( 'sq_city' );
    $price_raw = sq_listing_field( 'sq_price' );
    $method    = sq_listing_field( 'sq_contact' );
    $phone     = preg_replace( '/[^0-9]/', '', sq_listing_field( 'sq_phone' ) );

    $length = static function ( $text ) {
        return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
    };
    if ( ! in_array( $type, array( 'sell', 'swap', 'donate' ), true )
         || $length( $name ) < 5 || $length( $name ) > 100
         || $length( $content ) < 15 || $length( $content ) > 2000
         || ! $category || ! term_exists( $category, 'product_cat' )
         || ! in_array( $condition, array( 'excellent', 'good', 'fair' ), true )
         || '' === $city || $length( $city ) > 60
         || ! in_array( $method, array( 'whatsapp', 'phone' ), true )
         || strlen( $phone ) < 9 || strlen( $phone ) > 15 ) {
        wp_safe_redirect( sq_listing_return_url( 'invalid' ) );
        exit;
    }
    $price = (float) wc_format_decimal( $price_raw );
    if ( 'sell' === $type && ( ! is_numeric( $price_raw ) || $price <= 0 || $price > 1000000 ) ) {
        wp_safe_redirect( sq_listing_return_url( 'invalid' ) );
        exit;
    }

    $recent = get_posts( array(
        'post_type' => 'product', 'post_status' => array( 'pending', 'publish', 'draft' ),
        'author' => get_current_user_id(), 'posts_per_page' => 5, 'fields' => 'ids',
        'date_query' => array( array( 'after' => '24 hours ago' ) ),
    ) );
    if ( count( $recent ) >= 5 ) {
        wp_safe_redirect( sq_listing_return_url( 'limit' ) );
        exit;
    }

    // Up to five real photographs, each <= 5MB. No SVG or arbitrary files.
    if ( ! isset( $_FILES['sq_photos'] ) || ! is_array( $_FILES['sq_photos']['name'] ) ) {
        wp_safe_redirect( sq_listing_return_url( 'photos' ) );
        exit;
    }
    $photos = $_FILES['sq_photos'];
    if ( ! is_array( $photos['error'] ) || ! is_array( $photos['size'] )
         || ! is_array( $photos['tmp_name'] ) || ! is_array( $photos['type'] ) ) {
        wp_safe_redirect( sq_listing_return_url( 'photos' ) );
        exit;
    }
    $valid_indices = array();
    foreach ( $photos['name'] as $i => $filename ) {
        if ( '' === $filename || UPLOAD_ERR_NO_FILE === (int) $photos['error'][ $i ] ) {
            continue;
        }
        if ( UPLOAD_ERR_OK !== (int) $photos['error'][ $i ]
             || (int) $photos['size'][ $i ] > 5 * 1024 * 1024
             || ! is_uploaded_file( $photos['tmp_name'][ $i ] ) ) {
            wp_safe_redirect( sq_listing_return_url( 'photos' ) );
            exit;
        }
        $filetype = wp_check_filetype_and_ext( $photos['tmp_name'][ $i ], $filename );
        if ( ! in_array( $filetype['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
            wp_safe_redirect( sq_listing_return_url( 'photos' ) );
            exit;
        }
        $valid_indices[] = $i;
    }
    if ( ! $valid_indices || count( $valid_indices ) > 5 ) {
        wp_safe_redirect( sq_listing_return_url( 'photos' ) );
        exit;
    }

    try {
        $product = new WC_Product_Simple();
        $product->set_name( $name );
        $product->set_description( $content );
        $product->set_status( 'pending' );
        $product->set_catalog_visibility( 'visible' );
        $product->set_category_ids( array( $category ) );
        $product->set_regular_price( 'sell' === $type ? wc_format_decimal( $price ) : '0' );
        $product->set_sold_individually( true );
        $product_id = $product->save();
    } catch ( Exception $e ) {
        wp_safe_redirect( sq_listing_return_url( 'unavailable' ) );
        exit;
    }
    if ( ! $product_id ) {
        wp_safe_redirect( sq_listing_return_url( 'unavailable' ) );
        exit;
    }
    wp_update_post( array(
        'ID' => $product_id,
        'post_author' => get_current_user_id(),
        'post_status' => 'pending',
    ) );
    update_post_meta( $product_id, '_sq_listing_type', $type );
    update_post_meta( $product_id, '_sq_item_condition', $condition );
    update_post_meta( $product_id, '_sq_item_city', $city );
    update_post_meta( $product_id, '_sq_contact_method', $method );
    update_post_meta( $product_id, '_sq_contact_phone', $phone );

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $image_ids = array();
    foreach ( $valid_indices as $i ) {
        $_FILES['sq_single_photo'] = array(
            'name' => sanitize_file_name( $photos['name'][ $i ] ),
            'type' => $photos['type'][ $i ],
            'tmp_name' => $photos['tmp_name'][ $i ],
            'error' => $photos['error'][ $i ],
            'size' => $photos['size'][ $i ],
        );
        $image_id = media_handle_upload( 'sq_single_photo', $product_id, array(), array( 'test_form' => false ) );
        unset( $_FILES['sq_single_photo'] );
        if ( is_wp_error( $image_id ) ) {
            foreach ( $image_ids as $saved_image ) {
                wp_delete_attachment( $saved_image, true );
            }
            wp_delete_post( $product_id, true );
            wp_safe_redirect( sq_listing_return_url( 'photos' ) );
            exit;
        }
        $image_ids[] = $image_id;
    }
    $product->set_image_id( $image_ids[0] );
    $product->set_gallery_image_ids( array_slice( $image_ids, 1 ) );
    $product->save();
    // Redirect to the account's My Listings view so success is visible
    // alongside the newly pending product, rather than above a blank form.
    $destination = function_exists( 'sq_account_listings_url' )
        ? add_query_arg( 'sq_notice', 'submitted', sq_account_listings_url() )
        : sq_listing_return_url( 'submitted' );
    wp_safe_redirect( $destination );
    exit;
} );

/* Do not let swap/donation offers look like purchasable checkout items. */
add_filter( 'woocommerce_is_purchasable', function ( $purchasable, $product ) {
    if ( $product && in_array( get_post_meta( $product->get_id(), '_sq_listing_type', true ), array( 'swap', 'donate' ), true ) ) {
        return false;
    }
    return $purchasable;
}, 10, 2 );

/* Show saved listing type and city, not fictional details. */
add_action( 'woocommerce_single_product_summary', function () {
    global $product;
    if ( ! $product instanceof WC_Product || ! is_product() ) {
        return;
    }
    $id = $product->get_id();
    $type = get_post_meta( $id, '_sq_listing_type', true );
    $city = get_post_meta( $id, '_sq_item_city', true );
    $types = array( 'sell' => 'بيع', 'swap' => 'تبديل', 'donate' => 'تبرع' );
    if ( ! isset( $types[ $type ] ) && ! $city ) {
        return;
    }
    echo '<div class="sq-listing-facts">';
    if ( isset( $types[ $type ] ) ) {
        echo '<span>' . esc_html( $types[ $type ] ) . '</span>';
    }
    if ( $city ) {
        echo '<span>الموقع: ' . esc_html( $city ) . '</span>';
    }
    echo '</div>';
    $phone = get_post_meta( $id, '_sq_contact_phone', true );
    $method = get_post_meta( $id, '_sq_contact_method', true );
    if ( 'publish' !== get_post_status( $id ) || ! preg_match( '/^[0-9]{9,15}$/', $phone ) ) {
        return;
    }
    if ( 'whatsapp' === $method ) {
        echo '<p class="sq-listing-contact"><a href="' . esc_url( 'https://wa.me/' . $phone ) . '" target="_blank" rel="noopener noreferrer">تواصل مع صاحب الإعلان عبر واتساب</a></p>';
    } elseif ( 'phone' === $method ) {
        echo '<p class="sq-listing-contact"><a href="' . esc_url( 'tel:+' . $phone ) . '">اتصل بصاحب الإعلان</a></p>';
    }
}, 22 );

/* Use honest "تبديل" / "تبرع" labels for non-sale adverts. */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
    if ( $product ) {
        $type = get_post_meta( $product->get_id(), '_sq_listing_type', true );
        if ( 'swap' === $type ) {
            return '<span class="sq-listing-special-price">للتبديل</span>';
        }
        if ( 'donate' === $type ) {
            return '<span class="sq-listing-special-price">للتبرّع</span>';
        }
    }
    return $html;
}, 10, 2 );
