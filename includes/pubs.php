<?php
// Publications: reading, saving, validating, and the counts every page draws.
// All of them live in data/pubs.php as id => record, behind store.php's guard,
// and each proof file in data/proofs/ beside it, never served directly.
//
// A record:
//   id, owner (email), unit, dept, ay ("2025-26"), type (journal, conf, ...),
//   f (field key => value, per types.php), proof_file, proof_link,
//   created, updated
//
// The owner's unit and department are copied onto the record when it is saved,
// so a school's totals stay put if a faculty member later moves.

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/types.php';
require_once __DIR__ . '/units.php';

// --- Academic years ---------------------------------------------------------

// June to May, as the university runs it: June 2025 to May 2026 is "2025-26".
function academic_year(int $year, int $month): string {
    $start = $month >= 6 ? $year : $year - 1;
    return sprintf('%d-%02d', $start, ($start + 1) % 100);
}

function current_academic_year(): string {
    return academic_year((int) date('Y'), (int) date('n'));
}

// Newest first, from the current year back to the oldest one a faculty member
// is likely to be filling in. Ten years covers a NAAC cycle twice over.
function academic_years(int $count = 10): array {
    $start = (int) substr(current_academic_year(), 0, 4);
    $out = [];
    for ($i = 0; $i < $count; $i++) $out[] = academic_year($start - $i, 6);
    return $out;
}

function is_academic_year(string $ay): bool {
    return in_array($ay, academic_years(), true);
}

// Whether a month ("2026-03") or date ("2026-03-14") falls inside an academic
// year. The add form warns rather than refuses when it does not: a patent filed
// in one year and published in the next is normal, and the faculty member knows
// which year it belongs to better than a rule does.
function in_academic_year(string $value, string $ay): bool {
    if (!preg_match('/^(\d{4})-(\d{2})/', $value, $m)) return true;
    return academic_year((int) $m[1], (int) $m[2]) === $ay;
}

// --- Reading ----------------------------------------------------------------

function pubs_all(): array {
    return store_read('pubs');
}

function pub_find(string $id): ?array {
    $p = pubs_all()[$id] ?? null;
    return is_array($p) ? $p : null;
}

// Filters: ay (one year or ''), years (a list), kind (adypu / partner), unit,
// dept, type, owner, q (free text over title, source and DOI). Newest first.
function pubs_where(array $w): array {
    $q = mb_strtolower(trim((string) ($w['q'] ?? '')));
    $out = [];
    foreach (pubs_all() as $p) {
        if (!empty($w['ay']) && $p['ay'] !== $w['ay']) continue;
        if (!empty($w['years']) && !in_array($p['ay'], $w['years'], true)) continue;
        if (!empty($w['kind']) && unit_kind($p['unit']) !== $w['kind']) continue;
        if (!empty($w['unit']) && $p['unit'] !== $w['unit']) continue;
        if (!empty($w['dept']) && $p['dept'] !== $w['dept']) continue;
        if (!empty($w['type']) && $p['type'] !== $w['type']) continue;
        if (!empty($w['owner']) && $p['owner'] !== $w['owner']) continue;
        if ($q !== '' && !str_contains(mb_strtolower(implode(' ', [pub_title($p), pub_source($p), $p['f']['doi'] ?? '', $p['owner_name'] ?? '', $p['owner_erp'] ?? ''])), $q)) continue;
        $out[] = $p;
    }
    usort($out, fn($a, $b) => [pub_sort_date($b), $b['created']] <=> [pub_sort_date($a), $a['created']]);
    return $out;
}

function pub_title(array $p): string {
    $t = pub_type($p['type']);
    return (string) ($p['f'][$t['title']] ?? '');
}

// The second line under a title: the journal, conference, publisher or office.
function pub_source(array $p): string {
    $t = pub_type($p['type']);
    return (string) ($p['f'][$t['source']] ?? '');
}

// "03/2026" for a month, "14/03/2026" for a date: how the workbook writes them.
function pub_date_label(array $p): string {
    $t = pub_type($p['type']);
    return format_field_value((string) ($p['f'][$t['date']] ?? ''), $t['fields'][$t['date']][1]);
}

function pub_sort_date(array $p): string {
    $t = pub_type($p['type']);
    return (string) ($p['f'][$t['date']] ?? '');
}

function pub_indexing(array $p): string {
    return (string) ($p['f']['indexing'] ?? '');
}

function pub_has_proof(array $p): bool {
    return ($p['proof_file'] ?? '') !== '' && ($p['proof_link'] ?? '') !== '';
}

// A stored value as a person reads it.
function format_field_value($value, string $input): string {
    if (is_array($value)) return implode('; ', $value);
    $value = (string) $value;
    if ($value === '') return '';
    return match ($input) {
        'month' => preg_match('/^(\d{4})-(\d{2})$/', $value, $m) ? "$m[2]/$m[1]" : $value,
        'date' => preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) ? "$m[3]/$m[2]/$m[1]" : $value,
        'daterange' => implode(' to ', array_map(fn($d) => format_field_value($d, 'date'), explode('|', $value))),
        default => $value,
    };
}

// --- Counting ---------------------------------------------------------------

// [unit][type] => count over a set of publications, every unit and type
// present even at zero, so a chart never has to guess at a missing key.
function count_by_unit_type(array $pubs, array $units): array {
    $out = [];
    foreach ($units as $u) $out[$u] = array_fill_keys(type_ids(), 0);
    foreach ($pubs as $p) {
        if (isset($out[$p['unit']][$p['type']])) $out[$p['unit']][$p['type']]++;
    }
    return $out;
}

