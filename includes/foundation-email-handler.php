<?php
/**
 * Public AJAX endpoints, secure lead capture, resumable drafts and email delivery.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_foundation_submit_quote', 'foundation_process_quote' );
add_action( 'wp_ajax_nopriv_foundation_submit_quote', 'foundation_process_quote' );
add_action( 'wp_ajax_foundation_track_quote_event', 'foundation_track_quote_event' );
add_action( 'wp_ajax_nopriv_foundation_track_quote_event', 'foundation_track_quote_event' );
add_action( 'wp_ajax_foundation_save_quote_draft', 'foundation_save_quote_draft' );
add_action( 'wp_ajax_nopriv_foundation_save_quote_draft', 'foundation_save_quote_draft' );
add_action( 'wp_ajax_foundation_resume_quote_draft', 'foundation_resume_quote_draft' );
add_action( 'wp_ajax_nopriv_foundation_resume_quote_draft', 'foundation_resume_quote_draft' );

/**
 * Bound text without assuming mbstring is installed.
 */
function foundation_limit_text( $value, $length ) {
	$value = (string) $value;
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
}

/**
 * Return a conservative request address for rate limiting, never for auditing.
 */
function foundation_get_request_ip() {
	$candidates = array();
	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$candidates[] = wp_unslash( $_SERVER['REMOTE_ADDR'] );
	}
	if ( ! empty( $_SERVER['HTTP_CF_RAY'] ) && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		$candidates[] = wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	}

	foreach ( $candidates as $candidate ) {
		$candidate = trim( (string) $candidate );
		if ( false !== filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
			return (string) apply_filters( 'foundation_calculator_request_ip', $candidate );
		}
	}
	return (string) apply_filters( 'foundation_calculator_request_ip', 'unknown' );
}

/**
 * Small transient-backed limiter. It is intentionally fail-open if the object
 * cache is unavailable so a cache outage cannot block every customer.
 */
function foundation_rate_limit_allow( $bucket, $limit, $window, $identity = '' ) {
	$bucket   = sanitize_key( $bucket );
	$limit    = max( 1, absint( $limit ) );
	$window   = max( 10, absint( $window ) );
	$identity = '' !== $identity ? (string) $identity : foundation_get_request_ip();
	$key      = 'fpc_rl_' . md5( $bucket . '|' . $identity );
	$state    = get_transient( $key );

	if ( ! is_array( $state ) || empty( $state['started'] ) || ( time() - (int) $state['started'] ) >= $window ) {
		$state = array( 'started' => time(), 'count' => 0 );
	}
	if ( (int) $state['count'] >= $limit ) {
		return false;
	}
	$state['count'] = (int) $state['count'] + 1;
	set_transient( $key, $state, $window );
	return true;
}

function foundation_track_quote_event() {
	check_ajax_referer( 'foundation_nonce', 'nonce' );
	if ( ! foundation_rate_limit_allow( 'metric', 150, HOUR_IN_SECONDS ) ) {
		wp_send_json_success( array( 'tracked' => false ) );
	}

	$event = sanitize_key( wp_unslash( $_POST['event'] ?? '' ) );
	$map   = array(
		'view'       => 'form_views',
		'start'      => 'form_starts',
		'incomplete' => 'incomplete',
		'failure'    => 'failures',
	);
	if ( empty( $map[ $event ] ) ) {
		wp_send_json_success( array( 'tracked' => false ) );
	}

	foundation_increment_metric( $map[ $event ] );
	if ( 'failure' === $event ) {
		$message = foundation_limit_text( sanitize_text_field( wp_unslash( $_POST['message'] ?? '' ) ), 300 );
		if ( '' !== $message ) {
			foundation_set_metric_meta( 'last_failure', $message );
		}
	}
	wp_send_json_success( array( 'tracked' => true ) );
}

function foundation_generate_resume_token() {
	return wp_generate_password( 40, false, false );
}

function foundation_is_valid_resume_token( $token ) {
	return is_string( $token ) && (bool) preg_match( '/^[A-Za-z0-9_-]{24,80}$/', $token );
}

function foundation_get_draft_transient_key( $token ) {
	return 'foundation_quote_draft_' . hash_hmac( 'sha256', $token, wp_salt( 'nonce' ) );
}

function foundation_get_resume_url( $token, $base_url = '' ) {
	$base_url = $base_url ? $base_url : home_url( '/' );
	return add_query_arg( 'foundation_resume', rawurlencode( $token ), $base_url );
}

