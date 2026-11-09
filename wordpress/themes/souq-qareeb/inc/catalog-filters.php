<?php
/** Normalize optional price fields before WooCommerce builds its product query. */
defined( 'ABSPATH' ) || exit;

/** Keep the category archive selected when its GET form submits to the shop. */
function sq_selected_catalog_category() {
    if ( isset( $_GET['sq_category'] ) ) {
        return is_scalar( $_GET['sq_category'] )
            ? sanitize_title( wp_unslash( $_GET['sq_category'] ) ) : '';
    }
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) {
            return $term->slug;
        }
    }
    return '';
}

function sq_normalize_optional_price_filters( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    $product_search = $query->is_search()
        && in_array( 'product', (array) $query->get( 'post_type' ), true );
    if ( ! $query->is_post_type_archive( 'product' )
        && ! $query->is_tax( array( 'product_cat', 'product_tag' ) )
        && ! $product_search ) {
        return;
    }

    // Native GET forms submit empty optional inputs. WooCommerce interprets
    // an existing max_price="" as zero, hiding every paid product. Preserve
    // explicit "0" and populated bounds; only absent values mean no limit.
    foreach ( array( 'min_price', 'max_price' ) as $key ) {
        if ( isset( $_GET[ $key ] )
            && ( ! is_scalar( $_GET[ $key ] ) || '' === trim( (string) $_GET[ $key ] ) ) ) {
            unset( $_GET[ $key ] );
        }
    }
}
add_action( 'pre_get_posts', 'sq_normalize_optional_price_filters', 5 );
