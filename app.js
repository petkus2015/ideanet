/* ═══════════════════════════════════════════════════════════
   IDEANET — interakcie
   Vanilla JS, bez závislostí.
   ═══════════════════════════════════════════════════════════ */
(() => {
  'use strict';

  /* ─────────────────────────────────────────────────────────
     1) DÁTA PORTFÓLIA
     Tu meníte obsah karuselov. Poradie v poli = poradie na webe.

     video / social:
       poster — statický náhľad (9:16)
       video  — vaše finálne video (má prednosť), napr. assets/videos/reel-01.mp4
       demo   — zástupný klip, ktorý sa prehrá, kým vaše video nedodáte
     graphic:
       image  — ukážka grafiky (4:5)
  ───────────────────────────────────────────────────────── */
  const DATA = {
    video: [
      { id:'v1', cat:'Gastro',  title:'Ranná káva',       desc:'Brand film pre mestskú kaviareň — jeden natáčací deň, šesť reels.', dur:'0:18',
        poster:'assets/posters/video-01.svg', video:'assets/videos/reel-01.mp4', demo:'assets/videos/reel-01.webm' },
      { id:'v2', cat:'Fitness', title:'Séria pre klub',   desc:'Dvanásť tréningových videí natočených počas jedného popoludnia.',  dur:'0:22',
        poster:'assets/posters/video-02.svg', video:'assets/videos/reel-02.mp4', demo:'assets/videos/reel-02.webm' },
      { id:'v3', cat:'Produkt', title:'Detail chuti',     desc:'Makro produktové zábery pre e-shop s pralinkami.',                 dur:'0:15',
        poster:'assets/posters/video-03.svg', video:'assets/videos/reel-03.mp4', demo:'assets/videos/reel-03.webm' },
      { id:'v4', cat:'Reality', title:'Prehliadka bytu',  desc:'Plynulá gimbal prehliadka novostavby pre realitnú kanceláriu.',    dur:'0:29',
        poster:'assets/posters/video-04.svg', video:'assets/videos/reel-04.mp4', demo:'assets/videos/reel-04.webm' },
      { id:'v5', cat:'Event',   title:'Aftermovie',       desc:'Zostrih z firemnej konferencie dodaný do 24 hodín.',               dur:'0:34',
        poster:'assets/posters/video-05.svg', video:'assets/videos/reel-05.mp4', demo:'assets/videos/reel-05.webm' },
      { id:'v6', cat:'Fashion', title:'Lookbook jeseň',   desc:'Vertikálny lookbook pre lokálnu módnu značku.',                    dur:'0:20',
        poster:'assets/posters/video-06.svg', video:'assets/videos/reel-06.mp4', demo:'assets/videos/reel-06.webm' },
      { id:'v7', cat:'Beauty',  title:'Pred a po',        desc:'Premenový formát pre kadernícky salón, kompletne na kľúč.',        dur:'0:24',
        poster:'assets/posters/video-07.svg', video:'assets/videos/reel-07.mp4', demo:'assets/videos/reel-07.webm' },
      { id:'v8', cat:'Startup', title:'Ako to funguje',   desc:'Vysvetľovacie video k aplikácii — scenár, natáčanie, motion.',     dur:'0:31',
        poster:'assets/posters/video-08.svg', video:'assets/videos/reel-08.mp4', demo:'assets/videos/reel-08.webm' },
    ],

    graphic: [
      { id:'g1', cat:'Identita',  title:'NORDA Studio',      desc:'Logo, značkový systém a pravidlá používania.',        meta:'Logo',
        image:'assets/posters/graphic-01.svg' },
      { id:'g2', cat:'Typografia',title:'Typografický systém',desc:'Výber rezov a hierarchia pre web aj tlač.',           meta:'Brand manuál',
        image:'assets/posters/graphic-02.svg' },
      { id:'g3', cat:'Identita',  title:'Farebná paleta',    desc:'Paleta a jej použitie naprieč kanálmi.',              meta:'Paleta',
        image:'assets/posters/graphic-03.svg' },
      { id:'g4', cat:'Tlač',      title:'Letná scéna',       desc:'Plagátová séria pre mestský festival.',               meta:'Plagát B1',
        image:'assets/posters/graphic-04.svg' },
      { id:'g5', cat:'Tlač',      title:'Vizitky a tlačoviny',desc:'Sada firemných tlačovín s razbou.',                  meta:'Tlačoviny',
        image:'assets/posters/graphic-05.svg' },
      { id:'g6', cat:'Obaly',     title:'Roast No. 4',       desc:'Obalový dizajn pre pražiareň kávy.',                  meta:'Packaging',
        image:'assets/posters/graphic-06.svg' },
      { id:'g7', cat:'Kampaň',    title:'Vizuálny systém',   desc:'Deväť formátov pre jednu kampaň.',                    meta:'Kampaň',
        image:'assets/posters/graphic-07.svg' },
      { id:'g8', cat:'Gastro',    title:'Menu a cenník',     desc:'Sadzba jedálneho lístka pre reštauráciu.',            meta:'Sadzba',
        image:'assets/posters/graphic-08.svg' },
    ],

    social: [
      { id:'s1', cat:'Instagram', title:'Kaviareň Zrno',    desc:'Obsahový plán, príspevky a komunita — dosah +212 %.', dur:'IG',
        poster:'assets/posters/social-01.svg', video:'assets/videos/social-01.mp4', demo:'assets/videos/reel-01.webm' },
      { id:'s2', cat:'TikTok',    title:'Fit klub Nord',    desc:'Štyri videá týždenne a stabilný rast sledovateľov.',  dur:'TT',
        poster:'assets/posters/social-02.svg', video:'assets/videos/social-02.mp4', demo:'assets/videos/reel-02.webm' },
      { id:'s3', cat:'Instagram', title:'Studio Lumen',     desc:'Vizuálne zjednotený feed a pravidelné stories.',      dur:'IG',
        poster:'assets/posters/social-03.svg', video:'assets/videos/social-03.mp4', demo:'assets/videos/reel-06.webm' },
      { id:'s4', cat:'Facebook',  title:'Pekáreň Klas',     desc:'Lokálna komunikácia a podpora predajní.',             dur:'FB',
        poster:'assets/posters/social-04.svg', video:'assets/videos/social-04.mp4', demo:'assets/videos/reel-03.webm' },
      { id:'s5', cat:'Instagram', title:'Pralinky &amp; spol.', desc:'Produktové série a spolupráce s tvorcami.',       dur:'IG',
        poster:'assets/posters/social-05.svg', video:'assets/videos/social-05.mp4', demo:'assets/videos/reel-07.webm' },
      { id:'s6', cat:'LinkedIn',  title:'Appro Tech',       desc:'Odborný obsah a nábor pre technologickú firmu.',      dur:'IN',
        poster:'assets/posters/social-06.svg', video:'assets/videos/social-06.mp4', demo:'assets/videos/reel-08.webm' },
    ],
  };

  /* ── pomocníci ──────────────────────────────────────────── */
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const REDUCED = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const FINE    = matchMedia('(hover: hover) and (pointer: fine)');

  const ICON = {
    expand:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M10 20H4v-6M20 4l-7 7M4 20l7-7"/></svg>',
    sound :'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 010 7"/></svg>',
    mute  :'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H3v6h3l5 4z"/><path d="M22 9l-5 6M17 9l5 6"/></svg>',
  };

  /* jediné prehrávané video na celej stránke */
  let current = null;
  let hoverTimer = null;
  function stopAll(except) {
    clearTimeout(hoverTimer);
    $$('.slide.is-playing').forEach((slide) => {
      const v = $('video', slide);
      if (!v || v === except) return;
      v.pause();
      slide.classList.remove('is-playing');
    });
    if (current && current !== except) current.pause();
    current = except || null;
  }

  /* ─────────────────────────────────────────────────────────
     2) KARUSEL
     Desktop 5,5 karty · mobil 1,5 — počet riadi premenná --per
     v styles.css. Posun: swajp, ťahanie myšou, šípky, klávesy.
  ───────────────────────────────────────────────────────── */
  function buildCarousel(root) {
    const kind  = root.dataset.carousel;
    const ratio = root.dataset.ratio || '9/16';
    const limit = +root.dataset.limit || 0;      // data-limit="3" = len prvé tri
    const all   = DATA[kind] || [];
    const items = limit ? all.slice(0, limit) : all;
    const rail  = $('[data-rail]', root);
    const track = $('[data-progress]', root);
    const thumb = track ? $('span', track) : null;
    const arrows = $$('.arrow', root);
    if (!rail || !items.length) return null;

    const isVideo = kind !== 'graphic';

    rail.innerHTML = items.map((it, i) => {
      const media = isVideo
        ? `<img src="${it.poster}" alt="Náhľad ukážky ${it.title}" loading="lazy" decoding="async">
           <video preload="none" playsinline muted loop poster="${it.poster}" aria-label="${it.title}">
             <source src="${it.video}" type="video/mp4">
             <source src="${it.demo}" type="video/webm">
           </video>
           <span class="tile__bar" data-bar></span>
           <p class="tile__miss">Ukážka sa doplní do assets/videos/</p>`
        : `<img src="${it.image}" alt="Ukážka grafiky — ${it.title}" loading="lazy" decoding="async">`;

      return `
      <article class="slide" data-i="${i}" role="option" aria-selected="false" aria-label="${it.title}">
        <div class="tile" style="--ratio:${ratio}">
          ${media}
          <span class="tile__shade"></span>
          <span class="tile__cat">${it.cat}</span>
          ${it.dur || it.meta ? `<span class="tile__dur">${it.dur || it.meta}</span>` : ''}
          <button class="tile__play" data-act="${isVideo ? 'play' : 'open'}"
                  aria-label="${isVideo ? 'Prehrať ukážku' : 'Zobraziť'} ${it.title}"></button>
          <button class="tile__expand" data-act="open" aria-label="Otvoriť ${it.title} na celú obrazovku">${ICON.expand}</button>
        </div>
        <div class="slide__meta">
          <h4>${it.title}</h4>
          <p>${it.desc}</p>
        </div>
      </article>`;
    }).join('') + '<span class="rail-end" aria-hidden="true"></span>';

    const slides = $$('.slide', rail);
    const videos = $$('video', rail);

    /* keď video chýba, karta zostane na plagáte */
    videos.forEach((v) => {
      const slide = v.closest('.slide');
      v.addEventListener('error', () => {
        if (v.networkState === HTMLMediaElement.NETWORK_NO_SOURCE) slide.classList.add('no-src');
      }, true);
      const bar = $('[data-bar]', slide);
      v.addEventListener('timeupdate', () => {
        if (bar && v.duration) bar.style.width = (v.currentTime / v.duration * 100) + '%';
      });
    });

    /* ── prehrávanie ── */
    function play(i) {
      const slide = slides[i], v = videos[i];
      if (!v) return;
      if (v.preload === 'none') { v.preload = 'metadata'; v.load(); }
      stopAll(v);
      v.play()
        .then(() => slide.classList.add('is-playing'))
        .catch(() => { if (!v.currentSrc) slide.classList.add('no-src'); });
    }
    function pause(i) {
      const slide = slides[i], v = videos[i];
      if (!v) return;
      v.pause();
      slide.classList.remove('is-playing');
    }
    function toggle(i) {
      const v = videos[i];
      if (!v) return;
      v.paused ? play(i) : pause(i);
    }

    /* ── kliky ── */
    rail.addEventListener('click', (e) => {
      if (rail.dataset.moved === '1') return;      // klik po ťahaní ignorujeme
      const slide = e.target.closest('.slide');
      if (!slide) return;
      const i = +slide.dataset.i;
      const act = e.target.closest('[data-act]')?.dataset.act;
      if (act === 'open') { openLightbox(kind, i); return; }
      if (isVideo) toggle(i); else openLightbox(kind, i);
    });

    /* ── náhľad pri prejdení myšou (len desktop) ── */
    if (isVideo) {
      slides.forEach((slide, i) => {
        slide.addEventListener('mouseenter', () => {
          if (!FINE.matches || REDUCED) return;
          clearTimeout(hoverTimer);
          hoverTimer = setTimeout(() => play(i), 220);
        });
        slide.addEventListener('mouseleave', () => {
          clearTimeout(hoverTimer);
          if (!FINE.matches) return;
          pause(i);
        });
      });
    }

    /* ── posun ── */
    const step = () => {
      const first = slides[0];
      const gap = parseFloat(getComputedStyle(rail).gap) || 18;
      return first.getBoundingClientRect().width + gap;
    };
    const perView = () => Math.max(1, Math.round(rail.clientWidth / step() - 0.5));

    function scrollByCards(dir, count) {
      rail.scrollBy({
        left: dir * step() * (count || perView()),
        behavior: REDUCED ? 'auto' : 'smooth',
      });
    }
    arrows.forEach((btn) => btn.addEventListener('click', () => scrollByCards(+btn.dataset.dir)));

    /* ── klávesnica ── */
    rail.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { e.preventDefault(); scrollByCards(1, 1); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); scrollByCards(-1, 1); }
      if (e.key === 'Home') { e.preventDefault(); rail.scrollTo({ left:0, behavior:'smooth' }); }
      if (e.key === 'End')  { e.preventDefault(); rail.scrollTo({ left: rail.scrollWidth, behavior:'smooth' }); }
    });

    /* ── ťahanie myšou (na dotyku sa swajpuje natívne) ── */
    let dragging = false, startX = 0, startLeft = 0, moved = 0;
    rail.addEventListener('pointerdown', (e) => {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      dragging = true; moved = 0;
      startX = e.clientX; startLeft = rail.scrollLeft;
      rail.dataset.moved = '0';
      rail.classList.add('is-drag');
    });
    rail.addEventListener('pointermove', (e) => {
      if (!dragging) return;
      const dx = e.clientX - startX;
      if (Math.abs(dx) > 4) {
        moved = Math.abs(dx);
        rail.dataset.moved = '1';
        rail.setPointerCapture?.(e.pointerId);
      }
      rail.scrollLeft = startLeft - dx;
    });
    const endDrag = (e) => {
      if (!dragging) return;
      dragging = false;
      rail.classList.remove('is-drag');
      if (e && e.pointerId != null) rail.releasePointerCapture?.(e.pointerId);
      if (moved > 4) settle();
      setTimeout(() => { rail.dataset.moved = '0'; }, 40);
    };
    rail.addEventListener('pointerup', endDrag);
    rail.addEventListener('pointercancel', endDrag);
    rail.addEventListener('pointerleave', endDrag);

    /* po ťahaní dorovnáme na najbližšiu kartu */
    function settle() {
      const s = step();
      const target = Math.round(rail.scrollLeft / s) * s;
      rail.scrollTo({ left: target, behavior: REDUCED ? 'auto' : 'smooth' });
    }

    /* ── ukazovateľ posunu + stav šípok ── */
    function sync() {
      const max = rail.scrollWidth - rail.clientWidth;
      const ratioSeen = rail.clientWidth / rail.scrollWidth;
      if (thumb && track) {
        const w = Math.max(track.clientWidth * ratioSeen, 28);
        thumb.style.width = w + 'px';
        thumb.style.transform = `translateX(${max > 0 ? (rail.scrollLeft / max) * (track.clientWidth - w) : 0}px)`;
      }
      arrows.forEach((btn) => {
        const dir = +btn.dataset.dir;
        btn.disabled = dir < 0 ? rail.scrollLeft < 8 : rail.scrollLeft > max - 8;
      });
      slides.forEach((slide) => {
        const box = slide.getBoundingClientRect();
        const railBox = rail.getBoundingClientRect();
        const seen = box.left >= railBox.left - 8 && box.right <= railBox.right + 8;
        slide.setAttribute('aria-selected', seen ? 'true' : 'false');
      });
    }
    rail.addEventListener('scroll', () => requestAnimationFrame(sync), { passive:true });
    addEventListener('resize', sync);
    sync();

    /* ── zastavenie mimo obrazovky ── */
    if (isVideo && 'IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => {
          if (en.intersectionRatio < 0.35) {
            const i = +en.target.dataset.i;
            if (videos[i] && !videos[i].paused) pause(i);
          }
        });
      }, { root: rail, threshold:[0, 0.35, 0.8] });
      slides.forEach((s) => io.observe(s));
    }

    /* ── tichý náhľad prvej karty (hero) ── */
    if (isVideo && root.dataset.autoplay === '1' && !REDUCED && 'IntersectionObserver' in window) {
      new IntersectionObserver((entries) => {
        entries.forEach((en) => {
          if (en.isIntersecting) { if (!current) play(0); }
          else pause(0);
        });
      }, { threshold:0.4 }).observe(slides[0]);
    }

    return { kind, items, play, pause, sync };
  }

  const carousels = $$('[data-carousel]').map(buildCarousel).filter(Boolean);

  /* ─────────────────────────────────────────────────────────
     3) LIGHTBOX
  ───────────────────────────────────────────────────────── */
  const lb      = $('#lightbox');
  const lbStage = $('#lbStage');
  let lbKind = null, lbIndex = 0, lastFocus = null;

  function openLightbox(kind, index) {
    const items = DATA[kind];
    if (!items || !items[index]) return;
    lbKind = kind; lbIndex = index;
    lastFocus = document.activeElement;
    stopAll(null);
    renderLightbox();
    lb.hidden = false;
    document.body.style.overflow = 'hidden';
    $('#lbClose').focus();
  }

  function renderLightbox() {
    const it = DATA[lbKind][lbIndex];
    const isVideo = lbKind !== 'graphic';
    lbStage.innerHTML = isVideo
      ? `<video controls autoplay playsinline loop poster="${it.poster}" aria-label="${it.title}">
           <source src="${it.video}" type="video/mp4">
           <source src="${it.demo}" type="video/webm">
         </video>
         <figcaption class="lb__cap"><b>${it.title}</b>${it.desc}</figcaption>`
      : `<img src="${it.image}" alt="Ukážka grafiky — ${it.title}">
         <figcaption class="lb__cap"><b>${it.title}</b>${it.desc}</figcaption>`;
    const v = $('video', lbStage);
    if (v) v.play().catch(() => {});
  }

  function closeLightbox() {
    const v = $('video', lbStage);
    if (v) v.pause();
    lb.hidden = true;
    lbStage.innerHTML = '';
    document.body.style.overflow = '';
    lastFocus?.focus();
  }

  function stepLightbox(d) {
    const len = DATA[lbKind].length;
    lbIndex = (lbIndex + d + len) % len;
    renderLightbox();
  }

  $('#lbClose').addEventListener('click', closeLightbox);
  $('#lbPrev').addEventListener('click', () => stepLightbox(-1));
  $('#lbNext').addEventListener('click', () => stepLightbox(1));
  lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
  addEventListener('keydown', (e) => {
    if (lb.hidden) return;
    if (e.key === 'Escape')     closeLightbox();
    if (e.key === 'ArrowRight') stepLightbox(1);
    if (e.key === 'ArrowLeft')  stepLightbox(-1);
  });

  /* ─────────────────────────────────────────────────────────
     4) NAVIGÁCIA
  ───────────────────────────────────────────────────────── */
  const nav = $('#nav');
  const onScroll = () => nav.classList.toggle('is-stuck', scrollY > 10);
  addEventListener('scroll', onScroll, { passive:true });
  onScroll();

  const burger = $('#burger'), menu = $('#mobileMenu');
  const setMenu = (open) => {
    menu.hidden = !open;
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Zavrieť menu' : 'Otvoriť menu');
  };
  burger.addEventListener('click', () => setMenu(menu.hidden));
  menu.addEventListener('click', (e) => { if (e.target.closest('a')) setMenu(false); });
  addEventListener('keydown', (e) => { if (e.key === 'Escape' && !menu.hidden) setMenu(false); });
  matchMedia('(min-width: 981px)').addEventListener('change', (e) => { if (e.matches) setMenu(false); });

  /* zvýraznenie aktívnej sekcie */
  const navLinks = $$('.nav__links a');
  const sections = navLinks.map((a) => $(a.getAttribute('href'))).filter(Boolean);
  if ('IntersectionObserver' in window && sections.length) {
    const spy = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        navLinks.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === '#' + en.target.id));
      });
    }, { rootMargin:'-45% 0px -50% 0px' });
    sections.forEach((s) => spy.observe(s));
  }

  /* ─────────────────────────────────────────────────────────
     5) ODHALENIE OBSAHU + POČÍTADLÁ
  ───────────────────────────────────────────────────────── */
  const reveals = $$('.reveal');
  if ('IntersectionObserver' in window && !REDUCED) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en, k) => {
        if (!en.isIntersecting) return;
        setTimeout(() => en.target.classList.add('in'), k * 70);
        io.unobserve(en.target);
      });
    }, { rootMargin:'0px 0px -8% 0px', threshold:0.06 });
    reveals.forEach((el) => io.observe(el));
  } else {
    reveals.forEach((el) => el.classList.add('in'));
  }

  /* ─────────────────────────────────────────────────────────
     6) FORMULÁR
     Momentálne otvára e-mailového klienta. Pre odosielanie na
     server nahraďte blok mailto volaním fetch('/api/...').
  ───────────────────────────────────────────────────────── */
  const form = $('#contactForm'), status = $('#formStatus');
  if (form) {
    const setErr = (input, msg) => {
      input.closest('.field').classList.toggle('has-err', !!msg);
      const el = $(`.err[data-for="${input.id}"]`);
      if (el) el.textContent = msg || '';
      return !msg;
    };

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = $('#name'), email = $('#email'), msg = $('#msg');
      const ok = [
        setErr(name,  name.value.trim().length < 2 ? 'Doplňte meno alebo názov firmy.' : ''),
        setErr(email, /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(email.value.trim()) ? '' : 'Skontrolujte tvar e-mailu.'),
        setErr(msg,   msg.value.trim().length < 10 ? 'Napíšte aspoň pár viet o projekte.' : ''),
      ].every(Boolean);

      if (!ok) { status.textContent = 'Formulár ešte nie je kompletný.'; return; }

      const services = $$('input[name="sluzba"]:checked').map((c) => c.value).join(', ') || 'neurčené';
      const body = encodeURIComponent(
        `Meno a firma: ${name.value.trim()}\nE-mail: ${email.value.trim()}\nZáujem o: ${services}\n\n${msg.value.trim()}`
      );
      location.href = `mailto:ahoj@ideanet.sk?subject=${encodeURIComponent('Dopyt z webu — ' + name.value.trim())}&body=${body}`;
      status.textContent = 'Otváram váš e-mailový klient…';
      form.reset();
    });

    ['#name', '#email', '#msg'].forEach((sel) => {
      const el = $(sel);
      el?.addEventListener('input', () => {
        if (el.closest('.field').classList.contains('has-err')) setErr(el, '');
      });
    });
  }

  /* ─────────────────────────────────────────────────────────
     7) INTERAKCIE V HLAVIČKE
     Svetlo za kurzorom, náklon kariet a magnetické tlačidlo.
     Všetko len pre myš a len keď používateľ nežiada tlmený pohyb.
  ───────────────────────────────────────────────────────── */
  const hero = $('#hero');
  if (hero && !REDUCED && FINE.matches) {
    let raf = 0, mx = 50, my = 35;

    hero.addEventListener('pointermove', (e) => {
      if (e.pointerType !== 'mouse') return;
      const r = hero.getBoundingClientRect();
      mx = ((e.clientX - r.left) / r.width) * 100;
      my = ((e.clientY - r.top) / r.height) * 100;
      hero.classList.add('is-live');
      if (raf) return;
      raf = requestAnimationFrame(() => {
        raf = 0;
        hero.style.setProperty('--mx', mx.toFixed(2) + '%');
        hero.style.setProperty('--my', my.toFixed(2) + '%');
      });
    });
    hero.addEventListener('pointerleave', () => hero.classList.remove('is-live'));

    /* náklon kariet v hero karuseli */
    const heroReel = $('#heroReel');
    if (heroReel) {
      const MAX = 7;                       // stupňov
      heroReel.addEventListener('pointerenter', () => heroReel.classList.add('is-hover'));
      heroReel.addEventListener('pointerleave', () => {
        heroReel.classList.remove('is-hover');
        $$('.tile', heroReel).forEach((t) => {
          t.style.setProperty('--rx', '0deg');
          t.style.setProperty('--ry', '0deg');
        });
      });
      $$('.slide', heroReel).forEach((slide) => {
        const tile = $('.tile', slide);
        slide.addEventListener('pointermove', (e) => {
          if (e.pointerType !== 'mouse') return;
          const r = tile.getBoundingClientRect();
          const px = (e.clientX - r.left) / r.width - 0.5;
          const py = (e.clientY - r.top) / r.height - 0.5;
          tile.style.setProperty('--ry', (px * MAX).toFixed(2) + 'deg');
          tile.style.setProperty('--rx', (-py * MAX).toFixed(2) + 'deg');
        });
      });
    }

    /* magnetické hlavné tlačidlo */
    const magnet = $('.hero__cta .btn--dark');
    if (magnet) {
      const PULL = 7;                      // px
      magnet.addEventListener('pointermove', (e) => {
        const r = magnet.getBoundingClientRect();
        magnet.style.setProperty('--tx', (((e.clientX - r.left) / r.width - .5) * PULL * 2).toFixed(1) + 'px');
        magnet.style.setProperty('--ty', (((e.clientY - r.top) / r.height - .5) * PULL).toFixed(1) + 'px');
      });
      magnet.addEventListener('pointerleave', () => {
        magnet.style.setProperty('--tx', '0px');
        magnet.style.setProperty('--ty', '0px');
      });
    }
  }

  /* odkazy s data-preselect zaškrtnú danú službu v kontaktnom formulári
     (napr. CTA „Mám záujem o školenie“) predtým, než sa naň presunie fokus */
  $$('[data-preselect]').forEach((a) => {
    a.addEventListener('click', () => {
      const chip = $$('input[name="sluzba"]').find((i) => i.value === a.dataset.preselect);
      if (chip) chip.checked = true;
    });
  });

  /* rok v pätičke */
  const year = $('#year');
  if (year) year.textContent = new Date().getFullYear();
})();
