<?php
// --- PLAN NORMALIZATION HELPER ---
// Canonical plan names: free, basic, pro, studio
// Backward-compat aliases: starter -> basic, agency -> studio
if (!function_exists('ltl_saas_normalize_plan')) {
    function ltl_saas_normalize_plan($plan) {
        $plan = is_string($plan) ? strtolower(trim($plan)) : '';
        if ($plan === '') {
            return 'free';
        }
        $aliases = [
            'starter' => 'basic',
            'agency' => 'studio',
        ];
        return $aliases[$plan] ?? $plan;
    }
}

// --- PLAN LIMIT HELPER ---
if (!function_exists('ltl_saas_plan_posts_limit')) {
    function ltl_saas_plan_posts_limit($plan) {
        // Plan names: free, basic, pro, studio (lowercase canonical names)
        // See: docs/product/pricing-plans.md
        $plan = ltl_saas_normalize_plan($plan);
        $map = [
            'free' => 10,
            'basic' => 30,      // Previously 'free' => 20
            'pro' => 120,       // Previously 'starter' => 80
            'studio' => 300,    // Previously 'pro' => 250
        ];
        // Default to 'free' if plan not found (safest limit)
        return $map[$plan] ?? $map['free'];
    }
}

// --- TENANT STATE HELPER ---
if (!function_exists('ltl_saas_get_tenant_state')) {
    function ltl_saas_get_tenant_state($user_id) {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'ltl_saas_settings';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $settings_table WHERE user_id = %d", $user_id), ARRAY_A);
        $plan_raw = isset($row['plan']) && $row['plan'] ? $row['plan'] : 'free';
        $plan = ltl_saas_normalize_plan($plan_raw);
        $is_active = isset($row['is_active']) ? (bool)$row['is_active'] : true;
        $posts_used_month = isset($row['posts_this_month']) ? (int)$row['posts_this_month'] : 0;
        $posts_period_start = isset($row['posts_period_start']) && $row['posts_period_start'] ? $row['posts_period_start'] : date('Y-m-01');
        $posts_limit_month = ltl_saas_plan_posts_limit($plan);
        $posts_remaining = max(0, $posts_limit_month - $posts_used_month);
        return [
            'user_id' => (int)$user_id,
            'plan' => $plan,
            'is_active' => $is_active,
            'posts_used_month' => $posts_used_month,        // Issue #8: Renamed from posts_this_month for clarity
            'posts_limit_month' => $posts_limit_month,      // Issue #8: Explicit limit per plan
            'posts_remaining' => $posts_remaining,          // Issue #8: Calculated remaining quota
            'posts_period_start' => $posts_period_start,
        ];
    }
}
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-crypto.php';

final class LTL_SAAS_Portal {

    private static $instance = null;

    /** @var LTL_SAAS_Portal_Admin */
    public $admin;

    /** @var LTL_SAAS_Portal_REST */
    public $rest;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/Admin/class-admin.php';
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/REST/class-rest.php';

        $this->admin = new LTL_SAAS_Portal_Admin();
        $this->rest  = new LTL_SAAS_Portal_REST();

        add_shortcode( 'ltl_saas_dashboard', array( $this, 'shortcode_dashboard' ) );
        add_shortcode( 'ltl_saas_pricing', array( $this, 'shortcode_pricing' ) );

