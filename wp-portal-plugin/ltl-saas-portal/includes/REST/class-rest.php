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

        // TODO (#12): GET /active-users (Make auth)
        // TODO (#14): POST /run-callback
        register_rest_route( self::NAMESPACE, '/wp-connection/test', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'test_wp_connection' ),
            'permission_callback' => function() { return is_user_logged_in(); },
        ) );
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
