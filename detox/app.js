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

/* ===== Slider ===== */
let idx = 0, busy = false, heroQty = 1;
const root = document.documentElement;

function renderHero(i, first) {
  const p = PRODUCTS[i];
  root.style.setProperty('--bg', p.bg);
  $('meta[name=theme-color]').content = p.bg;
  $('#hTitle').innerHTML = `<span>${p.name}</span> <span>detox</span>`;
  $('#hPrice').textContent = fmt(p.price) + ' · 300 ml';
  $('#hLead').textContent = p.lead;
  $('#hDesc').textContent = p.desc;
  $('#hIdx').textContent = i + 1;
  heroQty = 1; $('#hQty').textContent = 1;

  const oval = $('#hOval'), bw = $('#hBottle');
  const layer = document.createElement('div');
  layer.className = 'oval-layer';
  layer.innerHTML = ART.fruit(p.fruit, p.seed);
  const bottle = document.createElement('div');
  bottle.className = 'bottle';
  bottle.innerHTML = ART.bottle(p);
  bottle.setAttribute('role', 'img');
  bottle.setAttribute('aria-label', `Fľaša ${p.name} detox, ${p.ingredients.join(', ')}`);

  if (first) { layer.classList.add('in'); bottle.classList.add('in'); oval.append(layer); bw.append(bottle); return; }
  const oldL = [...oval.children], oldB = [...bw.children];
  oldB.forEach((b) => b.classList.add('out'));
  oval.append(layer); bw.append(bottle);
  requestAnimationFrame(() => requestAnimationFrame(() => { layer.classList.add('in'); bottle.classList.add('in'); }));
  setTimeout(() => { oldL.forEach((n) => n.remove()); oldB.forEach((n) => n.remove()); }, reduced ? 0 : 900);
}

function go(dir) {
  if (busy) return;
  const next = (idx + dir + PRODUCTS.length) % PRODUCTS.length;
  if (reduced) { idx = next; renderHero(idx); return; }
  busy = true;
  document.querySelectorAll('.swap').forEach((el) => el.classList.add('out'));
  $('#hBottle').querySelectorAll('.bottle').forEach((b) => b.classList.add('out'));
  setTimeout(() => {
    idx = next;
    renderHero(idx);
    document.querySelectorAll('.swap').forEach((el) => el.classList.remove('out'));
    setTimeout(() => (busy = false), 450);
  }, 380);
}

$('#hTotal').textContent = PRODUCTS.length;
$('#prevBtn').onclick = () => go(-1);
$('#nextBtn').onclick = () => go(1);
$('#hMinus').onclick = () => { heroQty = Math.max(1, heroQty - 1); $('#hQty').textContent = heroQty; };
$('#hPlus').onclick = () => { heroQty = Math.min(30, heroQty + 1); $('#hQty').textContent = heroQty; };
$('#hAdd').onclick = () => add(PRODUCTS[idx].id, heroQty);

document.addEventListener('keydown', (e) => {
  if (e.target.closest('input,textarea,dialog[open]') || cartOpen) return;
  const hero = $('.hero').getBoundingClientRect();
  if (hero.bottom < 100) return;
  if (e.key === 'ArrowRight') go(1);
  if (e.key === 'ArrowLeft') go(-1);
});
(() => {
  let x0 = null, y0 = 0;
  const v = $('#hVisual');
  v.addEventListener('pointerdown', (e) => { x0 = e.clientX; y0 = e.clientY; });
  v.addEventListener('pointerup', (e) => {
    if (x0 === null) return;
    const dx = e.clientX - x0, dy = e.clientY - y0; x0 = null;
    if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) go(dx < 0 ? 1 : -1);
  });
})();

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
  if (t !== idx) { idx = t; renderHero(idx); }
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
  if (b) add(b.dataset.add, 1);
});
$('#shipNote').textContent = `Doprava zadarmo od ${fmt(CONFIG.freeShippingFrom)} · lisujeme v deň odoslania`;
$('#ftrCompany').textContent = CONFIG.company;
$('#ftrMail').textContent = CONFIG.email;
$('#ftrMail').href = 'mailto:' + CONFIG.email;

/* ===== Košík ===== */
let cart = store.get('liso-cart', {});
for (const id in cart) if (!byId(id)) delete cart[id];
let cartOpen = false;

const subtotal = () => Object.entries(cart).reduce((s, [id, q]) => s + byId(id).price * q, 0);
const count = () => Object.values(cart).reduce((s, q) => s + q, 0);

function add(id, q) {
  cart[id] = Math.min(99, (cart[id] || 0) + q);
  saveCart();
  const p = byId(id);
  toast(`${p.name}${p.num ? ' detox' : ''} × ${q} — v košíku`);
  const c = $('#cartCount'); c.classList.remove('pop'); void c.offsetWidth; c.classList.add('pop');
}
function setQty(id, q) { if (q <= 0) delete cart[id]; else cart[id] = Math.min(99, q); saveCart(); }
function saveCart() { store.set('liso-cart', cart); renderCart(); }

function renderCart() {
  const n = count(), sub = subtotal();
  const badge = $('#cartCount');
  badge.hidden = !n; badge.textContent = n;
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
  if (open) setTimeout(() => $('#cartClose').focus(), 50); else $('#cartBtn').focus({ preventScroll: true });
}
$('#cartBtn').onclick = () => toggleCart(true);
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
    <button class="btn-dark" type="button" onclick="this.closest('dialog').close()">Späť do obchodu</button>`;
  $('#coDone').hidden = false;
}

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

/* ===== Menu, hlavička ===== */
$('#menuBtn').onclick = () => {
  const open = !$('#mainNav').classList.contains('open');
  $('#mainNav').classList.toggle('open', open);
  $('#menuBtn').setAttribute('aria-expanded', open);
};
$('#mainNav').addEventListener('click', (e) => { if (e.target.closest('a')) $('#mainNav').classList.remove('open'); });
addEventListener('scroll', () => $('#hdr').classList.toggle('scrolled', scrollY > 40), { passive: true });

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

renderHero(0, true);
renderCart();
