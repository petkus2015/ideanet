/* Mediálna gramotnosť — spoločné správanie stránok */
(function () {
  'use strict';
  var root = document.documentElement;
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }
  function $(s, c) { return (c || document).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  /* ---------- Preferencie: téma a veľkosť písma ---------- */
  var savedTheme = store('mg-theme'); if (savedTheme) root.setAttribute('data-theme', savedTheme);
  var savedSize = store('mg-size'); if (savedSize) root.setAttribute('data-size', savedSize);

  /* ---------- Hlavička a pätička ---------- */
  var page = document.body.getAttribute('data-page') || '';
  var NAV = [
    ['podvody', 'podvody.html', 'Podvody'],
    ['trenazer', 'trenazer.html', 'Trenažér'],
    ['overit', 'overit.html', 'Overiť'],
    ['dezinformacie', 'dezinformacie.html', 'Dezinformácie'],
    ['materialy', 'materialy.html', 'Materiály'],
    ['seniori', 'seniori.html', 'Seniori'],
    ['mladi', 'mladi.html', 'Mladí'],
    ['pomoc', 'pomoc.html', 'Stalo sa mi to']
  ];
  // Logo webu: nahrajte oficiálne logo ako assets/logo.png (alebo zmeňte cestu nižšie).
  // Kým súbor neexistuje, zobrazí sa dočasná značka s názvom.
  var LOGO_FILE = 'assets/logo.png';
  var LOGO_ALT = 'Mediálna gramotnosť';
  function logoHtml(withTagline) {
    return '<a class="logo" href="index.html" aria-label="' + LOGO_ALT + ', domov">' +
      '<img class="logo-img" src="' + LOGO_FILE + '" alt="' + LOGO_ALT + '" onerror="this.parentNode.classList.add(\'logo-missing\');this.remove()">' +
      '<span class="logo-alt">' + LOGO + '<span>Mediálna gramotnosť' + (withTagline ? '<small>zastav sa · over si to</small>' : '') + '</span></span></a>';
  }
  var LOGO = '<svg width="34" height="34" viewBox="0 0 36 36" aria-hidden="true"><circle cx="15" cy="15" r="10.5" fill="none" stroke="currentColor" stroke-width="3"/><path d="M23 23 L32 32" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><rect x="8" y="12" width="14" height="6" rx="1" fill="#FFE04A"/></svg>';
  var header = $('#site-header');
  if (header) {
    header.outerHTML =
      '<a class="skip" href="#obsah">Preskočiť na obsah</a>' +
      '<div class="alert-bar"><div class="wrap"><span>Prišli ste o peniaze alebo údaje? <b>Hneď volajte svojej banke</b> a polícii na <b>158</b>.</span><a href="pomoc.html">Čo robiť krok za krokom →</a></div></div>' +
      '<header class="site-header"><nav class="wrap nav" aria-label="Hlavná navigácia">' +
      logoHtml(true) +
      '<ul class="nav-links" id="nav-links">' + NAV.map(function (n) {
        return '<li><a href="' + n[1] + '"' + (n[0] === 'pomoc' ? ' class="help-link"' : '') + (n[0] === page ? ' aria-current="page"' : '') + '>' + n[2] + '</a></li>';
      }).join('') + '</ul>' +
      '<div class="tools">' +
      '<button class="icon-btn" id="size-btn" type="button" aria-label="Zväčšiť písmo">A+</button>' +
      '<button class="icon-btn" id="theme-btn" type="button" aria-label="Prepnúť svetlý a tmavý režim"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg></button>' +
      '<button class="icon-btn menu-btn" id="menu-btn" type="button" aria-label="Menu" aria-expanded="false" aria-controls="nav-links"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>' +
      '</div></nav></header>';
  }
  var footer = $('#site-footer');
  if (footer) {
    footer.outerHTML =
      '<footer class="site-footer"><div class="wrap"><div class="foot-grid">' +
      '<div class="stack">' + logoHtml(false) + '<p class="muted" style="font-size:.93rem;max-width:34ch">Vzdelávací web o podvodoch, dezinformáciách a overovaní informácií. Pre mladých aj seniorov.</p></div>' +
      '<div><h4>Naučiť sa</h4><ul><li><a href="podvody.html">Atlas podvodov</a></li><li><a href="dezinformacie.html">Dezinformácie a AI</a></li><li><a href="overit.html">Ako overiť správu</a></li><li><a href="slovnik.html">Slovník pojmov</a></li><li><a href="materialy.html">Rýchle materiály</a></li></ul></div>' +
      '<div><h4>Vyskúšať</h4><ul><li><a href="trenazer.html">Trenažér podvodov</a></li><li><a href="overit.html#kontrola">Kontrola správy</a></li><li><a href="overit.html#adresa">Kontrola webovej adresy</a></li><li><a href="dezinformacie.html#kviz">Kvíz manipulácie</a></li></ul></div>' +
      '<div><h4>Pomoc</h4><ul><li><a href="pomoc.html">Stalo sa mi to</a></li><li><a href="seniori.html">Pre seniorov</a></li><li><a href="mladi.html">Pre mladých</a></li><li><a href="zdroje.html">Zdroje a metodika</a></li></ul></div>' +
      '</div><div class="foot-bottom"><span>Obsah vychádza z overených zdrojov EÚ a slovenských inštitúcií. Pozri <a href="zdroje.html">Zdroje</a>.</span><span>Tiesňové volanie 112 · Polícia 158</span></div></div></footer>';
  }

  var themeBtn = $('#theme-btn');
  if (themeBtn) themeBtn.addEventListener('click', function () {
    var cur = root.getAttribute('data-theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = cur === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next); store('mg-theme', next);
  });
  var sizeBtn = $('#size-btn');
  function sizeLabel() {
    var s = root.getAttribute('data-size') || 'm';
    sizeBtn.textContent = s === 'xl' ? 'A−' : 'A+';
    sizeBtn.setAttribute('aria-label', s === 'xl' ? 'Vrátiť bežné písmo' : 'Zväčšiť písmo');
  }
  if (sizeBtn) {
    sizeLabel();
    sizeBtn.addEventListener('click', function () {
      var s = root.getAttribute('data-size') || 'm';
      var next = s === 'm' ? 'l' : s === 'l' ? 'xl' : 'm';
      if (next === 'm') root.removeAttribute('data-size'); else root.setAttribute('data-size', next);
      store('mg-size', next); sizeLabel();
    });
  }
  var menuBtn = $('#menu-btn'), links = $('#nav-links');
  if (menuBtn) {
    menuBtn.addEventListener('click', function () {
      var open = links.classList.toggle('open');
      menuBtn.setAttribute('aria-expanded', String(open));
    });
  }

  /* ---------- Ukážky s varovnými znakmi ---------- */
  function initExample(ex) {
    $$('.flag', ex).forEach(function (f, i) { f.setAttribute('data-n', i + 1); });
    $$('.flag-list li', ex).forEach(function (li, i) { li.setAttribute('data-n', i + 1); });
    var btn = $('.flags-btn', ex);
    if (btn && !btn.dataset.bound) {
      btn.dataset.bound = '1';
      btn.addEventListener('click', function () {
        var on = ex.classList.toggle('show');
        btn.setAttribute('aria-pressed', String(on));
        btn.textContent = on ? 'Skryť varovné znaky' : 'Ukázať varovné znaky';
      });
    }
  }
  $$('.example').forEach(initExample);

  /* ---------- Filtre (data-filter na tlačidlách, data-tags na položkách) ---------- */
  $$('[data-filter-group]').forEach(function (group) {
    var target = $(group.getAttribute('data-filter-group'));
    var chips = $$('.chip', group);
    var empty = $(group.getAttribute('data-empty') || '#__none');
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        chips.forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
        c.setAttribute('aria-pressed', 'true');
        var f = c.getAttribute('data-filter'), shown = 0;
        $$('[data-tags]', target).forEach(function (it) {
          var ok = f === 'all' || (' ' + it.getAttribute('data-tags') + ' ').indexOf(' ' + f + ' ') > -1;
          it.hidden = !ok; if (ok) shown++;
        });
        if (empty) empty.hidden = shown > 0;
      });
    });
  });

  /* ---------- Vyhľadávanie v slovníku ---------- */
  var gq = $('#gloss-search');
  if (gq) gq.addEventListener('input', function () {
    var q = gq.value.trim().toLowerCase(), n = 0;
    $$('.gloss details').forEach(function (d) {
      var ok = !q || d.textContent.toLowerCase().indexOf(q) > -1;
      d.hidden = !ok; if (ok) n++;
    });
    $('#gloss-empty').hidden = n > 0;
  });

  /* ---------- Makety pre trenažér ---------- */
  function flagsList(flags) {
    return '<ul class="flag-list">' + flags.map(function (f) { return '<li>' + f + '</li>'; }).join('') + '</ul>';
  }
  var M = {
    sms: function (o) {
      return '<div class="m-phone"><div class="m-phone-head"><div class="m-phone-av">' + esc(o.av || '#') + '</div><div><b>' + o.from + '</b><span>' + (o.sub || 'SMS') + '</span></div></div>' +
        '<div class="m-thread"><div class="m-time">' + (o.time || 'Dnes 09:41') + '</div>' + o.msgs.map(function (m) { return '<div class="bubble' + (m.me ? ' me' : '') + '">' + m.t + '</div>'; }).join('') + '</div></div>';
    },
    wa: function (o) {
      return '<div class="m-phone wa"><div class="m-phone-head"><div class="m-phone-av">' + esc(o.av || '?') + '</div><div><b>' + o.from + '</b><span>' + (o.sub || 'online') + '</span></div></div>' +
        '<div class="m-thread">' + (o.sys ? '<div class="m-sys">' + o.sys + '</div>' : '') + o.msgs.map(function (m) { return '<div class="bubble' + (m.me ? ' me' : '') + '">' + m.t + '</div>'; }).join('') + '</div></div>';
    },
    mail: function (o) {
      return '<div class="mock"><div class="m-mail-top"><span class="m-dot"></span><span class="m-dot"></span><span class="m-dot"></span><span style="margin-left:6px">Doručená pošta</span></div>' +
        '<div class="m-mail"><div class="m-subject">' + o.subject + '</div><div class="m-from"><div class="m-av" style="background:' + (o.color || '#5f6368') + '">' + esc(o.av || '@') + '</div><div><b>' + o.from + '</b><span>' + o.addr + '</span></div></div>' +
        '<div class="m-body">' + o.body + '</div>' + (o.foot ? '<div class="m-foot">' + o.foot + '</div>' : '') + '</div></div>';
    },
    fb: function (o) {
      return '<div class="mock"><div class="m-fb"><div class="m-fb-head"><div class="m-av" style="background:' + (o.color || '#1877f2') + '">' + esc(o.av || 'f') + '</div><div><b>' + o.from + '</b><span>' + o.sub + '</span></div></div>' +
        '<div class="m-fb-text">' + o.text + '</div>' + (o.img ? '<div class="m-fb-img" style="background:' + (o.imgBg || 'linear-gradient(135deg,#1e3a8a,#0f766e)') + '">' + o.img + '</div>' : '') +
        (o.link ? '<div class="m-fb-link"><span>' + o.link[0] + '</span><b>' + o.link[1] + '</b></div>' : '') +
        '<div class="m-fb-stats"><span>' + (o.likes || '') + '</span><span>' + (o.comments || '') + '</span></div>' +
        '<div class="m-fb-actions"><span>Páči sa mi to</span><span>Komentovať</span><span>Zdieľať</span></div></div></div>';
    },
    web: function (o) {
      return '<div class="mock"><div class="m-browser-bar"><span class="m-dot"></span><span class="m-dot"></span><div class="m-url">' + o.url + '</div></div>' + o.body + '</div>';
    }
  };

  /* ---------- Trenažér ---------- */
  var T = [
    { scam: true, kind: 'SMS', html: M.sms({ from: '+212 6 41 xx xx xx', av: '+', msgs: [{ t: 'Slovenska posta: Vas balik nemohol byt doruceny pre <span class="flag">neuplnu adresu</span>. <span class="flag">Doplatte 1,20 EUR</span> a aktualizujte udaje do 24 hod: <span class="flag lnk">posta-sk.dorucenie-balik.top</span>' }] }),
      flags: ['Nečakaný balík a výzva na drobný doplatok. Slovenská pošta poplatky cez SMS nevyberá.', 'Malá suma má znížiť ostražitosť. Cieľom sú údaje z karty a SMS kód, ktorým podvodník pridá vašu kartu do svojho mobilu.', 'Skutočná adresa je <b>dorucenie-balik.top</b>, nie pošta. Číslo odosielateľa je zahraničné.'] },
    { scam: false, kind: 'SMS', html: M.sms({ from: 'Moja banka', av: 'B', msgs: [{ t: 'Platba kartou *4417: 23,40 EUR, LIDL, 30.09. 18:12. Ak ste platbu nevykonali, volajte cislo uvedene na zadnej strane karty.' }] }),
      flags: ['Správa len informuje o platbe, ktorú ste urobili. Nemá odkaz a nič od vás nechce.', 'Odporúča volať číslo z vašej karty, nie číslo v správe. Tak píšu skutočné banky.'] },
    { scam: true, kind: 'E-mail', html: M.mail({ subject: 'Oznámenie o vrátení preplatku dane', from: 'Finančná správa SR', addr: '&lt;<span class="flag">noreply@fs-vratka-online.com</span>&gt;', av: 'FS', color: '#0b6e4f',
        body: '<p>Vážený daňovník,</p><p>po kontrole vášho daňového priznania vám vzniká nárok na vrátenie <span class="flag">preplatku 248,60 €</span>.</p><p>Pre pripísanie sumy <span class="flag">potvrďte údaje platobnej karty do 48 hodín</span>, inak nárok zaniká.</p><span class="m-cta flag">Prevziať preplatok</span>' }),
      flags: ['Adresa odosielateľa nie je z domény štátu (.gov.sk / financnasprava.sk).', 'Nečakané peniaze sú návnada. Štát neposiela preplatky na základe e-mailu.', 'Časový nátlak a žiadosť o kartu. Na prijatie peňazí nikdy netreba zadávať údaje z karty.', 'Tlačidlo vedie mimo oficiálny web. Prihlasujte sa vždy cez adresu, ktorú si napíšete sami.'] },
    { scam: true, kind: 'WhatsApp', html: M.wa({ from: '+44 7700 9xx xxx', av: '?', sub: 'online', sys: 'Toto číslo nie je vo vašich kontaktoch', msgs: [{ t: '<span class="flag">Ahoj mami, toto je moje nové číslo</span>, starý mobil sa mi rozbil 😩' }, { t: 'Potrebujem nutne zaplatiť faktúru, z novej appky to zatiaľ nejde. <span class="flag">Pošleš mi 780 € na tento účet? Dnes ti to vrátim</span>' }, { t: '<span class="flag">Nevolaj, mám slabý signál</span>, píš sem ❤️' }] }),
      flags: ['„Nové číslo“ je klasický začiatok podvodu „syn/dcéra v tiesni“. Polícia SR naň opakovane upozorňuje.', 'Súrna žiadosť o peniaze na cudzí účet.', 'Snaha zabrániť overeniu hlasom. Zavolajte dieťaťu na jeho staré číslo.'] },
    { scam: true, kind: 'Facebook', html: M.fb({ from: 'Ekonomické Správy Dnes', sub: 'Sponzorované · 🌐', av: 'E', color: '#b91c1c',
        text: '<span class="flag">Známy moderátor prekvapil v priamom prenose:</span> „Vďaka tejto platforme zarábam <span class="flag">3 000 € týždenne</span> bez práce.“ Banky chcú, aby ste to nevedeli! <span class="flag">Ponuka platí len dnes.</span>',
        img: '[video: moderátor v štúdiu, logo televízie]', link: ['<span class="flag">smart-invest-ai.app</span>', 'Zaregistrujte sa a začnite s 250 €'], likes: '1,2 tis.', comments: 'Komentáre sú vypnuté' }),
      flags: ['Zneužitie tváre známej osoby. NBS upozorňuje, že podvodníci používajú deepfake videá politikov, športovcov a moderátorov.', 'Sľub vysokého a istého zisku bez rizika neexistuje.', 'Nátlak „len dnes“.', 'Cudzia doména (.app) namiesto webu banky alebo regulovaného obchodníka. Minimálny vklad 250 € je typický.'] },
    { scam: false, kind: 'E-mail', html: M.mail({ subject: 'Rodičovské združenie – štvrtok 17:00', from: 'Mgr. Jana Kováčová', addr: '&lt;kovacova@zs-lipova.edu.sk&gt;', av: 'JK', color: '#7c3aed',
        body: '<p>Dobrý deň,</p><p>pozývam vás na triedne rodičovské združenie 5.B vo štvrtok 9. 10. o 17:00 v triede č. 12. Budeme hovoriť o lyžiarskom kurze.</p><p>Ak nemôžete prísť, stačí mi odpísať.</p><p>S pozdravom<br>Jana Kováčová, triedna učiteľka</p>' }),
      flags: ['Odosielateľa poznáte a adresa sedí so školou.', 'Nežiada peniaze, heslá ani kliknutie na odkaz. Nie je tu nátlak.'] },
    { scam: true, kind: 'Chat bazár', html: M.wa({ from: 'Peter (kupujúci)', av: 'P', sub: 'naposledy online pred 1 min', msgs: [{ t: 'Dobrý deň, kočík ešte máte? Beriem ho <span class="flag">bez zjednávania</span>.' }, { t: 'Som v zahraničí, <span class="flag">pošlem kuriéra</span>. Platbu som už uhradil, prevezmite si ju tu: <span class="flag lnk">dpd-sk.platba-prijem.shop/94812</span>' }, { t: 'Treba tam <span class="flag">zadať číslo karty a kód zo SMS</span>, aby vám prišli peniaze.' }] }),
      flags: ['Kupujúci nezjednáva a ani nechce tovar vidieť.', 'Kuriér zo zahraničia a „už zaplatené“ sú typický scenár, pred ktorým varuje Polícia SR.', 'Odkaz nevedie na stránku kuriéra (skutočná doména je <b>platba-prijem.shop</b>).', 'Na prijatie peňazí netreba kartu ani kód. Zadaním dávate podvodníkovi prístup k svojim peniazom.'] },
    { scam: true, kind: 'Messenger', html: M.wa({ from: 'Zuzka Horváthová', av: 'Z', sub: 'Messenger', msgs: [{ t: '<span class="flag">Pozri, nie si to ty na tomto videu?? 😱</span>' }, { t: '<span class="flag lnk">video-fb.watch-clip.live/v=8812</span>' }] }),
      flags: ['Šokujúca otázka od známeho človeka. Jeho účet je pravdepodobne ukradnutý a správa ide hromadne všetkým priateľom.', 'Odkaz vedie na falošné prihlásenie do Facebooku. Kto sa prihlási, príde o účet. Napíšte Zuzke inou cestou.'] },
    { scam: false, kind: 'SMS', html: '<p class="example-label" style="text-align:center">Situácia: práve sa prihlasujete do svojho e-mailu a stránka si pýta kód.</p>' + M.sms({ from: 'Google', av: 'G', msgs: [{ t: 'G-482913 je váš overovací kód Google. Nikomu ho neposkytujte.' }] }),
      flags: ['Kód ste si vyžiadali vy, práve teraz, na stránke, ktorú ste otvorili sami.', 'Pozor: ak by kód prišiel <b>bez toho, aby ste sa prihlasovali</b>, niekto pozná vaše heslo. Kód nikomu nediktujte a heslo si zmeňte.'] },
    { scam: true, kind: 'Web', html: M.web({ url: '<span class="flag">microsoft-podpora-alert.online</span>/sk/', body: '<div class="m-popup"><b>⚠ VÁŠ POČÍTAČ JE NAPADNUTÝ</b><p><span class="flag">Neodpájajte ani nevypínajte počítač!</span> Boli zistené vírusy, ktoré kradnú bankové údaje.</p><p><span class="flag">Okamžite volajte technickú podporu Microsoft: +421 2 xxx xx xxx</span></p></div>' }),
      flags: ['Adresa nepatrí spoločnosti Microsoft.', 'Strašenie a zákaz vypnúť počítač. Okno sa dá zavrieť a počítač reštartovať.', 'Skutočná firma vám nikdy nenapíše číslo do vyskakovacieho okna. Na telefóne vás „technik“ navedie nainštalovať program na vzdialený prístup.'] },
    { scam: true, kind: 'Telegram', html: M.wa({ from: 'HR Manager – Práca z domu', av: 'HR', sub: 'Telegram', msgs: [{ t: 'Ahoj! Hľadáme brigádnikov 16+. <span class="flag">150 € denne, 1 hodina práce z mobilu</span> 💸' }, { t: 'Úloha: <span class="flag">na tvoj účet prídu platby od klientov, ty ich pošleš ďalej</span> a 10 % si necháš.' }, { t: '<span class="flag">Nikomu o tom nehovor</span>, je to exkluzívna ponuka.' }] }),
      flags: ['Neprimerane vysoký zárobok za nič.', 'Posielanie cudzích peňazí cez svoj účet je pranie špinavých peňazí. Europol varuje, že takto verbujú „peňažné muly“, najmä mladých.', 'Žiadosť o utajenie je vždy varovanie.'] },
    { scam: true, kind: 'E-mail', html: M.mail({ subject: '<span class="flag">Posledná výzva:</span> vaše predplatné bude zrušené', from: 'StreamPlus', addr: '&lt;<span class="flag">billing@streamplus-support-team.net</span>&gt;', av: 'S', color: '#e11d48',
        body: '<p>Dobrý deň zákazník,</p><p>vašu poslednú platbu sa nepodarilo spracovať. <span class="flag">Aktualizujte platobné údaje do 24 hodín</span>, inak bude váš účet zrušený.</p><span class="m-cta">Aktualizovať platbu</span>' }),
      flags: ['Strašenie v predmete.', 'Doména nepatrí službe. Pridané slová ako support, team či secure sú častý trik.', 'Neosobné oslovenie a nátlak na zadanie karty. Skontrolujte to v aplikácii služby, nie cez odkaz.'] }
  ];
  var tr = $('#trainer');
  if (tr) {
    var ti = 0, tScore = 0, answers = [];
    var stage = $('#t-stage'), prog = $('#t-progress'), side = $('#t-side');
    prog.innerHTML = T.map(function () { return '<span></span>'; }).join('');
    function tRender() {
      var it = T[ti];
      $$('span', prog).forEach(function (s, i) { s.className = answers[i] === undefined ? (i === ti ? 'now' : '') : (answers[i] ? 'right' : 'wrong'); });
      stage.innerHTML = '<div class="example" id="t-ex"><div class="example-bar"><span class="example-label">Ukážka ' + (ti + 1) + ' z ' + T.length + ' · ' + it.kind + '</span></div>' + it.html + flagsList(it.flags) + '</div>';
      initExample($('#t-ex'));
      side.innerHTML = '<p class="eyebrow">Otázka ' + (ti + 1) + ' z ' + T.length + '</p><h2 style="font-size:var(--step-2)">Je táto správa podvod?</h2><p class="muted">Pozorne si ju prečítajte. Všímajte si odosielateľa, odkaz a čo od vás chce.</p>' +
        '<div class="t-choice"><button class="btn danger" type="button" data-a="1">Podvod</button><button class="btn ok" type="button" data-a="0">Je v poriadku</button></div><div id="t-feedback" class="t-result" aria-live="polite"></div>';
      $$('.t-choice button', side).forEach(function (b) {
        b.addEventListener('click', function () {
          var said = b.getAttribute('data-a') === '1', right = said === it.scam;
          answers[ti] = right; if (right) tScore++;
          $$('.t-choice button', side).forEach(function (x) { x.disabled = true; x.style.opacity = x === b ? '1' : '.45'; });
          $('#t-ex').classList.add('show');
          $$('span', prog)[ti].className = right ? 'right' : 'wrong';
          $('#t-feedback').innerHTML = '<div class="verdict-box ' + (right ? 'ok' : 'danger') + '"><span class="stamp ' + (it.scam ? 'scam' : 'safe') + '">' + (it.scam ? 'Podvod' : 'V poriadku') + '</span><h3>' + (right ? 'Správne.' : 'Tentoraz nie.') + '</h3><p>' + (it.scam ? 'Pozrite si zvýraznené varovné znaky pri ukážke.' : 'Táto správa je v poriadku. Pri ukážke sú vysvetlené dôvody.') + '</p></div>' +
            '<button class="btn" type="button" id="t-next">' + (ti < T.length - 1 ? 'Ďalšia ukážka' : 'Zobraziť výsledok') + '</button>';
          $('#t-next').addEventListener('click', function () { if (ti < T.length - 1) { ti++; tRender(); tr.scrollIntoView({ block: 'start' }); } else tDone(); });
          $('#t-next').focus({ preventScroll: true });
        });
      });
    }
    function tDone() {
      $$('span', prog).forEach(function (s, i) { s.className = answers[i] ? 'right' : 'wrong'; });
      var pct = Math.round(tScore / T.length * 100);
      var msg = pct >= 90 ? 'Výborne. Podvodníci to s vami budú mať ťažké. Pošlite trenažér rodine.' : pct >= 60 ? 'Dobrý základ. Prejdite si v Atlase podvodov typy, pri ktorých ste sa pomýlili.' : 'Nevadí, presne na to je trenažér. Prejdite si Atlas podvodov a skúste to znova.';
      stage.innerHTML = '<div class="verdict-box ' + (pct >= 90 ? 'ok' : pct >= 60 ? 'warn' : 'danger') + '"><p class="eyebrow">Výsledok</p><p class="t-score">' + tScore + ' z ' + T.length + '</p><p>' + msg + '</p></div>';
      side.innerHTML = '<h2 style="font-size:var(--step-2)">Čo si zapamätať</h2><ul class="prose" style="padding-left:1.2em"><li>Nátlak a strach sú hlavné nástroje podvodníkov.</li><li>Kód zo SMS, PIN ani heslo nikomu nedávajte.</li><li>Na prijatie peňazí nikdy netreba údaje z karty.</li><li>Overujte inou cestou: zavolajte na známe číslo.</li></ul><div class="btn-row"><button class="btn" type="button" id="t-again">Skúsiť znova</button><a class="btn ghost" href="podvody.html">Atlas podvodov</a></div>';
      $('#t-again').addEventListener('click', function () { ti = 0; tScore = 0; answers = []; tRender(); });
    }
    tRender();
  }

  /* ---------- Kontrola správy ---------- */
  var ck = $('#checker');
  if (ck) {
    var vals = {};
    $$('.q', ck).forEach(function (q, i) {
      $$('.yn button', q).forEach(function (b) {
        b.addEventListener('click', function () {
          $$('.yn button', q).forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
          b.setAttribute('aria-pressed', 'true');
          vals[i] = b.getAttribute('data-v') === '1' ? +q.getAttribute('data-w') : 0;
          ckUpdate();
        });
      });
    });
    function ckUpdate() {
      var keys = Object.keys(vals), sum = 0, max = 0, hard = false;
      keys.forEach(function (k) { sum += vals[k]; });
      $$('.q', ck).forEach(function (q, i) { max += +q.getAttribute('data-w'); if (vals[i] && q.hasAttribute('data-hard')) hard = true; });
      var pct = Math.min(100, Math.round(sum / max * 220));
      if (hard) pct = Math.max(pct, 85);
      $('#meter-pin').style.left = pct + '%';
      var box = $('#ck-result');
      if (!keys.length) return;
      if (pct >= 60) box.className = 'verdict-box danger', box.innerHTML = '<span class="stamp scam">Vysoké riziko</span><h3>Takmer určite ide o podvod.</h3><p>Na nič neklikajte, neodpisujte a nič neplaťte. Správu vymažte alebo nahláste. Ak ste už niečo zadali, choďte na <a href="pomoc.html">Stalo sa mi to</a>.</p>';
      else if (pct >= 25) box.className = 'verdict-box warn', box.innerHTML = '<span class="stamp warn">Pozor</span><h3>Niečo tu nesedí.</h3><p>Overte si správu inou cestou: zavolajte na číslo, ktoré poznáte (z karty, zmluvy, oficiálneho webu), alebo sa prihláste cez aplikáciu, nie cez odkaz.</p>';
      else box.className = 'verdict-box ok', box.innerHTML = '<span class="stamp safe">Nízke riziko</span><h3>Zatiaľ nevidíme typické znaky podvodu.</h3><p>Aj tak buďte opatrní. Ak si nie ste istí, overte si to u odosielateľa známou cestou.</p>';
    }
    var reset = $('#ck-reset');
    if (reset) reset.addEventListener('click', function () {
      vals = {}; $$('.yn button', ck).forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
      $('#meter-pin').style.left = '0%'; var box = $('#ck-result'); box.className = 'verdict-box'; box.innerHTML = '<p class="muted">Odpovedzte na otázky a výsledok sa ukáže tu.</p>';
    });
  }

  /* ---------- Kontrola webovej adresy ---------- */
  var ut = $('#url-input');
  if (ut) {
    var BAD_TLD = ['top', 'xyz', 'tk', 'icu', 'click', 'buzz', 'live', 'shop', 'online', 'site', 'app', 'info', 'cfd', 'sbs'];
    var TWO = ['co.uk', 'com.au', 'org.uk', 'gov.uk'];
    function analyze() {
      var raw = ut.value.trim(), out = $('#url-out'), notes = [];
      if (!raw) { out.innerHTML = '<span class="dim">Sem sa vypíše skutočná adresa.</span>'; $('#url-notes').innerHTML = ''; return; }
      var s = raw.replace(/^\s*[a-z]+:\/\//i, '');
      var cut = s.search(/[\/?#]/), hostPart = cut > -1 ? s.slice(0, cut) : s, rest = cut > -1 ? s.slice(cut) : '';
      var userinfo = '';
      if (hostPart.indexOf('@') > -1) { userinfo = hostPart.slice(0, hostPart.lastIndexOf('@') + 1); hostPart = hostPart.slice(hostPart.lastIndexOf('@') + 1); notes.push(['danger', 'Adresa obsahuje znak <b>@</b>. Všetko pred ním prehliadač ignoruje. Je to trik, ako ukázať známe meno a poslať vás inam.']); }
      var host = hostPart.replace(/:\d+$/, '').toLowerCase();
      var labels = host.split('.').filter(Boolean);
      var n = TWO.indexOf(labels.slice(-2).join('.')) > -1 ? 3 : 2;
      var reg = labels.slice(-n).join('.'), sub = labels.slice(0, -n).join('.');
      var isIP = /^\d{1,3}(\.\d{1,3}){3}$/.test(host);
      if (isIP) { reg = host; sub = ''; notes.push(['danger', 'Namiesto mena je tu číselná IP adresa. Banky a úrady takéto odkazy neposielajú.']); }
      out.innerHTML = (userinfo ? '<s class="dim">' + esc(userinfo) + '</s>' : '') + (sub ? '<span class="dim">' + esc(sub) + '.</span>' : '') + '<span class="real">' + esc(reg) + '</span>' + '<span class="dim">' + esc(rest) + '</span>';
      if (!isIP && labels.length) {
        notes.unshift(['info', 'Skutočný vlastník stránky je <b>' + esc(reg) + '</b>. Rozhoduje posledná časť pred prvou lomkou, nie začiatok adresy.']);
        if (sub && /(posta|banka|bank|slsp|vub|tatra|csob|polic|financ|gov|login|secure|overenie|meta|facebook|google|microsoft)/.test(sub)) notes.push(['danger', 'Na začiatku je známe meno (<b>' + esc(sub) + '</b>), ale je to len pridaná časť. Stránka patrí <b>' + esc(reg) + '</b>.']);
        if (/xn--/.test(host)) notes.push(['danger', 'Adresa obsahuje „xn--“. Môže ísť o falošné písmená, ktoré vyzerajú ako bežné.']);
        if (BAD_TLD.indexOf(labels[labels.length - 1]) > -1) notes.push(['warn', 'Koncovka <b>.' + esc(labels[labels.length - 1]) + '</b> sa v podvodných kampaniach objavuje často. Sama osebe nie je dôkaz, ale buďte opatrní.']);
        if ((reg.match(/-/g) || []).length >= 2) notes.push(['warn', 'Veľa pomlčiek v názve domény (napr. posta-sk-balik) je častý znak napodobeniny.']);
      }
      $('#url-notes').innerHTML = notes.map(function (x) { return '<div class="callout ' + (x[0] === 'info' ? '' : x[0]) + '"><p>' + x[1] + '</p></div>'; }).join('');
    }
    ut.addEventListener('input', analyze);
    $$('[data-url]').forEach(function (b) { b.addEventListener('click', function () { ut.value = b.getAttribute('data-url'); analyze(); }); });
    analyze();
  }

  /* ---------- Rozhodovací strom „Stalo sa mi to“ ---------- */
  var tree = $('#tree');
  if (tree) {
    var opts = $$('.tree-opts button', tree);
    opts.forEach(function (b) {
      b.addEventListener('click', function () {
        opts.forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
        b.setAttribute('aria-pressed', 'true');
        $$('.tree-answer').forEach(function (a) { a.hidden = a.id !== b.getAttribute('data-target'); });
        var t = $('#' + b.getAttribute('data-target'));
        if (t) t.scrollIntoView({ block: 'start' });
      });
    });
  }

  /* ---------- Tlač materiálov (len mimo vloženého rámca) ---------- */
  var pb = $('#print-btn');
  if (pb) {
    var framed = true; try { framed = window.self !== window.top; } catch (e) { framed = true; }
    if (!framed) { pb.hidden = false; pb.addEventListener('click', function () { $$('[data-tags]', $('#lessons')).forEach(function (x) { x.hidden = false; }); window.print(); }); }
  }

  /* ---------- Kvíz: manipulačné techniky ---------- */
  var qz = $('#technique-quiz');
  if (qz) {
    var Q = JSON.parse($('#quiz-data').textContent), qi = 0, qs = 0;
    function qRender() {
      var it = Q[qi];
      qz.innerHTML = '<p class="eyebrow">Príspevok ' + (qi + 1) + ' z ' + Q.length + '</p><blockquote style="margin:0;font-family:var(--f-display);font-size:var(--step-1);font-weight:700;line-height:1.35">„' + esc(it.q) + '“</blockquote><p class="muted">Akú techniku manipulácie použil autor?</p><div class="answers">' +
        it.a.map(function (a, i) { return '<button type="button" data-i="' + i + '">' + esc(a) + '</button>'; }).join('') + '</div><p id="qz-ex" aria-live="polite"></p>';
      $$('.answers button', qz).forEach(function (b) {
        b.addEventListener('click', function () {
          var i = +b.getAttribute('data-i');
          $$('.answers button', qz).forEach(function (x, j) { x.disabled = true; if (j === it.r) x.classList.add('right'); });
          if (i === it.r) qs++; else b.classList.add('wrong');
          $('#qz-ex').innerHTML = '<b>' + (i === it.r ? 'Správne. ' : 'Nie celkom. ') + '</b>' + esc(it.e) + '<br><button class="btn" type="button" id="qz-next" style="margin-top:14px">' + (qi < Q.length - 1 ? 'Ďalší príspevok' : 'Výsledok') + '</button>';
          $('#qz-next').addEventListener('click', function () {
            if (qi < Q.length - 1) { qi++; qRender(); return; }
            qz.innerHTML = '<p class="eyebrow">Výsledok</p><p class="t-score">' + qs + ' z ' + Q.length + '</p><p class="muted">Kto pozná techniky manipulácie, ľahšie ich odhalí. Výskum tomu hovorí „mentálne očkovanie“.</p><button class="btn" type="button" id="qz-again">Skúsiť znova</button>';
            $('#qz-again').addEventListener('click', function () { qi = 0; qs = 0; qRender(); });
          });
        });
      });
    }
    qRender();
  }
})();
