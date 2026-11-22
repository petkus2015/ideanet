/* LISO — slider produktov, košík a objednávka. */

/* ===== Nastavenia obchodu — upravte pred spustením ===== */
const CONFIG = {
  email: 'objednavky@vasadomena.sk',      // kam chodia objednávky (mailto záloha)
  orderEndpoint: '',                      // URL, kam sa objednávka pošle ako JSON (napr. Formspree). Prázdne = e-mail.
  company: 'Názov firmy s.r.o., Ulica 1, 811 01 Bratislava · IČO 00000000 · DIČ 0000000000',
  iban: 'SK00 0000 0000 0000 0000 0000',
  freeShippingFrom: 35,
  shipping: [
    { id: 'kurier', label: 'Kuriér v chladiacom boxe', note: '1–2 pracovné dni', price: 4.9, address: true },
    { id: 'packeta', label: 'Packeta — výdajné miesto', note: 'v chladiacom boxe, 2 dni', price: 3.5, address: true },
    { id: 'osobne', label: 'Osobný odber', note: 'Bratislava, po–pi 8:00–16:00', price: 0, address: false },
  ],
  payment: [
    { id: 'prevod', label: 'Bankový prevod', note: 'údaje pošleme hneď', fee: 0 },
    { id: 'dobierka', label: 'Dobierka', note: 'platba kuriérovi', fee: 1.2, only: ['kurier', 'packeta'] },
    { id: 'odber', label: 'Pri odbere', note: 'hotovosť alebo karta', fee: 0, only: ['osobne'] },
  ],
};

/* ===== Produkty ===== */
const PRODUCTS = [
  {
    id: 'zeleny', num: '01', day: 'Deň prvý', name: 'Zelený', labelTop: 'ZELENÝ', price: 4.9,
    bg: '#9bc983', liquid: '#4f9a2a', dark: '#245712', ink: '#fff', fruit: 'greens', seed: 11,
    lead: 'Malá fľaša plná listovej zelene',
    desc: 'Kel, špenát a uhorka s kúskom zázvoru. Najjednoduchší spôsob, ako do dňa pridať porciu zeleniny.',
    ingredients: ['kel', 'špenát', 'uhorka', 'jablko', 'zázvor'],
  },
  {
    id: 'cerveny', num: '02', day: 'Deň druhý', name: 'Červený', labelTop: 'ČERVENÝ', price: 4.9,
    bg: '#e3877e', liquid: '#c4203f', dark: '#6b0b1f', ink: '#fff', fruit: 'raspberry', seed: 5,
    lead: 'Cvikla a maliny, zemité aj sladké zároveň',
    desc: 'Cviklu dopĺňajú maliny, mrkva a jablko. Limetka pridáva sviežosť, ktorá prebije zemitú chuť.',
    ingredients: ['cvikla', 'maliny', 'mrkva', 'jablko', 'limetka'],
  },
  {
    id: 'zlaty', num: '03', day: 'Deň tretí', name: 'Zlatý', labelTop: 'ZLATÝ', price: 4.9,
    bg: '#f2a85e', liquid: '#f08a12', dark: '#9a4a03', ink: '#fff', fruit: 'orange', seed: 3,
    lead: 'Pomaranč a kurkuma pre pomalé rána',
    desc: 'Pomarančová šťava s mrkvou, kurkumou a štipkou čierneho korenia. Chutí ako slnko vo fľaši.',
    ingredients: ['pomaranč', 'mrkva', 'kurkuma', 'citrón', 'čierne korenie'],
  },
  {
    id: 'citronovy', num: '04', day: 'Deň štvrtý', name: 'Citrónový', labelTop: 'CITRÓN', price: 4.5,
    bg: '#ecd97b', liquid: '#f1d43a', dark: '#b08d07', ink: '#3b3205', fruit: 'lemon', seed: 9,
    lead: 'Starý dobrý citrón s medom a zázvorom',
    desc: 'Pramenitá voda, citrón, zázvor a lyžička slovenského medu. Ľahký koniec štvordňového programu.',
    ingredients: ['voda', 'citrón', 'zázvor', 'med', 'mäta'],
  },
];

const BUNDLES = [
  { id: 'balik4', name: 'Detox na 4 dni', desc: 'Po 3 fľaše z každého druhu — 12 × 300 ml.', items: { zeleny: 3, cerveny: 3, zlaty: 3, citronovy: 3 }, price: 49 },
  { id: 'ochutnavka', name: 'Ochutnávka', desc: 'Jedna fľaša z každého druhu — 4 × 300 ml.', items: { zeleny: 1, cerveny: 1, zlaty: 1, citronovy: 1 }, price: 17.9 },
];

const ALL = [...PRODUCTS, ...BUNDLES];
const byId = (id) => ALL.find((p) => p.id === id);
const $ = (s, el = document) => el.querySelector(s);
const eur = new Intl.NumberFormat('sk-SK', { style: 'currency', currency: 'EUR' });
const fmt = (n) => eur.format(n);
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

const store = {
  get(k, d) { try { const v = localStorage.getItem(k); return v ? JSON.parse(v) : d; } catch { return d; } },
  set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch { /* bez úložiska */ } },
};

/* ===== Slider a pohyb ===== */
let idx = 0, busy = false, heroQty = 1;
const root = document.documentElement;
const hero = $('.hero'), vis = $('#hVisual');
const E_OUT = 'cubic-bezier(.16,1,.3,1)', E_IN = 'cubic-bezier(.7,0,.84,0)', E_IO = 'cubic-bezier(.76,0,.24,1)', SPRING = 'cubic-bezier(.34,1.45,.64,1)';
const AUTOPLAY_MS = 7000;

/* Spustí animáciu (Web Animations API); pri obmedzenom pohybe nič nerobí. */
function play(el, kf, o) {
  if (reduced || !el) return null;
  el.getAnimations().forEach((a) => a.cancel());
  return el.animate(kf, { fill: 'both', ...o });
}

