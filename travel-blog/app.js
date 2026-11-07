'use strict';
/* Adventuro — cestovateľský blog (bez závislostí).
   Téma: auto (podľa času) / svetlá / tmavá. Uložené články: localStorage (zoznam) + Cache API (obsah, offline). */

const $ = (s, r = document) => r.querySelector(s);
const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const store = {
  get(k, d) { try { const v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch { return d; } },
  set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch {} }
};

/* ---------- Obsah ---------- */
const CATS = [
  { id: 'all', label: 'Všetko', icon: '✦' },
  { id: 'plaze', label: 'Pláže', icon: '🏖' },
  { id: 'hory', label: 'Hory', icon: '⛰' },
  { id: 'lesy', label: 'Lesy', icon: '🌲' },
  { id: 'pusta', label: 'Púšť', icon: '🏜' },
  { id: 'mesta', label: 'Mestá', icon: '🏛' }
];
const ARTICLES = [
  { id: 'marinha', cat: 'plaze', scene: 'cliffs', title: 'Praia da Marinha', place: 'Lagoa, Algarve, Portugalsko', min: 6, date: '2026-10-28', featured: true,
    lead: 'Zlaté útesy, tyrkysová voda a ticho, ktoré v auguste nenájdete. Prečo je október v Algarve najlepší mesiac.',
    body: ['Na vrchol schodiska nad Praiou da Marinha sa vyplatí prísť ráno, keď slnko ešte nevytiahlo z útesov tú oranžovú. Dole pod vami sa vlny lámu o skalné oblúky a v čistej vode vidno dno aj z desiatich metrov.', ['q', 'Najlepšia fotka sa nerobí z pláže, ale z útesovej cesty nad ňou.'], ['h', 'Čo si zbaliť'], ['t', ['Pevné topánky na útesovú cestu (7 km do Benagil)', 'Vodu — kiosky sú iba pri parkovisku', 'Šnorchel, voda má v októbri ~21 °C']], 'Z pláže vedie cesta Passadiços cez útesy až k jaskyni Benagil. Vyrazte skôr ako autobusy s turistami — okolo desiatej je tu rušno, o ôsmej je to vaša pláž.'] },
  { id: 'navagio', cat: 'plaze', scene: 'bay', title: 'Navagio Beach', place: 'Zakynthos, Grécko', min: 5, date: '2026-09-14',
    lead: 'Vrak lode, biely piesok a smaragdová zátoka. Ako sa dostať k slávnej pláži bez davov.',
    body: ['Navagio je dostupná iba loďou, no najkrajší pohľad je zhora — z vyhliadky nad útesom. Obe verzie majú svoje čaro.', ['h', 'Tip'], ['t', ['Lode odchádzajú z Porto Vromi, nie z Zakynthos Town', 'Príďte pred 10:00 alebo po 16:00', 'Kúpanie pod útesom: pozor na padajúce kamene']], 'Večer zo Zakynthosu stojí za to ostať na západnej strane — západy slnka sú tu legendárne.'] },
  { id: 'reynisfjara', cat: 'plaze', scene: 'dark', title: 'Reynisfjara', place: 'Južné pobrežie, Island', min: 7, date: '2026-08-02',
    lead: 'Čierny piesok, bazaltové stĺpy a vlny, ktoré si nedovolia podceniť. Praktický sprievodca.',
    body: ['Čierna pláž pri Víku vyzerá ako iná planéta. Bazaltové stĺpy Hálsanefshellir pripomínajú organy a vlny tu majú ozajstnú silu.', ['q', 'Nikdy neotáčajte chrbát oceánu.'], 'Počas leta tu zostáva svetlo do polnoci, v zime sa oplatí prísť na polárnu žiaru.'] },
  { id: 'dolomity', cat: 'hory', scene: 'alps', title: 'Tri zuby Lavaredo', place: 'Dolomity, Taliansko', min: 9, date: '2026-07-19',
    lead: 'Okruh okolo Tre Cime di Lavaredo: 10 km, 400 výškových metrov a pohľady, ktoré nezabudnete.',
    body: ['Okruh začína na parkovisku pri Auronzo hut. Cesta vedie okolo útulní Lavaredo a Locatelli, odkiaľ je najkrajší pohľad na severnú stenu.', ['h', 'Praktické'], ['t', ['Mýto na cestu: ~30 €', 'Najlepšie svetlo: skoro ráno', 'Na útulni sa dá najesť a prespať']], 'Počasie sa tu mení za pol hodiny, preto vždy berte nepremokavú vrstvu.'] },
  { id: 'tatry', cat: 'hory', scene: 'alps', title: 'Rysy za úsvitu', place: 'Vysoké Tatry, Slovensko', min: 8, date: '2026-06-30',
    lead: 'Najvyššie dostupná tatranská turistika bez lezenia. Kedy vyraziť a čo si nezabudnúť.',
    body: ['Z Popradského plesa do sedla trvá prístup asi štyri hodiny. Reťaze v poslednom úseku vyžadujú pokoj, nie lezeckú techniku.', 'Chata pod Rysmi je otvorená celoročne; v sezóne rezervujte vopred.'] },
  { id: 'piramidy', cat: 'pusta', scene: 'desert', title: 'Egyptské pyramídy', place: 'Gíza, Egypt', min: 10, date: '2026-05-11',
    lead: 'Ako zažiť Gízu ráno, ako sa nenechať obabrať a prečo sa oplatí platiť za vstup do Chufuovej pyramídy.',
    body: ['Pyramída je štruktúra, ktorej viditeľné plochy sú trojuholníkové a zbiehajú sa do bodu. Ale žiadny opis nepripraví na pocit stáť pri jej úpätí.', ['h', 'Ako na to'], ['t', ['Príďte na otvorenie o 8:00', 'Dohodnite si cenu za ťavu vopred', 'Najlepší výhľad: Panoramic Point']], 'Zvyšok dňa venujte Veľkému egyptskému múzeu, ktoré je na dohľad.'] },
  { id: 'petra', cat: 'pusta', scene: 'ruins', title: 'Petra za súmraku', place: 'Wadi Musa, Jordánsko', min: 8, date: '2026-04-03',
    lead: 'Ružové mesto vytesané do skaly. Najlepší čas na návštevu a ako sa vyhnúť tlačenici.',
    body: ['Siq — úzky kaňon dlhý kilometer — je najlepším úvodom. Keď sa na konci objaví Pokladnica, naozaj zastane dych.', 'Vstupenka na dva dni sa oplatí, Petra sa nedá stihnúť za jedno popoludnie.'] },
  { id: 'borneo', cat: 'lesy', scene: 'forest', title: 'Dažďový prales na Borneu', place: 'Danum Valley, Malajzia', min: 11, date: '2026-03-15',
    lead: 'Päť dní v jednom z najstarších dažďových pralesov na svete. Čo čakať a ako sa pripraviť.',
    body: ['Svitanie v pralese znie ako orchester — gibony, vtáky a hmyz sa striedajú bez prestania.', ['q', 'Tichý prales neexistuje. Existuje len prales, ktorý ešte nepočúvate.'], 'Rátajte s vlhkosťou nad 90 % a s tým, že všetko, čo máte, bude mokré.'] },
  { id: 'kyoto', cat: 'mesta', scene: 'city', title: 'Kjóto bez davov', place: 'Kjóto, Japonsko', min: 7, date: '2026-02-20',
    lead: 'Fushimi Inari o šiestej ráno, tichý Arashiyama a čajovne, o ktorých turisti nevedia.',
    body: ['Ak chcete vidieť Kjóto také, aké bolo, vstaňte skôr. Brány torii na Fushimi Inari sú o šiestej prázdne a svetlo je mäkké.', ['t', ['Kúpte si IC kartu na MHD', 'Obedy vo všedný deň sú lacnejšie', 'Rezervujte čajový obrad vopred']]] }
];
const byId = id => ARTICLES.find(a => a.id === id);

