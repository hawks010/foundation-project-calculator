# Changelog

## 1.1.0

- imported the uploaded `v1.1.0` package into git on top of a live `v1.0.0` safety snapshot
- added a bundled GitHub auto-updater using `plugin-update-checker`
- added plugin metadata for `Plugin URI`, `Update URI`, WordPress, and PHP requirements
- restricted builder REST reads to authenticated administrators instead of leaving them public
- added server-side validation for required builder fields to match frontend rules
- tightened upload validation so unknown upload fields and disallowed extensions are rejected
- removed SVG from the default upload allowlist for safer first-run behaviour
- fixed success-state messaging so customer email disabled and failed states are not reported as sent
- improved mail fallback logic so placeholder sender addresses do not poison delivery defaults
- removed uninstall cleanup for a legacy option this version does not create
- confirmed PDF, JSON, and ZIP package generation on sandbox via WP-CLI

## 1.0.0

- original production plugin snapshot from `inkfire.co.uk`
