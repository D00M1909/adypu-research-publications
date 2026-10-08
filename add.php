<?php
// Adding a publication, or editing one with ?id=. The academic year comes
// first, then the type, then that type's fields from the workbook, then the
// proof as a file and a link.
require_once __DIR__ . '/includes/page.php';

auth_boot();
$u = auth_require();

$editing = null;
if (isset($_GET['id'])) {
    $editing = pub_find((string) $_GET['id']);
    if (!$editing || !pub_can_edit($editing, $u)) {
        http_response_code(404);
        exit('Not found.');
    }
} elseif (($u['unit'] ?? '') === '') {
    // An admin account with no school has nobody to file a paper under.
    redirect('admin.php');
}

$ay = (string) ($_POST['ay'] ?? $editing['ay'] ?? $_GET['ay'] ?? current_academic_year());
$type = (string) ($_POST['type'] ?? $editing['type'] ?? $_GET['type'] ?? 'journal');
if (!pub_type($type)) $type = 'journal';
$values = $editing['f'] ?? [];
$link = (string) ($_POST['proof_link'] ?? $editing['proof_link'] ?? '');
$errors = [];
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $err = 'The page expired. Please try again.';
    } else {
        if (!is_academic_year($ay)) $errors['ay'] = 'Choose the academic year.';
        [$values, $fieldErrors] = pub_read_fields($type, $_POST);
        $errors += $fieldErrors;
        $link = trim($link);
        if ($link !== '' && !preg_match('#^https?://\S+$#i', $link)) $errors['proof_link'] = 'Paste the full link, starting with https://';
        $hasFile = ($_FILES['proof_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE || ($editing['proof_file'] ?? '') !== '';
        if (!$hasFile && $link === '') $errors['proof'] = 'Add the proof as a file, a link, or both.';

        if (!$errors) {
            $id = $editing['id'] ?? pub_new_id();
            [$file, $fileErr] = proof_store($_FILES['proof_file'] ?? [], $id);
            if ($fileErr !== '') {
                $errors['proof'] = $fileErr;
            } else {
                // A new upload replaces the old file; a PDF over a JPG leaves the JPG behind otherwise.
                $old = $editing['proof_file'] ?? '';
                if ($file !== '' && $old !== '' && $old !== $file) @unlink(proof_path($old));
                $owner = $editing ? (auth_find($editing['owner']) ?? []) : $u;
                $record = [
                    'id'         => $id,
                    'owner'      => $editing['owner'] ?? $u['email'],
                    'owner_name' => $owner['name'] ?? ($editing['owner_name'] ?? ''),
                    'owner_erp'  => $owner['erp'] ?? ($editing['owner_erp'] ?? ''),
                    'designation' => $owner['designation'] ?? ($editing['designation'] ?? ''),
                    'unit'       => $editing['unit'] ?? $u['unit'],
                    'dept'       => $editing['dept'] ?? $u['dept'],
                    'ay'         => $ay,
                    'type'       => $type,
                    'f'          => $values,
                    'proof_file' => $file !== '' ? $file : ($editing['proof_file'] ?? ''),
                    'proof_link' => $link,
                    'created'    => $editing['created'] ?? date('Y-m-d H:i'),
                    'updated'    => date('Y-m-d H:i'),
                ];
                pub_save($record);
                $t = pub_type($type);
                $date = (string) ($values[$t['date']] ?? '');
                $msg = $editing ? 'Saved your changes.' : 'Added. It now counts towards ' . $ay . '.';
                if ($date !== '' && !in_academic_year($date, $ay)) {
                    $msg .= ' The ' . strtolower($t['fields'][$t['date']][0]) . ' falls in ' . academic_year((int) substr($date, 0, 4), (int) substr($date, 5, 2)) . ', so check the year is right.';
                }
                flash('ok', $msg);
                redirect($editing && ($editing['owner'] !== $u['email']) ? 'view.php?id=' . $id : 'mine.php?ay=' . $ay);
            }
        }
        if ($errors) $err = 'Some fields need a look before this can be saved.';
    }
}

$types = pub_types();
$crumb = '<a href="' . (!empty($u['admin']) && ($u['unit'] ?? '') === '' ? 'list.php' : 'mine.php') . '">' . (($u['unit'] ?? '') === '' ? 'All publications' : 'My publications') . '</a> / ' . ($editing ? 'Edit' : 'Add');
page_head($editing ? 'Edit publication' : 'Add publication', $editing ? 'mine' : 'add', $crumb);
echo notice('err', $err);
?>
<div class="card stepper no-print" id="stepper">
  <div class="step done" data-step="1"><span class="dot"><?= icon('check') ?></span><span>Academic year<small id="st-ay"><?= e($ay) ?></small></span></div>
  <span class="line done"></span>
  <div class="step done" data-step="2"><span class="dot"><?= icon('check') ?></span><span>Type<small id="st-type"><?= e($types[$type]['name']) ?></small></span></div>
  <span class="line"></span>
  <div class="step cur" data-step="3"><span class="dot">3</span><span>Details<small>From the IQAC format</small></span></div>
  <span class="line"></span>
  <div class="step" data-step="4"><span class="dot">4</span><span>Proof<small>File and link</small></span></div>
</div>

<form class="form2" method="post" enctype="multipart/form-data" id="pubform">
  <?= csrf_field() ?>
  <input type="hidden" name="MAX_FILE_SIZE" value="<?= PROOF_MAX_BYTES ?>">
  <div class="stack-col">
    <section class="card form-card">
      <h2>1. Academic year</h2>
      <p>June to May. Pick the year this publication should count in.</p>
      <div class="field<?= isset($errors['ay']) ? ' has-error' : '' ?>" style="max-width:320px">
        <select name="ay" id="ay">
          <?php foreach (academic_years() as $y): ?><option value="<?= $y ?>"<?= $y === $ay ? ' selected' : '' ?>><?= $y ?><?= $y === current_academic_year() ? ' (current)' : '' ?></option><?php endforeach ?>
        </select>
        <?php if (isset($errors['ay'])): ?><span class="err"><?= e($errors['ay']) ?></span><?php endif ?>
      </div>
    </section>

    <section class="card form-card">
      <h2>2. Type of publication</h2>
      <p>The fields below change to match.</p>
      <div class="type-pick">
        <?php foreach ($types as $id => $t): ?>
        <label class="type-opt"><input type="radio" name="type" value="<?= $id ?>"<?= $id === $type ? ' checked' : '' ?>>
          <span class="ic-b" style="background:<?= $t['color'] ?>1a;color:<?= $t['color'] ?>"><?= icon($t['icon']) ?></span>
          <span><b><?= e($t['name']) ?></b><small><?= count($t['fields']) + 1 ?> fields</small></span></label>
        <?php endforeach ?>
      </div>
    </section>

    <section class="card form-card">
      <h2>3. Details</h2>
      <p>Fields with <span class="req">*</span> are required.</p>
      <?php foreach ($types as $id => $t): ?>
      <div class="type-fields fgrid" data-type="<?= $id ?>" data-date="<?= e($t['date']) ?>" data-title="<?= e($t['title']) ?>"<?= $id === $type ? '' : ' hidden' ?>>
        <?php foreach ($t['fields'] as $key => $f) echo field_input($id, $key, $f, $id === $type ? ($values[$key] ?? '') : '', $id === $type ? ($errors[$key] ?? '') : ''); ?>
      </div>
      <?php endforeach ?>
    </section>

    <section class="card form-card">
      <h2>4. Proof</h2>
      <p>Upload the paper, certificate or acceptance letter, and paste its link. PDF, JPG or PNG, up to <?= PROOF_MAX_BYTES >> 20 ?> MB.</p>
      <div class="grid2">
        <div class="field<?= isset($errors['proof']) ? ' has-error' : '' ?>">
          <span class="label">Proof document</span>
          <label class="upload"><?= icon('upload') ?><span><strong id="file-name"><?= ($editing['proof_file'] ?? '') !== '' ? 'File on record. Choose another to replace it' : 'Choose a file' ?></strong><br>or drop it here</span>
            <input type="file" name="proof_file" id="proof_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"></label>
          <?php if (($editing['proof_file'] ?? '') !== ''): ?><a class="hint" href="proof.php?id=<?= e($editing['id']) ?>" target="_blank">Open the current file</a><?php endif ?>
          <?php if (isset($errors['proof'])): ?><span class="err"><?= e($errors['proof']) ?></span><?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors): ?><span class="hint">If you chose a file, choose it again.</span><?php endif ?>
        </div>
        <div class="field<?= isset($errors['proof_link']) ? ' has-error' : '' ?>">
          <label for="proof_link">Proof link</label>
          <input type="text" name="proof_link" id="proof_link" value="<?= e($link) ?>" placeholder="https://drive.google.com/...">
          <?php if (isset($errors['proof_link'])): ?><span class="err"><?= e($errors['proof_link']) ?></span><?php endif ?>
        </div>
      </div>
    </section>
  </div>

  <aside class="card sumcard" id="summary">
    <span class="card-label">Summary</span>
    <div class="r"><span>Academic year</span><b id="sum-ay"><?= e($ay) ?></b></div>
    <div class="r"><span>Type</span><b id="sum-type"><?= e($types[$type]['name']) ?></b></div>
    <div class="r"><span>Faculty</span><b><?= e($editing['owner_name'] ?? $u['name'] ?? '') ?></b></div>
    <div class="r"><span><?= unit_kind($editing['unit'] ?? $u['unit'] ?? '') === 'partner' ? 'Partner' : 'School' ?></span><b><?= e(unit_short($editing['unit'] ?? $u['unit'] ?? '')) ?></b></div>
    <hr>
    <div class="check no" id="chk-fields"><?= icon('check') ?><span>Required fields</span></div>
    <div class="check no" id="chk-file"><?= icon('check') ?><span>Proof file</span></div>
    <div class="check no" id="chk-link"><?= icon('check') ?><span>Proof link</span></div>
    <div class="check" id="chk-year" hidden><?= icon('warn') ?><span></span></div>
    <button class="btn primary" type="submit"><?= icon('check') ?><?= $editing ? 'Save changes' : 'Save publication' ?></button>
    <a class="btn" href="<?= $editing ? 'view.php?id=' . e($editing['id']) : 'mine.php' ?>">Cancel</a>
    <script type="application/json" id="form-data"><?= json_encode(['hasFile' => ($editing['proof_file'] ?? '') !== '', 'types' => array_map(fn($t) => $t['name'], $types)], JSON_HEX_TAG) ?></script>
  </aside>
</form>
<?php page_foot(['js/app.js']);
