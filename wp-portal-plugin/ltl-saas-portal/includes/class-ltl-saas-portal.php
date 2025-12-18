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
            $rss_url = esc_url_raw(trim($_POST['rss_url'] ?? ''));
            $language = $_POST['language'] ?? '';
            $tone = $_POST['tone'] ?? '';
            $frequency = $_POST['frequency'] ?? '';
            $publish_mode = $_POST['publish_mode'] ?? '';

            if ($rss_url && !filter_var($rss_url, FILTER_VALIDATE_URL)) {
                $error = 'Bitte eine gültige RSS-URL angeben.';
            } elseif ($language && !in_array($language, $languages, true)) {
                $error = 'Ungültige Sprache.';
            } elseif ($tone && !in_array($tone, $tones, true)) {
                $error = 'Ungültiger Ton.';
            } elseif ($frequency && !in_array($frequency, $frequencies, true)) {
                $error = 'Ungültige Frequenz.';
            } elseif ($publish_mode && !in_array($publish_mode, $publish_modes, true)) {
                $error = 'Ungültiger Veröffentlichungsmodus.';
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
                    $wpdb->insert($settings_table, $row);
                }
                $settings_success = 'Saved ✓';
            }
        }

        // --- CONNECTION: Handle connection form submit ---
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

        $settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM $settings_table WHERE user_id = %d", $user_id));
        $rss_url = $settings->rss_url ?? '';
        $language = $settings->language ?? '';
        $tone = $settings->tone ?? '';
        $frequency = $settings->frequency ?? '';
        $publish_mode = $settings->publish_mode ?? '';

        $runs_table = $wpdb->prefix . 'ltl_saas_runs';
        $last_runs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $runs_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 5", $user_id));

        ob_start();
        ?>
        <div class="ltl-saas-dashboard">
            <h2>LTL AutoBlog Cloud</h2>
            <?php if ($error): ?><div style="color:red;"><strong><?php echo esc_html($error); ?></strong></div><?php endif; ?>
            <?php if ($success): ?><div style="color:green;"><strong><?php echo esc_html($success); ?></strong></div><?php endif; ?>
            <form method="post" style="margin-bottom:2em;">
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

            <form method="post">
                <?php wp_nonce_field('ltl_saas_save_settings', 'ltl_saas_settings_nonce'); ?>
                <table>
                    <tr><td>RSS-Feed URL:</td><td><input type="url" name="rss_url" value="<?php echo esc_attr($rss_url); ?>" style="width:300px;"></td></tr>
                    <tr><td>Sprache:</td><td><select name="language">
                        <option value="">Bitte wählen</option>
                        <?php foreach($languages as $l): ?><option value="<?php echo $l; ?>" <?php selected($language, $l); ?>><?php echo strtoupper($l); ?></option><?php endforeach; ?>
                    </select></td></tr>
                    <tr><td>Ton:</td><td><select name="tone">
                        <option value="">Bitte wählen</option>
                        <?php foreach($tones as $t): ?><option value="<?php echo $t; ?>" <?php selected($tone, $t); ?>><?php echo ucfirst($t); ?></option><?php endforeach; ?>
                    </select></td></tr>
                    <tr><td>Frequenz:</td><td><select name="frequency">
                        <option value="">Bitte wählen</option>
                        <?php foreach($frequencies as $f): ?><option value="<?php echo $f; ?>" <?php selected($frequency, $f); ?>><?php echo $f === '3x_week' ? '3x/Woche' : ucfirst($f); ?></option><?php endforeach; ?>
                    </select></td></tr>
                    <tr><td>Veröffentlichung:</td><td><select name="publish_mode">
                        <option value="">Bitte wählen</option>
                        <?php foreach($publish_modes as $p): ?><option value="<?php echo $p; ?>" <?php selected($publish_mode, $p); ?>><?php echo ucfirst($p); ?></option><?php endforeach; ?>
                    </select></td></tr>
                </table>
                <button type="submit" name="ltl_saas_save_settings">Settings speichern</button>
                <?php if ($settings_success): ?><span style="color:green;margin-left:1em;"><strong><?php echo esc_html($settings_success); ?></strong></span><?php endif; ?>
            </form>

            <h3>Letzte 5 Runs</h3>
            <?php if (empty($last_runs)): ?>
                <p>Noch keine Runs.</p>
            <?php else: ?>
                <table style="width:100%;">
                    <thead><tr><th>Datum</th><th>Status</th><th>Post-URL</th><th>Details</th></tr></thead>
                    <tbody>
                    <?php foreach ($last_runs as $run): ?>
                        <tr>
                            <td><?php echo esc_html($run->created_at); ?></td>
                            <td>
                                <?php if ($run->status === 'success'): ?>
                                    <span style="color:green;">✓ Success</span>
                                <?php else: ?>
                                    <span style="color:red;">✗ Error</span>
                                <?php endif; ?>
                            </td>
                            <td><?php if ($run->post_url): ?><a href="<?php echo esc_url($run->post_url); ?>" target="_blank">View Post</a><?php endif; ?></td>
                            <td><?php if ($run->error): ?><pre><?php echo esc_html($run->error); ?></pre><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
