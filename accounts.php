<?php
// Faculty accounts: approving sign-ups that came without a join code,
// disabling, resetting passwords, making admins, and each unit's join code.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$me = auth_require(true);
$msg = ['', ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $msg = ['err', 'The page expired. Please try again.'];
    } else {
        $email = auth_key((string) ($_POST['email'] ?? ''));
        $target = $email !== '' ? auth_find($email) : null;
        $act = (string) ($_POST['action'] ?? '');
        $self = $email === auth_key($me['email']);
        if (in_array($act, ['approve', 'disable', 'enable', 'reset', 'admin', 'unadmin', 'save'], true) && !$target) {
            $msg = ['err', 'That account no longer exists.'];
        } elseif ($act === 'approve' || $act === 'enable') {
            auth_put($email, ['status' => 'active']);
            $msg = ['ok', $target['name'] . ' can sign in now.'];
        } elseif ($act === 'disable') {
            if ($self) $msg = ['err', 'You cannot disable your own account.'];
            else { auth_put($email, ['status' => 'disabled', 'remember' => '']); $msg = ['ok', $target['name'] . ' is disabled. Their publications stay.']; }
        } elseif ($act === 'reset') {
            $temp = auth_reset_password($email);
            $msg = ['ok', 'Temporary password for ' . $target['name'] . ': ' . $temp . ' (share it with them; they can change it under My account).'];
        } elseif ($act === 'admin' || $act === 'unadmin') {
            if ($self) $msg = ['err', 'Ask another admin to change your own access.'];
            else { auth_put($email, ['admin' => $act === 'admin']); $msg = ['ok', $target['name'] . ($act === 'admin' ? ' is now an admin.' : ' is no longer an admin.')]; }
        } elseif ($act === 'save') {
            $unit = (string) ($_POST['unit'] ?? '');
            $dept = ($_POST['dept'] ?? '') === '__other' ? trim((string) ($_POST['dept_other'] ?? '')) : trim((string) ($_POST['dept'] ?? ''));
            if (!unit_exists($unit)) $msg = ['err', 'Choose a school or partner.'];
            else {
                $err = auth_update_profile($email, (string) ($_POST['name'] ?? ''), $dept, (string) ($_POST['designation'] ?? ''));
                if ($err !== '') $msg = ['err', $err];
                else {
                    auth_put($email, ['unit' => $unit, 'erp' => strtoupper(trim((string) ($_POST['erp'] ?? '')))]);
                    flash('ok', 'Saved. Publications already filed keep the school they were filed under.');
                    redirect('accounts.php');
                }
            }
        } elseif ($act === 'code') {
            $unit = (string) ($_POST['unit'] ?? '');
            if (unit_exists($unit)) {
                $code = ($_POST['clear'] ?? '') ? '' : auth_new_code();
                store_update('faculty', function (array $d) use ($unit, $code) {
                    $d['codes'][$unit] = $code;
                    return $d;
                });
                $msg = ['ok', $code !== '' ? 'New join code for ' . unit_short($unit) . ': ' . $code : 'Join code for ' . unit_short($unit) . ' turned off. Sign-ups there now wait for approval.'];
            }
        }
    }
}

$data = auth_data();
$users = $data['users'];
$filter = in_array($_GET['show'] ?? '', ['pending', 'admins', 'disabled'], true) ? $_GET['show'] : '';
$q = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
$counts = ['pending' => 0, 'admins' => 0, 'disabled' => 0];
foreach ($users as $x) {
    if (($x['status'] ?? '') === 'pending') $counts['pending']++;
    if (($x['status'] ?? '') === 'disabled') $counts['disabled']++;
    if (!empty($x['admin'])) $counts['admins']++;
}
$list = [];
foreach ($users as $email => $x) {
    if ($filter === 'pending' && ($x['status'] ?? '') !== 'pending') continue;
    if ($filter === 'disabled' && ($x['status'] ?? '') !== 'disabled') continue;
    if ($filter === 'admins' && empty($x['admin'])) continue;
    if ($q !== '' && !str_contains(mb_strtolower(($x['name'] ?? '') . ' ' . $email . ' ' . ($x['erp'] ?? '')), $q)) continue;
    $list[$email] = $x;
}
// Pending first, then by name.
uksort($list, fn($a, $b) => [($list[$a]['status'] ?? '') !== 'pending', $list[$a]['name'] ?? ''] <=> [($list[$b]['status'] ?? '') !== 'pending', $list[$b]['name'] ?? '']);
$mine = [];
foreach (pubs_all() as $p) $mine[$p['owner']] = ($mine[$p['owner']] ?? 0) + 1;
$editing = isset($_GET['edit']) ? auth_find((string) $_GET['edit']) : null;

