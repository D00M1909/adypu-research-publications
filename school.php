<?php
// One school or partner: its totals, a tile per department, and its
// publications underneath.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_require(true);

$unit = (string) ($_GET['unit'] ?? '');
if (!unit_exists($unit)) redirect('admin.php');
$kind = unit_kind($unit);
$ay = (string) ($_GET['ay'] ?? '');
if ($ay !== '' && !is_academic_year($ay)) $ay = '';
$dept = (string) ($_GET['dept'] ?? '');
$type = (string) ($_GET['type'] ?? '');
if ($type !== '' && !pub_type($type)) $type = '';

$pubs = pubs_where(['unit' => $unit, 'ay' => $ay]);
$byType = count_by_type($pubs);
$byDept = count_by_dept_type($pubs, $unit);
$rows = pubs_where(['unit' => $unit, 'ay' => $ay, 'dept' => $dept, 'type' => $type]);
$faculty = count(array_unique(array_column($pubs, 'owner')));

$exportQ = http_build_query(array_filter(['unit' => $unit, 'ay' => $ay, 'dept' => $dept, 'type' => $type]));
$actions = '<form method="get" class="inline"><input type="hidden" name="unit" value="' . e($unit) . '">'
    . year_select('ay', $ay, true) . '</form>' . export_menu($exportQ);
page_head(unit_name($unit), $kind === 'partner' ? 'partner' : 'adypu',
    '<a href="admin.php?' . e(http_build_query(array_filter(['view' => $kind, 'ay' => $ay]))) . '">' . ($kind === 'partner' ? 'Knowledge partners' : 'ADYPU schools') . '</a>', $actions);

echo totals_card($ay !== '' ? 'Publications in ' . $ay : 'All publications', array_sum($byType),
    $faculty . ' faculty member' . ($faculty === 1 ? '' : 's') . ' contributing', $byType);
?>
<div class="section-title"><h2><?= $kind === 'partner' ? 'Programs' : 'Departments' ?></h2><span><?= $dept !== '' ? '<a href="' . e(url_with(['dept' => null])) . '">Show all</a>' : 'Click one to filter the list' ?></span></div>
<?php if (!$byDept): ?>
<div class="card empty-state"><b>No departments listed yet</b>Faculty can still type theirs in when they sign up.</div>
<?php else: ?>
<div class="grid3">
<?php foreach ($byDept as $d => $c) echo unit_tile($d, null, $c, url_with(['dept' => $d === $dept ? null : $d]) . '#list'); ?>
</div>
<?php endif ?>

<div class="section-title" id="list"><h2><?= $dept !== '' ? e($dept) : 'All publications' ?></h2><span><?= count($rows) ?> shown</span></div>
<div class="seg seg-wrap">
  <a href="<?= e(url_with(['type' => null])) ?>#list"<?= $type === '' ? ' class="on"' : '' ?>>All</a>
  <?php foreach (pub_types() as $id => $t): ?><a href="<?= e(url_with(['type' => $id])) ?>#list"<?= $type === $id ? ' class="on"' : '' ?>><?= e($t['short']) ?></a><?php endforeach ?>
</div>
<?= pubs_table($rows, false) ?>
<?php page_foot(['js/app.js']);
