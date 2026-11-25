/* Sparring redesign: interakcie. Bez knižníc, všetok obsah je v HTML. */
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const small = () => innerWidth <= 720;

  /* Úvod: čierne pásy sa otvoria (raz za session) */
  const intro = $('.intro');
  let seen = false;
  try { seen = sessionStorage.getItem('sp-intro') === '1'; } catch (e) {}
  if (reduce || seen) intro.classList.add('is-done');
  else {
    try { sessionStorage.setItem('sp-intro', '1'); } catch (e) {}
    setTimeout(() => intro.classList.add('is-open'), 250);
    setTimeout(() => intro.classList.add('is-done'), 1200);
  }

  /* Postupné zobrazenie blokov */
  const io = new IntersectionObserver((entries) => entries.forEach((e) => {
    if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
  }), { rootMargin: '0px 0px -10% 0px' });
  $$('[data-reveal]').forEach((el) => io.observe(el));

  /* Navigácia */
  const nav = $('[data-nav]');
  const closeAll = () => $$('.nav__item.is-open').forEach((x) => { x.classList.remove('is-open'); $('[data-mega]', x).setAttribute('aria-expanded', 'false'); });
  $$('[data-mega]').forEach((b) => {
    const item = b.parentElement;
    b.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = !item.classList.contains('is-open');
      closeAll();
      item.classList.toggle('is-open', open);
      b.setAttribute('aria-expanded', String(open));
    });
  });
  document.addEventListener('click', closeAll);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });
  const burger = $('[data-burger]');
  burger.addEventListener('click', () => burger.setAttribute('aria-expanded', String(nav.classList.toggle('is-menu'))));

  /* Scroll: pevná navigácia, širokouhlé pásy v hero, filmový pás */
  const hero = $('[data-hero]'), heroImg = $('.hero__img img');
  const reel = $('[data-reel]'), track = $('[data-reel-track]');
  let dist = 0, ticking = false;

  const measure = () => {
    const on = !reduce && !small();
    reel.classList.toggle('is-pinned', on);
    if (!on) { track.style.transform = ''; dist = 0; return; }
    dist = Math.max(0, track.scrollWidth - innerWidth);
    reel.style.setProperty('--reel-h', `${innerHeight + dist}px`);
  };

  const update = () => {
    ticking = false;
    const y = scrollY;
    nav.classList.toggle('is-solid', y > 20);

    if (!reduce) {
      const h = hero.offsetHeight, p = Math.min(1, y / h);
      const base = small() ? 36 : 70;
      hero.style.setProperty('--bar', `${base * (1 - p)}px`);
      heroImg.style.setProperty('--parallax', `${y * 0.12}px`);
    }

    if (dist) {
      const p = Math.min(1, Math.max(0, -reel.getBoundingClientRect().top / dist));
      track.style.transform = `translate3d(${-p * dist}px,0,0)`;
    }
  };
  addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
  addEventListener('resize', () => { measure(); update(); });
  addEventListener('load', () => { measure(); update(); });
  measure(); update();

  /* „Are you …?“ */
  const rot = $('[data-rotator]');
  if (rot && !reduce) {
    const items = $$('span', rot);
    let i = 0;
    items[0].classList.add('is-on');
    setInterval(() => {
      const prev = items[i];
      prev.classList.replace('is-on', 'is-out');
      setTimeout(() => prev.classList.remove('is-out'), 600);
      i = (i + 1) % items.length;
      items[i].classList.add('is-on');
    }, 2400);
  }

  /* Referencie */
  const rv = $('[data-reviews]');
  const qs = $$('.quote', rv), count = $('[data-review-count]');
  let cur = 0;
  const go = (k) => {
    cur = (k + qs.length) % qs.length;
    qs.forEach((q, j) => q.classList.toggle('is-active', j === cur));
    count.textContent = `${cur + 1} / ${qs.length}`;
  };
  $('[data-review-prev]').addEventListener('click', () => go(cur - 1));
  $('[data-review-next]').addEventListener('click', () => go(cur + 1));

  /* Kontaktný formulár */
  const dlg = $('[data-contact]');
  $$('[data-open-contact]').forEach((b) => b.addEventListener('click', () => dlg.showModal()));
  dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
})();