/* ---------- Ilustrácie (SVG, bez externých obrázkov) ---------- */
const PAL = {
  cliffs: ['#6fb8ec', '#e9f1ff', '#e09a4a', '#c97a32', '#2ba3c4'],
  bay: ['#8fd4ff', '#fff5e0', '#5db3a8', '#e8dcc2', '#18b6c9'],
  dark: ['#e6a469', '#554c7a', '#2d2d3a', '#16161e', '#1f3a58'],
  alps: ['#7aa7ff', '#ffd9b2', '#5f7aa8', '#3b4c73', '#1c2b45'],
  desert: ['#f2a65a', '#ffe0a8', '#d9823d', '#b9612a', '#8c4420'],
  ruins: ['#e8b08a', '#ffe6cf', '#c9724a', '#a24f33', '#6e3322'],
  forest: ['#3f7d5a', '#bfe3a2', '#2b6a44', '#174d30', '#0e3320'],
  city: ['#ff9aa8', '#ffe2c4', '#7a5c9e', '#4a3770', '#2a1f4a']
};
function scene(type, id = '') {
  const p = PAL[type] || PAL.alps, u = 'g' + id + type, h = [...id].reduce((a, c) => a + c.charCodeAt(0), 0);
  const j = n => (h * (n + 3)) % 30;
  const peaks = `M0 70 L${60 + j(1)} ${34 + j(2) / 3} L${110 + j(3)} 62 L${170 + j(4)} ${28 + j(5) / 3} L240 66 L300 ${40 + j(6) / 3} L400 62 V120 H0Z`;
  let g = '';
  if (type === 'desert' || type === 'ruins') {
    g = `<path d="M0 78 Q80 62 160 76 T400 70 V120 H0Z" fill="${p[2]}"/>
         <path d="M70 76 L112 38 L154 76Z M150 76 L180 50 L210 76Z M230 76 L264 46 L298 76Z" fill="${p[3]}"/>
         <path d="M0 96 Q120 80 240 94 T400 90 V120 H0Z" fill="${p[4]}"/>`;
  } else if (type === 'forest') {
    const t = (x, s) => `<path d="M${x} 112 L${x - 12 * s} 112 L${x - 4 * s} 86 L${x - 10 * s} 86 L${x} 54 L${x + 10 * s} 86 L${x + 4 * s} 86 L${x + 12 * s} 112Z" fill="${s > 1 ? p[3] : p[2]}"/>`;
    g = `<path d="${peaks}" fill="${p[2]}" opacity=".7"/>` + [20, 70, 120, 170, 225, 280, 335, 385].map((x, i) => t(x + (i % 2) * 8, 1 + (i % 3) * .3)).join('') + `<rect y="108" width="400" height="12" fill="${p[4]}"/>`;
  } else if (type === 'city') {
    g = [0, 38, 70, 112, 150, 196, 236, 282, 322, 362].map((x, i) => { const hgt = 30 + ((i * 37 + h) % 46); return `<rect x="${x}" y="${112 - hgt}" width="${30 + (i % 3) * 6}" height="${hgt}" fill="${i % 2 ? p[2] : p[3]}"/>`; }).join('') + `<rect y="106" width="400" height="14" fill="${p[4]}"/>`;
  } else if (type === 'alps') {
    g = `<path d="${peaks}" fill="${p[2]}"/><path d="M60 34 L74 52 L62 49 L52 56Z M170 28 L184 50 L172 47 L160 54Z" fill="#fff" opacity=".85"/><path d="M0 92 Q100 76 200 90 T400 84 V120 H0Z" fill="${p[3]}"/><path d="M0 108 Q150 96 400 106 V120 H0Z" fill="${p[4]}"/>`;
  } else { // plaže / útesy
    g = `<rect y="62" width="400" height="58" fill="${p[4]}"/><path d="M0 62 H400" stroke="#fff" opacity=".4"/>
         <path d="M${type === 'dark' ? 200 : 0} 120 V54 Q${(type === 'dark' ? 200 : 0) + 40} 40 ${(type === 'dark' ? 200 : 0) + 80} 52 T400 48 V120Z" fill="${p[2]}"/>
         <path d="M0 104 Q100 92 200 104 T400 100 V120 H0Z" fill="${p[0]}" opacity=".35"/>
         <path d="M0 112 Q80 100 160 112 T400 108 V120 H0Z" fill="${p[1]}" opacity=".7"/>`;
  }
  return `<svg viewBox="0 0 400 240" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
    <defs><linearGradient id="${u}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${p[0]}"/><stop offset="1" stop-color="${p[1]}"/></linearGradient></defs>
    <rect width="400" height="240" fill="url(#${u})"/><circle cx="${290 + j(7)}" cy="${70 + j(8)}" r="16" fill="#fff" opacity=".85"/><g transform="translate(0 120)">${g}</g></svg>`;
}

