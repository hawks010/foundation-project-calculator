<?php
/**
 * Inkfire pricing catalogue, calculator blueprint and authoritative quote engine.
 *
 * The public calculator reads a compiled, sanitised flow from foundation_form_data.
 * Prices live separately in foundation_pricing_catalog so staff can update amounts
 * without touching routing, field IDs or calculation logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Version of the bundled Inkfire pricing blueprint.
 */
function foundation_get_blueprint_version() {
	return '2026.08.05';
}

/**
 * Human-friendly pricing fields used by the simple admin screen.
 *
 * @return array<string,array<string,mixed>>
 */
function foundation_get_pricing_admin_sections() {
	return array(
		'web'      => array(
			'title'       => 'Web & Accessibility',
			'description' => 'Website builds, hosting, accessibility and image support.',
			'fields'      => array(
				'web_platform_fee'                => array( 'label' => 'Base platform fee', 'default' => 500, 'unit' => 'one-off' ),
				'web_small_per_page'               => array( 'label' => 'Small/basic development per page', 'default' => 450, 'unit' => 'per page' ),
				'web_bespoke_design_per_page'      => array( 'label' => 'Bespoke design per page', 'default' => 300, 'unit' => 'per page' ),
				'web_bespoke_development_per_page' => array( 'label' => 'Bespoke development per page', 'default' => 600, 'unit' => 'per page' ),
				'web_woocommerce'                  => array( 'label' => 'WooCommerce online shop', 'default' => 750, 'unit' => 'one-off' ),
				'web_host_only'                    => array( 'label' => 'Hosting only', 'default' => 30, 'unit' => 'per month' ),
				'web_host_plugins'                 => array( 'label' => 'Hosting + plugins', 'default' => 45, 'unit' => 'per month' ),
				'web_host_edit_1'                  => array( 'label' => 'Hosting + 1 hour editing', 'default' => 95, 'unit' => 'per month' ),
				'web_host_edit_4'                  => array( 'label' => 'Hosting + 4 hours editing', 'default' => 245, 'unit' => 'per month' ),
				'web_alt_text_25'                  => array( 'label' => 'Alt text per 25 images', 'default' => 100, 'unit' => 'per block of 25' ),
				'web_accessibility_testing'         => array( 'label' => 'Accessibility testing', 'default' => 60, 'unit' => 'one-off' ),
				'web_accessibility_setup'           => array( 'label' => 'Accessibility setup', 'default' => 500, 'unit' => 'one-off' ),
				'web_host_standalone'               => array( 'label' => 'Standalone website hosting', 'default' => 45, 'unit' => 'per month' ),
			),
		),
		'tech'     => array(
			'title'       => 'Tech & Support',
			'description' => 'Ongoing support, device management, Microsoft and accessibility technology.',
			'fields'      => array(
				'tech_user_support'          => array( 'label' => 'Ongoing IT support per user', 'default' => 50, 'unit' => 'per user/month' ),
				'tech_device_management'     => array( 'label' => 'Device management', 'default' => 35, 'unit' => 'per device/month' ),
				'tech_dark_web_domain'       => array( 'label' => 'Dark web monitoring', 'default' => 15, 'unit' => 'per domain/month' ),
				'tech_consulting_hour'       => array( 'label' => 'IT consulting', 'default' => 120, 'unit' => 'per hour' ),
				'tech_windows_configuration' => array( 'label' => 'Windows configuration', 'default' => 1800, 'unit' => 'one-off' ),
				'tech_ios_configuration'     => array( 'label' => 'iOS configuration', 'default' => 1800, 'unit' => 'one-off' ),
				'tech_android_configuration' => array( 'label' => 'Android configuration', 'default' => 1800, 'unit' => 'one-off' ),
				'tech_intune_device'         => array( 'label' => 'Intune management', 'default' => 25, 'unit' => 'per device/month' ),
				'tech_accessibility_hour'    => array( 'label' => 'Accessibility-focused tech support', 'default' => 120, 'unit' => 'per hour' ),
			),
		),
		'business' => array(
			'title'       => 'Business Support & Marketing',
			'description' => 'Branding, content, executive support, consultancy and marketing.',
			'fields'      => array(
				'business_branding_mini'        => array( 'label' => 'Mini branding package', 'default' => 600, 'unit' => 'one-off' ),
				'business_branding_major'       => array( 'label' => 'Major branding package', 'default' => 3000, 'unit' => 'one-off' ),
				'business_social_static'        => array( 'label' => 'Static social post', 'default' => 50, 'unit' => 'per post/month' ),
				'business_social_carousel'      => array( 'label' => 'Carousel social post', 'default' => 100, 'unit' => 'per post/month' ),
				'business_social_video'         => array( 'label' => 'Short-form video', 'default' => 150, 'unit' => 'per post/month' ),
				'business_blog_post'            => array( 'label' => 'Website blog post', 'default' => 150, 'unit' => 'per post/month' ),
				'business_newsletter'           => array( 'label' => 'Newsletter', 'default' => 150, 'unit' => 'per newsletter/month' ),
				'business_website_copy_hour'    => array( 'label' => 'Website copy', 'default' => 100, 'unit' => 'per hour' ),
				'business_ea_hour'              => array( 'label' => 'Executive Assistant', 'default' => 60, 'unit' => 'per hour' ),
				'business_pa_hour'              => array( 'label' => 'Personal Assistant', 'default' => 80, 'unit' => 'per hour' ),
				'business_va_hour'              => array( 'label' => 'Virtual Assistant', 'default' => 50, 'unit' => 'per hour' ),
				'business_customer_service_hour'=> array( 'label' => 'Customer service support', 'default' => 60, 'unit' => 'per hour' ),
				'business_consultancy_hour'     => array( 'label' => 'Strategy and consultancy', 'default' => 120, 'unit' => 'per hour' ),
				'business_graphic_design_hour'  => array( 'label' => 'Graphic design', 'default' => 75, 'unit' => 'per hour' ),
				'business_ugc_client_day'       => array( 'label' => 'UGC day at client site', 'default' => 500, 'unit' => 'per day' ),
				'business_ugc_venue_day'        => array( 'label' => 'UGC day with Inkfire venue', 'default' => 2500, 'unit' => 'per day' ),
			),
		),
	);
}

/**
 * @return array<string,float>
 */
function foundation_get_default_pricing_catalog() {
	$catalog = array();
	foreach ( foundation_get_pricing_admin_sections() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			$catalog[ $key ] = round( (float) $field['default'], 2 );
		}
	}
	return $catalog;
}

/**
 * @param mixed $input Raw catalogue input.
 * @return array<string,float>
 */
function foundation_sanitize_pricing_catalog( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$defaults = foundation_get_default_pricing_catalog();
	$output   = array();

	foreach ( $defaults as $key => $default ) {
		$value = array_key_exists( $key, $input ) ? $input[ $key ] : $default;
		$value = is_numeric( $value ) ? (float) $value : (float) $default;
		$output[ $key ] = round( max( 0, min( 1000000, $value ) ), 2 );
	}

	return $output;
}

function foundation_register_default_pricing_catalog() {
	$current = get_option( 'foundation_pricing_catalog', null );
	if ( null === $current ) {
		add_option( 'foundation_pricing_catalog', foundation_get_default_pricing_catalog(), '', false );
		return;
	}

	$sanitised = foundation_sanitize_pricing_catalog( $current );
	if ( $sanitised !== $current ) {
		update_option( 'foundation_pricing_catalog', $sanitised, false );
	}
}

/**
 * @return array<string,float>
 */
function foundation_get_pricing_catalog() {
	return foundation_sanitize_pricing_catalog( get_option( 'foundation_pricing_catalog', array() ) );
}

/**
 * Utility for blueprint option creation.
 */
function foundation_blueprint_option( $label, $value, $extra = array() ) {
	return array_merge(
		array(
			'label' => $label,
			'value' => $value,
			'price' => 0,
		),
		$extra
	);
}

