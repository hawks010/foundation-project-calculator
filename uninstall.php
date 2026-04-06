<?php
/**
 * Fired when the plugin is deleted.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete the main form data
delete_option( 'foundation_form_data' );

// Delete legacy data key (cleanup)
delete_option( 'foundation_form_structure' );