function count_by_type(array $pubs): array {
    $out = array_fill_keys(type_ids(), 0);
    foreach ($pubs as $p) if (isset($out[$p['type']])) $out[$p['type']]++;
    return $out;
}

// [ay][unit] => count, for the school-by-year table and the pie's year ticks.
function count_by_year_unit(array $pubs, array $years, array $units): array {
    $out = [];
    foreach ($years as $y) $out[$y] = array_fill_keys($units, 0);
    foreach ($pubs as $p) {
        if (isset($out[$p['ay']][$p['unit']])) $out[$p['ay']][$p['unit']]++;
    }
    return $out;
}

// [dept][type] => count, departments in the unit's own order, then any typed
// in as "Other" in the order they first appear.
function count_by_dept_type(array $pubs, string $unit): array {
    $out = [];
    foreach (unit_depts($unit) as $d) $out[$d] = array_fill_keys(type_ids(), 0);
    foreach ($pubs as $p) {
        $d = $p['dept'] !== '' ? $p['dept'] : 'Not given';
        $out[$d] ??= array_fill_keys(type_ids(), 0);
        if (isset($out[$d][$p['type']])) $out[$d][$p['type']]++;
    }
    return $out;
}

// The academic years worth showing as columns: every year that has a record,
// plus the current one, newest last, at most $max of them.
function years_with_data(array $pubs, int $max = 5): array {
    $ys = [current_academic_year() => true];
    foreach ($pubs as $p) $ys[$p['ay']] = true;
    $ys = array_keys($ys);
    sort($ys);
    return array_slice($ys, -$max);
}

// --- Saving -----------------------------------------------------------------

// Reads one type's fields out of a POST, trimmed, in the shape a record holds.
// Returns [values, errors]; errors is field key => message.
function pub_read_fields(string $type, array $post): array {
    $t = pub_type($type);
    $values = [];
    $errors = [];
    foreach ($t['fields'] as $key => $f) {
        [$label, $input, , $required, $options] = field_spec($f);
        $name = "f_{$type}_{$key}";
        if ($input === 'multi') {
            $v = array_values(array_intersect((array) ($post[$name] ?? []), $options));
            $empty = $v === [];
        } elseif ($input === 'daterange') {
            $from = trim((string) ($post[$name . '_from'] ?? ''));
            $to = trim((string) ($post[$name . '_to'] ?? '')) ?: $from;
            $v = $from === '' ? '' : "$from|$to";
            $empty = $v === '';
            if (!$empty && (!valid_date($from) || !valid_date($to) || $to < $from)) $errors[$key] = 'Check the dates.';
        } else {
            $v = trim((string) ($post[$name] ?? ''));
            $empty = $v === '';
            if (!$empty && $input === 'select' && !in_array($v, $options, true)) $errors[$key] = 'Choose one of the options.';
            if (!$empty && $input === 'month' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $v)) $errors[$key] = 'Choose a month and year.';
            if (!$empty && $input === 'date' && !valid_date($v)) $errors[$key] = 'Choose a date.';
            if (!$empty && $input === 'url' && !preg_match('#^(https?://|10\.)#i', $v)) $errors[$key] = 'Paste the full link, starting with https://, or a DOI starting with 10.';
        }
        if ($empty && $required) $errors[$key] = 'Required.';
        $values[$key] = $v;
    }
    return [$values, $errors];
}

function valid_date(string $d): bool {
    return (bool) preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

function pub_new_id(): string {
    return date('ymd') . '-' . bin2hex(random_bytes(4));
}

// Creates or replaces one record. $record must already be validated.
function pub_save(array $record): void {
    store_update('pubs', function (array $d) use ($record) {
        $d[$record['id']] = $record;
        return $d;
    });
}

function pub_delete(string $id): void {
    $p = pub_find($id);
    store_update('pubs', function (array $d) use ($id) {
        unset($d[$id]);
        return $d;
    });
    if ($p && ($p['proof_file'] ?? '') !== '') @unlink(proof_path($p['proof_file']));
}

// Who may open, edit or delete a record: its owner, or any admin.
function pub_can_edit(array $p, array $user): bool {
    return !empty($user['admin']) || $p['owner'] === $user['email'];
}

// --- Proof files ------------------------------------------------------------

function proof_dir(): string {
    return STORE_DIR . '/proofs';
}

function proof_path(string $name): string {
    // Names are ours (id + extension), but check the shape before touching disk.
    if (!preg_match('/^[0-9a-f-]+\.(pdf|jpg|png)$/', $name)) throw new InvalidArgumentException('bad proof name');
    return proof_dir() . '/' . $name;
}

// Moves an uploaded file into place for record $id. Returns [filename, error];
// filename '' with error '' means nothing was uploaded.
function proof_store(array $file, string $id): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['', ''];
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > PROOF_MAX_BYTES) {
        return ['', 'The proof file is over ' . (PROOF_MAX_BYTES >> 20) . ' MB. Try a smaller scan or just the first page.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) return ['', 'The proof file did not upload. Please try again.'];
    // The browser's claimed type is not trusted; the file's own bytes are.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = PROOF_TYPES[$mime] ?? null;
    if ($ext === null) return ['', 'The proof must be a PDF, JPG or PNG.'];
    if (!is_dir(proof_dir())) @mkdir(proof_dir(), 0775, true);
    $name = "$id.$ext";
    $dest = proof_path($name);
    $ok = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $dest) : rename($file['tmp_name'], $dest);
    return $ok ? [$name, ''] : ['', 'The proof file could not be saved. Please try again.'];
}
