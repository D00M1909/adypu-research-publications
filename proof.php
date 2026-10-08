<?php
// Serves a proof file to its owner or an admin. The files sit in data/proofs,
// which the web server never serves directly.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_require();
$p = pub_find((string) ($_GET['id'] ?? ''));
if (!$p || !pub_can_edit($p, $u) || ($p['proof_file'] ?? '') === '' || !is_file($path = proof_path($p['proof_file']))) {
    http_response_code(404);
    exit('Not found.');
}
$ext = pathinfo($path, PATHINFO_EXTENSION);
$mime = array_search($ext, PROOF_TYPES, true) ?: 'application/octet-stream';
$name = preg_replace('/[^A-Za-z0-9 _-]+/', '', mb_substr(pub_title($p), 0, 60)) ?: 'proof';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $name . '.' . $ext . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
