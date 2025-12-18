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
            // Access control: prevent saving when user is inactive
            $existing_settings = $wpdb->get_row($wpdb->prepare("SELECT is_active FROM $settings_table WHERE user_id = %d", $user_id));
            if ($existing_settings && isset($existing_settings->is_active) && intval($existing_settings->is_active) === 0) {
                $error = 'Account inaktiv. Einstellungen können nicht gespeichert werden.';
            } else {
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
                        $row['is_active'] = 1; // default active
                        $row['plan'] = 'free';
                        $wpdb->insert($settings_table, $row);
                    }
                    $settings_success = 'Saved ✓';
                }
            }
        }

        // --- CONNECTION: Handle connection form submit ---
        if ( isset($_POST['ltl_saas_save_connection']) && wp_verify_nonce($_POST['ltl_saas_nonce'], 'ltl_saas_save_connection') ) {
            // Access control: prevent saving connection when user is inactive
            $existing_settings = $wpdb->get_row($wpdb->prepare("SELECT is_active FROM $settings_table WHERE user_id = %d", $user_id));
            if ($existing_settings && isset($existing_settings->is_active) && intval($existing_settings->is_active) === 0) {
                $error = 'Account inaktiv. Verbindung kann nicht gespeichert werden.';
            } else {
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

        ob_start();
        if (!$is_active) {
            $pricing_url = get_option('ltl_saas_pricing_url', '#');
            echo '<div class="ltl-saas-locked" style="border:2px solid #e00; background:#fff0f0; padding:2em; text-align:center; max-width:500px; margin:2em auto;">';
            echo '<h2 style="color:#e00;">Abo erforderlich</h2>';
            echo '<p>Dein Zugang ist aktuell inaktiv. Bitte buche ein Abo, um fortzufahren.</p>';
            echo '<a href="' . esc_url($pricing_url) . '" class="button button-primary" style="font-size:1.2em;">Zu den Preisen</a>';
            echo '</div>';
        } else {
        ?>
        <div class="ltl-saas-dashboard">
            <h2>LTL AutoBlog Cloud</h2>
            <?php if ($error): ?><div style="color:red;"><strong><?php echo esc_html($error); ?></strong></div><?php endif; ?>
            <?php if ($success): ?><div style="color:green;"><strong><?php echo esc_html($success); ?></strong></div><?php endif; ?>

            <!-- Issue #20: Setup Progress Block -->
            <div style="background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="margin: 0;">📋 Dein Setup-Fortschritt</h2>
                    <a href="<?php echo esc_url(plugins_url('../../../docs/product/onboarding-detailed.md', __FILE__)); ?>" target="_blank" style="text-decoration: none; color: #667eea; font-size: 0.9em;">
                        📖 Hilfe &amp; Onboarding-Guide
                    </a>
                </div>

                <!-- Step 1: WordPress Connection -->
                <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                    <div style="font-size: 1.5em; margin-right: 15px;">
                        [<?php echo !empty($wp_url) ? '✅' : '⚠️'; ?>]
                    </div>
                    <div>
                        <strong>Schritt 1: WordPress verbinden</strong>
                        <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                            <?php echo !empty($wp_url) ? 'Verbunden ✓' : 'Noch nicht konfiguriert'; ?>
                        </p>
                    </div>
                    <div style="margin-left: auto;">
                        <a href="#wp-connection" class="button <?php echo !empty($wp_url) ? 'button-secondary' : 'button-primary'; ?>">
                            <?php echo !empty($wp_url) ? 'Bearbeiten' : 'Jetzt verbinden'; ?>
                        </a>
                    </div>
                </div>

                <!-- Step 2: RSS + Settings -->
                <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                    <div style="font-size: 1.5em; margin-right: 15px;">
                        [<?php echo !empty($rss_url) ? '✅' : '⚠️'; ?>]
                    </div>
                    <div>
                        <strong>Schritt 2: RSS-Feed + Einstellungen</strong>
                        <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                            <?php echo !empty($rss_url) ? 'RSS: ' . esc_html(substr($rss_url, 0, 30)) . '...' : 'Noch nicht konfiguriert'; ?>
                        </p>
                    </div>
                    <div style="margin-left: auto;">
                        <a href="#settings" class="button <?php echo !empty($rss_url) ? 'button-secondary' : 'button-primary'; ?>">
                            <?php echo !empty($rss_url) ? 'Bearbeiten' : 'Jetzt konfigurieren'; ?>
                        </a>
                    </div>
                </div>

                <?php
                // Step 3: Plan Status (Issue #20)
                $tenant_state = ltl_saas_get_tenant_state($user_id);
                $plan_display = ucfirst($tenant_state['plan']); // basic → Basic
                $is_active = $tenant_state['is_active'];
                $posts_used = $tenant_state['posts_used_month'];
                $posts_limit = $tenant_state['posts_limit_month'];
                ?>
                <!-- Step 3: Plan Active -->
                <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                    <div style="font-size: 1.5em; margin-right: 15px;">
                        [<?php echo $is_active ? '✅' : '⚠️'; ?>]
                    </div>
                    <div>
                        <strong>Schritt 3: Plan aktiv</strong>
                        <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                            <?php
                            if ($is_active) {
                                echo '<span style="color: green;">Plan: ' . esc_html($plan_display) . ' (' . $posts_used . '/' . $posts_limit . ' Posts)</span>';
                            } else {
                                echo '<span style="color: orange;">Abo erforderlich</span>';
                            }
                            ?>
                        </p>
                    </div>
                    <div style="margin-left: auto;">
                        <?php if (!$is_active): ?>
                            <a href="<?php echo esc_url(get_option('ltl_saas_gumroad_checkout_url_basic', 'https://lazytechlab.de')); ?>" class="button button-primary">
                                Abo aktivieren
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                // Step 4: Last Run Status (Issue #20)
                global $wpdb;
                $runs_table = $wpdb->prefix . 'ltl_saas_runs';
                $last_run = $wpdb->get_row($wpdb->prepare(
                    "SELECT status, finished_at, posts_created FROM $runs_table WHERE tenant_id = %d ORDER BY id DESC LIMIT 1",
                    $user_id
                ), ARRAY_A);
                $has_run = !empty($last_run);
                $run_ok = $has_run && $last_run['status'] === 'success';
                ?>
                <!-- Step 4: Last Run -->
                <div style="display: flex; align-items: center; padding: 10px 0;">
                    <div style="font-size: 1.5em; margin-right: 15px;">
                        [<?php echo $run_ok ? '✅' : ($has_run ? '⚠️' : '⏳'); ?>]
                    </div>
                    <div>
                        <strong>Schritt 4: Erster Durchlauf</strong>
                        <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                            <?php
                            if ($run_ok) {
                                $time_ago = human_time_diff(strtotime($last_run['finished_at']), current_time('timestamp'));
                                echo '<span style="color: green;">✓ Letzter Run: vor ' . esc_html($time_ago) . ' (' . (int)$last_run['posts_created'] . ' Posts)</span>';
                            } elseif ($has_run) {
                                echo '<span style="color: orange;">Letzter Run: ' . esc_html($last_run['status']) . '</span>';
                            } else {
                                echo 'Warte auf ersten automatischen Run...';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <form method="post" style="margin-bottom:2em;" id="wp-connection">
                <?php wp_nonce_field('ltl_saas_save_connection', 'ltl_saas_nonce'); ?>
                <h3>Schritt 1: WordPress verbinden</h3>
                <table>
                    <tr>
                        <td><label for="wp_url">🔗 WordPress URL <span title="Deine Website Domain" style="cursor: help; color: #667eea;">ℹ️</span></label></td>
                        <td>
                            <input type="url" id="wp_url" name="wp_url" value="<?php echo esc_attr($wp_url); ?>" placeholder="https://meinblog.de" required style="width:300px;">
                            <small style="color: #666;">✓ Muss https:// sein | 💡 Beispiel: https://meinseite.de (ohne /wp-admin)</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="wp_user">👤 Benutzername/E-Mail <span title="Dein WP Admin Benutzer" style="cursor: help; color: #667eea;">ℹ️</span></label></td>
                        <td>
                            <input type="text" id="wp_user" name="wp_user" value="<?php echo esc_attr($wp_user); ?>" placeholder="admin@meinblog.de" required>
                            <small style="color: #666;">💡 Der Benutzer, der die App-Passwörter erstellt hat</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="wp_app_password">🔐 Application Password <span title="Generiertes Passwort aus WP" style="cursor: help; color: #667eea;">ℹ️</span></label></td>
                        <td>
                            <input type="password" id="wp_app_password" name="wp_app_password" placeholder="xxxx xxxx xxxx xxxx" autocomplete="new-password">
                            <small style="color: #666;">💡 Generieren unter: WP-Admin → Nutzer → Profil → Anwendungspasswörter</small>
                        </td>
                    </tr>
                </table>
                <button type="submit" name="ltl_saas_save_connection">Speichern</button>
                <button type="button" id="ltl-saas-test-connection" class="button button-secondary">🧪 Test Connection</button>
            </form>
            <div id="ltl-saas-test-result"></div>

            <form method="post" id="settings">
                <?php wp_nonce_field('ltl_saas_save_settings', 'ltl_saas_settings_nonce'); ?>
                <h3>Schritt 2: RSS-Feed + Einstellungen</h3>
                <table>
                    <tr>
                        <td><label for="rss_url">📰 RSS-Quelle <span title="Deine RSS URL" style="cursor: help; color: #667eea;">ℹ️</span></label></td>
                        <td>
                            <input type="url" id="rss_url" name="rss_url" value="<?php echo esc_attr($rss_url); ?>" placeholder="https://beispiel.de/feed" style="width:300px;">
                            <small style="color: #666;">✓ Muss https:// sein | 💡 Beispiele: blog.de/feed, news-portal.com/rss | <a href="#" id="ltl-saas-test-rss" style="text-decoration:none; cursor:pointer; color: #667eea;">🧪 Test RSS</a></small>
                            <div id="ltl-saas-rss-result"></div>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="language">🌍 Sprache</label></td>
                        <td>
                            <select id="language" name="language">
                                <option value="">Bitte wählen</option>
                                <?php foreach($languages as $l): ?><option value="<?php echo $l; ?>" <?php selected($language, $l); ?>><?php echo strtoupper($l); ?></option><?php endforeach; ?>
                            </select>
                            <small style="color: #666;">💡 Die Sprache, in der Posts geschrieben werden</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="tone">✨ Ton</label></td>
                        <td>
                            <select id="tone" name="tone">
                                <option value="">Bitte wählen</option>
                                <?php foreach($tones as $t): ?><option value="<?php echo $t; ?>" <?php selected($tone, $t); ?>><?php echo ucfirst($t); ?></option><?php endforeach; ?>
                            </select>
                            <small style="color: #666;">💡 z.B. 'professional' oder 'funny'</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="frequency">📅 Frequenz</label></td>
                        <td>
                            <select id="frequency" name="frequency">
                                <option value="">Bitte wählen</option>
                                <?php foreach($frequencies as $f): ?><option value="<?php echo $f; ?>" <?php selected($frequency, $f); ?>><?php echo $f === '3x_week' ? '3x/Woche' : ucfirst($f); ?></option><?php endforeach; ?>
                            </select>
                            <small style="color: #666;">💡 Wie oft sollen Posts veröffentlicht werden?</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="publish_mode">📝 Veröffentlichung</label></td>
                        <td>
                            <select id="publish_mode" name="publish_mode">
                                <option value="">Bitte wählen</option>
                                <?php foreach($publish_modes as $p): ?><option value="<?php echo $p; ?>" <?php selected($publish_mode, $p); ?>><?php echo ucfirst($p); ?></option><?php endforeach; ?>
                            </select>
                            <small style="color: #666;">💡 'draft' = Vorlage prüfen | 'publish' = automatisch live</small>
                        </td>
                    </tr>
                </table>
                <button type="submit" name="ltl_saas_save_settings">Settings speichern</button>
                <?php if ($settings_success): ?><span style="color:green;margin-left:1em;"><strong><?php echo esc_html($settings_success); ?></strong></span><?php endif; ?>
            </form>

            <h3>Letzter Run</h3>
            <?php if (empty($last_runs)): ?>
                <p>Noch keine Runs.</p>
            <?php else: ?>
                <table style="width:100%;">
                    <thead><tr><th>Datum</th><th>Status</th><th>Posts</th><th>Fehler</th><th>Payload</th></tr></thead>
                    <tbody>
                    <?php foreach ($last_runs as $run): ?>
                        <tr>
                            <td><?php echo esc_html($run->created_at); ?></td>
                            <td><?php echo esc_html($run->status); ?></td>
                            <td><?php echo esc_html($run->posts_created); ?></td>
                            <td><?php if ($run->error_message): ?><pre><?php echo esc_html($run->error_message); ?></pre><?php endif; ?></td>
                            <td><?php if ($run->raw_payload): ?><details><summary>Show</summary><pre><?php echo esc_html(mb_strimwidth($run->raw_payload,0,512,'...')); ?></pre></details><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <button type="button" onclick="document.querySelector('.ltl-saas-runs-list').classList.toggle('open')">Runs anzeigen</button>
            <div class="ltl-saas-runs-list" style="display:none;">
                <!-- Hier könnte eine erweiterte Runs-Ansicht folgen -->
            </div>
        </div>
        <script>
        // Issue #20: Test WordPress Connection
        document.getElementById('ltl-saas-test-connection').addEventListener('click', function(e) {
            e.preventDefault();
            var btn = this;
            btn.disabled = true;
            var result = document.getElementById('ltl-saas-test-result');
            result.innerHTML = '⏳ Teste Verbindung...';
            result.style.color = '#666';

            var wpUrl = document.getElementById('wp_url').value;
            var wpUser = document.getElementById('wp_user').value;
            var wpPass = document.getElementById('wp_app_password').value;

            if (!wpUrl || !wpUser || !wpPass) {
                result.innerHTML = '❌ Alle Felder erforderlich (URL, User, Password)';
                result.style.color = '#dc3545';
                btn.disabled = false;
                return;
            }

            fetch('<?php echo esc_url(rest_url('ltl-saas/v1/test-connection')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    wp_url: wpUrl,
                    wp_user: wpUser,
                    wp_app_password: wpPass
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.innerHTML = '✅ Verbindung erfolgreich! (User: ' + data.user + ')';
                    result.style.color = '#28a745';
                } else {
                    result.innerHTML = '❌ Fehler: ' + (data.message || 'Unbekannter Fehler');
                    result.style.color = '#dc3545';
                }
            })
            .catch(err => {
                result.innerHTML = '❌ Netzwerkfehler: ' + err;
                result.style.color = '#dc3545';
            })
            .finally(() => { btn.disabled = false; });
        });

        // Issue #20: Test RSS Feed
        document.getElementById('ltl-saas-test-rss').addEventListener('click', function(e) {
            e.preventDefault();
            var rssUrl = document.getElementById('rss_url').value;
            var result = document.getElementById('ltl-saas-rss-result');
            result.innerHTML = '⏳ Teste RSS...';
            result.style.color = '#666';

            if (!rssUrl) {
                result.innerHTML = '❌ RSS-URL erforderlich';
                result.style.color = '#dc3545';
                return;
            }

            fetch('<?php echo esc_url(rest_url('ltl-saas/v1/test-rss')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    rss_url: rssUrl
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.innerHTML = '✅ RSS OK! Titel: ' + data.title;
                    result.style.color = '#28a745';
                } else {
                    result.innerHTML = '❌ Fehler: ' + (data.message || 'Unbekannter Fehler');
                    result.style.color = '#dc3545';
                }
            })
            .catch(err => {
                result.innerHTML = '❌ Netzwerkfehler: ' + err;
                result.style.color = '#dc3545';
            });
        });
        </script>
        <?php
        }
        return ob_get_clean();
    }

    /**
     * Issue #19: Pricing Landing Page Shortcode
     * Usage: [ltl_saas_pricing] or [ltl_saas_pricing lang="en"]
     */
    public function shortcode_pricing( $atts = [] ) {
        $atts = shortcode_atts( array(
            'lang' => 'de',
        ), $atts );

        $lang = strtolower( $atts['lang'] );
        if ( ! in_array( $lang, array( 'de', 'en' ), true ) ) {
            $lang = 'de';
        }

        // Get checkout URLs from admin settings
        // NOTE: Option keys are legacy (starter/pro/agency). We map them to canonical (basic/pro/studio).
        $url_basic = get_option( 'ltl_saas_checkout_url_starter', '' );
        $url_pro = get_option( 'ltl_saas_checkout_url_pro', '' );
        $url_studio = get_option( 'ltl_saas_checkout_url_agency', '' );

        // Free plan CTA: use WP registration URL (site owner can disable registrations).
        $url_free = function_exists('wp_registration_url') ? wp_registration_url() : wp_login_url();

        // Bilingual content
        $content = array(
            'de' => array(
                'hero_title' => 'Schreibe automatisch mit KI',
                'hero_subtitle' => 'Verwandle RSS-Feeds in SEO-optimierte WordPress-Posts',
                'plan_free' => 'Free',
                'plan_basic' => 'Basic',
                'plan_pro' => 'Pro',
                'plan_studio' => 'Studio',
                'price_free' => '€0',
                'price_basic' => '€19',
                'price_pro' => '€49',
                'price_studio' => 'Custom',
                'period' => '/Monat',
                'posts_free' => '10 Posts/Monat (1 Blog)',
                'posts_basic' => '30 Posts/Monat',
                'posts_pro' => '120 Posts/Monat',
                'posts_studio' => '300 Posts/Monat',
                'button_free' => 'Kostenlos starten',
                'button' => 'Starten',
                'contact' => 'Kontakt',
            ),
            'en' => array(
                'hero_title' => 'Automatically Write with AI',
                'hero_subtitle' => 'Turn RSS Feeds into SEO-optimized WordPress Posts',
                'plan_free' => 'Free',
                'plan_basic' => 'Basic',
                'plan_pro' => 'Pro',
                'plan_studio' => 'Studio',
                'price_free' => '$0',
                'price_basic' => '$19',
                'price_pro' => '$49',
                'price_studio' => 'Custom',
                'period' => '/month',
                'posts_free' => '10 posts/month (1 blog)',
                'posts_basic' => '30 posts/month',
                'posts_pro' => '120 posts/month',
                'posts_studio' => '300 posts/month',
                'button_free' => 'Start Free',
                'button' => 'Get Started',
                'contact' => 'Contact Sales',
            ),
        );

        $txt = $content[ $lang ];

        ob_start();
        ?>
        <style>
            .ltl-saas-pricing {
                background: #f8f9fa;
                padding: 60px 20px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .ltl-saas-pricing-hero {
                text-align: center;
                margin-bottom: 60px;
            }
            .ltl-saas-pricing-hero h1 {
                font-size: 2.5em;
                margin: 0 0 10px 0;
                color: #1a1a1a;
            }
            .ltl-saas-pricing-hero p {
                font-size: 1.2em;
                color: #666;
                margin: 0;
            }
            .ltl-saas-pricing-plans {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 30px;
                max-width: 1200px;
                margin: 0 auto;
            }
            .ltl-saas-pricing-card {
                background: white;
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                text-align: center;
                position: relative;
                transition: transform 0.3s, box-shadow 0.3s;
            }
            .ltl-saas-pricing-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            }
            .ltl-saas-pricing-card.featured {
                transform: scale(1.05);
                border: 3px solid #667eea;
            }
            .ltl-saas-pricing-card.featured:hover {
                transform: scale(1.05) translateY(-5px);
            }
            .ltl-saas-pricing-card-title {
                font-size: 1.5em;
                font-weight: 600;
                margin: 0 0 15px 0;
                color: #1a1a1a;
            }
            .ltl-saas-pricing-card-price {
                font-size: 2.5em;
                font-weight: 700;
                color: #667eea;
                margin: 15px 0 5px 0;
            }
            .ltl-saas-pricing-card-period {
                color: #888;
                font-size: 0.9em;
                margin-bottom: 20px;
            }
            .ltl-saas-pricing-card-desc {
                color: #666;
                font-size: 0.95em;
                margin-bottom: 30px;
            }
            .ltl-saas-pricing-card-button {
                display: inline-block;
                padding: 12px 30px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                transition: background 0.3s;
                border: none;
                cursor: pointer;
                font-size: 1em;
            }
            .ltl-saas-pricing-card-button:hover {
                background: #5568d3;
            }
            .ltl-saas-pricing-card-button.secondary {
                background: #f0f0f0;
                color: #333;
            }
            .ltl-saas-pricing-card-button.secondary:hover {
                background: #e0e0e0;
            }
            @media (max-width: 768px) {
                .ltl-saas-pricing-hero h1 {
                    font-size: 1.8em;
                }
                .ltl-saas-pricing-card.featured {
                    transform: scale(1);
                }
                .ltl-saas-pricing-card.featured:hover {
                    transform: translateY(-5px);
                }
                .ltl-saas-pricing-plans {
                    gap: 20px;
                }
            }
        </style>
        <div class="ltl-saas-pricing">
            <div class="ltl-saas-pricing-hero">
                <h1><?php echo esc_html( $txt['hero_title'] ); ?></h1>
                <p><?php echo esc_html( $txt['hero_subtitle'] ); ?></p>
            </div>
            <div class="ltl-saas-pricing-plans">
                <!-- Free Plan -->
                <div class="ltl-saas-pricing-card">
                    <h3 class="ltl-saas-pricing-card-title"><?php echo esc_html( $txt['plan_free'] ); ?></h3>
                    <div class="ltl-saas-pricing-card-price"><?php echo esc_html( $txt['price_free'] ); ?></div>
                    <div class="ltl-saas-pricing-card-period"><?php echo esc_html( $txt['period'] ); ?></div>
                    <div class="ltl-saas-pricing-card-desc"><?php echo esc_html( $txt['posts_free'] ); ?></div>
                    <?php if ( ! empty( $url_free ) ) : ?>
                        <a href="<?php echo esc_url( $url_free ); ?>" class="ltl-saas-pricing-card-button secondary"><?php echo esc_html( $txt['button_free'] ); ?></a>
                    <?php endif; ?>
                </div>

                <!-- Basic Plan -->
                <div class="ltl-saas-pricing-card">
                    <h3 class="ltl-saas-pricing-card-title"><?php echo esc_html( $txt['plan_basic'] ); ?></h3>
                    <div class="ltl-saas-pricing-card-price"><?php echo esc_html( $txt['price_basic'] ); ?></div>
                    <div class="ltl-saas-pricing-card-period"><?php echo esc_html( $txt['period'] ); ?></div>
                    <div class="ltl-saas-pricing-card-desc"><?php echo esc_html( $txt['posts_basic'] ); ?></div>
                    <?php if ( ! empty( $url_basic ) ) : ?>
                        <a href="<?php echo esc_url( $url_basic ); ?>" class="ltl-saas-pricing-card-button"><?php echo esc_html( $txt['button'] ); ?></a>
                    <?php endif; ?>
                </div>

                <!-- Pro Plan (Featured) -->
                <div class="ltl-saas-pricing-card featured">
                    <h3 class="ltl-saas-pricing-card-title"><?php echo esc_html( $txt['plan_pro'] ); ?></h3>
                    <div class="ltl-saas-pricing-card-price"><?php echo esc_html( $txt['price_pro'] ); ?></div>
                    <div class="ltl-saas-pricing-card-period"><?php echo esc_html( $txt['period'] ); ?></div>
                    <div class="ltl-saas-pricing-card-desc"><?php echo esc_html( $txt['posts_pro'] ); ?></div>
                    <?php if ( ! empty( $url_pro ) ) : ?>
                        <a href="<?php echo esc_url( $url_pro ); ?>" class="ltl-saas-pricing-card-button"><?php echo esc_html( $txt['button'] ); ?></a>
                    <?php endif; ?>
                </div>

                <!-- Studio Plan -->
                <div class="ltl-saas-pricing-card">
                    <h3 class="ltl-saas-pricing-card-title"><?php echo esc_html( $txt['plan_studio'] ); ?></h3>
                    <div class="ltl-saas-pricing-card-price"><?php echo esc_html( $txt['price_studio'] ); ?></div>
                    <div class="ltl-saas-pricing-card-period"><?php echo esc_html( $txt['period'] ); ?></div>
                    <div class="ltl-saas-pricing-card-desc"><?php echo esc_html( $txt['posts_studio'] ); ?></div>
                    <?php if ( ! empty( $url_studio ) ) : ?>
                        <a href="<?php echo esc_url( $url_studio ); ?>" class="ltl-saas-pricing-card-button secondary"><?php echo esc_html( $txt['contact'] ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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




