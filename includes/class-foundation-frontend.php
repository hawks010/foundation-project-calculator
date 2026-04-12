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
		add_action( 'wp_ajax_foundation_get_frontend_config', array( $this, 'ajax_get_frontend_config' ) );
		add_action( 'wp_ajax_nopriv_foundation_get_frontend_config', array( $this, 'ajax_get_frontend_config' ) );
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
		static $loader_printed = false;

		$atts = shortcode_atts(
			array(
				'button' => 'true',
			),
			$atts
		);

		$settings  = foundation_get_settings();
		$show_button = 'false' !== strtolower( (string) $atts['button'] );

		if ( $show_button ) {
			wp_enqueue_style( 'foundation-frontend-css' );
		}

		ob_start();
		?>
		<?php if ( $show_button ) : ?>
			<div id="foundation-app-trigger-wrapper">
				<button id="foundation-launch-btn" class="foundation-btn-primary" type="button"><?php echo esc_html( $settings['launch_button_label'] ); ?></button>
			</div>
		<?php endif; ?>

		<div id="foundation-app-overlay" style="display:none;"></div>
		<?php if ( ! $loader_printed ) : ?>
			<?php $loader_printed = true; ?>
			<script>
			(function() {
				if (window.FoundationProjectCalculatorLazy) {
					return;
				}

				var config = <?php echo wp_json_encode( $this->get_lazy_loader_config() ); ?>;
				var loadPromise = null;

				function closestTrigger(target) {
					if (!target || !target.closest) {
						return null;
					}
					return target.closest('.foundation-trigger, #foundation-launch-btn, a[href*="foundation-form"], a[href*="get-quote"], a[href="#foundation-launch-btn"]');
				}

				function loadCss() {
					if (document.getElementById('foundation-frontend-css-lazy')) {
						return;
					}
					var link = document.createElement('link');
					link.id = 'foundation-frontend-css-lazy';
					link.rel = 'stylesheet';
					link.href = config.cssUrl;
					document.head.appendChild(link);
				}

				function loadScript(src, id) {
					return new Promise(function(resolve, reject) {
						if (id && document.getElementById(id)) {
							resolve();
							return;
						}
						var script = document.createElement('script');
						if (id) {
							script.id = id;
						}
						script.src = src;
						script.async = false;
						script.onload = resolve;
						script.onerror = reject;
						document.body.appendChild(script);
					});
				}

				function getPageBaseUrl() {
					return window.location.origin + window.location.pathname;
				}

				function fetchFrontendConfig() {
					var body = new URLSearchParams();
					body.set('action', 'foundation_get_frontend_config');
					body.set('page_url', getPageBaseUrl());

					return fetch(config.ajaxUrl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						body: body.toString()
					})
						.then(function(response) { return response.json(); })
						.then(function(payload) {
							if (!payload || !payload.success || !payload.data) {
								throw new Error('Project form configuration could not be loaded.');
							}
							window.foundationConfig = payload.data;
						});
				}

				function ensureJquery() {
					if (window.jQuery) {
						return Promise.resolve();
					}
					return loadScript(config.jqueryUrl, 'jquery-core');
				}

				function loadApp(openAfterLoad) {
					if (openAfterLoad) {
						window.foundationAutoOpen = true;
					}

					if (!loadPromise) {
						loadCss();
						loadPromise = ensureJquery()
							.then(fetchFrontendConfig)
							.then(function() {
								return loadScript(config.jsUrl, 'foundation-frontend-js-lazy');
							});
					}

					return loadPromise.then(function() {
						if (openAfterLoad) {
							document.dispatchEvent(new CustomEvent('foundation:open'));
						}
					});
				}

				function hasResumeToken() {
					try {
						return new URLSearchParams(window.location.search).has(config.resumeQueryParam || 'foundation_resume');
					} catch (error) {
						return false;
					}
				}

				document.addEventListener('click', function(event) {
					var trigger = closestTrigger(event.target);
					if (!trigger) {
						return;
					}
					event.preventDefault();
					event.stopPropagation();
					loadApp(true).catch(function(error) {
						if (window.console && console.warn) {
							console.warn(error);
						}
					});
				}, true);

				window.FoundationProjectCalculatorLazy = {
					load: loadApp
				};

				if (hasResumeToken()) {
					loadApp(true).catch(function(error) {
						if (window.console && console.warn) {
							console.warn(error);
						}
					});
				}
			})();
			</script>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	public function ajax_get_frontend_config() {
		$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

		wp_send_json_success( $this->get_frontend_config( $page_url ) );
	}

	private function get_lazy_loader_config() {
		return array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'cssUrl'           => add_query_arg( 'ver', FOUNDATION_VERSION, FOUNDATION_URL . 'assets/css/foundation-frontend.css' ),
			'jsUrl'            => add_query_arg( 'ver', FOUNDATION_VERSION, FOUNDATION_URL . 'assets/js/foundation-frontend.js' ),
			'jqueryUrl'        => includes_url( 'js/jquery/jquery.min.js' ),
			'resumeQueryParam' => 'foundation_resume',
		);
	}

	private function get_frontend_config( $resume_base_url = '' ) {
		$form_data = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		$settings  = foundation_get_settings();

		if ( empty( $resume_base_url ) || ! $this->is_same_site_url( $resume_base_url ) ) {
			$resume_base_url = home_url( '/' );
		}

		return array(
			'formData' => $form_data,
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'foundation_nonce' ),
			'branding' => array(
				'launchButtonLabel'      => $settings['launch_button_label'],
				'wizardTitle'            => $settings['wizard_title'],
				'currencySymbol'         => $settings['currency_symbol'],
				'logoUrl'                => $settings['logo_url'],
				'introImageUrl'          => $settings['intro_image_url'],
				'introHeading'           => $settings['intro_heading'],
				'introText'              => $settings['intro_text'],
				'testimonialImageUrl'    => $settings['testimonial_image_url'],
					'testimonialHeading'     => $settings['testimonial_heading'],
					'testimonialQuote'       => $settings['testimonial_quote'],
					'testimonialAttribution' => $settings['testimonial_attribution'],
					'successMessage'         => $settings['success_message'],
					'quoteModeEnabled'       => foundation_is_quote_mode_enabled( $settings ),
				),
			'resume' => array(
				'baseUrl'    => esc_url_raw( $resume_base_url ),
				'queryParam' => 'foundation_resume',
			),
			'uploads' => array(
				'allowedTypes'     => foundation_parse_allowed_extensions( $settings ),
				'maxFileSizeMb'    => intval( $settings['max_file_size_mb'] ),
				'maxTotalSizeMb'   => intval( $settings['max_total_upload_mb'] ),
				'maxFilesPerField' => intval( $settings['max_files_per_field'] ),
			),
		);
	}

	private function is_same_site_url( $url ) {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$url_host  = wp_parse_url( $url, PHP_URL_HOST );

		return $site_host && $url_host && strtolower( $site_host ) === strtolower( $url_host );
	}
}
