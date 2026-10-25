/* Meškanie vlakov SK — prehľad z dát mapa.zsr.sk (vlaky/data/trains.json),
   ktoré každých 5 minút obnovuje scripts/fetch_zsr.py. */
(() => {
  'use strict';

  const DATA_URL = 'data/trains.json';
  const HISTORY_URL = 'data/history.json';
  const REFRESH_MS = 5 * 60 * 1000;
  const STALE_MS = 15 * 60 * 1000;
  const PAGE = 60;
  const FORCE_DEMO = new URLSearchParams(location.search).has('demo');

  const CATS = [
    { id: 0, label: 'Načas', range: '0–5 min', max: 5 },
    { id: 1, label: 'Mierne', range: '6–15 min', max: 15 },
    { id: 2, label: 'Stredné', range: '16–30 min', max: 30 },
    { id: 3, label: 'Veľké', range: '31–60 min', max: 60 },
    { id: 4, label: 'Extrémne', range: 'nad 60 min', max: Infinity },
  ];
  const catOf = d => CATS.find(c => d <= c.max).id;
  const cssVar = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmtMin = m => (m >= 60 ? `${Math.floor(m / 60)} h ${m % 60} min` : `${m} min`);
  const fmtTime = d => d.toLocaleTimeString('sk-SK', { hour: '2-digit', minute: '2-digit' });
  const fmtNum = (n, dp = 0) => n.toLocaleString('sk-SK', { minimumFractionDigits: dp, maximumFractionDigits: dp });
  const trainLabel = t => [t.type, t.number].filter(Boolean).join(' ') || t.id || 'Vlak';

  const state = {
    trains: [],
    history: [],
    prev: null,          // súhrn z predošlej aktualizácie (pre delty)
    updated: null,
    mode: 'loading',     // live | stale | demo | error
    cat: null,           // aktívna kategória
    q: '',
    carrier: '',
    sort: 'delay',
    shown: PAGE,
    active: null,        // id vybraného vlaku
    nextAt: Date.now() + REFRESH_MS,
  };

  /* ---------------- dáta ---------------- */

  async function getJSON(url) {
    const res = await fetch(`${url}?t=${Date.now()}`, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  }

  function summarize(trains) {
    const n = trains.length;
    const sum = trains.reduce((a, t) => a + t.delay, 0);
    return {
      n,
      avg: n ? sum / n : 0,
      onTime: n ? (100 * trains.filter(t => t.delay <= 5).length) / n : 0,
      over15: trains.filter(t => t.delay > 15).length,
    };
  }

  async function load() {
    const btn = $('refresh');
    btn.classList.add('is-spinning');
    const before = state.trains.length ? summarize(state.trains) : null;
    try {
      if (FORCE_DEMO) throw new Error('demo');
      const data = await getJSON(DATA_URL);
      if (!Array.isArray(data.trains)) throw new Error('neplatný formát');
      state.trains = data.trains.map(t => ({ ...t, delay: Math.max(0, Number(t.delay) || 0) }));
      state.updated = new Date(data.updated);
      state.mode = Date.now() - state.updated > STALE_MS ? 'stale' : 'live';
      try { state.history = await getJSON(HISTORY_URL); } catch { state.history = []; }
    } catch (err) {
      if (state.trains.length && state.mode !== 'demo') {
        state.mode = 'error';      // necháme posledné platné dáta
      } else {
        state.trains = demoTrains();
        state.history = demoHistory();
        state.updated = new Date();
        state.mode = 'demo';
      }
    }
    // delta voči predošlému stavu: história (n-2) alebo predošlé načítanie
    const h = state.history;
    state.prev = h.length > 1 ? h[h.length - 2] : before;
    state.nextAt = Date.now() + REFRESH_MS;
    btn.classList.remove('is-spinning');
    render();
  }

  /* ---------------- render ---------------- */

  function render() {
    renderStatus();
    renderKpis();
    renderDist();
    renderTrend();
    renderCarriers();
    renderList();
    renderMap();
  }

  function renderStatus() {
    const pill = $('pill');
    const labels = { live: 'Živé dáta', stale: 'Oneskorené dáta', demo: 'Ukážkové dáta', error: 'Výpadok spojenia', loading: 'Načítavam…' };
    pill.dataset.state = state.mode;
    $('pillText').textContent = labels[state.mode];
    $('updated').textContent = state.updated ? `Aktualizované ${fmtTime(state.updated)}` : '—';

    const n = $('notice');
    const msg = {
      demo: 'Zobrazujú sa <b>ukážkové dáta</b> — súbor <code>data/trains.json</code> zatiaľ neexistuje. Spustite <code>python3 scripts/fetch_zsr.py --loop</code> alebo GitHub Action „Meškanie vlakov“.',
      stale: `Posledné dáta sú z ${state.updated && fmtTime(state.updated)} — zdroj ŽSR sa pravdepodobne neaktualizuje.`,
      error: 'Nepodarilo sa načítať nové dáta. Zobrazujem posledný platný stav, ďalší pokus o 5 minút.',
    }[state.mode];
    n.hidden = !msg;
    if (msg) { n.innerHTML = msg; n.dataset.kind = state.mode; }
  }

  function delta(cur, prev, { unit = '', dp = 0, goodWhenUp = false } = {}) {
    if (prev == null) return '';
    const d = cur - prev;
    if (Math.abs(d) < (dp ? 0.05 : 0.5)) return '<span class="delta">bez zmeny</span>';
    const good = goodWhenUp ? d > 0 : d < 0;
    return `<span class="delta ${good ? 'good' : 'bad'}">${d > 0 ? '▲' : '▼'} ${fmtNum(Math.abs(d), dp)}${unit}</span> za 5 min`;
  }

  function renderKpis() {
    const s = summarize(state.trains);
    const p = state.prev;
    const delayed = state.trains.filter(t => t.delay > 5).length;
    $('kTotal').textContent = fmtNum(s.n);
    $('kTotalHint').innerHTML = `${fmtNum(delayed)} mešká viac ako 5 min`;
    $('kOnTime').textContent = `${fmtNum(s.onTime)} %`;
    $('kOnTimeHint').innerHTML = delta(s.onTime, p?.onTime, { unit: ' p. b.', goodWhenUp: true }) || '&nbsp;';
    $('kAvg').textContent = `${fmtNum(s.avg, 1)} min`;
    $('kAvgHint').innerHTML = delta(s.avg, p?.avg, { unit: ' min', dp: 1 }) || '&nbsp;';
    $('kOver').textContent = fmtNum(s.over15);
    $('kOverHint').innerHTML = delta(s.over15, p?.over15) || '&nbsp;';
    const top = state.trains.reduce((a, t) => (!a || t.delay > a.delay ? t : a), null);
    $('kMax').textContent = top ? fmtMin(top.delay) : '—';
    $('kMaxHint').textContent = top ? `${trainLabel(top)}${top.name ? ' ' + top.name : ''}` : '';
  }

  function counts() {
    const c = CATS.map(() => 0);
    state.trains.forEach(t => c[catOf(t.delay)]++);
    return c;
  }

  function renderDist() {
    const c = counts();
    const total = state.trains.length || 1;
    $('distBar').setAttribute('aria-label', CATS.map((k, i) => `${k.label} ${c[i]}`).join(', '));
    $('distBar').innerHTML = CATS.map((k, i) => {
      const pct = (100 * c[i]) / total;
      return c[i] ? `<button type="button" class="seg ${state.cat === i ? 'is-on' : ''}" data-cat="${i}"
        style="flex-grow:${c[i]};background:var(--c${i})" title="${k.label} (${k.range}): ${c[i]}">${pct >= 7 ? Math.round(pct) + ' %' : ''}</button>` : '';
    }).join('');
    $('distLegend').innerHTML = CATS.map((k, i) =>
      `<span class="leg"><i style="background:var(--c${i})"></i>${k.label} <span class="muted">${k.range}</span> <b>${c[i]}</b></span>`).join('');
    document.querySelector('.dist').classList.toggle('has-filter', state.cat !== null);

    $('chips').innerHTML = `<button type="button" class="chip" data-cat="" aria-pressed="${state.cat === null}">Všetky <span>${state.trains.length}</span></button>` +
      CATS.map((k, i) => `<button type="button" class="chip" data-cat="${i}" aria-pressed="${state.cat === i}"><i style="background:var(--c${i})"></i>${k.range} <span>${c[i]}</span></button>`).join('');
  }

  function renderTrend() {
    const h = state.history.filter(x => typeof x.onTime === 'number');
    $('trend').hidden = h.length < 3;
    if (h.length < 3) return;
    const W = 600, H = 80, pad = 4;
    const vals = h.map(x => x.onTime);
    const lo = Math.max(0, Math.min(...vals) - 5), hi = Math.min(100, Math.max(...vals) + 5);
    const x = i => (i / (h.length - 1)) * W;
    const y = v => H - pad - ((v - lo) / (hi - lo || 1)) * (H - pad * 2);
    const line = vals.map((v, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join('');
    const col = cssVar('--accent');
    $('trendSvg').innerHTML = `
      <path d="${line}L${W},${H}L0,${H}Z" fill="${col}" opacity=".12"/>
      <path d="${line}" stroke="${col}" stroke-width="2" fill="none" vector-effect="non-scaling-stroke"/>`;
    const first = new Date(h[0].t);
    $('trendRange').textContent = `od ${first.toLocaleString('sk-SK', { weekday: 'short', hour: '2-digit', minute: '2-digit' })} · rozsah ${fmtNum(Math.min(...vals))}–${fmtNum(Math.max(...vals))} %`;
  }

  function renderCarriers() {
    const sel = $('carrier');
    const list = [...new Set(state.trains.map(t => t.carrier).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'sk'));
    const cur = state.carrier;
    sel.innerHTML = '<option value="">Všetci dopravcovia</option>' + list.map(c => `<option${c === cur ? ' selected' : ''}>${esc(c)}</option>`).join('');
    if (cur && !list.includes(cur)) state.carrier = '';
  }

  const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

  function filtered() {
    const q = norm(state.q);
    const out = state.trains.filter(t =>
      (state.cat === null || catOf(t.delay) === state.cat) &&
      (!state.carrier || t.carrier === state.carrier) &&
      (!q || norm(`${t.type} ${t.number} ${t.type}${t.number} ${t.name} ${t.route} ${t.info} ${t.next}`).includes(q)));
    const byNum = (a, b) => (parseInt(a.number, 10) || 0) - (parseInt(b.number, 10) || 0);
    const sorters = {
      delay: (a, b) => b.delay - a.delay || byNum(a, b),
      number: byNum,
      type: (a, b) => a.type.localeCompare(b.type, 'sk') || byNum(a, b),
    };
    return out.sort(sorters[state.sort]);
  }

  function renderList() {
    const items = filtered();
    $('count').textContent = items.length === state.trains.length
      ? `${items.length} vlakov`
      : `${items.length} z ${state.trains.length} vlakov`;
    const shown = items.slice(0, state.shown);
    $('trains').innerHTML = shown.length ? shown.map(t => {
      const c = catOf(t.delay);
      const id = esc(t.id);
      const extra = [
        t.info && `<p>${esc(t.info)}</p>`,
        t.disruption && `<p class="tag-warn">Narušenie: ${esc(t.disruption)}</p>`,
        t.nad?.length && `<p class="tag-warn">Náhradná autobusová doprava: ${esc(t.nad.join(' – '))}</p>`,
        t.note && `<p>${esc(t.note)}</p>`,
        t.delayText && `<p>ŽSR: ${esc(t.delayText)}</p>`,
      ].filter(Boolean).join('');
      return `<li class="train ${state.active === t.id ? 'is-active' : ''}" data-id="${id}" tabindex="0" role="button"
          aria-expanded="${state.active === t.id}" aria-label="${esc(trainLabel(t))}, meškanie ${fmtMin(t.delay)}">
        <span class="badge b${c}">${t.delay <= 0 ? '0' : '+' + (t.delay >= 100 ? Math.round(t.delay) : t.delay)}<small>min</small></span>
        <div style="min-width:0">
          <div class="train__top">
            <span class="train__id">${esc(trainLabel(t))}</span>
            ${t.name ? `<span class="train__name">${esc(t.name)}</span>` : ''}
            ${t.carrier ? `<span class="train__carrier">${esc(t.carrier)}</span>` : ''}
          </div>
          ${t.route ? `<div class="train__route">${esc(t.route)}</div>` : ''}
          <div class="train__pos">${t.info ? esc(t.info) : 'Poloha neznáma'}${t.next ? ` → ${esc(t.next)}` : ''}</div>
        </div>
        ${extra ? `<div class="train__extra">${extra}</div>` : ''}
      </li>`;
    }).join('') : '<li class="empty">Žiadny vlak nezodpovedá filtru.</li>';
    $('more').hidden = items.length <= state.shown;
    $('more').textContent = `Zobraziť ďalšie (${items.length - state.shown})`;
  }

  /* ---------------- mapa ---------------- */

  let map, layer, tiles;
  const markers = new Map();

  function tileUrl() {
    const dark = matchMedia('(prefers-color-scheme: dark)').matches;
    return `https://{s}.basemaps.cartocdn.com/${dark ? 'dark_all' : 'light_all'}/{z}/{x}/{y}{r}.png`;
  }

  function initMap() {
    if (map || !window.L) return;
    map = L.map('map', { zoomControl: true, attributionControl: true, preferCanvas: true })
      .setView([48.75, 19.6], 7);
    tiles = L.tileLayer(tileUrl(), {
      maxZoom: 15, subdomains: 'abcd',
      attribution: '&copy; OpenStreetMap &copy; CARTO',
    }).addTo(map);
    layer = L.layerGroup().addTo(map);
    matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => { tiles.setUrl(tileUrl()); renderMap(); });
  }

  function renderMap() {
    initMap();
    if (!map) return;
    layer.clearLayers();
    markers.clear();
    const colors = CATS.map((_, i) => cssVar(`--c${i}`));
    const stroke = cssVar('--surface');
    const items = filtered().filter(t => t.lat != null && t.lon != null);
    // menšie meškania kreslíme prvé, aby veľké boli navrchu
    items.slice().sort((a, b) => a.delay - b.delay).forEach(t => {
      const c = catOf(t.delay);
      const m = L.circleMarker([t.lat, t.lon], {
        radius: 5 + c * 1.5, color: stroke, weight: 1.5, fillColor: colors[c], fillOpacity: .9,
      }).bindPopup(`<b>${esc(trainLabel(t))}${t.name ? ' ' + esc(t.name) : ''}</b><br>
        ${t.route ? esc(t.route) + '<br>' : ''}
        <span style="color:${colors[c]};font-weight:700">${t.delay <= 0 ? 'Načas' : '+' + fmtMin(t.delay)}</span>
        ${t.info ? '<br>' + esc(t.info) : ''}${t.next ? '<br>Ďalej: ' + esc(t.next) : ''}`);
      m.on('click', () => select(t.id, false));
      m.addTo(layer);
      markers.set(t.id, m);
    });
  }

  function select(id, fromList) {
    state.active = state.active === id && fromList ? null : id;
    renderList();
    const m = markers.get(id);
    if (m && state.active === id) {
      if (fromList) {
        map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 9), { duration: .6 });
        m.openPopup();
      } else {
        // klik na mapu: dostaň vlak do zoznamu (aj za stránkovaním)
        const idx = filtered().findIndex(t => t.id === id);
        if (idx >= state.shown) { state.shown = idx + 1; renderList(); }
        document.querySelector(`.train[data-id="${CSS.escape(id)}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    }
  }

  /* ---------------- interakcie ---------------- */

  function setCat(v) {
    state.cat = v === '' || v === null || state.cat === Number(v) ? null : Number(v);
    state.shown = PAGE;
    renderDist(); renderList(); renderMap();
  }

  function bind() {
    $('distBar').addEventListener('click', e => { const b = e.target.closest('[data-cat]'); if (b) setCat(b.dataset.cat); });
    $('chips').addEventListener('click', e => { const b = e.target.closest('[data-cat]'); if (b) setCat(b.dataset.cat); });
    let tq;
    $('q').addEventListener('input', e => {
      clearTimeout(tq);
      tq = setTimeout(() => { state.q = e.target.value; state.shown = PAGE; renderList(); renderMap(); }, 150);
    });
    $('carrier').addEventListener('change', e => { state.carrier = e.target.value; state.shown = PAGE; renderList(); renderMap(); });
    $('sort').addEventListener('change', e => { state.sort = e.target.value; renderList(); });
    $('more').addEventListener('click', () => { state.shown += PAGE; renderList(); });
    $('refresh').addEventListener('click', load);
    $('trains').addEventListener('click', e => { const li = e.target.closest('.train'); if (li) select(li.dataset.id, true); });
    $('trains').addEventListener('keydown', e => {
      if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('train')) { e.preventDefault(); select(e.target.dataset.id, true); }
    });
    // po návrate na kartu obnov hneď, ak sú dáta staršie ako interval
    document.addEventListener('visibilitychange', () => { if (!document.hidden && Date.now() >= state.nextAt) load(); });
  }

  function tick() {
    const left = Math.max(0, state.nextAt - Date.now());
    const s = Math.ceil(left / 1000);
    $('countdown').textContent = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    $('progress').style.width = `${100 - (100 * left) / REFRESH_MS}%`;
    if (left === 0 && !document.hidden) { state.nextAt = Date.now() + REFRESH_MS; load(); }
  }

  /* ---------------- ukážkové dáta (keď chýba trains.json) ---------------- */

  const ST = {
    BA: ['Bratislava hl.st.', 48.158, 17.106], TT: ['Trnava', 48.371, 17.585], LE: ['Leopoldov', 48.447, 17.767],
    TN: ['Trenčín', 48.894, 18.042], PU: ['Púchov', 49.12, 18.326], ZA: ['Žilina', 49.227, 18.74],
    VR: ['Vrútky', 49.112, 18.92], RK: ['Ružomberok', 49.079, 19.303], LM: ['Liptovský Mikuláš', 49.085, 19.613],
    PP: ['Poprad-Tatry', 49.056, 20.298], SN: ['Spišská Nová Ves', 48.944, 20.565], KY: ['Kysak', 48.853, 21.222],
    KE: ['Košice', 48.721, 21.267], PO: ['Prešov', 48.995, 21.244], HE: ['Humenné', 48.93, 21.906],
    GA: ['Galanta', 48.19, 17.727], NZ: ['Nové Zámky', 47.986, 18.166], ST: ['Štúrovo', 47.799, 18.717],
    LV: ['Levice', 48.213, 18.6], ZV: ['Zvolen', 48.574, 19.128], BB: ['Banská Bystrica', 48.731, 19.149],
    LC: ['Lučenec', 48.329, 19.666], NI: ['Nitra', 48.311, 18.085], KN: ['Komárno', 47.761, 18.128],
    KU: ['Kúty', 48.66, 17.03], PD: ['Prievidza', 48.771, 18.624], CA: ['Čadca', 49.437, 18.79],
    MI: ['Michalovce', 48.752, 21.919], CT: ['Čierna nad Tisou', 48.418, 22.087], RV: ['Rožňava', 48.661, 20.532],
  };
  const LINES = [
    { s: 'BA TT LE TN PU ZA VR RK LM PP SN KY KE', types: ['R', 'Ex', 'IC', 'Os'], names: ['Tatran', 'Kriváň', 'Šarišan', 'Chopok', 'Gerlach', 'Detvan'] },
    { s: 'BA GA NZ ST', types: ['Os', 'REX', 'EC'], names: ['Hungaria', 'Metropolitan', ''] },
    { s: 'NZ LV ZV BB', types: ['R', 'Os'], names: ['Hron', 'Sitno', ''] },
    { s: 'ZV LC RV KE', types: ['R', 'Os'], names: ['Rimava', 'Gemeran', ''] },
    { s: 'BA KU', types: ['Os', 'REX', 'EC'], names: ['Slovan', ''] },
    { s: 'KE KY PO', types: ['Os', 'REX'], names: [''] },
    { s: 'KE MI HE', types: ['R', 'Os'], names: ['Zemplín', 'Laborec', ''] },
    { s: 'KE CT', types: ['Os', 'R'], names: [''] },
    { s: 'ZA CA', types: ['Os', 'EC'], names: ['Beskydy', ''] },
    { s: 'LE NI', types: ['Os', 'R'], names: ['Zobor', ''] },
    { s: 'NZ KN', types: ['Os'], names: [''] },
    { s: 'NI PD', types: ['Os', 'R'], names: ['Ponitrian', ''] },
  ];
  const CARRIERS = { R: 'ZSSK', Ex: 'ZSSK', IC: 'ZSSK', Os: 'ZSSK', REX: 'ZSSK', EC: 'ZSSK', RJ: 'RegioJet', LE: 'Leo Express' };

  function rng(seed) { return () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296); }

  function demoDelay(r) {
    const x = r();
    if (x < 0.62) return Math.floor(r() * 6);
    if (x < 0.82) return 6 + Math.floor(r() * 10);
    if (x < 0.92) return 16 + Math.floor(r() * 15);
    if (x < 0.98) return 31 + Math.floor(r() * 30);
    return 61 + Math.floor(r() * 90);
  }

  function demoTrains() {
    const r = rng(Math.floor(Date.now() / REFRESH_MS));
    const out = [];
    const used = new Set();
    for (let i = 0; i < 168; i++) {
      const line = LINES[Math.floor(r() * LINES.length) % LINES.length];
      const st = line.s.split(' ');
      const rev = r() < 0.5;
      const seq = rev ? st.slice().reverse() : st;
      const seg = Math.floor(r() * (seq.length - 1));
      const f = r();
      const [aN, aLat, aLon] = ST[seq[seg]], [bN, bLat, bLon] = ST[seq[seg + 1]];
      let type = line.types[Math.floor(r() * line.types.length)];
      if (line.s.startsWith('BA TT') && r() < 0.12) type = r() < 0.5 ? 'RJ' : 'LE';
      let number;
      do { number = String(type === 'Os' ? 3000 + Math.floor(r() * 6000) : 100 + Math.floor(r() * 1900)); } while (used.has(number));
      used.add(number);
      const name = ['Os', 'REX'].includes(type) ? '' : line.names[Math.floor(r() * line.names.length)];
      const delay = demoDelay(r);
      const hh = new Date(Date.now() - (5 + r() * 20) * 60000);
      out.push({
        id: type + number, type, number, name,
        route: `${ST[seq[0]][0]} – ${ST[seq[seq.length - 1]][0]}`,
        carrier: CARRIERS[type] || 'ZSSK',
        delay, delayText: '',
        info: `Odchod ${aN} ${fmtTime(hh)}`,
        next: bN,
        lat: +(aLat + (bLat - aLat) * f).toFixed(4), lon: +(aLon + (bLon - aLon) * f).toFixed(4),
        disruption: delay > 60 && r() < 0.4 ? 'Porucha rušňa' : '',
        note: '', nad: delay > 30 && r() < 0.15 ? [aN, bN] : [],
      });
    }
    return out;
  }

  function demoHistory() {
    const r = rng(7);
    const now = Date.now();
    return Array.from({ length: 96 }, (_, i) => {
      const hour = new Date(now - (95 - i) * 15 * 60000).getHours();
      const rush = (hour >= 6 && hour <= 8) || (hour >= 15 && hour <= 18);
      return { t: new Date(now - (95 - i) * 15 * 60000).toISOString(), n: 150, onTime: 70 - (rush ? 12 : 0) + r() * 8, avg: 6 + r() * 4, over15: 10 + Math.floor(r() * 10) };
    });
  }

  bind();
  load();
  setInterval(tick, 1000);
  tick();
})();
