<?php
/**
 * Handles Saving & Loading Data via REST API
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Create the API Endpoints
	 * URL: /wp-json/foundation/v1/save
	 */
	public function register_routes() {
		register_rest_route( 'foundation/v1', '/save', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'save_form_data' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'foundation/v1', '/get', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_form_data' ),
			'permission_callback' => '__return_true', // Frontend needs to read this, so public is fine
		) );
	}

	/**
	 * Save the Drag & Drop Data
	 */
	public function save_form_data( $request ) {
		$data = $request->get_param( 'form_data' );

		// Sanitize logic could go here, but since we are storing a complex JSON object 
		// for internal use, we update the option directly.
		// UPDATED: key changed to 'foundation_form_data' to match Frontend & Email Handler
		update_option( 'foundation_form_data', $data );

		return new WP_REST_Response( array( 'success' => true, 'message' => 'Form saved successfully!' ), 200 );
	}

	/**
	 * Retrieve Data for the Frontend
	 */
	public function get_form_data() {
		// UPDATED: key changed to 'foundation_form_data' to match Frontend & Email Handler
		$data = get_option( 'foundation_form_data', array() );
		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Security Check: Only Admins can save
	 */
	public function check_permission() {
		return current_user_can( 'manage_options' );
	}
}