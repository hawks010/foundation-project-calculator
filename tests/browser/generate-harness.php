<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$settings = foundation_get_default_settings();
$config = array(
    'version' => FOUNDATION_VERSION,
    'blueprintVersion' => foundation_get_blueprint_version(),
    'formData' => foundation_get_inkfire_pricing_blueprint(),
    'pricingCatalog' => foundation_get_default_pricing_catalog(),
    'ajaxUrl' => '/mock-admin-ajax.php',
    'nonce' => 'browser-smoke-nonce',
    'branding' => array(
        'launchButtonLabel' => $settings['launch_button_label'],
        'wizardTitle' => $settings['wizard_title'],
        'currencySymbol' => $settings['currency_symbol'],
        'vatNote' => $settings['vat_note'],
        'estimateDisclaimer' => $settings['estimate_disclaimer'],
        'showLiveSummary' => true,
        'phoneRequired' => false,
        'privacyConsentLabel' => $settings['privacy_consent_label'],
        'privacyPolicyUrl' => 'https://example.test/privacy-policy/',
        'logoUrl' => '',
        'introImageUrl' => '',
        'introHeading' => $settings['intro_heading'],
        'introText' => $settings['intro_text'],
        'testimonialImageUrl' => '',
        'testimonialHeading' => '',
        'testimonialQuote' => '',
        'testimonialAttribution' => '',
        'successMessage' => $settings['success_message'],
        'quoteModeEnabled' => false,
    ),
    'resume' => array(
        'baseUrl' => 'http://127.0.0.1:8765/tests/browser/harness.html',
        'queryParam' => 'foundation_resume',
        'retentionDays' => 14,
    ),
    'uploads' => array(
        'allowedTypes' => foundation_parse_allowed_extensions($settings),
        'maxFileSizeMb' => 10,
        'maxTotalSizeMb' => 25,
        'maxFilesPerField' => 5,
    ),
);
$json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($json === false) {
    fwrite(STDERR, "Could not encode harness configuration.\n");
    exit(1);
}

$html = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Foundation Project Calculator browser smoke</title>
  <link rel="stylesheet" href="../../assets/css/foundation-frontend.css">
</head>
<body>
  <button type="button" data-foundation-calculator-open>Open calculator</button>
  <div id="foundation-app-overlay" hidden></div>
  <pre id="smoke-result" aria-live="polite">RUNNING</pre>
  <script>window.foundationConfig = __CONFIG__; window.foundationAutoOpen = true;</script>
  <script src="../../assets/js/foundation-frontend.js"></script>
  <script src="browser-smoke-runner.js"></script>
</body>
</html>
HTML;

$html = str_replace('__CONFIG__', $json, $html);
file_put_contents(__DIR__ . '/harness.html', $html);
file_put_contents(__DIR__ . '/harness-config.js', 'window.foundationConfig = ' . $json . '; window.foundationAutoOpen = true;');
