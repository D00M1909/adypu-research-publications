<?php
// A faculty member's own publications: the year first, then the type, then a
// search, then the table.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_require();
if (($u['unit'] ?? '') === '') redirect('admin.php');

$ay = (string) ($_GET['ay'] ?? '');
if ($ay !== '' && !is_academic_year($ay)) $ay = '';
$type = (string) ($_GET['type'] ?? '');
if ($type !== '' && !pub_type($type)) $type = '';
$q = trim((string) ($_GET['q'] ?? ''));

$inYear = pubs_where(['owner' => $u['email'], 'ay' => $ay]);
$rows = pubs_where(['owner' => $u['email'], 'ay' => $ay, 'type' => $type, 'q' => $q]);
$byType = count_by_type($inYear);
$missing = count(array_filter($inYear, fn($p) => !pub_has_proof($p)));

page_head('My publications', 'mine', e(unit_name($u['unit'])) . ($u['dept'] !== '' ? ' · ' . e($u['dept']) : ''),
    '<a class="btn primary" href="add.php' . ($ay !== '' ? '?ay=' . $ay : '') . '">' . icon('plus') . 'Add publication</a>');
echo flash();
?>
<form class="toolbar" method="get">
  <?= year_select('ay', $ay, true) ?>
  <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif ?>
  <label class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title, journal or DOI"></label>
</form>

<?= totals_card($ay !== '' ? 'Academic year ' . $ay : 'All years', array_sum($byType), $missing ? $missing . ' missing proof' : 'All proofs in', $byType) ?>

<div class="seg seg-wrap">
  <a href="<?= e(url_with(['type' => null])) ?>"<?= $type === '' ? ' class="on"' : '' ?>>All <b><?= array_sum($byType) ?></b></a>
  <?php foreach (pub_types() as $id => $t): ?>
  <a href="<?= e(url_with(['type' => $id])) ?>"<?= $type === $id ? ' class="on"' : '' ?>><?= e($t['short']) ?> <b><?= $byType[$id] ?></b></a>
  <?php endforeach ?>
</div>

<?php if (!$rows): ?>
<div class="card empty-state">
  <?php if ($inYear || $q !== ''): ?>
    <b>Nothing matches</b>Try another type or clear the search.
  <?php else: ?>
    <b>No publications <?= $ay !== '' ? 'in ' . e($ay) : 'yet' ?></b>Add your first one and it will show up here.
    <p><a class="btn primary" href="add.php<?= $ay !== '' ? '?ay=' . $ay : '' ?>"><?= icon('plus') ?>Add publication</a></p>
  <?php endif ?>
</div>
<?php else: ?>
<div class="card table-card">
<table>
  <thead><tr><th></th><th>Title</th><th>Year</th><th>Published</th><th>Indexing</th><th>Proof</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $p): ?>
    <tr>
      <td><?= type_badge($p['type']) ?></td>
      <td><div class="t-title"><a href="view.php?id=<?= e($p['id']) ?>"><?= e(pub_title($p)) ?></a></div><div class="t-sub"><?= e(pub_source($p)) ?></div></td>
      <td class="mono"><?= e($p['ay']) ?></td>
      <td class="mono"><?= e(pub_date_label($p)) ?></td>
      <td><?= e(pub_indexing($p)) ?></td>
      <td><?= proof_badges($p) ?></td>
      <td><div class="acts">
        <a href="view.php?id=<?= e($p['id']) ?>" title="View"><?= icon('eye') ?></a>
        <a href="add.php?id=<?= e($p['id']) ?>" title="Edit"><?= icon('edit') ?></a>
      </div></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>
<?php page_foot(['js/app.js']);
