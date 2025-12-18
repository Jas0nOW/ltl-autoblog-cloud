<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


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

        echo '<form method="post" action="options.php">';
        settings_fields('ltl_saas_portal_settings');
        do_settings_sections('ltl_saas_portal_settings');

        $token = get_option(self::OPTION_MAKE_TOKEN, '');
        $token_set = !empty($token);
        $token_hint = $token_set ? ('****' . substr($token, -4)) : '—';

        echo '<table class="form-table"><tr valign="top">';
        echo '<th scope="row">Make Token (keep secret)</th>';
        echo '<td>';
        if ($token_set) {
            echo '<span style="font-weight:bold; color:green;">Token gesetzt</span> ';
            echo '<span style="color:#888;">(' . esc_html($token_hint) . ')</span>';
        } else {
            echo '<span style="color:#888;">Kein Token gesetzt</span>';
        }
        echo '<br><button type="submit" name="ltl_saas_generate_token" class="button">Generate new token</button>';
        echo '</td></tr></table>';

        submit_button();
        echo '</form>';

        // Handle token generation
        if (isset($_POST['ltl_saas_generate_token'])) {
            check_admin_referer('ltl_saas_portal_settings-options');
            $new_token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            update_option(self::OPTION_MAKE_TOKEN, $new_token);
            echo '<div class="updated"><p>Neuer Token generiert.</p></div>';
        }

        echo '</div>';
    }
}