/* Rozmiestnenie ovocných kúskov okolo fľaše: [left %, top %, šírka %, hĺbka paralaxy, vpredu] */
const PIECES = [[-9, 13, 19, 34, 0], [86, 5, 15, 22, 0], [-15, 57, 14, 52, 1], [90, 45, 21, 42, 1], [5, 84, 12, 28, 1], [76, 82, 15, 60, 1]];

function titleHTML(p) {
  return [p.name, 'Detox'].map((w) => `<span class="ln"><span class="ln-in">${[...w].map((c) => `<span class="ch">${c}</span>`).join('')}</span></span>`).join('');
}

function setContent(p, i) {
  root.style.setProperty('--bg', p.bg);
  $('meta[name=theme-color]').content = p.bg;
  const t = $('#hTitle');
  t.innerHTML = titleHTML(p);
  t.setAttribute('aria-label', `${p.name} detox`);
  t.querySelectorAll('.ln').forEach((l) => l.setAttribute('aria-hidden', 'true'));
  $('#hPrice').textContent = fmt(p.price) + ' · 300 ml';
  $('#hLead').textContent = p.lead;
  $('#hDesc').textContent = p.desc;
  $('#hIdx').textContent = i + 1;
  heroQty = 1; $('#hQty').textContent = 1;
}

function makeLayer(p) {
  const layer = document.createElement('div');
  layer.className = 'oval-layer';
  layer.innerHTML = ART.fruit(p.fruit, p.seed);
  return layer;
}
function makeBottle(p) {
  const b = document.createElement('div');
  b.className = 'bottle';
  b.setAttribute('role', 'img');
  b.setAttribute('aria-label', `Fľaša ${p.name} detox, ${p.ingredients.join(', ')}`);
  b.innerHTML = `<div class="bob">${ART.bottle(p)}</div>`;
  return b;
}
function makePieces(p) {
  return PIECES.map(([l, t, w, z, front], k) => {
    const el = document.createElement('div');
    el.className = 'pc' + (front ? ' front' : '');
    el.style.cssText = `left:${l}%;top:${t}%;width:${w}%;--z:${z};--dur:${5 + (k % 3) * 1.4}s;--del:${-k * .9}s`;
    el.innerHTML = `<div class="pc-in">${ART.piece(p.fruit, k)}</div>`;
    return el;
  });
}

/* --- vstupy a výstupy jednotlivých vrstiev --- */
function textOut(dir) {
  $('#hTitle').querySelectorAll('.ch').forEach((c, k) =>
    play(c, [{ transform: 'none' }, { transform: `translateY(${-dir * 135}%) rotate(${-dir * 6}deg)` }], { duration: 480, delay: k * 16, easing: E_IN }));
  document.querySelectorAll('.hero .fx').forEach((el, k) =>
    play(el, [{ opacity: 1, transform: 'none', filter: 'blur(0)' }, { opacity: 0, transform: `translateY(${-dir * 16}px)`, filter: 'blur(6px)' }], { duration: 380, delay: k * 35, easing: E_IN }));
  const n = $('#hIdx');
  play(n, [{ transform: 'none' }, { transform: `translateY(${-dir * 110}%)` }], { duration: 350, easing: E_IN });
}
function textIn(dir, delay = 0) {
  $('#hTitle').querySelectorAll('.ch').forEach((c, k) =>
    play(c, [{ transform: `translateY(${dir * 135}%) rotate(${dir * 8}deg)` }, { transform: 'none' }], { duration: 950, delay: delay + k * 24, easing: E_OUT }));
  document.querySelectorAll('.hero .fx').forEach((el, k) =>
    play(el, [{ opacity: 0, transform: `translateY(${dir * 22}px)`, filter: 'blur(6px)' }, { opacity: 1, transform: 'none', filter: 'blur(0)' }], { duration: 850, delay: delay + 120 + k * 70, easing: E_OUT }));
  play($('#hIdx'), [{ transform: `translateY(${dir * 110}%)` }, { transform: 'none' }], { duration: 700, delay: delay + 100, easing: E_OUT });
}

function visualIn(p, dir, first) {
  const oval = $('#hOval'), bw = $('#hBottle'), parts = $('#hParts');
  const oldL = [...oval.children], oldB = [...bw.children], oldP = [...parts.children];
  const layer = makeLayer(p), bottle = makeBottle(p), pcs = makePieces(p);
  oval.append(layer); bw.append(bottle); parts.append(...pcs);
  if (reduced) { [...oldL, ...oldB, ...oldP].forEach((n) => n.remove()); return; }

  const at = first ? '50% 100%' : dir > 0 ? '100% 55%' : '0% 55%';
  const d0 = first ? 150 : 0;
  play(layer, [{ clipPath: `circle(0% at ${at})` }, { clipPath: `circle(150% at ${at})` }], { duration: first ? 1500 : 1150, delay: d0, easing: E_IO });
  play(layer.firstElementChild, [{ transform: 'scale(1.35) rotate(' + dir * 4 + 'deg)' }, { transform: 'none' }], { duration: 1700, delay: d0, easing: E_OUT });
  oldL.forEach((l) => play(l, [{ transform: 'none', filter: 'brightness(1)' }, { transform: `translateX(${-dir * 8}%) scale(.92)`, filter: 'brightness(.6)' }], { duration: 1150, easing: E_IO }));
  play(oval, [{ transform: 'scale(1)' }, { transform: first ? 'scale(.9)' : 'scale(.955)', offset: .4 }, { transform: 'scale(1)' }], { duration: first ? 1600 : 1200, easing: E_IO });

  oldB.forEach((b) => play(b, [{ transform: 'none', opacity: 1 }, { transform: `translate(${-dir * 70}%, 6%) rotate(${-dir * 18}deg) scale(.9)`, opacity: 0 }], { duration: 560, easing: E_IN }));
  play(bottle, first
    ? [{ transform: 'translateY(-55%) rotate(-6deg)', opacity: 0 }, { opacity: 1, offset: .3 }, { transform: 'none', opacity: 1 }]
    : [{ transform: `translate(${dir * 85}%, -6%) rotate(${dir * 20}deg)`, opacity: 0 }, { opacity: 1, offset: .35 }, { transform: 'none', opacity: 1 }],
  { duration: first ? 1400 : 1150, delay: first ? 550 : 300, easing: SPRING });

  oldP.forEach((pc, k) => play(pc, [{ transform: 'none', opacity: 1 }, { transform: `translate(${-dir * 160}%, ${(k % 2 ? 1 : -1) * 60}%) rotate(${-dir * 120}deg) scale(.3)`, opacity: 0 }], { duration: 520, delay: k * 25, easing: E_IN }));
  pcs.forEach((pc, k) => {
    const [l, t] = PIECES[k];
    const vx = (50 - l) * 2.2, vy = (50 - t) * 2.2; // vyletia zo stredu fľaše
    play(pc, [{ transform: `translate(${vx}%, ${vy}%) scale(0) rotate(${-dir * 160}deg)`, opacity: 0 }, { opacity: 1, offset: .25 }, { transform: 'none', opacity: 1 }],
      { duration: 1300, delay: (first ? 900 : 480) + k * 70, easing: SPRING });
  });

  setTimeout(() => { [...oldL, ...oldB, ...oldP].forEach((n) => n.remove()); }, 1250);
}

