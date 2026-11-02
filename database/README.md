# ShareBite database schema

`schema.sql` is a **structure-only SQL export** from the owner's local `sharebite_test` database on **November 2, 2026** (MySQL **8.4.7**). It contains the definitions of **16 tables**, indexes, and the foreign keys that existed in that database. No real user records, email addresses, passwords, password hashes, messages, donation rows or seed accounts are included.

The original export was checked for row-data statements before any content was committed. The committed file omits all `DROP TABLE` statements, the local database name, phpMyAdmin server information, transaction boilerplate and row data.

## Fresh installation (empty database ONLY)

1. Install MySQL 8 or a compatible database server and PHP with PDO MySQL. The source schema includes MySQL 8's `utf8mb4_0900_ai_ci` collation, which might **not** import unchanged on MariaDB or older MySQL installations.
2. In phpMyAdmin, create a **new, empty** database, for example `sharebite_demo`, with `utf8mb4` encoding. Never select the original `sharebite` or `sharebite_test` database as the import destination.
3. Open that newly created database, choose **Import**, select `database/schema.sql`, and run the import.
4. Set the local `DB_NAME` and other database environment variables required by `db_connection.php`; do **not** commit credentials. The default local database name is `sharebite`, so configure this explicitly when using a new name.
5. Start the PHP application and register test users. The SQL does not include any user accounts. Admin setup needs a separate, trusted local bootstrap procedure.
6. Confirm the full flow with test data before claiming that a new installation is operational.

**Do not import this file on an existing populated database.** It uses `CREATE TABLE IF NOT EXISTS` and foreign-key `ALTER TABLE` statements intended for a fresh install; it is **not** an upgrade or migration script and cannot safely reconcile existing tables.

### Tables included

| Area | Tables |
| --- | --- |
| Accounts and authentication | `users`, `password_resets` |
| Food donations and logistics | `donations`, `reservations`, `action_logs`, `charity_centers` |
| Community interactions | `review`, `saved_offers`, `suggestions`, `problem_reports` |
| Messaging and notifications | `contact_conversations`, `contact_messages`, `notification` |
| Historical/legacy model | `claimrequest`, `foodoffer`, `restaurants` |

Legacy tables are included for fidelity to the source database, not because every one is used by the current app. Some exported tables use **MyISAM**, which does not enforce foreign keys. Only the foreign-key constraints present in the exported database were retained; do not assume all relationships have database-level enforcement.

## Problem reporting compatibility

The exported legacy `problem_reports` table contains `ReportId`, `UserId`, `OfferId`, `Message`, and `CreatedAt`. The original reporting code attempted to insert different columns. This was corrected in **PR #5**: `report_problem.php` now stores the selected reason and optional details together in the existing `Message` field. The owner confirmed a successful report submission on `ShareBite_Test` after that fix. Other reporting, authorization, reset-email and Admin scenarios still need separate verification.

**Fresh-install user test completed on 2026-11-02:** The owner imported this structure into a new empty MySQL 8 database (`sharebite_fresh_test`), opened the separate `ShareBite_Fresh` application, registered and logged in with Volunteer, Donor, and Charity accounts, created a food offer, requested and approved it, and confirmed charity receipt with `Completed`/`Received` states. This verifies the **basic new-install journey**, not every feature or complete production readiness. Earlier, the owner also tested donor rejection on the existing `sharebite_test` database.

## Other files

- `map_upgrade.sql` in the repository root is an old, partial migration for adding coordinates. **Do not apply it** after importing `database/schema.sql`, because the current schema already includes latitude and longitude.
- `tools/export_schema.php` provides a command-line-only alternative for exporting structure from a local DB. Use it with the appropriate `DB_NAME` and review its output before commit.
- `php tests/schema_sql_test.php` checks that the checked-in SQL is structure-only and contains the expected tables. This is **not** a MySQL import test.

## Privacy and publication

Keep all full database dumps, user contact details, password hashes and email credentials out of GitHub. A source-level schema can generally be shared, but the entire repository still needs a separate privacy, dependency, and licensing review before visibility is changed to Public.
