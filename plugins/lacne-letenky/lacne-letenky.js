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
 *
 * Blok ukazuje iba ponuky z poslednej úspešnej aktualizácie – nikdy nie vymyslené ceny.
 */
(function () {
  'use strict';

  var FONTS = 'https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;800' +
    '&family=Figtree:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap';
  // základný odtieň "oblohy" na kartách podľa regiónu
  var HUES = { 'Európa': 222, 'Ázia': 338, 'Afrika': 28, 'Amerika': 262, 'Oceánia': 188, 'Svet': 205 };
  var ARROW = '<svg viewBox="0 0 16 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 11 11 5M6 5h5v5"/></svg>';

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

    el.innerHTML = '<div class="ll-grid"><div class="ll-skel"></div><div class="ll-skel"></div><div class="ll-skel"></div></div>';
    return ready.then(function (data) { render(el, data, opts); }, function (err) {
      el.innerHTML = '<div class="ll-empty"><p>Letenky sa nepodarilo načítať (' + esc(err.message) +
        '). Skontrolujte cestu k súboru deals.json v atribúte data-src.</p></div>';
    });
  }

  // Ponuky staršie ako toto sa už nezobrazia (aktualizácia zlyhala viackrát po sebe).
  var MAX_AGE_H = 36;

  function freshDeals(data) {
    if (!data.updatedAt) return [];
    if ((Date.now() - new Date(data.updatedAt).getTime()) / 36e5 > MAX_AGE_H) return [];
    var today = new Date().toLocaleDateString('sv-SE', { timeZone: 'Europe/Vienna' });
    return (data.deals || []).filter(function (d) { return !d.depart || d.depart > today; })
      .sort(function (a, b) { return a.price - b.price; });
  }

  function render(el, data, opts) {
    var deals = freshDeals(data);
    var limit = opts.limit || 12;
    var cities = {};
    (data.origins || []).forEach(function (o) { cities[o.code] = o.city; });

    function trendTag(d) {
      if (!d.prevPrice || d.prevPrice <= d.price) return '';
      return '<span class="ll-tag" data-kind="drop">▼ ' + money(d.prevPrice - d.price, d.currency) + '</span>';
    }
    function datesText(d) {
      return d.depart ? esc(day(d.depart)) + (d['return'] ? ' – ' + esc(day(d['return'])) : '') : 'flexibilný termín';
    }
    function cardHtml(d) {
      var from = cities[d.origin] || d.origin;
      return '<li><a class="ll-card" href="' + esc(d.url) + '" target="_blank" rel="noopener" aria-label="' +
          esc(d.city + ', ' + d.country + ' z ' + from + ' od ' + money(d.price, d.currency) + ' – otvoriť na momondo') + '">' +
        '<div class="ll-art" style="' + hues(d) + '">' + arc(d) +
          '<span class="ll-watermark" aria-hidden="true">' + esc(d.dest) + '</span>' +
          '<div class="ll-tags">' + (d.stops === 0 ? '<span class="ll-tag">Priamy</span>' : '') + trendTag(d) + '</div>' +
        '</div>' +
        '<div class="ll-body">' +
          '<div><span class="ll-route">' + esc(d.origin) + ' → ' + esc(d.dest) + ' · z ' + esc(from) + '</span>' +
            '<p class="ll-city">' + esc(d.city) + '</p><p class="ll-country">' + esc(d.country) + '</p></div>' +
          '<div class="ll-row"><div class="ll-dates">' + datesText(d) + '<small>' + nights(d.nights) +
              (d.stops > 0 ? ' · ' + stopsLabel(d.stops) : '') + '</small></div>' +
            '<div class="ll-price"><small>od</small><strong>' + money(d.price, d.currency) + '</strong></div></div>' +
          '<span class="ll-go">Hľadať let ' + ARROW + '</span>' +
        '</div></a></li>';
    }

    var status = deals.length
      ? '<span class="ll-live">Aktualizované ' + relDay(data.updatedAt) + ' ' + time(data.updatedAt) + '</span>' +
        '<span>ceny z momondo.co.uk</span>'
      : '<span class="ll-live" data-stale="true">Ponuky sa aktualizujú</span>';

    el.innerHTML =
      '<div class="ll-heading">' +
        '<p class="ll-status">' + status + '</p>' +
        '<h2 class="ll-title">' + esc(opts.title || 'Lacné letenky') + '<span>z Viedne a Bratislavy kamkoľvek do sveta</span></h2>' +
      '</div>' +
      (deals.length
        ? '<ul class="ll-grid">' + deals.slice(0, limit).map(cardHtml).join('') + '</ul>' +
          '<p class="ll-foot">Najnižšie ceny za osobu nájdené na <a href="https://www.momondo.co.uk/explore" target="_blank" rel="noopener">momondo.co.uk</a> ' +
            'pri poslednej aktualizácii. Ceny sa menia, pred nákupom ich overte.</p>'
        : '<div class="ll-empty"><p>Práve nemáme aktuálne ponuky. Nové ceny pribudnú pri najbližšej aktualizácii o 7:00, 12:00 alebo 18:00.</p></div>');
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
