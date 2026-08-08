<?php
/**
 * Public shortcode, lazy loader and calculator configuration.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_Frontend {

	public function __construct() {
		add_shortcode( 'foundation_form', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_ajax_foundation_get_frontend_config', array( $this, 'ajax_get_frontend_config' ) );
		add_action( 'wp_ajax_nopriv_foundation_get_frontend_config', array( $this, 'ajax_get_frontend_config' ) );
	}

	public function register_assets() {
		wp_register_style( 'foundation-frontend-css', FOUNDATION_URL . 'assets/css/foundation-frontend.css', array(), FOUNDATION_VERSION );
		wp_register_script( 'foundation-frontend-js', FOUNDATION_URL . 'assets/js/foundation-frontend.js', array(), FOUNDATION_VERSION, true );
	}

	public function render_shortcode( $atts ) {
		static $overlay_printed = false;
		static $loader_printed  = false;
		static $instance        = 0;
		$instance++;

		$atts = shortcode_atts(
			array(
				'button'      => 'true',
				'label'       => '',
				'class'       => '',
			),
			$atts,
			'foundation_form'
		);
		$settings    = foundation_get_settings();
		$show_button = 'false' !== strtolower( (string) $atts['button'] );
		$label       = '' !== trim( (string) $atts['label'] ) ? sanitize_text_field( $atts['label'] ) : $settings['launch_button_label'];
		$class       = sanitize_html_class( (string) $atts['class'] );

		ob_start();
		if ( $show_button ) :
			wp_enqueue_style( 'foundation-frontend-css' );
			?>
			<div class="foundation-app-trigger-wrapper">
				<button id="foundation-launch-btn-<?php echo esc_attr( $instance ); ?>" class="foundation-btn-primary foundation-launch-btn <?php echo esc_attr( $class ); ?>" type="button" data-foundation-calculator-open><?php echo esc_html( $label ); ?></button>
			</div>
			<?php
		endif;

		if ( ! $overlay_printed ) :
			$overlay_printed = true;
			?><div id="foundation-app-overlay" hidden></div><?php
		endif;

		if ( ! $loader_printed ) :
			$loader_printed = true;
			$loader_config = $this->get_lazy_loader_config();
			?>
			<script data-cfasync="false">
			(function () {
				'use strict';
				if (window.FoundationProjectCalculatorLazy) return;
				var config = <?php echo wp_json_encode( $loader_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
				var loading = null;

				function getOverlay() {
					var overlay = document.getElementById('foundation-app-overlay');
					/*
					 * Elementor and some themes place shortcode output inside a stacking
					 * context or an overflow-clipped footer. A fixed child cannot escape
					 * that ancestor, regardless of its own z-index. Keep the application
					 * overlay as a direct body child so it is painted at viewport level.
					 */
					if (overlay && document.body && overlay.parentNode !== document.body) {
						document.body.appendChild(overlay);
					}
					return overlay;
				}
				function matchesTrigger(target) {
					if (!target || !target.closest) return null;
					return target.closest('[data-foundation-calculator-open], .foundation-trigger, .foundation-launch-btn, a[href*="foundation-form"], a[href*="get-quote"], a[href*="#foundation-launch-btn"]');
				}
				function loadCss() {
					if (document.getElementById('foundation-frontend-css-lazy') || document.getElementById('foundation-frontend-css-css') || document.querySelector('link[href*="/foundation-frontend.css"]')) return;
					var link = document.createElement('link');
					link.id = 'foundation-frontend-css-lazy';
					link.rel = 'stylesheet';
					link.href = config.cssUrl;
					document.head.appendChild(link);
				}
				function loadScript() {
					return new Promise(function (resolve, reject) {
						if (document.getElementById('foundation-frontend-js-lazy')) { resolve(); return; }
						var script = document.createElement('script');
						script.id = 'foundation-frontend-js-lazy';
						script.setAttribute('data-cfasync', 'false');
						script.src = config.jsUrl;
						script.defer = true;
						script.onload = resolve;
						script.onerror = reject;
						document.body.appendChild(script);
					});
				}
				function getBaseUrl() {
					return window.location.origin + window.location.pathname;
				}
				function fetchConfig() {
					var body = new URLSearchParams();
					body.set('action', 'foundation_get_frontend_config');
					body.set('page_url', getBaseUrl());
					return fetch(config.ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
						body: body.toString()
					}).then(function (response) {
						if (!response.ok) throw new Error('The calculator could not be loaded.');
						return response.json();
					}).then(function (payload) {
						if (!payload || !payload.success || !payload.data) throw new Error('The calculator configuration is unavailable.');
						window.foundationConfig = payload.data;
					});
				}
				function load(openAfterLoad) {
					getOverlay();
					if (openAfterLoad) window.foundationAutoOpen = true;
					if (!loading) {
						loadCss();
						loading = fetchConfig().then(loadScript).catch(function (error) {
							loading = null;
							throw error;
						});
					}
					return loading.then(function () {
						if (openAfterLoad) document.dispatchEvent(new CustomEvent('foundation:open'));
					});
				}
				function showLoadError(error) {
					var overlay = getOverlay();
					if (!overlay) return;
					var card = document.createElement('div');
					var heading = document.createElement('strong');
					var detail = document.createElement('span');
					var close = document.createElement('button');
					card.className = 'foundation-load-error';
					card.setAttribute('role', 'alert');
					heading.textContent = 'We could not open the calculator.';
					detail.textContent = String(error && error.message ? error.message : 'Please refresh and try again.');
					close.type = 'button';
					close.textContent = 'Close';
					close.addEventListener('click', function () { overlay.hidden = true; overlay.className = ''; overlay.textContent = ''; });
					card.appendChild(heading);
					card.appendChild(detail);
					card.appendChild(close);
					overlay.textContent = '';
					overlay.appendChild(card);
					overlay.hidden = false;
					overlay.className = 'foundation-overlay is-active';
				}
				getOverlay();
				document.addEventListener('click', function (event) {
					var trigger = matchesTrigger(event.target);
					if (!trigger) return;
					event.preventDefault();
					load(true).catch(showLoadError);
				}, true);
				window.FoundationProjectCalculatorLazy = { load: load };
				try {
					/*
					 * The calculator script owns the resume lifecycle. Opening here would race
					 * the asynchronous restore request and leave the introduction on screen.
					 */
					if (new URLSearchParams(window.location.search).has(config.resumeQueryParam || 'foundation_resume')) load(false).catch(showLoadError);
				} catch (error) {}
			}());
			</script>
			<?php
		endif;

		return ob_get_clean();
	}

	public function ajax_get_frontend_config() {
		nocache_headers();
		$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
		wp_send_json_success( $this->get_frontend_config( $page_url ) );
	}

	private function get_lazy_loader_config() {
		return array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'cssUrl'           => add_query_arg( 'ver', FOUNDATION_VERSION, FOUNDATION_URL . 'assets/css/foundation-frontend.css' ),
			'jsUrl'            => add_query_arg( 'ver', FOUNDATION_VERSION, FOUNDATION_URL . 'assets/js/foundation-frontend.js' ),
			'resumeQueryParam' => 'foundation_resume',
		);
	}

	private function get_frontend_config( $resume_base_url = '' ) {
		$form_data = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		$settings  = foundation_get_settings();
		$resume_base_url = foundation_normalize_resume_base_url( $resume_base_url );

		return array(
			'version'        => FOUNDATION_VERSION,
			'blueprintVersion'=> (string) get_option( 'foundation_blueprint_version', '' ),
			'formData'       => $form_data,
			'pricingCatalog' => foundation_get_pricing_catalog(),
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'foundation_nonce' ),
			'branding'       => array(
				'launchButtonLabel'      => $settings['launch_button_label'],
				'wizardTitle'            => $settings['wizard_title'],
				'currencySymbol'         => $settings['currency_symbol'],
				'vatNote'                => $settings['vat_note'],
				'estimateDisclaimer'     => $settings['estimate_disclaimer'],
				'showLiveSummary'        => ! empty( $settings['show_live_summary'] ),
				'phoneRequired'          => ! empty( $settings['phone_required'] ),
				'privacyConsentLabel'    => $settings['privacy_consent_label'],
				'privacyPolicyUrl'       => $settings['privacy_policy_url'],
				'logoUrl'                => $settings['logo_url'],
				'introImageUrl'          => $settings['intro_image_url'],
				'introHeading'           => $settings['intro_heading'],
				'introText'              => $settings['intro_text'],
				'testimonialImageUrl'    => $settings['testimonial_image_url'],
				'successImageUrl'        => $settings['success_image_url'],
				'testimonialHeading'     => $settings['testimonial_heading'],
				'testimonialQuote'       => $settings['testimonial_quote'],
				'testimonialAttribution' => $settings['testimonial_attribution'],
				'successMessage'         => $settings['success_message'],
				'quoteModeEnabled'       => foundation_is_quote_mode_enabled( $settings ),
			),
			'resume' => array(
				'baseUrl'       => esc_url_raw( $resume_base_url ),
				'queryParam'    => 'foundation_resume',
				'retentionDays' => intval( $settings['draft_retention_days'] ),
			),
			'uploads' => array(
				'allowedTypes'     => foundation_parse_allowed_extensions( $settings ),
				'maxFileSizeMb'    => intval( $settings['max_file_size_mb'] ),
				'maxTotalSizeMb'   => intval( $settings['max_total_upload_mb'] ),
				'maxFilesPerField' => intval( $settings['max_files_per_field'] ),
			),
			'retention' => array(
				'localDays' => intval( $settings['anonymous_local_retention_days'] ?? 14 ),
			),
			'leadCapture' => array(
				'enabled'           => ! empty( $settings['early_capture_enabled'] ),
				'marketingEnabled'  => ! empty( $settings['marketing_opt_in_enabled'] ),
				'marketingLabel'    => (string) ( $settings['marketing_opt_in_label'] ?? '' ),
			),
			'spam' => array(
				'turnstileEnabled' => ! empty( $settings['turnstile_enabled'] ) && ! empty( $settings['turnstile_site_key'] ) && ! empty( $settings['turnstile_secret_key'] ),
				'turnstileSiteKey' => ! empty( $settings['turnstile_enabled'] ) ? (string) $settings['turnstile_site_key'] : '',
				'minimumInteractionSeconds' => intval( $settings['minimum_interaction_seconds'] ?? 2 ),
			),
		);
	}

}
