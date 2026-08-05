<?php
/**
 * Deterministic unit and integration-style tests for the calculator blueprint,
 * server-side pricing engine, schema validation and local lead storage.
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

final class FoundationTestFailure extends RuntimeException {}

$tests = array();
$results = array();

function foundation_test(string $name, callable $callback): void {
    global $tests;
    $tests[] = array($name, $callback);
}

function assert_true($condition, string $message = 'Expected condition to be true.'): void {
    if (!$condition) {
        throw new FoundationTestFailure($message);
    }
}

function assert_false($condition, string $message = 'Expected condition to be false.'): void {
    if ($condition) {
        throw new FoundationTestFailure($message);
    }
}

function assert_same($expected, $actual, string $message = ''): void {
    if ($expected !== $actual) {
        $suffix = $message !== '' ? ' ' . $message : '';
        throw new FoundationTestFailure(
            'Expected ' . var_export($expected, true) . ', received ' . var_export($actual, true) . '.' . $suffix
        );
    }
}

function assert_float(float $expected, $actual, string $message = '', float $epsilon = 0.0001): void {
    if (!is_numeric($actual) || abs($expected - (float) $actual) > $epsilon) {
        $suffix = $message !== '' ? ' ' . $message : '';
        throw new FoundationTestFailure(
            'Expected ' . $expected . ', received ' . var_export($actual, true) . '.' . $suffix
        );
    }
}

function assert_contains(string $needle, array $haystack, string $message = ''): void {
    foreach ($haystack as $value) {
        if ((string) $value === $needle) {
            return;
        }
    }
    throw new FoundationTestFailure($message !== '' ? $message : 'Expected array to contain ' . $needle . '.');
}

function assert_string_contains(string $needle, string $haystack, string $message = ''): void {
    if (strpos($haystack, $needle) === false) {
        throw new FoundationTestFailure($message !== '' ? $message : 'Expected string to contain ' . $needle . '.');
    }
}

/** @return array<int,array<string,mixed>> */
function test_steps(): array {
    return (array) get_option('foundation_form_data', array());
}

/** @return array<string,mixed> */
function test_find_field(string $field_id, ?array $steps = null): array {
    $steps = $steps ?? test_steps();
    foreach ($steps as $step) {
        foreach ((array) ($step['fields'] ?? array()) as $field) {
            if (($field['id'] ?? '') === $field_id) {
                return $field;
            }
        }
    }
    throw new FoundationTestFailure('Unknown field ID: ' . $field_id);
}

function test_option_index(string $field_id, string $value, ?array $steps = null): string {
    $field = test_find_field($field_id, $steps);
    foreach ((array) ($field['options'] ?? array()) as $index => $option) {
        if (($option['value'] ?? '') === $value) {
            return (string) $index;
        }
    }
    throw new FoundationTestFailure('Unknown option value ' . $value . ' for field ' . $field_id);
}

/** @param array<string,string|array<int,string>> $values */
function test_selections(array $values, ?array $steps = null): array {
    $steps = $steps ?? test_steps();
    $output = array();
    foreach ($values as $field_id => $value) {
        $field = test_find_field($field_id, $steps);
        if ('service_card' === ($field['type'] ?? '')) {
            $requested = is_array($value) ? $value : array((string) $value);
            $output[$field_id . '_options'] = array_map(
                static fn(string $option_value): string => test_option_index($field_id, $option_value, $steps),
                $requested
            );
            continue;
        }
        if ('toggle' === ($field['type'] ?? '')) {
            $output[$field_id . '_options'] = array(in_array((string) $value, array('yes', '1', 'true'), true) ? '0' : '1');
            continue;
        }
        if (in_array(($field['type'] ?? ''), array('number_input', 'range_slider'), true)) {
            $output[$field_id . '_val'] = $value;
            continue;
        }
        $output[$field_id] = $value;
    }
    return $output;
}

function test_quote(array $values): array {
    return foundation_calculate_quote(test_steps(), test_selections($values));
}

/** @return array<int,string> */
function test_visible_ids(array $values): array {
    $visible = foundation_get_visible_form_steps(test_steps(), test_selections($values));
    return array_values(array_map(static fn(array $step): string => (string) $step['id'], $visible));
}