/* ---------- Téma: auto / svetlá / tmavá ---------- */
const THEME_KEY = 'ab:theme';
const ICONS = {
  auto: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 3v18" /><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg>',
  light: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.500 1.500M17.500 17.500L19 19M5 19l1.500-1.500M17.500 6.500L19 5"/></svg>',
  dark: '<svg viewBox="0 0 24 24"><path d="M20 14.500A8 8 0 0 1 9.500 4 8 8 0 1 0 20 14.500z"/></svg>'
};
const LABEL = { auto: 'Auto', light: 'Svetlý', dark: 'Tmavý' };
const isNight = (d = new Date()) => d.getHours() >= 19 || d.getHours() < 7;   // tma 19:00–07:00
function applyTheme() {
  const mode = store.get(THEME_KEY, 'auto');
  const t = mode === 'auto' ? (isNight() ? 'dark' : 'light') : mode;
  document.documentElement.dataset.theme = t;
  const m = document.querySelector('meta[name=theme-color]'); if (m) m.content = t === 'dark' ? '#141517' : '#f2f4f9';
  $('#theme .theme__icon').innerHTML = ICONS[mode];
  $('#themeLabel').textContent = mode === 'auto' ? `Auto · ${t === 'dark' ? 'noc' : 'deň'}` : LABEL[mode];
  $('#theme').setAttribute('aria-label', `Režim: ${LABEL[mode]}. Kliknutím zmeníte.`);
}
$('#theme').addEventListener('click', () => {
  const order = ['auto', 'light', 'dark'], cur = store.get(THEME_KEY, 'auto');
  const next = order[(order.indexOf(cur) + 1) % 3];
  store.set(THEME_KEY, next); applyTheme(); toast(`Režim: ${LABEL[next]}`);
});
setInterval(() => store.get(THEME_KEY, 'auto') === 'auto' && applyTheme(), 60000);