page_head('Faculty accounts', 'accounts', 'Admin');
echo flash() . notice($msg[0], $msg[1]);

$btn = fn($email, $action, $label, $cls = 'btn small') => '<form method="post" class="inline">' . csrf_field()
    . '<input type="hidden" name="email" value="' . e($email) . '"><input type="hidden" name="action" value="' . $action . '"><button class="' . $cls . '" type="submit">' . $label . '</button></form>';
?>
<?php if ($editing): ?>
<section class="card form-card">
  <h2>Edit <?= e($editing['name'] ?? $editing['email']) ?></h2>
  <p><?= e($editing['email']) ?></p>
  <form method="post" class="fgrid">
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="email" value="<?= e($editing['email']) ?>">
    <div class="field"><label for="name">Full name</label><input type="text" id="name" name="name" value="<?= e($editing['name'] ?? '') ?>"></div>
    <div class="field"><label for="erp">ERP number</label><input type="text" id="erp" name="erp" value="<?= e($editing['erp'] ?? '') ?>"></div>
    <div class="field"><label for="designation">Designation</label><select id="designation" name="designation"><?php foreach (DESIGNATIONS as $d): ?><option<?= ($editing['designation'] ?? '') === $d ? ' selected' : '' ?>><?= e($d) ?></option><?php endforeach ?></select></div>
    <div class="field"><label for="unit">School or partner</label><select id="unit" name="unit">
      <optgroup label="ADYPU schools"><?php foreach (units_of('adypu') as $id): ?><option value="<?= $id ?>"<?= ($editing['unit'] ?? '') === $id ? ' selected' : '' ?>><?= e(unit_name($id)) ?></option><?php endforeach ?></optgroup>
      <optgroup label="Knowledge partners"><?php foreach (units_of('partner') as $id): ?><option value="<?= $id ?>"<?= ($editing['unit'] ?? '') === $id ? ' selected' : '' ?>><?= e(unit_name($id)) ?></option><?php endforeach ?></optgroup>
    </select></div>
    <div class="field"><label for="dept">Department</label><?= dept_select((string) ($editing['unit'] ?? ''), (string) ($editing['dept'] ?? '')) ?></div>
    <script type="application/json" id="dept-data"><?= json_encode(array_map('unit_depts', array_combine([...units_of('adypu'), ...units_of('partner')], [...units_of('adypu'), ...units_of('partner')])), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?></script>
    <div class="field form-actions"><button class="btn primary" type="submit">Save</button><a class="btn" href="accounts.php">Cancel</a></div>
  </form>
</section>
<?php endif ?>

<form class="toolbar" method="get">
  <div class="seg">
    <a href="accounts.php"<?= $filter === '' ? ' class="on"' : '' ?>>All <b><?= count($users) ?></b></a>
    <a href="?show=pending"<?= $filter === 'pending' ? ' class="on"' : '' ?>>Waiting <b><?= $counts['pending'] ?></b></a>
    <a href="?show=admins"<?= $filter === 'admins' ? ' class="on"' : '' ?>>Admins <b><?= $counts['admins'] ?></b></a>
    <a href="?show=disabled"<?= $filter === 'disabled' ? ' class="on"' : '' ?>>Disabled <b><?= $counts['disabled'] ?></b></a>
  </div>
  <?php if ($filter !== ''): ?><input type="hidden" name="show" value="<?= e($filter) ?>"><?php endif ?>
  <label class="search"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search name, email or ERP"></label>
</form>

<?php if (!$list): ?>
<div class="card empty-state"><b>No accounts here</b><?= $filter === 'pending' ? 'Nobody is waiting for approval.' : 'Faculty appear here once they sign up.' ?></div>
<?php else: ?>
<div class="card table-card">
<table>
  <thead><tr><th>Name</th><th>ERP</th><th>School / partner</th><th>Designation</th><th class="c">Papers</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $email => $x):
      $st = $x['status'] ?? 'active'; ?>
    <tr>
      <td><div class="t-title"><?= e($x['name'] ?? '') ?><?= !empty($x['admin']) ? ' <span class="chip">Admin</span>' : '' ?></div><div class="t-sub"><?= e($email) ?></div></td>
      <td class="mono"><?= e($x['erp'] ?? '') ?></td>
      <td><?= ($x['unit'] ?? '') !== '' ? e(unit_short($x['unit'])) . '<div class="t-sub">' . e($x['dept'] ?? '') . '</div>' : '<span class="muted">None</span>' ?></td>
      <td><?= e($x['designation'] ?? '') ?></td>
      <td class="c mono"><?= $mine[$email] ?? 0 ?></td>
      <td><?= $st === 'pending' ? '<span class="chip chip-miss">Waiting</span>' : ($st === 'disabled' ? '<span class="chip">Disabled</span>' : '<span class="ok-text">' . icon('check') . 'Active</span>') ?></td>
      <td><div class="row-acts">
        <?php if ($st === 'pending') echo $btn($email, 'approve', 'Approve', 'btn small primary'); ?>
        <details class="menu"><summary class="btn small"><?= icon('menu') ?></summary><div class="menu-panel right">
          <a href="?edit=<?= e(urlencode($email)) ?>"><?= icon('edit') ?>Edit details</a>
          <?= $btn($email, 'reset', icon('refresh') . 'Reset password', 'menu-item') ?>
          <?= !empty($x['admin']) ? $btn($email, 'unadmin', icon('user') . 'Remove admin', 'menu-item') : $btn($email, 'admin', icon('user-check') . 'Make admin', 'menu-item') ?>
          <?= $st === 'disabled' ? $btn($email, 'enable', icon('check') . 'Enable', 'menu-item') : ($st === 'active' ? $btn($email, 'disable', icon('x') . 'Disable', 'menu-item') : '') ?>
        </div></details>
      </div></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>

