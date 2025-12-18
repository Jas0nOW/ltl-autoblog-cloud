<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTL_SAAS_Portal_Crypto {
    /**
     * Encrypts a string using AES-256-CBC + HMAC-SHA256 (v1 format).
     * Format: v1:<base64(iv)>:<base64(ciphertext)>:<base64(hmac)>
     */
    public static function encrypt( $plaintext ) {
        if (!is_string($plaintext)) {
            return new WP_Error('crypto_invalid_plaintext', 'Invalid plaintext for encryption.');
        }
        $enc_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true);
        $mac_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . 'mac', true);
        $iv = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $enc_key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            return new WP_Error('crypto_encrypt_failed', 'Encryption failed.');
        }
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $mac_key, true);
        return 'v1:' . base64_encode($iv) . ':' . base64_encode($ciphertext) . ':' . base64_encode($hmac);
    }

    /**
     * Decrypts a string previously encrypted with encrypt().
     * Supports v1 (HMAC) and legacy (iv:ciphertext) format.
     * On error: returns WP_Error (never secrets in message)
     */
    public static function decrypt( $data ) {
        if (!is_string($data) || $data === '') {
            return new WP_Error('crypto_invalid_data', 'No data to decrypt.');
        }
        if (strpos($data, 'v1:') === 0) {
            // v1 format: v1:<iv>:<ciphertext>:<hmac>
            $parts = explode(':', $data, 4);
            if (count($parts) !== 4) {
                return new WP_Error('crypto_invalid_format', 'Invalid v1 crypto format.');
            }
            list(, $iv_b64, $ciphertext_b64, $hmac_b64) = $parts;
            $enc_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true);
            $mac_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . 'mac', true);
            $iv = base64_decode($iv_b64);
            $ciphertext = base64_decode($ciphertext_b64);
            $hmac = base64_decode($hmac_b64);
            if ($iv === false || $ciphertext === false || $hmac === false) {
                return new WP_Error('crypto_decode_error', 'Base64 decode failed.');
            }
            $calc_hmac = hash_hmac('sha256', $iv . $ciphertext, $mac_key, true);
            if (!hash_equals($hmac, $calc_hmac)) {
                return new WP_Error('crypto_hmac_mismatch', 'Integrity check failed.');
            }
            $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $enc_key, OPENSSL_RAW_DATA, $iv);
            if ($plaintext === false) {
                return new WP_Error('crypto_decrypt_failed', 'Decryption failed.');
            }
            return $plaintext;
        }
        // Legacy format: <iv>:<ciphertext>
        if (strpos($data, ':') !== false) {
            list($iv_b64, $ciphertext_b64) = explode(':', $data, 2);
            $enc_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true);
            $iv = base64_decode($iv_b64);
            $ciphertext = base64_decode($ciphertext_b64);
            if ($iv === false || $ciphertext === false) {
                return new WP_Error('crypto_decode_error', 'Base64 decode failed (legacy).');
            }
            $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $enc_key, OPENSSL_RAW_DATA, $iv);
            if ($plaintext === false) {
                return new WP_Error('crypto_decrypt_failed', 'Legacy decryption failed.');
            }
            // Optional: could return ['plaintext' => $plaintext, 'legacy' => true]
            return $plaintext;
        }
        return new WP_Error('crypto_invalid_format', 'Unknown crypto format.');
    }
}
