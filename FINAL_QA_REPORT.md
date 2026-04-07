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

The uploaded `v1.3.0` beta was imported for review, then the backend builder was rebuilt as a React/Tailwind admin app because the uploaded package did not actually contain the expected React source. The new app uses Vite, scoped Tailwind styles, clean light/dark theme tokens, slide-level editing, drag/drop reordering, keyboard move controls, and a service routing inspector that writes to the same `foundation_form_data` schema used by the public calculator.

Production now runs `foundation-project-calculator-v130` at `1.3.0`, with `foundation-project-calculator-v110` at `1.1.0` and the older `foundation-project-calculator` at `1.0.0` still installed and inactive for rollback. Blueprint is also running the separate `foundation-project-calculator-v130` copy, with `foundation-project-calculator-v110` left installed and inactive there as rollback.

## Final production rollout - 2026-04-07

- Production `v1.3.0` was installed into a freshly recreated `foundation-project-calculator-v130` plugin folder.
- The previous inactive production `v1.3.0` folder was backed up before removal to `/home/u363235284/domains/inkfire.co.uk/public_html/wp-content/foundation-project-calculator-v130-pre-final-20260407022914.tar.gz`.
- Production activation succeeded: `foundation-project-calculator-v130` active, `foundation-project-calculator-v110` inactive, and `foundation-project-calculator` inactive.
- Production schema sync check passed: 15 slides, `field_budget`, `field_timeline`, and `field_services_main` all present, 12 route targets, no broken route targets, no empty option labels, and no unreachable conditional slides.
- Production public page check passed: `https://inkfire.co.uk/foundation/` renders from the `foundation-project-calculator-v130` plugin folder and contains the core budget/timeline/services fields.
- Production asset check passed: `assets/admin/admin-app.js`, `assets/admin/admin-app.css`, and `assets/js/foundation-frontend.js` all returned `200`.
- Production AJAX submission smoke test passed with the live frontend nonce: `admin_sent=true`, `customer_sent=true`, and `customer_email_status=sent`.

## Test results

### Passed

- Local repo import on `codex/finalise-v1-3-0-admin-redesign`
- Confirmed beta package structure
- Confirmed no React/Tailwind source tooling is bundled
- Added React/Tailwind/Vite admin source under `src/admin`
- Built production admin assets to `assets/admin/admin-app.js` and `assets/admin/admin-app.css`
- Removed the unused legacy jQuery builder assets from the active package
- Restored `.gitignore` that was missing from the uploaded package
- PHP lint passed on all plugin PHP files
- `npm run build` passed for the React/Tailwind admin app
- JS syntax check passed for the built `assets/admin/admin-app.js`
- `git diff --check` passed
- Bundled updater parser dependencies are now tracked for GitHub/source release packaging
- Dark/light admin theme colors have been reset around scoped app variables
- Slide editing, field editing, service-option pricing, and route-to-slide controls are present in the React app
- Frontend sync warnings are present for missing core fields and broken route targets
- Legacy budget/timeline/service fields are now normalized into frontend-safe roles on REST read/save
- Settings now load and save through the same React admin workspace rather than a separate styled PHP page
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
- Final production full submission returned `admin_sent=true`, `customer_sent=true`, `customer_email_status=sent`

### Not run

- Uninstall cleanup was not executed on blueprint because it would intentionally delete shared plugin options used by the active test site

## Manual browser QA still recommended

- Full admin dashboard dark/light mode pass
- Builder journey by a non-technical editor
- React builder save/import/export pass on blueprint after redeploying this rebuild
- Full visual pass of the calculator modal on desktop and mobile
- Keyboard-only journey through every step
- Focus order and focus return after close
- Contrast and error-state checks against live branding
- Real upload flow with representative customer files
- Real email delivery and attachment inspection from the production mail setup

## Recommendation

Safe to leave `v1.3.0` active on production after the server-side smoke checks above.

Still recommended before calling the rollout fully closed:

- manual admin dashboard dark/light mode pass
- manual builder load/save/import/export pass
- manual public calculator pass on desktop and mobile
- inspection of the received production email attachment package
