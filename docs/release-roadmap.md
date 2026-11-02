# ShareBite: portfolio release now, public hosting later

These are two different releases. Making source code readable on GitHub does **not** host the PHP website; hosting it later requires a separately secured web server and production database.

## Phase 1: employer-facing GitHub portfolio

**Goal:** Let prospective employers understand the problem, architecture, source code and verified user journey. No live customer accounts or production traffic.

### Done

- Documented the main roles and donation workflow.
- Added a structure-only MySQL 8 schema with no application rows.
- Verified the core donor, volunteer and charity flow after a clean local import (owner-reported WAMP test).
- Added GitHub Actions checks for PHP syntax, throttle logic, donation rules and database structure.
- Wrote README and interview/CV notes for a portfolio audience.

### Blocking before public visibility

- Inspect **every screenshot** and all sample food photos for third-party ownership, EXIF or embedded personal/organization details. Historical screenshots include visible account/business labels and location information.
- Review Git commit history, PR revisions and filenames for personal data, credentials or other sensitive artifacts. Removing a file from the latest commit alone does **not** erase it from the history.
- Review and correct outstanding authorization, session, CSRF and reset-email issues documented in `docs/security-review.md`.
- Verify the repository contains only intended, licensed third-party libraries and assets.
- Create a **sanitized public copy with a clean history** if the original history contains private information, rather than relying on deleting files in the latest commit.
- Obtain the owner's explicit approval before changing visibility or giving external access.

Until those gates are complete, keep this original repository **Private**. An employer cannot view a Private repo by merely receiving its URL.

### Recommended demo material

Capture a fresh screenshot collection using disposable accounts and locations that are not tied to real users: homepage, donation details, role dashboards, reservation tracking, admin charts and map. Check that the screenshots do not show emails, phone numbers or actual people's names.

## Phase 2: future public application deployment

Only after portfolio release and separate operational planning:

1. Choose supported PHP hosting with a hardened web server and MySQL 8 database. Maintain independent production and test environments.
2. Set SMTP secrets in host environment/configuration outside the publicly accessible app; secure user-uploaded files outside the executable web root.
3. Review authentication, authorization and workflow race conditions, including negative tests for role impersonation, stale requests, tampered record IDs, CSRF and reset-email abuse.
4. Require HTTPS; configure secure session cookies, backups, error logging, monitoring and account recovery protections.
5. Define charity center verification policy. The current registration flow activates charities immediately, which is not automatically suitable for public use.
6. Define food safety, privacy, retention, consent and operational responsibilities before collecting information from real users.
7. Run deployment-specific testing and security review, then enable actual public access.

**Do not** treat green PHP syntax checks or a successful local demo as a production security approval.

For known findings and test coverage, see [security-review.md](security-review.md) and [testing.md](testing.md).
