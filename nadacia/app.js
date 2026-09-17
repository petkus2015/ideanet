/* ═══════════════════════════════════════════════
   Nadácia Anjelské krídla — interakcie
   Bez závislostí. Obsah slidera a galérie meňte
   v poliach SLIDES a GALLERY nižšie.
   ═══════════════════════════════════════════════ */
(() => {
  'use strict';

  /* Logo nadácie. V hlavičke je samotná značka (krídla so srdcom), v pätičke
     celé logo aj s názvom. Ak súbor chýba, zostane kreslená značka. */
  const LOGO_MARK = 'assets/img/logo-srdce.svg';   // značka do hlavičky
  const LOGO_KRIDLA = 'assets/img/logo-mark.png';  // staršia značka s krídlami
  const LOGO_FULL = 'assets/img/logo.png';

  /* Transparentný účet nadácie. Doplňte IBAN a odkaz na jeho výpis v banke.
     Kým je IBAN prázdny, v karte sa ukáže len naznačené miesto pre číslo účtu
     a tlačidlo Kopírovať na to upozorní. Prázdny odkaz sa skryje. */
  const UCET = {
    iban: 'SK23 8330 0000 0027 0189 5491',            // transparentný účet nadácie
    link: 'https://www.fio.sk/'                    // sem patrí adresa výpisu účtu v banke
  };

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ───────────────────────────────────────────
     1) OBSAH HERO SLIDERA
  ─────────────────────────────────────────── */
  const SLIDES = [
    {
      img:'assets/img/hero-1.svg',
      alt:'Dieťa s rozpaženými rukami pod anjelskými krídlami v úsvite',
      eyebrow:'Nadácia Anjelské krídla',
      title:'Naše krídla sú aj vašimi krídlami',
      text:'Pomáhame vážne chorým deťom, ich rodinám a všetkým, ktorým sa život zrazu obrátil naruby.',
      cta:[{t:'Chcem pomôcť', href:'#podpora', k:'primary'},{t:'Ako pomáhame', href:'#pomoc', k:'ghost'}]
    },
    {
      img:'assets/img/hero-2.svg',
      alt:'Rodina pod svetlom v tvare srdca',
      eyebrow:'Od roku 2016',
      title:'Nikto nemá ostať na to sám',
      text:'Podporujeme mamy, ktoré sa o choré deti starajú samy, rodiny v núdzi aj krízové centrá.',
      cta:[{t:'Príbeh nadácie', href:'#o-nas', k:'primary'},{t:'Napíšte nám', href:'#kontakt', k:'ghost'}]
    },
    {
      img:'assets/img/hero-3.svg',
      alt:'Rodina na kopci pri západe slnka',
      eyebrow:'2 % z dane',
      title:'Dve percentá, ktoré vás nič nestoja',
      text:'Nadácia nedostáva štátne dotácie. Vaše 2 % zo zaplatenej dane menia konkrétne rodiny.',
      cta:[{t:'Údaje pre vyhlásenie', href:'#dve-percenta', k:'primary'},{t:'Podporiť darom', href:'#podpora', k:'ghost'}]
    },
    {
      img:'assets/img/hero-4.svg',
      alt:'Dobrovoľníci nesúci balíky pomoci',
      eyebrow:'Dobrovoľníci',
      title:'Sto percent práce z voľného času',
      text:'Nadáciu tvoria odborníci a dobrovoľníci, ktorí pomáhajú bez nároku na odmenu.',
      cta:[{t:'Pridať sa', href:'#kontakt', k:'primary'},{t:'Naše projekty', href:'#projekty', k:'ghost'}]
    }
  ];

  /* ───────────────────────────────────────────
     2) OBSAH GALÉRIE
     span: 'w2' širšie, 'h2' vyššie, '' základné
  ─────────────────────────────────────────── */
  const GALLERY = [
    { src:'assets/img/gal-01.svg', cap:'Charitatívny beh pre rodiny',        span:'w2' },
    { src:'assets/img/gal-04.svg', cap:'Radosť je najlepšie poďakovanie',    span:'h2' },
    { src:'assets/img/gal-03.svg', cap:'Pomoc, ktorá drží pokope',           span:''   },
    { src:'assets/img/gal-02.svg', cap:'Benefičný koncert v Žiline',         span:''   },
    { src:'assets/img/gal-07.svg', cap:'Aktivity pre deti a rodičov',        span:'w2' },
    { src:'assets/img/gal-06.svg', cap:'Mama a dieťa — každodenná odvaha',   span:'h2' },
    { src:'assets/img/gal-09.svg', cap:'Balíčky pripravené na cestu',        span:''   },
    { src:'assets/img/gal-05.svg', cap:'Nezabúdame na seniorov',             span:''   },
    { src:'assets/img/gal-10.svg', cap:'Tím dobrovoľníkov nadácie',          span:'w2' },
    { src:'assets/img/gal-08.svg', cap:'Anjelské krídla nad Žilinou',        span:''   }
  ];

  /* ───────────────────────────────────────────
     3) NÁHĽAD ČLÁNKOV
     Po prepojení s redakčným systémom sem stačí
     doplniť odkaz do "href".
  ─────────────────────────────────────────── */
  const POSTS = [
    { img:'assets/img/gal-09.svg', meta:'Pomoc rodinám',   title:'Balíčky pre desať rodín putovali na severné Slovensko', href:'#' },
    { img:'assets/img/gal-02.svg', meta:'Podujatia',        title:'Benefičný koncert opäť spojil ľudí v správnej veci',     href:'#' },
    { img:'assets/img/gal-01.svg', meta:'Charitatívny beh', title:'Bežali sme za deti — štartovné išlo celé na pomoc',      href:'#' },
    { img:'assets/img/gal-06.svg', meta:'Príbehy',          title:'Mama troch detí: „Zrazu sme na to neboli sami“',         href:'#' },
    { img:'assets/img/gal-07.svg', meta:'Inklúzia',         title:'Popoludnie pre deti a rodičov v komunitnom centre',      href:'#' },
    { img:'assets/img/gal-05.svg', meta:'Seniori',          title:'Návštevy v zariadení, na ktoré sa tešíme celý rok',      href:'#' },
    { img:'assets/img/gal-04.svg', meta:'Rehabilitácia',    title:'Týždenný ozdravný pobyt pre šesť rodín',                 href:'#' },
    { img:'assets/img/gal-10.svg', meta:'Dobrovoľníci',     title:'Nové tváre v tíme — vitajte medzi anjelmi',              href:'#' }
  ];

  /* ───────────────────────────────────────────
     4) HERO SLIDER
  ─────────────────────────────────────────── */
  const DUR = 6500;
  const slidesWrap = $('#heroSlides');
  const copyWrap   = $('#heroCopy');
  const playBtn    = $('#heroPlay');

  let cur = 0, timer = null, playing = !reduce;

  slidesWrap.innerHTML = SLIDES.map((s, i) => `
    <div class="hero__slide${i === 0 ? ' is-on' : ''}" aria-hidden="${i !== 0}">
      <img src="${s.img}" alt="${s.alt}" ${i ? 'loading="lazy"' : 'fetchpriority="high"'} decoding="async">
    </div>`).join('');

  const slideEls = $$('.hero__slide', slidesWrap);

  function renderCopy(i){
    const s = SLIDES[i];
    copyWrap.innerHTML = `
      <p class="eyebrow">${s.eyebrow}</p>
      <h1>${s.title}</h1>
      <p>${s.text}</p>
      <div class="row">${s.cta.map(c => `<a class="btn btn--${c.k}" href="${c.href}">${c.t}</a>`).join('')}</div>`;
  }

  function go(i, user){
    i = (i + SLIDES.length) % SLIDES.length;
    if (i === cur && copyWrap.childElementCount) return;
    slideEls[cur].classList.remove('is-on');
    slideEls[cur].setAttribute('aria-hidden', 'true');

    cur = i;
    slideEls[cur].classList.add('is-on');
    slideEls[cur].setAttribute('aria-hidden', 'false');
    renderCopy(cur);
    if (user && playing) play();     // posun používateľom reštartuje odpočet, prehrávanie beží ďalej
  }

  function play(){
    if (reduce) return;
    playing = true;
    playBtn.dataset.state = 'playing';
    playBtn.setAttribute('aria-label', 'Pozastaviť automatické prehrávanie');
    playBtn.setAttribute('title', 'Pozastaviť');
    clearInterval(timer);
    timer = setInterval(() => go(cur + 1), DUR);
  }
  function pause(){
    playing = false;
    playBtn.dataset.state = 'paused';
    playBtn.setAttribute('aria-label', 'Spustiť automatické prehrávanie');
    playBtn.setAttribute('title', 'Prehrať');
    clearInterval(timer);
  }

  renderCopy(0);
  playBtn.addEventListener('click', () => (playing ? pause() : play()));
  $('#heroPrev').addEventListener('click', () => go(cur - 1, true));
  $('#heroNext').addEventListener('click', () => go(cur + 1, true));

  const hero = $('.hero');
  hero.addEventListener('mouseenter', () => { if (playing) clearInterval(timer); });
  hero.addEventListener('mouseleave', () => { if (playing) play(); });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) clearInterval(timer);
    else if (playing) play();
  });

  // klávesnica
  hero.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft')  { go(cur - 1, true); }
    if (e.key === 'ArrowRight') { go(cur + 1, true); }
  });

  // ťahanie prstom po fotke
  const heroMedia = $('.hero__media');
  let sx = 0, sy = 0, drag = false;
  heroMedia.addEventListener('touchstart', e => { sx = e.touches[0].clientX; sy = e.touches[0].clientY; drag = true; }, {passive:true});
  heroMedia.addEventListener('touchend', e => {
    if (!drag) return;
    drag = false;
    const dx = e.changedTouches[0].clientX - sx;
    const dy = e.changedTouches[0].clientY - sy;
    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) go(cur + (dx < 0 ? 1 : -1), true);
  }, {passive:true});

  if (reduce) pause(); else play();

  /* ───────────────────────────────────────────
     4b) KARUSEL ČLÁNKOV
     Posun prstom rieši natívne scrollovanie,
     šípky a guličky posúvajú po kartách.
  ─────────────────────────────────────────── */
  const track = $('#postsTrack');
  const dotsBox = $('#postsDots');
  const prevBtn = $('#postsPrev');
  const nextBtn = $('#postsNext');

  track.innerHTML = POSTS.map(p => `
    <li class="post">
      <a class="post__link" href="${p.href}" aria-label="${p.title}">
        <span class="post__media"><img src="${p.img}" alt="" loading="lazy" decoding="async"></span>
        <span class="post__meta">${p.meta}</span>
        <span class="post__title">${p.title}</span>
      </a>
    </li>`).join('');

  const cards = $$('.post', track);

  const step      = () => cards.length > 1
    ? cards[1].getBoundingClientRect().left - cards[0].getBoundingClientRect().left
    : cards[0].getBoundingClientRect().width;
  const maxScroll = () => track.scrollWidth - track.clientWidth;
  const atEnd     = () => track.scrollLeft >= maxScroll() - 2;
  const startX    = () => Math.max(0, track.scrollLeft + (cards[0].getBoundingClientRect().left - track.getBoundingClientRect().left));
  const atStart   = () => track.scrollLeft <= startX() + 2;

  // karta, ktorá je najbližšie k ľavému okraju pásu
  const nearest = () => {
    const x = track.getBoundingClientRect().left;
    let best = 0, bd = Infinity;
    cards.forEach((c, i) => {
      const d = Math.abs(c.getBoundingClientRect().left - x);
      if (d < bd){ bd = d; best = i; }
    });
    return best;
  };

  const slideTo = i => {
    i = Math.max(0, Math.min(cards.length - 1, i));
    const x = track.scrollLeft + (cards[i].getBoundingClientRect().left - track.getBoundingClientRect().left);
    track.scrollTo({ left: Math.min(x, maxScroll()), behavior: reduce ? 'auto' : 'smooth' });
  };

  // guličiek je toľko, koľko je skutočných pozícií posunu — každá je dosiahnuteľná
  let dots = [];
  function buildDots(){
    const pages = Math.max(1, Math.ceil(maxScroll() / step()) + 1);
    if (dots.length === pages) return;
    dotsBox.innerHTML = Array.from({length: pages}, (_, i) => `
      <button class="slider__dot" data-i="${i}" role="tab" aria-selected="false"
        aria-label="Posunúť na ${i + 1}. pozíciu z ${pages}"></button>`).join('');
    dots = $$('.slider__dot', dotsBox);
  }

  const syncSlider = () => {
    const i = atEnd() ? dots.length - 1 : Math.min(dots.length - 1, Math.round(track.scrollLeft / step()));
    dots.forEach((d, k) => {
      d.classList.toggle('is-on', k === i);
      d.setAttribute('aria-selected', String(k === i));
    });
    prevBtn.setAttribute('aria-disabled', String(atStart()));
    nextBtn.setAttribute('aria-disabled', String(atEnd()));
  };

  let ticking = false;
  track.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => { syncSlider(); ticking = false; });
  }, {passive:true});

  prevBtn.addEventListener('click', () => slideTo(nearest() - 1));
  nextBtn.addEventListener('click', () => slideTo(nearest() + 1));
  dotsBox.addEventListener('click', e => {
    const b = e.target.closest('.slider__dot');
    if (b) slideTo(+b.dataset.i);
  });
  track.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft')  { e.preventDefault(); slideTo(nearest() - 1); }
    if (e.key === 'ArrowRight') { e.preventDefault(); slideTo(nearest() + 1); }
  });

  let resizeT = null;
  addEventListener('resize', () => {
    clearTimeout(resizeT);
    resizeT = setTimeout(() => { buildDots(); syncSlider(); }, 150);
  }, {passive:true});

  buildDots();
  syncSlider();

  /* ───────────────────────────────────────────
     4c) PARTNERI
     Firma s logom má vyplnené "logo", bez loga
     sa vypíše len názov. "url" je nepovinná.
  ─────────────────────────────────────────── */
  const PARTNERI = [
    { nazov:'Aurora',              logo:'assets/img/partner-1.svg', url:'' },
    { nazov:'Vitalis',             logo:'assets/img/partner-2.svg', url:'' },
    { nazov:'Riečka',              logo:'assets/img/partner-3.svg', url:'' },
    { nazov:'Stavebniny Považie',  logo:'',                         url:'' },
    { nazov:'Kaviareň Pod Vežou',  logo:'',                         url:'' },
    { nazov:'Autodoprava Kováč',   logo:'',                         url:'' }
  ];

  const partneriGrid = $('#partneriGrid');
  if (partneriGrid){
    partneriGrid.innerHTML = PARTNERI.map(p => {
      const obsah = p.logo
        ? `<img src="${p.logo}" alt="${p.nazov}" loading="lazy" decoding="async">`
        : `<span class="partner__nazov">${p.nazov}</span>`;
      const vnutro = p.url
        ? `<a href="${p.url}" target="_blank" rel="noopener" aria-label="${p.nazov}">${obsah}</a>`
        : obsah;
      return `<li class="partner">${vnutro}</li>`;
    }).join('');
  }

  /* ───────────────────────────────────────────
     5) GALÉRIA + LIGHTBOX
  ─────────────────────────────────────────── */
  const gal = $('#gal');
  gal.innerHTML = GALLERY.map((g, i) => `
    <figure class="gal__item${g.span ? ' gal__item--' + g.span : ''}" data-i="${i}" tabindex="0" role="button"
            aria-label="Zväčšiť: ${g.cap}">
      <img src="${g.src}" alt="${g.cap}" loading="lazy" decoding="async">
      <figcaption>${g.cap}</figcaption>
    </figure>`).join('');

  const lb = $('#lb'), lbImg = $('#lbImg'), lbCap = $('#lbCap'), lbCount = $('#lbCount');
  let lbI = 0, lastFocus = null;

  function lbShow(i){
    lbI = (i + GALLERY.length) % GALLERY.length;
    const g = GALLERY[lbI];
    lbImg.src = g.src;
    lbImg.alt = g.cap;
    lbCap.textContent = g.cap;
    lbCount.textContent = `${lbI + 1} / ${GALLERY.length}`;
  }
  function lbOpen(i){
    lastFocus = document.activeElement;
    lbShow(i);
    lb.hidden = false;
    document.body.classList.add('lb-open');
    $('#lbClose').focus();
  }
  function lbClose(){
    lb.hidden = true;
    document.body.classList.remove('lb-open');
    if (lastFocus) lastFocus.focus();
  }

  gal.addEventListener('click', e => {
    const f = e.target.closest('.gal__item');
    if (f) lbOpen(+f.dataset.i);
  });
  gal.addEventListener('keydown', e => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const f = e.target.closest('.gal__item');
    if (f) { e.preventDefault(); lbOpen(+f.dataset.i); }
  });

  $('#lbClose').addEventListener('click', lbClose);
  $('#lbPrev').addEventListener('click', () => lbShow(lbI - 1));
  $('#lbNext').addEventListener('click', () => lbShow(lbI + 1));
  lb.addEventListener('click', e => { if (e.target === lb) lbClose(); });
  document.addEventListener('keydown', e => {
    if (lb.hidden) return;
    if (e.key === 'Escape')     lbClose();
    if (e.key === 'ArrowLeft')  lbShow(lbI - 1);
    if (e.key === 'ArrowRight') lbShow(lbI + 1);
    if (e.key === 'Tab'){                         // udrží fokus v dialógu
      const f = $$('button', lb);
      const first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first){ e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last){ e.preventDefault(); first.focus(); }
    }
  });
  let lsx = 0;
  lb.addEventListener('touchstart', e => { lsx = e.touches[0].clientX; }, {passive:true});
  lb.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - lsx;
    if (Math.abs(dx) > 50) lbShow(lbI + (dx < 0 ? 1 : -1));
  }, {passive:true});

  /* ───────────────────────────────────────────
     6) NAVIGÁCIA, SCROLLSPY, PROGRES
  ─────────────────────────────────────────── */
  const nav = $('#nav'), links = $('#navLinks'), burger = $('#burger'), progress = $('#navProgress');

  burger.addEventListener('click', () => {
    const open = links.classList.toggle('is-open');
    nav.classList.toggle('is-stuck', open || scrollY > 40);
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Zavrieť menu' : 'Otvoriť menu');
  });
  links.addEventListener('click', e => {
    if (e.target.closest('a')){
      links.classList.remove('is-open');
      nav.classList.toggle('is-stuck', scrollY > 40);
      burger.setAttribute('aria-expanded', 'false');
      burger.setAttribute('aria-label', 'Otvoriť menu');
    }
  });

  const navAnchors = $$('#navLinks a[href^="#"]:not(.btn)');
  const sections = navAnchors.map(a => $(a.getAttribute('href'))).filter(Boolean);

  const onScroll = () => {
    const y = scrollY;
    nav.classList.toggle('is-stuck', y > 40 || links.classList.contains('is-open'));
    $('#toTop').classList.toggle('is-on', y > 700);

    const h = document.documentElement.scrollHeight - innerHeight;
    progress.style.width = (h > 0 ? (y / h) * 100 : 0) + '%';

    let act = -1;
    sections.forEach((s, i) => { if (s.getBoundingClientRect().top <= 140) act = i; });
    navAnchors.forEach((a, i) => a.classList.toggle('is-active', i === act));
  };
  addEventListener('scroll', onScroll, {passive:true});
  onScroll();

  $('#toTop').addEventListener('click', () => scrollTo({top:0, behavior: reduce ? 'auto' : 'smooth'}));

  // odsadenie kotiev o výšku hlavičky
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href^="#"]');
    if (!a || a.getAttribute('href') === '#') return;
    const t = $(a.getAttribute('href'));
    if (!t) return;
    e.preventDefault();
    const off = t.id === 'top' ? 0 : parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) + 12;
    scrollTo({top: t.getBoundingClientRect().top + scrollY - off, behavior: reduce ? 'auto' : 'smooth'});
    history.replaceState(null, '', '#' + t.id);
  });

  /* ───────────────────────────────────────────
     7) ODHAĽOVANIE + POČÍTADLÁ
  ─────────────────────────────────────────── */
  const io = new IntersectionObserver((entries) => {
    entries.forEach(en => {
      if (!en.isIntersecting) return;
      en.target.classList.add('is-in');
      io.unobserve(en.target);
      const num = en.target.querySelector('[data-count]');
      if (num) count(num);
    });
  }, {threshold:.16, rootMargin:'0px 0px -60px 0px'});
  $$('.reveal').forEach((el, i) => { el.style.transitionDelay = (i % 4) * 70 + 'ms'; io.observe(el); });

  const ioNum = new IntersectionObserver(entries => {
    entries.forEach(en => { if (en.isIntersecting){ count(en.target); ioNum.unobserve(en.target); } });
  }, {threshold:.5});
  $$('[data-count]').forEach(el => ioNum.observe(el));

  function count(el){
    const to = +el.dataset.count;
    const suffix = el.dataset.suffix || '';
    if (reduce || el.dataset.plain || to === 0){ el.innerHTML = to + suffix; return; }
    const t0 = performance.now(), d = 1400;
    const step = t => {
      const p = Math.min((t - t0) / d, 1);
      el.innerHTML = Math.round(to * (1 - Math.pow(1 - p, 3))) + suffix;
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  /* ───────────────────────────────────────────
     8) KOPÍROVANIE ÚDAJOV
  ─────────────────────────────────────────── */
  const toast = $('#toast');
  let toastT = null;
  function say(msg){
    toast.textContent = msg;
    toast.classList.add('is-on');
    clearTimeout(toastT);
    toastT = setTimeout(() => toast.classList.remove('is-on'), 2400);
  }

  async function toClipboard(text){
    try{
      await navigator.clipboard.writeText(text);
      return true;
    }catch{
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.cssText = 'position:fixed;opacity:0';
      document.body.appendChild(ta);
      ta.select();
      let ok = false;
      try{ ok = document.execCommand('copy'); }catch{}
      ta.remove();
      return ok;
    }
  }

  $$('[data-copy]').forEach(btn => {
    const label = btn.textContent;
    btn.addEventListener('click', async () => {
      if (!btn.dataset.copy){
        say('IBAN transparentného účtu zatiaľ nie je doplnený.');
        return;
      }
      const ok = await toClipboard(btn.dataset.copy);
      say(ok ? 'Skopírované do schránky' : 'Kopírovanie sa nepodarilo — skúste označiť text ručne.');
      if (!ok) return;
      btn.classList.add('is-done');
      btn.textContent = 'Skopírované';
      setTimeout(() => { btn.classList.remove('is-done'); btn.textContent = label; }, 2000);
    });
  });

  /* ───────────────────────────────────────────
     9) TLAČIVÁ NA STIAHNUTIE
     Ak súbor v assets/dokumenty/ ešte nie je nahratý,
     namiesto chybovej stránky ukážeme návštevníkovi,
     ako sa k tlačivu dostane.
  ─────────────────────────────────────────── */
  $$('[data-doc]').forEach(a => {
    a.addEventListener('click', async e => {
      e.preventDefault();                   // o stiahnutie sa postaráme až po overení súboru
      const href = a.getAttribute('href');
      let ok = false;
      try{ ok = (await fetch(href, {method:'HEAD'})).ok; }catch{ ok = false; }
      if (!ok){
        say(`Tlačivo „${a.dataset.doc}“ vám radi pošleme na info@nadaciaanjelskekridla.sk`);
        return;
      }
      const tmp = document.createElement('a');
      tmp.href = href;
      tmp.download = href.split('/').pop();
      document.body.appendChild(tmp);
      tmp.click();
      tmp.remove();
    });
  });

  /* ───────────────────────────────────────────
     10) FORMULÁR
  ─────────────────────────────────────────── */
  const form = $('#form');
  const bad = (input, on) => {
    const f = input.closest('.f');
    if (f) f.classList.toggle('is-bad', on);
    else $(`.err[data-for="${input.id}"]`)?.classList.toggle('is-on', on);
  };

  /* pole na prílohu sa ukáže len pri žiadosti o pomoc */
  const temaEl   = $('#ftopic');
  const fileWrap = $('#fileWrap');
  const fileIn   = $('#ffile');
  const fileList = $('#fileList');
  const fileHint = $('#fileHint');
  const TEMA_ZIADOST = 'Žiadosť o pomoc';

  const velkost = b => b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' kB';

  function prepniPrilohu(){
    const treba = temaEl.value === TEMA_ZIADOST;
    fileWrap.hidden = !treba;
    if (!treba){
      fileIn.value = '';
      fileList.innerHTML = '';
      fileHint.hidden = true;
    }
  }
  temaEl.addEventListener('change', prepniPrilohu);
  prepniPrilohu();

  fileIn.addEventListener('change', () => {
    const subory = [...fileIn.files];
    fileList.innerHTML = subory.map(f =>
      `<li>${f.name}<span>${velkost(f.size)}</span></li>`).join('');
    fileHint.hidden = subory.length === 0;
  });

  ['fname','femail','fmsg','fgdpr'].forEach(id => {
    const el = $('#' + id);
    el.addEventListener('input',  () => bad(el, false));
    el.addEventListener('change', () => bad(el, false));
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    const name = $('#fname'), mail = $('#femail'), msg = $('#fmsg'), gdpr = $('#fgdpr');
    let first = null;
    const check = (el, ok) => { bad(el, !ok); if (!ok && !first) first = el; return ok; };

    check(name, name.value.trim().length > 1);
    check(mail, /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(mail.value.trim()));
    check(msg,  msg.value.trim().length > 4);
    check(gdpr, gdpr.checked);

    if (first){ first.focus(); say('Skontrolujte, prosím, zvýraznené polia.'); return; }

    const subory = fileWrap.hidden ? [] : [...fileIn.files];
    const prilohy = subory.length
      ? `\n\nPrílohy (pripojte ich, prosím, k tomuto e-mailu):\n` + subory.map(f => `– ${f.name}`).join('\n')
      : '';
    const body = `Meno: ${name.value.trim()}\nE-mail: ${mail.value.trim()}\nTéma: ${$('#ftopic').value}\n\n${msg.value.trim()}${prilohy}`;
    location.href = `mailto:info@nadaciaanjelskekridla.sk?subject=${encodeURIComponent('Web — ' + $('#ftopic').value)}&body=${encodeURIComponent(body)}`;
    say(subory.length
      ? 'Otvárame e-mail — nezabudnite pripojiť vybrané súbory.'
      : 'Otvárame váš e-mailový klient…');
  });

  /* ───────────────────────────────────────────
     11) LOGO NADÁCIE
  ─────────────────────────────────────────── */
  const putLogo = (mark, src, cls) => {
    if (!mark) return;
    mark.classList.add(cls);
    const img = document.createElement('img');
    img.className = 'brand__logo';
    img.src = src;
    img.alt = '';
    mark.prepend(img);
  };
  const ifExists = (src, done) => {
    const probe = new Image();
    probe.addEventListener('load', done);
    probe.src = src;
  };

  ifExists(LOGO_MARK, () => $$('.nav .brand__mark').forEach(m => putLogo(m, LOGO_MARK, 'brand__mark--srdce')));
  ifExists(LOGO_FULL, () => {
    putLogo($('.foot .brand__mark'), LOGO_FULL, 'brand__mark--full');
    const name = $('.foot__brand p b');       // názov už nesie samotné logo
    if (name){
      const br = name.nextElementSibling;
      if (br && br.tagName === 'BR') br.remove();
      name.remove();
    }
  });

  /* ───────────────────────────────────────────
     12) TRANSPARENTNÝ ÚČET
  ─────────────────────────────────────────── */
  const ucetIban = $('#ucetIban');
  const ucetCopy = $('#ucetCopy');
  const ucetLink = $('#ucetLink');

  if (UCET.iban){
    const cislo = UCET.iban.trim();
    ucetIban.textContent = cislo;
    ucetIban.setAttribute('data-vyplnene', '');
    ucetCopy.dataset.copy = cislo.replace(/\s+/g, '');
  }
  if (UCET.link){
    ucetLink.href = UCET.link;
    ucetLink.hidden = false;
  }

  /* ───────────────────────────────────────────
     13) DROBNOSTI
  ─────────────────────────────────────────── */
  $('#year').textContent = new Date().getFullYear();
})();
