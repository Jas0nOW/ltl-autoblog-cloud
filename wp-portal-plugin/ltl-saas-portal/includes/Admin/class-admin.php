<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }



require_once dirname(__FILE__,2) . '/../class-ltl-saas-portal-secrets.php';

class LTL_SAAS_Portal_Admin {
    const OPTION_MAKE_TOKEN = 'ltl_saas_make_token';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
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

        echo '<form method="post" action="options.php">';
        settings_fields('ltl_saas_portal_settings');
        do_settings_sections('ltl_saas_portal_settings');

        $token = LTL_SAAS_Portal_Secrets::get_make_token();
        $token_set = !empty($token);
        $token_hint = $token_set ? ('••••••' . substr($token, -4)) : '—';

        $api_key = LTL_SAAS_Portal_Secrets::get_api_key();
        $api_key_set = !empty($api_key);
        $api_key_hint = $api_key_set ? ('••••••' . substr($api_key, -4)) : '—';

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

        echo '</table>';

        submit_button();
        echo '</form>';

        echo '</div>';
    }
}
