=== Foundation Project Calculator ===
Contributors: Inkfire
Tags: project calculator, estimate, quote, accessibility, lead capture
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.6.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A guided Inkfire project calculator with a guarded visual journey editor, editable prices, secure server-side estimates, tailored-quote routing, and a private enquiry inbox.

== Description ==

Foundation Project Calculator converts Inkfire's Web, Tech, and Business pricing into a clear multi-step customer journey.

Version 1.6 adds a premium retention journey, soft name/email magic-link saving, anonymous same-device recovery, unfinished project briefs, funnel analytics and optional server-validated Cloudflare Turnstile while retaining the guarded Visual Journey Editor.

Key features:

* Multi-route calculator covering Web & Accessibility, Tech & Support, and Business Support & Marketing.
* Customers can combine several service routes in one estimate.
* Separate one-off and monthly totals.
* Exact, range, and tailored-quote outcomes.
* Live customer estimate summary with desktop panel and mobile sticky drawer.
* Premium Brief → Plan → Quote progress with route-completion rewards.
* Optional early name/email save with an explicit Continue without saving path.
* Private magic-link resume plus anonymous same-device recovery without contact PII in localStorage.
* Unfinished project-brief inbox and aggregate retention funnel.
* Optional separate marketing consent, never inferred from save/resume.
* Marketing consent is server-gated and ignored unless that separate opt-in is enabled.
* Optional Cloudflare Turnstile with server-side action/hostname validation.
* Server-authoritative price calculation and route validation.
* Thirty-eight editable prices grouped into plain admin sections.
* Structured visual customer-journey editor with per-card AJAX save, drag/keyboard reorder, + Add, Duplicate, safe connection editing, drafts and one-step undo.
* Blueprint integrity check and safe one-click migration with backup.
* Private enquiry inbox stored before email delivery is attempted.
* Branded team and customer emails.
* Optional PDF, JSON, and ZIP staff packages.
* Save-and-resume links restricted to the current site origin.
* Nonce checks, rate limits, honeypot handling, duplicate protection, and bounded input.
* Strict upload validation and executable-format deny-list.
* Automatic lead retention plus WordPress privacy exporter and eraser integration.
* Responsive keyboard and focus handling for the public modal.
* GitHub release updates through the bundled update checker.

All bundled prices are shown excluding VAT. The calculator is a planning estimate rather than a binding quotation.

== Installation ==

1. Back up the WordPress database and current calculator plugin.
2. Upload the `foundation-project-calculator` folder to `/wp-content/plugins/`.
3. Activate the plugin.
4. Open **Foundation > Project Calculator**.
5. On an upgrade, use **Back up and apply journey** when the migration notice appears.
6. Set the real recipient and verified sender under **Emails & branding**.
7. Add `[foundation_form]` to the calculator page.
8. Complete one exact-price, one range, and one tailored-quote submission on staging.

== Usage ==

Standard calculator:

`[foundation_form]`

Custom label:

`[foundation_form label="Plan my project"]`

Hide the built-in launch button:

`[foundation_form button="false"]`

Custom triggers can use `.foundation-trigger` or `data-foundation-calculator-open` while the shortcode remains on the page.

== Upgrade notice ==

= 1.6.2 =

* Fixes a bug where correcting a customer's email in the lead inbox revoked their saved-brief link without automatically sending a replacement.
* Fixes plugin uninstall so it also removes unfinished saved briefs, not only completed enquiries.
* Fixes the anonymous draft-save endpoint so it always issues a fresh private token instead of accepting a client-supplied one.
* Fixes the Turnstile secret key field so it is never echoed back into the settings form.
* Fixes a layout bug where the "Continue where I left off" card on the opening screen could push content below the visible area with no way to scroll to it.

= 1.6.1 =

* Adds lead workflow labels, inline follow-up/resend actions, editable customer name/email, safe permanent delete and Clear archived controls to the private calculator inbox.
* Revokes an old saved-brief link when the customer email changes or a brief is deleted; resending commits the replacement link only after the new email is accepted by WordPress mail.
* Adds an explicit Minimise control for expanded Journey Editor cards.
* Adds native WordPress Media Library pickers for Opening, Contact and optional Success images directly in the visual journey hierarchy, with AJAX save/remove and graceful no-image layout.
* Keeps database schema 1.4.0 and preserves the current journey, prices, settings, drafts and enquiries during an in-folder upgrade.

