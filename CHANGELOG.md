# Changelog

## 1.3.1

- fixed a frontend submission blocker where cached or draft selection state could report missing budget/timeline choices even after the customer had selected them
- added backend compatibility for older/cached picker payloads that include the scalar picker value without the selected option index
- bumped the plugin asset version so WordPress requests the corrected frontend JavaScript

## 1.3.0

- imported the uploaded `v1.3.0` admin redesign package into git
- replaced the legacy PHP/jQuery admin builder with a React/Tailwind admin app
- added a scoped Vite build that outputs production assets to `assets/admin`
- reset dark/light theme colors around a consistent admin surface instead of patching the legacy shell
- added staff-friendly slide editing with drag/drop, keyboard move controls, duplicate/remove actions, and empty states
- added a service-option inspector for labels, prices, and route-to-slide controls so backend and frontend stay aligned
- added frontend sync warnings for missing budget, timeline, services, empty options, and broken route targets
- added legacy metadata inference so existing budget/timeline/service fields are normalized into the roles the frontend expects
- moved settings into the React admin workspace as a tab on the main builder endpoint
- removed the disconnected Calculator Settings submenu from the Foundation admin menu
- added frontend save/resume draft support with magic-link email flow
- added lightweight journey metrics for views, starts, saved drafts, incomplete closes, failures, and completed submissions
- hardened resume links so emailed draft links are constrained to the current site URL
- added rate limiting for magic-link email sends
- improved no-email save messaging so users always see a usable resume link
- restored repo hygiene that was missing from the beta zip
- deployed the finalized `v1.3.0` build to production after blueprint and production smoke tests passed

## 1.0.0

- original production plugin snapshot from `inkfire.co.uk`
