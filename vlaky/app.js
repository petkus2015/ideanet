/* Meškanie vlakov SK — prehľad z dát mapa.zsr.sk (vlaky/data/trains.json),
   ktoré každých 5 minút obnovuje scripts/fetch_zsr.py. */
(() => {
  'use strict';

  const DATA_URL = 'data/trains.json';
  const HISTORY_URL = 'data/history.json';
  const REFRESH_MS = 5 * 60 * 1000;
  const STALE_MS = 15 * 60 * 1000;
  const PAGE = 15;
  const PREVIEW = !!window.VLAKY_DEMO;   // samostatný náhľad bez dátového súboru
  const FORCE_DEMO = PREVIEW || new URLSearchParams(location.search).has('demo');

  const CATS = [
    { label: 'Načas', range: '0–5 min', max: 5 },
    { label: 'Mierne', range: '6–15 min', max: 15 },
    { label: 'Stredné', range: '16–30 min', max: 30 },
    { label: 'Veľké', range: '31–60 min', max: 60 },
    { label: 'Extrémne', range: '> 60 min', max: Infinity },
  ];
  const catOf = d => CATS.findIndex(c => d <= c.max);

  // druhy vlakov podľa označenia ŽSR (TypVlaku)
  const GROUPS = [
    { id: 'r', label: 'Rýchliky', types: ['R'] },
    { id: 'ex', label: 'Expresy a IC', types: ['Ex', 'IC', 'SC', 'RJ', 'LE'] },
    { id: 'int', label: 'Medzinárodné', types: ['EC', 'EN', 'RJX', 'ICE', 'NJ'] },
    { id: 'reg', label: 'Regionálne', types: ['Os', 'REX', 'Zr', 'Sp', 'MOs'] },
  ];
  // poradie „rýchliky a expresy prvé“
  const FAST_RANK = { r: 0, ex: 0, int: 1, reg: 2, other: 3 };
  const QUICK_STATIONS = ['Bratislava', 'Žilina', 'Košice', 'Banská Bystrica', 'Poprad-Tatry', 'Nové Zámky'];
  const TYPE_GROUP = new Map(GROUPS.flatMap(g => g.types.map(t => [t.toUpperCase(), g.id])));
  const groupOf = t => TYPE_GROUP.get(String(t.type || '').toUpperCase()) || 'other';

  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmtMin = m => (m >= 60 ? `${Math.floor(m / 60)} h ${String(m % 60).padStart(2, '0')} min` : `${m} min`);
  const fmtTime = d => d.toLocaleTimeString('sk-SK', { hour: '2-digit', minute: '2-digit' });
  const fmtNum = (n, dp = 0) => n.toLocaleString('sk-SK', { minimumFractionDigits: dp, maximumFractionDigits: dp });
  const trainLabel = t => [t.type, t.number].filter(Boolean).join(' ') || t.id || 'Vlak';
  const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

  const state = {
    trains: [], history: [], prev: null, updated: null,
    mode: 'loading', cat: null, q: '', carrier: '', sort: 'fast',
    group: null, station: '', showPassed: false,
    shown: PAGE, active: null, nextAt: Date.now() + REFRESH_MS,
  };
  const store = {
    get(k) { try { return localStorage.getItem(`vlaky.${k}`) || ''; } catch { return ''; } },
    set(k, v) { try { localStorage.setItem(`vlaky.${k}`, v || ''); } catch { /* bez úložiska */ } },
  };

  /* ---------------- dáta ---------------- */

  async function getJSON(url) {
    const res = await fetch(`${url}?t=${Date.now()}`, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  }

  /* ---------------- stanica ---------------- */

  const km = (a, b) => Math.hypot((a[1] - b[1]) * 73.4, (a[0] - b[0]) * 111.2);
  const stationCoords = name => {
    const s = Object.values(ST).find(x => norm(x[0]) === norm(name));
    return s ? [s[1], s[2]] : null;
  };
  const sameStation = (field, st) => !!field && norm(field).includes(norm(st));

  // poloha bodu pozdĺž trasy: index úseku + podiel (s) a vzdialenosť od trasy v km (d)
  function along(path, p) {
    let best = { s: 0, d: Infinity };
    for (let i = 0; i < path.length - 1; i++) {
      const [ay, ax0] = path[i], [by, bx0] = path[i + 1];
      const ax = ax0 * 0.66, bx = bx0 * 0.66, px = p[1] * 0.66, py = p[0];
      const vx = bx - ax, vy = by - ay, l2 = vx * vx + vy * vy || 1e-12;
      const u = Math.max(0, Math.min(1, ((px - ax) * vx + (py - ay) * vy) / l2));
      const d = km(p, [ay + u * vy, (ax + u * vx) / 0.66]);
      if (d < best.d) best = { s: i + u, d };
    }
    return best;
  }

  // vzťah vlaku k vybranej stanici: here | upcoming | passed | null
  function relation(t) {
    const st = state.station;
    if (!st) return null;
    const c = stationCoords(st), pos = t.lat != null ? [t.lat, t.lon] : null;
    if (c && pos && km(c, pos) < 2) return 'here';
    if (c && t.path?.length > 1) {
      const S = along(t.path, c);
      if (S.d <= 3) return pos ? (S.s > along(t.path, pos).s ? 'upcoming' : 'passed') : 'upcoming';
    }
    const [from, to] = String(t.route || '').split(/\s+[-–]\s+/);
    if (sameStation(t.next, st) || sameStation(to, st)) return 'upcoming';
    if (sameStation(t.info, st) || sameStation(from, st)) return 'passed';
    return null;
  }

  const atStation = t => {
    const r = relation(t);
    return r === 'here' || r === 'upcoming' || (state.showPassed && r === 'passed');
  };
  const stationScope = () => (state.station ? state.trains.filter(atStation) : state.trains);
  const scope = () => (state.group ? stationScope().filter(t => groupOf(t) === state.group) : stationScope());
  const pickGroup = snap => (snap && state.group ? snap.g?.[state.group] : snap);

  function summarizeAll(trains) {
    const g = {};
    GROUPS.forEach(gr => { g[gr.id] = summarize(trains.filter(t => groupOf(t) === gr.id)); });
    return { ...summarize(trains), g };
  }

  function summarize(trains) {
    const n = trains.length;
    return {
      n,
      avg: n ? trains.reduce((a, t) => a + t.delay, 0) / n : 0,
      onTime: n ? (100 * trains.filter(t => t.delay <= 5).length) / n : 0,
      over15: trains.filter(t => t.delay > 15).length,
    };
  }

  async function load() {
    $('refresh').classList.add('is-busy');
    const before = state.trains.length ? summarizeAll(state.trains) : null;
    try {
      if (FORCE_DEMO) throw new Error('demo');
      const data = await getJSON(DATA_URL);
      if (!Array.isArray(data.trains)) throw new Error('neplatný formát');
      state.trains = data.trains.map(t => ({ ...t, delay: Math.max(0, Number(t.delay) || 0) }));
      state.updated = new Date(data.updated);
      state.mode = Date.now() - state.updated > STALE_MS ? 'stale' : 'live';
      try { state.history = await getJSON(HISTORY_URL); } catch { state.history = []; }
    } catch {
      if (state.trains.length && state.mode !== 'demo') {
        state.mode = 'error';   // ponecháme posledné platné dáta
      } else {
        state.trains = demoTrains();
        state.history = demoHistory(summarizeAll(state.trains));
        state.updated = new Date();
        state.mode = 'demo';
      }
    }
    const h = state.history;
    state.prev = h.length > 1 ? h[h.length - 2] : before;
    state.nextAt = Date.now() + REFRESH_MS;
    setTimeout(() => $('refresh').classList.remove('is-busy'), 400);
    render();
  }

  /* ---------------- render ---------------- */

  function render() {
    renderStatus(); renderStation(); renderSeg(); renderGroups(); renderKpis(); renderDist(); renderTrend(); renderCarriers(); renderList(); renderDots();
  }

  function renderStatus() {
    const labels = { live: 'Živé dáta', stale: 'Oneskorené dáta', demo: 'Ukážkové dáta', error: 'Výpadok spojenia', loading: 'Načítavam…' };
    $('pill').dataset.state = state.mode;
    $('pillText').textContent = labels[state.mode];
    $('updated').textContent = state.updated ? `aktualizované ${fmtTime(state.updated)}` : '—';
    const msg = {
      demo: PREVIEW
        ? '<b>Ukážka rozhrania.</b> Vlaky a meškania sú vymyslené, ale realistické. Naostro stránka každých 5 minút načíta dáta z mapy polohy vlakov ŽSR.'
        : '<b>Ukážkové dáta.</b> Súbor <code>data/trains.json</code> zatiaľ neexistuje. Spustite <code>python3 scripts/fetch_zsr.py --loop</code> alebo GitHub Action „Meškanie vlakov“.',
      stale: `Posledné dáta sú z ${state.updated && fmtTime(state.updated)}. Zdroj ŽSR sa pravdepodobne neaktualizuje.`,
      error: 'Nové dáta sa nepodarilo načítať. Zobrazujem posledný platný stav, ďalší pokus o 5 minút.',
    }[state.mode];
    const n = $('notice');
    n.hidden = !msg;
    if (msg) { n.innerHTML = msg; n.dataset.kind = state.mode; }
  }

  function delta(cur, prev, { unit = '', dp = 0, upIsGood = false } = {}) {
    if (prev == null) return '&nbsp;';
    const d = cur - prev;
    if (Math.abs(d) < (dp ? 0.05 : 0.5)) return 'bez zmeny za 5 min';
    const good = upIsGood ? d > 0 : d < 0;
    return `<span class="${good ? 'good' : 'bad'}">${d > 0 ? '▲' : '▼'} ${fmtNum(Math.abs(d), dp)}${unit}</span> za 5 min`;
  }

  function renderKpis() {
    const s = summarize(scope()), p = state.station ? null : pickGroup(state.prev);
    $('kTotal').textContent = fmtNum(s.n);
    $('kTotalHint').textContent = `${fmtNum(scope().filter(t => t.delay > 5).length)} mešká viac ako 5 min`;
    $('kScope').textContent = [state.group ? GROUPS.find(g => g.id === state.group).label : 'Všetky vlaky', state.station && `stanica ${state.station}`].filter(Boolean).join(' · ');
    $('kOnTime').innerHTML = `${fmtNum(s.onTime)}<small>%</small>`;
    $('kOnTimeBar').style.width = `${s.onTime}%`;
    $('kOnTimeHint').innerHTML = delta(s.onTime, p?.onTime, { unit: ' p. b.', upIsGood: true });
    $('kAvg').innerHTML = `${fmtNum(s.avg, 1)}<small>min</small>`;
    $('kAvgHint').innerHTML = delta(s.avg, p?.avg, { unit: ' min', dp: 1 });
    $('kOver').textContent = fmtNum(s.over15);
    $('kOverHint').innerHTML = delta(s.over15, p?.over15);
    const top = scope().reduce((a, t) => (!a || t.delay > a.delay ? t : a), null);
    $('kMax').innerHTML = top ? (top.delay >= 60 ? `${Math.floor(top.delay / 60)}<small>h</small>&nbsp;${String(top.delay % 60).padStart(2, '0')}<small>min</small>` : `${top.delay}<small>min</small>`) : '—';
    $('kMaxHint').textContent = top ? `${trainLabel(top)}${top.name ? ' ' + top.name : ''} · ${top.route || ''}` : '';
  }

  function counts() {
    const c = CATS.map(() => 0);
    scope().forEach(t => c[catOf(t.delay)]++);
    return c;
  }

  function renderDist() {
    const c = counts(), total = scope().length || 1, max = Math.max(...c, 1);
    $('distBar').setAttribute('aria-label', CATS.map((k, i) => `${k.label}: ${c[i]}`).join(', '));
    $('distBar').innerHTML = CATS.map((k, i) => c[i]
      ? `<span class="${state.cat === i ? 'on' : ''}" style="flex-grow:${c[i]};background:var(--c${i})" title="${k.label}: ${c[i]}"></span>` : '').join('');
    $('cats').innerHTML = CATS.map((k, i) => `
      <button type="button" class="cat" data-cat="${i}" aria-pressed="${state.cat === i}">
        <i style="background:var(--c${i})"></i>
        <span>${k.label} <em>${k.range}</em></span>
        <span class="mini"><span style="width:${(100 * c[i]) / max}%;background:var(--c${i})"></span></span>
        <b>${c[i]}</b><small>${Math.round((100 * c[i]) / total)} %</small>
      </button>`).join('');
    document.querySelector('.side').classList.toggle('has-cat', state.cat !== null);
    $('clearCat').hidden = state.cat === null;
  }

  function renderTrend() {
    const svg = $('trendSvg');
    const h = state.history.map(x => {
      const v = pickGroup(x);
      return v && typeof v.onTime === 'number' && v.n ? { t: x.t, onTime: v.onTime } : null;
    }).filter(Boolean);
    if (h.length < 3) {
      svg.innerHTML = '<text class="t-lbl" x="300" y="80" text-anchor="middle">História sa zbiera, graf sa zobrazí po pár aktualizáciách.</text>';
      $('trendNow').textContent = '';
      return;
    }
    const W = 600, H = 150, L = 34, R = 10, T = 10, B = 22;
    const vals = h.map(x => x.onTime);
    const lo = Math.max(0, Math.floor((Math.min(...vals) - 5) / 10) * 10);
    const hi = Math.min(100, Math.ceil((Math.max(...vals) + 5) / 10) * 10);
    const step = hi - lo > 40 ? 20 : 10;
    const t0 = new Date(h[0].t).getTime(), t1 = new Date(h[h.length - 1].t).getTime();
    const x = t => L + ((new Date(t).getTime() - t0) / (t1 - t0 || 1)) * (W - L - R);
    const y = v => T + (1 - (v - lo) / (hi - lo || 1)) * (H - T - B);
    let g = '';
    for (let v = lo; v <= hi; v += step) {
      g += `<line class="t-grid" x1="${L}" x2="${W - R}" y1="${y(v)}" y2="${y(v)}"/><text class="t-lbl" x="${L - 6}" y="${y(v) + 4}" text-anchor="end">${v}%</text>`;
    }
    [0, 0.5, 1].forEach(f => {
      const t = t0 + f * (t1 - t0);
      g += `<text class="t-lbl" x="${L + f * (W - L - R)}" y="${H - 5}" text-anchor="${f === 0 ? 'start' : f === 1 ? 'end' : 'middle'}">${fmtTime(new Date(t))}</text>`;
    });
    const pts = h.map(p => `${x(p.t).toFixed(1)},${y(p.onTime).toFixed(1)}`);
    const last = h[h.length - 1];
    svg.innerHTML = `
      <defs><linearGradient id="tg" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0" stop-color="var(--accent)" stop-opacity=".28"/><stop offset="1" stop-color="var(--accent)" stop-opacity="0"/>
      </linearGradient></defs>
      ${g}
      <path class="t-area" fill="url(#tg)" d="M${pts.join('L')}L${x(last.t)},${H - B}L${L},${H - B}Z"/>
      <path class="t-line" d="M${pts.join('L')}"/>
      <circle class="t-end" cx="${x(last.t)}" cy="${y(last.onTime)}" r="5"/>`;
    $('trendNow').textContent = `teraz ${fmtNum(last.onTime)} %`;
  }

  function renderGroups() {
    const fmtAvg = s => `ø ${fmtNum(s.avg, 1)} min`;
    const card = (id, label, types, trains) => {
      const s = summarize(trains), c = CATS.map(() => 0);
      trains.forEach(t => c[catOf(t.delay)]++);
      const max = trains.reduce((m, t) => Math.max(m, t.delay), 0);
      const on = state.group === id || (!state.group && id === '');
      return `<button type="button" class="grp ${id ? '' : 'grp--all'}" data-group="${id}" aria-pressed="${on}" ${s.n ? '' : 'disabled'}>
        <span class="grp__head"><span class="grp__name">${label}</span><span class="grp__types">${types}</span></span>
        <span class="grp__v">${s.n ? fmtNum(s.onTime) + '<small>% načas</small>' : '<small>žiadne vlaky</small>'}</span>
        <span class="grp__bar">${c.map((n, i) => (n ? `<span style="flex-grow:${n};background:var(--c${i})"></span>` : '')).join('')}</span>
        <span class="grp__meta"><span><b>${s.n}</b> vlakov</span><span>${fmtAvg(s)}</span><span>max <b>${fmtMin(max)}</b></span></span>
      </button>`;
    };
    const base = stationScope();
    $('groups').innerHTML = card('', 'Všetky', 'všetky druhy', base) +
      GROUPS.map(g => card(g.id, g.label, g.types.filter(t => state.trains.some(x => x.type === t)).join(' · ') || g.types.slice(0, 3).join(' · '),
        base.filter(t => groupOf(t) === g.id))).join('');
  }

  function renderSeg() {
    const base = stationScope();
    const btn = (id, label, n) => `<button type="button" data-group="${id}" aria-pressed="${(state.group || '') === id}">${label}<span>${n}</span></button>`;
    $('groupSeg').innerHTML = btn('', 'Všetky', base.length) +
      GROUPS.map(g => btn(g.id, g.label, base.filter(t => groupOf(t) === g.id).length)).join('');
  }

  function renderStation() {
    const st = state.station;
    $('station').value = st;
    $('stationClear').hidden = !st;
    $('quick').innerHTML = QUICK_STATIONS.map(n =>
      `<button type="button" data-station="${esc(n)}" aria-pressed="${norm(n) === norm(st)}">${esc(n)}</button>`).join('');
    $('thPos').textContent = st ? `Poloha voči stanici ${st}` : 'Posledná poloha';
    const bar = $('stbar');
    bar.hidden = !st;
    if (!st) return;
    const rel = { here: 0, upcoming: 0, passed: 0 };
    state.trains.forEach(t => { const r = relation(t); if (r) rel[r]++; });
    bar.innerHTML = `
      <span class="stbar__name"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>${esc(st)}</span>
      <span class="stbar__stat"><b>${rel.upcoming}</b> príde</span>
      <span class="stbar__stat"><b>${rel.here}</b> v stanici</span>
      <span class="stbar__stat muted"><b>${rel.passed}</b> už prešlo</span>
      <label class="toggle"><input type="checkbox" id="showPassed" ${state.showPassed ? 'checked' : ''}><span></span>Zobraziť aj vlaky, ktoré už prešli</label>`;
  }

  function refreshAll() {
    state.shown = PAGE;
    state.active = null;
    $('tip').hidden = true;
    renderStation(); renderSeg(); renderGroups(); renderKpis(); renderDist(); renderList(); renderDots();
  }

  function setGroup(id) {
    state.group = id || null;
    store.set('group', state.group);
    refreshAll();
    renderTrend();
  }

  function setStation(name) {
    const match = Object.values(ST).find(x => norm(x[0]) === norm(name));
    state.station = match ? match[0] : '';
    store.set('station', state.station);
    refreshAll();
  }

  function renderCarriers() {
    const list = [...new Set(state.trains.map(t => t.carrier).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'sk'));
    if (state.carrier && !list.includes(state.carrier)) state.carrier = '';
    $('carrier').innerHTML = '<option value="">Všetci dopravcovia</option>' +
      list.map(c => `<option${c === state.carrier ? ' selected' : ''}>${esc(c)}</option>`).join('');
  }

  function filtered() {
    const q = norm(state.q);
    const byNum = (a, b) => (parseInt(a.number, 10) || 0) - (parseInt(b.number, 10) || 0);
    const sorters = {
      fast: (a, b) => FAST_RANK[groupOf(a)] - FAST_RANK[groupOf(b)] || b.delay - a.delay || byNum(a, b),
      delay: (a, b) => b.delay - a.delay || byNum(a, b),
      number: byNum,
    };
    const passedLast = (a, b) => (relation(a) === 'passed') - (relation(b) === 'passed');
    return scope().filter(t =>
      (state.cat === null || catOf(t.delay) === state.cat) &&
      (!state.carrier || t.carrier === state.carrier) &&
      (!q || norm(`${t.type} ${t.number} ${t.type}${t.number} ${t.name} ${t.route} ${t.info} ${t.next}`).includes(q)))
      .sort((a, b) => (state.station && passedLast(a, b)) || sorters[state.sort](a, b));
  }

  const REL = { upcoming: ['up', 'príde'], here: ['here', 'v stanici'], passed: ['past', 'prešiel'] };
  function relBadge(t) {
    const r = state.station && REL[relation(t)];
    return r ? `<span class="rel rel--${r[0]}">${r[1]}</span>` : '';
  }

  function renderList() {
    const items = filtered();
    const all = scope().length;
    const grp = (state.group ? ` · ${GROUPS.find(g => g.id === state.group).label.toLowerCase()}` : '') + (state.station ? ` · ${state.station}` : '');
    $('count').textContent = items.length === all
      ? `${items.length} vlakov${grp}, zoradené: ${$('sort').selectedOptions[0].textContent.toLowerCase()}`
      : `${items.length} z ${all} vlakov${grp}`;
    $('rows').innerHTML = items.length ? items.slice(0, state.shown).map(t => {
      const c = catOf(t.delay), sel = state.active === t.id;
      const more = [
        t.disruption && `<span class="warn">Mimoriadnosť: ${esc(t.disruption)}</span>`,
        t.nad?.length && `<span class="warn">Náhradná autobusová doprava: ${esc(t.nad.join(' – '))}</span>`,
        t.note && `<span>${esc(t.note)}</span>`,
        t.delayText && `<span>Hlásenie ŽSR: ${esc(t.delayText)}</span>`,
        t.lat == null && '<span>Poloha vlaku nie je k dispozícii.</span>',
      ].filter(Boolean).join('');
      return `<li class="row ${sel ? 'sel' : ''}" data-id="${esc(t.id)}" tabindex="0" role="button" aria-expanded="${sel}"
          aria-label="${esc(trainLabel(t))}, meškanie ${fmtMin(t.delay)}">
        <div class="row__main">
          <span class="dly k${c}">${t.delay <= 0 ? '0' : '+' + t.delay}<small>min</small></span>
          <span class="tr"><span class="tr__id">${esc(trainLabel(t))}</span>${t.name ? `<span class="tr__name">${esc(t.name)}</span>` : ''}</span>
          <span class="cell">${esc(t.route) || '—'}</span>
          <span class="cell">${relBadge(t)}${t.info ? esc(t.info) : '<span class="muted">neznáma</span>'}${t.next ? `<small>ďalej ${esc(t.next)}</small>` : ''}</span>
          <span class="carrier">${esc(t.carrier || '—')}</span>
        </div>
        ${more ? `<div class="row__more">${more}</div>` : ''}
      </li>`;
    }).join('') : '<li class="empty">Žiadny vlak nezodpovedá filtru.</li>';
    const rest = items.length - state.shown;
    $('more').hidden = rest <= 0;
    $('more').textContent = `Zobraziť ďalšie (${rest})`;
  }

  /* ---------------- schéma siete ---------------- */

  const ST = {
    BA: ['Bratislava', 48.158, 17.106], TT: ['Trnava', 48.371, 17.585], LE: ['Leopoldov', 48.447, 17.767],
    TN: ['Trenčín', 48.894, 18.042], PU: ['Púchov', 49.12, 18.326], ZA: ['Žilina', 49.227, 18.74],
    VR: ['Vrútky', 49.112, 18.92], RK: ['Ružomberok', 49.079, 19.303], LM: ['Liptovský Mikuláš', 49.085, 19.613],
    PP: ['Poprad-Tatry', 49.056, 20.298], SN: ['Spišská Nová Ves', 48.944, 20.565], KY: ['Kysak', 48.853, 21.222],
    KE: ['Košice', 48.721, 21.267], PO: ['Prešov', 48.995, 21.244], HE: ['Humenné', 48.93, 21.906],
    GA: ['Galanta', 48.19, 17.727], NZ: ['Nové Zámky', 47.986, 18.166], ST: ['Štúrovo', 47.799, 18.717],
    LV: ['Levice', 48.213, 18.6], ZV: ['Zvolen', 48.574, 19.128], BB: ['Banská Bystrica', 48.731, 19.149],
    LC: ['Lučenec', 48.329, 19.666], NI: ['Nitra', 48.311, 18.085], KN: ['Komárno', 47.761, 18.128],
    KU: ['Kúty', 48.66, 17.03], PD: ['Prievidza', 48.771, 18.624], CA: ['Čadca', 49.437, 18.79],
    MI: ['Michalovce', 48.752, 21.919], CT: ['Čierna nad Tisou', 48.418, 22.087], RV: ['Rožňava', 48.661, 20.532],
    SE: ['Senica', 48.68, 17.367], PB: ['Považská Bystrica', 49.116, 18.448], BJ: ['Bardejov', 49.29, 21.276],
    TO: ['Topoľčany', 48.556, 18.176], RS: ['Rimavská Sobota', 48.383, 20.016], VK: ['Vranov n. T.', 48.889, 21.687],
  };
  const LINES = [
    'BA TT LE TN PU PB ZA VR RK LM PP SN KY KE',
    'BA GA NZ ST', 'NZ LV ZV BB VR', 'ZV LC RS RV KE', 'BA SE KU', 'KE KY PO BJ',
    'KE MI HE', 'KE CT', 'ZA CA', 'LE NI', 'NZ KN', 'NI TO PD', 'PO VK HE',
  ];
  const LABELS = {
    BA: [10, 18, 'start', 1], TN: [-10, 4, 'end', 1], ZA: [0, -14, 'middle', 1], BB: [10, -4, 'start', 1],
    ZV: [-10, 14, 'end', 0], PP: [0, -14, 'middle', 1], KE: [10, 18, 'start', 1], PO: [10, 0, 'start', 1],
    NZ: [-10, 4, 'end', 0], NI: [-10, 4, 'end', 0], HE: [10, 4, 'start', 0], LC: [8, 18, 'start', 0],
    KN: [10, 4, 'start', 0], CA: [0, -12, 'middle', 0], RV: [0, -12, 'middle', 0], TT: [-8, -8, 'end', 0],
  };
  const BORDER = [
    [16.95, 48.62], [17.2, 48.87], [17.42, 48.83], [17.7, 48.87], [17.93, 49.02], [18.1, 49.08], [18.16, 49.27],
    [18.4, 49.33], [18.55, 49.48], [18.85, 49.52], [18.98, 49.4], [19.17, 49.4], [19.25, 49.52], [19.47, 49.6],
    [19.78, 49.2], [20.07, 49.18], [20.35, 49.4], [20.57, 49.38], [20.92, 49.3], [21.1, 49.37], [21.4, 49.43],
    [21.62, 49.44], [21.97, 49.34], [22.25, 49.15], [22.56, 49.08], [22.43, 48.93], [22.3, 48.68], [22.15, 48.41],
    [21.85, 48.37], [21.6, 48.5], [21.43, 48.57], [21.1, 48.5], [20.83, 48.58], [20.5, 48.5], [20.28, 48.26],
    [19.92, 48.13], [19.63, 48.24], [19.47, 48.09], [19.0, 48.07], [18.82, 48.0], [18.77, 47.87], [18.45, 47.76],
    [18.0, 47.75], [17.75, 47.75], [17.48, 47.87], [17.17, 48.0], [17.07, 48.11], [16.95, 48.27], [16.86, 48.47],
  ];
  const proj = (lat, lon) => [(lon - 16.8) * 172.3, (49.68 - lat) * 261];
  const pt = (lat, lon) => proj(lat, lon).map(v => v.toFixed(1)).join(',');

  function drawBase() {
    const land = `<path class="m-land" d="M${BORDER.map(([lon, lat]) => pt(lat, lon)).join('L')}Z"/>`;
    const rails = LINES.map(l => {
      const d = 'M' + l.split(' ').map(k => pt(ST[k][1], ST[k][2])).join('L');
      return `<path class="m-rail" d="${d}"/><path class="m-rail-tie" d="${d}"/>`;
    }).join('');
    const sts = Object.entries(ST).map(([k, [, lat, lon]]) => {
      const [x, y] = proj(lat, lon);
      const lb = LABELS[k];
      return `<circle class="m-st" cx="${x}" cy="${y}" r="${lb ? 4 : 2.5}"/>` +
        (lb ? `<text class="m-lbl ${lb[3] ? 'm-lbl--big' : ''}" x="${x + lb[0]}" y="${y + lb[1]}" text-anchor="${lb[2]}">${ST[k][0]}</text>` : '');
    }).join('');
    $('mapSvg').innerHTML = `${land}${rails}${sts}<g id="dots"></g>`;
  }

  function renderDots() {
    const g = $('dots');
    if (!g) return;
    const items = filtered().filter(t => t.lat != null && t.lon != null).sort((a, b) => a.delay - b.delay);
    const sc = state.station && stationCoords(state.station);
    const stMark = sc ? (() => {
      const [x, y] = proj(sc[0], sc[1]);
      return `<circle class="st-sel" cx="${x}" cy="${y}" r="14"/><circle class="st-sel__dot" cx="${x}" cy="${y}" r="5"/>` +
        `<text class="st-sel__lbl" x="${x}" y="${y - 22}" text-anchor="middle">${esc(state.station)}</text>`;
    })() : '';
    g.innerHTML = stMark + items.map(t => {
      const c = catOf(t.delay), [x, y] = proj(t.lat, t.lon), r = 5 + c * 1.6, sel = state.active === t.id;
      return (c >= 3 ? `<circle class="halo" cx="${x}" cy="${y}" r="${r}" stroke="var(--c${c})"/>` : '') +
        `<circle class="dot ${sel ? 'sel' : ''}" data-id="${esc(t.id)}" cx="${x}" cy="${y}" r="${r}" fill="var(--c${c})"/>` +
        (sel ? `<circle class="selring" cx="${x}" cy="${y}" r="${r + 7}"/>` : '');
    }).join('');
  }

  function showTip(t, el) {
    const tip = $('tip'), box = $('map').getBoundingClientRect(), r = el.getBoundingClientRect();
    const c = catOf(t.delay);
    tip.innerHTML = `<b>${esc(trainLabel(t))}</b>${t.name ? ' ' + esc(t.name) : ''}<br>
      <span style="color:var(--c${c});font-weight:700">${t.delay <= 5 ? (t.delay ? '+' + t.delay + ' min · načas' : 'načas') : '+' + fmtMin(t.delay)}</span><br>
      ${esc(t.route)}${t.info ? `<br><span class="muted">${esc(t.info)}${t.next ? ' → ' + esc(t.next) : ''}</span>` : ''}`;
    tip.hidden = false;
    const cx = r.left + r.width / 2 - box.left, cy = r.top - box.top;
    const w = tip.offsetWidth, h = tip.offsetHeight;
    tip.style.left = `${Math.min(Math.max(8, cx - w / 2), box.width - w - 8)}px`;
    const top = cy - h - 10 < 8 ? cy + r.height + 10 : cy - h - 10;
    tip.style.top = `${Math.max(8, Math.min(top, box.height - h - 8))}px`;
  }

  function select(id, from) {
    state.active = state.active === id && from === 'list' ? null : id;
    if (from === 'map') {
      const idx = filtered().findIndex(t => t.id === id);
      if (idx >= state.shown) state.shown = idx + 1;
    }
    renderList(); renderDots();
    if (from === 'map') {
      document.querySelector(`.row[data-id="${CSS.escape(id)}"]`)?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    } else if (state.active) {
      const dot = document.querySelector(`.dot[data-id="${CSS.escape(id)}"]`);
      const t = state.trains.find(x => x.id === id);
      if (dot && t) showTip(t, dot);
    } else {
      $('tip').hidden = true;
    }
  }

  /* ---------------- interakcie ---------------- */

  function setCat(v) {
    state.cat = v === null || state.cat === Number(v) ? null : Number(v);
    state.shown = PAGE;
    renderDist(); renderList(); renderDots();
  }

  function bind() {
    $('groups').addEventListener('click', e => { const b = e.target.closest('[data-group]'); if (b) setGroup(b.dataset.group); });
    $('groupSeg').addEventListener('click', e => { const b = e.target.closest('[data-group]'); if (b) setGroup(b.dataset.group); });
    $('quick').addEventListener('click', e => {
      const b = e.target.closest('[data-station]');
      if (b) setStation(norm(b.dataset.station) === norm(state.station) ? '' : b.dataset.station);
    });
    $('station').addEventListener('input', e => {
      const v = e.target.value.trim();
      if (!v && state.station) setStation('');
      else if (Object.values(ST).some(x => norm(x[0]) === norm(v))) setStation(v);
    });
    $('station').addEventListener('keydown', e => {
      if (e.key !== 'Enter') return;
      const v = norm(e.target.value.trim());
      const hit = v && Object.values(ST).find(x => norm(x[0]).startsWith(v));
      if (hit) { setStation(hit[0]); e.target.blur(); }
    });
    $('stationClear').addEventListener('click', () => { setStation(''); $('station').focus(); });
    $('stbar').addEventListener('change', e => {
      if (e.target.id === 'showPassed') { state.showPassed = e.target.checked; refreshAll(); }
    });
    $('cats').addEventListener('click', e => { const b = e.target.closest('[data-cat]'); if (b) setCat(b.dataset.cat); });
    $('clearCat').addEventListener('click', () => setCat(null));
    let tq;
    $('q').addEventListener('input', e => {
      clearTimeout(tq);
      tq = setTimeout(() => { state.q = e.target.value; state.shown = PAGE; renderList(); renderDots(); }, 150);
    });
    $('carrier').addEventListener('change', e => { state.carrier = e.target.value; state.shown = PAGE; renderList(); renderDots(); });
    $('sort').addEventListener('change', e => { state.sort = e.target.value; renderList(); });
    $('more').addEventListener('click', () => { state.shown += PAGE; renderList(); });
    $('refresh').addEventListener('click', load);
    $('rows').addEventListener('click', e => { const li = e.target.closest('.row'); if (li) select(li.dataset.id, 'list'); });
    $('rows').addEventListener('keydown', e => {
      if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('row')) { e.preventDefault(); select(e.target.dataset.id, 'list'); }
    });
    const svg = $('mapSvg');
    svg.addEventListener('mouseover', e => {
      const d = e.target.closest('.dot'); if (!d) return;
      const t = state.trains.find(x => x.id === d.dataset.id); if (t) showTip(t, d);
    });
    svg.addEventListener('mouseout', e => { if (e.target.closest('.dot') && !state.active) $('tip').hidden = true; });
    svg.addEventListener('click', e => {
      const d = e.target.closest('.dot');
      if (!d) { state.active = null; $('tip').hidden = true; renderList(); renderDots(); return; }
      select(d.dataset.id, 'map');
      const nd = document.querySelector(`.dot[data-id="${CSS.escape(d.dataset.id)}"]`);
      const t = state.trains.find(x => x.id === d.dataset.id);
      if (nd && t) showTip(t, nd);
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && Date.now() >= state.nextAt) load(); });
  }

  function tick() {
    const left = Math.max(0, state.nextAt - Date.now()), s = Math.ceil(left / 1000);
    $('clock').textContent = new Date().toLocaleTimeString('sk-SK', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    $('countdown').textContent = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    $('ringFg').style.strokeDashoffset = String(100.53 * (left / REFRESH_MS));
    if (left === 0 && !document.hidden) { state.nextAt = Date.now() + REFRESH_MS; load(); }
  }

  /* ---------------- ukážkové dáta ---------------- */

  const SERVICES = [
    { l: 'BA TT LE TN PU PB ZA VR RK LM PP SN KY KE', types: ['R', 'R', 'Ex', 'IC', 'RJ', 'LE'], names: ['Tatran', 'Kriváň', 'Šarišan', 'Chopok', 'Gerlach', 'Hornád', 'Spiš', 'Rysy'] },
    { l: 'BA TT LE TN', types: ['Os', 'REX'] }, { l: 'ZA VR RK LM PP', types: ['Os'] },
    { l: 'BA GA NZ ST', types: ['Os', 'REX', 'EC'], names: ['Hungaria', 'Metropolitan', 'Avala'] },
    { l: 'NZ LV ZV BB', types: ['R', 'Os'], names: ['Hron', 'Sitno', 'Vepor'] }, { l: 'BB VR', types: ['Os', 'R'], names: ['Turiec'] },
    { l: 'ZV LC RS RV KE', types: ['R', 'Os'], names: ['Rimava', 'Gemeran', 'Novohrad'] },
    { l: 'BA SE KU', types: ['Os', 'REX', 'EC'], names: ['Slovan', 'Pannonia'] }, { l: 'KE KY PO', types: ['Os', 'REX'] },
    { l: 'PO BJ', types: ['Os'] }, { l: 'KE MI HE', types: ['R', 'Os'], names: ['Zemplín', 'Laborec'] }, { l: 'KE CT', types: ['Os', 'Os', 'EN'], names: ['Kyiv Express'] },
    { l: 'ZA CA', types: ['Os', 'EC', 'EC', 'EN'], names: ['Beskydy', 'Praha', 'Košičan'] }, { l: 'LE NI', types: ['Os', 'R'], names: ['Zobor'] },
    { l: 'NZ KN', types: ['Os'] }, { l: 'NI TO PD', types: ['Os', 'R'], names: ['Ponitrie', 'Bojnice'] }, { l: 'PO VK HE', types: ['Os'] },
  ];
  const CARRIER = { RJ: 'RegioJet', LE: 'Leo Express' };
  const ISSUES = ['Porucha rušňa', 'Obmedzenie rýchlosti na trati', 'Čakanie na prípoj', 'Porucha zabezpečovacieho zariadenia', 'Mimoriadna udalosť na trati', 'Neskorý príchod súpravy'];

  function rng(seed) { return () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296); }

  function demoDelay(r) {
    const x = r();
    if (x < 0.7) return Math.floor(r() * 6);
    if (x < 0.86) return 6 + Math.floor(r() * 10);
    if (x < 0.94) return 16 + Math.floor(r() * 15);
    if (x < 0.985) return 31 + Math.floor(r() * 30);
    return 61 + Math.floor(r() * 75);
  }

  function demoTrains() {
    const slot = Math.floor(Date.now() / REFRESH_MS);
    const base = rng(20261025), drift = rng(slot);
    const out = [], used = new Set();
    for (let i = 0; i < 172; i++) {
      const sv = SERVICES[Math.floor(base() * SERVICES.length)];
      let seq = sv.l.split(' ');
      if (base() < 0.5) seq = seq.reverse();
      const type = sv.types[Math.floor(base() * sv.types.length)];
      let number;
      do {
        number = String(type === 'Os' ? 2000 + Math.floor(base() * 6000) : type === 'REX' ? 1700 + Math.floor(base() * 300) : 200 + Math.floor(base() * 1400));
      } while (used.has(type + number));
      used.add(type + number);
      // poloha sa posúva po trati s každou aktualizáciou
      const prog = (base() + slot * 0.012) % 1;
      const segF = prog * (seq.length - 1), seg = Math.floor(segF), f = segF - seg;
      const a = ST[seq[seg]], b = ST[seq[seg + 1]];
      const baseDelay = demoDelay(base);
      let delay = baseDelay > 5 ? Math.max(6, baseDelay + Math.round((drift() - 0.45) * 6)) : baseDelay;
      if (['EC', 'EN'].includes(type) && base() < 0.55) delay += 8 + Math.floor(base() * 22);   // prípoje zo zahraničia
      if (['Ex', 'IC'].includes(type) && delay > 30) delay = Math.round(delay / 2);
      const dep = new Date(Date.now() - (2 + f * 18) * 60000);
      const named = sv.names && !['Os', 'REX', 'RJ', 'LE'].includes(type);
      const arrived = f > 0.9;   // tesne pred stanicou b hlási ŽSR už príchod do nej
      out.push({
        id: type + number, type, number,
        name: named ? sv.names[Math.floor(base() * sv.names.length)] : '',
        route: `${ST[seq[0]][0]} – ${ST[seq[seq.length - 1]][0]}`,
        carrier: CARRIER[type] || 'ZSSK',
        delay, delayText: '',
        info: arrived ? `Príchod ${b[0]} ${fmtTime(new Date())}` : `${f < 0.08 ? 'Príchod' : 'Odchod'} ${a[0]} ${fmtTime(dep)}`,
        next: arrived ? (ST[seq[seg + 2]]?.[0] || '') : b[0],
        lat: +(a[1] + (b[1] - a[1]) * f).toFixed(4), lon: +(a[2] + (b[2] - a[2]) * f).toFixed(4),
        path: seq.map(k => [ST[k][1], ST[k][2]]),
        disruption: delay > 30 ? ISSUES[Math.floor(base() * ISSUES.length)] : '',
        note: '', nad: delay > 45 && base() < 0.3 ? [a[0], b[0]] : [],
      });
    }
    return out;
  }

  function demoHistory(now) {
    const r = rng(11), end = Date.now(), pts = [];
    for (let i = 287; i >= 0; i--) {
      const t = new Date(end - i * 5 * 60000), hr = t.getHours() + t.getMinutes() / 60;
      const rush = Math.exp(-((hr - 7.5) ** 2) / 1.5) + Math.exp(-((hr - 16.5) ** 2) / 2);
      const wave = Math.sin(i / 9) * 1.5 + Math.sin(i / 23 + 1) * 2;
      const on = i === 0 ? now.onTime : Math.min(95, now.onTime - rush * 12 + wave + (r() - 0.5) * 1.5);
      const g = {};
      Object.entries(now.g).forEach(([id, s]) => {
        const k = { reg: 8, r: 14, ex: 10, int: 18 }[id] || 10;
        g[id] = { n: s.n, onTime: i === 0 ? s.onTime : Math.max(0, Math.min(100, s.onTime - rush * k + wave * 1.4 + (r() - 0.5) * 2)), avg: s.avg, over15: s.over15 };
      });
      pts.push({ t: t.toISOString(), n: now.n, onTime: on, avg: i === 0 ? now.avg : 5 + rush * 4 + r() * 2, over15: i === 0 ? now.over15 : Math.round(14 + rush * 12 + r() * 6), g });
    }
    return pts;
  }

  $('stationList').innerHTML = Object.values(ST).map(x => x[0]).sort((a, b) => a.localeCompare(b, 'sk'))
    .map(n => `<option value="${esc(n)}"></option>`).join('');
  state.station = stationCoords(store.get('station')) ? Object.values(ST).find(x => norm(x[0]) === norm(store.get('station')))[0] : '';
  state.group = GROUPS.some(g => g.id === store.get('group')) ? store.get('group') : null;
  drawBase();
  bind();
  load();
  setInterval(tick, 1000);
  tick();
})();
