# Foundation Project Calculator

Version: 1.1.0

A branded multi-step quote calculator and lead capture plugin for WordPress.

## What is included in this build

- Sanitised builder saves via the REST API
- Dedicated settings page for admin emails, branding, uploads, and customer copy
- Safer frontend rendering and improved validation states
- Customer confirmation toggle
- Upload validation for file type, file count, and file size
- Admin email staff package attachments:
  - PDF summary
  - JSON summary
  - ZIP package of uploads when supported by the server
- Updated uninstall cleanup

## Admin flow

1. Build the steps in **Foundation > Project Calculator**
2. Configure email, branding, and uploads in **Foundation > Calculator Settings**
3. Save and test the live flow from the frontend
4. Use `[foundation_form]` on the target page

## Notes

- The frontend launch button can be hidden with `[foundation_form button="false"]`
- You can trigger the calculator with `.foundation-trigger` or a link containing `get-quote`
- Upload packaging depends on server support for `ZipArchive`
