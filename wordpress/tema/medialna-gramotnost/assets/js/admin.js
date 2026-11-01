/* Administrácia témy: otázky testov (pridať, odstrániť, presunúť) a kontrola varovných znakov. */
(function () {
  'use strict';
  function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

  /* ---------- Počítadlo: zvýraznenia vs. vysvetlenia ---------- */
  function countFlags(scope) {
    var n = 0;
    $$('[data-field="sender"],[data-field="subject"],[data-field="address"],[data-field="url"],[data-field="message"]', scope).forEach(function (el) {
      var m = (el.value || '').match(/==(.+?)==/g); if (m) n += m.length;
    });
    var notesEl = scope.querySelector('[data-field="notes"]');
    var notes = notesEl ? notesEl.value.split(/\n/).filter(function (l) { return l.trim(); }).length : 0;
    var out = scope.querySelector('.mgt-count');
    if (!out) return;
    if (!n && !notes) { out.textContent = ''; out.className = 'mgt-count description'; return; }
    var ok = n === notes;
    out.className = 'mgt-count description ' + (ok ? 'mgt-ok' : 'mgt-warn');
    out.textContent = 'Zvýraznení: ' + n + ' · vysvetlení: ' + notes + (ok ? ' – sedí.' : ' – počet by mal byť rovnaký.');
  }
  function bindCounter(scope) {
    scope.addEventListener('input', function () { countFlags(scope); });
    countFlags(scope);
  }
  $$('#mgt_box_mock .mgt-box').forEach(bindCounter);

  /* ---------- Otázky testu ---------- */
  var rep = document.querySelector('.mgt-repeater');
  if (!rep) return;
  var tpl = document.querySelector('.mgt-q-template');
  var typeSel = document.querySelector('select[name="mg_meta[test_type]"]');
  var counter = Date.now();

  function applyType() {
    var t = typeSel ? typeSel.value : 'trenazer';
    document.body.classList.toggle('mgt-type-kviz', t === 'kviz');
    document.body.classList.toggle('mgt-type-trenazer', t !== 'kviz');
    var ex = document.getElementById('mgt_box_examples');
    if (ex) ex.style.display = t === 'kviz' ? 'none' : '';
  }
  function number() {
    $$('.mgt-q', rep).forEach(function (q, i) { q.querySelector('.mgt-q-num').textContent = (i + 1) + '.'; });
  }
  function title(q) {
    var t = q.querySelector('[data-field="text"]').value || q.querySelector('[data-field="message"]').value || 'Nová otázka';
    t = t.replace(/==/g, '').split('\n')[0];
    q.querySelector('.mgt-q-title').textContent = t.length > 70 ? t.slice(0, 70) + '…' : t;
  }
  function bind(q) {
    q.querySelector('.mgt-toggle').addEventListener('click', function () { q.classList.toggle('mgt-open'); });
    q.querySelector('.mgt-remove').addEventListener('click', function () {
      if (window.confirm('Odstrániť túto otázku?')) { q.remove(); number(); }
    });
    q.querySelector('.mgt-up').addEventListener('click', function () { if (q.previousElementSibling) rep.insertBefore(q, q.previousElementSibling); number(); });
    q.querySelector('.mgt-down').addEventListener('click', function () { if (q.nextElementSibling) rep.insertBefore(q.nextElementSibling, q); number(); });
    q.addEventListener('input', function () { title(q); });
    bindCounter(q.querySelector('.mgt-q-body'));
  }
  $$('.mgt-q', rep).forEach(bind);
  document.querySelector('.mgt-add').addEventListener('click', function () {
    var html = tpl.innerHTML.replace(/__i__/g, 'n' + (counter++));
    var wrap = document.createElement('div'); wrap.innerHTML = html;
    var q = wrap.firstElementChild;
    q.classList.add('mgt-open');
    rep.appendChild(q); bind(q); number();
    var first = q.querySelector(document.body.classList.contains('mgt-type-kviz') ? '[data-field="text"]' : '[data-field="message"]');
    if (first) first.focus();
  });
  if (typeSel) typeSel.addEventListener('change', applyType);
  applyType(); number();
})();