function test_manual_labels(array $quote): array {
    return array_values(array_map(static fn(array $item): string => (string) ($item['label'] ?? ''), (array) ($quote['manual_items'] ?? array())));
}

function test_line_labels(array $quote): array {
    return array_values(array_map(static fn(array $item): string => (string) ($item['label'] ?? ''), (array) ($quote['line_items'] ?? array())));
}

function test_setup_blueprint(): void {
    foundation_test_reset_state();
    foundation_register_default_settings();
    foundation_register_default_pricing_catalog();
    foundation_apply_inkfire_blueprint(false);
}

foundation_test('Blueprint installs with complete, healthy structure', function (): void {
    test_setup_blueprint();
    $health = foundation_get_blueprint_health();
    assert_true($health['ok']);
    assert_same(array(), $health['issues']);
    assert_same(28, $health['step_count']);
    assert_same(49, $health['field_count']);
    assert_same(38, $health['price_key_count']);
    assert_same(27, $health['route_target_count']);
    assert_same('2026.08.05', $health['blueprint_version']);
});

foundation_test('Custom or legacy journey is structurally reported but blocked from launch-ready status', function (): void {
    test_setup_blueprint();
    update_option('foundation_blueprint_version', '', false);
    $health = foundation_get_blueprint_health();
    assert_false($health['ok']);
    assert_true($health['structure_ok']);
    assert_false($health['blueprint_current']);
    assert_same(array(), $health['structure_issues']);
    assert_string_contains('custom or legacy', implode(' ', $health['issues']));
});

foundation_test('Blueprint health detects structurally valid tampering even when the version marker remains', function (): void {
    test_setup_blueprint();
    $steps = test_steps();
    $steps[0]['title'] = 'Changed outside the supported editor';
    update_option('foundation_form_data', $steps, false);
    $health = foundation_get_blueprint_health();
    assert_false($health['ok']);
    assert_true($health['structure_ok']);
    assert_false($health['blueprint_current']);
});

foundation_test('Resume links accept only the exact site origin and discard query fragments', function (): void {
    foundation_test_reset_state();
    assert_same('https://example.test/calculator/', foundation_normalize_resume_base_url('https://example.test/calculator/?campaign=one#step'));
    assert_same('https://example.test/', foundation_normalize_resume_base_url('http://example.test/calculator/'));
    assert_same('https://example.test/', foundation_normalize_resume_base_url('https://evil.example/calculator/'));
    assert_same('https://example.test/', foundation_normalize_resume_base_url('https://example.test:8443/calculator/'));
    assert_same('https://example.test/', foundation_normalize_resume_base_url('https://user:pass@example.test/calculator/'));
});

foundation_test('Transient rate limiter permits the configured allowance and blocks the next request', function (): void {
    foundation_test_reset_state();
    assert_true(foundation_rate_limit_allow('unit_test', 2, HOUR_IN_SECONDS, 'same-client'));
    assert_true(foundation_rate_limit_allow('unit_test', 2, HOUR_IN_SECONDS, 'same-client'));
    assert_false(foundation_rate_limit_allow('unit_test', 2, HOUR_IN_SECONDS, 'same-client'));
    assert_true(foundation_rate_limit_allow('unit_test', 2, HOUR_IN_SECONDS, 'different-client'));
});

foundation_test('Price catalogue has 38 bounded editable prices', function (): void {
    test_setup_blueprint();
    $catalog = foundation_get_pricing_catalog();
    assert_same(38, count($catalog));
    assert_float(500.0, $catalog['web_platform_fee']);
    assert_float(2500.0, foundation_sanitize_pricing_catalog(array('business_ugc_venue_day' => 2500))['business_ugc_venue_day']);
    assert_float(0.0, foundation_sanitize_pricing_catalog(array('web_platform_fee' => -99))['web_platform_fee']);
    assert_float(1000000.0, foundation_sanitize_pricing_catalog(array('web_platform_fee' => 99999999))['web_platform_fee']);
});

