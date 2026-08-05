# Foundation Project Calculator 1.4 Deployment Notes

## Release classification

Version 1.4.0 is a **production candidate**. Do not replace the live calculator until the staging gates below pass on the real Inkfire stack.

## Package

Install the production ZIP whose root directory is:

```text
foundation-project-calculator/
```

Do not rename the root folder between environments. A stable directory keeps asset URLs, updater identity, and stored default-logo migration predictable.

## Before deployment

1. Export the WordPress database.
2. Copy the currently active calculator plugin folder.
3. Record the current active plugin version and directory name.
4. Record the live calculator page URL and shortcode placement.
5. Capture the current email recipient, sender, SMTP provider, and verified domain.
6. Confirm a staging environment has the same theme, cache, security, SMTP, and PHP extensions as production.
7. Disable automatic production activation during testing.

## Staging installation

1. Upload the production ZIP in **Plugins > Add New > Upload Plugin**.
2. Activate Foundation Project Calculator.
3. Open **Foundation > Project Calculator**.
4. For an existing site, select **Back up and apply journey** in the migration panel.
5. Check that Overview reports the Inkfire 2026 journey and connected routes.
6. Open Prices and verify a sample from each category against `MALI_PRICING_MAPPING.md`.
7. Configure the real staging recipient and a sender verified by the staging SMTP provider.
8. Confirm the privacy-policy URL.
9. Add or verify `[foundation_form]` on the calculator page.
10. Purge WordPress, server, CDN, and browser caches.

## Required staging acceptance tests

### Customer journey

- Open from the built-in button.
- Open from any custom site trigger.
- Close with the close button and Escape.
- Navigate with keyboard only.
- Verify focus returns to the launching control.
- Complete the Web small-site example: 5 pages should produce £2,750 one-off before add-ons.
- Complete the Web full example used by automated smoke testing: 5-page small site, WooCommerce, hosting plus plugins, 26–50 alt texts, testing, and setup should produce £4,260 one-off plus £45/month.
- Complete a range example, such as 12–16 static social posts.
- Complete a tailored example, such as Cyber Essentials or 76+ alt texts.
- Combine at least two main routes and confirm totals add correctly.
- Verify mobile layout at approximately 375 px wide.
- Test current Chrome, Safari, Firefox, and Edge where available.

### Save and resume

- Save an estimate with a real inbox.
- Confirm the resume URL uses the exact current HTTPS host.
- Open it in a private browser session.
- Confirm answers and the appropriate visible screen are restored.
- Confirm files are not claimed to be retained.
- Repeat requests quickly enough to confirm rate limiting returns a friendly message rather than a fatal error.

### Submission and email

- Submit a complete estimate.
- Confirm the success receipt and reference.
- Confirm exactly one private inbox record exists.
- Confirm the team email arrives.
- Confirm the customer copy arrives when enabled.
- Confirm Reply-To points to the customer on the team email.
- Inspect PDF and JSON reports.
- Confirm the ZIP package when `ZipArchive` is installed.
- Temporarily route mail to a controlled failure or disable SMTP, then confirm the enquiry still appears locally with a failed mail status.
- Submit the same screen twice and confirm duplicate protection does not create a second record.

### Admin

- Edit one non-live staging price and confirm the public estimate changes.
- Restore the original price.
- Search prices by service name.
- Review the read-only journey map.
- Export configuration and confirm recipient/sender addresses are absent.
- Restore a journey backup on staging, then reapply the current blueprint.
- Confirm only administrators can access the dashboard and protected REST endpoints.

### Compatibility and operations

- Check browser developer tools for JavaScript errors.
- Check the WordPress debug log and PHP error log.
- Verify no mixed-content requests.
- Verify the active cache does not serve an older form configuration after a price change.
- Check the security plugin does not block legitimate `admin-ajax.php` requests.
- Confirm the host accepts the configured total attachment size.
- Run one test with object cache enabled and one after purge.

## Production release

Proceed only after the staging evidence is signed off.

1. Put the calculator page into a controlled maintenance window if traffic warrants it.
2. Back up the live database and plugin folder again.
3. Install and activate the 1.4.0 production ZIP.
4. Apply the journey migration if shown.
5. Verify prices and email settings were preserved.
6. Purge all cache layers.
7. Complete one live internal test using a non-customer email address.
8. Confirm the enquiry record and both email statuses.
9. Remove the test enquiry according to Inkfire's data process.
10. Monitor PHP, SMTP, and calculator failure logs during the first working day.

## Rollback

1. Deactivate version 1.4.0.
2. Restore the previous plugin directory.
3. Restore the database backup only when the previous code cannot safely read the new options or when the journey migration itself must be reversed.
4. When the plugin remains active and only the journey needs reversing, use **Customer journey > Restore backup** instead of a database rollback.
5. Purge all cache layers.
6. Test the calculator page and one internal submission.

New enquiry records use a private post type and can remain in the database during a code rollback, but verify the previous version does not expose or mishandle them.

## GitHub updates

The updater points to:

```text
Inkfire-limited/foundation-project-calculator
```

Publish tagged releases with an installable ZIP containing the stable `foundation-project-calculator` root folder. For a private repository, provide a token through the documented constant or filter and never store it in plugin options or the release archive.

## Host-dependent gates

The local QA environment could not prove:

- real WordPress activation on Inkfire staging
- live SMTP delivery and sender-domain authentication
- PHP 7.4 execution, although shipped runtime code was scanned for common PHP 8-only syntax
- the real `ZipArchive` path, because that extension was absent locally
- conflicts with the production theme, cache, firewall, object cache, or other plugins

These are deployment gates, not optional polish.
