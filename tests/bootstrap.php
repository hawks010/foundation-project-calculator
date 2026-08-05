<?php
/**
 * Minimal WordPress compatibility layer for deterministic plugin unit tests.
 * This is intentionally small and only implements APIs exercised by the tests.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/wordpress/');
}
if (!defined('FOUNDATION_PATH')) {
    define('FOUNDATION_PATH', dirname(__DIR__) . '/');
}
if (!defined('FOUNDATION_URL')) {
    define('FOUNDATION_URL', 'https://example.test/wp-content/plugins/foundation-project-calculator/');
}
if (!defined('FOUNDATION_VERSION')) {
    define('FOUNDATION_VERSION', '1.4.0');
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

$GLOBALS['wp_options'] = array(
    'admin_email' => 'admin@example.test',
    'blogname' => 'Inkfire Test',
);
$GLOBALS['wp_transients'] = array();
$GLOBALS['wp_posts'] = array();
$GLOBALS['wp_post_meta'] = array();
$GLOBALS['wp_next_post_id'] = 1;
$GLOBALS['wp_actions'] = array();
$GLOBALS['wp_filters'] = array();
$GLOBALS['wp_schedules'] = array();
$GLOBALS['wp_registered_post_types'] = array();

final class WP_Error {
    private string $code;
    private string $message;
    private mixed $data;

    public function __construct(string $code = '', string $message = '', mixed $data = null) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
    public function get_error_data(): mixed { return $this->data; }
}

function is_wp_error($thing): bool { return $thing instanceof WP_Error; }
function __(string $text, string $domain = ''): string { return $text; }
function _x(string $text, string $context, string $domain = ''): string { return $text; }
function esc_html__(string $text, string $domain = ''): string { return $text; }
function wp_json_encode($value, int $flags = 0, int $depth = 512): string|false { return json_encode($value, $flags, $depth); }

function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool {
    $GLOBALS['wp_actions'][$hook][] = $callback;
    return true;
}
function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool {
    $GLOBALS['wp_filters'][$hook][] = $callback;
    return true;
}
function do_action(string $hook, ...$args): void {
    foreach ($GLOBALS['wp_actions'][$hook] ?? array() as $callback) {
        call_user_func_array($callback, $args);
    }
}
function apply_filters(string $hook, $value, ...$args) {
    foreach ($GLOBALS['wp_filters'][$hook] ?? array() as $callback) {
        $value = call_user_func_array($callback, array_merge(array($value), $args));
    }
    return $value;
}

function get_option(string $key, $default = false) {
    return array_key_exists($key, $GLOBALS['wp_options']) ? $GLOBALS['wp_options'][$key] : $default;
}
function add_option(string $key, $value = '', string $deprecated = '', bool $autoload = true): bool {
    if (array_key_exists($key, $GLOBALS['wp_options'])) {
        return false;
    }
    $GLOBALS['wp_options'][$key] = $value;
    return true;
}
function update_option(string $key, $value, bool $autoload = true): bool {
    $GLOBALS['wp_options'][$key] = $value;
    return true;
}
function delete_option(string $key): bool {
    if (!array_key_exists($key, $GLOBALS['wp_options'])) {
        return false;
    }
    unset($GLOBALS['wp_options'][$key]);
    return true;
}

function set_transient(string $key, $value, int $expiration = 0): bool {
    $GLOBALS['wp_transients'][$key] = array(
        'value' => $value,
        'expires' => $expiration > 0 ? time() + $expiration : 0,
    );
    return true;
}
function get_transient(string $key) {
    if (!isset($GLOBALS['wp_transients'][$key])) {
        return false;
    }
    $record = $GLOBALS['wp_transients'][$key];
    if (!empty($record['expires']) && $record['expires'] < time()) {
        unset($GLOBALS['wp_transients'][$key]);
        return false;
    }
    return $record['value'];
}
function delete_transient(string $key): bool {
    unset($GLOBALS['wp_transients'][$key]);
    return true;
}

function sanitize_key($key): string {
    $key = strtolower((string) $key);
    return preg_replace('/[^a-z0-9_\-]/', '', $key) ?? '';
}
function sanitize_text_field($value): string {
    if (is_array($value) || is_object($value)) {
        return '';
    }
    $value = strip_tags((string) $value);
    $value = preg_replace('/[\r\n\t ]+/', ' ', $value) ?? '';
    return trim($value);
}
function sanitize_textarea_field($value): string {
    if (is_array($value) || is_object($value)) {
        return '';
    }
    $value = strip_tags((string) $value);
    $value = preg_replace("/\r\n?|\n/", "\n", $value) ?? '';
    return trim($value);
}
function sanitize_email($email): string {
    $email = filter_var(trim((string) $email), FILTER_SANITIZE_EMAIL);
    return is_string($email) ? $email : '';
}
function is_email($email): bool { return false !== filter_var((string) $email, FILTER_VALIDATE_EMAIL); }
function esc_url_raw($url): string {
    $url = filter_var(trim((string) $url), FILTER_SANITIZE_URL);
    return is_string($url) ? $url : '';
}
function wp_strip_all_tags($text, bool $remove_breaks = false): string {
    $text = strip_tags((string) $text);
    return $remove_breaks ? (preg_replace('/[\r\n\t ]+/', ' ', $text) ?? '') : $text;
}
function sanitize_file_name($name): string {
    $name = basename((string) $name);
    $name = preg_replace('/[^A-Za-z0-9._\-]/', '-', $name) ?? '';
    return trim($name, '.-');
}
function absint($value): int { return abs((int) $value); }
function wp_parse_args($args, $defaults = array()): array {
    return array_merge((array) $defaults, (array) $args);
}
function wp_parse_url($url, int $component = -1) {
    return parse_url((string) $url, $component);
}
function home_url(string $path = ''): string { return 'https://example.test' . ($path ? '/' . ltrim($path, '/') : ''); }
function get_bloginfo(string $show = ''): string {
    if ('name' === $show) {
        return (string) ($GLOBALS['wp_options']['blogname'] ?? 'Inkfire Test');
    }
    return '';
}
function number_format_i18n($number, int $decimals = 0): string { return number_format((float) $number, $decimals, '.', ','); }
function current_time(string $type, bool $gmt = false) {
    if ('mysql' === $type) {
        return gmdate('Y-m-d H:i:s');
    }
    return time();
}
function wp_generate_password(int $length = 12, bool $special_chars = true, bool $extra_special_chars = false): string {
    static $counter = 0;
    $counter++;
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $seed = hash('sha256', 'foundation-test-' . $counter);
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $alphabet[hexdec($seed[($i * 2) % strlen($seed)]) % strlen($alphabet)];
    }
    return $result;
}
function wp_generate_uuid4(): string {
    static $counter = 0;
    $counter++;
    return sprintf('00000000-0000-4000-8000-%012d', $counter);
}
function wp_salt(string $scheme = 'auth'): string { return 'foundation-test-salt-' . $scheme; }

function register_post_type(string $post_type, array $args = array()): object {
    $GLOBALS['wp_registered_post_types'][$post_type] = $args;
    return (object) $args;
}
function post_type_exists(string $post_type): bool { return isset($GLOBALS['wp_registered_post_types'][$post_type]); }
function wp_insert_post(array $postarr, bool $wp_error = false) {
    $id = $GLOBALS['wp_next_post_id']++;
    $GLOBALS['wp_posts'][$id] = array_merge(
        array(
            'ID' => $id,
            'post_type' => 'post',
            'post_status' => 'draft',
            'post_title' => '',
            'post_date_gmt' => gmdate('Y-m-d H:i:s'),
        ),
        $postarr
    );
    return $id;
}
function get_post_type($post_id): string|false {
    return $GLOBALS['wp_posts'][(int) $post_id]['post_type'] ?? false;
}
function update_post_meta($post_id, string $meta_key, $meta_value): bool {
    $GLOBALS['wp_post_meta'][(int) $post_id][$meta_key] = $meta_value;
    return true;
}
function get_post_meta($post_id, string $meta_key = '', bool $single = false) {
    $meta = $GLOBALS['wp_post_meta'][(int) $post_id] ?? array();
    if ('' === $meta_key) {
        return $meta;
    }
    return array_key_exists($meta_key, $meta) ? $meta[$meta_key] : ($single ? '' : array());
}
function get_post_field(string $field, $post_id) {
    return $GLOBALS['wp_posts'][(int) $post_id][$field] ?? '';
}
function wp_delete_post($post_id, bool $force_delete = false) {
    $id = (int) $post_id;
    if (!isset($GLOBALS['wp_posts'][$id])) {
        return false;
    }
    $post = (object) $GLOBALS['wp_posts'][$id];
    unset($GLOBALS['wp_posts'][$id], $GLOBALS['wp_post_meta'][$id]);
    return $post;
}
function wp_count_posts(string $type = 'post', string $perm = ''): object {
    $counts = array();
    foreach ($GLOBALS['wp_posts'] as $post) {
        if (($post['post_type'] ?? '') !== $type) {
            continue;
        }
        $status = (string) ($post['post_status'] ?? 'draft');
        $counts[$status] = ($counts[$status] ?? 0) + 1;
    }
    return (object) $counts;
}

/**
 * Tiny get_posts implementation supporting the filters used by Foundation_Submissions.
 */