foundation_test('Small five-page website is £2,750 one-off', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'web',
        'web_project_type' => 'small',
        'web_small_pages' => 5,
        'web_shop' => 'no',
        'web_hosting' => 'none',
        'web_alt_text' => 'none',
        'web_accessibility_testing' => 'no',
        'web_accessibility_setup' => 'no',
    ));
    assert_float(2750.0, $quote['one_off_min']);
    assert_float(2750.0, $quote['one_off_max']);
    assert_float(0.0, $quote['monthly_max']);
    assert_same(0, $quote['manual_count']);
});

foundation_test('Bespoke five-page website is £5,000 one-off', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'web',
        'web_project_type' => 'bespoke',
        'web_bespoke_pages' => 5,
        'web_shop' => 'no',
        'web_hosting' => 'none',
        'web_alt_text' => 'none',
        'web_accessibility_testing' => 'no',
        'web_accessibility_setup' => 'no',
    ));
    assert_float(5000.0, $quote['one_off_min']);
    assert_float(0.0, $quote['monthly_max']);
});

foundation_test('Full web example splits £4,260 one-off and £45 monthly', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'web',
        'web_project_type' => 'small',
        'web_small_pages' => 5,
        'web_shop' => 'yes',
        'web_hosting' => 'plugins',
        'web_alt_text' => '50',
        'web_accessibility_testing' => 'yes',
        'web_accessibility_setup' => 'yes',
    ));
    assert_float(4260.0, $quote['one_off_min']);
    assert_float(4260.0, $quote['one_off_max']);
    assert_float(45.0, $quote['monthly_min']);
    assert_float(45.0, $quote['monthly_max']);
    assert_same(0, $quote['manual_count']);
});

foundation_test('Alt text for 76+ images is routed to manual review', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'web',
        'web_project_type' => 'small',
        'web_small_pages' => 1,
        'web_shop' => 'no',
        'web_hosting' => 'none',
        'web_alt_text' => '76_plus',
        'web_accessibility_testing' => 'no',
        'web_accessibility_setup' => 'no',
    ));
    assert_same(1, $quote['manual_count']);
    assert_contains('Yes, 76+ images', test_manual_labels($quote));
});

foundation_test('Existing website route shows only the manual improvement branch', function (): void {
    test_setup_blueprint();
    $values = array(
        'route_selection' => 'web',
        'web_project_type' => 'existing',
        'web_existing_url' => 'https://example.test',
        'web_existing_changes' => 'Improve the checkout and accessibility.',
    );
    $visible = test_visible_ids($values);
    assert_contains('web_existing', $visible);
    assert_false(in_array('web_addons', $visible, true), 'Generic new-build add-ons must not appear on the existing-site route.');
    $quote = test_quote($values);
    assert_same(1, $quote['manual_count']);
    assert_float(0.0, $quote['one_off_max']);
});

foundation_test('Ongoing IT support calculates users, devices and dark-web domains monthly', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'tech',
        'tech_project_type' => 'ongoing',
        'tech_users' => 4,
        'tech_devices' => 6,
        'tech_ongoing_needs' => array('m365', 'security'),
        'tech_dark_web_toggle' => 'yes',
        'tech_dark_web_domains' => 2,
    ));
    assert_float(440.0, $quote['monthly_min']);
    assert_float(440.0, $quote['monthly_max']);
    assert_float(0.0, $quote['one_off_max']);
});

foundation_test('Occasional IT hours calculate at £120 per hour', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'tech',
        'tech_project_type' => 'occasional',
        'tech_occasional_size' => 'hours',
        'tech_consulting_hours' => 3,
    ));
    assert_float(360.0, $quote['one_off_max']);
    assert_same(0, $quote['manual_count']);
});

foundation_test('Small and larger IT projects never invent a price', function (): void {
    test_setup_blueprint();
    foreach (array('small_project', 'large_project') as $size) {
        $quote = test_quote(array(
            'route_selection' => 'tech',
            'tech_project_type' => 'occasional',
            'tech_occasional_size' => $size,
        ));
        assert_same(1, $quote['manual_count']);
        assert_float(0.0, $quote['one_off_max']);
    }
});

foundation_test('Microsoft setup and Intune management split one-off and monthly totals', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'tech',
        'tech_project_type' => 'microsoft',
        'tech_microsoft_systems' => array('windows', 'ios'),
        'tech_intune_devices' => 10,
    ));
    assert_float(3600.0, $quote['one_off_max']);
    assert_float(250.0, $quote['monthly_max']);
});

