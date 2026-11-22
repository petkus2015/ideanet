/* ═══════════════════════════════════════════════
   IDEANET — animácie cez knižnicu Motion (motion.dev)
   Progresívne vylepšenie: ak sa Motion z CDN nenačíta,
   app.js použije jednoduché CSS objavovanie a stránka funguje ďalej.
   ═══════════════════════════════════════════════ */
(() => {
  'use strict';

  const M = window.Motion;
  if (!M) return;
  const { animate, inView, scroll, stagger } = M;

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.documentElement.classList.add('has-motion');

  /* Pri obmedzenom pohybe len všetko zobrazíme. */
  if (reduce) { $$('.reveal').forEach(el => el.classList.add('is-in')); return; }

  const ease = [0.22, 1, 0.36, 1];
  const spring = { type: 'spring', bounce: 0.22, duration: 0.9 };

  /* Po dobehnutí vrátime štýly CSS, aby fungovali hover efekty (.card:hover a pod.). */
  const settle = (els) => els.forEach(el => {
    el.classList.add('is-in');
    el.style.opacity = '';
    el.style.transform = '';
    el.style.filter = '';
  });

  /* 1) Hero — postupný nástup s rozostrením */
  const heroItems = $$('.hero .reveal');
  animate(heroItems,
    { opacity: [0, 1], y: [28, 0], filter: ['blur(10px)', 'blur(0px)'] },
    { duration: 0.8, ease, delay: stagger(0.09, { startDelay: 0.1 }) }
  ).finished.then(() => settle(heroItems));

  /* 2) Skupiny kariet — prvky naskakujú postupne s pružinou */
  const groups = ['.cards', '.steps', '.quotes', '.plans'].flatMap(s => $$(s));
  const grouped = new Set();
  groups.forEach(group => {
    const items = [...group.children].filter(el => el.classList.contains('reveal'));
    if (!items.length) return;
    items.forEach(el => grouped.add(el));
    inView(group, () => {
      animate(items,
        { opacity: [0, 1], y: [40, 0], scale: [0.96, 1] },
        { duration: 0.6, ease, delay: stagger(0.08), y: spring, scale: spring }
      ).finished.then(() => settle(items));
    }, { amount: 0.15 });
  });

  /* 3) Ostatné samostatné prvky (nadpisy sekcií, kontakt…) */
  $$('.reveal')
    .filter(el => !el.closest('.hero') && !grouped.has(el))
    .forEach(el => inView(el, () => {
      animate(el, { opacity: [0, 1], y: [24, 0] }, { duration: 0.7, ease })
        .finished.then(() => settle([el]));
    }, { amount: 0.25 }));

  /* 4) Indikátor priebehu skrolovania hore na stránke */
  const bar = $('.scroll-progress');
  if (bar) scroll(animate(bar, { scaleX: [0, 1] }, { ease: 'linear' }));

  /* 5) Paralaxa telefónu v hero pri odchode zo sekcie.
        Používame vlastnosť `translate`, aby sa nebila s CSS animáciou `transform`. */
  const hero = $('.hero'), visual = $('.hero__visual');
  if (hero && visual) {
    scroll((p) => {
      visual.style.translate = `0 ${(p * 90).toFixed(1)}px`;
      visual.style.opacity = String(1 - p * 0.6);
    }, { target: hero, offset: ['start start', 'end start'] });
  }
})();
