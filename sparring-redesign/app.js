/* Sparring redesign — interakcie a filmové efekty.
   Bez knižníc. Všetok obsah je v HTML (SEO), JS iba pridáva pohyb. */
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const mobile = () => innerWidth <= 760;

  /* ---------- 1. Letterbox intro (raz za session, max ~1.4 s) ---------- */
  const intro = $('.intro');
  let seen = false;
  try { seen = sessionStorage.getItem('sp-intro') === '1'; } catch (e) {}
  const startPage = () => {
    $$('[data-split]').forEach((el) => io.observe(el));
    $('.hero__title')?.classList.add('is-in');
    setTimeout(() => $('.hud')?.classList.add('is-on'), 600);
  };
  if (!intro || reduce || seen) {
    intro?.classList.add('is-done');
    requestAnimationFrame(() => startPage());
  } else {
    try { sessionStorage.setItem('sp-intro', '1'); } catch (e) {}
    const num = $('[data-countdown]');
    let n = 3;
    const t0 = performance.now();
    const tick = (t) => {
      const el = (t - t0) / 1000;               // 0..0.9 s countdown
      const step = Math.min(2, Math.floor(el / 0.3));
      if (3 - step !== n) { n = 3 - step; num.textContent = n; }
      num.style.setProperty('--p', `${((el % 0.3) / 0.3) * 360}deg`);
      if (el < 0.9) requestAnimationFrame(tick);
      else {
        intro.classList.add('is-open');
        startPage();
        setTimeout(() => intro.classList.add('is-done'), 1200);
      }
    };
    requestAnimationFrame(tick);
    addEventListener('keydown', () => { intro.classList.add('is-open', 'is-done'); }, { once: true });
  }

  /* ---------- 2. Split text (slovo po slove, blur → ostro) ---------- */
  $$('[data-split]').forEach((el) => {
    let i = 0;
    const walk = (node) => {
      [...node.childNodes].forEach((c) => {
        if (c.nodeType === 3) {
          const frag = document.createDocumentFragment();
          c.textContent.split(/(\s+)/).forEach((part) => {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.append(' '); return; }
            const w = document.createElement('span'); w.className = 'w';
            const s = document.createElement('span'); s.textContent = part; s.style.setProperty('--i', i++);
            w.append(s); frag.append(w);
          });
          c.replaceWith(frag);
        } else if (c.nodeType === 1) walk(c);
      });
    };
    walk(el);
  });

  /* ---------- 3. Reveal on scroll ---------- */
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
  }, { rootMargin: '0px 0px -12% 0px', threshold: 0.12 });
  $$('[data-reveal]').forEach((el) => io.observe(el));

  /* ---------- 4. Navigation ---------- */
  const nav = $('[data-nav]');
  $$('[data-mega]').forEach((b) => {
    const item = b.parentElement;
    const set = (open) => { item.classList.toggle('is-open', open); b.setAttribute('aria-expanded', open); };
    b.addEventListener('click', (e) => { e.stopPropagation(); const o = !item.classList.contains('is-open'); $$('.nav__item.is-open').forEach((x) => x !== item && x.classList.remove('is-open')); set(o); });
    if (matchMedia('(hover: hover)').matches) {
      item.addEventListener('mouseenter', () => innerWidth > 1080 && set(true));
      item.addEventListener('mouseleave', () => innerWidth > 1080 && set(false));
    }
  });
  document.addEventListener('click', () => $$('.nav__item.is-open').forEach((x) => { x.classList.remove('is-open'); $('[data-mega]', x).setAttribute('aria-expanded', false); }));
  const burger = $('[data-burger]');
  burger.addEventListener('click', () => { const o = nav.classList.toggle('is-menu'); burger.setAttribute('aria-expanded', o); });

  /* ---------- 5. Scroll loop: nav, HUD timecode, reel pin ---------- */
  const hudTc = $('[data-hud-tc]'), hudScene = $('[data-hud-scene]');
  const scenes = $$('.scene');
  const reel = $('[data-reel]'), track = $('[data-reel-track]'), fill = $('[data-reel-fill]');
  let reelDist = 0, lastY = scrollY, ticking = false;

  const pinReel = !reduce;
  const measureReel = () => {
    if (!reel) return;
    const on = pinReel && !mobile();
    reel.classList.toggle('is-pinned', on);
    if (!on) { track.style.transform = ''; reelDist = 0; reel.style.removeProperty('--reel-h'); return; }
    reelDist = Math.max(0, track.scrollWidth - innerWidth);
    reel.style.setProperty('--reel-h', `${innerHeight + reelDist}px`);
  };

  const pad = (n) => String(Math.floor(n)).padStart(2, '0');
  const onScroll = () => {
    ticking = false;
    const y = scrollY;
    nav.classList.toggle('is-solid', y > 40);
    nav.classList.toggle('is-hidden', y > 600 && y > lastY && !nav.classList.contains('is-menu') && !$('.nav__item.is-open'));
    lastY = y;

    // timecode: celá stránka = 3 min „filmu“ pri 24 fps
    const max = document.documentElement.scrollHeight - innerHeight;
    const secs = (y / Math.max(1, max)) * 180;
    hudTc.textContent = `00:${pad(secs / 60)}:${pad(secs % 60)}:${pad((secs * 24) % 24)}`;
    const mid = innerHeight * 0.45;
    for (const s of scenes) { const r = s.getBoundingClientRect(); if (r.top <= mid && r.bottom > mid) { hudScene.textContent = s.dataset.scene; break; } }

    if (reelDist) {
      const r = reel.getBoundingClientRect();
      const p = Math.min(1, Math.max(0, -r.top / reelDist));
      track.style.transform = `translate3d(${-p * reelDist}px,0,0)`;
      fill.style.width = `${p * 100}%`;
    }
  };
  addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
  addEventListener('resize', () => { measureReel(); onScroll(); });
  addEventListener('load', () => { measureReel(); onScroll(); });
  measureReel(); onScroll();

  /* ---------- 6. Projector beam + dust (canvas, iba keď je hero viditeľné) ---------- */
  const cv = $('[data-beam]');
  if (cv && !reduce) {
    const ctx = cv.getContext('2d');
    let w, h, dpr, run = true, motes = [];
    const size = () => {
      dpr = Math.min(2, devicePixelRatio || 1);
      w = cv.clientWidth; h = cv.clientHeight; cv.width = w * dpr; cv.height = h * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      motes = Array.from({ length: mobile() ? 40 : 90 }, () => ({ x: Math.random() * w, y: Math.random() * h, r: Math.random() * 1.6 + .3, vx: (Math.random() - .5) * .15, vy: -Math.random() * .25 - .05, a: Math.random() * .6 + .2 }));
    };
    const ox = () => w * 0.92, oy = () => -h * 0.08;
    const inBeam = (x, y) => { // kužeľ od projektora vpravo hore smerom doľava dole
      const dx = x - ox(), dy = y - oy(); const ang = Math.atan2(dy, dx); return ang > 1.95 && ang < 2.55;
    };
    let mx = 0, my = 0;
    addEventListener('pointermove', (e) => { mx = (e.clientX / innerWidth - .5); my = (e.clientY / innerHeight - .5); }, { passive: true });
    const draw = (t) => {
      if (!run) return;
      ctx.clearRect(0, 0, w, h);
      const flick = .9 + Math.sin(t / 90) * .03 + Math.random() * .04;
      const g = ctx.createRadialGradient(ox(), oy(), 0, ox(), oy(), Math.hypot(w, h) * .9);
      g.addColorStop(0, `rgba(190,225,255,${.22 * flick})`); g.addColorStop(.5, `rgba(120,180,255,${.06 * flick})`); g.addColorStop(1, 'rgba(120,180,255,0)');
      ctx.fillStyle = g; ctx.beginPath(); ctx.moveTo(ox(), oy());
      const L = Math.hypot(w, h) * 1.2;
      ctx.lineTo(ox() + Math.cos(1.95 + mx * .05) * L, oy() + Math.sin(1.95) * L);
      ctx.lineTo(ox() + Math.cos(2.55 + mx * .05) * L, oy() + Math.sin(2.55 + my * .05) * L);
      ctx.closePath(); ctx.fill();
      for (const m of motes) {
        m.x += m.vx + mx * .2; m.y += m.vy;
        if (m.y < -5) { m.y = h + 5; m.x = Math.random() * w; }
        if (m.x < -5) m.x = w + 5; if (m.x > w + 5) m.x = -5;
        const lit = inBeam(m.x, m.y);
        ctx.fillStyle = `rgba(230,240,255,${lit ? m.a : m.a * .15})`;
        ctx.beginPath(); ctx.arc(m.x, m.y, m.r, 0, 6.283); ctx.fill();
      }
      requestAnimationFrame(draw);
    };
    size(); addEventListener('resize', size);
    new IntersectionObserver(([e]) => { const was = run; run = e.isIntersecting; if (run && !was) requestAnimationFrame(draw); }).observe(cv);
    requestAnimationFrame(draw);
  }

  /* ---------- 7. Hero player: cesta „from idea to exit“ ---------- */
  const stages = [
    ['Chapter 1 — Idea', 'Founders’ agreement, IP assignment and the right company structure — before the first line of code ships.'],
    ['Chapter 2 — Incorporate', 'Slovak s.r.o., Czech s.r.o. or a Delaware C-Corp — set up for the investors you want to meet.'],
    ['Chapter 3 — Seed', 'SAFEs, convertibles and term sheets negotiated by people who have sat on both sides of the table.'],
    ['Chapter 4 — Team & ESOP', 'Employment, contractor and ESOP plans that keep the best people in the room.'],
    ['Chapter 5 — Scale', 'Licences, GDPR, MiCA, the EU AI Act and expansion to the US — handled while you grow.'],
    ['Chapter 6 — Exit', 'Due diligence, M&A and the closing table. The final scene, written properly.'],
  ];
  const player = $('[data-player]');
  if (player) {
    const marks = $$('[data-stage]', player), prog = $('[data-player-progress]'), time = $('[data-player-time]');
    const ch = $('[data-player-chapter]'), tx = $('[data-player-text]'), stage = $('.player__stage', player);
    const DUR = 4200; let cur = 0, t0 = performance.now(), paused = false;
    const show = (i, user) => {
      cur = i; t0 = performance.now();
      marks.forEach((m, k) => m.classList.toggle('is-on', k === i));
      ch.textContent = stages[i][0]; tx.textContent = stages[i][1];
      stage.classList.remove('is-swap'); void stage.offsetWidth; stage.classList.add('is-swap');
      if (user) paused = true;
    };
    marks.forEach((m) => m.addEventListener('click', () => show(+m.dataset.stage, true)));
    player.addEventListener('mouseleave', () => { paused = false; t0 = performance.now(); });
    const loop = (t) => {
      const local = paused ? 0 : Math.min(1, (t - t0) / DUR);
      const total = (cur + local) / stages.length;
      prog.style.width = `${total * 100}%`;
      const s = Math.floor(total * 132); time.textContent = `${pad(s / 60)}:${pad(s % 60)}`;
      if (!paused && !reduce && local >= 1) show((cur + 1) % stages.length);
      requestAnimationFrame(loop);
    };
    requestAnimationFrame(loop);
  }

  /* ---------- 8. Countery ---------- */
  const cio = new IntersectionObserver((ents) => ents.forEach((e) => {
    if (!e.isIntersecting) return; cio.unobserve(e.target);
    const el = e.target, to = +el.dataset.count; if (reduce) { el.textContent = to; return; }
    const t0 = performance.now(); const f = (t) => { const p = Math.min(1, (t - t0) / 1600); el.textContent = Math.round(to * (1 - Math.pow(1 - p, 4))); if (p < 1) requestAnimationFrame(f); }; requestAnimationFrame(f);
  }), { threshold: .6 });
  $$('[data-count]').forEach((el) => cio.observe(el));

  /* ---------- 9. Rotator „Are you …?“ ---------- */
  const rot = $('[data-rotator]');
  if (rot && !reduce) {
    const items = $$('span', rot); let i = 0; items[0].classList.add('is-on');
    setInterval(() => {
      items[i].classList.remove('is-on'); items[i].classList.add('is-out');
      const prev = items[i]; setTimeout(() => prev.classList.remove('is-out'), 800);
      i = (i + 1) % items.length; items[i].classList.add('is-on');
    }, 2200);
  }

  /* ---------- 10. Reviews (titulky so stories progress) ---------- */
  const rv = $('[data-reviews]');
  if (rv) {
    const qs = $$('.quote', rv), bars = $('[data-review-bars]'), tg = $('[data-review-toggle]');
    const DUR = 7000; let cur = 0, t0 = performance.now(), paused = reduce;
    qs.forEach((_, k) => { const b = document.createElement('button'); b.setAttribute('aria-label', `Review ${k + 1}`); b.innerHTML = '<i></i>'; b.onclick = () => go(k); bars.append(b); });
    const bs = $$('button', bars);
    const go = (k) => {
      cur = (k + qs.length) % qs.length; t0 = performance.now();
      qs.forEach((q, j) => q.classList.toggle('is-active', j === cur));
      bs.forEach((b, j) => { b.classList.toggle('is-done', j < cur); $('i', b).style.width = j < cur ? '100%' : '0'; });
    };
    $('[data-review-prev]').onclick = () => go(cur - 1);
    $('[data-review-next]').onclick = () => go(cur + 1);
    const setPaused = (p) => { paused = p; tg.textContent = p ? '▶' : '❚❚'; tg.setAttribute('aria-label', p ? 'Play' : 'Pause'); if (!p) t0 = performance.now() - (parseFloat($('i', bs[cur]).style.width) || 0) / 100 * DUR; };
    tg.onclick = () => setPaused(!paused);
    setPaused(paused);
    let visible = false;
    new IntersectionObserver(([e]) => { visible = e.isIntersecting; if (visible) t0 = performance.now() - (parseFloat($('i', bs[cur]).style.width) || 0) / 100 * DUR; }).observe(rv);
    const loop = (t) => {
      if (!paused && visible) { const p = Math.min(1, (t - t0) / DUR); $('i', bs[cur]).style.width = `${p * 100}%`; if (p >= 1) go(cur + 1); }
      requestAnimationFrame(loop);
    };
    go(0); requestAnimationFrame(loop);
  }

  /* ---------- 11. Contact dialog ---------- */
  const dlg = $('[data-contact]');
  $$('[data-open-contact]').forEach((b) => b.addEventListener('click', () => {
    const topic = b.dataset.topic;
    if (topic) $$('[data-topics] .chip', dlg).forEach((c) => c.classList.toggle('is-on', c.textContent === topic));
    dlg.showModal();
  }));
  $$('[data-topics] .chip', dlg).forEach((c) => c.addEventListener('click', () => { $$('[data-topics] .chip', dlg).forEach((x) => x.classList.remove('is-on')); c.classList.add('is-on'); }));
  dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
})();
