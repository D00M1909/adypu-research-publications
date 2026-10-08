<?php
// The shell every signed-in page shares: the red sidebar, the white top bar and
// the footer. login.php has its own, smaller one.

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/pubs.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/form.php';

function e($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// A link to the current page with some query parameters changed. Null removes one.
function url_with(array $changes, ?string $path = null): string {
    $q = array_merge($_GET, $changes);
    $q = array_filter($q, fn($v) => $v !== null && $v !== '' && $v !== []);
    $path ??= basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    return $path . ($q ? '?' . http_build_query($q) : '');
}

function asset(string $path): string {
    $file = __DIR__ . '/../' . $path;
    return $path . '?v=' . (is_file($file) ? filemtime($file) : 0);
}

// $themed is false for the PDF print page, which always prints light.
function html_head(string $title, bool $themed = true): void {
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($themed): ?>
<script>
// Runs before the page paints so dark mode never flashes light. A saved choice
// wins; otherwise the site follows the system setting, live.
(function () {
  var root = document.documentElement, mq = matchMedia('(prefers-color-scheme: dark)'), saved = null;
  try { saved = localStorage.getItem('theme'); } catch (e) {}
  var set = function (t) { root.dataset.theme = t; };
  set(saved || (mq.matches ? 'dark' : 'light'));
  mq.addEventListener('change', function (e) { if (!saved) set(e.matches ? 'dark' : 'light'); });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-theme-toggle]')) return;
    saved = root.dataset.theme === 'dark' ? 'light' : 'dark';
    set(saved);
    try { localStorage.setItem('theme', saved); } catch (e) {}
  });
})();
</script>
<?php endif ?>
<title><?= e($title) ?> &middot; ADYPU Research Publications</title>
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<meta name="theme-color" content="#C21B27">
</head>
<?php
}

// $crumb is the small grey line above the title; $actions is raw HTML for the
// right of the top bar; $active names the sidebar item to highlight.
function page_head(string $title, string $active, string $crumb = '', string $actions = ''): array {
    $u = auth_require();
    html_head($title);
    echo '<body class="app">' . icon_sprite();
    echo sidebar($u, $active);
    echo '<div class="wrap"><header class="topbar">'
       . '<button class="menu-btn" type="button" aria-label="Menu" onclick="document.body.classList.toggle(\'nav-open\')">' . icon('menu') . '</button>'
       . '<div class="topbar-title">' . ($crumb !== '' ? '<div class="crumbs">' . $crumb . '</div>' : '') . '<h1>' . e($title) . '</h1></div>'
       . '<span class="spacer"></span><div class="topbar-actions">' . $actions . '</div></header><main class="content">';
    return $u;
}

function page_foot(array $scripts = []): void {
    echo '</main><footer class="site-footer"><span>ADYPU Research Publications</span>'
       . '<span>Built by <a href="https://github.com/D00M1909" target="_blank" rel="noopener">Advait</a></span></footer></div>';
    foreach ($scripts as $s) echo '<script src="' . asset($s) . '"></script>';
    echo "</body>\n</html>\n";
}