/**
 * Bundled flow compiled from Mali's 5 August 2026 pricing board.
 *
 * Where the source board does not define a reliable formula, the blueprint records
 * a manual-review outcome instead of inventing a price.
 *
 * @return array<int,array<string,mixed>>
 */
function foundation_get_inkfire_pricing_blueprint() {
	$manual_general = 'A tailored quote is required because scope, business size or delivery details affect the price.';

	return array(
		array(
			'id'       => 'start_routes',
			'title'    => 'Choose what you need',
			'subtitle' => 'Choose one or more areas. You can combine web, tech and business support in one planning estimate.',
			'fields'   => array(
				array(
					'id'             => 'route_selection',
					'type'           => 'service_card',
					'role'           => 'services_main',
					'variant'        => 'services',
					'selection_mode' => 'multi',
					'label'          => 'What do you need help with?',
					'helper'         => 'Select every area that applies.',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Web & Accessibility', 'web', array( 'route_step_ids' => array( 'web_support_type' ) ) ),
						foundation_blueprint_option( 'Tech & Support', 'tech', array( 'route_step_ids' => array( 'tech_support_type' ) ) ),
						foundation_blueprint_option( 'Business Support & Marketing', 'business', array( 'route_step_ids' => array( 'business_support_type' ) ) ),
					),
				),
			),
		),

		// Web and accessibility route.
		array(
			'id'             => 'web_support_type',
			'title'          => 'Web & Accessibility',
			'subtitle'       => 'Tell us what kind of website support you need.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'web_project_type',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'What type of website support do you need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'A new small/basic website', 'small', array( 'route_step_ids' => array( 'web_small_build', 'web_addons' ) ) ),
						foundation_blueprint_option( 'A new bespoke website', 'bespoke', array( 'route_step_ids' => array( 'web_bespoke_build', 'web_addons' ) ) ),
						foundation_blueprint_option( 'Improvements to an existing website', 'existing', array( 'route_step_ids' => array( 'web_existing' ) ) ),
					),
				),
			),
		),
		array(
			'id'             => 'web_small_build',
			'title'          => 'Small/basic website',
			'subtitle'       => 'The planning estimate includes the base platform plus development for each page.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'                 => 'web_small_pages',
					'type'               => 'number_input',
					'label'              => 'How many pages do you need?',
					'helper'             => 'Example: 5 pages = £500 platform + 5 × £450 development.',
					'required'           => true,
					'min'                => 1,
					'max'                => 50,
					'step'               => 1,
					'unit'               => 'pages',
					'base_price_key'     => 'web_platform_fee',
					'price_per_unit_key' => 'web_small_per_page',
					'billing'            => 'one_off',
					'line_item_label'    => 'Small/basic website',
				),
			),
		),
		array(
			'id'             => 'web_bespoke_build',
			'title'          => 'Bespoke website',
			'subtitle'       => 'The planning estimate includes platform, bespoke design and bespoke development.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'                 => 'web_bespoke_pages',
					'type'               => 'number_input',
					'label'              => 'How many pages do you need?',
					'helper'             => 'Example: 5 pages = £500 platform + 5 × (£300 design + £600 development).',
					'required'           => true,
					'min'                => 1,
					'max'                => 50,
					'step'               => 1,
					'unit'               => 'pages',
					'base_price_key'     => 'web_platform_fee',
					'price_per_unit_keys'=> array( 'web_bespoke_design_per_page', 'web_bespoke_development_per_page' ),
					'billing'            => 'one_off',
					'line_item_label'    => 'Bespoke website',
				),
			),
		),
		array(
			'id'             => 'web_existing',
			'title'          => 'Existing website improvements',
			'subtitle'       => 'We need a little context before we can price this accurately.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'          => 'web_existing_url',
					'type'        => 'text_input',
					'label'       => 'What is your website URL?',
					'placeholder' => 'https://',
				),
				array(
					'id'          => 'web_existing_changes',
					'type'        => 'rich_text',
					'label'       => 'What changes are you looking to make?',
					'placeholder' => 'Tell us what is not working and what you would like to improve.',
					'required'    => true,
				),
				array(
					'id'                => 'web_existing_manual',
					'type'              => 'calculation',
					'line_item_label'   => 'Existing website improvements',
					'pricing_type'      => 'manual',
					'manual_note'       => 'Discovery is required before existing website improvements can be priced.',
				),
			),
		),
		array(
			'id'             => 'web_addons',
			'title'          => 'Website options',
			'subtitle'       => 'Add any services that should sit alongside the website work.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'           => 'web_shop',
					'type'         => 'toggle',
					'label'        => 'Do you need an online shop?',
					'yes_label'    => 'Yes, add WooCommerce',
					'no_label'     => 'No',
					'price_key'    => 'web_woocommerce',
					'billing'      => 'one_off',
					'line_item_label' => 'WooCommerce online shop',
				),
				array(
					'id'             => 'web_hosting',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'Do you need hosting and maintenance?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'No hosting or maintenance', 'none' ),
						foundation_blueprint_option( 'Hosting only', 'hosting', array( 'price_key' => 'web_host_only', 'billing' => 'monthly', 'line_item_label' => 'Hosting only' ) ),
						foundation_blueprint_option( 'Hosting + plugins', 'plugins', array( 'price_key' => 'web_host_plugins', 'billing' => 'monthly', 'line_item_label' => 'Hosting + plugins' ) ),
						foundation_blueprint_option( 'Hosting + 1 hour editing', 'edit_1', array( 'price_key' => 'web_host_edit_1', 'billing' => 'monthly', 'line_item_label' => 'Hosting + 1 hour editing' ) ),
						foundation_blueprint_option( 'Hosting + 4 hours editing', 'edit_4', array( 'price_key' => 'web_host_edit_4', 'billing' => 'monthly', 'line_item_label' => 'Hosting + 4 hours editing' ) ),
					),
				),
				array(
					'id'             => 'web_alt_text',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'Do you need alt text support for images?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'No alt text support', 'none' ),
						foundation_blueprint_option( 'Yes, up to 25 images', '25', array( 'unit_price_key' => 'web_alt_text_25', 'quantity_min' => 1, 'quantity_max' => 1, 'billing' => 'one_off', 'line_item_label' => 'Alt text support (up to 25 images)' ) ),
						foundation_blueprint_option( 'Yes, 26-50 images', '50', array( 'unit_price_key' => 'web_alt_text_25', 'quantity_min' => 2, 'quantity_max' => 2, 'billing' => 'one_off', 'line_item_label' => 'Alt text support (26-50 images)' ) ),
						foundation_blueprint_option( 'Yes, 51-75 images', '75', array( 'unit_price_key' => 'web_alt_text_25', 'quantity_min' => 3, 'quantity_max' => 3, 'billing' => 'one_off', 'line_item_label' => 'Alt text support (51-75 images)' ) ),
						foundation_blueprint_option( 'Yes, 76+ images', '76_plus', array( 'pricing_type' => 'manual', 'manual_note' => 'Alt text support for 76+ images needs a manual review.' ) ),
					),
				),
				array(
					'id'              => 'web_accessibility_testing',
					'type'            => 'toggle',
					'label'           => 'Do you need accessibility testing?',
					'yes_label'       => 'Yes',
					'no_label'        => 'No',
					'price_key'       => 'web_accessibility_testing',
					'billing'         => 'one_off',
					'line_item_label' => 'Accessibility testing',
				),
				array(
					'id'              => 'web_accessibility_setup',
					'type'            => 'toggle',
					'label'           => 'Do you need accessibility setup?',
					'yes_label'       => 'Yes',
					'no_label'        => 'No',
					'price_key'       => 'web_accessibility_setup',
					'billing'         => 'one_off',
					'line_item_label' => 'Accessibility setup',
				),
			),
		),

		// Tech route.
		array(
			'id'             => 'tech_support_type',
			'title'          => 'Tech & Support',
			'subtitle'       => 'Choose the type of technical support you need.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'tech_project_type',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'What kind of tech support do you need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Ongoing IT support', 'ongoing', array( 'route_step_ids' => array( 'tech_ongoing' ) ) ),
						foundation_blueprint_option( 'Occasional IT help', 'occasional', array( 'route_step_ids' => array( 'tech_occasional' ) ) ),
						foundation_blueprint_option( 'Microsoft setup & management', 'microsoft', array( 'route_step_ids' => array( 'tech_microsoft' ) ) ),
						foundation_blueprint_option( 'Cyber Essentials support', 'cyber', array( 'route_step_ids' => array( 'tech_cyber' ) ) ),
						foundation_blueprint_option( 'Accessibility-focused tech support', 'accessibility', array( 'route_step_ids' => array( 'tech_accessibility' ) ) ),
						foundation_blueprint_option( 'Not sure yet', 'not_sure', array( 'route_step_ids' => array( 'tech_not_sure' ) ) ),
					),
				),
			),
		),
		array(
			'id'             => 'tech_ongoing',
			'title'          => 'Ongoing IT support',
			'subtitle'       => 'Tell us the size of the team and device estate.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'                 => 'tech_users',
					'type'               => 'number_input',
					'label'              => 'How many team members require support?',
					'required'           => true,
					'min'                => 1,
					'max'                => 500,
					'unit'               => 'users',
					'price_per_unit_key' => 'tech_user_support',
					'billing'            => 'monthly',
					'line_item_label'    => 'Ongoing IT support',
				),
				array(
					'id'                 => 'tech_devices',
					'type'               => 'number_input',
					'label'              => 'How many devices require management?',
					'required'           => true,
					'min'                => 0,
					'max'                => 1000,
					'unit'               => 'devices',
					'price_per_unit_key' => 'tech_device_management',
					'billing'            => 'monthly',
					'line_item_label'    => 'Device management',
				),
				array(
					'id'             => 'tech_ongoing_needs',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'Do you require any of the following?',
					'options'        => array(
						foundation_blueprint_option( 'Microsoft 365 support', 'm365' ),
						foundation_blueprint_option( 'Email setup & management', 'email' ),
						foundation_blueprint_option( 'Cloud backups', 'backups' ),
						foundation_blueprint_option( 'Cybersecurity/antivirus', 'security' ),
						foundation_blueprint_option( 'Remote support', 'remote' ),
						foundation_blueprint_option( 'Accessibility-focused support', 'accessibility' ),
					),
				),
				array(
					'id'                 => 'tech_dark_web_toggle',
					'type'               => 'toggle',
					'label'              => 'Do you require dark web monitoring?',
					'yes_label'          => 'Yes',
					'no_label'           => 'No',
					'yes_route_step_ids' => array( 'tech_dark_web' ),
				),
			),
		),
		array(
			'id'             => 'tech_dark_web',
			'title'          => 'Dark web monitoring',
			'subtitle'       => 'Monitoring is priced per domain each month.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'                 => 'tech_dark_web_domains',
					'type'               => 'number_input',
					'label'              => 'How many domains need monitoring?',
					'required'           => true,
					'min'                => 1,
					'max'                => 100,
					'unit'               => 'domains',
					'price_per_unit_key' => 'tech_dark_web_domain',
					'billing'            => 'monthly',
					'line_item_label'    => 'Dark web monitoring',
				),
			),
		),
		array(
			'id'             => 'tech_occasional',
			'title'          => 'Occasional IT help',
			'subtitle'       => 'A few hours can be estimated now. Projects need discovery.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'tech_occasional_size',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'Roughly how much support do you think you’ll need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'A few hours', 'hours', array( 'route_step_ids' => array( 'tech_occasional_hours' ) ) ),
						foundation_blueprint_option( 'A small project', 'small_project', array( 'pricing_type' => 'manual', 'manual_note' => 'A small IT project needs a scoped starting estimate.' ) ),
						foundation_blueprint_option( 'A larger project', 'large_project', array( 'pricing_type' => 'manual', 'manual_note' => 'A larger IT project needs a discovery call.' ) ),
						foundation_blueprint_option( 'Not sure yet', 'not_sure', array( 'pricing_type' => 'manual', 'manual_note' => 'Inkfire will help define the right level of IT support.' ) ),
					),
				),
			),
		),
		array(
			'id'             => 'tech_occasional_hours',
			'title'          => 'IT consulting hours',
			'subtitle'       => 'Choose the number of hours to include in the planning estimate.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'                 => 'tech_consulting_hours',
					'type'               => 'number_input',
					'label'              => 'How many hours should we estimate?',
					'required'           => true,
					'min'                => 1,
					'max'                => 80,
					'unit'               => 'hours',
					'price_per_unit_key' => 'tech_consulting_hour',
					'billing'            => 'one_off',
					'line_item_label'    => 'IT consulting',
				),
			),
		),
		array(
			'id'             => 'tech_microsoft',
			'title'          => 'Microsoft setup & management',
			'subtitle'       => 'Choose each platform that needs configuring, then add ongoing Intune management.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'tech_microsoft_systems',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'Which systems require setup?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Windows devices', 'windows', array( 'price_key' => 'tech_windows_configuration', 'billing' => 'one_off', 'line_item_label' => 'Windows configuration' ) ),
						foundation_blueprint_option( 'iOS devices', 'ios', array( 'price_key' => 'tech_ios_configuration', 'billing' => 'one_off', 'line_item_label' => 'iOS configuration' ) ),
						foundation_blueprint_option( 'Android devices', 'android', array( 'price_key' => 'tech_android_configuration', 'billing' => 'one_off', 'line_item_label' => 'Android configuration' ) ),
					),
				),
				array(
					'id'                 => 'tech_intune_devices',
					'type'               => 'number_input',
					'label'              => 'How many devices require ongoing Intune management?',
					'helper'             => 'Enter 0 if ongoing Intune management is not required.',
					'required'           => true,
					'min'                => 0,
					'max'                => 1000,
					'unit'               => 'devices',
					'price_per_unit_key' => 'tech_intune_device',
					'billing'            => 'monthly',
					'line_item_label'    => 'Intune device management',
				),
			),
		),
		array(
			'id'             => 'tech_cyber',
			'title'          => 'Cyber Essentials support',
			'subtitle'       => 'Cyber Essentials projects vary with business size and the current setup.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'tech_cyber_level',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'What level of support do you need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Preparation support', 'preparation', array( 'pricing_type' => 'manual', 'manual_note' => 'Cyber Essentials preparation needs discovery.' ) ),
						foundation_blueprint_option( 'Full guided support', 'guided', array( 'pricing_type' => 'manual', 'manual_note' => 'Full guided Cyber Essentials support needs discovery.' ) ),
						foundation_blueprint_option( 'Remediation help', 'remediation', array( 'pricing_type' => 'manual', 'manual_note' => 'Cyber Essentials remediation needs discovery.' ) ),
						foundation_blueprint_option( 'Not sure yet', 'not_sure', array( 'pricing_type' => 'manual', 'manual_note' => 'Inkfire will identify the appropriate Cyber Essentials route.' ) ),
					),
				),
			),
		),
		array(
			'id'             => 'tech_accessibility',
			'title'          => 'Accessibility-focused tech support',
			'subtitle'       => 'Choose the support areas and an estimated number of hours.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'tech_accessibility_needs',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'What do you need support with?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Accessible device setup', 'device_setup' ),
						foundation_blueprint_option( 'Accessibility software support', 'software' ),
						foundation_blueprint_option( 'Workplace accessibility support', 'workplace' ),
						foundation_blueprint_option( 'General accessibility guidance', 'guidance' ),
					),
				),
				array(
					'id'                 => 'tech_accessibility_hours',
					'type'               => 'number_input',
					'label'              => 'How many hours should we estimate?',
					'required'           => true,
					'min'                => 1,
					'max'                => 80,
					'unit'               => 'hours',
					'price_per_unit_key' => 'tech_accessibility_hour',
					'billing'            => 'one_off',
					'line_item_label'    => 'Accessibility-focused tech support',
				),
			),
		),
		array(
			'id'             => 'tech_not_sure',
			'title'          => 'Not sure what tech support you need?',
			'subtitle'       => 'Describe the problem and Inkfire will recommend the right route.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'          => 'tech_not_sure_details',
					'type'        => 'rich_text',
					'label'       => 'What is happening at the moment?',
					'required'    => true,
					'placeholder' => 'Tell us about the people, devices, systems and the main problem.',
				),
				array(
					'id'              => 'tech_not_sure_manual',
					'type'            => 'calculation',
					'line_item_label' => 'Tech support discovery',
					'pricing_type'    => 'manual',
					'manual_note'     => $manual_general,
				),
			),
		),

		// Business and marketing route.
		array(
			'id'             => 'business_support_type',
			'title'          => 'Business Support & Marketing',
			'subtitle'       => 'Choose one or more services. Each selected service opens only the questions it needs.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_project_type',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'What kind of business support do you need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Branding', 'branding', array( 'route_step_ids' => array( 'business_branding' ) ) ),
						foundation_blueprint_option( 'Social media content', 'social', array( 'route_step_ids' => array( 'business_social' ) ) ),
						foundation_blueprint_option( 'Blog content', 'blog', array( 'route_step_ids' => array( 'business_blog' ) ) ),
						foundation_blueprint_option( 'Website copy & content', 'website_copy', array( 'route_step_ids' => array( 'business_website_copy' ) ) ),
						foundation_blueprint_option( 'Newsletters', 'newsletters', array( 'route_step_ids' => array( 'business_newsletters' ) ) ),
						foundation_blueprint_option( 'Executive, PA, VA or customer service support', 'general_support', array( 'route_step_ids' => array( 'business_general_support' ) ) ),
						foundation_blueprint_option( 'Strategy, consultancy or training', 'strategy', array( 'route_step_ids' => array( 'business_strategy' ) ) ),
						foundation_blueprint_option( 'Marketing support', 'marketing', array( 'route_step_ids' => array( 'business_marketing' ) ) ),
					),
				),
			),
		),
		array(
			'id'             => 'business_branding',
			'title'          => 'Branding',
			'subtitle'       => 'Choose a core package, then any tailored add-ons.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_branding_package',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'Which branding package do you need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Mini branding package', 'mini', array( 'price_key' => 'business_branding_mini', 'billing' => 'one_off', 'line_item_label' => 'Mini branding package' ) ),
						foundation_blueprint_option( 'Major branding package', 'major', array( 'price_key' => 'business_branding_major', 'billing' => 'one_off', 'line_item_label' => 'Major branding package' ) ),
						foundation_blueprint_option( 'Not sure - show both options', 'not_sure', array( 'pricing_type' => 'manual', 'manual_note' => 'Inkfire will help choose between the Mini and Major branding packages.' ) ),
					),
				),
				array(
					'id'             => 'business_branding_addons',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'Do you also need any tailored brand assets?',
					'helper'         => 'These are scoped separately because the board lists their prices as variable.',
					'options'        => array(
						foundation_blueprint_option( 'Brand guidelines', 'guidelines', array( 'pricing_type' => 'manual', 'manual_note' => 'Additional brand guidelines need scoping.' ) ),
						foundation_blueprint_option( 'Social templates', 'social_templates', array( 'pricing_type' => 'manual', 'manual_note' => 'Additional social templates need scoping.' ) ),
						foundation_blueprint_option( 'Business cards', 'business_cards', array( 'pricing_type' => 'manual', 'manual_note' => 'Business card design needs scoping.' ) ),
						foundation_blueprint_option( 'Presentation templates', 'presentation_templates', array( 'pricing_type' => 'manual', 'manual_note' => 'Presentation templates need scoping.' ) ),
						foundation_blueprint_option( 'Email signatures', 'email_signatures', array( 'pricing_type' => 'manual', 'manual_note' => 'Email signatures need scoping.' ) ),
					),
				),
			),
		),
		array(
			'id'             => 'business_social',
			'title'          => 'Social media content',
			'subtitle'       => 'Choose the service mix, monthly volume and main content type.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_social_support',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'What kind of social media support do you need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Graphics & branded posts', 'graphics' ),
						foundation_blueprint_option( 'Captions & copywriting', 'copy' ),
						foundation_blueprint_option( 'Short-form video editing', 'video_editing' ),
						foundation_blueprint_option( 'Content planning', 'planning' ),
						foundation_blueprint_option( 'Scheduling & posting', 'scheduling' ),
						foundation_blueprint_option( 'Community management', 'community', array( 'pricing_type' => 'manual', 'manual_note' => 'Community management requires a tailored scope.' ) ),
						foundation_blueprint_option( 'Analytics & reporting', 'analytics', array( 'pricing_type' => 'manual', 'manual_note' => 'Analytics and reporting requires a tailored scope.' ) ),
						foundation_blueprint_option( 'Ad management', 'ads', array( 'pricing_type' => 'manual', 'manual_note' => 'Paid ad management requires discovery.' ) ),
						foundation_blueprint_option( 'Photography/content days', 'photography', array( 'pricing_type' => 'manual', 'manual_note' => 'Photography/content days require scheduling and scope.' ) ),
						foundation_blueprint_option( 'Not sure yet', 'not_sure', array( 'pricing_type' => 'manual', 'manual_note' => 'Inkfire will recommend the right social support mix.' ) ),
					),
				),
				array(
					'id'                => 'business_social_volume',
					'type'              => 'service_card',
					'selection_mode'    => 'single',
					'pricing_component' => true,
					'label'             => 'How much content do you need?',
					'required'          => true,
					'options'           => array(
						foundation_blueprint_option( 'A few posts each month (estimate 4)', 'few', array( 'quantity_min' => 4, 'quantity_max' => 4, 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Weekly content (estimate 8)', 'weekly', array( 'quantity_min' => 8, 'quantity_max' => 8, 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Multiple posts per week (estimate 12-16)', 'multiple', array( 'quantity_min' => 12, 'quantity_max' => 16, 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Daily content', 'daily', array( 'pricing_type' => 'manual', 'manual_note' => 'Daily social content needs a discovery call.', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Not sure yet', 'not_sure', array( 'pricing_type' => 'manual', 'manual_note' => 'Social content volume needs discovery.', 'pricing_component' => true ) ),
					),
				),
				array(
					'id'                => 'business_social_content_type',
					'type'              => 'service_card',
					'selection_mode'    => 'single',
					'pricing_component' => true,
					'label'             => 'What type of content would you like?',
					'required'          => true,
					'options'           => array(
						foundation_blueprint_option( 'Static posts', 'static', array( 'unit_price_key' => 'business_social_static', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Carousel posts', 'carousel', array( 'unit_price_key' => 'business_social_carousel', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Reels/short videos', 'video', array( 'unit_price_key' => 'business_social_video', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Story graphics', 'stories', array( 'pricing_type' => 'manual', 'manual_note' => 'The source board does not define a standalone story-graphics rate, so this needs a tailored content mix.', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Mixed content', 'mixed', array( 'pricing_type' => 'manual', 'manual_note' => 'Mixed social content needs the exact content mix before pricing.', 'pricing_component' => true ) ),
					),
				),
				array(
					'id'                      => 'business_social_calculation',
					'type'                    => 'calculation',
					'line_item_label'         => 'Social media content',
					'unit_source_field_id'    => 'business_social_content_type',
					'quantity_source_field_id'=> 'business_social_volume',
					'billing'                 => 'monthly',
				),
			),
		),
		array(
			'id'             => 'business_blog',
			'title'          => 'Blog content',
			'subtitle'       => 'Blog content is priced per post each month.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_blog_count',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'How many blog posts do you need each month?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( '1 post', '1', array( 'unit_price_key' => 'business_blog_post', 'quantity_min' => 1, 'quantity_max' => 1, 'billing' => 'monthly', 'line_item_label' => 'Blog content (1 post/month)' ) ),
						foundation_blueprint_option( '2 posts', '2', array( 'unit_price_key' => 'business_blog_post', 'quantity_min' => 2, 'quantity_max' => 2, 'billing' => 'monthly', 'line_item_label' => 'Blog content (2 posts/month)' ) ),
						foundation_blueprint_option( '4 posts', '4', array( 'unit_price_key' => 'business_blog_post', 'quantity_min' => 4, 'quantity_max' => 4, 'billing' => 'monthly', 'line_item_label' => 'Blog content (4 posts/month)' ) ),
						foundation_blueprint_option( '8+ posts', '8_plus', array( 'pricing_type' => 'manual', 'manual_note' => 'Eight or more blog posts per month needs a tailored content plan.' ) ),
					),
				),
			),
		),
		array(
			'id'             => 'business_website_copy',
			'title'          => 'Website copy & content',
			'subtitle'       => 'Choose the support type and a realistic hour range.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_copy_type',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'What content support do you need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Website copywriting', 'copywriting' ),
						foundation_blueprint_option( 'Editing existing content', 'editing' ),
						foundation_blueprint_option( 'Accessibility-friendly rewriting', 'accessible_rewrite' ),
						foundation_blueprint_option( 'Product descriptions', 'products' ),
						foundation_blueprint_option( 'General content support', 'general' ),
					),
				),
				array(
					'id'             => 'business_copy_hours',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'Roughly how many hours should we estimate?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( '1-3 hours', '1_3', array( 'unit_price_key' => 'business_website_copy_hour', 'quantity_min' => 1, 'quantity_max' => 3, 'billing' => 'one_off', 'line_item_label' => 'Website copy (1-3 hours)' ) ),
						foundation_blueprint_option( '4-8 hours', '4_8', array( 'unit_price_key' => 'business_website_copy_hour', 'quantity_min' => 4, 'quantity_max' => 8, 'billing' => 'one_off', 'line_item_label' => 'Website copy (4-8 hours)' ) ),
						foundation_blueprint_option( '8-16 hours', '8_16', array( 'unit_price_key' => 'business_website_copy_hour', 'quantity_min' => 8, 'quantity_max' => 16, 'billing' => 'one_off', 'line_item_label' => 'Website copy (8-16 hours)' ) ),
						foundation_blueprint_option( '16+ hours', '16_plus', array( 'pricing_type' => 'manual', 'manual_note' => 'Website copy requiring 16+ hours needs a scoped quote.' ) ),
					),
				),
			),
		),
		array(
			'id'             => 'business_newsletters',
			'title'          => 'Newsletters',
			'subtitle'       => 'Choose the monthly newsletter frequency.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_newsletter_count',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'How many newsletters do you need each month?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( '1 newsletter', '1', array( 'unit_price_key' => 'business_newsletter', 'quantity_min' => 1, 'quantity_max' => 1, 'billing' => 'monthly', 'line_item_label' => 'Newsletters (1/month)' ) ),
						foundation_blueprint_option( '2 newsletters', '2', array( 'unit_price_key' => 'business_newsletter', 'quantity_min' => 2, 'quantity_max' => 2, 'billing' => 'monthly', 'line_item_label' => 'Newsletters (2/month)' ) ),
						foundation_blueprint_option( '4 newsletters', '4', array( 'unit_price_key' => 'business_newsletter', 'quantity_min' => 4, 'quantity_max' => 4, 'billing' => 'monthly', 'line_item_label' => 'Newsletters (4/month)' ) ),
						foundation_blueprint_option( 'Weekly', 'weekly', array( 'unit_price_key' => 'business_newsletter', 'quantity_min' => 4, 'quantity_max' => 5, 'billing' => 'monthly', 'line_item_label' => 'Weekly newsletters' ) ),
					),
				),
			),
		),
		array(
			'id'             => 'business_general_support',
			'title'          => 'Executive and business support',
			'subtitle'       => 'Access to Work-funded support follows a tailored quote route. Other support can be estimated by role and hours.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_access_to_work',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'Do you have Access to Work funding?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Yes - contact us for a funded quote', 'yes', array( 'route_step_ids' => array( 'business_access_to_work_manual' ) ) ),
						foundation_blueprint_option( 'No', 'no', array( 'route_step_ids' => array( 'business_general_support_pricing' ) ) ),
					),
				),
			),
		),
		array(
			'id'             => 'business_access_to_work_manual',
			'title'          => 'Access to Work-funded support',
			'subtitle'       => 'Funding arrangements need a tailored proposal rather than an automatic total.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'              => 'business_access_to_work_manual_item',
					'type'            => 'calculation',
					'line_item_label' => 'Access to Work-funded support',
					'pricing_type'    => 'manual',
					'manual_note'     => 'Access to Work-funded support requires a tailored quote.',
				),
			),
		),
		array(
			'id'             => 'business_general_support_pricing',
			'title'          => 'Business support hours',
			'subtitle'       => 'Choose the role and the number of support hours needed each month.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'                => 'business_support_role',
					'type'              => 'service_card',
					'selection_mode'    => 'single',
					'pricing_component' => true,
					'label'             => 'What type of support are you looking for?',
					'required'          => true,
					'options'           => array(
						foundation_blueprint_option( 'Executive Assistant / specialist support', 'ea', array( 'unit_price_key' => 'business_ea_hour', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Personal Assistant / one-to-one support', 'pa', array( 'unit_price_key' => 'business_pa_hour', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Virtual Assistant / general business admin', 'va', array( 'unit_price_key' => 'business_va_hour', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Customer service support', 'customer_service', array( 'unit_price_key' => 'business_customer_service_hour', 'pricing_component' => true ) ),
					),
				),
				array(
					'id'                => 'business_support_hours',
					'type'              => 'service_card',
					'selection_mode'    => 'single',
					'pricing_component' => true,
					'label'             => 'How much support do you need each month?',
					'required'          => true,
					'options'           => array(
						foundation_blueprint_option( '5 hours', '5', array( 'quantity_min' => 5, 'quantity_max' => 5, 'pricing_component' => true ) ),
						foundation_blueprint_option( '10 hours', '10', array( 'quantity_min' => 10, 'quantity_max' => 10, 'pricing_component' => true ) ),
						foundation_blueprint_option( '20 hours', '20', array( 'quantity_min' => 20, 'quantity_max' => 20, 'pricing_component' => true ) ),
						foundation_blueprint_option( '40+ hours', '40_plus', array( 'pricing_type' => 'manual', 'manual_note' => 'Forty or more support hours needs a tailored monthly arrangement.', 'pricing_component' => true ) ),
					),
				),
				array(
					'id'                      => 'business_support_calculation',
					'type'                    => 'calculation',
					'line_item_label'         => 'Monthly business support',
					'unit_source_field_id'    => 'business_support_role',
					'quantity_source_field_id'=> 'business_support_hours',
					'billing'                 => 'monthly',
				),
			),
		),
		array(
			'id'             => 'business_strategy',
			'title'          => 'Strategy, consultancy and training',
			'subtitle'       => 'Choose the focus, delivery model and a planning number of hours.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_strategy_focus',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'What kind of support do you need?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Business strategy and mentoring', 'strategy' ),
						foundation_blueprint_option( 'Accessibility consultancy', 'accessibility' ),
						foundation_blueprint_option( 'Digital transformation', 'digital' ),
						foundation_blueprint_option( 'Systems/process improvement', 'systems' ),
						foundation_blueprint_option( 'Growth strategy', 'growth' ),
						foundation_blueprint_option( 'Training/workshops', 'training' ),
					),
				),
				array(
					'id'                => 'business_strategy_delivery',
					'type'              => 'service_card',
					'selection_mode'    => 'single',
					'pricing_component' => true,
					'label'             => 'How would you like support delivered?',
					'required'          => true,
					'options'           => array(
						foundation_blueprint_option( 'One-off consultancy', 'one_off', array( 'billing_override' => 'one_off', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Monthly consultancy', 'monthly', array( 'billing_override' => 'monthly', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Workshop/training', 'workshop', array( 'billing_override' => 'one_off', 'pricing_component' => true ) ),
						foundation_blueprint_option( 'Ongoing advisory support', 'ongoing', array( 'billing_override' => 'monthly', 'pricing_component' => true ) ),
					),
				),
				array(
					'id'                 => 'business_strategy_hours',
					'type'               => 'number_input',
					'label'              => 'How many hours should we use for the planning estimate?',
					'required'           => true,
					'min'                => 1,
					'max'                => 80,
					'unit'               => 'hours',
					'price_per_unit_key' => 'business_consultancy_hour',
					'billing_from_field' => 'business_strategy_delivery',
					'billing'            => 'one_off',
					'line_item_label'    => 'Strategy and consultancy',
				),
			),
		),
		array(
			'id'             => 'business_marketing',
			'title'          => 'Marketing support',
			'subtitle'       => 'Choose each marketing service that applies.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_marketing_services',
					'type'           => 'service_card',
					'selection_mode' => 'multi',
					'label'          => 'What marketing support are you interested in?',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'Graphic design', 'graphic_design', array( 'route_step_ids' => array( 'business_graphic_design' ) ) ),
						foundation_blueprint_option( 'Paid ads', 'paid_ads', array( 'pricing_type' => 'manual', 'manual_note' => 'Paid ads require a discovery call and media budget discussion.' ) ),
						foundation_blueprint_option( 'UGC creation', 'ugc', array( 'route_step_ids' => array( 'business_ugc' ) ) ),
						foundation_blueprint_option( 'Illustration', 'illustration', array( 'pricing_type' => 'manual', 'manual_note' => 'Illustration requires a tailored creative brief.' ) ),
						foundation_blueprint_option( 'Print & packaging', 'print_packaging', array( 'pricing_type' => 'manual', 'manual_note' => 'Print and packaging work requires a tailored brief.' ) ),
						foundation_blueprint_option( 'Website hosting', 'hosting', array( 'price_key' => 'web_host_standalone', 'billing' => 'monthly', 'line_item_label' => 'Website hosting' ) ),
					),
				),
			),
		),
		array(
			'id'             => 'business_graphic_design',
			'title'          => 'Graphic design',
			'subtitle'       => 'Use an estimated number of hours for the planning range.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'                 => 'business_graphic_design_hours',
					'type'               => 'number_input',
					'label'              => 'How many hours should we estimate?',
					'required'           => true,
					'min'                => 1,
					'max'                => 100,
					'unit'               => 'hours',
					'price_per_unit_key' => 'business_graphic_design_hour',
					'billing'            => 'one_off',
					'line_item_label'    => 'Graphic design',
				),
			),
		),
		array(
			'id'             => 'business_ugc',
			'title'          => 'UGC creation',
			'subtitle'       => 'Choose whether the content day takes place at your premises or an Inkfire-provided venue.',
			'is_conditional' => true,
			'fields'         => array(
				array(
					'id'             => 'business_ugc_location',
					'type'           => 'service_card',
					'selection_mode' => 'single',
					'label'          => 'Where will the content day take place?',
					'helper'         => 'Travel is not included in the client-site rate.',
					'required'       => true,
					'options'        => array(
						foundation_blueprint_option( 'At your premises', 'client_site', array( 'price_key' => 'business_ugc_client_day', 'billing' => 'one_off', 'line_item_label' => 'UGC day - client site' ) ),
						foundation_blueprint_option( 'Inkfire provides the venue', 'inkfire_venue', array( 'price_key' => 'business_ugc_venue_day', 'billing' => 'one_off', 'line_item_label' => 'UGC day - Inkfire venue' ) ),
					),
				),
			),
		),
	);
}

