# Production Readiness Report

**Product:** Foundation Project Calculator
**Candidate:** 1.4.0
**Assessment date:** 5 August 2026
**Status:** Production candidate, pending real staging approval

## Executive assessment

The uploaded 1.3.x package has been reworked into a maintainable Inkfire calculator based on Mali's August 2026 board. The risky administration problem has been addressed by separating the stable route blueprint from everyday price and settings changes. The customer estimate is recalculated on the server, completed leads are stored before email, and the main abuse and data-handling paths have explicit controls.

The package is suitable for staging deployment. It should not be described as conclusively production-ready until the real WordPress, SMTP, theme, cache, firewall, and host checks pass.

## Scope completed

### Pricing and flow

- Three main routes with multi-selection.
- Twenty-eight screens and forty-nine fields.
- Thirty-eight editable price keys.
- Twenty-seven connected route targets.
- Exact, range, one-off, monthly, and manual-review outcomes.
- Conservative handling of every undefined source amount.
- Contact capture after the estimate review.

### Administration

- Server-rendered dashboard with no production compilation requirement.
- Searchable price editor.
- Read-only journey map.
- Safe migration callout for old/custom flows.
- Automatic backup before blueprint application.
- One-step journey restore.
- Blueprint content fingerprint, not only version checking.
- Private enquiry inbox and email statuses.
- Operational, retention, export, and metrics controls.

### Reliability

- Local lead write before team email.
- Human-readable reference.
- Idempotent submission token.
- Atomic short-lived duplicate lock using a unique WordPress option.
- Email failure reported without telling the customer their lead was lost.
- Bounded cleanup cron.
- Configuration export without recipient or sender addresses.

### Security and privacy

- Nonces on all four public state-changing AJAX handlers.
- `manage_options` permission on every registered REST route.
- Server-authoritative pricing.
- Visible-route required-field validation.
- Option-index, single-choice, numeric-bound, and off-route validation.
- Sanitised contact, answer, setting, and pricing data.
- Honeypot and several bounded rate limits.
- Same-origin resume URL enforcement with exact scheme, host, and port.
- HMAC-hashed draft and submission storage keys.
- Upload MIME/extension checks, PHP upload checks, and blocked active formats.
- Private, non-queryable, non-REST enquiry post type.
- Automatic data retention and WordPress privacy callbacks.
- Anonymous metrics without customer email addresses.

## Automated evidence

| Area | Result |
|---|---:|
| Deterministic PHP tests | 40 passed, 0 failed |
| Static and security checks | 15 passed, 0 failed |
| Chromium customer-journey smoke | Passed |
| PHP syntax | Passed under PHP 8.4.16 |
| JavaScript syntax | Passed under Node 22.16.0 |
| CSS structural check | Passed |
| Git whitespace check | Passed |

The Chromium test exercised the real public CSS and JavaScript through:

- Web & Accessibility
- small website
- five pages
- WooCommerce
- hosting plus plugins
- 26–50 alt texts
- accessibility testing
- accessibility setup
- contact submission and success receipt
- post-completion save-control hiding and clean restart

It verified £4,260 one-off plus £45/month, excluding VAT.

## Manual review completed locally

- Admin handlers use capability and nonce checks.
- Output escaping was checked across the dashboard and email templates.
- Public configuration excludes team addresses and server secrets.
- Submitted browser totals are not accepted.
- Enquiry storage precedes `wp_mail()`.
- Temporary reports are cleaned after mail attempts.
- Existing non-empty journeys are not overwritten on activation.
- Price updates through the protected REST route merge with the live catalogue rather than resetting omitted values.
- The updater points to the Inkfire Limited repository and boots at the correct lifecycle point.
- Uninstall clears plugin options, private enquiries, drafts, rate-limit transients, idempotency caches, and stale locks.

## Remaining release gates

| Gate | Why it remains |
|---|---|
| Real staging activation | No complete WordPress runtime or database driver was available locally |
| Live SMTP test | Mail acceptance and inbox delivery depend on the configured provider and verified sender |
| Active-theme and plugin conflict test | The production stack was not present in the local container |
| Cache/CDN test | Stale HTML or aggressive optimisation can affect the lazy loader |
| Firewall/security-plugin test | Public `admin-ajax.php` behaviour is environment-specific |
| PHP 7.4 runtime test | Only PHP 8.4 was installed locally; compatibility was checked statically |
| `ZipArchive` execution | The local PHP build did not include the extension; fallback behaviour is implemented |
| Cross-browser sign-off | Chromium was automated; Safari, Firefox, and Edge need staging checks |
| Real accessibility audit | Keyboard/focus behaviours are implemented, but formal assistive-technology testing remains |

## Release recommendation

Deploy 1.4.0 to the real staging site using `DEPLOYMENT_NOTES.md`. Approve production only after a successful exact-price journey, range journey, tailored journey, multi-route journey, save/resume cycle, local inbox record, live team email, live customer email, duplicate test, mobile check, and cache purge test.
