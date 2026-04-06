<?php
/**
 * Foundation Calculator - Email Handler
 * Handles AJAX submission, data formatting, and email dispatch.
 */

add_action('wp_ajax_foundation_submit_quote', 'foundation_process_quote');
add_action('wp_ajax_nopriv_foundation_submit_quote', 'foundation_process_quote');

function foundation_process_quote() {
    check_ajax_referer('foundation_nonce', 'nonce');

    $contact    = isset($_POST['contact']) ? $_POST['contact'] : [];
    $selections = isset($_POST['selections']) ? $_POST['selections'] : [];
    $honeypot   = isset($_POST['foundation_honey']) ? sanitize_text_field((string) wp_unslash($_POST['foundation_honey'])) : '';

    if ($honeypot !== '') {
        wp_send_json_success(['total' => 0, 'admin_sent' => false, 'customer_sent' => false]);
    }

    $contact = array(
        'name'    => isset($contact['name']) ? sanitize_text_field((string) $contact['name']) : '',
        'email'   => isset($contact['email']) ? sanitize_email((string) $contact['email']) : '',
        'phone'   => isset($contact['phone']) ? sanitize_text_field((string) $contact['phone']) : '',
        'company' => isset($contact['company']) ? sanitize_text_field((string) $contact['company']) : '',
        'website' => isset($contact['website']) ? esc_url_raw((string) $contact['website']) : '',
    );

    if ($contact['name'] === '' || $contact['email'] === '' || $contact['phone'] === '' || $contact['company'] === '') {
        wp_send_json_error(['message' => 'Please complete your name, company, email, and phone number.'], 422);
    }

    if (!is_email($contact['email'])) {
        wp_send_json_error(['message' => 'Please enter a valid email address.'], 422);
    }

    $rate_limit_key = 'foundation_quote_' . md5(strtolower($contact['email']) . '|' . foundation_get_request_ip());
    if (false !== get_transient($rate_limit_key)) {
        wp_send_json_error(['message' => 'Please wait a moment before sending another request.'], 429);
    }
    set_transient($rate_limit_key, 1, 30);

    if (
        empty($selections['field_budget_options']) ||
        empty($selections['field_timeline_options']) ||
        empty($selections['field_services_main_options'])
    ) {
        wp_send_json_error(['message' => 'Please complete your budget, timeline, and service selections before requesting a quote.'], 422);
    }
    
    $steps = get_option('foundation_form_data', []); 
    if (empty($steps) || !is_array($steps)) {
        wp_send_json_error(['message' => 'Configuration not found.']);
    }

    $summary = [];
    $total_price = 0.00;
    $attachment_count = 0;
    $attachments = [];
    $uploaded_files = foundation_collect_uploaded_files();

    // Loop through all steps to capture EVERY completed field
    foreach ($steps as $step) {
        foreach ($step['fields'] as $field) {
            $field_id = $field['id'];
            $label = isset($field['label']) ? $field['label'] : 'Untitled';
            $type = $field['type'];
            
            // A. Text / Rich Text
            if (($type === 'text_input' || $type === 'rich_text') && !empty($selections[$field_id])) {
                $val = sanitize_textarea_field($selections[$field_id]);
                $display_val = nl2br($val);
                $summary[] = ['label' => $label, 'value' => $display_val, 'price' => 0];
            }
            // B. File Uploads
            elseif ($type === 'file_upload' && !empty($uploaded_files[$field_id])) {
                foreach ($uploaded_files[$field_id] as $file_data) {
                    $filename = basename(str_replace('\\', '/', $file_data['name']));
                    $attachment_count++;
                    if (!empty($file_data['tmp_name']) && is_readable($file_data['tmp_name'])) {
                        $attachments[] = $file_data['tmp_name'];
                    }
                    $summary[] = ['label' => $label, 'value' => '<strong>📎 ' . esc_html($filename) . '</strong>', 'price' => 0];
                }
            }
            // C. Range Slider
            elseif ($type === 'range_slider' && isset($selections[$field_id . '_val'])) {
                $val = intval($selections[$field_id . '_val']);
                $price = floatval($field['price_per_unit'] ?? 0) * $val;
                $unit = isset($field['unit']) ? $field['unit'] : 'units';
                $total_price += $price;
                $summary[] = ['label' => $label, 'value' => "{$val} {$unit}", 'price' => $price];
            }
            // D. Options (Service Cards / Toggles)
            elseif (isset($selections[$field_id . '_options']) && is_array($selections[$field_id . '_options'])) {
                $user_indices = $selections[$field_id . '_options'];
                $chosen_labels = [];
                $this_field_price = 0;
                if (isset($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $idx => $opt) {
                        if (in_array((string)$idx, $user_indices)) {
                            $chosen_labels[] = $opt['label'];
                            $p = floatval($opt['price'] ?? 0);
                            $this_field_price += $p;
                        }
                    }
                }
                if (!empty($chosen_labels)) {
                    $total_price += $this_field_price;
                    $summary[] = ['label' => $label, 'value' => implode(', ', $chosen_labels), 'price' => $this_field_price];
                }
            }
        }
    }

    $show_pricing = ($total_price > 0);

    // Generate HTML for both recipients
    $admin_email_content = foundation_generate_email_html($contact, $summary, $total_price, true, $show_pricing, $attachment_count);
    $customer_email_content = foundation_generate_email_html($contact, $summary, $total_price, false, $show_pricing, 0);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $contact['name'] . ' <' . $contact['email'] . '>',
    ];
    
    // Send to Admin
    $admin_to = 'webmaster@inkfire.co.uk';
    $subject_admin = 'New customer project brief: ' . esc_html($contact['company']);
    $admin_sent = wp_mail($admin_to, $subject_admin, $admin_email_content, $headers, $attachments);

    // Send to Customer
    $customer_sent = true;
    if (is_email($contact['email'])) {
        $subject_cust = 'We’ve received your project brief - ' . get_bloginfo('name');
        $customer_sent = wp_mail($contact['email'], $subject_cust, $customer_email_content, ['Content-Type: text/html; charset=UTF-8']);
    }

    if (!$admin_sent) {
        wp_send_json_error(['message' => 'The quote email could not be sent right now. Please try again shortly.'], 500);
    }

    wp_send_json_success([
        'total'         => $total_price,
        'admin_sent'    => (bool) $admin_sent,
        'customer_sent' => (bool) $customer_sent,
    ]);
}

