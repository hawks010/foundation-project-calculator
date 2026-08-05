<?php
/**
 * Simple, server-rendered admin dashboard for the Inkfire calculator.
 *
 * The old React builder exposed implementation details and made ordinary price
 * changes risky. The 1.4 dashboard separates the stable journey blueprint from
 * the editable price catalogue and operational settings.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_Admin {

	private $page_slug = 'foundation-form-builder';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_foundation_save_prices', array( $this, 'save_prices' ) );
		add_action( 'admin_post_foundation_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_foundation_apply_blueprint', array( $this, 'apply_blueprint' ) );
		add_action( 'admin_post_foundation_restore_blueprint_backup', array( $this, 'restore_blueprint_backup' ) );
		add_action( 'admin_post_foundation_reset_metrics', array( $this, 'reset_metrics' ) );
		add_action( 'admin_post_foundation_export_configuration', array( $this, 'export_configuration' ) );
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
				array( $this, 'render_admin_page' ),
				'dashicons-chart-area',
				12
			);
		}

		add_submenu_page(
			$parent_slug,
			__( 'Project Calculator', 'foundation-customer-form' ),
			__( 'Project Calculator', 'foundation-customer-form' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_admin_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, $this->page_slug ) && false === strpos( (string) $hook, 'foundation-by-inkfire' ) ) {
			return;
		}
		wp_enqueue_style(
			'foundation-calculator-admin',
			FOUNDATION_URL . 'assets/admin/foundation-calculator-admin.css',
			array(),
			FOUNDATION_VERSION
		);
		wp_enqueue_script(
			'foundation-calculator-admin',
			FOUNDATION_URL . 'assets/admin/foundation-calculator-admin.js',
			array(),
			FOUNDATION_VERSION,
			true
		);
	}

	private function check_admin_request( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this calculator.', 'foundation-customer-form' ), 403 );
		}
		check_admin_referer( $nonce_action );
	}

	private function redirect( $tab, $notice ) {
		$url = add_query_arg(
			array(
				'page'              => $this->page_slug,
				'tab'               => sanitize_key( $tab ),
				'foundation_notice' => sanitize_key( $notice ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public function save_prices() {
		$this->check_admin_request( 'foundation_save_prices' );
		$input   = isset( $_POST['foundation_pricing_catalog'] ) ? wp_unslash( $_POST['foundation_pricing_catalog'] ) : array();
		$current = foundation_get_pricing_catalog();
		update_option( 'foundation_pricing_catalog', foundation_sanitize_pricing_catalog( array_merge( $current, is_array( $input ) ? $input : array() ) ), false );
		$this->redirect( 'prices', 'prices_saved' );
	}

	public function save_settings() {
		$this->check_admin_request( 'foundation_save_settings' );
		$current = foundation_get_settings();
		$input   = isset( $_POST['foundation_form_settings'] ) && is_array( $_POST['foundation_form_settings'] )
			? wp_unslash( $_POST['foundation_form_settings'] )
			: array();
		$section = isset( $_POST['foundation_settings_section'] ) ? sanitize_key( wp_unslash( $_POST['foundation_settings_section'] ) ) : 'emails';

		$checkboxes_by_section = array(
			'emails'   => array( 'customer_confirmation_enabled' ),
			'branding' => array( 'show_live_summary', 'phone_required' ),
			'advanced' => array( 'quote_mode_enabled', 'attach_pdf_summary', 'attach_json_summary', 'attach_zip_package' ),
		);
		if ( isset( $checkboxes_by_section[ $section ] ) ) {
			foreach ( $checkboxes_by_section[ $section ] as $key ) {
				$input[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
			}
		}

		$settings = foundation_sanitize_settings( array_merge( $current, $input ) );
		update_option( 'foundation_form_settings', $settings, false );
		$this->redirect( in_array( $section, array( 'emails', 'branding' ), true ) ? 'emails' : 'advanced', 'settings_saved' );
	}

	public function apply_blueprint() {
		$this->check_admin_request( 'foundation_apply_blueprint' );
		foundation_apply_inkfire_blueprint( true );
		$this->redirect( 'journey', 'blueprint_applied' );
	}

	public function restore_blueprint_backup() {
		$this->check_admin_request( 'foundation_restore_blueprint_backup' );
		$backup = get_option( 'foundation_form_data_backup', array() );
		if ( is_array( $backup ) && ! empty( $backup['form_data'] ) && is_array( $backup['form_data'] ) ) {
			$current = get_option( 'foundation_form_data', array() );
			update_option(
				'foundation_form_data_backup',
				array(
					'created_at' => current_time( 'mysql' ),
					'version'    => get_option( 'foundation_blueprint_version', '' ),
					'form_data'  => $current,
				),
				false
			);
			update_option( 'foundation_form_data', foundation_normalize_form_data( $backup['form_data'] ), false );
			update_option( 'foundation_blueprint_version', isset( $backup['version'] ) ? sanitize_text_field( $backup['version'] ) : '', false );
			$this->redirect( 'journey', 'backup_restored' );
		}
		$this->redirect( 'journey', 'backup_missing' );
	}

	public function reset_metrics() {
		$this->check_admin_request( 'foundation_reset_metrics' );
		update_option( 'foundation_form_metrics', foundation_get_default_metrics(), false );
		$this->redirect( 'overview', 'metrics_reset' );
	}

	public function export_configuration() {
		$this->check_admin_request( 'foundation_export_configuration' );
		$payload = array(
			'exported_at'       => gmdate( 'c' ),
			'plugin_version'    => FOUNDATION_VERSION,
			'blueprint_version' => get_option( 'foundation_blueprint_version', '' ),
			'pricing_catalog'   => foundation_get_pricing_catalog(),
			'form_data'         => foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) ),
			'settings'          => foundation_get_settings(),
		);
		// Secrets are not stored by the plugin. Even so, omit sender and recipient addresses from exports by default.
		foreach ( array( 'admin_email', 'cc_emails', 'from_email' ) as $key ) {
			unset( $payload['settings'][ $key ] );
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="foundation-project-calculator-config-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	private function get_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		return in_array( $tab, array( 'overview', 'enquiries', 'prices', 'journey', 'emails', 'advanced' ), true ) ? $tab : 'overview';
	}

	private function get_dashboard_data() {
		$metrics = foundation_get_metrics();
		$views   = max( 0, intval( isset( $metrics['form_views'] ) ? $metrics['form_views'] : 0 ) );
		$starts  = max( 0, intval( isset( $metrics['form_starts'] ) ? $metrics['form_starts'] : 0 ) );
		$complete = max( 0, intval( isset( $metrics['responses_saved'] ) ? $metrics['responses_saved'] : 0 ) );
		$saved   = max( 0, intval( isset( $metrics['saved_drafts'] ) ? $metrics['saved_drafts'] : 0 ) );
		$incomplete = max( 0, intval( isset( $metrics['incomplete'] ) ? $metrics['incomplete'] : 0 ) );
		$failures = max( 0, intval( isset( $metrics['failures'] ) ? $metrics['failures'] : 0 ) );

		return array(
			'views'           => $views,
			'starts'          => $starts,
			'complete'        => $complete,
			'saved'           => $saved,
			'incomplete'      => $incomplete,
			'failures'        => $failures,
			'completion_rate' => $starts > 0 ? round( ( $complete / $starts ) * 100, 1 ) : 0,
			'last_failure'    => isset( $metrics['last_failure'] ) ? $metrics['last_failure'] : '',
		);
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab    = $this->get_tab();
		$health = foundation_get_blueprint_health();
		?>
		<div class="wrap foundation-calculator-admin">
			<div class="fpc-admin-hero">
				<div>
					<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Foundation by Inkfire', 'foundation-customer-form' ); ?></p>
					<h1><?php esc_html_e( 'Project Calculator', 'foundation-customer-form' ); ?></h1>
					<p><?php esc_html_e( 'Edit prices without touching the journey. Check the customer flow without wrestling a builder.', 'foundation-customer-form' ); ?></p>
				</div>
				<div class="fpc-admin-hero-actions">
					<span class="fpc-status-pill <?php echo $health['ok'] ? 'is-good' : 'is-warning'; ?>">
						<?php echo $health['ok'] ? esc_html__( 'Journey healthy', 'foundation-customer-form' ) : esc_html__( 'Check required', 'foundation-customer-form' ); ?>
					</span>
					<code>[foundation_form]</code>
					<button class="button" type="button" data-fpc-copy="[foundation_form]"><?php esc_html_e( 'Copy shortcode', 'foundation-customer-form' ); ?></button>
				</div>
			</div>

			<?php $this->render_notice(); ?>
			<nav class="fpc-admin-tabs" aria-label="<?php esc_attr_e( 'Calculator sections', 'foundation-customer-form' ); ?>">
				<?php
				$tabs = array(
					'overview' => __( 'Overview', 'foundation-customer-form' ),
					'enquiries'=> __( 'Enquiries', 'foundation-customer-form' ),
					'prices'   => __( 'Prices', 'foundation-customer-form' ),
					'journey'  => __( 'Customer journey', 'foundation-customer-form' ),
					'emails'   => __( 'Emails & branding', 'foundation-customer-form' ),
					'advanced' => __( 'Advanced', 'foundation-customer-form' ),
				);
				foreach ( $tabs as $key => $label ) :
					$url = add_query_arg( array( 'page' => $this->page_slug, 'tab' => $key ), admin_url( 'admin.php' ) );
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="<?php echo $tab === $key ? 'is-active' : ''; ?>" <?php echo $tab === $key ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<main class="fpc-admin-main">
				<?php
				switch ( $tab ) {
					case 'enquiries':
						$this->render_enquiries();
						break;
					case 'prices':
						$this->render_prices();
						break;
					case 'journey':
						$this->render_journey( $health );
						break;
					case 'emails':
						$this->render_emails_branding();
						break;
					case 'advanced':
						$this->render_advanced();
						break;
					default:
						$this->render_overview( $health );
						break;
				}
				?>
			</main>
		</div>
		<?php
	}

	private function render_notice() {
		$notice = isset( $_GET['foundation_notice'] ) ? sanitize_key( wp_unslash( $_GET['foundation_notice'] ) ) : '';
		$messages = array(
			'prices_saved'      => array( 'success', __( 'Prices saved. The public calculator will use them immediately.', 'foundation-customer-form' ) ),
			'settings_saved'    => array( 'success', __( 'Settings saved.', 'foundation-customer-form' ) ),
			'blueprint_applied' => array( 'success', __( 'The Inkfire journey was applied and the previous journey was backed up.', 'foundation-customer-form' ) ),
			'backup_restored'   => array( 'success', __( 'The previous journey was restored. The replaced version is now the backup.', 'foundation-customer-form' ) ),
			'backup_missing'    => array( 'error', __( 'No journey backup was available to restore.', 'foundation-customer-form' ) ),
			'metrics_reset'     => array( 'success', __( 'Journey metrics were reset.', 'foundation-customer-form' ) ),
		);
		if ( empty( $messages[ $notice ] ) ) {
			return;
		}
		$type = $messages[ $notice ][0];
		$message = $messages[ $notice ][1];
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	}

	private function render_overview( $health ) {
		$data     = $this->get_dashboard_data();
		$settings = foundation_get_settings();
		$stored_enquiries = class_exists( 'Foundation_Submissions' ) ? Foundation_Submissions::count_all() : 0;
		$cards = array(
			array( 'label' => __( 'Calculator views', 'foundation-customer-form' ), 'value' => $data['views'], 'note' => __( 'Opened on the site', 'foundation-customer-form' ) ),
			array( 'label' => __( 'Journeys started', 'foundation-customer-form' ), 'value' => $data['starts'], 'note' => __( 'Moved beyond the intro', 'foundation-customer-form' ) ),
			array( 'label' => __( 'Stored enquiries', 'foundation-customer-form' ), 'value' => $stored_enquiries, 'note' => sprintf( __( '%s%% completion from starts', 'foundation-customer-form' ), number_format_i18n( $data['completion_rate'], 1 ) ) ),
			array( 'label' => __( 'Saved drafts', 'foundation-customer-form' ), 'value' => $data['saved'], 'note' => __( 'Resume links created', 'foundation-customer-form' ) ),
		);
		?>
		<section class="fpc-admin-grid fpc-admin-grid-metrics">
			<?php foreach ( $cards as $card ) : ?>
				<article class="fpc-admin-card fpc-metric-card">
					<p><?php echo esc_html( $card['label'] ); ?></p>
					<strong><?php echo esc_html( number_format_i18n( $card['value'] ) ); ?></strong>
					<span><?php echo esc_html( $card['note'] ); ?></span>
				</article>
			<?php endforeach; ?>
		</section>

		<?php if ( empty( $health['blueprint_current'] ) ) : ?>
			<section class="fpc-admin-card fpc-blueprint-callout">
				<div>
					<p class="fpc-admin-eyebrow"><?php esc_html_e( 'One safe migration step', 'foundation-customer-form' ); ?></p>
					<h2><?php esc_html_e( 'Apply Mali’s Inkfire pricing journey', 'foundation-customer-form' ); ?></h2>
					<p><?php esc_html_e( 'This site is still using a custom or older flow. Applying the bundled journey creates a backup first, then installs the tested Web, Tech and Business routes. Prices and email settings are left untouched.', 'foundation-customer-form' ); ?></p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-fpc-confirm="<?php esc_attr_e( 'Back up the current journey and apply the Inkfire 2026 pricing journey?', 'foundation-customer-form' ); ?>">
					<input type="hidden" name="action" value="foundation_apply_blueprint"><?php wp_nonce_field( 'foundation_apply_blueprint' ); ?>
					<button class="button button-primary button-hero" type="submit"><?php esc_html_e( 'Back up and apply journey', 'foundation-customer-form' ); ?></button>
				</form>
			</section>
		<?php endif; ?>

		<section class="fpc-admin-grid fpc-admin-grid-two">
			<article class="fpc-admin-card">
				<div class="fpc-card-heading">
					<div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'System check', 'foundation-customer-form' ); ?></p><h2><?php esc_html_e( 'Ready-state checklist', 'foundation-customer-form' ); ?></h2></div>
					<span class="fpc-status-pill <?php echo $health['ok'] ? 'is-good' : 'is-warning'; ?>"><?php echo esc_html( count( $health['issues'] ) ); ?> <?php esc_html_e( 'issues', 'foundation-customer-form' ); ?></span>
				</div>
				<ul class="fpc-health-list">
					<?php $this->health_row( ! empty( $health['blueprint_current'] ), __( 'Inkfire 2026 pricing journey', 'foundation-customer-form' ), ! empty( $health['blueprint_current'] ) ? __( 'Mali’s mapped Web, Tech and Business journey is active.', 'foundation-customer-form' ) : __( 'Back up and apply the bundled journey before launch.', 'foundation-customer-form' ) ); ?>
					<?php $this->health_row( ! empty( $health['structure_ok'] ), __( 'Journey routes and price keys', 'foundation-customer-form' ), ! empty( $health['structure_ok'] ) ? __( 'No broken targets or missing prices found.', 'foundation-customer-form' ) : implode( ' ', (array) ( $health['structure_issues'] ?? array() ) ) ); ?>
					<?php $this->health_row( class_exists( 'Foundation_Submissions' ) && post_type_exists( Foundation_Submissions::POST_TYPE ), __( 'Local enquiry inbox', 'foundation-customer-form' ), __( 'Leads are stored before email delivery is attempted.', 'foundation-customer-form' ) ); ?>
					<?php $this->health_row( is_email( $settings['admin_email'] ) && ! foundation_is_placeholder_email( $settings['admin_email'] ), __( 'Lead notification email', 'foundation-customer-form' ), $settings['admin_email'] ); ?>
					<?php $this->health_row( ! empty( $settings['privacy_policy_url'] ), __( 'Privacy link', 'foundation-customer-form' ), $settings['privacy_policy_url'] ? $settings['privacy_policy_url'] : __( 'Add a privacy-policy URL.', 'foundation-customer-form' ) ); ?>
					<?php $this->health_row( class_exists( 'ZipArchive' ), __( 'Staff ZIP package', 'foundation-customer-form' ), class_exists( 'ZipArchive' ) ? __( 'ZipArchive is available.', 'foundation-customer-form' ) : __( 'ZipArchive is not installed. PDF and JSON can still be attached.', 'foundation-customer-form' ) ); ?>
				</ul>
				<p class="fpc-help"><?php esc_html_e( 'A live SMTP send and browser checkout-style smoke test are still deployment checks. The plugin cannot prove mail delivery from this screen.', 'foundation-customer-form' ); ?></p>
			</article>

			<article class="fpc-admin-card">
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Current configuration', 'foundation-customer-form' ); ?></p>
				<h2><?php esc_html_e( 'Inkfire pricing journey', 'foundation-customer-form' ); ?></h2>
				<dl class="fpc-definition-list">
					<div><dt><?php esc_html_e( 'Blueprint', 'foundation-customer-form' ); ?></dt><dd><?php echo esc_html( $health['blueprint_version'] ? $health['blueprint_version'] : __( 'Custom / legacy', 'foundation-customer-form' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Screens', 'foundation-customer-form' ); ?></dt><dd><?php echo esc_html( $health['step_count'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Questions', 'foundation-customer-form' ); ?></dt><dd><?php echo esc_html( $health['field_count'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Editable prices', 'foundation-customer-form' ); ?></dt><dd><?php echo esc_html( count( foundation_get_default_pricing_catalog() ) ); ?></dd></div>
				</dl>
				<div class="fpc-button-row">
					<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => $this->page_slug, 'tab' => 'enquiries' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Open enquiry inbox', 'foundation-customer-form' ); ?></a>
					<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => $this->page_slug, 'tab' => 'prices' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit prices', 'foundation-customer-form' ); ?></a>
					<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => $this->page_slug, 'tab' => 'journey' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'View customer flow', 'foundation-customer-form' ); ?></a>
				</div>
			</article>
		</section>

		<?php if ( $data['last_failure'] ) : ?>
			<section class="fpc-admin-card fpc-alert-card"><strong><?php esc_html_e( 'Latest recorded failure:', 'foundation-customer-form' ); ?></strong> <?php echo esc_html( $data['last_failure'] ); ?></section>
		<?php endif; ?>
		<?php
	}

	private function health_row( $good, $label, $detail ) {
		?>
		<li><span class="fpc-health-icon <?php echo $good ? 'is-good' : 'is-warning'; ?>" aria-hidden="true"><?php echo $good ? '✓' : '!'; ?></span><div><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( $detail ); ?></span></div></li>
		<?php
	}

	private function format_enquiry_date( $created_at ) {
		if ( empty( $created_at ) ) {
			return '';
		}
		$format = trim( get_option( 'date_format', 'j F Y' ) . ' ' . get_option( 'time_format', 'H:i' ) );
		return get_date_from_gmt( (string) $created_at, $format );
	}

	private function render_mail_status( $status, $label ) {
		$status = in_array( $status, array( 'pending', 'sent', 'failed', 'disabled' ), true ) ? $status : 'pending';
		$names  = array(
			'pending'  => __( 'Pending', 'foundation-customer-form' ),
			'sent'     => __( 'Sent', 'foundation-customer-form' ),
			'failed'   => __( 'Failed', 'foundation-customer-form' ),
			'disabled' => __( 'Disabled', 'foundation-customer-form' ),
		);
		?>
		<span class="fpc-mail-status is-<?php echo esc_attr( $status ); ?>"><span><?php echo esc_html( $label ); ?></span> <?php echo esc_html( $names[ $status ] ); ?></span>
		<?php
	}

	private function render_enquiries() {
		$enquiry_id = isset( $_GET['enquiry_id'] ) ? absint( $_GET['enquiry_id'] ) : 0;
		if ( $enquiry_id ) {
			$this->render_enquiry_detail( $enquiry_id );
			return;
		}

		$page       = isset( $_GET['enquiry_page'] ) ? max( 1, absint( $_GET['enquiry_page'] ) ) : 1;
		$per_page   = 30;
		$total      = Foundation_Submissions::count_all();
		$total_pages= max( 1, (int) ceil( $total / $per_page ) );
		if ( $page > $total_pages ) {
			$page = $total_pages;
		}
		$records = Foundation_Submissions::get_page( $page, $per_page );
		$base_url = add_query_arg( array( 'page' => $this->page_slug, 'tab' => 'enquiries' ), admin_url( 'admin.php' ) );
		?>
		<div class="fpc-section-intro">
			<div>
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Private lead store', 'foundation-customer-form' ); ?></p>
				<h2><?php esc_html_e( 'Calculator enquiries', 'foundation-customer-form' ); ?></h2>
				<p><?php esc_html_e( 'Each completed enquiry is saved here before email is attempted, so an SMTP wobble does not swallow the lead.', 'foundation-customer-form' ); ?></p>
			</div>
			<span class="fpc-status-pill is-good"><?php echo esc_html( sprintf( _n( '%s stored enquiry', '%s stored enquiries', $total, 'foundation-customer-form' ), number_format_i18n( $total ) ) ); ?></span>
		</div>

		<section class="fpc-admin-card fpc-enquiry-inbox">
			<?php if ( empty( $records ) ) : ?>
				<div class="fpc-empty-state">
					<h3><?php esc_html_e( 'No enquiries yet', 'foundation-customer-form' ); ?></h3>
					<p><?php esc_html_e( 'Completed customer estimates will appear here with their pricing, answers and delivery status.', 'foundation-customer-form' ); ?></p>
				</div>
			<?php else : ?>
				<div class="fpc-enquiry-table-wrap">
					<table class="widefat striped fpc-enquiry-table">
						<thead><tr>
							<th scope="col"><?php esc_html_e( 'Reference', 'foundation-customer-form' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Contact', 'foundation-customer-form' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Estimate', 'foundation-customer-form' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Email delivery', 'foundation-customer-form' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Received', 'foundation-customer-form' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'foundation-customer-form' ); ?></span></th>
						</tr></thead>
						<tbody>
						<?php foreach ( $records as $record ) :
							$contact  = (array) ( $record['contact'] ?? array() );
							$quote    = (array) ( $record['quote'] ?? array() );
							$currency = $quote['currency'] ?? foundation_get_settings()['currency_symbol'] ?? '£';
							$detail_url = add_query_arg( array( 'enquiry_id' => absint( $record['id'] ?? 0 ), 'enquiry_page' => $page ), $base_url );
							?>
							<tr>
								<td data-label="<?php esc_attr_e( 'Reference', 'foundation-customer-form' ); ?>"><strong><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $record['reference'] ?? '' ); ?></a></strong></td>
								<td data-label="<?php esc_attr_e( 'Contact', 'foundation-customer-form' ); ?>"><strong><?php echo esc_html( $contact['company'] ?? '' ); ?></strong><span><?php echo esc_html( $contact['name'] ?? '' ); ?></span><a href="mailto:<?php echo esc_attr( $contact['email'] ?? '' ); ?>"><?php echo esc_html( $contact['email'] ?? '' ); ?></a></td>
								<td data-label="<?php esc_attr_e( 'Estimate', 'foundation-customer-form' ); ?>" class="fpc-enquiry-estimate">
									<?php if ( ! empty( $quote['one_off_max'] ) ) : ?><span><b><?php esc_html_e( 'One-off:', 'foundation-customer-form' ); ?></b> <?php echo esc_html( foundation_format_quote_range( $quote['one_off_min'] ?? 0, $quote['one_off_max'] ?? 0, $currency ) ); ?></span><?php endif; ?>
									<?php if ( ! empty( $quote['monthly_max'] ) ) : ?><span><b><?php esc_html_e( 'Monthly:', 'foundation-customer-form' ); ?></b> <?php echo esc_html( foundation_format_quote_range( $quote['monthly_min'] ?? 0, $quote['monthly_max'] ?? 0, $currency ) ); ?></span><?php endif; ?>
									<?php if ( empty( $quote['has_pricing'] ) ) : ?><span><?php esc_html_e( 'Tailored quote only', 'foundation-customer-form' ); ?></span><?php endif; ?>
									<?php if ( ! empty( $quote['manual_items'] ) ) : ?><small><?php echo esc_html( sprintf( _n( '%d tailored item', '%d tailored items', count( $quote['manual_items'] ), 'foundation-customer-form' ), count( $quote['manual_items'] ) ) ); ?></small><?php endif; ?>
								</td>
								<td data-label="<?php esc_attr_e( 'Email delivery', 'foundation-customer-form' ); ?>" class="fpc-mail-status-stack"><?php $this->render_mail_status( $record['admin_mail_status'] ?? 'pending', __( 'Team', 'foundation-customer-form' ) ); ?><?php $this->render_mail_status( $record['customer_mail_status'] ?? 'pending', __( 'Customer', 'foundation-customer-form' ) ); ?></td>
								<td data-label="<?php esc_attr_e( 'Received', 'foundation-customer-form' ); ?>"><?php echo esc_html( $this->format_enquiry_date( $record['created_at'] ?? '' ) ); ?></td>
								<td><a class="button" href="<?php echo esc_url( $detail_url ); ?>"><?php esc_html_e( 'View', 'foundation-customer-form' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php if ( $total_pages > 1 ) : ?>
					<nav class="fpc-pagination" aria-label="<?php esc_attr_e( 'Enquiry pages', 'foundation-customer-form' ); ?>">
						<span><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'foundation-customer-form' ), $page, $total_pages ) ); ?></span>
						<div>
							<?php if ( $page > 1 ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( 'enquiry_page', $page - 1, $base_url ) ); ?>"><?php esc_html_e( 'Previous', 'foundation-customer-form' ); ?></a><?php endif; ?>
							<?php if ( $page < $total_pages ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( 'enquiry_page', $page + 1, $base_url ) ); ?>"><?php esc_html_e( 'Next', 'foundation-customer-form' ); ?></a><?php endif; ?>
						</div>
					</nav>
				<?php endif; ?>
			<?php endif; ?>
		</section>
		<?php
	}

	private function render_enquiry_detail( $enquiry_id ) {
		$record = Foundation_Submissions::get_record( $enquiry_id );
		$page   = isset( $_GET['enquiry_page'] ) ? max( 1, absint( $_GET['enquiry_page'] ) ) : 1;
		$back_url = add_query_arg(
			array( 'page' => $this->page_slug, 'tab' => 'enquiries', 'enquiry_page' => $page ),
			admin_url( 'admin.php' )
		);

		if ( empty( $record ) ) {
			?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'That enquiry could not be found.', 'foundation-customer-form' ); ?></p></div>
			<p><a class="button" href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Back to enquiries', 'foundation-customer-form' ); ?></a></p>
			<?php
			return;
		}

		$contact  = (array) ( $record['contact'] ?? array() );
		$quote    = (array) ( $record['quote'] ?? array() );
		$summary  = (array) ( $record['summary'] ?? array() );
		$currency = $quote['currency'] ?? foundation_get_settings()['currency_symbol'] ?? '£';
		?>
		<div class="fpc-detail-toolbar">
			<a class="button" href="<?php echo esc_url( $back_url ); ?>">← <?php esc_html_e( 'Back to enquiries', 'foundation-customer-form' ); ?></a>
			<span><?php echo esc_html( $this->format_enquiry_date( $record['created_at'] ?? '' ) ); ?></span>
		</div>

		<div class="fpc-section-intro fpc-enquiry-detail-heading">
			<div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Stored enquiry', 'foundation-customer-form' ); ?></p><h2><?php echo esc_html( $record['reference'] ?? '' ); ?></h2><p><?php echo esc_html( $contact['company'] ?? '' ); ?> · <?php echo esc_html( $contact['name'] ?? '' ); ?></p></div>
			<div class="fpc-mail-status-stack is-horizontal"><?php $this->render_mail_status( $record['admin_mail_status'] ?? 'pending', __( 'Team email', 'foundation-customer-form' ) ); ?><?php $this->render_mail_status( $record['customer_mail_status'] ?? 'pending', __( 'Customer email', 'foundation-customer-form' ) ); ?></div>
		</div>

		<section class="fpc-admin-grid fpc-admin-grid-two fpc-enquiry-detail-grid">
			<article class="fpc-admin-card">
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Contact', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Customer details', 'foundation-customer-form' ); ?></h3>
				<dl class="fpc-definition-list fpc-contact-list">
					<div><dt><?php esc_html_e( 'Name', 'foundation-customer-form' ); ?></dt><dd><?php echo esc_html( $contact['name'] ?? '' ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Business', 'foundation-customer-form' ); ?></dt><dd><?php echo esc_html( $contact['company'] ?? '' ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Email', 'foundation-customer-form' ); ?></dt><dd><a href="mailto:<?php echo esc_attr( $contact['email'] ?? '' ); ?>"><?php echo esc_html( $contact['email'] ?? '' ); ?></a></dd></div>
					<?php if ( ! empty( $contact['phone'] ) ) : ?><div><dt><?php esc_html_e( 'Phone', 'foundation-customer-form' ); ?></dt><dd><?php echo esc_html( $contact['phone'] ); ?></dd></div><?php endif; ?>
					<?php if ( ! empty( $contact['website'] ) ) : ?><div><dt><?php esc_html_e( 'Website', 'foundation-customer-form' ); ?></dt><dd><a href="<?php echo esc_url( $contact['website'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $contact['website'] ); ?></a></dd></div><?php endif; ?>
				</dl>
				<?php if ( ! empty( $contact['notes'] ) ) : ?><div class="fpc-notes-box"><strong><?php esc_html_e( 'Additional notes', 'foundation-customer-form' ); ?></strong><p><?php echo nl2br( esc_html( $contact['notes'] ) ); ?></p></div><?php endif; ?>
			</article>

			<article class="fpc-admin-card">
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Estimate', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Pricing summary', 'foundation-customer-form' ); ?></h3>
				<div class="fpc-quote-total-grid">
					<div><span><?php esc_html_e( 'One-off estimate', 'foundation-customer-form' ); ?></span><strong><?php echo ! empty( $quote['one_off_max'] ) ? esc_html( foundation_format_quote_range( $quote['one_off_min'] ?? 0, $quote['one_off_max'] ?? 0, $currency ) ) : esc_html__( 'None calculated', 'foundation-customer-form' ); ?></strong><small><?php esc_html_e( 'excluding VAT', 'foundation-customer-form' ); ?></small></div>
					<div><span><?php esc_html_e( 'Monthly estimate', 'foundation-customer-form' ); ?></span><strong><?php echo ! empty( $quote['monthly_max'] ) ? esc_html( foundation_format_quote_range( $quote['monthly_min'] ?? 0, $quote['monthly_max'] ?? 0, $currency ) ) : esc_html__( 'None calculated', 'foundation-customer-form' ); ?></strong><small><?php esc_html_e( 'per month, excluding VAT', 'foundation-customer-form' ); ?></small></div>
				</div>
				<?php if ( ! empty( $quote['manual_items'] ) ) : ?>
					<div class="fpc-tailored-box"><strong><?php esc_html_e( 'Needs a tailored quote', 'foundation-customer-form' ); ?></strong><ul><?php foreach ( $quote['manual_items'] as $item ) : ?><li><b><?php echo esc_html( $item['label'] ?? 'Service' ); ?></b><span><?php echo esc_html( $item['note'] ?? 'Scope to be confirmed.' ); ?></span></li><?php endforeach; ?></ul></div>
				<?php endif; ?>
			</article>
		</section>

		<?php if ( ! empty( $quote['line_items'] ) ) : ?>
		<section class="fpc-admin-card fpc-detail-section">
			<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Calculation', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Priced line items', 'foundation-customer-form' ); ?></h3>
			<div class="fpc-enquiry-table-wrap"><table class="widefat striped fpc-detail-table"><thead><tr><th><?php esc_html_e( 'Service', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Billing', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Amount', 'foundation-customer-form' ); ?></th></tr></thead><tbody>
			<?php foreach ( $quote['line_items'] as $item ) : ?><tr><td><?php echo esc_html( $item['label'] ?? 'Service' ); ?></td><td><?php echo 'monthly' === ( $item['billing'] ?? '' ) ? esc_html__( 'Monthly', 'foundation-customer-form' ) : esc_html__( 'One-off', 'foundation-customer-form' ); ?></td><td><strong><?php echo esc_html( foundation_format_quote_range( $item['min'] ?? 0, $item['max'] ?? 0, $currency ) ); ?></strong></td></tr><?php endforeach; ?>
			</tbody></table></div>
		</section>
		<?php endif; ?>

		<section class="fpc-admin-card fpc-detail-section">
			<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Customer journey', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Answers supplied', 'foundation-customer-form' ); ?></h3>
			<?php if ( empty( $summary ) ) : ?><p><?php esc_html_e( 'No answer summary was stored.', 'foundation-customer-form' ); ?></p><?php else : ?>
			<div class="fpc-enquiry-table-wrap"><table class="widefat striped fpc-detail-table"><thead><tr><th><?php esc_html_e( 'Section', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Question', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Answer', 'foundation-customer-form' ); ?></th></tr></thead><tbody>
			<?php foreach ( $summary as $row ) : ?><tr><td><?php echo esc_html( $row['section'] ?? '' ); ?></td><td><strong><?php echo esc_html( $row['label'] ?? '' ); ?></strong></td><td class="fpc-answer-cell"><?php echo nl2br( esc_html( $row['value_text'] ?? $row['value'] ?? '' ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table></div>
			<?php endif; ?>
		</section>

		<?php if ( ! empty( $record['attachment_names'] ) ) : ?>
		<section class="fpc-admin-card fpc-detail-section"><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Uploads', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Attachment names', 'foundation-customer-form' ); ?></h3><ul class="fpc-attachment-list"><?php foreach ( $record['attachment_names'] as $filename ) : ?><li><?php echo esc_html( $filename ); ?></li><?php endforeach; ?></ul><p class="fpc-help"><?php esc_html_e( 'For data minimisation, this inbox stores filenames rather than duplicate uploaded files. The current Inkfire pricing journey does not ask customers for uploads.', 'foundation-customer-form' ); ?></p></section>
		<?php endif; ?>
		<?php
	}

	private function render_prices() {
		$catalog  = foundation_get_pricing_catalog();
		$sections = foundation_get_pricing_admin_sections();
		?>
		<form class="fpc-admin-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="foundation_save_prices">
			<?php wp_nonce_field( 'foundation_save_prices' ); ?>
			<div class="fpc-section-intro">
				<div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Safe price editor', 'foundation-customer-form' ); ?></p><h2><?php esc_html_e( 'Inkfire service prices', 'foundation-customer-form' ); ?></h2><p><?php esc_html_e( 'Change the amount. The questions, branching and formulas stay locked and stable.', 'foundation-customer-form' ); ?></p></div>
				<label class="fpc-search"><span class="screen-reader-text"><?php esc_html_e( 'Filter prices', 'foundation-customer-form' ); ?></span><input type="search" placeholder="<?php esc_attr_e( 'Find a service…', 'foundation-customer-form' ); ?>" data-fpc-price-search></label>
			</div>

			<?php foreach ( $sections as $section_key => $section ) : ?>
				<section class="fpc-admin-card fpc-price-section" data-fpc-price-section>
					<div class="fpc-card-heading"><div><p class="fpc-admin-eyebrow"><?php echo esc_html( ucfirst( $section_key ) ); ?></p><h3><?php echo esc_html( $section['title'] ); ?></h3><p><?php echo esc_html( $section['description'] ); ?></p></div></div>
					<div class="fpc-price-grid">
						<?php foreach ( $section['fields'] as $key => $field ) : ?>
							<label class="fpc-price-field" data-fpc-price-row data-search-text="<?php echo esc_attr( strtolower( $field['label'] . ' ' . $field['unit'] ) ); ?>">
								<span><?php echo esc_html( $field['label'] ); ?></span>
								<div class="fpc-money-input"><b aria-hidden="true">£</b><input type="number" min="0" max="1000000" step="0.01" name="foundation_pricing_catalog[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( number_format( isset( $catalog[ $key ] ) ? $catalog[ $key ] : $field['default'], 2, '.', '' ) ); ?>"><small><?php echo esc_html( $field['unit'] ); ?></small></div>
							</label>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>

			<div class="fpc-sticky-save"><p><?php esc_html_e( 'Prices are shown excluding VAT in the public estimate.', 'foundation-customer-form' ); ?></p><button class="button button-primary button-hero" type="submit"><?php esc_html_e( 'Save all prices', 'foundation-customer-form' ); ?></button></div>
		</form>
		<?php
	}

	private function render_journey( $health ) {
		$steps  = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		$groups = array( 'start' => array(), 'web' => array(), 'tech' => array(), 'business' => array() );
		foreach ( $steps as $step ) {
			$id = isset( $step['id'] ) ? $step['id'] : '';
			$group = 0 === strpos( $id, 'web_' ) ? 'web' : ( 0 === strpos( $id, 'tech_' ) ? 'tech' : ( 0 === strpos( $id, 'business_' ) ? 'business' : 'start' ) );
			$groups[ $group ][] = $step;
		}
		$backup = get_option( 'foundation_form_data_backup', array() );
		?>
		<div class="fpc-section-intro"><div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Read-only journey map', 'foundation-customer-form' ); ?></p><h2><?php esc_html_e( 'What customers will be asked', 'foundation-customer-form' ); ?></h2><p><?php esc_html_e( 'Prices are edited on the Prices tab. The compiled flow is kept read-only so an everyday edit cannot snap a route.', 'foundation-customer-form' ); ?></p></div><span class="fpc-status-pill <?php echo $health['ok'] ? 'is-good' : 'is-warning'; ?>"><?php echo $health['ok'] ? esc_html__( 'All routes connected', 'foundation-customer-form' ) : esc_html__( 'Flow needs attention', 'foundation-customer-form' ); ?></span></div>

		<?php if ( ! $health['ok'] ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( implode( ' ', $health['issues'] ) ); ?></p></div><?php endif; ?>

		<?php
		$group_names = array(
			'start'    => __( 'Start', 'foundation-customer-form' ),
			'web'      => __( 'Web & Accessibility', 'foundation-customer-form' ),
			'tech'     => __( 'Tech & Support', 'foundation-customer-form' ),
			'business' => __( 'Business Support & Marketing', 'foundation-customer-form' ),
		);
		foreach ( $groups as $group_key => $group_steps ) :
			if ( empty( $group_steps ) ) {
				continue;
			}
			?>
			<section class="fpc-journey-group">
				<h3><?php echo esc_html( $group_names[ $group_key ] ); ?></h3>
				<div class="fpc-journey-grid">
					<?php foreach ( $group_steps as $step ) : $this->render_journey_step( $step ); endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

		<section class="fpc-admin-grid fpc-admin-grid-two fpc-danger-zone">
			<article class="fpc-admin-card">
				<h3><?php esc_html_e( 'Reapply Inkfire blueprint', 'foundation-customer-form' ); ?></h3>
				<p><?php esc_html_e( 'Use this when the journey itself has become damaged. The current version is backed up first. Your prices and email settings are not changed.', 'foundation-customer-form' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-fpc-confirm="<?php esc_attr_e( 'Replace the current journey with the bundled Inkfire blueprint?', 'foundation-customer-form' ); ?>">
					<input type="hidden" name="action" value="foundation_apply_blueprint"><?php wp_nonce_field( 'foundation_apply_blueprint' ); ?>
					<button class="button" type="submit"><?php esc_html_e( 'Reapply blueprint', 'foundation-customer-form' ); ?></button>
				</form>
			</article>
			<article class="fpc-admin-card">
				<h3><?php esc_html_e( 'Restore previous journey', 'foundation-customer-form' ); ?></h3>
				<p><?php echo ! empty( $backup['created_at'] ) ? esc_html( sprintf( __( 'Backup created %s.', 'foundation-customer-form' ), $backup['created_at'] ) ) : esc_html__( 'No backup is currently available.', 'foundation-customer-form' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-fpc-confirm="<?php esc_attr_e( 'Restore the previous journey? The current journey will become the new backup.', 'foundation-customer-form' ); ?>">
					<input type="hidden" name="action" value="foundation_restore_blueprint_backup"><?php wp_nonce_field( 'foundation_restore_blueprint_backup' ); ?>
					<button class="button" type="submit" <?php disabled( empty( $backup['form_data'] ) ); ?>><?php esc_html_e( 'Restore backup', 'foundation-customer-form' ); ?></button>
				</form>
			</article>
		</section>
		<?php
	}

	private function render_journey_step( $step ) {
		?>
		<article class="fpc-journey-step">
			<div class="fpc-journey-number"><?php echo esc_html( strtoupper( substr( $step['id'], 0, 1 ) ) ); ?></div>
			<div class="fpc-journey-content">
				<div class="fpc-card-heading"><div><h4><?php echo esc_html( $step['title'] ); ?></h4><p><?php echo esc_html( $step['subtitle'] ); ?></p></div><?php if ( ! empty( $step['is_conditional'] ) ) : ?><span class="fpc-mini-pill"><?php esc_html_e( 'Conditional', 'foundation-customer-form' ); ?></span><?php endif; ?></div>
				<ul>
					<?php foreach ( $step['fields'] as $field ) : ?>
						<?php if ( 'calculation' === $field['type'] ) : continue; endif; ?>
						<li><strong><?php echo esc_html( $field['label'] ? $field['label'] : $field['line_item_label'] ); ?></strong>
							<?php if ( ! empty( $field['options'] ) ) : ?><span><?php echo esc_html( implode( ' · ', wp_list_pluck( $field['options'], 'label' ) ) ); ?></span><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</article>
		<?php
	}

	private function render_emails_branding() {
		$settings = foundation_get_settings();
		?>
		<div class="fpc-admin-grid fpc-admin-grid-two">
			<form class="fpc-admin-card fpc-admin-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="foundation_save_settings"><input type="hidden" name="foundation_settings_section" value="emails"><?php wp_nonce_field( 'foundation_save_settings' ); ?>
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Delivery', 'foundation-customer-form' ); ?></p><h2><?php esc_html_e( 'Email notifications', 'foundation-customer-form' ); ?></h2>
				<?php $this->input_field( 'admin_email', __( 'Send new enquiries to', 'foundation-customer-form' ), $settings['admin_email'], 'email', true ); ?>
				<?php $this->input_field( 'cc_emails', __( 'CC addresses', 'foundation-customer-form' ), $settings['cc_emails'], 'text', false, __( 'Comma-separated.', 'foundation-customer-form' ) ); ?>
				<?php $this->input_field( 'from_name', __( 'Sender name', 'foundation-customer-form' ), $settings['from_name'] ); ?>
				<?php $this->input_field( 'from_email', __( 'Sender email', 'foundation-customer-form' ), $settings['from_email'], 'email' ); ?>
				<?php $this->input_field( 'admin_subject_prefix', __( 'Admin subject prefix', 'foundation-customer-form' ), $settings['admin_subject_prefix'] ); ?>
				<?php $this->input_field( 'customer_subject', __( 'Customer subject', 'foundation-customer-form' ), $settings['customer_subject'] ); ?>
				<?php $this->checkbox_field( 'customer_confirmation_enabled', __( 'Send the customer a copy of their estimate', 'foundation-customer-form' ), ! empty( $settings['customer_confirmation_enabled'] ) ); ?>
				<?php $this->textarea_field( 'customer_intro', __( 'Customer email introduction', 'foundation-customer-form' ), $settings['customer_intro'] ); ?>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Save email settings', 'foundation-customer-form' ); ?></button>
			</form>

			<form class="fpc-admin-card fpc-admin-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="foundation_save_settings"><input type="hidden" name="foundation_settings_section" value="branding"><?php wp_nonce_field( 'foundation_save_settings' ); ?>
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Customer experience', 'foundation-customer-form' ); ?></p><h2><?php esc_html_e( 'Branding and estimate copy', 'foundation-customer-form' ); ?></h2>
				<?php $this->input_field( 'launch_button_label', __( 'Launch button', 'foundation-customer-form' ), $settings['launch_button_label'] ); ?>
				<?php $this->input_field( 'wizard_title', __( 'Calculator title', 'foundation-customer-form' ), $settings['wizard_title'] ); ?>
				<?php $this->input_field( 'currency_symbol', __( 'Currency symbol', 'foundation-customer-form' ), $settings['currency_symbol'] ); ?>
				<?php $this->textarea_field( 'vat_note', __( 'VAT note', 'foundation-customer-form' ), $settings['vat_note'], 2 ); ?>
				<?php $this->textarea_field( 'estimate_disclaimer', __( 'Estimate disclaimer', 'foundation-customer-form' ), $settings['estimate_disclaimer'], 3 ); ?>
				<?php $this->checkbox_field( 'show_live_summary', __( 'Show a live estimate summary while customers answer', 'foundation-customer-form' ), ! empty( $settings['show_live_summary'] ) ); ?>
				<?php $this->checkbox_field( 'phone_required', __( 'Require a phone number', 'foundation-customer-form' ), ! empty( $settings['phone_required'] ) ); ?>
				<?php $this->input_field( 'privacy_policy_url', __( 'Privacy policy URL', 'foundation-customer-form' ), $settings['privacy_policy_url'], 'url' ); ?>
				<?php $this->textarea_field( 'privacy_consent_label', __( 'Privacy consent wording', 'foundation-customer-form' ), $settings['privacy_consent_label'], 2 ); ?>
				<?php $this->input_field( 'logo_url', __( 'Logo URL', 'foundation-customer-form' ), $settings['logo_url'], 'url' ); ?>
				<?php $this->textarea_field( 'success_message', __( 'Success message', 'foundation-customer-form' ), $settings['success_message'], 3 ); ?>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Save customer experience', 'foundation-customer-form' ); ?></button>
			</form>
		</div>
		<?php
	}

	private function render_advanced() {
		$settings = foundation_get_settings();
		?>
		<div class="fpc-admin-grid fpc-admin-grid-two">
			<form class="fpc-admin-card fpc-admin-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="foundation_save_settings"><input type="hidden" name="foundation_settings_section" value="advanced"><?php wp_nonce_field( 'foundation_save_settings' ); ?>
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Operational controls', 'foundation-customer-form' ); ?></p><h2><?php esc_html_e( 'Uploads, drafts and safeguards', 'foundation-customer-form' ); ?></h2>
				<?php $this->checkbox_field( 'quote_mode_enabled', __( 'Quote-only mode: hide calculated prices', 'foundation-customer-form' ), ! empty( $settings['quote_mode_enabled'] ) ); ?>
				<?php $this->input_field( 'allowed_file_types', __( 'Allowed upload extensions', 'foundation-customer-form' ), $settings['allowed_file_types'], 'text', false, __( 'SVG and executable formats should remain excluded.', 'foundation-customer-form' ) ); ?>
				<?php $this->input_field( 'max_file_size_mb', __( 'Maximum file size (MB)', 'foundation-customer-form' ), $settings['max_file_size_mb'], 'number' ); ?>
				<?php $this->input_field( 'max_total_upload_mb', __( 'Maximum total upload (MB)', 'foundation-customer-form' ), $settings['max_total_upload_mb'], 'number' ); ?>
				<?php $this->input_field( 'max_files_per_field', __( 'Maximum files per upload question', 'foundation-customer-form' ), $settings['max_files_per_field'], 'number' ); ?>
				<?php $this->input_field( 'draft_retention_days', __( 'Saved-draft retention (days)', 'foundation-customer-form' ), $settings['draft_retention_days'], 'number' ); ?>
				<?php $this->input_field( 'lead_retention_days', __( 'Stored-enquiry retention (days)', 'foundation-customer-form' ), $settings['lead_retention_days'], 'number', false, __( 'Private enquiry records are deleted automatically after this period. Minimum 30 days.', 'foundation-customer-form' ) ); ?>
				<?php $this->input_field( 'submission_cooldown_seconds', __( 'Repeat-submission cooldown (seconds)', 'foundation-customer-form' ), $settings['submission_cooldown_seconds'], 'number' ); ?>
				<?php $this->checkbox_field( 'attach_pdf_summary', __( 'Attach PDF summary to staff email', 'foundation-customer-form' ), ! empty( $settings['attach_pdf_summary'] ) ); ?>
				<?php $this->checkbox_field( 'attach_json_summary', __( 'Attach JSON summary to staff email', 'foundation-customer-form' ), ! empty( $settings['attach_json_summary'] ) ); ?>
				<?php $this->checkbox_field( 'attach_zip_package', __( 'Attach a ZIP package to staff email', 'foundation-customer-form' ), ! empty( $settings['attach_zip_package'] ) ); ?>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Save advanced settings', 'foundation-customer-form' ); ?></button>
			</form>
			<article class="fpc-admin-card">
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Maintenance', 'foundation-customer-form' ); ?></p><h2><?php esc_html_e( 'Backups and metrics', 'foundation-customer-form' ); ?></h2>
				<p><?php esc_html_e( 'Export a human-readable JSON snapshot before a deployment. Notification addresses are omitted from the export.', 'foundation-customer-form' ); ?></p>
				<form class="fpc-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="foundation_export_configuration"><?php wp_nonce_field( 'foundation_export_configuration' ); ?><button class="button" type="submit"><?php esc_html_e( 'Export configuration', 'foundation-customer-form' ); ?></button></form>
				<hr>
				<p><?php esc_html_e( 'Reset only the anonymous counters shown on the Overview tab. This does not change prices or customer settings.', 'foundation-customer-form' ); ?></p>
				<form class="fpc-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-fpc-confirm="<?php esc_attr_e( 'Reset all calculator metrics?', 'foundation-customer-form' ); ?>"><input type="hidden" name="action" value="foundation_reset_metrics"><?php wp_nonce_field( 'foundation_reset_metrics' ); ?><button class="button" type="submit"><?php esc_html_e( 'Reset metrics', 'foundation-customer-form' ); ?></button></form>
				<hr>
				<p><strong><?php esc_html_e( 'Version', 'foundation-customer-form' ); ?>:</strong> <?php echo esc_html( FOUNDATION_VERSION ); ?><br><strong><?php esc_html_e( 'Blueprint', 'foundation-customer-form' ); ?>:</strong> <?php echo esc_html( get_option( 'foundation_blueprint_version', __( 'Custom', 'foundation-customer-form' ) ) ); ?></p>
			</article>
		</div>
		<?php
	}

	private function input_field( $key, $label, $value, $type = 'text', $required = false, $help = '' ) {
		?>
		<label class="fpc-field"><span><?php echo esc_html( $label ); ?></span><input type="<?php echo esc_attr( $type ); ?>" name="foundation_form_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" <?php echo $required ? 'required' : ''; ?>><?php if ( $help ) : ?><small><?php echo esc_html( $help ); ?></small><?php endif; ?></label>
		<?php
	}

	private function textarea_field( $key, $label, $value, $rows = 4 ) {
		?>
		<label class="fpc-field"><span><?php echo esc_html( $label ); ?></span><textarea rows="<?php echo esc_attr( $rows ); ?>" name="foundation_form_settings[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $value ); ?></textarea></label>
		<?php
	}

	private function checkbox_field( $key, $label, $checked ) {
		?>
		<label class="fpc-check"><input type="checkbox" name="foundation_form_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $checked ); ?>><span><?php echo esc_html( $label ); ?></span></label>
		<?php
	}
}
