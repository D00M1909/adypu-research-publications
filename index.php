<?php
// The front door: admins to the overview, faculty to their publications.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_user();
if ($u === null) redirect('login.php');
redirect(!empty($u['admin']) ? 'admin.php' : 'mine.php');