/**
 * Back up and install the bundled Inkfire blueprint.
 *
 * @param bool $make_backup Whether to back up the current flow first.
 * @return array<string,mixed>
 */
function foundation_apply_inkfire_blueprint( $make_backup = true ) {
	$current = get_option( 'foundation_form_data', array() );
	if ( $make_backup && is_array( $current ) && ! empty( $current ) ) {
		$backup = array(
			'created_at' => current_time( 'mysql' ),
			'version'    => get_option( 'foundation_blueprint_version', '' ),
			'form_data'  => $current,
		);
		update_option( 'foundation_form_data_backup', $backup, false );
	}

	$blueprint = foundation_normalize_form_data( foundation_get_inkfire_pricing_blueprint() );
	update_option( 'foundation_form_data', $blueprint, false );
	update_option( 'foundation_blueprint_version', foundation_get_blueprint_version(), false );
	update_option( 'foundation_blueprint_applied_at', current_time( 'mysql' ), false );

	return array(
		'steps'   => count( $blueprint ),
		'version' => foundation_get_blueprint_version(),
	);
}

function foundation_canonicalize_blueprint_value( $value ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}

	$keys    = array_keys( $value );
	$is_list = empty( $keys ) || $keys === range( 0, count( $keys ) - 1 );
	foreach ( $value as $key => $item ) {
		$value[ $key ] = foundation_canonicalize_blueprint_value( $item );
	}
	if ( ! $is_list ) {
		ksort( $value, SORT_STRING );
	}
	return $value;
}

