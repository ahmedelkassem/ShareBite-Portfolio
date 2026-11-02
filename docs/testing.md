# ShareBite local verification

Automated checks in GitHub Actions:
- `php -l` for every PHP file.
- `php tests/login_throttle_test.php` for the basic login limiter.
- `php tests/donation_state_test.php` for authorization and state-guard logic.

These checks do **not** use a real MySQL instance or browser. Always test transaction and permission changes on `ShareBite_Test` linked to its own test database.

## End-to-end exercise

1. Donor adds a new offer with an authentic JPG, PNG or WebP.
2. Volunteer requests it for an active charity center; verify the offer becomes Pending.
3. Donor approves and checks the volunteer's reservation status.
4. Charity confirms receipt after approval. Verify the reservation becomes Completed and delivery_status Received.
5. Repeat with a second offer: Donor approves and marks pickup Collected first. Charity should still be able to confirm receipt.
6. Repeat with a rejected request. It must not appear as confirmable in Charity dashboard.
7. Verify repeated POSTs or stale browser tabs cannot approve or confirm the same action twice.
8. Check a volunteer cannot approve a donor's request and a different charity cannot confirm its delivery.
9. Confirm that logs and notifications correspond to successful committed transitions.

Use disposable test offers only. Do not upload real users' data or database dumps to GitHub.

## Pickup vs. receipt regression checks (PR #7 merged)

Use a disposable offer in `ShareBite_Test` and keep the production/original `ShareBite` project unchanged.

1. Donor publishes an available donation; Volunteer requests it for an active Charity; Donor approves. Check Reservation = Approved and Donation = Claimed.
2. Before pickup, open the tracking page. It should say donor-approved, not Received.
3. Donor clicks the existing button to mark pickup/collection. Check Reservation = Collected and Donation = Collected.
4. Open tracking again **before** the Charity receives it. It must say **Picked Up**, not **Received**.
5. Sign in as the selected Charity, verify the Confirm Received button is still available, and submit. Then Reservation = Completed, Delivery = Received and tracking says Received.
6. Repeated pickup requests, another donor, or any non-donor role must not be able to change the donation. A Pending donation must not be marked Collected.

These are manual MySQL/browser integration checks; passing the unit tests alone does not verify this flow.

## Fresh-install verification: owner confirmed on 2026-11-02

The owner performed the following browser tests on a **separate** WampServer folder `ShareBite_Fresh` connected to the newly imported MySQL database `sharebite_fresh_test`. This did not use the original `ShareBite` database or the `ShareBite_Test` database.

- Imported the `database/schema.sql` structure-only export into the empty database and verified that the ShareBite homepage loaded with no donations.
- Registered and logged in as a **Volunteer**.
- Registered and logged in as a **Donor**, and successfully published a test food donation.
- Registered and logged in as a **Charity** with its own charity center.
- Volunteer requested the test donation and selected that charity center. The request moved to **Pending**.
- Donor approved the reservation; the user confirmed success.
- Charity confirmed receipt. The user reported the expected **Completed** reservation status and **Received** delivery/tracking status.

**Result:** The main user journey from a new installation worked on the owner's computer. This is user-reported manual testing, **not** remotely executed or independently observed by the assistant. It does **not** verify every Admin screen, password-reset email limits, rejection, pickup-before-delivery, repeated requests, unauthorized requests, mail delivery, empty-schema re-import behavior, or production deployment. Keep the database and example data local; do not publish test emails or full DB dumps.
