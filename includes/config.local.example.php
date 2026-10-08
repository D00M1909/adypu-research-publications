<?php
// Copy to config.local.php (gitignored) on each server.

// The first admin. No account is ever committed to git, so this pair is how the
// very first person gets in; everyone else signs up at login.php. Generate the
// hash with:
//   php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
define('ADMIN_EMAIL', 'you@example.com');
define('ADMIN_HASH', '$2y$10$replace-this-with-the-output-above');
