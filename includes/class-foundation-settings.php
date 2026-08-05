<?php
/**
 * Register the calculator settings option.
 *
 * The visible controls live in the main server-rendered calculator dashboard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_Settings {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		register_setting(
			'foundation_form_settings_group',
			'foundation_form_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'foundation_sanitize_settings',
				'default'           => foundation_get_default_settings(),
			)
		);
	}
}
