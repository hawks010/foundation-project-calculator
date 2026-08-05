=== Foundation Project Calculator ===
Contributors: Inkfire
Tags: project calculator, estimate, quote, accessibility, lead capture
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A guided Inkfire project calculator with editable prices, secure server-side estimates, tailored-quote routing, and a simple private enquiry inbox.

== Description ==

Foundation Project Calculator converts Inkfire's Web, Tech, and Business pricing into a clear multi-step customer journey.

Version 1.4 keeps the route logic stable and makes ordinary administration much simpler. Staff edit labelled prices, email settings, branding, and operational controls without working inside a complex visual flow builder.

Key features:

* Multi-route calculator covering Web & Accessibility, Tech & Support, and Business Support & Marketing.
* Customers can combine several service routes in one estimate.
* Separate one-off and monthly totals.
* Exact, range, and tailored-quote outcomes.
* Live customer estimate summary.
* Server-authoritative price calculation and route validation.
* Thirty-eight editable prices grouped into plain admin sections.
* Read-only customer-journey map with route and price-key health checks.
* Blueprint integrity check and safe one-click migration with backup.
* Private enquiry inbox stored before email delivery is attempted.
* Branded team and customer emails.
* Optional PDF, JSON, and ZIP staff packages.
* Save-and-resume links restricted to the current site origin.
* Nonce checks, rate limits, honeypot handling, duplicate protection, and bounded input.
* Strict upload validation and executable-format deny-list.
* Automatic lead retention plus WordPress privacy exporter and eraser integration.
* Responsive keyboard and focus handling for the public modal.
* GitHub release updates through the bundled update checker.

All bundled prices are shown excluding VAT. The calculator is a planning estimate rather than a binding quotation.

== Installation ==

1. Back up the WordPress database and current calculator plugin.
2. Upload the `foundation-project-calculator` folder to `/wp-content/plugins/`.
3. Activate the plugin.
4. Open **Foundation > Project Calculator**.
5. On an upgrade, use **Back up and apply journey** when the migration notice appears.
6. Set the real recipient and verified sender under **Emails & branding**.
7. Add `[foundation_form]` to the calculator page.
8. Complete one exact-price, one range, and one tailored-quote submission on staging.

== Usage ==

Standard calculator:

`[foundation_form]`

Custom label:

`[foundation_form label="Plan my project"]`

Hide the built-in launch button:

`[foundation_form button="false"]`

Custom triggers can use `.foundation-trigger` or `data-foundation-calculator-open` while the shortcode remains on the page.

== Upgrade notice ==

= 1.4.0 =
The pricing journey has been rebuilt from the August 2026 Inkfire board. Existing real journeys are preserved until an administrator explicitly backs them up and applies the bundled journey.

== Frequently Asked Questions ==

= Will an email failure lose the enquiry? =

No. A completed enquiry is stored in the private calculator inbox before WordPress attempts email delivery.

= Are the browser totals trusted? =

No. The server validates the answers and calculates the final stored estimate from the live price catalogue.

= Does the plugin include VAT? =

The bundled prices exclude VAT. The public VAT note is editable under Emails & branding.

= Can staff change the customer questions? =

The 1.4 admin intentionally presents the tested journey as read-only. Staff can safely change prices and customer-facing operational copy. A protected legacy REST route remains available to administrators for controlled tooling, and any custom save clears the bundled-blueprint status.

= Does ZIP packaging always work? =

ZIP packaging requires the PHP `ZipArchive` extension. PDF and JSON reports can still be attached when it is unavailable.

= Is this release fully approved for production? =

It is a production candidate. Complete the staging, SMTP, theme, cache, and host checks in the deployment notes before release.

== Changelog ==

= 1.4.0 =
* Rebuilt the calculator around Mali's August 2026 Inkfire pricing board.
* Added 28 guided screens, 49 questions, 38 editable prices, and 27 verified route targets.
* Replaced the complex everyday admin flow editor with a server-rendered dashboard and safe price editor.
* Added a private local enquiry inbox and stored leads before mail delivery.
* Added exact, range, monthly, and tailored-quote outcomes.
* Added server-authoritative pricing, strict route validation, duplicate locks, rate limits, and same-origin resume links.
* Added journey integrity checks, safe backup/apply migration, and restore controls.
* Added deterministic PHP, static-security, and Chromium journey checks.

= 1.3.5 =
* Previous administration and customer-journey release.

= 1.0.0 =
* Initial release.
