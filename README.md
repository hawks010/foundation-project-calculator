# Foundation Project Calculator

Version: 1.3.0

Public repository: https://github.com/hawks010/foundation-project-calculator

A branded multi-step quote calculator and lead capture plugin for WordPress, hardened for a production-safe rollout on Inkfire.

## What is included in this build

- Sanitised builder saves via the REST API
- Authenticated REST access for builder reads and writes
- Dedicated settings page for admin emails, branding, uploads, and customer copy
- Safer frontend rendering and improved validation states
- Server-side required-field validation for non-JavaScript and tampered submissions
- Customer confirmation toggle
- More accurate success-state messaging when customer emails are disabled or fail
- Upload validation for file type, file count, and file size
- Safer default upload types with SVG excluded by default
- Admin email staff package attachments:
  - PDF summary
  - JSON summary
  - ZIP package of uploads when supported by the server
- Bundled GitHub auto-updater using `plugin-update-checker`
- React/Tailwind admin builder with dashboard/builder sections and clean dark/light theme switching
- Staff-friendly slide editing with drag/drop, keyboard move controls, and a service-option routing inspector
- Save/resume draft flow with magic-link email support
- Updated uninstall cleanup

## Admin flow

1. Build the steps in **Foundation > Project Calculator**
2. Use the slide rail to add, remove, duplicate, and reorder customer-facing slides
3. Use the builder canvas to add questions and service pickers
4. Use the inspector to edit prices, route service options to detail slides, and check frontend sync warnings
5. Configure email, branding, and uploads in **Foundation > Calculator Settings**
6. Save and test the live flow from the frontend
7. Use `[foundation_form]` on the target page

## Admin build

- Source lives in `src/admin`
- Production assets are built to `assets/admin/admin-app.js` and `assets/admin/admin-app.css`
- Build command: `npm run build`
- Tailwind preflight is disabled so the admin app does not globally reset WordPress admin styles
- The React app saves through the existing authenticated `foundation/v1/save` REST endpoint and keeps the frontend `foundation_form_data` schema intact

## GitHub updater

- The plugin now includes a GitHub-based updater pointed at `hawks010/foundation-project-calculator`
- Publish release ZIPs from tagged GitHub releases so WordPress can detect updates cleanly
- If the repository ever becomes private again, provide a token with:
  - `FOUNDATION_PROJECT_CALCULATOR_GITHUB_TOKEN`
  - or the `foundation_project_calculator_github_token` filter

## Notes

- The frontend launch button can be hidden with `[foundation_form button="false"]`
- You can trigger the calculator with `.foundation-trigger` or a link containing `get-quote`
- Upload packaging depends on server support for `ZipArchive`
- SMTP delivery depends on a verified sender domain in the active mail provider