/* ---------- Uložené články (localStorage + Cache API) ---------- */
const SAVED_KEY = 'ab:saved', CACHE = 'adventuro-saved-v1';
const savedIds = () => store.get(SAVED_KEY, []);
const isSaved = id => savedIds().includes(id);
const cacheUrl = id => `./__saved/${id}.json`;
async function cacheSave(a) { try { const c = await caches.open(CACHE); await c.put(cacheUrl(a.id), new Response(JSON.stringify(a), { headers: { 'Content-Type': 'application/json' } })); } catch {} }
async function cacheDel(id) { try { const c = await caches.open(CACHE); await c.delete(cacheUrl(id)); } catch {} }
async function cacheLoad(id) { try { const r = await (await caches.open(CACHE)).match(cacheUrl(id)); return r ? r.json() : null; } catch { return null; } }

async function toggleSave(id) {
  const a = byId(id); if (!a) return;
  let ids = savedIds();
  if (ids.includes(id)) { ids = ids.filter(x => x !== id); store.set(SAVED_KEY, ids); await cacheDel(id); toast('Odstránené z uložených'); }
  else { ids.unshift(id); store.set(SAVED_KEY, ids); await cacheSave(a); toast('Uložené — dostupné aj offline'); }
  updateBadge();
  document.querySelectorAll(`[data-save="${id}"]`).forEach(b => { b.setAttribute('aria-pressed', ids.includes(id)); const l = b.querySelector('.lbl'); if (l) l.textContent = ids.includes(id) ? 'Uložené' : 'Uložiť'; });
  if (location.hash === '#/saved') render();
}
function updateBadge() { const n = savedIds().length, b = $('#badge'); b.hidden = !n; b.textContent = n; }

