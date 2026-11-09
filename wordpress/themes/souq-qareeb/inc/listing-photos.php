<?php
/** Optional replacement photos for owner-edited community listings. */
defined( 'ABSPATH' ) || exit;

/** Validate the entire batch before moving files or changing product data. */
function sq_listing_replacement_photos( $photos ) {
    if ( null === $photos ) {
        return array();
    }
    $invalid = new WP_Error( 'photos', 'تعذّر التحقق من الصور المختارة.' );
    if ( ! is_array( $photos ) ) {
        return $invalid;
    }
    foreach ( array( 'name', 'tmp_name', 'error', 'size', 'type' ) as $field ) {
        if ( ! isset( $photos[ $field ] ) || ! is_array( $photos[ $field ] ) ) {
            return $invalid;
        }
        if ( array_keys( $photos[ $field ] ) !== array_keys( $photos['name'] ) ) {
            return $invalid;
        }
    }
    if ( count( $photos['name'] ) > 5 ) {
        return $invalid;
    }
    $files = array();
    foreach ( $photos['name'] as $i => $name ) {
        foreach ( array( 'name', 'tmp_name', 'error', 'size', 'type' ) as $field ) {
            if ( ! is_scalar( $photos[ $field ][ $i ] ) ) {
                return $invalid;
            }
        }
        if ( UPLOAD_ERR_NO_FILE === (int) $photos['error'][ $i ] && '' === $name ) {
            continue;
        }
        $tmp = $photos['tmp_name'][ $i ];
        if ( '' === $name || UPLOAD_ERR_OK !== (int) $photos['error'][ $i ]
            || (int) $photos['size'][ $i ] <= 0 || (int) $photos['size'][ $i ] > 5 * 1024 * 1024
            || ! is_uploaded_file( $tmp ) || filesize( $tmp ) > 5 * 1024 * 1024 ) {
            return $invalid;
        }
        $type = wp_check_filetype_and_ext( $tmp, $name );
        if ( empty( $type['ext'] ) || ! in_array( $type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
            return $invalid;
        }
        $files[] = array( 'name' => sanitize_file_name( $name ), 'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK, 'size' => (int) $photos['size'][ $i ], 'type' => $type['type'] );
    }
    return $files;
}

/** Upload only validated files; rollback only attachments created by this batch. */
function sq_upload_listing_replacement_photos( $files, $product_id ) {
    if ( ! $files ) {
        return array();
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $ids = array();
    foreach ( $files as $file ) {
        $id = media_handle_sideload( $file, $product_id );
        if ( is_wp_error( $id ) ) {
            foreach ( $ids as $created_id ) {
                wp_delete_attachment( $created_id, true );
            }
            return $id;
        }
        $ids[] = $id;
    }
    return $ids;
}

/** A failed product save must not discard a photo it already persisted. */
function sq_cleanup_failed_listing_photo_save( $created_ids, $product_id ) {
    $used = array_merge( array( (int) get_post_thumbnail_id( $product_id ) ),
        array_map( 'absint', explode( ',', (string) get_post_meta( $product_id, '_product_image_gallery', true ) ) ) );
    foreach ( $created_ids as $created_id ) {
        if ( ! in_array( (int) $created_id, $used, true ) ) {
            wp_delete_attachment( $created_id, true );
        }
    }
}
