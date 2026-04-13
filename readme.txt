=== Foundation Project Calculator ===
Contributors: Inkfire
Tags: calculator, quote, estimate, form builder, lead capture
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.3.5
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
* React/Tailwind admin builder with metrics, builder/settings navigation, and clean dark/light theme switching.
* Staff-friendly slide editing with drag/drop, keyboard move controls, and service-option routing.
* Save/resume draft flow with magic-link email support.
* GitHub-based auto-updates using bundled `plugin-update-checker`.

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
* SVG is excluded from the default upload allowlist for safety.
* Automatic updates expect tagged GitHub releases.
* Admin source lives in `src/admin`; production assets are built to `assets/admin`.

== Changelog ==

= 1.3.5 =
* Mounted the existing React builder inside the shared Foundation admin shell without changing REST routes, settings storage, or builder data.

= 1.3.1 =

- Fixed a frontend submission blocker where cached or draft selection state could report missing budget/timeline choices even after the customer had selected them.

= 1.3.0 =
* Rebuilt the admin builder in React and Tailwind with metrics and dashboard/builder navigation.
* Reset dark/light theme colors for a scoped, consistent admin UI.
* Added staff-friendly slide editing, drag/drop reordering, keyboard move controls, and service routing controls.
* Added save/resume drafts with magic-link email support.
* Added journey metrics for views, starts, saved drafts, incomplete closes, failures, and completed submissions.
* Hardened resume links so emailed draft links are constrained to the current site URL.
* Added rate limiting for magic-link email sends.
* Migrated plugin-default logo URLs when moving between versioned plugin folders.
* Kept the production frontend design intact while preserving the v1.1.0 validation, upload, email, and updater hardening.

= 1.0.0 =
* Initial release.