let tt; function toast(msg) { const t = $('#toast'); t.textContent = msg; t.classList.add('show'); clearTimeout(tt); tt = setTimeout(() => t.classList.remove('show'), 2000); }

/* ---------- Komponenty ---------- */
const HEART = '<svg viewBox="0 0 24 24"><path d="M12 20s-7-4.400-7-10a4 4 0 0 1 7-2.600A4 4 0 0 1 19 10c0 5.600-7 10-7 10z"/></svg>';
const ARROW = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M7 17L17 7M8 7h9v9"/></svg>';
const saveBtn = (id, cls = '') => `<button class="save ${cls}" data-save="${id}" aria-pressed="${isSaved(id)}" aria-label="Uložiť článok">${HEART}</button>`;
const city = a => a.place.split(',')[0];

const cardSm = a => `<div class="card card--sm"><a class="card__art" href="#/a/${a.id}" aria-label="${esc(a.title)}">${scene(a.scene, a.id)}</a>${saveBtn(a.id)}
  <a class="card__body" href="#/a/${a.id}"><h3>${esc(a.title)}</h3><p>${esc(a.place.split(',').slice(-2).join(','))}</p></a></div>`;
const cardWide = a => `<div class="card card--wide"><a class="card__art" href="#/a/${a.id}" aria-label="${esc(a.title)}">${scene(a.scene, a.id)}</a><span class="tag">Nový príbeh</span>${saveBtn(a.id)}
  <a class="card__body" href="#/a/${a.id}"><h3>${esc(a.title)}</h3><p>${esc(a.place)}</p><div class="meta"><span>${a.min} min čítania</span>·<span>${fmtDate(a.date)}</span></div></a></div>`;
const listCard = a => `<article class="list-card"><a class="list-card__art" href="#/a/${a.id}" tabindex="-1">${scene(a.scene, a.id)}</a>
  <a href="#/a/${a.id}"><h3>${esc(a.title)}</h3><p>${esc(a.place)} · ${a.min} min</p></a>${saveBtn(a.id)}</article>`;
const fmtDate = d => new Date(d).toLocaleDateString('sk-SK', { day: 'numeric', month: 'long', year: 'numeric' });
const chips = active => `<div class="chips" role="group" aria-label="Kategórie">${CATS.map(c => `<button class="chip" data-cat="${c.id}" aria-pressed="${c.id === active}"><i>${c.icon}</i>${c.label}</button>`).join('')}</div>`;
const greeting = () => { const h = new Date().getHours(); return h < 10 ? 'Dobré ráno' : h < 18 ? 'Ahoj' : 'Dobrý večer'; };

