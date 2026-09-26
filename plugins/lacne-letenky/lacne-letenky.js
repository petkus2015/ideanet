/*!
 * Lacné letenky – plugin s najlacnejšími letenkami z Viedne a Bratislavy kamkoľvek.
 * Dáta: data/deals.json (aktualizuje GitHub Actions 3× denne z momondo.co.uk).
 *
 * Vloženie na stránku:
 *   <link rel="stylesheet" href="plugins/lacne-letenky/lacne-letenky.css">
 *   <div data-lacne-letenky data-src="plugins/lacne-letenky/data/deals.json"></div>
 *   <script src="plugins/lacne-letenky/lacne-letenky.js" defer></script>
 *
 * Alebo ručne: LacneLetenky.mount(element, { src: '…/deals.json', limit: 8 })
 *
 * Blok ukazuje iba ponuky z poslednej úspešnej aktualizácie – nikdy nie vymyslené ceny.
 */
(function () {
  'use strict';

  var FONTS = 'https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700' +
    '&family=JetBrains+Mono:wght@500;600&display=swap';
  var ARROW = '<svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10h12M11 5l5 5-5 5"/></svg>';
  var PLANE = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M21.5 15.5v-2l-8-5V3.2c0-.9-.7-1.7-1.5-1.7s-1.5.8-1.5 1.7v5.3l-8 5v2l8-2.5v5.2l-2 1.5V21l3.5-1 3.5 1v-1.3l-2-1.5V13l8 2.5z" transform="rotate(90 12 12)"/></svg>';
  var CAL = '<svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4.5" width="14" height="12.5" rx="2.5"/><path d="M3 8.5h14M7 2.5v4M13 2.5v4"/></svg>';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function money(v, cur) {
    try {
      return new Intl.NumberFormat('sk-SK', { style: 'currency', currency: cur || 'EUR', currencyDisplay: 'narrowSymbol', maximumFractionDigits: 0 }).format(v);
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

    el.innerHTML = '<div class="ll-grid"><div class="ll-skel"></div><div class="ll-skel"></div><div class="ll-skel"></div><div class="ll-skel"></div></div>';
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
    var limit = opts.limit || 8;
    var cities = {};
    (data.origins || []).forEach(function (o) { cities[o.code] = o.city; });

    function datesText(d) {
      if (!d.depart) return 'Flexibilný termín';
      return esc(day(d.depart)) + (d['return'] ? ' – ' + esc(day(d['return'])) : '') + ' · ' + nights(d.nights);
    }
    function cardHtml(d) {
      var from = cities[d.origin] || d.origin;
      var drop = d.prevPrice && d.prevPrice > d.price
        ? '<span class="ll-drop" title="Zlacnené od poslednej aktualizácie">▼ ' + money(d.prevPrice - d.price, d.currency) + '</span>' : '';
      return '<li><a class="ll-card" href="' + esc(d.url) + '" target="_blank" rel="noopener" aria-label="' +
          esc(d.city + ', ' + d.country + ', let z ' + from + ' od ' + money(d.price, d.currency) + '. Otvoriť na momondo') + '">' +
        '<div class="ll-card-top"><span class="ll-route">' + esc(d.origin) + ' ' + PLANE + ' ' + esc(d.dest) + '</span>' +
          (stopsLabel(d.stops) ? '<span class="ll-chip">' + stopsLabel(d.stops) + '</span>' : '') + '</div>' +
        '<div><p class="ll-city">' + esc(d.city) + '</p><p class="ll-country">' + esc(d.country) + ' · z ' + esc(from) + '</p></div>' +
        '<p class="ll-dates">' + CAL + '<span>' + datesText(d) + '</span></p>' +
        '<div class="ll-card-bottom"><div class="ll-price"><span class="ll-price-label"><small>' + (d['return'] ? 'spiatočná od' : 'od') + '</small>' + drop + '</span>' +
          '<strong>' + money(d.price, d.currency) + '</strong></div>' +
          '<span class="ll-go" aria-hidden="true">' + ARROW + '</span></div>' +
      '</a></li>';
    }

    var updated = deals.length
      ? '<p class="ll-updated">Aktualizované <b>' + relDay(data.updatedAt) + ' ' + time(data.updatedAt) + '</b></p>'
      : '<p class="ll-updated" data-stale="true">Ponuky sa aktualizujú</p>';

    el.innerHTML =
      '<div class="ll-head"><div>' +
          '<p class="ll-eyebrow">Lacné letenky</p>' +
          '<h2 class="ll-title">' + esc(opts.title || 'Kam lacno z Viedne a Bratislavy') + '</h2>' +
        '</div>' + updated + '</div>' +
      (deals.length
        ? '<ul class="ll-grid">' + deals.slice(0, limit).map(cardHtml).join('') + '</ul>' +
          '<div class="ll-foot"><span>Najnižšie ceny za osobu v eurách z momondo.co.uk' +
            (data.fx ? ', prepočítané kurzom ECB' + (data.fx.date ? ' z ' + esc(data.fx.date) : '') : '') +
            '. Ceny sa menia, pred nákupom ich overte.</span>' +
            '<a class="ll-more" href="https://www.momondo.co.uk/explore" target="_blank" rel="noopener">Všetky destinácie na momondo →</a></div>'
        : '<div class="ll-empty"><b>Práve nemáme aktuálne ponuky</b><span>Nové ceny pribudnú pri najbližšej aktualizácii o 7:00, 12:00 alebo 18:00.</span></div>');
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
