<?php
// Every publication, filtered by year, school or partner, type and a search.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_require(true);

$f = [
    'ay'   => (string) ($_GET['ay'] ?? ''),
    'kind' => in_array($_GET['kind'] ?? '', ['adypu', 'partner'], true) ? $_GET['kind'] : '',
    'unit' => unit_exists((string) ($_GET['unit'] ?? '')) ? (string) $_GET['unit'] : '',
    'type' => pub_type((string) ($_GET['type'] ?? '')) ? (string) $_GET['type'] : '',
    'q'    => trim((string) ($_GET['q'] ?? '')),
];
if ($f['ay'] !== '' && !is_academic_year($f['ay'])) $f['ay'] = '';
$rows = pubs_where($f);

page_head('All publications', 'all', 'Admin', export_menu(http_build_query(array_filter($f))));
echo flash();
$opt = fn($v, $label, $sel) => '<option value="' . e($v) . '"' . ($v === $sel ? ' selected' : '') . '>' . e($label) . '</option>';
?>
<form class="toolbar" method="get">
  <?= year_select('ay', $f['ay'], true) ?>
  <label class="sel"><?= icon('users') ?><select name="unit" onchange="this.form.submit()">
    <?= $opt('', 'All schools and partners', $f['unit']) ?>
    <optgroup label="ADYPU schools"><?php foreach (units_of('adypu') as $id) echo $opt($id, unit_short($id), $f['unit']); ?></optgroup>
    <optgroup label="Knowledge partners"><?php foreach (units_of('partner') as $id) echo $opt($id, unit_short($id), $f['unit']); ?></optgroup>
  </select></label>
  <label class="sel"><?= icon('file') ?><select name="type" onchange="this.form.submit()">
    <?= $opt('', 'All types', $f['type']) ?>
    <?php foreach (pub_types() as $id => $t) echo $opt($id, $t['name'], $f['type']); ?>
  </select></label>
  <label class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search title, journal, DOI, faculty or ERP"></label>
</form>
<div class="section-title"><h2><?= count($rows) ?> publication<?= count($rows) === 1 ? '' : 's' ?></h2><?php if (array_filter($f)): ?><span><a href="list.php">Clear filters</a></span><?php endif ?></div>
<?= pubs_table($rows, true) ?>
<?php page_foot(['js/app.js']);