= 1.6.0 =

* Adds the premium Brief → Plan → Quote opening, responsive mobile brand hero, route progress, live pricing rewards, route completion and mobile estimate drawer.
* Adds optional early name/email capture with Skip, private magic-link resume, anonymous same-device recovery and reuse of captured identity at final contact.
* Stores unfinished project briefs privately with progress, quote, captured/verified state and separate marketing-consent state.
* Adds first-party funnel and screen-friction metrics plus stale-failure diagnostic cleanup.
* Adds configurable abuse/retention controls and optional Cloudflare Turnstile on magic-link/final-submit actions with server-side action/hostname checks.
* Keeps database schema 1.4.0 and preserves the current Inkfire journey, prices, settings and completed enquiries during an in-folder upgrade.

= 1.5.0 =

* Replaces the read-only Customer Journey map with a structured Visual Journey Editor.
* Adds per-card AJAX edit/save, lane and per-card + Add, Duplicate, pointer drag and keyboard Move controls, plain-English route connections, draft state and one-step Undo.
* New and duplicated screens stay unconnected/offline until an administrator deliberately connects them; backwards/broken routes are rejected by structural validation.
* Pricing formulas and monetary values remain protected in the mini builder and public totals remain server-authoritative.
* Keeps the 1.4.4 public Inkfire frontend, Cloudflare/Elementor/resume/email fixes and WCAG 2.2 AA-focused safeguards unchanged.
* Does not migrate the database or reset stored pricing, journey data, settings, drafts, or enquiries.

= 1.4.3 =

* Exempts the critical calculator loader from Cloudflare Rocket Loader so an immediate first click on the public `#foundation-launch-btn` is not lost to ordinary hash navigation.
* Restores the original Inkfire dark-glass/gradient frontend appearance on the current 1.4.x renderer, with an accessible persistent light/dark theme control.
* Improves field-error announcement/association, keyboard focus handling, target sizing, 320px reflow, reduced motion, forced colours, and increased-contrast support.
* Retains the 1.4.2 body-level overlay, private resume, and genuine PDF/JSON/ZIP attachment repairs.
* Does not migrate the database or reset the Inkfire 2026 journey, prices, email settings, or enquiries.

= 1.4.2 =

* Moves the calculator overlay to a direct child of the document body so Elementor/footer stacking contexts and overflow clipping cannot paint it behind the page.
* Re-checks the overlay placement each time the calculator opens and when a loader error is displayed.
* Adds screenshot-aware browser regressions that verify the modal is the topmost painted element at its centre on mobile and desktop.
* Sends generated staff reports with genuine `.pdf`, `.json`, and `.zip` extensions instead of the WordPress `.tmp` filenames Outlook treated as unsupported.
* Adds report magic-byte, JSON-reference, ZIP-entry, extension, allow-list, and cleanup regressions.

= 1.4.1 =
Fixes the mobile external launch link used on the Inkfire quote page and restores saved estimates to the correct screen after asynchronous loading.

= 1.4.0 =
The pricing journey has been rebuilt from the August 2026 Inkfire board. Existing real journeys are preserved until an administrator explicitly backs them up and applies the bundled journey.

== Frequently Asked Questions ==

= Will an email failure lose the enquiry? =

No. A completed enquiry is stored in the private calculator inbox before WordPress attempts email delivery.

= Are the browser totals trusted? =

No. The server validates the answers and calculates the final stored estimate from the live price catalogue.

= Does the plugin include VAT? =

The bundled prices exclude VAT. The public VAT note is editable under Emails & branding.

= Can staff change the customer questions? =

Yes. Version 1.6.1 retains the guarded Visual Journey Editor introduced in 1.5.0. Administrators can expand a card, edit customer-facing copy and safe field settings, add or duplicate draft screens, add service choices, connect screens to compatible earlier answers, and reorder siblings. Pricing formulas remain protected and monetary values stay in the Prices tab.

= Does ZIP packaging always work? =

ZIP packaging requires the PHP `ZipArchive` extension. PDF and JSON reports can still be attached when it is unavailable.

= Is this release fully approved for production? =

It is a production candidate. Complete the staging, SMTP, theme, cache, and host checks in the deployment notes before release.

== Changelog ==