/* Farba sa rozleje z miesta, odkiaľ prišiel pokyn (šípka, prst). */
function flood(color, x, y) {
  const f = $('#flood'), r = hero.getBoundingClientRect();
  const fx = x - r.left, fy = y - r.top;
  const R = Math.hypot(Math.max(fx, r.width - fx), Math.max(fy, r.height - fy));
  f.style.background = color;
  if (reduced) { hero.style.setProperty('--hero-bg', color); return; }
  const a = play(f, [{ clipPath: `circle(0px at ${fx}px ${fy}px)` }, { clipPath: `circle(${R}px at ${fx}px ${fy}px)` }], { duration: 1150, easing: E_IO });
  a.onfinish = () => { hero.style.setProperty('--hero-bg', color); a.cancel(); };
}

function originOf(dir, src) {
  if (src && 'clientX' in src) return [src.clientX, src.clientY];
  const el = src instanceof Element ? src : vis;
  const r = el.getBoundingClientRect();
  return src instanceof Element ? [r.left + r.width / 2, r.top + r.height / 2] : [dir > 0 ? r.right : r.left, r.top + r.height * .55];
}

function go(dir, src, target) {
  if (busy) return;
  const next = target ?? (idx + dir + PRODUCTS.length) % PRODUCTS.length;
  if (next === idx) return;
  const p = PRODUCTS[next];
  restartAuto();
  if (reduced) { idx = next; setContent(p, idx); hero.style.setProperty('--hero-bg', p.bg); visualIn(p, dir); return; }
  busy = true;
  const [x, y] = originOf(dir, src);
  flood(p.bg, x, y);
  textOut(dir);
  visualIn(p, dir);
  setTimeout(() => {
    idx = next;
    setContent(p, idx);
    textIn(dir);
    setTimeout(() => (busy = false), 650);
  }, 520);
}

function intro() {
  const p = PRODUCTS[0];
  setContent(p, 0);
  hero.style.setProperty('--hero-bg', p.bg);
  visualIn(p, 1, true);
  textIn(1, 250);
  play($('#hdr'), [{ transform: 'translateY(-100%)' }, { transform: 'none' }], { duration: 1000, delay: 100, easing: E_OUT })
    ?.finished.then((a) => a.cancel());
  document.querySelectorAll('.h-side > *').forEach((el, k) =>
    play(el, [{ opacity: 0, transform: 'translateY(20px)' }, { opacity: 1, transform: 'none' }], { duration: 900, delay: 700 + k * 120, easing: E_OUT }));
}

/* --- automatické prepínanie s ukazovateľom okolo šípky --- */
const ring = $('#ring');
let autoOn = !reduced, heroVisible = true;
ring.style.setProperty('--auto', AUTOPLAY_MS + 'ms');
function restartAuto() {
  ring.classList.remove('run'); void ring.getBoundingClientRect();
  if (autoOn) ring.classList.add('run');
}
function syncPause() {
  const paused = !autoOn || !heroVisible || document.hidden || cartOpen || !!document.querySelector('dialog[open]') || !!hero.querySelector(':focus-visible') || dragging;
  hero.classList.toggle('paused', paused);
}
ring.addEventListener('animationend', () => go(1));
$('#autoBtn').onclick = () => {
  autoOn = !autoOn;
  $('#autoBtn').setAttribute('aria-pressed', autoOn);
  $('#autoBtn').setAttribute('aria-label', autoOn ? 'Pozastaviť automatické prepínanie' : 'Spustiť automatické prepínanie');
  restartAuto(); syncPause();
};
if (!autoOn) { $('#autoBtn').setAttribute('aria-pressed', 'false'); hero.classList.add('no-auto'); }
new IntersectionObserver(([e]) => { heroVisible = e.intersectionRatio > .4; syncPause(); }, { threshold: [0, .4, 1] }).observe(hero);
document.addEventListener('visibilitychange', syncPause);
hero.addEventListener('focusin', syncPause);
hero.addEventListener('focusout', () => setTimeout(syncPause));
setInterval(syncPause, 500);

$('#hTotal').textContent = PRODUCTS.length;
$('#prevBtn').onclick = (e) => go(-1, e.currentTarget);
$('#nextBtn').onclick = (e) => go(1, e.currentTarget);
$('#hMinus').onclick = () => { heroQty = Math.max(1, heroQty - 1); $('#hQty').textContent = heroQty; };
$('#hPlus').onclick = () => { heroQty = Math.min(30, heroQty + 1); $('#hQty').textContent = heroQty; };
$('#hAdd').onclick = (e) => add(PRODUCTS[idx].id, heroQty, e.currentTarget);

