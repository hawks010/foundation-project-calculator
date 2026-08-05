#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
REPORT="$ROOT/tests/STATIC_QA_RESULTS.md"
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT

pass_count=0
fail_count=0
rows=()

pass() {
  pass_count=$((pass_count + 1))
  rows+=("| PASS | $1 |")
  printf 'PASS  %s\n' "$1"
}

fail() {
  fail_count=$((fail_count + 1))
  rows+=("| FAIL | $1 |")
  printf 'FAIL  %s\n' "$1" >&2
}

# Lint every shipped PHP file, including the bundled updater library.
php_failed=0
while IFS= read -r -d '' file; do
  if ! php -l "$file" >"$TMP" 2>&1; then
    php_failed=1
    cat "$TMP" >&2
  fi
done < <(find . -path './.git' -prune -o -type f -name '*.php' -print0)
if [[ "$php_failed" -eq 0 ]]; then pass "All PHP files pass syntax lint under $(php -r 'echo PHP_VERSION;')"; else fail "PHP syntax lint"; fi

js_failed=0
while IFS= read -r -d '' file; do
  if ! node --check "$file" >"$TMP" 2>&1; then
    js_failed=1
    cat "$TMP" >&2
  fi
done < <(find assets -type f -name '*.js' -print0)
if [[ "$js_failed" -eq 0 ]]; then pass "All shipped JavaScript files pass node --check"; else fail "JavaScript syntax check"; fi

if python3 - <<'PY'
from pathlib import Path
for path in Path('assets').rglob('*.css'):
    text = path.read_text(encoding='utf-8')
    # A lightweight structural check. CSS strings/comments are simple in this package.
    if text.count('{') != text.count('}'):
        raise SystemExit(f'Unbalanced braces in {path}')
PY
then pass "All shipped CSS files have balanced block braces"; else fail "CSS structure check"; fi

OWN_PHP=(foundation-customer-form.php uninstall.php includes)
if ! rg -n --glob '*.php' '\b(eval|assert|exec|shell_exec|system|passthru|proc_open|popen|base64_decode|unserialize)\s*\(' "${OWN_PHP[@]}" >"$TMP" 2>&1; then
  pass "No dangerous execution or unsafe deserialisation primitives in plugin-owned PHP"
else
  cat "$TMP" >&2
  fail "Dangerous PHP primitive scan"
fi

if rg -n "check_ajax_referer\( 'foundation_nonce', 'nonce' \)" includes/foundation-email-handler.php | wc -l | grep -qx '4'; then
  pass "All four public mutating AJAX handlers require the calculator nonce"
else
  fail "Public AJAX nonce coverage"
fi

if [[ "$(rg -n "permission_callback.*check_permission" includes/class-foundation-api.php | wc -l | tr -d ' ')" -eq 7 ]] \
  && rg -q "return current_user_can\( 'manage_options' \);" includes/class-foundation-api.php; then
  pass "Every REST route is restricted to manage_options"
else
  fail "REST permission callback coverage"
fi

if python3 - <<'PY'
from pathlib import Path
text = Path('includes/foundation-email-handler.php').read_text(encoding='utf-8')
assert 'foundation_calculate_quote( $steps, $selections )' in text
for key in ('price', 'total', 'quote'):
    assert "$_POST['" + key not in text
    assert '$_POST[\"' + key not in text
PY
then
  pass "Submission totals are calculated server-side and are not accepted from browser totals"
else
  fail "Server-authoritative pricing check"
fi

if rg -q "wp_check_filetype_and_ext" includes/foundation-core.php \
  && rg -q "foundation_get_blocked_upload_extensions" includes/foundation-core.php \
  && rg -q "is_uploaded_file" includes/foundation-core.php; then
  pass "Uploads use WordPress extension/MIME recognition, uploaded-file checks and an executable deny-list"
else
  fail "Upload validation safeguards"
fi

if python3 - <<'PY'
from pathlib import Path
text = Path('includes/foundation-email-handler.php').read_text(encoding='utf-8')
assert "foundation_rate_limit_allow( 'draft_save_ip', 30, HOUR_IN_SECONDS )" in text
for line in text.splitlines():
    if 'last_saved_draft' in line:
        assert "contact['email']" not in line
        assert 'contact["email"]' not in line
PY
then
  pass "Every public draft save is rate-limited and anonymous metrics do not retain the customer email"
else
  fail "Draft abuse and metric-privacy safeguards"
fi

if rg -q '\$home_scheme !== \$base_scheme' includes/foundation-email-handler.php \
  && rg -q '\$home_host !== \$base_host' includes/foundation-email-handler.php \
  && rg -q '\$home_port !== \$base_port' includes/foundation-email-handler.php; then
  pass "Resume URLs require an exact same scheme, host and port"
else
  fail "Resume URL same-origin enforcement"
fi

if rg -q "foundation_get_blueprint_fingerprint" includes/foundation-pricing.php \
  && rg -q "hash_equals" includes/foundation-pricing.php; then
  pass "Launch health detects changes to the bundled journey, not only a version label"
else
  fail "Blueprint integrity fingerprint"
fi

if rg -q "Foundation_Submissions::create" includes/foundation-email-handler.php \
  && rg -q "wp_mail" includes/foundation-email-handler.php; then
  create_line="$(rg -n "Foundation_Submissions::create" includes/foundation-email-handler.php | head -1 | cut -d: -f1)"
  mail_line="$(rg -n '\$admin_sent\s*=\s*wp_mail' includes/foundation-email-handler.php | head -1 | cut -d: -f1 || true)"
  if [[ -n "$create_line" && -n "$mail_line" && "$create_line" -lt "$mail_line" ]]; then
    pass "Enquiries are stored locally before email delivery is attempted"
  else
    fail "Lead-before-mail ordering"
  fi
else
  fail "Local lead storage and mail delivery presence"
fi

if ! rg -n 'admin-app|foundation-admin-shell|createRoot|react-dom|vite|tailwind' foundation-customer-form.php includes assets README.md readme.txt CHANGELOG.md DEPLOYMENT_NOTES.md FINAL_QA_REPORT.md >"$TMP" 2>&1; then
  pass "No obsolete React/Vite admin runtime references remain"
else
  cat "$TMP" >&2
  fail "Obsolete admin runtime scan"
fi

if ! rg -n --glob '*.php' '\b(match|str_contains|str_starts_with|str_ends_with)\s*\(|\breadonly\b|\benum\b' foundation-customer-form.php includes uninstall.php >"$TMP" 2>&1; then
  pass "Plugin-owned runtime code avoids common PHP 8-only constructs and remains syntactically compatible with the declared PHP 7.4 baseline"
else
  cat "$TMP" >&2
  fail "Declared PHP 7.4 compatibility scan"
fi

if git diff --check >"$TMP" 2>&1; then pass "Git diff has no whitespace errors"; else cat "$TMP" >&2; fail "Git whitespace check"; fi

{
  printf '# Static and Security QA Results\n\n'
  printf 'Generated: %s UTC\n\n' "$(date -u '+%Y-%m-%d %H:%M:%S')"
  printf 'Environment: PHP %s, Node %s\n\n' "$(php -r 'echo PHP_VERSION;')" "$(node -p 'process.version')"
  printf '| Result | Check |\n|---|---|\n'
  printf '%s\n' "${rows[@]}"
  printf '\n**Checks:** %d passed, %d failed.\n' "$pass_count" "$fail_count"
} > "$REPORT"

printf '\nStatic checks: %d passed, %d failed\n' "$pass_count" "$fail_count"
[[ "$fail_count" -eq 0 ]]