function foundation_normalize_resume_base_url( $base_url ) {
	$home_url   = home_url( '/' );
	$home_parts = wp_parse_url( $home_url );
	$base_parts = wp_parse_url( (string) $base_url );

	if ( ! is_array( $home_parts ) || ! is_array( $base_parts ) ) {
		return $home_url;
	}

	$home_scheme = strtolower( (string) ( $home_parts['scheme'] ?? '' ) );
	$base_scheme = strtolower( (string) ( $base_parts['scheme'] ?? '' ) );
	$home_host   = strtolower( (string) ( $home_parts['host'] ?? '' ) );
	$base_host   = strtolower( (string) ( $base_parts['host'] ?? '' ) );
	$home_port   = isset( $home_parts['port'] ) ? (int) $home_parts['port'] : 0;
	$base_port   = isset( $base_parts['port'] ) ? (int) $base_parts['port'] : 0;

	if ( '' === $home_scheme || '' === $home_host || $home_scheme !== $base_scheme || $home_host !== $base_host || $home_port !== $base_port ) {
		return $home_url;
	}
	if ( isset( $base_parts['user'] ) || isset( $base_parts['pass'] ) ) {
		return $home_url;
	}

	$path   = isset( $base_parts['path'] ) && '' !== $base_parts['path'] ? $base_parts['path'] : '/';
	$origin = $home_scheme . '://' . $home_host . ( $home_port ? ':' . $home_port : '' );

	return esc_url_raw( $origin . '/' . ltrim( $path, '/' ) );
}

/**
 * Sanitize customer selections with hard bounds against oversized nested input.
 */
function foundation_sanitize_submission_selections( $selections ) {
	$clean = array();
	if ( ! is_array( $selections ) ) {
		return $clean;
	}

	$count = 0;
	foreach ( $selections as $key => $value ) {
		if ( $count >= 220 ) {
			break;
		}
		$key = substr( sanitize_key( $key ), 0, 100 );
		if ( '' === $key ) {
			continue;
		}

		if ( is_array( $value ) ) {
			$items = array();
			foreach ( array_slice( $value, 0, 100 ) as $item ) {
				if ( is_scalar( $item ) ) {
					$items[] = foundation_limit_text( sanitize_text_field( (string) $item ), 300 );
				}
			}
			$clean[ $key ] = array_values( array_unique( $items ) );
		} elseif ( is_scalar( $value ) ) {
			$clean[ $key ] = foundation_limit_text( sanitize_textarea_field( (string) $value ), 5000 );
		}
		$count++;
	}
	return $clean;
}

function foundation_sanitize_contact( $raw_contact, $settings, $draft = false ) {
	$raw_contact = is_array( $raw_contact ) ? $raw_contact : array();
	$contact     = array(
		'name'    => foundation_limit_text( sanitize_text_field( $raw_contact['name'] ?? '' ), 120 ),
		'company' => foundation_limit_text( sanitize_text_field( $raw_contact['company'] ?? '' ), 160 ),
		'email'   => foundation_limit_text( sanitize_email( $raw_contact['email'] ?? '' ), 190 ),
		'phone'   => foundation_limit_text( sanitize_text_field( $raw_contact['phone'] ?? '' ), 60 ),
		'website' => foundation_limit_text( esc_url_raw( $raw_contact['website'] ?? '' ), 500 ),
		'notes'   => foundation_limit_text( sanitize_textarea_field( $raw_contact['notes'] ?? '' ), 5000 ),
		'privacy' => foundation_normalize_bool( $raw_contact['privacy'] ?? false ),
	);

	if ( $draft ) {
		return $contact;
	}

	$missing = array();
	if ( strlen( $contact['name'] ) < 2 ) {
		$missing[] = 'your full name';
	}
	if ( strlen( $contact['company'] ) < 2 ) {
		$missing[] = 'your business or organisation name';
	}
	if ( ! is_email( $contact['email'] ) ) {
		$missing[] = 'a valid email address';
	}
	if ( ! empty( $settings['phone_required'] ) && strlen( $contact['phone'] ) < 3 ) {
		$missing[] = 'your phone number';
	}
	if ( ! $contact['privacy'] ) {
		$missing[] = 'the privacy confirmation';
	}

	if ( ! empty( $missing ) ) {
		return new WP_Error(
			'foundation_invalid_contact',
			'Please complete ' . implode( ', ', $missing ) . '.',
			array( 'status' => 422 )
		);
	}
	return $contact;
}

