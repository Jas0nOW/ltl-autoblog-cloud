<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }



require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-secrets.php';

class LTL_SAAS_Portal_Admin {
    const OPTION_MAKE_TOKEN = 'ltl_saas_make_token';
    const OPTION_CHECKOUT_URL_STARTER = 'ltl_saas_checkout_url_starter';
    const OPTION_CHECKOUT_URL_PRO = 'ltl_saas_checkout_url_pro';
    const OPTION_CHECKOUT_URL_AGENCY = 'ltl_saas_checkout_url_agency';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'gumroad_json_error_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_head', array( $this, 'output_custom_colors' ) );
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

        // Color Customizer
        register_setting(
            'ltl_saas_portal_settings',
            'ltl_saas_custom_colors',
            array(
                'type' => 'array',
                'sanitize_callback' => array( $this, 'sanitize_custom_colors' ),
                'show_in_rest' => false,
                'default' => array(),
            )
        );
    }

    public function sanitize_gumroad_secret($value) {
        if ( ! is_string( $value ) ) {
            $value = '';
        }
        $value = trim($value);
        return preg_match('/^[A-Za-z0-9\-_]{16,}$/', $value) ? $value : '';
    }

    public function sanitize_gumroad_product_map($value) {
        if ( ! is_string( $value ) ) {
            $value = '';
        }
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
        if ( ! is_string( $value ) ) {
            $value = '';
        }
        // Only allow base64url (A-Za-z0-9-_)
        return preg_match('/^[A-Za-z0-9\-_]{32,}$/', $value) ? $value : '';
    }

    public function sanitize_custom_colors( $colors ) {
        if ( ! is_array( $colors ) ) {
            return array();
        }

        $sanitized = array();
        $allowed_keys = array( 'primary', 'success', 'error', 'warning' );

        foreach ( $colors as $key => $value ) {
            if ( in_array( $key, $allowed_keys ) ) {
                $sanitized[ $key ] = sanitize_hex_color( $value );
            }
        }

        return $sanitized;
    }

    public function enqueue_admin_assets( $hook ) {
        // Only load on our plugin pages
        if ( strpos( $hook, 'ltl-saas-portal' ) === false ) {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'ltlb-admin',
            LTL_SAAS_PORTAL_PLUGIN_URL . 'assets/admin.css',
            array(),
            LTL_SAAS_PORTAL_VERSION
        );

        // Enqueue admin JS
        wp_enqueue_script(
            'ltlb-admin',
            LTL_SAAS_PORTAL_PLUGIN_URL . 'assets/admin.js',
            array( 'jquery' ),
            LTL_SAAS_PORTAL_VERSION,
            true
        );

        // Localize script with strings and AJAX URL
        wp_localize_script( 'ltlb-admin', 'ltlbAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ltlb_admin' ),
            'strings' => array(
                'confirm_regenerate' => __( 'Regenerating will invalidate the old token. Continue?', 'ltl-saas-portal' ),
                'testing' => __( 'Testing...', 'ltl-saas-portal' ),
            ),
        ) );
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

    /**
     * Get current active tab
     */
    private function get_current_tab() {
        return isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
    }

    /**
     * Render tab navigation
     */
    private function render_tabs() {
        $current = $this->get_current_tab();
        $tabs = array(
            'settings' => __( 'Settings', 'ltl-saas-portal' ),
            'design' => __( 'Design', 'ltl-saas-portal' ),
        );

        echo '<h2 class="nav-tab-wrapper ltlb-nav-tabs">';
        foreach ( $tabs as $slug => $label ) {
            $active = $current === $slug ? 'nav-tab-active' : '';
            $url = admin_url( 'admin.php?page=ltl-saas-portal&tab=' . $slug );
            echo '<a href="' . esc_url( $url ) . '" class="nav-tab ' . $active . '">';
            echo esc_html( $label );
            echo '</a>';
        }
        echo '</h2>';
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>LTL AutoBlog Cloud Portal</h1>';

        // Render tab navigation
        $this->render_tabs();

        $current_tab = $this->get_current_tab();

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

        // Render content based on current tab
        if ( $current_tab === 'design' ) {
            echo '<form method="post" action="options.php">';
            settings_fields( 'ltl_saas_portal_settings' );
            $this->render_tab_design();
            submit_button();
            echo '</form>';
        } else {
            $this->render_tab_settings();
        }

        echo '</div>';
    }

    /**
     * Render Settings tab
     */
    private function render_tab_settings() {
        echo '<p>Admin-Seite (nur Owner). Kunden nutzen den Shortcode <code>[ltl_saas_dashboard]</code>.</p>';

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
        echo '<br><span style="color:#888;">JSON, z.B. {"prod_ABC123": "basic", "prod_DEF456": "pro", "prod_GHI789": "studio"} <strong>(Validate JSON vor dem Speichern!)</strong></span>';
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
        echo '<th scope="row">Checkout URL - Basic</th>';
        echo '<td>';
        $url_starter = get_option(self::OPTION_CHECKOUT_URL_STARTER, '');
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_STARTER) . '" value="' . esc_attr($url_starter) . '" style="width:100%; max-width:400px;">';
        echo '<br><span style="color:#888;">Beispiel: https://gumroad.com/l/ltl-basic</span>';
        echo '</td></tr>';

        echo '<tr valign="top">';
        echo '<th scope="row">Checkout URL - Pro</th>';
        echo '<td>';
        $url_pro = get_option(self::OPTION_CHECKOUT_URL_PRO, '');
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_PRO) . '" value="' . esc_attr($url_pro) . '" style="width:100%; max-width:400px;">';
        echo '<br><span style="color:#888;">Beispiel: https://gumroad.com/l/ltl-pro</span>';
        echo '</td></tr>';

        echo '<tr valign="top">';
        echo '<th scope="row">Checkout URL - Studio</th>';
        echo '<td>';
        $url_agency = get_option(self::OPTION_CHECKOUT_URL_AGENCY, '');
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_AGENCY) . '" value="' . esc_attr($url_agency) . '" style="width:100%; max-width:400px;">';
        echo '<br><span style="color:#888;">Beispiel: https://gumroad.com/l/ltl-studio</span>';
        echo '</td></tr>';

        echo '</table>';

        submit_button();
        echo '</form>';
    }

    // ============================================
    // Color Customizer Helper Methods
    // ============================================

    /**
     * Output custom colors as inline CSS
     */
    public function output_custom_colors() {
        $custom_colors = get_option( 'ltl_saas_custom_colors', array() );
        if ( empty( $custom_colors ) ) {
            return;
        }

        echo '<style id="ltlb-custom-colors">';
        echo ':root {';

        foreach ( $custom_colors as $key => $color ) {
            if ( empty( $color ) ) {
                continue;
            }

            echo '--ltlb-color-' . esc_attr( $key ) . ': ' . esc_attr( $color ) . ';';

            // Auto-generate light variants
            if ( in_array( $key, array( 'success', 'error', 'warning' ) ) ) {
                $light = $this->adjust_color_brightness( $color, 80 );
                echo '--ltlb-color-' . esc_attr( $key ) . '-light: ' . esc_attr( $light ) . ';';
            }

            // Auto-generate hover variant for primary
            if ( $key === 'primary' ) {
                $hover = $this->adjust_color_brightness( $color, -10 );
                echo '--ltlb-color-primary-hover: ' . esc_attr( $hover ) . ';';
            }
        }

        echo '}';
        echo '</style>';
    }

    /**
     * Get default color value
     *
     * @param string $key Color key
     * @return string Hex color
     */
    private function get_default_color( $key ) {
        $defaults = array(
            'primary' => '#667eea',
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107',
        );
        return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '#667eea';
    }

    /**
     * Adjust color brightness (for auto-generating light/hover variants)
     *
     * @param string $hex Hex color value
     * @param int $percent Brightness adjustment (-100 to 100)
     * @return string Adjusted hex color
     */
    private function adjust_color_brightness( $hex, $percent ) {
        $hex = str_replace( '#', '', $hex );

        if ( strlen( $hex ) !== 6 ) {
            return '#' . $hex;
        }

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $r = min( 255, max( 0, $r + ( $r * $percent / 100 ) ) );
        $g = min( 255, max( 0, $g + ( $g * $percent / 100 ) ) );
        $b = min( 255, max( 0, $b + ( $b * $percent / 100 ) ) );

        return sprintf( '#%02x%02x%02x', round( $r ), round( $g ), round( $b ) );
    }

    /**
     * Render Design tab with color customizer
     */
    private function render_tab_design() {
        echo '<div class="ltlb-section">';
        echo '<div class="ltlb-section__header">';
        echo '<h3 class="ltlb-section__title">' . esc_html__( 'Brand Colors', 'ltl-saas-portal' ) . '</h3>';
        echo '<p class="ltlb-section__description">';
        echo esc_html__( 'Customize colors to match your brand. Changes apply to both admin and frontend.', 'ltl-saas-portal' );
        echo '</p>';
        echo '</div>';

        echo '<div class="ltlb-color-customizer">';
        echo '<div class="ltlb-color-customizer__controls">';

        $colors = array(
            'primary' => __( 'Primary Color', 'ltl-saas-portal' ),
            'success' => __( 'Success Color', 'ltl-saas-portal' ),
            'error' => __( 'Error Color', 'ltl-saas-portal' ),
            'warning' => __( 'Warning Color', 'ltl-saas-portal' ),
        );

        $saved_colors = get_option( 'ltl_saas_custom_colors', array() );

        foreach ( $colors as $key => $label ) {
            $default = $this->get_default_color( $key );
            $value = isset( $saved_colors[ $key ] ) ? $saved_colors[ $key ] : $default;

            echo '<div class="ltlb-color-field">';
            echo '<label for="color_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
            echo '<div class="ltlb-color-field__input-group">';
            echo '<input type="color" id="color_' . esc_attr( $key ) . '" name="ltl_saas_custom_colors[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="ltlb-color-picker" data-color-key="' . esc_attr( $key ) . '" />';
            echo '<input type="text" value="' . esc_attr( $value ) . '" class="ltlb-color-hex" readonly />';
            echo '<button type="button" class="button ltlb-reset-color" data-default="' . esc_attr( $default ) . '" data-target="color_' . esc_attr( $key ) . '">';
            echo '↺ ' . esc_html__( 'Reset', 'ltl-saas-portal' );
            echo '</button>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>'; // .ltlb-color-customizer__controls

        // Live Preview
        echo '<div class="ltlb-color-customizer__preview">';
        echo '<h4>' . esc_html__( 'Live Preview', 'ltl-saas-portal' ) . '</h4>';
        echo '<div class="ltlb-preview-samples">';

        // Sample components
        echo '<button type="button" class="ltlb-btn ltlb-btn--primary">' . esc_html__( 'Primary Button', 'ltl-saas-portal' ) . '</button>';
        echo '<button type="button" class="ltlb-btn ltlb-btn--secondary">' . esc_html__( 'Secondary Button', 'ltl-saas-portal' ) . '</button>';

        echo '<div style="margin-top: 12px;">';
        echo '<span class="ltlb-badge ltlb-badge--success">' . esc_html__( 'Success', 'ltl-saas-portal' ) . '</span> ';
        echo '<span class="ltlb-badge ltlb-badge--error">' . esc_html__( 'Error', 'ltl-saas-portal' ) . '</span> ';
        echo '<span class="ltlb-badge ltlb-badge--warning">' . esc_html__( 'Warning', 'ltl-saas-portal' ) . '</span>';
        echo '</div>';

        echo '<div class="ltlb-card" style="margin-top: 16px; max-width: 100%;">';
        echo '<div class="ltlb-card__header">';
        echo '<h3 class="ltlb-card__title">' . esc_html__( 'Sample Card', 'ltl-saas-portal' ) . '</h3>';
        echo '<span class="ltlb-badge ltlb-badge--success">' . esc_html__( 'Active', 'ltl-saas-portal' ) . '</span>';
        echo '</div>';
        echo '<div class="ltlb-card__body">';
        echo '<p>' . esc_html__( 'This is how your customized colors will look in cards and sections.', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // .ltlb-preview-samples
        echo '</div>'; // .ltlb-color-customizer__preview

        echo '</div>'; // .ltlb-color-customizer

        echo '</div>'; // .ltlb-section
    }
}
