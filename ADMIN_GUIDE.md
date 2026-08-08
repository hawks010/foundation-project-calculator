# Foundation Project Calculator 1.6.1 Admin Guide

## 1. Where to manage it

Open **WordPress Admin → Foundation → Project Calculator**.

The calculator is split into six everyday areas so staff do not need the old complex builder:

1. **Overview**
2. **Enquiries**
3. **Prices**
4. **Customer journey**
5. **Emails & branding**
6. **Advanced**

## 2. Overview

The top cards show calculator views, journeys started, unfinished email-linked project briefs and stored completed enquiries.

### Project brief funnel

The retention funnel shows first-party counts for:

- Opened
- Started
- Email captured
- Email verified
- Price reached
- Review reached
- Submitted

These are aggregate calculator events. Per-screen statistics are also used to highlight the questions with the most validation friction. They are not intended to be an analytics copy of customer free-text answers.

### Latest unresolved failure

New failures store a time and plugin version. A later successful submission clears the active failure alert while preserving the historical failure counter.

Old un-timestamped `last_failure` values from legacy versions are treated as historical, which prevents stale messages such as old required-field lists from looking current after an upgrade.

## 3. Enquiries

The screen now has views for **All**, **Unfinished briefs**, and **Completed**.

### Unfinished project briefs

A brief appears after a visitor voluntarily supplies a name/email and requests a private return link.

The row/detail view can show:

- name/email;
- captured vs verified email state;
- progress percentage;
- current one-off/monthly estimate;
- last activity;
- separate marketing-consent state.

**Email captured** means a save link was requested. **Verified email** means the customer later used that private link. A customer does not have to verify before continuing the calculator.

When the same saved brief becomes a completed enquiry, the unfinished record is marked converted and drops out of the normal unfinished queue.

### Completed enquiries

Completed enquiries remain private WordPress records and are written before email delivery is attempted. The detail view contains customer data, answers, quote totals, generated reference and mail status.

### Lead workflow and actions

Each unfinished brief or completed enquiry has an internal workflow label: **New, In progress, Follow up, Waiting, Complete, Archived**. Changing the label saves immediately by AJAX.

Useful lead actions include:

- **Follow up**: opens a pre-addressed email in the administrator's mail client.
- **Resend link**: unfinished briefs receive a freshly rotated private resume link.
- **Change customer details**: open the lead and correct its name/email. If a brief email changes, the old private link is revoked and the address becomes unverified until a new link is used.
- **Delete record**: permanently removes one lead after confirmation.
- **Clear archived**: appears when archived records exist and permanently clears only records explicitly labelled Archived.

Use **Archived** for records staff no longer need in the working queue, then clear them when appropriate under the organisation's retention policy.

## 4. Prices

Prices remain the monetary source of truth. The Journey Editor intentionally does not duplicate formulas or allow arbitrary price editing inside cards.

When a price changes:

1. update it under **Prices**;
2. save;
3. purge all applicable caches;
4. verify a normal public request shows the new value;
5. restore/test if it was only a temporary QA mutation.

## 5. Customer journey / Visual Journey Editor

The hierarchy follows:

**Opening → Start → Web / Tech / Business → Review → Contact → Success**

Each journey card can be expanded in place and saved by AJAX without a full page reload.

### Safe editing

Editable card content includes customer-facing title, description, question wording, helpers, required state, safe field settings, choices and compatible incoming connections.

### Add / Duplicate

- **+ Add** creates a new editor-owned unconnected Draft after a card or inside a lane.
- **Duplicate** is the easiest way to start from a similar screen. It creates fresh IDs and stays unconnected until deliberately routed.

Unconnected drafts do not appear to customers.

### Reorder

Use pointer drag for convenience, or **Move up / Move down** as the keyboard-accessible equivalent. Reordering is confined to siblings in the same route.

### Connect a new branch

A clean workflow is:

1. add a new choice to the earlier service selection;
2. duplicate/add the target screen;
3. edit the new screen;
4. connect it to the compatible earlier choice;
5. save;
6. test the public route.

The editor writes to the existing calculator routing model rather than maintaining a second flow database.

### Undo and recovery

