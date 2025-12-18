<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTL_SAAS_Portal_REST {

    const NAMESPACE = 'ltl-saas/v1';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( self::NAMESPACE, '/health', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'health' ),
            'permission_callback' => '__return_true',
        ) );

        // Issue #12: GET /active-users (API key auth)
        register_rest_route( self::NAMESPACE, '/active-users', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_active_users' ),
            'permission_callback' => '__return_true', // Auth in callback
        ) );

        // Issue #14: POST /run-callback (API key auth)
        register_rest_route( self::NAMESPACE, '/run-callback', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'run_callback' ),
            'permission_callback' => '__return_true', // Auth in callback
        ) );

        // Make Multi-Tenant: GET /make/tenants (Token Auth, SSL enforced)
        register_rest_route( self::NAMESPACE, '/make/tenants', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_make_tenants' ),
            'permission_callback' => array( $this, 'permission_make_tenants' ),
        ) );

        // Sprint 07: Gumroad Billing Ping
        register_rest_route( self::NAMESPACE, '/gumroad/ping', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'gumroad_ping' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * POST /wp-json/ltl-saas/v1/gumroad/ping
     * Gumroad sends application/x-www-form-urlencoded
     * Required: secret (query or header), email, product_id, refunded
     * Security: secret must match option, SSL required
     * Response: always 200 {ok:true} if processed, else 4xx
     */
    public function gumroad_ping( $request ) {
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-secrets.php';
        // Enforce SSL
        if ( ! is_ssl() ) {
            return new WP_REST_Response(['error' => 'HTTPS required'], 403);
        }
        // Secret: query param or header
        $secret = $request->get_param('secret');
        if (!$secret) {
            $secret = $request->get_header('X-Gumroad-Secret');
        }
        $option_secret = LTL_SAAS_Portal_Secrets::get_gumroad_secret();
        if (!$option_secret || !$secret || !hash_equals($option_secret, $secret)) {
            return new WP_REST_Response(['error' => 'Forbidden'], 403);
        }
        // Parse form params
        $params = $request->get_body_params();
        $email = isset($params['email']) ? sanitize_email($params['email']) : '';
        $product_id = isset($params['product_id']) ? sanitize_text_field($params['product_id']) : '';
        $subscription_id = isset($params['subscription_id']) ? sanitize_text_field($params['subscription_id']) : '';
        $recurrence = isset($params['recurrence']) ? sanitize_text_field($params['recurrence']) : '';
        $refunded = isset($params['refunded']) ? $params['refunded'] : '';
        $sale_id = isset($params['sale_id']) ? sanitize_text_field($params['sale_id']) : '';
        // TODO: Provisioning logic in next prompt
        // Respond quickly
        return new WP_REST_Response(['ok' => true], 200);
    }

    /**
     * Permission callback for /make/tenants: Token + SSL required
     */
    public function permission_make_tenants( $request ) {
        if ( ! is_ssl() ) {
            return new WP_Error('forbidden', 'HTTPS required for secrets.', array('status' => 403));
        }
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-secrets.php';
        $make_token = LTL_SAAS_Portal_Secrets::get_make_token();
        $header_token = $request->get_header('X-LTL-SAAS-TOKEN');
        if (!$header_token || !$make_token || !is_string($make_token) || !trim($make_token) || !hash_equals($make_token, $header_token)) {
            return new WP_Error('forbidden', 'Forbidden', array('status' => 403));
        }
        return true;
    }
    /**
     * GET /wp-json/ltl-saas/v1/make/tenants
     * Returns all tenants for Make.com (multi-tenant config pull)
     * Auth: X-LTL-SAAS-TOKEN header, compared to option ltl_saas_make_token
     * 401 if header missing, 403 if token missing/invalid/empty
     */
    public function get_make_tenants( $request ) {
        // Auth/SSL handled in permission_callback
        global $wpdb;
        $conn_table = $wpdb->prefix . 'ltl_saas_connections';
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-crypto.php';
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal.php';
        $users = $wpdb->get_results("SELECT * FROM $conn_table");
        $result = [];
        foreach ($users as $u) {
            $state = ltl_saas_get_tenant_state($u->user_id);
            if (!$state['is_active']) {
                continue; // skip inactive tenants
            }
            // Reset-Check: If posts_period_start != current month start, reset posts_this_month and update posts_period_start
            $current_month_start = date('Y-m-01');
            if ($state['posts_period_start'] !== $current_month_start) {
                $settings_table = $wpdb->prefix . 'ltl_saas_settings';
                $wpdb->update(
                    $settings_table,
                    [
                        'posts_this_month' => 0,
                        'posts_period_start' => $current_month_start
                    ],
                    ['user_id' => $u->user_id]
                );
                $state['posts_this_month'] = 0;
                $state['posts_period_start'] = $current_month_start;
            }
            // Enforce: skip if posts_this_month >= posts_limit_month
            $skip = false;
            $skip_reason = '';
            $remaining = $state['posts_limit_month'] - $state['posts_this_month'];
            if ($state['posts_this_month'] >= $state['posts_limit_month']) {
                $skip = true;
                $skip_reason = 'monthly_limit_reached';
                $remaining = 0;
            }
            $decrypted = LTL_SAAS_Portal_Crypto::decrypt($u->wp_app_password_enc);
            if (is_wp_error($decrypted)) {
                continue; // skip tenant if decryption fails
            }
            // Sanitize outputs
            $site_url = esc_url_raw($u->wp_url);
            $settings_table = $wpdb->prefix . 'ltl_saas_settings';
            $settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM $settings_table WHERE user_id = %d", $u->user_id), ARRAY_A);
            $rss_url = isset($settings['rss_url']) ? esc_url_raw($settings['rss_url']) : '';
            $language = isset($settings['language']) ? sanitize_text_field($settings['language']) : '';
            $tone = isset($settings['tone']) ? sanitize_text_field($settings['tone']) : '';
            $publish_mode = isset($settings['publish_mode']) ? sanitize_text_field($settings['publish_mode']) : '';
            $frequency = isset($settings['frequency']) ? sanitize_text_field($settings['frequency']) : '';
            $plan = isset($settings['plan']) ? sanitize_text_field($settings['plan']) : '';
            $tenant = [
                'tenant_id' => (int)$u->user_id,
                'site_url' => $site_url,
                'wp_username' => sanitize_user($u->wp_user),
                'wp_app_password' => $decrypted,
                'rss_url' => $rss_url,
                'language' => $language,
                'tone' => $tone,
                'publish_mode' => $publish_mode,
                'frequency' => $frequency,
                'plan' => $plan,
                'is_active' => $state['is_active'],
                'posts_this_month' => $state['posts_this_month'],
                'posts_limit_month' => $state['posts_limit_month'],
                'posts_period_start' => $state['posts_period_start'],
                'skip' => $skip,
                'skip_reason' => $skip ? $skip_reason : '',
                'remaining' => $remaining,
            ];
            $result[] = $tenant;
        }
        return new WP_REST_Response($result, 200);
    }

    /**
     * GET /wp-json/ltl-saas/v1/active-users
     * Returns all users with a saved connection (active), with settings and decrypted app password.
     * Protected by API key in header X-LTL-API-Key (compared to option ltl_saas_api_key).
     *
     * NOTE: The decrypted app password is only for backend/service use. Never expose to frontend/UI.
     */
    public function get_active_users( $request ) {
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-secrets.php';
        $api_key = LTL_SAAS_Portal_Secrets::get_api_key();
        $header_key = $request->get_header('X-LTL-API-Key');
        if (!$api_key || !$header_key || !hash_equals($api_key, $header_key)) {
            return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        }
        global $wpdb;
        $conn_table = $wpdb->prefix . 'ltl_saas_connections';
        $settings_table = $wpdb->prefix . 'ltl_saas_settings';
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-crypto.php';
        $users = $wpdb->get_results("SELECT * FROM $conn_table");
        $result = [];
        foreach ($users as $u) {
            $settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM $settings_table WHERE user_id = %d", $u->user_id), ARRAY_A);
            $is_active = isset($settings['is_active']) ? (bool)$settings['is_active'] : true;
            if (!$is_active) {
                continue; // skip inactive users
            }
            $result[] = [
                'user_id' => (int)$u->user_id,
                'settings' => $settings ?: (object)[],
                'wp_url' => $u->wp_url,
                'wp_user' => $u->wp_user,
                'wp_app_password' => '***',
            ];
        }
        return $result;
    }

    /**
     * Testet die gespeicherte WP-Verbindung des eingeloggten Users.
     */
    public function run_callback( $request ) {
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-secrets.php';
        $api_key = LTL_SAAS_Portal_Secrets::get_api_key();
        $header_key = $request->get_header('X-LTL-API-Key');
        if (!$api_key || !$header_key || !hash_equals($api_key, $header_key)) {
            return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ltl_saas_runs';
        $settings_table = $wpdb->prefix . 'ltl_saas_settings';
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal.php';
        $params = $request->get_json_params();
        if (!is_array($params)) $params = [];
        $tenant_id = isset($params['tenant_id']) ? intval($params['tenant_id']) : 0;
        $status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
        $started_at = isset($params['started_at']) ? sanitize_text_field($params['started_at']) : null;
        $finished_at = isset($params['finished_at']) ? sanitize_text_field($params['finished_at']) : null;
        $posts_created = isset($params['posts_created']) ? intval($params['posts_created']) : null;
        $error_message = isset($params['error_message']) ? sanitize_textarea_field($params['error_message']) : null;
        $meta = isset($params['meta']) ? wp_json_encode($params['meta']) : null;
        $raw_payload = wp_json_encode(array_slice($params,0,20));
        if ($raw_payload && strlen($raw_payload) > 8192) {
            $raw_payload = mb_strimwidth($raw_payload, 0, 8192, '...');
        }
        if (!$tenant_id || !$status) {
            return new WP_REST_Response(['error' => 'Missing tenant_id or status'], 400);
        }
        // Validate tenant_id exists
        $conn_table = $wpdb->prefix . 'ltl_saas_connections';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $conn_table WHERE user_id = %d", $tenant_id));
        if (!$exists) {
            return new WP_REST_Response(['ok'=>false, 'error'=>'unknown_tenant'], 400);
        }
        // Insert run as before
        $row = [
            'tenant_id' => $tenant_id,
            'status' => $status,
            'started_at' => $started_at,
            'finished_at' => $finished_at,
            'posts_created' => $posts_created,
            'error_message' => $error_message,
            'raw_payload' => $raw_payload,
            'created_at' => current_time('mysql'),
        ];
        $ok = $wpdb->insert($table, $row);
        // --- Sprint 04: Increment posts_this_month for successful publish ---
        if ($ok && $status === 'success') {
            $state = ltl_saas_get_tenant_state($tenant_id);
            $current_month_start = date('Y-m-01');
            if ($state['posts_period_start'] !== $current_month_start) {
                // Reset for new month
                $wpdb->update(
                    $settings_table,
                    [
                        'posts_this_month' => 1,
                        'posts_period_start' => $current_month_start
                    ],
                    ['user_id' => $tenant_id]
                );
            } else {
                // Normal increment
                $wpdb->query($wpdb->prepare(
                    "UPDATE $settings_table SET posts_this_month = posts_this_month + 1 WHERE user_id = %d",
                    $tenant_id
                ));
            }
            // Do NOT set is_active=0 if over limit; only limit enforcement applies
        }
        if ($ok) {
            return ['success' => true, 'id' => $wpdb->insert_id];
        } else {
            return new WP_REST_Response(['error' => 'DB insert failed'], 500);
        }
    }
    public function test_wp_connection( $request ) {
            $user_id = get_current_user_id();
            global $wpdb;
            // Access control: block inactive users
            $settings_table = $wpdb->prefix . 'ltl_saas_settings';
            $existing_settings = $wpdb->get_row($wpdb->prepare("SELECT is_active FROM $settings_table WHERE user_id = %d", $user_id));
            if ($existing_settings && isset($existing_settings->is_active) && intval($existing_settings->is_active) === 0) {
                return new WP_REST_Response( [ 'success' => false, 'message' => 'Account inaktiv' ], 403 );
            }
            $table = $wpdb->prefix . 'ltl_saas_connections';
            $conn = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id ) );
            if ( ! $conn ) {
                return new WP_REST_Response( [ 'success' => false, 'message' => 'Keine Verbindung gespeichert.' ], 400 );
            }
            require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-crypto.php';
            $wp_url = $conn->wp_url;
            $wp_user = $conn->wp_user;
            $wp_app_password = LTL_SAAS_Portal_Crypto::decrypt( $conn->wp_app_password_enc );
            if ( is_wp_error($wp_app_password) || ! $wp_url || ! $wp_user || ! $wp_app_password ) {
                return new WP_REST_Response( [ 'success' => false, 'message' => 'Ungültige Verbindungsdaten.' ], 400 );
            }
            $api_url = rtrim( $wp_url, '/' ) . '/wp-json/wp/v2/users/me';
            $auth = base64_encode( $wp_user . ':' . $wp_app_password );
            $args = [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth,
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ];
            $resp = wp_remote_get( $api_url, $args );
            if ( is_wp_error( $resp ) ) {
                return new WP_REST_Response( [ 'success' => false, 'message' => $resp->get_error_message() ], 500 );
            }
            $code = wp_remote_retrieve_response_code( $resp );
            $body = wp_remote_retrieve_body( $resp );
            $json = json_decode( $body, true );
            if ( $code === 200 && isset($json['id']) ) {
                return [
                    'success' => true,
                    'remote_user' => [
                        'id' => $json['id'],
                        'name' => $json['name'] ?? '',
                        'roles' => $json['roles'] ?? [],
                    ],
                ];
            } else {
                return new WP_REST_Response( [ 'success' => false, 'message' => $json['message'] ?? 'Fehler', 'response' => $json ], 400 );
            }
        }

    public function health( $request ) {
        return array(
            'ok' => true,
            'plugin_version' => LTL_SAAS_PORTAL_VERSION,
            'timestamp' => gmdate('c'),
        );
    }
}