function foundation_save_quote_draft() {
	check_ajax_referer( 'foundation_nonce', 'nonce' );
	$settings       = foundation_get_settings();
	$token          = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
	$raw_contact    = isset( $_POST['contact'] ) ? wp_unslash( $_POST['contact'] ) : array();
	$raw_selections = isset( $_POST['selections'] ) ? wp_unslash( $_POST['selections'] ) : array();
	$current_step   = intval( $_POST['current_step'] ?? -1 );
	$resume_base    = foundation_normalize_resume_base_url( esc_url_raw( wp_unslash( $_POST['resume_base'] ?? home_url( '/' ) ) ) );
	$send_email     = ! empty( $_POST['send_email'] ) && '1' === (string) wp_unslash( $_POST['send_email'] );
	$contact        = foundation_sanitize_contact( $raw_contact, $settings, true );
	$selections     = foundation_sanitize_submission_selections( $raw_selections );

	// Saving a draft writes to the database even when no email is requested. Bound
	// every public save so automated requests cannot create unlimited transients.
	if ( ! foundation_rate_limit_allow( 'draft_save_ip', 30, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => 'Too many estimates were saved from this connection. Please wait before trying again.' ), 429 );
	}

	if ( $send_email ) {
		if ( ! is_email( $contact['email'] ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a valid email address before sending a resume link.' ), 422 );
		}
		$email_identity = hash_hmac( 'sha256', strtolower( $contact['email'] ), wp_salt( 'auth' ) );
		if ( ! foundation_rate_limit_allow( 'draft_email', 4, HOUR_IN_SECONDS, $email_identity ) || ! foundation_rate_limit_allow( 'draft_ip', 12, HOUR_IN_SECONDS ) ) {
			wp_send_json_error( array( 'message' => 'Too many resume links were requested. Please wait before trying again.' ), 429 );
		}
	}

	if ( ! foundation_is_valid_resume_token( $token ) ) {
		$token = foundation_generate_resume_token();
	}

	$payload = array(
		'contact'      => $contact,
		'selections'   => $selections,
		'current_step' => max( -1, min( 200, $current_step ) ),
		'updated_at'   => current_time( 'mysql' ),
	);
	$retention = max( 1, min( 90, absint( $settings['draft_retention_days'] ?? 14 ) ) ) * DAY_IN_SECONDS;
	set_transient( foundation_get_draft_transient_key( $token ), $payload, $retention );
	foundation_increment_metric( 'saved_drafts' );
	if ( ! empty( $contact['email'] ) ) {
		// Metrics remain anonymous. The draft itself contains the address and expires.
		foundation_set_metric_meta( 'last_saved_draft', current_time( 'M j, Y g:i a' ) );
	}

	$resume_url = foundation_get_resume_url( $token, $resume_base );
	$email_sent = false;
	if ( $send_email ) {
		$headers = foundation_build_mail_headers( $settings );
		$body    = '<p>Hi ' . esc_html( $contact['name'] ? $contact['name'] : 'there' ) . ',</p>';
		$body   .= '<p>You can resume your saved Inkfire project estimate using the private link below:</p>';
		$body   .= '<p><a href="' . esc_url( $resume_url ) . '">' . esc_html( $resume_url ) . '</a></p>';
		$body   .= '<p>The link expires in ' . esc_html( max( 1, absint( $settings['draft_retention_days'] ?? 14 ) ) ) . ' days. Uploaded files are not stored and will need to be added again.</p>';
		$email_sent = wp_mail( $contact['email'], 'Resume your Inkfire project estimate', $body, $headers );
	}

	wp_send_json_success(
		array(
			'token'      => $token,
			'resume_url' => $resume_url,
			'email_sent' => (bool) $email_sent,
		)
	);
}

function foundation_resume_quote_draft() {
	check_ajax_referer( 'foundation_nonce', 'nonce' );
	$token = sanitize_text_field( wp_unslash( $_REQUEST['token'] ?? '' ) );
	if ( ! foundation_is_valid_resume_token( $token ) ) {
		wp_send_json_error( array( 'message' => 'This saved estimate link is not valid.' ), 404 );
	}
	if ( ! foundation_rate_limit_allow( 'draft_resume', 60, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => 'Too many saved estimates were requested. Please wait and try again.' ), 429 );
	}

	$payload = get_transient( foundation_get_draft_transient_key( $token ) );
	// Backwards compatibility for drafts created by 1.3.x/early 1.4 builds.
	if ( empty( $payload ) ) {
		$legacy_key = 'foundation_quote_draft_' . $token;
		$payload    = get_transient( $legacy_key );
		if ( is_array( $payload ) ) {
			$settings = foundation_get_settings();
			set_transient( foundation_get_draft_transient_key( $token ), $payload, max( 1, absint( $settings['draft_retention_days'] ?? 14 ) ) * DAY_IN_SECONDS );
			delete_transient( $legacy_key );
		}
	}
	if ( empty( $payload ) || ! is_array( $payload ) ) {
		wp_send_json_error( array( 'message' => 'This saved estimate has expired or could not be found.' ), 404 );
	}
	unset( $payload['token'] );
	wp_send_json_success( $payload );
}

/**
 * Human-readable answers from visible screens only.
 */
