<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTL_SAAS_Portal_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    public function register_menu() {
        add_menu_page(
            'LTL AutoBlog Cloud',
            'LTL AutoBlog Cloud',
            'manage_options',
            'ltl-saas-portal',
            array( $this, 'render_admin_page' ),
            'dashicons-cloud',
            58
        );
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>LTL AutoBlog Cloud Portal</h1>';
        echo '<p>Admin-Seite (nur Owner). Kunden nutzen den Shortcode <code>[ltl_saas_dashboard]</code>.</p>';
        echo '</div>';
    }
}
