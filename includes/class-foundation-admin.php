<?php
/**
 * Handles the React admin dashboard and builder shell.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_admin_menu() {
		global $admin_page_hooks;
		$parent_slug = 'foundation-by-inkfire';

		if ( empty( $admin_page_hooks[ $parent_slug ] ) ) {
			add_menu_page(
				__( 'Foundation', 'foundation-customer-form' ),
				__( 'Foundation', 'foundation-customer-form' ),
				'manage_options',
				$parent_slug,
				null,
				'dashicons-hammer',
				12
			);
			remove_submenu_page( $parent_slug, $parent_slug );
		}

		add_submenu_page(
			$parent_slug,
			__( 'Foundation Form', 'foundation-customer-form' ),
			__( 'Project Calculator', 'foundation-customer-form' ),
			'manage_options',
			'foundation-form-builder',
			array( $this, 'render_admin_page' )
		);
	}

	private function get_dashboard_data() {
		$metrics = foundation_get_metrics();
		$views   = max( 0, intval( $metrics['form_views'] ?? 0 ) );
		$starts  = max( 0, intval( $metrics['form_starts'] ?? 0 ) );
		$complete = max( 0, intval( $metrics['responses_saved'] ?? 0 ) );
		$saved   = max( 0, intval( $metrics['saved_drafts'] ?? 0 ) );
		$incomplete = max( 0, intval( $metrics['incomplete'] ?? 0 ) );
		$failures = max( 0, intval( $metrics['failures'] ?? 0 ) );
		$completion_rate = $starts > 0 ? round( ( $complete / $starts ) * 100, 1 ) : 0;
		$save_rate = $starts > 0 ? round( ( $saved / $starts ) * 100, 1 ) : 0;

		return array(
			'metrics'          => array(
				array(
					'label' => __( 'Form views', 'foundation-customer-form' ),
					'value' => number_format_i18n( $views ),
					'note'  => __( 'People who saw the calculator', 'foundation-customer-form' ),
				),
				array(
					'label' => __( 'Started', 'foundation-customer-form' ),
					'value' => number_format_i18n( $starts ),
					'note'  => __( 'Opened step one', 'foundation-customer-form' ),
				),
				array(
					'label' => __( 'Responses saved', 'foundation-customer-form' ),
					'value' => number_format_i18n( $complete ),
					'note'  => sprintf( __( '%s%% completion rate', 'foundation-customer-form' ), number_format_i18n( $completion_rate, 1 ) ),
				),
				array(
					'label' => __( 'Saved drafts', 'foundation-customer-form' ),
					'value' => number_format_i18n( $saved ),
					'note'  => sprintf( __( '%s%% used resume later', 'foundation-customer-form' ), number_format_i18n( $save_rate, 1 ) ),
				),
				array(
					'label' => __( 'Incomplete', 'foundation-customer-form' ),
					'value' => number_format_i18n( $incomplete ),
					'note'  => __( 'Journey closed before submit', 'foundation-customer-form' ),
				),
				array(
					'label' => __( 'Failures', 'foundation-customer-form' ),
					'value' => number_format_i18n( $failures ),
					'note'  => __( 'Errors or blocked sends', 'foundation-customer-form' ),
				),
			),
			'journey'          => array(
				array(
					'label'   => __( 'Viewed calculator', 'foundation-customer-form' ),
					'value'   => number_format_i18n( $views ),
					'percent' => '100%',
				),
				array(
					'label'   => __( 'Started step one', 'foundation-customer-form' ),
					'value'   => number_format_i18n( $starts ),
					'percent' => $views > 0 ? number_format_i18n( ( $starts / $views ) * 100, 1 ) . '%' : '0%',
				),
				array(
					'label'   => __( 'Saved for later', 'foundation-customer-form' ),
					'value'   => number_format_i18n( $saved ),
					'percent' => $starts > 0 ? number_format_i18n( ( $saved / $starts ) * 100, 1 ) . '%' : '0%',
				),
				array(
					'label'   => __( 'Completed submission', 'foundation-customer-form' ),
					'value'   => number_format_i18n( $complete ),
					'percent' => $starts > 0 ? number_format_i18n( ( $complete / $starts ) * 100, 1 ) . '%' : '0%',
				),
			),
			'last_failure'     => $metrics['last_failure'] ?? '',
			'last_saved_draft' => $metrics['last_saved_draft'] ?? '',
		);
	}

	public function render_admin_page() {
		?>
		<div class="wrap foundation-admin-wrap">
			<div id="foundation-admin-app">
				<p><?php esc_html_e( 'Loading Foundation builder...', 'foundation-customer-form' ); ?></p>
			</div>
		</div>
		<?php
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'foundation-form-builder' ) ) {
			return;
		}

		$css_path = FOUNDATION_PATH . 'assets/admin/admin-app.css';
		$js_path  = FOUNDATION_PATH . 'assets/admin/admin-app.js';
		$css_url  = FOUNDATION_URL . 'assets/admin/admin-app.css';
		$js_url   = FOUNDATION_URL . 'assets/admin/admin-app.js';
		$version  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : FOUNDATION_VERSION;

		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'foundation-admin-app',
				$css_url,
				array(),
				file_exists( $css_path ) ? (string) filemtime( $css_path ) : $version
			);
		}

		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'foundation-admin-app',
				$js_url,
				array(),
				$version,
				true
			);
			wp_script_add_data( 'foundation-admin-app', 'type', 'module' );
		}

		wp_localize_script(
			'foundation-admin-app',
			'foundationData',
			array(
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'apiUrl'        => rest_url( 'foundation/v1/' ),
				'settingsUrl'   => admin_url( 'admin.php?page=foundation-form-settings' ),
				'pluginVersion' => FOUNDATION_VERSION,
				'logoUrl'       => FOUNDATION_URL . 'assets/IMG_1089.png',
				'dashboard'     => $this->get_dashboard_data(),
			)
		);
	}
}
