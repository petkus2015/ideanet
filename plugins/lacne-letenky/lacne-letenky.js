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

  var REFRESH = '<svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 10a6.5 6.5 0 1 1-1.9-4.6M16.5 3.5v3.5H13"/></svg>';
  // Ponuky staršie ako toto sa už nezobrazia (aktualizácia zlyhala viackrát po sebe).
  var MAX_AGE_H = 36;
  // Hľadáme lety s odletom najviac 3 mesiace dopredu (rovnako ako scripts/update_deals.py).
  var HORIZON_DAYS = 92;

  function viennaDate(offsetDays) {
    return new Date(Date.now() + (offsetDays || 0) * 864e5).toLocaleDateString('sv-SE', { timeZone: 'Europe/Vienna' });
  }
  function isFresh(data) {
    return !!data.updatedAt && (Date.now() - new Date(data.updatedAt).getTime()) / 36e5 <= MAX_AGE_H;
  }
  function inWindow(d) {
    return !!d.depart && d.depart > viennaDate(0) && d.depart <= viennaDate(HORIZON_DAYS);
  }
  function freshDeals(data) {
    if (!isFresh(data)) return [];
    return (data.deals || []).filter(inWindow).sort(function (a, b) { return a.price - b.price; });
  }

  // Načíta dáta. fresh=true obíde cache prehliadača, aby tlačidlo vždy dostalo najnovší deals.json.
  function loadData(opts, fresh) {
    if (fresh && opts.src) {
      var url = opts.src + (opts.src.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
      return fetch(url, { cache: 'no-store' }).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      });
    }
    if (opts.data) return Promise.resolve(opts.data);
    if (window.LACNE_LETENKY_DATA) return Promise.resolve(window.LACNE_LETENKY_DATA);
    return fetch(opts.src || 'data/deals.json', { cache: 'no-cache' }).then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    });
  }

  function mount(el, opts) {
    opts = opts || {};
    if (opts.fonts !== false) ensureFonts();
    el.classList.add('ll');
    el.setAttribute('role', 'region');
    el.setAttribute('aria-label', 'Lacné letenky z Viedne a Bratislavy');

    el.innerHTML = '<div class="ll-grid"><div class="ll-skel"></div><div class="ll-skel"></div><div class="ll-skel"></div><div class="ll-skel"></div></div>';
    return loadData(opts, false).then(function (data) { render(el, data, opts, ''); }, function (err) {
      el.innerHTML = '<div class="ll-empty"><p>Letenky sa nepodarilo načítať (' + esc(err.message) +
        '). Skontrolujte cestu k súboru deals.json v atribúte data-src.</p></div>';
    });
  }

  function render(el, data, opts, note) {
    var fresh = isFresh(data);
    // Bangkok (a ďalšie sledované mestá): iba spiatočná letenka v najbližších 3 mesiacoch.
    var watch = (data.watch || []).map(function (w) {
      var d = w.deal;
      return {
        name: w.name, searchUrl: w.searchUrl, featured: w.featured !== false,
        deal: fresh && d && d['return'] && inWindow(d) ? d : null
      };
    });
    // featured (Bangkok) = veľká karta; ostatné sledované (Dubaj, Abu Dhabí) sa vždy pridajú medzi karty
    var pinned = watch.filter(function (w) { return !w.featured; });
    var pinnedDeals = pinned.filter(function (w) { return w.deal; })
      .map(function (w) { var d = {}; for (var k in w.deal) d[k] = w.deal[k]; d.city = w.name; return d; });
    var missing = fresh ? pinned.filter(function (w) { return !w.deal; }).map(function (w) { return w.name; }) : [];
    watch = watch.filter(function (w) { return w.featured; });
    // sledované mesto (Bangkok) má vlastnú kartu, jeho letiská sa v mriežke neopakujú
    var watched = {};
    (data.watch || []).forEach(function (w) { (w.airports || []).forEach(function (a) { watched[a] = 1; }); });
    var limit = opts.limit || 8;
    var deals = freshDeals(data).filter(function (d) { return !watched[d.dest]; })
      .slice(0, Math.max(0, limit - pinnedDeals.length))
      .concat(pinnedDeals)
      .sort(function (a, b) { return a.price - b.price; });
    var cities = {};
    (data.origins || []).forEach(function (o) { cities[o.code] = o.city; });

    function datesText(d) {
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
        return '<div class="ll-watch-empty"><span><b>' + esc(w.name) + '</b> – v najbližších 3 mesiacoch sme pri poslednom hľadaní nenašli spiatočnú letenku.</span>' +
          '<a class="ll-more" href="' + esc(w.searchUrl) + '" target="_blank" rel="noopener">Pozrieť na momondo →</a></div>';
      }
      var from = cities[d.origin] || d.origin;
      var drop = d.prevPrice && d.prevPrice > d.price
        ? '<span class="ll-drop">▼ ' + money(d.prevPrice - d.price, d.currency) + ' od posledného hľadania</span>' : '';
      return '<a class="ll-feature" href="' + esc(d.url) + '" target="_blank" rel="noopener" aria-label="' +
          esc(w.name + ', spiatočná letenka z ' + from + ' od ' + money(d.price, d.currency) + '. Otvoriť na momondo') + '">' +
        '<svg class="ll-feature-arc" viewBox="0 0 400 200" preserveAspectRatio="none" aria-hidden="true"><path d="M10 190 C 120 20, 290 10, 390 70"/></svg>' +
        '<div class="ll-feature-main">' +
          '<p class="ll-feature-kicker">Najlacnejšia spiatočná do ' + esc(w.name === 'Bangkok' ? 'Bangkoku' : w.name) + '</p>' +
          '<p class="ll-feature-city">' + esc(w.name) + '</p>' +
          '<p class="ll-feature-meta"><span class="ll-route">' + esc(d.origin) + ' ' + PLANE + ' ' + esc(d.dest) + '</span>' +
            '<span>z ' + esc(from) + '</span>' + (stopsLabel(d.stops) ? '<span>' + stopsLabel(d.stops) + '</span>' : '') + '</p>' +
          '<p class="ll-feature-meta">' + CAL + '<span>' + datesText(d) + '</span></p>' +
        '</div>' +
        '<div class="ll-feature-side">' +
          '<small>spiatočná od</small>' +
          '<strong>' + money(d.price, d.currency) + '</strong>' + drop +
          '<span class="ll-feature-cta">Pozrieť let ' + ARROW + '</span>' +
        '</div></a>';
    }

    var status = fresh
      ? '<p class="ll-updated">Hľadané <b>' + relDay(data.updatedAt) + ' ' + time(data.updatedAt) + '</b>' +
          ' · odlety do 3 mesiacov</p>'
      : '<p class="ll-updated" data-stale="true">Ponuky sa pripravujú</p>';

    el.innerHTML =
      '<div class="ll-head">' +
        '<div><p class="ll-eyebrow">Lacné letenky z Viedne a Bratislavy</p>' +
          '<h2 class="ll-title">' + esc(opts.title || 'Najlacnejšie letenky kamkoľvek') + '</h2>' + status + '</div>' +
        '<button type="button" class="ll-refresh" data-refresh>' + REFRESH + '<span>Vyhľadať lacné letenky</span></button>' +
      '</div>' +
      '<p class="ll-msg" role="status" aria-live="polite"' + (note ? '' : ' hidden') + '>' + esc(note) + '</p>' +
      watch.map(watchHtml).join('') +
      (deals.length
        ? '<ul class="ll-grid">' + deals.map(cardHtml).join('') + '</ul>' +
          (missing.length ? '<p class="ll-foot">' + esc(missing.join(', ')) + ' – v najbližších 3 mesiacoch sme pri poslednom hľadaní nenašli spiatočnú letenku.</p>' : '') +
          '<p class="ll-foot">Najnižšie ceny za osobu v eurách z momondo.co.uk' +
            (data.fx ? ', prepočítané kurzom ECB' + (data.fx.date ? ' z ' + esc(data.fx.date) : '') : '') +
            '. Ceny sa menia, pred nákupom ich overte.</p>'
        : '<div class="ll-empty"><b>Práve nemáme aktuálne ponuky</b><span>Nové ceny pribudnú pri najbližšom hľadaní o 7:00, 12:00 alebo 18:00.</span></div>');

    var btn = el.querySelector('[data-refresh]');
    btn.addEventListener('click', function () {
      if (btn.getAttribute('aria-busy') === 'true') return;
      btn.setAttribute('aria-busy', 'true');
      btn.querySelector('span').textContent = 'Hľadám ponuky…';
      var started = Date.now();
      var done = function (next, msg) {
        // krátke čakanie, aby hľadanie nepôsobilo ako bliknutie
        setTimeout(function () {
          render(el, next, opts, msg);
          var b = el.querySelector('[data-refresh]');
          if (b) b.focus();
        }, Math.max(0, 700 - (Date.now() - started)));
      };
      loadData(opts, true).then(function (next) {
        var isNew = next.updatedAt && next.updatedAt !== data.updatedAt;
        var when = next.updatedAt ? relDay(next.updatedAt) + ' ' + time(next.updatedAt) : '';
        var nextRun = next.nextUpdate ? ' Ďalšie hľadanie na momondo prebehne ' + relDay(next.nextUpdate) + ' o ' + time(next.nextUpdate) + '.' : '';
        done(next, isNew ? 'Našli sme nové ponuky z hľadania ' + when + '.' : 'Máte najnovšie ponuky z hľadania ' + when + '.' + nextRun);
      }, function () {
        done(data, 'Nové ponuky sa teraz nepodarilo načítať, zobrazujeme posledné známe. Skúste to o chvíľu znova.');
      });
    });
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
