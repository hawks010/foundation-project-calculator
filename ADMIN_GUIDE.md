# Foundation Project Calculator 1.4 Admin Guide

This guide is for Inkfire staff who need to manage prices, enquiries, emails, and customer-facing wording without touching the calculator's route logic.

## 1. First login after installation or upgrade

Open **WordPress Admin > Foundation > Project Calculator**.

The Overview tab shows a ready-state checklist. Resolve each warning before launch.

### Fresh installation

The bundled Inkfire 2026 pricing journey is installed automatically when no real calculator flow exists.

### Existing installation

An existing non-empty journey is preserved. The Overview tab will show a pink migration panel titled **Apply Mali’s Inkfire pricing journey**.

Select **Back up and apply journey**. This action:

- stores the current journey as a rollback copy
- installs the tested Web, Tech, and Business routes
- leaves existing price values intact
- leaves email and branding settings intact

The journey fingerprint will show a warning whenever the live flow differs from the bundled version, even if a version label was left behind.

## 2. Overview

The Overview tab contains:

- calculator views
- journeys started
- stored enquiries
- saved drafts
- completion percentage from recorded starts
- blueprint status
- route and price-key integrity
- local inbox status
- notification email status
- privacy-link status
- ZIP support status
- the most recent recorded operational failure

The counters are anonymous. They do not contain customer email addresses.

A green checklist does not prove SMTP delivery. A real submission is still required on staging.

## 3. Enquiries

Open **Enquiries** to see completed customer estimates.

Each row shows:

- reference
- contact and business
- one-off and monthly estimate
- tailored-quote count
- team email status
- customer email status
- date received

Open an enquiry to see the stored contact details, calculated line items, tailored items, customer answers, and any attachment names.

The enquiry is stored before email is attempted. A failed team email therefore does not remove the lead. The email status will show **Failed**, and the Overview tab records the latest delivery failure.

The inbox stores contact and estimate data as a private, non-public WordPress post. It does not duplicate uploaded files. The current Inkfire journey contains no upload question.

## 4. Prices

Open **Prices** for the safe price editor.

Prices are grouped into:

- Web & Accessibility
- Tech & Support
- Business Support & Marketing

Use the search field to find a service. Enter prices as numbers without a currency sign, then select **Save all prices**.

The route, billing period, formula, and tailored-quote rules are locked. Changing a price cannot disconnect a screen.

All public totals are shown excluding VAT. The VAT wording is controlled under Emails & branding.

### Important price-editing rule

Do not enter VAT-inclusive figures unless Inkfire intentionally changes the public VAT wording and quoting policy. The bundled formulas assume the catalogue values are excluding VAT.

## 5. Customer journey

Open **Customer journey** to review what customers are asked.

The map is grouped into Start, Web & Accessibility, Tech & Support, and Business Support & Marketing. Conditional screens are labelled.

The normal dashboard does not edit route internals. This is intentional. It prevents a small wording or price change from breaking the calculator graph.

### Reapply blueprint

Use **Reapply blueprint** only when the journey is damaged or has been changed by controlled tooling. The live journey is backed up first.

### Restore previous journey

Use **Restore backup** to swap the current and previous journeys. This provides a single rollback step. Prices and email settings are separate and are not rolled back by this action.

## 6. Emails & branding

### Email notifications

Configure:

- team recipient
- optional comma-separated CC recipients
- sender name
- verified sender email
- team subject prefix
- customer subject
- customer confirmation toggle
- customer email introduction

Use an address verified by the active SMTP provider. WordPress accepting `wp_mail()` is not proof that the message reached an inbox.

### Customer experience

Configure:

- launch-button label
- calculator title
- currency symbol
- VAT note
- estimate disclaimer
- live-summary toggle
- phone requirement
- privacy-policy URL
- privacy-consent wording
- logo URL
- success message

The intro and testimonial defaults remain in the settings option for backwards compatibility. The normal 1.4 screen exposes the operational fields most likely to need changes.

## 7. Advanced

### Quote-only mode

Enable **Quote-only mode** to hide calculated prices from customers while still collecting their scope. The server continues to calculate and store the underlying estimate.

### Attachments

Choose whether team emails include:

- PDF report
- JSON report
- ZIP package

A ZIP package requires the PHP `ZipArchive` extension. Without it, readable PDF and JSON reports are attached separately.

### Upload limits

The current route does not request uploads, but future approved flows can use the controls for:

- allowed extensions
- maximum file size
- maximum total upload size
- maximum files per upload question

Executable and browser-active formats remain blocked even when entered in the settings field.

### Retention and abuse controls

- Saved-draft retention: 1 to 90 days
- Stored-enquiry retention: 30 to 3,650 days
- Repeat-submission cooldown: 5 to 600 seconds

### Export configuration

The JSON export includes the journey, prices, blueprint version, and non-secret settings. It omits team, CC, and sender email addresses.

### Reset metrics

Resetting metrics clears only anonymous counters and recent failure text. It does not delete enquiries, prices, or configuration.

## 8. Adding the calculator to a page

Use:

```text
[foundation_form]
```

For an existing custom button:

```text
[foundation_form button="false"]
```

Give the custom element either `.foundation-trigger` or `data-foundation-calculator-open`.

Only one modal is printed per page even when the shortcode appears more than once.

## 9. Release-day checks

Complete all of these on staging:

1. Open and close the calculator with keyboard and pointer.
2. Complete a small website estimate and confirm the one-off formula.
3. Complete a route that produces a range.
4. Complete a route that requires a tailored quote.
5. Combine at least two main routes.
6. Save progress, receive the link, and restore it in a private browser window.
7. Submit the estimate twice using the same screen and confirm only one enquiry exists.
8. Confirm the team email, customer email, inbox record, PDF, and JSON.
9. Test on a narrow mobile viewport.
10. Purge page cache, object cache, and CDN cache, then repeat the public test.

## 10. Troubleshooting

### The Overview says the journey is custom or legacy

Use **Back up and apply journey**. If it returns after applying, a protected tool or old integration is changing `foundation_form_data`.

### The enquiry is in the inbox but no email arrived

The plugin completed its most important job. Check the email status, SMTP logs, sender-domain verification, spam folder, and provider limits.

### The calculator opens with old questions or prices

Purge the WordPress page cache, server cache, CDN, and browser cache. The config response is sent with no-cache headers, but a cached page can still contain an older loader.

### The ZIP is missing

Check the Overview health item for `ZipArchive`. PDF and JSON remain available when ZIP support is absent.

### A saved link opens the home page

Saved links are intentionally constrained to the same site origin. Ensure the page URL is HTTPS and not being rewritten to a different host or port by a proxy.