function foundation_get_blueprint_fingerprint( $form_data ) {
	$normalized = foundation_normalize_form_data( is_array( $form_data ) ? $form_data : array() );
	$encoded    = wp_json_encode( foundation_canonicalize_blueprint_value( $normalized ) );
	return is_string( $encoded ) && '' !== $encoded ? hash( 'sha256', $encoded ) : '';
}

function foundation_is_inkfire_blueprint_installed() {
	if ( foundation_get_blueprint_version() !== (string) get_option( 'foundation_blueprint_version', '' ) ) {
		return false;
	}

	$expected = foundation_get_blueprint_fingerprint( foundation_get_inkfire_pricing_blueprint() );
	$current  = foundation_get_blueprint_fingerprint( get_option( 'foundation_form_data', array() ) );
	return '' !== $expected && '' !== $current && hash_equals( $expected, $current );
}

/**
 * Get selected option objects for a service-card field.
 *
 * @param array<string,mixed> $field Field definition.
 * @param array<string,mixed> $selections Sanitised selections.
 * @return array<int,array<string,mixed>>
 */
function foundation_get_selected_options( $field, $selections ) {
	$field_id = $field['id'] ?? '';
	$indexes  = $selections[ $field_id . '_options' ] ?? array();
	$indexes  = is_array( $indexes ) ? array_map( 'strval', $indexes ) : array();
	$options  = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
	$selected = array();

	foreach ( $options as $index => $option ) {
		if ( in_array( (string) $index, $indexes, true ) ) {
			$option['_index'] = $index;
			$selected[]       = $option;
		}
	}
	return $selected;
}

