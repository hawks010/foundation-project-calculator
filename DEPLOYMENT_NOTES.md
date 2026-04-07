# Deployment Notes

## Repository

- Public repo: https://github.com/hawks010/foundation-project-calculator
- Main branches:
  - `main`
  - `backup/live-old-version`
  - `work/finalise-v1-1-0`
  - `codex/finalise-v1-3-0-admin-redesign`

## Current server layout

- Production WordPress: `/home/u363235284/domains/inkfire.co.uk/public_html`
- Blueprint WordPress: `/home/u363235284/domains/inkfire.co.uk/public_html/blueprint`
- Sandbox WordPress: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox`
- Production active plugin copy: `/home/u363235284/domains/inkfire.co.uk/public_html/wp-content/plugins/foundation-project-calculator-v110`
- Production rollback plugin kept installed but inactive: `/home/u363235284/domains/inkfire.co.uk/public_html/wp-content/plugins/foundation-project-calculator`
- Production v1.3.0 copy currently installed but inactive: `/home/u363235284/domains/inkfire.co.uk/public_html/wp-content/plugins/foundation-project-calculator-v130`
- Blueprint active plugin copy for v1.3.0 QA: `/home/u363235284/domains/inkfire.co.uk/public_html/blueprint/wp-content/plugins/foundation-project-calculator-v130`
- Blueprint rollback plugin copy: `/home/u363235284/domains/inkfire.co.uk/public_html/blueprint/wp-content/plugins/foundation-project-calculator-v110`
- Sandbox old snapshot copy kept inactive: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox/wp-content/plugins/foundation-project-calculator-live-v100`
- Sandbox new working copy: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox/wp-content/plugins/foundation-project-calculator-v110`

## Current live status before v1.3.0 rollout

- `inkfire.co.uk`: `foundation-project-calculator-v110` is active at `1.1.0`
- `inkfire.co.uk`: old `foundation-project-calculator` is still installed and inactive at `1.0.0`
- `inkfire.co.uk`: `foundation-project-calculator-v130` is installed and inactive at `1.3.0`
- `blueprint.inkfire.co.uk`: `foundation-project-calculator-v130` is active at `1.3.0`
- `blueprint.inkfire.co.uk`: `foundation-project-calculator-v110` is installed and inactive at `1.1.0`
- `sandbox.inkfire.co.uk`: `foundation-project-calculator-v110` is active at `1.1.0`
- `sandbox.inkfire.co.uk`: `foundation-project-calculator-live-v100` is installed and inactive at `1.0.0`

## v1.3.0 rollout status

- The `v1.3.0` admin redesign package is being finalised on `codex/finalise-v1-3-0-admin-redesign`
- Blueprint server-side smoke testing has passed
- Do not deploy `v1.3.0` to production before manual browser QA
- The uploaded beta does not include React/Tailwind source tooling; it ships as PHP, vanilla JS, and CSS inside the WordPress plugin

## Blueprint v1.3.0 smoke test results

- Activation passed
- Frontend render uses `foundation-project-calculator-v130` assets
- Admin dashboard render callback passed
- Settings page render callback passed
- Unauthenticated builder REST read returns `401`
- Authenticated builder REST read returns `200`
- Save draft without email returns a same-site resume URL
- Magic-link send returned `email_sent=true`
- Resume-token retrieval restored draft data
- Invalid upload rejected `.php`
- Controlled submission returned `admin_sent=true` and `customer_sent=true`
- Metrics updated for saved drafts and successful submissions

## Sandbox status after v1.1.0 QA

- `foundation-project-calculator-live-v100`: installed, inactive
- `foundation-project-calculator-v110`: installed, active
- QA page: `https://sandbox.inkfire.co.uk/foundation-calculator-qa/`

## Production activation recommendation

1. Keep the existing production plugin directory in place as the rollback copy.
2. Complete manual browser QA on `blueprint.inkfire.co.uk`.
3. Upload the finalized production build into a separate production plugin directory only after blueprint manual QA passes.
4. Deactivate the currently active `foundation-project-calculator-v110` production copy only during the final change window.
5. Activate the finalized `v1.3.0` plugin and immediately test:
   - shortcode page render
   - one successful submission
   - admin notification email
   - customer email
   - generated PDF/JSON/ZIP attachments
   - dashboard dark/light toggle
   - save/resume magic link

## Rollback

1. Deactivate the new production plugin.
2. Reactivate `/wp-content/plugins/foundation-project-calculator-v110/foundation-customer-form.php`.
3. Re-check the shortcode page and one submission.

If the `v1.1.0` copy is unavailable for any reason, the older `/wp-content/plugins/foundation-project-calculator/foundation-customer-form.php` copy remains installed as a secondary rollback option.

## GitHub release notes

- The updater expects tagged GitHub releases.
- For the current production slug, publish release ZIPs whose root folder matches the active plugin directory being updated.