function foundation_build_submission_summary( $steps, $selections, $uploaded_files ) {
	$summary = array();
	$visible = foundation_get_visible_form_steps( $steps, $selections );

	foreach ( $visible as $step ) {
		$section = sanitize_text_field( $step['title'] ?? '' );
		foreach ( (array) ( $step['fields'] ?? array() ) as $field ) {
			$field_id = $field['id'] ?? '';
			$type     = $field['type'] ?? '';
			$label    = sanitize_text_field( $field['label'] ?? 'Untitled question' );
			$value    = '';

			if ( in_array( $type, array( 'section_title', 'description', 'divider', 'calculation' ), true ) ) {
				continue;
			}

			if ( 'service_card' === $type ) {
				$labels = array();
				foreach ( foundation_get_selected_options( $field, $selections ) as $option ) {
					$labels[] = sanitize_text_field( $option['label'] ?? 'Option' );
				}
				$value = implode( ', ', $labels );
			} elseif ( 'toggle' === $type ) {
				$selected = array_map( 'strval', (array) ( $selections[ $field_id . '_options' ] ?? array() ) );
				if ( in_array( '0', $selected, true ) ) {
					$value = $field['yes_label'] ?? 'Yes';
				} elseif ( in_array( '1', $selected, true ) ) {
					$value = $field['no_label'] ?? 'No';
				}
			} elseif ( in_array( $type, array( 'number_input', 'range_slider' ), true ) ) {
				$number = $selections[ $field_id . '_val' ] ?? $selections[ $field_id ] ?? '';
				if ( '' !== (string) $number && is_numeric( $number ) ) {
					$value = (string) ( 0 + $number ) . ( ! empty( $field['unit'] ) ? ' ' . $field['unit'] : '' );
				}
			} elseif ( 'file_upload' === $type ) {
				$names = array();
				foreach ( (array) ( $uploaded_files[ $field_id ] ?? array() ) as $file ) {
					$names[] = sanitize_file_name( $file['name'] ?? '' );
				}
				$value = implode( ', ', array_filter( $names ) );
			} else {
				$value = sanitize_textarea_field( $selections[ $field_id ] ?? '' );
			}

			if ( '' !== trim( (string) $value ) ) {
				$summary[] = array(
					'section'    => $section,
					'field_id'   => sanitize_key( $field_id ),
					'label'      => $label,
					'value_text' => foundation_limit_text( (string) $value, 5000 ),
				);
			}
		}
	}
	return $summary;
}

function foundation_flatten_uploaded_files( $uploaded_files ) {
	$flat = array();
	foreach ( (array) $uploaded_files as $files ) {
		foreach ( (array) $files as $file ) {
			$flat[] = $file;
		}
	}
	return $flat;
}

function foundation_build_mail_headers( $settings, $reply_to_email = '', $reply_to_name = '' ) {
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$from_email = sanitize_email( $settings['from_email'] ?? '' );
	$from_name  = foundation_limit_text( sanitize_text_field( $settings['from_name'] ?? '' ), 120 );
	if ( is_email( $from_email ) && ! foundation_is_placeholder_email( $from_email ) ) {
		$headers[] = 'From: ' . wp_specialchars_decode( $from_name ? $from_name : get_bloginfo( 'name' ), ENT_QUOTES ) . ' <' . $from_email . '>';
	}
	if ( is_email( $reply_to_email ) ) {
		$reply_name = foundation_limit_text( sanitize_text_field( $reply_to_name ), 120 );
		$headers[]  = 'Reply-To: ' . wp_specialchars_decode( $reply_name ? $reply_name : $reply_to_email, ENT_QUOTES ) . ' <' . sanitize_email( $reply_to_email ) . '>';
	}
	return $headers;
}

function foundation_quote_response_payload( $record, $duplicate = false ) {
	$quote           = (array) ( $record['quote'] ?? array() );
	$admin_status    = (string) ( $record['admin_mail_status'] ?? 'unknown' );
	$customer_status = (string) ( $record['customer_mail_status'] ?? 'unknown' );
	$message         = 'Your enquiry has been stored safely.';
	if ( 'sent' === $admin_status ) {
		$message = 'Your estimate has been sent to Inkfire.';
	} elseif ( 'failed' === $admin_status ) {
		$message = 'Your enquiry is safely stored in the Inkfire calculator inbox, although the notification email did not send.';
	}

	return array(
		'quote'                 => $quote,
		'reference'             => $record['reference'] ?? '',
		'admin_email_status'    => $admin_status,
		'customer_email_status' => $customer_status,
		'duplicate'             => (bool) $duplicate,
		'message'               => $message,
	);
}