foundation_test('Cyber Essentials support is discovery-led', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'tech',
        'tech_project_type' => 'cyber',
        'tech_cyber_level' => 'guided',
    ));
    assert_same(1, $quote['manual_count']);
    assert_float(0.0, $quote['one_off_max']);
});

foundation_test('Accessibility-focused tech support calculates at £120 per hour', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'tech',
        'tech_project_type' => 'accessibility',
        'tech_accessibility_needs' => array('device_setup', 'software'),
        'tech_accessibility_hours' => 4,
    ));
    assert_float(480.0, $quote['one_off_max']);
});

foundation_test('Social static content at 12-16 posts creates a monthly range', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('social'),
        'business_social_support' => array('graphics', 'copy'),
        'business_social_volume' => 'multiple',
        'business_social_content_type' => 'static',
    ));
    assert_float(600.0, $quote['monthly_min']);
    assert_float(800.0, $quote['monthly_max']);
    assert_true($quote['has_range']);
    assert_same(0, $quote['manual_count']);
});

foundation_test('Social story graphics remain manual because the board has no standalone rate', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('social'),
        'business_social_support' => array('graphics'),
        'business_social_volume' => 'few',
        'business_social_content_type' => 'stories',
    ));
    assert_same(1, $quote['manual_count']);
    assert_float(0.0, $quote['monthly_max']);
});

foundation_test('Daily social volume remains manual', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('social'),
        'business_social_support' => array('graphics'),
        'business_social_volume' => 'daily',
        'business_social_content_type' => 'carousel',
    ));
    assert_same(1, $quote['manual_count']);
});

foundation_test('Blog pricing handles four posts and routes 8+ to discovery', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('blog'),
        'business_blog_count' => '4',
    ));
    assert_float(600.0, $quote['monthly_max']);
    $manual = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('blog'),
        'business_blog_count' => '8_plus',
    ));
    assert_same(1, $manual['manual_count']);
});

foundation_test('Website copy 4-8 hours produces a £400-£800 one-off range', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('website_copy'),
        'business_copy_type' => array('copywriting', 'accessible_rewrite'),
        'business_copy_hours' => '4_8',
    ));
    assert_float(400.0, $quote['one_off_min']);
    assert_float(800.0, $quote['one_off_max']);
});

foundation_test('Weekly newsletters are represented as a 4-5 per month range', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('newsletters'),
        'business_newsletter_count' => 'weekly',
    ));
    assert_float(600.0, $quote['monthly_min']);
    assert_float(750.0, $quote['monthly_max']);
});

foundation_test('Access to Work funding uses a tailored quote only', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('general_support'),
        'business_access_to_work' => 'yes',
    ));
    assert_same(1, $quote['manual_count']);
    assert_float(0.0, $quote['monthly_max']);
    assert_false(in_array('business_general_support_pricing', test_visible_ids(array(
        'route_selection' => 'business',
        'business_project_type' => array('general_support'),
        'business_access_to_work' => 'yes',
    )), true));
});

foundation_test('Non-funded PA support calculates selected monthly hours', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('general_support'),
        'business_access_to_work' => 'no',
        'business_support_role' => 'pa',
        'business_support_hours' => '10',
    ));
    assert_float(800.0, $quote['monthly_max']);
    assert_same(0, $quote['manual_count']);
});

foundation_test('Strategy delivery switches billing between one-off and monthly', function (): void {
    test_setup_blueprint();
    $oneOff = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('strategy'),
        'business_strategy_focus' => array('strategy'),
        'business_strategy_delivery' => 'one_off',
        'business_strategy_hours' => 5,
    ));
    assert_float(600.0, $oneOff['one_off_max']);
    assert_float(0.0, $oneOff['monthly_max']);

    $monthly = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('strategy'),
        'business_strategy_focus' => array('growth'),
        'business_strategy_delivery' => 'monthly',
        'business_strategy_hours' => 5,
    ));
    assert_float(0.0, $monthly['one_off_max']);
    assert_float(600.0, $monthly['monthly_max']);
});

