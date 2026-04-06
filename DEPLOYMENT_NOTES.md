# Deployment Notes

## Repository

- Public repo: https://github.com/hawks010/foundation-project-calculator
- Main branches:
  - `main`
  - `backup/live-old-version`
  - `work/finalise-v1-1-0`

## Current server layout

- Production WordPress: `/home/u363235284/domains/inkfire.co.uk/public_html`
- Blueprint WordPress: `/home/u363235284/domains/inkfire.co.uk/public_html/blueprint`
- Sandbox WordPress: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox`
- Production live plugin kept untouched: `/home/u363235284/domains/inkfire.co.uk/public_html/wp-content/plugins/foundation-project-calculator`
- Production active plugin copy: `/home/u363235284/domains/inkfire.co.uk/public_html/wp-content/plugins/foundation-project-calculator-v110`
- Blueprint active plugin copy: `/home/u363235284/domains/inkfire.co.uk/public_html/blueprint/wp-content/plugins/foundation-project-calculator-v110`
- Sandbox old snapshot copy kept inactive: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox/wp-content/plugins/foundation-project-calculator-live-v100`
- Sandbox new working copy: `/home/u363235284/domains/inkfire.co.uk/public_html/sandbox/wp-content/plugins/foundation-project-calculator-v110`

## Blueprint status after QA

- `foundation-project-calculator-v110`: installed, active
- QA page: `https://blueprint.inkfire.co.uk/foundation-calculator-qa/`
- Controlled submission result: `admin_sent=true`, `customer_sent=true`

## Sandbox status after QA

- `foundation-project-calculator-live-v100`: installed, inactive
- `foundation-project-calculator-v110`: installed, active
- QA page: `https://sandbox.inkfire.co.uk/foundation-calculator-qa/`

## Production status after rollout

- `foundation-project-calculator-v110`: installed, active
- `foundation-project-calculator`: installed, inactive
- Validated live page: `https://inkfire.co.uk/foundation/`
- Controlled submission result: `admin_sent=true`, `customer_sent=true`

## Production activation workflow used

1. Keep the existing production plugin directory in place as the rollback copy.
2. Upload the finalized build into a separate production directory named `foundation-project-calculator-v110`.
3. Deactivate the old live plugin only during the final change window.
4. Activate the finalized plugin immediately after deactivation.
5. Verify:
   - shortcode page render
   - one successful submission
   - admin notification email
   - customer email
   - generated PDF/JSON/ZIP attachments

## Rollback

1. Deactivate the new production plugin.
2. Reactivate `/wp-content/plugins/foundation-project-calculator/foundation-customer-form.php`.
3. Re-check the shortcode page and one submission.

## GitHub release notes

- The updater expects tagged GitHub releases.
- For this rollout, publish a release ZIP whose root folder is `foundation-project-calculator-v110` so update packages match the installed production plugin slug.
