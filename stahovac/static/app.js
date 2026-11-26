(() => {
  'use strict';

  const $ = (id) => document.getElementById(id);
  const el = {
    url: $('url'), clear: $('clear'), paste: $('paste'), fetch: $('fetch'),
    notice: $('notice'), loading: $('loading'), result: $('result'),
    thumb: $('thumb'), badge: $('platformBadge'), duration: $('duration'),
    title: $('title'), uploader: $('uploader'), dims: $('dims'), options: $('options'),
    progress: $('progress'), pLabel: $('progressLabel'), pPct: $('progressPct'),
    pBar: $('progressBar'), pSub: $('progressSub'),
    download: $('download'), cancel: $('cancel'),
    historyWrap: $('historyWrap'), history: $('history'), clearHistory: $('clearHistory'),
    toast: $('toast'),
  };

  // Python server: /api/…  ·  PHP hosting (FTP): index.html nastaví window.STAHOVAC_API = 'api.php'
  const API_BASE = window.STAHOVAC_API || null;
  const apiUrl = (path) => (API_BASE ? `${API_BASE}?r=${encodeURIComponent(path)}` : `/api/${path}`);

  const PLATFORM_NAMES = { tiktok: 'TikTok', instagram: 'Instagram', youtube: 'YouTube', facebook: 'Facebook', other: 'Web' };
  const PATTERNS = {
    tiktok: /(^|\.)tiktok\.com$/i,
    instagram: /(^|\.)(instagram\.com|instagr\.am)$/i,
    youtube: /(^|\.)(youtube\.com|youtu\.be|youtube-nocookie\.com)$/i,
    facebook: /(^|\.)(facebook\.com|fb\.watch|fb\.com)$/i,
  };

  let current = null;     // údaje o nájdenom videu
  let selected = null;    // zvolená možnosť
  let job = null;         // { id, timer }

  // ------------------------------------------------------------------ pomocné
  const fmtBytes = (n) => {
    if (!n) return '';
    const u = ['B', 'kB', 'MB', 'GB'];
    let i = 0;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return `${n.toFixed(n < 10 && i > 1 ? 1 : 0)} ${u[i]}`;
  };
  const fmtDur = (s) => {
    if (!s && s !== 0) return '';
    s = Math.round(s);
    const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), r = s % 60;
    return (h ? `${h}:${String(m).padStart(2, '0')}` : m) + ':' + String(r).padStart(2, '0');
  };
  const extractUrl = (text) => {
    const m = (text || '').match(/https?:\/\/[^\s<>"']+/i);
    return m ? m[0] : (text || '').trim();
  };
  const platformOf = (text) => {
    try {
      const host = new URL(extractUrl(text)).hostname;
      return Object.keys(PATTERNS).find((k) => PATTERNS[k].test(host)) || null;
    } catch { return null; }
  };
  const store = {
    get(k, d) { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch { /* súkromné okno */ } },
  };
  let toastTimer;
  const toast = (msg) => {
    el.toast.textContent = msg;
    el.toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.toast.classList.remove('show'), 2600);
  };
  const vibrate = (ms) => { try { navigator.vibrate && navigator.vibrate(ms); } catch { /* */ } };

  async function api(path, opts = {}) {
    const res = await fetch(path, {
      ...opts,
      headers: { 'Content-Type': 'application/json', ...(opts.headers || {}) },
    });
    let data = {};
    try { data = await res.json(); } catch { /* */ }
    if (!res.ok) throw new Error(data.error || 'Server je nedostupný. Skúste to znova.');
    return data;
  }

  // ------------------------------------------------------------------ vstup
  function onInput() {
    const p = platformOf(el.url.value);
    document.querySelectorAll('.chip').forEach((c) => c.classList.toggle('on', c.dataset.p === p));
    el.clear.hidden = !el.url.value;
  }
  el.url.addEventListener('input', onInput);
  el.url.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); findVideo(); } });
  el.clear.addEventListener('click', () => { el.url.value = ''; onInput(); el.url.focus(); });

  el.paste.addEventListener('click', async () => {
    try {
      const text = await navigator.clipboard.readText();
      if (!text) { toast('Schránka je prázdna'); return; }
      el.url.value = extractUrl(text);
      onInput();
      findVideo();
    } catch {
      el.url.focus();
      toast('Podržte prst v poli a zvoľte Vložiť');
    }
  });
  el.fetch.addEventListener('click', findVideo);

  function showError(msg) {
    el.notice.textContent = msg;
    el.notice.hidden = false;
    vibrate([30, 40, 30]);
  }

  // ------------------------------------------------------------------ hľadanie videa
  async function findVideo() {
    const url = extractUrl(el.url.value);
    el.url.value = url;
    onInput();
    el.notice.hidden = true;
    if (!url) { showError('Vložte odkaz na video.'); return; }
    if (!platformOf(url)) { showError('Podporované sú odkazy z TikToku, Instagramu, YouTube a Facebooku.'); return; }

    stopJob(true);
    el.result.hidden = true;
    el.loading.hidden = false;
    el.fetch.disabled = true;
    el.url.blur();
    try {
      current = await api(apiUrl('info'), { method: 'POST', body: JSON.stringify({ url }) });
      renderResult();
      addHistory(current);
    } catch (err) {
      showError(err.message);
    } finally {
      el.loading.hidden = true;
      el.fetch.disabled = false;
    }
  }

  function renderResult() {
    const v = current;
    const media = el.thumb.parentElement;
    media.classList.toggle('noimg', !v.thumbnail);
    el.thumb.onerror = () => media.classList.add('noimg');
    if (v.thumbnail) el.thumb.src = v.thumbnail; else el.thumb.removeAttribute('src');
    el.badge.textContent = PLATFORM_NAMES[v.platform] || '';
    el.duration.textContent = fmtDur(v.duration);
    el.duration.hidden = !v.duration;
    el.title.textContent = v.title;
    el.uploader.textContent = v.uploader ? `@${String(v.uploader).replace(/^@/, '')}` : '';
    el.uploader.hidden = !v.uploader;
    el.dims.textContent = v.width && v.height
      ? `Originál: ${v.width} × ${v.height}${v.width < v.height ? ' (na výšku)' : ''}` : '';

    el.options.innerHTML = '';
    v.options.forEach((o, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'option';
      b.setAttribute('role', 'radio');
      b.setAttribute('aria-checked', i === 0 ? 'true' : 'false');
      const res = o.width && o.height ? `${o.width}×${o.height}` : '';
      const sub = [res, o.fps && o.fps > 30 ? `${o.fps} fps` : '', o.codec, o.hint].filter(Boolean).join(' · ');
      b.innerHTML = `<span class="radio"></span>
        <span class="txt"><span class="name"></span><span class="sub"></span></span>
        <span class="size"></span>`;
      b.querySelector('.name').textContent = o.label;
      if (o.id === 'original') {
        const t = document.createElement('span'); t.className = 'tag'; t.textContent = 'NAJLEPŠIA';
        b.querySelector('.name').appendChild(t);
      }
      b.querySelector('.sub').textContent = sub;
      b.querySelector('.size').textContent = o.size ? `~${fmtBytes(o.size)}` : '';
      b.addEventListener('click', () => {
        if (job) return;
        el.options.querySelectorAll('.option').forEach((x) => x.setAttribute('aria-checked', 'false'));
        b.setAttribute('aria-checked', 'true');
        selected = o;
        resetDownloadButton();
      });
      el.options.appendChild(b);
    });
    selected = v.options[0];
    resetDownloadButton();
    el.progress.hidden = true;
    el.result.hidden = false;
    requestAnimationFrame(() => el.result.scrollIntoView({ behavior: 'smooth', block: 'start' }));
  }

  // ------------------------------------------------------------------ sťahovanie
  function resetDownloadButton() {
    el.download.disabled = false;
    el.download.classList.remove('done');
    el.download.querySelector('span').textContent = 'Stiahnuť';
    el.download.onclick = startDownload;
    el.cancel.hidden = true;
  }

  async function startDownload() {
    if (!current || !selected || job) return;
    el.notice.hidden = true;
    el.download.disabled = true;
    el.download.querySelector('span').textContent = 'Sťahujem…';
    el.progress.hidden = false;
    setProgress('Pripravujem…', null, '');
    try {
      const { id } = await api(apiUrl('download'), {
        method: 'POST',
        body: JSON.stringify({ url: current.url, spec: selected.spec, audio: !!selected.audio }),
      });
      job = { id, timer: null };
      el.cancel.hidden = false;
      poll();
    } catch (err) {
      el.progress.hidden = true;
      resetDownloadButton();
      showError(err.message);
    }
  }

  function setProgress(label, pct, sub) {
    el.pLabel.textContent = label;
    const bar = el.pBar.parentElement;
    if (pct == null) {
      bar.classList.add('indeterminate');
      el.pPct.textContent = '';
    } else {
      bar.classList.remove('indeterminate');
      el.pBar.style.width = `${pct}%`;
      el.pPct.textContent = `${Math.floor(pct)} %`;
    }
    el.pSub.textContent = sub || '';
  }

  async function poll() {
    if (!job) return;
    const id = job.id;
    let s;
    try {
      s = await api(apiUrl(`jobs/${id}`));
    } catch {
      if (job && job.id === id) job.timer = setTimeout(poll, 1500);
      return;
    }
    if (!job || job.id !== id) return;

    if (s.status === 'queued') {
      setProgress('V poradí…', null, '');
    } else if (s.status === 'downloading') {
      const part = selected.audio ? 'zvuk' : (s.stream > 1 ? 'zvukovú stopu' : 'video');
      const sub = [
        s.total ? `${fmtBytes(s.downloaded)} z ${fmtBytes(s.total)}` : fmtBytes(s.downloaded),
        s.speed ? `${fmtBytes(s.speed)}/s` : '',
        s.eta ? `zostáva ${fmtDur(s.eta)}` : '',
      ].filter(Boolean).join(' · ');
      setProgress(`Sťahujem ${part}…`, s.percent, sub);
    } else if (s.status === 'processing') {
      setProgress('Spájam obraz a zvuk…', null, 'bez prekódovania, kvalita zostáva pôvodná');
    } else if (s.status === 'done') {
      finishJob(id, s);
      return;
    } else if (s.status === 'error' || s.status === 'cancelled') {
      stopJob();
      el.progress.hidden = true;
      if (s.status === 'error') showError(s.error || 'Sťahovanie zlyhalo.');
      return;
    }
    job.timer = setTimeout(poll, 700);
  }

  function finishJob(id, s) {
    job = null;
    el.cancel.hidden = true;
    setProgress('Hotovo', 100, `${s.filename} · ${fmtBytes(s.size)}`);
    const href = apiUrl(`jobs/${id}/file`);
    triggerSave(href, s.filename);
    vibrate(40);
    toast('Video sa ukladá do zariadenia');
    el.download.disabled = false;
    el.download.classList.add('done');
    el.download.querySelector('span').textContent = 'Uložiť znova';
    el.download.onclick = () => triggerSave(href, s.filename);
  }

  function triggerSave(href, name) {
    const a = document.createElement('a');
    a.href = href;
    a.download = name || '';
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  function stopJob(silent) {
    if (job) {
      clearTimeout(job.timer);
      if (!silent) { /* */ }
      fetch(apiUrl(`jobs/${job.id}/cancel`), { method: 'POST' }).catch(() => {});
      job = null;
    }
    resetDownloadButton();
  }
  el.cancel.addEventListener('click', () => {
    stopJob();
    el.progress.hidden = true;
    toast('Sťahovanie zrušené');
  });

  // ------------------------------------------------------------------ história
  function addHistory(v) {
    const list = store.get('history', []).filter((h) => h.url !== v.url);
    list.unshift({ url: v.url, title: v.title, thumb: v.thumbnail, platform: v.platform, at: Date.now() });
    store.set('history', list.slice(0, 12));
    renderHistory();
  }
  function renderHistory() {
    const list = store.get('history', []);
    el.historyWrap.hidden = !list.length;
    el.history.innerHTML = '';
    list.forEach((h) => {
      const li = document.createElement('li');
      const b = document.createElement('button');
      b.type = 'button';
      const img = document.createElement(h.thumb ? 'img' : 'span');
      if (h.thumb) {
        img.src = h.thumb; img.alt = ''; img.referrerPolicy = 'no-referrer'; img.loading = 'lazy';
        img.onerror = () => { const ph = document.createElement('span'); ph.className = 'ph'; img.replaceWith(ph); };
      } else img.className = 'ph';
      const txt = document.createElement('span');
      txt.className = 'h-txt';
      txt.innerHTML = '<div class="h-title"></div><div class="h-sub"></div>';
      txt.querySelector('.h-title').textContent = h.title;
      txt.querySelector('.h-sub').textContent = `${PLATFORM_NAMES[h.platform] || ''} · ${new Date(h.at).toLocaleDateString('sk-SK')}`;
      b.append(img, txt);
      b.addEventListener('click', () => { el.url.value = h.url; onInput(); findVideo(); });
      li.appendChild(b);
      el.history.appendChild(li);
    });
  }
  el.clearHistory.addEventListener('click', () => { store.set('history', []); renderHistory(); });

  // ------------------------------------------------------------------ štart
  // Podpora „Zdieľať do aplikácie“ (PWA share target) a ?url= v adrese
  const params = new URLSearchParams(location.search);
  const shared = params.get('url') || params.get('text') || params.get('title');
  renderHistory();
  if (shared) {
    el.url.value = extractUrl(shared);
    onInput();
    history.replaceState(null, '', location.pathname);
    findVideo();
  }
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(() => {});
  }
})();
