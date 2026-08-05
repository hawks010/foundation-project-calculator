# Changelog

## 1.4.0, 5 August 2026

### Pricing and customer journey

- Compiled Mali's August 2026 pricing board into a multi-route Inkfire journey.
- Added 28 screens, 49 fields, 38 editable prices, and 27 validated route targets.
- Allowed customers to combine Web & Accessibility, Tech & Support, and Business Support & Marketing in one estimate.
- Separated one-off totals, monthly totals, ranges, and tailored-quote items.
- Kept undefined or scope-dependent services manual rather than inventing false prices.
- Added a live estimate summary, review screen, contact screen, receipt reference, and save-and-resume journey.
- Hid save controls after completion, cleared resume tokens from the address bar, and reset completed sessions cleanly for a new estimate.
- Preserved the shortcode and external-trigger behaviour used by existing pages.

### Administration

- Replaced the difficult everyday flow-editing experience with a plain server-rendered dashboard.
- Added Overview, Enquiries, Prices, Customer journey, Emails & branding, and Advanced tabs.
- Added a searchable price editor that changes amounts without exposing route internals.
- Made the compiled customer journey read-only in normal administration.
- Added a prominent safe migration step for upgraded sites.
- Added automatic journey backup before applying the Inkfire blueprint and a one-click restore control.
- Added a structural route check plus a fingerprint that detects changes even when a version marker remains.
- Kept protected legacy REST endpoints for controlled administrative tooling.

### Lead reliability

- Added a private local enquiry store with human-readable references.
- Stored completed enquiries before attempting team or customer email delivery.
- Added team and customer mail-status reporting in the admin inbox.
- Added bounded automatic lead retention.
- Added WordPress personal-data export and erase callbacks.
- Added PDF and JSON estimate reports and optional ZIP packaging.

### Security and resilience

- Recalculated every submitted estimate on the server.
- Validated required fields only on visible routes.
- Rejected unknown option indexes, multiple answers for single-choice fields, off-step values, and out-of-range numbers.
- Added nonce checks to all public mutations and capability checks to all administrative REST routes.
- Added honeypot handling and IP, email, draft, metric, and resume rate limits.
- Added idempotency tokens and a short database-backed duplicate-submission lock.
- Hashed saved-estimate and submission tokens before using them as storage keys.
- Restricted saved-estimate links to the exact current scheme, host, and port.
- Removed customer email addresses from anonymous metrics.
- Added upload count, size, MIME, extension, and executable/browser-active file safeguards.
- Kept configuration exports free of sender and recipient addresses.

### Quality assurance

- Added deterministic PHP tests for pricing, branching, validation, privacy, storage, rate limiting, and blueprint integrity.
- Added static checks for PHP, JavaScript, CSS, security controls, and compatibility hazards.
- Added a Chromium smoke journey that exercises the real customer interface from route selection to receipt.
- Removed obsolete build-time administration files and dependencies from the production package.

## 1.3.5

- Previous calculator administration release.

## 1.3.1

- Corrected required-choice handling when cached or draft state was restored.

## 1.0.0

- Initial release.