function foundation_process_quote() {
	check_ajax_referer( 'foundation_nonce', 'nonce' );
	$settings = foundation_get_settings();

	$honeypot = sanitize_text_field( wp_unslash( $_POST['foundation_honey'] ?? '' ) );
	if ( '' !== $honeypot ) {
		// Deliberately indistinguishable from success to automated form spam.
		wp_send_json_success(
			array(
				'quote'                 => array(),
				'customer_email_status' => 'disabled',
				'admin_email_status'    => 'disabled',
			)
		);
	}

	$submission_id = sanitize_text_field( wp_unslash( $_POST['submission_id'] ?? '' ) );
	if ( ! Foundation_Submissions::is_valid_token( $submission_id ) ) {
		wp_send_json_error( array( 'message' => 'Your calculator session is invalid. Please reload the page and try again.' ), 400 );
	}

	$existing_id = Foundation_Submissions::find_by_token( $submission_id );
	if ( $existing_id ) {
		wp_send_json_success( foundation_quote_response_payload( Foundation_Submissions::get_record( $existing_id ), true ) );
	}

	$raw_contact = isset( $_POST['contact'] ) ? wp_unslash( $_POST['contact'] ) : array();
	$contact     = foundation_sanitize_contact( $raw_contact, $settings, false );
	if ( is_wp_error( $contact ) ) {
		foundation_increment_metric( 'failures' );
		foundation_set_metric_meta( 'last_failure', $contact->get_error_message() );
		wp_send_json_error( array( 'message' => $contact->get_error_message() ), 422 );
	}

	$email_identity = hash_hmac( 'sha256', strtolower( $contact['email'] ), wp_salt( 'auth' ) );
	$cooldown       = max( 5, min( 600, absint( $settings['submission_cooldown_seconds'] ?? 30 ) ) );
	if ( ! foundation_rate_limit_allow( 'submit_cooldown', 1, $cooldown, $email_identity . '|' . foundation_get_request_ip() )
		|| ! foundation_rate_limit_allow( 'submit_ip_hour', 8, HOUR_IN_SECONDS )
		|| ! foundation_rate_limit_allow( 'submit_email_hour', 5, HOUR_IN_SECONDS, $email_identity ) ) {
		wp_send_json_error( array( 'message' => 'Too many enquiries were submitted in a short period. Please wait before trying again.' ), 429 );
	}

	$steps = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
	if ( empty( $steps ) || ( 1 === count( $steps ) && empty( $steps[0]['fields'] ) ) ) {
		foundation_increment_metric( 'failures' );
		foundation_set_metric_meta( 'last_failure', 'Calculator configuration missing.' );
		wp_send_json_error( array( 'message' => 'The calculator is temporarily unavailable. Please contact Inkfire directly.' ), 503 );
	}

	$raw_selections = isset( $_POST['selections'] ) ? wp_unslash( $_POST['selections'] ) : array();
	$selections     = foundation_sanitize_submission_selections( $raw_selections );
	$uploads        = foundation_collect_uploaded_files( $settings, $steps, $selections );
	if ( ! empty( $uploads['errors'] ) ) {
		$message = implode( ' ', $uploads['errors'] );
		foundation_increment_metric( 'failures' );
		foundation_set_metric_meta( 'last_failure', $message );
		wp_send_json_error( array( 'message' => $message ), 422 );
	}

	$missing = foundation_validate_required_submission_fields( $steps, $selections, $uploads['files'] );
	if ( ! empty( $missing ) ) {
		$message = 'Please complete: ' . implode( ', ', $missing ) . '.';
		foundation_increment_metric( 'failures' );
		foundation_set_metric_meta( 'last_failure', $message );
		wp_send_json_error( array( 'message' => $message ), 422 );
	}

	$value_errors = foundation_validate_submission_values( $steps, $selections );
	if ( ! empty( $value_errors ) ) {
		$message = implode( ' ', $value_errors );
		foundation_increment_metric( 'failures' );
		foundation_set_metric_meta( 'last_failure', $message );
		wp_send_json_error( array( 'message' => $message ), 422 );
	}

	$quote = foundation_calculate_quote( $steps, $selections );
	if ( empty( $quote['has_pricing'] ) && empty( $quote['manual_items'] ) ) {
		wp_send_json_error( array( 'message' => 'Choose at least one service before sending your estimate.' ), 422 );
	}

	$summary          = foundation_build_submission_summary( $steps, $selections, $uploads['files'] );
	$flat_attachments = foundation_flatten_uploaded_files( $uploads['files'] );
	$attachment_names = array_map(
		static function ( $file ) {
			return sanitize_file_name( $file['name'] ?? '' );
		},
		$flat_attachments
	);

	$lock_acquired = Foundation_Submissions::acquire_submission_lock( $submission_id );
	if ( ! $lock_acquired ) {
		// Another request with the same idempotency token may be finishing. Give it
		// a brief chance to publish the stored record before asking the browser to retry.
		for ( $attempt = 0; $attempt < 5; $attempt++ ) {
			usleep( 100000 );
			$existing_id = Foundation_Submissions::find_by_token( $submission_id );
			if ( $existing_id ) {
				wp_send_json_success( foundation_quote_response_payload( Foundation_Submissions::get_record( $existing_id ), true ) );
			}
		}
		wp_send_json_error( array( 'message' => 'This enquiry is already being processed. Please wait a moment and try again.' ), 409 );
	}

	// Re-check after taking the lock in case the first request completed between
	// the initial lookup and lock acquisition.
	$existing_id = Foundation_Submissions::find_by_token( $submission_id );
	if ( $existing_id ) {
		Foundation_Submissions::release_submission_lock( $submission_id );
		wp_send_json_success( foundation_quote_response_payload( Foundation_Submissions::get_record( $existing_id ), true ) );
	}

	$stored = Foundation_Submissions::create( $contact, $selections, $summary, $quote, $submission_id, $attachment_names );
	Foundation_Submissions::release_submission_lock( $submission_id );
	if ( is_wp_error( $stored ) ) {
		foundation_increment_metric( 'failures' );
		foundation_set_metric_meta( 'last_failure', 'Local enquiry storage failed.' );
		wp_send_json_error( array( 'message' => 'Your enquiry could not be stored safely. Please contact Inkfire directly.' ), 500 );
	}

	$post_id   = (int) $stored['id'];
	$reference = (string) $stored['reference'];

	$pdf_path  = ! empty( $settings['attach_pdf_summary'] ) ? foundation_generate_pdf_attachment( $contact, $summary, $quote, $settings, $reference ) : '';
	$json_path = ! empty( $settings['attach_json_summary'] ) ? foundation_generate_json_attachment( $contact, $summary, $quote, $settings, $flat_attachments, $reference ) : '';
	$zip_path  = ! empty( $settings['attach_zip_package'] ) ? foundation_create_submission_package( $contact, $summary, $quote, $settings, $uploads['files'], $pdf_path, $json_path, $reference ) : '';

	$admin_content    = foundation_generate_email_html( $contact, $summary, $quote, true, $settings, $reference, count( $flat_attachments ) );
	$customer_content = foundation_generate_email_html( $contact, $summary, $quote, false, $settings, $reference, 0 );
	$admin_to         = ( ! empty( $settings['admin_email'] ) && ! foundation_is_placeholder_email( $settings['admin_email'] ) )
		? sanitize_email( $settings['admin_email'] )
		: foundation_get_default_notification_email();
	$subject_prefix   = foundation_limit_text( sanitize_text_field( $settings['admin_subject_prefix'] ?? '' ), 120 );
	$subject_admin    = ( $subject_prefix ? $subject_prefix : 'New project calculator enquiry' ) . ': ' . $contact['company'] . ' [' . $reference . ']';
	$admin_headers    = foundation_build_mail_headers( $settings, $contact['email'], $contact['name'] );
	if ( ! empty( $settings['cc_emails'] ) ) {
		$admin_headers[] = 'Cc: ' . $settings['cc_emails'];
	}

	$admin_attachments = array();
	if ( $zip_path && is_readable( $zip_path ) ) {
		// The ZIP already contains the reports and uploads, avoiding duplicate mail weight.
		$admin_attachments[] = $zip_path;
	} else {
		foreach ( array( $pdf_path, $json_path ) as $report_path ) {
			if ( $report_path && is_readable( $report_path ) ) {
				$admin_attachments[] = $report_path;
			}
		}
		foreach ( $flat_attachments as $file ) {
			if ( ! empty( $file['tmp_name'] ) && is_readable( $file['tmp_name'] ) ) {
				$admin_attachments[] = $file['tmp_name'];
			}
		}
	}

	$admin_sent  = wp_mail( $admin_to, $subject_admin, $admin_content, $admin_headers, $admin_attachments );
	$admin_status = $admin_sent ? 'sent' : 'failed';

	$customer_status = 'disabled';
	if ( ! empty( $settings['customer_confirmation_enabled'] ) ) {
		$customer_subject = foundation_limit_text( sanitize_text_field( $settings['customer_subject'] ?? '' ), 150 );
		if ( '' === $customer_subject ) {
			$customer_subject = 'Your Inkfire planning estimate';
		}
		$customer_sent   = wp_mail( $contact['email'], $customer_subject . ' [' . $reference . ']', $customer_content, foundation_build_mail_headers( $settings, $admin_to, 'Inkfire' ) );
		$customer_status = $customer_sent ? 'sent' : 'failed';
	}

	Foundation_Submissions::update_mail_status( $post_id, $admin_status, $customer_status );
	foundation_cleanup_temp_files( array( $pdf_path, $json_path, $zip_path ) );
	foundation_increment_metric( 'responses_saved' );
	if ( ! $admin_sent ) {
		foundation_increment_metric( 'failures' );
		foundation_set_metric_meta( 'last_failure', 'Admin notification email failed for ' . $reference . '; enquiry stored locally.' );
	}

	wp_send_json_success( foundation_quote_response_payload( Foundation_Submissions::get_record( $post_id ), false ) );
}

