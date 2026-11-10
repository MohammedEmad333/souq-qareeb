<?php
/** Disposable loopback listing-photo test data and assertions. Never production. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
    throw new RuntimeException( 'Local WP-CLI only.' );
}
add_filter( 'pre_wp_mail', '__return_true' );
$phase = getenv( 'SQ_PHOTO_PHASE' ) ?: 'prepare';
function sq_photo_expect( $ok, $message ) {
    if ( ! $ok ) { throw new RuntimeException( $message ); }
    echo 'PASS: ' . $message . PHP_EOL;
}
if ( 'prepare' === $phase ) {
    $owner = wp_create_user( 'qa_photo_owner', 'disposable-photo-qa-only', 'photo-owner@example.invalid' );
    $other = wp_create_user( 'qa_photo_other', 'disposable-photo-qa-only', 'photo-other@example.invalid' );
    sq_photo_expect( ! is_wp_error( $owner ) && ! is_wp_error( $other ), 'synthetic photo users created' );
    ( new WP_User( $owner ) )->set_role( 'customer' );
    ( new WP_User( $other ) )->set_role( 'customer' );
    $term = wp_insert_term( 'QA Photo Category', 'product_cat' );
    sq_photo_expect( ! is_wp_error( $term ), 'synthetic category created' );
    $page = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'add-listing', 'post_title' => 'QA Add Listing' ) );
    $product = new WC_Product_Simple();
    $product->set_name( 'QA photo edit synthetic item' );
    $product->set_description( 'Synthetic listing used only for safe local photo editing checks.' );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'hidden' );
    $product->set_regular_price( '5' );
    sq_configure_listing_inventory( $product, 'sell' );
    $product->set_stock_quantity( 0 );
    $product->set_category_ids( array( $term['term_id'] ) );
    $id = $product->save();
    wp_update_post( array( 'ID' => $id, 'post_author' => $owner ) );
    foreach ( array( '_sq_listing_type' => 'sell', '_sq_item_condition' => 'good', '_sq_item_city' => 'QA City', '_sq_contact_method' => 'phone', '_sq_contact_phone' => '999123456789' ) as $key => $value ) {
        update_post_meta( $id, $key, $value );
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = wp_tempnam( 'qa-original.webp' );
    copy( get_template_directory() . '/assets/images/hero-mobile-image.webp', $tmp );
    $image = media_handle_sideload( array( 'name' => 'qa-original.webp', 'tmp_name' => $tmp ), $id );
    sq_photo_expect( ! is_wp_error( $image ), 'original synthetic image uploaded' );
    $product->set_image_id( $image );
    $product->save();
    update_option( 'sq_photo_qa_state', array( 'id' => $id, 'original' => $image, 'owner' => $owner ) );
    file_put_contents( getenv( 'GITHUB_ENV' ), 'QA_PHOTO_EDIT_URL=' . add_query_arg( 'sq_edit', $id, get_permalink( $page ) ) . PHP_EOL, FILE_APPEND );
} else {
    $state = get_option( 'sq_photo_qa_state' );
    $product = wc_get_product( $state['id'] );
    sq_photo_expect( 0 === (int) $product->get_stock_quantity(), 'photo edits never replenish sold stock' );
    sq_photo_expect( 'attachment' === get_post_type( $state['original'] ) && file_exists( get_attached_file( $state['original'] ) ), 'original media file retained' );
    if ( 'unchanged' === $phase ) {
        sq_photo_expect( (int) $state['original'] === (int) $product->get_image_id(), 'omitted or rejected upload preserves original photo' );
    } elseif ( 'replaced' === $phase ) {
        sq_photo_expect( (int) $state['original'] !== (int) $product->get_image_id(), 'new featured photo persisted' );
        sq_photo_expect( 1 === count( $product->get_gallery_image_ids() ), 'new gallery photo persisted' );
        sq_photo_expect( 'pending' === $product->get_status(), 'edited listing returns to moderation' );
    } else {
        throw new RuntimeException( 'Unknown phase.' );
    }
}