function foundation_collect_uploaded_files() {
    if (empty($_FILES['uploads']) || !is_array($_FILES['uploads'])) {
        return [];
    }

    $uploads = $_FILES['uploads'];
    $names = $uploads['name'] ?? [];
    $tmp_names = $uploads['tmp_name'] ?? [];
    $types = $uploads['type'] ?? [];
    $sizes = $uploads['size'] ?? [];
    $errors = $uploads['error'] ?? [];
    $collected = [];

    foreach ($names as $field_id => $file_names) {
        if (!is_array($file_names)) {
            continue;
        }

        foreach ($file_names as $index => $name) {
            $error = $errors[$field_id][$index] ?? UPLOAD_ERR_NO_FILE;
            $tmp_name = $tmp_names[$field_id][$index] ?? '';
            if ($error !== UPLOAD_ERR_OK || empty($tmp_name) || !is_uploaded_file($tmp_name)) {
                continue;
            }

            $collected[$field_id][] = [
                'name' => sanitize_file_name((string) $name),
                'tmp_name' => $tmp_name,
                'type' => sanitize_mime_type((string) ($types[$field_id][$index] ?? '')),
                'size' => (int) ($sizes[$field_id][$index] ?? 0),
            ];
        }
    }

    return $collected;
}

function foundation_get_request_ip() {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $value = (string) wp_unslash($_SERVER[$key]);
        if ('HTTP_X_FORWARDED_FOR' === $key) {
            $parts = array_map('trim', explode(',', $value));
            $value = $parts[0] ?? '';
        }
        $value = preg_replace('/[^0-9a-fA-F:\\.]/', '', $value);
        if (!empty($value)) {
            return $value;
        }
    }

    return 'unknown';
}

/**
 * Generate HTML Email Template (Responsive & Accessible)
 */