document.addEventListener('keydown', (e) => {
  if (e.target.closest('input,textarea,dialog[open]') || cartOpen) return;
  if (hero.getBoundingClientRect().bottom < 100) return;
  if (e.key === 'ArrowRight') go(1);
  if (e.key === 'ArrowLeft') go(-1);
});

/* --- ťahanie fľaše prstom / myšou --- */
let dragging = false;
(() => {
  let x0 = 0, y0 = 0, dx = 0, id = null, axis = null;
  vis.addEventListener('pointerdown', (e) => {
    if (busy || e.button > 0) return;
    id = e.pointerId; x0 = e.clientX; y0 = e.clientY; dx = 0; axis = null;
  });
  vis.addEventListener('pointermove', (e) => {
    if (e.pointerId !== id) return;
    const mx = e.clientX - x0, my = e.clientY - y0;
    if (!axis && Math.hypot(mx, my) > 8) {
      axis = Math.abs(mx) > Math.abs(my) ? 'x' : 'y';
      if (axis === 'x') { dragging = true; vis.setPointerCapture(id); vis.classList.add('dragging'); syncPause(); }
    }
    if (axis !== 'x') return;
    dx = mx;
    vis.style.setProperty('--drag', (dx / (1 + Math.abs(dx) / 380)).toFixed(1));
  });
  const end = (e) => {
    if (e.pointerId !== id) return;
    id = null;
    if (!dragging) return;
    dragging = false;
    vis.classList.remove('dragging');
    vis.style.setProperty('--drag', 0);
    if (Math.abs(dx) > 70) go(dx < 0 ? 1 : -1, e);
    syncPause();
  };
  vis.addEventListener('pointerup', end);
  vis.addEventListener('pointercancel', end);
})();

/* --- paralaxa: kurzor + skrolovanie (jemne dobieha cez lerp) --- */
(() => {
  if (reduced) return;
  const fine = matchMedia('(pointer: fine)').matches;
  let tx = 0, ty = 0, cx = 0, cy = 0, sy = 0, csy = 0, raf = 0;
  const tick = () => {
    cx += (tx - cx) * .07; cy += (ty - cy) * .07; csy += (sy - csy) * .18;
    hero.style.setProperty('--mx', cx.toFixed(4));
    hero.style.setProperty('--my', cy.toFixed(4));
    hero.style.setProperty('--sy', csy.toFixed(4));
    raf = Math.abs(tx - cx) + Math.abs(ty - cy) + Math.abs(sy - csy) > .001 ? requestAnimationFrame(tick) : 0;
  };
  const kick = () => { if (!raf) raf = requestAnimationFrame(tick); };
  if (fine) addEventListener('pointermove', (e) => { tx = e.clientX / innerWidth * 2 - 1; ty = e.clientY / innerHeight * 2 - 1; kick(); }, { passive: true });
  addEventListener('scroll', () => { sy = Math.min(1, scrollY / hero.offsetHeight); kick(); }, { passive: true });
})();

/* --- magnetické šípky --- */
if (!reduced && matchMedia('(pointer: fine)').matches) {
  document.querySelectorAll('.arrow, .auto-btn').forEach((b) => {
    b.addEventListener('pointermove', (e) => {
      const r = b.getBoundingClientRect();
      b.style.translate = `${(e.clientX - r.left - r.width / 2) * .35}px ${(e.clientY - r.top - r.height / 2) * .35}px`;
    });
    b.addEventListener('pointerleave', () => (b.style.translate = ''));
  });
}

/* ===== Program a obchod ===== */
$('#days').innerHTML = PRODUCTS.map((p, i) => `
  <li style="--c:${p.bg}">
    <span class="day-num">${p.num}</span>
    <div class="day-bottle">${ART.bottle(p)}</div>
    <h3>${p.day}</h3>
    <p>${p.name} detox — ${p.ingredients.slice(0, 3).join(', ')}</p>
    <button class="link-arrow" data-slide="${i}">Pozrieť <span aria-hidden="true">⟶</span></button>
  </li>`).join('');
$('#days').addEventListener('click', (e) => {
  const b = e.target.closest('[data-slide]'); if (!b) return;
  const t = +b.dataset.slide;
  scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
  if (t !== idx) setTimeout(() => go(t > idx ? 1 : -1, null, t), reduced ? 0 : 450);
});

$('#products').innerHTML = PRODUCTS.map((p) => `
  <article class="card" style="--c:${p.bg}">
    <div class="card-visual">
      <div class="card-oval">${ART.fruit(p.fruit, p.seed)}</div>
      <div class="card-bottle">${ART.bottle(p)}</div>
    </div>
    <div class="card-body">
      <div class="card-title"><h3>${p.name} detox</h3><strong>${fmt(p.price)}</strong></div>
      <p>${p.ingredients.join(' · ')}</p>
      <button class="btn-dark btn-block" data-add="${p.id}">Do košíka</button>
    </div>
  </article>`).join('');

$('#bundles').innerHTML = BUNDLES.map((b) => {
  const full = Object.entries(b.items).reduce((s, [id, q]) => s + byId(id).price * q, 0);
  const dots = Object.entries(b.items).map(([id]) => `<i style="background:${byId(id).bg}"></i>`).join('');
  return `<article class="bundle">
    <div class="bundle-dots">${dots}</div>
    <div class="bundle-info"><h3>${b.name}</h3><p>${b.desc}</p></div>
    <div class="bundle-price"><s>${fmt(full)}</s><strong>${fmt(b.price)}</strong></div>
    <button class="btn-dark" data-add="${b.id}">Do košíka</button>
  </article>`;
}).join('');

document.addEventListener('click', (e) => {
  const b = e.target.closest('[data-add]');
  if (b) add(b.dataset.add, 1, b);
});
$('#shipNote').textContent = `Doprava zadarmo od ${fmt(CONFIG.freeShippingFrom)} · lisujeme v deň odoslania`;
$('#ftrCompany').textContent = CONFIG.company;
$('#ftrFree').textContent = 'od ' + fmt(CONFIG.freeShippingFrom);
$('#year').textContent = new Date().getFullYear();
$('#ftrMail').textContent = CONFIG.email;
$('#ftrMail').href = 'mailto:' + CONFIG.email;