        // Output custom colors on frontend (priority 100 to ensure after stylesheets)
        add_action( 'wp_head', array( $this, 'output_custom_colors_frontend' ), 100 );
    }

    /**
     * Enqueue frontend assets for shortcodes
     */
    private function enqueue_frontend_assets() {
        static $enqueued = false;
        if ( $enqueued ) {
            return;
        }

        wp_enqueue_style(
            'ltlb-frontend',
            LTL_SAAS_PORTAL_PLUGIN_URL . 'assets/frontend.css',
            array(),
            LTL_SAAS_PORTAL_VERSION
        );

        $enqueued = true;
    }

    /**
     * Output custom colors as inline CSS on frontend (Frontend only)
     */
    public function output_custom_colors_frontend() {
        $custom_colors = get_option( 'ltl_saas_custom_colors_frontend', array() );

        // Ensure we always output colors (use defaults if not set)
        $defaults = array(
            'primary' => '#667eea',
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'form_bg' => '#f8f9fa',
        );

        $colors = wp_parse_args( $custom_colors, $defaults );

        echo '<!-- LTL Custom Colors Frontend -->';
        echo '<style id="ltlb-custom-colors-frontend">';
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

            // Auto-generate hover variant for primary
            if ( $key === 'primary' ) {
                $hover = $this->adjust_color_brightness( $color, -10 );
                echo '--ltlb-color-primary-hover: ' . esc_attr( $hover ) . ';';
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
     * Adjust color brightness (helper for auto-generating variants)
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
     * Activation: create DB tables for connections/settings/runs.
     */
    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $connections = $wpdb->prefix . 'ltl_saas_connections';
        $settings    = $wpdb->prefix . 'ltl_saas_settings';
        $runs        = $wpdb->prefix . 'ltl_saas_runs';

        // NOTE: We intentionally store sensitive fields encrypted (see LTL_SAAS_Portal_Crypto).
        $sql = [];

        $sql[] = "CREATE TABLE $connections (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            wp_url TEXT NOT NULL,
            wp_user VARCHAR(191) NOT NULL,
            wp_app_password_enc LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $settings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            rss_url TEXT NULL,
            language VARCHAR(20) NULL,
            tone VARCHAR(50) NULL,
            frequency VARCHAR(20) NULL,
            publish_mode VARCHAR(20) NULL,
            plan VARCHAR(32) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            posts_this_month INT NOT NULL DEFAULT 0,
            posts_period_start DATE NULL,
            json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        // Versioning for future upgrades
        update_option('ltl_saas_db_version', LTL_SAAS_PORTAL_VERSION . '-sprint04');

        // Backfill: set posts_period_start to current month for NULLs
        $wpdb->query($wpdb->prepare(
            "UPDATE $settings SET posts_period_start = %s WHERE posts_period_start IS NULL",
            date('Y-m-01')
        ));

        $sql[] = "CREATE TABLE $runs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) UNSIGNED NOT NULL,
            execution_id VARCHAR(255) NULL,
            status VARCHAR(32) NOT NULL,
            started_at DATETIME NULL,
            finished_at DATETIME NULL,
            posts_created INT NULL,
            error_message LONGTEXT NULL,
            raw_payload LONGTEXT NULL,
            attempts TINYINT DEFAULT 1,
            last_http_status SMALLINT DEFAULT NULL,
            retry_backoff_ms INT DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY execution_id (execution_id),
            KEY tenant_id (tenant_id)
        ) $charset_collate;";

        foreach ( $sql as $statement ) {
            dbDelta( $statement );
        }
    }

    public static function deactivate() {
        // We do NOT delete tables on deactivate (data safety).
    }

    /**
     * Get translations for frontend dashboard
     */
    private function get_dashboard_translations($lang = 'en') {
        $translations = array(
            'en' => array(
                // Login
                'login_required' => 'Please log in to access your dashboard.',
                'login_button' => 'Login',

                // Dashboard Header
                'dashboard_title' => 'LTL AutoBlog Cloud',
                'dashboard_subtitle' => 'Your AI-Powered Content Automation',

                // Progress Card
                'progress_title' => 'Your Setup Progress',
                'help_guide' => 'Help & Onboarding Guide',

                // Step 1
                'step1_title' => 'Step 1: Connect WordPress',
                'step1_connected' => 'Connected ✓',
                'step1_pending' => 'Not configured yet',
                'btn_connect' => 'Connect Now',
                'btn_edit' => 'Edit',

                // Step 2
                'step2_title' => 'Step 2: RSS Feed & Settings',
                'step2_pending' => 'Not configured yet',
                'btn_configure' => 'Configure Now',

                // Step 3
                'step3_title' => 'Step 3: Active Plan',
                'step3_required' => 'Subscription required',
                'btn_activate' => 'Activate Subscription',

                // Step 4
                'step4_title' => 'Step 4: First Run',
                'step4_success' => 'Last run: %s ago (%d Posts)',
                'step4_status' => 'Last run: %s',
                'step4_waiting' => 'Waiting for first automatic run...',

                // Form Labels
                'label_wp_url' => 'WordPress URL',
                'tooltip_wp_url' => 'Your website domain',
                'help_wp_url' => '✓ Must be https:// | Example: https://myblog.com (without /wp-admin)',
                'label_wp_user' => 'Username/Email',
                'tooltip_wp_user' => 'Your WP admin user',
                'help_wp_user' => 'The user who created the app passwords',
                'label_wp_password' => 'Application Password',
                'tooltip_wp_password' => 'Generated password from WP',
                'help_wp_password' => 'Generate at: WP-Admin → Users → Profile → Application Passwords',
                'label_rss_url' => 'RSS Source',
                'tooltip_rss_url' => 'Your RSS URL',
                'help_rss_url' => '✓ Must be https:// | Examples: blog.com/feed, news-portal.com/rss',
                'label_language' => 'Language',
                'help_language' => 'The language in which posts will be written',
                'label_tone' => 'Tone',
                'help_tone' => 'e.g. professional or funny',
                'label_frequency' => 'Frequency',
                'help_frequency' => 'How often should posts be published?',
                'label_publish_mode' => 'Publish Mode',
                'help_publish_mode' => 'draft = review first | publish = automatically live',
                'week' => 'Week',

                // Buttons
                'btn_save' => 'Save Connection',
                'btn_test_connection' => 'Test Connection',
                'btn_test_rss' => 'Test RSS',
                'btn_save_settings' => 'Save Settings',
                'btn_show_runs' => 'Show Runs',
                'btn_hide_runs' => 'Hide Runs',

                // Runs Table
                'runs_title' => 'Recent Runs',
                'runs_empty' => 'No runs yet.',
                'table_date' => 'Date',
                'table_status' => 'Status',
                'table_posts' => 'Posts',
                'table_error' => 'Error',
                'table_payload' => 'Payload',
                'show_error' => 'Show',
                'show_payload' => 'Show',

                // Messages
                'select_placeholder' => 'Please select',
                'connection_saved' => 'Connection saved.',
                'settings_saved' => 'Saved ✓',
                'testing' => 'Testing connection...',
                'testing_rss' => 'Testing RSS...',
                'connection_success' => 'Connection successful! User:',
                'rss_title' => 'Title:',

                // Locked State
                'locked_title' => 'Subscription Required',
                'locked_message' => 'Your access is currently inactive. Please subscribe to continue.',
                'locked_button' => 'View Pricing',

                // Errors
                'error_inactive' => 'Account inactive. Settings cannot be saved.',
                'error_url' => 'Please enter a valid WordPress URL.',
                'error_user' => 'Please enter a valid username or email.',
                'error_password' => 'Please enter an Application Password.',
                'error_rss' => 'Please enter a valid RSS URL.',
                'error_language' => 'Invalid language.',
                'error_tone' => 'Invalid tone.',
                'error_frequency' => 'Invalid frequency.',
                'error_publish' => 'Invalid publish mode.',
                'error_all_fields' => 'All fields required (URL, User, Password)',
                'error_rss_required' => 'RSS URL required',
                'error_prefix' => 'Error:',
                'error_unknown' => 'Unknown error',
                'error_network' => 'Network error:',
            ),
            'de' => array(
                // Login
                'login_required' => 'Bitte einloggen um auf dein Dashboard zuzugreifen.',
                'login_button' => 'Einloggen',

                // Dashboard Header
                'dashboard_title' => 'LTL AutoBlog Cloud',
                'dashboard_subtitle' => 'Deine KI-gestützte Content-Automation',

                // Progress Card
                'progress_title' => 'Dein Setup-Fortschritt',
                'help_guide' => 'Hilfe & Onboarding-Guide',

                // Step 1
                'step1_title' => 'Schritt 1: WordPress verbinden',
                'step1_connected' => 'Verbunden ✓',
                'step1_pending' => 'Noch nicht konfiguriert',
                'btn_connect' => 'Jetzt verbinden',
                'btn_edit' => 'Bearbeiten',

                // Step 2
                'step2_title' => 'Schritt 2: RSS-Feed & Einstellungen',
                'step2_pending' => 'Noch nicht konfiguriert',
                'btn_configure' => 'Jetzt konfigurieren',

                // Step 3
                'step3_title' => 'Schritt 3: Plan aktiv',
                'step3_required' => 'Abo erforderlich',
                'btn_activate' => 'Abo aktivieren',

                // Step 4
                'step4_title' => 'Schritt 4: Erster Durchlauf',
                'step4_success' => 'Letzter Run: vor %s (%d Posts)',
                'step4_status' => 'Letzter Run: %s',
                'step4_waiting' => 'Warte auf ersten automatischen Run...',

                // Form Labels
                'label_wp_url' => 'WordPress URL',
                'tooltip_wp_url' => 'Deine Website Domain',
                'help_wp_url' => '✓ Muss https:// sein | Beispiel: https://meinblog.de (ohne /wp-admin)',
                'label_wp_user' => 'Benutzername/E-Mail',
                'tooltip_wp_user' => 'Dein WP Admin Benutzer',
                'help_wp_user' => 'Der Benutzer, der die App-Passwörter erstellt hat',
                'label_wp_password' => 'Application Password',
                'tooltip_wp_password' => 'Generiertes Passwort aus WP',
                'help_wp_password' => 'Generieren unter: WP-Admin → Nutzer → Profil → Anwendungspasswörter',
                'label_rss_url' => 'RSS-Quelle',
                'tooltip_rss_url' => 'Deine RSS URL',
                'help_rss_url' => '✓ Muss https:// sein | Beispiele: blog.de/feed, news-portal.com/rss',
                'label_language' => 'Sprache',
                'help_language' => 'Die Sprache, in der Posts geschrieben werden',
                'label_tone' => 'Ton',
                'help_tone' => 'z.B. professional oder funny',
                'label_frequency' => 'Frequenz',
                'help_frequency' => 'Wie oft sollen Posts veröffentlicht werden?',
                'label_publish_mode' => 'Veröffentlichung',
                'help_publish_mode' => 'draft = Vorlage prüfen | publish = automatisch live',
                'week' => 'Woche',

                // Buttons
                'btn_save' => 'Verbindung speichern',
                'btn_test_connection' => 'Verbindung testen',
                'btn_test_rss' => 'RSS testen',
                'btn_save_settings' => 'Einstellungen speichern',
                'btn_show_runs' => 'Runs anzeigen',
                'btn_hide_runs' => 'Runs verstecken',

                // Runs Table
                'runs_title' => 'Letzte Runs',
                'runs_empty' => 'Noch keine Runs.',
                'table_date' => 'Datum',
                'table_status' => 'Status',
                'table_posts' => 'Posts',
                'table_error' => 'Fehler',
                'table_payload' => 'Payload',
                'show_error' => 'Anzeigen',
                'show_payload' => 'Anzeigen',

                // Messages
                'select_placeholder' => 'Bitte wählen',
                'connection_saved' => 'Verbindung gespeichert.',
                'settings_saved' => 'Gespeichert ✓',
                'testing' => 'Teste Verbindung...',
                'testing_rss' => 'Teste RSS...',
                'connection_success' => 'Verbindung erfolgreich! User:',
                'rss_title' => 'Titel:',

                // Locked State
                'locked_title' => 'Abo erforderlich',
                'locked_message' => 'Dein Zugang ist aktuell inaktiv. Bitte buche ein Abo, um fortzufahren.',
                'locked_button' => 'Zu den Preisen',

                // Errors
                'error_inactive' => 'Account inaktiv. Einstellungen können nicht gespeichert werden.',
                'error_url' => 'Bitte eine gültige WordPress-URL angeben.',
                'error_user' => 'Bitte einen gültigen Benutzernamen oder E-Mail angeben.',
                'error_password' => 'Bitte ein Application Password angeben.',
                'error_rss' => 'Bitte eine gültige RSS-URL angeben.',
                'error_language' => 'Ungültige Sprache.',
                'error_tone' => 'Ungültiger Ton.',
                'error_frequency' => 'Ungültige Frequenz.',
                'error_publish' => 'Ungültiger Veröffentlichungsmodus.',
                'error_all_fields' => 'Alle Felder erforderlich (URL, User, Password)',
                'error_rss_required' => 'RSS-URL erforderlich',
                'error_prefix' => 'Fehler:',
                'error_unknown' => 'Unbekannter Fehler',
                'error_network' => 'Netzwerkfehler:',
            ),
        );
        return isset($translations[$lang]) ? $translations[$lang] : $translations['en'];
    }

    public function shortcode_dashboard( $atts = [] ) {
        // Enqueue frontend assets
        $this->enqueue_frontend_assets();

        // Handle frontend language switch (stored in cookie)
        $current_lang = isset($_COOKIE['ltl_frontend_lang']) ? sanitize_text_field($_COOKIE['ltl_frontend_lang']) : 'en';
        if (!in_array($current_lang, array('en', 'de'))) {
            $current_lang = 'en';
        }

        // Get translations
        $t = $this->get_dashboard_translations($current_lang);

        if ( ! is_user_logged_in() ) {
            return '
            <div class="ltlb-login-required">
                <div class="ltlb-login-required__icon">🔐</div>
                <p class="ltlb-login-required__message">' . esc_html($t['login_required']) . '</p>
                <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="ltlb-btn ltlb-btn--primary">' . esc_html($t['login_button']) . '</a>
            </div>';
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'ltl_saas_connections';
        $settings_table = $wpdb->prefix . 'ltl_saas_settings';
        $error = '';
        $success = '';
        $settings_success = '';

        // --- SETTINGS: Handle settings form submit ---
        $languages = ['de','en','es','fr','it','pt','nl','pl'];
        $tones = ['professional','casual','nerdy','funny','serious'];
        $frequencies = ['daily','3x_week','weekly'];
        $publish_modes = ['draft','publish'];

        if (isset($_POST['ltl_saas_save_settings']) && wp_verify_nonce($_POST['ltl_saas_settings_nonce'], 'ltl_saas_save_settings')) {
            // Access control: prevent saving when user is inactive
            $existing_settings = $wpdb->get_row($wpdb->prepare("SELECT is_active FROM $settings_table WHERE user_id = %d", $user_id));
            if ($existing_settings && isset($existing_settings->is_active) && intval($existing_settings->is_active) === 0) {
                $error = $t['error_inactive'];
            } else {
                $rss_url = esc_url_raw(trim($_POST['rss_url'] ?? ''));
                $language = $_POST['language'] ?? '';
                $tone = $_POST['tone'] ?? '';
                $frequency = $_POST['frequency'] ?? '';
                $publish_mode = $_POST['publish_mode'] ?? '';

                if ($rss_url && !filter_var($rss_url, FILTER_VALIDATE_URL)) {
                    $error = $t['error_rss'];
                } elseif ($language && !in_array($language, $languages, true)) {
                    $error = $t['error_language'];
                } elseif ($tone && !in_array($tone, $tones, true)) {
                    $error = $t['error_tone'];
                } elseif ($frequency && !in_array($frequency, $frequencies, true)) {
                    $error = $t['error_frequency'];
                } elseif ($publish_mode && !in_array($publish_mode, $publish_modes, true)) {
                    $error = $t['error_publish'];
                } else {
                    $row = [
                        'user_id' => $user_id,
                        'rss_url' => $rss_url,
                        'language' => $language,
                        'tone' => $tone,
                        'frequency' => $frequency,
                        'publish_mode' => $publish_mode,
                        'updated_at' => current_time('mysql'),
                    ];
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $settings_table WHERE user_id = %d", $user_id));
                    if ($exists) {
                        $wpdb->update($settings_table, $row, ['user_id' => $user_id]);
                    } else {
                        $row['created_at'] = current_time('mysql');
                        $row['is_active'] = 1; // default active
                        $row['plan'] = 'free';
                        $wpdb->insert($settings_table, $row);
                    }
                    $settings_success = $t['settings_saved'];
                }
            }
        }

        // --- CONNECTION: Handle connection form submit ---
        if ( isset($_POST['ltl_saas_save_connection']) && wp_verify_nonce($_POST['ltl_saas_nonce'], 'ltl_saas_save_connection') ) {
            // Access control: prevent saving connection when user is inactive
            $existing_settings = $wpdb->get_row($wpdb->prepare("SELECT is_active FROM $settings_table WHERE user_id = %d", $user_id));
            if ($existing_settings && isset($existing_settings->is_active) && intval($existing_settings->is_active) === 0) {
                $error = $t['error_inactive'];
            } else {
                $wp_url = esc_url_raw(trim($_POST['wp_url'] ?? ''));
                $wp_user = sanitize_user(trim($_POST['wp_user'] ?? ''));
                $wp_app_password = trim($_POST['wp_app_password'] ?? '');

                if ( empty($wp_url) || ! filter_var($wp_url, FILTER_VALIDATE_URL) ) {
                    $error = $t['error_url'];
                } elseif ( empty($wp_user) ) {
                    $error = $t['error_user'];
                } elseif ( empty($wp_app_password) ) {
                    $error = 'Bitte ein Application Password angeben.';
                } else {
                    $enc = LTL_SAAS_Portal_Crypto::encrypt($wp_app_password);
                    $row = [
                        'user_id' => $user_id,
                        'wp_url' => $wp_url,
                        'wp_user' => $wp_user,
                        'wp_app_password_enc' => $enc,
                        'updated_at' => current_time('mysql'),
                    ];
                    // Insert or update
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE user_id = %d", $user_id));
                    if ($exists) {
                        $wpdb->update($table, $row, ['user_id' => $user_id]);
                    } else {
                        $row['created_at'] = current_time('mysql');
                        $wpdb->insert($table, $row);
                    }
                    $success = 'Verbindung gespeichert.';
                }
            }
        }

        // Load current values
        $conn = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
        $wp_url = $conn->wp_url ?? '';
        $wp_user = $conn->wp_user ?? '';

        $settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM $settings_table WHERE user_id = %d", $user_id));

        $rss_url = $settings->rss_url ?? '';
        $language = $settings->language ?? '';
        $tone = $settings->tone ?? '';
        $frequency = $settings->frequency ?? '';
        $publish_mode = $settings->publish_mode ?? '';
        $is_active = isset($settings->is_active) ? (int)$settings->is_active : 1;

        $runs_table = $wpdb->prefix . 'ltl_saas_runs';
        $last_runs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $runs_table WHERE tenant_id = %d ORDER BY created_at DESC LIMIT 10", $user_id));

        // Get tenant state for progress card
        $tenant_state = ltl_saas_get_tenant_state($user_id);
        $plan_display = ucfirst($tenant_state['plan']);
        $plan_is_active = $tenant_state['is_active'];
        $posts_used = $tenant_state['posts_used_month'];
        $posts_limit = $tenant_state['posts_limit_month'];

        // Last run status
        $last_run = $wpdb->get_row($wpdb->prepare(
            "SELECT status, finished_at, posts_created FROM $runs_table WHERE tenant_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        ), ARRAY_A);
        $has_run = !empty($last_run);
        $run_ok = $has_run && $last_run['status'] === 'success';

        ob_start();

        // Locked state for inactive users
        if (!$is_active) {
            $pricing_url = get_option('ltl_saas_pricing_url', '#');
            ?>
            <div class="ltlb-dashboard ltlb-locked">
                <div class="ltlb-locked-content">
                    <div class="ltlb-locked-icon">🔒</div>
                    <h2><?php echo esc_html($t['locked_title']); ?></h2>
                    <p><?php echo esc_html($t['locked_message']); ?></p>
                    <a href="<?php echo esc_url(is_string($pricing_url) ? $pricing_url : ''); ?>" class="ltlb-btn ltlb-btn-primary ltlb-btn-lg">
                        <?php echo esc_html($t['locked_button']); ?>
                    </a>
                </div>
            </div>
            <?php
        } else {
        ?>
        <div class="ltlb-dashboard">
            <!-- Dashboard Header with Language Switcher -->
            <div class="ltlb-dashboard-header">
                <div class="ltlb-header-content">
                    <h1 class="ltlb-dashboard-title">
                        <span class="ltlb-title-icon">🚀</span>
                        <?php echo esc_html($t['dashboard_title']); ?>
                    </h1>
                    <p class="ltlb-dashboard-subtitle"><?php echo esc_html($t['dashboard_subtitle']); ?></p>
                </div>
                <div class="ltlb-header-actions">
                    <!-- Frontend Language Switcher -->
                    <div class="ltlb-lang-switcher">
                        <button type="button" class="ltlb-lang-btn <?php echo $current_lang === 'en' ? 'active' : ''; ?>" data-lang="en">🇺🇸 EN</button>
                        <button type="button" class="ltlb-lang-btn <?php echo $current_lang === 'de' ? 'active' : ''; ?>" data-lang="de">🇩🇪 DE</button>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <?php if ($error): ?>
            <div class="ltlb-alert ltlb-alert-error">
                <span class="ltlb-alert-icon">❌</span>
                <span class="ltlb-alert-message"><?php echo esc_html($error); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="ltlb-alert ltlb-alert-success">
                <span class="ltlb-alert-icon">✅</span>
                <span class="ltlb-alert-message"><?php echo esc_html($success); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($settings_success): ?>
            <div class="ltlb-alert ltlb-alert-success">
                <span class="ltlb-alert-icon">✅</span>
                <span class="ltlb-alert-message"><?php echo esc_html($settings_success); ?></span>
            </div>
            <?php endif; ?>

            <!-- Setup Progress Card -->
            <div class="ltlb-progress-card">
                <div class="ltlb-progress-header">
                    <h2 class="ltlb-progress-title">
                        <span class="ltlb-progress-icon">📋</span>
                        <?php echo esc_html($t['progress_title']); ?>
                    </h2>
                    <a href="<?php echo esc_url(plugins_url('../../../docs/product/onboarding-detailed.md', __FILE__)); ?>" target="_blank" class="ltlb-help-link">
                        📖 <?php echo esc_html($t['help_guide']); ?>
                    </a>
                </div>

                <!-- Step 1: WordPress Connection -->
                <div class="ltlb-step <?php echo !empty($wp_url) ? 'completed' : 'pending'; ?>">
                    <div class="ltlb-step-icon">
                        <?php echo !empty($wp_url) ? '✅' : '⚠️'; ?>
                    </div>
                    <div class="ltlb-step-content">
                        <strong class="ltlb-step-title"><?php echo esc_html($t['step1_title']); ?></strong>
                        <p class="ltlb-step-status">
                            <?php echo !empty($wp_url) ? esc_html($t['step1_connected']) : esc_html($t['step1_pending']); ?>
                        </p>
                    </div>
                    <div class="ltlb-step-action">
                        <a href="#wp-connection" class="ltlb-btn <?php echo !empty($wp_url) ? 'ltlb-btn-secondary' : 'ltlb-btn-primary'; ?>">
                            <?php echo !empty($wp_url) ? esc_html($t['btn_edit']) : esc_html($t['btn_connect']); ?>
                        </a>
                    </div>
                </div>

                <!-- Step 2: RSS + Settings -->
                <div class="ltlb-step <?php echo !empty($rss_url) ? 'completed' : 'pending'; ?>">
                    <div class="ltlb-step-icon">
                        <?php echo !empty($rss_url) ? '✅' : '⚠️'; ?>
                    </div>
                    <div class="ltlb-step-content">
                        <strong class="ltlb-step-title"><?php echo esc_html($t['step2_title']); ?></strong>
                        <p class="ltlb-step-status">
                            <?php
                            if (!empty($rss_url)) {
                                echo 'RSS: ' . esc_html(substr($rss_url, 0, 35)) . '...';
                            } else {
                                echo esc_html($t['step2_pending']);
                            }
                            ?>
                        </p>
                    </div>
                    <div class="ltlb-step-action">
                        <a href="#settings" class="ltlb-btn <?php echo !empty($rss_url) ? 'ltlb-btn-secondary' : 'ltlb-btn-primary'; ?>">
                            <?php echo !empty($rss_url) ? esc_html($t['btn_edit']) : esc_html($t['btn_configure']); ?>
                        </a>
                    </div>
                </div>

                <!-- Step 3: Plan Status -->
                <div class="ltlb-step <?php echo $plan_is_active ? 'completed' : 'pending'; ?>">
                    <div class="ltlb-step-icon">
                        <?php echo $plan_is_active ? '✅' : '⚠️'; ?>
                    </div>
                    <div class="ltlb-step-content">
                        <strong class="ltlb-step-title"><?php echo esc_html($t['step3_title']); ?></strong>
                        <p class="ltlb-step-status">
                            <?php
                            if ($plan_is_active) {
                                echo '<span class="ltlb-text-success">Plan: ' . esc_html($plan_display) . ' (' . intval($posts_used) . '/' . intval($posts_limit) . ' Posts)</span>';
                            } else {
                                echo '<span class="ltlb-text-warning">' . esc_html($t['step3_required']) . '</span>';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="ltlb-step-action">
                        <?php if (!$plan_is_active): ?>
                            <?php $checkout_url_basic = get_option('ltl_saas_gumroad_checkout_url_basic', 'https://lazytechlab.de'); ?>
                            <a href="<?php echo esc_url(is_string($checkout_url_basic) ? $checkout_url_basic : ''); ?>" class="ltlb-btn ltlb-btn-primary">
                                <?php echo esc_html($t['btn_activate']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Step 4: Last Run -->
                <div class="ltlb-step <?php echo $run_ok ? 'completed' : ($has_run ? 'warning' : 'pending'); ?>">
                    <div class="ltlb-step-icon">
                        <?php echo $run_ok ? '✅' : ($has_run ? '⚠️' : '⏳'); ?>
                    </div>
                    <div class="ltlb-step-content">
                        <strong class="ltlb-step-title"><?php echo esc_html($t['step4_title']); ?></strong>
                        <p class="ltlb-step-status">
                            <?php
                            if ($run_ok) {
                                $time_ago = human_time_diff(strtotime($last_run['finished_at']), current_time('timestamp'));
                                echo '<span class="ltlb-text-success">✓ ' . sprintf($t['step4_success'], esc_html($time_ago), (int)$last_run['posts_created']) . '</span>';
                            } elseif ($has_run) {
                                echo '<span class="ltlb-text-warning">' . sprintf($t['step4_status'], esc_html($last_run['status'])) . '</span>';
                            } else {
                                echo esc_html($t['step4_waiting']);
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- WordPress Connection Form -->
            <div class="ltlb-form-section" id="wp-connection">
                <div class="ltlb-section-header">
                    <h3 class="ltlb-section-title">
                        <span class="ltlb-section-icon">🔗</span>
                        <?php echo esc_html($t['step1_title']); ?>
                    </h3>
                </div>
                <form method="post" class="ltlb-form">
                    <?php wp_nonce_field('ltl_saas_save_connection', 'ltl_saas_nonce'); ?>

                    <div class="ltlb-form-group">
                        <label for="wp_url" class="ltlb-label">
                            🔗 <?php echo esc_html($t['label_wp_url']); ?>
                            <span class="ltlb-tooltip" title="<?php echo esc_attr($t['tooltip_wp_url']); ?>">ℹ️</span>
                        </label>
                        <input type="url" id="wp_url" name="wp_url" class="ltlb-input"
                               value="<?php echo esc_attr($wp_url); ?>"
                               placeholder="https://meinblog.de" required>
                        <small class="ltlb-help-text"><?php echo esc_html($t['help_wp_url']); ?></small>
                    </div>

                    <div class="ltlb-form-group">
                        <label for="wp_user" class="ltlb-label">
                            👤 <?php echo esc_html($t['label_wp_user']); ?>
                            <span class="ltlb-tooltip" title="<?php echo esc_attr($t['tooltip_wp_user']); ?>">ℹ️</span>
                        </label>
                        <input type="text" id="wp_user" name="wp_user" class="ltlb-input"
                               value="<?php echo esc_attr($wp_user); ?>"
                               placeholder="admin@meinblog.de" required>
                        <small class="ltlb-help-text"><?php echo esc_html($t['help_wp_user']); ?></small>
                    </div>

                    <div class="ltlb-form-group">
                        <label for="wp_app_password" class="ltlb-label">
                            🔐 <?php echo esc_html($t['label_wp_password']); ?>
                            <span class="ltlb-tooltip" title="<?php echo esc_attr($t['tooltip_wp_password']); ?>">ℹ️</span>
                        </label>
                        <input type="password" id="wp_app_password" name="wp_app_password" class="ltlb-input"
                               placeholder="xxxx xxxx xxxx xxxx" autocomplete="new-password">
                        <small class="ltlb-help-text"><?php echo esc_html($t['help_wp_password']); ?></small>
                    </div>

                    <div class="ltlb-form-actions">
                        <button type="submit" name="ltl_saas_save_connection" class="ltlb-btn ltlb-btn-primary">
                            <?php echo esc_html($t['btn_save']); ?>
                        </button>
                        <button type="button" id="ltl-saas-test-connection" class="ltlb-btn ltlb-btn-secondary">
                            🧪 <?php echo esc_html($t['btn_test_connection']); ?>
                        </button>
                    </div>
                </form>
                <div id="ltl-saas-test-result" class="ltlb-test-result"></div>
            </div>

            <!-- RSS & Settings Form -->
            <div class="ltlb-form-section" id="settings">
                <div class="ltlb-section-header">
                    <h3 class="ltlb-section-title">
                        <span class="ltlb-section-icon">📰</span>
                        <?php echo esc_html($t['step2_title']); ?>
                    </h3>
                </div>
                <form method="post" class="ltlb-form">
                    <?php wp_nonce_field('ltl_saas_save_settings', 'ltl_saas_settings_nonce'); ?>

                    <div class="ltlb-form-group">
                        <label for="rss_url" class="ltlb-label">
                            📰 <?php echo esc_html($t['label_rss_url']); ?>
                            <span class="ltlb-tooltip" title="<?php echo esc_attr($t['tooltip_rss_url']); ?>">ℹ️</span>
                        </label>
                        <div class="ltlb-input-with-action">
                            <input type="url" id="rss_url" name="rss_url" class="ltlb-input"
                                   value="<?php echo esc_attr($rss_url); ?>"
                                   placeholder="https://beispiel.de/feed">
                            <button type="button" id="ltl-saas-test-rss" class="ltlb-btn ltlb-btn-icon" title="<?php echo esc_attr($t['btn_test_rss']); ?>">
                                🧪
                            </button>
                        </div>
                        <small class="ltlb-help-text"><?php echo esc_html($t['help_rss_url']); ?></small>
                        <div id="ltl-saas-rss-result" class="ltlb-test-result"></div>
                    </div>

                    <div class="ltlb-form-row">
                        <div class="ltlb-form-group ltlb-col-half">
                            <label for="language" class="ltlb-label">🌍 <?php echo esc_html($t['label_language']); ?></label>
                            <select id="language" name="language" class="ltlb-select">
                                <option value=""><?php echo esc_html($t['select_placeholder']); ?></option>
                                <?php foreach($languages as $l): ?>
                                    <option value="<?php echo esc_attr($l); ?>" <?php selected($language, $l); ?>><?php echo strtoupper($l); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="ltlb-help-text"><?php echo esc_html($t['help_language']); ?></small>
                        </div>

                        <div class="ltlb-form-group ltlb-col-half">
                            <label for="tone" class="ltlb-label">✨ <?php echo esc_html($t['label_tone']); ?></label>
                            <select id="tone" name="tone" class="ltlb-select">
                                <option value=""><?php echo esc_html($t['select_placeholder']); ?></option>
                                <?php foreach($tones as $tone_opt): ?>
                                    <option value="<?php echo esc_attr($tone_opt); ?>" <?php selected($tone, $tone_opt); ?>><?php echo ucfirst($tone_opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="ltlb-help-text"><?php echo esc_html($t['help_tone']); ?></small>
                        </div>
                    </div>

                    <div class="ltlb-form-row">
                        <div class="ltlb-form-group ltlb-col-half">
                            <label for="frequency" class="ltlb-label">📅 <?php echo esc_html($t['label_frequency']); ?></label>
                            <select id="frequency" name="frequency" class="ltlb-select">
                                <option value=""><?php echo esc_html($t['select_placeholder']); ?></option>
                                <?php foreach($frequencies as $f): ?>
                                    <option value="<?php echo esc_attr($f); ?>" <?php selected($frequency, $f); ?>>
                                        <?php echo $f === '3x_week' ? '3x/' . $t['week'] : ucfirst($f); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="ltlb-help-text"><?php echo esc_html($t['help_frequency']); ?></small>
                        </div>

                        <div class="ltlb-form-group ltlb-col-half">
                            <label for="publish_mode" class="ltlb-label">📝 <?php echo esc_html($t['label_publish_mode']); ?></label>
                            <select id="publish_mode" name="publish_mode" class="ltlb-select">
                                <option value=""><?php echo esc_html($t['select_placeholder']); ?></option>
                                <?php foreach($publish_modes as $p): ?>
                                    <option value="<?php echo esc_attr($p); ?>" <?php selected($publish_mode, $p); ?>><?php echo ucfirst($p); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="ltlb-help-text"><?php echo esc_html($t['help_publish_mode']); ?></small>
                        </div>
                    </div>

                    <div class="ltlb-form-actions">
                        <button type="submit" name="ltl_saas_save_settings" class="ltlb-btn ltlb-btn-primary">
                            <?php echo esc_html($t['btn_save_settings']); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Run History Section -->
            <div class="ltlb-runs-section">
                <div class="ltlb-section-header">
                    <h3 class="ltlb-section-title">
                        <span class="ltlb-section-icon">📊</span>
                        <?php echo esc_html($t['runs_title']); ?>
                    </h3>
                    <button type="button" class="ltlb-btn ltlb-btn-sm ltlb-btn-secondary" id="ltlb-toggle-runs">
                        <?php echo esc_html($t['btn_show_runs']); ?>
                    </button>
                </div>

                <div class="ltlb-runs-content" id="ltlb-runs-list">
                    <?php if (empty($last_runs)): ?>
                        <div class="ltlb-empty-state">
                            <div class="ltlb-empty-icon">📭</div>
                            <p><?php echo esc_html($t['runs_empty']); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="ltlb-table-wrapper">
                            <table class="ltlb-table">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html($t['table_date']); ?></th>
                                        <th><?php echo esc_html($t['table_status']); ?></th>
                                        <th><?php echo esc_html($t['table_posts']); ?></th>
                                        <th><?php echo esc_html($t['table_error']); ?></th>
                                        <th><?php echo esc_html($t['table_payload']); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($last_runs as $run): ?>
                                    <tr>
                                        <td class="ltlb-td-date"><?php echo esc_html($run->created_at); ?></td>
                                        <td>
                                            <span class="ltlb-badge <?php echo $run->status === 'success' ? 'ltlb-badge-success' : 'ltlb-badge-error'; ?>">
                                                <?php echo esc_html($run->status); ?>
                                            </span>
                                        </td>
                                        <td class="ltlb-td-count"><?php echo esc_html($run->posts_created); ?></td>
                                        <td class="ltlb-td-error">
                                            <?php if ($run->error_message): ?>
                                                <details class="ltlb-details">
                                                    <summary><?php echo esc_html($t['show_error']); ?></summary>
                                                    <pre class="ltlb-code"><?php echo esc_html($run->error_message); ?></pre>
                                                </details>
                                            <?php else: ?>
                                                <span class="ltlb-text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="ltlb-td-payload">
                                            <?php if ($run->raw_payload): ?>
                                                <details class="ltlb-details">
                                                    <summary><?php echo esc_html($t['show_payload']); ?></summary>
                                                    <pre class="ltlb-code"><?php echo esc_html(mb_strimwidth($run->raw_payload, 0, 512, '...')); ?></pre>
                                                </details>
                                            <?php else: ?>
                                                <span class="ltlb-text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
        (function() {
            // Language Switcher
            document.querySelectorAll('.ltlb-lang-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var lang = this.getAttribute('data-lang');
                    document.cookie = 'ltl_frontend_lang=' + lang + ';path=/;max-age=' + (86400 * 365);
                    window.location.reload();
                });
            });

            // Toggle Runs Section
            var toggleBtn = document.getElementById('ltlb-toggle-runs');
            var runsList = document.getElementById('ltlb-runs-list');
            if (toggleBtn && runsList) {
                toggleBtn.addEventListener('click', function() {
                    runsList.classList.toggle('open');
                    this.textContent = runsList.classList.contains('open') ? '<?php echo esc_js($t['btn_hide_runs']); ?>' : '<?php echo esc_js($t['btn_show_runs']); ?>';
                });
            }

            // Test WordPress Connection
            var testConnBtn = document.getElementById('ltl-saas-test-connection');
            if (testConnBtn) {
                testConnBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var btn = this;
                    btn.disabled = true;
                    btn.classList.add('loading');
                    var result = document.getElementById('ltl-saas-test-result');
                    result.innerHTML = '<span class="ltlb-text-muted">⏳ <?php echo esc_js($t['testing']); ?></span>';

                    var wpUrl = document.getElementById('wp_url').value;
                    var wpUser = document.getElementById('wp_user').value;
                    var wpPass = document.getElementById('wp_app_password').value;

                    if (!wpUrl || !wpUser || !wpPass) {
                        result.innerHTML = '<span class="ltlb-text-error">❌ <?php echo esc_js($t['error_all_fields']); ?></span>';
                        btn.disabled = false;
                        btn.classList.remove('loading');
                        return;
                    }

                    fetch('<?php echo esc_url(rest_url('ltl-saas/v1/test-connection')); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ wp_url: wpUrl, wp_user: wpUser, wp_app_password: wpPass })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            result.innerHTML = '<span class="ltlb-text-success">✅ <?php echo esc_js($t['connection_success']); ?> (' + data.user + ')</span>';
                        } else {
                            result.innerHTML = '<span class="ltlb-text-error">❌ <?php echo esc_js($t['error_prefix']); ?> ' + (data.message || '<?php echo esc_js($t['error_unknown']); ?>') + '</span>';
                        }
                    })
                    .catch(function(err) {
                        result.innerHTML = '<span class="ltlb-text-error">❌ <?php echo esc_js($t['error_network']); ?> ' + err + '</span>';
                    })
                    .finally(function() {
                        btn.disabled = false;
                        btn.classList.remove('loading');
                    });
                });
            }

            // Test RSS Feed
            var testRssBtn = document.getElementById('ltl-saas-test-rss');
            if (testRssBtn) {
                testRssBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var rssUrl = document.getElementById('rss_url').value;
                    var result = document.getElementById('ltl-saas-rss-result');
                    result.innerHTML = '<span class="ltlb-text-muted">⏳ <?php echo esc_js($t['testing_rss']); ?></span>';

                    if (!rssUrl) {
                        result.innerHTML = '<span class="ltlb-text-error">❌ <?php echo esc_js($t['error_rss_required']); ?></span>';
                        return;
                    }

                    fetch('<?php echo esc_url(rest_url('ltl-saas/v1/test-rss')); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ rss_url: rssUrl })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            result.innerHTML = '<span class="ltlb-text-success">✅ RSS OK! <?php echo esc_js($t['rss_title']); ?> ' + data.title + '</span>';
                        } else {
                            result.innerHTML = '<span class="ltlb-text-error">❌ <?php echo esc_js($t['error_prefix']); ?> ' + (data.message || '<?php echo esc_js($t['error_unknown']); ?>') + '</span>';
                        }
                    })
                    .catch(function(err) {
                        result.innerHTML = '<span class="ltlb-text-error">❌ <?php echo esc_js($t['error_network']); ?> ' + err + '</span>';
                    });
                });
            }
        })();
        </script>
        <?php
        }
        return ob_get_clean();
    }

    /**
     * Issue #19: Pricing Landing Page Shortcode
     * Usage: [ltl_saas_pricing] or [ltl_saas_pricing lang="en"]
     * Features: Agency Design, Language Switcher, Responsive
     */
    public function shortcode_pricing( $atts = [] ) {
        // Enqueue frontend assets
        $this->enqueue_frontend_assets();

        // Handle frontend language switch (stored in cookie)
        $cookie_lang = isset($_COOKIE['ltl_frontend_lang']) ? sanitize_text_field($_COOKIE['ltl_frontend_lang']) : null;

        $atts = shortcode_atts( array(
            'lang' => $cookie_lang ?? 'en',
        ), $atts );

        $lang = strtolower( $atts['lang'] );
        if ( ! in_array( $lang, array( 'de', 'en' ), true ) ) {
            $lang = 'en';
        }

        // Get checkout URLs from admin settings
        $url_basic = get_option( 'ltl_saas_checkout_url_starter', '' );
        $url_pro = get_option( 'ltl_saas_checkout_url_pro', '' );
        $url_studio = get_option( 'ltl_saas_checkout_url_agency', '' );
        $url_free = function_exists('wp_registration_url') ? wp_registration_url() : wp_login_url();

        // Bilingual content
        $content = array(
            'de' => array(
                'hero_title' => 'Schreibe automatisch mit KI',
                'hero_subtitle' => 'Verwandle RSS-Feeds in SEO-optimierte WordPress-Posts – vollautomatisch und professionell.',
                'plan_free' => 'Free',
                'plan_basic' => 'Basic',
                'plan_pro' => 'Pro',
                'plan_studio' => 'Studio',
                'price_free' => '€0',
                'price_basic' => '€19',
                'price_pro' => '€49',
                'price_studio' => 'Custom',
                'period' => '/Monat',
                'posts_free' => '10 Posts/Monat',
                'posts_basic' => '30 Posts/Monat',
                'posts_pro' => '120 Posts/Monat',
                'posts_studio' => '300 Posts/Monat',
                'blogs_free' => '1 Blog',
                'blogs_basic' => '3 Blogs',
                'blogs_pro' => '10 Blogs',
                'blogs_studio' => 'Unbegrenzt',
                'features_free' => ['Basis-KI-Artikel', 'Standard-Support', 'RSS-Import'],
                'features_basic' => ['Premium-KI-Artikel', 'E-Mail-Support', 'SEO-Optimierung', 'Automatische Veröffentlichung'],
                'features_pro' => ['Top-KI-Qualität', 'Priority-Support', 'Multi-Blog-Management', 'Analytics Dashboard', 'Custom Prompts'],
                'features_studio' => ['Enterprise-KI', 'Dedicated Support', 'White-Label-Option', 'API-Zugang', 'SLA-Garantie'],
                'popular' => 'Beliebt',
                'button_free' => 'Kostenlos starten',
                'button' => 'Jetzt starten',
                'contact' => 'Kontakt',
            ),
            'en' => array(
                'hero_title' => 'Write Automatically with AI',
                'hero_subtitle' => 'Transform RSS feeds into SEO-optimized WordPress posts – fully automated and professional.',
                'plan_free' => 'Free',
                'plan_basic' => 'Basic',
                'plan_pro' => 'Pro',
                'plan_studio' => 'Studio',
                'price_free' => '$0',
                'price_basic' => '$19',
                'price_pro' => '$49',
                'price_studio' => 'Custom',
                'period' => '/month',
                'posts_free' => '10 posts/month',
                'posts_basic' => '30 posts/month',
                'posts_pro' => '120 posts/month',
                'posts_studio' => '300 posts/month',
                'blogs_free' => '1 Blog',
                'blogs_basic' => '3 Blogs',
                'blogs_pro' => '10 Blogs',
                'blogs_studio' => 'Unlimited',
                'features_free' => ['Basic AI Articles', 'Standard Support', 'RSS Import'],
                'features_basic' => ['Premium AI Articles', 'Email Support', 'SEO Optimization', 'Auto Publishing'],
                'features_pro' => ['Top AI Quality', 'Priority Support', 'Multi-Blog Management', 'Analytics Dashboard', 'Custom Prompts'],
                'features_studio' => ['Enterprise AI', 'Dedicated Support', 'White-Label Option', 'API Access', 'SLA Guarantee'],
                'popular' => 'Popular',
                'button_free' => 'Start Free',
                'button' => 'Get Started',
                'contact' => 'Contact Sales',
            ),
        );

        $txt = $content[ $lang ];

        ob_start();
        ?>
        <div class="ltlb-pricing">
            <!-- Language Switcher -->
            <div class="ltlb-pricing-header">
                <div class="ltlb-lang-switcher ltlb-pricing-lang">
                    <button type="button" class="ltlb-lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>" data-lang="en">🇺🇸 EN</button>
                    <button type="button" class="ltlb-lang-btn <?php echo $lang === 'de' ? 'active' : ''; ?>" data-lang="de">🇩🇪 DE</button>
                </div>
            </div>

            <!-- Hero Section -->
            <div class="ltlb-pricing-hero">
                <h1 class="ltlb-pricing-title">
                    <span class="ltlb-pricing-icon">🚀</span>
                    <?php echo esc_html( $txt['hero_title'] ); ?>
                </h1>
                <p class="ltlb-pricing-subtitle"><?php echo esc_html( $txt['hero_subtitle'] ); ?></p>
            </div>

            <!-- Pricing Cards -->
            <div class="ltlb-pricing-grid">
                <!-- Free Plan -->
                <div class="ltlb-pricing-card">
                    <div class="ltlb-pricing-card-header">
                        <h3 class="ltlb-pricing-card-title"><?php echo esc_html( $txt['plan_free'] ); ?></h3>
                        <div class="ltlb-pricing-card-price">
                            <span class="ltlb-price-amount"><?php echo esc_html( $txt['price_free'] ); ?></span>
                            <span class="ltlb-price-period"><?php echo esc_html( $txt['period'] ); ?></span>
                        </div>
                    </div>
                    <div class="ltlb-pricing-card-body">
                        <div class="ltlb-pricing-highlight">
                            <span class="ltlb-highlight-icon">📝</span>
                            <span><?php echo esc_html( $txt['posts_free'] ); ?></span>
                        </div>
                        <div class="ltlb-pricing-highlight">
                            <span class="ltlb-highlight-icon">🌐</span>
                            <span><?php echo esc_html( $txt['blogs_free'] ); ?></span>
                        </div>
                        <ul class="ltlb-pricing-features">
                            <?php foreach ( $txt['features_free'] as $feature ) : ?>
                                <li><span class="ltlb-feature-check">✓</span><?php echo esc_html( $feature ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="ltlb-pricing-card-footer">
                        <?php if ( ! empty( $url_free ) ) : ?>
                            <a href="<?php echo esc_url( $url_free ); ?>" class="ltlb-btn ltlb-btn-secondary ltlb-btn-block">
                                <?php echo esc_html( $txt['button_free'] ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Basic Plan -->
                <div class="ltlb-pricing-card">
                    <div class="ltlb-pricing-card-header">
                        <h3 class="ltlb-pricing-card-title"><?php echo esc_html( $txt['plan_basic'] ); ?></h3>
                        <div class="ltlb-pricing-card-price">
                            <span class="ltlb-price-amount"><?php echo esc_html( $txt['price_basic'] ); ?></span>
                            <span class="ltlb-price-period"><?php echo esc_html( $txt['period'] ); ?></span>
                        </div>
                    </div>
                    <div class="ltlb-pricing-card-body">
                        <div class="ltlb-pricing-highlight">
                            <span class="ltlb-highlight-icon">📝</span>
                            <span><?php echo esc_html( $txt['posts_basic'] ); ?></span>
                        </div>
                        <div class="ltlb-pricing-highlight">
                            <span class="ltlb-highlight-icon">🌐</span>
                            <span><?php echo esc_html( $txt['blogs_basic'] ); ?></span>
                        </div>
                        <ul class="ltlb-pricing-features">
                            <?php foreach ( $txt['features_basic'] as $feature ) : ?>
                                <li><span class="ltlb-feature-check">✓</span><?php echo esc_html( $feature ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="ltlb-pricing-card-footer">
                        <?php if ( ! empty( $url_basic ) ) : ?>
                            <a href="<?php echo esc_url( $url_basic ); ?>" class="ltlb-btn ltlb-btn-primary ltlb-btn-block">
                                <?php echo esc_html( $txt['button'] ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pro Plan (Featured) -->
                <div class="ltlb-pricing-card ltlb-pricing-card-featured">
                    <div class="ltlb-pricing-badge"><?php echo esc_html( $txt['popular'] ); ?></div>
                    <div class="ltlb-pricing-card-header">
                        <h3 class="ltlb-pricing-card-title"><?php echo esc_html( $txt['plan_pro'] ); ?></h3>
                        <div class="ltlb-pricing-card-price">
                            <span class="ltlb-price-amount"><?php echo esc_html( $txt['price_pro'] ); ?></span>
                            <span class="ltlb-price-period"><?php echo esc_html( $txt['period'] ); ?></span>
                        </div>
                    </div>
                    <div class="ltlb-pricing-card-body">
                        <div class="ltlb-pricing-highlight">
                            <span class="ltlb-highlight-icon">📝</span>
                            <span><?php echo esc_html( $txt['posts_pro'] ); ?></span>
                        </div>
                        <div class="ltlb-pricing-highlight">
                            <span class="ltlb-highlight-icon">🌐</span>
                            <span><?php echo esc_html( $txt['blogs_pro'] ); ?></span>
                        </div>
                        <ul class="ltlb-pricing-features">
                            <?php foreach ( $txt['features_pro'] as $feature ) : ?>
                                <li><span class="ltlb-feature-check">✓</span><?php echo esc_html( $feature ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="ltlb-pricing-card-footer">
                        <?php if ( ! empty( $url_pro ) ) : ?>
                            <a href="<?php echo esc_url( $url_pro ); ?>" class="ltlb-btn ltlb-btn-primary ltlb-btn-block">
                                <?php echo esc_html( $txt['button'] ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Studio Plan -->
                <div class="ltlb-pricing-card ltlb-pricing-card-dark">
                    <div class="ltlb-pricing-card-header">
                        <h3 class="ltlb-pricing-card-title"><?php echo esc_html( $txt['plan_studio'] ); ?></h3>
                        <div class="ltlb-pricing-card-price">
                            <span class="ltlb-price-amount"><?php echo esc_html( $txt['price_studio'] ); ?></span>
                        </div>
                    </div>
                    <div class="ltlb-pricing-card-body">
                        <div class="ltlb-pricing-highlight">
                            <span class="ltlb-highlight-icon">📝</span>
                            <span><?php echo esc_html( $txt['posts_studio'] ); ?></span>
                        </div>
                        <div class="ltlb-pricing-highlight">
                            <span class="ltlb-highlight-icon">🌐</span>
                            <span><?php echo esc_html( $txt['blogs_studio'] ); ?></span>
                        </div>
                        <ul class="ltlb-pricing-features">
                            <?php foreach ( $txt['features_studio'] as $feature ) : ?>
                                <li><span class="ltlb-feature-check">✓</span><?php echo esc_html( $feature ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="ltlb-pricing-card-footer">
                        <?php if ( ! empty( $url_studio ) ) : ?>
                            <a href="<?php echo esc_url( $url_studio ); ?>" class="ltlb-btn ltlb-btn-outline ltlb-btn-block">
                                <?php echo esc_html( $txt['contact'] ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function() {
            document.querySelectorAll('.ltlb-pricing .ltlb-lang-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var lang = this.getAttribute('data-lang');
                    document.cookie = 'ltl_frontend_lang=' + lang + ';path=/;max-age=' + (86400 * 365);
                    window.location.reload();
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}

/**
 * Atomic month rollover helper (Issue #22 Phase 1)
 * Ensures reset + increment are atomic using WHERE clause
 * Returns: true if reset happened, false if no reset needed
 */
function ltl_saas_atomic_month_rollover( $user_id ) {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'ltl_saas_settings';
    $current_month_start = date('Y-m-01');

    // Atomic UPDATE: only update if posts_period_start != current month
    // This prevents races where 2 parallel requests both try to reset
    $updated = $wpdb->query($wpdb->prepare(
        "UPDATE $settings_table SET posts_this_month = 0, posts_period_start = %s WHERE user_id = %d AND posts_period_start != %s",
        $current_month_start,
        $user_id,
        $current_month_start
    ));

    return $updated > 0; // true if reset happened
}




