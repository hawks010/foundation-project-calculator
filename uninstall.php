<?php
/**
 * Fired when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'foundation_form_data' );
delete_option( 'foundation_form_settings' );

delete_option( 'foundation_form_metrics' );
