<?php
/**
 * Owner-only editing of community adverts.
 * Every change re-enters moderation; existing images and listing type are
 * retained until dedicated photo/type-editing flows are implemented.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Return a listing only when it belongs to the current authenticated user.
 * Do not rely solely on WordPress edit_post capabilities: customers lack them.
 */
function sq_get_owned_editable_listing( $id ) {
    $id = absint( $id );
    if ( ! is_user_logged_in() || ! $id ) {
        return false;
    }
    $post = get_post( $id );
    if ( ! $post || 'product' !== $post->post_type
        || (int) $post->post_author !== get_current_user_id()
        || ! in_array( $post->post_status, array( 'publish', 'pending', 'draft' ), true )
        || ! in_array( get_post_meta( $id, '_sq_listing_type', true ), array( 'sell', 'swap', 'donate' ), true ) ) {
        return false;
    }
    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false;
    return $product instanceof WC_Product_Simple ? $product : false;
}

function sq_listing_edit_url( $id, $notice = '' ) {
    $page = get_page_by_path( 'add-listing' );
    $url = $page && 'publish' === $page->post_status ? get_permalink( $page ) : home_url( '/' );
    $args = array( 'sq_edit' => absint( $id ) );
    if ( $notice ) {
        $args['sq_listing_status'] = sanitize_key( $notice );
    }
    return add_query_arg( $args, $url );
}

add_action( 'admin_post_sq_update_listing', function () {
    if ( ! is_user_logged_in() || ! function_exists( 'wc_get_product' ) ) {
        wp_die( 'يرجى تسجيل الدخول.', '', array( 'response' => 403 ) );
    }
    $id = isset( $_POST['sq_edit_id'] ) && is_scalar( $_POST['sq_edit_id'] )
        ? absint( wp_unslash( $_POST['sq_edit_id'] ) ) : 0;
    $product = sq_get_owned_editable_listing( $id );
    if ( ! $product ) {
        wp_die( 'ليس لديك صلاحية تعديل هذا الإعلان.', '', array( 'response' => 403 ) );
    }
    if ( ! isset( $_POST['sq_listing_nonce'] ) || ! is_scalar( $_POST['sq_listing_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sq_listing_nonce'] ) ), 'sq_update_listing_' . $id ) ) {
        wp_die( 'تعذّر التحقق من طلب التعديل.', '', array( 'response' => 403 ) );
    }
    if ( ! function_exists( 'sq_listing_field' ) ) {
        wp_safe_redirect( sq_listing_edit_url( $id, 'unavailable' ) );
        exit;
    }
    $name = sq_listing_field( 'sq_name' );
    $description = isset( $_POST['sq_description'] ) && is_scalar( $_POST['sq_description'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['sq_description'] ) ) : '';
    $category = absint( sq_listing_field( 'sq_category' ) );
    $condition = sq_listing_field( 'sq_condition' );
    $city = sq_listing_field( 'sq_city' );
    $price_raw = sq_listing_field( 'sq_price' );
    $method = sq_listing_field( 'sq_contact' );
    $phone = preg_replace( '/[^0-9]/', '', sq_listing_field( 'sq_phone' ) );
    $type = get_post_meta( $id, '_sq_listing_type', true );
    $length = static function ( $value ) {
        return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
    };

    $category_term = $category ? get_term( $category, 'product_cat' ) : false;
    if ( $length( $name ) < 5 || $length( $name ) > 100
        || $length( $description ) < 15 || $length( $description ) > 2000
        || ! $category_term || is_wp_error( $category_term )
        || ! in_array( $condition, array( 'excellent', 'good', 'fair' ), true )
        || '' === trim( $city ) || $length( $city ) > 60
        || ! in_array( $method, array( 'whatsapp', 'phone' ), true )
        || strlen( $phone ) < 9 || strlen( $phone ) > 15
        || ( 'sell' === $type && ( ! is_numeric( $price_raw )
            || (float) wc_format_decimal( $price_raw ) <= 0
            || (float) wc_format_decimal( $price_raw ) > 1000000 ) ) ) {
        wp_safe_redirect( sq_listing_edit_url( $id, 'invalid' ) );
        exit;
    }

    // Status is changed in the same save as the product fields, so publishing
    // cannot remain active when a seller modifies approved information.
    try {
        $product->set_status( 'pending' );
        $product->set_name( $name );
        $product->set_description( $description );
        $product->set_category_ids( array( $category ) );
        $product->set_regular_price( 'sell' === $type ? wc_format_decimal( $price_raw ) : '0' );
        $product->save();
    } catch ( Exception $e ) {
        wp_safe_redirect( sq_listing_edit_url( $id, 'unavailable' ) );
        exit;
    }
    update_post_meta( $id, '_sq_item_condition', $condition );
    update_post_meta( $id, '_sq_item_city', $city );
    update_post_meta( $id, '_sq_contact_method', $method );
    update_post_meta( $id, '_sq_contact_phone', $phone );

    $destination = function_exists( 'sq_account_listings_url' ) ? sq_account_listings_url() : sq_listing_edit_url( $id );
    wp_safe_redirect( add_query_arg( 'sq_notice', 'updated', $destination ) );
    exit;
} );
