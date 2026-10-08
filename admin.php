<?php
// The admin overview: every school's or partner's publications for a year (or
// all of them), split by type, then a pie by school with a tick per year and
// the school-by-year table.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_require(true);

$view = ($_GET['view'] ?? 'adypu') === 'partner' ? 'partner' : 'adypu';
$ay = (string) ($_GET['ay'] ?? '');
if ($ay !== '' && !is_academic_year($ay)) $ay = '';

$units = units_of($view);
$allKind = pubs_where(['kind' => $view]);
$pubs = $ay !== '' ? array_values(array_filter($allKind, fn($p) => $p['ay'] === $ay)) : $allKind;
$byType = count_by_type($pubs);
$byUnit = count_by_unit_type($pubs, $units);
$years = years_with_data($allKind);
$yearUnit = count_by_year_unit($allKind, $years, $units);
$cur = current_academic_year();
$curByType = count_by_type(array_filter($allKind, fn($p) => $p['ay'] === $cur));
$reporting = count(array_filter($byUnit, fn($c) => array_sum($c) > 0));
$word = $view === 'partner' ? 'partner' : 'school';

$exportQ = http_build_query(array_filter(['kind' => $view, 'ay' => $ay]));
$actions = '<form method="get" class="inline">'
    . '<div class="seg"><a href="' . e(url_with(['view' => 'adypu'])) . '"' . ($view === 'adypu' ? ' class="on"' : '') . '>ADYPU</a>'
    . '<a href="' . e(url_with(['view' => 'partner'])) . '"' . ($view === 'partner' ? ' class="on"' : '') . '>Knowledge Partner</a></div>'
    . '<input type="hidden" name="view" value="' . $view . '">' . year_select('ay', $ay, true) . '</form>'
    . export_menu($exportQ);
page_head('Overview', $view === 'partner' ? 'partner' : 'overview', 'Admin', $actions);
echo flash();

echo totals_card(
    $ay !== '' ? 'Publications in ' . $ay : 'All publications',
    array_sum($byType),
    $ay !== '' ? $reporting . ' of ' . count($units) . ' ' . $word . 's reporting' : '+' . array_sum($curByType) . ' in ' . $cur . ' so far',
    $byType,
    $ay === '' ? $curByType : null
);
?>
<div class="section-title"><h2><?= $view === 'partner' ? 'Knowledge partners' : 'Schools' ?></h2><span>Click a <?= $word ?> to open it</span></div>
<div class="grid3">
<?php foreach ($units as $id) echo unit_tile(unit_short($id), unit_icon($id), $byUnit[$id], 'school.php?' . http_build_query(array_filter(['unit' => $id, 'ay' => $ay]))); ?>
</div>

<div class="row2">
  <section class="card panel" id="pie-panel">
    <div class="panel-h col"><h2>Publications by <?= $word ?></h2>
      <div class="ticks">
        <?php foreach ($years as $y): ?><label class="chip"><input type="checkbox" value="<?= $y ?>"<?= $ay === '' || $ay === $y ? ' checked' : '' ?>><?= icon('check') ?><?= $y ?></label><?php endforeach ?>
      </div>
    </div>
    <div class="pie-split">
      <div class="pie-box"></div>
      <div class="legend"></div>
    </div>
    <script type="application/json" class="pie-data"><?= json_encode([
        'units' => array_map(fn($id, $i) => ['name' => unit_short($id), 'color' => UNIT_PALETTE[$i % count(UNIT_PALETTE)]], $units, array_keys($units)),
        'counts' => array_map('array_values', $yearUnit),
    ], JSON_HEX_TAG) ?></script>
    <noscript><?php
        $slices = [];
        foreach ($units as $i => $id) $slices[] = [unit_short($id), array_sum($byUnit[$id]), UNIT_PALETTE[$i % count(UNIT_PALETTE)]];
        echo pie_svg($slices, 220);
    ?></noscript>
  </section>
  <section class="card panel">
    <div class="panel-h"><h2><?= ucfirst($word) ?> by academic year</h2><span class="muted">Darker means more</span></div>
    <div class="table-scroll"><table class="heat">
      <thead><tr><th><?= ucfirst($word) ?></th><?php foreach ($years as $y): ?><th class="c"><?= $y ?></th><?php endforeach ?><th class="c">Total</th></tr></thead>
      <tbody>
      <?php $max = max(1, ...array_values(array_map(fn($r) => max($r ?: [0]), $yearUnit)));
      foreach ($units as $id):
          $row = array_column($yearUnit, $id); ?>
        <tr><td><a href="school.php?unit=<?= $id ?>"><?= e(unit_short($id)) ?></a></td>
        <?php foreach ($row as $v): $a = $v === 0 ? 0.04 : 0.1 + $v / $max * 0.75; ?>
          <td class="c" style="background:rgba(194,27,39,<?= number_format($a, 2) ?>);color:<?= $a > 0.45 ? '#fff' : 'var(--ink)' ?>"><?= $v ?></td>
        <?php endforeach ?>
        <td class="c"><?= array_sum($row) ?></td></tr>
      <?php endforeach ?>
      </tbody>
    </table></div>
  </section>
</div>
<?php page_foot(['js/app.js']);
