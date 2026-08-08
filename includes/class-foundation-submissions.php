<?php
/**
 * Reliable local storage for calculator enquiries.
 *
 * A lead is stored before WordPress attempts email delivery. This prevents an
 * SMTP outage from silently eating a customer enquiry.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Foundation_Submissions {
	const POST_TYPE       = 'foundation_quote';
	const BRIEF_POST_TYPE = 'foundation_brief';
	const CRON_HOOK       = 'foundation_cleanup_old_submissions';


	/**
	 * Lightweight internal workflow labels. These are deliberately separate
	 * from the brief lifecycle (captured/verified/converted) so staff can manage
	 * follow-up without changing customer-facing behaviour.
	 */
	public static function workflow_statuses() {
		return array(
			'new'         => __( 'New', 'foundation-customer-form' ),
			'in_progress' => __( 'In progress', 'foundation-customer-form' ),
			'follow_up'   => __( 'Follow up', 'foundation-customer-form' ),
			'waiting'     => __( 'Waiting', 'foundation-customer-form' ),
			'complete'    => __( 'Complete', 'foundation-customer-form' ),
			'archived'    => __( 'Archived', 'foundation-customer-form' ),
		);
	}

	private static function lead_post_type( $record_type ) {
		if ( 'brief' === $record_type ) {
			return self::BRIEF_POST_TYPE;
		}
		if ( 'enquiry' === $record_type ) {
			return self::POST_TYPE;
		}
		return '';
	}

	public static function get_workflow_status( $post_id, $record_type ) {
		$post_type = self::lead_post_type( $record_type );
		if ( '' === $post_type || $post_type !== get_post_type( absint( $post_id ) ) ) {
			return '';
		}
		$status  = sanitize_key( (string) get_post_meta( absint( $post_id ), '_foundation_workflow_status', true ) );
		$allowed = self::workflow_statuses();
		if ( isset( $allowed[ $status ] ) ) {
			return $status;
		}
		return 'brief' === $record_type ? 'in_progress' : 'new';
	}

	public static function update_workflow_status( $post_id, $record_type, $status ) {
		$post_id   = absint( $post_id );
		$post_type = self::lead_post_type( $record_type );
		$status    = sanitize_key( (string) $status );
		$allowed   = self::workflow_statuses();
		if ( '' === $post_type || $post_type !== get_post_type( $post_id ) || ! isset( $allowed[ $status ] ) ) {
			return false;
		}
		update_post_meta( $post_id, '_foundation_workflow_status', $status );
		update_post_meta( $post_id, '_foundation_workflow_updated_at', current_time( 'mysql' ) );
		return true;
	}

	private static function resume_revocation_key( $token_hash ) {
		$token_hash = (string) $token_hash;
		return '' === $token_hash ? '' : 'fpc_rev_' . substr( hash( 'sha256', $token_hash ), 0, 32 );
	}

	public static function revoke_brief_magic_link( $post_id ) {
		$post_id = absint( $post_id );
		if ( self::BRIEF_POST_TYPE !== get_post_type( $post_id ) ) {
			return false;
		}
		$token_hash = (string) get_post_meta( $post_id, '_foundation_brief_token_hash', true );
		$transient_key = sanitize_text_field( (string) get_post_meta( $post_id, '_foundation_brief_transient_key', true ) );
		if ( '' !== $transient_key ) {
			delete_transient( $transient_key );
		}
		if ( '' !== $token_hash ) {
			$revocation_key = self::resume_revocation_key( $token_hash );
			if ( '' !== $revocation_key ) {
				$settings = foundation_get_settings();
				$days = max( 1, min( 90, absint( $settings['draft_retention_days'] ?? 30 ) ) );
				set_transient( $revocation_key, 1, $days * DAY_IN_SECONDS );
			}
		}
		delete_post_meta( $post_id, '_foundation_brief_token_hash' );
		delete_post_meta( $post_id, '_foundation_brief_transient_key' );
		return true;
	}

	public static function is_resume_token_revoked( $token ) {
		$token_hash = self::hash_token( $token );
		$revocation_key = self::resume_revocation_key( $token_hash );
		return '' !== $revocation_key && (bool) get_transient( $revocation_key );
	}

	public static function update_lead_contact( $post_id, $record_type, $email, $name = '' ) {
		$post_id   = absint( $post_id );
		$post_type = self::lead_post_type( $record_type );
		$email     = sanitize_email( $email );
		$name      = sanitize_text_field( $name );
		if ( '' === $post_type || $post_type !== get_post_type( $post_id ) || ! is_email( $email ) ) {
			return new WP_Error( 'foundation_invalid_lead_contact', __( 'Enter a valid customer email address.', 'foundation-customer-form' ) );
		}

		if ( 'brief' === $record_type ) {
			$meta_key  = '_foundation_brief_contact';
			$email_key = '_foundation_brief_contact_email';
		} else {
			$meta_key  = '_foundation_contact';
			$email_key = '_foundation_contact_email';
		}
		$contact   = (array) get_post_meta( $post_id, $meta_key, true );
		$old_email = sanitize_email( $contact['email'] ?? '' );
		$contact['email'] = $email;
		if ( '' !== $name ) {
			$contact['name'] = $name;
		}
		update_post_meta( $post_id, $meta_key, $contact );
		update_post_meta( $post_id, $email_key, $email );

		if ( 'brief' === $record_type && strtolower( $old_email ) !== strtolower( $email ) ) {
			self::revoke_brief_magic_link( $post_id );
			update_post_meta( $post_id, '_foundation_brief_verified', 0 );
			update_post_meta( $post_id, '_foundation_brief_verified_at', '' );
			update_post_meta( $post_id, '_foundation_brief_status', 'captured' );
		}
		return $contact;
	}

	public static function delete_lead( $post_id, $record_type ) {
		$post_id   = absint( $post_id );
		$post_type = self::lead_post_type( $record_type );
		if ( '' === $post_type || $post_type !== get_post_type( $post_id ) ) {
			return false;
		}
		if ( 'brief' === $record_type ) {
			self::revoke_brief_magic_link( $post_id );
		}
		return (bool) wp_delete_post( $post_id, true );
	}

	public static function count_archived() {
		$count = 0;
		foreach ( array( self::BRIEF_POST_TYPE, self::POST_TYPE ) as $post_type ) {
			$count += count( get_posts( array(
				'post_type'      => $post_type,
				'post_status'    => 'private',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_foundation_workflow_status',
				'meta_value'     => 'archived',
			) ) );
		}
		return $count;
	}

	public static function clear_archived() {
		$deleted = 0;
		foreach ( array( 'brief' => self::BRIEF_POST_TYPE, 'enquiry' => self::POST_TYPE ) as $record_type => $post_type ) {
			$ids = get_posts( array(
				'post_type'      => $post_type,
				'post_status'    => 'private',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_foundation_workflow_status',
				'meta_value'     => 'archived',
			) );
			foreach ( $ids as $post_id ) {
				if ( self::delete_lead( $post_id, $record_type ) ) {
					$deleted++;
				}
			}
		}
		return $deleted;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'schedule_cleanup' ) );
		add_action( self::CRON_HOOK, array( $this, 'cleanup_old_submissions' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_privacy_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_privacy_eraser' ) );
	}

	/**
	 * Private storage type. No public URL, REST exposure or standard editor UI.
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Calculator enquiries', 'foundation-customer-form' ),
					'singular_name' => __( 'Calculator enquiry', 'foundation-customer-form' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);

		register_post_type(
			self::BRIEF_POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Unfinished project briefs', 'foundation-customer-form' ),
					'singular_name' => __( 'Unfinished project brief', 'foundation-customer-form' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	public static function schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function unschedule_cleanup() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public static function generate_reference() {
		$suffix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', wp_generate_password( 7, false, false ) ) );
		if ( strlen( $suffix ) < 5 ) {
			$suffix .= strtoupper( substr( hash( 'sha256', wp_generate_uuid4() ), 0, 5 ) );
		}
		return 'INK-' . gmdate( 'Ymd' ) . '-' . substr( $suffix, 0, 7 );
	}

	/**
	 * Store a lead before mail is attempted.
	 *
	 * @return array|WP_Error
	 */
	public static function create( $contact, $selections, $summary, $quote, $submission_token = '', $attachment_names = array() ) {
		$reference = self::generate_reference();
		$company   = isset( $contact['company'] ) ? $contact['company'] : '';
		$name      = isset( $contact['name'] ) ? $contact['name'] : '';
		$title     = trim( $reference . ' · ' . ( $company ? $company : $name ) );

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => wp_strip_all_tags( $title ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$token_hash = self::hash_token( $submission_token );
		update_post_meta( $post_id, '_foundation_reference', $reference );
		update_post_meta( $post_id, '_foundation_contact', $contact );
		update_post_meta( $post_id, '_foundation_contact_email', sanitize_email( $contact['email'] ?? '' ) );
		update_post_meta( $post_id, '_foundation_selections', $selections );
		update_post_meta( $post_id, '_foundation_summary', $summary );
		update_post_meta( $post_id, '_foundation_quote', $quote );
		update_post_meta( $post_id, '_foundation_attachment_names', array_values( array_map( 'sanitize_file_name', (array) $attachment_names ) ) );
		update_post_meta( $post_id, '_foundation_submission_token_hash', $token_hash );
		update_post_meta( $post_id, '_foundation_admin_mail_status', 'pending' );
		update_post_meta( $post_id, '_foundation_customer_mail_status', 'pending' );

		if ( '' !== $token_hash ) {
			set_transient( 'foundation_submission_' . $token_hash, (int) $post_id, DAY_IN_SECONDS );
		}

		return array(
			'id'        => (int) $post_id,
			'reference' => $reference,
		);
	}

	public static function hash_token( $submission_token ) {
		if ( ! self::is_valid_token( $submission_token ) ) {
			return '';
		}
		return hash_hmac( 'sha256', $submission_token, wp_salt( 'auth' ) );
	}

	/**
	 * Acquire a short, database-backed lock for an idempotency token. add_option()
	 * is atomic on the unique option name, which closes the small race between a
	 * duplicate lookup and the first enquiry being stored.
	 */
	public static function acquire_submission_lock( $submission_token, $ttl = 120 ) {
		$token_hash = self::hash_token( $submission_token );
		if ( '' === $token_hash ) {
			return false;
		}

		$key     = 'foundation_submission_lock_' . $token_hash;
		$expires = time() + max( 30, min( 300, absint( $ttl ) ) );
		if ( add_option( $key, $expires, '', false ) ) {
			return true;
		}

		$current = (int) get_option( $key, 0 );
		if ( $current > 0 && $current < time() ) {
			delete_option( $key );
			return add_option( $key, $expires, '', false );
		}

		return false;
	}

	public static function release_submission_lock( $submission_token ) {
		$token_hash = self::hash_token( $submission_token );
		if ( '' !== $token_hash ) {
			delete_option( 'foundation_submission_lock_' . $token_hash );
		}
	}

	public static function is_valid_token( $submission_token ) {
		return is_string( $submission_token ) && (bool) preg_match( '/^[A-Za-z0-9_-]{20,100}$/', $submission_token );
	}

	public static function find_by_token( $submission_token ) {
		$token_hash = self::hash_token( $submission_token );
		if ( '' === $token_hash ) {
			return 0;
		}

		$cached = (int) get_transient( 'foundation_submission_' . $token_hash );
		if ( $cached > 0 && self::POST_TYPE === get_post_type( $cached ) ) {
			return $cached;
		}

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_foundation_submission_token_hash',
				'meta_value'     => $token_hash,
			)
		);

		return empty( $posts ) ? 0 : (int) $posts[0];
	}

	public static function get_record( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id < 1 || self::POST_TYPE !== get_post_type( $post_id ) ) {
			return array();
		}

		return array(
			'id'                   => $post_id,
			'reference'            => (string) get_post_meta( $post_id, '_foundation_reference', true ),
			'contact'              => (array) get_post_meta( $post_id, '_foundation_contact', true ),
			'selections'           => (array) get_post_meta( $post_id, '_foundation_selections', true ),
			'summary'              => (array) get_post_meta( $post_id, '_foundation_summary', true ),
			'quote'                => (array) get_post_meta( $post_id, '_foundation_quote', true ),
			'attachment_names'     => (array) get_post_meta( $post_id, '_foundation_attachment_names', true ),
			'admin_mail_status'    => (string) get_post_meta( $post_id, '_foundation_admin_mail_status', true ),
			'customer_mail_status' => (string) get_post_meta( $post_id, '_foundation_customer_mail_status', true ),
			'workflow_status'      => self::get_workflow_status( $post_id, 'enquiry' ),
			'created_at'           => (string) get_post_field( 'post_date_gmt', $post_id ),
		);
	}

	public static function find_brief_by_token( $token ) {
		$token_hash = self::hash_token( $token );
		if ( '' === $token_hash ) {
			return 0;
		}
		$posts = get_posts(
			array(
				'post_type'      => self::BRIEF_POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_foundation_brief_token_hash',
				'meta_value'     => $token_hash,
			)
		);
		return empty( $posts ) ? 0 : (int) $posts[0];
	}

	public static function upsert_brief( $token, $contact, $selections, $current_step, $quote, $progress = 0, $marketing_consent = false ) {
		$token_hash = self::hash_token( $token );
		$email      = sanitize_email( $contact['email'] ?? '' );
		if ( '' === $token_hash || ! is_email( $email ) ) {
			return new WP_Error( 'foundation_invalid_brief', 'A valid saved-brief token and email address are required.' );
		}
		$post_id = self::find_brief_by_token( $token );
		if ( ! $post_id ) {
			$title = trim( 'Draft · ' . ( ! empty( $contact['name'] ) ? $contact['name'] : $email ) );
			$post_id = wp_insert_post(
				array(
					'post_type'   => self::BRIEF_POST_TYPE,
					'post_status' => 'private',
					'post_title'  => wp_strip_all_tags( $title ),
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			update_post_meta( $post_id, '_foundation_brief_token_hash', $token_hash );
			update_post_meta( $post_id, '_foundation_brief_verified', 0 );
			update_post_meta( $post_id, '_foundation_brief_status', 'captured' );
		}

		update_post_meta( $post_id, '_foundation_brief_contact', $contact );
		update_post_meta( $post_id, '_foundation_brief_contact_email', $email );
		update_post_meta( $post_id, '_foundation_brief_selections', $selections );
		update_post_meta( $post_id, '_foundation_brief_current_step', max( -1, min( 200, intval( $current_step ) ) ) );
		update_post_meta( $post_id, '_foundation_brief_quote', (array) $quote );
		update_post_meta( $post_id, '_foundation_brief_progress', max( 0, min( 100, absint( $progress ) ) ) );
		update_post_meta( $post_id, '_foundation_brief_updated_at', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_foundation_brief_marketing_consent', $marketing_consent ? 1 : 0 );
		return (int) $post_id;
	}

	public static function mark_brief_verified( $token ) {
		$post_id = self::find_brief_by_token( $token );
		if ( $post_id ) {
			$already = (bool) get_post_meta( $post_id, '_foundation_brief_verified', true );
			update_post_meta( $post_id, '_foundation_brief_verified', 1 );
			update_post_meta( $post_id, '_foundation_brief_verified_at', current_time( 'mysql' ) );
			update_post_meta( $post_id, '_foundation_brief_status', 'verified' );
			return ! $already;
		}
		return false;
	}

	public static function mark_brief_converted( $token, $enquiry_id ) {
		$post_id = self::find_brief_by_token( $token );
		if ( ! $post_id ) {
			return false;
		}
		update_post_meta( $post_id, '_foundation_brief_status', 'converted' );
		update_post_meta( $post_id, '_foundation_brief_enquiry_id', absint( $enquiry_id ) );
		update_post_meta( $post_id, '_foundation_brief_converted_at', current_time( 'mysql' ) );
		return true;
	}

	public static function get_brief_record( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id < 1 || self::BRIEF_POST_TYPE !== get_post_type( $post_id ) ) {
			return array();
		}
		return array(
			'id'                => $post_id,
			'contact'           => (array) get_post_meta( $post_id, '_foundation_brief_contact', true ),
			'selections'        => (array) get_post_meta( $post_id, '_foundation_brief_selections', true ),
			'quote'             => (array) get_post_meta( $post_id, '_foundation_brief_quote', true ),
			'current_step'      => intval( get_post_meta( $post_id, '_foundation_brief_current_step', true ) ),
			'progress'          => absint( get_post_meta( $post_id, '_foundation_brief_progress', true ) ),
			'verified'          => (bool) get_post_meta( $post_id, '_foundation_brief_verified', true ),
			'status'            => (string) get_post_meta( $post_id, '_foundation_brief_status', true ),
			'marketing_consent' => (bool) get_post_meta( $post_id, '_foundation_brief_marketing_consent', true ),
			'workflow_status'   => self::get_workflow_status( $post_id, 'brief' ),
			'updated_at'        => (string) get_post_meta( $post_id, '_foundation_brief_updated_at', true ),
			'created_at'        => (string) get_post_field( 'post_date_gmt', $post_id ),
		);
	}

	public static function get_briefs( $limit = 100, $include_converted = false ) {
		$ids = get_posts( array( 'post_type' => self::BRIEF_POST_TYPE, 'post_status' => 'private', 'posts_per_page' => max( 1, min( 200, absint( $limit ) ) ), 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ) );
		$records = array_values( array_filter( array_map( array( __CLASS__, 'get_brief_record' ), $ids ) ) );
		if ( ! $include_converted ) {
			$records = array_values( array_filter( $records, static function ( $record ) { return 'converted' !== ( $record['status'] ?? '' ); } ) );
		}
		return $records;
	}

	public static function count_briefs( $include_converted = false ) {
		return count( self::get_briefs( 200, $include_converted ) );
	}

	public static function update_mail_status( $post_id, $admin_status, $customer_status ) {
		$allowed = array( 'pending', 'sent', 'failed', 'disabled' );
		if ( in_array( $admin_status, $allowed, true ) ) {
			update_post_meta( $post_id, '_foundation_admin_mail_status', $admin_status );
		}
		if ( in_array( $customer_status, $allowed, true ) ) {
			update_post_meta( $post_id, '_foundation_customer_mail_status', $customer_status );
		}
	}

	/**
	 * Recent leads for the simple dashboard.
	 */
	public static function get_recent( $limit = 25 ) {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => max( 1, min( 100, absint( $limit ) ) ),
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		return array_values( array_filter( array_map( array( __CLASS__, 'get_record' ), $ids ) ) );
	}

	/**
	 * Paginated enquiry records for the private admin inbox.
	 */
	public static function get_page( $page = 1, $per_page = 30 ) {
		$page     = max( 1, absint( $page ) );
		$per_page = max( 1, min( 100, absint( $per_page ) ) );
		$ids      = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		return array_values( array_filter( array_map( array( __CLASS__, 'get_record' ), $ids ) ) );
	}

	public static function count_all() {
		$counts = wp_count_posts( self::POST_TYPE );
		return $counts && isset( $counts->private ) ? (int) $counts->private : 0;
	}

	public function cleanup_old_submissions() {
		$settings = foundation_get_settings();
		$days     = max( 30, min( 3650, absint( $settings['lead_retention_days'] ?? 180 ) ) );
		$before   = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// Bound each cron run to 1,000 deletions so housekeeping cannot monopolise a request.
		for ( $batch = 0; $batch < 10; $batch++ ) {
			$ids = get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'private',
					'posts_per_page' => 100,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					'date_query'     => array(
						array(
							'column' => 'post_date_gmt',
							'before' => $before,
						),
					),
				)
			);

			foreach ( $ids as $post_id ) {
				wp_delete_post( (int) $post_id, true );
			}

			if ( count( $ids ) < 100 ) {
				break;
			}
		}
		$draft_days   = max( 1, min( 90, absint( $settings['draft_retention_days'] ?? 30 ) ) );
		$draft_before = gmdate( 'Y-m-d H:i:s', time() - ( $draft_days * DAY_IN_SECONDS ) );
		for ( $batch = 0; $batch < 5; $batch++ ) {
			$ids = get_posts(
				array(
					'post_type'      => self::BRIEF_POST_TYPE,
					'post_status'    => 'private',
					'posts_per_page' => 100,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					'meta_key'       => '_foundation_brief_updated_at',
					'meta_value'     => $draft_before,
					'meta_compare'   => '<',
					'meta_type'      => 'DATETIME',
				)
			);
			foreach ( $ids as $post_id ) {
				wp_delete_post( (int) $post_id, true );
			}
			if ( count( $ids ) < 100 ) {
				break;
			}
		}
	}

	public function register_privacy_exporter( $exporters ) {
		$exporters['foundation-project-calculator'] = array(
			'exporter_friendly_name' => __( 'Foundation Project Calculator enquiries', 'foundation-customer-form' ),
			'callback'               => array( $this, 'privacy_exporter' ),
		);
		return $exporters;
	}

	public function privacy_exporter( $email_address, $page = 1 ) {
		$email = sanitize_email( $email_address );
		$page  = max( 1, absint( $page ) );
		$data  = array();

		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 50,
				'paged'          => $page,
				'fields'         => 'ids',
				'meta_key'       => '_foundation_contact_email',
				'meta_value'     => $email,
			)
		);
		foreach ( $ids as $post_id ) {
			$record = self::get_record( $post_id );
			$data[] = array(
				'group_id'    => 'foundation-calculator-enquiries',
				'group_label' => __( 'Calculator enquiries', 'foundation-customer-form' ),
				'item_id'     => 'foundation-quote-' . $post_id,
				'data'        => array(
					array( 'name' => __( 'Reference', 'foundation-customer-form' ), 'value' => $record['reference'] ?? '' ),
					array( 'name' => __( 'Contact details', 'foundation-customer-form' ), 'value' => wp_json_encode( $record['contact'], JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Answers', 'foundation-customer-form' ), 'value' => wp_json_encode( $record['summary'], JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Estimate', 'foundation-customer-form' ), 'value' => wp_json_encode( $record['quote'], JSON_UNESCAPED_SLASHES ) ),
				),
			);
		}

		$brief_ids = get_posts(
			array(
				'post_type'      => self::BRIEF_POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 50,
				'paged'          => $page,
				'fields'         => 'ids',
				'meta_key'       => '_foundation_brief_contact_email',
				'meta_value'     => $email,
			)
		);
		foreach ( $brief_ids as $post_id ) {
			$record = self::get_brief_record( $post_id );
			$data[] = array(
				'group_id'    => 'foundation-calculator-briefs',
				'group_label' => __( 'Saved project briefs', 'foundation-customer-form' ),
				'item_id'     => 'foundation-brief-' . $post_id,
				'data'        => array(
					array( 'name' => __( 'Contact details', 'foundation-customer-form' ), 'value' => wp_json_encode( $record['contact'], JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Saved selections', 'foundation-customer-form' ), 'value' => wp_json_encode( $record['selections'], JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Current estimate', 'foundation-customer-form' ), 'value' => wp_json_encode( $record['quote'], JSON_UNESCAPED_SLASHES ) ),
					array( 'name' => __( 'Progress', 'foundation-customer-form' ), 'value' => (string) ( $record['progress'] ?? 0 ) . '%' ),
					array( 'name' => __( 'Email verified', 'foundation-customer-form' ), 'value' => ! empty( $record['verified'] ) ? 'Yes' : 'No' ),
					array( 'name' => __( 'Marketing consent', 'foundation-customer-form' ), 'value' => ! empty( $record['marketing_consent'] ) ? 'Yes' : 'No' ),
				),
			);
		}

		return array( 'data' => $data, 'done' => count( $ids ) < 50 && count( $brief_ids ) < 50 );
	}

	public function register_privacy_eraser( $erasers ) {
		$erasers['foundation-project-calculator'] = array(
			'eraser_friendly_name' => __( 'Foundation Project Calculator enquiries', 'foundation-customer-form' ),
			'callback'              => array( $this, 'privacy_eraser' ),
		);
		return $erasers;
	}

	public function privacy_eraser( $email_address, $page = 1 ) {
		$email = sanitize_email( $email_address );
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 50,
				// Always remove the first remaining batch. Paging a shrinking result set
				// can otherwise skip records after the first deletion pass.
				'paged'          => 1,
				'fields'         => 'ids',
				'meta_key'       => '_foundation_contact_email',
				'meta_value'     => $email,
			)
		);
		$brief_ids = get_posts(
			array(
				'post_type'      => self::BRIEF_POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 50,
				'paged'          => 1,
				'fields'         => 'ids',
				'meta_key'       => '_foundation_brief_contact_email',
				'meta_value'     => $email,
			)
		);
		foreach ( array_merge( $ids, $brief_ids ) as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
		return array(
			'items_removed'  => ! empty( $ids ) || ! empty( $brief_ids ),
			'items_retained' => false,
			'messages'       => array(),
			'done'           => count( $ids ) < 50 && count( $brief_ids ) < 50,
		);
	}
}
