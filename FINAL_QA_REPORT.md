# Final QA Report

Date: 2026-04-06

## Scope

- Harden the uploaded `v1.1.0` plugin package
- Preserve the production `v1.0.0` plugin as the rollback reference
- Install and test the new plugin as a separate copy on sandbox
- Prepare the plugin in GitHub with branch history and updater support

## Environments used

- Production path: `/home/u363235284/domains/inkfire.co.uk/public_html`
- Sandbox path: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox`
- Sandbox QA URL: `https://sandbox.inkfire.co.uk/foundation-calculator-qa/`

## Summary

The plugin is materially safer than the uploaded package. Syntax checks passed, sandbox activation passed, the public builder REST read route is no longer exposed, required-field validation now exists on the server, success-state messaging is more truthful, the updater is bundled, and artifact generation works.

The main unresolved blocker is external mail delivery on sandbox: SMTP2GO rejected sender domains that are not verified in that environment. The plugin now behaves more safely around placeholder sender values, but final production sign-off still needs one real browser submission and one real mail-provider delivery test with a verified sender.

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
- Real upload flow with representative customer files
- Real email delivery and attachment inspection from the production mail setup
- Builder UX pass by a non-technical editor

## Recommendation

Safe for staged activation and final browser-led sign-off.

Not yet safe to activate blindly on production until:

- sender settings are confirmed against a verified SMTP identity
- one real browser submission is completed
- one real outbound email round-trip is verified
