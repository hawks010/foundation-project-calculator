<?php
/**
 * Handles AJAX submission, data formatting and email dispatch.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_foundation_submit_quote', 'foundation_process_quote' );
add_action( 'wp_ajax_nopriv_foundation_submit_quote', 'foundation_process_quote' );

function foundation_process_quote() {
	check_ajax_referer( 'foundation_nonce', 'nonce' );

	$raw_contact = isset( $_POST['contact'] ) ? wp_unslash( $_POST['contact'] ) : array();
	$raw_selections = isset( $_POST['selections'] ) ? wp_unslash( $_POST['selections'] ) : array();
	$honeypot = isset( $_POST['foundation_honey'] ) ? sanitize_text_field( wp_unslash( $_POST['foundation_honey'] ) ) : '';

	if ( '' !== $honeypot ) {
		wp_send_json_success( array( 'total' => 0, 'admin_sent' => false, 'customer_sent' => false ) );
	}

	$contact = array(
		'name'    => sanitize_text_field( $raw_contact['name'] ?? '' ),
		'email'   => sanitize_email( $raw_contact['email'] ?? '' ),
		'phone'   => sanitize_text_field( $raw_contact['phone'] ?? '' ),
		'company' => sanitize_text_field( $raw_contact['company'] ?? '' ),
		'website' => esc_url_raw( $raw_contact['website'] ?? '' ),
	);

	if ( '' === $contact['name'] || '' === $contact['email'] || '' === $contact['phone'] || '' === $contact['company'] ) {
		wp_send_json_error( array( 'message' => 'Please complete your name, company, email and phone number.' ), 422 );
	}

	if ( ! is_email( $contact['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ), 422 );
	}

	$rate_limit_key = 'foundation_quote_' . md5( strtolower( $contact['email'] ) . '|' . foundation_get_request_ip() );
	if ( false !== get_transient( $rate_limit_key ) ) {
		wp_send_json_error( array( 'message' => 'Please wait a moment before sending another request.' ), 429 );
	}
	set_transient( $rate_limit_key, 1, 30 );

	$steps = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
	if ( empty( $steps ) ) {
		wp_send_json_error( array( 'message' => 'Calculator configuration not found.' ), 500 );
	}
	$settings = foundation_get_settings();

	$uploaded_result = foundation_collect_uploaded_files( $settings, $steps );
	if ( ! empty( $uploaded_result['errors'] ) ) {
		wp_send_json_error( array( 'message' => implode( ' ', $uploaded_result['errors'] ) ), 422 );
	}
	$uploaded_files = $uploaded_result['files'];

	$selections = foundation_sanitize_submission_selections( $raw_selections );
	$missing_required = foundation_validate_required_submission_fields( $steps, $selections, $uploaded_files );
	if ( ! empty( $missing_required ) ) {
		wp_send_json_error( array( 'message' => 'Please complete the required fields: ' . implode( ', ', $missing_required ) . '.' ), 422 );
	}

	$core_field_ids = foundation_get_core_selection_field_ids( $steps );
	$missing_core = array();
	foreach ( array( 'budget' => 'budget', 'timeline' => 'timeline', 'services_main' => 'service selection' ) as $key => $label ) {
		$field_id = $core_field_ids[ $key ] ?? '';
		if ( empty( $field_id ) ) {
			continue;
		}
		$selected = $selections[ $field_id . '_options' ] ?? array();
		if ( ! is_array( $selected ) || empty( $selected ) ) {
			$missing_core[] = $label;
		}
	}

	if ( ! empty( $missing_core ) ) {
		wp_send_json_error( array( 'message' => 'Please complete your ' . implode( ', ', $missing_core ) . ' before requesting a quote.' ), 422 );
	}

	$summary_data = foundation_build_submission_summary( $steps, $selections, $uploaded_files, $settings );
	$summary      = $summary_data['summary'];
	$total_price  = $summary_data['total'];
	$attachments  = $summary_data['attachments'];
	$attachment_count = $summary_data['attachment_count'];
	$show_pricing = $total_price > 0;

	$pdf_path = ! empty( $settings['attach_pdf_summary'] ) ? foundation_generate_pdf_attachment( $contact, $summary, $total_price, $settings ) : '';
	$json_path = ! empty( $settings['attach_json_summary'] ) ? foundation_generate_json_attachment( $contact, $summary, $total_price, $settings, $attachments ) : '';
	$zip_path = ! empty( $settings['attach_zip_package'] ) ? foundation_create_submission_package( $contact, $summary, $total_price, $settings, $uploaded_files, $pdf_path, $json_path ) : '';

	$admin_email_content = foundation_generate_email_html( $contact, $summary, $total_price, true, $show_pricing, $attachment_count, $settings );
	$customer_email_content = foundation_generate_email_html( $contact, $summary, $total_price, false, $show_pricing, 0, $settings );

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( ! empty( $settings['from_name'] ) && ! empty( $settings['from_email'] ) && ! foundation_is_placeholder_email( $settings['from_email'] ) ) {
		$headers[] = 'From: ' . wp_specialchars_decode( $settings['from_name'], ENT_QUOTES ) . ' <' . $settings['from_email'] . '>';
	}
	$headers[] = 'Reply-To: ' . wp_specialchars_decode( $contact['name'], ENT_QUOTES ) . ' <' . $contact['email'] . '>';
	if ( ! empty( $settings['cc_emails'] ) ) {
		$headers[] = 'Cc: ' . $settings['cc_emails'];
	}

	$admin_to = ( ! empty( $settings['admin_email'] ) && ! foundation_is_placeholder_email( $settings['admin_email'] ) )
		? sanitize_email( $settings['admin_email'] )
		: foundation_get_default_notification_email();
	$admin_subject_prefix = trim( (string) ( $settings['admin_subject_prefix'] ?? '' ) );
	$subject_admin = '' !== $admin_subject_prefix
		? $admin_subject_prefix . ': ' . $contact['company']
		: 'New project brief: ' . $contact['company'];

	$admin_mail_attachments = array();
	foreach ( $attachments as $file ) {
		if ( ! empty( $file['tmp_name'] ) && is_readable( $file['tmp_name'] ) ) {
			$admin_mail_attachments[] = $file['tmp_name'];
		}
	}
	foreach ( array( $pdf_path, $json_path, $zip_path ) as $extra_file ) {
		if ( ! empty( $extra_file ) && file_exists( $extra_file ) ) {
			$admin_mail_attachments[] = $extra_file;
		}
	}

	$admin_sent = wp_mail( $admin_to, $subject_admin, $admin_email_content, $headers, $admin_mail_attachments );

	$customer_sent = false;
	$customer_email_status = 'disabled';
	if ( ! empty( $settings['customer_confirmation_enabled'] ) && is_email( $contact['email'] ) ) {
		$customer_subject = trim( $settings['customer_subject'] );
		if ( '' === $customer_subject ) {
			$customer_subject = 'We’ve received your project brief - ' . get_bloginfo( 'name' );
		}
		$customer_sent = wp_mail(
			$contact['email'],
			$customer_subject,
			$customer_email_content,
			array_filter( array(
				'Content-Type: text/html; charset=UTF-8',
				( ! empty( $settings['from_name'] ) && ! empty( $settings['from_email'] ) && ! foundation_is_placeholder_email( $settings['from_email'] ) ) ? 'From: ' . wp_specialchars_decode( $settings['from_name'], ENT_QUOTES ) . ' <' . $settings['from_email'] . '>' : '',
				is_email( $admin_to ) ? 'Reply-To: ' . $admin_to : '',
			) )
		);
		$customer_email_status = $customer_sent ? 'sent' : 'failed';
	}

	foundation_cleanup_temp_files( array( $pdf_path, $json_path, $zip_path ) );

	if ( ! $admin_sent ) {
		wp_send_json_error( array( 'message' => 'The quote email could not be sent right now. Please try again shortly.' ), 500 );
	}

	wp_send_json_success(
		array(
			'total'         => $total_price,
			'admin_sent'    => (bool) $admin_sent,
			'customer_sent' => (bool) $customer_sent,
			'customer_email_status' => $customer_email_status,
		)
	);
}

function foundation_sanitize_submission_selections( $selections ) {
	$clean = array();
	if ( ! is_array( $selections ) ) {
		return $clean;
	}

	foreach ( $selections as $key => $value ) {
		$key = sanitize_key( $key );
		if ( is_array( $value ) ) {
			$clean[ $key ] = array_values( array_map( 'sanitize_text_field', array_map( 'strval', $value ) ) );
		} else {
			$clean[ $key ] = sanitize_textarea_field( (string) $value );
		}
	}
	return $clean;
}

function foundation_build_submission_summary( $steps, $selections, $uploaded_files, $settings ) {
	$summary = array();
	$total_price = 0.0;
	$attachments = array();
	$attachment_count = 0;
	$currency = $settings['currency_symbol'] ?? '£';

	foreach ( $steps as $step ) {
		$fields = isset( $step['fields'] ) && is_array( $step['fields'] ) ? $step['fields'] : array();
		foreach ( $fields as $field ) {
			$field_id = $field['id'] ?? '';
			$label = $field['label'] ?? 'Untitled';
			$type = $field['type'] ?? '';

			if ( 'text_input' === $type || 'rich_text' === $type ) {
				$value = isset( $selections[ $field_id ] ) ? sanitize_textarea_field( $selections[ $field_id ] ) : '';
				if ( '' !== $value ) {
					$summary[] = array(
						'label'      => $label,
						'value'      => nl2br( esc_html( $value ) ),
						'value_text' => $value,
						'price'      => 0,
					);
				}
				continue;
			}

			if ( 'file_upload' === $type ) {
				if ( empty( $uploaded_files[ $field_id ] ) ) {
					continue;
				}
				$names = array();
				foreach ( $uploaded_files[ $field_id ] as $file_data ) {
					$filename = basename( str_replace( '\\', '/', $file_data['name'] ?? '' ) );
					$names[] = $filename;
					$attachments[] = $file_data;
					$attachment_count++;
				}
				$summary[] = array(
					'label'      => $label,
					'value'      => '<strong>📎 ' . esc_html( implode( ', ', $names ) ) . '</strong>',
					'value_text' => implode( ', ', $names ),
					'price'      => 0,
				);
				continue;
			}

			if ( 'range_slider' === $type && isset( $selections[ $field_id . '_val' ] ) ) {
				$value = intval( $selections[ $field_id . '_val' ] );
				$min   = intval( $field['min'] ?? 1 );
				$max   = max( $min, intval( $field['max'] ?? $min ) );
				$value = max( $min, min( $max, $value ) );
				$price = floatval( $field['price_per_unit'] ?? 0 ) * $value;
				$unit  = $field['unit'] ?? 'units';
				$total_price += $price;
				$summary[] = array(
					'label'      => $label,
					'value'      => esc_html( $value . ' ' . $unit ),
					'value_text' => $value . ' ' . $unit,
					'price'      => $price,
				);
				continue;
			}

			if ( isset( $selections[ $field_id . '_options' ] ) && is_array( $selections[ $field_id . '_options' ] ) ) {
				$user_indices = array_map( 'strval', $selections[ $field_id . '_options' ] );
				$chosen_labels = array();
				$this_field_price = 0;
				$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
				foreach ( $options as $idx => $opt ) {
					if ( in_array( (string) $idx, $user_indices, true ) ) {
						$chosen_labels[] = sanitize_text_field( $opt['label'] ?? 'Option' );
						$this_field_price += floatval( $opt['price'] ?? 0 );
					}
				}
				if ( ! empty( $chosen_labels ) ) {
					$total_price += $this_field_price;
					$summary[] = array(
						'label'      => $label,
						'value'      => esc_html( implode( ', ', $chosen_labels ) ),
						'value_text' => implode( ', ', $chosen_labels ),
						'price'      => $this_field_price,
					);
				}
			}
		}
	}

	if ( empty( $summary ) ) {
		$summary[] = array(
			'label'      => 'Submission',
			'value'      => 'No selections were captured.',
			'value_text' => 'No selections were captured.',
			'price'      => 0,
		);
	}

	return array(
		'summary'          => $summary,
		'total'            => round( $total_price, 2 ),
		'attachments'      => $attachments,
		'attachment_count' => $attachment_count,
		'currency'         => $currency,
	);
}

function foundation_get_request_ip() {
	foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
		if ( empty( $_SERVER[ $key ] ) ) {
			continue;
		}
		$value = (string) wp_unslash( $_SERVER[ $key ] );
		if ( 'HTTP_X_FORWARDED_FOR' === $key ) {
			$parts = array_map( 'trim', explode( ',', $value ) );
			$value = $parts[0] ?? '';
		}
		$value = preg_replace( '/[^0-9a-fA-F:\.]/', '', $value );
		if ( ! empty( $value ) ) {
			return $value;
		}
	}

	return 'unknown';
}

function foundation_generate_email_html( $contact, $summary, $total, $is_admin, $show_pricing, $attachment_count, $settings ) {
	$header_bg   = '#191A28';
	$header_text = '#FBCCBF';
	$text_color  = '#2d3436';
	$link_color  = '#07a079';
	$logo_url    = $settings['logo_url'] ?? '';
	$currency    = $settings['currency_symbol'] ?? '£';
	$portfolio_url = $settings['portfolio_url'] ?? '';

	$social_links = array_filter( array(
		'LinkedIn'  => $settings['linkedin_url'] ?? '',
		'Twitter/X' => $settings['twitter_url'] ?? '',
		'Facebook'  => $settings['facebook_url'] ?? '',
		'Instagram' => $settings['instagram_url'] ?? '',
		'TikTok'    => $settings['tiktok_url'] ?? '',
	) );

	ob_start();
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php echo esc_html( $is_admin ? 'New customer project brief' : 'Project brief received' ); ?></title>
	</head>
	<body style="margin:0;padding:0;background:#f5f7fb;color:<?php echo esc_attr( $text_color ); ?>;font-family:Arial,Helvetica,sans-serif;">
		<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f7fb;padding:20px 0;">
			<tr>
				<td align="center">
					<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:720px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;">
						<tr>
							<td style="padding:28px;background:<?php echo esc_attr( $header_bg ); ?>;color:#fff;">
								<table role="presentation" width="100%">
									<tr>
										<td valign="middle">
											<?php if ( $logo_url ) : ?>
												<img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" style="height:52px;width:auto;display:block;margin-bottom:12px;">
											<?php endif; ?>
											<h1 style="margin:0;color:<?php echo esc_attr( $header_text ); ?>;font-size:28px;line-height:1.15;">
												<?php echo esc_html( $is_admin ? 'New customer project brief' : 'Thanks, we have your brief' ); ?>
											</h1>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td style="padding:28px;">
								<?php if ( $is_admin ) : ?>
									<p style="margin:0 0 12px;">A new project enquiry has been submitted.</p>
									<p style="margin:0 0 18px;"><strong>Contact:</strong> <?php echo esc_html( $contact['name'] ); ?>, <?php echo esc_html( $contact['company'] ); ?>, <?php echo esc_html( $contact['email'] ); ?>, <?php echo esc_html( $contact['phone'] ); ?></p>
									<?php if ( $attachment_count > 0 ) : ?>
										<p style="margin:0 0 18px;color:#475569;">Files uploaded: <?php echo esc_html( $attachment_count ); ?>. Staff package attachments have been added where available.</p>
									<?php endif; ?>
								<?php else : ?>
									<p style="margin:0 0 12px;">Hi <?php echo esc_html( $contact['name'] ); ?>,</p>
									<p style="margin:0 0 18px;"><?php echo esc_html( $settings['customer_intro'] ?? 'Thanks for sending over your project brief! We have received your details safely.' ); ?></p>
								<?php endif; ?>

								<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
									<thead>
										<tr>
											<th align="left" style="padding:0 0 12px;border-bottom:2px solid #e5e7eb;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#64748b;">Detail</th>
											<?php if ( $show_pricing ) : ?>
												<th align="right" style="padding:0 0 12px;border-bottom:2px solid #e5e7eb;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#64748b;">Cost</th>
											<?php endif; ?>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $summary as $row ) : ?>
											<tr>
												<td valign="top" style="padding:14px 0;border-bottom:1px solid #edf2f7;">
													<strong style="display:block;margin-bottom:4px;color:<?php echo esc_attr( $text_color ); ?>;"><?php echo esc_html( $row['label'] ?? '' ); ?></strong>
													<span style="color:#475569;line-height:1.55;"><?php echo wp_kses_post( $row['value'] ?? '' ); ?></span>
												</td>
												<?php if ( $show_pricing ) : ?>
													<td align="right" valign="top" style="padding:14px 0;border-bottom:1px solid #edf2f7;font-weight:700;color:<?php echo esc_attr( $text_color ); ?>;white-space:nowrap;">
														<?php echo floatval( $row['price'] ?? 0 ) > 0 ? esc_html( $currency . number_format_i18n( floatval( $row['price'] ), 0 ) ) : '-'; ?>
													</td>
												<?php endif; ?>
											</tr>
										<?php endforeach; ?>
										<?php if ( $show_pricing ) : ?>
											<tr>
												<td style="padding-top:18px;font-size:20px;font-weight:800;color:<?php echo esc_attr( $header_bg ); ?>;">Estimated total</td>
												<td align="right" style="padding-top:18px;font-size:20px;font-weight:800;color:<?php echo esc_attr( $header_bg ); ?>;"><?php echo esc_html( $currency . number_format_i18n( $total, 0 ) ); ?></td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>

								<?php if ( ! $is_admin && $portfolio_url ) : ?>
									<p style="margin:28px 0 0;text-align:center;">
										<a href="<?php echo esc_url( $portfolio_url ); ?>" style="display:inline-block;background:#FADACF;color:#000;padding:14px 26px;border-radius:999px;text-decoration:none;font-weight:700;">Hear from our clients ↗</a>
									</p>
								<?php endif; ?>
								<?php if ( ! $is_admin && ! empty( $social_links ) ) : ?>
									<p style="margin:24px 0 8px;text-align:center;color:#64748b;">Follow us:</p>
									<p style="margin:0;text-align:center;">
										<?php $link_parts = array(); foreach ( $social_links as $label => $url ) { $link_parts[] = '<a href="' . esc_url( $url ) . '" style="color:' . esc_attr( $link_color ) . ';text-decoration:none;font-weight:600;">' . esc_html( $label ) . '</a>'; } echo wp_kses_post( implode( ' &nbsp;·&nbsp; ', $link_parts ) ); ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td style="padding:18px 28px;background:#f8fafc;color:#94a3b8;font-size:12px;text-align:center;">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. All rights reserved.</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
	</html>
	<?php
	return ob_get_clean();
}
