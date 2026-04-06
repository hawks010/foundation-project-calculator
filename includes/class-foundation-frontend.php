<?php
/**
 * Handles the Frontend Display (Shortcode)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_Frontend {

	public function __construct() {
		add_shortcode( 'foundation_form', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register Scripts & Styles
	 */
	public function enqueue_assets() {
		// Use time() during dev to bust cache, switch to '1.0.0' for production
		$version = time(); 

		// 1. The Styles
		wp_register_style(
			'foundation-frontend-css',
			FOUNDATION_URL . 'assets/css/foundation-frontend.css',
			array(),
			$version
		);

		// 2. The Logic
		wp_register_script(
			'foundation-frontend-js',
			FOUNDATION_URL . 'assets/js/foundation-frontend.js',
			array( 'jquery' ), 
			$version,
			true // Load in footer
		);
	}

	/**
	 * The Shortcode [foundation_form]
	 */
	public function render_shortcode( $atts ) {
		// Allow hiding the default button: [foundation_form button="false"]
		$atts = shortcode_atts( array(
			'button' => 'true',
		), $atts );

		wp_enqueue_style( 'foundation-frontend-css' );
		wp_enqueue_script( 'foundation-frontend-js' );

		// Load data saved by the Admin Builder
		$form_data = get_option( 'foundation_form_data', array() );

		// Pass data to JS
		wp_localize_script(
			'foundation-frontend-js',
			'foundationConfig',
			array(
				'formData' => $form_data,
				'currency' => '£',
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'foundation_nonce' ) // Critical for Email Handler
			)
		);

		ob_start();
		?>
		
		<?php if ( $atts['button'] !== 'false' ) : ?>
			<div id="foundation-app-trigger-wrapper">
				<button id="foundation-launch-btn" class="foundation-btn-primary">Get a Quote</button>
			</div>
		<?php endif; ?>

		<!-- The Overlay Container (JS will move this to body) -->
		<div id="foundation-app-overlay" style="display:none;">
			<!-- The JS builds the wizard layout inside here dynamically -->
		</div>
		<?php
		return ob_get_clean();
	}
}