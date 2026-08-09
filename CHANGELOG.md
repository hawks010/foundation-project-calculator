# Changelog

## 1.6.3, 9 August 2026

- Rewrites the PDF attachment as a branded, properly laid-out document: masthead, contact block,
  the customer's answers grouped by section in plain English, an itemised estimate table with
  right-aligned amounts, emphasised totals and the VAT/planning-estimate small print. Adds a real
  text-layout engine (Helvetica metrics for wrapping and right alignment, multi-page handling).
- Fixes currency symbols being stripped from the PDF. The report fonts now declare
  `/WinAnsiEncoding` and text is converted to Windows-1252, so `£` renders instead of vanishing —
  previously a total read "11,000 excluding VAT" with no currency symbol at all.
- Ships a single PDF attachment rather than a ZIP bundling a PDF, a JSON file and a README, so
  non-technical recipients get one readable document.
- Adds a thank-you message, an expected reply time and social links beneath the estimate on the
  final screen, reusing the social URLs already configured for the customer email.
- Adds a **Success screen wording** panel to the Customer Journey tab covering the thank-you
  message, reply time, follow-us heading and all five social links, so the copy can be updated
  without a developer.
- Fixes the final screen collapsing to an unbalanced full-width layout when no closing image is
  set: the content column is now capped and centred, so it reads correctly with or without an image.
- Fixes the consent and marketing tick boxes sitting out of line with their labels. A later rule
  resized the control from 20px to 24px without updating the offset tuned for the smaller box.
