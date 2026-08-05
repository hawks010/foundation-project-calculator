# Static and Security QA Results

Generated: 2026-08-05 20:56:58 UTC

Environment: PHP 8.4.16, Node v22.16.0

| Result | Check |
|---|---|
| PASS | All PHP files pass syntax lint under 8.4.16 |
| PASS | All shipped JavaScript files pass node --check |
| PASS | All shipped CSS files have balanced block braces |
| PASS | No dangerous execution or unsafe deserialisation primitives in plugin-owned PHP |
| PASS | All four public mutating AJAX handlers require the calculator nonce |
| PASS | Every REST route is restricted to manage_options |
| PASS | Submission totals are calculated server-side and are not accepted from browser totals |
| PASS | Uploads use WordPress extension/MIME recognition, uploaded-file checks and an executable deny-list |
| PASS | Every public draft save is rate-limited and anonymous metrics do not retain the customer email |
| PASS | Resume URLs require an exact same scheme, host and port |
| PASS | Launch health detects changes to the bundled journey, not only a version label |
| PASS | Enquiries are stored locally before email delivery is attempted |
| PASS | No obsolete React/Vite admin runtime references remain |
| PASS | Plugin-owned runtime code avoids common PHP 8-only constructs and remains syntactically compatible with the declared PHP 7.4 baseline |
| PASS | Git diff has no whitespace errors |

**Checks:** 15 passed, 0 failed.
