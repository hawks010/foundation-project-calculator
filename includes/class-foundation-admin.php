<?php
/**
 * Handles the admin builder screen.
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

	public function render_admin_page() {
		$version  = FOUNDATION_VERSION;
		$logo_url = FOUNDATION_URL . 'assets/IMG_1089.png';
		?>
		<div id="fp-dashboard-wrapper" class="wrap">
			<div class="fp-header">
				<div class="fp-title-group">
					<img src="<?php echo esc_url( $logo_url ); ?>" class="fp-logo" alt="Inkfire" />
					<div class="fp-branding-text">
						<h1><?php esc_html_e( 'Foundation: Project Calculator', 'foundation-customer-form' ); ?></h1>
						<p class="fp-byline"><?php esc_html_e( 'Build the quote flow here. Email, file and branding settings live in the companion settings page.', 'foundation-customer-form' ); ?></p>
					</div>
				</div>
				<div class="fp-theme-toggle">
					<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=foundation-form-settings' ) ); ?>"><?php esc_html_e( 'Open settings', 'foundation-customer-form' ); ?></a>
					<label for="fnd-dark-switch" class="screen-reader-text"><?php esc_html_e( 'Toggle dark mode', 'foundation-customer-form' ); ?></label>
					<label class="fp-switch">
						<input type="checkbox" id="fnd-dark-switch" />
						<span class="fp-toggle-slider"></span>
					</label>
					<span class="fp-badge">v<?php echo esc_html( $version ); ?></span>
				</div>
			</div>

			<div class="fp-quickstart" role="note" aria-label="Quick start guide">
				<div class="fp-quickstart-card">
					<strong>1. Build the screens</strong>
					<span>Add screens, titles, questions and prices.</span>
				</div>
				<div class="fp-quickstart-card">
					<strong>2. Open settings</strong>
					<span>Set admin email, branding, uploads and customer copy.</span>
				</div>
				<div class="fp-quickstart-card">
					<strong>3. Save and test</strong>
					<span>Send a test enquiry from the frontend before publishing changes.</span>
				</div>
			</div>

			<div class="fp-card-builder-frame" style="margin-top:20px;">
				<div id="foundation-admin-app"></div>
			</div>
		</div>

		<script>
		(function(){
			const wrap = document.getElementById('fp-dashboard-wrapper');
			const sw = document.getElementById('fnd-dark-switch');
			const storedTheme = localStorage.getItem('foundation_admin_theme');
			if (storedTheme === 'dark') {
				wrap.classList.add('fp-dark-mode');
				if (sw) sw.checked = true;
			}
			if (sw && wrap) {
				sw.addEventListener('change', function() {
					if (this.checked) {
						wrap.classList.add('fp-dark-mode');
						localStorage.setItem('foundation_admin_theme', 'dark');
					} else {
						wrap.classList.remove('fp-dark-mode');
						localStorage.setItem('foundation_admin_theme', 'light');
					}
				});
			}
		})();
		</script>
		<?php
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'foundation-form-builder' ) ) {
			return;
		}

		wp_enqueue_style(
			'foundation-admin-css',
			FOUNDATION_URL . 'assets/css/foundation-builder.css',
			array(),
			FOUNDATION_VERSION
		);

		$dashboard_css = '
		#fp-dashboard-wrapper { margin: 35px 20px 0 0; padding: 35px; background: #ffffff; border-radius: 35px; max-width: unset; box-sizing: border-box; transition: background 0.3s, color 0.3s; }
		#fp-dashboard-wrapper .fp-header { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin: 0 0 12px; }
		#fp-dashboard-wrapper .fp-title-group { display: flex; align-items: center; gap: 15px; }
		#fp-dashboard-wrapper .fp-logo { height: 80px; width: auto; margin-bottom: -10px; margin-right: -10px; display: block; }
		#fp-dashboard-wrapper .fp-branding-text h1 { margin: 0; font-size: 1.45rem; color: #1f2937; line-height: 1.2; }
		#fp-dashboard-wrapper .fp-byline { margin: 2px 0 0; color: #4b5563; font-size: 14px; max-width: 720px; }
		#fp-dashboard-wrapper .fp-badge { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .75rem; background: #212121; color: #fff; margin-left: 10px; }
		#fp-dashboard-wrapper .fp-theme-toggle { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
		#fp-dashboard-wrapper .fp-quickstart { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap: 16px; margin-top: 14px; }
		#fp-dashboard-wrapper .fp-quickstart-card { background: linear-gradient(180deg, #ffffff, #f8fafc); border: 1px solid rgba(15,23,42,0.08); border-radius: 22px; padding: 18px 20px; box-shadow: 0 10px 24px rgba(15,23,42,0.05); }
		#fp-dashboard-wrapper .fp-quickstart-card strong { display: block; margin-bottom: 8px; font-size: 15px; color: #111827; }
		#fp-dashboard-wrapper .fp-quickstart-card span { color: #475569; font-size: 14px; line-height: 1.45; }
		#fp-dashboard-wrapper .fp-switch { position: relative; display: inline-flex; align-items: center; gap: .6rem; cursor: pointer; }
		#fp-dashboard-wrapper .fp-switch input { position: absolute!important; opacity: 0!important; width: 1px; height: 1px; margin: 0; pointer-events: none; }
		#fp-dashboard-wrapper .fp-toggle-slider { position: relative; width: 42px; height: 24px; border-radius: 999px; background: #cfd3d8; transition: background .2s; }
		#fp-dashboard-wrapper .fp-toggle-slider:after { content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 999px; background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: transform .2s; }
		#fp-dashboard-wrapper .fp-switch input:checked + .fp-toggle-slider { background: #07a079; }
		#fp-dashboard-wrapper .fp-switch input:checked + .fp-toggle-slider:after { transform: translateX(18px); }
		#foundation-admin-app { width: 100%; min-height: 500px; }
		#fp-dashboard-wrapper.fp-dark-mode { background: #1a1b1e !important; color: #f5f7fb !important; }
		#fp-dashboard-wrapper.fp-dark-mode .fp-branding-text h1 { color: #FBCCBF !important; }
		#fp-dashboard-wrapper.fp-dark-mode .fp-byline, #fp-dashboard-wrapper.fp-dark-mode .fp-quickstart-card span { color: #9CA3AF !important; }
		#fp-dashboard-wrapper.fp-dark-mode .fp-quickstart-card { background: #1F2033 !important; border-color: #2D2E45 !important; box-shadow: none !important; }
		#fp-dashboard-wrapper.fp-dark-mode .fp-quickstart-card strong { color: #fff !important; }
		#fp-dashboard-wrapper.fp-dark-mode .fp-header { border-bottom: 1px solid #2D2E45 !important; padding-bottom: 20px; margin-bottom: 0; }
		#fp-dashboard-wrapper.fp-dark-mode .builder-col { background: #1F2033 !important; border-color: #2D2E45 !important; color: #E5E7EB; }
		#fp-dashboard-wrapper.fp-dark-mode .builder-col h3,
		#fp-dashboard-wrapper.fp-dark-mode .builder-col h4,
		#fp-dashboard-wrapper.fp-dark-mode .tool-info strong,
		#fp-dashboard-wrapper.fp-dark-mode .field-type-label,
		#fp-dashboard-wrapper.fp-dark-mode .preview-label { color: #fff !important; }
		#fp-dashboard-wrapper.fp-dark-mode .builder-col.canvas, #fp-dashboard-wrapper.fp-dark-mode .settings-group[style*="background"] { background: #111827 !important; border-color: #1f2937 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .step-manager-bar, #fp-dashboard-wrapper.fp-dark-mode .settings-group, #fp-dashboard-wrapper.fp-dark-mode .inline-routing-editor { background: #1F2033 !important; border-color: #2D2E45 !important; color: #E5E7EB !important; }
		#fp-dashboard-wrapper.fp-dark-mode .draggable-item, #fp-dashboard-wrapper.fp-dark-mode input[type=text], #fp-dashboard-wrapper.fp-dark-mode input[type=number], #fp-dashboard-wrapper.fp-dark-mode input[type=email], #fp-dashboard-wrapper.fp-dark-mode textarea, #fp-dashboard-wrapper.fp-dark-mode select { background: #262626 !important; border-color: #404040 !important; color: #fff !important; }
		#fp-dashboard-wrapper.fp-dark-mode .draggable-item:hover, #fp-dashboard-wrapper.fp-dark-mode .canvas-field.selected, #fp-dashboard-wrapper.fp-dark-mode .canvas-field:hover { border-color: #04AD93 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .button-primary { background: #04AD93 !important; border-color: #04AD93 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .button-secondary { background: #374151 !important; color: #fff !important; border-color: #4b5563 !important; }
		';
		wp_add_inline_style( 'foundation-admin-css', $dashboard_css );

		wp_enqueue_script(
			'foundation-admin-js',
			FOUNDATION_URL . 'assets/js/foundation-builder.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-sortable' ),
			FOUNDATION_VERSION,
			true
		);

		wp_localize_script(
			'foundation-admin-js',
			'foundationData',
			array(
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'apiUrl'       => rest_url( 'foundation/v1/' ),
				'settingsUrl'  => admin_url( 'admin.php?page=foundation-form-settings' ),
				'pluginVersion'=> FOUNDATION_VERSION,
			)
		);
	}
}
