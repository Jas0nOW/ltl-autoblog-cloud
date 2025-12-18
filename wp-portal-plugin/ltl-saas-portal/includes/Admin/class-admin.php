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
        // Output custom colors after stylesheets (priority 100)
        add_action( 'admin_head', array( $this, 'output_custom_colors' ), 100 );
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
                'sanitize_callback' => array( $this, 'sanitize_url_safe' ),
                'show_in_rest' => false,
                'default' => '',
            )
        );
        register_setting(
            'ltl_saas_portal_settings',
            self::OPTION_CHECKOUT_URL_PRO,
            array(
                'type' => 'string',
                'sanitize_callback' => array( $this, 'sanitize_url_safe' ),
                'show_in_rest' => false,
                'default' => '',
            )
        );
        register_setting(
            'ltl_saas_portal_settings',
            self::OPTION_CHECKOUT_URL_AGENCY,
            array(
                'type' => 'string',
                'sanitize_callback' => array( $this, 'sanitize_url_safe' ),
                'show_in_rest' => false,
                'default' => '',
            )
        );

        // Color Customizer - Backend Colors
        register_setting(
            'ltl_saas_portal_settings',
            'ltl_saas_custom_colors_backend',
            array(
                'type' => 'array',
                'sanitize_callback' => array( $this, 'sanitize_custom_colors' ),
                'show_in_rest' => false,
                'default' => array(),
            )
        );

        // Color Customizer - Frontend Colors
        register_setting(
            'ltl_saas_portal_settings',
            'ltl_saas_custom_colors_frontend',
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

    public function sanitize_url_safe( $value ) {
        // Prevent PHP 8.3 ltrim(null) deprecations from esc_url_raw
        if ( ! is_string( $value ) ) {
            return '';
        }
        return esc_url_raw( $value );
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
        $allowed_keys = array( 'primary', 'success', 'error', 'warning', 'form_bg' );

        foreach ( $allowed_keys as $key ) {
            $raw_value = isset( $colors[ $key ] ) ? $colors[ $key ] : '';
            $value = is_string( $raw_value ) ? trim( $raw_value ) : '';

            // Ensure a leading # for sanitize_hex_color
            if ( $value !== '' && $value[0] !== '#' ) {
                $value = '#' . $value;
            }

            // Use WP core sanitizer; falls back to default on invalid input
            $clean = sanitize_hex_color( $value );
            if ( ! $clean ) {
                $clean = $this->get_default_color( $key );
            }

            $sanitized[ $key ] = $clean;
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

    /**     * Render language switcher
     */
    private function render_language_switcher() {
        $current_lang = get_user_meta(get_current_user_id(), 'ltl_portal_language', true);
        if (!$current_lang) {
            $current_lang = 'en_US'; // Default
        }

        $base_url = admin_url('admin.php?page=ltl-saas-portal&tab=' . $this->get_current_tab());

        echo '<div class="ltlb-language-switcher">';
        echo '<span class="ltlb-language-switcher__label">🌍 ' . esc_html__('Language:', 'ltl-saas-portal') . '</span>';

        // English Button
        $en_class = $current_lang === 'en_US' ? 'ltlb-lang-btn ltlb-lang-btn--active' : 'ltlb-lang-btn';
        echo '<a href="' . esc_url($base_url . '&ltl_lang=en_US') . '" class="' . $en_class . '">';
        echo '<span class="ltlb-lang-btn__flag">🇺🇸</span>';
        echo '<span class="ltlb-lang-btn__text">English</span>';
        echo '</a>';

        // German Button
        $de_class = $current_lang === 'de_DE' ? 'ltlb-lang-btn ltlb-lang-btn--active' : 'ltlb-lang-btn';
        echo '<a href="' . esc_url($base_url . '&ltl_lang=de_DE') . '" class="' . $de_class . '">';
        echo '<span class="ltlb-lang-btn__flag">🇩🇪</span>';
        echo '<span class="ltlb-lang-btn__text">Deutsch</span>';
        echo '</a>';

        echo '</div>';
    }

    /**     * Render tab navigation
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
        // Handle language switch
        if (isset($_GET['ltl_lang']) && in_array($_GET['ltl_lang'], array('en_US', 'de_DE'))) {
            update_user_meta(get_current_user_id(), 'ltl_portal_language', sanitize_text_field($_GET['ltl_lang']));
            // Reload to apply language
            wp_redirect(admin_url('admin.php?page=ltl-saas-portal&tab=' . $this->get_current_tab()));
            exit;
        }

        // Load user's preferred language
        $user_lang = get_user_meta(get_current_user_id(), 'ltl_portal_language', true);
        if ($user_lang && in_array($user_lang, array('en_US', 'de_DE'))) {
            switch_to_locale($user_lang);
        }

        echo '<div class="wrap">';
        echo '<div class="ltlb-page-header">';
        echo '<h1>LTL AutoBlog Cloud Portal</h1>';
        $this->render_language_switcher();
        echo '</div>';

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
     * Render Settings tab - Professional Agency Design
     */
    private function render_tab_settings() {
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

        echo '<form method="post" action="options.php">';
        settings_fields('ltl_saas_portal_settings');
        do_settings_sections('ltl_saas_portal_settings');

        echo '<div class="ltlb-tab-header">';
        echo '<h2>' . esc_html__( 'Portal Settings', 'ltl-saas-portal' ) . '</h2>';
        submit_button( __( 'Save Changes', 'ltl-saas-portal' ), 'primary', 'submit', false );
        echo '</div>';

        // ===== HERO INFO BOX =====
        echo '<div class="ltlb-hero-box">';
        echo '<div class="ltlb-hero-box__icon">🚀</div>';
        echo '<div class="ltlb-hero-box__content">';
        echo '<h2>' . esc_html__( 'LTL AutoBlog Cloud Settings', 'ltl-saas-portal' ) . '</h2>';
        echo '<p>' . esc_html__( 'Configure your API keys, tokens, and integrations. Customers can access their dashboard via the shortcode:', 'ltl-saas-portal' ) . ' <code>[ltl_saas_dashboard]</code></p>';
        echo '</div>';
        echo '</div>';

        // ===== API & AUTHENTICATION SECTION =====
        echo '<div class="ltlb-settings-section">';
        echo '<div class="ltlb-settings-section__header">';
        echo '<div class="ltlb-settings-section__icon">🔐</div>';
        echo '<div>';
        echo '<h3>' . esc_html__( 'API & Authentication', 'ltl-saas-portal' ) . '</h3>';
        echo '<p>' . esc_html__( 'Secure tokens for Make.com integration and portal authentication.', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';
        echo '</div>';

        echo '<div class="ltlb-settings-grid">';

        // Make Token Card
        echo '<div class="ltlb-settings-card">';
        echo '<div class="ltlb-settings-card__header">';
        echo '<span class="ltlb-settings-card__title">Make Token</span>';
        if ($token_set) {
            echo '<span class="ltlb-status ltlb-status--success">✓ Active</span>';
        } else {
            echo '<span class="ltlb-status ltlb-status--warning">Not Set</span>';
        }
        echo '</div>';
        echo '<div class="ltlb-settings-card__body">';
        echo '<p class="ltlb-settings-card__description">' . esc_html__( 'Used for Make.com webhook authentication.', 'ltl-saas-portal' ) . '</p>';
        if ($token_set) {
            echo '<div class="ltlb-secret-display"><code>' . esc_html($token_hint) . '</code></div>';
        }
        echo '<button type="submit" name="ltl_saas_generate_token" class="ltlb-btn ltlb-btn--secondary ltlb-btn--sm">';
        echo '<span class="ltlb-btn__icon">🔄</span> ' . ($token_set ? esc_html__( 'Regenerate', 'ltl-saas-portal' ) : esc_html__( 'Generate', 'ltl-saas-portal' ));
        echo '</button>';
        echo '</div>';
        echo '</div>';

        // API Key Card
        echo '<div class="ltlb-settings-card">';
        echo '<div class="ltlb-settings-card__header">';
        echo '<span class="ltlb-settings-card__title">API Key</span>';
        if ($api_key_set) {
            echo '<span class="ltlb-status ltlb-status--success">✓ Active</span>';
        } else {
            echo '<span class="ltlb-status ltlb-status--warning">Not Set</span>';
        }
        echo '</div>';
        echo '<div class="ltlb-settings-card__body">';
        echo '<p class="ltlb-settings-card__description">' . esc_html__( 'Portal to Make.com communication key.', 'ltl-saas-portal' ) . '</p>';
        if ($api_key_set) {
            echo '<div class="ltlb-secret-display"><code>' . esc_html($api_key_hint) . '</code></div>';
        }
        echo '<button type="submit" name="ltl_saas_regenerate_api_key" class="ltlb-btn ltlb-btn--secondary ltlb-btn--sm">';
        echo '<span class="ltlb-btn__icon">🔄</span> ' . ($api_key_set ? esc_html__( 'Regenerate', 'ltl-saas-portal' ) : esc_html__( 'Generate', 'ltl-saas-portal' ));
        echo '</button>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // .ltlb-settings-grid
        echo '</div>'; // .ltlb-settings-section

        // ===== BILLING SECTION =====
        echo '<div class="ltlb-settings-section">';
        echo '<div class="ltlb-settings-section__header">';
        echo '<div class="ltlb-settings-section__icon">💳</div>';
        echo '<div>';
        echo '<h3>' . esc_html__( 'Billing Integration (Gumroad)', 'ltl-saas-portal' ) . '</h3>';
        echo '<p>' . esc_html__( 'Configure Gumroad webhook for automatic subscription management.', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';
        echo '</div>';

        echo '<div class="ltlb-settings-grid">';

        // Gumroad Secret Card
        echo '<div class="ltlb-settings-card">';
        echo '<div class="ltlb-settings-card__header">';
        echo '<span class="ltlb-settings-card__title">Webhook Secret</span>';
        if ($gumroad_secret_set) {
            echo '<span class="ltlb-status ltlb-status--success">✓ Active</span>';
        } else {
            echo '<span class="ltlb-status ltlb-status--warning">Not Set</span>';
        }
        echo '</div>';
        echo '<div class="ltlb-settings-card__body">';
        echo '<p class="ltlb-settings-card__description">' . esc_html__( 'Secret key for Gumroad ping verification.', 'ltl-saas-portal' ) . '</p>';
        if ($gumroad_secret_set) {
            echo '<div class="ltlb-secret-display"><code>' . esc_html($gumroad_secret_hint) . '</code></div>';
        }
        echo '<button type="submit" name="ltl_saas_generate_gumroad_secret" class="ltlb-btn ltlb-btn--secondary ltlb-btn--sm">';
        echo '<span class="ltlb-btn__icon">🔄</span> ' . ($gumroad_secret_set ? esc_html__( 'Regenerate', 'ltl-saas-portal' ) : esc_html__( 'Generate', 'ltl-saas-portal' ));
        echo '</button>';
        echo '</div>';
        echo '</div>';

        // Ping URL Card
        echo '<div class="ltlb-settings-card">';
        echo '<div class="ltlb-settings-card__header">';
        echo '<span class="ltlb-settings-card__title">Webhook URL</span>';
        echo '<span class="ltlb-status ltlb-status--info">📋 Copy</span>';
        echo '</div>';
        echo '<div class="ltlb-settings-card__body">';
        echo '<p class="ltlb-settings-card__description">' . esc_html__( 'Add this URL to your Gumroad ping settings.', 'ltl-saas-portal' ) . '</p>';
        echo '<div class="ltlb-url-display">';
        echo '<code>https://' . esc_html($_SERVER['HTTP_HOST']) . '/wp-json/ltl-saas/v1/gumroad/ping?secret=YOUR_SECRET</code>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // .ltlb-settings-grid

        // Product Mapping
        echo '<div class="ltlb-settings-card ltlb-settings-card--full">';
        echo '<div class="ltlb-settings-card__header">';
        echo '<span class="ltlb-settings-card__title">📦 ' . esc_html__( 'Product ID → Plan Mapping', 'ltl-saas-portal' ) . '</span>';
        echo '</div>';
        echo '<div class="ltlb-settings-card__body">';
        echo '<p class="ltlb-settings-card__description">' . esc_html__( 'Map Gumroad product IDs to your subscription plans (JSON format).', 'ltl-saas-portal' ) . '</p>';
        echo '<textarea name="' . esc_attr(LTL_SAAS_Portal_Secrets::OPTION_GUMROAD_PRODUCT_MAP) . '" class="ltlb-textarea" rows="4" placeholder=\'{"prod_ABC123": "basic", "prod_DEF456": "pro", "prod_GHI789": "studio"}\'>' . esc_textarea($gumroad_map) . '</textarea>';
        echo '<p class="ltlb-help-text">⚠️ ' . esc_html__( 'Validate JSON before saving!', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // .ltlb-settings-section

        // ===== MARKETING SECTION =====
        echo '<div class="ltlb-settings-section">';
        echo '<div class="ltlb-settings-section__header">';
        echo '<div class="ltlb-settings-section__icon">🎯</div>';
        echo '<div>';
        echo '<h3>' . esc_html__( 'Marketing & Checkout Links', 'ltl-saas-portal' ) . '</h3>';
        echo '<p>' . esc_html__( 'Configure checkout URLs for your pricing page.', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';
        echo '</div>';

        $url_starter = get_option(self::OPTION_CHECKOUT_URL_STARTER, '');
        $url_pro = get_option(self::OPTION_CHECKOUT_URL_PRO, '');
        $url_agency = get_option(self::OPTION_CHECKOUT_URL_AGENCY, '');

        echo '<div class="ltlb-checkout-links">';

        // Basic Plan
        echo '<div class="ltlb-checkout-card ltlb-checkout-card--basic">';
        echo '<div class="ltlb-checkout-card__badge">Basic</div>';
        echo '<label>' . esc_html__( 'Checkout URL', 'ltl-saas-portal' ) . '</label>';
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_STARTER) . '" value="' . esc_attr($url_starter) . '" placeholder="https://gumroad.com/l/ltl-basic" class="ltlb-input">';
        echo '</div>';

        // Pro Plan
        echo '<div class="ltlb-checkout-card ltlb-checkout-card--pro">';
        echo '<div class="ltlb-checkout-card__badge">Pro</div>';
        echo '<label>' . esc_html__( 'Checkout URL', 'ltl-saas-portal' ) . '</label>';
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_PRO) . '" value="' . esc_attr($url_pro) . '" placeholder="https://gumroad.com/l/ltl-pro" class="ltlb-input">';
        echo '</div>';

        // Studio Plan
        echo '<div class="ltlb-checkout-card ltlb-checkout-card--studio">';
        echo '<div class="ltlb-checkout-card__badge">Studio</div>';
        echo '<label>' . esc_html__( 'Checkout URL', 'ltl-saas-portal' ) . '</label>';
        echo '<input type="url" name="' . esc_attr(self::OPTION_CHECKOUT_URL_AGENCY) . '" value="' . esc_attr($url_agency) . '" placeholder="https://gumroad.com/l/ltl-studio" class="ltlb-input">';
        echo '</div>';

        echo '</div>'; // .ltlb-checkout-links
        echo '</div>'; // .ltlb-settings-section

        // Submit Button
        echo '<div class="ltlb-submit-wrapper">';
        submit_button( esc_html__( 'Save All Settings', 'ltl-saas-portal' ), 'primary large', 'submit', false );
        echo '</div>';

        echo '</form>';
    }

    // ============================================
    // Color Customizer Helper Methods
    // ============================================

    /**
     * Output custom colors as inline CSS (Backend only)
     */
    public function output_custom_colors() {
        $custom_colors = get_option( 'ltl_saas_custom_colors_backend', array() );

        // Ensure we always output colors (use defaults if not set)
        $defaults = array(
            'primary' => '#667eea',
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'form_bg' => '#f8f9fa',
        );

        $colors = wp_parse_args( $custom_colors, $defaults );

        echo '<!-- LTL Custom Colors Backend -->';
        echo '<style id="ltlb-custom-colors-backend">';
        echo ':root {';

        foreach ( $colors as $key => $color ) {
            // Guard against null/non-string colors to prevent PHP 8.3 ltrim(null) deprecations
            if ( ! is_string( $color ) || empty( $color ) ) {
                continue;
            }

            // Convert underscore to dash for CSS variable name (form_bg -> form-bg)
            $css_key = str_replace( '_', '-', $key );

            echo '--ltlb-color-' . esc_attr( $css_key ) . ': ' . esc_attr( $color ) . ';';

            // Auto-generate light variants
            if ( in_array( $key, array( 'success', 'error', 'warning' ) ) ) {
                $light = $this->adjust_color_brightness( $color, 80 );
                echo '--ltlb-color-' . esc_attr( $css_key ) . '-light: ' . esc_attr( $light ) . ';';
            }

            // Auto-generate all primary color variants
            if ( $key === 'primary' ) {
                $hover = $this->adjust_color_brightness( $color, -10 );
                $light = $this->adjust_color_brightness( $color, 80 );
                echo '--ltlb-color-primary-hover: ' . esc_attr( $hover ) . ';';
                echo '--ltlb-color-primary-light: ' . esc_attr( $light ) . ';';
                echo '--ltlb-color-primary-gradient: linear-gradient(135deg, ' . esc_attr( $color ) . ' 0%, ' . esc_attr( $hover ) . ' 100%);';
                // RGB components for dynamic shadows
                $rgb = $this->get_rgb_components( $color );
                echo '--ltlb-color-primary-rgb: ' . esc_attr( $rgb ) . ';';
            }

            // Auto-generate contrasting text color for form background
            if ( $key === 'form_bg' ) {
                $text_color = $this->get_contrasting_color( $color );
                echo '--ltlb-color-form-text: ' . esc_attr( $text_color ) . ';';
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
            'form_bg' => '#f8f9fa',
        );
        return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '#667eea';
    }

    /**
     * Calculate contrasting text color based on background luminance
     * Returns white or dark text color for readability
     *
     * @param string $hex Hex color value
     * @return string Contrasting color (#ffffff for light bg, #1a1a1a for dark bg)
     */
    private function get_contrasting_color( $hex ) {
        $hex = str_replace( '#', '', $hex );

        if ( strlen( $hex ) !== 6 ) {
            return '#1a1a1a'; // Default to dark
        }

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        // Calculate luminance using relative luminance formula
        $luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;

        // Return white text for dark backgrounds, dark text for light backgrounds
        return $luminance > 0.5 ? '#1a1a1a' : '#ffffff';
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
     * Return RGB components (e.g., "102, 126, 234") for a hex color
     * Used to build rgba() values in CSS via variables
     *
     * @param string $hex Hex color value
     * @return string RGB components string
     */
    private function get_rgb_components( $hex ) {
        $hex = str_replace( '#', '', $hex );

        if ( strlen( $hex ) !== 6 ) {
            // Fallback to default primary rgb
            return '102, 126, 234';
        }

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        return sprintf( '%d, %d, %d', $r, $g, $b );
    }

    /**
     * Render Design tab with color customizer
     */
    private function render_tab_design() {
        echo '<form method="post" action="options.php">';
        settings_fields('ltl_saas_portal_settings');
        do_settings_sections('ltl_saas_portal_settings');

        echo '<div class="ltlb-tab-header">';
        echo '<h2>' . esc_html__( 'Customize Colors', 'ltl-saas-portal' ) . '</h2>';
        submit_button( __( 'Save Changes', 'ltl-saas-portal' ), 'primary', 'submit', false );
        echo '</div>';

        // ===== BACKEND COLORS SECTION =====
        echo '<div class="ltlb-section" style="margin-bottom: 40px;">';
        echo '<div class="ltlb-section__header">';
        echo '<h3 class="ltlb-section__title">🎨 ' . esc_html__( 'Backend Colors (Admin Area)', 'ltl-saas-portal' ) . '</h3>';
        echo '<p class="ltlb-section__description">';
        echo esc_html__( 'These colors apply only to the WordPress admin dashboard and plugin settings.', 'ltl-saas-portal' );
        echo '</p>';
        echo '</div>';

        echo '<div class="ltlb-color-customizer">';
        echo '<div class="ltlb-color-customizer__controls">';

        $colors = array(
            'primary' => __( 'Primary Color', 'ltl-saas-portal' ),
            'success' => __( 'Success Color', 'ltl-saas-portal' ),
            'error' => __( 'Error Color', 'ltl-saas-portal' ),
            'warning' => __( 'Warning Color', 'ltl-saas-portal' ),
            'form_bg' => __( 'Form Background Color', 'ltl-saas-portal' ),
        );

        $saved_colors = get_option( 'ltl_saas_custom_colors_backend', array() );

        foreach ( $colors as $key => $label ) {
            $default = $this->get_default_color( $key );
            $value = isset( $saved_colors[ $key ] ) && is_string( $saved_colors[ $key ] ) && ! empty( $saved_colors[ $key ] )
                ? $saved_colors[ $key ]
                : $default;
            // Ensure value starts with # (WordPress sanitize_hex_color should have done this)
            if ( $value && $value[0] !== '#' ) {
                $value = '#' . $value;
            }

            echo '<div class="ltlb-color-field">';
            echo '<label for="backend_color_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
            echo '<div class="ltlb-color-field__input-group">';
            echo '<input type="color" id="backend_color_' . esc_attr( $key ) . '" name="ltl_saas_custom_colors_backend[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="ltlb-color-picker" data-color-key="' . esc_attr( $key ) . '" />';
            echo '<input type="text" value="' . esc_attr( $value ) . '" class="ltlb-color-hex" readonly />';
            echo '<button type="button" class="button ltlb-reset-color" data-default="' . esc_attr( $default ) . '" data-target="backend_color_' . esc_attr( $key ) . '">';
            echo '↺ ' . esc_html__( 'Reset', 'ltl-saas-portal' );
            echo '</button>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>'; // .ltlb-color-customizer__controls

        // Backend Live Preview
        echo '<div class="ltlb-color-customizer__preview">';
        echo '<h4>' . esc_html__( 'Backend Admin Preview', 'ltl-saas-portal' ) . '</h4>';
        echo '<div class="ltlb-preview-samples">';

        // Complete Dashboard Preview - ID on the element with background!
        echo '<div id="ltlb-backend-preview" style="padding: 24px; border-radius: 12px; background: var(--ltlb-color-form-bg, #f8f9fa); box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';

        // Header Section
        echo '<div style="margin-bottom: 20px;">';
        echo '<h3 style="margin: 0 0 8px 0; color: var(--ltlb-color-form-text, #333); font-size: 1.5rem;">' . esc_html__( 'Dashboard Preview', 'ltl-saas-portal' ) . '</h3>';
        echo '<p style="margin: 0; color: var(--ltlb-color-form-text, #333); opacity: 0.8;">' . esc_html__( 'This is how your customers will see the portal', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';

        // Status Badges Row
        echo '<div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">';
        echo '<span class="ltlb-badge ltlb-badge--success">' . esc_html__( 'Active', 'ltl-saas-portal' ) . '</span>';
        echo '<span class="ltlb-badge ltlb-badge--warning">' . esc_html__( 'Pending', 'ltl-saas-portal' ) . '</span>';
        echo '<span class="ltlb-badge ltlb-badge--error">' . esc_html__( 'Error', 'ltl-saas-portal' ) . '</span>';
        echo '</div>';

        // Action Buttons Row
        echo '<div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">';
        echo '<button type="button" class="ltlb-btn ltlb-btn--primary">' . esc_html__( 'Primary Action', 'ltl-saas-portal' ) . '</button>';
        echo '<button type="button" class="ltlb-btn ltlb-btn--secondary">' . esc_html__( 'Secondary Action', 'ltl-saas-portal' ) . '</button>';
        echo '</div>';

        // Sample Card
        echo '<div class="ltlb-card" style="margin-bottom: 20px;">';
        echo '<div class="ltlb-card__header">';
        echo '<h3 class="ltlb-card__title">' . esc_html__( 'Connection Settings', 'ltl-saas-portal' ) . '</h3>';
        echo '<span class="ltlb-badge ltlb-badge--success">' . esc_html__( 'Connected', 'ltl-saas-portal' ) . '</span>';
        echo '</div>';
        echo '<div class="ltlb-card__body">';
        echo '<p style="margin: 0 0 12px 0;">' . esc_html__( 'Your WordPress site is connected and ready to receive automated blog posts.', 'ltl-saas-portal' ) . '</p>';

        // Form Field in Card
        echo '<label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.875rem; color: var(--ltlb-color-form-text, #333); text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html__( 'Website URL', 'ltl-saas-portal' ) . '</label>';
        echo '<input type="text" placeholder="https://your-website.com" value="https://example.com" style="width: 100%; padding: 10px 14px; border: 2px solid rgba(0,0,0,0.1); border-radius: 6px; background: white; color: #1a1a1a; font-size: 1rem;" disabled />';
        echo '</div>';
        echo '<div class="ltlb-card__footer" style="display: flex; justify-content: space-between; align-items: center;">';
        echo '<span style="font-size: 0.875rem; color: var(--ltlb-color-form-text, #333); opacity: 0.7;">' . esc_html__( 'Last updated: 2 hours ago', 'ltl-saas-portal' ) . '</span>';
        echo '<button type="button" class="ltlb-btn ltlb-btn--primary" style="padding: 8px 16px; font-size: 0.875rem;">' . esc_html__( 'Edit', 'ltl-saas-portal' ) . '</button>';
        echo '</div>';
        echo '</div>';

        // Form Section with Background Color
        echo '<div style="padding: 20px; border-radius: 8px; background: white; border: 2px solid rgba(0,0,0,0.05);">';
        echo '<h4 style="margin: 0 0 16px 0; color: var(--ltlb-color-form-text, #333);">' . esc_html__( 'Blog Settings', 'ltl-saas-portal' ) . '</h4>';

        echo '<div style="margin-bottom: 16px;">';
        echo '<label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.875rem; color: var(--ltlb-color-form-text, #333); text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html__( 'Language', 'ltl-saas-portal' ) . '</label>';
        echo '<select style="width: 100%; padding: 10px 14px; border: 2px solid rgba(0,0,0,0.1); border-radius: 6px; background: white; color: #1a1a1a; font-size: 1rem;" disabled>';
        echo '<option>Deutsch</option>';
        echo '</select>';
        echo '</div>';

        echo '<div style="margin-bottom: 16px;">';
        echo '<label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.875rem; color: var(--ltlb-color-form-text, #333); text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html__( 'RSS Feed URL', 'ltl-saas-portal' ) . '</label>';
        echo '<input type="url" placeholder="https://your-source.com/feed" style="width: 100%; padding: 10px 14px; border: 2px solid rgba(0,0,0,0.1); border-radius: 6px; background: white; color: #1a1a1a; font-size: 1rem;" disabled />';
        echo '</div>';

        echo '<button type="button" class="ltlb-btn ltlb-btn--primary" style="width: 100%;">' . esc_html__( 'Save Settings', 'ltl-saas-portal' ) . '</button>';
        echo '</div>';

        echo '</div>'; // Main preview container

        echo '</div>'; // .ltlb-preview-samples
        echo '</div>'; // .ltlb-color-customizer__preview

        echo '</div>'; // .ltlb-color-customizer

        // Output initial backend preview colors
        $saved_backend_colors = get_option( 'ltl_saas_custom_colors_backend', array() );
        $backend_defaults = array(
            'primary' => '#667eea',
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'form_bg' => '#f8f9fa',
        );
        $preview_colors = wp_parse_args( $saved_backend_colors, $backend_defaults );

        echo '<style>';
        echo '.ltlb-color-customizer__preview {';
        foreach ( $preview_colors as $key => $color ) {
            if ( is_string( $color ) && ! empty( $color ) ) {
                // Convert underscore to dash for CSS variable name
                $css_key = str_replace( '_', '-', $key );
                echo '--ltlb-color-' . esc_attr( $css_key ) . ': ' . esc_attr( $color ) . ';';
                if ( $key === 'primary' ) {
                    $hover = $this->adjust_color_brightness( $color, -10 );
                    echo '--ltlb-color-primary-hover: ' . esc_attr( $hover ) . ';';
                }
                if ( $key === 'form_bg' ) {
                    $text_color = $this->get_contrasting_color( $color );
                    echo '--ltlb-color-form-text: ' . esc_attr( $text_color ) . ';';
                }
            }
        }
        echo '}';
        echo '</style>';

        echo '</div>'; // .ltlb-section (Backend)

        // Add inline CSS for backend preview with SAVED VALUES
        echo '<style id="ltlb-backend-preview-colors">';
        echo '#ltlb-backend-preview {';

        // Merge with defaults
        $backend_defaults = array(
            'primary' => '#667eea',
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'form_bg' => '#f8f9fa',
        );
        $backend_preview_colors = wp_parse_args( $saved_colors, $backend_defaults );

        foreach ( $backend_preview_colors as $key => $color ) {
            if ( is_string( $color ) && ! empty( $color ) ) {
                // DIRECT VALUE FOR BACKGROUND, NOT CSS VARIABLE
                if ( $key === 'form_bg' ) {
                    echo 'background-color: ' . esc_attr( $color ) . ' !important;';
                    $text_color = $this->get_contrasting_color( $color );
                    echo '--ltlb-color-form-text: ' . esc_attr( $text_color ) . ';';
                } else {
                    echo '--ltlb-color-' . esc_attr( $key ) . ': ' . esc_attr( $color ) . ';';
                    if ( $key === 'primary' ) {
                        $hover = $this->adjust_color_brightness( $color, -10 );
                        echo '--ltlb-color-primary-hover: ' . esc_attr( $hover ) . ';';
                    }
                }
            }
        }
        echo '}';
        echo '</style>';

        // ===== FRONTEND COLORS SECTION =====
        echo '<div class="ltlb-section" style="margin-top: 40px;">';
        echo '<div class="ltlb-section__header">';
        echo '<h3 class="ltlb-section__title">🌐 ' . esc_html__( 'Frontend Colors (Customer Portal)', 'ltl-saas-portal' ) . '</h3>';
        echo '<p class="ltlb-section__description">';
        echo esc_html__( 'These colors apply to the customer-facing shortcode dashboard on the frontend.', 'ltl-saas-portal' );
        echo '</p>';
        echo '</div>';

        echo '<div class="ltlb-color-customizer">';
        echo '<div class="ltlb-color-customizer__controls">';

        $frontend_colors = array(
            'primary' => __( 'Primary Color', 'ltl-saas-portal' ),
            'success' => __( 'Success Color', 'ltl-saas-portal' ),
            'error' => __( 'Error Color', 'ltl-saas-portal' ),
            'warning' => __( 'Warning Color', 'ltl-saas-portal' ),
            'form_bg' => __( 'Form Background Color', 'ltl-saas-portal' ),
        );

        $saved_frontend_colors = get_option( 'ltl_saas_custom_colors_frontend', array() );

        foreach ( $frontend_colors as $key => $label ) {
            $default = $this->get_default_color( $key );
            $value = isset( $saved_frontend_colors[ $key ] ) && is_string( $saved_frontend_colors[ $key ] ) && ! empty( $saved_frontend_colors[ $key ] )
                ? $saved_frontend_colors[ $key ]
                : $default;
            // Ensure value starts with # (WordPress sanitize_hex_color should have done this)
            if ( $value && $value[0] !== '#' ) {
                $value = '#' . $value;
            }

            echo '<div class="ltlb-color-field">';
            echo '<label for="frontend_color_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
            echo '<div class="ltlb-color-field__input-group">';
            echo '<input type="color" id="frontend_color_' . esc_attr( $key ) . '" name="ltl_saas_custom_colors_frontend[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="ltlb-color-picker-frontend" data-color-key="' . esc_attr( $key ) . '" />';
            echo '<input type="text" value="' . esc_attr( $value ) . '" class="ltlb-color-hex" readonly />';
            echo '<button type="button" class="button ltlb-reset-color" data-default="' . esc_attr( $default ) . '" data-target="frontend_color_' . esc_attr( $key ) . '">';
            echo '↺ ' . esc_html__( 'Reset', 'ltl-saas-portal' );
            echo '</button>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>'; // .ltlb-color-customizer__controls

        // Frontend Live Preview
        echo '<div class="ltlb-color-customizer__preview">';
        echo '<h4>' . esc_html__( 'Frontend Portal Preview', 'ltl-saas-portal' ) . '</h4>';
        echo '<div class="ltlb-preview-samples">';

        // Frontend Dashboard Preview - ID on the element with background!
        echo '<div id="ltlb-frontend-preview" class="ltl-saas-dashboard-preview" style="padding: 32px; border-radius: 12px; background: var(--ltlb-color-form-bg-frontend, #f8f9fa); box-shadow: 0 8px 24px rgba(0,0,0,0.2); max-width: 100%;">';

        // Hero Header
        echo '<div style="text-align: center; margin-bottom: 32px;">';
        echo '<h2 style="margin: 0 0 12px 0; color: var(--ltlb-color-form-text-frontend, #333); font-size: 2rem; font-weight: 700;">' . esc_html__( 'Welcome to Your Dashboard', 'ltl-saas-portal' ) . '</h2>';
        echo '<p style="margin: 0; color: var(--ltlb-color-form-text-frontend, #333); opacity: 0.9; font-size: 1.125rem;">' . esc_html__( 'Manage your automated blog content', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';

        // Status Cards Grid
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">';

        echo '<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        echo '<span class="ltlb-badge ltlb-badge--success" style="margin-bottom: 8px;">' . esc_html__( 'Active', 'ltl-saas-portal' ) . '</span>';
        echo '<h4 style="margin: 8px 0 4px 0; color: var(--ltlb-color-form-text-frontend, #333);">' . esc_html__( 'Status', 'ltl-saas-portal' ) . '</h4>';
        echo '<p style="margin: 0; font-size: 0.875rem; color: var(--ltlb-color-form-text-frontend, #333); opacity: 0.7;">' . esc_html__( 'Your account is active', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';

        echo '<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        echo '<span class="ltlb-badge ltlb-badge--count" style="margin-bottom: 8px; background: #e3f2fd; color: #1976d2;">12</span>';
        echo '<h4 style="margin: 8px 0 4px 0; color: var(--ltlb-color-form-text-frontend, #333);">' . esc_html__( 'Posts', 'ltl-saas-portal' ) . '</h4>';
        echo '<p style="margin: 0; font-size: 0.875rem; color: var(--ltlb-color-form-text-frontend, #333); opacity: 0.7;">' . esc_html__( 'This month', 'ltl-saas-portal' ) . '</p>';
        echo '</div>';

        echo '</div>';

        // Main Card
        echo '<div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-bottom: 24px;">';
        echo '<div style="background: linear-gradient(135deg, var(--ltlb-color-primary-frontend, #667eea) 0%, var(--ltlb-color-primary-hover-frontend, #5568d3) 100%); padding: 20px; color: white;">';
        echo '<h3 style="margin: 0; color: white;">' . esc_html__( 'Your Settings', 'ltl-saas-portal' ) . '</h3>';
        echo '</div>';
        echo '<div style="padding: 24px;">';

        // Form Fields
        echo '<div style="margin-bottom: 16px;">';
        echo '<label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.875rem; color: var(--ltlb-color-form-text-frontend, #333); text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html__( 'Website URL', 'ltl-saas-portal' ) . '</label>';
        echo '<input type="text" value="https://your-website.com" style="width: 100%; padding: 12px 16px; border: 2px solid rgba(0,0,0,0.1); border-radius: 6px; background: white; color: #1a1a1a; font-size: 1rem;" disabled />';
        echo '</div>';

        echo '<div style="margin-bottom: 16px;">';
        echo '<label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.875rem; color: var(--ltlb-color-form-text-frontend, #333); text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html__( 'RSS Feed', 'ltl-saas-portal' ) . '</label>';
        echo '<input type="text" placeholder="https://source.com/feed" style="width: 100%; padding: 12px 16px; border: 2px solid rgba(0,0,0,0.1); border-radius: 6px; background: white; color: #1a1a1a; font-size: 1rem;" disabled />';
        echo '</div>';

        // Action Button
        echo '<button type="button" style="width: 100%; padding: 12px 24px; background: linear-gradient(135deg, var(--ltlb-color-primary-frontend, #667eea) 0%, var(--ltlb-color-primary-hover-frontend, #5568d3) 100%); color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' . esc_html__( 'Save Changes', 'ltl-saas-portal' ) . '</button>';

        echo '</div>';
        echo '</div>';

        echo '</div>'; // .ltl-saas-dashboard-preview

        echo '</div>'; // .ltlb-preview-samples
        echo '</div>'; // .ltlb-color-customizer__preview

        echo '</div>'; // .ltlb-color-customizer
        echo '</div>'; // .ltlb-section (Frontend)

        // Add inline CSS for frontend preview with defaults
        echo '<style id="ltlb-frontend-preview-colors">';
        echo '#ltlb-frontend-preview {';

        // Merge with defaults
        $frontend_defaults = array(
            'primary' => '#667eea',
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'form_bg' => '#f8f9fa',
        );
        $preview_colors = wp_parse_args( $saved_frontend_colors, $frontend_defaults );

        foreach ( $preview_colors as $key => $color ) {
            if ( is_string( $color ) && ! empty( $color ) ) {
                // DIRECT VALUE FOR BACKGROUND, NOT CSS VARIABLE
                if ( $key === 'form_bg' ) {
                    echo 'background-color: ' . esc_attr( $color ) . ' !important;';
                    $text_color = $this->get_contrasting_color( $color );
                    echo '--ltlb-color-form-text-frontend: ' . esc_attr( $text_color ) . ';';
                } else {
                    echo '--ltlb-color-' . esc_attr( $key ) . '-frontend: ' . esc_attr( $color ) . ';';
                    if ( $key === 'primary' ) {
                        $hover = $this->adjust_color_brightness( $color, -10 );
                        echo '--ltlb-color-primary-hover-frontend: ' . esc_attr( $hover ) . ';';
                    }
                }
            }
        }
        echo '}';
        echo '</style>';
    }
}
