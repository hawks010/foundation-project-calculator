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

/**
 * Public and operational settings. Values are deliberately conservative.
 * Pricing is stored separately in foundation_pricing_catalog.
 */
function foundation_get_default_settings() {
	return array(
		'admin_email'                   => foundation_get_default_notification_email(),
		'cc_emails'                     => '',
		'from_name'                     => wp_strip_all_tags( get_bloginfo( 'name' ) ),
		'from_email'                    => foundation_get_default_sender_email(),
		'customer_confirmation_enabled' => 1,
		'admin_subject_prefix'          => 'New project calculator enquiry',
		'customer_subject'              => 'Your Inkfire planning estimate',
		'customer_intro'                => 'Thanks for using the Inkfire project calculator. We have received your answers safely and will review anything that needs a tailored quote.',
		'success_message'               => 'Your estimate and project details have been sent safely. Our team will review any tailored-quote items and follow up.',
		'quote_mode_enabled'            => 0,
		'launch_button_label'           => 'Build your estimate',
		'wizard_title'                  => 'Inkfire Project Calculator',
		'currency_symbol'               => '£',
		'vat_note'                      => 'All prices are shown excluding VAT. VAT will be added where applicable.',
		'estimate_disclaimer'           => 'This is a planning estimate, not a binding quotation. Final pricing may change after Inkfire confirms the scope.',
		'show_live_summary'             => 1,
		'phone_required'                => 0,
		'privacy_consent_label'         => 'I agree that Inkfire may use these details to respond to my enquiry.',
		'privacy_policy_url'            => home_url( '/privacy-policy/' ),
		'logo_url'                      => foundation_get_default_logo_url(),
		'intro_image_url'               => 'https://inkfire.co.uk/wp-content/uploads/2025/12/250515_SCOPE-AWARDS_02_0489.jpg',
		'intro_heading'                 => 'A clearer route to the right support',
		'intro_text'                    => 'Choose the areas you need, answer only the relevant questions, and receive a clear split between one-off work, monthly support and anything that needs a tailored quote.',
		'testimonial_image_url'         => 'https://inkfire.co.uk/wp-content/uploads/2025/12/Screenshot-2025-12-01-at-22.00.39.png',
		'testimonial_heading'           => 'Built around real people',
		'testimonial_quote'             => 'Inkfire combines web, technology, creative support and accessibility in one joined-up team.',
		'testimonial_attribution'       => 'Inkfire',
		'portfolio_url'                 => 'https://inkfire.co.uk/portfolio',
		'linkedin_url'                  => 'https://uk.linkedin.com/company/inkfire',
		'twitter_url'                   => 'https://twitter.com/Inkfirelimited',
		'facebook_url'                  => 'https://facebook.com/inkfirelimited',
		'instagram_url'                 => 'https://www.instagram.com/inkfirelimited/',
		'tiktok_url'                    => 'https://www.tiktok.com/@inkfirelimited',
		'allowed_file_types'            => 'pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,zip,txt,csv',
		'max_file_size_mb'              => 10,
		'max_total_upload_mb'           => 25,
		'max_files_per_field'           => 5,
		'attach_pdf_summary'            => 1,
		'attach_json_summary'           => 1,
		'attach_zip_package'            => 1,
		'draft_retention_days'          => 14,
		'submission_cooldown_seconds'   => 30,
		'lead_retention_days'           => 180,
	);
}

