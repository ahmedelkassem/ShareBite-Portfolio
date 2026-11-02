# Curated portfolio repository note

This copy begins with an independent new Git history. Unreviewed historic screenshots and unlicensed example food photos from the source repository have NOT been copied here. Other source-code, operational and licensing risks listed below still need review. The source repository's historic screenshots are NOT present in this copy's Git history.

# Security review and release checklist

This file documents current code review findings and verification scope. A green PHP syntax check is **not** a full security audit.

## Completed in merged PRs

- HTML output encoding and some redirect / CSRF controls were strengthened in PR #1.
- Failed-login throttling, image MIME/extension/dimension checks, randomized filenames, and an Apache uploads-directory rule were added in PR #2.
- The owner confirmed an end-to-end donation flow on a separate local test database before those PRs were merged, and later confirmed login throttling and image upload rejection behavior.

## Completed or confirmed after PR #3

- Donor approval/rejection and charity receipt use transactional state checks and a shared validation module; PR #3 was merged.
- The owner manually tested donor approval, charity confirmation of receipt, and donor rejection against `ShareBite_Test` on WAMP.
- Automated checks cover the state guards. Repeated, stale and unauthorized crafted requests still need manual integration tests.
- The locally exported 2026-11-02 schema was checked to contain only DDL; a sanitized 16-table structure-only SQL file is available at `database/schema.sql`. No row data is committed.
- PR #5 fixed the legacy `problem_reports` insertion columns. The owner tested submitting a problem report successfully on `ShareBite_Test`.

## Other audit findings (November 2026)

- Public charity registration currently inserts a charity-center record with `status = 'Active'` immediately, rather than waiting for Admin approval. This is an explicit product/authorization decision and has **not** been changed automatically.
- PR #7 moved donor pickup authorization/state checks into a locked transaction and separated donor pickup from confirmed charity receipt in tracking. The owner reported those manual tests working on `ShareBite_Test`. The fresh-install test confirmed direct charity receipt after donor approval, but did not repeat the optional pickup-before-receipt path.
- **Portfolio screenshot cleanup completed in this copy:** only two contact-redacted JPG screenshots created from disposable accounts are included. Unreviewed historic screenshots and example food photos were not imported into this new repository. Image rights and metadata of included assets, and all other remaining release checks, still require review.
- We have **not** proven the absence of credentials or personal data from all Git history, forks, PR diffs, or screenshots. The repo must remain Private pending a full history/asset review.

## Fresh-install status and remaining limits (2026-11-02)

The owner reported that a separately created `ShareBite_Fresh` installation connected to `sharebite_fresh_test` loaded, supported Volunteer/Donor/Charity account creation, created a food offer, accepted a Volunteer request, allowed Donor approval, and reached `Completed` / `Received` after Charity confirmation. See `docs/testing.md`.

This is evidence that the core flow works on a clean local database, **not** evidence that the project is safe for deployment or public release. Visual screenshot audit, photo licensing, entire Git history and privacy review, authorization abuse cases, reset-email integration, and production configuration remain open.

## Still to review before making the repository Public

1. Confirm only the intended files, images, licenses, and screenshots are safe to publish. Inspect all commit history for exposed credentials or personal data, not just the latest commit.
2. Password reset request rate limiting was merged in PR #6, and its unit tests passed. Verify the behavior under local WAMP, then assess session/cookie security for deployment. The single-server limiter uses a 60-minute rolling window with three requests per email and twenty per client IP; other users on the same IP share the latter quota.
3. Test authorization across every role (Volunteer, Donor, Charity, Admin), including tampered IDs and actions on another account's resources.
4. Recheck file upload web-server configuration. An `.htaccess` rule alone depends on Apache settings and is not sufficient for production upload security.
5. The bundled PHPMailer code identifies as **7.1.1** (the official release from May 2026, as checked in November 2026), and Bootstrap's header loads **5.3.3**. Review the actual versions of Leaflet, Chart.js, other CDN assets, security advisories, and their licenses before Public release.
6. **Basic fresh-install validation completed on 2026-11-02:** the owner imported the 16-table schema into a new empty MySQL 8 database and confirmed registration/login for three roles, offer creation, volunteer request, donor approval and charity receipt. Other Admin, authorization, security and reporting workflows on a clean install remain unverified.
7. Verify relevant logs, error handling, secrets configuration, HTTPS, backup handling, and privacy practices before internet hosting.

## Further manual tests before public release

Use `ShareBite_Test` and its own test database only; keep the original WAMP project untouched.

- Donor approves a Pending reservation: one Approved reservation, donation Claimed, and one action log.
- Donor rejects a Pending reservation: Rejected reservation, donation Available.
- Repeated approval/rejection should do nothing. A different donor must not be able to update the request.
- Charity receiving a Pending or Rejected request is blocked, even if a POST is crafted.
- Charity receiving a Donor-approved request succeeds; repeating it does nothing.
- A donation already marked Collected through pickup remains confirmable by the correct charity center.
- Verify the reservation and donation statuses stay in sync after all cases.
- Recheck registration, volunteer request, tracking and notification screens after updating test files.

The owner tested approval, receipt and rejection on local WAMP. The assistant cannot run WAMP integration tests remotely; the remaining cases above are not yet confirmed.