function foundation_email_quote_totals_html( $quote, $settings ) {
	if ( foundation_is_quote_mode_enabled( $settings ) ) {
		return '<div style="background:#303347;color:#fff;border-radius:14px;padding:18px 20px;margin:22px 0;"><strong style="display:block;color:#FBCCBF;font-size:20px;">Tailored quotation</strong><span>Inkfire will confirm the scope and final price.</span></div>';
	}
	$currency = $settings['currency_symbol'] ?? '£';
	$cards    = '';
	if ( ! empty( $quote['one_off_max'] ) ) {
		$cards .= '<td valign="top" style="width:50%;padding:6px;"><div style="background:#191A28;color:#fff;border-radius:14px;padding:18px;"><span style="font-size:12px;color:#cfd3df;">One-off estimate</span><strong style="display:block;color:#FBCCBF;font-size:24px;line-height:1.2;margin-top:4px;">' . esc_html( foundation_format_quote_range( $quote['one_off_min'] ?? 0, $quote['one_off_max'] ?? 0, $currency ) ) . '</strong><small style="color:#cfd3df;">excluding VAT</small></div></td>';
	}
	if ( ! empty( $quote['monthly_max'] ) ) {
		$cards .= '<td valign="top" style="width:50%;padding:6px;"><div style="background:#191A28;color:#fff;border-radius:14px;padding:18px;"><span style="font-size:12px;color:#cfd3df;">Monthly estimate</span><strong style="display:block;color:#FBCCBF;font-size:24px;line-height:1.2;margin-top:4px;">' . esc_html( foundation_format_quote_range( $quote['monthly_min'] ?? 0, $quote['monthly_max'] ?? 0, $currency ) ) . '</strong><small style="color:#cfd3df;">per month, excluding VAT</small></div></td>';
	}
	return $cards ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px -6px;"><tr>' . $cards . '</tr></table>' : '';
}

