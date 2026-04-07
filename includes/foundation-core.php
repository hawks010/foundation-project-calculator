<?php
/**
 * Shared helpers, defaults, sanitisation, validation and packaging.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function foundation_is_placeholder_email( $email ) {
	$email = is_string( $email ) ? trim( strtolower( $email ) ) : '';
	if ( ! is_email( $email ) ) {
		return true;
	}

	$domain = substr( strrchr( $email, '@' ), 1 );

	return in_array( $domain, array( 'example.com', 'example.org', 'example.net' ), true );
}

function foundation_get_default_notification_email() {
	$admin_email = get_option( 'admin_email' );
	if ( ! foundation_is_placeholder_email( $admin_email ) ) {
		return sanitize_email( $admin_email );
	}

	return 'webmaster@inkfire.co.uk';
}

function foundation_get_default_sender_email() {
	$admin_email = get_option( 'admin_email' );
	if ( ! foundation_is_placeholder_email( $admin_email ) ) {
		return sanitize_email( $admin_email );
	}

	return foundation_get_default_notification_email();
}

function foundation_get_default_logo_url() {
	return FOUNDATION_URL . 'assets/IMG_1089.png';
}

function foundation_is_default_plugin_logo_url( $url ) {
	$path = wp_parse_url( (string) $url, PHP_URL_PATH );
	if ( ! is_string( $path ) ) {
		return false;
	}

	return (bool) preg_match( '#/wp-content/plugins/foundation-project-calculator[^/]*/assets/IMG_1089\.png$#', $path );
}

function foundation_migrate_default_asset_settings( $settings ) {
	if ( ! is_array( $settings ) ) {
		return $settings;
	}

	$logo_url = isset( $settings['logo_url'] ) ? (string) $settings['logo_url'] : '';
	if ( '' === $logo_url || foundation_is_default_plugin_logo_url( $logo_url ) ) {
		$settings['logo_url'] = foundation_get_default_logo_url();
	}

	return $settings;
}

function foundation_get_default_settings() {
	return array(
		'admin_email'                  => foundation_get_default_notification_email(),
		'cc_emails'                    => '',
		'from_name'                    => wp_strip_all_tags( get_bloginfo( 'name' ) ),
		'from_email'                   => foundation_get_default_sender_email(),
		'customer_confirmation_enabled'=> 1,
		'admin_subject_prefix'         => 'New customer project brief',
		'customer_subject'             => 'We’ve received your project brief',
		'customer_intro'               => 'Thanks for sending over your project brief! We have received your details safely.',
		'success_message'              => 'A detailed copy of your proposal has been sent to your email.',
		'launch_button_label'          => 'Get a Quote',
		'wizard_title'                 => 'Inkfire Project Calculator',
		'currency_symbol'              => '£',
		'logo_url'                     => foundation_get_default_logo_url(),
		'intro_image_url'              => 'https://inkfire.co.uk/wp-content/uploads/2025/12/250515_SCOPE-AWARDS_02_0489.jpg',
		'intro_heading'                => 'Why choose Inkfire?',
		'intro_text'                   => 'We’re a disabled-led team of 15+ with lived experience at the heart of everything we do. Our specialists bring decades of combined expertise across IT, web, creative and accessibility - delivering solutions that genuinely work for real people.',
		'testimonial_image_url'        => 'https://inkfire.co.uk/wp-content/uploads/2025/12/Screenshot-2025-12-01-at-22.00.39.png',
		'testimonial_heading'          => 'Why Our Clients Love Inkfire',
		'testimonial_quote'            => '“I\'m SO grateful for all that you do. I absolutely love my websites, and working with Imali and the team has been such a gift. A special shout-out to Sonny - he\'s always so helpful, efficient, and nothing is ever too much trouble. YAY for Sonny!”',
		'testimonial_attribution'      => 'Cat Lawless - catlawless.com',
		'portfolio_url'                => 'https://inkfire.co.uk/portfolio',
		'linkedin_url'                 => 'https://uk.linkedin.com/company/inkfire',
		'twitter_url'                  => 'https://twitter.com/Inkfirelimited',
		'facebook_url'                 => 'https://facebook.com/inkfirelimited',
		'instagram_url'                => 'https://www.instagram.com/inkfirelimited/',
		'tiktok_url'                   => 'https://www.tiktok.com/@inkfirelimited',
		'allowed_file_types'           => 'pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,zip,txt,csv',
		'max_file_size_mb'             => 10,
		'max_total_upload_mb'          => 25,
		'max_files_per_field'          => 5,
		'attach_pdf_summary'           => 1,
		'attach_json_summary'          => 1,
		'attach_zip_package'           => 1,
	);
}