/* ===== Košík ===== */
let cart = store.get('liso-cart', {});
for (const id in cart) if (!byId(id)) delete cart[id];
let cartOpen = false;

const subtotal = () => Object.entries(cart).reduce((s, [id, q]) => s + byId(id).price * q, 0);
const count = () => Object.values(cart).reduce((s, q) => s + q, 0);

function add(id, q, src) {
  cart[id] = Math.min(99, (cart[id] || 0) + q);
  const p = byId(id);
  const done = () => {
    saveCart();
    const t = cartTarget(), c = t.querySelector('.badge');
    if (c) { c.classList.remove('pop'); void c.offsetWidth; c.classList.add('pop'); }
    play(t.querySelector('svg'), [{ transform: 'none' }, { transform: 'rotate(-14deg) scale(1.15)' }, { transform: 'rotate(10deg)' }, { transform: 'none' }], { duration: 600, easing: E_OUT });
  };
  toast(`${p.name}${p.num ? ' detox' : ''} × ${q} — v košíku`);
  if (src && !reduced) flyToCart(src, p.bg || '#ecd97b', p.num ? p : null).then(done); else done();
}

/* Viditeľné tlačidlo košíka: na mobile v spodnej lište, inak v hlavičke. */
const cartTarget = () => ($('#tabCart').offsetParent ? $('#tabCart') : $('#cartBtn'));

/* Fľaštička preletí oblúkom z tlačidla do košíka. */
function flyToCart(src, color, prod) {
  const a = src.getBoundingClientRect(), b = cartTarget().getBoundingClientRect();
  const el = document.createElement('div');
  el.className = 'fly';
  el.style.background = color;
  if (prod) el.innerHTML = ART.bottle(prod);
  document.body.append(el);
  const x0 = a.left + a.width / 2, y0 = a.top + a.height / 2, x1 = b.left + b.width / 2, y1 = b.top + b.height / 2;
  const cx = (x0 + x1) / 2 + (x1 < x0 ? 80 : -80), cy = Math.min(y0, y1) - 160;
  const kf = [];
  for (let i = 0; i <= 20; i++) {
    const t = i / 20, u = 1 - t;
    const x = u * u * x0 + 2 * u * t * cx + t * t * x1, y = u * u * y0 + 2 * u * t * cy + t * t * y1;
    kf.push({ transform: `translate(${x - 22}px, ${y - 22}px) scale(${1 + Math.sin(t * Math.PI) * .35 - t * .6}) rotate(${t * 360}deg)`, opacity: t > .92 ? 0 : 1 });
  }
  const an = el.animate(kf, { duration: 850, easing: 'cubic-bezier(.45,0,.25,1)', fill: 'both' });
  return an.finished.then(() => el.remove());
}
function setQty(id, q) { if (q <= 0) delete cart[id]; else cart[id] = Math.min(99, q); saveCart(); }
function saveCart() { store.set('liso-cart', cart); renderCart(); }

function renderCart() {
  const n = count(), sub = subtotal();
  document.querySelectorAll('.badge').forEach((badge) => { badge.hidden = !n; badge.textContent = n; });
  $('#cartBtn').setAttribute('aria-label', `Košík, položiek: ${n}`);
  $('#cartSubtotal').textContent = fmt(sub);
  $('#checkoutBtn').disabled = !n;
  const left = CONFIG.freeShippingFrom - sub;
  $('#freeText').innerHTML = left > 0 ? `Do dopravy zadarmo chýba <b>${fmt(left)}</b>` : '<b>Dopravu máte zadarmo.</b>';
  $('#freeFill').style.width = Math.min(100, (sub / CONFIG.freeShippingFrom) * 100) + '%';
  $('#cartItems').innerHTML = n ? Object.entries(cart).map(([id, q]) => {
    const p = byId(id);
    const thumb = p.num ? `<div class="ci-thumb" style="--c:${p.bg}">${ART.bottle(p)}</div>`
      : `<div class="ci-thumb bundle-thumb">${Object.keys(p.items).map((k) => `<i style="background:${byId(k).bg}"></i>`).join('')}</div>`;
    return `<li class="ci">
      ${thumb}
      <div class="ci-info"><strong>${p.num ? p.name + ' detox' : p.name}</strong><span>${fmt(p.price)}${p.num ? ' · 300 ml' : ''}</span>
        <div class="qty qty-sm"><button data-q="${id}" data-d="-1" aria-label="Menej">−</button><output>${q}</output><button data-q="${id}" data-d="1" aria-label="Viac">+</button></div>
      </div>
      <div class="ci-right"><strong>${fmt(p.price * q)}</strong><button class="ci-rm" data-rm="${id}">Odstrániť</button></div>
    </li>`;
  }).join('') : '<li class="empty">Košík je prázdny.<br><a href="#obchod" data-close>Vybrať šťavu</a></li>';
}
$('#cartItems').addEventListener('click', (e) => {
  const q = e.target.closest('[data-q]'), rm = e.target.closest('[data-rm]');
  if (q) setQty(q.dataset.q, (cart[q.dataset.q] || 0) + +q.dataset.d);
  if (rm) setQty(rm.dataset.rm, 0);
  if (e.target.closest('[data-close]')) toggleCart(false);
});

function toggleCart(open) {
  cartOpen = open;
  if (open) $('#toast').classList.remove('show');
  $('#cart').classList.toggle('open', open);
  $('#cart').setAttribute('aria-hidden', !open);
  $('#scrim').hidden = !open;
  document.body.classList.toggle('locked', open);
  if (open) setTimeout(() => $('#cartClose').focus(), 50); else cartTarget().focus({ preventScroll: true });
}
document.querySelectorAll('[data-cart]').forEach((b) => (b.onclick = () => toggleCart(true)));
$('#cartClose').onclick = () => toggleCart(false);
$('#scrim').onclick = () => toggleCart(false);
document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && cartOpen) toggleCart(false); });

