<?php
/** International contact numbers shared by listing writes and public links. */
defined( 'ABSPATH' ) || exit;

/** Return country-code digits, or an empty string for ambiguous/invalid input. */
function sq_normalize_contact_phone( $value ) {
    if ( ! is_string( $value ) ) {
        return '';
    }
    $value = strtr( trim( $value ), array(
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ) );
    // Permit familiar visual separators, but never silently discard letters,
    // extensions or URL syntax. A local leading zero is not a country code.
    $value = preg_replace( '/[\s()\-]+/u', '', $value );
    if ( ! is_string( $value ) ) {
        return '';
    }
    if ( 0 === strpos( $value, '00' ) ) {
        $value = substr( $value, 2 );
    } elseif ( 0 === strpos( $value, '+' ) ) {
        $value = substr( $value, 1 );
    }
    return preg_match( '/^[1-9][0-9]{8,14}$/D', $value ) ? $value : '';
}