function foundation_get_settings() {
	$settings = get_option( 'foundation_form_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return foundation_migrate_default_asset_settings( wp_parse_args( $settings, foundation_get_default_settings() ) );
}



function foundation_get_default_metrics() {
	return array(
		'form_views'      => 0,
		'form_starts'     => 0,
		'responses_saved' => 0,
		'saved_drafts'    => 0,
		'incomplete'      => 0,
		'failures'        => 0,
		'last_failure'    => '',
		'last_saved_draft'=> '',
	);
}

function foundation_get_metrics() {
	$metrics = get_option( 'foundation_form_metrics', array() );
	if ( ! is_array( $metrics ) ) {
		$metrics = array();
	}
	return wp_parse_args( $metrics, foundation_get_default_metrics() );
}

function foundation_register_default_metrics() {
	$current = get_option( 'foundation_form_metrics', null );
	if ( null === $current ) {
		add_option( 'foundation_form_metrics', foundation_get_default_metrics() );
		return;
	}
	if ( ! is_array( $current ) ) {
		$current = array();
	}
	$merged = wp_parse_args( $current, foundation_get_default_metrics() );
	if ( $merged !== $current ) {
		update_option( 'foundation_form_metrics', $merged );
	}
}

function foundation_increment_metric( $key, $amount = 1 ) {
	$metrics = foundation_get_metrics();
	if ( ! isset( $metrics[ $key ] ) ) {
		$metrics[ $key ] = 0;
	}
	$metrics[ $key ] = max( 0, intval( $metrics[ $key ] ) + intval( $amount ) );
	update_option( 'foundation_form_metrics', $metrics, false );
	return $metrics;
}

function foundation_set_metric_meta( $key, $value ) {
	$metrics = foundation_get_metrics();
	$metrics[ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	update_option( 'foundation_form_metrics', $metrics, false );
	return $metrics;
}

function foundation_register_default_settings() {
	$current = get_option( 'foundation_form_settings', null );
	if ( null === $current ) {
		add_option( 'foundation_form_settings', foundation_get_default_settings() );
		return;
	}

	if ( ! is_array( $current ) ) {
		$current = array();
	}

	$merged = foundation_migrate_default_asset_settings( wp_parse_args( $current, foundation_get_default_settings() ) );
	if ( $merged !== $current ) {
		update_option( 'foundation_form_settings', $merged );
	}
}

function foundation_sanitize_settings( $input ) {
	$defaults = foundation_get_default_settings();
	$input    = is_array( $input ) ? $input : array();
	$output   = array();

	foreach ( $defaults as $key => $default ) {
		$value = isset( $input[ $key ] ) ? $input[ $key ] : $default;
		if ( in_array( $key, array( 'customer_confirmation_enabled', 'attach_pdf_summary', 'attach_json_summary', 'attach_zip_package' ), true ) && ! isset( $input[ $key ] ) ) {
			$value = 0;
		}

		switch ( $key ) {
			case 'admin_email':
			case 'from_email':
				$output[ $key ] = sanitize_email( $value );
				break;
			case 'cc_emails':
				$emails = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
				$emails = array_filter( $emails, 'is_email' );
				$output[ $key ] = implode( ', ', $emails );
				break;
			case 'logo_url':
			case 'intro_image_url':
			case 'testimonial_image_url':
			case 'portfolio_url':
			case 'linkedin_url':
			case 'twitter_url':
			case 'facebook_url':
			case 'instagram_url':
			case 'tiktok_url':
				$output[ $key ] = esc_url_raw( (string) $value );
				break;
			case 'customer_confirmation_enabled':
			case 'attach_pdf_summary':
			case 'attach_json_summary':
			case 'attach_zip_package':
				$output[ $key ] = empty( $value ) ? 0 : 1;
				break;
			case 'max_file_size_mb':
			case 'max_total_upload_mb':
			case 'max_files_per_field':
				$output[ $key ] = max( 1, absint( $value ) );
				break;
			case 'allowed_file_types':
				$types = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $value ) ) ) );
				$types = array_unique( array_map( function( $type ) {
					$type = preg_replace( '/[^a-z0-9]/', '', $type );
					return ltrim( $type, '.' );
				}, $types ) );
				$output[ $key ] = implode( ',', array_filter( $types ) );
				break;
			case 'currency_symbol':
				$output[ $key ] = sanitize_text_field( (string) $value );
				if ( '' === $output[ $key ] ) {
					$output[ $key ] = '£';
				}
				break;
			default:
				$output[ $key ] = sanitize_textarea_field( (string) $value );
				break;
		}
	}

	return $output;
}