function sidebar(array $u, string $active): string {
    $admin = !empty($u['admin']);
    $mine = ($u['unit'] ?? '') !== '' ? count(pubs_where(['owner' => $u['email']])) : 0;
    $nav = fn($id, $href, $ic, $label, $count = null) =>
        '<a class="nav' . ($active === $id ? ' on' : '') . '" href="' . $href . '">' . icon($ic) . '<span>' . e($label) . '</span>'
        . ($count !== null ? '<span class="cnt">' . $count . '</span>' : '') . '</a>';

    $h = '<aside class="side"><a class="side-brand" href="index.php"><span class="brand-logo"><img src="img/logo.png" alt="Ajeenkya D Y Patil University"></span>'
       . '<span class="side-title">ADYPU Research<br>Publications</span></a><nav>';
    if ($admin) {
        $pending = count(array_filter(auth_data()['users'], fn($x) => ($x['status'] ?? '') === 'pending'));
        $h .= '<div class="grp">Admin</div>'
            . $nav('overview', 'admin.php', 'pie', 'Overview')
            . $nav('all', 'list.php', 'list', 'All publications', count(pubs_all()))
            . $nav('accounts', 'accounts.php', 'users', 'Faculty accounts', $pending ?: null)
            . '<div class="grp">Institutions</div>'
            . $nav('adypu', 'admin.php?view=adypu', 'eng', 'ADYPU', count(SCHOOLS))
            . $nav('partner', 'admin.php?view=partner', 'partner', 'Knowledge partners', count(PARTNERS));
    }
    if (($u['unit'] ?? '') !== '') {
        $h .= '<div class="grp">' . ($admin ? 'My work' : 'Faculty') . '</div>'
            . $nav('mine', 'mine.php', 'home', 'My publications', $mine)
            . $nav('add', 'add.php', 'plus', 'Add publication');
    }
    $h .= $nav('account', 'account.php', 'user', 'My account') . '</nav>';
    $initials = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', preg_replace('/^(Dr|Prof|Mr|Ms|Mrs)\.?\s+/i', '', trim($u['name'] ?? 'A'))), 0, 2)));
    $sub = ($u['erp'] ?? '') !== '' ? 'ERP ' . $u['erp'] : $u['email'];
    $h .= '<div class="who"><span class="avatar">' . e(mb_strtoupper($initials)) . '</span><span class="who-text"><b>' . e($u['name'] ?? '') . '</b><span>' . e($sub) . '</span></span>'
        . '<button class="who-out theme-btn" type="button" data-theme-toggle title="Light or dark" aria-label="Switch light or dark mode">' . icon('moon', 'ic-moon') . icon('sun', 'ic-sun') . '</button>'
        . '<a class="who-out" href="login.php?logout=1" title="Sign out" aria-label="Sign out">' . icon('out') . '</a></div></aside>'
        . '<div class="nav-scrim" onclick="document.body.classList.remove(\'nav-open\')"></div>';
    return $h;
}

// One place that decides how a message looks, so a success and a failure can
// never be styled the same by accident.
function notice(string $kind, string $text): string {
    if ($text === '') return '';
    return '<p class="notice notice-' . e($kind) . '">' . icon($kind === 'ok' ? 'check' : 'warn') . '<span>' . e($text) . '</span></p>';
}

