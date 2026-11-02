# Portfolio publication review

**Reviewed:** 2026-11-02. **Scope:** this independent `ShareBite-Portfolio` repository, not the private historical `ShareBite` repository and not a production website.

## Checks completed in the current portfolio repository

- New independent history: two original commits created for the sanitized copy. The source repository's old screenshot history was **not** copied.
- Current files: application PHP, data-free `database/schema.sql`, unit tests and a GitHub Actions workflow, together with two masked test-account screenshots.
- No actual `mail_config.php`, `.env`, full user database, historic screenshots or sample food-photo files in this repository tree.
- PHPMailer code retains its attribution, and its actual LGPL 2.1 license text is now included under `PHPMailer/LICENSE`.
- Reviewed key state-change endpoints for role checks and CSRF controls, and reviewed dynamic HTML output on application pages. `reset_password.php` had an unsafe email value in an HTML attribute: corrected with `escapeHtml()` in the security-review PR.
- Automated regression tests check that specific known privacy files are absent and that the reset form continues to escape this field.

This is a **focused source review**, not a comprehensive penetration test, static-analysis certification or review of all possible data flows. No exposed access tokens were identified in the reviewed files, but this does not guarantee there are no undiscovered secrets or security bugs.

## Outstanding before converting this portfolio repository to Public

1. **Owner and collaborator approval:** confirm rights to publish all original PHP/CSS/SQL code and the two demo screenshots. A course or group project can have shared contributions.
2. **Access control and logic tests:** attempt unauthorized operations with mismatched Volunteer, Donor, Charity and Admin accounts, stale reservations and tampered record IDs. The success-path tests don't establish these defenses.
3. **Account and session controls:** review cookie `Secure`/`HttpOnly`/`SameSite` and server session policies for later hosting, including role changes and recovery.
4. **Charity verification decision:** registration currently creates an `Active` charity without Admin approval. That behavior is not changed by this review.
5. **Dependencies:** Chart.js is loaded from an unversioned CDN URL. Pin its version and consider CDN integrity protection in a separately tested change. Preserve vendor attribution and verify any actual PHP/JS dependencies before deployment.
6. **Production deployment:** SMTP credentials, HTTPS, upload-serving restrictions, rate-limit infrastructure, backups and privacy practices still need a separate review before the website can accept real users.

## Recommendation

The portfolio content and independent history are much cleaner than the original repository, but **do not automatically set Public**. Confirm ownership and finish appropriate security/privacy review for the intended exposure. An offline interview demo or explicit access to the Private repository is possible meanwhile.

Source references: [OWASP Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html), [OWASP XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html), [GitHub's history-removal guidance](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository), and [third-party notices](../THIRD_PARTY_NOTICES.md).
