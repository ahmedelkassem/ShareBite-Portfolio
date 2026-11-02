# ShareBite | Portfolio project profile

## Short overview (for a project card)

**ShareBite** is a full-stack food donation platform developed with PHP, MySQL and JavaScript. It coordinates surplus-food offers between donors, volunteers and charity centers through role-based dashboards, reservation approvals, location mapping and delivery confirmation.

## CV project entry (English)

**ShareBite | Full-Stack Web Application**
**Technologies:** PHP, MySQL, PDO, JavaScript, HTML/CSS, Bootstrap, Leaflet, OpenStreetMap, Chart.js, PHPMailer, GitHub Actions.

- Developed a multi-role food donation system for Donors, Volunteers, Charity Centers and Admins, supporting the end-to-end donation journey.
- Built food-offer management and filtering, donation requests, approval/rejection, delivery tracking and charity receipt confirmation.
- Integrated interactive location mapping, dashboard analytics, notifications, and an SQL-backed workflow.
- Added application protections and automated checks for core state transitions, authentication throttling and file uploads. Verified a clean local installation with test accounts.

*Use these bullets only if they accurately reflect your personal contribution. When describing a group project, distinguish your own work from team contributions.*

## Interview-friendly 60-second explanation

> ShareBite is a PHP and MySQL web application I worked on to address coordination problems in food donation. Donors publish surplus-food offers, volunteers request donations and choose a destination charity, and the donor approves the handoff. A charity center then confirms receipt. Different roles have their own dashboards, and users can track the donation status. The application also uses maps to display locations and charts for administrative reporting. I used GitHub to maintain the code and add focused automated checks. I also tested the main workflow after importing a structure-only schema into a fresh local MySQL database. It's currently a portfolio project, with public hosting planned only after security and privacy review.

## Technical points worth demonstrating

| Interview topic | Code to show | Honest explanation |
| --- | --- | --- |
| Data modeling | `database/schema.sql` | The main tables cover users, donations, reservations, charity centers, logs and more |
| Authentication | `login.php`, `login_throttle.php` | Uses password hashing, sessions and rate limiting |
| Authorization and concurrency | `donor_action.php`, `confirm_delivery.php`, `complete_pickup.php` | Restricts workflow changes by role and current state; key actions run in transactions |
| Maps | `map.php` | Uses Leaflet and OpenStreetMap to display donation and charity locations |
| Dashboards | `admin_dashboard.php` | Aggregates donations into monthly and city-based charts |
| Testing | `tests/`, `.github/workflows/php-lint.yml` | PHP syntax checks plus focused business-rule tests, complemented by local WAMP testing |

## Current proof, not a deployment claim

On November 2, 2026, the owner confirmed that the core Donor/Volunteer/Charity journey worked from a new local database install. This is **not** a production rollout, external penetration test, comprehensive browser automation or guarantee that every screen works under all conditions. Production infrastructure, privacy protections and broader authorization tests are future work.

## Before sharing with employers

The repository is currently Private. Before changing visibility, replace or remove screenshots containing personal/account information, confirm image rights, scan the full Git history for secrets and personal data, and review `docs/security-review.md`.

If the project remains Private, an ordinary GitHub link alone will not let a recruiter view its code. You can either grant appropriate repository access to an individual reviewer, or publish a separately reviewed and sanitized portfolio copy once it is safe.
