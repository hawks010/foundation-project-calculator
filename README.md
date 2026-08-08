# Foundation Project Calculator

**Version 1.6.1**
**Repository:** https://github.com/Inkfire-limited/foundation-project-calculator

Foundation Project Calculator is Inkfire's guided WordPress project-estimate system. It combines the August 2026 Web, Tech and Business pricing blueprint with a low-maintenance visual journey editor, server-authoritative estimates, resumable project briefs and a private lead/enquiry inbox.

## 1.6.1 admin-management scope

Version 1.6.1 is a narrow administration and journey-media release on top of the 1.6.0 retention/customer experience. Public pricing and journey data remain authoritative and the database schema is unchanged.

### Managed lead inbox

- Workflow labels: New, In progress, Follow up, Waiting, Complete and Archived.
- Inline status changes save by protected AJAX.
- Unfinished briefs expose View, Follow up and Resend link actions.
- Detail views allow staff to correct customer name/email, resend a magic link and permanently delete a record.
- Email correction invalidates the previous private return link and marks the corrected address unverified until the fresh link is used.
- Archived records can be cleared deliberately as a separate destructive action.

### Journey editor polish

- Expanded cards have an explicit **Minimise card** control and the Edit button becomes Minimise while open.
- Opening, Contact and Success nodes expose inline image controls backed by the standard WordPress Media Library.
- Image selection/removal saves by AJAX without reloading the Journey page.
- An optional Success image is supported independently of the existing opening and contact/testimonial images.
- Removing an opening/contact image does not leave an empty half-panel on the public calculator.

## 1.6.0 release scope

Version 1.6.0 is a retention, lead-capture, anti-spam and premium-customer-experience release built on the guarded 1.5.0 journey editor.

### Premium public journey

- Reworks the opening into an Inkfire project-brief experience: **Brief → Plan → Quote**.
- Keeps the original Inkfire split-image personality on desktop and preserves a compact branded image hero on smaller screens instead of dropping the human visual.
- Uses a dark glass default and a WCAG-aware light mode with a green-glass/white-text primary treatment.
- Shows meaningful within-route progress rather than an unreliable global percentage.
- Keeps the right-side live estimate on desktop and uses a sticky expandable estimate pill on mobile.
- Rewards useful progress with subtle price feedback and a dedicated route-completion screen.
- Provides contextual help and human-readable validation without dead-ending tailored/discovery services.

### Retention and resume

- Offers an optional early **name + email** capture immediately after the intro, with an unmistakable **Continue without saving** path.
- Creates a private magic-link draft when the visitor chooses to save, then lets them continue immediately without forcing inbox verification.
- Marks an email as verified only after the private resume link is actually used.
- Autosaves anonymous journey state locally without putting contact PII into `localStorage`.
- Offers **Continue where I left off** on return and a recoverable leave panel instead of silently discarding work.
- Reuses captured name/email at final submission instead of making customers enter the same details again.
- Stores unfinished email-linked briefs in a private WordPress post type with progress, current quote, verification state and marketing-consent state.

### Spam and abuse protection

- Optional Cloudflare Turnstile integration for magic-link sends and final submissions.
- Server-side Siteverify validation checks success, expected action and expected hostname; the secret key never enters public configuration or exports.
- Honeypot handling, WordPress nonces, per-IP/per-email throttles, resend cooldowns, minimum-interaction checks, strict same-origin resume URLs and existing idempotency/duplicate locks remain layered underneath.
- Magic-link tokens are stored as hashes in unfinished-brief records.
- Turnstile is intentionally disabled until real site/secret keys are configured. The other protections remain active when it is off.

### Admin intelligence

- Overview now includes a first-party project-brief funnel: opened, started, email captured, email verified, price reached, review reached and submitted.
- Per-screen metrics surface validation friction without recording free-text customer answers in analytics.
- Enquiries now includes unfinished project briefs alongside completed submissions.
- Advanced exposes retention and abuse thresholds plus Turnstile configuration.
- Legacy un-timestamped `last_failure` values no longer masquerade as current errors. New failures record time/version and a later successful submission clears the active alert.

### Journey editor retained

The 1.5.0 Visual Journey Editor remains intentionally structured rather than becoming a freeform page builder:

- per-card AJAX save;
- + Add and Duplicate;
- pointer drag plus keyboard Move up/Move down;
- safe unconnected drafts;
- plain-English connections to earlier choices;
- one-step Undo;
- pricing/formulas kept outside the mini builder.

## Current Inkfire blueprint

The bundled Inkfire 2026 blueprint contains:

- 28 journey screens;
- 49 fields;
- 38 editable prices;
- 27 connected route targets;
- Web & Accessibility, Tech & Support, and Business Support & Marketing routes that can be combined in one estimate.

Exact prices, ranges, monthly charges and discovery/tailored outcomes remain distinct. Existing real journeys are not silently replaced during upgrade.

## Administration

- **Overview:** health, funnel, screen friction, blueprint state and operational diagnostics.
- **Enquiries:** unfinished email-linked briefs plus completed private enquiries.
- **Prices:** the monetary source of truth.
- **Customer journey:** guarded visual mini builder.
- **Emails & branding:** recipients, sender identity, public wording, images, privacy and customer confirmation.
- **Advanced:** quote mode, attachments, retention, abuse controls, Turnstile, export and metrics reset.

## Requirements

- WordPress 6.4+
- PHP 7.4+
- HTTPS strongly recommended
- Verified SMTP strongly recommended
- `ZipArchive` optional; PDF and JSON reports work without it
- Cloudflare Turnstile optional but recommended on the public production calculator once real keys are configured

## Installation / upgrade

1. Back up the database and current plugin folder.
2. Install the approved production ZIP into the existing `foundation-project-calculator` plugin folder.
3. Confirm WordPress reports **1.6.1**.
4. Do **not** reapply Mali's blueprint just because the plugin version changed.
5. Confirm the stored journey/prices/email settings survived intact.
6. Configure the privacy-policy URL and verified SMTP sender.
7. Configure Turnstile site/secret keys under **Advanced** before enabling Turnstile.
8. Purge NitroPack, WordPress object cache and host LiteSpeed cache.
9. Run the live gates in `DEPLOYMENT_NOTES.md`.

`FOUNDATION_DB_VERSION` remains **1.4.0**. Version 1.6.1 does not require a schema migration.

## Shortcode and triggers

```text
[foundation_form]
```

Custom label:

```text
[foundation_form label="Plan my project"]
```

Hide the built-in trigger while retaining the calculator mount:

```text
[foundation_form button="false"]
```

Supported external triggers include `.foundation-trigger`, `data-foundation-calculator-open`, links containing `get-quote` / `foundation-form`, and the live Inkfire `href="#foundation-launch-btn"` CTA.

The critical inline/dynamic loader opts out of Cloudflare Rocket Loader and the overlay is reparented directly under `body` before display.

## Data, privacy and lead safety

- Anonymous browser recovery stores selections/current journey position only, not name/email/contact data.
- Email-linked drafts are private WordPress records and use hashed magic-link tokens.
- Email verification means the visitor used the private resume link; it is not required before continuing the calculator.
- Optional marketing permission is a separate unchecked control and is not treated as permission merely because someone requested a save/resume link.
- Completed enquiries are stored locally before mail delivery is attempted.
- WordPress personal-data exporter/eraser support covers completed enquiries and saved project briefs.
- Retention periods are configurable and cleanup is scheduled.

## Security model

The browser estimate is for UX only. Final totals are recalculated from the live server-side price catalogue. The server validates visible routes, required values, option indexes, numeric bounds, uploads and contact data.

Public mutations are protected by nonce checks plus abuse controls. Turnstile, when enabled, is validated server-side and checks the expected action/hostname. The Turnstile secret is admin-only, omitted from configuration export and never emitted into frontend configuration.

## Accessibility

The public interface is built and regression-tested around WCAG 2.2 AA-relevant behaviour:

- named modal/dialog semantics;
- native grouped choice controls;
- keyboard-operable selection and focus trap/return;
- textual error identification, association and focus movement;
- large primary pointer targets;
- persistent help once a question journey begins;
- no redundant name/email entry after early capture;
- 320 CSS px reflow;
- light/dark contrast checks;
- reduced-motion, forced-colour and increased-contrast handling;
- a recoverable close/leave experience.

Automated evidence is not a substitute for a final live keyboard and screen-reader acceptance pass.

## Development and testing

There is no production build step. PHP, CSS and JavaScript are shipped directly.

Run everything:

```bash
bash tests/run.sh
```

The suite covers deterministic pricing/data/security behaviour, static/syntax checks, complete Chromium flow, Elementor paint-order regressions, private resume, WCAG-focused browser behaviour, retention/lead-capture behaviour and the admin journey editor.

## Release status

Version 1.6.1 is packaged as a **production-ready release candidate**. Local/source-package checks must pass before the artifact is shipped. Live production acceptance still requires real Cloudflare/Turnstile, SMTP, caching and browser/screen-reader gates described in `DEPLOYMENT_NOTES.md`.