let toastT;
function toast(msg) {
  const t = $('#toast');
  t.innerHTML = `<span>${esc(msg)}</span><button type="button">Zobraziť košík</button>`;
  t.querySelector('button').onclick = () => { t.classList.remove('show'); toggleCart(true); };
  t.classList.add('show');
  clearTimeout(toastT); toastT = setTimeout(() => t.classList.remove('show'), 3200);
}

/* ===== Objednávka ===== */
const dlg = $('#checkout'), form = $('#coForm');
let sel = store.get('liso-checkout', { ship: 'kurier', pay: 'prevod' });

function payOptions() { return CONFIG.payment.filter((p) => !p.only || p.only.includes(sel.ship)); }

function renderChoices() {
  if (!CONFIG.shipping.some((s) => s.id === sel.ship)) sel.ship = CONFIG.shipping[0].id;
  const pays = payOptions();
  if (!pays.some((p) => p.id === sel.pay)) sel.pay = pays[0].id;
  const freeShip = subtotal() >= CONFIG.freeShippingFrom;
  $('#shipChoices').innerHTML = CONFIG.shipping.map((s) => `
    <label class="choice"><input type="radio" name="ship" value="${s.id}" ${s.id === sel.ship ? 'checked' : ''}>
      <span><b>${s.label}</b><small>${s.note}</small></span><em>${s.price === 0 || freeShip ? 'zadarmo' : fmt(s.price)}</em></label>`).join('');
  $('#payChoices').innerHTML = pays.map((p) => `
    <label class="choice"><input type="radio" name="pay" value="${p.id}" ${p.id === sel.pay ? 'checked' : ''}>
      <span><b>${p.label}</b><small>${p.note}</small></span><em>${p.fee ? '+ ' + fmt(p.fee) : ''}</em></label>`).join('');
  const ship = CONFIG.shipping.find((s) => s.id === sel.ship);
  $('#addrSet').hidden = !ship.address;
  $('#addrSet').disabled = !ship.address;
  renderSummary();
}

function totals() {
  const sub = subtotal();
  const ship = CONFIG.shipping.find((s) => s.id === sel.ship);
  const pay = CONFIG.payment.find((p) => p.id === sel.pay);
  const shipping = sub >= CONFIG.freeShippingFrom ? 0 : ship.price;
  const fee = pay.fee || 0;
  return { sub, shipping, fee, total: Math.round((sub + shipping + fee) * 100) / 100, ship, pay };
}

function renderSummary() {
  const t = totals();
  $('#coItems').innerHTML = Object.entries(cart).map(([id, q]) => {
    const p = byId(id);
    return `<li><span>${q} × ${p.num ? p.name + ' detox' : p.name}</span><span>${fmt(p.price * q)}</span></li>`;
  }).join('');
  $('#coSub').textContent = fmt(t.sub);
  $('#coShip').textContent = t.shipping ? fmt(t.shipping) : 'zadarmo';
  $('#coFeeRow').hidden = !t.fee;
  $('#coFee').textContent = fmt(t.fee);
  $('#coTotal').textContent = fmt(t.total);
}

form.addEventListener('change', (e) => {
  if (e.target.name === 'ship' || e.target.name === 'pay') {
    sel[e.target.name] = e.target.value;
    store.set('liso-checkout', sel);
    renderChoices();
  }
});

$('#checkoutBtn').onclick = () => {
  if (!count()) return;
  toggleCart(false);
  form.hidden = false; $('#coDone').hidden = true; $('#coError').hidden = true;
  const saved = store.get('liso-customer', {});
  for (const k in saved) if (form.elements[k] && !form.elements[k].value) form.elements[k].value = saved[k];
  renderChoices();
  dlg.showModal();
};
$('#coClose').onclick = () => dlg.close();
dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });

function orderId() {
  const d = new Date();
  return `${String(d.getFullYear()).slice(2)}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}${String(Math.floor(Math.random() * 9000) + 1000)}`;
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const err = $('#coError');
  err.hidden = true;
  if (!form.checkValidity()) {
    const bad = form.querySelector(':invalid');
    form.classList.add('tried');
    err.textContent = bad?.name === 'terms' ? 'Na dokončenie potvrďte obchodné podmienky.' : 'Skontrolujte, prosím, zvýraznené polia.';
    err.hidden = false;
    bad?.focus();
    return;
  }
  const f = Object.fromEntries(new FormData(form));
  const t = totals();
  const order = {
    id: orderId(),
    created: new Date().toISOString(),
    customer: { name: f.name, email: f.email, phone: f.phone },
    address: t.ship.address ? { street: f.street, city: f.city, zip: f.zip } : null,
    shipping: t.ship.label, payment: t.pay.label, note: f.note || '',
    items: Object.entries(cart).map(([id, q]) => ({ id, name: byId(id).num ? byId(id).name + ' detox' : byId(id).name, qty: q, price: byId(id).price })),
    subtotal: t.sub, shippingPrice: t.shipping, fee: t.fee, total: t.total,
  };
  store.set('liso-customer', { name: f.name, email: f.email, phone: f.phone, street: f.street || '', city: f.city || '', zip: f.zip || '' });

  const btn = $('#coSubmit');
  btn.disabled = true; btn.textContent = 'Odosielam…';
  let via = 'mail';
  try {
    if (CONFIG.orderEndpoint) {
      const r = await fetch(CONFIG.orderEndpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(order) });
      if (!r.ok) throw new Error('HTTP ' + r.status);
      via = 'server';
    } else {
      location.href = `mailto:${CONFIG.email}?subject=${encodeURIComponent('Objednávka ' + order.id)}&body=${encodeURIComponent(orderText(order))}`;
    }
  } catch (ex) {
    err.textContent = 'Objednávku sa nepodarilo odoslať. Skúste to znova alebo nám napíšte na ' + CONFIG.email + '.';
    err.hidden = false;
    btn.disabled = false; btn.textContent = 'Objednať s povinnosťou platby';
    return;
  }
  btn.disabled = false; btn.textContent = 'Objednať s povinnosťou platby';
  const orders = store.get('liso-orders', []); orders.push(order); store.set('liso-orders', orders);
  cart = {}; saveCart();
  showDone(order, via);
});