foundation_test('Marketing can combine graphic design, UGC and hosting', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => 'business',
        'business_project_type' => array('marketing'),
        'business_marketing_services' => array('graphic_design', 'ugc', 'hosting'),
        'business_graphic_design_hours' => 3,
        'business_ugc_location' => 'client_site',
    ));
    assert_float(725.0, $quote['one_off_max']);
    assert_float(45.0, $quote['monthly_max']);
});

foundation_test('Multi-route journey combines web, tech and business pricing', function (): void {
    test_setup_blueprint();
    $quote = test_quote(array(
        'route_selection' => array('web', 'tech', 'business'),
        'web_project_type' => 'small',
        'web_small_pages' => 2,
        'web_shop' => 'no',
        'web_hosting' => 'hosting',
        'web_alt_text' => 'none',
        'web_accessibility_testing' => 'no',
        'web_accessibility_setup' => 'no',
        'tech_project_type' => 'accessibility',
        'tech_accessibility_needs' => array('guidance'),
        'tech_accessibility_hours' => 2,
        'business_project_type' => array('blog'),
        'business_blog_count' => '2',
    ));
    assert_float(1640.0, $quote['one_off_max']);
    assert_float(330.0, $quote['monthly_max']);
});

foundation_test('Required validation ignores hidden branches', function (): void {
    test_setup_blueprint();
    $selections = test_selections(array(
        'route_selection' => 'web',
        'web_project_type' => 'existing',
        'web_existing_url' => 'https://example.test',
        'web_existing_changes' => 'A specific list of changes.',
    ));
    $missing = foundation_validate_required_submission_fields(test_steps(), $selections, array());
    assert_same(array(), $missing);
});

foundation_test('Schema rejects a tampered service option index', function (): void {
    test_setup_blueprint();
    $selections = test_selections(array('route_selection' => 'web'));
    $selections['web_project_type_options'] = array('999');
    // Make web_project_type visible through route_selection and validate it.
    $errors = foundation_validate_submission_values(test_steps(), $selections);
    assert_true(count($errors) >= 1);
    assert_string_contains('no longer available', implode(' ', $errors));
});

foundation_test('Schema rejects multiple choices in a single-choice field', function (): void {
    test_setup_blueprint();
    $selections = test_selections(array('route_selection' => 'web'));
    $selections['web_project_type_options'] = array(
        test_option_index('web_project_type', 'small'),
        test_option_index('web_project_type', 'bespoke'),
    );
    $errors = foundation_validate_submission_values(test_steps(), $selections);
    assert_string_contains('Choose only one option', implode(' ', $errors));
});

foundation_test('Schema rejects numbers outside bounds and off-step values', function (): void {
    test_setup_blueprint();
    $outOfRange = test_selections(array(
        'route_selection' => 'web',
        'web_project_type' => 'small',
        'web_small_pages' => 9999,
    ));
    $errors = foundation_validate_submission_values(test_steps(), $outOfRange);
    assert_string_contains('must be between', implode(' ', $errors));

    $steps = test_steps();
    foreach ($steps as &$step) {
        foreach ($step['fields'] as &$field) {
            if (($field['id'] ?? '') === 'web_small_pages') {
                $field['step'] = 2;
            }
        }
    }
    unset($step, $field);
    $offStep = test_selections(array(
        'route_selection' => 'web',
        'web_project_type' => 'small',
        'web_small_pages' => 4,
    ), $steps);
    $errors = foundation_validate_submission_values($steps, $offStep);
    assert_string_contains('increments of 2', implode(' ', $errors));
});

foundation_test('Direct quote calculation clamps numeric quantities to live bounds', function (): void {
    test_setup_blueprint();
    $selections = test_selections(array(
        'route_selection' => 'web',
        'web_project_type' => 'small',
        'web_small_pages' => 99999,
        'web_shop' => 'no',
        'web_hosting' => 'none',
        'web_alt_text' => 'none',
        'web_accessibility_testing' => 'no',
        'web_accessibility_setup' => 'no',
    ));
    $field = test_find_field('web_small_pages');
    $expected = 500.0 + (450.0 * (float) $field['max']);
    $quote = foundation_calculate_quote(test_steps(), $selections);
    assert_float($expected, $quote['one_off_max']);
});

