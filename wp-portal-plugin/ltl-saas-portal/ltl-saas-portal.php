<?php
/**
 * Plugin Name: LTL AutoBlog Cloud Portal
 * Description: Customer portal for LTL AutoBlog Cloud (connect WP sites, store settings, and provide Make.com multi-tenant endpoints).
 * Version: 0.1.0
 * Author: LazyTechLab
 * Text Domain: ltl-saas-portal
 * Domain Path: /languages
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'LTL_SAAS_PORTAL_VERSION', '0.1.0' );
define( 'LTL_SAAS_PORTAL_PLUGIN_FILE', __FILE__ );
define( 'LTL_SAAS_PORTAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LTL_SAAS_PORTAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once LTL_SAAS_PORTAL_PLUGIN_DIR . 'includes/class-ltl-saas-portal.php';

function ltl_saas_portal() {
    return LTL_SAAS_Portal::instance();
}

/**
 * Load plugin text domain for translations
 */
function ltl_saas_portal_load_textdomain() {
    load_plugin_textdomain(
        'ltl-saas-portal',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'ltl_saas_portal_load_textdomain' );

register_activation_hook( __FILE__, array( 'LTL_SAAS_Portal', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LTL_SAAS_Portal', 'deactivate' ) );

add_action( 'plugins_loaded', 'ltl_saas_portal' );
