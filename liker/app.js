/* Zlatý Žĺtok — interakcie, scroll scéna, košík a objednávka */
(function () {
  'use strict';

  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  const smooth = (t) => t * t * (3 - 2 * t);
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const API = (window.LIKER_API || 'api').replace(/\/$/, '');
  const Art = window.ZZArt;

  const store = {
    get(k, d) { try { const v = localStorage.getItem(k); return v == null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* súkromné okno */ } }
  };

  const money = new Intl.NumberFormat('sk-SK', { style: 'currency', currency: 'EUR' });
  const fmt = (cents) => money.format(cents / 100);

  let CATALOG = null;
  let PRODUCTS = {};

  /* ---------------- overenie veku ---------------- */
  function ageGate() {
    const gate = $('#agegate');
    if (store.get('zz-age', false)) return;
    gate.hidden = false;
    document.body.style.overflow = 'hidden';
    const yes = $('#age-yes');
    yes.focus();
    // WCAG 2.4.3: Tab cykluje len medzi tlačidlami dialógu
    gate.addEventListener('keydown', (e) => {
      if (e.key !== 'Tab') return;
      const f = gate.querySelectorAll('button, a[href]'); const first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
    yes.addEventListener('click', () => {
      store.set('zz-age', true);
      gate.hidden = true;
      document.body.style.overflow = '';
    });
  }

  /* ---------------- scroll scéna ---------------- */
  const CH = [0, 0.08, 0.36, 0.64]; // začiatky kapitol
  const story = $('#story');
  const stage = $('#stage');
  const bottleEl = $('#hero-bottle');
  const glassEl = $('#glass');
  const megas = $$('.mega');
  const chaps = $$('.chap');
  const navLinks = $$('.chapnav a');

  const isMobile = () => window.matchMedia('(max-width: 760px), (max-aspect-ratio: 4/5)').matches;

  // kľúčové snímky fľaše: x vo vw, y vo vh, r v stupňoch, s mierka
  const BOTTLE_KEYS = {
    desk: [
      { p: 0, x: 0, y: 0, r: -3, s: 1 }, { p: 0.04, x: 0, y: 0, r: -3, s: 1 },
      { p: 0.16, x: 0, y: 0, r: 4, s: 1 }, { p: 0.32, x: 0, y: 0, r: 4, s: 1 },
      { p: 0.44, x: 0, y: 1, r: -2, s: 0.95 }, { p: 0.6, x: 0, y: 1, r: -2, s: 0.95 },
      { p: 0.74, x: 5, y: 0, r: 7, s: 1 }, { p: 1, x: 5, y: 0, r: 7, s: 1 }
    ],
    mob: [
      { p: 0, x: 0, y: 0, r: -3, s: 1 }, { p: 0.04, x: 0, y: 0, r: -3, s: 1 },
      { p: 0.16, x: 0, y: 0, r: 4, s: 1 }, { p: 0.32, x: 0, y: 0, r: 4, s: 1 },
      { p: 0.44, x: 0, y: 0, r: -2, s: 0.9 }, { p: 0.6, x: 0, y: 0, r: -2, s: 0.9 },
      { p: 0.74, x: 16, y: 0, r: 7, s: 0.92 }, { p: 1, x: 16, y: 0, r: 7, s: 0.92 }
    ]
  };
  const GLASS_KEYS = {
    desk: [{ p: 0, x: -70, r: -30, o: 0 }, { p: 0.62, x: -70, r: -30, o: 0 }, { p: 0.76, x: -11, r: -4, o: 1 }, { p: 1, x: -11, r: -4, o: 1 }],
    mob: [{ p: 0, x: -80, r: -30, o: 0 }, { p: 0.62, x: -80, r: -30, o: 0 }, { p: 0.76, x: -19, r: -4, o: 1 }, { p: 1, x: -19, r: -4, o: 1 }]
  };
  const SPLATS = [
    { name: 'Žĺtky', txt: 'Čerstvé, z voľného výbehu, oddeľované ručne.', color: '#F2C14E', cls: 'splat--yolk', seed: 11,
      desk: [-23, -4], mob: [-30, -15] },
    { name: 'Vanilka', txt: 'Celé struky z Madagaskaru, lúhované tri týždne.', color: '#B5172F', seed: 23,
      desk: [-17, 24], mob: [-29, 13] },
    { name: 'Škorica', txt: 'Cejlónska — jemnejšia a sladšia než bežná.', color: '#B5172F', seed: 41,
      desk: [21, -8], mob: [30, -15] },
    { name: 'Rum', txt: 'Tmavý, s tónmi karamelu a sušeného ovocia.', color: '#1D1713', seed: 57,
      desk: [22, 21], mob: [29, 13] }
  ];

  function interp(keys, p) {
    let i = 0;
    while (i < keys.length - 2 && p > keys[i + 1].p) i++;
    const a = keys[i], b = keys[i + 1];
    const t = smooth(clamp((p - a.p) / (b.p - a.p || 1), 0, 1));
    const out = {};
    for (const k in a) if (k !== 'p') out[k] = lerp(a[k], b[k], t);
    return out;
  }

  function splitMegas() {
    megas.forEach((m) => {
      const text = m.textContent.trim();
      m.textContent = '';
      const ln = document.createElement('span');
      ln.className = 'ln distress';
      [...text].forEach((c, i) => {
        const s = document.createElement('span');
        s.className = 'ch';
        s.style.setProperty('--i', i);
        s.textContent = c;
        ln.appendChild(s);
      });
      m.appendChild(ln);
    });
  }

  function fitMegas() {
    const max = stage.clientWidth * (isMobile() ? 0.94 : 0.9);
    const base = isMobile() ? stage.clientWidth * 0.26 : Math.min(stage.clientWidth * 0.17, 320);
    megas.forEach((m) => {
      m.style.setProperty('--fs', base + 'px');
      const w = m.firstChild.scrollWidth;
      if (w > max) m.style.setProperty('--fs', Math.floor(base * max / w) + 'px');
    });
  }

  function buildSplats() {
    const wrap = $('#splats');
    wrap.innerHTML = SPLATS.map((s, i) => `
      <div class="splat ${s.cls || ''}" style="--i:${i}">
        ${Art.splat(s.seed, s.color)}
        <div class="splat__txt"><b>${s.name}</b><span>${s.txt}</span></div>
      </div>`).join('');
    placeSplats();
  }
  function placeSplats() {
    const mob = isMobile();
    const vw = window.innerWidth / 100, vh = stage.clientHeight / 100;
    $$('.splat').forEach((el, i) => {
      const [x, y] = SPLATS[i][mob ? 'mob' : 'desk'];
      el.style.setProperty('--x', (x * vw).toFixed(1) + 'px');
      el.style.setProperty('--y', (y * vh).toFixed(1) + 'px');
    });
  }

  let target = 0, current = 0, activeCh = -1, storyVisible = true;

  function readProgress() {
    const rect = story.getBoundingClientRect();
    const total = story.offsetHeight - window.innerHeight;
    target = clamp(-rect.top / (total || 1), 0, 1);
  }

  function setChapter(n) {
    if (n === activeCh) return;
    activeCh = n;
    megas.forEach((m) => {
      const c = +m.dataset.ch;
      m.classList.toggle('is-active', c === n);
      m.classList.toggle('is-past', c < n);
    });
    chaps.forEach((c) => c.classList.toggle('is-active', +c.dataset.ch === n));
    navLinks.forEach((a, i) => a.classList.toggle('is-active', i === n));
    $$('.splat').forEach((s) => s.classList.toggle('is-on', n === 2));
  }

  function renderStage() {
    const mob = isMobile();
    const vw = window.innerWidth / 100, vh = stage.clientHeight / 100;
    const b = interp(BOTTLE_KEYS[mob ? 'mob' : 'desk'], current);
    bottleEl.style.transform = `translate3d(${(b.x * vw).toFixed(1)}px, ${(b.y * vh).toFixed(1)}px, 0) rotate(${b.r.toFixed(2)}deg) scale(${b.s.toFixed(3)})`;
    const g = interp(GLASS_KEYS[mob ? 'mob' : 'desk'], current);
    const gw = glassEl.offsetWidth;
    glassEl.style.transform = `translate3d(${(g.x * vw - gw / 2).toFixed(1)}px, 0, 0) rotate(${g.r.toFixed(2)}deg)`;
    glassEl.style.opacity = g.o.toFixed(3);

    let n = 0;
    for (let i = CH.length - 1; i >= 0; i--) if (target >= CH[i]) { n = i; break; }
    setChapter(n);

    // jemná paralaxa nadpisu v rámci kapitoly
    const start = CH[n], end = CH[n + 1] ?? 1;
    const local = (current - start) / (end - start);
    const m = megas[n];
    if (m) m.style.setProperty('--py', ((0.5 - local) * 40).toFixed(1) + 'px');
  }

  function loop() {
    if (storyVisible) {
      current = reduced ? target : current + (target - current) * 0.12;
      if (Math.abs(target - current) < 0.0004) current = target;
      renderStage();
    }
    requestAnimationFrame(loop);
  }

  function gotoChapter(n) {
    const total = story.offsetHeight - window.innerHeight;
    const start = CH[n], end = CH[n + 1] ?? 1;
    const p = n === 0 ? 0 : start + (end - start) * 0.45;
    window.scrollTo({ top: story.offsetTop + p * total, behavior: reduced ? 'auto' : 'smooth' });
  }

  function initStory() {
    bottleEl.style.transformOrigin = '50% 92%';
    glassEl.style.transformOrigin = '50% 100%';
    splitMegas();
    buildSplats();
    navLinks.forEach((a) => a.setAttribute('data-label', a.textContent));
    $$('[data-goto]').forEach((a) => a.addEventListener('click', (e) => { e.preventDefault(); gotoChapter(+a.dataset.goto); }));
    new IntersectionObserver((es) => { storyVisible = es[0].isIntersecting; }, { rootMargin: '100px' }).observe(story);
    window.addEventListener('scroll', readProgress, { passive: true });
    const onResize = () => { fitMegas(); placeSplats(); readProgress(); };
    window.addEventListener('resize', onResize);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(onResize);
    fitMegas();
    readProgress();
    current = target;
    renderStage();
    requestAnimationFrame(loop);
  }

  /* ---------------- produkty ---------------- */
  function renderShelf() {
    const shelf = $('#shelf');
    shelf.innerHTML = CATALOG.products.map((p, i) => `
      <article class="card" style="--i:${i}; --c:${p.colors.label}">
        <div class="card__stage">
          <span class="card__badge ${p.id === 'vianocny' ? 'card__badge--hot' : ''}">${p.badge}</span>
          <div class="card__splat">${Art.splat(100 + i * 13, p.colors.label === '#F1E1BF' ? '#E7CF9F' : p.colors.label, { drip: false })}</div>
          <div class="card__bottle">${Art.bottle(p)}</div>
        </div>
        <div class="card__body">
          <p class="card__meta">${p.volume} · ${p.abv} % obj.</p>
          <h3 class="card__name">${p.short}</h3>
          <p class="card__desc">${p.desc}</p>
          <div class="card__foot">
            <span class="card__price">${fmt(p.price)}</span>
            <button class="btn btn--red" type="button" data-add="${p.id}">Do košíka</button>
          </div>
        </div>
      </article>`).join('');
    const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } }), { threshold: 0.15 });
    $$('.card', shelf).forEach((c) => io.observe(c));
  }

  /* ---------------- košík ---------------- */
  let cart = store.get('zz-cart', {});
  const form = $('#checkout');
  const payment = () => (form.elements.payment.value || 'card');

  function cleanCart() {
    for (const id in cart) if (!PRODUCTS[id] || !(cart[id] > 0)) delete cart[id];
  }
  function count() { return Object.values(cart).reduce((a, b) => a + b, 0); }
  function totals(pay) {
    const s = CATALOG.shipping;
    const subtotal = Object.entries(cart).reduce((sum, [id, q]) => sum + PRODUCTS[id].price * q, 0);
    const shipping = subtotal === 0 || subtotal >= s.freeFrom ? 0 : s.price;
    const cod = pay === 'cod' && subtotal > 0 ? s.codFee : 0;
    return { subtotal, shipping, cod, total: subtotal + shipping + cod };
  }

  function setQty(id, q) {
    q = clamp(Math.round(q), 0, 24);
    if (q === 0) delete cart[id]; else cart[id] = q;
    store.set('zz-cart', cart);
    renderCart();
  }
  function add(id, n = 1, from) {
    if (!PRODUCTS[id]) return;
    setQty(id, (cart[id] || 0) + n);
    flyToCart(id, from);
    toast(`${n} × ${PRODUCTS[id].short} v košíku`);
  }

  function renderCart() {
    if (!CATALOG) return;
    const t = totals(payment());
    const n = count();
    $$('[data-cart-count]').forEach((el) => { el.textContent = n; });
    $$('[data-total]').forEach((el) => { el.textContent = fmt(t.total); });
    $$('[data-subtotal]').forEach((el) => { el.textContent = fmt(t.subtotal); });
    $$('[data-shipping]').forEach((el) => { el.textContent = t.shipping === 0 && t.subtotal > 0 ? 'zadarmo' : fmt(t.shipping || CATALOG.shipping.price); });
    $$('[data-cod]').forEach((el) => { el.textContent = fmt(CATALOG.shipping.codFee); });
    $$('[data-cod-fee]').forEach((el) => { el.textContent = '+' + fmt(CATALOG.shipping.codFee); });
    $('[data-cod-row]').hidden = t.cod === 0;
    $('[data-submit-label]').textContent = payment() === 'cod' ? 'Objednať na dobierku' : 'Zaplatiť kartou';

    const left = CATALOG.shipping.freeFrom - t.subtotal;
    $('[data-free-hint]').textContent = t.subtotal === 0 ? '' : left > 0 ? `Pridajte ešte za ${fmt(left)} a doprava bude zadarmo.` : 'Doprava zadarmo ✓';

    const list = $('#summary-items');
    const ids = Object.keys(cart);
    $('#summary-empty').hidden = ids.length > 0;
    // zachovať existujúce riadky (kvôli animácii), pridať nové, zmazať staré
    const existing = new Map($$('.summary__item', list).map((li) => [li.dataset.id, li]));
    existing.forEach((li, id) => { if (!cart[id]) li.remove(); });
    ids.forEach((id) => {
      const p = PRODUCTS[id];
      let li = existing.get(id);
      if (!li) {
        li = document.createElement('li');
        li.className = 'summary__item';
        li.dataset.id = id;
        li.innerHTML = `
          <div class="summary__thumb">${Art.bottle(p)}</div>
          <div>
            <p class="summary__name">${p.name} <small>${p.volume}</small></p>
            <div class="summary__ctrl">
              <div class="qty qty--sm"><button type="button" data-dec aria-label="Menej">−</button><output></output><button type="button" data-inc aria-label="Viac">+</button></div>
              <button type="button" class="summary__rm" data-rm>Odstrániť</button>
            </div>
          </div>
          <span class="summary__line"></span>`;
        list.appendChild(li);
      }
      $('output', li).textContent = cart[id];
      $('.summary__line', li).textContent = fmt(p.price * cart[id]);
    });
    updateCartbar();
  }

  $('#summary-items').addEventListener('click', (e) => {
    const li = e.target.closest('.summary__item');
    if (!li) return;
    const id = li.dataset.id;
    if (e.target.closest('[data-inc]')) setQty(id, cart[id] + 1);
    else if (e.target.closest('[data-dec]')) setQty(id, cart[id] - 1);
    else if (e.target.closest('[data-rm]')) setQty(id, 0);
  });

  let orderVisible = false;
  function updateCartbar() {
    const bar = $('#cartbar');
    const on = count() > 0 && !orderVisible;
    if (on) bar.hidden = false;
    requestAnimationFrame(() => bar.classList.toggle('is-on', on));
  }

  function flyToCart(id, from) {
    const cartBtn = $('.cartbtn');
    cartBtn.classList.remove('bump'); void cartBtn.offsetWidth; cartBtn.classList.add('bump');
    if (reduced || !from || !from.getBoundingClientRect) return;
    const a = from.getBoundingClientRect(), b = cartBtn.getBoundingClientRect();
    const f = document.createElement('div');
    f.className = 'flyer';
    f.innerHTML = Art.bottle(PRODUCTS[id], { plain: true });
    f.style.left = (a.left + a.width / 2 - 20) + 'px';
    f.style.top = (a.top + a.height / 2 - 60) + 'px';
    document.body.appendChild(f);
    const dx = b.left + b.width / 2 - (a.left + a.width / 2), dy = b.top + b.height / 2 - (a.top + a.height / 2);
    const anim = f.animate([
      { transform: 'translate(0,0) scale(1) rotate(0deg)', opacity: 1 },
      { transform: `translate(${dx * 0.5}px, ${dy * 0.5 - 120}px) scale(.8) rotate(-120deg)`, opacity: 1, offset: 0.5 },
      { transform: `translate(${dx}px, ${dy}px) scale(.25) rotate(-300deg)`, opacity: 0.2 }
    ], { duration: 850, easing: 'cubic-bezier(.5,0,.3,1)' });
    anim.onfinish = () => f.remove();
  }

  let toastTimer;
  function toast(msg) {
    const t = $('#toast');
    t.textContent = msg;
    t.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('is-on'), 2200);
  }

  function bindBuyButtons() {
    document.addEventListener('click', (e) => {
      const buy = e.target.closest('[data-buy]');
      const addBtn = e.target.closest('[data-add]');
      if (buy) {
        add(buy.dataset.buy, 1, buy);
        if (!buy.closest('.summary')) setTimeout(() => $('#objednavka').scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' }), 350);
      } else if (addBtn) {
        add(addBtn.dataset.add, 1, addBtn.closest('.card')?.querySelector('.card__bottle') || addBtn);
      }
    });
    // lokálny výber množstva v nákupnom boxe
    $$('[data-qty-local]').forEach((q) => {
      const out = $('output', q);
      q.addEventListener('click', (e) => {
        const b = e.target.closest('[data-step]');
        if (b) out.textContent = clamp(+out.textContent + +b.dataset.step, 1, 24);
      });
    });
    $$('[data-add-local]').forEach((b) => b.addEventListener('click', () => {
      const out = $('[data-qty-local] output', b.closest('.panel'));
      add(b.dataset.addLocal, +out.textContent, bottleEl);
      out.textContent = 1;
    }));
    $$('[data-price]').forEach((el) => { const p = PRODUCTS[el.dataset.price]; if (p) el.textContent = fmt(p.price); });
  }

  /* ---------------- formulár a odoslanie ---------------- */
  function showError(msg) {
    const box = $('#form-error');
    box.textContent = msg;
    box.hidden = !msg;
    if (msg) box.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'center' });
  }

  function validate() {
    let ok = true, first = null;
    $$('input, select, textarea', form).forEach((el) => {
      if (el.type === 'radio') return;
      let valid = el.checkValidity();
      if (el.name === 'zip' && valid) valid = /^\d{3}\s?\d{2}$/.test(el.value.trim());
      if (el.name === 'phone' && valid) valid = el.value.replace(/[^\d]/g, '').length >= 9;
      const wrap = el.closest('.field, .check');
      if (wrap) wrap.classList.toggle('is-invalid', !valid);
      if (valid) el.removeAttribute('aria-invalid'); else el.setAttribute('aria-invalid', 'true'); // WCAG 3.3.1
      if (!valid) { ok = false; first = first || el; }
    });
    if (first) first.focus({ preventScroll: false });
    return ok;
  }

  form.addEventListener('change', (e) => { if (e.target.name === 'payment') renderCart(); });
  form.addEventListener('input', (e) => {
    const wrap = e.target.closest('.is-invalid');
    if (wrap && e.target.checkValidity()) { wrap.classList.remove('is-invalid'); e.target.removeAttribute('aria-invalid'); }
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    showError('');
    if (count() === 0) { showError('Košík je prázdny. Pridajte aspoň jednu fľašu.'); return; }
    if (!validate()) { showError('Skontrolujte, prosím, označené polia.'); return; }

    const fd = new FormData(form);
    const payload = {
      items: Object.entries(cart).map(([id, qty]) => ({ id, qty })),
      payment: fd.get('payment'),
      adult: fd.get('adult') === 'on',
      terms: fd.get('terms') === 'on',
      customer: {
        name: fd.get('name'), email: fd.get('email'), phone: fd.get('phone'),
        street: fd.get('street'), city: fd.get('city'), zip: fd.get('zip'),
        country: fd.get('country'), note: fd.get('note')
      }
    };
    // náhľad bez servera: objednávka sa len nasimuluje
    if (window.LIKER_DEMO) {
      const d = new Date();
      const id = 'ZZ-' + d.toISOString().slice(2, 10).replace(/-/g, '') + '-' + Math.random().toString(16).slice(2, 8).toUpperCase().padEnd(6, '0');
      store.set('zz-last-order', { id, payment: payload.payment, status: payload.payment === 'cod' ? 'new_cod' : 'demo', total: totals(payload.payment).total, firstName: String(payload.customer.name).trim().split(' ')[0] });
      if (window.LIKER_STANDALONE) { showThanks(id, payload); return; }
      window.location.href = `dakujeme.html?order=${id}&method=${payload.payment}&demo=1`;
      return;
    }
    const btn = $('#submit-btn');
    const label = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = payload.payment === 'card' ? 'Presmerúvam na platbu…' : 'Odosielam objednávku…';
    try {
      const res = await fetch(API + '/order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.url) {
        if (res.status === 404 || res.status === 405) throw new Error('Objednávkový server nebeží. Spustite web cez „node server.js“ (pozri README).');
        throw new Error(data.error || 'Objednávku sa nepodarilo odoslať. Skúste to znova.');
      }
      window.location.href = data.url;
    } catch (err) {
      showError(err.message && !/Failed to fetch|NetworkError|Load failed/.test(err.message) ? err.message : 'Spojenie zlyhalo. Skontrolujte internet a skúste znova.');
      btn.disabled = false;
      btn.innerHTML = label;
      renderCart();
    }
  });

  /* poďakovanie priamo na stránke (samostatná ukážka v jednom súbore) */
  function showThanks(id, payload) {
    const t = totals(payload.payment);
    const name = String(payload.customer.name).trim().split(' ')[0];
    const cod = payload.payment === 'cod';
    const wrap = document.createElement('div');
    wrap.className = 'agegate';
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.innerHTML = `
      <div class="agegate__card">
        <div class="thanks__bottle" style="height:200px;animation:none">${Art.bottle(PRODUCTS.vianocny)}</div>
        <span class="demo-flag">Ukážka — objednávka sa neodoslala</span>
        <p class="eyebrow">${cod ? 'Objednávka na dobierku prijatá' : 'Platba prijatá'}</p>
        <h2>Ďakujeme${name ? ', ' + name.replace(/[<>&"]/g, '') : ''}!</h2>
        <p>${cod ? 'Fľaše zabalíme a odošleme do 2 pracovných dní. Sumu zaplatíte kuriérovi pri prevzatí.' : 'Na ostrom webe by tu zákazník zaplatil kartou cez Stripe.'}</p>
        <p class="thanks__box">Číslo objednávky: ${id} · ${fmt(t.total)}</p>
        <div class="agegate__actions"><button class="btn btn--red" type="button">Späť na web</button></div>
      </div>`;
    document.body.appendChild(wrap);
    document.body.style.overflow = 'hidden';
    cart = {}; store.set('zz-cart', cart); renderCart();
    form.reset(); renderCart();
    $('button', wrap).focus();
    $('button', wrap).addEventListener('click', () => {
      wrap.remove();
      document.body.style.overflow = '';
      window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
    });
  }

  /* ---------------- sneh ---------------- */
  function snow() {
    const cv = $('#snow');
    if (!cv || reduced) return;
    const ctx = cv.getContext('2d');
    let w, h, flakes = [], on = false, dpr;
    function size() {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      w = cv.clientWidth; h = cv.clientHeight;
      cv.width = w * dpr; cv.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const n = Math.round(Math.min(160, w * h / 9000));
      flakes = Array.from({ length: n }, () => ({ x: Math.random() * w, y: Math.random() * h, r: 1 + Math.random() * 3, s: 0.3 + Math.random() * 1.1, d: Math.random() * Math.PI * 2 }));
    }
    function tick() {
      if (!on) return;
      ctx.clearRect(0, 0, w, h);
      ctx.fillStyle = 'rgba(255,248,236,.85)';
      ctx.beginPath();
      for (const f of flakes) {
        f.y += f.s; f.d += 0.01; f.x += Math.sin(f.d) * 0.4;
        if (f.y > h + 5) { f.y = -5; f.x = Math.random() * w; }
        ctx.moveTo(f.x + f.r, f.y);
        ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
      }
      ctx.fill();
      requestAnimationFrame(tick);
    }
    size();
    window.addEventListener('resize', size);
    new IntersectionObserver((es) => { const was = on; on = es[0].isIntersecting; if (on && !was) tick(); }).observe(cv);
  }

  /* ---------------- odpočet do Štedrého dňa ---------------- */
  function countdown() {
    const now = new Date();
    let target = new Date(now.getFullYear(), 11, 24);
    if (now > new Date(now.getFullYear(), 11, 26, 23, 59)) target = new Date(now.getFullYear() + 1, 11, 24);
    const els = {};
    $$('[data-cd]').forEach((el) => { els[el.dataset.cd] = el; });
    const pad = (n) => String(n).padStart(2, '0');
    function tick() {
      let s = Math.max(0, Math.floor((target - new Date()) / 1000));
      const d = Math.floor(s / 86400); s -= d * 86400;
      const h = Math.floor(s / 3600); s -= h * 3600;
      const m = Math.floor(s / 60); s -= m * 60;
      els.d.textContent = pad(d); els.h.textContent = pad(h); els.m.textContent = pad(m); els.s.textContent = pad(s);
    }
    tick();
    setInterval(tick, 1000);
  }

  /* ---------------- ostatné ---------------- */
  function misc() {
    const top = $('.topbar');
    const onScroll = () => top.classList.toggle('is-scrolled', window.scrollY > 10);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } }), { threshold: 0.2 });
    $$('.reveal').forEach((el) => io.observe(el));
    new IntersectionObserver((es) => { orderVisible = es[0].isIntersecting; updateCartbar(); }, { threshold: 0.05 }).observe($('#objednavka'));
    $('#year').textContent = new Date().getFullYear();
  }

  /* ---------------- štart ---------------- */
  async function loadCatalog() {
    if (window.LIKER_CATALOG) return window.LIKER_CATALOG;
    const res = await fetch('data/products.json', { cache: 'no-cache' });
    if (!res.ok) throw new Error('Katalóg sa nenačítal');
    return res.json();
  }

  async function init() {
    ageGate();
    try {
      CATALOG = await loadCatalog();
    } catch (e) {
      console.error(e);
      toast('Produkty sa nenačítali. Otvorte web cez server (README).');
      return;
    }
    CATALOG.products.forEach((p) => { PRODUCTS[p.id] = p; });
    cleanCart();

    $('.bottle-float').innerHTML = Art.bottle(PRODUCTS.vianocny);
    glassEl.innerHTML = Art.glass();
    initStory();
    renderShelf();
    bindBuyButtons();
    renderCart();
    snow();
    countdown();
    misc();
    requestAnimationFrame(() => document.body.classList.add('is-loaded'));
  }

  init();
})();
