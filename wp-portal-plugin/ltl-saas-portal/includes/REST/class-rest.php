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
