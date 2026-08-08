# Foundation Project Calculator 1.6.1 Production Readiness Report

**Candidate:** 1.6.1
**Date:** 7 August 2026
**Local status:** **production-ready release candidate**, pending controlled live Inkfire acceptance.

## Scope

1.6.1 keeps the 1.6.0 customer-retention system and 1.5 Visual Journey Editor architecture, then closes the main day-to-day admin usability gaps.

### Enquiries / lead operations

Administrators can now:

- assign **New, In progress, Follow up, Waiting, Complete or Archived** workflow states;
- update workflow state inline by AJAX;
- open a lead and correct customer name/email;
- follow up using a pre-addressed email action;
- resend a private magic link for unfinished briefs;
- permanently delete an individual controlled record;
- clear only records deliberately marked **Archived**;
- see captured/verified email state and current progress/estimate data.

### Journey authoring

Administrators can now:

- minimise an expanded card again without reloading;
- change the **Opening image**, **Contact image** and **Closing/Success image** from the native WordPress Media Library;
- remove those images without deleting the media item;
- save image choices immediately by protected AJAX.

The existing Add, Duplicate, drag, keyboard reorder, per-card editor, Undo and route guard rails remain.

## Low-maintenance design decisions

- No new SPA/admin framework was introduced.
- Lead workflow uses the existing private WordPress records and metadata.
- Media selections remain ordinary URL settings selected through `wp.media()` rather than creating a second media subsystem.
- Bulk clear is intentionally limited to Archived records.
- Customer-facing pricing/formulas remain separate from admin journey presentation controls.
- Database schema remains **1.4.0**.

## Security and integrity boundaries

- Lead-management AJAX endpoints require `manage_options` and the existing protected dashboard nonce.
- Journey-media AJAX uses an explicit three-key allow-list and URL sanitisation.
- Correcting an unfinished brief email immediately invalidates the previous verification state and revokes the previous magic link.
- When an administrator resends a magic link, the old link remains usable if mail sending fails; it is revoked only after the replacement message is accepted for sending.
- Public quote totals remain server-authoritative.
- Existing Turnstile, honeypot, throttling, same-origin resume, token hashing, submission locking/idempotency and upload protections remain in force.
- Completed enquiries continue to be stored before notification email is attempted.

## Customer frontend containment

1.6.1 does not intentionally redesign the 1.6 customer journey. The existing premium Inkfire frontend, Brief → Plan → Quote structure, responsive image treatment, live estimate, retention system, Rocket Loader exclusion and Elementor body-level overlay handling remain.

A small customer-facing addition permits an independent Success/Closing image. Removing an Opening, Contact or Success image collapses the visual panel instead of leaving a blank split layout.

## Accessibility position

The existing WCAG 2.2 AA-focused customer suite still passes, including modal semantics, keyboard operation, focus handling, validation association, contrast checks, 320 CSS px reflow and reduced-motion/forced-colour safeguards.

The admin Journey Editor retains keyboard Move up/down controls in addition to pointer drag. Media selection is available through the standard WordPress Media Library and card minimisation has explicit buttons.

This is strong automated evidence, not formal accessibility certification; production keyboard/screen-reader acceptance remains a release gate.

## Data and migration safety

- `FOUNDATION_DB_VERSION`: **1.4.0**
- no 1.6.1 schema migration;
- no required blueprint reapplication;
- no intended price reset;
- no intended email-setting reset;
- no intended deletion of current project briefs or enquiries.

## Local verification status

- Deterministic suite: **67/67 PASS**
- Static/security suite: **24/24 PASS**
- Journey Editor browser suite: **16/16 PASS**
- Lead-management browser suite: **7/7 PASS**
- Full customer smoke: **PASS**
- Mobile/desktop launch regressions: **PASS**
- Private resume regression: **PASS**
- Retention browser suite: **PASS**
- WCAG 2.2 AA-focused browser suite: **PASS**

## Live acceptance required

The real Inkfire environment must still prove:

- Cloudflare/Rocket Loader immediate first click;
- NitroPack/object/LiteSpeed cache freshness;
- live SMTP delivery and magic-link rotation;
- old-link rejection after an email correction;
- WordPress Media Library image selection and removal;
- workflow/archive/delete operations on controlled QA records;
- final submission attachments and duplicate protection;
- current browser and assistive-technology behaviour.

Until those gates pass, the correct release label is **production-ready release candidate**, not live production accepted.