/**
 * Resolve all route IDs reachable from the current answers.
 *
 * Routes are only activated by fields on already-visible steps. This prevents an
 * answer left behind in a hidden branch from keeping a nested branch visible.
 *
 * @param array<int,array<string,mixed>> $steps Normalised steps.
 * @param array<string,mixed> $selections Sanitised selections.
 * @return array<int,string>
 */
function foundation_get_selected_route_ids( $steps, $selections ) {
	$route_ids = array();
	$changed   = true;
	$passes    = 0;

	while ( $changed && $passes < 20 ) {
		$changed = false;
		$passes++;
		foreach ( $steps as $step ) {
			$step_id = $step['id'] ?? '';
			if ( ! empty( $step['is_conditional'] ) && ! in_array( $step_id, $route_ids, true ) ) {
				continue;
			}

			foreach ( $step['fields'] ?? array() as $field ) {
				$type = $field['type'] ?? '';
				if ( 'service_card' === $type ) {
					foreach ( foundation_get_selected_options( $field, $selections ) as $option ) {
						$targets = $option['route_step_ids'] ?? array();
						if ( empty( $targets ) && ! empty( $option['route_step_id'] ) ) {
							$targets = array( $option['route_step_id'] );
						}
						foreach ( (array) $targets as $target ) {
							$target = sanitize_key( $target );
							if ( $target && ! in_array( $target, $route_ids, true ) ) {
								$route_ids[] = $target;
								$changed     = true;
							}
						}
					}
				}
				if ( 'toggle' === $type ) {
					$selected = $selections[ ( $field['id'] ?? '' ) . '_options' ] ?? array();
					$yes      = is_array( $selected ) && in_array( '0', array_map( 'strval', $selected ), true );
					if ( $yes ) {
						foreach ( (array) ( $field['yes_route_step_ids'] ?? array() ) as $target ) {
							$target = sanitize_key( $target );
							if ( $target && ! in_array( $target, $route_ids, true ) ) {
								$route_ids[] = $target;
								$changed     = true;
							}
						}
					}
				}
			}
		}
	}

	return $route_ids;
}

