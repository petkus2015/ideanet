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

  var SITE = 'https://www.momondo.co.uk';
  var ANYWHERE = 'anywhere';
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function isoDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

  // Odkaz na momondo podľa výberu vo vyhľadávaní.
  // Kamkoľvek -> mapa momondo Explore; konkrétne mesto -> vyhľadávanie s flexibilnými dátumami ±3 dni.
  function searchUrl(origins, dest, month) {
    if (dest === ANYWHERE) {
      // Explore berie jedno letisko; pri "oboch" otvoríme Viedeň, Bratislava sa prepne priamo na mape.
      return SITE + '/explore/' + origins.split(',')[0] + '-anywhere';
    }
    var now = new Date(), dep;
    if (month) {
      var p = month.split('-');
      dep = new Date(+p[0], +p[1] - 1, 15);
      if (dep < now) dep = new Date(now.getTime() + 7 * 864e5);
    } else {
      dep = new Date(now.getTime() + 21 * 864e5);
    }
    var ret = new Date(dep.getTime() + 12 * 864e5);
    return SITE + '/flight-search/' + origins + '-' + dest + '/' + isoDate(dep) + '-flexible-3days/' +
      isoDate(ret) + '-flexible-3days?sort=price_a';
  }

  // Ponuky staršie ako toto sa už nezobrazia (aktualizácia zlyhala viackrát po sebe).
  var MAX_AGE_H = 36;

  function isFresh(data) {
    return !!data.updatedAt && (Date.now() - new Date(data.updatedAt).getTime()) / 36e5 <= MAX_AGE_H;
  }
  function upcoming(d) {
    var today = new Date().toLocaleDateString('sv-SE', { timeZone: 'Europe/Vienna' });
    return !d.depart || d.depart > today;
  }
  function freshDeals(data) {
    if (!isFresh(data)) return [];
    return (data.deals || []).filter(upcoming).sort(function (a, b) { return a.price - b.price; });
  }

  function render(el, data, opts) {
    var watch = (data.watch || []).map(function (w) {
      return { name: w.name, searchUrl: w.searchUrl, deal: isFresh(data) && w.deal && upcoming(w.deal) ? w.deal : null };
    });
    var featured = {};
    watch.forEach(function (w) { if (w.deal) featured[w.deal.origin + w.deal.dest] = 1; });
    // sledovaná ponuka má vlastnú kartu, v mriežke sa neopakuje
    var deals = freshDeals(data).filter(function (d) { return !featured[d.origin + d.dest]; });
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

    function watchHtml(w) {
      var d = w.deal;
      if (!d) {
        return '<div class="ll-watch-empty"><span><b>' + esc(w.name) + '</b> – pri poslednej aktualizácii sme nenašli ponuku.</span>' +
          '<a class="ll-more" href="' + esc(w.searchUrl) + '" target="_blank" rel="noopener">Hľadať na momondo →</a></div>';
      }
      var from = cities[d.origin] || d.origin;
      var drop = d.prevPrice && d.prevPrice > d.price
        ? '<span class="ll-drop">▼ ' + money(d.prevPrice - d.price, d.currency) + ' od poslednej aktualizácie</span>' : '';
      return '<a class="ll-feature" href="' + esc(d.url) + '" target="_blank" rel="noopener" aria-label="' +
          esc(w.name + ' z ' + from + ' od ' + money(d.price, d.currency) + '. Otvoriť na momondo') + '">' +
        '<svg class="ll-feature-arc" viewBox="0 0 400 200" preserveAspectRatio="none" aria-hidden="true"><path d="M10 190 C 120 20, 290 10, 390 70"/></svg>' +
        '<div class="ll-feature-main">' +
          '<p class="ll-feature-kicker">Sledujeme pre vás</p>' +
          '<p class="ll-feature-city">' + esc(w.name) + '</p>' +
          '<p class="ll-feature-meta"><span class="ll-route">' + esc(d.origin) + ' ' + PLANE + ' ' + esc(d.dest) + '</span>' +
            '<span>z ' + esc(from) + '</span>' + (stopsLabel(d.stops) ? '<span>' + stopsLabel(d.stops) + '</span>' : '') + '</p>' +
          '<p class="ll-feature-meta">' + CAL + '<span>' + datesText(d) + '</span></p>' +
        '</div>' +
        '<div class="ll-feature-side">' +
          '<small>' + (d['return'] ? 'spiatočná od' : 'od') + '</small>' +
          '<strong>' + money(d.price, d.currency) + '</strong>' + drop +
          '<span class="ll-feature-cta">Pozrieť let ' + ARROW + '</span>' +
        '</div></a>';
    }

    function searchHtml() {
      var months = '<option value="">Kedykoľvek</option>', d = new Date();
      for (var i = 0; i < 7; i++) {
        var m = new Date(d.getFullYear(), d.getMonth() + i, 1);
        var label = m.toLocaleDateString('sk-SK', { month: 'long', year: 'numeric' });
        months += '<option value="' + m.getFullYear() + '-' + pad(m.getMonth() + 1) + '">' + esc(label.charAt(0).toUpperCase() + label.slice(1)) + '</option>';
      }
      var dests = '<option value="' + ANYWHERE + '">Kamkoľvek do sveta</option>' +
        (data.watch || []).map(function (w) {
          return '<option value="' + esc(w.airports.join(',')) + '">' + esc(w.name) + '</option>';
        }).join('');
      return '<form class="ll-search" data-search>' +
        '<label class="ll-field"><span>Odkiaľ</span><select name="from">' +
          '<option value="VIE,BTS">Viedeň a Bratislava</option><option value="VIE">Viedeň (VIE)</option><option value="BTS">Bratislava (BTS)</option>' +
        '</select></label>' +
        '<label class="ll-field"><span>Kam</span><select name="to">' + dests + '</select></label>' +
        '<label class="ll-field" data-when><span>Kedy</span><select name="when">' + months + '</select></label>' +
        '<a class="ll-search-go" href="#" target="_blank" rel="noopener">Hľadať na momondo ' + ARROW + '</a>' +
        '<p class="ll-search-note" data-note hidden>Mapa momondo otvorí ceny z Viedne, Bratislavu prepnete priamo na mape.</p>' +
      '</form>';
    }

    var updated = isFresh(data)
      ? '<p class="ll-updated">Aktualizované <b>' + relDay(data.updatedAt) + ' ' + time(data.updatedAt) + '</b></p>'
      : '<p class="ll-updated" data-stale="true">Ponuky sa aktualizujú</p>';

    el.innerHTML =
      '<div class="ll-head"><div>' +
          '<p class="ll-eyebrow">Lacné letenky</p>' +
          '<h2 class="ll-title">' + esc(opts.title || 'Kam lacno z Viedne a Bratislavy') + '</h2>' +
        '</div>' + updated + '</div>' +
      searchHtml() +
      watch.map(watchHtml).join('') +
      (deals.length
        ? '<ul class="ll-grid">' + deals.slice(0, limit).map(cardHtml).join('') + '</ul>' +
          '<div class="ll-foot"><span>Najnižšie ceny za osobu v eurách z momondo.co.uk' +
            (data.fx ? ', prepočítané kurzom ECB' + (data.fx.date ? ' z ' + esc(data.fx.date) : '') : '') +
            '. Ceny sa menia, pred nákupom ich overte.</span>' +
            '<a class="ll-more" href="https://www.momondo.co.uk/explore" target="_blank" rel="noopener">Všetky destinácie na momondo →</a></div>'
        : '<div class="ll-empty"><b>Práve nemáme aktuálne ponuky</b><span>Nové ceny pribudnú pri najbližšej aktualizácii o 7:00, 12:00 alebo 18:00.</span></div>');

    var form = el.querySelector('[data-search]');
    function syncSearch() {
      var from = form.elements.from.value, to = form.elements.to.value;
      var anywhere = to === ANYWHERE;
      // mapa Explore nemá výber mesiaca – pole "Kedy" pri "Kamkoľvek" skryjeme
      form.querySelector('[data-when]').hidden = anywhere;
      form.querySelector('[data-note]').hidden = !(anywhere && from.indexOf(',') > -1);
      form.querySelector('.ll-search-go').href = searchUrl(from, to, anywhere ? '' : form.elements.when.value);
    }
    form.addEventListener('change', syncSearch);
    form.addEventListener('submit', function (e) { e.preventDefault(); form.querySelector('.ll-search-go').click(); });
    syncSearch();
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