foundation_test('Executable and browser-active upload types are always removed', function (): void {
    test_setup_blueprint();
    $settings = foundation_sanitize_settings(array_merge(
        foundation_get_default_settings(),
        array('allowed_file_types' => 'pdf,jpg,php,phtml,svg,js,html,exe,docx')
    ));
    $allowed = foundation_parse_allowed_extensions($settings);
    assert_same(array('pdf', 'jpg', 'docx'), $allowed);
});

foundation_test('Local enquiry storage creates a reference and supports idempotent lookup', function (): void {
    test_setup_blueprint();
    $instance = new Foundation_Submissions();
    $instance->register_post_type();
    $token = '12345678901234567890abcdef';
    $created = Foundation_Submissions::create(
        array('name' => 'Test Person', 'company' => 'Test Ltd', 'email' => 'person@example.test'),
        array('route_selection_options' => array('0')),
        array(array('label' => 'Route', 'value' => 'Web')),
        array('one_off_min' => 500, 'one_off_max' => 500, 'monthly_min' => 0, 'monthly_max' => 0),
        $token,
        array('scope.pdf')
    );
    assert_false(is_wp_error($created));
    assert_string_contains('INK-', $created['reference']);
    assert_same($created['id'], Foundation_Submissions::find_by_token($token));
    assert_same(1, Foundation_Submissions::count_all());
    $record = Foundation_Submissions::get_record($created['id']);
    assert_same('person@example.test', $record['contact']['email']);
    assert_same(array('scope.pdf'), $record['attachment_names']);
});

foundation_test('Submission lock is atomic, expirable and releasable', function (): void {
    test_setup_blueprint();
    $token = 'abcdefghijklmnopqrstuvwxyz123456';
    assert_true(Foundation_Submissions::acquire_submission_lock($token));
    assert_false(Foundation_Submissions::acquire_submission_lock($token));
    Foundation_Submissions::release_submission_lock($token);
    assert_true(Foundation_Submissions::acquire_submission_lock($token));
    Foundation_Submissions::release_submission_lock($token);
});

foundation_test('Mail status accepts only known states', function (): void {
    test_setup_blueprint();
    $instance = new Foundation_Submissions();
    $instance->register_post_type();
    $created = Foundation_Submissions::create(
        array('name' => 'Mail Test', 'company' => 'Inkfire', 'email' => 'mail@example.test'),
        array(), array(), array(), 'validtokenvalidtoken12345'
    );
    Foundation_Submissions::update_mail_status($created['id'], 'sent', 'disabled');
    $record = Foundation_Submissions::get_record($created['id']);
    assert_same('sent', $record['admin_mail_status']);
    assert_same('disabled', $record['customer_mail_status']);
    Foundation_Submissions::update_mail_status($created['id'], 'hacked', 'hacked');
    $record = Foundation_Submissions::get_record($created['id']);
    assert_same('sent', $record['admin_mail_status']);
    assert_same('disabled', $record['customer_mail_status']);
});

foundation_test('Privacy exporter returns matching enquiries and eraser removes all batches safely', function (): void {
    test_setup_blueprint();
    $instance = new Foundation_Submissions();
    $instance->register_post_type();
    for ($i = 0; $i < 55; $i++) {
        Foundation_Submissions::create(
            array('name' => 'Privacy Person', 'company' => 'Inkfire', 'email' => 'privacy@example.test'),
            array(), array(), array(), 'privacytoken' . str_pad((string) $i, 20, 'x')
        );
    }
    Foundation_Submissions::create(
        array('name' => 'Other Person', 'company' => 'Elsewhere', 'email' => 'other@example.test'),
        array(), array(), array(), 'othertoken123456789012345678'
    );
    $export = $instance->privacy_exporter('privacy@example.test', 1);
    assert_same(50, count($export['data']));
    assert_false($export['done']);
    $eraseOne = $instance->privacy_eraser('privacy@example.test', 1);
    assert_true($eraseOne['items_removed']);
    assert_false($eraseOne['done']);
    $eraseTwo = $instance->privacy_eraser('privacy@example.test', 2);
    assert_true($eraseTwo['items_removed']);
    assert_true($eraseTwo['done']);
    $remaining = $instance->privacy_exporter('privacy@example.test', 1);
    assert_same(0, count($remaining['data']));
    assert_same(1, Foundation_Submissions::count_all());
});

