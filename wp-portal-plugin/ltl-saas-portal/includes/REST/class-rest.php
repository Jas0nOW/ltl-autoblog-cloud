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

        // Make Multi-Tenant: GET /make/tenants (Token Auth)
        register_rest_route( self::NAMESPACE, '/make/tenants', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_make_tenants' ),
            'permission_callback' => '__return_true',
        ) );
    }
    /**
     * GET /wp-json/ltl-saas/v1/make/tenants
     * Returns all tenants for Make.com (multi-tenant config pull)
     * Auth: X-LTL-SAAS-TOKEN header, compared to option ltl_saas_make_token
     * 401 if header missing, 403 if token missing/invalid/empty
     */
    public function get_make_tenants( $request ) {
        $make_token = get_option('ltl_saas_make_token');
        $header_token = $request->get_header('X-LTL-SAAS-TOKEN');
        if (!$header_token) {
            return new WP_REST_Response(['error' => 'Missing token'], 401);
        }
        if (!$make_token || !is_string($make_token) || !trim($make_token)) {
            return new WP_REST_Response(['error' => 'Endpoint disabled'], 403);
        }
        if (!hash_equals($make_token, $header_token)) {
            return new WP_REST_Response(['error' => 'Forbidden'], 403);
        }
        global $wpdb;
        $conn_table = $wpdb->prefix . 'ltl_saas_connections';
        $settings_table = $wpdb->prefix . 'ltl_saas_settings';
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-crypto.php';
        $users = $wpdb->get_results("SELECT * FROM $conn_table");
        $result = [];
        foreach ($users as $u) {
            $settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM $settings_table WHERE user_id = %d", $u->user_id), ARRAY_A);
            $decrypted = LTL_SAAS_Portal_Crypto::decrypt($u->wp_app_password_enc);
            // Sanitize outputs
            $site_url = esc_url_raw($u->wp_url);
            $rss_url = isset($settings['rss_url']) ? esc_url_raw($settings['rss_url']) : '';
            $language = isset($settings['language']) ? sanitize_text_field($settings['language']) : '';
            $tone = isset($settings['tone']) ? sanitize_text_field($settings['tone']) : '';
            $publish_mode = isset($settings['publish_mode']) ? sanitize_text_field($settings['publish_mode']) : '';
            $frequency = isset($settings['frequency']) ? sanitize_text_field($settings['frequency']) : '';
            $plan = isset($settings['plan']) ? sanitize_text_field($settings['plan']) : '';
            $is_active = isset($settings['is_active']) ? (bool)$settings['is_active'] : true;
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
                'is_active' => $is_active,
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
        $api_key = get_option('ltl_saas_api_key');
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
            $decrypted = LTL_SAAS_Portal_Crypto::decrypt($u->wp_app_password_enc);
            $result[] = [
                'user_id' => (int)$u->user_id,
                'settings' => $settings ?: (object)[],
                'wp_url' => $u->wp_url,
                'wp_user' => $u->wp_user,
                'wp_app_password' => $decrypted, // Only for backend/service use!
            ];
        }
        return $result;
    }

    /**
     * Testet die gespeicherte WP-Verbindung des eingeloggten Users.
     */
    public function run_callback( $request ) {
        $api_key = get_option('ltl_saas_api_key');
        $header_key = $request->get_header('X-LTL-API-Key');
        if (!$api_key || !$header_key || !hash_equals($api_key, $header_key)) {
            return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ltl_saas_runs';
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
        if ($ok) {
            return ['success' => true, 'id' => $wpdb->insert_id];
        } else {
            return new WP_REST_Response(['error' => 'DB insert failed'], 500);
        }
    }
    public function test_wp_connection( $request ) {
            $user_id = get_current_user_id();
            global $wpdb;
            $table = $wpdb->prefix . 'ltl_saas_connections';
            $conn = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id ) );
            if ( ! $conn ) {
                return new WP_REST_Response( [ 'success' => false, 'message' => 'Keine Verbindung gespeichert.' ], 400 );
            }
            require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-crypto.php';
            $wp_url = $conn->wp_url;
            $wp_user = $conn->wp_user;
            $wp_app_password = LTL_SAAS_Portal_Crypto::decrypt( $conn->wp_app_password_enc );
            if ( ! $wp_url || ! $wp_user || ! $wp_app_password ) {
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
