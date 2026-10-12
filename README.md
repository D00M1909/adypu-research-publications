# ADYPU Research Publications

Faculty from ADYPU schools and knowledge partners log their research output.
Admins see the totals by school and academic year, and export them.

![The admin overview](docs/screenshots/overview.png)

*Sample data.*

## What it does

- Journal papers, conference papers, books, book chapters, patents and copyrights,
  with the fields from the IQAC research data format
- A proof file or link on every entry
- Overview by school, partner, department and academic year
- Export to Excel, CSV and PDF
- Faculty sign up with a join code, or wait for an admin to approve them
- Light and dark themes

![Adding a journal paper](docs/screenshots/add-journal.png)

![Knowledge partners in dark mode](docs/screenshots/partners-dark.png)

## Running it

PHP 8.1+ with `fileinfo` and `zip`. No framework, no database.

```
cp includes/config.local.example.php includes/config.local.php
php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
```

Put your email and that hash in `includes/config.local.php`, then:

```
php -S localhost:8000
php tools/seed-demo.php    # optional demo data, password demo1234
php tests/run.php
```

## Deploying

```
tools/stage-upload.sh          # changed since the last commit
tools/stage-upload.sh --all    # everything, for a fresh install
```

Drop the `upload/` folder on the server's htdocs, then create
`includes/config.local.php` there. `data/` is never uploaded.
