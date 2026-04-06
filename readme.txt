=== Foundation Project Calculator ===
Contributors: Inkfire
Tags: calculator, quote, estimate, form builder, lead capture
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.1.0
License: GPLv2 or later

A branded multi-step project calculator for WordPress with configurable emails, upload packaging, and an accessible builder.

== Description ==

Foundation Project Calculator helps teams collect project briefs in a cleaner, more production-ready way.

Key features:

* Multi-step builder for quote and onboarding-style flows.
* Conditional routing between screens.
* Pricing support for cards, toggles, and sliders.
* Customer confirmation email and branded admin notification email.
* Upload handling with file type and size validation.
* Admin package attachments including PDF summary, JSON summary, and optional ZIP bundle of uploads.
* Frontend branding controls for intro copy, imagery, success messaging, and customer CTA links.
* Improved sanitisation and safer saved builder data.
* Accessible frontend improvements including clearer validation, focus management, and keyboard support.

== Installation ==

1. Upload the `foundation-project-calculator` folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. Go to **Foundation > Project Calculator** to build your form flow.
4. Go to **Foundation > Calculator Settings** to set email recipients, branding, and upload rules.
5. Place the shortcode `[foundation_form]` on the page where the calculator should appear.

== Usage ==

Frontend shortcode:
`[foundation_form]`

Hide the default button:
`[foundation_form button="false"]`

This is useful when you want to launch the form with your own trigger using `.foundation-trigger` or a link containing `get-quote`.

== Notes ==

* Uploaded files are validated against the allowed file list in the settings page.
* The admin email can include a PDF summary, JSON summary, and ZIP package when supported by the server.
* The builder stores sanitised form definitions in the `foundation_form_data` option.

== Changelog ==

= 1.1.0 =
* Added a friendly settings screen for notifications, branding, and upload rules.
* Replaced hard-coded branding and email values with configurable options.
* Added sanitisation for builder saves and safer frontend rendering.
* Added upload validation, PDF summaries, JSON exports, and optional ZIP staff package attachments.
* Improved frontend accessibility, focus handling, and validation feedback.
* Updated uninstall cleanup.

= 1.0.0 =
* Initial release.
