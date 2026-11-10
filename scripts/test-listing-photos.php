<?php
/** Malformed upload regressions without moving files or creating attachments. */
define( 'ABSPATH', __DIR__ );
class WP_Error {}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
require __DIR__ . '/../wordpress/themes/souq-qareeb/inc/listing-photos.php';
$empty = array( 'name' => array( '' ), 'type' => array( '' ), 'tmp_name' => array( '' ),
    'error' => array( UPLOAD_ERR_NO_FILE ), 'size' => array( 0 ) );
foreach ( array( null, $empty ) as $input ) {
    if ( array() !== sq_listing_replacement_photos( $input ) ) {
        throw new RuntimeException( 'Omitted replacement should retain images.' );
    }
}
$inputs = array( 'string', array(), array( 'name' => 'scalar' ) );
foreach ( array( 'name', 'type', 'tmp_name', 'error', 'size' ) as $field ) {
    $missing = $empty;
    unset( $missing[ $field ] );
    $inputs[] = $missing;
    $nested = $empty;
    $nested[ $field ][0] = array( 'nested' );
    $inputs[] = $nested;
}
$mismatched = $empty;
$mismatched['tmp_name'] = array();
$inputs[] = $mismatched;
$too_many = array_map( static function ( $values ) { return array_fill( 0, 6, $values[0] ); }, $empty );
$inputs[] = $too_many;
$too_large = $empty;
$too_large['name'][0] = 'large.webp';
$too_large['error'][0] = UPLOAD_ERR_OK;
$too_large['size'][0] = 5 * 1024 * 1024 + 1;
$inputs[] = $too_large;
$not_uploaded = $too_large;
$not_uploaded['size'][0] = 100;
$not_uploaded['tmp_name'][0] = __FILE__;
$inputs[] = $not_uploaded;
foreach ( $inputs as $input ) {
    if ( ! is_wp_error( sq_listing_replacement_photos( $input ) ) ) {
        throw new RuntimeException( 'Unsafe upload accepted.' );
    }
}
echo 'PASS: optional image retention and ' . count( $inputs ) . " malformed/unsafe upload cases\n";
function get_post_thumbnail_id( $id ) { return 90; }
function get_post_meta( $id, $key, $single ) { return '91'; }
function absint( $value ) { return abs( (int) $value ); }
function wp_delete_attachment( $id, $force ) { global $deleted; $deleted[] = $id; }
$deleted = array();
sq_cleanup_failed_listing_photo_save( array( 90, 91, 92 ), 10 );
if ( array( 92 ) !== $deleted ) {
    throw new RuntimeException( 'Cleanup must preserve referenced featured/gallery images.' );
}
echo "PASS: failed-save cleanup preserves referenced photos\n";