foundation_test('Retention cleanup deletes old enquiries but preserves recent ones', function (): void {
    test_setup_blueprint();
    update_option('foundation_form_settings', array_merge(foundation_get_default_settings(), array('lead_retention_days' => 30)), false);
    $instance = new Foundation_Submissions();
    $instance->register_post_type();
    $old = Foundation_Submissions::create(
        array('name' => 'Old', 'company' => 'Old Co', 'email' => 'old@example.test'),
        array(), array(), array(), 'oldtoken12345678901234567890'
    );
    $recent = Foundation_Submissions::create(
        array('name' => 'Recent', 'company' => 'New Co', 'email' => 'new@example.test'),
        array(), array(), array(), 'newtoken12345678901234567890'
    );
    $GLOBALS['wp_posts'][$old['id']]['post_date_gmt'] = gmdate('Y-m-d H:i:s', time() - (45 * DAY_IN_SECONDS));
    $GLOBALS['wp_posts'][$recent['id']]['post_date_gmt'] = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS);
    $instance->cleanup_old_submissions();
    assert_same(false, get_post_type($old['id']));
    assert_same(Foundation_Submissions::POST_TYPE, get_post_type($recent['id']));
});

foundation_test('Cleanup cron schedules once and can be cleared', function (): void {
    test_setup_blueprint();
    Foundation_Submissions::schedule_cleanup();
    $first = wp_next_scheduled(Foundation_Submissions::CRON_HOOK);
    assert_true(is_int($first));
    Foundation_Submissions::schedule_cleanup();
    assert_same($first, wp_next_scheduled(Foundation_Submissions::CRON_HOOK));
    Foundation_Submissions::unschedule_cleanup();
    assert_same(false, wp_next_scheduled(Foundation_Submissions::CRON_HOOK));
});

$start = microtime(true);
$passed = 0;
$failed = 0;

foreach ($tests as [$name, $callback]) {
    $caseStart = microtime(true);
    try {
        $callback();
        $duration = (microtime(true) - $caseStart) * 1000;
        $passed++;
        $results[] = array('status' => 'PASS', 'name' => $name, 'duration_ms' => $duration, 'message' => '');
        echo sprintf("PASS  %s (%.1f ms)\n", $name, $duration);
    } catch (Throwable $error) {
        $duration = (microtime(true) - $caseStart) * 1000;
        $failed++;
        $results[] = array('status' => 'FAIL', 'name' => $name, 'duration_ms' => $duration, 'message' => $error->getMessage());
        echo sprintf("FAIL  %s (%.1f ms)\n      %s\n", $name, $duration, $error->getMessage());
    }
}

$totalDuration = (microtime(true) - $start) * 1000;
echo "\n" . str_repeat('=', 72) . "\n";
echo sprintf("Tests: %d  Passed: %d  Failed: %d  Duration: %.1f ms\n", count($tests), $passed, $failed, $totalDuration);

$report = array(
    '# Foundation Project Calculator 1.4.0 Test Results',
    '',
    '- Run: ' . gmdate('Y-m-d H:i:s') . ' UTC',
    '- PHP: ' . PHP_VERSION,
    '- Tests: ' . count($tests),
    '- Passed: ' . $passed,
    '- Failed: ' . $failed,
    '- Duration: ' . number_format($totalDuration, 1) . ' ms',
    '',
    '| Result | Test | Duration |',
    '|---|---|---:|',
);
foreach ($results as $result) {
    $name = str_replace('|', '\\|', $result['name']);
    $report[] = sprintf('| %s | %s | %.1f ms |', $result['status'], $name, $result['duration_ms']);
    if ($result['message'] !== '') {
        $report[] = '';
        $report[] = '> Failure: ' . str_replace("\n", ' ', $result['message']);
        $report[] = '';
    }
}
$report[] = '';
$report[] = $failed === 0
    ? '**Result: PASS.** The deterministic pricing, routing, validation and local-storage test suite completed without failures.'
    : '**Result: FAIL.** Resolve the failed cases before packaging.';
file_put_contents(__DIR__ . '/TEST_RESULTS.md', implode("\n", $report) . "\n");

exit($failed === 0 ? 0 : 1);
