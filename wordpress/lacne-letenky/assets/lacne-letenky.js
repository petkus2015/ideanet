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
  var CHEVRON = '<svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8l5 5 5-5"/></svg>';
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

  var SOURCES = { momondo: 'momondo', ryanair: 'Ryanair', wizzair: 'Wizz Air' };
  function sourceName(d) { return SOURCES[d.source] || 'momondo'; }
  var REFRESH = '<svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 10a6.5 6.5 0 1 1-1.9-4.6M16.5 3.5v3.5H13"/></svg>';
  // Ponuky staršie ako toto sa už nezobrazia (aktualizácia zlyhala viackrát po sebe).
  var MAX_AGE_H = 36;
  var LIST_PREVIEW = 3; // koľko riadkov zoznamu Ázia a SAE je vidno pred rozbalením
  var listSeq = 0;
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
      // refresh=1: WordPress plugin spustí živé hľadanie (momondo + Ryanair); statický deals.json ho ignoruje
      var url = opts.src + (opts.src.indexOf('?') > -1 ? '&' : '?') + 'refresh=1&t=' + Date.now();
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
    return loadData(opts, false).then(function (data) {
      render(el, data, opts, '');
      // aktuálne ceny pri každom otvorení stránky (WordPress: živé hľadanie, výsledok platí pár minút)
      if (opts.autoRefresh && opts.src) search(el, data, opts, true);
    }, function (err) {
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
        name: w.name, searchUrl: w.searchUrl, featured: w.featured !== false, list: !!w.list, note: w.note,
        deal: fresh && d && d['return'] && inWindow(d) ? d : null
      };
    });
    // featured (Bangkok) = veľká karta; list (Ázia, SAE) = zoznam pod ňou;
    // ostatné sledované mestá sa vždy pridajú medzi karty
    var listed = watch.filter(function (w) { return !w.featured && w.list; });
    var listId = el.getAttribute('data-ll-list') || 'll-list-' + (++listSeq);
    el.setAttribute('data-ll-list', listId);
    var pinned = watch.filter(function (w) { return !w.featured && !w.list; });
    var pinnedDeals = pinned.filter(function (w) { return w.deal; })
      .map(function (w) { var d = {}; for (var k in w.deal) d[k] = w.deal[k]; d.city = w.name; return d; });
    watch = watch.filter(function (w) { return w.featured; });
    // sledované mesto (Bangkok) má vlastnú kartu, jeho letiská sa v mriežke neopakujú
    var watched = {};
    (data.watch || []).forEach(function (w) { (w.airports || []).forEach(function (a) { watched[a] = 1; }); });
    var limit = opts.limit || 8;
    // výber pod ponukami: všetky / Európa / mimo Európy
    var region = opts.region || 'all';
    var inRegion = function (d) {
      return region === 'all' || (region === 'eu' ? d.region === 'Európa' : d.region !== 'Európa');
    };
    var pinnedIn = pinnedDeals.filter(inRegion);
    var allDeals = freshDeals(data);
    var deals = allDeals.filter(function (d) { return !watched[d.dest] && inRegion(d); })
      .slice(0, Math.max(0, limit - pinnedIn.length))
      .concat(pinnedIn)
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
          esc(d.city + ', ' + d.country + ', let z ' + from + ' od ' + money(d.price, d.currency) + '. Otvoriť na ' + sourceName(d)) + '">' +
        '<div class="ll-card-top"><span class="ll-route">' + esc(d.origin) + ' ' + PLANE + ' ' + esc(d.dest) + '</span>' +
          (stopsLabel(d.stops) ? '<span class="ll-chip">' + stopsLabel(d.stops) + '</span>' : '') + '</div>' +
        '<div><p class="ll-city">' + esc(d.city) + '</p><p class="ll-country">' + esc(d.country) + ' · z ' + esc(from) + '</p></div>' +
        '<p class="ll-dates">' + CAL + '<span>' + datesText(d) + '</span></p>' +
        '<div class="ll-card-bottom"><div class="ll-price"><span class="ll-price-label"><small>' + (d['return'] ? 'spiatočná od' : 'od') + '</small>' + drop + '</span>' +
          '<strong>' + money(d.price, d.currency) + '</strong>' +
          '<span class="ll-source" data-src="' + esc(d.source || 'momondo') + '">cez ' + esc(sourceName(d)) + '</span></div>' +
          '<span class="ll-go" aria-hidden="true">' + ARROW + '</span></div>' +
      '</a></li>';
    }

    function watchHtml(w) {
      var d = w.deal;
      if (!d) {
        return '<div class="ll-watch-empty"><span><b>' + esc(w.name) + '</b> – v najbližších 3 mesiacoch som pri poslednom hľadaní nenašiel spiatočnú letenku.</span>' +
          '<a class="ll-more" href="' + esc(w.searchUrl) + '" target="_blank" rel="noopener">Pozrieť na momondo →</a></div>';
      }
      var from = cities[d.origin] || d.origin;
      var drop = d.prevPrice && d.prevPrice > d.price
        ? '<span class="ll-drop">▼ ' + money(d.prevPrice - d.price, d.currency) + ' od posledného hľadania</span>' : '';
      return '<a class="ll-feature" href="' + esc(d.url) + '" target="_blank" rel="noopener" aria-label="' +
          esc(w.name + ', spiatočná letenka z ' + from + ' od ' + money(d.price, d.currency) + '. Otvoriť na ' + sourceName(d)) + '">' +
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
          '<span class="ll-feature-cta">Kúpiť ' + ARROW + '</span>' +
        '</div></a>';
    }

    function listHtml() {
      if (!listed.length) return '';
      var rows = listed.slice().sort(function (a, b) {
        return (a.deal ? a.deal.price : 1e9) - (b.deal ? b.deal.price : 1e9);
      }).map(function (w, i) {
        var d = w.deal;
        // zoznam je zbalený – ďalšie riadky sa ukážu po kliknutí na „Zobraziť všetky“
        var li = '<li' + (i >= LIST_PREVIEW && !opts.listOpen ? ' hidden' : '') + '>';
        if (!d) {
          return li + '<div class="ll-row is-empty">' +
            '<span class="ll-row-main"><b class="ll-row-name">' + esc(w.name) + '</b>' +
              '<span class="ll-row-sub">' + esc(w.note || '') + '</span></span>' +
            '<span class="ll-row-dates">Bez spiatočnej ponuky do 3 mesiacov</span>' +
            '<span class="ll-row-price"></span>' +
            '<a class="ll-row-go" href="' + esc(w.searchUrl) + '" target="_blank" rel="noopener">Hľadať ' + ARROW + '</a>' +
          '</div></li>';
        }
        var from = cities[d.origin] || d.origin;
        return li + '<a class="ll-row" href="' + esc(d.url) + '" target="_blank" rel="noopener" aria-label="' +
            esc(w.name + ', ' + d.city + ', spiatočná letenka z ' + from + ' od ' + money(d.price, d.currency) + '. Otvoriť na ' + sourceName(d)) + '">' +
          '<span class="ll-row-main"><b class="ll-row-name">' + esc(w.name) + '</b>' +
            '<span class="ll-row-sub">' + esc(d.city) + ' · <span class="ll-route">' + esc(d.origin) + ' ' + PLANE + ' ' + esc(d.dest) + '</span>' +
            (stopsLabel(d.stops) ? ' · ' + stopsLabel(d.stops) : '') + '</span></span>' +
          '<span class="ll-row-dates">' + CAL + '<span>' + datesText(d) + '</span></span>' +
          '<span class="ll-row-price"><small>od</small><strong>' + money(d.price, d.currency) + '</strong>' +
            '<em class="ll-source" data-src="' + esc(d.source || 'momondo') + '">cez ' + esc(sourceName(d)) + '</em></span>' +
          '<span class="ll-row-go">Kúpiť ' + ARROW + '</span>' +
        '</a></li>';
      }).join('');
      var more = listed.length - LIST_PREVIEW;
      return '<section class="ll-list-wrap" aria-label="Ázia a SAE">' +
        '<h3 class="ll-list-title">Ázia a SAE – najlacnejšie spiatočné letenky</h3>' +
        '<ul class="ll-list" id="' + listId + '">' + rows + '</ul>' +
        (more > 0
          ? '<button type="button" class="ll-list-toggle" data-list-toggle aria-controls="' + listId + '" aria-expanded="' + !!opts.listOpen + '">' +
              '<span>' + (opts.listOpen ? 'Zobraziť menej' : 'Zobraziť všetky (' + listed.length + ')') + '</span>' + CHEVRON + '</button>'
          : '') +
        '</section>';
    }

    var regions = [['all', 'Všetky'], ['eu', 'Európa'], ['world', 'Mimo Európy']];
    var hasAny = allDeals.length > 0;

    // Blok začína rovno ponukami; nadpis sa ukáže, len ak ho web zadá (data-title / Nadpis v nastaveniach).
    el.innerHTML =
      (opts.title ? '<h2 class="ll-title">' + esc(opts.title) + '</h2>' : '') +
      watch.map(watchHtml).join('') +
      (fresh ? listHtml() : '') +
      (deals.length
        ? '<div class="ll-rail">' +
            '<ul class="ll-grid" tabindex="0" aria-label="Lacné letenky">' + deals.map(cardHtml).join('') + '</ul>' +
            '<div class="ll-navbar">' +
              '<button type="button" class="ll-nav" data-nav="-1" aria-label="Predchádzajúce ponuky" disabled>' + ARROW + '</button>' +
              '<button type="button" class="ll-nav" data-nav="1" aria-label="Ďalšie ponuky">' + ARROW + '</button>' +
            '</div>' +
          '</div>'
        : hasAny
          ? '<div class="ll-empty"><b>' + (region === 'eu' ? 'V Európe' : 'Mimo Európy') + ' som teraz nenašiel ponuky</b><span>Skúste inú oblasť alebo nové hľadanie.</span></div>'
          : '<div class="ll-empty"><b>Práve nemáme aktuálne ponuky</b><span>Nové ceny pribudnú pri najbližšom hľadaní o 7:00, 12:00 alebo 18:00.</span></div>') +
      (hasAny
        ? '<div class="ll-filter" role="group" aria-label="Oblasť">' + regions.map(function (r) {
            return '<button type="button" data-region="' + r[0] + '" aria-pressed="' + (region === r[0]) + '">' + r[1] + '</button>';
          }).join('') + '</div>'
        : '') +
      '<div class="ll-actions">' +
        '<button type="button" class="ll-refresh" data-refresh>' + REFRESH + '<span>Vyhľadaj aktuálne lacné letenky</span></button>' +
        '<p class="ll-msg" role="status" aria-live="polite"' + (note ? '' : ' hidden') + '>' + esc(note) + '</p>' +
      '</div>' +
      (hasAny
        ? '<p class="ll-foot">Porovnávam momondo.co.uk, ryanair.com a wizzair.com a ukazujem najnižšiu cenu za osobu v eurách' +
            (data.fx ? ', prepočítané kurzom ECB' + (data.fx.date ? ' z ' + esc(data.fx.date) : '') : '') +
            (fresh ? '. Hľadané ' + relDay(data.updatedAt) + ' ' + time(data.updatedAt) : '') +
            '. Ceny sa menia, pred nákupom ich overte.</p>'
        : '');

    // rozbalenie / zbalenie zoznamu Ázia a SAE
    var toggle = el.querySelector('[data-list-toggle]');
    if (toggle) {
      toggle.addEventListener('click', function () {
        opts.listOpen = !opts.listOpen;
        Array.prototype.forEach.call(el.querySelectorAll('.ll-list > li'), function (li, i) {
          li.hidden = i >= LIST_PREVIEW && !opts.listOpen;
        });
        toggle.setAttribute('aria-expanded', String(opts.listOpen));
        toggle.firstChild.textContent = opts.listOpen ? 'Zobraziť menej' : 'Zobraziť všetky (' + listed.length + ')';
      });
    }

    // výber oblasti – prekreslí ponuky bez nového hľadania
    Array.prototype.forEach.call(el.querySelectorAll('[data-region]'), function (b) {
      b.addEventListener('click', function () {
        opts.region = b.getAttribute('data-region');
        render(el, data, opts, '');
        var again = el.querySelector('[data-region="' + opts.region + '"]');
        if (again) again.focus();
      });
    });

    // šípky posúvača (myš na počítači); prstom sa posúva priamo
    var grid = el.querySelector('.ll-grid');
    if (grid) {
      var navs = el.querySelectorAll('[data-nav]');
      var navbar = el.querySelector('.ll-navbar');
      var syncNav = function () {
        var max = grid.scrollWidth - grid.clientWidth - 2;
        navs[0].disabled = grid.scrollLeft <= 2;
        navs[1].disabled = grid.scrollLeft >= max;
        navbar.hidden = max <= 0; // všetko sa zmestí – šípky netreba
      };
      Array.prototype.forEach.call(navs, function (b) {
        b.addEventListener('click', function () {
          var card = grid.querySelector('li');
          var step = card ? card.getBoundingClientRect().width + parseFloat(getComputedStyle(grid).columnGap || 0) : grid.clientWidth / 1.5;
          grid.scrollBy({ left: step * +b.getAttribute('data-nav'), behavior: 'smooth' });
        });
      });
      grid.addEventListener('scroll', syncNav, { passive: true });
      syncNav();
    }

    var btn = el.querySelector('[data-refresh]');
    btn.addEventListener('click', function () { search(el, data, opts, false); });
  }

  // Nové hľadanie: po kliknutí na tlačidlo, alebo automaticky hneď po načítaní stránky (auto = true).
  // Pri automatickom hľadaní sa nič neruší – kým beží, blok ukazuje posledné známe ceny.
  function search(el, data, opts, auto, retry) {
    var btn = el.querySelector('[data-refresh]');
    if (!btn || btn.getAttribute('aria-busy') === 'true') return;
    btn.setAttribute('aria-busy', 'true');
    btn.querySelector('span').textContent = auto ? 'Načítavam aktuálne ceny…' : 'Hľadám najlacnejšie letenky…';
    var started = Date.now();
    var done = function (next, msg) {
      // krátke čakanie, aby hľadanie nepôsobilo ako bliknutie
      setTimeout(function () {
        var grid = el.querySelector('.ll-grid');
        var left = grid ? grid.scrollLeft : 0;
        render(el, next, opts, msg);
        var g = el.querySelector('.ll-grid');
        if (g && left) g.scrollLeft = left;
        var b = el.querySelector('[data-refresh]');
        if (b && !auto) b.focus();
      }, auto ? 0 : Math.max(0, 700 - (Date.now() - started)));
    };
    loadData(opts, true).then(function (next) {
      if (!next || !next.updatedAt) {
        done(next && next.updatedAt ? next : data, auto ? '' : 'Ponuky sa ešte pripravujú. Skúste to o chvíľu znova.');
        return;
      }
      // WordPress: výsledok živého hľadania
      if (next.liveBusy) {
        if (auto && !retry) {
          // hľadanie práve spustil iný návštevník – o chvíľu si vezmem jeho výsledok
          setTimeout(function () { btn.removeAttribute('aria-busy'); search(el, data, opts, true, true); }, 6000);
          return;
        }
        done(auto ? data : next, auto ? '' : 'Hľadanie práve prebieha pre iného návštevníka. Skúste to o pár sekúnd.');
        return;
      }
      if (next.liveError) {
        done(next, auto ? '' : 'Nové hľadanie sa teraz nepodarilo, zdroje neodpovedali. Zobrazujem ponuky z ' +
          relDay(next.updatedAt) + ' ' + time(next.updatedAt) + '. Skúste to o chvíľu znova.');
        return;
      }
      if (next.live) {
        done(next, next.liveAgeMin > 0
          ? 'Ponuky sú aktuálne – vyhľadal som ich pred ' + next.liveAgeMin + ' min (' + time(next.updatedAt) + ').'
          : 'Hotovo – najlacnejšie letenky som vyhľadal práve teraz (' + time(next.updatedAt) + ').');
        return;
      }
      if (auto) { done(next, ''); return; }
      var isNew = next.updatedAt && next.updatedAt !== data.updatedAt;
      var when = next.updatedAt ? relDay(next.updatedAt) + ' ' + time(next.updatedAt) : '';
      var nextRun = next.nextUpdate && new Date(next.nextUpdate) > new Date() ? ' Ďalšie hľadanie prebehne ' + relDay(next.nextUpdate) + ' o ' + time(next.nextUpdate) + '.' : '';
      done(next, isNew ? 'Našiel som nové ponuky z hľadania ' + when + '.' : 'Máte najnovšie ponuky z hľadania ' + when + '.' + nextRun);
    }, function () {
      done(data, auto ? '' : 'Nové ponuky sa teraz nepodarilo načítať, zobrazujem posledné známe. Skúste to o chvíľu znova.');
    });
  }

  function auto() {
    document.querySelectorAll('[data-lacne-letenky]').forEach(function (el) {
      if (el.__ll) return;
      el.__ll = true;
      // WordPress plugin vkladá počiatočné dáta priamo do stránky – blok sa ukáže bez čakania
      var initial = null, init = el.querySelector('script[data-ll-initial]');
      if (init) { try { initial = JSON.parse(init.textContent); } catch (e) { initial = null; } }
      if (el.getAttribute('data-font') === 'inherit') el.classList.add('ll-inherit-font');
      mount(el, {
        data: initial || undefined,
        src: el.getAttribute('data-src') || undefined,
        limit: +el.getAttribute('data-limit') || 0,
        title: el.getAttribute('data-title') || undefined,
        fonts: el.getAttribute('data-fonts') !== 'false',
        autoRefresh: el.hasAttribute('data-autoload')
      });
    });
  }

  window.LacneLetenky = { mount: mount };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', auto);
  else auto();
})();
