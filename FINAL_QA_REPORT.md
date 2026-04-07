# Final QA Report

Date: 2026-04-07

## Scope

- Harden the uploaded `v1.3.0` plugin package
- Preserve the current production `v1.1.0` active plugin and the inactive `v1.0.0` rollback copy
- Smoke test the admin redesign, dark mode, save/resume flow, and existing frontend journey
- Prepare the plugin in GitHub on `codex/finalise-v1-3-0-admin-redesign`

## Environments used

- Production path: `/home/u363235284/domains/inkfire.co.uk/public_html`
- Blueprint path: `/home/u363235284/domains/inkfire.co.uk/public_html/blueprint`
- Sandbox path: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox`
- Current production calculator URL: `https://inkfire.co.uk/foundation/`
- Blueprint QA URL: `https://blueprint.inkfire.co.uk/foundation-calculator-qa/`

## Summary

The uploaded `v1.3.0` beta has been imported for review. The beta does not include a React source tree, Tailwind config, Vite config, or package manifest; the shipped implementation is PHP, vanilla JS, and CSS. The admin dashboard redesign is therefore being finalised as a WordPress plugin patch rather than as a React/Tailwind source build.

Production currently remains on `foundation-project-calculator-v110` at `1.1.0`, with the older `foundation-project-calculator` at `1.0.0` still installed and inactive. A `foundation-project-calculator-v130` copy is present on production but inactive. Blueprint is running the separate `foundation-project-calculator-v130` copy for smoke testing, with `foundation-project-calculator-v110` left installed and inactive there as rollback.

## Test results

### Passed

- Local repo import on `codex/finalise-v1-3-0-admin-redesign`
- Confirmed beta package structure
- Confirmed no React/Tailwind source tooling is bundled
- Restored `.gitignore` that was missing from the uploaded package
- PHP lint passed on all plugin PHP files
- JS syntax checks passed for `foundation-builder.js` and `foundation-frontend.js`
- `git diff --check` passed
- Bundled updater parser dependencies are now tracked for GitHub/source release packaging
- Dark-mode dashboard shell now has a real themed background inside WordPress admin
- Resume-link base URL is constrained to the current WordPress site
- Magic-link email sends are rate-limited
- Invalid magic-link email requests are rejected before draft storage and metrics mutation
- No-email save messaging now exposes the resume link directly instead of relying on clipboard support
- Plugin default logo URL migrates from older plugin folders to the active plugin folder
- Blueprint install as separate `foundation-project-calculator-v130` copy
- Blueprint activation passed with `foundation-project-calculator-v110` left installed and inactive
- Blueprint frontend render uses `foundation-project-calculator-v130` CSS and JS assets
- Blueprint frontend config includes same-site resume settings
- Blueprint unauthenticated builder REST read returns `401`
- Blueprint authenticated builder REST read returns `200` with 15 steps
- Blueprint admin dashboard render callback returned `admin-shell-ok` and `dark-ok`
- Blueprint settings page render callback returned `settings-ok`
- Blueprint save draft without email returned a same-site resume URL
- Blueprint invalid magic-link email was rejected
- Blueprint resume-token retrieval restored contact, selections, and current step
- Blueprint invalid upload rejected `.php`
- Blueprint full submission returned `admin_sent=true`, `customer_sent=true`, `customer_email_status=sent`
- Blueprint magic-link email returned `email_sent=true`
- Blueprint metrics updated for `responses_saved` and `saved_drafts`

### Not run

- Uninstall cleanup was not executed on blueprint because it would intentionally delete shared plugin options used by the active test site

## Manual browser QA still recommended

- Full admin dashboard dark/light mode pass
- Builder journey by a non-technical editor
- Full visual pass of the calculator modal on desktop and mobile
- Keyboard-only journey through every step
- Focus order and focus return after close
- Contrast and error-state checks against live branding
- Real upload flow with representative customer files
- Real email delivery and attachment inspection from the production mail setup

## Recommendation

Safe to keep testing on blueprint. Not yet recommended for production until a short manual browser pass confirms the dashboard and public calculator visually.

Do not activate `v1.3.0` on production until:

- manual admin dashboard dark/light mode pass is complete
- manual builder load/save/import/export pass is complete
- manual public calculator pass is complete on desktop and mobile
- one real received email attachment package is inspected
- rollback from `v1.3.0` to production `v1.1.0` is documented