function foundation_normalize_bool( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}

	$value = strtolower( trim( (string) $value ) );
	return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

function foundation_generate_id( $prefix ) {
	return sanitize_key( $prefix . '_' . wp_generate_password( 10, false, false ) );
}

function foundation_normalize_form_data( $steps ) {
	if ( ! is_array( $steps ) ) {
		$steps = array();
	}

	$allowed_types = array(
		'service_card',
		'range_slider',
		'toggle',
		'text_input',
		'section_title',
		'description',
		'divider',
		'rich_text',
		'file_upload',
	);
	$allowed_variants = array( 'services', 'budget', 'timeline' );
	$normalized = array();

	foreach ( $steps as $step_index => $step ) {
		if ( ! is_array( $step ) ) {
			continue;
		}

		$step_id = ! empty( $step['id'] ) ? sanitize_key( $step['id'] ) : foundation_generate_id( 'step' );
		if ( '' === $step_id ) {
			$step_id = foundation_generate_id( 'step' );
		}

		$normalized_step = array(
			'id'             => $step_id,
			'title'          => sanitize_text_field( $step['title'] ?? sprintf( 'Screen %d', $step_index + 1 ) ),
			'subtitle'       => sanitize_textarea_field( $step['subtitle'] ?? 'Fill in the details below.' ),
			'is_conditional' => foundation_normalize_bool( $step['is_conditional'] ?? false ),
			'fields'         => array(),
		);

		$fields = isset( $step['fields'] ) && is_array( $step['fields'] ) ? $step['fields'] : array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['type'] ) || ! in_array( $field['type'], $allowed_types, true ) ) {
				continue;
			}

			$type    = $field['type'];
			$field_id = ! empty( $field['id'] ) ? sanitize_key( $field['id'] ) : foundation_generate_id( 'field' );
			if ( '' === $field_id ) {
				$field_id = foundation_generate_id( 'field' );
			}

			$normalized_field = array(
				'id'          => $field_id,
				'type'        => $type,
				'label'       => sanitize_text_field( $field['label'] ?? '' ),
				'helper'      => sanitize_textarea_field( $field['helper'] ?? '' ),
				'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
				'text'        => sanitize_textarea_field( $field['text'] ?? '' ),
				'required'    => foundation_normalize_bool( $field['required'] ?? false ),
			);

			switch ( $type ) {
				case 'service_card':
					$variant = sanitize_key( $field['variant'] ?? 'services' );
					if ( ! in_array( $variant, $allowed_variants, true ) ) {
						$variant = 'services';
					}
					$role       = sanitize_key( $field['role'] ?? '' );
					$field_hint = strtolower( $field_id . ' ' . wp_strip_all_tags( (string) ( $field['label'] ?? '' ) ) );

					// Older builder exports sometimes stored core budget/timeline pickers as generic services.
					if ( '' === $role ) {
						if ( false !== strpos( $field_hint, 'budget' ) ) {
							$role    = 'budget';
							$variant = 'budget';
						} elseif ( false !== strpos( $field_hint, 'timeline' ) || false !== strpos( $field_hint, 'when would you like to start' ) ) {
							$role    = 'timeline';
							$variant = 'timeline';
						} elseif ( false !== strpos( $field_id, 'services_main' ) || false !== strpos( $field_hint, 'which services' ) || false !== strpos( $field_hint, 'services do you need' ) ) {
							$role    = 'services_main';
							$variant = 'services';
						}
					}
					if ( 'budget' === $role ) {
						$variant = 'budget';
					} elseif ( 'timeline' === $role ) {
						$variant = 'timeline';
					}

					$normalized_field['variant'] = $variant;
					$normalized_field['role']    = $role;
					$normalized_field['options'] = array();
					$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
					foreach ( $options as $option ) {
						if ( ! is_array( $option ) ) {
							continue;
						}
						$normalized_field['options'][] = array(
							'label'         => sanitize_text_field( $option['label'] ?? 'Option' ),
							'price'         => round( floatval( $option['price'] ?? 0 ), 2 ),
							'route_step_id' => sanitize_key( $option['route_step_id'] ?? '' ),
						);
					}
					break;
				case 'range_slider':
					$normalized_field['min']            = intval( $field['min'] ?? 1 );
					$normalized_field['max']            = max( $normalized_field['min'], intval( $field['max'] ?? 50 ) );
					$normalized_field['step']           = max( 1, intval( $field['step'] ?? 1 ) );
					$normalized_field['unit']           = sanitize_text_field( $field['unit'] ?? 'units' );
					$normalized_field['price_per_unit'] = round( floatval( $field['price_per_unit'] ?? 0 ), 2 );
					break;
				case 'toggle':
					$normalized_field['yes_label'] = sanitize_text_field( $field['yes_label'] ?? 'Yes' );
					$normalized_field['no_label']  = sanitize_text_field( $field['no_label'] ?? 'No' );
					$normalized_field['price']     = round( floatval( $field['price'] ?? 0 ), 2 );
					break;
				case 'file_upload':
					$normalized_field['accept']          = sanitize_text_field( $field['accept'] ?? '' );
					$normalized_field['max_files']       = max( 1, intval( $field['max_files'] ?? 5 ) );
					$normalized_field['max_file_size_mb']= max( 1, intval( $field['max_file_size_mb'] ?? 10 ) );
					break;
			}

			$normalized_step['fields'][] = $normalized_field;
		}

		$normalized[] = $normalized_step;
	}

	if ( empty( $normalized ) ) {
		$normalized[] = array(
			'id'             => 'step_1',
			'title'          => 'Project Scope',
			'subtitle'       => 'Fill in the details below.',
			'is_conditional' => false,
			'fields'         => array(),
		);
	}

	return array_values( $normalized );
}

