#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
REPORT="$ROOT/tests/BROWSER_SMOKE_RESULTS.md"
SCREENSHOT="$ROOT/tests/browser/browser-smoke.png"
SERVER_LOG="$ROOT/tests/browser/http-server.log"
PLAYWRIGHT_OUT="$ROOT/tests/browser/playwright-result.txt"

php tests/browser/generate-harness.php
node --check tests/browser/browser-smoke-runner.js

: >"$SERVER_LOG"

set +e
timeout 40s xvfb-run -a /opt/pyvenv/bin/python tests/browser/run-playwright.py >"$PLAYWRIGHT_OUT" 2>"$ROOT/tests/browser/playwright-errors.log"
code=$?
set -e

result="FAIL"
message="Browser smoke did not report a pass."
if [[ "$code" -eq 0 ]] && grep -q '^PASS' "$PLAYWRIGHT_OUT"; then
  result="PASS"
  message="Complete Web & Accessibility route, live/review totals, contact submission, success receipt and clean restart passed in Chromium via Playwright."
else
  message="$(tr '\n' ' ' <"$PLAYWRIGHT_OUT" | sed 's/[[:space:]]\+/ /g')"
  [[ -n "$message" ]] || message="Playwright exited with code $code."
fi

{
  printf '# Browser Smoke Test Results\n\n'
  printf 'Generated: %s UTC\n\n' "$(date -u '+%Y-%m-%d %H:%M:%S')"
  printf -- '- Browser: Chromium %s controlled by Playwright 1.57.0 under Xvfb\n' "$(chromium --version | sed 's/^Chromium //')"
  printf -- '- Result: **%s**\n' "$result"
  printf -- '- Journey: Web & Accessibility → small website → 5 pages → WooCommerce → Hosting + plugins → 26–50 alt texts → accessibility testing and setup\n'
  printf -- '- Expected review totals: **£4,260 one-off + £45/month**, excluding VAT\n'
  printf -- '- Submission response: mocked transport, real calculator rendering, branching, validation and interaction logic\n'
  printf -- '- Evidence: `tests/browser/browser-smoke-success.png`, `tests/browser/browser-smoke.png`, `tests/browser/browser-smoke-dom.html`, and `tests/browser/browser-console.json`\n\n'
  printf '%s\n' "$message"
} > "$REPORT"

printf '%s  %s\n' "$result" "$message"
[[ "$result" == "PASS" ]]
