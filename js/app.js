// The little bits that make the pages feel quick: department lists that follow
// the school, the add form's type switch and checklist, and the pie's year ticks.
// Every page still works without it; the server checks everything again.
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };

  // --- Department follows school, with "Other" to type one in ---------------

  var unit = $('#unit'), dept = $('#dept'), deptData = $('#dept-data');
  var other = $('#dept_other');
  function showOther() {
    if (other && dept) {
      other.hidden = dept.value !== '__other';
      other.required = !other.hidden;
    }
  }
  function fillDepts(keep) {
    if (!unit || !dept || !deptData) return;
    var list = JSON.parse(deptData.textContent)[unit.value];
    if (!list) { dept.innerHTML = '<option value="">Choose your school first</option>'; showOther(); return; }
    var sel = keep || '', found = false;
    var h = '<option value="">Choose</option>';
    list.forEach(function (d) { if (d === sel) found = true; h += '<option' + (d === sel ? ' selected' : '') + '>' + esc(d) + '</option>'; });
    h += '<option value="__other"' + (sel === '__other' || (sel && !found) ? ' selected' : '') + '>Other</option>';
    dept.innerHTML = h;
    if (sel && !found && sel !== '__other' && other) other.value = sel;
    showOther();
  }
  if (unit && dept && deptData) {
    fillDepts(dept.getAttribute('data-selected') || dept.value);
    unit.addEventListener('change', function () { fillDepts(''); });
  }
  if (dept) dept.addEventListener('change', showOther);
  showOther();

  // Sign-up: ADYPU or partner decides which list shows and whether ERP is required.
  var kinds = $$('input[name=kind]');
  function applyKind() {
    var k = ($('input[name=kind]:checked') || {}).value || 'adypu';
    $$('#unit optgroup').forEach(function (g) {
      var on = g.getAttribute('data-kind') === k;
      g.hidden = !on; g.disabled = !on;
    });
    if (unit && unit.selectedOptions[0] && unit.selectedOptions[0].parentNode.disabled) { unit.value = ''; fillDepts(''); }
    var erp = $('#erp'), hint = $('#erp-hint');
    if (erp) erp.required = k === 'adypu';
    if (hint) hint.textContent = k === 'adypu' ? '' : '(if you have one)';
  }
  if (kinds.length) { kinds.forEach(function (r) { r.addEventListener('change', applyKind); }); applyKind(); }

  // --- Add / edit form ------------------------------------------------------

  var form = $('#pubform');
  if (form) {
    var data = JSON.parse($('#form-data').textContent);
    var fileInput = $('#proof_file'), linkInput = $('#proof_link'), ay = $('#ay');

    var current = function () { return ($('input[name=type]:checked', form) || {}).value; };
    var section = function () { return $('.type-fields[data-type="' + current() + '"]', form); };

    var setCheck = function (el, ok, text) {
      el.classList.toggle('no', !ok);
      if (text) $('span', el).textContent = text;
    };

    // "2026-03" or "2026-03-14" to the academic year it falls in.
    var yearOf = function (v) {
      var m = /^(\d{4})-(\d{2})/.exec(v || '');
      if (!m) return '';
      var y = +m[1], start = +m[2] >= 6 ? y : y - 1;
      return start + '-' + String((start + 1) % 100).padStart(2, '0');
    };

    var refresh = function () {
      var sec = section();
      $$('.type-fields', form).forEach(function (s) {
        var on = s === sec;
        s.hidden = !on;
        $$('input, select, textarea', s).forEach(function (i) { i.disabled = !on; });
      });
      var name = data.types[current()];
      $('#st-type').textContent = name; $('#sum-type').textContent = name;
      $('#st-ay').textContent = ay.value; $('#sum-ay').textContent = ay.value;

      var missing = 0;
      $$('.field[data-required]', sec).forEach(function (f) {
        var filled = $$('input, select, textarea', f).some(function (i) {
          return (i.type === 'checkbox') ? i.checked : i.value.trim() !== '';
        });
        if (!filled) missing++;
      });
      setCheck($('#chk-fields'), missing === 0, missing === 0 ? 'Required fields' : missing + ' required field' + (missing === 1 ? '' : 's') + ' left');
      var hasFile = data.hasFile || (fileInput.files && fileInput.files.length > 0);
      setCheck($('#chk-file'), hasFile, 'Proof file');
      setCheck($('#chk-link'), linkInput.value.trim() !== '', 'Proof link');

      var dateKey = sec.getAttribute('data-date');
      var dateInput = $('[name="f_' + current() + '_' + dateKey + '"]', sec);
      var y = dateInput ? yearOf(dateInput.value) : '';
      var warn = $('#chk-year');
      warn.hidden = !y || y === ay.value;
      warn.classList.add('warn');
      if (!warn.hidden) $('span', warn).textContent = 'The date is in ' + y + ', not ' + ay.value;

      // Stepper: details done when nothing required is left, proof when either is in.
      var s3 = $('[data-step="3"]'), s4 = $('[data-step="4"]');
      s3.classList.toggle('done', missing === 0); s3.classList.toggle('cur', missing > 0);
      var proofOk = hasFile || linkInput.value.trim() !== '';
      s4.classList.toggle('done', proofOk); s4.classList.toggle('cur', missing === 0 && !proofOk);
      $('.dot', s3).innerHTML = missing === 0 ? $('.dot', $('[data-step="1"]')).innerHTML : '3';
      $('.dot', s4).innerHTML = proofOk ? $('.dot', $('[data-step="1"]')).innerHTML : '4';
    };

    fileInput.addEventListener('change', function () {
      if (fileInput.files.length) $('#file-name').textContent = fileInput.files[0].name;
      refresh();
    });
    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
  }

  // --- Pie with a tick per academic year -------------------------------------

  $$('#pie-panel').forEach(function (panel) {
    var d = JSON.parse($('.pie-data', panel).textContent);
    var box = $('.pie-box', panel), legend = $('.legend', panel);
    var size = 220, r = size / 2;

    var draw = function () {
      var years = $$('.ticks input:checked', panel).map(function (i) { return i.value; });
      var values = d.units.map(function (u, i) {
        return years.reduce(function (s, y) { return s + ((d.counts[y] || [])[i] || 0); }, 0);
      });
      var total = values.reduce(function (a, b) { return a + b; }, 0);
      var svg = '';
      var nonzero = values.filter(function (v) { return v > 0; }).length;
      if (total === 0) {
        svg = '<circle cx="' + r + '" cy="' + r + '" r="' + r + '" style="fill:var(--track)"/>';
      } else {
        var a0 = -Math.PI / 2;
        values.forEach(function (v, i) {
          if (!v) return;
          var title = '<title>' + esc(d.units[i].name) + ': ' + v + '</title>';
          if (nonzero === 1) { svg += '<circle cx="' + r + '" cy="' + r + '" r="' + r + '" fill="' + d.units[i].color + '">' + title + '</circle>'; return; }
          var a1 = a0 + v / total * 2 * Math.PI;
          svg += '<path d="M' + r + ' ' + r + ' L' + (r + r * Math.cos(a0)).toFixed(2) + ' ' + (r + r * Math.sin(a0)).toFixed(2) +
            ' A' + r + ' ' + r + ' 0 ' + (a1 - a0 > Math.PI ? 1 : 0) + ' 1 ' + (r + r * Math.cos(a1)).toFixed(2) + ' ' + (r + r * Math.sin(a1)).toFixed(2) +
            ' Z" fill="' + d.units[i].color + '" style="stroke:var(--surface-raised)" stroke-width="2">' + title + '</path>';
          a0 = a1;
        });
      }
      box.innerHTML = '<svg class="pie" viewBox="0 0 ' + size + ' ' + size + '" width="' + size + '" height="' + size + '" role="img">' + svg + '</svg>';
      legend.innerHTML = d.units.map(function (u, i) {
        return '<div class="legend-row"><i class="swatch" style="background:' + u.color + '"></i>' + esc(u.name) +
          '<span class="n">' + values[i] + '</span><span class="p">' + (total ? Math.round(values[i] / total * 100) : 0) + '%</span></div>';
      }).join('') + '<div class="legend-row legend-total"><b>Total</b><span class="n">' + total + '</span><span class="p"></span></div>';
    };
    $$('.ticks input', panel).forEach(function (i) { i.addEventListener('change', draw); });
    draw();
  });

  // Close an open menu when clicking anywhere else.
  document.addEventListener('click', function (ev) {
    $$('details.menu[open]').forEach(function (m) { if (!m.contains(ev.target)) m.removeAttribute('open'); });
  });
})();