function foundation_get_core_selection_field_ids( $steps ) {
	$ids = array(
		'budget'        => '',
		'timeline'      => '',
		'services_main' => '',
	);

	foreach ( foundation_normalize_form_data( $steps ) as $step ) {
		foreach ( $step['fields'] as $field ) {
			if ( 'service_card' !== $field['type'] ) {
				continue;
			}

			$role    = $field['role'] ?? '';
			$variant = $field['variant'] ?? '';
			$field_id = $field['id'];

			if ( 'budget' === $role && empty( $ids['budget'] ) ) {
				$ids['budget'] = $field_id;
			}
			if ( 'timeline' === $role && empty( $ids['timeline'] ) ) {
				$ids['timeline'] = $field_id;
			}
			if ( 'services_main' === $role && empty( $ids['services_main'] ) ) {
				$ids['services_main'] = $field_id;
			}

			if ( empty( $ids['budget'] ) && 'budget' === $variant ) {
				$ids['budget'] = $field_id;
			}
			if ( empty( $ids['timeline'] ) && 'timeline' === $variant ) {
				$ids['timeline'] = $field_id;
			}
			if ( empty( $ids['services_main'] ) && 'services' === $variant ) {
				$ids['services_main'] = $field_id;
			}
		}
	}

	return $ids;
}

function foundation_parse_allowed_extensions( $settings ) {
	$raw = $settings['allowed_file_types'] ?? '';
	$extensions = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $raw ) ) ) );
	$extensions = array_map( function( $extension ) {
		$extension = preg_replace( '/[^a-z0-9]/', '', $extension );
		return ltrim( $extension, '.' );
	}, $extensions );
	return array_values( array_unique( array_filter( $extensions ) ) );
}