**Undo last change** keeps one rolling pre-change journey snapshot. The older blueprint backup/restore remains a separate larger recovery mechanism.

### Minimise an editor card

When a card is expanded, its Edit button changes to **Minimise** and a second **Minimise card** control appears beside Save. Either collapses the editor without navigating or reloading the page.

### Change opening/contact/success images

The Journey hierarchy now contains image controls exactly where the visual is used:

- **Opening slide → Opening image**
- **Shared finish → Contact details → Contact image**
- **Shared finish → Success → Closing image**

Click the thumbnail or **Choose image** to open the normal WordPress Media Library. Select an image and choose **Use this image**; it saves immediately by AJAX. **Remove** clears the image. Removing an image does not delete it from WordPress Media Library.

## 6. Emails & branding

Configure the team recipient, optional CCs, sender identity, customer confirmation, VAT/disclaimer wording, logo/imagery, privacy policy and public labels.

Use a sender address verified by the active SMTP provider. A successful `wp_mail()` call is not the same thing as proving final inbox placement, so live SMTP acceptance is part of release testing.

The 1.6 frontend reuses the original Inkfire personality with a premium Brief → Plan → Quote intro, responsive image hero, live estimate and accessible dark/light treatment.

## 7. Advanced: retention and lead capture

### Early capture

**Offer name + email save before the first service question** controls the soft capture step.

Customers can always choose **Continue without saving**. Anonymous selections are still recoverable on the same device through local browser storage, without name/email contact PII.

### Optional marketing permission

**Show a separate optional marketing opt-in** controls whether the independent marketing checkbox appears. It remains unchecked by default in the customer UI.

The save/resume email is operational and is not treated as marketing permission.

### Retention

Configure:

- anonymous local-browser recovery duration;
- email-linked saved-brief retention;
- completed-enquiry retention.

Expired server-side saved briefs/enquiries are removed by the scheduled cleanup routine according to the configured periods.

## 8. Advanced: spam / abuse protection

The calculator always keeps its existing nonce, honeypot, rate-limit and duplicate/idempotency layers.

### Cloudflare Turnstile

To enable Turnstile:

1. create the production widget in Cloudflare;
2. enter the **site key** and **secret key**;
3. enable Turnstile;
4. save settings;
5. run a real magic-link send and final-submission test.

Turnstile is applied to magic-link sends and final submission. The server verifies the token against Cloudflare and requires the expected action and hostname. The secret is omitted from public frontend config and configuration exports.

Do not enable Turnstile with missing/incorrect keys on production.

### Abuse thresholds

Advanced exposes bounded controls for:

- minimum interaction time;
- magic-link resend cooldown;
- magic links per email/hour;
- magic links per connection/hour;
- draft saves per connection/hour;
- final submits per connection/hour;
- final submits per email/hour;
- duplicate submission cooldown.

Start with the supplied defaults and tune from real traffic rather than aggressively blocking legitimate customers.

## 9. Save/resume behaviour

A customer who chooses to save receives a private magic link and continues immediately.

On another device/browser, the link restores the saved screen/answers. When the link is used, the saved brief is marked email-verified. The visible token is removed from the browser URL after capture.

Resume URLs are restricted to the site's exact HTTPS origin. Magic tokens are stored as hashes in the unfinished-brief record.

## 10. Public retention behaviour

The customer experience now includes:

- premium opening with clear time/value expectations;
- early soft capture with Skip;
- Brief → Plan → Quote stage guidance;
- route-level progress;
- early live price reward;
- desktop live-summary panel;
- mobile sticky estimate pill/drawer;
- contextual help;
- local browser recovery;
- recoverable close/leave dialog;
- route-completion reward;
- add-another-service flow;
- late final contact step that reuses captured name/email;
- tailored/manual services that continue rather than dead-end.

## 11. Deployment note

Version 1.6.1 keeps `FOUNDATION_DB_VERSION` at **1.4.0** and does not require a schema migration. Do not reapply the current Inkfire blueprint merely because the plugin version changes.

After any upgrade purge NitroPack, WordPress object cache and host LiteSpeed cache, then test a normal cacheable request.