function foundation_generate_email_html( $contact, $summary, $quote, $is_admin, $settings, $reference = '', $attachment_count = 0 ) {
	$currency      = $settings['currency_symbol'] ?? '£';
	$logo_url      = $settings['logo_url'] ?? '';
	$portfolio_url = $settings['portfolio_url'] ?? '';
	$quote_mode    = foundation_is_quote_mode_enabled( $settings );
	$social_links  = array_filter(
		array(
			'LinkedIn'  => $settings['linkedin_url'] ?? '',
			'Twitter/X' => $settings['twitter_url'] ?? '',
			'Facebook'  => $settings['facebook_url'] ?? '',
			'Instagram' => $settings['instagram_url'] ?? '',
			'TikTok'    => $settings['tiktok_url'] ?? '',
		)
	);

	ob_start();
	?>
	<!doctype html>
	<html lang="en">
	<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $is_admin ? 'New Inkfire project enquiry' : 'Your Inkfire estimate' ); ?></title></head>
	<body style="margin:0;padding:0;background:#f3f5f8;color:#2d3343;font-family:Arial,Helvetica,sans-serif;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f8;padding:20px 8px;">
			<tr><td align="center">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:760px;background:#fff;border:1px solid #dde1e9;border-radius:18px;overflow:hidden;">
					<tr><td style="background:#191A28;color:#fff;padding:28px;">
						<?php if ( $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt="Inkfire" style="display:block;max-height:52px;max-width:180px;width:auto;margin-bottom:14px;"><?php endif; ?>
						<p style="margin:0 0 5px;color:#FBCCBF;font-size:12px;font-weight:bold;letter-spacing:.12em;text-transform:uppercase;">Reference <?php echo esc_html( $reference ); ?></p>
						<h1 style="margin:0;color:#fff;font-size:28px;line-height:1.2;"><?php echo esc_html( $is_admin ? 'New project calculator enquiry' : 'Your Inkfire planning estimate' ); ?></h1>
					</td></tr>
					<tr><td style="padding:28px;">
						<?php if ( $is_admin ) : ?>
							<p style="margin:0 0 10px;">A new enquiry is stored safely in the calculator inbox.</p>
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8fb;border-radius:12px;margin:0 0 20px;">
								<tr><td style="padding:15px 18px;line-height:1.65;"><strong><?php echo esc_html( $contact['name'] ); ?></strong><br><?php echo esc_html( $contact['company'] ); ?><br><a href="mailto:<?php echo esc_attr( $contact['email'] ); ?>" style="color:#075e53;"><?php echo esc_html( $contact['email'] ); ?></a><?php if ( $contact['phone'] ) : ?><br><?php echo esc_html( $contact['phone'] ); ?><?php endif; ?><?php if ( $contact['website'] ) : ?><br><a href="<?php echo esc_url( $contact['website'] ); ?>" style="color:#075e53;"><?php echo esc_html( $contact['website'] ); ?></a><?php endif; ?></td></tr>
							</table>
							<?php if ( $attachment_count ) : ?><p style="color:#5e6475;font-size:13px;">The customer uploaded <?php echo esc_html( $attachment_count ); ?> file<?php echo 1 === $attachment_count ? '' : 's'; ?>. See the attached staff package.</p><?php endif; ?>
						<?php else : ?>
							<p style="margin:0 0 10px;">Hi <?php echo esc_html( $contact['name'] ); ?>,</p>
							<p style="margin:0 0 18px;line-height:1.65;"><?php echo esc_html( $settings['customer_intro'] ?? '' ); ?></p>
						<?php endif; ?>

						<?php echo wp_kses_post( foundation_email_quote_totals_html( $quote, $settings ) ); ?>

						<?php if ( ! $quote_mode && ! empty( $quote['line_items'] ) ) : ?>
							<h2 style="font-size:19px;margin:28px 0 10px;color:#191A28;">Calculated items</h2>
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
								<?php foreach ( $quote['line_items'] as $item ) : ?>
									<tr><td style="border-bottom:1px solid #e8ebf0;padding:12px 0;color:#3c4354;"><?php echo esc_html( $item['label'] ?? 'Service' ); ?></td><td align="right" style="border-bottom:1px solid #e8ebf0;padding:12px 0;font-weight:bold;white-space:nowrap;color:#191A28;"><?php echo esc_html( foundation_format_quote_range( $item['min'] ?? 0, $item['max'] ?? 0, $currency ) ); ?><?php echo 'monthly' === ( $item['billing'] ?? '' ) ? '/mo' : ''; ?></td></tr>
								<?php endforeach; ?>
							</table>
						<?php endif; ?>

						<?php if ( ! empty( $quote['manual_items'] ) ) : ?>
							<div style="background:#fff7df;border:1px solid #e5cb7d;border-radius:14px;padding:18px 20px;margin-top:22px;">
								<h2 style="font-size:18px;margin:0 0 12px;color:#604100;">Items needing a tailored quote</h2>
								<?php foreach ( $quote['manual_items'] as $item ) : ?><p style="margin:10px 0;color:#654a13;"><strong><?php echo esc_html( $item['label'] ?? 'Service' ); ?>:</strong> <?php echo esc_html( $item['note'] ?? 'Scope to be confirmed.' ); ?></p><?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $is_admin ) : ?>
							<h2 style="font-size:19px;margin:28px 0 10px;color:#191A28;">Customer answers</h2>
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
								<?php foreach ( $summary as $row ) : ?>
									<tr><td valign="top" style="border-bottom:1px solid #e8ebf0;padding:12px 10px 12px 0;width:42%;"><small style="display:block;color:#737a8a;margin-bottom:3px;"><?php echo esc_html( $row['section'] ?? '' ); ?></small><strong><?php echo esc_html( $row['label'] ?? '' ); ?></strong></td><td valign="top" style="border-bottom:1px solid #e8ebf0;padding:12px 0;white-space:pre-wrap;"><?php echo nl2br( esc_html( $row['value_text'] ?? '' ) ); ?></td></tr>
								<?php endforeach; ?>
							</table>
							<?php if ( $contact['notes'] ) : ?><h2 style="font-size:19px;margin:28px 0 8px;color:#191A28;">Additional notes</h2><p style="white-space:pre-wrap;line-height:1.65;"><?php echo nl2br( esc_html( $contact['notes'] ) ); ?></p><?php endif; ?>
						<?php endif; ?>

						<div style="background:#f7f8fb;border-left:4px solid #0c806f;border-radius:0 10px 10px 0;padding:14px 17px;margin-top:24px;color:#5e6475;font-size:13px;line-height:1.55;"><strong style="color:#303347;">Estimate information</strong><br><?php echo esc_html( $settings['vat_note'] ?? '' ); ?><br><?php echo esc_html( $settings['estimate_disclaimer'] ?? '' ); ?></div>

						<?php if ( ! $is_admin && $portfolio_url ) : ?><p style="margin:26px 0 0;text-align:center;"><a href="<?php echo esc_url( $portfolio_url ); ?>" style="display:inline-block;background:#FBCCBF;color:#191A28;border-radius:999px;padding:13px 24px;text-decoration:none;font-weight:bold;">See Inkfire's work</a></p><?php endif; ?>
						<?php if ( ! $is_admin && $social_links ) : ?><p style="margin:24px 0 0;text-align:center;color:#737a8a;font-size:13px;"><?php $parts = array(); foreach ( $social_links as $label => $url ) { $parts[] = '<a href="' . esc_url( $url ) . '" style="color:#075e53;text-decoration:none;font-weight:bold;">' . esc_html( $label ) . '</a>'; } echo wp_kses_post( implode( ' &nbsp;·&nbsp; ', $parts ) ); ?></p><?php endif; ?>
					</td></tr>
					<tr><td style="background:#f7f8fb;color:#818898;font-size:12px;padding:17px 28px;text-align:center;">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></td></tr>
				</table>
			</td></tr>
		</table>
	</body>
	</html>
	<?php
	return ob_get_clean();
}