function foundation_validate_required_submission_fields( $steps, $selections, $uploaded_files ) {
	$missing = array();

	foreach ( foundation_normalize_form_data( $steps ) as $step ) {
		foreach ( $step['fields'] as $field ) {
			if ( empty( $field['required'] ) ) {
				continue;
			}

			$field_id = $field['id'] ?? '';
			$type     = $field['type'] ?? '';
			$label    = sanitize_text_field( $field['label'] ?? 'this field' );
			$has_value = false;

			switch ( $type ) {
				case 'service_card':
				case 'toggle':
					$selected = $selections[ $field_id . '_options' ] ?? array();
					$has_value = is_array( $selected ) && ! empty( $selected );
					break;
				case 'range_slider':
					$value = $selections[ $field_id . '_val' ] ?? '';
					$has_value = '' !== (string) $value;
					break;
				case 'file_upload':
					$files = $uploaded_files[ $field_id ] ?? array();
					$has_value = is_array( $files ) && ! empty( $files );
					break;
				case 'text_input':
				case 'rich_text':
				default:
					$value = $selections[ $field_id ] ?? '';
					$has_value = '' !== trim( (string) $value );
					break;
			}

			if ( ! $has_value ) {
				$missing[] = $label;
			}
		}
	}

	return array_values( array_unique( array_filter( $missing ) ) );
}

function foundation_collect_uploaded_files( $settings, $steps ) {
	if ( empty( $_FILES['uploads'] ) || ! is_array( $_FILES['uploads'] ) ) {
		return array(
			'files'          => array(),
			'total_size'     => 0,
			'total_count'    => 0,
			'errors'         => array(),
		);
	}

	$field_lookup = array();
	foreach ( foundation_normalize_form_data( $steps ) as $step ) {
		foreach ( $step['fields'] as $field ) {
			$field_lookup[ $field['id'] ] = $field;
		}
	}

	$uploads              = $_FILES['uploads'];
	$allowed_extensions   = foundation_parse_allowed_extensions( $settings );
	$global_max_file_size = max( 1, absint( $settings['max_file_size_mb'] ?? 10 ) ) * 1024 * 1024;
	$global_max_total     = max( 1, absint( $settings['max_total_upload_mb'] ?? 25 ) ) * 1024 * 1024;
	$global_max_per_field = max( 1, absint( $settings['max_files_per_field'] ?? 5 ) );

	$names      = $uploads['name'] ?? array();
	$tmp_names  = $uploads['tmp_name'] ?? array();
	$types      = $uploads['type'] ?? array();
	$sizes      = $uploads['size'] ?? array();
	$errors     = $uploads['error'] ?? array();

	$collected  = array();
	$total_size = 0;
	$total_count = 0;
	$messages   = array();

	foreach ( $names as $field_id => $file_names ) {
		if ( ! is_array( $file_names ) ) {
			continue;
		}

		$field = $field_lookup[ $field_id ] ?? array();
		if ( empty( $field ) || 'file_upload' !== ( $field['type'] ?? '' ) ) {
			$messages[] = 'One or more upload fields are not valid for this form.';
			continue;
		}
		$per_field_count = 0;
		$per_field_max = max( 1, intval( $field['max_files'] ?? $global_max_per_field ) );
		$per_field_max_file_size = max( 1, intval( $field['max_file_size_mb'] ?? ( $global_max_file_size / ( 1024 * 1024 ) ) ) ) * 1024 * 1024;

		foreach ( $file_names as $index => $name ) {
			$error    = $errors[ $field_id ][ $index ] ?? UPLOAD_ERR_NO_FILE;
			$tmp_name = $tmp_names[ $field_id ][ $index ] ?? '';
			$size     = (int) ( $sizes[ $field_id ][ $index ] ?? 0 );
			$type     = sanitize_mime_type( (string) ( $types[ $field_id ][ $index ] ?? '' ) );

			if ( UPLOAD_ERR_NO_FILE === $error ) {
				continue;
			}
			if ( UPLOAD_ERR_OK !== $error || empty( $tmp_name ) || ! is_uploaded_file( $tmp_name ) ) {
				$messages[] = sprintf( 'One of the files for %s could not be uploaded.', $field['label'] ?? 'this field' );
				continue;
			}
			if ( $per_field_count >= $per_field_max ) {
				$messages[] = sprintf( 'You can upload up to %d files for %s.', $per_field_max, $field['label'] ?? 'this field' );
				continue;
			}
			if ( $size > $per_field_max_file_size ) {
				$messages[] = sprintf( '%s is larger than the %dMB limit.', sanitize_file_name( (string) $name ), intval( $per_field_max_file_size / 1024 / 1024 ) );
				continue;
			}
			if ( ( $total_size + $size ) > $global_max_total ) {
				$messages[] = sprintf( 'Uploads exceed the total %dMB limit for this submission.', intval( $global_max_total / 1024 / 1024 ) );
				continue;
			}

			$filename = sanitize_file_name( (string) $name );
			$check    = wp_check_filetype_and_ext( $tmp_name, $filename );
			$ext      = strtolower( $check['ext'] ?? pathinfo( $filename, PATHINFO_EXTENSION ) );
			if ( empty( $ext ) || ! in_array( $ext, $allowed_extensions, true ) ) {
				$messages[] = sprintf( '%s is not an allowed file type.', $filename );
				continue;
			}

			$collected[ $field_id ][] = array(
				'name'     => $filename,
				'tmp_name' => $tmp_name,
				'type'     => $type,
				'size'     => $size,
				'ext'      => $ext,
			);
			$per_field_count++;
			$total_count++;
			$total_size += $size;
		}
	}

	return array(
		'files'       => $collected,
		'total_size'  => $total_size,
		'total_count' => $total_count,
		'errors'      => array_values( array_unique( $messages ) ),
	);
}

