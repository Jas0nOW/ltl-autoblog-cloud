<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTL_SAAS_Portal_Secrets {
    const OPTION_MAKE_TOKEN = 'ltl_saas_make_token';
    const OPTION_API_KEY = 'ltl_saas_api_key';
    const OPTION_GUMROAD_SECRET = 'ltl_saas_gumroad_secret';
    const OPTION_GUMROAD_PRODUCT_MAP = 'ltl_saas_gumroad_product_map';

    public static function get_make_token() {
        $token = get_option(self::OPTION_MAKE_TOKEN, '');
        return is_string($token) ? trim($token) : '';
    }

    public static function set_make_token($token) {
        if (!is_string($token)) return;
        $token = trim($token);
        if (!preg_match('/^[A-Za-z0-9\-_]{32,}$/', $token)) return;
        update_option(self::OPTION_MAKE_TOKEN, $token);
    }

    public static function get_api_key() {
        $key = get_option(self::OPTION_API_KEY, '');
        return is_string($key) ? trim($key) : '';
    }

    public static function set_api_key($key) {
        if (!is_string($key)) return;
        $key = trim($key);
        if (strlen($key) < 16) return;
        update_option(self::OPTION_API_KEY, $key);
    }

    public static function get_gumroad_secret() {
        $secret = get_option(self::OPTION_GUMROAD_SECRET, '');
        return is_string($secret) ? trim($secret) : '';
    }

    public static function set_gumroad_secret($secret) {
        if (!is_string($secret)) return;
        $secret = trim($secret);
        if (!preg_match('/^[A-Za-z0-9\-_]{16,}$/', $secret)) return;
        update_option(self::OPTION_GUMROAD_SECRET, $secret);
    }

    public static function get_gumroad_product_map() {
        $json = get_option(self::OPTION_GUMROAD_PRODUCT_MAP, '');
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    public static function set_gumroad_product_map($json) {
        if (!is_string($json)) return;
        $arr = json_decode($json, true);
        if (!is_array($arr)) return;
        $pretty = wp_json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        update_option(self::OPTION_GUMROAD_PRODUCT_MAP, $pretty);
    }

    public static function has_make_token() {
        $token = self::get_make_token();
        return !empty($token);
    }
}
