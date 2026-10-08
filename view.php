<?php
// One publication in full, every field in the workbook's order, and the
// place to delete it from.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_require();
$p = pub_find((string) ($_GET['id'] ?? ''));
if (!$p || !pub_can_edit($p, $u)) {
    http_response_code(404);
    exit('Not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && csrf_ok()) {
    pub_delete($p['id']);
    flash('ok', 'Deleted "' . pub_title($p) . '".');
    redirect($p['owner'] === $u['email'] ? 'mine.php' : 'list.php');
}

$t = pub_type($p['type']);
$mineView = $p['owner'] === $u['email'];
$crumb = $mineView ? '<a href="mine.php">My publications</a>' : '<a href="list.php">All publications</a>';
page_head($t['name'], $mineView ? 'mine' : 'all', $crumb . ' / ' . e($p['ay']),
    '<a class="btn" href="add.php?id=' . e($p['id']) . '">' . icon('edit') . 'Edit</a>');
echo flash();
?>
<section class="card form-card">
  <div class="view-head">
    <?= type_badge($p['type']) ?>
    <div><h2><?= e(pub_title($p)) ?></h2><p class="muted"><?= e(pub_source($p)) ?><?= pub_date_label($p) !== '' ? ' · ' . e(pub_date_label($p)) : '' ?></p></div>
  </div>
  <dl class="dl">
    <dt>Faculty</dt><dd><?= e($p['owner_name'] ?? '') ?><?= ($p['owner_erp'] ?? '') !== '' ? ' <span class="muted">(ERP ' . e($p['owner_erp']) . ')</span>' : '' ?></dd>
    <dt><?= unit_kind($p['unit']) === 'partner' ? 'Knowledge partner' : 'School' ?></dt><dd><?= e(unit_name($p['unit'])) ?><?= $p['dept'] !== '' ? ', ' . e($p['dept']) : '' ?></dd>
    <dt>Academic year</dt><dd><?= e($p['ay']) ?></dd>
    <?php foreach ($t['fields'] as $key => $f):
        [$label, $input] = field_spec($f);
        $v = format_field_value($p['f'][$key] ?? '', $input); ?>
    <dt><?= e($label) ?></dt><dd><?php
        if ($v === '') echo '<span class="muted">Not given</span>';
        elseif ($input === 'url') echo '<a href="' . e(preg_match('#^10\.#', $v) ? 'https://doi.org/' . $v : $v) . '" target="_blank" rel="noopener">' . e($v) . '</a>';
        else echo nl2br(e($v)); ?></dd>
    <?php endforeach ?>
    <dt>Proof document</dt><dd><?= ($p['proof_file'] ?? '') !== '' ? '<a href="proof.php?id=' . e($p['id']) . '" target="_blank">' . icon('file') . ' Open file</a>' : '<span class="chip chip-miss">Missing</span>' ?></dd>
    <dt>Proof link</dt><dd><?= ($p['proof_link'] ?? '') !== '' ? '<a href="' . e($p['proof_link']) . '" target="_blank" rel="noopener">' . e($p['proof_link']) . '</a>' : '<span class="chip chip-miss">Missing</span>' ?></dd>
    <dt>Added</dt><dd class="muted"><?= e($p['created']) ?><?= $p['updated'] !== $p['created'] ? ', last changed ' . e($p['updated']) : '' ?></dd>
  </dl>
  <form method="post" class="danger-zone no-print" onsubmit="return confirm('Delete this publication? This cannot be undone.')">
    <?= csrf_field() ?><input type="hidden" name="action" value="delete">
    <button class="btn danger" type="submit"><?= icon('trash') ?>Delete</button>
  </form>
</section>
<?php page_foot();
