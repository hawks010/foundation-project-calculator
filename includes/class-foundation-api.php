<?php
/**
 * Backwards-compatible REST API for configuration exports and legacy tooling.
 * The normal 1.4 admin screen uses nonce-protected admin-post handlers.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'foundation/v1',
			'/save',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_form_data' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
		register_rest_route(
			'foundation/v1',
			'/get',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_form_data' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
		register_rest_route(
			'foundation/v1',
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
		register_rest_route(
			'foundation/v1',
			'/pricing',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_pricing' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_pricing' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
		register_rest_route(
			'foundation/v1',
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_health' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	public function save_form_data( $request ) {
		$data = $request->get_param( 'form_data' );
		if ( ! is_array( $data ) || count( $data ) > 200 ) {
			return new WP_Error( 'foundation_invalid_form', __( 'The journey payload is not valid.', 'foundation-customer-form' ), array( 'status' => 400 ) );
		}
		$current = get_option( 'foundation_form_data', array() );
		if ( is_array( $current ) && ! empty( $current ) ) {
			update_option(
				'foundation_form_data_backup',
				array(
					'created_at' => current_time( 'mysql' ),
					'version'    => get_option( 'foundation_blueprint_version', '' ),
					'form_data'  => $current,
				),
				false
			);
		}
		$normalized = foundation_normalize_form_data( $data );
		update_option( 'foundation_form_data', $normalized, false );
		update_option( 'foundation_blueprint_version', '', false );
		return new WP_REST_Response( array( 'success' => true, 'message' => __( 'Journey saved successfully.', 'foundation-customer-form' ) ), 200 );
	}

	public function get_form_data() {
		return new WP_REST_Response( foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) ), 200 );
	}

	public function get_settings() {
		return new WP_REST_Response( foundation_get_settings(), 200 );
	}

	public function save_settings( $request ) {
		$data     = $request->get_param( 'settings' );
		$current  = foundation_get_settings();
		$settings = foundation_sanitize_settings( array_merge( $current, is_array( $data ) ? $data : array() ) );
		update_option( 'foundation_form_settings', $settings, false );
		return new WP_REST_Response( array( 'success' => true, 'message' => __( 'Settings saved successfully.', 'foundation-customer-form' ), 'settings' => foundation_get_settings() ), 200 );
	}

	public function get_pricing() {
		return new WP_REST_Response( foundation_get_pricing_catalog(), 200 );
	}

	public function save_pricing( $request ) {
		$data    = $request->get_param( 'pricing' );
		$current = foundation_get_pricing_catalog();
		$pricing = foundation_sanitize_pricing_catalog( array_merge( $current, is_array( $data ) ? $data : array() ) );
		update_option( 'foundation_pricing_catalog', $pricing, false );
		return new WP_REST_Response( array( 'success' => true, 'pricing' => $pricing ), 200 );
	}

	public function get_health() {
		return new WP_REST_Response( foundation_get_blueprint_health(), 200 );
	}

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}
}
