<?php
// Downloads of whatever the admin is looking at: Excel with a summary tab and
// one tab per type carrying every field from the workbook, CSV, or a page
// laid out for printing to PDF.
require_once __DIR__ . '/includes/page.php';
require_once __DIR__ . '/includes/xlsx.php';

auth_boot();
$u = auth_require(true);

$f = [
    'ay'   => is_academic_year((string) ($_GET['ay'] ?? '')) ? (string) $_GET['ay'] : '',
    'kind' => in_array($_GET['kind'] ?? '', ['adypu', 'partner'], true) ? $_GET['kind'] : '',
    'unit' => unit_exists((string) ($_GET['unit'] ?? '')) ? (string) $_GET['unit'] : '',
    'dept' => trim((string) ($_GET['dept'] ?? '')),
    'type' => pub_type((string) ($_GET['type'] ?? '')) ? (string) $_GET['type'] : '',
    'q'    => trim((string) ($_GET['q'] ?? '')),
];
$rows = pubs_where($f);
$format = (string) ($_GET['format'] ?? 'xlsx');

// What the file is of, for its name and its heading.
$scope = $f['unit'] !== '' ? unit_short($f['unit']) : ($f['kind'] === 'partner' ? 'Knowledge Partners' : ($f['kind'] === 'adypu' ? 'ADYPU' : 'All'));
if ($f['dept'] !== '') $scope .= ' ' . $f['dept'];
if ($f['type'] !== '') $scope .= ' ' . pub_type($f['type'])['name'];
$yearLabel = $f['ay'] !== '' ? $f['ay'] : 'All years';
$filename = preg_replace('/[^A-Za-z0-9-]+/', '-', "Research Publications $scope $yearLabel");

$personCols = ['Academic Year', 'Faculty Name', 'ERP Number', 'Designation', 'School / Partner', 'Department'];
$person = fn($p) => [$p['ay'], $p['owner_name'] ?? '', $p['owner_erp'] ?? '', $p['designation'] ?? '', unit_name($p['unit']), $p['dept']];
$fieldsOf = function (array $p): array {
    $out = [];
    foreach (pub_type($p['type'])['fields'] as $key => $fs) $out[] = format_field_value($p['f'][$key] ?? '', field_spec($fs)[1]);
    return $out;
};
$proof = fn($p) => [($p['proof_file'] ?? '') !== '' ? 'Uploaded' : 'Missing', $p['proof_link'] ?? ''];
$labels = fn($type) => array_map(fn($fs) => $fs[0], array_values(pub_type($type)['fields']));
$commonCols = ['Type', 'Title', 'Journal / Conference / Publisher', 'Date', 'Indexing', 'DOI / URL'];
$common = fn($p) => [pub_type($p['type'])['name'], pub_title($p), pub_source($p), pub_date_label($p), pub_indexing($p), (string) ($p['f']['doi'] ?? '')];

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // so Excel reads the dashes and accents right
    if ($f['type'] !== '') {
        fputcsv($out, [...$personCols, ...$labels($f['type']), 'Proof File', 'Proof Link']);
        foreach ($rows as $p) fputcsv($out, [...$person($p), ...$fieldsOf($p), ...$proof($p)]);
    } else {
        fputcsv($out, [...$personCols, ...$commonCols, 'Proof File', 'Proof Link']);
        foreach ($rows as $p) fputcsv($out, [...$person($p), ...$common($p), ...$proof($p)]);
    }
    exit;
}

// Units that belong in the summary: the one asked for, or every unit on the
// side asked for, or all of them.
$units = $f['unit'] !== '' ? [$f['unit']] : ($f['kind'] !== '' ? units_of($f['kind']) : [...units_of('adypu'), ...units_of('partner')]);
$byUnit = count_by_unit_type($rows, $units);
$types = pub_types();

if ($format === 'xlsx') {
    $head = fn(array $cols) => array_map(fn($c) => ['b', $c], $cols);
    $summary = [[['b', 'Research Publications: ' . $scope]], ['Academic year: ' . $yearLabel], ['Exported ' . date('d/m/Y H:i')], [],
        $head(['School / Partner', ...array_map(fn($t) => $t['name'], array_values($types)), 'Total'])];
    foreach ($byUnit as $id => $c) $summary[] = [unit_name($id), ...array_map('strval', array_values($c)), (string) array_sum($c)];
    $tot = count_by_type($rows);
    $summary[] = $head(['Total', ...array_map('strval', array_values($tot)), (string) array_sum($tot)]);
    $sheets = ['Summary' => ['cols' => [34, 14, 16, 10, 14, 10, 12, 10], 'rows' => $summary]];
    foreach ($types as $id => $t) {
        if ($f['type'] !== '' && $f['type'] !== $id) continue;
        $sheetRows = [$head([...$personCols, ...$labels($id), 'Proof File', 'Proof Link'])];
        foreach ($rows as $p) if ($p['type'] === $id) $sheetRows[] = [...$person($p), ...$fieldsOf($p), ...$proof($p)];
        $sheets[$t['name']] = ['cols' => [12, 24, 12, 18, 28, 26, ...array_fill(0, count($t['fields']), 24), 12, 36], 'rows' => $sheetRows];
    }
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    write_xlsx($tmp, $sheets);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

// PDF: a plain page the browser prints, which every phone and PC can save as PDF.
html_head('Export', false);
?>
<body class="print-page" onload="window.print()">
<div class="print-head" style="display:block">
  <h1>Research Publications: <?= e($scope) ?></h1>
  <p>Academic year <?= e($yearLabel) ?> · <?= count($rows) ?> publications · Exported <?= date('d/m/Y') ?></p>
</div>
<p class="no-print"><button class="btn primary" onclick="window.print()">Print or save as PDF</button></p>
<table class="print-table">
  <thead><tr><th>School / Partner</th><?php foreach ($types as $t): ?><th class="c"><?= e($t['short']) ?></th><?php endforeach ?><th class="c">Total</th></tr></thead>
  <tbody><?php foreach ($byUnit as $id => $c): ?><tr><td><?= e(unit_name($id)) ?></td><?php foreach ($c as $n): ?><td class="c"><?= $n ?></td><?php endforeach ?><td class="c"><b><?= array_sum($c) ?></b></td></tr><?php endforeach ?></tbody>
</table>
<?php foreach ($types as $id => $t):
    $list = array_filter($rows, fn($p) => $p['type'] === $id);
    if (!$list) continue; ?>
<h2 class="print-h2"><?= e($t['name']) ?> (<?= count($list) ?>)</h2>
<table class="print-table">
  <thead><tr><th>Title</th><th>Source</th><th>Faculty</th><th>School / Partner</th><th>Year</th><th>Date</th><th>Indexing</th><th>Proof</th></tr></thead>
  <tbody><?php foreach ($list as $p): ?><tr>
    <td><?= e(pub_title($p)) ?></td><td><?= e(pub_source($p)) ?></td><td><?= e($p['owner_name'] ?? '') ?></td>
    <td><?= e(unit_short($p['unit'])) ?><?= $p['dept'] !== '' ? ', ' . e($p['dept']) : '' ?></td><td><?= e($p['ay']) ?></td><td><?= e(pub_date_label($p)) ?></td>
    <td><?= e(pub_indexing($p)) ?></td><td><?= pub_has_proof($p) ? 'Complete' : 'Missing' ?></td>
  </tr><?php endforeach ?></tbody>
</table>
<?php endforeach ?>
</body>
</html>
