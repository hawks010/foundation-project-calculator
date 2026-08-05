<?php
/**
 * Plugin Name: Foundation Project Calculator
 * Plugin URI: https://github.com/Inkfire-limited/foundation-project-calculator
 * Description: A guided, accessible project calculator with Inkfire pricing, one-off and monthly estimates, tailored-quote routing, secure lead capture and a simple admin dashboard.
 * Version: 1.4.0
 * Author: Inkfire
 * Text Domain: foundation-customer-form
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Update URI: https://github.com/Inkfire-limited/foundation-project-calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FOUNDATION_FILE', __FILE__ );
define( 'FOUNDATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'FOUNDATION_URL', plugin_dir_url( __FILE__ ) );
define( 'FOUNDATION_VERSION', '1.4.0' );
define( 'FOUNDATION_DB_VERSION', '1.4.0' );

require_once FOUNDATION_PATH . 'includes/foundation-core.php';
require_once FOUNDATION_PATH . 'includes/foundation-pricing.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-admin.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-settings.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-api.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-frontend.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-submissions.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-github-updater.php';
require_once FOUNDATION_PATH . 'includes/foundation-email-handler.php';

/**
 * Determine whether the current journey is only the empty placeholder created by
 * older releases. Existing customer journeys are never overwritten silently.
 */
function foundation_form_is_empty_or_placeholder( $form_data ) {
	if ( empty( $form_data ) || ! is_array( $form_data ) ) {
		return true;
	}
	$steps = foundation_normalize_form_data( $form_data );
	return 1 === count( $steps ) && empty( $steps[0]['fields'] );
}

/**
 * Register options and perform a safe, idempotent upgrade.
 */
function foundation_run_upgrade() {
	foundation_register_default_settings();
	foundation_register_default_metrics();
	foundation_register_default_pricing_catalog();

	$existing = get_option( 'foundation_form_data', array() );
	if ( foundation_form_is_empty_or_placeholder( $existing ) ) {
		foundation_apply_inkfire_blueprint( false );
	}

	Foundation_Submissions::schedule_cleanup();
	update_option( 'foundation_db_version', FOUNDATION_DB_VERSION, false );
}

/**
 * Activation uses the bundled Inkfire journey only when no real journey exists.
 */
function foundation_activate() {
	foundation_run_upgrade();
}
register_activation_hook( __FILE__, 'foundation_activate' );

function foundation_deactivate() {
	Foundation_Submissions::unschedule_cleanup();
}
register_deactivation_hook( __FILE__, 'foundation_deactivate' );

/**
 * Initialise the plugin.
 */
function foundation_init() {
	load_plugin_textdomain( 'foundation-customer-form', false, dirname( plugin_basename( FOUNDATION_FILE ) ) . '/languages' );

	if ( FOUNDATION_DB_VERSION !== (string) get_option( 'foundation_db_version', '' ) ) {
		foundation_run_upgrade();
	} else {
		foundation_register_default_settings();
		foundation_register_default_metrics();
		foundation_register_default_pricing_catalog();
	}

	Foundation_Github_Updater::instance();
	new Foundation_Admin();
	new Foundation_Settings();
	new Foundation_API();
	new Foundation_Frontend();
	new Foundation_Submissions();
}
add_action( 'plugins_loaded', 'foundation_init' );