// A message that survives one redirect: set before header('Location'), shown
// on the next page.
function flash(?string $kind = null, string $text = ''): string {
    auth_session();
    if ($kind !== null) {
        $_SESSION['flash'] = [$kind, $text];
        return '';
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f ? notice($f[0], $f[1]) : '';
}

function redirect(string $to): never {
    header('Location: ' . $to);
    exit;
}

// --- Pieces the admin pages and the faculty pages share --------------------

// The totals card: a count, then one bar split by type in the type colours,
// then each type's count.
function totals_card(string $label, int $total, string $sub, array $byType): string {
    $types = pub_types();
    $bar = '';
    foreach ($byType as $t => $n) {
        if ($n > 0) $bar .= '<span style="flex:' . $n . ';background:' . $types[$t]['color'] . '" title="' . e($types[$t]['name']) . ': ' . $n . '"></span>';
    }
    if ($bar === '') $bar = '<span class="empty"></span>';
    $keys = '';
    foreach ($byType as $t => $n) {
        $keys .= '<div class="key" style="border-color:' . $types[$t]['color'] . '"><span class="l">' . e($types[$t]['name']) . '</span>'
               . '<span class="n">' . $n . '</span></div>';
    }
    return '<div class="card totals"><div class="tot"><div class="k">' . e($label) . '</div><div class="v">' . $total . '</div><div class="d">' . e($sub) . '</div></div>'
         . '<div class="tot-right"><div class="bigbar">' . $bar . '</div><div class="keys">' . $keys . '</div></div></div>';
}

// One card with a name, a count and the same type-coloured bar. $href makes
// the whole card a link.
function unit_tile(string $name, ?string $iconId, array $byType, ?string $href = null): string {
    $types = pub_types();
    $n = array_sum($byType);
    $stack = '';
    foreach ($byType as $t => $c) {
        if ($c > 0) $stack .= '<span style="width:' . round($c / $n * 100, 2) . '%;background:' . $types[$t]['color'] . '"></span>';
    }
    $sub = $byType['journal'] . ' journal · ' . $byType['conf'] . ' conference · ' . ($byType['book'] + $byType['chapter']) . ' book · ' . ($byType['patent'] + $byType['copyright']) . ' IP';
    $tag = $href ? 'a' : 'div';
    return '<' . $tag . ' class="card tile' . ($n === 0 ? ' zero' : '') . '"' . ($href ? ' href="' . e($href) . '"' : '') . '>'
         . ($iconId === 'partner'
             // Partners share one handshake icon, which tells eleven tiles apart
             // by nothing; the name's first letter does, as on the dashboard.
             ? '<span class="tile-monogram" aria-hidden="true">' . e(mb_strtoupper(mb_substr($name, 0, 1))) . '</span>'
             : ($iconId ? '<span class="tile-ic">' . icon($iconId) . '</span>' : ''))
         . '<span class="name">' . e($name) . '</span><span class="count">' . $n . ' <small>publication' . ($n === 1 ? '' : 's') . '</small></span>'
         . '<span class="stack">' . $stack . '</span><span class="sub">' . $sub . '</span></' . $tag . '>';
}

// A pie (or ring, when $hole > 0) from [[label, value, colour], ...].
function pie_svg(array $slices, int $size, float $hole = 0, string $centerTop = '', string $centerBottom = ''): string {
    $total = array_sum(array_column($slices, 1));
    $r = $size / 2;
    $out = '';
    if ($total === 0) {
        $out = '<circle cx="' . $r . '" cy="' . $r . '" r="' . $r . '" style="fill:var(--track)"/>';
    } else {
        $a0 = -M_PI / 2;
        $nonzero = array_values(array_filter($slices, fn($s) => $s[1] > 0));
        foreach ($nonzero as $s) {
            if (count($nonzero) === 1) {
                $out .= '<circle cx="' . $r . '" cy="' . $r . '" r="' . $r . '" fill="' . $s[2] . '"><title>' . e($s[0]) . ': ' . $s[1] . '</title></circle>';
                break;
            }
            $a1 = $a0 + $s[1] / $total * 2 * M_PI;
            $large = $a1 - $a0 > M_PI ? 1 : 0;
            $out .= sprintf('<path d="M%1$s %1$s L%2$.2f %3$.2f A%1$s %1$s 0 %4$d 1 %5$.2f %6$.2f Z" fill="%7$s" style="stroke:var(--surface-raised)" stroke-width="2"><title>%8$s: %9$d</title></path>',
                $r, $r + $r * cos($a0), $r + $r * sin($a0), $large, $r + $r * cos($a1), $r + $r * sin($a1), $s[2], e($s[0]), $s[1]);
            $a0 = $a1;
        }
    }
    if ($hole > 0) {
        $out .= '<circle cx="' . $r . '" cy="' . $r . '" r="' . ($r * $hole) . '" style="fill:var(--surface-raised)"/>';
        if ($centerTop !== '') $out .= '<text x="' . $r . '" y="' . ($r + 4) . '" text-anchor="middle" class="pie-big">' . e($centerTop) . '</text>';
        if ($centerBottom !== '') $out .= '<text x="' . $r . '" y="' . ($r + 24) . '" text-anchor="middle" class="pie-small">' . e($centerBottom) . '</text>';
    }
    return '<svg class="pie" viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '" role="img">' . $out . '</svg>';
}

// Colours for schools or partners in a pie, in unit order.
const UNIT_PALETTE = ['#C21B27', '#2563eb', '#15803d', '#d97706', '#7c3aed', '#0891b2', '#db2777', '#65a30d', '#475569', '#9a3412', '#0f766e'];

function type_badge(string $type): string {
    $t = pub_type($type);
    return '<span class="type-ic" style="color:' . $t['color'] . '" title="' . e($t['name']) . '">' . icon($t['icon']) . '</span>';
}

function proof_badges(array $p): string {
    $file = ($p['proof_file'] ?? '') !== '';
    $link = ($p['proof_link'] ?? '') !== '';
    if ($file && $link) return '<span class="ok-text">' . icon('check') . 'Complete</span>';
    return '<span class="chip chip-miss">Missing ' . (!$file && !$link ? 'file and link' : (!$file ? 'file' : 'link')) . '</span>';
}

function year_select(string $name, string $selected, bool $allowAll, string $allLabel = 'All academic years'): string {
    $h = '<label class="sel">' . icon('calendar') . '<select name="' . e($name) . '" onchange="this.form.submit()">';
    if ($allowAll) $h .= '<option value="">' . e($allLabel) . '</option>';
    foreach (academic_years() as $y) $h .= '<option value="' . $y . '"' . ($y === $selected ? ' selected' : '') . '>Academic year ' . $y . '</option>';
    return $h . '</select></label>';
}

// The Export button: Excel, CSV or a printable page, for whatever the page
// above it is showing. $query carries those filters.
function export_menu(string $query): string {
    $q = $query !== '' ? '&' . $query : '';
    return '<details class="menu"><summary class="btn primary">' . icon('download') . 'Export' . icon('caret') . '</summary><div class="menu-panel">'
         . '<a href="export.php?format=xlsx' . e($q) . '">' . icon('file') . 'Excel (.xlsx)</a>'
         . '<a href="export.php?format=csv' . e($q) . '">' . icon('list') . 'CSV</a>'
         . '<a href="export.php?format=pdf' . e($q) . '" target="_blank">' . icon('download') . 'PDF (print)</a>'
         . '</div></details>';
}

// The admin's table of publications, with who filed each one. $showUnit adds
// the school column, for lists that span more than one.
function pubs_table(array $rows, bool $showUnit): string {
    if (!$rows) return '<div class="card empty-state"><b>No publications here yet</b>Try another year or filter.</div>';
    $h = '<div class="card table-card"><table><thead><tr><th></th><th>Title</th><th>Faculty</th>' . ($showUnit ? '<th>School / partner</th>' : '<th>Department</th>')
       . '<th>Year</th><th>Published</th><th>Indexing</th><th>Proof</th><th></th></tr></thead><tbody>';
    foreach ($rows as $p) {
        $h .= '<tr><td>' . type_badge($p['type']) . '</td>'
            . '<td><div class="t-title"><a href="view.php?id=' . e($p['id']) . '">' . e(pub_title($p)) . '</a></div><div class="t-sub">' . e(pub_source($p)) . '</div></td>'
            . '<td>' . e($p['owner_name'] ?? '') . (($p['owner_erp'] ?? '') !== '' ? '<div class="t-sub mono">' . e($p['owner_erp']) . '</div>' : '') . '</td>'
            . '<td>' . ($showUnit ? e(unit_short($p['unit'])) . '<div class="t-sub">' . e($p['dept']) . '</div>' : e($p['dept'])) . '</td>'
            . '<td class="mono">' . e($p['ay']) . '</td><td class="mono">' . e(pub_date_label($p)) . '</td>'
            . '<td>' . e(pub_indexing($p)) . '</td><td>' . proof_badges($p) . '</td>'
            . '<td><div class="acts"><a href="view.php?id=' . e($p['id']) . '" title="View">' . icon('eye') . '</a><a href="add.php?id=' . e($p['id']) . '" title="Edit">' . icon('edit') . '</a></div></td></tr>';
    }
    return $h . '</tbody></table></div>';
}
