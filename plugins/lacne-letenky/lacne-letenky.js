/*!
 * Lacné letenky – plugin s najlacnejšími letenkami z Viedne a Bratislavy kamkoľvek.
 * Dáta: data/deals.json (aktualizuje GitHub Actions 3× denne z momondo.co.uk).
 *
 * Vloženie na stránku:
 *   <link rel="stylesheet" href="plugins/lacne-letenky/lacne-letenky.css">
 *   <div data-lacne-letenky data-src="plugins/lacne-letenky/data/deals.json"></div>
 *   <script src="plugins/lacne-letenky/lacne-letenky.js" defer></script>
 *
 * Alebo ručne: LacneLetenky.mount(element, { src: '…/deals.json', limit: 30 })
 */
(function () {
  'use strict';

  var FONTS = 'https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;800' +
    '&family=Figtree:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap';
  var REGIONS = ['Európa', 'Ázia', 'Afrika', 'Amerika', 'Oceánia', 'Svet'];
  var SLOT_NAMES = { rano: 'Ráno', obed: 'Obed', vecer: 'Večer' };
  var STORE_KEY = 'lacne-letenky:filters';
  var PLANE = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">' +
    '<path d="M21.5 15.5v-2l-8-5V3.2c0-.9-.7-1.7-1.5-1.7s-1.5.8-1.5 1.7v5.3l-8 5v2l8-2.5v5.2l-2 1.5V21l3.5-1 3.5 1v-1.3l-2-1.5V13l8 2.5z"/></svg>';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function money(v, cur) {
    try {
      return new Intl.NumberFormat('sk-SK', { style: 'currency', currency: cur || 'GBP', currencyDisplay: 'narrowSymbol', maximumFractionDigits: 0 }).format(v);
    } catch (e) { return v + ' ' + (cur || ''); }
  }
  function day(iso) {
    if (!iso) return '';
    var d = new Date(iso + 'T12:00:00');
    return d.toLocaleDateString('sk-SK', { weekday: 'short', day: 'numeric', month: 'short' });
  }
  function time(iso) {
    return new Date(iso).toLocaleTimeString('sk-SK', { hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Vienna' });
  }
  function relDay(iso) {
    var d = new Date(iso), now = new Date();
    var fmt = function (x) { return x.toLocaleDateString('sv-SE', { timeZone: 'Europe/Vienna' }); };
    var y = new Date(now.getTime() - 864e5), t = new Date(now.getTime() + 864e5);
    if (fmt(d) === fmt(now)) return 'dnes';
    if (fmt(d) === fmt(y)) return 'včera';
    if (fmt(d) === fmt(t)) return 'zajtra';
    return d.toLocaleDateString('sk-SK', { day: 'numeric', month: 'short', timeZone: 'Europe/Vienna' });
  }
  function nights(n) {
    if (n == null) return 'jednosmerne';
    if (n === 1) return '1 noc';
    if (n >= 2 && n <= 4) return n + ' noci';
    return n + ' nocí';
  }
  function stopsLabel(s) {
    if (s == null) return 'prestupy podľa ponuky';
    if (s === 0) return 'Priamy let';
    if (s === 1) return '1 prestup';
    return s + ' prestupy';
  }
  function load() { try { return JSON.parse(localStorage.getItem(STORE_KEY)) || {}; } catch (e) { return {}; } }
  function save(state) {
    try {
      localStorage.setItem(STORE_KEY, JSON.stringify({ origin: state.origin, region: state.region, sort: state.sort, direct: state.direct }));
    } catch (e) { /* úložisko nie je k dispozícii */ }
  }
  function ensureFonts() {
    if (document.querySelector('link[data-ll-fonts]')) return;
    var l = document.createElement('link');
    l.rel = 'stylesheet'; l.href = FONTS; l.setAttribute('data-ll-fonts', '');
    document.head.appendChild(l);
  }

  function mount(el, opts) {
    opts = opts || {};
    if (opts.fonts !== false) ensureFonts();
    el.classList.add('ll');
    el.setAttribute('role', 'region');
    el.setAttribute('aria-label', 'Lacné letenky z Viedne a Bratislavy');

    var ready = opts.data ? Promise.resolve(opts.data)
      : window.LACNE_LETENKY_DATA ? Promise.resolve(window.LACNE_LETENKY_DATA)
      : fetch(opts.src || 'data/deals.json', { cache: 'no-cache' }).then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        });

    el.innerHTML = '<p class="ll-empty">Načítavam letenky…</p>';
    return ready.then(function (data) { render(el, data, opts); }, function (err) {
      el.innerHTML = '<div class="ll-empty"><p>Letenky sa nepodarilo načítať (' + esc(err.message) +
        '). Skontrolujte cestu k súboru deals.json v atribúte data-src.</p></div>';
    });
  }

  function render(el, data, opts) {
    var deals = (data.deals || []).slice();
    var maxAll = deals.reduce(function (m, d) { return Math.max(m, d.price); }, 0);
    var ceil = Math.max(50, Math.ceil(maxAll / 50) * 50);
    var saved = load();
    var state = {
      origin: saved.origin || 'ALL', region: saved.region || 'ALL', sort: saved.sort || 'price',
      direct: !!saved.direct, q: '', max: ceil
    };
    var cur = (deals[0] && deals[0].currency) || 'GBP';
    var regionsPresent = REGIONS.filter(function (r) { return deals.some(function (d) { return d.region === r; }); });
    var ageH = (Date.now() - new Date(data.updatedAt).getTime()) / 36e5;
    var uid = 'll' + Math.random().toString(36).slice(2, 7);

    var slots = (data.schedule || []).map(function (s) {
      var st = s.id === data.slot ? 'last' : '';
      if (data.nextUpdate && time(data.nextUpdate) === s.time) st = 'next';
      return '<span class="ll-slot" data-state="' + st + '">' + esc(SLOT_NAMES[s.id] || s.id) +
        ' <small>' + esc(s.time) + '</small></span>';
    }).join('');

    var notes = '';
    if (data.sample) {
      notes += '<p class="ll-note"><b>Ukážkové ceny.</b> Skutočné ponuky z momondo.co.uk sa sem doplnia pri najbližšej plánovanej aktualizácii.</p>';
    } else if (ageH > 9) {
      notes += '<p class="ll-note"><b>Ceny môžu byť neaktuálne.</b> Posledná úspešná aktualizácia bola pred ' + Math.round(ageH) + ' h.</p>';
    }
    if (data.errors && data.errors.length) {
      notes += '<p class="ll-note">Pri poslednej aktualizácii sa nenačítalo: ' + esc(data.errors.map(function (e) { return e.split(':')[0]; }).join(', ')) + '.</p>';
    }

    var originBtns = [['ALL', 'Obe letiská', '']].concat((data.origins || []).map(function (o) {
      return [o.code, o.city, o.code];
    })).map(function (o) {
      return '<button type="button" data-origin="' + o[0] + '">' + esc(o[1]) + (o[2] ? ' <code>' + o[2] + '</code>' : '') + '</button>';
    }).join('');

    el.innerHTML =
      '<header class="ll-head">' +
        '<div class="ll-brand"><div class="ll-glyph">' + PLANE + '</div><div>' +
          '<p class="ll-eyebrow">Odlety VIE · BTS → kamkoľvek</p>' +
          '<h2 class="ll-title">' + esc(opts.title || 'Lacné letenky') + '</h2></div></div>' +
        '<div class="ll-status"><span>Aktualizované <b>' + relDay(data.updatedAt) + ' ' + time(data.updatedAt) + '</b>' +
          (data.nextUpdate ? ' · ďalšia ' + relDay(data.nextUpdate) + ' ' + time(data.nextUpdate) : '') + '</span>' +
          '<div class="ll-slots" aria-label="Aktualizácia 3× denne">' + slots + '</div></div>' +
      '</header>' + notes +
      '<form class="ll-controls" autocomplete="off">' +
        '<div class="ll-row"><span class="ll-label">Odkiaľ</span><div class="ll-seg">' + originBtns + '</div></div>' +
        '<div class="ll-row"><span class="ll-label">Kam</span><div class="ll-chips" data-regions></div></div>' +
        '<div class="ll-fields">' +
          '<label class="ll-field"><span class="ll-label">Hľadať</span>' +
            '<input type="search" id="' + uid + '-q" placeholder="Mesto, krajina, kód"></label>' +
          '<label class="ll-field"><span class="ll-label">Max. cena <output id="' + uid + '-out"></output></span>' +
            '<input type="range" id="' + uid + '-max" min="' + Math.max(10, Math.floor((deals[0] ? Math.min.apply(null, deals.map(function (d) { return d.price; })) : 10) / 10) * 10) +
            '" max="' + ceil + '" step="10" value="' + ceil + '"></label>' +
          '<label class="ll-field"><span class="ll-label">Zoradiť</span><select id="' + uid + '-sort">' +
            '<option value="price">Najlacnejšie</option><option value="date">Najskorší odlet</option>' +
            '<option value="nights">Najdlhší pobyt</option><option value="drop">Najviac zlacnené</option></select></label>' +
          '<label class="ll-check"><input type="checkbox" id="' + uid + '-direct"> Len priame lety</label>' +
        '</div>' +
      '</form>' +
      '<div class="ll-summary" aria-live="polite"></div>' +
      '<ol class="ll-board"></ol>' +
      '<footer class="ll-foot">Ceny sú najnižšie nájdené ceny za osobu z <a href="https://www.momondo.co.uk/explore" target="_blank" rel="noopener">momondo.co.uk</a> ' +
        'v čase aktualizácie a môžu sa zmeniť. Cenu vždy overte pred nákupom.</footer>';

    var $ = function (s) { return el.querySelector(s); };
    var form = $('form'), q = $('#' + uid + '-q'), max = $('#' + uid + '-max'), out = $('#' + uid + '-out'),
      sort = $('#' + uid + '-sort'), direct = $('#' + uid + '-direct'), board = $('.ll-board'),
      summary = $('.ll-summary'), regionBox = $('[data-regions]');
    sort.value = state.sort; direct.checked = state.direct;

    function matches(d, skipRegion) {
      if (state.origin !== 'ALL' && d.origin !== state.origin) return false;
      if (!skipRegion && state.region !== 'ALL' && d.region !== state.region) return false;
      if (state.direct && d.stops !== 0) return false;
      if (d.price > state.max) return false;
      if (state.q) {
        var hay = (d.city + ' ' + d.country + ' ' + d.dest + ' ' + d.region).toLowerCase()
          .normalize('NFD').replace(/[̀-ͯ]/g, '');
        if (hay.indexOf(state.q) === -1) return false;
      }
      return true;
    }

    function update() {
      out.textContent = state.max >= ceil ? 'bez limitu' : 'do ' + money(state.max, cur);
      el.querySelectorAll('[data-origin]').forEach(function (b) {
        b.setAttribute('aria-pressed', String(b.getAttribute('data-origin') === state.origin));
      });

      var base = deals.filter(function (d) { return matches(d, true); });
      regionBox.innerHTML = ['ALL'].concat(regionsPresent).map(function (r) {
        var n = r === 'ALL' ? base.length : base.filter(function (d) { return d.region === r; }).length;
        return '<button type="button" class="ll-chip" data-region="' + r + '" aria-pressed="' + (state.region === r) + '">' +
          (r === 'ALL' ? 'Celý svet' : r) + ' <span>' + n + '</span></button>';
      }).join('');

      var list = deals.filter(function (d) { return matches(d, false); });
      list.sort(function (a, b) {
        if (state.sort === 'date') return (a.depart || '9').localeCompare(b.depart || '9') || a.price - b.price;
        if (state.sort === 'nights') return (b.nights || 0) - (a.nights || 0) || a.price - b.price;
        if (state.sort === 'drop') {
          var da = a.prevPrice ? a.prevPrice - a.price : -1e9, db = b.prevPrice ? b.prevPrice - b.price : -1e9;
          return db - da || a.price - b.price;
        }
        return a.price - b.price;
      });
      if (opts.limit) list = list.slice(0, opts.limit);

      var cheapest = list.reduce(function (m, d) { return !m || d.price < m.price ? d : m; }, null);
      var countries = {};
      list.forEach(function (d) { countries[d.countryCode || d.country] = 1; });
      summary.innerHTML = list.length
        ? '<span><strong>' + list.length + '</strong> ' + (list.length === 1 ? 'ponuka' : list.length < 5 ? 'ponuky' : 'ponúk') + '</span>' +
          '<span><strong>' + Object.keys(countries).length + '</strong> krajín</span>' +
          '<span>najlacnejšie <strong>' + money(cheapest.price, cur) + '</strong> · ' + esc(cheapest.city) + '</span>'
        : '';

      if (!list.length) {
        board.innerHTML = '<li class="ll-empty"><p>Týmto filtrom nezodpovedá žiadna letenka.</p>' +
          '<button type="button" data-reset>Zrušiť filtre</button></li>';
        return;
      }
      board.innerHTML = list.map(function (d) {
        var trend = '';
        if (d.prevPrice && d.prevPrice !== d.price) {
          var diff = d.price - d.prevPrice;
          trend = '<span class="ll-trend" data-dir="' + (diff < 0 ? 'down' : 'up') + '">' +
            (diff < 0 ? '▼ ' : '▲ ') + money(Math.abs(diff), cur) + '</span>';
        }
        var from = (data.origins || []).filter(function (o) { return o.code === d.origin; })[0];
        return '<li class="ll-deal" data-top="' + (d === cheapest) + '">' +
          '<div class="ll-route"><span class="ll-iata">' + esc(d.origin) + ' <i>→</i> ' + esc(d.dest) + '</span>' +
            '<span class="ll-from">z ' + esc(from ? from.city : d.origin) + '</span></div>' +
          '<div class="ll-place"><p class="ll-city">' + esc(d.city) + '</p><p class="ll-country">' + esc(d.country) + ' · ' + esc(d.region) + '</p></div>' +
          '<div class="ll-dates">' + (d.depart ? esc(day(d.depart)) + (d['return'] ? ' – ' + esc(day(d['return'])) : '') : 'flexibilný termín') +
            '<small>' + nights(d.nights) + '</small></div>' +
          '<div class="ll-stops" data-direct="' + (d.stops === 0) + '">' + stopsLabel(d.stops) + '</div>' +
          '<div class="ll-price"><span class="ll-amount"><small>od</small>' + money(d.price, d.currency || cur) + '</span>' + trend + '</div>' +
          '<a class="ll-cta" href="' + esc(d.url) + '" target="_blank" rel="noopener" aria-label="Hľadať let ' +
            esc(d.origin + ' – ' + d.city) + ' na momondo">momondo <span aria-hidden="true">↗</span></a>' +
        '</li>';
      }).join('');
    }

    el.addEventListener('click', function (e) {
      var t = e.target.closest('button');
      if (!t || !el.contains(t)) return;
      if (t.hasAttribute('data-origin')) state.origin = t.getAttribute('data-origin');
      else if (t.hasAttribute('data-region')) state.region = t.getAttribute('data-region');
      else if (t.hasAttribute('data-reset')) {
        state.origin = 'ALL'; state.region = 'ALL'; state.direct = false; state.q = ''; state.max = ceil;
        q.value = ''; max.value = ceil; direct.checked = false;
      } else return;
      save(state); update();
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); });
    q.addEventListener('input', function () {
      state.q = q.value.trim().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); update();
    });
    max.addEventListener('input', function () { state.max = +max.value; update(); });
    sort.addEventListener('change', function () { state.sort = sort.value; save(state); update(); });
    direct.addEventListener('change', function () { state.direct = direct.checked; save(state); update(); });

    update();
  }

  function auto() {
    document.querySelectorAll('[data-lacne-letenky]').forEach(function (el) {
      if (el.__ll) return;
      el.__ll = true;
      mount(el, {
        src: el.getAttribute('data-src') || undefined,
        limit: +el.getAttribute('data-limit') || 0,
        title: el.getAttribute('data-title') || undefined,
        fonts: el.getAttribute('data-fonts') !== 'false'
      });
    });
  }

  window.LacneLetenky = { mount: mount };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', auto);
  else auto();
})();
