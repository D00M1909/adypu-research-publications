<?php
// A faculty member's own details and password.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_require();
$msg = ['', ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $msg = ['err', 'The page expired. Please try again.'];
    } elseif (($_POST['action'] ?? '') === 'profile' && ($u['unit'] ?? '') !== '') {
        $dept = ($_POST['dept'] ?? '') === '__other' ? trim((string) ($_POST['dept_other'] ?? '')) : trim((string) ($_POST['dept'] ?? ''));
        $err = auth_update_profile($u['email'], (string) ($_POST['name'] ?? ''), $dept, (string) ($_POST['designation'] ?? ''));
        if ($err === '') {
            flash('ok', 'Saved your details.');
            redirect('account.php');
        }
        $msg = ['err', $err];
    } elseif (($_POST['action'] ?? '') === 'password') {
        if (($_POST['new'] ?? '') !== ($_POST['confirm'] ?? '')) {
            $msg = ['err', 'The two new passwords do not match.'];
        } else {
            $err = auth_change_password($u['email'], (string) ($_POST['current'] ?? ''), (string) ($_POST['new'] ?? ''));
            $msg = $err === '' ? ['ok', 'Password changed. Other devices are signed out.'] : ['err', $err];
        }
    }
}

page_head('My account', 'account');
echo flash() . notice($msg[0], $msg[1]);
?>
<div class="row2">
<?php if (($u['unit'] ?? '') !== ''): ?>
  <section class="card form-card">
    <h2>Your details</h2>
    <p>These go on every publication you add.</p>
    <form method="post" class="stack-col">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="field"><label for="name">Full name</label><input type="text" id="name" name="name" value="<?= e($_POST['name'] ?? $u['name'] ?? '') ?>"></div>
      <div class="grid2">
        <div class="field"><span class="label">ERP number</span><input type="text" value="<?= e($u['erp'] ?? '') ?>" disabled></div>
        <div class="field"><span class="label"><?= unit_kind($u['unit']) === 'partner' ? 'Knowledge partner' : 'School' ?></span><input type="text" value="<?= e(unit_name($u['unit'])) ?>" disabled></div>
      </div>
      <div class="grid2">
        <div class="field"><label for="dept">Department</label><?= dept_select($u['unit'], (string) ($u['dept'] ?? '')) ?></div>
        <div class="field"><label for="designation">Designation</label><select id="designation" name="designation"><?php foreach (DESIGNATIONS as $d): ?><option<?= ($u['designation'] ?? '') === $d ? ' selected' : '' ?>><?= e($d) ?></option><?php endforeach ?></select></div>
      </div>
      <p class="hint">To change your ERP number or school, ask an admin.</p>
      <div><button class="btn primary" type="submit">Save details</button></div>
    </form>
  </section>
<?php endif ?>
  <section class="card form-card">
    <h2>Password</h2>
    <p>Signed in as <?= e($u['email']) ?></p>
    <form method="post" class="stack-col">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <input type="email" name="email" value="<?= e($u['email']) ?>" autocomplete="username" hidden>
      <div class="field"><label for="current">Current password</label><input type="password" id="current" name="current" required autocomplete="current-password"></div>
      <div class="field"><label for="new">New password</label><input type="password" id="new" name="new" required minlength="<?= AUTH_MIN_PASSWORD ?>" autocomplete="new-password"></div>
      <div class="field"><label for="confirm">New password again</label><input type="password" id="confirm" name="confirm" required autocomplete="new-password"></div>
      <div><button class="btn primary" type="submit">Change password</button></div>
    </form>
  </section>
</div>
<?php page_foot(['js/app.js']);