function foundation_flatten_summary_for_export( $contact, $summary, $total, $settings ) {
	$currency = $settings['currency_symbol'] ?? '£';
	$lines = array(
		'Project Brief Summary',
		'',
		'Contact details',
		'Name: ' . ( $contact['name'] ?? '' ),
		'Company: ' . ( $contact['company'] ?? '' ),
		'Email: ' . ( $contact['email'] ?? '' ),
		'Phone: ' . ( $contact['phone'] ?? '' ),
		'Website: ' . ( $contact['website'] ?? '' ),
		'',
		'Brief summary',
	);

	foreach ( $summary as $row ) {
		$label = wp_strip_all_tags( $row['label'] ?? '' );
		$value = wp_strip_all_tags( $row['value_text'] ?? ( $row['value'] ?? '' ) );
		$price = isset( $row['price'] ) && floatval( $row['price'] ) > 0 ? ' [' . $currency . number_format_i18n( floatval( $row['price'] ), 0 ) . ']' : '';
		$lines[] = sprintf( '- %s: %s%s', $label, $value, $price );
	}

	$lines[] = '';
	$lines[] = 'Estimated total: ' . $currency . number_format_i18n( floatval( $total ), 0 );
	$lines[] = 'Generated: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC';

	return $lines;
}

function foundation_pdf_escape_text( $text ) {
	$text = remove_accents( (string) $text );
	$text = preg_replace( '/[^\x20-\x7E]/', '?', $text );
	$text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
	return $text;
}

