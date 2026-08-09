<?php
/**
 * Simple, server-rendered admin dashboard for the Inkfire calculator.
 *
 * The old React builder exposed implementation details and made ordinary changes
 * risky. The 1.5 dashboard keeps pricing/server rules protected while providing
 * a structured, AJAX-powered Visual Journey Editor for safe everyday changes.
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
		add_action( 'wp_ajax_foundation_journey_save_step', array( $this, 'ajax_journey_save_step' ) );
		add_action( 'wp_ajax_foundation_journey_add_step', array( $this, 'ajax_journey_add_step' ) );
		add_action( 'wp_ajax_foundation_journey_duplicate_step', array( $this, 'ajax_journey_duplicate_step' ) );
		add_action( 'wp_ajax_foundation_journey_reorder', array( $this, 'ajax_journey_reorder' ) );
		add_action( 'wp_ajax_foundation_journey_delete_draft', array( $this, 'ajax_journey_delete_draft' ) );
		add_action( 'wp_ajax_foundation_journey_undo', array( $this, 'ajax_journey_undo' ) );
		add_action( 'wp_ajax_foundation_journey_save_media', array( $this, 'ajax_journey_save_media' ) );
		add_action( 'wp_ajax_foundation_lead_update', array( $this, 'ajax_lead_update' ) );
		add_action( 'wp_ajax_foundation_lead_delete', array( $this, 'ajax_lead_delete' ) );
		add_action( 'wp_ajax_foundation_lead_resend_magic', array( $this, 'ajax_lead_resend_magic' ) );
		add_action( 'wp_ajax_foundation_lead_clear_archived', array( $this, 'ajax_lead_clear_archived' ) );
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
		wp_enqueue_media();
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
		wp_localize_script(
			'foundation-calculator-admin',
			'FoundationJourneyAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'foundation_journey_builder' ),
				'strings' => array(
					'error'                => __( 'That change could not be saved. Please try again.', 'foundation-customer-form' ),
					'deleteDraftConfirm'   => __( 'Delete this unconnected draft screen? This cannot be undone after another builder change.', 'foundation-customer-form' ),
					'removeOptionConfirm'  => __( 'This choice currently routes to another screen. Removing it will also remove that route. Continue?', 'foundation-customer-form' ),
					'deleteLeadConfirm'    => __( 'Permanently delete this lead record? This cannot be undone.', 'foundation-customer-form' ),
					'clearArchivedConfirm' => __( 'Permanently delete every archived calculator lead? This cannot be undone.', 'foundation-customer-form' ),
					'mediaTitle'           => __( 'Choose a calculator image', 'foundation-customer-form' ),
					'mediaButton'          => __( 'Use this image', 'foundation-customer-form' ),
				),
			)
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
			'advanced' => array( 'quote_mode_enabled', 'attach_pdf_summary', 'attach_json_summary', 'attach_zip_package', 'early_capture_enabled', 'marketing_opt_in_enabled', 'turnstile_enabled' ),
		);
		if ( isset( $checkboxes_by_section[ $section ] ) ) {
			foreach ( $checkboxes_by_section[ $section ] as $key ) {
				$input[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
			}
		}

		// The secret field is rendered blank (never echoed back to the browser),
		// so an empty submission means "leave the stored secret unchanged", not
		// "clear it" — only overwrite when a genuinely new value was typed.
		if ( isset( $input['turnstile_secret_key'] ) && '' === trim( (string) $input['turnstile_secret_key'] ) ) {
			unset( $input['turnstile_secret_key'] );
		}

		$settings = foundation_sanitize_settings( array_merge( $current, $input ) );
		update_option( 'foundation_form_settings', $settings, false );

		if ( 'journey' === $section ) {
			$this->redirect( 'journey', 'settings_saved' );
		}

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
					'created_at'         => current_time( 'mysql' ),
					'version'            => get_option( 'foundation_blueprint_version', '' ),
					'form_data'          => $current,
					'builder_customized' => (bool) get_option( 'foundation_journey_builder_customized', false ),
				),
				false
			);
			update_option( 'foundation_form_data', foundation_normalize_form_data( $backup['form_data'] ), false );
			update_option( 'foundation_blueprint_version', isset( $backup['version'] ) ? sanitize_text_field( $backup['version'] ) : '', false );
			if ( ! empty( $backup['builder_customized'] ) ) {
				update_option( 'foundation_journey_builder_customized', 1, false );
			} else {
				delete_option( 'foundation_journey_builder_customized' );
			}
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
			'journey_editor'    => array(
				'customized' => (bool) get_option( 'foundation_journey_builder_customized', false ),
				'revision'   => max( 0, intval( get_option( 'foundation_journey_builder_revision', 0 ) ) ),
				'updated_at' => sanitize_text_field( get_option( 'foundation_journey_builder_updated_at', '' ) ),
			),
			'settings'          => foundation_get_settings(),
		);
		// Omit notification addresses and security secrets from support/export packages.
		foreach ( array( 'admin_email', 'cc_emails', 'from_email', 'turnstile_secret_key' ) as $key ) {
			unset( $payload['settings'][ $key ] );
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="foundation-project-calculator-config-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}


	private function check_journey_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this journey.', 'foundation-customer-form' ) ), 403 );
		}
		check_ajax_referer( 'foundation_journey_builder', 'nonce' );
		if ( foundation_get_blueprint_version() !== (string) get_option( 'foundation_blueprint_version', '' ) ) {
			wp_send_json_error( array( 'message' => __( 'The installed journey is not based on the current Inkfire blueprint. Reapply the blueprint before using the visual editor.', 'foundation-customer-form' ) ), 409 );
		}
	}

	private function commit_journey_steps( $new_steps ) {
		$current           = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		$previous_custom   = (bool) get_option( 'foundation_journey_builder_customized', false );
		$previous_revision = max( 0, intval( get_option( 'foundation_journey_builder_revision', 0 ) ) );
		$new_steps         = foundation_normalize_form_data( $new_steps );

		update_option( 'foundation_form_data', $new_steps, false );
		update_option( 'foundation_journey_builder_customized', 1, false );
		$health = foundation_get_blueprint_health();
		if ( empty( $health['structure_ok'] ) ) {
			update_option( 'foundation_form_data', $current, false );
			if ( $previous_custom ) {
				update_option( 'foundation_journey_builder_customized', 1, false );
			} else {
				delete_option( 'foundation_journey_builder_customized' );
			}
			wp_send_json_error(
				array(
					'message' => __( 'That edit would break the journey structure, so it was not saved.', 'foundation-customer-form' ),
					'issues'  => isset( $health['structure_issues'] ) ? $health['structure_issues'] : array(),
				),
				422
			);
		}

		update_option(
			'foundation_form_data_builder_backup',
			array(
				'created_at'         => current_time( 'mysql' ),
				'form_data'          => $current,
				'builder_customized' => $previous_custom,
				'revision'           => $previous_revision,
			),
			false
		);
		update_option( 'foundation_journey_builder_revision', $previous_revision + 1, false );
		update_option( 'foundation_journey_builder_updated_at', current_time( 'mysql' ), false );
		return foundation_get_blueprint_health();
	}

	private function journey_builder_html( $health = null ) {
		if ( null === $health ) {
			$health = foundation_get_blueprint_health();
		}
		ob_start();
		$this->render_journey_builder( $health );
		return (string) ob_get_clean();
	}

	private function send_journey_ajax_success( $message, $focus_step_id = '' ) {
		$health = foundation_get_blueprint_health();
		wp_send_json_success(
			array(
				'message'       => sanitize_text_field( $message ),
				'focus_step_id' => sanitize_key( $focus_step_id ),
				'builder_html'  => $this->journey_builder_html( $health ),
				'health'        => $health,
			)
		);
	}

	public function ajax_journey_save_step() {
		$this->check_journey_ajax();
		$step_id = isset( $_POST['step_id'] ) ? sanitize_key( wp_unslash( $_POST['step_id'] ) ) : '';
		$payload_json = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
		$payload = json_decode( (string) $payload_json, true );
		if ( '' === $step_id || ! is_array( $payload ) ) {
			wp_send_json_error( array( 'message' => __( 'The screen data was incomplete.', 'foundation-customer-form' ) ), 400 );
		}
		$steps = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		$exists = false;
		foreach ( $steps as $step ) {
			if ( ( isset( $step['id'] ) ? $step['id'] : '' ) === $step_id ) {
				$exists = true;
				break;
			}
		}
		if ( ! $exists ) {
			wp_send_json_error( array( 'message' => __( 'That screen no longer exists.', 'foundation-customer-form' ) ), 404 );
		}
		$steps = foundation_journey_patch_step( $steps, $step_id, $payload );
		$is_conditional = ! empty( $payload['is_conditional'] );
		$connections = $is_conditional && ! empty( $payload['connections'] ) && is_array( $payload['connections'] ) ? $payload['connections'] : array();
		$steps = foundation_journey_set_incoming_connections( $steps, $step_id, $connections );
		$this->commit_journey_steps( $steps );
		$this->send_journey_ajax_success( __( 'Screen saved.', 'foundation-customer-form' ), $step_id );
	}

	public function ajax_journey_add_step() {
		$this->check_journey_ajax();
		$group = isset( $_POST['group'] ) ? sanitize_key( wp_unslash( $_POST['group'] ) ) : 'start';
		$after_step_id = isset( $_POST['after_step_id'] ) ? sanitize_key( wp_unslash( $_POST['after_step_id'] ) ) : '';
		$result = foundation_journey_create_step( get_option( 'foundation_form_data', array() ), $group, $after_step_id );
		$this->commit_journey_steps( $result['steps'] );
		$this->send_journey_ajax_success( __( 'New draft screen added. Connect it when it is ready.', 'foundation-customer-form' ), $result['step_id'] );
	}

	public function ajax_journey_duplicate_step() {
		$this->check_journey_ajax();
		$step_id = isset( $_POST['step_id'] ) ? sanitize_key( wp_unslash( $_POST['step_id'] ) ) : '';
		$result = foundation_journey_duplicate_step( get_option( 'foundation_form_data', array() ), $step_id );
		if ( empty( $result['step_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'The screen could not be found.', 'foundation-customer-form' ) ), 404 );
		}
		$this->commit_journey_steps( $result['steps'] );
		$this->send_journey_ajax_success( __( 'Screen duplicated as an unconnected draft. Its pricing rules were copied, so review them before connecting it.', 'foundation-customer-form' ), $result['step_id'] );
	}

	public function ajax_journey_reorder() {
		$this->check_journey_ajax();
		$group = isset( $_POST['group'] ) ? sanitize_key( wp_unslash( $_POST['group'] ) ) : '';
		$order_json = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : '';
		$order = json_decode( (string) $order_json, true );
		if ( ! is_array( $order ) || ! in_array( $group, foundation_journey_allowed_groups(), true ) ) {
			wp_send_json_error( array( 'message' => __( 'The requested screen order was invalid.', 'foundation-customer-form' ) ), 400 );
		}
		$steps = foundation_journey_reorder_group( get_option( 'foundation_form_data', array() ), $group, $order );
		$this->commit_journey_steps( $steps );
		$this->send_journey_ajax_success( __( 'Screen order saved.', 'foundation-customer-form' ) );
	}

	public function ajax_journey_delete_draft() {
		$this->check_journey_ajax();
		$step_id = isset( $_POST['step_id'] ) ? sanitize_key( wp_unslash( $_POST['step_id'] ) ) : '';
		$steps   = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		if ( ! foundation_journey_is_custom_step_id( $step_id ) || ! empty( foundation_journey_incoming_connections( $steps, $step_id ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Only unconnected screens created in the Journey Editor can be deleted here. Disconnect it first.', 'foundation-customer-form' ) ), 409 );
		}
		$found = false;
		foreach ( $steps as $index => $step ) {
			if ( ( isset( $step['id'] ) ? $step['id'] : '' ) === $step_id ) {
				unset( $steps[ $index ] );
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			wp_send_json_error( array( 'message' => __( 'That draft screen no longer exists.', 'foundation-customer-form' ) ), 404 );
		}
		$this->commit_journey_steps( array_values( $steps ) );
		$this->send_journey_ajax_success( __( 'Draft screen deleted.', 'foundation-customer-form' ) );
	}

	public function ajax_journey_undo() {
		$this->check_journey_ajax();
		$backup = get_option( 'foundation_form_data_builder_backup', array() );
		if ( ! is_array( $backup ) || empty( $backup['form_data'] ) || ! is_array( $backup['form_data'] ) ) {
			wp_send_json_error( array( 'message' => __( 'There is no Journey Editor change to undo yet.', 'foundation-customer-form' ) ), 404 );
		}
		$current = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		$current_custom = (bool) get_option( 'foundation_journey_builder_customized', false );
		$current_revision = max( 0, intval( get_option( 'foundation_journey_builder_revision', 0 ) ) );
		update_option( 'foundation_form_data', foundation_normalize_form_data( $backup['form_data'] ), false );
		if ( ! empty( $backup['builder_customized'] ) ) {
			update_option( 'foundation_journey_builder_customized', 1, false );
		} else {
			delete_option( 'foundation_journey_builder_customized' );
		}
		update_option(
			'foundation_form_data_builder_backup',
			array(
				'created_at'         => current_time( 'mysql' ),
				'form_data'          => $current,
				'builder_customized' => $current_custom,
				'revision'           => $current_revision,
			),
			false
		);
		update_option( 'foundation_journey_builder_revision', $current_revision + 1, false );
		update_option( 'foundation_journey_builder_updated_at', current_time( 'mysql' ), false );
		$this->send_journey_ajax_success( __( 'Last Journey Editor change undone. You can use Undo again to swap back.', 'foundation-customer-form' ) );
	}


	private function check_dashboard_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to manage calculator leads.', 'foundation-customer-form' ) ), 403 );
		}
		check_ajax_referer( 'foundation_journey_builder', 'nonce' );
	}

	public function ajax_journey_save_media() {
		$this->check_dashboard_ajax();
		$key = isset( $_POST['setting_key'] ) ? sanitize_key( wp_unslash( $_POST['setting_key'] ) ) : '';
		$allowed = array( 'intro_image_url', 'testimonial_image_url', 'success_image_url' );
		if ( ! in_array( $key, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'That image slot is not editable here.', 'foundation-customer-form' ) ), 422 );
		}
		$url = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		if ( ! empty( $_POST['image_url'] ) && '' === $url ) {
			wp_send_json_error( array( 'message' => __( 'Choose a valid WordPress media image.', 'foundation-customer-form' ) ), 422 );
		}
		$settings = foundation_get_settings();
		$settings[ $key ] = $url;
		update_option( 'foundation_form_settings', foundation_sanitize_settings( $settings ), false );
		wp_send_json_success( array( 'message' => $url ? __( 'Journey image updated.', 'foundation-customer-form' ) : __( 'Journey image removed.', 'foundation-customer-form' ), 'setting_key' => $key, 'image_url' => $url ) );
	}

	private function send_admin_magic_link( $brief_id ) {
		$brief_id = absint( $brief_id );
		$record   = Foundation_Submissions::get_brief_record( $brief_id );
		if ( empty( $record ) ) {
			return new WP_Error( 'foundation_missing_brief', __( 'That saved project brief could not be found.', 'foundation-customer-form' ) );
		}
		$contact = (array) ( $record['contact'] ?? array() );
		$email   = sanitize_email( $contact['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'foundation_invalid_brief_email', __( 'Add a valid customer email before resending the magic link.', 'foundation-customer-form' ) );
		}
		$cooldown_key = 'foundation_admin_magic_' . $brief_id;
		if ( get_transient( $cooldown_key ) ) {
			return new WP_Error( 'foundation_admin_magic_cooldown', __( 'A magic link was just sent. Wait a few seconds before sending another.', 'foundation-customer-form' ) );
		}

		$settings = foundation_get_settings();
		$old_transient_key = sanitize_text_field( (string) get_post_meta( $brief_id, '_foundation_brief_transient_key', true ) );

		$token = foundation_generate_resume_token();
		$payload = array(
			'contact'      => $contact,
			'selections'   => (array) ( $record['selections'] ?? array() ),
			'current_step' => max( -1, min( 200, intval( $record['current_step'] ?? -1 ) ) ),
			'updated_at'   => current_time( 'mysql' ),
		);
		$retention = max( 1, min( 90, absint( $settings['draft_retention_days'] ?? 30 ) ) ) * DAY_IN_SECONDS;
		$transient_key = foundation_get_draft_transient_key( $token );
		set_transient( $transient_key, $payload, $retention );

		$resume_url = foundation_get_resume_url( $token, home_url( '/' ) );
		$headers    = foundation_build_mail_headers( $settings );
		$name       = trim( sanitize_text_field( $contact['name'] ?? '' ) );
		$body  = '<p>Hi ' . esc_html( $name ? $name : __( 'there', 'foundation-customer-form' ) ) . ',</p>';
		$body .= '<p>Your Inkfire Project Brief is saved. Use the private button below to continue exactly where you left off.</p>';
		$body .= '<p><a href="' . esc_url( $resume_url ) . '" style="display:inline-block;background:#075e53;color:#fff;border-radius:999px;padding:13px 22px;text-decoration:none;font-weight:bold;">Continue my project brief</a></p>';
		$body .= '<p style="color:#5e6475;font-size:13px;">This private link expires in ' . esc_html( max( 1, absint( $settings['draft_retention_days'] ?? 30 ) ) ) . ' days.</p>';
		$sent = wp_mail( $email, __( 'Your Inkfire Project Brief is saved', 'foundation-customer-form' ), $body, $headers );
		if ( ! $sent ) {
			delete_transient( $transient_key );
			return new WP_Error( 'foundation_magic_mail_failed', __( 'The existing brief is still safe, but WordPress could not hand the new magic-link email to the mail provider.', 'foundation-customer-form' ) );
		}
		if ( '' !== $old_transient_key && $old_transient_key !== $transient_key ) {
			delete_transient( $old_transient_key );
		}
		update_post_meta( $brief_id, '_foundation_brief_token_hash', Foundation_Submissions::hash_token( $token ) );
		update_post_meta( $brief_id, '_foundation_brief_transient_key', $transient_key );
		update_post_meta( $brief_id, '_foundation_brief_updated_at', current_time( 'mysql' ) );
		update_post_meta( $brief_id, '_foundation_brief_magic_link_last_sent', current_time( 'mysql' ) );
		set_transient( $cooldown_key, 1, 10 );
		return true;
	}

	public function ajax_lead_update() {
		$this->check_dashboard_ajax();
		$record_type = isset( $_POST['record_type'] ) ? sanitize_key( wp_unslash( $_POST['record_type'] ) ) : '';
		$record_id   = isset( $_POST['record_id'] ) ? absint( $_POST['record_id'] ) : 0;
		if ( ! in_array( $record_type, array( 'brief', 'enquiry' ), true ) || $record_id < 1 ) {
			wp_send_json_error( array( 'message' => __( 'That lead record is not valid.', 'foundation-customer-form' ) ), 422 );
		}

		if ( isset( $_POST['status'] ) ) {
			$status = sanitize_key( wp_unslash( $_POST['status'] ) );
			if ( ! Foundation_Submissions::update_workflow_status( $record_id, $record_type, $status ) ) {
				wp_send_json_error( array( 'message' => __( 'That workflow status could not be saved.', 'foundation-customer-form' ) ), 422 );
			}
		}

		$email_changed  = false;
		$resend_error   = '';
		if ( isset( $_POST['email'] ) ) {
			$email = sanitize_email( wp_unslash( $_POST['email'] ) );
			$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$before = 'brief' === $record_type ? Foundation_Submissions::get_brief_record( $record_id ) : Foundation_Submissions::get_record( $record_id );
			$old_email = sanitize_email( $before['contact']['email'] ?? '' );
			$updated = Foundation_Submissions::update_lead_contact( $record_id, $record_type, $email, $name );
			if ( is_wp_error( $updated ) ) {
				wp_send_json_error( array( 'message' => $updated->get_error_message() ), 422 );
			}
			$email_changed = strtolower( $old_email ) !== strtolower( $email );

			// Auto-send a replacement magic link the moment the address is
			// corrected, rather than leaving it to a separate manual click. The
			// previous link is left intact by update_lead_contact() above and is
			// only retired by send_admin_magic_link() once this send succeeds, so
			// a failed send here never leaves the customer with zero valid link.
			if ( $email_changed && 'brief' === $record_type ) {
				$resend = $this->send_admin_magic_link( $record_id );
				if ( is_wp_error( $resend ) ) {
					$resend_error = $resend->get_error_message();
				}
			}
		}

		$status = Foundation_Submissions::get_workflow_status( $record_id, $record_type );
		$labels = Foundation_Submissions::workflow_statuses();
		if ( $email_changed && 'brief' === $record_type ) {
			$message = '' !== $resend_error
				? sprintf(
					/* translators: %s: reason the replacement magic link could not be sent. */
					__( 'Customer details updated, but the replacement magic link could not be sent (%s). The previous link still works — use Resend once this is fixed.', 'foundation-customer-form' ),
					$resend_error
				)
				: __( 'Customer details updated and a new private magic link has been sent to the corrected address.', 'foundation-customer-form' );
		} else {
			$message = __( 'Lead updated.', 'foundation-customer-form' );
		}
		wp_send_json_success( array(
			'message'       => $message,
			'status'        => $status,
			'status_label'  => $labels[ $status ] ?? ucfirst( str_replace( '_', ' ', $status ) ),
			'email_changed' => $email_changed,
			'resend_error'  => $resend_error,
		) );
	}

	public function ajax_lead_resend_magic() {
		$this->check_dashboard_ajax();
		$brief_id = isset( $_POST['record_id'] ) ? absint( $_POST['record_id'] ) : 0;
		$result   = $this->send_admin_magic_link( $brief_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 422 );
		}
		wp_send_json_success( array( 'message' => __( 'A fresh private magic link has been sent to the customer.', 'foundation-customer-form' ) ) );
	}

	public function ajax_lead_delete() {
		$this->check_dashboard_ajax();
		$record_type = isset( $_POST['record_type'] ) ? sanitize_key( wp_unslash( $_POST['record_type'] ) ) : '';
		$record_id   = isset( $_POST['record_id'] ) ? absint( $_POST['record_id'] ) : 0;
		if ( ! Foundation_Submissions::delete_lead( $record_id, $record_type ) ) {
			wp_send_json_error( array( 'message' => __( 'That lead could not be deleted.', 'foundation-customer-form' ) ), 404 );
		}
		wp_send_json_success( array( 'message' => __( 'Lead permanently deleted.', 'foundation-customer-form' ) ) );
	}

	public function ajax_lead_clear_archived() {
		$this->check_dashboard_ajax();
		$deleted = Foundation_Submissions::clear_archived();
		wp_send_json_success( array( 'message' => sprintf( _n( '%d archived lead deleted.', '%d archived leads deleted.', $deleted, 'foundation-customer-form' ), $deleted ), 'deleted' => $deleted ) );
	}

	private function get_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		return in_array( $tab, array( 'overview', 'enquiries', 'prices', 'journey', 'emails', 'advanced' ), true ) ? $tab : 'overview';
	}

	private function get_dashboard_data() {
		$metrics = foundation_get_metrics();
		$views   = max( 0, intval( $metrics['form_views'] ?? 0 ) );
		$starts  = max( 0, intval( $metrics['form_starts'] ?? 0 ) );
		$complete = max( 0, intval( $metrics['responses_saved'] ?? 0 ) );
		$saved   = max( 0, intval( $metrics['saved_drafts'] ?? 0 ) );
		$incomplete = max( 0, intval( $metrics['incomplete'] ?? 0 ) );
		$failures = max( 0, intval( $metrics['failures'] ?? 0 ) );
		$failure_at = sanitize_text_field( $metrics['last_failure_at'] ?? '' );
		$success_at = sanitize_text_field( $metrics['last_success_at'] ?? '' );
		$active_failure = '';
		if ( ! empty( $metrics['last_failure'] ) && $failure_at ) {
			$failure_ts = strtotime( $failure_at );
			$success_ts = $success_at ? strtotime( $success_at ) : 0;
			if ( $failure_ts && ( ! $success_ts || $failure_ts > $success_ts ) ) {
				$active_failure = sanitize_text_field( $metrics['last_failure'] );
			}
		}

		return array(
			'views'              => $views,
			'starts'             => $starts,
			'complete'           => $complete,
			'saved'              => $saved,
			'incomplete'         => $incomplete,
			'failures'           => $failures,
			'email_captures'     => max( 0, intval( $metrics['email_captures'] ?? 0 ) ),
			'email_verified'     => max( 0, intval( $metrics['email_verified'] ?? 0 ) ),
			'first_estimates'    => max( 0, intval( $metrics['first_estimates'] ?? 0 ) ),
			'review_reached'     => max( 0, intval( $metrics['review_reached'] ?? 0 ) ),
			'route_completions'  => max( 0, intval( $metrics['route_completions'] ?? 0 ) ),
			'back_clicks'        => max( 0, intval( $metrics['back_clicks'] ?? 0 ) ),
			'validation_errors'  => max( 0, intval( $metrics['validation_errors'] ?? 0 ) ),
			'not_sure_choices'   => max( 0, intval( $metrics['not_sure_choices'] ?? 0 ) ),
			'screen_stats'       => is_array( $metrics['screen_stats'] ?? null ) ? $metrics['screen_stats'] : array(),
			'completion_rate'    => $starts > 0 ? round( ( $complete / $starts ) * 100, 1 ) : 0,
			'last_failure'       => $active_failure,
			'last_failure_at'    => $failure_at,
			'last_failure_version'=> sanitize_text_field( $metrics['last_failure_version'] ?? '' ),
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
					<p><?php esc_html_e( 'Edit prices and the customer journey without wrestling a complicated builder.', 'foundation-customer-form' ); ?></p>
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
		$unfinished_briefs = class_exists( 'Foundation_Submissions' ) ? Foundation_Submissions::count_briefs() : 0;
		$cards = array(
			array( 'label' => __( 'Calculator views', 'foundation-customer-form' ), 'value' => $data['views'], 'note' => __( 'Opened on the site', 'foundation-customer-form' ) ),
			array( 'label' => __( 'Journeys started', 'foundation-customer-form' ), 'value' => $data['starts'], 'note' => __( 'Moved beyond the intro', 'foundation-customer-form' ) ),
			array( 'label' => __( 'Email-linked briefs', 'foundation-customer-form' ), 'value' => $unfinished_briefs, 'note' => __( 'Unfinished leads saved with a private return link', 'foundation-customer-form' ) ),
			array( 'label' => __( 'Stored enquiries', 'foundation-customer-form' ), 'value' => $stored_enquiries, 'note' => sprintf( __( '%s%% completion from starts', 'foundation-customer-form' ), number_format_i18n( $data['completion_rate'], 1 ) ) ),
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

		<section class="fpc-admin-card fpc-funnel-card">
			<div class="fpc-card-heading"><div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Customer retention', 'foundation-customer-form' ); ?></p><h2><?php esc_html_e( 'Project brief funnel', 'foundation-customer-form' ); ?></h2></div><span class="fpc-status-pill is-good"><?php echo esc_html( number_format_i18n( $data['completion_rate'], 1 ) . '% submitted' ); ?></span></div>
			<div class="fpc-funnel-grid">
				<?php
				$funnel = array(
					array( __( 'Opened', 'foundation-customer-form' ), $data['views'] ),
					array( __( 'Started', 'foundation-customer-form' ), $data['starts'] ),
					array( __( 'Email captured', 'foundation-customer-form' ), $data['email_captures'] ),
					array( __( 'Email verified', 'foundation-customer-form' ), $data['email_verified'] ),
					array( __( 'Price reached', 'foundation-customer-form' ), $data['first_estimates'] ),
					array( __( 'Review reached', 'foundation-customer-form' ), $data['review_reached'] ),
					array( __( 'Submitted', 'foundation-customer-form' ), $data['complete'] ),
				);
				foreach ( $funnel as $stage ) :
					$rate = $data['views'] > 0 ? round( ( intval( $stage[1] ) / $data['views'] ) * 100 ) : 0;
				?>
				<div class="fpc-funnel-stage"><span><?php echo esc_html( $stage[0] ); ?></span><strong><?php echo esc_html( number_format_i18n( $stage[1] ) ); ?></strong><small><?php echo esc_html( $rate . '% of opens' ); ?></small></div>
				<?php endforeach; ?>
			</div>
			<?php if ( ! empty( $data['screen_stats'] ) ) :
				$screen_stats = $data['screen_stats'];
				uasort( $screen_stats, static function ( $a, $b ) { return intval( $b['validation_errors'] ?? 0 ) - intval( $a['validation_errors'] ?? 0 ); } );
				$friction = array_slice( $screen_stats, 0, 3, true );
			?>
			<div class="fpc-friction-list"><strong><?php esc_html_e( 'Highest validation friction', 'foundation-customer-form' ); ?></strong><?php foreach ( $friction as $screen_id => $stats ) : if ( empty( $stats['validation_errors'] ) ) continue; ?><span><code><?php echo esc_html( $screen_id ); ?></code> <?php echo esc_html( sprintf( __( '%d validation errors', 'foundation-customer-form' ), intval( $stats['validation_errors'] ) ) ); ?></span><?php endforeach; ?></div>
			<?php endif; ?>
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
					<?php $this->health_row( empty( $settings['turnstile_enabled'] ) || ( ! empty( $settings['turnstile_site_key'] ) && ! empty( $settings['turnstile_secret_key'] ) ), __( 'Spam protection', 'foundation-customer-form' ), ! empty( $settings['turnstile_enabled'] ) ? __( 'Cloudflare Turnstile is configured for magic links and final submissions.', 'foundation-customer-form' ) : __( 'Turnstile is optional and currently off; honeypot, rate limits and idempotency remain active.', 'foundation-customer-form' ) ); ?>
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
			<section class="fpc-admin-card fpc-alert-card"><strong><?php esc_html_e( 'Latest unresolved failure:', 'foundation-customer-form' ); ?></strong> <?php echo esc_html( $data['last_failure'] ); ?><?php if ( $data['last_failure_at'] || $data['last_failure_version'] ) : ?><small><?php echo esc_html( trim( ( $data['last_failure_version'] ? 'v' . $data['last_failure_version'] : '' ) . ( $data['last_failure_at'] ? ' · ' . $data['last_failure_at'] : '' ) ) ); ?></small><?php endif; ?></section>
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

	private function render_workflow_select( $record_type, $record_id, $status, $compact = false ) {
		$statuses = Foundation_Submissions::workflow_statuses();
		$status   = isset( $statuses[ $status ] ) ? $status : ( 'brief' === $record_type ? 'in_progress' : 'new' );
		?>
		<label class="<?php echo $compact ? 'fpc-status-select-wrap is-compact' : 'fpc-status-select-wrap'; ?>">
			<?php if ( ! $compact ) : ?><span><?php esc_html_e( 'Workflow status', 'foundation-customer-form' ); ?></span><?php endif; ?>
			<select data-fpc-lead-status data-record-type="<?php echo esc_attr( $record_type ); ?>" data-record-id="<?php echo esc_attr( $record_id ); ?>" aria-label="<?php esc_attr_e( 'Lead workflow status', 'foundation-customer-form' ); ?>">
				<?php foreach ( $statuses as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	private function render_lead_manager( $record_type, $record, $back_url ) {
		$record_id = absint( $record['id'] ?? 0 );
		$contact   = (array) ( $record['contact'] ?? array() );
		$email     = sanitize_email( $contact['email'] ?? '' );
		$name      = sanitize_text_field( $contact['name'] ?? '' );
		$status    = sanitize_key( $record['workflow_status'] ?? ( 'brief' === $record_type ? 'in_progress' : 'new' ) );
		$subject   = 'brief' === $record_type ? __( 'Your Inkfire Project Brief', 'foundation-customer-form' ) : sprintf( __( 'Your Inkfire enquiry %s', 'foundation-customer-form' ), sanitize_text_field( $record['reference'] ?? '' ) );
		$mailto    = $email ? 'mailto:' . $email . '?subject=' . rawurlencode( $subject ) : '';
		?>
		<section class="fpc-admin-card fpc-lead-manager" data-fpc-lead-manager data-record-type="<?php echo esc_attr( $record_type ); ?>" data-record-id="<?php echo esc_attr( $record_id ); ?>">
			<div class="fpc-card-heading">
				<div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Lead controls', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Manage this customer', 'foundation-customer-form' ); ?></h3><p><?php esc_html_e( 'Update the workflow label, correct contact details, follow up or remove the record without leaving the calculator dashboard.', 'foundation-customer-form' ); ?></p></div>
				<?php if ( 'brief' === $record_type ) : ?><span class="fpc-status-pill <?php echo ! empty( $record['verified'] ) ? 'is-good' : 'is-warning'; ?>" data-fpc-verification-pill><?php echo ! empty( $record['verified'] ) ? esc_html__( 'Email verified', 'foundation-customer-form' ) : esc_html__( 'Email not verified', 'foundation-customer-form' ); ?></span><?php endif; ?>
			</div>
			<div class="fpc-lead-control-grid">
				<?php $this->render_workflow_select( $record_type, $record_id, $status, false ); ?>
				<label class="fpc-field"><span><?php esc_html_e( 'Customer name', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( $name ); ?>" data-fpc-lead-name maxlength="160"></label>
				<label class="fpc-field"><span><?php esc_html_e( 'Customer email', 'foundation-customer-form' ); ?></span><input type="email" value="<?php echo esc_attr( $email ); ?>" data-fpc-lead-email autocomplete="off"></label>
			</div>
			<div class="fpc-lead-actions">
				<button class="button button-primary" type="button" data-fpc-lead-save-contact><?php esc_html_e( 'Save customer details', 'foundation-customer-form' ); ?></button>
				<?php if ( $mailto ) : ?><a class="button" href="<?php echo esc_url( $mailto ); ?>"><?php esc_html_e( 'Follow up by email', 'foundation-customer-form' ); ?></a><?php endif; ?>
				<?php if ( 'brief' === $record_type ) : ?><button class="button" type="button" data-fpc-resend-magic data-record-id="<?php echo esc_attr( $record_id ); ?>"><?php esc_html_e( 'Resend magic link', 'foundation-customer-form' ); ?></button><?php endif; ?>
				<button class="button fpc-danger-button" type="button" data-fpc-delete-lead data-record-type="<?php echo esc_attr( $record_type ); ?>" data-record-id="<?php echo esc_attr( $record_id ); ?>" data-return-url="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Delete record', 'foundation-customer-form' ); ?></button>
			</div>
			<?php if ( 'brief' === $record_type ) : ?><p class="fpc-lead-control-note"><?php esc_html_e( 'Changing the email marks it unverified. Use Resend magic link afterwards so the customer receives a fresh private return link at the corrected address.', 'foundation-customer-form' ); ?></p><?php endif; ?>
		</section>
		<?php
	}

	private function render_enquiries() {
		$enquiry_id = isset( $_GET['enquiry_id'] ) ? absint( $_GET['enquiry_id'] ) : 0;
		$brief_id   = isset( $_GET['brief_id'] ) ? absint( $_GET['brief_id'] ) : 0;
		if ( $enquiry_id ) {
			$this->render_enquiry_detail( $enquiry_id );
			return;
		}
		if ( $brief_id ) {
			$this->render_brief_detail( $brief_id );
			return;
		}

		$view = isset( $_GET['enquiry_view'] ) ? sanitize_key( wp_unslash( $_GET['enquiry_view'] ) ) : 'all';
		if ( ! in_array( $view, array( 'all', 'completed', 'unfinished' ), true ) ) {
			$view = 'all';
		}
		$page           = isset( $_GET['enquiry_page'] ) ? max( 1, absint( $_GET['enquiry_page'] ) ) : 1;
		$per_page       = 30;
		$total          = Foundation_Submissions::count_all();
		$brief_total    = Foundation_Submissions::count_briefs();
		$archived_total = Foundation_Submissions::count_archived();
		$total_pages    = max( 1, (int) ceil( $total / $per_page ) );
		if ( $page > $total_pages ) {
			$page = $total_pages;
		}
		$records  = 'unfinished' === $view ? array() : Foundation_Submissions::get_page( $page, $per_page );
		$briefs   = 'completed' === $view ? array() : Foundation_Submissions::get_briefs( 100, false );
		$base_url = add_query_arg( array( 'page' => $this->page_slug, 'tab' => 'enquiries' ), admin_url( 'admin.php' ) );
		$settings = foundation_get_settings();
		?>
		<div class="fpc-section-intro fpc-lead-section-intro">
			<div>
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Private lead store', 'foundation-customer-form' ); ?></p>
				<h2><?php esc_html_e( 'Project briefs & enquiries', 'foundation-customer-form' ); ?></h2>
				<p><?php esc_html_e( 'Manage unfinished briefs and completed enquiries from one clean inbox. Set follow-up status, correct details, resend private links and archive or delete records when they are finished.', 'foundation-customer-form' ); ?></p>
			</div>
			<div class="fpc-lead-overview-actions"><span class="fpc-status-pill is-good"><?php echo esc_html( number_format_i18n( $total + $brief_total ) . ' active records' ); ?></span><?php if ( $archived_total > 0 ) : ?><button class="button" type="button" data-fpc-clear-archived><?php echo esc_html( sprintf( _n( 'Clear %d archived', 'Clear %d archived', $archived_total, 'foundation-customer-form' ), $archived_total ) ); ?></button><?php endif; ?></div>
		</div>
		<nav class="fpc-subtabs" aria-label="<?php esc_attr_e( 'Lead views', 'foundation-customer-form' ); ?>">
			<?php foreach ( array( 'all' => sprintf( __( 'All (%d)', 'foundation-customer-form' ), $total + $brief_total ), 'unfinished' => sprintf( __( 'Unfinished briefs (%d)', 'foundation-customer-form' ), $brief_total ), 'completed' => sprintf( __( 'Completed (%d)', 'foundation-customer-form' ), $total ) ) as $key => $label ) : ?>
				<a class="<?php echo $view === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'enquiry_view', $key, $base_url ) ); ?>" <?php echo $view === $key ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( 'completed' !== $view ) : ?>
		<section class="fpc-admin-card fpc-enquiry-inbox">
			<div class="fpc-card-heading"><div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'In progress', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Unfinished project briefs', 'foundation-customer-form' ); ?></h3><p><?php esc_html_e( 'These customers supplied an email but have not submitted the final enquiry yet.', 'foundation-customer-form' ); ?></p></div><span><?php echo esc_html( sprintf( _n( '%d brief', '%d briefs', $brief_total, 'foundation-customer-form' ), $brief_total ) ); ?></span></div>
			<?php if ( empty( $briefs ) ) : ?><div class="fpc-empty-state"><p><?php esc_html_e( 'No unfinished email-linked briefs right now.', 'foundation-customer-form' ); ?></p></div><?php else : ?>
			<div class="fpc-enquiry-table-wrap"><table class="widefat striped fpc-enquiry-table"><thead><tr><th><?php esc_html_e( 'Contact', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Workflow', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Progress', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Estimate so far', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Last active', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Actions', 'foundation-customer-form' ); ?></th></tr></thead><tbody>
			<?php foreach ( $briefs as $brief ) :
				$contact = (array) ( $brief['contact'] ?? array() );
				$quote = (array) ( $brief['quote'] ?? array() );
				$currency = $quote['currency'] ?? $settings['currency_symbol'] ?? '£';
				$detail_url = add_query_arg( array( 'brief_id' => absint( $brief['id'] ?? 0 ), 'enquiry_view' => $view ), $base_url );
				$email = sanitize_email( $contact['email'] ?? '' );
				?>
			<tr>
				<td><strong><?php echo esc_html( $contact['name'] ?? '' ); ?></strong><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><span class="fpc-mini-pill <?php echo ! empty( $brief['verified'] ) ? 'is-live' : 'is-draft'; ?>"><?php echo ! empty( $brief['verified'] ) ? esc_html__( 'Verified email', 'foundation-customer-form' ) : esc_html__( 'Email captured', 'foundation-customer-form' ); ?></span></td>
				<td><?php $this->render_workflow_select( 'brief', absint( $brief['id'] ?? 0 ), $brief['workflow_status'] ?? 'in_progress', true ); ?></td>
				<td><strong><?php echo esc_html( absint( $brief['progress'] ?? 0 ) . '%' ); ?></strong></td>
				<td class="fpc-enquiry-estimate"><?php if ( ! empty( $quote['one_off_max'] ) ) : ?><span><b><?php esc_html_e( 'One-off:', 'foundation-customer-form' ); ?></b> <?php echo esc_html( foundation_format_quote_range( $quote['one_off_min'] ?? 0, $quote['one_off_max'] ?? 0, $currency ) ); ?></span><?php endif; ?><?php if ( ! empty( $quote['monthly_max'] ) ) : ?><span><b><?php esc_html_e( 'Monthly:', 'foundation-customer-form' ); ?></b> <?php echo esc_html( foundation_format_quote_range( $quote['monthly_min'] ?? 0, $quote['monthly_max'] ?? 0, $currency ) ); ?></span><?php endif; ?><?php if ( empty( $quote['has_pricing'] ) ) : ?><span><?php esc_html_e( 'Waiting for priced choices', 'foundation-customer-form' ); ?></span><?php endif; ?></td>
				<td><?php echo esc_html( $brief['updated_at'] ?? '' ); ?></td>
				<td><div class="fpc-row-actions"><a class="button" href="<?php echo esc_url( $detail_url ); ?>"><?php esc_html_e( 'View', 'foundation-customer-form' ); ?></a><?php if ( $email ) : ?><a class="button" href="mailto:<?php echo esc_attr( $email ); ?>?subject=<?php echo esc_attr( rawurlencode( __( 'Your Inkfire Project Brief', 'foundation-customer-form' ) ) ); ?>"><?php esc_html_e( 'Follow up', 'foundation-customer-form' ); ?></a><?php endif; ?><button class="button" type="button" data-fpc-resend-magic data-record-id="<?php echo esc_attr( absint( $brief['id'] ?? 0 ) ); ?>"><?php esc_html_e( 'Resend link', 'foundation-customer-form' ); ?></button></div></td>
			</tr>
			<?php endforeach; ?></tbody></table></div><?php endif; ?>
		</section>
		<?php endif; ?>

		<?php if ( 'unfinished' !== $view ) : ?>
		<section class="fpc-admin-card fpc-enquiry-inbox">
			<div class="fpc-card-heading"><div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Completed', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Submitted enquiries', 'foundation-customer-form' ); ?></h3><p><?php esc_html_e( 'Submitted project briefs remain editable as internal workflow records while the original quote data stays intact.', 'foundation-customer-form' ); ?></p></div><span><?php echo esc_html( sprintf( _n( '%d enquiry', '%d enquiries', $total, 'foundation-customer-form' ), $total ) ); ?></span></div>
			<?php if ( empty( $records ) ) : ?><div class="fpc-empty-state"><p><?php esc_html_e( 'No completed enquiries yet.', 'foundation-customer-form' ); ?></p></div><?php else : ?>
			<div class="fpc-enquiry-table-wrap"><table class="widefat striped fpc-enquiry-table"><thead><tr><th><?php esc_html_e( 'Reference', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Contact', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Workflow', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Estimate', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Email delivery', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Received', 'foundation-customer-form' ); ?></th><th><?php esc_html_e( 'Actions', 'foundation-customer-form' ); ?></th></tr></thead><tbody>
			<?php foreach ( $records as $record ) :
				$contact = (array) ( $record['contact'] ?? array() );
				$quote = (array) ( $record['quote'] ?? array() );
				$currency = $quote['currency'] ?? $settings['currency_symbol'] ?? '£';
				$detail_url = add_query_arg( array( 'enquiry_id' => absint( $record['id'] ?? 0 ), 'enquiry_page' => $page, 'enquiry_view' => $view ), $base_url );
				$email = sanitize_email( $contact['email'] ?? '' );
				?>
			<tr>
				<td><strong><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $record['reference'] ?? '' ); ?></a></strong></td>
				<td><strong><?php echo esc_html( $contact['company'] ?? '' ); ?></strong><span><?php echo esc_html( $contact['name'] ?? '' ); ?></span><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
				<td><?php $this->render_workflow_select( 'enquiry', absint( $record['id'] ?? 0 ), $record['workflow_status'] ?? 'new', true ); ?></td>
				<td class="fpc-enquiry-estimate"><?php if ( ! empty( $quote['one_off_max'] ) ) : ?><span><b><?php esc_html_e( 'One-off:', 'foundation-customer-form' ); ?></b> <?php echo esc_html( foundation_format_quote_range( $quote['one_off_min'] ?? 0, $quote['one_off_max'] ?? 0, $currency ) ); ?></span><?php endif; ?><?php if ( ! empty( $quote['monthly_max'] ) ) : ?><span><b><?php esc_html_e( 'Monthly:', 'foundation-customer-form' ); ?></b> <?php echo esc_html( foundation_format_quote_range( $quote['monthly_min'] ?? 0, $quote['monthly_max'] ?? 0, $currency ) ); ?></span><?php endif; ?></td>
				<td class="fpc-mail-status-stack"><?php $this->render_mail_status( $record['admin_mail_status'] ?? 'pending', __( 'Team', 'foundation-customer-form' ) ); ?><?php $this->render_mail_status( $record['customer_mail_status'] ?? 'pending', __( 'Customer', 'foundation-customer-form' ) ); ?></td>
				<td><?php echo esc_html( $this->format_enquiry_date( $record['created_at'] ?? '' ) ); ?></td>
				<td><div class="fpc-row-actions"><a class="button" href="<?php echo esc_url( $detail_url ); ?>"><?php esc_html_e( 'View', 'foundation-customer-form' ); ?></a><?php if ( $email ) : ?><a class="button" href="mailto:<?php echo esc_attr( $email ); ?>?subject=<?php echo esc_attr( rawurlencode( sprintf( __( 'Your Inkfire enquiry %s', 'foundation-customer-form' ), $record['reference'] ?? '' ) ) ); ?>"><?php esc_html_e( 'Follow up', 'foundation-customer-form' ); ?></a><?php endif; ?></div></td>
			</tr>
			<?php endforeach; ?></tbody></table></div>
			<?php if ( $total_pages > 1 ) : ?><nav class="fpc-pagination" aria-label="<?php esc_attr_e( 'Enquiry pages', 'foundation-customer-form' ); ?>"><span><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'foundation-customer-form' ), $page, $total_pages ) ); ?></span><div><?php if ( $page > 1 ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( array( 'enquiry_page' => $page - 1, 'enquiry_view' => $view ), $base_url ) ); ?>"><?php esc_html_e( 'Previous', 'foundation-customer-form' ); ?></a><?php endif; ?><?php if ( $page < $total_pages ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( array( 'enquiry_page' => $page + 1, 'enquiry_view' => $view ), $base_url ) ); ?>"><?php esc_html_e( 'Next', 'foundation-customer-form' ); ?></a><?php endif; ?></div></nav><?php endif; ?>
			<?php endif; ?>
		</section>
		<?php endif; ?>
		<?php
	}

	private function render_brief_detail( $brief_id ) {
		$record = Foundation_Submissions::get_brief_record( $brief_id );
		$back_url = add_query_arg( array( 'page' => $this->page_slug, 'tab' => 'enquiries', 'enquiry_view' => 'unfinished' ), admin_url( 'admin.php' ) );
		if ( empty( $record ) ) { echo '<div class="notice notice-error inline"><p>' . esc_html__( 'That saved project brief could not be found.', 'foundation-customer-form' ) . '</p></div>'; return; }
		$contact=(array)($record['contact']??array()); $quote=(array)($record['quote']??array()); $currency=$quote['currency']??foundation_get_settings()['currency_symbol']??'£';
		?>
		<div class="fpc-detail-toolbar"><a class="button" href="<?php echo esc_url($back_url); ?>">← <?php esc_html_e('Back to leads','foundation-customer-form');?></a><span><?php echo esc_html($record['updated_at']??'');?></span></div>
		<div class="fpc-section-intro fpc-enquiry-detail-heading"><div><p class="fpc-admin-eyebrow"><?php esc_html_e('Unfinished project brief','foundation-customer-form');?></p><h2><?php echo esc_html($contact['name']??$contact['email']??'Saved brief');?></h2><p><?php echo esc_html($contact['email']??'');?></p></div><span class="fpc-status-pill <?php echo !empty($record['verified'])?'is-good':'';?>"><?php echo !empty($record['verified'])?esc_html__('Verified email','foundation-customer-form'):esc_html__('Email captured','foundation-customer-form');?></span></div>
		<section class="fpc-admin-grid fpc-admin-grid-two fpc-enquiry-detail-grid"><article class="fpc-admin-card"><p class="fpc-admin-eyebrow"><?php esc_html_e('Lead','foundation-customer-form');?></p><h3><?php esc_html_e('Saved contact','foundation-customer-form');?></h3><dl class="fpc-definition-list"><div><dt><?php esc_html_e('Name','foundation-customer-form');?></dt><dd><?php echo esc_html($contact['name']??'');?></dd></div><div><dt><?php esc_html_e('Email','foundation-customer-form');?></dt><dd><a href="mailto:<?php echo esc_attr($contact['email']??'');?>"><?php echo esc_html($contact['email']??'');?></a></dd></div><div><dt><?php esc_html_e('Progress','foundation-customer-form');?></dt><dd><?php echo esc_html(absint($record['progress']??0).'%');?></dd></div><div><dt><?php esc_html_e('Marketing consent','foundation-customer-form');?></dt><dd><?php echo !empty($record['marketing_consent'])?esc_html__('Yes','foundation-customer-form'):esc_html__('No','foundation-customer-form');?></dd></div></dl></article><article class="fpc-admin-card"><p class="fpc-admin-eyebrow"><?php esc_html_e('Estimate so far','foundation-customer-form');?></p><h3><?php esc_html_e('Current value','foundation-customer-form');?></h3><?php echo wp_kses_post( $this->render_admin_quote_totals( $quote, $currency ) ); ?></article></section>
		<?php $this->render_lead_manager( 'brief', $record, $back_url ); ?>
		<?php
	}

	private function render_admin_quote_totals( $quote, $currency ) {
		$one = ! empty( $quote['one_off_max'] ) ? foundation_format_quote_range( $quote['one_off_min'] ?? 0, $quote['one_off_max'] ?? 0, $currency ) : __( 'Not priced yet', 'foundation-customer-form' );
		$monthly = ! empty( $quote['monthly_max'] ) ? foundation_format_quote_range( $quote['monthly_min'] ?? 0, $quote['monthly_max'] ?? 0, $currency ) . '/month' : __( 'No monthly cost yet', 'foundation-customer-form' );
		return '<div class="fpc-quote-total-grid"><div><span>' . esc_html__( 'One-off', 'foundation-customer-form' ) . '</span><strong>' . esc_html( $one ) . '</strong></div><div><span>' . esc_html__( 'Monthly', 'foundation-customer-form' ) . '</span><strong>' . esc_html( $monthly ) . '</strong></div></div>';
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

		<?php $this->render_lead_manager( 'enquiry', $record, $back_url ); ?>

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
		?>
		<div class="fpc-section-intro fpc-journey-intro">
			<div>
				<p class="fpc-admin-eyebrow"><?php esc_html_e( 'Visual Journey Editor', 'foundation-customer-form' ); ?></p>
				<h2><?php esc_html_e( 'See the flow, then edit only what you need', 'foundation-customer-form' ); ?></h2>
				<p><?php esc_html_e( 'Screens follow the customer hierarchy from opening slide to final confirmation. Expand any card to edit it, drag sibling cards to reorder them, or use the accessible Move buttons. Changes save without reloading this page.', 'foundation-customer-form' ); ?></p>
			</div>
			<div class="fpc-journey-health-stack">
				<span class="fpc-status-pill <?php echo ! empty( $health['structure_ok'] ) ? 'is-good' : 'is-warning'; ?>"><?php echo ! empty( $health['structure_ok'] ) ? esc_html__( 'Journey healthy', 'foundation-customer-form' ) : esc_html__( 'Flow needs attention', 'foundation-customer-form' ); ?></span>
				<?php if ( ! empty( $health['builder_customized'] ) ) : ?><span class="fpc-mini-pill"><?php esc_html_e( 'Safely customised', 'foundation-customer-form' ); ?></span><?php endif; ?>
				<?php if ( ! empty( $health['draft_step_count'] ) ) : ?><span class="fpc-mini-pill is-draft"><?php echo esc_html( sprintf( _n( '%d draft', '%d drafts', intval( $health['draft_step_count'] ), 'foundation-customer-form' ), intval( $health['draft_step_count'] ) ) ); ?></span><?php endif; ?>
			</div>
		</div>
		<?php if ( empty( $health['structure_ok'] ) ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( implode( ' ', isset( $health['structure_issues'] ) ? $health['structure_issues'] : array() ) ); ?></p></div><?php endif; ?>
		<?php $this->render_journey_builder( $health ); ?>

		<section class="fpc-admin-grid fpc-admin-grid-two fpc-danger-zone">
			<article class="fpc-admin-card">
				<h3><?php esc_html_e( 'Reapply Inkfire blueprint', 'foundation-customer-form' ); ?></h3>
				<p><?php esc_html_e( 'Use this only when the journey itself has become damaged. The current version is backed up first. Your prices and email settings are not changed.', 'foundation-customer-form' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-fpc-confirm="<?php esc_attr_e( 'Replace the current journey with the bundled Inkfire blueprint?', 'foundation-customer-form' ); ?>">
					<input type="hidden" name="action" value="foundation_apply_blueprint"><?php wp_nonce_field( 'foundation_apply_blueprint' ); ?>
					<button class="button" type="submit"><?php esc_html_e( 'Reapply blueprint', 'foundation-customer-form' ); ?></button>
				</form>
			</article>
			<article class="fpc-admin-card">
				<?php $backup = get_option( 'foundation_form_data_backup', array() ); ?>
				<h3><?php esc_html_e( 'Restore previous journey', 'foundation-customer-form' ); ?></h3>
				<p><?php echo ! empty( $backup['created_at'] ) ? esc_html( sprintf( __( 'Backup created %s.', 'foundation-customer-form' ), $backup['created_at'] ) ) : esc_html__( 'No blueprint backup is currently available.', 'foundation-customer-form' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-fpc-confirm="<?php esc_attr_e( 'Restore the previous journey? The current journey will become the new backup.', 'foundation-customer-form' ); ?>">
					<input type="hidden" name="action" value="foundation_restore_blueprint_backup"><?php wp_nonce_field( 'foundation_restore_blueprint_backup' ); ?>
					<button class="button" type="submit" <?php disabled( empty( $backup['form_data'] ) ); ?>><?php esc_html_e( 'Restore backup', 'foundation-customer-form' ); ?></button>
				</form>
			</article>
		</section>
		<?php
	}

	private function journey_groups( $steps ) {
		$groups = array( 'start' => array(), 'web' => array(), 'tech' => array(), 'business' => array() );
		foreach ( foundation_normalize_form_data( $steps ) as $step ) {
			$group = foundation_journey_group_for_step( $step );
			$groups[ $group ][] = $step;
		}
		return $groups;
	}

	private function render_journey_builder( $health ) {
		$steps       = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
		$groups      = $this->journey_groups( $steps );
		$backup      = get_option( 'foundation_form_data_builder_backup', array() );
		$revision    = max( 0, intval( get_option( 'foundation_journey_builder_revision', 0 ) ) );
		$updated_at  = sanitize_text_field( get_option( 'foundation_journey_builder_updated_at', '' ) );
		$settings    = foundation_get_settings();
		?>
		<div class="fpc-journey-builder" data-fpc-journey-builder>
			<div class="fpc-journey-toolbar">
				<div>
					<strong><?php echo esc_html( sprintf( __( '%d customer screens', 'foundation-customer-form' ), count( $steps ) ) ); ?></strong>
					<span><?php echo $revision > 0 ? esc_html( sprintf( __( 'Journey Editor revision %d%s', 'foundation-customer-form' ), $revision, $updated_at ? ' · ' . $updated_at : '' ) ) : esc_html__( 'Bundled journey, not yet customised', 'foundation-customer-form' ); ?></span>
				</div>
				<div class="fpc-journey-toolbar-actions">
					<button class="button" type="button" data-fpc-journey-undo <?php disabled( empty( $backup['form_data'] ) ); ?>><?php esc_html_e( 'Undo last change', 'foundation-customer-form' ); ?></button>
					<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => $this->page_slug, 'tab' => 'prices' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit prices', 'foundation-customer-form' ); ?></a>
				</div>
			</div>
			<div class="fpc-journey-toast" role="status" aria-live="polite" aria-atomic="true" data-fpc-journey-toast></div>

			<div class="fpc-flow-system-node is-opening">
				<div class="fpc-flow-node-icon" aria-hidden="true">1</div>
				<div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Opening slide', 'foundation-customer-form' ); ?></p><h3><?php esc_html_e( 'Inkfire introduction', 'foundation-customer-form' ); ?></h3><p><?php esc_html_e( 'The branded opening panel appears before the first journey screen. Change its image here without leaving the flow editor.', 'foundation-customer-form' ); ?></p></div>
				<?php $this->render_journey_media_picker( 'intro_image_url', __( 'Opening image', 'foundation-customer-form' ), $settings['intro_image_url'] ?? '' ); ?>
			</div>
			<div class="fpc-flow-connector" aria-hidden="true"><span></span></div>

			<section class="fpc-route-lane is-start" data-fpc-journey-lane="start">
				<?php $this->render_journey_lane_header( 'start', __( 'Start', 'foundation-customer-form' ), __( 'The first customer choice before the three service routes split.', 'foundation-customer-form' ), count( $groups['start'] ) ); ?>
				<div class="fpc-route-list" data-fpc-journey-list="start">
					<?php foreach ( $groups['start'] as $index => $step ) : $this->render_journey_step( $step, $steps, 'start', $index, count( $groups['start'] ) ); endforeach; ?>
				</div>
			</section>

			<div class="fpc-flow-split" aria-hidden="true"><span></span><span></span><span></span></div>
			<div class="fpc-route-grid">
				<?php
				$route_meta = array(
					'web'      => array( __( 'Web & Accessibility', 'foundation-customer-form' ), __( 'Website builds, improvements, hosting and accessibility.', 'foundation-customer-form' ) ),
					'tech'     => array( __( 'Tech & Support', 'foundation-customer-form' ), __( 'IT, Microsoft, Cyber Essentials and accessibility tech.', 'foundation-customer-form' ) ),
					'business' => array( __( 'Business Support & Marketing', 'foundation-customer-form' ), __( 'Branding, content, marketing and business support.', 'foundation-customer-form' ) ),
				);
				foreach ( $route_meta as $group => $meta ) :
					?>
					<section class="fpc-route-lane is-<?php echo esc_attr( $group ); ?>" data-fpc-journey-lane="<?php echo esc_attr( $group ); ?>">
						<?php $this->render_journey_lane_header( $group, $meta[0], $meta[1], count( $groups[ $group ] ) ); ?>
						<div class="fpc-route-list" data-fpc-journey-list="<?php echo esc_attr( $group ); ?>">
							<?php foreach ( $groups[ $group ] as $index => $step ) : $this->render_journey_step( $step, $steps, $group, $index, count( $groups[ $group ] ) ); endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>

			<div class="fpc-flow-merge" aria-hidden="true"><span></span></div>
			<section class="fpc-shared-finish" aria-labelledby="fpc-shared-finish-title">
				<div class="fpc-shared-finish-heading"><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Shared finish', 'foundation-customer-form' ); ?></p><h3 id="fpc-shared-finish-title"><?php esc_html_e( 'Every completed route rejoins here', 'foundation-customer-form' ); ?></h3></div>
				<div class="fpc-shared-finish-grid">
					<?php $this->render_system_finish_card( '1', __( 'Review estimate', 'foundation-customer-form' ), __( 'Shows one-off, monthly and tailored-quote outcomes together.', 'foundation-customer-form' ) ); ?>
					<?php $this->render_system_finish_card( '2', __( 'Contact details', 'foundation-customer-form' ), __( 'Collects the customer details only after the estimate.', 'foundation-customer-form' ), 'testimonial_image_url', __( 'Contact image', 'foundation-customer-form' ), $settings['testimonial_image_url'] ?? '' ); ?>
					<?php $this->render_system_finish_card( '3', __( 'Success', 'foundation-customer-form' ), __( 'Confirms the enquiry reference and next steps.', 'foundation-customer-form' ), 'success_image_url', __( 'Closing image', 'foundation-customer-form' ), $settings['success_image_url'] ?? '' ); ?>
				</div>

				<form class="fpc-success-copy-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'foundation_save_settings' ); ?>
					<input type="hidden" name="action" value="foundation_save_settings">
					<input type="hidden" name="foundation_settings_section" value="journey">

					<div class="fpc-success-copy-heading">
						<h4><?php esc_html_e( 'Success screen wording', 'foundation-customer-form' ); ?></h4>
						<p><?php esc_html_e( 'Shown to the customer on the final screen, after their estimate. Edit here without needing a developer.', 'foundation-customer-form' ); ?></p>
					</div>

					<?php $this->textarea_field( 'success_message', __( 'Thank-you message', 'foundation-customer-form' ), $settings['success_message'] ?? '', 3 ); ?>
					<?php $this->input_field( 'success_response_time', __( 'When we will reply', 'foundation-customer-form' ), $settings['success_response_time'] ?? '', 'text', false, __( 'For example: A member of the team will get back to you within 1 to 3 working days.', 'foundation-customer-form' ) ); ?>
					<?php $this->input_field( 'success_follow_heading', __( 'Follow-us heading', 'foundation-customer-form' ), $settings['success_follow_heading'] ?? '', 'text', false, __( 'Sits above the social links. Leave the links below empty to hide this section entirely.', 'foundation-customer-form' ) ); ?>

					<div class="fpc-success-copy-heading">
						<h4><?php esc_html_e( 'Social links', 'foundation-customer-form' ); ?></h4>
						<p><?php esc_html_e( 'Used on the success screen and in the customer confirmation email. Clear a field to hide that network.', 'foundation-customer-form' ); ?></p>
					</div>

					<div class="fpc-success-social-grid">
						<?php $this->input_field( 'linkedin_url', __( 'LinkedIn', 'foundation-customer-form' ), $settings['linkedin_url'] ?? '', 'url' ); ?>
						<?php $this->input_field( 'instagram_url', __( 'Instagram', 'foundation-customer-form' ), $settings['instagram_url'] ?? '', 'url' ); ?>
						<?php $this->input_field( 'facebook_url', __( 'Facebook', 'foundation-customer-form' ), $settings['facebook_url'] ?? '', 'url' ); ?>
						<?php $this->input_field( 'tiktok_url', __( 'TikTok', 'foundation-customer-form' ), $settings['tiktok_url'] ?? '', 'url' ); ?>
						<?php $this->input_field( 'twitter_url', __( 'X (Twitter)', 'foundation-customer-form' ), $settings['twitter_url'] ?? '', 'url' ); ?>
					</div>

					<p class="fpc-success-copy-actions"><button class="button button-primary" type="submit"><?php esc_html_e( 'Save success screen', 'foundation-customer-form' ); ?></button></p>
				</form>
			</section>
		</div>
		<?php
	}

	private function render_journey_lane_header( $group, $title, $description, $count ) {
		?>
		<div class="fpc-route-lane-header">
			<div><p class="fpc-admin-eyebrow"><?php echo esc_html( sprintf( _n( '%d screen', '%d screens', $count, 'foundation-customer-form' ), $count ) ); ?></p><h3><?php echo esc_html( $title ); ?></h3><p><?php echo esc_html( $description ); ?></p></div>
			<button class="button fpc-add-screen" type="button" data-fpc-add-step data-group="<?php echo esc_attr( $group ); ?>"><span aria-hidden="true">＋</span><?php esc_html_e( 'Add screen', 'foundation-customer-form' ); ?></button>
		</div>
		<?php
	}

	private function render_journey_media_picker( $setting_key, $label, $image_url ) {
		$image_url = esc_url( (string) $image_url );
		?>
		<div class="fpc-journey-media-picker" data-fpc-media-picker data-setting-key="<?php echo esc_attr( $setting_key ); ?>">
			<button class="fpc-media-preview" type="button" data-fpc-media-select aria-label="<?php echo esc_attr( sprintf( __( 'Choose %s from the WordPress Media Library', 'foundation-customer-form' ), $label ) ); ?>">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="" data-fpc-media-preview <?php echo $image_url ? '' : 'hidden'; ?>>
				<span data-fpc-media-empty <?php echo $image_url ? 'hidden' : ''; ?>><?php esc_html_e( 'No image', 'foundation-customer-form' ); ?></span>
			</button>
			<div class="fpc-media-picker-copy"><strong><?php echo esc_html( $label ); ?></strong><span data-fpc-media-status><?php esc_html_e( 'Uses the WordPress Media Library.', 'foundation-customer-form' ); ?></span></div>
			<div class="fpc-media-picker-actions"><button class="button" type="button" data-fpc-media-select><?php esc_html_e( 'Choose image', 'foundation-customer-form' ); ?></button><button class="button-link-delete" type="button" data-fpc-media-remove <?php echo $image_url ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'foundation-customer-form' ); ?></button></div>
		</div>
		<?php
	}

	private function render_system_finish_card( $number, $title, $description, $media_key = '', $media_label = '', $media_url = '' ) {
		?>
		<article class="fpc-system-finish-card"><span><?php echo esc_html( $number ); ?></span><div><h4><?php echo esc_html( $title ); ?></h4><p><?php echo esc_html( $description ); ?></p><?php if ( $media_key ) : ?><?php $this->render_journey_media_picker( $media_key, $media_label, $media_url ); ?><?php endif; ?></div></article>
		<?php
	}

	private function render_journey_step( $step, $all_steps, $group, $position, $total ) {
		$step_id          = isset( $step['id'] ) ? $step['id'] : '';
		$incoming         = foundation_journey_incoming_connections( $all_steps, $step_id );
		$sources          = foundation_journey_connection_sources( $all_steps );
		$is_conditional   = ! empty( $step['is_conditional'] );
		$is_draft         = $is_conditional && empty( $incoming );
		$is_custom        = foundation_journey_is_custom_step_id( $step_id );
		$editor_id        = 'fpc-editor-' . $step_id;
		$step_positions = array();
		foreach ( $all_steps as $step_position => $positioned_step ) {
			$positioned_id = isset( $positioned_step['id'] ) ? $positioned_step['id'] : '';
			if ( '' !== $positioned_id ) {
				$step_positions[ $positioned_id ] = $step_position;
			}
		}
		$current_step_position = isset( $step_positions[ $step_id ] ) ? $step_positions[ $step_id ] : PHP_INT_MAX;
		$connection_labels = array();
		foreach ( $incoming as $connection_key ) {
			if ( isset( $sources[ $connection_key ] ) ) {
				$source = $sources[ $connection_key ];
				$connection_labels[] = $source['step_title'] . ' → ' . $source['option_label'];
			}
		}
		?>
		<article class="fpc-journey-step <?php echo $is_draft ? 'is-draft' : 'is-live'; ?>" data-fpc-step-id="<?php echo esc_attr( $step_id ); ?>" data-fpc-group="<?php echo esc_attr( $group ); ?>">
			<div class="fpc-journey-step-head">
				<button class="fpc-drag-handle" type="button" data-fpc-drag-handle aria-label="<?php echo esc_attr( sprintf( __( 'Drag %s to reorder', 'foundation-customer-form' ), $step['title'] ) ); ?>" title="<?php esc_attr_e( 'Drag to reorder', 'foundation-customer-form' ); ?>"><span aria-hidden="true">⋮⋮</span></button>
				<div class="fpc-journey-number" aria-hidden="true"><?php echo esc_html( strtoupper( substr( $group, 0, 1 ) ) ); ?></div>
				<button class="fpc-journey-summary" type="button" data-fpc-toggle-step aria-expanded="false" aria-controls="<?php echo esc_attr( $editor_id ); ?>">
					<span class="fpc-journey-summary-title"><?php echo esc_html( $step['title'] ); ?></span>
					<span class="fpc-journey-summary-subtitle"><?php echo esc_html( $step['subtitle'] ); ?></span>
					<span class="fpc-journey-summary-route"><?php echo $is_conditional ? ( $is_draft ? esc_html__( 'Draft · not connected to a customer choice yet', 'foundation-customer-form' ) : esc_html( __( 'Appears after: ', 'foundation-customer-form' ) . implode( ' · ', array_slice( $connection_labels, 0, 2 ) ) . ( count( $connection_labels ) > 2 ? ' +' . ( count( $connection_labels ) - 2 ) : '' ) ) ) : esc_html__( 'Always visible in this route', 'foundation-customer-form' ); ?></span>
				</button>
				<div class="fpc-step-pills"><span class="fpc-mini-pill <?php echo $is_draft ? 'is-draft' : 'is-live'; ?>"><?php echo $is_draft ? esc_html__( 'Draft', 'foundation-customer-form' ) : esc_html__( 'Live', 'foundation-customer-form' ); ?></span><?php if ( $is_conditional ) : ?><span class="fpc-mini-pill"><?php esc_html_e( 'Conditional', 'foundation-customer-form' ); ?></span><?php endif; ?></div>
				<div class="fpc-step-actions">
					<button class="button-link fpc-icon-button" type="button" data-fpc-move-step="up" <?php disabled( 0 === $position ); ?> aria-label="<?php esc_attr_e( 'Move screen up', 'foundation-customer-form' ); ?>">↑</button>
					<button class="button-link fpc-icon-button" type="button" data-fpc-move-step="down" <?php disabled( $position >= $total - 1 ); ?> aria-label="<?php esc_attr_e( 'Move screen down', 'foundation-customer-form' ); ?>">↓</button>
					<button class="button fpc-card-add" type="button" data-fpc-add-step data-group="<?php echo esc_attr( $group ); ?>" data-after-step-id="<?php echo esc_attr( $step_id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Add a new screen after %s', 'foundation-customer-form' ), $step['title'] ) ); ?>"><span aria-hidden="true">＋</span><?php esc_html_e( 'Add', 'foundation-customer-form' ); ?></button>
					<button class="button" type="button" data-fpc-duplicate-step><?php esc_html_e( 'Duplicate', 'foundation-customer-form' ); ?></button>
					<button class="button button-primary" type="button" data-fpc-toggle-step aria-expanded="false" aria-controls="<?php echo esc_attr( $editor_id ); ?>"><span data-fpc-toggle-label><?php esc_html_e( 'Edit', 'foundation-customer-form' ); ?></span></button>
				</div>
			</div>
			<div class="fpc-journey-editor" id="<?php echo esc_attr( $editor_id ); ?>" data-fpc-step-editor hidden>
				<div class="fpc-editor-section">
					<div class="fpc-editor-section-heading"><div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Screen', 'foundation-customer-form' ); ?></p><h4><?php esc_html_e( 'Customer-facing copy', 'foundation-customer-form' ); ?></h4></div><code><?php echo esc_html( $step_id ); ?></code></div>
					<div class="fpc-editor-grid-two">
						<label class="fpc-field"><span><?php esc_html_e( 'Screen title', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( $step['title'] ); ?>" data-fpc-step-title maxlength="160"></label>
						<label class="fpc-field"><span><?php esc_html_e( 'Short description', 'foundation-customer-form' ); ?></span><textarea data-fpc-step-subtitle rows="3"><?php echo esc_textarea( $step['subtitle'] ); ?></textarea></label>
					</div>
					<label class="fpc-check fpc-conditional-toggle"><input type="checkbox" data-fpc-step-conditional <?php checked( $is_conditional ); ?>><span><strong><?php esc_html_e( 'Only show this screen when a previous answer connects to it', 'foundation-customer-form' ); ?></strong><small><?php esc_html_e( 'Leave this on for route-specific screens. Turn it off only when the screen should always appear.', 'foundation-customer-form' ); ?></small></span></label>
				</div>

				<div class="fpc-editor-section" data-fpc-connections-section>
					<div class="fpc-editor-section-heading"><div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Connections', 'foundation-customer-form' ); ?></p><h4><?php esc_html_e( 'When does this screen appear?', 'foundation-customer-form' ); ?></h4><p><?php esc_html_e( 'Choose the earlier answers that should unlock this screen. A new or duplicated card stays safely offline until you connect it.', 'foundation-customer-form' ); ?></p></div></div>
					<div class="fpc-connection-grid">
						<?php
						$connection_count = 0;
						foreach ( $sources as $source_key => $source ) :
							if ( $source['step_id'] === $step_id ) {
								continue;
							}
							if ( 'start' !== $source['group'] && $source['group'] !== $group ) {
								continue;
							}
							if ( isset( $step_positions[ $source['step_id'] ] ) && $step_positions[ $source['step_id'] ] >= $current_step_position ) {
								continue;
							}
							$connection_count++;
							?>
							<label class="fpc-connection-choice"><input type="checkbox" value="<?php echo esc_attr( $source_key ); ?>" data-fpc-connection <?php checked( in_array( $source_key, $incoming, true ) ); ?>><span><strong><?php echo esc_html( $source['step_title'] ); ?></strong><small><?php echo esc_html( $source['field_label'] . ' → ' . $source['option_label'] ); ?></small></span></label>
						<?php endforeach; ?>
						<?php if ( 0 === $connection_count ) : ?><p class="fpc-help"><?php esc_html_e( 'No compatible earlier choices are available yet.', 'foundation-customer-form' ); ?></p><?php endif; ?>
					</div>
				</div>

				<div class="fpc-editor-section">
					<div class="fpc-editor-section-heading"><div><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Questions', 'foundation-customer-form' ); ?></p><h4><?php esc_html_e( 'What customers answer on this screen', 'foundation-customer-form' ); ?></h4><p><?php esc_html_e( 'The mini builder exposes customer-facing content and safe controls. Existing pricing formulas stay protected.', 'foundation-customer-form' ); ?></p></div></div>
					<div class="fpc-field-editor-stack">
						<?php foreach ( (array) $step['fields'] as $field ) : $this->render_journey_field_editor( $field ); endforeach; ?>
					</div>
				</div>

				<div class="fpc-step-savebar">
					<div class="fpc-step-save-status" role="status" aria-live="polite" data-fpc-step-save-status></div>
					<div>
						<button class="button" type="button" data-fpc-collapse-step><?php esc_html_e( 'Minimise card', 'foundation-customer-form' ); ?></button>
						<?php if ( $is_custom && $is_draft ) : ?><button class="button fpc-delete-draft" type="button" data-fpc-delete-draft><?php esc_html_e( 'Delete draft', 'foundation-customer-form' ); ?></button><?php endif; ?>
						<button class="button button-primary button-hero" type="button" data-fpc-save-step><?php esc_html_e( 'Save screen', 'foundation-customer-form' ); ?></button>
					</div>
				</div>
			</div>
		</article>
		<?php
	}

	private function render_journey_field_editor( $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : '';
		$field_id = isset( $field['id'] ) ? $field['id'] : '';
		$pricing_keys = array();
		foreach ( array( 'price_key', 'base_price_key', 'price_per_unit_key', 'unit_price_key' ) as $key ) {
			if ( ! empty( $field[ $key ] ) ) {
				$pricing_keys[] = $field[ $key ];
			}
		}
		foreach ( (array) ( isset( $field['price_per_unit_keys'] ) ? $field['price_per_unit_keys'] : array() ) as $key ) {
			$pricing_keys[] = $key;
		}
		foreach ( (array) ( isset( $field['options'] ) ? $field['options'] : array() ) as $option ) {
			if ( ! empty( $option['price_key'] ) ) {
				$pricing_keys[] = $option['price_key'];
			}
			if ( ! empty( $option['unit_price_key'] ) ) {
				$pricing_keys[] = $option['unit_price_key'];
			}
		}
		$pricing_keys = array_values( array_unique( array_filter( $pricing_keys ) ) );
		?>
		<article class="fpc-journey-field-editor" data-fpc-field-editor data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $type ); ?>">
			<div class="fpc-field-editor-heading"><div><strong><?php echo esc_html( $field['label'] ? $field['label'] : ( ! empty( $field['line_item_label'] ) ? $field['line_item_label'] : ucfirst( str_replace( '_', ' ', $type ) ) ) ); ?></strong><span><?php echo esc_html( str_replace( '_', ' ', $type ) ); ?></span></div><code><?php echo esc_html( $field_id ); ?></code></div>
			<?php if ( 'calculation' === $type ) : ?>
				<div class="fpc-protected-rule"><strong><?php esc_html_e( 'Protected calculation rule', 'foundation-customer-form' ); ?></strong><span><?php esc_html_e( 'This rule stays connected to the server-authoritative pricing engine and is intentionally not editable here.', 'foundation-customer-form' ); ?></span></div>
			<?php else : ?>
				<div class="fpc-editor-grid-two">
					<label class="fpc-field"><span><?php esc_html_e( 'Question / label', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( isset( $field['label'] ) ? $field['label'] : '' ); ?>" data-fpc-field-label maxlength="220"></label>
					<label class="fpc-field"><span><?php esc_html_e( 'Helper text', 'foundation-customer-form' ); ?></span><textarea rows="2" data-fpc-field-helper><?php echo esc_textarea( isset( $field['helper'] ) ? $field['helper'] : '' ); ?></textarea></label>
				</div>
				<label class="fpc-check is-compact"><input type="checkbox" data-fpc-field-required <?php checked( ! empty( $field['required'] ) ); ?>><span><?php esc_html_e( 'Required answer', 'foundation-customer-form' ); ?></span></label>

				<?php if ( 'service_card' === $type ) : ?>
					<label class="fpc-field fpc-selection-mode"><span><?php esc_html_e( 'Selection behaviour', 'foundation-customer-form' ); ?></span><select data-fpc-field-selection-mode><option value="single" <?php selected( 'single', isset( $field['selection_mode'] ) ? $field['selection_mode'] : 'single' ); ?>><?php esc_html_e( 'Choose one', 'foundation-customer-form' ); ?></option><option value="multi" <?php selected( 'multi', isset( $field['selection_mode'] ) ? $field['selection_mode'] : '' ); ?>><?php esc_html_e( 'Choose one or more', 'foundation-customer-form' ); ?></option></select></label>
					<div class="fpc-option-editor" data-fpc-option-editor>
						<div class="fpc-option-editor-heading"><strong><?php esc_html_e( 'Choices', 'foundation-customer-form' ); ?></strong><button class="button" type="button" data-fpc-add-option><span aria-hidden="true">＋</span><?php esc_html_e( 'Add choice', 'foundation-customer-form' ); ?></button></div>
						<div class="fpc-option-list" data-fpc-option-list>
							<?php foreach ( (array) ( isset( $field['options'] ) ? $field['options'] : array() ) as $option ) : $has_routes = ! empty( $option['route_step_ids'] ) || ! empty( $option['route_step_id'] ); ?>
								<div class="fpc-option-row" data-fpc-option-row data-option-routes="<?php echo $has_routes ? '1' : '0'; ?>">
									<span class="fpc-option-grip" aria-hidden="true">•</span>
									<input type="hidden" value="<?php echo esc_attr( isset( $option['value'] ) ? $option['value'] : '' ); ?>" data-fpc-option-value>
									<label><span class="screen-reader-text"><?php esc_html_e( 'Choice label', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( isset( $option['label'] ) ? $option['label'] : '' ); ?>" data-fpc-option-label maxlength="180"></label>
									<?php if ( $has_routes ) : ?><span class="fpc-option-route-pill"><?php esc_html_e( 'Routes onward', 'foundation-customer-form' ); ?></span><?php endif; ?>
									<button class="button-link-delete" type="button" data-fpc-remove-option><?php esc_html_e( 'Remove', 'foundation-customer-form' ); ?></button>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php elseif ( in_array( $type, array( 'number_input', 'range_slider' ), true ) ) : ?>
					<div class="fpc-number-settings">
						<label class="fpc-field"><span><?php esc_html_e( 'Minimum', 'foundation-customer-form' ); ?></span><input type="number" step="any" value="<?php echo esc_attr( isset( $field['min'] ) ? $field['min'] : 0 ); ?>" data-fpc-field-min></label>
						<label class="fpc-field"><span><?php esc_html_e( 'Maximum', 'foundation-customer-form' ); ?></span><input type="number" step="any" value="<?php echo esc_attr( isset( $field['max'] ) ? $field['max'] : 50 ); ?>" data-fpc-field-max></label>
						<label class="fpc-field"><span><?php esc_html_e( 'Step', 'foundation-customer-form' ); ?></span><input type="number" min="0.01" step="any" value="<?php echo esc_attr( isset( $field['step'] ) ? $field['step'] : 1 ); ?>" data-fpc-field-step></label>
						<label class="fpc-field"><span><?php esc_html_e( 'Unit', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( isset( $field['unit'] ) ? $field['unit'] : '' ); ?>" data-fpc-field-unit></label>
					</div>
				<?php elseif ( 'toggle' === $type ) : ?>
					<div class="fpc-editor-grid-two"><label class="fpc-field"><span><?php esc_html_e( 'Yes label', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( isset( $field['yes_label'] ) ? $field['yes_label'] : 'Yes' ); ?>" data-fpc-field-yes-label></label><label class="fpc-field"><span><?php esc_html_e( 'No label', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( isset( $field['no_label'] ) ? $field['no_label'] : 'No' ); ?>" data-fpc-field-no-label></label></div>
				<?php elseif ( 'text_input' === $type ) : ?>
					<label class="fpc-field"><span><?php esc_html_e( 'Placeholder', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" data-fpc-field-placeholder></label>
				<?php elseif ( in_array( $type, array( 'description', 'section_title', 'rich_text' ), true ) ) : ?>
					<label class="fpc-field"><span><?php esc_html_e( 'Text', 'foundation-customer-form' ); ?></span><textarea rows="3" data-fpc-field-text><?php echo esc_textarea( isset( $field['text'] ) ? $field['text'] : '' ); ?></textarea></label>
				<?php elseif ( 'file_upload' === $type ) : ?>
					<div class="fpc-editor-grid-three"><label class="fpc-field"><span><?php esc_html_e( 'Accepted file types', 'foundation-customer-form' ); ?></span><input type="text" value="<?php echo esc_attr( isset( $field['accept'] ) ? $field['accept'] : '' ); ?>" data-fpc-field-accept></label><label class="fpc-field"><span><?php esc_html_e( 'Maximum files', 'foundation-customer-form' ); ?></span><input type="number" min="1" max="20" value="<?php echo esc_attr( isset( $field['max_files'] ) ? $field['max_files'] : 5 ); ?>" data-fpc-field-max-files></label><label class="fpc-field"><span><?php esc_html_e( 'Maximum MB per file', 'foundation-customer-form' ); ?></span><input type="number" min="1" max="100" value="<?php echo esc_attr( isset( $field['max_file_size_mb'] ) ? $field['max_file_size_mb'] : 10 ); ?>" data-fpc-field-max-size></label></div>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( ! empty( $pricing_keys ) ) : ?><div class="fpc-protected-rule is-pricing"><strong><?php esc_html_e( 'Pricing linked', 'foundation-customer-form' ); ?></strong><span><?php echo esc_html( implode( ', ', $pricing_keys ) ); ?></span><a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->page_slug, 'tab' => 'prices' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit amounts in Prices', 'foundation-customer-form' ); ?></a></div><?php endif; ?>
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
				<hr><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Lead capture & retention', 'foundation-customer-form' ); ?></p>
				<?php $this->checkbox_field( 'early_capture_enabled', __( 'Offer name + email save before the first service question', 'foundation-customer-form' ), ! empty( $settings['early_capture_enabled'] ) ); ?>
				<?php $this->checkbox_field( 'marketing_opt_in_enabled', __( 'Show a separate optional marketing opt-in', 'foundation-customer-form' ), ! empty( $settings['marketing_opt_in_enabled'] ) ); ?>
				<?php $this->textarea_field( 'marketing_opt_in_label', __( 'Marketing opt-in wording', 'foundation-customer-form' ), $settings['marketing_opt_in_label'], 2 ); ?>
				<?php $this->input_field( 'anonymous_local_retention_days', __( 'Anonymous browser-draft retention (days)', 'foundation-customer-form' ), $settings['anonymous_local_retention_days'], 'number', false, __( 'Only journey selections are stored locally. Contact details are not stored in anonymous browser drafts.', 'foundation-customer-form' ) ); ?>
				<?php $this->input_field( 'allowed_file_types', __( 'Allowed upload extensions', 'foundation-customer-form' ), $settings['allowed_file_types'], 'text', false, __( 'SVG and executable formats should remain excluded.', 'foundation-customer-form' ) ); ?>
				<?php $this->input_field( 'max_file_size_mb', __( 'Maximum file size (MB)', 'foundation-customer-form' ), $settings['max_file_size_mb'], 'number' ); ?>
				<?php $this->input_field( 'max_total_upload_mb', __( 'Maximum total upload (MB)', 'foundation-customer-form' ), $settings['max_total_upload_mb'], 'number' ); ?>
				<?php $this->input_field( 'max_files_per_field', __( 'Maximum files per upload question', 'foundation-customer-form' ), $settings['max_files_per_field'], 'number' ); ?>
				<?php $this->input_field( 'draft_retention_days', __( 'Saved-draft retention (days)', 'foundation-customer-form' ), $settings['draft_retention_days'], 'number' ); ?>
				<?php $this->input_field( 'lead_retention_days', __( 'Stored-enquiry retention (days)', 'foundation-customer-form' ), $settings['lead_retention_days'], 'number', false, __( 'Private enquiry records are deleted automatically after this period. Minimum 30 days.', 'foundation-customer-form' ) ); ?>
				<?php $this->input_field( 'submission_cooldown_seconds', __( 'Repeat-submission cooldown (seconds)', 'foundation-customer-form' ), $settings['submission_cooldown_seconds'], 'number' ); ?>
				<hr><p class="fpc-admin-eyebrow"><?php esc_html_e( 'Abuse protection', 'foundation-customer-form' ); ?></p>
				<?php $this->checkbox_field( 'turnstile_enabled', __( 'Enable Cloudflare Turnstile on resume-link sends and final submissions', 'foundation-customer-form' ), ! empty( $settings['turnstile_enabled'] ) ); ?>
				<?php $this->input_field( 'turnstile_site_key', __( 'Turnstile site key', 'foundation-customer-form' ), $settings['turnstile_site_key'], 'text' ); ?>
				<?php $this->input_field( 'turnstile_secret_key', __( 'Turnstile secret key', 'foundation-customer-form' ), '', 'password', false, empty( $settings['turnstile_secret_key'] ) ? __( 'Not set. Excluded from calculator configuration exports and never sent to the browser.', 'foundation-customer-form' ) : __( 'A secret is already saved. Leave blank to keep it, or enter a new value to replace it. Excluded from calculator configuration exports and never sent to the browser.', 'foundation-customer-form' ) ); ?>
				<?php $this->input_field( 'minimum_interaction_seconds', __( 'Minimum interaction before protected requests (seconds)', 'foundation-customer-form' ), $settings['minimum_interaction_seconds'], 'number' ); ?>
				<?php $this->input_field( 'magic_link_resend_seconds', __( 'Magic-link resend cooldown (seconds)', 'foundation-customer-form' ), $settings['magic_link_resend_seconds'], 'number' ); ?>
				<?php $this->input_field( 'magic_link_email_limit_hour', __( 'Magic links per email / hour', 'foundation-customer-form' ), $settings['magic_link_email_limit_hour'], 'number' ); ?>
				<?php $this->input_field( 'magic_link_ip_limit_hour', __( 'Magic links per connection / hour', 'foundation-customer-form' ), $settings['magic_link_ip_limit_hour'], 'number' ); ?>
				<?php $this->input_field( 'draft_save_ip_limit_hour', __( 'Draft saves per connection / hour', 'foundation-customer-form' ), $settings['draft_save_ip_limit_hour'], 'number' ); ?>
				<?php $this->input_field( 'submit_ip_limit_hour', __( 'Submissions per connection / hour', 'foundation-customer-form' ), $settings['submit_ip_limit_hour'], 'number' ); ?>
				<?php $this->input_field( 'submit_email_limit_hour', __( 'Submissions per email / hour', 'foundation-customer-form' ), $settings['submit_email_limit_hour'], 'number' ); ?>
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