- Rewrites the **Need help** panel, which mixed internal wording ("tailored work", "without
  blocking the rest of the estimate") with an odd fallback heading, into plain customer-facing
  English.
- Keeps `FOUNDATION_DB_VERSION` at 1.4.0. No schema migration or blueprint reapply is required.

## 1.6.2, 8 August 2026

Fixes found during an independent pre-launch QA pass of 1.6.1.

- Fixes the admin email-correction flow (`Foundation_Admin::ajax_lead_update()`): the previous
  saved-brief magic link is no longer revoked (`Foundation_Submissions::update_lead_contact()`)
  until a replacement has been generated and successfully emailed
  (`Foundation_Admin::send_admin_magic_link()`), auto-triggered on a successful email correction
  instead of requiring a separate manual "Resend" click. A failed send now leaves the previous
  link fully working and reports the failure back to the admin.
- Fixes `uninstall.php`: adds a batched delete loop for `foundation_brief` posts (unfinished saved
  briefs), mirroring the existing loop for completed `foundation_quote` enquiries, and extends the
  transient-prefix cleanup to include magic-link revocation markers and admin-resend cooldowns.
- Fixes `foundation_save_quote_draft()`: a new draft now always receives a fresh
  `foundation_generate_resume_token()` value; a client-supplied token is only reused when it
  already resolves to an existing brief, closing a path where a well-formed but attacker-chosen
  token could be accepted verbatim.
- Fixes the Turnstile secret key field in Advanced settings (`class-foundation-admin.php`): it now
  renders blank instead of echoing the stored secret's value into the page source, and an empty
  submission leaves the stored secret unchanged rather than clearing it.
- Fixes a CSS layout bug: `.foundation-canvas` was missing `height: 100%` under the intro and
  contact views, so it grew to its full content height instead of respecting its container and
  scrolling — meaning the "Continue where I left off" local-recovery card could push the CTA and
  small print below the visible modal with no way to scroll to them on shorter viewports. Also
  tightens `.foundation-intro-heritage h1` sizing/margin so the opening headline needs less
  vertical space.
- Keeps `FOUNDATION_DB_VERSION` at 1.4.0. No schema migration or blueprint reapply is required.

## 1.6.1, 7 August 2026

- Turns the Enquiries screen into a practical lead-management inbox with clearer card padding, inline workflow states (**New, In progress, Follow up, Waiting, Complete, Archived**), direct follow-up links and magic-link resend controls.
- Adds a dedicated lead-management panel on brief/enquiry detail views for correcting customer name/email, changing workflow state, resending a private return link and permanently deleting a record.
- Adds a safe **Clear archived** action instead of a destructive blanket inbox wipe.
- Revokes the previous saved-brief magic link when an administrator changes the customer email or deletes the brief, preventing an old link from verifying a corrected address. A replacement link is only committed after WordPress accepts the new email hand-off.
- Makes expanded Visual Journey Editor cards explicitly collapsible again with **Minimise card** and a toggle label that switches between Edit/Minimise.
- Adds inline WordPress Media Library controls directly to the Journey hierarchy for the opening image, contact hand-off image and optional closing/success image. Images save/remove by protected AJAX without a full-page reload.
- Makes the image thumbnail itself open the native WordPress image chooser and keeps image removal graceful: if an opening/contact image is removed, the customer layout no longer reserves an empty visual panel.
- Adds optional `success_image_url` branding support while leaving the current Inkfire journey, prices and database schema untouched.
- Adds browser regressions for card minimising, native Media Library contract, lead workflow/resend/contact controls and responsive admin layout.
- Keeps `FOUNDATION_DB_VERSION` at 1.4.0. No schema migration or blueprint reapply is required.

## 1.6.0, 7 August 2026

- Reworks the customer opening into a premium Inkfire **Brief → Plan → Quote** experience while retaining the original split-image character on desktop and a compact branded image hero on mobile.
- Fixes light-mode primary/theme glass treatment to use sufficiently dark Inkfire green with white text.
- Adds meaningful route progress, early live-price rewards, a dedicated route-completion moment, add-another-service choices, contextual help and a sticky/expandable mobile estimate summary.
- Adds optional early name/email capture with a clear **Continue without saving** path. Customers who save receive a private return link but continue the calculator immediately without forced verification.
- Adds anonymous same-device recovery in localStorage containing journey state only, plus a recoverable leave panel and return-visitor continuation.
- Stores email-linked unfinished project briefs privately with hashed token, progress, quote, captured/verified/converted state and separate marketing-consent state.
- Reuses captured identity at final contact to avoid redundant entry.
- Adds first-party retention funnel metrics and per-screen validation-friction counters without copying free-text answers into analytics.
- Adds optional Cloudflare Turnstile for magic-link sends and final submissions. Server-side Siteverify validation checks success, expected action and expected hostname; the secret is never emitted in public config or configuration export.
- Adds configurable magic-link resend, per-email/per-IP, draft-save and final-submit abuse thresholds plus minimum-interaction timing. Existing nonce, honeypot, duplicate locks, idempotency and same-origin resume safeguards remain.
- Extends the Enquiries admin screen with unfinished project briefs and the Overview with funnel/friction reporting.
- Repairs stale legacy failure diagnostics by timestamping/versioning new failures and clearing only the active failure after a later successful submission.
- Keeps `FOUNDATION_DB_VERSION` at 1.4.0. No schema migration or blueprint reapply is required.

## 1.5.0, 7 August 2026

- Replaces the read-only Customer Journey page with a structured Visual Journey Editor.
- Shows the hierarchy from opening slide through Start, the Web/Tech/Business route lanes, and the shared review/contact/success finish.
- Adds per-screen expand/edit controls with AJAX saving and no full-page reload.
- Adds drag-to-reorder for sibling screens plus keyboard-accessible Move up/Move down controls.
- Adds Add screen and Duplicate controls. New and duplicated screens start as unconnected drafts so they cannot affect customers until deliberately connected.
- Adds editable service choices with an Add choice control while preserving existing pricing and routing metadata for existing choices.
- Adds a plain-English connection editor that writes back to the existing route_step_ids model instead of introducing a second routing schema.
- Adds one-step Journey Editor undo and automatic structural validation. Backward routes and broken targets are rejected.
- Keeps pricing formulas protected in the mini builder and links admins to the Prices tab for monetary changes.
- Allows supported Journey Editor customisations to remain launch-healthy while direct/out-of-band journey tampering is still detected.
- Database schema remains 1.4.0. No migration is required.

## 1.4.4, 7 August 2026

### Original frontend alignment with current pricing and accessibility architecture

- Rebuilds the desktop intro as the original two-panel composition: full-height left imagery/content and a focused right-side start workspace.
- Reuses the configured testimonial image and copy for the contact hand-off slide, echoing the original visual journey without changing submissions.
- Replaces the dashboard-style labelled progress meter with the original thin segmented progress bar while retaining accessible `role="progressbar"` values and text for assistive technology.
- Restores the centred step banner, 90% inner question width, 32–40px desktop content spacing, and responsive three/two/one-column option-card rhythm from 1.3.5.
- Retains the newer right-side live estimate panel for one-off, monthly, range, and tailored-quote feedback.
- Keeps all 1.4.3 functional repairs: Cloudflare Rocket Loader opt-out, body-level overlay portal, private resume restoration, real PDF/JSON/ZIP attachment extensions, server-authoritative pricing, security controls, and current stored data.
- Keeps WCAG 2.2 AA-focused improvements including native grouped controls, textual error association, focus management, 44px+ targets, 320 CSS px reflow, reduced-motion, forced-colour, and increased-contrast handling.

## 1.4.3, 7 August 2026

### Cloudflare first-click repair, Inkfire visual restoration, and accessibility hardening

- Exempts the critical inline calculator loader from Cloudflare Rocket Loader with `data-cfasync="false"`, closing the live race where an immediately clickable `#foundation-launch-btn` could navigate to a URL fragment before the handler registered.
- Marks the dynamically inserted customer script `data-cfasync="false"` as an additional optimisation-safety guard.
- Ports the original 1.3.5 Inkfire dark-glass/gradient frontend onto the current 1.4.x renderer instead of restoring old pricing, submission, admin, or persistence code.
- Restores the dark treatment as the default and adds a persistent, accessible light/dark theme control.
- Extends the restored visual system across route selection, fields, toggles, live totals, review, contact, save/resume, loading, errors, and the success receipt.
- Adds textual inline field errors with `role="alert"`, `aria-invalid`, `aria-describedby`, and focus movement to the first invalid control.
- Strengthens focus indicators, control target sizes, 320 CSS px reflow, reduced-motion handling, forced-colour support, increased-contrast support, and focus-obscuring scroll margins.
- Adds an accessibility-focused Playwright suite covering modal semantics, target sizes, fieldset/legend labelling, keyboard answer selection, validation error association, focus trapping/return, 320px reflow, theme state, and key palette contrast.
- Strengthens regression-harness generation so the source test fails if the critical lazy loader is missing the Rocket Loader opt-out.
- Retains the 1.4.2 body-level overlay fix, saved-estimate restoration, report attachment extension fix, Inkfire 2026 blueprint, all 38 prices, submission/email logic, private enquiry storage, admin dashboard, protected REST routes, and database schema.

## 1.4.2, 6 August 2026

### Elementor visibility and Outlook attachment hotfixes

- Moves the single calculator overlay to a direct child of `document.body` before loading or opening it, preventing Elementor/footer stacking contexts and ancestor `overflow: hidden` from painting the modal behind the page.
- Re-checks overlay placement on every open and before displaying a loader error, covering dynamic page-builder reparenting as well as the normal shortcode lifecycle.
- Adds mobile and desktop browser fixtures that reproduce the live Elementor stacking context and verify actual paint order with `document.elementFromPoint()`, rather than relying on DOM/CSS visibility alone.
- Preserves `.pdf`, `.json`, and `.zip` on generated staff-report filenames. WordPress `wp_tempnam()` creates a `.tmp` path, which caused Outlook to present the staff ZIP as an unsupported attachment.
- Gives generated reports a recognisable reference-specific filename and rejects any report extension outside the fixed PDF/JSON/ZIP allow-list.
- Adds deterministic magic-byte, JSON-decoding, ZIP-entry, filename-extension, and cleanup regressions for the generated staff reports.
- Confirms the visual regression fails against unmodified 1.4.1 and passes against 1.4.2.
- Leaves the Inkfire blueprint, all 38 prices, quote calculation, submission persistence, email recipients/content, admin dashboard, protected REST routes, database schema, and migration behaviour unchanged.
- Normalises whitespace in the source-only nonce-count test so the static suite behaves consistently on macOS and Linux.

## 1.4.1, 6 August 2026

### Production-acceptance repairs

- Added the live mobile page target `#foundation-launch-btn` to the lazy-loader trigger contract, while retaining the existing shortcode button, `.foundation-trigger`, `data-foundation-calculator-open`, `foundation-form`, and `get-quote` trigger patterns.
- Removed the resume auto-open race from the lazy loader. Resume URLs now load the application without dispatching an early generic open event.
- Added a dedicated, accessible “Restoring your estimate” state while the private saved-estimate request completes.
- Redraws the active calculator when restored answers arrive, ensuring the saved screen and selected answers replace the loading state even when the modal is already open.
- Clears captured resume tokens from the visible URL as early as the browser permits.
- Added Playwright regressions for the exact 375 px external mobile CTA and a delayed private save-and-resume response.
- Made the static QA suite run correctly from an extracted source ZIP where Git metadata is intentionally absent.

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
