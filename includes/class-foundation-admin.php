<?php
/**
 * Handles the admin dashboard and builder shell.
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
			'metrics' => array(
				array( 'label' => __( 'Form views', 'foundation-customer-form' ), 'value' => number_format_i18n( $views ), 'note' => __( 'People who saw the calculator', 'foundation-customer-form' ), 'tone' => 'accent' ),
				array( 'label' => __( 'Started', 'foundation-customer-form' ), 'value' => number_format_i18n( $starts ), 'note' => __( 'Opened step one', 'foundation-customer-form' ), 'tone' => 'default' ),
				array( 'label' => __( 'Responses saved', 'foundation-customer-form' ), 'value' => number_format_i18n( $complete ), 'note' => sprintf( __( '%s%% completion rate', 'foundation-customer-form' ), number_format_i18n( $completion_rate, 1 ) ), 'tone' => 'success' ),
				array( 'label' => __( 'Saved drafts', 'foundation-customer-form' ), 'value' => number_format_i18n( $saved ), 'note' => sprintf( __( '%s%% used resume later', 'foundation-customer-form' ), number_format_i18n( $save_rate, 1 ) ), 'tone' => 'accent' ),
				array( 'label' => __( 'Incomplete', 'foundation-customer-form' ), 'value' => number_format_i18n( $incomplete ), 'note' => __( 'Journey closed before submit', 'foundation-customer-form' ), 'tone' => 'warning' ),
				array( 'label' => __( 'Failures', 'foundation-customer-form' ), 'value' => number_format_i18n( $failures ), 'note' => __( 'Errors or blocked sends', 'foundation-customer-form' ), 'tone' => 'warning' ),
			),
			'journey' => array(
				array( 'label' => __( 'Viewed calculator', 'foundation-customer-form' ), 'value' => number_format_i18n( $views ), 'percent' => '100%' ),
				array( 'label' => __( 'Started step one', 'foundation-customer-form' ), 'value' => number_format_i18n( $starts ), 'percent' => $views > 0 ? number_format_i18n( ( $starts / $views ) * 100, 1 ) . '%' : '0%' ),
				array( 'label' => __( 'Saved for later', 'foundation-customer-form' ), 'value' => number_format_i18n( $saved ), 'percent' => $starts > 0 ? number_format_i18n( ( $saved / $starts ) * 100, 1 ) . '%' : '0%' ),
				array( 'label' => __( 'Completed submission', 'foundation-customer-form' ), 'value' => number_format_i18n( $complete ), 'percent' => $starts > 0 ? number_format_i18n( ( $complete / $starts ) * 100, 1 ) . '%' : '0%' ),
			),
			'last_failure' => $metrics['last_failure'] ?? '',
			'last_saved_draft' => $metrics['last_saved_draft'] ?? '',
		);
	}

	public function render_admin_page() {
		$version  = FOUNDATION_VERSION;
		$logo_url = FOUNDATION_URL . 'assets/IMG_1089.png';
		$dashboard = $this->get_dashboard_data();
		?>
		<div id="fp-admin-shell" class="wrap" data-theme="dark">
			<aside class="fp-shell-rail" aria-label="Project Calculator sections">
				<div class="fp-rail-brand">
					<img src="<?php echo esc_url( $logo_url ); ?>" class="fp-shell-logo" alt="Inkfire" />
					<div>
						<div class="fp-shell-kicker"><?php esc_html_e( 'Foundation', 'foundation-customer-form' ); ?></div>
						<div class="fp-shell-name"><?php esc_html_e( 'Project Calculator', 'foundation-customer-form' ); ?></div>
					</div>
				</div>
				<div class="fp-shell-chip">v<?php echo esc_html( $version ); ?></div>
				<nav class="fp-shell-nav">
					<button class="fp-shell-nav-btn is-active" type="button" data-panel="overview"><?php esc_html_e( 'Dashboard', 'foundation-customer-form' ); ?></button>
					<button class="fp-shell-nav-btn" type="button" data-panel="builder"><?php esc_html_e( 'Builder', 'foundation-customer-form' ); ?></button>
					<a class="fp-shell-nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=foundation-form-settings' ) ); ?>"><?php esc_html_e( 'Calculator Settings', 'foundation-customer-form' ); ?></a>
				</nav>
				<div class="fp-shell-rail-card">
					<strong><?php esc_html_e( 'Save & resume', 'foundation-customer-form' ); ?></strong>
					<p><?php esc_html_e( 'This release adds the operational groundwork for draft saves, magic links, and analytics so you can support overwhelmed clients better.', 'foundation-customer-form' ); ?></p>
				</div>
				<div class="fp-shell-theme-row">
					<span><?php esc_html_e( 'Theme', 'foundation-customer-form' ); ?></span>
					<label class="fp-switch">
						<input type="checkbox" id="fnd-dark-switch" aria-label="<?php esc_attr_e( 'Use dark dashboard theme', 'foundation-customer-form' ); ?>" checked />
						<span class="fp-toggle-slider"></span>
					</label>
				</div>
			</aside>

			<div class="fp-shell-main">
				<header class="fp-shell-hero">
					<div>
						<div class="fp-shell-kicker"><?php esc_html_e( 'Admin redesign preview, now live in plugin form', 'foundation-customer-form' ); ?></div>
						<h1><?php esc_html_e( 'A calmer backend for real humans', 'foundation-customer-form' ); ?></h1>
						<p><?php esc_html_e( 'The internal rail now sits vertically so it does not fight the WordPress menu, the dashboard shows form health at a glance, and the builder sits in its own lane.', 'foundation-customer-form' ); ?></p>
					</div>
					<div class="fp-shell-hero-actions">
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=foundation-form-settings' ) ); ?>"><?php esc_html_e( 'Open settings', 'foundation-customer-form' ); ?></a>
						<button class="button button-primary" type="button" data-panel-jump="builder"><?php esc_html_e( 'Open builder', 'foundation-customer-form' ); ?></button>
					</div>
				</header>

				<section class="fp-shell-panel is-active" id="fp-panel-overview">
					<div class="fp-overview-grid">
						<div class="fp-overview-main">
							<div class="fp-surface">
								<div class="fp-surface-header">
									<div>
										<div class="fp-surface-kicker"><?php esc_html_e( 'Essential stats', 'foundation-customer-form' ); ?></div>
										<h2><?php esc_html_e( 'What is happening in the form right now', 'foundation-customer-form' ); ?></h2>
									</div>
								</div>
								<div class="fp-stat-grid">
									<?php foreach ( $dashboard['metrics'] as $card ) : ?>
										<div class="fp-stat-card">
											<div class="fp-stat-label"><?php echo esc_html( $card['label'] ); ?></div>
											<div class="fp-stat-value"><?php echo esc_html( $card['value'] ); ?></div>
											<div class="fp-stat-meta"><span class="fp-tone fp-tone-<?php echo esc_attr( $card['tone'] ); ?>"><?php echo esc_html( $card['label'] ); ?></span><span><?php echo esc_html( $card['note'] ); ?></span></div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="fp-surface">
								<div class="fp-surface-header">
									<div class="fp-surface-kicker"><?php esc_html_e( 'Journey health', 'foundation-customer-form' ); ?></div>
									<h2><?php esc_html_e( 'Where people are dropping off', 'foundation-customer-form' ); ?></h2>
								</div>
								<div class="fp-stack-list">
									<?php foreach ( $dashboard['journey'] as $item ) : ?>
										<div class="fp-stack-item">
											<div>
												<strong><?php echo esc_html( $item['label'] ); ?></strong>
												<span><?php esc_html_e( 'Conversion checkpoint', 'foundation-customer-form' ); ?></span>
											</div>
											<div class="fp-stack-metric"><strong><?php echo esc_html( $item['value'] ); ?></strong><span><?php echo esc_html( $item['percent'] ); ?></span></div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>

						<div class="fp-overview-side">
							<div class="fp-surface">
								<div class="fp-surface-kicker"><?php esc_html_e( 'Incomplete & failed', 'foundation-customer-form' ); ?></div>
								<h2><?php esc_html_e( 'The stuff you actually need to chase', 'foundation-customer-form' ); ?></h2>
								<div class="fp-stack-list">
									<div class="fp-alert-card">
										<strong><?php esc_html_e( 'Latest failure', 'foundation-customer-form' ); ?></strong>
										<span><?php echo esc_html( $dashboard['last_failure'] ? $dashboard['last_failure'] : __( 'No failure logged yet.', 'foundation-customer-form' ) ); ?></span>
									</div>
									<div class="fp-alert-card fp-alert-card-soft">
										<strong><?php esc_html_e( 'Latest saved draft', 'foundation-customer-form' ); ?></strong>
										<span><?php echo esc_html( $dashboard['last_saved_draft'] ? $dashboard['last_saved_draft'] : __( 'No draft saved yet.', 'foundation-customer-form' ) ); ?></span>
									</div>
								</div>
							</div>

							<div class="fp-surface">
								<div class="fp-surface-kicker"><?php esc_html_e( 'Save & resume', 'foundation-customer-form' ); ?></div>
								<h2><?php esc_html_e( 'Help overwhelmed clients come back later', 'foundation-customer-form' ); ?></h2>
								<div class="fp-quick-cards fp-quick-cards-single">
									<div class="fp-quick-card"><strong><?php esc_html_e( 'Draft saves are live', 'foundation-customer-form' ); ?></strong><span><?php esc_html_e( 'Clients can now save their progress and receive a one-click resume link by email.', 'foundation-customer-form' ); ?></span></div>
									<div class="fp-quick-card"><strong><?php esc_html_e( 'Files still need re-uploading', 'foundation-customer-form' ); ?></strong><span><?php esc_html_e( 'For privacy and browser limits, uploaded files are not stored in drafts. The resume flow restores answers and contact details.', 'foundation-customer-form' ); ?></span></div>
								</div>
							</div>
						</div>
					</div>
				</section>

				<section class="fp-shell-panel" id="fp-panel-builder">
					<div class="fp-surface fp-builder-surface">
						<div class="fp-surface-header fp-surface-header-builder">
							<div>
								<div class="fp-surface-kicker"><?php esc_html_e( 'Builder', 'foundation-customer-form' ); ?></div>
								<h2><?php esc_html_e( 'Build one step at a time', 'foundation-customer-form' ); ?></h2>
								<p><?php esc_html_e( 'The builder still runs on the proven production engine underneath, but it now sits inside a cleaner admin shell so the workflow makes more sense.', 'foundation-customer-form' ); ?></p>
							</div>
						</div>
						<div id="foundation-admin-app"></div>
					</div>
				</section>
			</div>
		</div>

		<script>
		(function(){
			const shell = document.getElementById('fp-admin-shell');
			const switcher = document.getElementById('fnd-dark-switch');
			let savedTheme = '';
			try {
				savedTheme = localStorage.getItem('foundation_admin_theme') || '';
			} catch (error) {}
			if (savedTheme === 'light') {
				shell.setAttribute('data-theme', 'light');
				if (switcher) switcher.checked = false;
			}
			function setPanel(panel) {
				document.querySelectorAll('.fp-shell-nav-btn').forEach(btn => btn.classList.toggle('is-active', btn.getAttribute('data-panel') === panel));
				document.querySelectorAll('.fp-shell-panel').forEach(el => el.classList.toggle('is-active', el.id === 'fp-panel-' + panel));
			}
			document.querySelectorAll('.fp-shell-nav-btn, [data-panel-jump]').forEach(btn => {
				btn.addEventListener('click', function(){ setPanel(this.getAttribute('data-panel') || this.getAttribute('data-panel-jump')); });
			});
			if (switcher) {
				switcher.addEventListener('change', function(){
					const next = this.checked ? 'dark' : 'light';
					shell.setAttribute('data-theme', next);
					try {
						localStorage.setItem('foundation_admin_theme', next);
					} catch (error) {}
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
		#fp-admin-shell{--fp-bg:#081120;--fp-surface:rgba(255,255,255,.05);--fp-surface-strong:#0b1d39;--fp-border:rgba(255,255,255,.10);--fp-text:#fff;--fp-muted:#b6c0d0;--fp-accent:#53e4ce;--fp-soft:#0d223f;--fp-warning:#f0b849;--fp-success:#34d399;--fp-shadow:0 24px 70px rgba(0,0,0,.28);display:grid;grid-template-columns:236px minmax(0,1fr);gap:20px;margin:28px 20px 0 0;padding:20px;min-height:calc(100vh - 120px);border-radius:34px;color:var(--fp-text);background:radial-gradient(circle at top left,rgba(83,228,206,.14),transparent 34%),radial-gradient(circle at bottom right,rgba(251,204,191,.12),transparent 30%),var(--fp-bg)}
		#fp-admin-shell[data-theme="light"]{--fp-bg:#eef3f7;--fp-surface:#fff;--fp-surface-strong:#f8fbfd;--fp-border:#dbe4eb;--fp-text:#0f172a;--fp-muted:#5b6577;--fp-accent:#0f766e;--fp-soft:#f4f8fb;--fp-warning:#b45309;--fp-success:#047857}
		#fp-admin-shell .fp-shell-rail,#fp-admin-shell .fp-shell-main>*{box-sizing:border-box}
		#fp-admin-shell .fp-shell-rail{position:sticky;top:32px;align-self:start;display:flex;flex-direction:column;gap:16px;padding:18px;border:1px solid var(--fp-border);border-radius:28px;background:var(--fp-surface);backdrop-filter:blur(18px);box-shadow:var(--fp-shadow)}
		#fp-admin-shell .fp-shell-brand,#fp-admin-shell .fp-rail-brand{display:flex;align-items:center;gap:12px}.fp-shell-logo{width:46px;height:46px;object-fit:contain}.fp-shell-kicker,.fp-surface-kicker{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.18em;color:var(--fp-accent)}.fp-shell-name{font-size:16px;font-weight:700;line-height:1.2}.fp-shell-chip{display:inline-flex;align-self:flex-start;padding:6px 10px;border-radius:999px;background:rgba(83,228,206,.12);border:1px solid rgba(83,228,206,.22);color:var(--fp-accent);font-size:12px;font-weight:700}
		#fp-admin-shell .fp-shell-nav{display:flex;flex-direction:column;gap:8px}.fp-shell-nav-btn,.fp-shell-nav-link{appearance:none;border:1px solid transparent;background:transparent;border-radius:18px;padding:12px 14px;font-size:14px;font-weight:600;text-align:left;color:var(--fp-text);text-decoration:none;cursor:pointer}.fp-shell-nav-btn:hover,.fp-shell-nav-link:hover{background:var(--fp-soft)}.fp-shell-nav-btn.is-active{background:linear-gradient(90deg,rgba(83,228,206,.16),rgba(83,228,206,.06));border-color:rgba(83,228,206,.2)}
		#fp-admin-shell .fp-shell-rail-card{padding:14px 16px;border:1px solid var(--fp-border);border-radius:22px;background:var(--fp-soft)}#fp-admin-shell .fp-shell-rail-card strong{display:block;margin-bottom:8px}#fp-admin-shell .fp-shell-rail-card p{margin:0;color:var(--fp-muted);font-size:13px;line-height:1.6}
		#fp-admin-shell .fp-shell-theme-row{display:flex;justify-content:space-between;align-items:center;padding:10px 4px 0;color:var(--fp-muted);font-size:13px;font-weight:600}
		#fp-admin-shell .fp-shell-main{display:flex;flex-direction:column;gap:18px}.fp-shell-hero,.fp-surface{border:1px solid var(--fp-border);border-radius:30px;background:var(--fp-surface);backdrop-filter:blur(18px);box-shadow:var(--fp-shadow)}.fp-shell-hero{display:flex;justify-content:space-between;gap:20px;padding:24px 26px}.fp-shell-hero h1{margin:4px 0 0;font-size:clamp(1.7rem,2vw,2.35rem);line-height:1.08}.fp-shell-hero p{margin:10px 0 0;max-width:760px;color:var(--fp-muted);font-size:15px;line-height:1.65}.fp-shell-hero-actions{display:flex;flex-wrap:wrap;align-items:flex-start;gap:10px}
		#fp-admin-shell .fp-shell-panel{display:none}#fp-admin-shell .fp-shell-panel.is-active{display:block}.fp-overview-grid{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(320px,.92fr);gap:18px}.fp-overview-main,.fp-overview-side{display:flex;flex-direction:column;gap:18px}.fp-surface{padding:24px}.fp-surface h2{margin:6px 0 0;font-size:1.5rem}.fp-surface p{color:var(--fp-muted)}.fp-surface-header{display:flex;justify-content:space-between;gap:20px;align-items:flex-end}.fp-surface-header-builder{margin-bottom:18px}.fp-builder-surface #foundation-admin-app{width:100%;min-height:650px}
		#fp-admin-shell .fp-stat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:20px}.fp-stat-card{padding:18px;border-radius:24px;border:1px solid var(--fp-border);background:var(--fp-soft)}.fp-stat-label{font-size:13px;color:var(--fp-muted)}.fp-stat-value{margin-top:8px;font-size:2rem;font-weight:800;line-height:1.05}.fp-stat-meta{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-top:12px;font-size:12px;color:var(--fp-muted)}.fp-tone{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-weight:700}.fp-tone-default{background:rgba(148,163,184,.14);color:var(--fp-text)}.fp-tone-accent{background:rgba(83,228,206,.14);color:var(--fp-accent)}.fp-tone-success{background:rgba(52,211,153,.14);color:var(--fp-success)}.fp-tone-warning{background:rgba(240,184,73,.14);color:var(--fp-warning)}
		#fp-admin-shell .fp-stack-list{display:flex;flex-direction:column;gap:12px;margin-top:20px}.fp-stack-item,.fp-alert-card,.fp-quick-card{display:flex;justify-content:space-between;gap:14px;padding:16px 18px;border-radius:22px;border:1px solid var(--fp-border);background:var(--fp-soft)}.fp-stack-item strong,.fp-alert-card strong,.fp-quick-card strong{display:block;font-size:14px}.fp-stack-item span,.fp-alert-card span,.fp-quick-card span{display:block;color:var(--fp-muted);font-size:13px;line-height:1.55}.fp-stack-metric{text-align:right}.fp-stack-metric strong{font-size:1.1rem}.fp-alert-card{flex-direction:column}.fp-alert-card-soft{background:rgba(83,228,206,.06)}.fp-quick-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:20px}.fp-quick-cards-single{grid-template-columns:1fr}
		#fp-admin-shell .fp-switch{position:relative;display:inline-flex}.fp-switch input{position:absolute!important;opacity:0!important;width:1px;height:1px}.fp-toggle-slider{position:relative;display:inline-block;width:44px;height:24px;border-radius:999px;background:#94a3b8}.fp-toggle-slider:after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;transition:transform .2s}.fp-switch input:checked + .fp-toggle-slider{background:#07a079}.fp-switch input:checked + .fp-toggle-slider:after{transform:translateX(20px)}
		#fp-admin-shell[data-theme="light"] .button.button-secondary{border-color:#cbd5e1;color:#0f172a;background:#fff}#fp-admin-shell[data-theme="light"] .button.button-primary{background:#0f766e;border-color:#0f766e;color:#fff}
		@media (max-width: 1280px){#fp-admin-shell{grid-template-columns:1fr}.fp-overview-grid{grid-template-columns:1fr}.fp-shell-rail{position:static}.fp-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
		@media (max-width: 782px){#fp-admin-shell{margin-right:10px}.fp-shell-hero,.fp-surface{padding:18px}.fp-shell-hero{flex-direction:column}.fp-stat-grid,.fp-quick-cards{grid-template-columns:1fr}}
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