function orderText(o) {
  const L = [`Objednávka č. ${o.id}`, '', ...o.items.map((i) => `${i.qty} × ${i.name} — ${fmt(i.price * i.qty)}`), '',
    `Medzisúčet: ${fmt(o.subtotal)}`, `Doprava (${o.shipping}): ${o.shippingPrice ? fmt(o.shippingPrice) : 'zadarmo'}`];
  if (o.fee) L.push(`Poplatok (${o.payment}): ${fmt(o.fee)}`);
  L.push(`SPOLU: ${fmt(o.total)}`, `Platba: ${o.payment}`, '', `Meno: ${o.customer.name}`, `E-mail: ${o.customer.email}`, `Telefón: ${o.customer.phone}`);
  if (o.address) L.push(`Adresa: ${o.address.street}, ${o.address.zip} ${o.address.city}`);
  if (o.note) L.push(`Poznámka: ${o.note}`);
  return L.join('\n');
}

function showDone(o, via) {
  form.hidden = true;
  const bank = o.payment === 'Bankový prevod' ? `
    <div class="bank">
      <div><span>Suma</span><strong>${fmt(o.total)}</strong></div>
      <div><span>IBAN</span><strong>${esc(CONFIG.iban)}</strong></div>
      <div><span>Variabilný symbol</span><strong>${o.id}</strong></div>
    </div>
    <p class="muted">Šťavy lisujeme po pripísaní platby.</p>` : '';
  const mailNote = via === 'mail'
    ? `<p class="notice">Otvoril sa vám e-mail s objednávkou. <b>Odošlite ho</b>, aby sme ju dostali. Ak sa neotvoril, <a href="mailto:${CONFIG.email}?subject=${encodeURIComponent('Objednávka ' + o.id)}&body=${encodeURIComponent(orderText(o))}">kliknite sem</a>.</p>`
    : `<p>Potvrdenie sme poslali na <b>${esc(o.customer.email)}</b>.</p>`;
  $('#coDone').innerHTML = `
    <div class="done-mark"><svg viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></div>
    <h2>Ďakujeme za objednávku</h2>
    <p>Číslo objednávky <b>${o.id}</b> · ${esc(o.shipping)}</p>
    ${mailNote}
    ${bank}
    <button class="btn-dark" type="button" data-close-dialog>Späť do obchodu</button>`;
  $('#coDone').hidden = false;
}

document.addEventListener('click', (e) => {
  const b = e.target.closest('[data-close-dialog]');
  if (b) b.closest('dialog').close();
});

/* ===== Hľadanie ===== */
const sp = $('#searchPanel'), si = $('#searchInput');
$('#searchBtn').onclick = () => {
  sp.hidden = !sp.hidden;
  $('#searchBtn').setAttribute('aria-expanded', !sp.hidden);
  if (!sp.hidden) { si.focus(); search(); }
};
const norm = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
function search() {
  const q = norm(si.value.trim());
  const hits = ALL.filter((p) => !q || norm([p.name, p.desc, ...(p.ingredients || [])].join(' ')).includes(q));
  $('#searchResults').innerHTML = hits.length ? hits.map((p) => `
    <li><span class="sr-dot" style="background:${p.bg || '#222'}"></span>
      <span class="sr-name">${p.num ? p.name + ' detox' : p.name}<small>${p.ingredients ? p.ingredients.join(', ') : p.desc}</small></span>
      <b>${fmt(p.price)}</b><button class="btn-dark btn-sm" data-add="${p.id}">Pridať</button></li>`).join('')
    : '<li class="sr-empty">Nič sme nenašli.</li>';
}
si.addEventListener('input', search);
document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !sp.hidden) { sp.hidden = true; $('#searchBtn').focus(); } });

/* ===== Pätička: najbližšie lisovanie, pás surovín, polička ===== */
(() => {
  // Objednávky po–št do 12:00 lisujeme nasledujúci deň ráno.
  const DAYS = ['nedeľa', 'pondelok', 'utorok', 'streda', 'štvrtok', 'piatok', 'sobota'];
  function nextPress(now) {
    const d = new Date(now);
    for (let i = 0; i < 8; i++) {
      const cut = new Date(d.getFullYear(), d.getMonth(), d.getDate() + i, 12);
      const wd = cut.getDay();
      if (wd >= 1 && wd <= 4 && cut > now) return { cut, press: new Date(cut.getFullYear(), cut.getMonth(), cut.getDate() + 1, 6) };
    }
  }
  const dayName = (d, now) => {
    const diff = Math.round((new Date(d.getFullYear(), d.getMonth(), d.getDate()) - new Date(now.getFullYear(), now.getMonth(), now.getDate())) / 864e5);
    return diff === 0 ? 'dnes' : diff === 1 ? 'zajtra' : DAYS[d.getDay()];
  };
  function update() {
    const now = new Date(), { cut, press } = nextPress(now);
    $('#pressDay').innerHTML = `<span>${dayName(press, now)}</span> ${press.getDate()}. ${press.getMonth() + 1}. <small>o 6:00</small>`;
    const m = Math.max(0, Math.round((cut - now) / 6e4)), h = Math.floor(m / 60);
    const left = h >= 24 ? `${Math.floor(h / 24)} d ${h % 24} h` : `${h} h ${m % 60} min`;
    const ACC = ['v nedeľu', 'v pondelok', 'v utorok', 'v stredu', 'vo štvrtok', 'v piatok', 'v sobotu'];
    const when = { dnes: 'dnes', zajtra: 'zajtra' }[dayName(cut, now)] || ACC[cut.getDay()];
    $('#pressLeft').innerHTML = `Objednajte ${when} do 12:00, zostáva <b>${left}</b>.`;
  }
  update(); setInterval(update, 30000);

  const words = PRODUCTS.flatMap((p) => p.ingredients.slice(0, 3).map((w) => [w, p.bg]));
  const row = words.map(([w, c]) => `<span><i style="background:${c}"></i>${w}</span>`).join('');
  $('#mqTrack').innerHTML = row + row;
  $('#shelf').innerHTML = PRODUCTS.map((p) => `<div class="shelf-b">${ART.bottle(p)}</div>`).join('');
})();

