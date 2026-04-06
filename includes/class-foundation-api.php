<?php
/**
 * Handles saving and loading builder data via REST API.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( 'foundation/v1', '/save', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'save_form_data' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'foundation/v1', '/get', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_form_data' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function save_form_data( $request ) {
		$data = $request->get_param( 'form_data' );
		$normalized = foundation_normalize_form_data( $data );
		update_option( 'foundation_form_data', $normalized );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Form saved successfully.', 'foundation-customer-form' ),
			),
			200
		);
	}

	public function get_form_data() {
		$data = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		return new WP_REST_Response( $data, 200 );
	}

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}
}
