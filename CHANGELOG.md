# Changelog

## 1.3.0

- imported the uploaded `v1.3.0` admin redesign package into git
- added the redesigned admin dashboard shell with dashboard, builder, settings, metrics, and theme switching
- added frontend save/resume draft support with magic-link email flow
- added lightweight journey metrics for views, starts, saved drafts, incomplete closes, failures, and completed submissions
- fixed beta dark-mode shell behaviour so the admin dashboard renders on a real dark surface inside WordPress admin
- hardened resume links so emailed draft links are constrained to the current site URL
- added rate limiting for magic-link email sends
- improved no-email save messaging so users always see a usable resume link
- restored repo hygiene that was missing from the beta zip
- noted that this package does not include React/Tailwind source tooling; the redesign is shipped as PHP, vanilla JS, and CSS in the plugin

## 1.0.0

- original production plugin snapshot from `inkfire.co.uk`