/* ===== Spodná lišta na mobile ===== */
(() => {
  const bar = $('#tabbar'), ind = $('#tabInd');
  const links = [...bar.querySelectorAll('[data-sec]')];
  let cur = null;
  const set = (id) => {
    const a = links.find((l) => l.dataset.sec === id);
    if (!a || a === cur) return;
    cur?.removeAttribute('aria-current'); a.setAttribute('aria-current', 'page'); cur = a;
    ind.style.width = a.offsetWidth + 'px';
    ind.style.translate = a.offsetLeft + 'px 0';
  };
  const map = { domov: 'domov', 'o-nas': 'o-nas', detox: 'detox', obchod: 'obchod', faq: 'obchod', kontakt: 'obchod' };
  const io = new IntersectionObserver((list) => list.forEach((e) => e.isIntersecting && set(map[e.target.id])), { rootMargin: '-45% 0px -50% 0px' });
  Object.keys(map).forEach((id) => { const el = document.getElementById(id); if (el) io.observe(el); });
  links.forEach((l) => l.addEventListener('click', () => set(l.dataset.sec)));
  addEventListener('resize', () => { const a = cur; cur = null; if (a) set(a.dataset.sec); });
  requestAnimationFrame(() => set('domov'));
})();

/* ===== Hlavička ===== */
(() => {
  let last = 0;
  addEventListener('scroll', () => {
    const y = scrollY, h = $('#hdr');
    h.classList.toggle('scrolled', y > 40);
    if (Math.abs(y - last) > 6) {
      h.classList.toggle('hide', y > last && y > 300 && $('#searchPanel').hidden && !$('#mainNav').classList.contains('open'));
      last = y;
    }
  }, { passive: true });
})();

/* ===== Odhaľovanie pri skrolovaní ===== */
(() => {
  document.querySelectorAll('[data-reveal-group]').forEach((g) =>
    [...g.children].forEach((c, k) => { c.setAttribute('data-reveal', ''); c.style.setProperty('--i', k); }));
  const els = document.querySelectorAll('[data-reveal]');
  if (reduced || !('IntersectionObserver' in window)) { els.forEach((e) => e.classList.add('in')); return; }
  // Orezaný prvok (clip-path) observer nevidí, preto sa pri ňom sleduje rodič.
  const map = new Map();
  els.forEach((el) => {
    const t = el.dataset.reveal === 'clip' ? el.parentElement : el;
    map.set(t, [...(map.get(t) || []), el]);
  });
  const io = new IntersectionObserver((list) => list.forEach((e) => {
    if (e.isIntersecting) { map.get(e.target).forEach((el) => el.classList.add('in')); io.unobserve(e.target); }
  }), { rootMargin: '0px 0px -8% 0px', threshold: .12 });
  map.forEach((_, t) => io.observe(t));
})();

/* ===== 3D náklon kariet ===== */
if (!reduced && matchMedia('(pointer: fine)').matches) {
  document.querySelectorAll('.card, .days li').forEach((c) => {
    c.addEventListener('pointermove', (e) => {
      const r = c.getBoundingClientRect();
      c.style.setProperty('--ry', ((e.clientX - r.left) / r.width - .5) * 10 + 'deg');
      c.style.setProperty('--rx', -((e.clientY - r.top) / r.height - .5) * 8 + 'deg');
      c.style.setProperty('--gx', (e.clientX - r.left) / r.width * 100 + '%');
      c.style.setProperty('--gy', (e.clientY - r.top) / r.height * 100 + '%');
    });
    c.addEventListener('pointerleave', () => { c.style.setProperty('--rx', '0deg'); c.style.setProperty('--ry', '0deg'); });
  });
}

/* ===== Právne texty (šablóny — nahraďte vlastnými) ===== */
const LEGAL = {
  vop: ['Obchodné podmienky', `<p><b>Šablóna — nahraďte vlastným textom.</b></p><p>Predávajúci: ${esc(CONFIG.company)}.</p><p>Kúpna zmluva vzniká potvrdením objednávky. Ceny sú uvedené s DPH. Šťavy sú potraviny podliehajúce rýchlej skaze, preto pri nich podľa § 7 ods. 6 písm. d) zákona č. 102/2014 Z. z. nemožno odstúpiť od zmluvy bez udania dôvodu. Reklamácie poškodeného tovaru riešime e-mailom na ${esc(CONFIG.email)} do 24 hodín od prevzatia.</p>`],
  gdpr: ['Ochrana osobných údajov', `<p><b>Šablóna — nahraďte vlastným textom.</b></p><p>Prevádzkovateľ ${esc(CONFIG.company)} spracúva meno, e-mail, telefón a adresu výlučne na vybavenie objednávky (čl. 6 ods. 1 písm. b GDPR) a vedenie účtovníctva. Údaje odovzdáme len dopravcovi. Svoje práva si môžete uplatniť na ${esc(CONFIG.email)}.</p>`],
};
document.addEventListener('click', (e) => {
  const a = e.target.closest('[data-legal]'); if (!a) return;
  e.preventDefault();
  const [t, b] = LEGAL[a.dataset.legal];
  $('#legalTitle').textContent = t; $('#legalBody').innerHTML = b;
  $('#legal').showModal();
});

renderCart();
intro();
restartAuto();
