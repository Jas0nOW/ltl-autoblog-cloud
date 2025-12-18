<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

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
            json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE $runs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            post_url TEXT NULL,
            error LONGTEXT NULL,
            meta LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        foreach ( $sql as $statement ) {
            dbDelta( $statement );
        }
    }

    public static function deactivate() {
        // We do NOT delete tables on deactivate (data safety).
    }

    public function shortcode_dashboard( $atts = [] ) {
        if ( ! is_user_logged_in() ) {
            return '<p>Bitte einloggen.</p>';
        }

        ob_start();
        ?>
        <div class="ltl-saas-dashboard">
            <h2>LTL AutoBlog Cloud</h2>
            <p>Status: <strong>Portal aktiv</strong></p>
            <p>Platzhalter: Settings-UI + WP-Connect kommen in den nächsten Issues (#9, #10).</p>
        </div>
        <?php
        return ob_get_clean();
    }
}
