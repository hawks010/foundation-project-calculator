# Final QA Report

Date: 2026-04-06

## Scope

- Harden the uploaded `v1.1.0` plugin package
- Preserve the production `v1.0.0` plugin as the rollback reference
- Install and test the new plugin as a separate copy on sandbox and blueprint
- Roll the approved build onto production without deleting the old plugin folder
- Prepare the plugin in GitHub with branch history and updater support

## Environments used

- Production path: `/home/u363235284/domains/inkfire.co.uk/public_html`
- Blueprint path: `/home/u363235284/domains/inkfire.co.uk/public_html/blueprint`
- Sandbox path: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox`
- Blueprint QA URL: `https://blueprint.inkfire.co.uk/foundation-calculator-qa/`
- Sandbox QA URL: `https://sandbox.inkfire.co.uk/foundation-calculator-qa/`

## Summary

The plugin is materially safer than the uploaded package. Syntax checks passed, sandbox activation passed, the public builder REST read route is no longer exposed, required-field validation now exists on the server, success-state messaging is more truthful, the updater is bundled, and artifact generation works.

Sandbox remained limited by its mail configuration, but blueprint and production both completed controlled live submissions successfully with admin and customer email sending. The production site is now running `foundation-project-calculator-v110` as the active plugin, with the previous `foundation-project-calculator` copy still installed and inactive for rollback.

## Test results

### Passed

- PHP lint on all plugin PHP files
- Separate sandbox install without touching the production plugin directory
- Old sandbox snapshot left installed and inactive
- New sandbox copy activated successfully
- Deactivate/reactivate cycle succeeded without fatal errors
- Shortcode render check
- Frontend assets loaded from the separate sandbox plugin directory
- Public REST access to `foundation/v1/get` now returns `rest_forbidden`
- REST routes register correctly inside WordPress
- Admin page render callback returns HTML
- Settings page render callback returns HTML
- Bundled updater library loads successfully
- Required-field server validation rejects incomplete submissions
- Upload validation rejects disallowed file types
- PDF summary generation
- JSON summary generation
- ZIP package generation with expected files
- Uninstall cleanup removes the plugin options created by this version
- Blueprint install into `/wp-content/plugins/foundation-project-calculator-v110`
- Blueprint controlled validation failure test
- Blueprint controlled upload rejection test
- Blueprint controlled success submission with `admin_sent=true` and `customer_sent=true`
- Production install into `/wp-content/plugins/foundation-project-calculator-v110`
- Production activation swap with rollback fallback
- Production page render from `foundation-project-calculator-v110` assets
- Production controlled validation failure test
- Production controlled success submission with `admin_sent=true` and `customer_sent=true`

### Failed or blocked

- Real outbound admin email delivery on sandbox
- Real outbound customer email delivery on sandbox

Reason:

- SMTP2GO rejected the sender domain as unverified in the sandbox environment

## Manual browser QA still recommended

- Full visual pass of the calculator modal on desktop and mobile
- Keyboard-only journey through every step
- Focus order and focus return after close
- Contrast and error-state checks against live branding
- Real upload flow with representative customer files through the browser UI
- Attachment inspection from the received production admin email
- Builder UX pass by a non-technical editor

## Recommendation

Safe to remain active on production, with final browser-led QA still recommended.

Remaining sign-off items:

- one manual browser walkthrough on production desktop/mobile
- one keyboard-only accessibility pass
- one visual inspection of the received production email attachments
