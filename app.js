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

     Pás sa nikdy nehýbe dvoma spôsobmi naraz. Buď ho ťahá prst
     (vtedy to rieši prehliadač a má vlastnú zotrvačnosť), alebo
     ho vedie jedna animácia tu v kóde. CSS snap sa počas nej vypína,
     aby sa s ňou nepretláčal.
  ─────────────────────────────────────────── */
  /* -1 znamená „ešte nič nenastavené“, aby prvé updateActive() triedy naozaj rozdalo */
  let active = -1, railW = 0, maxScroll = 0, geo = [];
  const bar = $('#railBar'), prevBtn = $('#prevReel'), nextBtn = $('#nextReel');
  const clamp = (n, lo, hi) => Math.min(hi, Math.max(lo, n));

  /* Rozmery kariet čítame raz — nie pri každom snímku animácie.
     Čítanie offsetLeft počas skrolovania si vynucuje prepočet layoutu
     a práve to pás trhalo. */
  function measure() {
    railW = rail.clientWidth;
    maxScroll = rail.scrollWidth - railW;
    geo = cards.map(c => ({
      left: c.offsetLeft,
      width: c.offsetWidth,
      mid: c.offsetLeft + c.offsetWidth / 2,
    }));
    bar.style.width = (100 / Math.max(1, cards.length)) + '%';
  }

  const targetFor = (i) => {
    const g = geo[i];
    return g ? clamp(g.left - (railW - g.width) / 2, 0, maxScroll) : 0;
  };

  /* Karta najbližšie k stredu. Rozhoduje geometria, nie poradie — vľavo
     je pás odsadený len na úroveň textu, takže prvé karty sa do stredu
     dostať nedajú a „na kraji je aktívna prvá“ by klamalo. */
  function nearest(scrollLeft = rail.scrollLeft) {
    if (scrollLeft >= maxScroll - 2) return cards.length - 1;
    const mid = scrollLeft + railW / 2;
    let best = 0, bestD = Infinity;
    for (let i = 0; i < geo.length; i++) {
      const d = Math.abs(geo[i].mid - mid);
      if (d < bestD) { bestD = d; best = i; }
    }
    return best;
  }

  /* ── jedna plynulá animácia pásu ───────────
     Poradie tu je dôležitejšie, než sa zdá. CSS snap sa musí vypnúť
     skôr, než sa prvýkrát prečíta pozícia pásu — čítanie si vynúti
     prepočet layoutu a snap v tej chvíli stihne pás šklbnúť na svoju
     stranu. Späť ho pustíme až vtedy, keď pás stojí presne na karte. */
  let glideRaf = null;

  function cancelGlide() {
    if (glideRaf) cancelAnimationFrame(glideRaf);
    glideRaf = null;
  }

  const freeze = () => rail.classList.add('is-gliding');    // snap preč
  const settle = () => rail.classList.remove('is-gliding'); // snap späť

  function glideTo(i, dur) {
    cancelGlide();
    freeze();
    const from = rail.scrollLeft, to = targetFor(i), dist = to - from;

    if (reduce || Math.abs(dist) < 1) {
      rail.scrollLeft = to; settle(); updateActive(); return;
    }

    /* kratší posun nesmie trvať rovnako dlho ako cesta cez celý pás */
    const ms = dur || clamp(Math.abs(dist) * 0.85, 300, 820);

    /* Čas berieme až z prvého snímku. Značka, ktorú requestAnimationFrame
       podá, patrí začiatku práve kresleného snímku a vie byť staršia než
       performance.now() v okamihu naplánovania — rozdiel potom vyjde
       záporný a pás na začiatku animácie cúvol. */
    let t0 = null;

    const frame = (t) => {
      if (t0 === null) t0 = t;
      const p = Math.min(1, Math.max(0, (t - t0) / ms));
      rail.scrollLeft = from + dist * (1 - Math.pow(1 - p, 3));   // easeOutCubic
      if (p < 1) { glideRaf = requestAnimationFrame(frame); return; }
      glideRaf = null;
      settle();
      updateActive();
    };
    glideRaf = requestAnimationFrame(frame);
  }

  function center(i, instant) {
    if (!cards[i]) return;
    if (instant || reduce) {
      cancelGlide(); freeze();
      rail.scrollLeft = targetFor(i);
      settle(); updateActive();
      return;
    }
    glideTo(i);
  }

  /* ── stav pod pásom ────────────────────────── */
  function updateActive() {
    const best = nearest();
    if (best !== active) {
      active = best;
      stopAll(videos[active]);                       // mimo stredu sa neprehráva
      cards.forEach((c, i) => {
        c.classList.toggle('is-active', i === active);
        c.setAttribute('aria-selected', i === active);
      });
      dots.forEach((d, i) => d.classList.toggle('is-active', i === active));
    }
    const pct = maxScroll > 0 ? rail.scrollLeft / maxScroll : 0;
    bar.style.transform = `translateX(${pct * (cards.length - 1) * 100}%)`;
    prevBtn.disabled = rail.scrollLeft < 4;
    nextBtn.disabled = rail.scrollLeft > maxScroll - 4;
  }

  rail.addEventListener('scroll', () => {
    if (rail._raf) return;
    rail._raf = requestAnimationFrame(() => { rail._raf = null; updateActive(); });
  }, { passive: true });

  /* Posun o kartu, ktorá pásom skutočne pohne. Krajné karty majú cieľovú
     pozíciu orezanú na 0, takže „aktívna + 1“ vedela skončiť na mieste
     a tlačidlo vyzeralo pokazené. */
  function stepCard(dir) {
    const cur = rail.scrollLeft;
    if (dir > 0) {
      for (let i = 0; i < cards.length; i++) if (targetFor(i) > cur + 1) return glideTo(i);
      return glideTo(cards.length - 1);
    }
    for (let i = cards.length - 1; i >= 0; i--) if (targetFor(i) < cur - 1) return glideTo(i);
    return glideTo(0);
  }

  prevBtn.addEventListener('click', () => stepCard(-1));
  nextBtn.addEventListener('click', () => stepCard(1));
  dots.forEach(d => d.addEventListener('click', () => center(+d.dataset.i)));

  /* ── ťahanie myšou ─────────────────────────
     Dotyku sa nedotýkame. Prehliadač má pre prst vlastné zotrvačné
     skrolovanie aj snap a čokoľvek, čím by sme ho nahradili, by bolo
     horšie. Myš takú zotrvačnosť nemá, tak ju dorobíme. */
  let down = false, dragging = false, startX = 0, startScroll = 0, lastX = 0, lastT = 0, vel = 0;

  rail.addEventListener('pointerdown', (e) => {
    if (e.pointerType === 'touch') return;
    if (e.pointerType === 'mouse' && e.button !== 0) return;
    cancelGlide(); freeze();
    down = true; dragging = false; vel = 0;
    startX = lastX = e.clientX;
    startScroll = rail.scrollLeft;
    lastT = performance.now();
    rail.dataset.moved = '0';
  });

  rail.addEventListener('pointermove', (e) => {
    if (!down) return;
    const dx = e.clientX - startX;

    if (!dragging) {
      if (Math.abs(dx) <= 6) return;                 // ešte to môže byť klik
      dragging = true;
      rail.dataset.moved = '1';
      rail.classList.add('is-dragging');
      /* zachytenie vie odmietnuť ukazovateľ, ktorý už nie je aktívny */
      try { rail.setPointerCapture(e.pointerId); } catch (_) {}
    }

    rail.scrollLeft = startScroll - dx;

    /* rýchlosť v px/ms, vyhladená — surové hodnoty medzi snímkami skáču */
    const now = performance.now(), dt = now - lastT;
    if (dt > 0) {
      vel = vel * 0.7 + ((lastX - e.clientX) / dt) * 0.3;
      lastX = e.clientX;
      lastT = now;
    }
  });

  function endDrag() {
    if (!down) return;
    down = false;
    if (!dragging) { settle(); return; }        // bol to klik, nie ťahanie
    dragging = false;
    rail.classList.remove('is-dragging');

    /* Kam by pás doletel, keby mal zotrvačnosť — a tam dotiahneme
       najbližšiu kartu. Jedna animácia namiesto dojazdu, ktorý by ešte
       dobiehal, keď už snap ťahá inam. */
    if (performance.now() - lastT > 120) vel = 0;    // pred pustením sa už nehýbal
    const projected = clamp(rail.scrollLeft + vel * 170, 0, maxScroll);
    glideTo(nearest(projected));
    setTimeout(() => { rail.dataset.moved = '0'; }, 0);
  }

  ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(ev =>
    rail.addEventListener(ev, endDrag));

  /* ── koliesko a touchpad ───────────────────
     Kým sa točí, pás ide voľne a snap je vypnutý. Keď sa zastaví,
     dojde na najbližšiu kartu. Pôvodne sa každý tik kolieska pretláčal
     so snapom a z toho bolo to trhanie. */
  let wheelTimer = null;

  rail.addEventListener('wheel', (e) => {
    const d = Math.abs(e.deltaY) > Math.abs(e.deltaX) ? e.deltaY : e.deltaX;
    const atEdge = (d < 0 && rail.scrollLeft <= 0) || (d > 0 && rail.scrollLeft >= maxScroll - 1);
    if (atEdge) return;                              // na kraji nech skroluje stránka
    e.preventDefault();

    cancelGlide(); freeze();                         // snap počas pohybu prekáža
    rail.scrollLeft = clamp(rail.scrollLeft + d, 0, maxScroll);

    clearTimeout(wheelTimer);
    wheelTimer = setTimeout(() => glideTo(nearest(), 360), 140);
  }, { passive: false });

  /* ── klávesnica ────────────────────────────── */
  rail.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') { e.preventDefault(); stepCard(1); }
    if (e.key === 'ArrowLeft')  { e.preventDefault(); stepCard(-1); }
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(active); }
  });

  /* mimo obrazovky → pauza */
  new IntersectionObserver(([en]) => { if (!en.isIntersecting) stopAll(); }, { threshold: 0 })
    .observe(rail);

  measure();
  requestAnimationFrame(() => { measure(); center(0, true); });

  let resizeTimer = null;
  addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => { measure(); center(active, true); }, 120);
  });

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
      let t0 = null; const dur = 1400;
      const tick = (t) => {
        if (t0 === null) t0 = t;
        const p = Math.min(1, Math.max(0, (t - t0) / dur));
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

  /* ───────────────────────────────────────────
     8) CENNÍK — prepínanie pohľadov
  ─────────────────────────────────────────── */
  const tabBtns = $$('.tabs__btn');
  const showTab = (key, focus) => {
    tabBtns.forEach(b => {
      const on = b.dataset.tab === key;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-selected', on ? 'true' : 'false');
      b.tabIndex = on ? 0 : -1;
      const panel = $('#panel-' + b.dataset.tab);
      if (panel) {
        panel.hidden = !on;
        panel.classList.toggle('is-active', on);
        /* obsah skrytého panelu observer nezachytí — odkryjeme ho ručne */
        if (on) $$('.reveal', panel).forEach(el => el.classList.add('is-in'));
      }
      if (on && focus) b.focus();
    });
  };

  tabBtns.forEach((btn, i) => {
    btn.addEventListener('click', () => showTab(btn.dataset.tab));
    btn.addEventListener('keydown', (e) => {
      const dir = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (!dir) return;
      e.preventDefault();
      showTab(tabBtns[(i + dir + tabBtns.length) % tabBtns.length].dataset.tab, true);
    });
  });

  /* karty služieb otvoria príslušný pohľad v cenníku */
  $$('.lane[data-tab]').forEach(lane => {
    lane.addEventListener('click', () => showTab(lane.dataset.tab));
  });

  /* ───────────────────────────────────────────
     9) KALKULAČKA
  ─────────────────────────────────────────── */
  const calc = $('#calcForm');
  if (calc) {
    const pkg   = $('#calcPkg'),   speed = $('#calcSpeed'),
          km    = $('#calcKm'),    hours = $('#calcHours'),
          raw   = $('#calcRaw');
    const outTotal = $('#calcTotal'), outUnit = $('#calcUnit'),
          outRows  = $('#calcRows'),  outCta  = $('#calcCta'),
          outPer   = $('#calcPer');

    const KM_RATE = .4, HOUR_RATE = 60, RAW_FEE = 80;
    const eur = (n) => Math.round(n).toLocaleString('sk-SK') + ' €';

    const recalc = () => {
      const opt  = pkg.options[pkg.selectedIndex];
      const base = +opt.value;
      const unit = opt.dataset.unit || '';
      const rush = +speed.value;
      /* účtuje sa každá začatá polhodina */
      const extraH = Math.ceil(Math.max(0, +hours.value || 0) * 2) / 2;
      const kmOne  = Math.max(0, +km.value || 0);

      const rushFee  = base * rush;
      const hoursFee = extraH * HOUR_RATE;
      const travel   = kmOne * 2 * KM_RATE;
      const rawFee   = raw.checked ? RAW_FEE : 0;
      const total    = base + rushFee + hoursFee + travel + rawFee;

      const rows = [[opt.textContent.split(' · ')[0], eur(base), 'is-base']];
      if (rushFee)  rows.push(['Same-day (+50 %)', eur(rushFee)]);
      if (hoursFee) rows.push([`Natáčanie navyše — ${extraH} h`, eur(hoursFee)]);
      if (travel)   rows.push([`Doprava — ${kmOne * 2} km`, eur(travel)]);
      if (rawFee)   rows.push(['RAW materiál', eur(rawFee)]);

      outRows.innerHTML = rows
        .map(([l, v, cls]) => `<li class="${cls || ''}"><span>${l}</span><b>${v}</b></li>`)
        .join('');

      outTotal.textContent = Math.round(total).toLocaleString('sk-SK');
      outUnit.textContent  = (unit === 'mesačne' ? 'mesačne, bez DPH' : unit + ', bez DPH');

      /* Koľko stojí jedno hotové video vrátane natáčania aj dodania.
         Pri podcaste to nedáva zmysel — hlavný výstup je epizóda, nie Reels. */
      const reels = +opt.dataset.reels || 0;
      outPer.hidden = reels < 1;
      if (reels >= 1) {
        outPer.innerHTML = reels === 1
          ? 'Jeden Reel od natočenia po dodanie.'
          : `Vychádza to na <b>${eur(total / reels)}</b> za jeden Reel.`;
      }

      /* prenos výberu do kontaktného formulára */
      outCta.dataset.package = opt.textContent.split(' · ')[0];
      outCta.dataset.summary = rows.map(([l, v]) => `${l}: ${v}`).join('\n')
        + `\nSpolu (orientačne): ${eur(total)} ${unit === 'mesačne' ? 'mesačne' : ''}`.trimEnd();
    };

    calc.addEventListener('input', recalc);
    calc.addEventListener('change', recalc);
    recalc();
  }

  /* ───────────────────────────────────────────
     10) PREDVYPLNENIE DOPYTU
  ─────────────────────────────────────────── */
  const typeSel = $('#type'), msgField = $('#msg');
  const pickOption = (label) => {
    if (!typeSel || !label) return;
    const opt = [...typeSel.options].find(o => o.textContent.trim() === label.trim())
             || [...typeSel.options].find(o => o.textContent.toLowerCase().includes(label.toLowerCase().split(' —')[0]));
    if (opt) typeSel.value = opt.value || opt.textContent;
  };

  $$('[data-package]').forEach(el => el.addEventListener('click', () => {
    pickOption(el.dataset.package);
    if (el.dataset.summary && msgField && !msgField.value.trim()) {
      msgField.value = 'Rozpočet podľa kalkulačky:\n' + el.dataset.summary + '\n\n';
    }
    if (typeSel) {
      typeSel.classList.add('is-flash');
      setTimeout(() => typeSel.classList.remove('is-flash'), 1200);
    }
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