/* ---------- Stránky ---------- */
function viewHome() {
  const f = ARTICLES.find(a => a.featured), heroScene = isNight() ? 'forest' : 'cliffs';
  return `<section class="hero"><div class="hero__bg">${scene(heroScene, 'hero')}</div>
    <div class="hello"><div class="avatar" aria-hidden="true">A</div><div><small>${greeting()}, cestovateľ</small><strong>Vitaj späť!</strong></div></div>
    <h1>Pripravený na ďalšie <em>dobrodružstvo?</em></h1>
    <form class="search" action="#/explore" role="search" id="heroSearch"><label><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg><input name="q" type="search" placeholder="Hľadaj miesto, krajinu, tip…" aria-label="Hľadať"></label>
    <a class="fab" href="#/explore" aria-label="Filtre"><svg viewBox="0 0 24 24"><path d="M5 4v16M12 4v16M19 4v16M3 9h4M10 14h4M17 8h4"/></svg></a></form></section>
  <div class="wrap">
    <div class="section">${chips('all')}</div>
    <div class="section"><div class="section__head"><h2>Obľúbené miesta</h2><a class="link" href="#/explore">Zobraziť všetko</a></div>
      <div class="rail" id="rail">${ARTICLES.slice(1, 7).map(cardSm).join('')}</div></div>
    <div class="section">${cardWide(f)}</div>
    <div class="section"><div class="bento">
      <div class="tile tile--accent"><b>${ARTICLES.length}+</b><span>Príbehov z ciest</span></div>
      <div class="tile"><b>13</b><span>Navštívených krajín</span></div>
      <div class="tile tile--wide"><div><b>${savedIds().length}</b> <span>&nbsp;uložených offline</span></div><a class="link" href="#/saved">Otvoriť →</a></div></div></div>
    <div class="section"><div class="section__head"><h2>Najnovšie články</h2></div><div class="grid">${[...ARTICLES].sort((a, b) => b.date.localeCompare(a.date)).slice(0, 4).map(listCard).join('')}</div></div>
    <footer class="foot">© 2026 Adventuro · Cestuj pomaly, píš čestne.</footer></div>`;
}

let exploreState = { cat: 'all', q: '' };
function exploreList() {
  const q = exploreState.q.trim().toLowerCase();
  const list = ARTICLES.filter(a => (exploreState.cat === 'all' || a.cat === exploreState.cat) && (!q || (a.title + a.place + a.lead).toLowerCase().includes(q)));
  return list.length ? list.map(listCard).join('') : `<div class="empty" style="grid-column:1/-1"><b>Nič sme nenašli</b>Skúste iný výraz alebo kategóriu.</div>`;
}
function viewExplore() {
  return `<div class="page"><h1>Objavuj miesta</h1><p>Príbehy a tipy z ciest po celom svete.</p>
    <form class="search search--plain" onsubmit="return false"><label><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg><input id="q" type="search" placeholder="Hľadať…" value="${esc(exploreState.q)}" aria-label="Hľadať"></label></form>
    <div class="section" style="margin-top:16px">${chips(exploreState.cat)}</div>
    <div class="section"><div class="grid" id="results">${exploreList()}</div></div></div>`;
}
async function viewSaved() {
  const ids = savedIds();
  // Obsah berieme z Cache API (funguje offline); ak chýba, použijeme dáta aplikácie.
  const items = (await Promise.all(ids.map(async id => (await cacheLoad(id)) || byId(id)))).filter(Boolean);
  return `<div class="page"><h1>Uložené články</h1><p class="pill-note">Uložené vo vašom zariadení · čítate aj bez internetu</p>
    ${items.length ? `<div class="grid">${items.map(listCard).join('')}</div>` :
      `<div class="empty"><svg viewBox="0 0 24 24"><path d="M12 20s-7-4.400-7-10a4 4 0 0 1 7-2.600A4 4 0 0 1 19 10c0 5.600-7 10-7 10z"/></svg><b>Zatiaľ nič uložené</b>Ťuknite na srdiečko pri článku a nájdete ho tu.<br><a class="btn" href="#/explore">Objavovať články</a></div>`}</div>`;
}
async function viewArticle(id) {
  const a = (await cacheLoad(id)) || byId(id);
  if (!a) return `<div class="page"><div class="empty"><b>Článok sa nenašiel</b><a class="btn" href="#/">Domov</a></div></div>`;
  const body = a.body.map(b => typeof b === 'string' ? `<p>${esc(b)}</p>` : b[0] === 'q' ? `<blockquote>${esc(b[1])}</blockquote>` : b[0] === 'h' ? `<h2>${esc(b[1])}</h2>` : `<ul class="tips">${b[1].map(t => `<li>${esc(t)}</li>`).join('')}</ul>`).join('');
  const more = ARTICLES.filter(x => x.id !== a.id && x.cat === a.cat).concat(ARTICLES.filter(x => x.cat !== a.cat)).slice(0, 4);
  return `<article><div class="article__hero"><div class="card__art">${scene(a.scene, a.id)}</div><div><small>${esc(a.place)} · ${a.min} min čítania</small><h1>${esc(a.title)}</h1></div></div>
    <div class="article__bar"><a class="back" href="javascript:history.back()"><svg viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg>Späť</a>
      <button class="save save--pill" data-save="${a.id}" aria-pressed="${isSaved(a.id)}">${HEART.replace('<svg', '<svg width="18" height="18"')}<span class="lbl">${isSaved(a.id) ? 'Uložené' : 'Uložiť'}</span></button></div>
    <div class="article__body"><p><strong>${esc(a.lead)}</strong></p>${body}</div>
    <div class="wrap"><div class="section"><div class="section__head"><h2>Čítajte ďalej</h2></div><div class="rail">${more.map(cardSm).join('')}</div></div></div></article>`;
}
function viewAbout() {
  return `<div class="page"><h1>O mne</h1><p>Ahoj, som cestovateľ, ktorý píše o miestach, kde sa oplatí spomaliť.</p>
    <div class="bento"><div class="tile tile--accent"><b>13</b><span>krajín</span></div><div class="tile"><b>15K+</b><span>čitateľov</span></div>
    <div class="tile tile--wide"><div><b>4.8/5</b> <span>&nbsp;hodnotenie čitateľov</span></div></div></div>
    <div class="section"><a class="btn" href="mailto:ahoj@adventuro.example">Napíš mi</a> <a class="btn btn--ghost" href="#/explore">Čítať príbehy</a></div></div>`;
}

