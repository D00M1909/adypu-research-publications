<?php
// Sign in, and the onboarding form for a new faculty member: who they are,
// their ERP number, where they teach, then the account itself.
require_once __DIR__ . '/includes/page.php';

auth_boot();

if (isset($_GET['logout'])) {
    auth_logout();
    redirect('login.php');
}

$mode = ($_GET['mode'] ?? '') === 'signup' ? 'signup' : 'signin';
// Where to go after signing in, kept to a page on this site.
$next = (string) ($_GET['next'] ?? '');
$nextPath = basename((string) parse_url($next, PHP_URL_PATH));
$nextQuery = (string) parse_url($next, PHP_URL_QUERY);
$next = preg_match('/^[a-z]+\.php$/', $nextPath) ? $nextPath . ($nextQuery !== '' ? '?' . $nextQuery : '') : '';
$err = '';
$done = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $err = 'The page expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'signin') {
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        // Checked before signing in, so a waiting account never gets a session.
        $waiting = auth_find($email);
        if ($waiting && ($waiting['status'] ?? '') === 'pending' && password_verify($password, $waiting['hash'] ?? '')) {
            $err = 'Your account is waiting for an admin to approve it.';
        } else {
            $err = auth_login($email, $password);
            if ($err === '') redirect($next !== '' ? $next : 'index.php');
        }
    } elseif (($_POST['action'] ?? '') === 'signup') {
        $mode = 'signup';
        $f = $_POST;
        if (($f['dept'] ?? '') === '__other') $f['dept'] = trim((string) ($f['dept_other'] ?? ''));
        $err = auth_signup($f);
        if ($err === '') {
            $u = auth_find((string) $f['email']);
            if (($u['status'] ?? '') === 'active') {
                auth_login((string) $f['email'], (string) $f['password']);
                redirect('mine.php');
            }
            $done = 'Your account is created. An admin will approve it soon, then you can sign in. If your school has a join code, ask your coordinator for it next time and you will get in straight away.';
            $mode = 'signin';
        }
    }
}

$old = fn($k) => e($_POST[$k] ?? '');

html_head($mode === 'signup' ? 'Create account' : 'Sign in');
?>
<body class="auth">
<?= icon_sprite() ?>
<section class="auth-side">
  <span class="brand-logo auth-logo"><img src="img/logo.png" alt="Ajeenkya D Y Patil University"></span>
  <h1>Research Publications</h1>
  <p>Journal papers, conference papers, books, chapters, patents and copyrights from ADYPU and its knowledge partners, in one place.</p>
</section>
<main class="auth-main">
<?php if ($mode === 'signin'): ?>
  <form class="card auth-card" method="post" action="login.php<?= $next !== '' ? '?next=' . e(urlencode($next)) : '' ?>">
    <h2>Sign in</h2>
    <?= notice('ok', $done) ?><?= notice('err', $err) ?>
    <?= csrf_field() ?><input type="hidden" name="action" value="signin">
    <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= $old('email') ?>" required autocomplete="username"></div>
    <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
    <button class="btn primary" type="submit">Sign in</button>
    <p class="switch">New here? <a href="login.php?mode=signup">Create an account</a></p>
  </form>
<?php else:
  $kind = ($_POST['kind'] ?? 'adypu') === 'partner' ? 'partner' : 'adypu';
  $unit = (string) ($_POST['unit'] ?? '');
  $depts = [];
  foreach ([...units_of('adypu'), ...units_of('partner')] as $id) $depts[$id] = unit_depts($id);
?>
  <form class="card auth-card wide" method="post" action="login.php?mode=signup" id="signup">
    <h2>Create your account</h2>
    <?= notice('err', $err) ?>
    <?= csrf_field() ?><input type="hidden" name="action" value="signup">

    <div class="field"><span class="label">I teach at</span>
      <div class="seg seg-fill">
        <label><input type="radio" name="kind" value="adypu"<?= $kind === 'adypu' ? ' checked' : '' ?>> ADYPU school</label>
        <label><input type="radio" name="kind" value="partner"<?= $kind === 'partner' ? ' checked' : '' ?>> Knowledge partner</label>
      </div>
    </div>
    <div class="grid2">
      <div class="field"><label for="unit">School or partner</label>
        <select id="unit" name="unit" required>
          <option value="">Choose</option>
          <optgroup label="ADYPU schools" data-kind="adypu"><?php foreach (units_of('adypu') as $id): ?><option value="<?= $id ?>"<?= $unit === $id ? ' selected' : '' ?>><?= e(unit_name($id)) ?></option><?php endforeach ?></optgroup>
          <optgroup label="Knowledge partners" data-kind="partner"><?php foreach (units_of('partner') as $id): ?><option value="<?= $id ?>"<?= $unit === $id ? ' selected' : '' ?>><?= e(unit_name($id)) ?></option><?php endforeach ?></optgroup>
        </select>
      </div>
      <div class="field"><label for="dept">Department</label>
        <select id="dept" name="dept" required data-selected="<?= $old('dept') ?>"><option value="">Choose your school first</option></select>
        <input type="text" name="dept_other" id="dept_other" placeholder="Type your department" value="<?= $old('dept_other') ?>" hidden>
      </div>
    </div>
    <div class="grid2">
      <div class="field"><label for="name">Full name</label><input type="text" id="name" name="name" value="<?= $old('name') ?>" required placeholder="Dr. Priya Kulkarni"></div>
      <div class="field"><label for="erp">ERP number <span class="hint" id="erp-hint"><?= $kind === 'partner' ? '(if you have one)' : '' ?></span></label><input type="text" id="erp" name="erp" value="<?= $old('erp') ?>"<?= $kind === 'adypu' ? ' required' : '' ?>></div>
    </div>
    <div class="grid2">
      <div class="field"><label for="designation">Designation</label>
        <select id="designation" name="designation" required><option value="">Choose</option><?php foreach (DESIGNATIONS as $d): ?><option<?= ($_POST['designation'] ?? '') === $d ? ' selected' : '' ?>><?= e($d) ?></option><?php endforeach ?></select>
      </div>
      <div class="field"><label for="code">Join code <span class="hint">(from your coordinator)</span></label><input type="text" id="code" name="code" value="<?= $old('code') ?>" placeholder="ABCD-EFGH" autocomplete="off"></div>
    </div>
    <div class="grid2">
      <div class="field"><label for="semail">Email</label><input type="email" id="semail" name="email" value="<?= $old('email') ?>" required autocomplete="username"></div>
      <div class="field"><label for="spassword">Password</label><input type="password" id="spassword" name="password" required minlength="<?= AUTH_MIN_PASSWORD ?>" autocomplete="new-password" placeholder="At least <?= AUTH_MIN_PASSWORD ?> characters"></div>
    </div>
    <button class="btn primary" type="submit">Create account</button>
    <p class="switch">Already have an account? <a href="login.php">Sign in</a></p>
    <script type="application/json" id="dept-data"><?= json_encode($depts, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?></script>
  </form>
<?php endif ?>
</main>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
