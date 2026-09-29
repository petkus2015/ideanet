/* ═══════════════════════════════════════════════════════════
   IDEANET AVADA BLOCKS — interakcie
   Vanilla JS, bez závislostí. Funguje preľubovoľný počet
   blokov na stránke naraz — nič nie je naviazané na pevné ID.
   ═══════════════════════════════════════════════════════════ */
(() => {
  'use strict';

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const REDUCED = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const FINE = matchMedia('(hover: hover) and (pointer: fine)');

  /* ─────────────────────────────────────────────────────────
     1) SPOLOČNÝ LIGHTBOX
     Vytvorí sa raz, lenivo, pri prvom použití — funguje pre
     všetky karusely na stránke.
  ───────────────────────────────────────────────────────── */
  let lb = null, lbItems = [], lbIndex = 0, lbLastFocus = null;

  function ensureLightbox() {
    if (lb) return lb;
    const el = document.createElement('div');
    el.className = 'ib-lightbox';
    el.hidden = true;
    el.innerHTML = `
      <button class="ib-lightbox__close" aria-label="Zavrieť (Esc)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
      <button class="ib-lightbox__nav ib-lightbox__nav--prev" aria-label="Predchádzajúce">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>
      </button>
      <figure class="ib-lightbox__stage"></figure>
      <button class="ib-lightbox__nav ib-lightbox__nav--next" aria-label="Ďalšie">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
      </button>`;
    document.body.appendChild(el);
    $('.ib-lightbox__close', el).addEventListener('click', closeLightbox);
    $('.ib-lightbox__nav--prev', el).addEventListener('click', () => stepLightbox(-1));
    $('.ib-lightbox__nav--next', el).addEventListener('click', () => stepLightbox(1));
    el.addEventListener('click', (e) => { if (e.target === el) closeLightbox(); });
    addEventListener('keydown', (e) => {
      if (el.hidden) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowRight') stepLightbox(1);
      if (e.key === 'ArrowLeft') stepLightbox(-1);
    });
    lb = el;
    return el;
  }

  function openLightbox(items, index) {
    if (!items || !items[index]) return;
    ensureLightbox();
    lbItems = items; lbIndex = index;
    lbLastFocus = document.activeElement;
    stopAllVideos(null);
    renderLightbox();
    lb.hidden = false;
    document.body.style.overflow = 'hidden';
    $('.ib-lightbox__close', lb).focus();
  }

  function renderLightbox() {
    const it = lbItems[lbIndex];
    const stage = $('.ib-lightbox__stage', lb);
    const isVideo = it.mode === 'video' && it.video;
    stage.innerHTML = isVideo
      ? `<video controls autoplay playsinline loop poster="${it.poster || ''}" aria-label="${it.title || ''}">
           <source src="${it.video}" type="video/mp4">
         </video>
         <figcaption class="ib-lightbox__cap"><b>${it.title || ''}</b>${it.desc || ''}</figcaption>`
      : `<img src="${it.poster || it.video || ''}" alt="${it.title || ''}">
         <figcaption class="ib-lightbox__cap"><b>${it.title || ''}</b>${it.desc || ''}</figcaption>`;
    const v = $('video', stage);
    if (v) v.play().catch(() => {});
  }

  function closeLightbox() {
    if (!lb) return;
    const v = $('video', lb);
    if (v) v.pause();
    lb.hidden = true;
    $('.ib-lightbox__stage', lb).innerHTML = '';
    document.body.style.overflow = '';
    lbLastFocus?.focus();
  }

  function stepLightbox(d) {
    const len = lbItems.length;
    lbIndex = (lbIndex + d + len) % len;
    renderLightbox();
  }

  /* jediné prehrávané video na celej stránke naraz */
  let currentVideo = null;
  function stopAllVideos(except) {
    $$('.ib-slide.is-playing').forEach((slide) => {
      const v = $('video', slide);
      if (!v || v === except) return;
      v.pause();
      slide.classList.remove('is-playing');
    });
    if (currentVideo && currentVideo !== except) currentVideo.pause();
    currentVideo = except || null;
  }

  /* ─────────────────────────────────────────────────────────
     2) KARUSEL — inicializuje sa pre každý .ib-carousel zvlášť
  ───────────────────────────────────────────────────────── */
  function initCarousel(root) {
    const rail = $('.ib-rail', root);
    if (!rail) return;
    const mode = root.dataset.mode || 'video';
    const isVideo = mode === 'video';
    const slides = () => $$('.ib-slide', rail);
    const track = $('.ib-progress', root);
    const thumb = track ? $('span', track) : null;
    const arrows = $$('.ib-arrow', root);

    /* dáta pre lightbox, čítané zo samotného DOM (server ich vykreslil) */
    const items = slides().map((slide) => ({
      mode,
      title: slide.dataset.title || '',
      desc: slide.dataset.desc || '',
      poster: slide.dataset.poster || '',
      video: slide.dataset.video || '',
    }));

    function play(i) {
      const slide = slides()[i], v = $('video', slide);
      if (!v) return;
      if (v.preload === 'none') { v.preload = 'metadata'; v.load(); }
      stopAllVideos(v);
      v.play().then(() => slide.classList.add('is-playing')).catch(() => {});
    }
    function pause(i) {
      const slide = slides()[i], v = $('video', slide);
      if (!v) return;
      v.pause();
      slide.classList.remove('is-playing');
    }
    function toggle(i) {
      const v = $('video', slides()[i]);
      if (!v) { openLightbox(items, i); return; }
      v.paused ? play(i) : pause(i);
    }

    rail.addEventListener('click', (e) => {
      if (rail.dataset.moved === '1') return;
      const slide = e.target.closest('.ib-slide');
      if (!slide) return;
      const i = slides().indexOf(slide);
      const act = e.target.closest('[data-act]')?.dataset.act;
      if (act === 'open') { openLightbox(items, i); return; }
      isVideo ? toggle(i) : openLightbox(items, i);
    });

    if (isVideo && FINE.matches && !REDUCED) {
      let hoverTimer = null;
      slides().forEach((slide, i) => {
        slide.addEventListener('mouseenter', () => {
          clearTimeout(hoverTimer);
          hoverTimer = setTimeout(() => play(i), 220);
        });
        slide.addEventListener('mouseleave', () => { clearTimeout(hoverTimer); pause(i); });
      });
    }

    const step = () => {
      const first = slides()[0];
      if (!first) return 0;
      const gap = parseFloat(getComputedStyle(rail).gap) || 18;
      return first.getBoundingClientRect().width + gap;
    };
    function scrollByCards(dir, count) {
      const perView = Math.max(1, Math.round(rail.clientWidth / (step() || 1) - 0.5));
      rail.scrollBy({ left: dir * step() * (count || perView), behavior: REDUCED ? 'auto' : 'smooth' });
    }
    arrows.forEach((btn) => btn.addEventListener('click', () => scrollByCards(+btn.dataset.dir)));

    rail.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { e.preventDefault(); scrollByCards(1, 1); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); scrollByCards(-1, 1); }
    });

    let dragging = false, startX = 0, startLeft = 0, moved = 0;
    rail.addEventListener('pointerdown', (e) => {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      dragging = true; moved = 0; startX = e.clientX; startLeft = rail.scrollLeft;
      rail.dataset.moved = '0';
      rail.classList.add('is-drag');
    });
    rail.addEventListener('pointermove', (e) => {
      if (!dragging) return;
      const dx = e.clientX - startX;
      if (Math.abs(dx) > 4) { moved = Math.abs(dx); rail.dataset.moved = '1'; rail.setPointerCapture?.(e.pointerId); }
      rail.scrollLeft = startLeft - dx;
    });
    const endDrag = (e) => {
      if (!dragging) return;
      dragging = false;
      rail.classList.remove('is-drag');
      if (e && e.pointerId != null) rail.releasePointerCapture?.(e.pointerId);
      if (moved > 4) {
        const s = step();
        rail.scrollTo({ left: Math.round(rail.scrollLeft / s) * s, behavior: REDUCED ? 'auto' : 'smooth' });
      }
      setTimeout(() => { rail.dataset.moved = '0'; }, 40);
    };
    rail.addEventListener('pointerup', endDrag);
    rail.addEventListener('pointercancel', endDrag);
    rail.addEventListener('pointerleave', endDrag);

    function sync() {
      const max = rail.scrollWidth - rail.clientWidth;
      const ratioSeen = rail.clientWidth / rail.scrollWidth;
      if (thumb && track) {
        const w = Math.max(track.clientWidth * ratioSeen, 26);
        thumb.style.width = w + 'px';
        thumb.style.transform = `translateX(${max > 0 ? (rail.scrollLeft / max) * (track.clientWidth - w) : 0}px)`;
      }
      arrows.forEach((btn) => {
        const dir = +btn.dataset.dir;
        btn.disabled = dir < 0 ? rail.scrollLeft < 8 : rail.scrollLeft > max - 8;
      });
    }
    rail.addEventListener('scroll', () => requestAnimationFrame(sync), { passive: true });
    addEventListener('resize', sync);
    sync();

    if (isVideo && 'IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => {
          if (en.intersectionRatio < 0.35) {
            const i = slides().indexOf(en.target);
            const v = $('video', en.target);
            if (v && !v.paused) pause(i);
          }
        });
      }, { root: rail, threshold: [0, 0.35, 0.8] });
      slides().forEach((s) => io.observe(s));
    }

    /* voliteľné automatické tiché prehratie prvej karty (hero) */
    if (isVideo && root.dataset.autoplay === '1' && !REDUCED && 'IntersectionObserver' in window) {
      new IntersectionObserver((entries) => {
        entries.forEach((en) => {
          if (en.isIntersecting) { if (!currentVideo) play(0); }
          else pause(0);
        });
      }, { threshold: 0.4 }).observe(slides()[0]);
    }
  }

  /* ─────────────────────────────────────────────────────────
     3) ODHALENIE PRI SCROLLI
  ───────────────────────────────────────────────────────── */
  function initReveal(scope) {
    const items = $$('.ib-reveal', scope);
    if (!items.length) return;
    if ('IntersectionObserver' in window && !REDUCED) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en, k) => {
          if (!en.isIntersecting) return;
          setTimeout(() => en.target.classList.add('in'), k * 60);
          io.unobserve(en.target);
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });
      items.forEach((el) => io.observe(el));
    } else {
      items.forEach((el) => el.classList.add('in'));
    }
  }

  /* ─────────────────────────────────────────────────────────
     4) KONTAKTNÝ FORMULÁR
  ───────────────────────────────────────────────────────── */
  function initContact(scope) {
    const form = $('.ib-form[data-ib-contact]', scope);
    if (!form) return;
    const status = $('.ib-form__status', form);
    const mailto = form.dataset.mailto || '';

    const setErr = (input, msg) => {
      input.closest('.ib-field').classList.toggle('has-err', !!msg);
      const el = $(`.ib-err[data-for="${input.id}"]`, form);
      if (el) el.textContent = msg || '';
      return !msg;
    };

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = $('[name="ib_name"]', form);
      const email = $('[name="ib_email"]', form);
      const msg = $('[name="ib_msg"]', form);
      const ok = [
        setErr(name, name && name.value.trim().length < 2 ? 'Doplňte meno alebo názov firmy.' : ''),
        setErr(email, email && /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(email.value.trim()) ? '' : 'Skontrolujte tvar e-mailu.'),
        setErr(msg, msg && msg.value.trim().length < 10 ? 'Napíšte aspoň pár viet o projekte.' : ''),
      ].every(Boolean);
      if (!ok) { if (status) status.textContent = 'Formulár ešte nie je kompletný.'; return; }

      const services = $$('input[name="ib_sluzba"]:checked', form).map((c) => c.value).join(', ') || 'neurčené';
      const body = encodeURIComponent(
        `Meno a firma: ${name ? name.value.trim() : ''}\nE-mail: ${email ? email.value.trim() : ''}\nZáujem o: ${services}\n\n${msg ? msg.value.trim() : ''}`
      );
      const subject = encodeURIComponent('Dopyt z webu — ' + (name ? name.value.trim() : ''));
      if (mailto) location.href = `mailto:${mailto}?subject=${subject}&body=${body}`;
      if (status) status.textContent = 'Otváram váš e-mailový klient…';
      form.reset();
    });

    $$('[name="ib_name"],[name="ib_email"],[name="ib_msg"]', form).forEach((el) => {
      el.addEventListener('input', () => { if (el.closest('.ib-field').classList.contains('has-err')) setErr(el, ''); });
    });
  }

  /* odkazy s data-ib-preselect zaškrtnú danú službu v najbližšom formulári na stránke */
  function initPreselect() {
    $$('[data-ib-preselect]').forEach((a) => {
      a.addEventListener('click', () => {
        const val = a.dataset.ibPreselect;
        const chip = $$('input[name="ib_sluzba"]').find((i) => i.value === val);
        if (chip) chip.checked = true;
      });
    });
  }

  /* ─────────────────────────────────────────────────────────
     5) HERO — svetlo za kurzorom, náklon kariet, magnet tlačidlo
  ───────────────────────────────────────────────────────── */
  function initHeroFx(hero) {
    if (REDUCED || !FINE.matches) return;
    let raf = 0;
    hero.addEventListener('pointermove', (e) => {
      if (e.pointerType !== 'mouse') return;
      const r = hero.getBoundingClientRect();
      const mx = ((e.clientX - r.left) / r.width) * 100;
      const my = ((e.clientY - r.top) / r.height) * 100;
      hero.classList.add('is-live');
      if (raf) return;
      raf = requestAnimationFrame(() => {
        raf = 0;
        hero.style.setProperty('--mx', mx.toFixed(2) + '%');
        hero.style.setProperty('--my', my.toFixed(2) + '%');
      });
    });
    hero.addEventListener('pointerleave', () => hero.classList.remove('is-live'));

    const magnet = $('.ib-hero__cta .ib-btn:not(.ib-btn--ghost)', hero);
    if (magnet) {
      const PULL = 7;
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

  /* ─────────────────────────────────────────────────────────
     INICIALIZÁCIA
  ───────────────────────────────────────────────────────── */
  function boot() {
    $$('.ib-scope').forEach((scope) => {
      $$('.ib-carousel', scope).forEach(initCarousel);
      $$('.ib-hero', scope).forEach(initHeroFx);
      initReveal(scope);
      initContact(scope);
    });
    initPreselect();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  /* keď Avada Builder alebo AJAX navigácia dosadí nový obsah,
     dá sa zavolať znova bez straty existujúcich inicializácií */
  window.ideanetBlocksInit = boot;
})();