function foundation_get_settings() {
	$settings = get_option( 'foundation_form_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	return foundation_migrate_default_asset_settings( wp_parse_args( $settings, foundation_get_default_settings() ) );
}

function foundation_is_quote_mode_enabled( $settings = null ) {
	if ( null === $settings ) {
		$settings = foundation_get_settings();
	}
	return ! empty( $settings['quote_mode_enabled'] );
}

function foundation_get_default_metrics() {
	return array(
		'form_views'       => 0,
		'form_starts'      => 0,
		'responses_saved'  => 0,
		'saved_drafts'     => 0,
		'incomplete'       => 0,
		'failures'         => 0,
		'last_failure'     => '',
		'last_saved_draft' => '',
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
		add_option( 'foundation_form_metrics', foundation_get_default_metrics(), '', false );
		return;
	}
	$current = is_array( $current ) ? $current : array();
	$merged  = wp_parse_args( $current, foundation_get_default_metrics() );
	if ( $merged !== $current ) {
		update_option( 'foundation_form_metrics', $merged, false );
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
	$metrics         = foundation_get_metrics();
	$metrics[ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	update_option( 'foundation_form_metrics', $metrics, false );
	return $metrics;
}

function foundation_register_default_settings() {
	$current = get_option( 'foundation_form_settings', null );
	if ( null === $current ) {
		add_option( 'foundation_form_settings', foundation_get_default_settings(), '', false );
		return;
	}
	$current = is_array( $current ) ? $current : array();
	$merged  = foundation_migrate_default_asset_settings( wp_parse_args( $current, foundation_get_default_settings() ) );
	if ( $merged !== $current ) {
		update_option( 'foundation_form_settings', $merged, false );
	}
}

function foundation_sanitize_settings( $input ) {
	$defaults = foundation_get_default_settings();
	$input    = is_array( $input ) ? $input : array();
	$output   = array();
	$booleans = array(
		'customer_confirmation_enabled',
		'quote_mode_enabled',
		'show_live_summary',
		'phone_required',
		'attach_pdf_summary',
		'attach_json_summary',
		'attach_zip_package',
	);
	$urls = array(
		'logo_url', 'intro_image_url', 'testimonial_image_url', 'portfolio_url',
		'linkedin_url', 'twitter_url', 'facebook_url', 'instagram_url', 'tiktok_url',
		'privacy_policy_url',
	);
	$integers = array(
		'max_file_size_mb'            => array( 1, 100 ),
		'max_total_upload_mb'         => array( 1, 250 ),
		'max_files_per_field'         => array( 1, 25 ),
		'draft_retention_days'        => array( 1, 90 ),
		'submission_cooldown_seconds' => array( 5, 600 ),
		'lead_retention_days'         => array( 30, 3650 ),
	);

	foreach ( $defaults as $key => $default ) {
		$value = array_key_exists( $key, $input ) ? $input[ $key ] : $default;
		if ( in_array( $key, $booleans, true ) ) {
			$output[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
			continue;
		}
		if ( isset( $integers[ $key ] ) ) {
			$bounds         = $integers[ $key ];
			$output[ $key ] = max( $bounds[0], min( $bounds[1], absint( $value ) ) );
			continue;
		}
		if ( in_array( $key, $urls, true ) ) {
			$output[ $key ] = esc_url_raw( (string) $value );
			continue;
		}

		switch ( $key ) {
			case 'admin_email':
			case 'from_email':
				$output[ $key ] = sanitize_email( $value );
				break;
			case 'cc_emails':
				$emails = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
				$emails = array_filter( $emails, 'is_email' );
				$output[ $key ] = implode( ', ', array_unique( $emails ) );
				break;
			case 'allowed_file_types':
				$types   = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $value ) ) ) );
				$blocked = function_exists( 'foundation_get_blocked_upload_extensions' ) ? foundation_get_blocked_upload_extensions() : array( 'php', 'phtml', 'phar', 'exe', 'js', 'html', 'svg' );
				$types   = array_unique(
					array_filter(
						array_map(
							static function ( $type ) {
								$type = preg_replace( '/[^a-z0-9]/', '', $type );
								return ltrim( $type, '.' );
							},
							$types
						),
						static function ( $type ) use ( $blocked ) {
							return '' !== $type && ! in_array( $type, $blocked, true );
						}
					)
				);
				$output[ $key ] = implode( ',', $types );
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

/**
 * Sanitise an array of route IDs while preserving order.
 */
function foundation_normalize_route_ids( $value ) {
	$value  = is_array( $value ) ? $value : ( empty( $value ) ? array() : array( $value ) );
	$output = array();
	foreach ( $value as $route_id ) {
		$route_id = sanitize_key( $route_id );
		if ( $route_id && ! in_array( $route_id, $output, true ) ) {
			$output[] = $route_id;
		}
	}
	return $output;
}

/**
 * Normalise builder data. The function accepts legacy 1.3.x fields and the
 * compiled 1.4 pricing blueprint. Unknown keys are intentionally discarded.
 */
function foundation_normalize_form_data( $steps ) {
	$steps = is_array( $steps ) ? $steps : array();
	$allowed_types = array(
		'service_card', 'range_slider', 'number_input', 'toggle', 'text_input',
		'section_title', 'description', 'divider', 'rich_text', 'file_upload', 'calculation',
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
			'title'          => sanitize_text_field( isset( $step['title'] ) ? $step['title'] : sprintf( 'Screen %d', $step_index + 1 ) ),
			'subtitle'       => sanitize_textarea_field( isset( $step['subtitle'] ) ? $step['subtitle'] : 'Fill in the details below.' ),
			'is_conditional' => foundation_normalize_bool( isset( $step['is_conditional'] ) ? $step['is_conditional'] : false ),
			'fields'         => array(),
		);

		$fields = isset( $step['fields'] ) && is_array( $step['fields'] ) ? $step['fields'] : array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['type'] ) || ! in_array( $field['type'], $allowed_types, true ) ) {
				continue;
			}
			$type     = $field['type'];
			$field_id = ! empty( $field['id'] ) ? sanitize_key( $field['id'] ) : foundation_generate_id( 'field' );
			if ( '' === $field_id ) {
				$field_id = foundation_generate_id( 'field' );
			}
			$normalized_field = array(
				'id'          => $field_id,
				'type'        => $type,
				'label'       => sanitize_text_field( isset( $field['label'] ) ? $field['label'] : '' ),
				'helper'      => sanitize_textarea_field( isset( $field['helper'] ) ? $field['helper'] : '' ),
				'placeholder' => sanitize_text_field( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ),
				'text'        => sanitize_textarea_field( isset( $field['text'] ) ? $field['text'] : '' ),
				'required'    => foundation_normalize_bool( isset( $field['required'] ) ? $field['required'] : false ),
			);

			// Shared pricing/routing metadata used by the authoritative quote engine.
			$text_keys = array(
				'pricing_type', 'billing', 'line_item_label', 'manual_note', 'price_key',
				'base_price_key', 'price_per_unit_key', 'unit_price_key', 'billing_from_field',
				'billing_override', 'unit_source_field_id', 'quantity_source_field_id',
			);
			foreach ( $text_keys as $key ) {
				if ( isset( $field[ $key ] ) && '' !== (string) $field[ $key ] ) {
					$normalized_field[ $key ] = in_array( $key, array( 'line_item_label', 'manual_note' ), true )
						? sanitize_text_field( $field[ $key ] )
						: sanitize_key( $field[ $key ] );
				}
			}
			foreach ( array( 'price', 'base_price', 'price_per_unit', 'quantity_min', 'quantity_max' ) as $key ) {
				if ( isset( $field[ $key ] ) && is_numeric( $field[ $key ] ) ) {
					$normalized_field[ $key ] = round( max( 0, (float) $field[ $key ] ), 2 );
				}
			}
			if ( isset( $field['pricing_component'] ) ) {
				$normalized_field['pricing_component'] = foundation_normalize_bool( $field['pricing_component'] );
			}
			if ( ! empty( $field['price_per_unit_keys'] ) && is_array( $field['price_per_unit_keys'] ) ) {
				$normalized_field['price_per_unit_keys'] = array_values( array_unique( array_filter( array_map( 'sanitize_key', $field['price_per_unit_keys'] ) ) ) );
			}
			if ( isset( $field['yes_route_step_ids'] ) ) {
				$normalized_field['yes_route_step_ids'] = foundation_normalize_route_ids( $field['yes_route_step_ids'] );
			}

			switch ( $type ) {
				case 'service_card':
					$variant = sanitize_key( isset( $field['variant'] ) ? $field['variant'] : 'services' );
					if ( ! in_array( $variant, $allowed_variants, true ) ) {
						$variant = 'services';
					}
					$role = sanitize_key( isset( $field['role'] ) ? $field['role'] : '' );
					$field_hint = strtolower( $field_id . ' ' . wp_strip_all_tags( (string) ( isset( $field['label'] ) ? $field['label'] : '' ) ) );
					if ( '' === $role ) {
						if ( false !== strpos( $field_hint, 'budget' ) ) {
							$role = 'budget';
							$variant = 'budget';
						} elseif ( false !== strpos( $field_hint, 'timeline' ) || false !== strpos( $field_hint, 'when would you like to start' ) ) {
							$role = 'timeline';
							$variant = 'timeline';
						} elseif ( false !== strpos( $field_id, 'services_main' ) || false !== strpos( $field_hint, 'which services' ) || false !== strpos( $field_hint, 'services do you need' ) ) {
							$role = 'services_main';
							$variant = 'services';
						}
					}
					if ( 'budget' === $role ) {
						$variant = 'budget';
					} elseif ( 'timeline' === $role ) {
						$variant = 'timeline';
					}
					$selection_mode = sanitize_key( isset( $field['selection_mode'] ) ? $field['selection_mode'] : '' );
					if ( ! in_array( $selection_mode, array( 'single', 'multi' ), true ) ) {
						$selection_mode = ( 'services' === $variant || 'services_main' === $role ) ? 'multi' : 'single';
					}
					$normalized_field['variant']        = $variant;
					$normalized_field['role']           = $role;
					$normalized_field['selection_mode'] = $selection_mode;
					$normalized_field['options']        = array();
					$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
					foreach ( $options as $option_index => $option ) {
						if ( ! is_array( $option ) ) {
							continue;
						}
						$value = isset( $option['value'] ) ? sanitize_key( $option['value'] ) : sanitize_key( 'option_' . $option_index );
						$normalized_option = array(
							'label'         => sanitize_text_field( isset( $option['label'] ) ? $option['label'] : 'Option' ),
							'value'         => $value,
							'price'         => round( max( 0, (float) ( isset( $option['price'] ) ? $option['price'] : 0 ) ), 2 ),
							'route_step_id' => sanitize_key( isset( $option['route_step_id'] ) ? $option['route_step_id'] : '' ),
							'route_step_ids'=> foundation_normalize_route_ids( isset( $option['route_step_ids'] ) ? $option['route_step_ids'] : array() ),
						);
						foreach ( array( 'pricing_type', 'billing', 'line_item_label', 'manual_note', 'price_key', 'unit_price_key', 'billing_override' ) as $key ) {
							if ( isset( $option[ $key ] ) && '' !== (string) $option[ $key ] ) {
								$normalized_option[ $key ] = in_array( $key, array( 'line_item_label', 'manual_note' ), true )
									? sanitize_text_field( $option[ $key ] )
									: sanitize_key( $option[ $key ] );
							}
						}
						foreach ( array( 'price_per_unit', 'quantity_min', 'quantity_max' ) as $key ) {
							if ( isset( $option[ $key ] ) && is_numeric( $option[ $key ] ) ) {
								$normalized_option[ $key ] = round( max( 0, (float) $option[ $key ] ), 2 );
							}
						}
						if ( isset( $option['pricing_component'] ) ) {
							$normalized_option['pricing_component'] = foundation_normalize_bool( $option['pricing_component'] );
						}
						$normalized_field['options'][] = $normalized_option;
					}
					break;

				case 'range_slider':
				case 'number_input':
					$normalized_field['min']  = isset( $field['min'] ) ? (float) $field['min'] : ( 'number_input' === $type ? 0 : 1 );
					$normalized_field['max']  = max( $normalized_field['min'], isset( $field['max'] ) ? (float) $field['max'] : 50 );
					$normalized_field['step'] = max( 0.01, isset( $field['step'] ) ? (float) $field['step'] : 1 );
					$normalized_field['unit'] = sanitize_text_field( isset( $field['unit'] ) ? $field['unit'] : 'units' );
					break;

				case 'toggle':
					$normalized_field['yes_label'] = sanitize_text_field( isset( $field['yes_label'] ) ? $field['yes_label'] : 'Yes' );
					$normalized_field['no_label']  = sanitize_text_field( isset( $field['no_label'] ) ? $field['no_label'] : 'No' );
					$normalized_field['price']     = round( max( 0, (float) ( isset( $field['price'] ) ? $field['price'] : 0 ) ), 2 );
					break;

				case 'file_upload':
					$normalized_field['accept']           = sanitize_text_field( isset( $field['accept'] ) ? $field['accept'] : '' );
					$normalized_field['max_files']        = max( 1, intval( isset( $field['max_files'] ) ? $field['max_files'] : 5 ) );
					$normalized_field['max_file_size_mb'] = max( 1, intval( isset( $field['max_file_size_mb'] ) ? $field['max_file_size_mb'] : 10 ) );
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
	$ids = array( 'budget' => '', 'timeline' => '', 'services_main' => '' );
	foreach ( foundation_normalize_form_data( $steps ) as $step ) {
		foreach ( $step['fields'] as $field ) {
			if ( 'service_card' !== $field['type'] ) {
				continue;
			}
			$role = isset( $field['role'] ) ? $field['role'] : '';
			$variant = isset( $field['variant'] ) ? $field['variant'] : '';
			$field_id = $field['id'];
			foreach ( array( 'budget', 'timeline', 'services_main' ) as $key ) {
				if ( empty( $ids[ $key ] ) && ( $key === $role || ( 'services_main' === $key ? 'services' : $key ) === $variant ) ) {
					$ids[ $key ] = $field_id;
				}
			}
		}
	}
	return $ids;
}

/**
 * Executable and browser-active formats are never accepted, even if an
 * administrator accidentally adds them to the comma-separated allow-list.
 */
function foundation_get_blocked_upload_extensions() {
	return array(
		'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
		'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'zsh',
		'exe', 'dll', 'com', 'bat', 'cmd', 'scr', 'msi', 'jar',
		'js', 'mjs', 'cjs', 'html', 'htm', 'shtml', 'xhtml',
		'svg', 'svgz', 'htaccess', 'config',
	);
}

function foundation_parse_allowed_extensions( $settings ) {
	$raw = $settings['allowed_file_types'] ?? '';
	$extensions = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $raw ) ) ) );
	$extensions = array_map( function( $extension ) {
		$extension = preg_replace( '/[^a-z0-9]/', '', $extension );
		return ltrim( $extension, '.' );
	}, $extensions );
	$blocked = foundation_get_blocked_upload_extensions();
	return array_values(
		array_unique(
			array_filter(
				$extensions,
				static function ( $extension ) use ( $blocked ) {
					return '' !== $extension && ! in_array( $extension, $blocked, true );
				}
			)
		)
	);
}