function foundation_generate_email_html($contact, $summary, $total, $is_admin, $show_pricing, $attachment_count) {
    // --- STYLING VARIABLES ---
    $bg_color = '#ffffff'; // Clean white background for 100% width look
    $header_bg = '#191A28';
    $header_text = '#FBCCBF';
    $link_color = '#07a079';
    $text_color = '#2d3436';
    $logo_url = 'https://inkfire.co.uk/wp-content/uploads/2025/11/IMG_1089.png';
    $portfolio_url = 'https://inkfire.co.uk/portfolio'; 
    
    ob_start();
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta name="format-detection" content="telephone=no" />
        <title><?php echo $is_admin ? 'New customer project brief' : 'Project Brief Received'; ?></title>
        <style type="text/css">
            /* Resets */
            body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: <?php echo $bg_color; ?>; }
            table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
            
            /* Responsive Utilities */
            @media screen and (max-width: 600px) {
                .container { width: 100% !important; max-width: 100% !important; }
                .mobile-padding { padding-left: 20px !important; padding-right: 20px !important; }
                .stack-column { display: block !important; width: 100% !important; }
                .mobile-text-center { text-align: center !important; }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 0; background-color: <?php echo $bg_color; ?>;">
        
        <!-- 100% Width Wrapper -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="background-color: <?php echo $bg_color; ?>;">
            <tr>
                <td align="center">
                    
                    <!-- Main Content Container (Max 600px) -->
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;" role="presentation" class="container">
                        
                        <!-- Header -->
                        <tr>
                            <td align="center" style="background-color: <?php echo $header_bg; ?>; padding: 40px 20px;" class="mobile-padding">
                                <a href="https://inkfire.co.uk" target="_blank" style="text-decoration:none;">
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Inkfire" width="180" style="display: block; border: 0; font-family: sans-serif; font-size: 20px; color: #ffffff; margin-bottom: 25px;">
                                </a>
                                <h1 style="margin: 0; font-family: sans-serif; font-size: 24px; line-height: 1.3; font-weight: 700; color: <?php echo $header_text; ?>;">
                                    <?php echo $is_admin ? 'New customer project brief' : 'Project Brief Received'; ?>
                                </h1>
                            </td>
                        </tr>

                        <!-- Content Body -->
                        <tr>
                            <td style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: <?php echo $text_color; ?>;" class="mobile-padding">
                                
                                <!-- Intro Text -->
                                <?php if ($is_admin): ?>
                                    <p style="margin: 0 0 20px 0;"><strong><?php echo esc_html($contact['name']); ?></strong> from <strong><?php echo esc_html($contact['company']); ?></strong> has just submitted a brief.</p>
                                    
                                    <!-- Admin Details Box -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="background-color: #f8f9fa; border-radius: 6px; margin-bottom: 30px;">
                                        <tr>
                                            <td style="padding: 20px;">
                                                <div style="margin-bottom:8px;"><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($contact['email']); ?>" style="color: <?php echo $link_color; ?>; text-decoration: none; font-weight: 600;"><?php echo esc_html($contact['email']); ?></a></div>
                                                <div style="margin-bottom:8px;"><strong>Phone:</strong> <?php echo esc_html($contact['phone']); ?></div>
                                                <div style="margin-bottom:8px;"><strong>Website:</strong> <a href="<?php echo esc_url($contact['website']); ?>" style="color: <?php echo $link_color; ?>; text-decoration: none; font-weight: 600;"><?php echo esc_html($contact['website']); ?></a></div>
                                                
                                                <!-- Attachment Indicator -->
                                                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #e0e0e0;">
                                                    <strong>Attachments:</strong> 
                                                    <?php if($attachment_count > 0): ?>
                                                        <span style="color:<?php echo $link_color; ?>; font-weight:bold;">Yes (<?php echo $attachment_count; ?> file<?php echo $attachment_count > 1 ? 's' : ''; ?>)</span>
                                                    <?php else: ?>
                                                        <span style="color:#999;">None</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>

                                <?php else: ?>
                                    <p style="margin: 0 0 10px 0;">Hi <?php echo esc_html($contact['name']); ?>,</p>
                                    <p style="margin: 0 0 10px 0;">Thanks for sending over your project brief! We have received your details safely.</p>
                                    <p style="margin: 0 0 30px 0;">A member of our team is reviewing your requirements and will be in touch shortly to discuss the next steps.</p>
                                <?php endif; ?>

                                <!-- Data Table -->
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <th align="left" style="border-bottom: 2px solid #eee; padding: 10px 0; color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;" width="75%"><?php echo $is_admin ? 'Project Details' : 'Your Brief Details'; ?></th>
                                            <?php if($show_pricing): ?>
                                                <th align="right" style="border-bottom: 2px solid #eee; padding: 10px 0; color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;" width="25%">Cost</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($summary)): ?>
                                            <tr><td colspan="2" style="padding: 16px 0; border-bottom: 1px solid #f0f0f0;">No selections made.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($summary as $row): ?>
                                            <tr>
                                                <td valign="top" style="padding: 16px 0; border-bottom: 1px solid #f0f0f0;">
                                                    <span style="font-weight: 700; display: block; margin-bottom: 4px; font-size: 14px; color: <?php echo $text_color; ?>;"><?php echo esc_html($row['label']); ?></span>
                                                    <span style="color: #636e72; font-size: 14px; display: block;"><?php echo $row['value']; // Contains safe HTML ?></span>
                                                </td>
                                                <?php if($show_pricing): ?>
                                                <td align="right" valign="top" style="padding: 16px 0; border-bottom: 1px solid #f0f0f0; font-weight: 600; color: <?php echo $text_color; ?>;">
                                                    <?php echo ($row['price'] > 0) ? '£' . number_format($row['price'], 0) : '-'; ?>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <?php if($show_pricing): ?>
                                        <tr>
                                            <td style="padding-top: 25px; border-top: 2px solid #eee; font-size: 20px; font-weight: 800; color: <?php echo $header_bg; ?>;">Estimated Total</td>
                                            <td align="right" style="padding-top: 25px; border-top: 2px solid #eee; font-size: 20px; font-weight: 800; color: <?php echo $header_bg; ?>;">£<?php echo number_format($total, 0); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <!-- Customer Call to Action -->
                                <?php if (!$is_admin): ?>
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                                        <tr>
                                            <td align="center">
                                                <!-- Pink Button (Black Text) -->
                                                <a href="<?php echo esc_url($portfolio_url); ?>" style="display: inline-block; background-color: #FADACF; color: #000000; padding: 14px 30px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 16px; margin: 20px 0;">
                                                    Hear from our clients <span style="font-size: 18px; vertical-align: middle; margin-left: 5px;">&#8599;</span>
                                                </a>
                                                
                                                <p style="margin: 20px 0 10px 0; font-size: 14px; color: #555;">Follow us for updates:</p>
                                                
                                                <!-- Social Icons -->
                                                <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto;">
                                                    <tr>
                                                        <td style="padding: 0 8px;"><a href="https://uk.linkedin.com/company/inkfire" target="_blank"><img src="https://cdn-icons-png.flaticon.com/512/3536/3536505.png" width="24" height="24" alt="LinkedIn" style="display:block;"></a></td>
                                                        <td style="padding: 0 8px;"><a href="https://twitter.com/Inkfirelimited" target="_blank"><img src="https://cdn-icons-png.flaticon.com/512/3256/3256013.png" width="24" height="24" alt="Twitter" style="display:block;"></a></td>
                                                        <td style="padding: 0 8px;"><a href="https://facebook.com/inkfirelimited" target="_blank"><img src="https://cdn-icons-png.flaticon.com/512/124/124010.png" width="24" height="24" alt="Facebook" style="display:block;"></a></td>
                                                        <td style="padding: 0 8px;"><a href="https://www.instagram.com/inkfirelimited/" target="_blank"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" width="24" height="24" alt="Instagram" style="display:block;"></a></td>
                                                        <td style="padding: 0 8px;"><a href="https://www.tiktok.com/@inkfirelimited" target="_blank"><img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" width="24" height="24" alt="TikTok" style="display:block;"></a></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                <?php endif; ?>

                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td align="center" style="background-color: #f1f2f6; padding: 25px; font-family: sans-serif; font-size: 12px; color: #b2bec3;">
                                &copy; <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. All rights reserved.
                            </td>
                        </tr>

                    </table>
                    <!-- End Main Container -->

                </td>
            </tr>
        </table>
        <!-- End Wrapper -->

    </body>
    </html>
    <?php
    return ob_get_clean();
}