function foundation_generate_pdf_attachment( $contact, $summary, $total, $settings ) {
	$lines = foundation_flatten_summary_for_export( $contact, $summary, $total, $settings );
	$chunks = array_chunk( $lines, 42 );
	$page_count = count( $chunks );
	$objects = array();
	$object_index = 1;

	$catalog_obj = $object_index++;
	$pages_obj   = $object_index++;
	$font_obj    = $object_index++;
	$page_objects = array();
	$content_objects = array();

	foreach ( $chunks as $chunk ) {
		$page_objects[]    = $object_index++;
		$content_objects[] = $object_index++;
	}

	$objects[ $catalog_obj ] = '<< /Type /Catalog /Pages ' . $pages_obj . ' 0 R >>';
	$kids = array_map( function( $obj_num ) {
		return $obj_num . ' 0 R';
	}, $page_objects );
	$objects[ $pages_obj ] = '<< /Type /Pages /Kids [ ' . implode( ' ', $kids ) . ' ] /Count ' . $page_count . ' >>';
	$objects[ $font_obj ]  = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

	foreach ( $chunks as $index => $chunk ) {
		$stream = array();
		$stream[] = 'BT';
		$stream[] = '/F1 16 Tf';
		$stream[] = '50 790 Td';
		$stream[] = '18 TL';
		$title = foundation_pdf_escape_text( 'Project Brief - ' . ( $contact['company'] ?? $contact['name'] ?? 'Submission' ) );
		$stream[] = '(' . $title . ') Tj';
		$stream[] = 'T*';
		$stream[] = '/F1 11 Tf';
		$stream[] = '14 TL';
		$stream[] = '(Page ' . ( $index + 1 ) . ' of ' . $page_count . ') Tj';
		$stream[] = 'T*';
		$stream[] = 'T*';
		foreach ( $chunk as $line ) {
			$stream[] = '(' . foundation_pdf_escape_text( $line ) . ') Tj';
			$stream[] = 'T*';
		}
		$stream[] = 'ET';
		$stream_text = implode( "\n", $stream );

		$content_obj = $content_objects[ $index ];
		$page_obj    = $page_objects[ $index ];

		$objects[ $content_obj ] = '<< /Length ' . strlen( $stream_text ) . ' >>' . "\nstream\n" . $stream_text . "\nendstream";
		$objects[ $page_obj ]    = '<< /Type /Page /Parent ' . $pages_obj . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $font_obj . ' 0 R >> >> /Contents ' . $content_obj . ' 0 R >>';
	}

	$pdf = "%PDF-1.4\n";
	$offsets = array( 0 );
	for ( $i = 1; $i < $object_index; $i++ ) {
		$offsets[ $i ] = strlen( $pdf );
		$pdf .= $i . " 0 obj\n" . $objects[ $i ] . "\nendobj\n";
	}
	$xref_offset = strlen( $pdf );
	$pdf .= 'xref' . "\n";
	$pdf .= '0 ' . $object_index . "\n";
	$pdf .= "0000000000 65535 f \n";
	for ( $i = 1; $i < $object_index; $i++ ) {
		$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
	}
	$pdf .= 'trailer << /Size ' . $object_index . ' /Root ' . $catalog_obj . ' 0 R >>' . "\n";
	$pdf .= 'startxref' . "\n" . $xref_offset . "\n%%EOF";

	$temp_file = wp_tempnam( 'foundation-brief.pdf' );
	if ( ! $temp_file ) {
		return '';
	}
	if ( false === file_put_contents( $temp_file, $pdf ) ) {
		@unlink( $temp_file );
		return '';
	}
	return $temp_file;
}

function foundation_generate_json_attachment( $contact, $summary, $total, $settings, $attachments = array() ) {
	$data = array(
		'generated_at' => gmdate( 'c' ),
		'contact'      => $contact,
		'summary'      => $summary,
		'estimated_total' => round( floatval( $total ), 2 ),
		'currency'     => $settings['currency_symbol'] ?? '£',
		'attachments'  => array_values( array_map( function( $item ) {
			return array(
				'name' => $item['name'] ?? '',
				'size' => $item['size'] ?? 0,
				'type' => $item['type'] ?? '',
			);
		}, $attachments ) ),
	);

	$temp_file = wp_tempnam( 'foundation-brief.json' );
	if ( ! $temp_file ) {
		return '';
	}
	$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	if ( empty( $json ) || false === file_put_contents( $temp_file, $json ) ) {
		@unlink( $temp_file );
		return '';
	}
	return $temp_file;
}

function foundation_create_submission_package( $contact, $summary, $total, $settings, $uploaded_files, $pdf_path = '', $json_path = '' ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return '';
	}

	$zip_path = wp_tempnam( 'foundation-brief-package.zip' );
	if ( ! $zip_path ) {
		return '';
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path, ZipArchive::OVERWRITE ) ) {
		@unlink( $zip_path );
		return '';
	}

	if ( $pdf_path && file_exists( $pdf_path ) ) {
		$zip->addFile( $pdf_path, 'project-brief-summary.pdf' );
	}
	if ( $json_path && file_exists( $json_path ) ) {
		$zip->addFile( $json_path, 'project-brief-summary.json' );
	}
	$zip->addFromString( 'README.txt', implode( "\n", foundation_flatten_summary_for_export( $contact, $summary, $total, $settings ) ) );

	foreach ( $uploaded_files as $field_id => $files ) {
		$field_folder = sanitize_key( $field_id );
		foreach ( $files as $file ) {
			if ( empty( $file['tmp_name'] ) || ! file_exists( $file['tmp_name'] ) ) {
				continue;
			}
			$zip->addFile( $file['tmp_name'], 'uploads/' . $field_folder . '/' . sanitize_file_name( $file['name'] ?? 'file' ) );
		}
	}

	$zip->close();
	return $zip_path;
}

function foundation_cleanup_temp_files( $files ) {
	foreach ( $files as $file ) {
		if ( ! empty( $file ) && is_string( $file ) && file_exists( $file ) ) {
			@unlink( $file );
		}
	}
}