/**
 * @param array<int,array<string,mixed>> $steps Normalised steps.
 * @param array<string,mixed> $selections Sanitised selections.
 * @return array<int,array<string,mixed>>
 */
function foundation_get_visible_form_steps( $steps, $selections ) {
	$route_ids = foundation_get_selected_route_ids( $steps, $selections );
	return array_values(
		array_filter(
			$steps,
			static function ( $step ) use ( $route_ids ) {
				return empty( $step['is_conditional'] ) || in_array( $step['id'] ?? '', $route_ids, true );
			}
		)
	);
}

/**
 * Resolve a configured price by key, falling back to a literal amount.
 */
function foundation_resolve_price( $source, $catalog, $key_name = 'price_key', $literal_name = 'price' ) {
	$key = isset( $source[ $key_name ] ) ? sanitize_key( $source[ $key_name ] ) : '';
	if ( $key && array_key_exists( $key, $catalog ) ) {
		return round( (float) $catalog[ $key ], 2 );
	}
	return round( (float) ( $source[ $literal_name ] ?? 0 ), 2 );
}

function foundation_resolve_price_per_unit( $source, $catalog ) {
	$total = 0.0;
	$keys  = array();
	if ( ! empty( $source['unit_price_key'] ) ) {
		$keys[] = $source['unit_price_key'];
	}
	if ( ! empty( $source['price_per_unit_key'] ) ) {
		$keys[] = $source['price_per_unit_key'];
	}
	if ( ! empty( $source['price_per_unit_keys'] ) && is_array( $source['price_per_unit_keys'] ) ) {
		$keys = array_merge( $keys, $source['price_per_unit_keys'] );
	}
	foreach ( $keys as $key ) {
		$key = sanitize_key( $key );
		if ( $key && isset( $catalog[ $key ] ) ) {
			$total += (float) $catalog[ $key ];
		}
	}
	if ( 0.0 === $total && isset( $source['price_per_unit'] ) ) {
		$total = (float) $source['price_per_unit'];
	}
	return round( $total, 2 );
}

