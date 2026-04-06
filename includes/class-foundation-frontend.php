<?php
/**
 * Handles the frontend display shortcode and assets.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_Frontend {

	public function __construct() {
		add_shortcode( 'foundation_form', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		wp_register_style(
			'foundation-frontend-css',
			FOUNDATION_URL . 'assets/css/foundation-frontend.css',
			array(),
			FOUNDATION_VERSION
		);

		wp_register_script(
			'foundation-frontend-js',
			FOUNDATION_URL . 'assets/js/foundation-frontend.js',
			array( 'jquery' ),
			FOUNDATION_VERSION,
			true
		);
	}

	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'button' => 'true',
			),
			$atts
		);

		wp_enqueue_style( 'foundation-frontend-css' );
		wp_enqueue_script( 'foundation-frontend-js' );

		$form_data = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		$settings  = foundation_get_settings();

		wp_localize_script(
			'foundation-frontend-js',
			'foundationConfig',
			array(
				'formData' => $form_data,
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'foundation_nonce' ),
				'branding' => array(
					'launchButtonLabel' => $settings['launch_button_label'],
					'wizardTitle'       => $settings['wizard_title'],
					'currencySymbol'    => $settings['currency_symbol'],
					'logoUrl'           => $settings['logo_url'],
					'introImageUrl'     => $settings['intro_image_url'],
					'introHeading'      => $settings['intro_heading'],
					'introText'         => $settings['intro_text'],
					'testimonialImageUrl' => $settings['testimonial_image_url'],
					'testimonialHeading'  => $settings['testimonial_heading'],
					'testimonialQuote'    => $settings['testimonial_quote'],
					'testimonialAttribution' => $settings['testimonial_attribution'],
					'successMessage'       => $settings['success_message'],
				),
				'uploads' => array(
					'allowedTypes'    => foundation_parse_allowed_extensions( $settings ),
					'maxFileSizeMb'   => intval( $settings['max_file_size_mb'] ),
					'maxTotalSizeMb'  => intval( $settings['max_total_upload_mb'] ),
					'maxFilesPerField'=> intval( $settings['max_files_per_field'] ),
				),
			)
		);

		ob_start();
		?>
		<?php if ( 'false' !== $atts['button'] ) : ?>
			<div id="foundation-app-trigger-wrapper">
				<button id="foundation-launch-btn" class="foundation-btn-primary"><?php echo esc_html( $settings['launch_button_label'] ); ?></button>
			</div>
		<?php endif; ?>

		<div id="foundation-app-overlay" style="display:none;"></div>
		<?php
		return ob_get_clean();
	}
}