<div class="section-title"><h2>Join codes</h2><span>Faculty who sign up with their unit's code get in straight away</span></div>
<div class="card table-card">
<table>
  <thead><tr><th>School / partner</th><th>Code</th><th class="c">Faculty</th><th></th></tr></thead>
  <tbody>
  <?php $perUnit = [];
  foreach ($users as $x) if (($x['unit'] ?? '') !== '') $perUnit[$x['unit']] = ($perUnit[$x['unit']] ?? 0) + 1;
  foreach ([...units_of('adypu'), ...units_of('partner')] as $id):
      $code = (string) ($data['codes'][$id] ?? ''); ?>
    <tr><td><?= e(unit_name($id)) ?></td>
      <td><?= $code !== '' ? '<span class="mono code">' . e($code) . '</span>' : '<span class="muted">None, sign-ups wait for approval</span>' ?></td>
      <td class="c mono"><?= $perUnit[$id] ?? 0 ?></td>
      <td><div class="row-acts">
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="code"><input type="hidden" name="unit" value="<?= $id ?>"><button class="btn small" type="submit"><?= icon('refresh') ?><?= $code !== '' ? 'New code' : 'Create code' ?></button></form>
        <?php if ($code !== ''): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="code"><input type="hidden" name="unit" value="<?= $id ?>"><input type="hidden" name="clear" value="1"><button class="btn small" type="submit">Turn off</button></form><?php endif ?>
      </div></td></tr>
  <?php endforeach ?>
  </tbody>
</table>
</div>
<?php page_foot(['js/app.js']);