= 1.6.2 =
* Fixes the admin email-correction flow to auto-send a replacement magic link and only revoke the previous one once the replacement is confirmed sent, instead of revoking immediately with resend left as a separate manual step.
* Fixes plugin uninstall to also purge unfinished saved-brief records and their associated transients, matching the existing behaviour for completed enquiries.
* Fixes the public draft-save endpoint to always generate a fresh, server-random resume token for a new draft, closing a path where a well-formed client-supplied token could be accepted as-is.
* Fixes the Turnstile secret key admin field to render blank rather than echoing the stored secret into the page, with leave-blank-to-keep-current semantics on save.
* Fixes a CSS layout bug on the opening screen where the returning-visitor "Continue where I left off" card could push content past the visible modal height with no way to scroll to it; also tightens the opening headline sizing.

= 1.6.1 =
* Adds the managed lead inbox workflow controls, customer detail correction, follow-up/resend, delete and archived cleanup.
* Revokes replaced/deleted brief links safely and avoids invalidating a working link before a replacement email succeeds.
* Adds Journey card minimising and native WordPress Media Library controls for opening/contact/success images.
* Adds optional success imagery and graceful no-image public layouts without changing pricing or the database schema.

= 1.6.0 =
* Adds the premium retention flow, responsive branded mobile intro, route progress, price rewards and mobile live-estimate drawer.
* Adds soft early lead capture, private magic-link resume, anonymous local recovery, unfinished project briefs and identity reuse.
* Adds retention funnel/friction metrics, separate optional marketing consent, configurable retention/rate limits and stale-failure cleanup.
* Adds optional Cloudflare Turnstile with server-side Siteverify success/action/hostname validation while keeping the secret out of public config/exports.
* Retains the 1.5 Visual Journey Editor, server-authoritative pricing, Elementor/Cloudflare loader repairs and database schema 1.4.0.

= 1.5.0 =
* Adds the structured Visual Journey Editor with per-card AJAX saving, + Add, Duplicate, drag/keyboard reorder, safe plain-English connections, offline drafts and one-step Undo.
* Preserves the 1.4.4 public frontend and server-authoritative pricing/submission behaviour.

= 1.4.3 =
* Marks the critical inline/dynamic customer scripts `data-cfasync="false"` for Cloudflare Rocket Loader compatibility.
* Ports the 1.3.5 Inkfire glass/gradient presentation onto the current frontend renderer without restoring old backend or pricing logic.
* Adds persistent dark/light theme state and extends the restored design across the full customer journey.
* Adds accessible inline errors, stronger focus/target/reflow/contrast safeguards, and a WCAG 2.2 AA-focused browser regression suite.
* Keeps 1.4.2 pricing, submission, resume, attachment, admin, REST, storage, and database behaviour.

= 1.4.2 =
* Moves the calculator overlay out of Elementor/footer stacking contexts and into the document body before opening.
* Re-checks overlay placement on every open and loader error.
* Sends staff reports with genuine `.pdf`, `.json`, and `.zip` filenames instead of Outlook-blocked WordPress `.tmp` names.
* Adds mobile and desktop paint-order regressions plus deterministic report-extension coverage.
* Leaves pricing, submission storage, email wording and recipients, admin, REST, and database schema unchanged.

= 1.4.1 =
* Recognises external links targeting `#foundation-launch-btn`, including the visible mobile quote CTA.
* Prevents the resume lazy loader from racing the saved-state request.
* Adds a clear restoring state and reliably redraws an already-open calculator after saved answers arrive.
* Adds browser regression coverage for the 375 px mobile trigger and delayed private resume restoration.

= 1.4.0 =
* Rebuilt the calculator around Mali's August 2026 Inkfire pricing board.
* Added 28 guided screens, 49 questions, 38 editable prices, and 27 verified route targets.
* Replaced the complex everyday admin flow editor with a server-rendered dashboard and safe price editor.
* Added a private local enquiry inbox and stored leads before mail delivery.
* Added exact, range, monthly, and tailored-quote outcomes.
* Added server-authoritative pricing, strict route validation, duplicate locks, rate limits, and same-origin resume links.
* Added journey integrity checks, safe backup/apply migration, and restore controls.
* Added deterministic PHP, static-security, and Chromium journey checks.

= 1.3.5 =
* Previous administration and customer-journey release.

= 1.0.0 =
* Initial release.
