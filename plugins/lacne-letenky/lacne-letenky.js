/*!
 * Lacné letenky – plugin s najlacnejšími letenkami z Viedne a Bratislavy kamkoľvek.
 * Dáta: data/deals.json (aktualizuje GitHub Actions 3× denne z momondo.co.uk).
 *
 * Vloženie na stránku:
 *   <link rel="stylesheet" href="plugins/lacne-letenky/lacne-letenky.css">
 *   <div data-lacne-letenky data-src="plugins/lacne-letenky/data/deals.json"></div>
 *   <script src="plugins/lacne-letenky/lacne-letenky.js" defer></script>
 *
 * Alebo ručne: LacneLetenky.mount(element, { src: '…/deals.json', limit: 12 })
 */
(function () {
  'use strict';

  var FONTS = 'https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;800' +
    '&family=Figtree:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap';
  var REGIONS = ['Európa', 'Ázia', 'Afrika', 'Amerika', 'Oceánia', 'Svet'];
  // základný odtieň "oblohy" na kartách podľa regiónu
  var HUES = { 'Európa': 222, 'Ázia': 338, 'Afrika': 28, 'Amerika': 262, 'Oceánia': 188, 'Svet': 205 };
  var BUDGETS = [50, 100, 250, 500];
  var STORE_KEY = 'lacne-letenky:filters';
  var ARROW = '<svg viewBox="0 0 16 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 11 11 5M6 5h5v5"/></svg>';
  var SEARCH = '<svg viewBox="0 0 16 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="7" cy="7" r="4.5"/><path d="m10.5 10.5 3 3"/></svg>';
  var PLANE = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" fill="currentColor"><path d="M21.5 15.5v-2l-8-5V3.2c0-.9-.7-1.7-1.5-1.7s-1.5.8-1.5 1.7v5.3l-8 5v2l8-2.5v5.2l-2 1.5V21l3.5-1 3.5 1v-1.3l-2-1.5V13l8 2.5z"/></svg>';

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
    return new Date(iso + 'T12:00:00').toLocaleDateString('sk-SK', { weekday: 'short', day: 'numeric', month: 'short' });
  }
  function time(iso) {
    return new Date(iso).toLocaleTimeString('sk-SK', { hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Vienna' });
  }
  function relDay(iso) {
    var d = new Date(iso), now = new Date();
    var fmt = function (x) { return x.toLocaleDateString('sv-SE', { timeZone: 'Europe/Vienna' }); };
    if (fmt(d) === fmt(now)) return 'dnes';
    if (fmt(d) === fmt(new Date(now.getTime() - 864e5))) return 'včera';
    if (fmt(d) === fmt(new Date(now.getTime() + 864e5))) return 'zajtra';
    return d.toLocaleDateString('sk-SK', { day: 'numeric', month: 'short', timeZone: 'Europe/Vienna' });
  }
  function nights(n) {
    if (n == null) return 'jednosmerne';
    if (n === 1) return '1 noc';
    if (n >= 2 && n <= 4) return n + ' noci';
    return n + ' nocí';
  }
  function stopsLabel(s) {
    if (s == null) return '';
    if (s === 0) return 'Priamy let';
    if (s === 1) return '1 prestup';
    return s + ' prestupy';
  }
  function plural(n, one, few, many) { return n === 1 ? one : n > 1 && n < 5 ? few : many; }
  function hash(s) { var h = 0; for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) | 0; return Math.abs(h); }
  function hues(d) {
    var h = hash(d.dest), base = HUES[d.region] != null ? HUES[d.region] : 205;
    var h1 = base + (h % 70) - 35;
    return '--h1:' + h1 + ';--h2:' + (h1 + 34 + (h % 20));
  }
  function arc(d) {
    var h = hash(d.origin + d.dest), y1 = 78 + (h % 12), y2 = 30 + (h % 22), cy = 4 + (h % 18);
    return '<svg class="ll-arc" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">' +
      '<path d="M8 ' + y1 + ' Q 48 ' + cy + ' 90 ' + y2 + '"/></svg>';
  }
  function norm(s) { return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
  function load() { try { return JSON.parse(localStorage.getItem(STORE_KEY)) || {}; } catch (e) { return {}; } }
  function save(state) {
    try {
      localStorage.setItem(STORE_KEY, JSON.stringify({ origin: state.origin, region: state.region, sort: state.sort, direct: state.direct, max: state.max }));
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

    el.innerHTML = '<div class="ll-skel"></div><div class="ll-grid"><div class="ll-skel"></div><div class="ll-skel"></div><div class="ll-skel"></div></div>';
    return ready.then(function (data) { render(el, data, opts); }, function (err) {
      el.innerHTML = '<div class="ll-empty"><p>Letenky sa nepodarilo načítať (' + esc(err.message) +
        '). Skontrolujte cestu k súboru deals.json v atribúte data-src.</p></div>';
    });
  }

  function render(el, data, opts) {
    var deals = (data.deals || []).slice();
    var cur = (deals[0] && deals[0].currency) || 'GBP';
    var pageSize = opts.limit || 12;
    var saved = load();
    var state = {
      origin: saved.origin || 'ALL', region: saved.region || 'ALL', sort: saved.sort || 'price',
      direct: !!saved.direct, max: saved.max || 0, q: '', shown: pageSize
    };
    var regionsPresent = REGIONS.filter(function (r) { return deals.some(function (d) { return d.region === r; }); });
    var stale = (Date.now() - new Date(data.updatedAt).getTime()) / 36e5 > 9;
    var cities = {};
    (data.origins || []).forEach(function (o) { cities[o.code] = o.city; });
    var uid = 'll' + Math.random().toString(36).slice(2, 7);

    var note = '';
    if (data.sample) note = '<p class="ll-note"><b>Ukážkové ceny.</b> Skutočné ponuky z momondo.co.uk sa doplnia pri najbližšej aktualizácii.</p>';
    else if (stale) note = '<p class="ll-note"><b>Ceny môžu byť neaktuálne.</b> Posledná aktualizácia: ' + relDay(data.updatedAt) + ' ' + time(data.updatedAt) + '.</p>';

    var originBtns = [['ALL', 'Obe letiská', '']].concat((data.origins || []).map(function (o) {
      return [o.code, o.city, o.code];
    })).map(function (o) {
      return '<button type="button" data-origin="' + o[0] + '">' + esc(o[1]) + (o[2] ? ' <code>' + o[2] + '</code>' : '') + '</button>';
    }).join('');

    el.innerHTML =
      '<div class="ll-top">' +
        '<div class="ll-heading">' +
          '<p class="ll-status"><span class="ll-live" data-stale="' + stale + '">Aktualizované ' + relDay(data.updatedAt) + ' ' + time(data.updatedAt) + '</span>' +
            (data.nextUpdate ? '<span>ďalšie ceny ' + relDay(data.nextUpdate) + ' o ' + time(data.nextUpdate) + '</span>' : '') +
            (data.sample ? '<span class="ll-pill">Ukážka</span>' : '') + '</p>' +
          '<h2 class="ll-title">' + esc(opts.title || 'Lacné letenky') + '<span>z Viedne a Bratislavy kamkoľvek do sveta</span></h2>' +
        '</div>' +
        '<div class="ll-seg" role="group" aria-label="Odletové letisko">' + originBtns + '</div>' +
      '</div>' + note +
      '<div data-hero></div>' +
      '<div class="ll-bar">' +
        '<div class="ll-scroll" data-regions role="group" aria-label="Región"></div>' +
        '<div class="ll-tools">' +
          '<div class="ll-scroll" data-budgets role="group" aria-label="Rozpočet"></div>' +
          '<span class="ll-sep"></span>' +
          '<button type="button" class="ll-chip" data-direct>Len priame</button>' +
          '<select class="ll-sort" id="' + uid + '-sort" aria-label="Zoradiť">' +
            '<option value="price">Najlacnejšie</option><option value="date">Najskorší odlet</option>' +
            '<option value="nights">Najdlhší pobyt</option><option value="drop">Najviac zlacnené</option></select>' +
          '<label class="ll-search">' + SEARCH + '<input type="search" id="' + uid + '-q" placeholder="Kam chcete letieť?" aria-label="Hľadať mesto alebo krajinu"></label>' +
        '</div>' +
      '</div>' +
      '<p class="ll-count" aria-live="polite"></p>' +
      '<ul class="ll-grid"></ul>' +
      '<button type="button" class="ll-more" data-more hidden></button>' +
      '<p class="ll-foot">Najnižšie nájdené ceny za osobu z <a href="https://www.momondo.co.uk/explore" target="_blank" rel="noopener">momondo.co.uk</a>. ' +
        'Ceny sa menia, pred nákupom ich overte. Aktualizácia 3× denne: 7:00, 12:00, 18:00.</p>';

    var $ = function (s) { return el.querySelector(s); };
    var q = $('#' + uid + '-q'), sort = $('#' + uid + '-sort'), grid = $('.ll-grid'), hero = $('[data-hero]'),
      count = $('.ll-count'), more = $('[data-more]');
    sort.value = state.sort;

    function matches(d, skip) {
      if (state.origin !== 'ALL' && d.origin !== state.origin) return false;
      if (skip !== 'region' && state.region !== 'ALL' && d.region !== state.region) return false;
      if (state.direct && d.stops !== 0) return false;
      if (state.max && d.price > state.max) return false;
      if (state.q && norm(d.city + ' ' + d.country + ' ' + d.dest + ' ' + d.region).indexOf(state.q) === -1) return false;
      return true;
    }
    function trendTag(d) {
      if (!d.prevPrice || d.prevPrice === d.price) return '';
      var diff = d.price - d.prevPrice;
      return '<span class="ll-tag" data-kind="' + (diff < 0 ? 'drop' : 'up') + '">' + (diff < 0 ? '▼ ' : '▲ ') + money(Math.abs(diff), cur) + '</span>';
    }
    function datesText(d) {
      return d.depart ? esc(day(d.depart)) + (d['return'] ? ' – ' + esc(day(d['return'])) : '') : 'flexibilný termín';
    }

    function heroHtml(d) {
      return '<a class="ll-hero" href="' + esc(d.url) + '" target="_blank" rel="noopener">' +
        '<div class="ll-hero-art" style="' + hues(d) + '">' + arc(d) +
          '<span class="ll-watermark" aria-hidden="true">' + esc(d.dest) + '</span>' +
          '<span class="ll-hero-label">' + PLANE + ' Najlacnejšie práve teraz</span>' +
          '<div><p class="ll-hero-city">' + esc(d.city) + '</p>' +
            '<p class="ll-hero-sub">' + esc(d.country) + ' · ' + esc(d.region) + '</p></div>' +
          '<div class="ll-hero-route"><span>' + esc(d.origin) + ' → ' + esc(d.dest) + '</span>' +
            (stopsLabel(d.stops) ? '<span>' + stopsLabel(d.stops) + '</span>' : '') +
            '<span>z ' + esc(cities[d.origin] || d.origin) + '</span></div>' +
        '</div>' +
        '<div class="ll-stub">' +
          '<span class="ll-stub-label">' + (d['return'] ? 'Spiatočná od' : 'Jednosmerná od') + '</span>' +
          '<span class="ll-stub-price">' + money(d.price, d.currency || cur) + '</span>' +
          '<span class="ll-stub-dates">' + datesText(d) + '<br>' + nights(d.nights) + '</span>' +
          '<span class="ll-btn">Pozrieť na momondo ' + ARROW + '</span>' +
        '</div></a>';
    }

    function cardHtml(d) {
      return '<li><a class="ll-card" href="' + esc(d.url) + '" target="_blank" rel="noopener" aria-label="' +
          esc(d.city + ', ' + d.country + ' z ' + (cities[d.origin] || d.origin) + ' od ' + money(d.price, d.currency || cur) + ' – otvoriť na momondo') + '">' +
        '<div class="ll-art" style="' + hues(d) + '">' + arc(d) +
          '<span class="ll-watermark" aria-hidden="true">' + esc(d.dest) + '</span>' +
          '<div class="ll-tags">' + (d.stops === 0 ? '<span class="ll-tag">Priamy</span>' : '') + trendTag(d) + '</div>' +
        '</div>' +
        '<div class="ll-body">' +
          '<div><span class="ll-route">' + esc(d.origin) + ' → ' + esc(d.dest) + ' · z ' + esc(cities[d.origin] || d.origin) + '</span>' +
            '<p class="ll-city">' + esc(d.city) + '</p><p class="ll-country">' + esc(d.country) + '</p></div>' +
          '<div class="ll-row"><div class="ll-dates">' + datesText(d) + '<small>' + nights(d.nights) +
              (d.stops > 0 ? ' · ' + stopsLabel(d.stops) : '') + '</small></div>' +
            '<div class="ll-price"><small>od</small><strong>' + money(d.price, d.currency || cur) + '</strong></div></div>' +
          '<span class="ll-go">Hľadať let ' + ARROW + '</span>' +
        '</div></a></li>';
    }

    function update() {
      el.querySelectorAll('[data-origin]').forEach(function (b) {
        b.setAttribute('aria-pressed', String(b.getAttribute('data-origin') === state.origin));
      });
      $('[data-direct]').setAttribute('aria-pressed', String(state.direct));

      var forRegions = deals.filter(function (d) { return matches(d, 'region'); });
      $('[data-regions]').innerHTML = ['ALL'].concat(regionsPresent).map(function (r) {
        var n = r === 'ALL' ? forRegions.length : forRegions.filter(function (d) { return d.region === r; }).length;
        return '<button type="button" class="ll-chip" data-region="' + r + '" aria-pressed="' + (state.region === r) + '">' +
          (r === 'ALL' ? 'Celý svet' : r) + ' <i>' + n + '</i></button>';
      }).join('');
      $('[data-budgets]').innerHTML = [0].concat(BUDGETS).map(function (b) {
        return '<button type="button" class="ll-chip" data-max="' + b + '" aria-pressed="' + (state.max === b) + '">' +
          (b ? 'do ' + money(b, cur) : 'Každá cena') + '</button>';
      }).join('');

      var list = deals.filter(function (d) { return matches(d); });
      list.sort(function (a, b) {
        if (state.sort === 'date') return (a.depart || '9').localeCompare(b.depart || '9') || a.price - b.price;
        if (state.sort === 'nights') return (b.nights || 0) - (a.nights || 0) || a.price - b.price;
        if (state.sort === 'drop') {
          var da = a.prevPrice ? a.prevPrice - a.price : -1e9, db = b.prevPrice ? b.prevPrice - b.price : -1e9;
          return db - da || a.price - b.price;
        }
        return a.price - b.price;
      });

      if (!list.length) {
        hero.innerHTML = '';
        count.textContent = '';
        grid.innerHTML = '<li class="ll-empty"><p>Týmto filtrom nezodpovedá žiadna letenka.</p>' +
          '<button type="button" class="ll-chip" data-reset>Zrušiť filtre</button></li>';
        more.hidden = true;
        return;
      }

      var cheapest = list.reduce(function (m, d) { return d.price < m.price ? d : m; }, list[0]);
      hero.innerHTML = heroHtml(cheapest);
      var rest = list.filter(function (d) { return d !== cheapest; });
      var countries = {};
      list.forEach(function (d) { countries[d.countryCode || d.country] = 1; });
      var nc = Object.keys(countries).length;
      count.innerHTML = '<b>' + list.length + '</b> ' + plural(list.length, 'destinácia', 'destinácie', 'destinácií') +
        ' v <b>' + nc + '</b> ' + plural(nc, 'krajine', 'krajinách', 'krajinách');
      grid.innerHTML = rest.slice(0, state.shown).map(cardHtml).join('');
      var left = rest.length - state.shown;
      more.hidden = left <= 0;
      more.textContent = 'Zobraziť ďalšie (' + Math.max(0, left) + ')';
    }

    el.addEventListener('click', function (e) {
      var t = e.target.closest('button');
      if (!t || !el.contains(t)) return;
      if (t.hasAttribute('data-origin')) state.origin = t.getAttribute('data-origin');
      else if (t.hasAttribute('data-region')) state.region = t.getAttribute('data-region');
      else if (t.hasAttribute('data-max')) state.max = +t.getAttribute('data-max');
      else if (t.hasAttribute('data-direct')) state.direct = !state.direct;
      else if (t.hasAttribute('data-more')) { state.shown += pageSize; update(); return; }
      else if (t.hasAttribute('data-reset')) {
        state.origin = 'ALL'; state.region = 'ALL'; state.direct = false; state.q = ''; state.max = 0; q.value = '';
      } else return;
      state.shown = pageSize;
      save(state); update();
    });
    q.addEventListener('input', function () { state.q = norm(q.value.trim()); state.shown = pageSize; update(); });
    sort.addEventListener('change', function () { state.sort = sort.value; save(state); update(); });

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
