<?php
// Quick checks for the rules that are easy to get wrong. Run: php tests/run.php
// Uses a throwaway data folder, never the real one.
$tmp = sys_get_temp_dir() . '/rp-test-' . getmypid();
@mkdir($tmp);
define('STORE_DIR', $tmp);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pubs.php';

$fails = 0;
function check(string $what, $got, $want): void {
    global $fails;
    if ($got === $want) { echo "  ok  $what\n"; return; }
    $fails++;
    echo "FAIL  $what\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
}

echo "Academic years\n";
check('June starts a year', academic_year(2025, 6), '2025-26');
check('May ends it', academic_year(2026, 5), '2025-26');
check('1999 to 2000', academic_year(1999, 7), '1999-00');
check('month inside', in_academic_year('2026-03', '2025-26'), true);
check('date outside', in_academic_year('2026-06-01', '2025-26'), false);
check('ten years offered', count(academic_years()), 10);

echo "Fields match the workbook (plus proof)\n";
$want = ['journal' => 14, 'conf' => 19, 'book' => 18, 'chapter' => 14, 'patent' => 18, 'copyright' => 14];
foreach ($want as $t => $n) check("$t has $n", count(pub_type($t)['fields']) + 1, $n);

echo "Reading a form\n";
[$v, $err] = pub_read_fields('journal', []);
check('empty journal lists required fields', array_keys($err), ['title', 'journal', 'issn', 'publisher', 'pages', 'issue', 'volume', 'published', 'indexing', 'doi', 'category']);
[$v, $err] = pub_read_fields('conf', ['f_conf_dates_from' => '2026-01-10', 'f_conf_dates_to' => '2026-01-09']);
check('dates backwards refused', $err['dates'] ?? '', 'Check the dates.');
[$v, $err] = pub_read_fields('conf', ['f_conf_dates_from' => '2026-01-10']);
check('one date is a one-day range', $v['dates'], '2026-01-10|2026-01-10');
[$v, $err] = pub_read_fields('journal', ['f_journal_indexing' => 'Made up', 'f_journal_doi' => '10.1000/xyz', 'f_journal_sdg' => ['SDG 4 – Quality Education', 'nonsense']]);
check('select outside options refused', isset($err['indexing']), true);
check('bare DOI accepted', isset($err['doi']), false);
check('multi keeps only real options', $v['sdg'], ['SDG 4 – Quality Education']);
check('month shown as MM/YYYY', format_field_value('2026-03', 'month'), '03/2026');

echo "Sign-up\n";
check('ADYPU needs ERP', auth_signup(['name' => 'A', 'unit' => 'eng', 'dept' => 'Robotics', 'designation' => 'Professor', 'email' => 'a@x.in', 'password' => 'longenough']), 'Please enter your ERP number.');
check('partner without ERP is fine', auth_signup(['name' => 'B', 'unit' => 'kp-aero', 'dept' => 'Avionics', 'designation' => 'Professor', 'email' => 'b@x.in', 'password' => 'longenough']), '');
check('no code means pending', auth_find('b@x.in')['status'], 'pending');
store_update('faculty', function ($d) { $d['codes']['eng'] = 'ABCD-EFGH'; return $d; });
check('right code', auth_signup(['name' => 'C', 'erp' => 'e1', 'unit' => 'eng', 'dept' => 'Robotics', 'designation' => 'Professor', 'email' => 'C@X.in', 'password' => 'longenough', 'code' => 'abcd efgh']), '');
check('right code means active', auth_find('c@x.in')['status'], 'active');
check('ERP stored uppercase', auth_find('c@x.in')['erp'], 'E1');
check('same ERP refused', auth_signup(['name' => 'D', 'erp' => 'E1', 'unit' => 'eng', 'dept' => 'Robotics', 'designation' => 'Professor', 'email' => 'd@x.in', 'password' => 'longenough']), 'That ERP number already has an account. Try signing in, or ask an admin.');

echo "Counting\n";
$pubs = [['unit' => 'eng', 'type' => 'journal', 'ay' => '2025-26', 'dept' => 'Robotics'], ['unit' => 'eng', 'type' => 'patent', 'ay' => '2024-25', 'dept' => 'Robotics'], ['unit' => 'mgmt', 'type' => 'journal', 'ay' => '2025-26', 'dept' => '']];
check('by type', count_by_type($pubs)['journal'], 2);
check('by unit', array_sum(count_by_unit_type($pubs, ['eng', 'mgmt'])['eng']), 2);
check('by year and unit', count_by_year_unit($pubs, ['2024-25', '2025-26'], ['eng', 'mgmt'])['2025-26'], ['eng' => 1, 'mgmt' => 1]);
check('no department lands somewhere', array_sum(count_by_dept_type($pubs, 'mgmt')['Not given']), 1);

array_map('unlink', glob("$tmp/*"));
@rmdir($tmp);
echo $fails ? "\n$fails failed\n" : "\nAll passed\n";
exit($fails ? 1 : 0);