/* ---------- Router ---------- */
const main = $('#main');
async function render() {
  const h = location.hash.replace(/^#/, '') || '/';
  const [, route, arg] = h.split('/');
  let html, nav = 'home';
  if (route === 'explore') { html = viewExplore(); nav = 'explore'; }
  else if (route === 'saved') { html = await viewSaved(); nav = 'saved'; }
  else if (route === 'about') { html = viewAbout(); nav = 'about'; }
  else if (route === 'a') { html = await viewArticle(arg); nav = ''; }
  else html = viewHome();
  main.innerHTML = html;
  document.querySelectorAll('.dock a').forEach(a => a.dataset.nav === nav ? a.setAttribute('aria-current', 'page') : a.removeAttribute('aria-current'));
  window.scrollTo(0, 0);
  document.title = route === 'a' && byId(arg) ? `${byId(arg).title} — Adventuro` : 'Adventuro — cestovateľský blog';
}
addEventListener('hashchange', render);

/* Delegované udalosti */
document.addEventListener('click', e => {
  const s = e.target.closest('[data-save]'); if (s) { e.preventDefault(); toggleSave(s.dataset.save); return; }
  const c = e.target.closest('[data-cat]');
  if (c) {
    exploreState.cat = c.dataset.cat;
    if (location.hash.startsWith('#/explore')) { document.querySelectorAll('.chip').forEach(x => x.setAttribute('aria-pressed', x === c)); $('#results').innerHTML = exploreList(); }
    else { location.hash = '#/explore'; }
  }
});
document.addEventListener('input', e => { if (e.target.id === 'q') { exploreState.q = e.target.value; $('#results').innerHTML = exploreList(); } });
document.addEventListener('submit', e => { if (e.target.id === 'heroSearch') { e.preventDefault(); exploreState.q = new FormData(e.target).get('q') || ''; exploreState.cat = 'all'; location.hash = '#/explore'; } });

/* Offline shell */
if ('serviceWorker' in navigator && location.protocol.startsWith('http')) navigator.serviceWorker.register('sw.js').catch(() => {});

applyTheme(); updateBadge(); render();