function foundation_validate_required_submission_fields( $steps, $selections, $uploaded_files ) {
	$missing    = array();
	$normalized = foundation_normalize_form_data( $steps );
	$visible    = function_exists( 'foundation_get_visible_form_steps' )
		? foundation_get_visible_form_steps( $normalized, is_array( $selections ) ? $selections : array() )
		: $normalized;

	foreach ( $visible as $step ) {
		foreach ( $step['fields'] as $field ) {
			if ( empty( $field['required'] ) ) {
				continue;
			}
			$field_id  = isset( $field['id'] ) ? $field['id'] : '';
			$type      = isset( $field['type'] ) ? $field['type'] : '';
			$label     = sanitize_text_field( isset( $field['label'] ) ? $field['label'] : 'this field' );
			$has_value = false;

			switch ( $type ) {
				case 'service_card':
				case 'toggle':
					$selected  = isset( $selections[ $field_id . '_options' ] ) ? $selections[ $field_id . '_options' ] : array();
					$has_value = is_array( $selected ) && ! empty( $selected );
					break;
				case 'range_slider':
				case 'number_input':
					$value     = isset( $selections[ $field_id . '_val' ] ) ? $selections[ $field_id . '_val' ] : ( isset( $selections[ $field_id ] ) ? $selections[ $field_id ] : '' );
					$has_value = '' !== (string) $value && is_numeric( $value );
					break;
				case 'file_upload':
					$files     = isset( $uploaded_files[ $field_id ] ) ? $uploaded_files[ $field_id ] : array();
					$has_value = is_array( $files ) && ! empty( $files );
					break;
				case 'calculation':
				case 'section_title':
				case 'description':
				case 'divider':
					$has_value = true;
					break;
				default:
					$value     = isset( $selections[ $field_id ] ) ? $selections[ $field_id ] : '';
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

/**
 * Validate field values against the live, visible schema. This catches direct
 * POST tampering that a browser's required/min/max attributes cannot prevent.
 *
 * @return array<int,string> Human-readable validation errors.
 */
function foundation_validate_submission_values( $steps, $selections ) {
	$errors     = array();
	$normalized = foundation_normalize_form_data( $steps );
	$selections = is_array( $selections ) ? $selections : array();
	$visible    = function_exists( 'foundation_get_visible_form_steps' )
		? foundation_get_visible_form_steps( $normalized, $selections )
		: $normalized;

	foreach ( $visible as $step ) {
		foreach ( (array) ( $step['fields'] ?? array() ) as $field ) {
			$field_id = (string) ( $field['id'] ?? '' );
			$type     = (string) ( $field['type'] ?? '' );
			$label    = sanitize_text_field( $field['label'] ?? 'A field' );

			if ( 'service_card' === $type ) {
				$selected = $selections[ $field_id . '_options' ] ?? array();
				if ( ! is_array( $selected ) ) {
					$errors[] = $label . ' contains an invalid selection.';
					continue;
				}
				$selected      = array_values( array_unique( array_map( 'strval', $selected ) ) );
				$valid_indexes = array_map( 'strval', array_keys( (array) ( $field['options'] ?? array() ) ) );
				if ( array_diff( $selected, $valid_indexes ) ) {
					$errors[] = $label . ' contains an option that is no longer available.';
				}
				if ( 'single' === ( $field['selection_mode'] ?? 'single' ) && count( $selected ) > 1 ) {
					$errors[] = 'Choose only one option for ' . $label . '.';
				}
				continue;
			}

			if ( 'toggle' === $type ) {
				$selected = $selections[ $field_id . '_options' ] ?? array();
				if ( ! is_array( $selected ) || count( array_unique( array_map( 'strval', $selected ) ) ) > 1 || array_diff( array_map( 'strval', (array) $selected ), array( '0', '1' ) ) ) {
					$errors[] = $label . ' contains an invalid yes/no choice.';
				}
				continue;
			}

			if ( in_array( $type, array( 'number_input', 'range_slider' ), true ) ) {
				$value = $selections[ $field_id . '_val' ] ?? $selections[ $field_id ] ?? '';
				if ( '' === (string) $value ) {
					continue;
				}
				if ( ! is_numeric( $value ) || ! is_finite( (float) $value ) ) {
					$errors[] = $label . ' must be a valid number.';
					continue;
				}
				$number = (float) $value;
				$min    = isset( $field['min'] ) ? (float) $field['min'] : 0.0;
				$max    = isset( $field['max'] ) ? (float) $field['max'] : $min;
				if ( $number < $min || $number > $max ) {
					$errors[] = sprintf( '%s must be between %s and %s.', $label, $min, $max );
					continue;
				}
				$step_value = max( 0.01, (float) ( $field['step'] ?? 1 ) );
				$steps_from_min = ( $number - $min ) / $step_value;
				if ( abs( $steps_from_min - round( $steps_from_min ) ) > 0.00001 ) {
					$errors[] = sprintf( '%s must use increments of %s.', $label, $step_value );
				}
			}
		}
	}

	return array_values( array_unique( $errors ) );
}

function foundation_collect_uploaded_files( $settings, $steps, $selections = array() ) {
	if ( empty( $_FILES['uploads'] ) || ! is_array( $_FILES['uploads'] ) ) {
		return array(
			'files'          => array(),
			'total_size'     => 0,
			'total_count'    => 0,
			'errors'         => array(),
		);
	}

	$field_lookup = array();
	$normalized_steps = foundation_normalize_form_data( $steps );
	$upload_steps = function_exists( 'foundation_get_visible_form_steps' )
		? foundation_get_visible_form_steps( $normalized_steps, is_array( $selections ) ? $selections : array() )
		: $normalized_steps;
	foreach ( $upload_steps as $step ) {
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

			$filename      = sanitize_file_name( (string) $name );
			$check         = wp_check_filetype_and_ext( $tmp_name, $filename );
			$ext           = strtolower( (string) ( $check['ext'] ?? '' ) );
			$detected_type = sanitize_mime_type( (string) ( $check['type'] ?? '' ) );
			if ( empty( $ext ) || empty( $detected_type ) || ! in_array( $ext, $allowed_extensions, true ) ) {
				$messages[] = sprintf( '%s is not an allowed or recognised file type.', $filename );
				continue;
			}

			$collected[ $field_id ][] = array(
				'name'     => $filename,
				'tmp_name' => $tmp_name,
				'type'     => $detected_type,
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

function foundation_format_money_value( $value, $currency = '£' ) {
	$value    = round( (float) $value, 2 );
	$decimals = abs( $value - round( $value ) ) > 0.00001 ? 2 : 0;
	return $currency . number_format_i18n( $value, $decimals );
}

function foundation_format_quote_range( $min, $max, $currency = '£' ) {
	$min = round( (float) $min, 2 );
	$max = round( max( $min, (float) $max ), 2 );
	return abs( $min - $max ) < 0.00001
		? foundation_format_money_value( $min, $currency )
		: foundation_format_money_value( $min, $currency ) . ' to ' . foundation_format_money_value( $max, $currency );
}

/**
 * Flatten a submission into a portable, human-readable report.
 */
function foundation_flatten_summary_for_export( $contact, $summary, $quote, $settings, $reference = '' ) {
	$currency   = $settings['currency_symbol'] ?? '£';
	$quote_mode = foundation_is_quote_mode_enabled( $settings );
	$quote      = is_array( $quote ) ? $quote : array();
	$lines      = array(
		'Inkfire Project Calculator Enquiry',
		'' !== $reference ? 'Reference: ' . $reference : '',
		'Generated: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC',
		'',
		'Contact details',
		'Name: ' . ( $contact['name'] ?? '' ),
		'Company: ' . ( $contact['company'] ?? '' ),
		'Email: ' . ( $contact['email'] ?? '' ),
		'Phone: ' . ( $contact['phone'] ?? '' ),
		'Website: ' . ( $contact['website'] ?? '' ),
	);

	if ( ! empty( $contact['notes'] ) ) {
		$lines[] = 'Notes: ' . preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $contact['notes'] ) );
	}

	$lines[] = '';
	$lines[] = 'Customer answers';
	foreach ( (array) $summary as $row ) {
		$label   = wp_strip_all_tags( $row['label'] ?? '' );
		$value   = wp_strip_all_tags( $row['value_text'] ?? ( $row['value'] ?? '' ) );
		$section = wp_strip_all_tags( $row['section'] ?? '' );
		$prefix  = '' !== $section ? '[' . $section . '] ' : '';
		$lines[] = sprintf( '- %s%s: %s', $prefix, $label, $value );
	}

	$lines[] = '';
	$lines[] = 'Estimate';
	if ( $quote_mode ) {
		$lines[] = 'Quote-only mode was enabled. Calculated prices were hidden from the customer.';
	} else {
		if ( ! empty( $quote['one_off_max'] ) ) {
			$lines[] = 'One-off estimate: ' . foundation_format_quote_range( $quote['one_off_min'] ?? 0, $quote['one_off_max'] ?? 0, $currency ) . ' excluding VAT';
		}
		if ( ! empty( $quote['monthly_max'] ) ) {
			$lines[] = 'Monthly estimate: ' . foundation_format_quote_range( $quote['monthly_min'] ?? 0, $quote['monthly_max'] ?? 0, $currency ) . ' per month excluding VAT';
		}
		if ( empty( $quote['one_off_max'] ) && empty( $quote['monthly_max'] ) ) {
			$lines[] = 'No automatic price was available.';
		}
	}

	foreach ( (array) ( $quote['line_items'] ?? array() ) as $item ) {
		$amount  = foundation_format_quote_range( $item['min'] ?? 0, $item['max'] ?? 0, $currency );
		$suffix  = 'monthly' === ( $item['billing'] ?? '' ) ? ' per month' : ' one-off';
		$lines[] = '- ' . wp_strip_all_tags( $item['label'] ?? 'Service' ) . ': ' . $amount . $suffix;
	}

	if ( ! empty( $quote['manual_items'] ) ) {
		$lines[] = '';
		$lines[] = 'Tailored-quote items';
		foreach ( (array) $quote['manual_items'] as $item ) {
			$lines[] = '- ' . wp_strip_all_tags( $item['label'] ?? 'Service' ) . ': ' . wp_strip_all_tags( $item['note'] ?? 'Scope to be confirmed.' );
		}
	}

	$lines[] = '';
	$lines[] = wp_strip_all_tags( $settings['vat_note'] ?? 'All prices are shown excluding VAT.' );
	$lines[] = wp_strip_all_tags( $settings['estimate_disclaimer'] ?? '' );

	return array_values( array_filter( $lines, static function ( $line ) { return null !== $line; } ) );
}

function foundation_pdf_escape_text( $text ) {
	$text = remove_accents( (string) $text );
	$text = preg_replace( '/[^\x20-\x7E]/', '?', $text );
	$text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
	return $text;
}

function foundation_generate_pdf_attachment( $contact, $summary, $quote, $settings, $reference = '' ) {
	$lines      = foundation_flatten_summary_for_export( $contact, $summary, $quote, $settings, $reference );
	$chunks     = array_chunk( $lines, 42 );
	$page_count = max( 1, count( $chunks ) );
	$objects    = array();
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
	$kids = array_map( static function ( $obj_num ) { return $obj_num . ' 0 R'; }, $page_objects );
	$objects[ $pages_obj ] = '<< /Type /Pages /Kids [ ' . implode( ' ', $kids ) . ' ] /Count ' . $page_count . ' >>';
	$objects[ $font_obj ]  = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

	foreach ( $chunks as $index => $chunk ) {
		$stream   = array( 'BT', '/F1 16 Tf', '50 790 Td', '18 TL' );
		$title    = foundation_pdf_escape_text( 'Project Estimate - ' . ( $reference ? $reference : ( $contact['company'] ?? $contact['name'] ?? 'Submission' ) ) );
		$stream[] = '(' . $title . ') Tj';
		$stream[] = 'T*';
		$stream[] = '/F1 10 Tf';
		$stream[] = '13 TL';
		$stream[] = '(Page ' . ( $index + 1 ) . ' of ' . $page_count . ') Tj';
		$stream[] = 'T*';
		$stream[] = 'T*';
		foreach ( $chunk as $line ) {
			// Wrap long ASCII lines to avoid running beyond the page edge.
			$wrapped = explode( "\n", wordwrap( (string) $line, 88, "\n", true ) );
			foreach ( $wrapped as $wrapped_line ) {
				$stream[] = '(' . foundation_pdf_escape_text( $wrapped_line ) . ') Tj';
				$stream[] = 'T*';
			}
		}
		$stream[]   = 'ET';
		$stream_text = implode( "\n", $stream );
		$content_obj = $content_objects[ $index ];
		$page_obj    = $page_objects[ $index ];
		$objects[ $content_obj ] = '<< /Length ' . strlen( $stream_text ) . ' >>' . "\nstream\n" . $stream_text . "\nendstream";
		$objects[ $page_obj ]    = '<< /Type /Page /Parent ' . $pages_obj . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $font_obj . ' 0 R >> >> /Contents ' . $content_obj . ' 0 R >>';
	}

	$pdf     = "%PDF-1.4\n";
	$offsets = array( 0 );
	for ( $i = 1; $i < $object_index; $i++ ) {
		$offsets[ $i ] = strlen( $pdf );
		$pdf .= $i . " 0 obj\n" . $objects[ $i ] . "\nendobj\n";
	}
	$xref_offset = strlen( $pdf );
	$pdf .= "xref\n0 " . $object_index . "\n0000000000 65535 f \n";
	for ( $i = 1; $i < $object_index; $i++ ) {
		$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
	}
	$pdf .= 'trailer << /Size ' . $object_index . ' /Root ' . $catalog_obj . " 0 R >>\nstartxref\n" . $xref_offset . "\n%%EOF";

	$temp_file = wp_tempnam( 'foundation-project-estimate.pdf' );
	if ( ! $temp_file || false === file_put_contents( $temp_file, $pdf ) ) {
		if ( $temp_file ) {
			@unlink( $temp_file );
		}
		return '';
	}
	return $temp_file;
}

function foundation_generate_json_attachment( $contact, $summary, $quote, $settings, $attachments = array(), $reference = '' ) {
	$data = array(
		'schema_version' => 2,
		'generated_at'   => gmdate( 'c' ),
		'reference'      => sanitize_text_field( $reference ),
		'contact'        => $contact,
		'answers'        => $summary,
		'quote'          => $quote,
		'quote_mode'     => foundation_is_quote_mode_enabled( $settings ),
		'currency'       => $settings['currency_symbol'] ?? '£',
		'vat_note'       => $settings['vat_note'] ?? '',
		'attachments'    => array_values(
			array_map(
				static function ( $item ) {
					return array(
						'name' => sanitize_file_name( $item['name'] ?? '' ),
						'size' => max( 0, (int) ( $item['size'] ?? 0 ) ),
						'type' => sanitize_mime_type( $item['type'] ?? '' ),
					);
				},
				(array) $attachments
			)
		),
	);

	$temp_file = wp_tempnam( 'foundation-project-estimate.json' );
	$json      = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	if ( ! $temp_file || empty( $json ) || false === file_put_contents( $temp_file, $json ) ) {
		if ( $temp_file ) {
			@unlink( $temp_file );
		}
		return '';
	}
	return $temp_file;
}

function foundation_create_submission_package( $contact, $summary, $quote, $settings, $uploaded_files, $pdf_path = '', $json_path = '', $reference = '' ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return '';
	}

	$zip_path = wp_tempnam( 'foundation-project-estimate.zip' );
	if ( ! $zip_path ) {
		return '';
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path, ZipArchive::OVERWRITE ) ) {
		@unlink( $zip_path );
		return '';
	}

	if ( $pdf_path && file_exists( $pdf_path ) ) {
		$zip->addFile( $pdf_path, 'project-estimate.pdf' );
	}
	if ( $json_path && file_exists( $json_path ) ) {
		$zip->addFile( $json_path, 'project-estimate.json' );
	}
	$zip->addFromString( 'README.txt', implode( "\n", foundation_flatten_summary_for_export( $contact, $summary, $quote, $settings, $reference ) ) );

	$used_entries = array();
	foreach ( (array) $uploaded_files as $field_id => $files ) {
		$field_folder = sanitize_key( $field_id );
		foreach ( (array) $files as $file_index => $file ) {
			if ( empty( $file['tmp_name'] ) || ! is_readable( $file['tmp_name'] ) ) {
				continue;
			}
			$name      = sanitize_file_name( $file['name'] ?? 'file' );
			$name      = $name ? $name : 'file-' . ( (int) $file_index + 1 );
			$entry     = 'uploads/' . $field_folder . '/' . $name;
			$extension = pathinfo( $name, PATHINFO_EXTENSION );
			$stem      = $extension ? substr( $name, 0, -1 * ( strlen( $extension ) + 1 ) ) : $name;
			$counter   = 2;
			while ( isset( $used_entries[ strtolower( $entry ) ] ) ) {
				$candidate = $stem . '-' . $counter . ( $extension ? '.' . $extension : '' );
				$entry     = 'uploads/' . $field_folder . '/' . $candidate;
				$counter++;
			}
			$used_entries[ strtolower( $entry ) ] = true;
			$zip->addFile( $file['tmp_name'], $entry );
		}
	}

	$zip->close();
	return $zip_path;
}

function foundation_cleanup_temp_files( $files ) {
	foreach ( (array) $files as $file ) {
		if ( ! empty( $file ) && is_string( $file ) && file_exists( $file ) ) {
			@unlink( $file );
		}
	}
}
