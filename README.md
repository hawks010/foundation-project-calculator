# Foundation Project Calculator

**Version 1.4.0**
**Repository:** https://github.com/Inkfire-limited/foundation-project-calculator

A guided WordPress project calculator for Inkfire. It turns the August 2026 pricing board into a maintainable customer journey with separate one-off and monthly totals, clear tailored-quote outcomes, secure lead capture, and a deliberately simple administration screen.

## What changed in 1.4

Version 1.4 removes the fragile visual flow builder from everyday use. The customer journey is now a tested, read-only blueprint, while the things staff actually need to change are separated into plain controls:

- **Overview:** health checks, anonymous journey metrics, and migration status.
- **Enquiries:** a private local inbox for completed estimates and email-delivery status.
- **Prices:** 38 clearly labelled prices, grouped by Web, Tech, and Business services.
- **Customer journey:** a read-only map of the questions and routes.
- **Emails & branding:** recipients, sender identity, customer copy, VAT wording, logo, and privacy link.
- **Advanced:** quote-only mode, attachment controls, retention, rate limits, export, and metrics reset.

The public calculator supports all three Inkfire routes in one estimate:

1. Web & Accessibility
2. Tech & Support
3. Business Support & Marketing

Customers can select one or several routes. Exact prices, ranges, monthly charges, and services requiring discovery are kept distinct.

## Requirements

- WordPress 6.4 or later
- PHP 7.4 or later
- HTTPS recommended
- A verified SMTP configuration strongly recommended
- `ZipArchive` optional. PDF and JSON reports still work without it.

## Installation

1. Back up the WordPress database and the current plugin folder.
2. Upload the `foundation-project-calculator` folder to `/wp-content/plugins/`.
3. Activate **Foundation Project Calculator**.
4. Open **Foundation > Project Calculator**.
5. Complete every item in the ready-state checklist.
6. Configure the notification and sender addresses under **Emails & branding**.
7. Place `[foundation_form]` on the calculator page.
8. Run a complete test submission on staging before production activation.

### Upgrading an existing installation

A fresh or empty installation receives the Inkfire 2026 journey automatically. A real existing journey is never overwritten silently.

After upgrading, the Overview screen may show **Apply Mali’s Inkfire pricing journey**. Select **Back up and apply journey**. The previous flow is stored as a restorable backup; prices and email settings are preserved.

## Shortcode and triggers

Standard button and calculator:

```text
[foundation_form]
```

Custom button label:

```text
[foundation_form label="Plan my project"]
```

Load the calculator without rendering the default button:

```text
[foundation_form button="false"]
```

A custom link or button can open the calculator by using either:

- the class `.foundation-trigger`
- the attribute `data-foundation-calculator-open`
- a link whose URL contains `get-quote` or `foundation-form`

Keep the shortcode on the page even when using an external trigger because it prints the calculator mount and lazy loader.

## Pricing behaviour

The browser shows an immediate estimate for usability, but it is not trusted for submission. The server validates the visible route, required answers, option indexes, number bounds, uploads, and contact data, then recalculates the estimate using the live pricing catalogue.

The result can contain:

- an exact one-off total
- a one-off range
- an exact monthly total
- a monthly range
- one or more tailored-quote items

All bundled prices are excluding VAT. The VAT note and estimate disclaimer are editable.

See `MALI_PRICING_MAPPING.md` for the complete source mapping and every interpretation made where the board did not define a safe formula.

## Lead reliability and privacy

Completed enquiries are written to a private WordPress post type before email is attempted. An SMTP outage therefore leaves the lead available under **Foundation > Project Calculator > Enquiries**.

Other safeguards include:

- nonce validation on public mutations
- IP and email-based rate limiting
- honeypot spam handling
- idempotency tokens and a database-backed duplicate-submission lock
- same-origin saved-estimate links
- strict field and route validation
- executable and browser-active upload deny-list
- bounded file count and file size
- private, non-public enquiry storage
- configurable automatic enquiry retention
- WordPress personal-data exporter and eraser support
- configuration exports that omit recipient and sender addresses

The bundled Inkfire journey does not currently ask customers to upload files. Upload support remains available for a future approved journey.

## Development and testing

The shipped admin and customer interfaces use plain, versioned PHP, CSS, and JavaScript. There is no production asset compilation step.

Run the deterministic suite from the plugin root:

```bash
bash tests/run.sh
```

Run the Chromium customer-journey smoke test separately:

```bash
bash tests/test-browser-smoke.sh
```

Reports are written to:

- `tests/TEST_RESULTS.md`
- `tests/STATIC_QA_RESULTS.md`
- `tests/BROWSER_SMOKE_RESULTS.md`

## Release status

Version 1.4.0 is a **production candidate**. Local unit, security, syntax, and browser-journey checks pass. Final approval still requires installation on the real staging WordPress site, a live SMTP test, and conflict checks with the active theme, caching layer, security plugins, and the host’s PHP configuration.

See `DEPLOYMENT_NOTES.md` and `PRODUCTION_READINESS_REPORT.md` before release.
