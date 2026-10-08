<?php
// Settings that differ per server live in config.local.php, which is never
// committed. Copy config.local.example.php to start one.

if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// The first admin account, set only in config.local.php on the server. Once
// that admin changes their password the account moves into data/faculty.php
// and this pair stops working, so it is never a permanent second way in.
defined('ADMIN_EMAIL') || define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: '');
defined('ADMIN_HASH') || define('ADMIN_HASH', getenv('ADMIN_HASH') ?: '');

// Proof uploads. InfinityFree caps a single upload at 10 MB; 5 MB keeps a
// scanned certificate comfortably inside it and the account's disk quota.
const PROOF_MAX_BYTES = 5 * 1024 * 1024;
const PROOF_TYPES = [
    'application/pdf' => 'pdf',
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
];
