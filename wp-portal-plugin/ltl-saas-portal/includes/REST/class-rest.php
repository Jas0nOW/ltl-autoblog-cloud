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

        // Issue #7: Gumroad Billing Webhook (Ping endpoint)
        // Supports both /gumroad/webhook (Issue #7 contract) and /gumroad/ping (legacy)
        register_rest_route( self::NAMESPACE, '/gumroad/webhook', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'gumroad_webhook' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( self::NAMESPACE, '/gumroad/ping', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'gumroad_webhook' ),
            'permission_callback' => '__return_true',
        ) );

        // Issue #20: Test WordPress Connection
        register_rest_route( self::NAMESPACE, '/test-connection', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'test_wp_connection' ),
            'permission_callback' => array( $this, 'permission_user_logged_in' ),
        ) );

        // Issue #20: Test RSS Feed
        register_rest_route( self::NAMESPACE, '/test-rss', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'test_rss_feed' ),
            'permission_callback' => array( $this, 'permission_user_logged_in' ),
        ) );
    }

    /**
     * Issue #23: Rate limiting helper
     * Tracks failed auth attempts per IP and rejects if exceeds threshold
     * Uses WP transient for state (TTL 15 minutes)
     */
    private function check_rate_limit( $request, $endpoint_name ) {
        $ip = $this->get_client_ip();
        $limit_key = 'ltl_saas_ratelimit_' . md5($ip . $endpoint_name);
        $max_attempts = 10;  // 10 failed attempts
        $window_seconds = 900; // 15 minutes

        $attempts = get_transient($limit_key);
        if ($attempts === false) {
            $attempts = 0;
        }

        if ($attempts >= $max_attempts) {
            error_log('[LTL-SAAS] Rate limit exceeded: IP=' . $ip . ', endpoint=' . $endpoint_name . ', attempts=' . $attempts);
            return new WP_Error('rate_limit', 'Too many requests from this IP', array('status' => 429));
        }

        return true;
    }

    /**
     * Issue #23: Increment rate limit counter on auth failure
     */
    private function increment_rate_limit( $request, $endpoint_name ) {
        $ip = $this->get_client_ip();
        $limit_key = 'ltl_saas_ratelimit_' . md5($ip . $endpoint_name);
        $window_seconds = 900; // 15 minutes

        $attempts = get_transient($limit_key);
        if ($attempts === false) {
            $attempts = 0;
        }
        set_transient($limit_key, $attempts + 1, $window_seconds);
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }

    /**
     * POST /wp-json/ltl-saas/v1/gumroad/webhook (or /gumroad/ping for legacy)
     * Issue #7: Gumroad Billing Webhook Endpoint
     *
     * Processes Gumroad sale/subscribe/cancel/refund events:
     * - sale/subscribe (refunded=false) → User active + Plan set
     * - cancel/refund (refunded=true) → User deactivated (not deleted)
     *
     * Gumroad sends application/x-www-form-urlencoded.
     * Required: secret (query or header), email, product_id, refunded
     * Optional: subscription_id, recurrence, sale_id
     * Security: secret must match option ltl_saas_gumroad_secret, SSL required
     * Response: always 200 {ok:true} if processed, else 4xx for auth failure
     */
    public function gumroad_webhook( $request ) {
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
            error_log('[LTL-SAAS] Gumroad webhook: Secret mismatch or missing');
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

        // Issue #7: User & Settings Provisioning
        if (!$email) {
            error_log('[LTL-SAAS] Gumroad webhook: missing email, no-op (quick response)');
            return new WP_REST_Response(['ok' => true], 200); // Respond quickly
        }

        // Find or create user
        $user = get_user_by('email', $email);
        if (!$user) {
            // Create new user
            $user_login = sanitize_user(sanitize_email(explode('@', $email)[0]));
            $user_login = $this->ensure_unique_username($user_login);
            $user_pass = wp_generate_password(16, true, true);
            $user_id = wp_create_user($user_login, $user_pass, $email);
            if (is_wp_error($user_id)) {
                error_log('[LTL-SAAS] Failed to create user: ' . $user_id->get_error_message());
                return new WP_REST_Response(['ok' => true], 200); // Respond quickly
            }
            // Set user role to subscriber
            $new_user = get_user_by('ID', $user_id);
            if ($new_user) {
                $new_user->add_role('subscriber');
            }
            // Send welcome email
            $this->send_gumroad_welcome_email($email, $user_login, $user_pass);
            $user_id = $user_id;
        } else {
            $user_id = $user->ID;
        }

        // Determine plan from product_map
        $product_map = LTL_SAAS_Portal_Secrets::get_gumroad_product_map();
        $plan = isset($product_map[$product_id]) ? $product_map[$product_id] : 'starter';
        if ($product_id && !isset($product_map[$product_id])) {
            error_log('[LTL-SAAS] Gumroad webhook: unmapped product_id=' . $product_id . ', using default plan=starter');
        }

        // Issue #7: Refunded handling
        // sale/subscribe (refunded=false) → activate; cancel/refund (refunded=true) → deactivate
        $is_active = 1;
        $deactivated_reason = '';
        if ($refunded === 'true' || $refunded === '1') {
            $is_active = 0;
            $deactivated_reason = 'refunded';
        }

        // Upsert settings
        global $wpdb;
        $settings_table = $wpdb->prefix . 'ltl_saas_settings';
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $settings_table WHERE user_id = %d", $user_id));

        if ($existing) {
            // Update existing
            $wpdb->update(
                $settings_table,
                [
                    'plan' => $plan,
                    'is_active' => $is_active,
                    'updated_at' => current_time('mysql'),
                ],
                ['user_id' => $user_id]
            );
            error_log('[LTL-SAAS] Gumroad webhook: plan updated to=' . $plan . ', is_active=' . $is_active . ', user_id=' . $user_id);
        } else {
            // Insert new
            $wpdb->insert(
                $settings_table,
                [
                    'user_id' => $user_id,
                    'plan' => $plan,
                    'is_active' => $is_active,
                    'posts_this_month' => 0,
                    'posts_period_start' => date('Y-m-01'),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]
            );
            error_log('[LTL-SAAS] Gumroad webhook: new user created, plan=' . $plan . ', user_id=' . $user_id);
        }

        if ($subscription_id) {
            update_user_meta($user_id, 'gumroad_subscription_id', sanitize_text_field($subscription_id));
        }

        // Respond quickly
        return new WP_REST_Response(['ok' => true], 200);
    }

    /**
     * Ensure username is unique
     */
    private function ensure_unique_username($base_login) {
        $login = $base_login;
        $counter = 1;
        while (username_exists($login)) {
            $login = $base_login . $counter;
            $counter++;
        }
        return $login;
    }

    /**
     * Send welcome email to new Gumroad customer
     */
    private function send_gumroad_welcome_email($email, $user_login, $user_pass) {
        $subject = 'Willkommen bei LTL AutoBlog Cloud!';
        $login_url = wp_login_url();
        $reset_url = wp_lostpassword_url();
        $message = sprintf(
            'Hallo,\n\n' .
            'Dein Account wurde erfolgreich erstellt!\n\n' .
            'Login: %s\n' .
            'Benutzer: %s\n' .
            'Passwort: %s\n\n' .
            'Login URL: %s\n' .
            'Passwort zurücksetzen: %s\n\n' .
            'Viel Erfolg!\nLTL AutoBlog Cloud Team',
            get_bloginfo('name'),
            $user_login,
            $user_pass,
            $login_url,
            $reset_url
        );
        wp_mail($email, $subject, $message);
    }

    /**
     * Permission callback for /make/tenants: Token + SSL required
     */
    public function permission_make_tenants( $request ) {
        // === Issue #23: Rate limiting check ===
        $rate_limit_check = $this->check_rate_limit($request, 'make-tenants');
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check; // Will be converted to 429 response
        }

        if ( ! is_ssl() ) {
            $this->increment_rate_limit($request, 'make-tenants');
            return new WP_Error('forbidden', 'HTTPS required for secrets.', array('status' => 403));
        }
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal-secrets.php';
        $make_token = LTL_SAAS_Portal_Secrets::get_make_token();
        $header_token = $request->get_header('X-LTL-SAAS-TOKEN');
        if (!$header_token || !$make_token || !is_string($make_token) || !trim($make_token) || !hash_equals($make_token, $header_token)) {
            // Increment rate limit on failed auth
            $this->increment_rate_limit($request, 'make-tenants');
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

            // === Issue #22: Atomic month rollover ===
            $reset_happened = ltl_saas_atomic_month_rollover($u->user_id);
            if ($reset_happened) {
                // Re-fetch state after reset
                $state = ltl_saas_get_tenant_state($u->user_id);
            }

            // Enforce: skip if posts_used_month >= posts_limit_month
            $skip = false;
            $skip_reason = '';
            $remaining = $state['posts_limit_month'] - $state['posts_used_month'];
            if ($state['posts_used_month'] >= $state['posts_limit_month']) {
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
                'posts_used_month' => $state['posts_used_month'],     // Issue #8: Renamed for clarity
                'posts_limit_month' => $state['posts_limit_month'],
                'posts_remaining' => $state['posts_remaining'],       // Issue #8: Explicit remaining
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

        // === Issue #23: Rate limiting check ===
        $rate_limit_check = $this->check_rate_limit($request, 'run-callback');
        if (is_wp_error($rate_limit_check)) {
            return new WP_REST_Response(['error' => $rate_limit_check->get_error_message()], 429);
        }

        $api_key = LTL_SAAS_Portal_Secrets::get_api_key();
        $header_key = $request->get_header('X-LTL-API-Key');
        if (!$api_key || !$header_key || !hash_equals($api_key, $header_key)) {
            // Increment rate limit on failed auth
            $this->increment_rate_limit($request, 'run-callback');
            return new WP_REST_Response(['error' => 'Unauthorized'], 401);
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ltl_saas_runs';
        $settings_table = $wpdb->prefix . 'ltl_saas_settings';
        require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal.php';
        $params = $request->get_json_params();
        if (!is_array($params)) $params = [];

        $tenant_id = isset($params['tenant_id']) ? intval($params['tenant_id']) : 0;
        $execution_id = isset($params['execution_id']) ? sanitize_text_field($params['execution_id']) : '';
        $status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
        $started_at = isset($params['started_at']) ? sanitize_text_field($params['started_at']) : null;
        $finished_at = isset($params['finished_at']) ? sanitize_text_field($params['finished_at']) : null;
        $posts_created = isset($params['posts_created']) ? intval($params['posts_created']) : null;
        $error_message = isset($params['error_message']) ? sanitize_textarea_field($params['error_message']) : null;
        $meta = isset($params['meta']) ? wp_json_encode($params['meta']) : null;

        // Issue #17: Retry telemetry fields
        $attempts = isset($params['attempts']) ? intval($params['attempts']) : 1;
        $last_http_status = isset($params['last_http_status']) ? intval($params['last_http_status']) : null;
        $retry_backoff_ms = isset($params['retry_backoff_ms']) ? intval($params['retry_backoff_ms']) : 0;

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

        // === Idempotency Check: If execution_id is provided, check if already processed ===
        $already_processed = false;
        if ($execution_id) {
            $existing_run = $wpdb->get_row($wpdb->prepare(
                "SELECT id, status FROM $table WHERE execution_id = %s",
                $execution_id
            ));
            if ($existing_run) {
                // Already processed this execution - return success without double-incrementing
                error_log('[LTL-SAAS] Callback: idempotent re-send detected, execution_id=' . $execution_id . ', status=' . $existing_run->status);
                $already_processed = true;
                return new WP_REST_Response(['ok'=>true, 'id'=>$existing_run->id, 'idempotent'=>true], 200);
            }
        }

        // Insert run with retry telemetry
        $row = [
            'tenant_id' => $tenant_id,
            'execution_id' => $execution_id ?: null,
            'status' => $status,
            'started_at' => $started_at,
            'finished_at' => $finished_at,
            'posts_created' => $posts_created,
            'error_message' => $error_message,
            'raw_payload' => $raw_payload,
            'attempts' => $attempts,
            'last_http_status' => $last_http_status,
            'retry_backoff_ms' => $retry_backoff_ms,
            'created_at' => current_time('mysql'),
        ];
        $ok = $wpdb->insert($table, $row);

        // Log retry telemetry if attempts > 1
        if ($attempts > 1 && $last_http_status) {
            error_log('[LTL-SAAS] Callback: Retry telemetry - tenant_id=' . $tenant_id . ', status=' . $status . ', attempts=' . $attempts . ', last_http_status=' . $last_http_status . ', backoff_ms=' . $retry_backoff_ms);
        }

        // === Increment usage ONLY on first successful processing (not on idempotent re-sends) ===
        if ($ok && $status === 'success' && !$already_processed) {
            // Atomic month rollover (Issue #22)
            $reset_happened = ltl_saas_atomic_month_rollover($tenant_id);

            $settings_table = $wpdb->prefix . 'ltl_saas_settings';
            // Increment counter
            $wpdb->query($wpdb->prepare(
                "UPDATE $settings_table SET posts_this_month = posts_this_month + 1 WHERE user_id = %d",
                $tenant_id
            ));
            // Do NOT set is_active=0 if over limit; only limit enforcement applies
        }
        if ($ok) {
            return new WP_REST_Response(['ok' => true, 'id' => $wpdb->insert_id], 200);
        } else {
            return new WP_REST_Response(['error' => 'DB insert failed'], 500);
        }
    }

    /**
     * Issue #20: Permission check - user must be logged in
     */
    public function permission_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Issue #20: POST /test-connection
     * Test WordPress connection with provided credentials
     * Body: wp_url, wp_user, wp_app_password
     */
    public function test_wp_connection( $request ) {
        $params = $request->get_json_params();
        $wp_url = isset( $params['wp_url'] ) ? esc_url_raw( $params['wp_url'] ) : '';
        $wp_user = isset( $params['wp_user'] ) ? sanitize_user( $params['wp_user'] ) : '';
        $wp_pass = isset( $params['wp_app_password'] ) ? $params['wp_app_password'] : '';

        if ( ! $wp_url || ! $wp_user || ! $wp_pass ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Missing fields' ), 400 );
        }

        // Try to get /wp-json/wp/v2/users/me
        $api_url = rtrim( $wp_url, '/' ) . '/wp-json/wp/v2/users/me';
        $auth = base64_encode( $wp_user . ':' . $wp_pass );

        $response = wp_remote_get( $api_url, array(
            'headers' => array( 'Authorization' => 'Basic ' . $auth ),
            'timeout' => 5,
            'sslverify' => true,
        ) );

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            return array(
                'success' => true,
                'user' => isset( $body['name'] ) ? $body['name'] : 'OK',
            );
        } else {
            $error_msg = 'HTTP ' . $code;
            if ( is_wp_error( $response ) ) {
                $error_msg = $response->get_error_message();
            }
            return array(
                'success' => false,
                'message' => $error_msg,
            );
        }
    }

    /**
     * Issue #20: POST /test-rss
     * Test RSS feed validity and fetch first item title
     * Body: rss_url
     */
    public function test_rss_feed( $request ) {
        $params = $request->get_json_params();
        $rss_url = isset( $params['rss_url'] ) ? esc_url_raw( $params['rss_url'] ) : '';

        if ( ! $rss_url ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Missing RSS URL' ), 400 );
        }

        $response = wp_remote_get( $rss_url, array(
            'timeout' => 5,
            'sslverify' => true,
        ) );

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return array(
                'success' => false,
                'message' => 'HTTP ' . $code,
            );
        }

        $body = wp_remote_retrieve_body( $response );
        $xml = simplexml_load_string( $body );

        if ( $xml === false ) {
            return array(
                'success' => false,
                'message' => 'Invalid XML/RSS',
            );
        }

        // Try to get first item title
        $title = '';
        if ( isset( $xml->channel->item[0]->title ) ) {
            $title = (string) $xml->channel->item[0]->title;
        } elseif ( isset( $xml->entry[0]->title ) ) {
            $title = (string) $xml->entry[0]->title;
        }

        return array(
            'success' => true,
            'title' => substr( $title, 0, 50 ),
        );
    }

    public function health( $request ) {
        return array(
            'ok' => true,
            'plugin_version' => LTL_SAAS_PORTAL_VERSION,
            'timestamp' => gmdate('c'),
        );
    }
}