function get_posts(array $args = array()): array {
    $defaults = array(
        'post_type' => 'post',
        'post_status' => '',
        'posts_per_page' => 5,
        'paged' => 1,
        'fields' => '',
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $args = array_merge($defaults, $args);
    $rows = array_values($GLOBALS['wp_posts']);
    $rows = array_filter($rows, static function (array $post) use ($args): bool {
        if (!empty($args['post_type']) && ($post['post_type'] ?? '') !== $args['post_type']) {
            return false;
        }
        if (!empty($args['post_status']) && ($post['post_status'] ?? '') !== $args['post_status']) {
            return false;
        }
        if (!empty($args['meta_key'])) {
            $meta = $GLOBALS['wp_post_meta'][(int) $post['ID']][$args['meta_key']] ?? null;
            if ((string) $meta !== (string) ($args['meta_value'] ?? '')) {
                return false;
            }
        }
        if (!empty($args['date_query'][0]['before'])) {
            $column = $args['date_query'][0]['column'] ?? 'post_date_gmt';
            if (strtotime((string) ($post[$column] ?? 'now')) >= strtotime((string) $args['date_query'][0]['before'])) {
                return false;
            }
        }
        return true;
    });

    $order = strtoupper((string) $args['order']) === 'ASC' ? 1 : -1;
    usort($rows, static function (array $a, array $b) use ($args, $order): int {
        $orderby = strtoupper((string) $args['orderby']);
        if ('ID' === $orderby) {
            return ((int) $a['ID'] <=> (int) $b['ID']) * $order;
        }
        return strcmp((string) ($a['post_date_gmt'] ?? ''), (string) ($b['post_date_gmt'] ?? '')) * $order;
    });

    $limit = max(1, (int) $args['posts_per_page']);
    $page = max(1, (int) $args['paged']);
    $rows = array_slice($rows, ($page - 1) * $limit, $limit);
    if ('ids' === $args['fields']) {
        return array_map(static fn(array $row): int => (int) $row['ID'], $rows);
    }
    return array_map(static fn(array $row): object => (object) $row, $rows);
}

function wp_next_scheduled(string $hook) {
    return $GLOBALS['wp_schedules'][$hook]['timestamp'] ?? false;
}
function wp_schedule_event(int $timestamp, string $recurrence, string $hook, array $args = array(), bool $wp_error = false): bool {
    $GLOBALS['wp_schedules'][$hook] = array('timestamp' => $timestamp, 'recurrence' => $recurrence, 'args' => $args);
    return true;
}
function wp_clear_scheduled_hook(string $hook, array $args = array(), bool $wp_error = false): int {
    if (!isset($GLOBALS['wp_schedules'][$hook])) {
        return 0;
    }
    unset($GLOBALS['wp_schedules'][$hook]);
    return 1;
}

require_once FOUNDATION_PATH . 'includes/foundation-core.php';
require_once FOUNDATION_PATH . 'includes/foundation-pricing.php';
require_once FOUNDATION_PATH . 'includes/class-foundation-submissions.php';
require_once FOUNDATION_PATH . 'includes/foundation-email-handler.php';

function foundation_test_reset_state(): void {
    $GLOBALS['wp_options'] = array(
        'admin_email' => 'admin@example.test',
        'blogname' => 'Inkfire Test',
    );
    $GLOBALS['wp_transients'] = array();
    $GLOBALS['wp_posts'] = array();
    $GLOBALS['wp_post_meta'] = array();
    $GLOBALS['wp_next_post_id'] = 1;
    $GLOBALS['wp_schedules'] = array();
    $GLOBALS['wp_registered_post_types'] = array();
}
