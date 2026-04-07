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

## Current live status after v1.3.0 rollout

- `inkfire.co.uk`: `foundation-project-calculator-v130` is active at `1.3.0`
- `inkfire.co.uk`: `foundation-project-calculator-v110` is still installed and inactive at `1.1.0`
- `inkfire.co.uk`: old `foundation-project-calculator` is still installed and inactive at `1.0.0`
- `blueprint.inkfire.co.uk`: `foundation-project-calculator-v130` is active at `1.3.0`
- `blueprint.inkfire.co.uk`: `foundation-project-calculator-v110` is installed and inactive at `1.1.0`
- `sandbox.inkfire.co.uk`: `foundation-project-calculator-v110` is active at `1.1.0`
- `sandbox.inkfire.co.uk`: `foundation-project-calculator-live-v100` is installed and inactive at `1.0.0`

## v1.3.0 rollout status

- The `v1.3.0` admin redesign package was rebuilt on `codex/finalise-v1-3-0-admin-redesign`
- The uploaded beta did not include React/Tailwind source tooling, so this branch now adds a real React/Tailwind/Vite admin builder under `src/admin`
- Production assets are built to `assets/admin`
- Blueprint server-side smoke testing passed after the React rebuild
- Production has been switched from `foundation-project-calculator-v110` to `foundation-project-calculator-v130`
- The previous inactive production `v1.3.0` folder was backed up and removed before the current build was installed fresh
- Backup path: `/home/u363235284/domains/inkfire.co.uk/public_html/wp-content/foundation-project-calculator-v130-pre-final-20260407022914.tar.gz`

## Blueprint v1.3.0 smoke test results

Previous beta results before this React rebuild:

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

React rebuild local results:

- `npm run build` passed
- Built JS syntax check passed
- PHP lint passed across all plugin PHP files
- Legacy builder assets are no longer referenced by the admin enqueue path
- Blueprint REST role check passed after refresh: `field_budget:budget:budget`, `field_timeline:timeline:timeline`, `field_services_main:services_main:services`
- Blueprint settings REST get/save round-trip passed with `33` settings keys
- The separate `foundation-form-settings` submenu is no longer registered by this plugin build

## Sandbox status after v1.1.0 QA

- `foundation-project-calculator-live-v100`: installed, inactive
- `foundation-project-calculator-v110`: installed, active
- QA page: `https://sandbox.inkfire.co.uk/foundation-calculator-qa/`

## Production activation recommendation

`v1.3.0` is now active on production. Leave `foundation-project-calculator-v110` and the original `foundation-project-calculator` installed and inactive until the team has completed manual browser QA and inspected a received email package.

Final production smoke checks completed:

- shortcode page render
- schema/frontend role sync
- route-target integrity
- production admin and frontend assets returning `200`
- one successful AJAX submission
- admin notification email accepted by `wp_mail`
- customer email accepted by `wp_mail`

## Rollback

1. Deactivate the new production plugin.
2. Reactivate `/wp-content/plugins/foundation-project-calculator-v110/foundation-customer-form.php`.
3. Re-check the shortcode page and one submission.

If the `v1.1.0` copy is unavailable for any reason, the older `/wp-content/plugins/foundation-project-calculator/foundation-customer-form.php` copy remains installed as a secondary rollback option.

## GitHub release notes

- The updater expects tagged GitHub releases.
- For the current production slug, publish release ZIPs whose root folder matches the active plugin directory being updated.
