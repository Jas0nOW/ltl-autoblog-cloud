<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTL_SAAS_Portal_Crypto {
    /**
     * Encrypts a string using AES-256-CBC, key derived from AUTH_KEY and SECURE_AUTH_KEY.
     * Returns base64-encoded string: iv:ciphertext
     */
    public static function encrypt( $plaintext ) {
        $key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );
        $iv = openssl_random_pseudo_bytes( 16 );
        $ciphertext = openssl_encrypt( $plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
        return base64_encode( $iv ) . ':' . base64_encode( $ciphertext );
    }

    /**
     * Decrypts a string previously encrypted with encrypt().
     */
    public static function decrypt( $data ) {
        if ( strpos( $data, ':' ) === false ) return false;
        list( $iv_b64, $ciphertext_b64 ) = explode( ':', $data, 2 );
        $key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );
        $iv = base64_decode( $iv_b64 );
        $ciphertext = base64_decode( $ciphertext_b64 );
        return openssl_decrypt( $ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
    }
}
