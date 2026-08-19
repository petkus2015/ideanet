/* ═══════════════════════════════════════════════
   IDEANET — interakcie
   ═══════════════════════════════════════════════ */
(() => {
  'use strict';

  /* ───────────────────────────────────────────
     1) DÁTA PORTFÓLIA
     Vlastné video pridáte tak, že do assets/videos/
     nahráte .mp4 (9:16) a doplníte cestu do "video".
     Ak súbor neexistuje, karta zostane na plagáte.
  ─────────────────────────────────────────── */
  const REELS = [
    { id:'r1', cat:'Gastro',   title:'Ranná káva',        desc:'Brand film pre mestskú kaviareň — jeden natáčací deň, šesť reels.',      dur:'0:18', poster:'assets/posters/reel-01.svg', video:'assets/videos/reel-01.mp4', demo:'assets/videos/reel-01.webm', tags:['Brand film','Grading','Zvuk'] },
    { id:'r2', cat:'Fitness',  title:'Séria pre klub',     desc:'Dvanásť tréningových reels natočených počas jedného popoludnia.',        dur:'0:22', poster:'assets/posters/reel-02.svg', video:'assets/videos/reel-02.mp4', demo:'assets/videos/reel-02.webm', tags:['Séria','Titulky','Slow-mo'] },
    { id:'r3', cat:'Produkt',  title:'Detail chuti',       desc:'Makro produktové zábery pre e-shop s pralinkami.',                        dur:'0:15', poster:'assets/posters/reel-03.svg', video:'assets/videos/reel-03.mp4', demo:'assets/videos/reel-03.webm', tags:['Makro','Produkt','Svetlo'] },
    { id:'r4', cat:'Reality',  title:'Prehliadka bytu',    desc:'Plynulá gimbal prehliadka novostavby pre realitnú kanceláriu.',          dur:'0:29', poster:'assets/posters/reel-04.svg', video:'assets/videos/reel-04.mp4', demo:'assets/videos/reel-04.webm', tags:['Gimbal','Tour','Hudba'] },
    { id:'r5', cat:'Event',    title:'Aftermovie',         desc:'Zostrih z firemnej konferencie dodaný do 24 hodín.',                      dur:'0:34', poster:'assets/posters/reel-05.svg', video:'assets/videos/reel-05.mp4', demo:'assets/videos/reel-05.webm', tags:['Event','Rýchle dodanie'] },
    { id:'r6', cat:'Fashion',  title:'Lookbook jeseň',     desc:'Vertikálny lookbook pre lokálnu módnu značku.',                           dur:'0:20', poster:'assets/posters/reel-06.svg', video:'assets/videos/reel-06.mp4', demo:'assets/videos/reel-06.webm', tags:['Fashion','Grading','Motion'] },
    { id:'r7', cat:'Beauty',   title:'Pred a po',          desc:'Premenový formát pre kadernícky salón, kompletne na kľúč.',              dur:'0:24', poster:'assets/posters/reel-07.svg', video:'assets/videos/reel-07.mp4', demo:'assets/videos/reel-07.webm', tags:['Transformácia','Titulky'] },
    { id:'r8', cat:'Startup',  title:'Ako to funguje',     desc:'Vysvetľovacie video k aplikácii — scenár, natáčanie, motion.',            dur:'0:31', poster:'assets/posters/reel-08.svg', video:'assets/videos/reel-08.mp4', demo:'assets/videos/reel-08.webm', tags:['Explainer','Motion','Scenár'] },
  ];

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ───────────────────────────────────────────
     2) STAVBA KARIET
  ─────────────────────────────────────────── */
  const rail = $('#rail');
  const dotsWrap = $('#railDots');

  const icon = {
    sound:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 010 7"/></svg>',
    mute :'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H3v6h3l5 4z"/><path d="M22 9l-5 6M17 9l5 6"/></svg>',
    exp  :'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>'
  };

  rail.innerHTML = REELS.map((r, i) => `
    <article class="reel" id="${r.id}" data-i="${i}" role="option" aria-selected="false" aria-label="${r.title}">
      <div class="reel__media">
        <img src="${r.poster}" alt="Náhľad videa ${r.title}" loading="lazy" decoding="async" width="1080" height="1920">
        <video preload="none" playsinline muted loop poster="${r.poster}" aria-label="${r.title}">
          <source src="${r.video}" type="video/mp4">
          <source src="${r.demo}" type="video/webm">
        </video>
      </div>
      <div class="reel__shade"></div>

      <div class="reel__top">
        <span class="reel__cat">${r.cat}</span>
        <span class="reel__dur">${r.dur}</span>
      </div>

      <button class="reel__play" data-act="play" aria-label="Prehrať ukážku ${r.title}"></button>
      <p class="reel__hint">Video sem doplníte do assets/videos/</p>

      <div class="reel__body">
        <h3>${r.title}</h3>
        <p>${r.desc}</p>
        <span class="tags">${r.tags.map(t => `<span>${t}</span>`).join('')}</span>
        <div class="reel__tools">
          <button class="tool" data-act="sound" aria-label="Zapnúť zvuk">${icon.mute}<span>Zvuk</span></button>
          <button class="tool" data-act="expand" aria-label="Rozbaliť ${r.title}">${icon.exp}<span>Rozbaliť</span></button>
        </div>
      </div>
      <span class="reel__line"></span>
    </article>`).join('');

  const cards  = $$('.reel', rail);
  const videos = $$('.reel video', rail);

  /* plagát ako fallback, keď video súbor chýba */
  videos.forEach((v, i) => {
    v.addEventListener('error', () => {
      if (v.networkState === HTMLMediaElement.NETWORK_NO_SOURCE) cards[i].classList.add('no-src');
    }, true);
    v.addEventListener('timeupdate', () => {
      if (!v.duration) return;
      cards[i].querySelector('.reel__line').style.width = (v.currentTime / v.duration * 100) + '%';
    });
  });

  /* bodky */
  dotsWrap.innerHTML = REELS.map((r, i) =>
    `<button role="tab" data-i="${i}" aria-label="Video ${i + 1}: ${r.title}"></button>`).join('');
  const dots = $$('button', dotsWrap);

  /* ───────────────────────────────────────────
     3) PREHRÁVANIE
  ─────────────────────────────────────────── */
  function stopAll(except) {
    videos.forEach((v, i) => {
      if (v === except) return;
      v.pause();
      cards[i].classList.remove('is-playing');
    });
  }

  function play(i) {
    const v = videos[i], card = cards[i];
    if (v.preload === 'none') { v.preload = 'auto'; v.load(); }
    stopAll(v);
    v.play().then(() => card.classList.add('is-playing'))
            .catch(() => { if (!v.currentSrc) card.classList.add('no-src'); });
  }

  function toggle(i) {
    const v = videos[i];
    if (v.paused) { center(i); play(i); }
    else { v.pause(); cards[i].classList.remove('is-playing'); }
  }

  rail.addEventListener('click', (e) => {
    if (rail.dataset.moved === '1') return;          // klik po ťahaní ignorujeme
    const card = e.target.closest('.reel');
    if (!card) return;
    const i = +card.dataset.i;
    const btn = e.target.closest('[data-act]');
    const act = btn ? btn.dataset.act : 'play';

    if (act === 'expand') { openLightbox(i); return; }
    if (act === 'sound') {
      const v = videos[i];
      v.muted = !v.muted;
      btn.innerHTML = (v.muted ? icon.mute : icon.sound) + '<span>Zvuk</span>';
      btn.setAttribute('aria-label', v.muted ? 'Zapnúť zvuk' : 'Vypnúť zvuk');
      if (v.paused) toggle(i);
      return;
    }
    toggle(i);
  });

  /* ───────────────────────────────────────────
     4) POSÚVANIE — ťahanie, šípky, koliesko
  ─────────────────────────────────────────── */
  let active = 0;

  function center(i, behavior = 'smooth') {
    const card = cards[i];
    if (!card) return;
    const left = card.offsetLeft - (rail.clientWidth - card.offsetWidth) / 2;
    rail.scrollTo({ left, behavior: reduce ? 'auto' : behavior });
  }

  function updateActive() {
    const max = rail.scrollWidth - rail.clientWidth;
    const mid = rail.scrollLeft + rail.clientWidth / 2;
    let best = 0, bestD = Infinity;
    cards.forEach((c, i) => {
      const d = Math.abs(c.offsetLeft + c.offsetWidth / 2 - mid);
      if (d < bestD) { bestD = d; best = i; }
    });
    /* na krajoch sa karta vycentrovať nedá — aktívna je teda prvá / posledná */
    if (rail.scrollLeft <= 2) best = 0;
    else if (rail.scrollLeft >= max - 2) best = cards.length - 1;
    if (best !== active) {
      active = best;
      stopAll(videos[active]);                       // mimo stredu sa neprehráva
    }
    cards.forEach((c, i) => {
      c.classList.toggle('is-active', i === active);
      c.setAttribute('aria-selected', i === active);
    });
    dots.forEach((d, i) => d.classList.toggle('is-active', i === active));

    const pct = max > 0 ? rail.scrollLeft / max : 0;
    const bar = $('#railBar');
    bar.style.width = (100 / cards.length) + '%';
    bar.style.transform = `translateX(${pct * (cards.length - 1) * 100}%)`;

    $('#prevReel').disabled = rail.scrollLeft < 4;
    $('#nextReel').disabled = rail.scrollLeft > max - 4;
  }

  rail.addEventListener('scroll', () => {
    if (rail._raf) return;
    rail._raf = requestAnimationFrame(() => { rail._raf = null; updateActive(); });
  }, { passive: true });

  $('#prevReel').addEventListener('click', () => center(Math.max(0, active - 1)));
  $('#nextReel').addEventListener('click', () => center(Math.min(cards.length - 1, active + 1)));
  dots.forEach(d => d.addEventListener('click', () => center(+d.dataset.i)));

  /* ťahanie myšou / prstom */
  let down = false, startX = 0, startScroll = 0;
  rail.addEventListener('pointerdown', (e) => {
    if (e.pointerType === 'mouse' && e.button !== 0) return;
    down = true; startX = e.clientX; startScroll = rail.scrollLeft;
    rail.dataset.moved = '0';
  });
  rail.addEventListener('pointermove', (e) => {
    if (!down) return;
    const dx = e.clientX - startX;
    if (Math.abs(dx) > 6) {
      rail.dataset.moved = '1';
      rail.classList.add('is-dragging');
      rail.setPointerCapture?.(e.pointerId);
    }
    if (rail.dataset.moved === '1') rail.scrollLeft = startScroll - dx;
  });
  const endDrag = () => {
    if (!down) return;
    down = false;
    rail.classList.remove('is-dragging');
    if (rail.dataset.moved === '1') {
      requestAnimationFrame(() => { updateActive(); center(active); });
      setTimeout(() => { rail.dataset.moved = '0'; }, 0);
    }
  };
  ['pointerup', 'pointercancel', 'pointerleave'].forEach(ev => rail.addEventListener(ev, endDrag));

  /* vertikálne koliesko → horizontálny posun */
  rail.addEventListener('wheel', (e) => {
    if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
    const max = rail.scrollWidth - rail.clientWidth;
    const at = (e.deltaY < 0 && rail.scrollLeft <= 0) || (e.deltaY > 0 && rail.scrollLeft >= max - 1);
    if (at) return;                                   // na kraji necháme skrolovať stránku
    e.preventDefault();
    rail.scrollLeft += e.deltaY;
  }, { passive: false });

  /* klávesnica */
  rail.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') { e.preventDefault(); center(Math.min(cards.length - 1, active + 1)); }
    if (e.key === 'ArrowLeft')  { e.preventDefault(); center(Math.max(0, active - 1)); }
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(active); }
  });

  /* mimo obrazovky → pauza */
  new IntersectionObserver(([en]) => { if (!en.isIntersecting) stopAll(); }, { threshold: 0 })
    .observe(rail);

  requestAnimationFrame(() => { center(0, 'auto'); updateActive(); });
  addEventListener('resize', () => { center(active, 'auto'); updateActive(); });

  /* ───────────────────────────────────────────
     5) LIGHTBOX
  ─────────────────────────────────────────── */
  const lb = $('#lightbox'), lbFrame = $('#lbFrame');
  let lbIndex = 0, lastFocus = null;

  function openLightbox(i) {
    lbIndex = i;
    const r = REELS[i];
    stopAll();
    lastFocus = document.activeElement;
    lbFrame.innerHTML =
      `<img src="${r.poster}" alt="Náhľad videa ${r.title}">
       <video poster="${r.poster}" playsinline controls autoplay loop>
          <source src="${r.video}" type="video/mp4">
          <source src="${r.demo}" type="video/webm">
       </video>`;
    $('#lbTitle').textContent = r.title;
    $('#lbDesc').textContent  = r.desc;
    $('#lbTags').innerHTML    = r.tags.map(t => `<span>${t}</span>`).join('');
    lb.hidden = false;
    document.body.classList.add('is-locked');
    $('#lbClose').focus();
    const v = $('video', lbFrame);
    v.addEventListener('error', () => { v.style.display = 'none'; }, true);
    v.play?.().catch(() => {});
  }

  function closeLightbox() {
    lb.hidden = true;
    lbFrame.innerHTML = '';
    document.body.classList.remove('is-locked');
    lastFocus?.focus();
  }

  const step = (d) => openLightbox((lbIndex + d + REELS.length) % REELS.length);

  $('#lbClose').addEventListener('click', closeLightbox);
  $('#lbPrev').addEventListener('click', () => step(-1));
  $('#lbNext').addEventListener('click', () => step(1));
  lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
  addEventListener('keydown', (e) => {
    if (lb.hidden) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') step(1);
    if (e.key === 'ArrowLeft') step(-1);
  });

  /* ───────────────────────────────────────────
     6) NAVIGÁCIA + MENU
  ─────────────────────────────────────────── */
  const nav = $('#nav');
  const onScroll = () => nav.classList.toggle('is-stuck', scrollY > 12);
  onScroll(); addEventListener('scroll', onScroll, { passive: true });

  const burger = $('#burger'), menu = $('#mobileMenu');
  const setMenu = (open) => {
    menu.hidden = !open;
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Zavrieť menu' : 'Otvoriť menu');
  };
  burger.addEventListener('click', () => setMenu(menu.hidden));
  menu.addEventListener('click', (e) => { if (e.target.closest('a')) setMenu(false); });
  addEventListener('keydown', (e) => { if (e.key === 'Escape' && !menu.hidden) setMenu(false); });
  matchMedia('(min-width: 861px)').addEventListener('change', (e) => { if (e.matches) setMenu(false); });

  /* aktívna sekcia v menu */
  const links = $$('.nav__links a');
  const sections = links.map(a => $(a.getAttribute('href'))).filter(Boolean);
  const spy = new IntersectionObserver((ents) => {
    ents.forEach(en => {
      if (!en.isIntersecting) return;
      links.forEach(a => a.classList.toggle('is-active', a.getAttribute('href') === '#' + en.target.id));
    });
  }, { rootMargin: '-45% 0px -50%' });
  sections.forEach(s => spy.observe(s));

  /* ───────────────────────────────────────────
     7) DROBNOSTI
  ─────────────────────────────────────────── */
  /* reveal pri skrolovaní */
  const io = new IntersectionObserver((ents) => {
    ents.forEach(en => {
      if (!en.isIntersecting) return;
      en.target.classList.add('is-in');
      io.unobserve(en.target);
    });
  }, { threshold: .12, rootMargin: '0px 0px -8%' });
  $$('.reveal').forEach((el, i) => { el.style.transitionDelay = (i % 4) * 70 + 'ms'; io.observe(el); });

  /* počítadlá */
  $$('[data-count]').forEach(el => {
    const target = +el.dataset.count, suffix = el.dataset.suffix || '';
    new IntersectionObserver((ents, obs) => {
      if (!ents[0].isIntersecting) return;
      obs.disconnect();
      if (reduce) { el.textContent = target + suffix; return; }
      const t0 = performance.now(), dur = 1400;
      const tick = (t) => {
        const p = Math.min(1, (t - t0) / dur);
        el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))) + suffix;
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    }, { threshold: .6 }).observe(el);
  });

  /* svetlo pod kurzorom na kartách */
  $$('.card').forEach(c => c.addEventListener('pointermove', (e) => {
    const r = c.getBoundingClientRect();
    c.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    c.style.setProperty('--my', (e.clientY - r.top) + 'px');
  }));

  /* formulár */
  const form = $('#contactForm'), status = $('#formStatus');
  const setErr = (input, msg) => {
    input.closest('.field').classList.toggle('is-err', !!msg);
    const el = $(`.err[data-for="${input.id}"]`);
    if (el) el.textContent = msg || '';
  };
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = $('#name'), email = $('#email'), msg = $('#msg');
    let ok = true;
    if (!name.value.trim()) { setErr(name, 'Doplňte meno alebo názov značky.'); ok = false; } else setErr(name);
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.value.trim())) { setErr(email, 'Zadajte platný e-mail.'); ok = false; } else setErr(email);
    if (msg.value.trim().length < 10) { setErr(msg, 'Napíšte aspoň pár viet o projekte.'); ok = false; } else setErr(msg);
    if (!ok) { status.textContent = ''; return; }

    /* Bez backendu otvoríme e-mailového klienta.
       Pri nasadení sem doplňte volanie na váš formulárový endpoint. */
    const body = encodeURIComponent(
      `Meno: ${name.value}\nE-mail: ${email.value}\nTyp: ${$('#type').value}\n\n${msg.value}`);
    status.textContent = 'Ďakujem! Otváram e-mail s vaším dopytom…';
    location.href = `mailto:ahoj@ideanet.sk?subject=${encodeURIComponent('Dopyt z webu — ' + name.value)}&body=${body}`;
    form.reset();
  });

  $('#year').textContent = new Date().getFullYear();
})();
