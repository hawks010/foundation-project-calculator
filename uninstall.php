<?php
/**
 * Remove calculator data when an administrator deletes the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

wp_clear_scheduled_hook( 'foundation_cleanup_old_submissions' );

foreach (
	array(
		'foundation_form_data',
		'foundation_form_settings',
		'foundation_form_metrics',
		'foundation_pricing_catalog',
		'foundation_form_data_backup',
		'foundation_blueprint_version',
		'foundation_blueprint_applied_at',
		'foundation_db_version',
	)
	as $option_name
) {
	delete_option( $option_name );
}

// The enquiry and brief post types are deliberately hidden, so remove them in
// bounded batches without relying on the post types being registered during
// uninstall.
global $wpdb;
foreach ( array( 'foundation_quote', 'foundation_brief' ) as $foundation_post_type ) {
	do {
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s ORDER BY ID ASC LIMIT 500",
				$foundation_post_type
			)
		);
		foreach ( $post_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
	} while ( count( $post_ids ) === 500 );
}

// Clear expiring drafts, rate-limit buckets, idempotency caches and stale locks.
$option_prefixes = array(
	'_transient_foundation_quote_draft_',
	'_transient_timeout_foundation_quote_draft_',
	'_transient_foundation_submission_',
	'_transient_timeout_foundation_submission_',
	'_transient_fpc_rl_',
	'_transient_timeout_fpc_rl_',
	'_transient_fpc_rev_',
	'_transient_timeout_fpc_rev_',
	'_transient_foundation_admin_magic_',
	'_transient_timeout_foundation_admin_magic_',
	'foundation_submission_lock_',
);
foreach ( $option_prefixes as $prefix ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $prefix ) . '%'
		)
	);
}
