<?php
/**
 * Handles the Admin Dashboard Interface
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the Admin Menu (Nested under Foundation parent)
	 */
	public function add_admin_menu() {
		global $admin_page_hooks;
		$parent_slug = 'foundation-by-inkfire';

		// 1. Create Parent Menu if it doesn't exist yet
		if ( empty( $admin_page_hooks[ $parent_slug ] ) ) {
			add_menu_page(
				__( 'Foundation', 'foundation-customer-form' ),
				__( 'Foundation', 'foundation-customer-form' ),
				'manage_options',
				$parent_slug,
				null, // No callback needed for the parent slug itself
				'dashicons-hammer', // Shared Icon
				12
			);
			// Fix the duplicate submenu link for the parent item
			remove_submenu_page( $parent_slug, $parent_slug );
		}

		// 2. Add This Plugin as Submenu
		add_submenu_page(
			$parent_slug,
			__( 'Foundation Form', 'foundation-customer-form' ),
			__( 'Project Calculator', 'foundation-customer-form' ), // Submenu Label
			'manage_options',
			'foundation-form-builder',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the Wrapper Div with Foundation Styling
	 */
	public function render_admin_page() {
		$version = FOUNDATION_VERSION;
		$logo_url = FOUNDATION_URL . 'assets/IMG_1089.png';
		?>
		<div id="fp-dashboard-wrapper" class="wrap">
			
			<!-- Header / Branding -->
			<div class="fp-header">
				<div class="fp-title-group">
					<img src="<?php echo esc_url( $logo_url ); ?>" class="fp-logo" alt="Inkfire" />
					<div class="fp-branding-text">
						<h1><?php esc_html_e('Foundation: Project Calculator', 'foundation-customer-form'); ?></h1>
						<p class="fp-byline"><?php esc_html_e('Drag-and-drop builder for your project quote calculator.', 'foundation-customer-form'); ?></p>
					</div>
				</div>
				<div class="fp-theme-toggle">
					<label for="fnd-dark-switch" class="screen-reader-text"><?php esc_html_e('Toggle dark mode', 'foundation-customer-form'); ?></label>
					<label class="fp-switch">
						<input type="checkbox" id="fnd-dark-switch" />
						<span class="fp-toggle-slider"></span>
					</label>
					<span class="fp-badge">v<?php echo esc_html( $version ); ?></span>
				</div>
			</div>

			<!-- Main React App Container -->
			<div class="fp-card-builder-frame" style="margin-top:20px;">
				<div id="foundation-admin-app"></div>
			</div>

		</div>

		<!-- Inline JS for Dark Mode Persistence -->
		<script>
		(function(){
			const wrap = document.getElementById('fp-dashboard-wrapper');
			const sw   = document.getElementById('fnd-dark-switch');
			
			// Check Local Storage
			const storedTheme = localStorage.getItem('foundation_admin_theme');
			if(storedTheme === 'dark') {
				wrap.classList.add('fp-dark-mode');
				if(sw) sw.checked = true;
			}

			if(sw && wrap) {
				sw.addEventListener('change', function() {
					if(this.checked) {
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

	/**
	 * Load Scripts & Styles
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'foundation-form-builder' ) === false ) {
			return;
		}

		$version = time();

		// 1. Load the React App Styles
		wp_enqueue_style(
			'foundation-admin-css',
			FOUNDATION_URL . 'assets/css/foundation-builder.css', 
			array(),
			$version
		);

		// 2. Inject Foundation Admin Dashboard CSS (Consolidated Styling)
		$dashboard_css = '
		/* Container & Typography */
		#fp-dashboard-wrapper { margin: 35px 20px 0 0; padding: 35px; background: #ffffff; border-radius: 35px; max-width: unset; box-sizing: border-box; transition: background 0.3s, color 0.3s; }
		#fp-dashboard-wrapper .fp-header { display: flex; align-items: center; justify-content: space-between; margin: 0 0 12px; }
		#fp-dashboard-wrapper .fp-title-group { display: flex; align-items: center; gap: 15px; }
		#fp-dashboard-wrapper .fp-logo { height: 80px; width: auto; margin-bottom: -10px; margin-right: -10px; display: block; }
		#fp-dashboard-wrapper .fp-branding-text h1 { margin: 0; font-size: 1.45rem; color: #1f2937; line-height: 1.2; }
		#fp-dashboard-wrapper .fp-byline { margin: 2px 0 0; color: #4b5563; font-size: 14px; }
		#fp-dashboard-wrapper .fp-badge { display: inline-block; padding: .15rem .5rem; border-radius: 6px; font-size: .75rem; background: #212121; color: #fff; margin-left: 10px; }
		#fp-dashboard-wrapper .fp-theme-toggle { display: flex; align-items: center; gap: 8px; }

		/* Toggle Switch */
		#fp-dashboard-wrapper .fp-switch { position: relative; display: inline-flex; align-items: center; gap: .6rem; cursor: pointer; }
		#fp-dashboard-wrapper .fp-switch input { position: absolute!important; opacity: 0!important; width: 1px; height: 1px; margin: 0; pointer-events: none; }
		#fp-dashboard-wrapper .fp-toggle-slider { position: relative; width: 42px; height: 24px; border-radius: 999px; background: #cfd3d8; transition: background .2s; }
		#fp-dashboard-wrapper .fp-toggle-slider:after { content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 999px; background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: transform .2s; }
		#fp-dashboard-wrapper .fp-switch input:checked + .fp-toggle-slider { background: #07a079; }
		#fp-dashboard-wrapper .fp-switch input:checked + .fp-toggle-slider:after { transform: translateX(18px); }

		/* Adjust React Builder */
		#foundation-admin-app { width: 100%; min-height: 500px; }

		/* --- DARK MODE OVERRIDES --- */
		#fp-dashboard-wrapper.fp-dark-mode { background: #1a1b1e !important; color: #f5f7fb !important; }
		
		/* Header Branding (Matched to Email: Dark Navy + Peach) */
		#fp-dashboard-wrapper.fp-dark-mode .fp-branding-text h1 { color: #FBCCBF !important; } /* Peach */
		#fp-dashboard-wrapper.fp-dark-mode .fp-byline { color: #9CA3AF !important; }
		#fp-dashboard-wrapper.fp-dark-mode .fp-header { border-bottom: 1px solid #2D2E45 !important; padding-bottom: 20px; margin-bottom: -20px; }
		
		/* Builder Columns & Panels */
		#fp-dashboard-wrapper.fp-dark-mode .builder-col { background: #1F2033 !important; border-color: #2D2E45 !important; color: #E5E7EB; }
		#fp-dashboard-wrapper.fp-dark-mode .builder-col h3 { color: #f3f4f6 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .builder-col h4 { color: #E5E7EB !important; }
		
		/* Canvas Area */
		#fp-dashboard-wrapper.fp-dark-mode .builder-col.canvas { background: #111827 !important; border-color: #1f2937 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .step-manager-bar { background: #1F2033 !important; border-bottom: 1px solid #2D2E45 !important; }
		
		/* Toolbox Items (Widgets) */
		#fp-dashboard-wrapper.fp-dark-mode .draggable-item { background: #262626 !important; border-color: #404040 !important; color: #f3f4f6 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .draggable-item:hover { border-color: #04AD93 !important; background: #2a2a2a !important; }
		#fp-dashboard-wrapper.fp-dark-mode .tool-icon { background: #333 !important; color: #fff !important; }
		#fp-dashboard-wrapper.fp-dark-mode .tool-info strong { color: #fff !important; }
		#fp-dashboard-wrapper.fp-dark-mode .tool-info small { color: #9ca3af !important; }

		/* Canvas Fields */
		#fp-dashboard-wrapper.fp-dark-mode .canvas-field { background: #1f2937 !important; border: 1px solid #374151 !important; box-shadow: none; }
		#fp-dashboard-wrapper.fp-dark-mode .canvas-field:hover,
		#fp-dashboard-wrapper.fp-dark-mode .canvas-field.selected { border-color: #04AD93 !important; background: #1f2937 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .field-header-bar { background: #374151 !important; border-bottom: 1px solid #4b5563 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .field-type-label { color: #e5e7eb !important; }
		#fp-dashboard-wrapper.fp-dark-mode .preview-label { color: #fff !important; }
		#fp-dashboard-wrapper.fp-dark-mode .preview-sub { color: #9ca3af !important; }
		
		/* FIXED: Preview Pills (Service Options) */
		#fp-dashboard-wrapper.fp-dark-mode .preview-pill, 
		#fp-dashboard-wrapper.fp-dark-mode .preview-toggle-pill {
			background: #374151 !important;
			color: #fff !important;
			border: 1px solid #4b5563 !important;
		}

		/* Step Tabs */
		#fp-dashboard-wrapper.fp-dark-mode .step-tab { background: #374151 !important; color: #9ca3af !important; border: 1px solid #4b5563 !important; border-bottom-color: #4b5563 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .step-tab.is-active { background: #1f2937 !important; color: #fff !important; border-bottom-color: #1f2937 !important; border-top-color: #04AD93 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .step-tab:hover:not(.is-active) { background: #4b5563 !important; color: #fff !important; }

		/* Buttons */
		#fp-dashboard-wrapper.fp-dark-mode .button-secondary { background: #374151 !important; color: #fff !important; border-color: #4b5563 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .button-secondary:hover { background: #4b5563 !important; border-color: #6b7280 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .button-primary { background: #04AD93 !important; border-color: #04AD93 !important; color: #fff !important; }
		#fp-dashboard-wrapper.fp-dark-mode .button-primary:hover { background: #039680 !important; }

		/* Form Inputs (Settings Panel) */
		#fp-dashboard-wrapper.fp-dark-mode input[type=text],
		#fp-dashboard-wrapper.fp-dark-mode input[type=number],
		#fp-dashboard-wrapper.fp-dark-mode input[type=email],
		#fp-dashboard-wrapper.fp-dark-mode textarea,
		#fp-dashboard-wrapper.fp-dark-mode select {
			background: #262626 !important;
			border: 1px solid #404040 !important;
			color: #fff !important;
		}
		#fp-dashboard-wrapper.fp-dark-mode input:focus,
		#fp-dashboard-wrapper.fp-dark-mode textarea:focus,
		#fp-dashboard-wrapper.fp-dark-mode select:focus {
			border-color: #04AD93 !important;
			box-shadow: 0 0 0 1px #04AD93 !important;
		}
		#fp-dashboard-wrapper.fp-dark-mode label { color: #d1d5db !important; }
		
		/* FIXED: Flow Logic Box (Settings Group with Inline Style) */
		#fp-dashboard-wrapper.fp-dark-mode .settings-group,
		#fp-dashboard-wrapper.fp-dark-mode .settings-group[style*="background"] { 
			background: #1F2033 !important; 
			border-color: #2D2E45 !important; 
			border-bottom: 1px solid #2D2E45 !important;
			color: #E5E7EB !important;
		}
		#fp-dashboard-wrapper.fp-dark-mode .settings-help { color: #9CA3AF !important; }
		
		/* Inline Routing Panel */
		#fp-dashboard-wrapper.fp-dark-mode .inline-routing-editor { background: #13141F !important; border-color: #2D2E45 !important; }
		#fp-dashboard-wrapper.fp-dark-mode .choices-panel-header { background: #2A2B40 !important; color: #FFFFFF !important; }
		#fp-dashboard-wrapper.fp-dark-mode .choices-badge { background: #07a079 !important; color: #FFFFFF !important; }
		#fp-dashboard-wrapper.fp-dark-mode .choices-panel-body { background: #1F2033 !important; color: #E5E7EB !important; }
		';
		wp_add_inline_style('foundation-admin-css', $dashboard_css);

		// 3. Load the React App Script
		wp_enqueue_script(
			'foundation-admin-js',
			FOUNDATION_URL . 'assets/js/foundation-builder.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-sortable' ), 
			$version,
			true
		);

		// 4. Pass Data
		wp_localize_script(
			'foundation-admin-js',
			'foundationData',
			array(
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'apiUrl'  => rest_url( 'foundation/v1/' ),
			)
		);
	}
}