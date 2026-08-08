# Foundation Project Calculator 1.6.1 Deployment Notes

## Release scope

1.6.1 adds managed lead workflow controls, safer magic-link administration, collapsible Journey cards and inline WordPress Media Library controls on top of the 1.6.0 retention/customer experience.

The Inkfire 2026 pricing blueprint remains the pricing/routing source of truth and the database schema version remains **1.4.0**.

## Before deployment

1. Take a fresh full database backup.
2. Archive the active `foundation-project-calculator` plugin folder.
3. Record current plugin version, blueprint version, journey health, screen/field/price counts and email-settings fingerprint.
4. Record current unfinished/completed enquiry counts.
5. Keep the previous plugin installer/snapshot as rollback material.
6. Confirm the site's privacy policy URL and verified SMTP sender.

## Install

1. Install the approved `foundation-project-calculator-1.6.1-production.zip` in the existing plugin folder.
2. Confirm WordPress reports **1.6.1**.
3. Do **not** reapply Mali's journey merely because the plugin version changed.
4. Confirm the existing 28-screen / 49-field / 38-price / 27-route-target blueprint, or the site's supported customised equivalent, remains intact.
5. Confirm email recipients/sender and current enquiries survived the replacement.

## Turnstile setup

Turnstile integration ships **disabled until real keys are provided**.

For production:

1. configure the real Inkfire site key and secret under **Advanced**;
2. enable Turnstile;
3. save;
4. verify the Overview health row reports spam protection configured;
5. perform a real magic-link send and final submission before relying on it.

If Turnstile is intentionally left off during a staged rollout, nonce, honeypot, bounded rate limits, minimum-interaction checks and duplicate protection remain active.

## Mandatory cache purge

Purge all three known layers:

1. NitroPack;
2. WordPress object cache;
3. host LiteSpeed cache.

Then inspect a **normal cacheable public request** and confirm the returned assets reference `ver=1.6.1`. Do not validate only through a cache-bypass URL.

## Live acceptance order

### Gate A: immediate public launch

At mobile and desktop sizes, load a fresh signed-out homepage and click the real `#foundation-launch-btn` as soon as it becomes clickable.

Pass conditions:

- first click is intercepted;
- configuration request starts;
- no `#foundation-launch-btn` fragment is left behind;
- overlay parent is `body`;
- modal is visibly painted above Elementor;
- no calculator console error.

### Gate B: premium intro / mobile responsive experience

Verify:

- desktop keeps the split team-image/project-brief composition;
- light-mode controls use white text on green glass;
- mobile keeps a compact branded image hero rather than dropping the image;
- intro content fits at 320–390 CSS px without horizontal scrolling;
- Save is not offered before there is anything to save.

### Gate C: early soft capture + magic link

1. Select **Build my estimate**.
2. Confirm name/email capture appears and **Continue without saving** is clearly available.
3. Submit a controlled internal name/email.
4. Confirm the customer proceeds immediately without being forced to open email.
5. Confirm exactly one unfinished private project brief appears in Admin → Enquiries.
6. Confirm the resume email arrives.
7. Open the link in a private browser/device.
8. Confirm the exact saved screen/answers restore and the admin brief becomes **Verified email**.
9. Confirm the visible resume token is removed from the URL after capture.

### Gate D: anonymous recovery

Using a fresh browser without entering email:

1. Skip capture.
2. answer a few questions;
3. close through the recovery panel;
4. reopen;
5. confirm **Continue where I left off** restores journey state;
6. inspect browser storage and confirm no name/email/contact PII is present.

### Gate E: known quote + retention flow

Run the known Web route:

- small website, five pages;
- WooCommerce;
- Hosting + plugins;
- 26–50 alt texts;
- accessibility testing;
- accessibility setup.

Expected: **£4,260 one-off + £45/month**, excluding VAT.

Confirm:

- live estimate updates;
- route-completion screen appears;
- customer can add another service or review;
- final contact screen reuses early name/email rather than asking again.

### Gate F: real submission / email / reports

Submit one controlled internal enquiry and verify:

- exactly one completed private enquiry;
- unfinished magic-link brief is marked converted/removed from normal unfinished queue;
- team notification accepted/delivered;
- customer confirmation accepted/delivered if enabled;
- Reply-To correct;
- PDF/JSON/ZIP filenames have real extensions;
- duplicate replay returns the same reference and creates no second enquiry;
- test data can be deleted cleanly.

### Gate G: Turnstile / abuse

With real production Turnstile enabled:

- magic-link send succeeds for a normal human flow;
- final submission succeeds;
- wrong/expired challenge is rejected with a recoverable message;
- legitimate calculator `admin-ajax.php` requests are not blocked by AIOWPS or hosting security;
- resend cooldown/rate limits do not false-positive during an ordinary journey.

### Gate H: admin

Verify:

- Overview funnel renders;
- unfinished brief appears with captured/verified state, progress and quote;
- completed enquiry remains available;
- stale legacy failure text is not shown as unresolved;
- Journey Editor Add/Duplicate/reorder/AJAX save still work;
- price search and journey health work;
- configuration export contains no recipient/sender/Turnstile secret.

### Gate I: accessibility/browser

Run keyboard and screen-reader checks on the real page and launch in current Chrome/Chromium, Safari, Edge and Firefox where available. Verify focus trap/return, error announcements, 320 CSS px reflow, reduced motion and the mobile estimate drawer.

## Gate G: 1.6.1 admin management

After the public gates pass:

1. Open **Foundation → Project Calculator → Enquiries**.
2. Confirm the unfinished and completed cards have normal internal padding.
3. On a controlled unfinished brief, change Workflow between In progress/Follow up/Waiting and confirm it saves without a reload.
4. Open the brief, correct a test email, confirm the address becomes unverified, then use **Resend magic link** and verify only the new link works.
5. Confirm **Delete record** requires confirmation. Use a disposable test lead only.
6. Mark a test record Archived and confirm **Clear archived** removes archived records only.
7. Open **Customer journey**, expand a card and confirm **Minimise card** collapses it.
8. Under Opening slide, click the image thumbnail or **Choose image**, select an image through the standard WordPress Media Library and confirm the preview updates without a page reload. Repeat the control check for Contact and Success.
9. Remove a temporary image and confirm the public calculator does not reserve a blank visual panel.

## Rollback

If a gate fails:

1. preserve evidence before changing state;
2. restore the pre-1.6.1 plugin folder;
3. restore database/options only if stored configuration was actually mutated/corrupted;
4. purge NitroPack, object cache and LiteSpeed;
5. verify a normal public request serves the rollback version.

Because 1.6.1 has **no schema migration**, a code rollback does not inherently require a database rollback. Retain the database snapshot for configuration/data recovery if needed.
