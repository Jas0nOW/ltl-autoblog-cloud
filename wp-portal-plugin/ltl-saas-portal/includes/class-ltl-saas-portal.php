<?php
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

        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'ltl_saas_connections';
        $error = '';
        $success = '';

        // Handle form submit
        if ( isset($_POST['ltl_saas_save_connection']) && wp_verify_nonce($_POST['ltl_saas_nonce'], 'ltl_saas_save_connection') ) {
            $wp_url = esc_url_raw(trim($_POST['wp_url'] ?? ''));
            $wp_user = sanitize_user(trim($_POST['wp_user'] ?? ''));
            $wp_app_password = trim($_POST['wp_app_password'] ?? '');

            if ( empty($wp_url) || ! filter_var($wp_url, FILTER_VALIDATE_URL) ) {
                $error = 'Bitte eine gültige WordPress-URL angeben.';
            } elseif ( empty($wp_user) ) {
                $error = 'Bitte einen gültigen Benutzernamen oder E-Mail angeben.';
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

        // Load current values
        $conn = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
        $wp_url = $conn->wp_url ?? '';
        $wp_user = $conn->wp_user ?? '';

        ob_start();
        ?>
        <div class="ltl-saas-dashboard">
            <h2>LTL AutoBlog Cloud</h2>
            <?php if ($error): ?><div style="color:red;"><strong><?php echo esc_html($error); ?></strong></div><?php endif; ?>
            <?php if ($success): ?><div style="color:green;"><strong><?php echo esc_html($success); ?></strong></div><?php endif; ?>
            <form method="post">
                <?php wp_nonce_field('ltl_saas_save_connection', 'ltl_saas_nonce'); ?>
                <table>
                    <tr><td>WordPress-URL:</td><td><input type="url" name="wp_url" value="<?php echo esc_attr($wp_url); ?>" required style="width:300px;"></td></tr>
                    <tr><td>Benutzername/E-Mail:</td><td><input type="text" name="wp_user" value="<?php echo esc_attr($wp_user); ?>" required></td></tr>
                    <tr><td>Application Password:</td><td><input type="password" name="wp_app_password" value="" autocomplete="new-password"></td></tr>
                </table>
                <button type="submit" name="ltl_saas_save_connection">Speichern</button>
                <button type="button" id="ltl-saas-test-connection">Test connection</button>
            </form>
            <div id="ltl-saas-test-result"></div>
        </div>
        <script>
        document.getElementById('ltl-saas-test-connection').addEventListener('click', function(e) {
            e.preventDefault();
            var btn = this;
            btn.disabled = true;
            var result = document.getElementById('ltl-saas-test-result');
            result.innerHTML = 'Testing...';
            fetch('<?php echo esc_url(rest_url('ltl-saas/v1/wp-connection/test')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
                credentials: 'same-origin',
                body: JSON.stringify({})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    result.innerHTML = '<span style="color:red">' + (data.message || 'Fehler') + '</span>';
                }
            })
            .catch(err => { result.innerHTML = '<span style="color:red">' + err + '</span>'; })
            .finally(() => { btn.disabled = false; });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
