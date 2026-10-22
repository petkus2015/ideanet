/* ═══════════════════════════════════════════════
   IDEANET — interakcie

   Obsah neprichádza odtiaľto, ale z WordPressu — videá, texty aj čísla
   kalkulačky sú v objekte window.IDEANET, ktorý téma vypisuje do stránky
   (inc/setup.php → ideanet_js_data). V tomto súbore netreba meniť nič,
   keď sa mení cenník alebo portfólio.
   ═══════════════════════════════════════════════ */
(() => {
  'use strict';

  /* ───────────────────────────────────────────
     1) DÁTA PORTFÓLIA
     Videá sa pridávajú v administrácii: Reels → Pridať nový.
  ─────────────────────────────────────────── */
  const DATA  = window.IDEANET || {};
  const REELS = Array.isArray(DATA.reels) ? DATA.reels : [];
  const T     = DATA.texts || {};
  const t = (key, vars) => {
    let s = T[key] || '';
    if (vars) Object.keys(vars).forEach(k => { s = s.split('{' + k + '}').join(vars[k]); });
    return s;
  };

  /* Texty chodia z databázy — do HTML ich vkladáme len cez escapovanie. */
  const esc = (s) => String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ───────────────────────────────────────────
     2) STAVBA KARIET
  ─────────────────────────────────────────── */
  const rail = $('#rail');
  const dotsWrap = $('#railDots');
  /* Hlavička a pätička sú na každej stránke, portfólio len na úvodnej —
     preto sa všetko okolo pásu zapína až keď je naozaj v stránke. */
  let cards = [], videos = [], dots = [], reelsRail = null;

  const icon = {
    sound:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 010 7"/></svg>',
    mute :'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H3v6h3l5 4z"/><path d="M22 9l-5 6M17 9l5 6"/></svg>',
    exp  :'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>'
  };

  if (rail && REELS.length) {
    rail.innerHTML = REELS.map((r, i) => `
    <article class="reel" id="${r.id}" data-i="${i}" role="option" aria-selected="false" aria-label="${esc(r.title)}">
      <div class="reel__media">
        ${r.poster ? `<img src="${esc(r.poster)}" alt="${esc(r.title)}" loading="lazy" decoding="async" width="1080" height="1920">` : ''}
        <video preload="${(r.video || r.webm) ? 'metadata' : 'none'}" playsinline muted loop ${r.poster ? `poster="${esc(r.poster)}"` : ''} aria-label="${esc(r.title)}">
          ${r.video ? `<source src="${esc(r.video)}" type="video/mp4">` : ''}
          ${r.webm  ? `<source src="${esc(r.webm)}" type="video/webm">` : ''}
        </video>
      </div>
      <div class="reel__shade"></div>

      <div class="reel__top">
        <span class="reel__cat">${esc(r.cat)}</span>
        <span class="reel__dur">${esc(r.dur)}</span>
      </div>

      <button class="reel__play" data-act="play" aria-label="${esc(T.play + ' ' + r.title)}"></button>
      <p class="reel__hint">${esc(T.noVideo)}</p>

      <div class="reel__body">
        <h3>${esc(r.title)}</h3>
        <p>${esc(r.desc)}</p>
        <span class="tags">${(r.tags || []).map(x => `<span>${esc(x)}</span>`).join('')}</span>
        <div class="reel__tools">
          <button class="tool" data-act="sound" aria-label="${esc(T.soundOn)}">${icon.mute}<span>${esc(T.sound)}</span></button>
          <button class="tool tool--expand" data-act="expand" title="${esc(T.expand)}" aria-label="${esc(T.expand + ' ' + r.title)}">${icon.exp}</button>
        </div>
      </div>
      <span class="reel__line"></span>
    </article>`).join('');

    cards  = $$('.reel', rail);
    videos = $$('.reel video', rail);

    /* Dĺžka na karte sa berie z videa, nie z ručne vyplneného poľa —
       prehliadač ju pozná hneď, ako načíta popis súboru. Vyplnená
       hodnota slúži len dovtedy, prípadne keď video chýba. */
    const cas = (sek) => {
      const m = Math.floor(sek / 60), z = Math.round(sek % 60);
      return z === 60 ? (m + 1) + ':00' : m + ':' + String(z).padStart(2, '0');
    };

    /* plagát ako záloha, keď video chýba alebo sa nedá prehrať */
    videos.forEach((v, i) => {
      v.addEventListener('loadedmetadata', () => {
        if (!isFinite(v.duration) || v.duration <= 0) return;
        const el = cards[i].querySelector('.reel__dur');
        if (el) el.textContent = cas(v.duration);
      });
      v.addEventListener('error', () => {
        if (v.networkState === HTMLMediaElement.NETWORK_NO_SOURCE) cards[i].classList.add('no-src');
      }, true);
      v.addEventListener('timeupdate', () => {
        if (!v.duration) return;
        cards[i].querySelector('.reel__line').style.width = (v.currentTime / v.duration * 100) + '%';
      });
      if (!v.querySelector('source')) cards[i].classList.add('no-src');
    });

    if (dotsWrap) {
      dotsWrap.innerHTML = REELS.map((r, i) =>
        `<button role="tab" data-i="${i}" aria-label="${esc(T.video + ' ' + (i + 1) + ': ' + r.title)}"></button>`).join('');
      dots = $$('button', dotsWrap);
    }
  }

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
    if (v.preload !== 'auto') {
      /* load() by zahodil už načítaný popis aj dĺžku — stačí povoliť
         sťahovanie, zvyšok dotiahne samotné play() */
      v.preload = 'auto';
      if (!v.currentSrc) v.load();
    }
    stopAll(v);
    v.play().then(() => card.classList.add('is-playing'))
            .catch(() => { if (!v.currentSrc) card.classList.add('no-src'); });
  }

  function toggle(i) {
    const v = videos[i];
    if (v.paused) { center(i); play(i); }
    else { v.pause(); cards[i].classList.remove('is-playing'); }
  }

  if (rail) rail.addEventListener('click', (e) => {
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
      btn.innerHTML = (v.muted ? icon.mute : icon.sound) + '<span>' + esc(T.sound) + '</span>';
      btn.setAttribute('aria-label', v.muted ? T.soundOn : T.soundOff);
      if (v.paused) toggle(i);
      return;
    }
    toggle(i);
  });

  /* ───────────────────────────────────────────
     4) POSÚVANIE — ťahanie, šípky, koliesko

     Jedna obsluha pre oba pásy — videá aj vizuály. Pás sa nikdy nehýbe
     dvoma spôsobmi naraz: buď ho ťahá prst (vtedy to rieši prehliadač
     a má vlastnú zotrvačnosť), alebo ho vedie jedna animácia tu v kóde.
     CSS snap sa počas nej vypína, aby sa s ňou nepretláčal.
  ─────────────────────────────────────────── */
  function createRail({ rail, cards, dots, prev, next, bar, onActive }) {
    let active = -1, railW = 0, maxScroll = 0, geo = [];
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
      if (bar) bar.style.width = (100 / Math.max(1, cards.length)) + '%';
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
    const cancelGlide = () => { if (glideRaf) cancelAnimationFrame(glideRaf); glideRaf = null; };
    const freeze = () => rail.classList.add('is-gliding');
    const settle = () => rail.classList.remove('is-gliding');

    function glideTo(i, dur) {
      cancelGlide();
      freeze();
      const from = rail.scrollLeft, to = targetFor(i), dist = to - from;

      if (reduce || Math.abs(dist) < 1) {
        rail.scrollLeft = to; settle(); updateActive(); return;
      }
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
        glideRaf = null; settle(); updateActive();
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
        if (onActive) onActive(active);
        cards.forEach((c, i) => {
          c.classList.toggle('is-active', i === active);
          c.setAttribute('aria-selected', i === active);
        });
        dots.forEach((d, i) => d.classList.toggle('is-active', i === active));
      }
      if (bar) {
        const pct = maxScroll > 0 ? rail.scrollLeft / maxScroll : 0;
        bar.style.transform = `translateX(${pct * (cards.length - 1) * 100}%)`;
      }
      if (prev) prev.disabled = rail.scrollLeft < 4;
      if (next) next.disabled = rail.scrollLeft > maxScroll - 4;
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

    if (prev) prev.addEventListener('click', () => stepCard(-1));
    if (next) next.addEventListener('click', () => stepCard(1));
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
      freeze();                                   // snap nesmie dostať ani jeden snímok
      rail.classList.remove('is-dragging');

      /* Kam by pás doletel, keby mal zotrvačnosť — a tam dotiahneme
         najbližšiu kartu. */
      if (performance.now() - lastT > 120) vel = 0;    // pred pustením sa už nehýbal
      const projected = clamp(rail.scrollLeft + vel * 170, 0, maxScroll);
      glideTo(nearest(projected));
      setTimeout(() => { rail.dataset.moved = '0'; }, 0);
    }

    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(ev =>
      rail.addEventListener(ev, endDrag));

    /* ── koliesko a touchpad ───────────────────
       Kým sa točí, pás ide voľne a snap je vypnutý. Keď sa zastaví,
       dojde na najbližšiu kartu. */
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

    rail.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { e.preventDefault(); stepCard(1); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); stepCard(-1); }
    });

    measure();
    requestAnimationFrame(() => { measure(); center(0, true); });

    let resizeTimer = null;
    addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => { measure(); center(0 + Math.max(0, active), true); }, 120);
    });

    return {
      center,
      stepCard,
      get active() { return active; },
    };
  }

  /* ── pás s videami ─────────────────────────── */
  if (rail && cards.length) {
    reelsRail = createRail({
      rail, cards, dots,
      prev: $('#prevReel'), next: $('#nextReel'),   // bez ukazovateľa priebehu — stačia guličky
      onActive: (i) => stopAll(videos[i]),            // mimo stredu sa neprehráva
    });

    rail.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(reelsRail.active); }
    });

    /* mimo obrazovky → pauza */
    new IntersectionObserver(([en]) => { if (!en.isIntersecting) stopAll(); }, { threshold: 0 })
      .observe(rail);
  }
  const center = (i, instant) => { if (reelsRail) reelsRail.center(i, instant); };

  /* ── pás s vizuálmi ────────────────────────── */
  const visRail = $('#visRail');
  if (visRail) {
    createRail({
      rail: visRail,
      cards: $$('.shot', visRail),
      dots: $$('button', $('#visDots')),
      prev: $('#prevVis'), next: $('#nextVis'),   // bez ukazovateľa priebehu — stačia guličky
    });
  }

  /* ───────────────────────────────────────────
     5) LIGHTBOX
  ─────────────────────────────────────────── */
  const lb = $('#lightbox'), lbFrame = $('#lbFrame');
  let lbIndex = 0, lastFocus = null;

  function openLightbox(i) {
    if (!lb || !REELS[i]) return;
    lbIndex = i;
    const r = REELS[i];
    stopAll();
    lastFocus = document.activeElement;
    lbFrame.innerHTML =
      `${r.poster ? `<img src="${esc(r.poster)}" alt="${esc(r.title)}">` : ''}
       <video ${r.poster ? `poster="${esc(r.poster)}"` : ''} playsinline controls autoplay loop>
          ${r.video ? `<source src="${esc(r.video)}" type="video/mp4">` : ''}
          ${r.webm  ? `<source src="${esc(r.webm)}" type="video/webm">` : ''}
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

  if (lb) {
    $('#lbClose').addEventListener('click', closeLightbox);
    $('#lbPrev').addEventListener('click', () => step(-1));
    $('#lbNext').addEventListener('click', () => step(1));
    lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
  }
  addEventListener('keydown', (e) => {
    if (!lb || lb.hidden) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') step(1);
    if (e.key === 'ArrowLeft') step(-1);
  });

  /* ── video v telefóne na úvodnej obrazovke ──
     Je to ozdoba, takže sa prehráva samo a bez zvuku. Kto má vypnuté
     animácie, uvidí prvý obrázok — hýbuce sa video by bolo presne to,
     čomu sa tým nastavením chce vyhnúť. */
  const heroVideo = $('#heroVideo');
  if (heroVideo) {
    if (reduce) {
      heroVideo.autoplay = false;
      heroVideo.removeAttribute('autoplay');
      heroVideo.pause();
    } else {
      /* Safari autoplay niekedy odmietne, keď je karta na pozadí —
         skúsime to znova, až keď je video na obrazovke. */
      const spusti = () => { const p = heroVideo.play(); if (p) p.catch(() => {}); };
      spusti();
      new IntersectionObserver((ents) => {
        ents.forEach(en => { if (en.isIntersecting && heroVideo.paused) spusti(); });
      }, { threshold: .2 }).observe(heroVideo);
    }
  }

  /* ───────────────────────────────────────────
     6) NAVIGÁCIA + MENU
  ─────────────────────────────────────────── */
  const nav = $('#nav'), navLinks = $('#navLinks'), pill = $('#navPill'),
        progress = $('#scrollProgress');
  const links = navLinks ? $$('a', navLinks) : [];

  /* WordPress ukladá do menu celú adresu (https://web.sk/#cennik), kým
     na statickej verzii tam bola len kotva. Zaujíma nás vždy tá časť
     za mriežkou. */
  const hashOf = (a) => {
    const href = a.getAttribute('href') || '';
    const at = href.indexOf('#');
    return at < 0 ? '' : href.slice(at);
  };

  /* ── ukazovateľ priebehu + správanie lišty ──
     Lišta sa skrýva až po prejdení prvej obrazovky, aby hneď na začiatku
     neposkakovala. Pri pohybe hore sa vráti okamžite. */
  let lastY = scrollY, parked = false;

  const onScroll = () => {
    if (!nav || !progress) return;
    const y = scrollY;
    const max = document.documentElement.scrollHeight - innerHeight;
    progress.style.transform = `scaleX(${max > 0 ? Math.min(1, y / max) : 0})`;

    nav.classList.toggle('is-stuck', y > 12);

    if (document.body.classList.contains('is-menu')) { lastY = y; return; }

    const dy = y - lastY;
    if (Math.abs(dy) > 6) {                       // drobné pohyby ignorujeme
      const away = dy > 0 && y > innerHeight * 0.6;
      if (away !== parked) { parked = away; nav.classList.toggle('is-away', away); }
      lastY = y;
    }
  };
  onScroll();
  addEventListener('scroll', onScroll, { passive: true });

  /* ── plávajúca značka pod odkazmi ──────────
     Presúva sa pod kurzor, po odídení myši sa vráti na čítanú sekciu. */
  let activeLink = null;

  const movePill = (el) => {
    if (!pill || !navLinks) return;
    /* offsetWidth, nie offsetParent — ten je pri position:fixed vždy null */
    if (!el || !navLinks.offsetWidth) { pill.style.opacity = '0'; return; }
    pill.style.opacity = '1';
    pill.style.width = el.offsetWidth + 'px';
    pill.style.transform = `translate3d(${el.offsetLeft}px,-50%,0)`;
  };
  const restorePill = () => movePill(activeLink);

  links.forEach(a => {
    a.addEventListener('pointerenter', () => movePill(a));
    a.addEventListener('focus', () => movePill(a));
  });
  if (navLinks) {
    navLinks.addEventListener('pointerleave', restorePill);
    navLinks.addEventListener('focusout', restorePill);
  }

  /* ── spodná lišta na mobile ────────────────
     Vlastná značka, lebo položky sú inak široké než hore a sú to iné
     sekcie — do piatich miest sa všetkých šesť nezmestí. */
  const tabbar = $('#tabbar'), tabPill = $('#tabPill');
  const tabs = tabbar ? $$('a', tabbar) : [];

  const moveTabPill = (el) => {
    if (!tabPill || !tabbar) return;
    if (!el || !tabbar.offsetWidth) { tabPill.style.opacity = '0'; return; }
    tabPill.style.opacity = '1';
    tabPill.style.width = el.offsetWidth + 'px';
    tabPill.style.transform = `translate3d(${el.offsetLeft}px,0,0)`;
  };

  /* ── mobilné menu ──────────────────────────── */
  const moreBtn = $('#moreBtn'), menuClose = $('#menuClose'), menu = $('#mobileMenu');
  const setMenu = (open) => {
    if (!menu || !moreBtn) return;
    menu.hidden = !open;
    document.body.classList.toggle('is-menu', open);
    document.body.classList.toggle('is-locked', open);
    moreBtn.setAttribute('aria-expanded', String(open));
    moreBtn.setAttribute('aria-label', open ? 'Zavrieť ďalšie sekcie' : 'Otvoriť ďalšie sekcie');
    if (open) { nav.classList.remove('is-away'); parked = false; }
    else moreBtn.focus({ preventScroll: true });
  };
  if (moreBtn && menu && menuClose) {
    moreBtn.addEventListener('click', () => setMenu(menu.hidden));
    menuClose.addEventListener('click', () => setMenu(false));
    menu.addEventListener('click', (e) => { if (e.target.closest('a')) setMenu(false); });
    addEventListener('keydown', (e) => { if (e.key === 'Escape' && !menu.hidden) setMenu(false); });
  }
  matchMedia('(min-width: 861px)').addEventListener('change', (e) => {
    if (e.matches) setMenu(false);
    restorePill();
    restoreTabPill();
  });

  /* ── práve čítaná sekcia ─────────────────────
     Spodná lišta nemá všetky sekcie, takže keď je čítaná niektorá
     z chýbajúcich, značka sa schová namiesto toho, aby ukazovala inam. */
  let activeTab = null;
  const restoreTabPill = () => moveTabPill(activeTab);

  const markActive = (id) => {
    links.forEach(a => {
      const on = hashOf(a) === '#' + id;
      a.classList.toggle('is-active', on);
      if (on) activeLink = a;
    });
    activeTab = null;
    tabs.forEach(a => {
      const on = hashOf(a) === '#' + id;
      a.classList.toggle('is-active', on);
      if (on) activeTab = a;
    });
    if (!navLinks || !navLinks.matches(':hover')) restorePill();
    restoreTabPill();
  };

  const sections = links
    .map(a => { const h = hashOf(a); return h.length > 1 ? $(h) : null; })
    .filter(Boolean);
  const spy = new IntersectionObserver((ents) => {
    ents.forEach(en => { if (en.isIntersecting) markActive(en.target.id); });
  }, { rootMargin: '-45% 0px -50%' });
  sections.forEach(s => spy.observe(s));

  /* rozmery položiek sa menia s písmom aj šírkou okna */
  let pillTimer = null;
  const repositionPills = () => { restorePill(); restoreTabPill(); };
  addEventListener('resize', () => {
    clearTimeout(pillTimer);
    pillTimer = setTimeout(repositionPills, 120);
  });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(repositionPills);

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

  /* ── jemné svetlo pod kurzorom ──────────────
     Pozícia sa k myši len približuje, nekopíruje ju — skok o celý
     rozdiel by pôsobil tvrdo. Kreslí sa raz za snímok, nie pri každom
     pohybe myši, ktorých príde aj niekoľko na snímok. */
  const glow = $('#cursorGlow');
  if (glow && matchMedia('(hover:hover)').matches && !reduce) {
    let gx = innerWidth / 2, gy = innerHeight / 2, tx = gx, ty = gy, raf = null;

    const draw = () => {
      gx += (tx - gx) * 0.12;
      gy += (ty - gy) * 0.12;
      glow.style.transform = `translate3d(${gx}px,${gy}px,0)`;
      raf = (Math.abs(tx - gx) > .5 || Math.abs(ty - gy) > .5) ? requestAnimationFrame(draw) : null;
    };

    addEventListener('pointermove', (e) => {
      if (e.pointerType === 'touch') return;
      tx = e.clientX; ty = e.clientY;
      glow.classList.add('is-on');
      if (!raf) raf = requestAnimationFrame(draw);
    }, { passive: true });

    addEventListener('pointerleave', () => glow.classList.remove('is-on'));
    document.addEventListener('mouseleave', () => glow.classList.remove('is-on'));

    /* nárazová vlna pri kliknutí — prstenec sa sám odstráni, keď dobehne */
    addEventListener('pointerdown', (e) => {
      if (e.pointerType === 'touch') return;
      const w = document.createElement('span');
      w.className = 'ripple';
      w.style.left = e.clientX + 'px';
      w.style.top = e.clientY + 'px';
      document.body.appendChild(w);
      /* animácie bežia na dvoch pseudoprvkoch, takže animationend by
         prišiel skôr, než dobehne tá pomalšia — radšej jeden časovač */
      setTimeout(() => w.remove(), 2000);
    }, { passive: true });
  }

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

     Čísla sem chodia z Prispôsobiť (IDEANET.calc), nie sú napísané tu.
     Cenník stojí na tom, že prvý Reel počíta s natáčaním do dvoch hodín
     a každý ďalší pridá hodinu. Rezervovaný poldeň či celý deň je
     samostatná sadzba — platí sa čas, nie počet videí, takže blok kryje
     svoj počet Reels a ďalšie sa pripočítajú.
  ─────────────────────────────────────────── */
  const calc = $('#calcForm');
  if (calc && DATA.calc) {
    const C = DATA.calc;
    const addons = Array.isArray(C.addons) ? C.addons : [];

    const reels = $('#calcReels'), reelsOut = $('#reelsOut'),
          km = $('#calcKm'), shootHint = $('#shootHint');

    const outTotal = $('#calcTotal'), outUnit = $('#calcUnit'), outPer = $('#calcPer'),
          outRows  = $('#calcRows'),  outTip  = $('#calcTip'), outCta = $('#calcCta');

    const eur = (n) => Math.round(n).toLocaleString('sk-SK') + ' €';
    /* po predložke „do" je genitív: do 1 hodiny, do 2 hodín, do 5 hodín */
    const hodin = (n) => n + (n === 1 ? ' hodiny' : ' hodín');
    /* samostatný počet má iný tvar: 1 hodina, 2 hodiny, 5 hodín */
    const hodinPocet = (n) => n + (n === 1 ? ' hodina' : n < 5 ? ' hodiny' : ' hodín');
    const kusov = (n) => n === 1 ? '1 Reel' : n + ' Reels';

    const boxes = addons.map((a, i) => $('#addon' + i));

    const recalc = () => {
      const n = +reels.value;
      const picked = $('input[name="calcMode"]:checked');
      const mode = picked ? picked.value : 'none';
      const day = mode === 'day', half = mode === 'half';
      reelsOut.textContent = n;

      /* natáčací čas: bez rezervácie prvý Reel do dvoch hodín a každý
         ďalší +1 h, pri rezervácii je čas daný blokom */
      const shoot = day ? C.dayHours : half ? C.halfHours : C.firstHours + (n - 1);
      shootHint.textContent = day
        ? t('calcHintDay', { h: C.dayHours })
        : half
          ? t('calcHintHalf', { h: C.halfHours })
          : t('calcHintNone', { h: hodin(shoot), f: C.firstHours });

      const inc   = day ? C.dayReels : half ? C.halfReels : 1;
      const seed  = day ? C.day : half ? C.half : C.first;
      const extra = Math.max(0, n - inc);
      const base  = seed + extra * C.next;

      const rows = [[
        day  ? t('calcRowDay',  { h: C.dayHours,  n: kusov(Math.min(n, inc)) })
        : half ? t('calcRowHalf', { h: C.halfHours, n: kusov(Math.min(n, inc)) })
        :        t('calcRowFirst', { h: hodin(C.firstHours) }),
        eur(seed), 'is-base']];

      if (extra > 0) rows.push([
        mode === 'none'
          ? t('calcRowExtraTime', { n: extra, t: hodinPocet(extra) })
          : t('calcRowExtra', { n: extra }),
        eur(extra * C.next)]);

      /* doplnky: percento sa počíta zo základu, mesačné sa do
         jednorazovej sumy nesčítava a uvádza sa zvlášť */
      let total = base, mesacne = 0;
      const side = [];

      addons.forEach((a, i) => {
        if (!boxes[i] || !boxes[i].checked) return;
        if (a.kind === 'mesacne') { mesacne += a.amount; side.push([a.label, '+ ' + eur(a.amount) + ' ' + T.monthly]); return; }
        const fee = a.kind === 'percento' ? base * (a.amount / 100) : a.amount;
        rows.push([a.label, eur(fee)]);
        total += fee;
      });

      const kmOne  = Math.max(0, +km.value || 0);
      const travel = kmOne * 2 * C.kmRate;
      if (travel) { rows.push([T.travel + ' — ' + (kmOne * 2) + ' km', eur(travel)]); total += travel; }

      outRows.innerHTML =
        rows.map(([l, v, cls]) => `<li class="${cls || ''}"><span>${esc(l)}</span><b>${esc(v)}</b></li>`).join('') +
        side.map(([l, v]) => `<li class="is-side"><span>${esc(l)}</span><b>${esc(v)}</b></li>`).join('');

      outTotal.textContent = Math.round(total).toLocaleString('sk-SK');
      outUnit.textContent  = T.unit || '';
      outPer.innerHTML = n === 1
        ? esc(T.oneReel)
        : (T.perReel || '%s').replace('%s', '<b>' + esc(eur(total / n)) + '</b>');

      /* pri väčšom objeme je mesačná spolupráca rozumnejšia */
      const tip = C.tipFrom > 0 && n >= C.tipFrom && T.tip;
      outTip.hidden = !tip;
      if (tip) outTip.innerHTML = T.tip;

      /* podklad pre dopyt */
      outCta.dataset.package = T.calcPkg || '';
      outCta.dataset.summary = rows.map(([l, v]) => `${l}: ${v}`).join('\n')
        + `\n${T.total}: ${eur(total)}`
        + (mesacne ? `\n${side.map(([l, v]) => l + ': ' + v).join('\n')}` : '');
    };

    /* krokovanie tlačidlami aj ťahaním */
    const nudge = (d) => {
      reels.value = Math.min(+reels.max, Math.max(+reels.min, +reels.value + d));
      recalc();
    };
    const up = $('#reelsUp'), down = $('#reelsDown');
    if (up) up.addEventListener('click', () => nudge(1));
    if (down) down.addEventListener('click', () => nudge(-1));

    calc.addEventListener('input', recalc);
    calc.addEventListener('change', recalc);
    recalc();
  }

  /* ───────────────────────────────────────────
     10) PREDVYPLNENIE A ODOSLANIE DOPYTU
  ─────────────────────────────────────────── */
  const typeSel = $('#type'), msgField = $('#msg');
  const pickOption = (label) => {
    if (!typeSel || !label) return;
    const want = label.trim().toLowerCase();
    const opt = [...typeSel.options].find(o => o.textContent.trim().toLowerCase() === want)
             || [...typeSel.options].find(o => o.textContent.toLowerCase().includes(want.split(' —')[0]));
    if (opt) typeSel.value = opt.value || opt.textContent;
  };

  $$('[data-package]').forEach(el => el.addEventListener('click', () => {
    pickOption(el.dataset.package);
    if (el.dataset.summary && msgField && !msgField.value.trim()) {
      msgField.value = T.calcSum + '\n' + el.dataset.summary + '\n\n';
    }
    if (typeSel) {
      typeSel.classList.add('is-flash');
      setTimeout(() => typeSel.classList.remove('is-flash'), 1200);
    }
  }));

  /* formulár */
  const form = $('#contactForm'), status = $('#formStatus');
  if (form && status) {
    const setErr = (input, msg) => {
      input.closest('.field').classList.toggle('is-err', !!msg);
      const el = $(`.err[data-for="${input.id}"]`);
      if (el) el.textContent = msg || '';
    };

    form.addEventListener('submit', (e) => {
      const name = $('#name'), email = $('#email'), msg = $('#msg');
      let ok = true;
      if (!name.value.trim()) { setErr(name, T.errName); ok = false; } else setErr(name);
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.value.trim())) { setErr(email, T.errEmail); ok = false; } else setErr(email);
      if (msg.value.trim().length < 10) { setErr(msg, T.errMsg); ok = false; } else setErr(msg);

      if (!ok) { e.preventDefault(); status.textContent = ''; return; }

      /* Bez fetch necháme formulár odísť normálne — WordPress ho
         spracuje cez admin-post.php a vráti sa späť s hláškou. */
      if (!window.fetch || !DATA.ajax) return;

      e.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      status.textContent = T.sending;
      if (button) button.disabled = true;

      fetch(DATA.ajax, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
        .then(r => r.json())
        .then(res => {
          const payload = res && res.data ? res.data : {};
          status.textContent = payload.message || (res && res.success ? '' : T.formErr);
          if (res && res.success) {
            form.reset();
            [name, email, msg].forEach(f => setErr(f));
          } else if (payload.errors) {
            Object.keys(payload.errors).forEach(key => {
              const field = $('#' + key);
              if (field) setErr(field, payload.errors[key]);
            });
          }
        })
        .catch(() => { status.textContent = T.formErr; })
        .then(() => { if (button) button.disabled = false; });
    });
  }

})();