function foundation_quote_add_manual_item( &$quote, $label, $note ) {
	$label = sanitize_text_field( $label );
	$note  = sanitize_text_field( $note );
	$key   = strtolower( $label . '|' . $note );
	foreach ( $quote['manual_items'] as $existing ) {
		if ( strtolower( ( $existing['label'] ?? '' ) . '|' . ( $existing['note'] ?? '' ) ) === $key ) {
			return;
		}
	}
	$quote['manual_items'][] = array( 'label' => $label, 'note' => $note );
}

function foundation_quote_add_line_item( &$quote, $label, $min, $max, $billing, $meta = array() ) {
	$min     = round( max( 0, (float) $min ), 2 );
	$max     = round( max( $min, (float) $max ), 2 );
	$billing = 'monthly' === $billing ? 'monthly' : 'one_off';
	if ( $min <= 0 && $max <= 0 ) {
		return;
	}

	$quote[ $billing . '_min' ] += $min;
	$quote[ $billing . '_max' ] += $max;
	$quote['line_items'][] = array_merge(
		array(
			'label'   => sanitize_text_field( $label ),
			'min'     => $min,
			'max'     => $max,
			'billing' => $billing,
		),
		$meta
	);
}

/**
 * Get the selected option for a single-choice component field.
 */
function foundation_find_selected_option_by_field_id( $steps, $field_id, $selections ) {
	foreach ( $steps as $step ) {
		foreach ( $step['fields'] ?? array() as $field ) {
			if ( ( $field['id'] ?? '' ) === $field_id && 'service_card' === ( $field['type'] ?? '' ) ) {
				$selected = foundation_get_selected_options( $field, $selections );
				return empty( $selected ) ? null : $selected[0];
			}
		}
	}
	return null;
}

/**
 * Determine billing from a selected component option when configured.
 */
function foundation_resolve_billing( $source, $steps, $selections ) {
	$billing = isset( $source['billing'] ) && 'monthly' === $source['billing'] ? 'monthly' : 'one_off';
	$field_id = sanitize_key( $source['billing_from_field'] ?? '' );
	if ( $field_id ) {
		$option = foundation_find_selected_option_by_field_id( $steps, $field_id, $selections );
		if ( $option && ! empty( $option['billing_override'] ) ) {
			$billing = 'monthly' === $option['billing_override'] ? 'monthly' : 'one_off';
		}
	}
	return $billing;
}

/**
 * Authoritative quote calculation. No client-supplied price values are trusted.
 *
 * @param array<int,array<string,mixed>> $steps Normalised flow.
 * @param array<string,mixed> $selections Sanitised customer selections.
 * @param array<string,float>|null $catalog Optional price catalogue.
 * @return array<string,mixed>
 */
