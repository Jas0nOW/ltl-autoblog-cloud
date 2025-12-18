<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }



require_once dirname(__FILE__,2) . '/../class-ltl-saas-portal-secrets.php';

class LTL_SAAS_Portal_Admin {
    const OPTION_MAKE_TOKEN = 'ltl_saas_make_token';
    const OPTION_CHECKOUT_URL_STARTER = 'ltl_saas_checkout_url_starter';
    const OPTION_CHECKOUT_URL_PRO = 'ltl_saas_checkout_url_pro';
    const OPTION_CHECKOUT_URL_AGENCY = 'ltl_saas_checkout_url_agency';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'gumroad_json_error_notice' ) );
    }

    public function register_settings() {
        register_setting(
            'ltl_saas_portal_settings',
            self::OPTION_MAKE_TOKEN,
            array(
                'type' => 'string',
                'sanitize_callback' => array( $this, 'sanitize_token' ),
                'show_in_rest' => false,
                'default' => '',
            )
        );
        register_setting(
            'ltl_saas_portal_settings',
            LTL_SAAS_Portal_Secrets::OPTION_GUMROAD_SECRET,
            array(
                'type' => 'string',
                'sanitize_callback' => array( $this, 'sanitize_gumroad_secret' ),
                'show_in_rest' => false,
                'default' => '',
            )
        );
        register_setting(
            'ltl_saas_portal_settings',
            LTL_SAAS_Portal_Secrets::OPTION_GUMROAD_PRODUCT_MAP,
            array(
                'type' => 'string',
                'sanitize_callback' => array( $this, 'sanitize_gumroad_product_map' ),
                'show_in_rest' => false,
                'default' => '',
            )
        );
        // Issue #19: Checkout Links
        register_setting(
            'ltl_saas_portal_settings',
            self::OPTION_CHECKOUT_URL_STARTER,
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'show_in_rest' => false,
                'default' => '',
            )
        );
        register_setting(
            'ltl_saas_portal_settings',
            self::OPTION_CHECKOUT_URL_PRO,
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'show_in_rest' => false,
                'default' => '',
            )
        );
        register_setting(
            'ltl_saas_portal_settings',
            self::OPTION_CHECKOUT_URL_AGENCY,
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'show_in_rest' => false,
                'default' => '',
            )
        );
    }

    public function sanitize_gumroad_secret($value) {
        $value = trim($value);
        return preg_match('/^[A-Za-z0-9\-_]{16,}$/', $value) ? $value : '';
    }

    public function sanitize_gumroad_product_map($value) {
        $value = trim($value);
        $arr = json_decode($value, true);
        if (!is_array($arr)) {
            set_transient('ltl_saas_gumroad_json_error', true, 30);
            return get_option(LTL_SAAS_Portal_Secrets::OPTION_GUMROAD_PRODUCT_MAP, '');
        }
        delete_transient('ltl_saas_gumroad_json_error');
        return wp_json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function gumroad_json_error_notice() {
        if (get_transient('ltl_saas_gumroad_json_error')) {
            echo '<div class="notice notice-error"><p>Gumroad Product Map: Ungültiges JSON! Änderungen wurden nicht gespeichert.</p></div>';
        }
    }

    public function sanitize_token( $value ) {
        // Only allow base64url (A-Za-z0-9-_)
        return preg_match('/^[A-Za-z0-9\-_]{32,}$/', $value) ? $value : '';
    }

    public function register_menu() {
        add_menu_page(
            'LTL AutoBlog Cloud',
            'LTL AutoBlog Cloud',
            'manage_options',
            'ltl-saas-portal',
            array( $this, 'render_admin_page' ),
            'dashicons-cloud',
            58
        );
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>LTL AutoBlog Cloud Portal</h1>';
        echo '<p>Admin-Seite (nur Owner). Kunden nutzen den Shortcode <code>[ltl_saas_dashboard]</code>.</p>';

        // Handle API Key regeneration
        if (isset($_POST['ltl_saas_regenerate_api_key'])) {
            check_admin_referer('ltl_saas_portal_settings-options');
            $new_key = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            LTL_SAAS_Portal_Secrets::set_api_key($new_key);
            echo '<div class="updated"><p>Neuer API Key generiert.</p></div>';
        }

        // Handle Make Token generation
        if (isset($_POST['ltl_saas_generate_token'])) {
            check_admin_referer('ltl_saas_portal_settings-options');
            $new_token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            LTL_SAAS_Portal_Secrets::set_make_token($new_token);
            echo '<div class="updated"><p>Neuer Make Token generiert.</p></div>';
        }

        // Handle Gumroad Secret generation
        if (isset($_POST['ltl_saas_generate_gumroad_secret'])) {
            check_admin_referer('ltl_saas_portal_settings-options');
            $new_secret = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
            LTL_SAAS_Portal_Secrets::set_gumroad_secret($new_secret);
            echo '<div class="updated"><p>Neues Gumroad Secret generiert.</p></div>';
        }

        echo '<form method="post" action="options.php">';
        settings_fields('ltl_saas_portal_settings');
        do_settings_sections('ltl_saas_portal_settings');

        $token = LTL_SAAS_Portal_Secrets::get_make_token();
        $token_set = !empty($token);
        $token_hint = $token_set ? ('••••••' . substr($token, -4)) : '—';

        $api_key = LTL_SAAS_Portal_Secrets::get_api_key();
        $api_key_set = !empty($api_key);
        $api_key_hint = $api_key_set ? ('••••••' . substr($api_key, -4)) : '—';

        $gumroad_secret = LTL_SAAS_Portal_Secrets::get_gumroad_secret();
        $gumroad_secret_set = !empty($gumroad_secret);
        $gumroad_secret_hint = $gumroad_secret_set ? ('••••••' . substr($gumroad_secret, -4)) : '—';

        $gumroad_map = get_option(LTL_SAAS_Portal_Secrets::OPTION_GUMROAD_PRODUCT_MAP, '');

        echo '<table class="form-table">';
        // Make Token row
        echo '<tr valign="top">';
        echo '<th scope="row">Make Token (keep secret)</th>';
        echo '<td>';
        if ($token_set) {
            echo '<span style="font-weight:bold; color:green;">Token gesetzt</span> ';
            echo '<span style="color:#888;">(' . esc_html($token_hint) . ')</span>';
        } else {
            echo '<span style="color:#888;">Kein Token gesetzt</span>';
        }
        echo '<br><button type="submit" name="ltl_saas_generate_token" class="button">Generate new token</button>';
        echo '</td></tr>';

        // API Key row
        echo '<tr valign="top">';
        echo '<th scope="row">API Key (Portal → Make)</th>';
        echo '<td>';
        if ($api_key_set) {
            echo '<span style="font-weight:bold; color:green;">API Key gesetzt</span> ';
            echo '<span style="color:#888;">(' . esc_html($api_key_hint) . ')</span>';
        } else {
            echo '<span style="color:#888;">Kein API Key gesetzt</span>';
        }
        echo '<br><button type="submit" name="ltl_saas_regenerate_api_key" class="button">Generate new API key</button>';
        echo '</td></tr>';

        // Gumroad Billing section
        echo '<tr valign="top"><th colspan="2"><h2>Billing (Gumroad)</h2></th></tr>';
        // Gumroad Secret
        echo '<tr valign="top">';
        echo '<th scope="row">Gumroad Secret</th>';
        echo '<td>';
        if ($gumroad_secret_set) {
            echo '<span style="font-weight:bold; color:green;">Secret gesetzt</span> ';
            echo '<span style="color:#888;">(' . esc_html($gumroad_secret_hint) . ')</span>';
        } else {
            echo '<span style="color:#888;">Kein Secret gesetzt</span>';
        }
        echo '<br><button type="submit" name="ltl_saas_generate_gumroad_secret" class="button">Generate new secret</button>';
        echo '</td></tr>';

        // Gumroad Product Map
        echo '<tr valign="top">';
        echo '<th scope="row">Product-ID → Plan Mapping</th>';
        echo '<td>';
        echo '<textarea name="' . esc_attr(LTL_SAAS_Portal_Secrets::OPTION_GUMROAD_PRODUCT_MAP) . '" rows="6" cols="60">' . esc_textarea($gumroad_map) . '</textarea>';
        echo '<br><span style="color:#888;">JSON, z.B. {"prod_ABC123": "starter", ...} <strong>(Validate JSON vor dem Speichern!)</strong></span>';
        echo '</td></tr>';

        // Help text
        echo '<tr valign="top">';
        echo '<th scope="row">Ping URL</th>';
        echo '<td>';
        echo '<code>https://' . esc_html($_SERVER['HTTP_HOST']) . '/wp-json/ltl-saas/v1/gumroad/ping?secret=XXXX</code>';
        echo '</td></tr>';

        // Issue #19: Marketing Section - Checkout Links
        echo '<tr valign="top"><th colspan="2"><h2>Marketing (Pricing Landing Page)</h2></th></tr>';

        echo '<tr valign="top">';
        echo '<th scope="row">Checkout URL - Starter</th>';
        echo '<td>';
        $url_starter = get_option(self::OPTION_CHECKOUT_URL_STARTER, '');
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_STARTER) . '" value="' . esc_attr($url_starter) . '" style="width:100%; max-width:400px;">';
        echo '<br><span style="color:#888;">Beispiel: https://gumroad.com/l/ltl-starter</span>';
        echo '</td></tr>';

        echo '<tr valign="top">';
        echo '<th scope="row">Checkout URL - Pro</th>';
        echo '<td>';
        $url_pro = get_option(self::OPTION_CHECKOUT_URL_PRO, '');
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_PRO) . '" value="' . esc_attr($url_pro) . '" style="width:100%; max-width:400px;">';
        echo '<br><span style="color:#888;">Beispiel: https://gumroad.com/l/ltl-pro</span>';
        echo '</td></tr>';

        echo '<tr valign="top">';
        echo '<th scope="row">Checkout URL - Agency</th>';
        echo '<td>';
        $url_agency = get_option(self::OPTION_CHECKOUT_URL_AGENCY, '');
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_AGENCY) . '" value="' . esc_attr($url_agency) . '" style="width:100%; max-width:400px;">';
        echo '<br><span style="color:#888;">Beispiel: https://gumroad.com/l/ltl-agency</span>';
        echo '</td></tr>';

        echo '</table>';

        submit_button();
        echo '</form>';

        echo '</div>';
    }
}
