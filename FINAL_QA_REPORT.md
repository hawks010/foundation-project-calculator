# Foundation Project Calculator 1.6.1 Final Local QA Report

**QA date:** 7 August 2026
**Candidate:** 1.6.1
**Result:** **PASS — production-ready release candidate for controlled Inkfire deployment.**

This is source/package approval, not a claim that 1.6.1 has already passed the live Inkfire Cloudflare, SMTP, NitroPack/LiteSpeed and browser/screen-reader stack.

## Release intent

Version 1.6.1 is a focused admin-management and journey-authoring release on top of the 1.6.0 retention/lead-capture build. It does not replace the pricing engine, customer journey data model or public calculator architecture.

The release adds:

- a practical lead-management inbox with workflow labels: **New, In progress, Follow up, Waiting, Complete, Archived**;
- AJAX workflow updates without page reloads;
- lead detail controls to correct customer name/email, follow up by email, resend a private magic link and permanently delete a controlled record;
- safe **Clear archived** rather than a destructive clear-all action;
- automatic invalidation of an unfinished brief's previous magic link when the customer's email changes;
- mail-failure-safe magic-link rotation so an existing valid link is not destroyed unless the replacement email is accepted for sending;
- improved padding, spacing, controls and responsive behaviour in the Enquiries screen;
- **Minimise** controls for expanded Visual Journey Editor cards;
- WordPress Media Library selectors, stored by AJAX, for the **Opening**, **Contact** and **Success** images;
- graceful image removal so a missing image does not leave a blank heritage panel on the customer frontend;
- an independent optional success/closing image setting.

## Deterministic application/security tests

**67 passed, 0 failed.**

Coverage includes the existing pricing, routing, validation, privacy, retention, duplicate protection, save/resume and Visual Journey Editor cases plus new 1.6.1 tests for:

- workflow labels without mutation of quote data;
- customer-detail correction on saved briefs;
- changed-email verification reset;
- previous magic-link revocation after email correction;
- archived-only bulk clearing;
- independent success-image sanitisation.

The known full Web example remains **£4,260 one-off + £45/month**, excluding VAT.

## Static / syntax / security suite

**24 passed, 0 failed.**

The suite confirms, among other checks:

- all plugin PHP and JavaScript syntax passes;
- all public mutations retain nonce protection;
- Journey Editor mutations remain `manage_options` + nonce protected;
- all lead-management mutations are `manage_options` + nonce protected;
- the three journey media slots are allow-listed and use the native WordPress Media Library;
- pricing remains server-authoritative;
- report attachment extensions remain allow-listed;
- upload, throttling, Turnstile, resume-origin and enquiry-before-email protections remain in place;
- the critical Cloudflare Rocket Loader exclusion remains in the frontend loader;
- reduced-motion/high-contrast/validation accessibility hooks remain present.

## Visual Journey Editor browser QA

**16 passed, 0 failed.**

Verified:

- the structured Start / Web / Tech / Business hierarchy;
- Add, Duplicate, pointer drag and keyboard Move controls;
- expansion without navigation;
- **Minimise** from both the card header and editor footer without reload;
- Add choice and per-card AJAX save;
- lane Add and per-card Add-after;
- keyboard and pointer reorder paths;
- Opening, Contact and Success image slots;
- native `wp.media()` image-only picker configuration;
- AJAX media save;
- responsive admin reflow;
- keyboard alternative to drag.

## Lead-management browser QA

**7 passed, 0 failed.**

Verified:

- padded unfinished/completed lead sections;
- workflow, follow-up and magic-link controls in the inbox;
- inline workflow AJAX save with no page reload;
- private magic-link resend;
- customer name/email correction;
- changed-email state visibly requiring a fresh magic link;
- resend and permanent-delete controls on lead detail;
- mobile admin reflow without whole-page horizontal overflow.

## Customer browser regressions

All existing customer-facing suites pass:

- **Complete Chromium quote smoke:** PASS
- **Immediate mobile CTA / Elementor stacking:** PASS
- **Immediate desktop CTA / Elementor stacking:** PASS
- **Delayed private magic-link resume:** PASS
- **Retention / lead-capture browser suite:** PASS
- **WCAG 2.2 AA-focused browser suite:** PASS

The public 1.6.0 retention UX, premium opening, live estimate, same-device recovery, early optional email capture, magic-link flow, route-completion reward and contact reuse remain intact.

Automated accessibility evidence is not formal WCAG certification. Final live keyboard and screen-reader acceptance remains required.

## Data/schema position

- Plugin version: **1.6.1**
- `FOUNDATION_DB_VERSION`: **1.4.0**
- Blueprint: **2026.08.05**
- Expected current journey: **28 screens / 49 fields / 38 prices / 27 connected route targets**
- **No database migration** is introduced by 1.6.1.
- Upgrade does not intentionally reapply the blueprint, reset prices, overwrite email settings or remove existing enquiries/project briefs.

## Remaining live-only gates

Before labelling 1.6.1 production accepted on Inkfire:

1. take a fresh file/database rollback snapshot;
2. replace the plugin in the existing folder and confirm 1.6.1 is active;
3. purge NitroPack, WordPress object cache and host LiteSpeed cache;
4. prove the immediate real homepage CTA still defeats Cloudflare Rocket Loader;
5. create one controlled unfinished brief, change its test email, confirm the old magic link is rejected and a newly resent link restores the brief;
6. exercise workflow labels, Follow up, Archive/Clear archived and controlled record deletion;
7. expand and minimise a Journey card;
8. change and restore Opening/Contact/Success images using the live WordPress Media Library;
9. remove one image temporarily and prove the frontend collapses gracefully rather than rendering a blank panel;
10. run a controlled final submission and confirm SMTP, Reply-To, PDF/JSON/ZIP and duplicate protection;
11. perform final browser, keyboard and screen-reader checks.

## Recommendation

Approve the exact packaged 1.6.1 artifact for **controlled deployment and live acceptance**. Do not reapply Mali's blueprint during the upgrade.
