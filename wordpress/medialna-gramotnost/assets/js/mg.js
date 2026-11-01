/* Mediálna gramotnosť – správanie prvkov.
 * Všetko sa číta z HTML, ktoré vygeneroval plugin, takže úpravy v Avada Builderi
 * sa prejavia bez zmeny skriptu. Prvky pridané neskôr (živý editor Avada)
 * sa inicializujú cez MutationObserver.
 */
(function () {
  'use strict';
  var root = document.documentElement;
  var T = (window.mgData && window.mgData.i18n) || {};
  function t(k, d) { return T[k] || d; }
  function $(s, c) { return (c || document).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }
  function once(el, key) { if (el.getAttribute('data-mg-' + key)) return false; el.setAttribute('data-mg-' + key, '1'); return true; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  /* ---------- Veľkosť písma (A+) ---------- */
  var saved = store('mg-size'); if (saved && saved !== 'm') root.setAttribute('data-mg-size', saved);
  function sizeLabel(btn) {
    var s = root.getAttribute('data-mg-size') || 'm';
    btn.textContent = s === 'xl' ? 'A−' : 'A+';
    btn.setAttribute('aria-label', s === 'xl' ? t('sizeReset', 'Vrátiť bežné písmo') : t('sizeUp', 'Zväčšiť písmo'));
  }
  function initSize(scope) {
    $$('.mg-size-btn', scope).forEach(function (btn) {
      if (!once(btn, 'size')) return;
      sizeLabel(btn);
      btn.addEventListener('click', function () {
        var s = root.getAttribute('data-mg-size') || 'm';
        var next = s === 'm' ? 'l' : s === 'l' ? 'xl' : 'm';
        if (next === 'm') root.removeAttribute('data-mg-size'); else root.setAttribute('data-mg-size', next);
        store('mg-size', next);
        $$('.mg-size-btn').forEach(sizeLabel);
      });
    });
  }

  /* ---------- Ukážky s varovnými znakmi ---------- */
  function numberExample(ex) {
    $$('.mg-flag', ex).forEach(function (f, i) { f.setAttribute('data-n', i + 1); });
    $$('.mg-flag-list li', ex).forEach(function (li, i) { li.setAttribute('data-n', i + 1); });
  }
  function initExamples(scope) {
    $$('.mg-example', scope).forEach(function (ex) {
      if (!once(ex, 'ex')) return;
      numberExample(ex);
      var btn = $('.mg-flags-btn', ex);
      if (!btn) return;
      btn.addEventListener('click', function () {
        var on = ex.classList.toggle('mg-show');
        btn.setAttribute('aria-pressed', String(on));
        btn.textContent = on ? (btn.getAttribute('data-on') || '') : (btn.getAttribute('data-off') || '');
      });
    });
  }

  /* ---------- Filter ---------- */
  function scopeEl(item) {
    if (item.getAttribute('data-mg-scope') === 'container') {
      return item.closest('.fusion-fullwidth, .mg-section') || item;
    }
    return item;
  }
  function initFilters(scope) {
    $$('[data-mg-filter-group]', scope).forEach(function (box) {
      if (!once(box, 'filter')) return;
      var group = box.getAttribute('data-mg-filter-group');
      var chips = $$('.mg-chip', box);
      var empty = $('.mg-filter-empty', box);
      chips.forEach(function (c) {
        c.addEventListener('click', function () {
          chips.forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
          c.setAttribute('aria-pressed', 'true');
          var f = c.getAttribute('data-filter'), shown = 0;
          $$('[data-mg-group="' + group + '"][data-mg-tags]').forEach(function (it) {
            var ok = f === 'all' || (' ' + it.getAttribute('data-mg-tags') + ' ').indexOf(' ' + f + ' ') > -1;
            scopeEl(it).hidden = !ok;
            if (ok) shown++;
          });
          if (empty) empty.hidden = shown > 0;
        });
      });
    });
  }

  /* ---------- Trenažér ---------- */
  function initTrainers(scope) {
    $$('.mg-trainer-wrap', scope).forEach(function (w) {
      if (!once(w, 'trainer')) return;
      var items = $$('.mg-t-item', w);
      if (!items.length) return;
      var d = function (k) { return w.getAttribute('data-' + k) || ''; };
      var stage = $('.mg-t-stage', w), side = $('.mg-t-side', w), prog = $('.mg-t-progress', w);
      var tpl = $('.mg-t-summary', w);
      var i = 0, score = 0, ans = [];
      $('.mg-t-items', w).hidden = true;
      prog.innerHTML = items.map(function () { return '<span></span>'; }).join('');
      function bars() { $$('span', prog).forEach(function (s, k) { s.className = ans[k] === undefined ? (k === i ? 'mg-now' : '') : (ans[k] ? 'mg-right' : 'mg-wrong'); }); }
      function show() {
        var it = items[i];
        bars();
        stage.innerHTML = '<p class="mg-example-label">' + esc(t('sample', 'Ukážka')) + ' ' + (i + 1) + ' / ' + items.length + (it.getAttribute('data-kind') ? ' · ' + esc(it.getAttribute('data-kind')) : '') + '</p>' + it.innerHTML;
        var ex = $('.mg-example', stage); ex.removeAttribute('data-mg-ex'); numberExample(ex);
        side.innerHTML = '<p class="mg-eyebrow">' + esc(t('question', 'Otázka')) + ' ' + (i + 1) + ' / ' + items.length + '</p><h2>' + esc(d('question')) + '</h2><p class="mg-muted">' + esc(d('hint')) + '</p>' +
          '<div class="mg-t-choice"><button class="mg-btn mg-danger" type="button" data-a="scam">' + esc(d('scam')) + '</button><button class="mg-btn mg-ok" type="button" data-a="safe">' + esc(d('safe')) + '</button></div><div class="mg-t-result"></div>';
        $$('.mg-t-choice button', side).forEach(function (b) {
          b.addEventListener('click', function () {
            var right = b.getAttribute('data-a') === it.getAttribute('data-verdict');
            var isScam = it.getAttribute('data-verdict') === 'scam';
            ans[i] = right; if (right) score++;
            $$('.mg-t-choice button', side).forEach(function (x) { x.disabled = true; x.style.opacity = x === b ? '1' : '.45'; });
            ex.classList.add('mg-show'); bars();
            $('.mg-t-result', side).innerHTML = '<div class="mg-verdict-box mg-' + (right ? 'ok' : 'danger') + '"><span class="mg-stamp mg-' + (isScam ? 'scam' : 'safe') + '">' + esc(isScam ? d('scam') : d('safe')) + '</span><h3>' + esc(right ? t('right', 'Správne.') : t('wrong', 'Tentoraz nie.')) + '</h3><p>' + esc(isScam ? t('seeFlags', 'Pozrite si zvýraznené varovné znaky pri ukážke.') : t('seeSafe', 'Táto správa je v poriadku. Pri ukážke sú vysvetlené dôvody.')) + '</p></div>' +
              '<button class="mg-btn mg-t-next" type="button">' + esc(i < items.length - 1 ? t('next', 'Ďalšia ukážka') : t('result', 'Zobraziť výsledok')) + '</button>';
            var nx = $('.mg-t-next', side);
            nx.addEventListener('click', function () { if (i < items.length - 1) { i++; show(); w.scrollIntoView({ block: 'start' }); } else done(); });
            nx.focus({ preventScroll: true });
          });
        });
      }
      function done() {
        $$('span', prog).forEach(function (s, k) { s.className = ans[k] ? 'mg-right' : 'mg-wrong'; });
        var pct = Math.round(score / items.length * 100);
        var cls = pct >= 90 ? 'ok' : pct >= 60 ? 'warn' : 'danger';
        stage.innerHTML = '<div class="mg-verdict-box mg-' + cls + '"><p class="mg-eyebrow">' + esc(t('resultTitle', 'Výsledok')) + '</p><p class="mg-t-score">' + score + ' / ' + items.length + '</p><p>' + esc(d(pct >= 90 ? 'result-high' : pct >= 60 ? 'result-mid' : 'result-low')) + '</p></div>';
        side.innerHTML = '<h2>' + esc(t('remember', 'Čo si zapamätať')) + '</h2><div class="mg-prose">' + (tpl ? tpl.innerHTML : '') + '</div><div class="mg-btn-row"><button class="mg-btn mg-t-again" type="button">' + esc(t('again', 'Skúsiť znova')) + '</button></div>';
        $('.mg-t-again', side).addEventListener('click', function () { i = 0; score = 0; ans = []; show(); });
      }
      show();
    });
  }

  /* ---------- Kontrola správy ---------- */
  function initCheckers(scope) {
    $$('.mg-checker', scope).forEach(function (w) {
      if (!once(w, 'checker')) return;
      var qs = $$('.mg-q', w), vals = {}, box = $('.mg-ck-result', w), pin = $('.mg-meter i', w);
      var d = function (k) { return esc(w.getAttribute('data-' + k) || ''); };
      function update() {
        var keys = Object.keys(vals); if (!keys.length) return;
        var sum = 0, max = 0, hard = false;
        keys.forEach(function (k) { sum += vals[k]; });
        qs.forEach(function (q, k) { max += +q.getAttribute('data-w'); if (vals[k] && q.hasAttribute('data-hard')) hard = true; });
        var pct = Math.min(100, Math.round(sum / max * 220)); if (hard) pct = Math.max(pct, 85);
        pin.style.left = pct + '%';
        var lvl = pct >= 60 ? ['danger', 'scam', 'high', t('riskHigh', 'Vysoké riziko')] : pct >= 25 ? ['warn', 'warn', 'mid', t('riskMid', 'Pozor')] : ['ok', 'safe', 'low', t('riskLow', 'Nízke riziko')];
        box.className = 'mg-ck-result mg-verdict-box mg-' + lvl[0];
        box.innerHTML = '<span class="mg-stamp mg-' + lvl[1] + '">' + esc(lvl[3]) + '</span><h3>' + d(lvl[2] + '-title') + '</h3><p>' + d(lvl[2] + '-text') + '</p>';
      }
      qs.forEach(function (q, k) {
        $$('.mg-yn button', q).forEach(function (b) {
          b.addEventListener('click', function () {
            $$('.mg-yn button', q).forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
            b.setAttribute('aria-pressed', 'true');
            vals[k] = b.getAttribute('data-v') === '1' ? +q.getAttribute('data-w') : 0;
            update();
          });
        });
      });
      var reset = $('.mg-ck-reset', w);
      if (reset) reset.addEventListener('click', function () {
        vals = {}; $$('.mg-yn button', w).forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
        pin.style.left = '0%'; box.className = 'mg-ck-result mg-verdict-box'; box.innerHTML = '<p class="mg-muted">' + d('empty') + '</p>';
      });
    });
  }

  /* ---------- Kontrola webovej adresy ---------- */
  var BAD_TLD = ['top', 'xyz', 'tk', 'icu', 'click', 'buzz', 'live', 'shop', 'online', 'site', 'app', 'info', 'cfd', 'sbs'];
  var TWO = ['co.uk', 'com.au', 'org.uk', 'gov.uk'];
  var BRAND = /(posta|banka|bank|slsp|vub|tatra|csob|polic|financ|gov|login|secure|overenie|meta|facebook|google|microsoft)/;
  function initUrl(scope) {
    $$('.mg-url-checker', scope).forEach(function (w) {
      if (!once(w, 'url')) return;
      var inp = $('.mg-url-input', w), out = $('.mg-url-out', w), notesBox = $('.mg-url-notes', w);
      function analyze() {
        var raw = inp.value.trim(), notes = [];
        if (!raw) { out.innerHTML = '<span class="mg-dim">' + esc(t('urlEmpty', 'Sem sa vypíše skutočná adresa.')) + '</span>'; notesBox.innerHTML = ''; return; }
        var s = raw.replace(/^\s*[a-z]+:\/\//i, '');
        var cut = s.search(/[\/?#]/), hostPart = cut > -1 ? s.slice(0, cut) : s, rest = cut > -1 ? s.slice(cut) : '';
        var userinfo = '';
        if (hostPart.indexOf('@') > -1) { userinfo = hostPart.slice(0, hostPart.lastIndexOf('@') + 1); hostPart = hostPart.slice(hostPart.lastIndexOf('@') + 1); notes.push(['danger', t('urlAt', 'Adresa obsahuje znak @. Všetko pred ním prehliadač ignoruje. Je to trik, ako ukázať známe meno a poslať vás inam.')]); }
        var host = hostPart.replace(/:\d+$/, '').toLowerCase();
        var labels = host.split('.').filter(Boolean);
        var n = TWO.indexOf(labels.slice(-2).join('.')) > -1 ? 3 : 2;
        var reg = labels.slice(-n).join('.'), sub = labels.slice(0, -n).join('.');
        var isIP = /^\d{1,3}(\.\d{1,3}){3}$/.test(host);
        if (isIP) { reg = host; sub = ''; notes.push(['danger', t('urlIp', 'Namiesto mena je tu číselná IP adresa. Banky a úrady takéto odkazy neposielajú.')]); }
        out.innerHTML = (userinfo ? '<s class="mg-dim">' + esc(userinfo) + '</s>' : '') + (sub ? '<span class="mg-dim">' + esc(sub) + '.</span>' : '') + '<span class="mg-real">' + esc(reg) + '</span><span class="mg-dim">' + esc(rest) + '</span>';
        if (!isIP && labels.length) {
          notes.unshift(['', t('urlOwner', 'Skutočný vlastník stránky je %s. Rozhoduje posledná časť pred prvou lomkou, nie začiatok adresy.').replace('%s', '<b>' + esc(reg) + '</b>')]);
          if (sub && BRAND.test(sub)) notes.push(['danger', t('urlBrand', 'Na začiatku je známe meno (%1), ale je to len pridaná časť. Stránka patrí %2.').replace('%1', '<b>' + esc(sub) + '</b>').replace('%2', '<b>' + esc(reg) + '</b>')]);
          if (/xn--/.test(host)) notes.push(['danger', t('urlPuny', 'Adresa obsahuje „xn--“. Môže ísť o falošné písmená, ktoré vyzerajú ako bežné.')]);
          var tld = labels[labels.length - 1];
          if (BAD_TLD.indexOf(tld) > -1) notes.push(['warn', t('urlTld', 'Koncovka .%s sa v podvodných kampaniach objavuje často. Sama osebe nie je dôkaz, ale buďte opatrní.').replace('%s', '<b>' + esc(tld) + '</b>')]);
          if ((reg.match(/-/g) || []).length >= 2) notes.push(['warn', t('urlDash', 'Veľa pomlčiek v názve domény je častý znak napodobeniny.')]);
        }
        notesBox.innerHTML = notes.map(function (x) { return '<div class="mg-callout' + (x[0] ? ' mg-' + x[0] : '') + '"><p>' + x[1] + '</p></div>'; }).join('');
      }
      inp.addEventListener('input', analyze);
      $$('[data-url]', w).forEach(function (b) { b.addEventListener('click', function () { inp.value = b.getAttribute('data-url'); analyze(); }); });
      analyze();
    });
  }

  /* ---------- Rozhodovací strom ---------- */
  function initTrees(scope) {
    $$('.mg-tree', scope).forEach(function (w) {
      if (!once(w, 'tree')) return;
      var opts = $('.mg-tree-opts', w), items = $$('.mg-tree-item', w);
      items.forEach(function (it) {
        var btn = $('.mg-tree-btn', it), ans = $('.mg-tree-answer', it);
        ans.hidden = true;
        opts.appendChild(btn);
        btn.addEventListener('click', function () {
          $$('.mg-tree-btn', opts).forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
          btn.setAttribute('aria-pressed', 'true');
          items.forEach(function (x) { $('.mg-tree-answer', x).hidden = x !== it; });
          ans.scrollIntoView({ block: 'start' });
        });
      });
    });
  }

  /* ---------- Kvíz ---------- */
  function initQuizzes(scope) {
    $$('.mg-quiz-wrap', scope).forEach(function (w) {
      if (!once(w, 'quiz')) return;
      var qs = $$('.mg-quiz-q', w), box = $('.mg-quiz', w), i = 0, score = 0;
      var d = function (k) { return w.getAttribute('data-' + k) || ''; };
      if (!qs.length) return;
      function show() {
        var q = qs[i].cloneNode(true), correct = +q.getAttribute('data-correct');
        box.innerHTML = '<p class="mg-eyebrow">' + esc(d('label')) + ' ' + (i + 1) + ' / ' + qs.length + '</p>';
        box.appendChild(q);
        var p = document.createElement('p'); p.className = 'mg-muted'; p.textContent = d('prompt');
        q.insertBefore(p, $('.mg-answers', q));
        $$('.mg-answers button', q).forEach(function (b) {
          b.addEventListener('click', function () {
            var k = +b.getAttribute('data-i');
            $$('.mg-answers button', q).forEach(function (x, j) { x.disabled = true; if (j === correct) x.classList.add('mg-right'); });
            if (k === correct) score++; else b.classList.add('mg-wrong');
            var ex = $('.mg-quiz-ex', q); ex.hidden = false;
            ex.insertAdjacentHTML('afterbegin', '<b>' + esc(k === correct ? t('right', 'Správne.') : t('notQuite', 'Nie celkom.')) + '</b> ');
            ex.insertAdjacentHTML('beforeend', '<br><button class="mg-btn mg-quiz-next" type="button">' + esc(i < qs.length - 1 ? d('next') : t('result', 'Zobraziť výsledok')) + '</button>');
            $('.mg-quiz-next', q).addEventListener('click', function () {
              if (i < qs.length - 1) { i++; show(); return; }
              box.innerHTML = '<p class="mg-eyebrow">' + esc(t('resultTitle', 'Výsledok')) + '</p><p class="mg-t-score">' + score + ' / ' + qs.length + '</p><p class="mg-muted">' + esc(d('result')) + '</p><button class="mg-btn mg-quiz-again" type="button">' + esc(t('again', 'Skúsiť znova')) + '</button>';
              $('.mg-quiz-again', box).addEventListener('click', function () { i = 0; score = 0; show(); });
            });
          });
        });
      }
      show();
    });
  }

  /* ---------- Slovník ---------- */
  function initGlossary(scope) {
    $$('.mg-el-glossary', scope).forEach(function (w) {
      if (!once(w, 'gloss')) return;
      var inp = $('.mg-search-input', w), empty = $('.mg-gloss-empty', w);
      if (!inp) return;
      inp.addEventListener('input', function () {
        var q = inp.value.trim().toLowerCase(), n = 0;
        $$('.mg-gloss details', w).forEach(function (d) { var ok = !q || d.textContent.toLowerCase().indexOf(q) > -1; d.hidden = !ok; if (ok) n++; });
        if (empty) empty.hidden = n > 0;
      });
    });
  }

  /* ---------- Tlač kariet ---------- */
  function initPrint(scope) {
    var framed = true; try { framed = window.self !== window.top; } catch (e) { framed = true; }
    $$('.mg-print-btn', scope).forEach(function (b) {
      if (!once(b, 'print') || framed) return;
      b.hidden = false;
      b.addEventListener('click', function () {
        $$('[data-mg-tags]').forEach(function (x) { scopeEl(x).hidden = false; });
        window.print();
      });
    });
  }

  function init(scope) {
    scope = scope || document;
    initSize(scope); initExamples(scope); initFilters(scope); initTrainers(scope); initCheckers(scope);
    initUrl(scope); initTrees(scope); initQuizzes(scope); initGlossary(scope); initPrint(scope);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(); });
  else init();

  // Živý editor Avada a iné nástroje vkladajú HTML neskôr.
  if ('MutationObserver' in window) {
    var pending = false;
    new MutationObserver(function () {
      if (pending) return; pending = true;
      setTimeout(function () { pending = false; init(); }, 150);
    }).observe(document.documentElement, { childList: true, subtree: true });
  }
  window.mgInit = init;
})();