function foundation_calculate_quote( $steps, $selections, $catalog = null ) {
	$steps      = foundation_normalize_form_data( $steps );
	$selections = is_array( $selections ) ? $selections : array();
	$catalog    = is_array( $catalog ) ? foundation_sanitize_pricing_catalog( $catalog ) : foundation_get_pricing_catalog();
	$visible    = foundation_get_visible_form_steps( $steps, $selections );
	$quote      = array(
		'one_off_min' => 0.0,
		'one_off_max' => 0.0,
		'monthly_min' => 0.0,
		'monthly_max' => 0.0,
		'line_items'  => array(),
		'manual_items'=> array(),
		'currency'    => foundation_get_settings()['currency_symbol'] ?? '£',
		'vat_note'    => foundation_get_settings()['vat_note'] ?? 'All prices are shown excluding VAT. VAT will be added where applicable.',
	);

	foreach ( $visible as $step ) {
		foreach ( $step['fields'] ?? array() as $field ) {
			$type            = $field['type'] ?? '';
			$label           = $field['line_item_label'] ?? $field['label'] ?? 'Service';
			$pricing_type    = $field['pricing_type'] ?? 'fixed';
			$billing         = foundation_resolve_billing( $field, $steps, $selections );
			$manual_note     = $field['manual_note'] ?? 'A tailored quote is required.';

			if ( 'calculation' === $type ) {
				if ( 'manual' === $pricing_type ) {
					foundation_quote_add_manual_item( $quote, $label, $manual_note );
					continue;
				}

				$unit_field = sanitize_key( $field['unit_source_field_id'] ?? '' );
				$qty_field  = sanitize_key( $field['quantity_source_field_id'] ?? '' );
				if ( $unit_field && $qty_field ) {
					$unit_option = foundation_find_selected_option_by_field_id( $steps, $unit_field, $selections );
					$qty_option  = foundation_find_selected_option_by_field_id( $steps, $qty_field, $selections );
					if ( ! $unit_option || ! $qty_option ) {
						continue;
					}
					if ( 'manual' === ( $unit_option['pricing_type'] ?? '' ) || 'manual' === ( $qty_option['pricing_type'] ?? '' ) ) {
						$note = $unit_option['manual_note'] ?? $qty_option['manual_note'] ?? $manual_note;
						foundation_quote_add_manual_item( $quote, $label, $note );
						continue;
					}
					$unit_price = foundation_resolve_price_per_unit( $unit_option, $catalog );
					$qty_min    = max( 0, (float) ( $qty_option['quantity_min'] ?? 0 ) );
					$qty_max    = max( $qty_min, (float) ( $qty_option['quantity_max'] ?? $qty_min ) );
					foundation_quote_add_line_item(
						$quote,
						$label,
						$unit_price * $qty_min,
						$unit_price * $qty_max,
						$billing,
						array(
							'unit_price'   => $unit_price,
							'quantity_min' => $qty_min,
							'quantity_max' => $qty_max,
						)
					);
				}
				continue;
			}

			if ( 'service_card' === $type ) {
				foreach ( foundation_get_selected_options( $field, $selections ) as $option ) {
					$option_label        = $option['line_item_label'] ?? $option['label'] ?? $label;
					$option_pricing_type = $option['pricing_type'] ?? 'fixed';
					$is_component        = ! empty( $option['pricing_component'] ) || ! empty( $field['pricing_component'] );

					// Pricing components are evaluated by their linked calculation field.
					// Handling a manual component here as well would create two tailored
					// quote rows for one customer choice (for example daily social volume).
					if ( $is_component ) {
						continue;
					}
					if ( 'manual' === $option_pricing_type ) {
						foundation_quote_add_manual_item( $quote, $option_label, $option['manual_note'] ?? $manual_note );
						continue;
					}

					$option_billing = isset( $option['billing'] ) && 'monthly' === $option['billing'] ? 'monthly' : $billing;
					$unit_price     = foundation_resolve_price_per_unit( $option, $catalog );
					if ( $unit_price > 0 && isset( $option['quantity_min'] ) ) {
						$qty_min = max( 0, (float) $option['quantity_min'] );
						$qty_max = max( $qty_min, (float) ( $option['quantity_max'] ?? $qty_min ) );
						foundation_quote_add_line_item( $quote, $option_label, $unit_price * $qty_min, $unit_price * $qty_max, $option_billing, array( 'unit_price' => $unit_price, 'quantity_min' => $qty_min, 'quantity_max' => $qty_max ) );
						continue;
					}
					$price = foundation_resolve_price( $option, $catalog );
					foundation_quote_add_line_item( $quote, $option_label, $price, $price, $option_billing );
				}
				continue;
			}

			if ( 'toggle' === $type ) {
				$selected = $selections[ ( $field['id'] ?? '' ) . '_options' ] ?? array();
				$yes      = is_array( $selected ) && in_array( '0', array_map( 'strval', $selected ), true );
				if ( ! $yes ) {
					continue;
				}
				if ( in_array( $pricing_type, array( 'manual', 'manual_when_yes' ), true ) ) {
					foundation_quote_add_manual_item( $quote, $label, $manual_note );
					continue;
				}
				$price = foundation_resolve_price( $field, $catalog );
				foundation_quote_add_line_item( $quote, $label, $price, $price, $billing );
				continue;
			}

			if ( in_array( $type, array( 'number_input', 'range_slider' ), true ) ) {
				$field_id = $field['id'] ?? '';
				$value    = $selections[ $field_id . '_val' ] ?? $selections[ $field_id ] ?? null;
				if ( null === $value || '' === (string) $value || ! is_numeric( $value ) || ! is_finite( (float) $value ) ) {
					continue;
				}
				$min      = isset( $field['min'] ) ? (float) $field['min'] : 0.0;
				$max      = isset( $field['max'] ) ? max( $min, (float) $field['max'] ) : max( $min, (float) $value );
				$quantity = min( $max, max( $min, (float) $value ) );
				$base     = foundation_resolve_price( $field, $catalog, 'base_price_key', 'base_price' );
				$unit     = foundation_resolve_price_per_unit( $field, $catalog );
				$total    = $base + ( $unit * $quantity );
				foundation_quote_add_line_item( $quote, $label, $total, $total, $billing, array( 'unit_price' => $unit, 'quantity_min' => $quantity, 'quantity_max' => $quantity, 'base_price' => $base ) );
			}
		}
	}

	foreach ( array( 'one_off_min', 'one_off_max', 'monthly_min', 'monthly_max' ) as $key ) {
		$quote[ $key ] = round( (float) $quote[ $key ], 2 );
	}
	$quote['has_range']    = $quote['one_off_min'] !== $quote['one_off_max'] || $quote['monthly_min'] !== $quote['monthly_max'];
	$quote['manual_count'] = count( $quote['manual_items'] );
	$quote['has_pricing']  = $quote['one_off_max'] > 0 || $quote['monthly_max'] > 0;
	return $quote;
}

/**
 * Validate the bundled flow and live pricing catalogue.
 *
 * @return array<string,mixed>
 */
function foundation_get_blueprint_health() {
	$steps       = foundation_normalize_form_data( get_option( 'foundation_form_data', array() ) );
	$catalog     = foundation_get_pricing_catalog();
	$step_ids    = array();
	$field_ids   = array();
	$price_keys  = array();
	$route_ids   = array();
	$issues      = array();

	foreach ( $steps as $step ) {
		$step_id = $step['id'] ?? '';
		if ( isset( $step_ids[ $step_id ] ) ) {
			$issues[] = 'Duplicate step ID: ' . $step_id;
		}
		$step_ids[ $step_id ] = true;
		foreach ( $step['fields'] ?? array() as $field ) {
			$field_id = $field['id'] ?? '';
			if ( isset( $field_ids[ $field_id ] ) ) {
				$issues[] = 'Duplicate field ID: ' . $field_id;
			}
			$field_ids[ $field_id ] = true;
			foreach ( array( 'price_key', 'base_price_key', 'price_per_unit_key' ) as $key_name ) {
				if ( ! empty( $field[ $key_name ] ) ) {
					$price_keys[] = $field[ $key_name ];
				}
			}
			foreach ( (array) ( $field['price_per_unit_keys'] ?? array() ) as $key ) {
				$price_keys[] = $key;
			}
			foreach ( (array) ( $field['yes_route_step_ids'] ?? array() ) as $route ) {
				$route_ids[] = $route;
			}
			foreach ( $field['options'] ?? array() as $option ) {
				foreach ( array( 'price_key', 'unit_price_key' ) as $key_name ) {
					if ( ! empty( $option[ $key_name ] ) ) {
						$price_keys[] = $option[ $key_name ];
					}
				}
				foreach ( (array) ( $option['route_step_ids'] ?? array() ) as $route ) {
					$route_ids[] = $route;
				}
			}
		}
	}

	foreach ( array_unique( array_filter( $price_keys ) ) as $key ) {
		if ( ! array_key_exists( $key, $catalog ) ) {
			$issues[] = 'Missing price key: ' . $key;
		}
	}
	foreach ( array_unique( array_filter( $route_ids ) ) as $route ) {
		if ( ! isset( $step_ids[ $route ] ) ) {
			$issues[] = 'Broken route target: ' . $route;
		}
	}

	$structural_issues = array_values( array_unique( $issues ) );
	$blueprint_current = foundation_is_inkfire_blueprint_installed();
	if ( ! $blueprint_current ) {
		$issues[] = 'The live journey is custom or legacy. Apply the bundled Inkfire pricing journey before launch.';
	}

	return array(
		'ok'                => empty( $issues ),
		'structure_ok'      => empty( $structural_issues ),
		'structure_issues'  => $structural_issues,
		'blueprint_current' => $blueprint_current,
		'issues'            => array_values( array_unique( $issues ) ),
		'step_count'        => count( $steps ),
		'field_count'       => count( $field_ids ),
		'price_key_count'   => count( array_unique( array_filter( $price_keys ) ) ),
		'route_target_count'=> count( array_unique( array_filter( $route_ids ) ) ),
		'blueprint_version' => (string) get_option( 'foundation_blueprint_version', '' ),
		'expected_version'  => foundation_get_blueprint_version(),
	);
}
