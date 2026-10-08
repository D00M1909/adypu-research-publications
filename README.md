# ADYPU Research Publications

Faculty from ADYPU schools and knowledge partners log their journal papers, conference papers, books, book chapters, patents and copyrights. Admins see the totals by school and academic year, open any school down to its departments, and export everything to Excel, CSV or PDF.

The fields for each type match the IQAC "Format For Research Data Collection" workbook, plus a proof file and link for every entry.

Plain PHP 8, no framework, no database. Data is stored as JSON in `data/`, which is created on first use and never committed.

## Running it locally

```
cp includes/config.local.example.php includes/config.local.php
php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
```

Put your email and that hash into `includes/config.local.php`, then:

```
php -S localhost:8000
```

Open http://localhost:8000 and sign in with that email and password. That is the first admin account.

To fill the site with made-up faculty and publications for testing:

```
php tools/seed-demo.php
```

Every demo account's password is `demo1234`. Delete the `data/` folder to start clean.

Checks: `php tests/run.php`

## How sign-up works

Faculty create their own account with their name, ERP number, school or partner, department and designation. Each school and partner can have a join code (Faculty accounts page). Anyone who signs up with the right code gets in straight away; anyone without one waits for an admin to approve them.

Publications do not need approval.

## Academic years

June to May, written as 2025-26. A faculty member picks the year first when adding a publication.

## Hosting

Upload everything except `data/`, `tests/` and `tools/`, then create `includes/config.local.php` on the server. The `.htaccess` keeps `data/` and `includes/` from being opened in a browser.
