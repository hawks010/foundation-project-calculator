<?php
/**
 * Plugin Name: Foundation Project Calculator
 * Description: A multi-step project calculator and lead capture tool with branded customer emails, upload packaging, and an accessible builder.
 * Version: 1.1.0
 * Author: Inkfire
 * Text Domain: foundation-customer-form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FOUNDATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'FOUNDATION_URL', plugin_dir_url( __FILE__ ) );
define( 'FOUNDATION_VERSION', '1.1.0' );

require_once FOUNDATION_PATH . 'includes/foundation-core.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-admin.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-settings.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-api.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-frontend.php';
require_once FOUNDATION_PATH . 'includes/foundation-email-handler.php';

/**
 * Activation: set safe defaults.
 */
function foundation_activate() {
	foundation_register_default_settings();
	$existing = get_option( 'foundation_form_data', array() );
	if ( empty( $existing ) || ! is_array( $existing ) ) {
		update_option( 'foundation_form_data', foundation_normalize_form_data( array() ) );
	}
}
register_activation_hook( __FILE__, 'foundation_activate' );

/**
 * Initialize the plugin.
 */
function foundation_init() {
	foundation_register_default_settings();
	new Foundation_Admin();
	new Foundation_Settings();
	new Foundation_API();
	new Foundation_Frontend();
}
add_action( 'plugins_loaded', 'foundation_init' );
