<?php
/**
 * Plugin Name: Foundation Project Calculator
 * Description: A lightweight, drag-and-drop quote calculator.
 * Version: 1.0.0
 * Author: Inkfire
 * Text Domain: foundation-customer-form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'FOUNDATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'FOUNDATION_URL', plugin_dir_url( __FILE__ ) );
define( 'FOUNDATION_VERSION', '1.0.0' );

// 1. Load the Admin Interface
require_once FOUNDATION_PATH . 'includes/class-foundation-admin.php';

// 2. Load the API (Handles Saving Data)
require_once FOUNDATION_PATH . 'includes/class-foundation-api.php';

// 3. Load the Frontend (The actual calculator)
require_once FOUNDATION_PATH . 'includes/class-foundation-frontend.php';

// 4. Load the Email Handler
require_once FOUNDATION_PATH . 'includes/foundation-email-handler.php';

/**
 * Initialize the Plugin
 */
function foundation_init() {
	new Foundation_Admin();
	new Foundation_API();
	new Foundation_Frontend();
}
add_action( 'plugins_loaded', 'foundation_init' );

