# Foundation Project Calculator 1.4.0 Final Local QA Report

**Run date:** 5 August 2026
**Working source:** user-supplied 1.3.x plugin archive
**Pricing source:** Mali's Inkfire pricing-flow PDF dated 5 August 2026
**Repository target:** Inkfire-limited/foundation-project-calculator

## Outcome

**Local result: PASS for a production-candidate package.**

No claim is made that the live Inkfire stack has passed. The remaining environmental checks are listed below.

## Environment

- PHP 8.4.16
- Node.js 22.16.0
- Chromium 144.0.7559.96
- Playwright 1.57.0 under Xvfb
- No local WordPress database runtime
- No PHP `ZipArchive` extension
- No PHP 7.4 binary

## Unit and integration-style tests

The deterministic PHP suite covers:

- blueprint structure and integrity fingerprint
- legacy/custom journey warning
- exact same-origin resume URLs
- transient rate limiting
- bounded price sanitisation
- small and bespoke website formulas
- complete Web example
- alt-text manual threshold
- existing-site discovery route
- ongoing and occasional IT calculations
- project-size manual outcomes
- Microsoft setup and Intune totals
- Cyber Essentials discovery
- accessibility tech hours
- social exact, range, and manual outcomes
- blog, copy, and newsletter outcomes
- Access to Work routing
- EA, PA, VA, and customer-support calculations
- strategy billing selection
- marketing combination
- multi-route combination
- hidden-field required validation
- tampered option rejection
- single-choice enforcement
- numeric bounds and step validation
- direct-engine numeric clamping
- blocked upload extensions
- private enquiry creation and lookup
- submission locking
- mail-status allow-list
- privacy export and erasure
- retention cleanup
- cron scheduling

Final result: **40 passed, 0 failed**.

## Static and security checks

The static script verifies:

- syntax of every shipped PHP file, including the bundled updater
- JavaScript parseability
- balanced CSS blocks
- absence of dangerous execution and unsafe deserialisation primitives in plugin-owned PHP
- nonce coverage for public mutations
- capability protection for REST routes
- server-side price calculation
- upload safeguards
- draft abuse protection and metric privacy
- exact-origin saved links
- blueprint integrity fingerprint
- lead storage before mail
- absence of obsolete administration runtime references
- avoidance of common PHP 8-only constructs in shipped plugin code
- clean Git whitespace

Final result: **15 passed, 0 failed**.

## Chromium customer smoke test

The browser test uses the actual public calculator CSS and JavaScript with a mocked transport response. It exercises rendering, branching, client validation, live totals, review, contact submission, and the success receipt.

Journey:

1. Web & Accessibility
2. Small/basic website
3. Five pages
4. WooCommerce enabled
5. Hosting plus plugins
6. 26–50 alt texts
7. Accessibility testing
8. Accessibility setup
9. Contact submission

Expected and observed result:

- One-off: £4,260
- Monthly: £45
- VAT: excluded
- Success receipt: displayed
- Save control after completion: hidden
- Close and reopen: returned to a clean new estimate
- Browser console errors: none recorded

## Code and architecture review

### Administration

- The everyday editor no longer exposes branching internals.
- Price values are separate from the customer-flow definition.
- The journey is visible but read-only.
- Existing real flows are preserved during upgrade.
- Applying the new journey creates a backup.
- Restoring a backup swaps it safely with the current journey.
- A content fingerprint detects supported-flow drift.

### Customer submission

- The server reads the live journey and catalogue.
- Contact and answers are sanitised and bounded.
- Hidden branches do not become required.
- Invalid browser selections are rejected.
- Uploads are checked before use.
- The estimate is calculated on the server.
- A private enquiry is created before email.
- Duplicate requests return the original record.
- Email failures remain visible in the local inbox.

### Data lifecycle

- Drafts expire.
- Stored enquiries expire according to the configured period.
- Personal-data export and erase callbacks are registered.
- Uninstall removes plugin data and transient storage.
- Uploaded files are not duplicated in the local inbox.

## Known limitations and live gates

The following were not possible in the local environment:

- activating the package on a real WordPress staging database
- sending through Inkfire's actual SMTP service
- receiving and inspecting a real mailbox delivery
- exercising the installed PHP 7.4 runtime
- exercising `ZipArchive`
- testing the active Inkfire theme, optimisation stack, object cache, security plugin, or CDN
- automated Safari and Firefox runs
- formal screen-reader testing

These checks must be completed on staging. See `DEPLOYMENT_NOTES.md`.

## Final packaging rule

The installable release must contain one root directory named `foundation-project-calculator` and must exclude source-control data, test evidence, generated browser harnesses, and deleted build tooling. The source archive may include tests and reports but must also exclude `.git` and generated browser artefacts.
