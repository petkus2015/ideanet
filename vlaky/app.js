/* Meškanie vlakov SK — prehľad z dát mapa.zsr.sk (vlaky/data/trains.json),
   ktoré každé 2 minúty obnovuje scripts/fetch_zsr.py. */
(() => {
  'use strict';

  // nastavenia: samostatná stránka používa predvolené cesty, WordPress plugin ich podá cez window.VLAKY_CONFIG
  const CFG = window.VLAKY_CONFIG || {};
  const ROOT = CFG.root || document;   // v plugine Shadow DOM, aby sa štýly témy a aplikácie nemiešali
  const HOST = CFG.root ? CFG.root.host : document.documentElement;
  const DATA_URL = CFG.trainsUrl || 'data/trains.json';
  const HISTORY_URL = CFG.historyUrl || 'data/history.json';
  const TIMETABLE_URL = CFG.timetableUrl || 'data/timetable.json';   // cestovný poriadok na dnes (z GTFS ŽSR)
  const BOARD_PAGE = 20;
  const BOARD_AHEAD = 240;   // minúty dopredu na tabuli odchodov
  const REFRESH_MS = 2 * 60 * 1000;
  const STALE_MS = 10 * 60 * 1000;
  const STEP = `${REFRESH_MS / 60000} min`;   // „za 2 min“ pri zmenách súhrnu
  const PAGE = 15;
  const REDUCED_MOTION = matchMedia('(prefers-reduced-motion: reduce)');
  const PREVIEW = !!(window.VLAKY_DEMO || CFG.demo);   // náhľad s vymyslenými dátami
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
    { id: 'int', label: 'Medzinárodné', types: ['EC', 'EN', 'RJX', 'ICE', 'NJ', 'rj', 'rjx'] },
    { id: 'reg', label: 'Regionálne', types: ['Os', 'REX', 'Zr', 'Sp', 'MOs', 'RRR'] },
  ];
  // poradie „rýchliky a expresy prvé“
  const FAST_RANK = { r: 0, ex: 0, int: 1, reg: 2, other: 3 };
  const QUICK_STATIONS = CFG.quickStations?.length ? CFG.quickStations : ['Bratislava', 'Žilina', 'Košice', 'Banská Bystrica', 'Poprad-Tatry', 'Nové Zámky'];
  // malé „rj“/„rjx“ sú railjety ÖBB (medzinárodné), veľké „RJ“ je RegioJet: preto sa porovnáva aj presný tvar
  const TYPE_EXACT = new Map(GROUPS.flatMap(g => g.types.filter(t => t === t.toLowerCase()).map(t => [t, g.id])));
  const TYPE_GROUP = new Map(GROUPS.flatMap(g => g.types.filter(t => t !== t.toLowerCase()).map(t => [t.toUpperCase(), g.id])));
  const groupOf = t => TYPE_EXACT.get(String(t.type || '')) || TYPE_GROUP.get(String(t.type || '').toUpperCase()) || 'other';

  const $ = id => ROOT.getElementById(id);
  const $q = sel => ROOT.querySelector(sel);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmtMin = m => (m >= 60 ? `${Math.floor(m / 60)} h ${String(m % 60).padStart(2, '0')} min` : `${m} min`);
  const fmtTime = d => d.toLocaleTimeString('sk-SK', { hour: '2-digit', minute: '2-digit' });
  const fmtNum = (n, dp = 0) => n.toLocaleString('sk-SK', { minimumFractionDigits: dp, maximumFractionDigits: dp });
  // krátke názvy dopravcov: skratka ZSSK a bez právnej formy (a.s., s.r.o.)
  const CARRIER_SHORT = [[/železničná\s+spoločnosť\s+slovensko/i, 'ZSSK'], [/^regiojet/i, 'RegioJet'], [/^leo\s*express/i, 'Leo Express']];
  const shortCarrier = name => {
    const n = String(name || '').trim();
    const hit = CARRIER_SHORT.find(([re]) => re.test(n));
    return hit ? hit[1] : n.replace(/,?\s*(a\.\s?s\.|s\.\s?r\.\s?o\.|spol\.\s*s\s*r\.\s?o\.)$/i, '').trim();
  };
  const trainLabel = t => [t.type, t.number].filter(Boolean).join(' ') || t.id || 'Vlak';
  const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

  const state = {
    trains: [], history: [], prev: null, updated: null,
    mode: 'loading', cat: null, q: '', carrier: '', sort: 'fast',
    group: null, station: '', showPassed: false,
    tt: null, stats24: null, boardMode: 'dep', boardShown: BOARD_PAGE,
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
  // stanice: z cestovného poriadku, inak z vlastného zoznamu ST
  const stationRows = () => (state.tt?.stations?.length ? state.tt.stations : Object.values(ST));
  const findStation = name => {
    const n = norm(name).trim();
    return n ? stationRows().find(x => norm(x[0]) === n) || stationRows().find(x => norm(x[0]).startsWith(n)) : undefined;
  };
  const stationCoords = name => {
    const s = findStation(name);
    return s && s[1] != null ? [s[1], s[2]] : null;
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

  // bez cestovného poriadku: Príchody = vlaky smerom do stanice, Odchody = vlaky, ktoré stanicou pokračujú ďalej
  const atStation = t => {
    const r = relation(t);
    if (!(r === 'here' || r === 'upcoming' || (state.showPassed && r === 'passed'))) return false;
    const [from, to] = routeEnds(t.route);
    return state.boardMode === 'dep' ? !sameStation(to, state.station) : !sameStation(from, state.station);
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

  // chyba pri zobrazení nesmie nechať stránku navždy na „Načítavam…“: ukážeme ju, aby sa dala nahlásiť
  function showFatal(err) {
    console.error('Meškanie vlakov:', err);
    try {
      $('pill').dataset.state = 'error';
      $('pillText').textContent = 'Chyba zobrazenia';
      const n = $('notice');
      n.hidden = false;
      n.dataset.kind = 'error';
      n.textContent = `Tabuľu sa nepodarilo zobraziť (${(err && err.message) || err}). Skúste obnoviť stránku. Ak chyba pretrváva, pošlite tento text správcovi webu.`;
    } catch { /* stránka nemá ani základné prvky */ }
  }

  function load() { return loadData().catch(showFatal); }

  async function loadData() {
    $('refresh').classList.add('is-busy');
    const before = state.trains.length ? summarizeAll(state.trains) : null;
    try {
      if (FORCE_DEMO) throw new Error('demo');
      const data = await getJSON(DATA_URL);
      if (!Array.isArray(data.trains)) throw new Error('neplatný formát');
      state.trains = data.trains.map(t => ({ ...t, delay: Math.max(0, Number(t.delay) || 0), carrier: shortCarrier(t.carrier) }));
      state.stats24 = data.stats24 && data.stats24.all ? data.stats24 : null;
      const today = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Europe/Bratislava' }).format(new Date());
      if (!state.tt || state.tt.date !== today) {
        try {
          const tt = await getJSON(TIMETABLE_URL);
          if (Array.isArray(tt.trips)) {
            tt.trips.forEach(x => { x[4] = shortCarrier(x[4]); });
            state.tt = tt;
          }
        } catch { /* bez cestovného poriadku */ }
      }
      state.updated = new Date(data.updated);
      state.mode = Date.now() - state.updated > STALE_MS ? 'stale' : 'live';
      try { state.history = await getJSON(HISTORY_URL); } catch { state.history = []; }
    } catch {
      if (state.trains.length && state.mode !== 'demo') {
        state.mode = 'error';   // ponecháme posledné platné dáta
      } else {
        state.tt = demoTimetable();
        state.trains = demoTrains(state.tt);
        state.stats24 = demoStats24(state.tt);
        state.history = demoHistory(summarizeAll(state.trains));
        state.updated = new Date();
        state.mode = 'demo';
      }
    }
    if (state.station && !findStation(state.station)) state.station = '';
    else if (state.station) state.station = findStation(state.station)[0];
    const h = state.history;
    state.prev = h.length > 1 ? h[h.length - 2] : before;
    state.trains.forEach(t => { try { t.next = resolveNext(t); } catch { t.next = ''; } });
    state.nextAt = Date.now() + REFRESH_MS;
    setTimeout(() => $('refresh').classList.remove('is-busy'), 400);
    render();
  }

  /* ---------------- tabuľa odchodov ---------------- */

  const TZ_FMT = new Intl.DateTimeFormat('sk-SK', { timeZone: 'Europe/Bratislava', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
  const nowMin = () => {
    const p = Object.fromEntries(TZ_FMT.formatToParts(new Date()).map(x => [x.type, x.value]));
    return Number(p.hour) * 60 + Number(p.minute) + new Date().getSeconds() / 60;
  };
  const hm = m => { const v = ((Math.round(m) % 1440) + 1440) % 1440; return `${String(Math.floor(v / 60)).padStart(2, '0')}:${String(v % 60).padStart(2, '0')}`; };
  const digits = s => String(s || '').replace(/\D/g, '');

  const boardActive = () => !!(state.station && state.tt && state.tt.stations.some(x => norm(x[0]) === norm(state.station)));

  // spoje vybranej stanice z cestovného poriadku + aktuálne meškanie podľa čísla vlaku
  function boardEntries(mode) {
    const tt = state.tt, now = nowMin();
    const si = tt.stations.findIndex(x => norm(x[0]) === norm(state.station));
    const live = new Map(state.trains.map(t => [digits(t.number), t]));
    const sc = tt.stations[si];
    const out = [];
    tt.trips.forEach(([type, number, name, stops, carrier]) => {
      const i = stops.findIndex(x => x[0] === si);
      if (i < 0 || (mode === 'dep' && i === stops.length - 1) || (mode === 'arr' && i === 0)) return;
      const plan = mode === 'dep' ? (stops[i][2] ?? stops[i][1]) : (stops[i][1] ?? stops[i][2]);
      const t = live.get(digits(number)) || null;
      const delay = t ? t.delay : null;
      const exp = plan + (delay || 0);
      if (exp < now - 10 || plan > now + BOARD_AHEAD) return;

      // kde je vlak voči stanici
      let status;
      if (t && t.lat != null) {
        const path = stops.map(x => tt.stations[x[0]].slice(1)).filter(c => c[0] != null);
        const pos = [t.lat, t.lon];
        const here = sc[1] != null && km(pos, [sc[1], sc[2]]) < 1.5;
        const passed = !here && sc[1] != null && path.length > 1 && along(path, pos).s > along(path, [sc[1], sc[2]]).s;
        if (passed) status = mode === 'dep' ? 'gone' : 'arrived';
        else if (here) status = mode === 'dep' ? 'here' : 'arrived';
        else if (exp - now <= 2) status = 'now';
        else status = delay > 5 ? 'late' : 'ok';
      } else if (exp < now - 2) {
        status = mode === 'dep' ? 'gone' : 'arrived';
      } else {
        status = 'plan';
      }
      if ((status === 'gone' || status === 'arrived') && exp < now - 10) return;

      const names = idx => idx.map(k => tt.stations[stops[k][0]][0]);
      const rest = mode === 'dep'
        ? names(stops.slice(i + 1, -1).map((_, k) => i + 1 + k))
        : names(stops.slice(1, i).map((_, k) => 1 + k)).reverse();
      out.push({
        type, number, name, carrier: t?.carrier || carrier || '', plan, exp, delay, status, train: t,
        group: groupOf({ type }),
        origin: tt.stations[stops[0][0]][0],
        dest: tt.stations[stops[stops.length - 1][0]][0],
        via: rest,
      });
    });
    return out.sort((a, b) => a.plan - b.plan || a.exp - b.exp);
  }

  const STATUS = {
    ok: ['ok', '✓ Načas'], late: ['late', ''], now: ['now', 'Odchádza teraz'], here: ['here', 'Stojí v stanici'],
    gone: ['gone', 'Odišiel'], arrived: ['gone', 'Prišiel'], plan: ['plan', 'Ešte nevyrazil'],
  };

  function boardFiltered(mode) {
    const q = norm(state.q);
    return boardEntries(mode).filter(e =>
      (!state.group || e.group === state.group) &&
      (!state.carrier || e.carrier === state.carrier) &&
      (!q || norm(`${e.type} ${e.number} ${e.type}${e.number} ${e.name} ${e.origin} ${e.dest} ${e.via.join(' ')}`).includes(q)));
  }

  // celé názvy druhov vlakov — skratky nie každý pozná
  const TYPE_LABEL = {
    Os: 'Osobný vlak', REX: 'Regionálny expres', Zr: 'Zrýchlený vlak', Sp: 'Spešný vlak', R: 'Rýchlik', Ex: 'Expres',
    IC: 'InterCity', SC: 'SuperCity', EC: 'EuroCity', EN: 'Nočný EuroNight', RJ: 'RegioJet', LE: 'Leo Express', RJX: 'Railjet xpress',
    rj: 'Railjet', rjx: 'Railjet xpress', RRR: 'Regionálny vlak',
  };
  const flap = txt => [...txt].map(ch => (ch === ':' ? '<i>:</i>' : `<span>${ch}</span>`)).join('');
  // čas posledného hlásenia polohy: pole Cas zo ŽSR, inak čas v texte hlásenia („Odchod Žilina 12:31“)
  const TIME_RE = /\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/;
  function reportTime(t) {
    for (const v of [t.time, t.info]) {
      const str = String(v || '');
      const ms = str.match(/Date\((\d{10,})/) || (/^\d{4}-\d\d-\d\dT/.test(str) ? [0, Date.parse(str)] : null);
      if (ms && Number(ms[1])) return TZ_FMT.format(new Date(Number(ms[1])));
      const m = str.match(TIME_RE);
      if (m) return `${m[1].padStart(2, '0')}:${m[2]}`;
    }
    return '';
  }
  const routeEnds = route => {
    const parts = String(route || '').split(/\s+[-–—]\s+/).map(x => x.trim()).filter(Boolean);
    return parts.length > 1 ? [parts[0], parts[parts.length - 1]] : ['', parts[0] || ''];
  };

  function renderBoard() {
    const mode = state.boardMode, now = nowMin();
    const items = boardFiltered(mode);
    ROOT.querySelectorAll('.fids__tabs [data-mode]').forEach(b => b.setAttribute('aria-selected', String(b.dataset.mode === mode)));
    $('fidsTime').textContent = mode === 'dep' ? 'Odchod' : 'Príchod';
    $('fidsDir').textContent = mode === 'dep' ? 'Kam ide' : 'Odkiaľ prichádza';
    const ahead = items.filter(e => !['gone', 'arrived'].includes(e.status));
    const lateN = ahead.filter(e => e.delay > 5).length;
    $('fidsNote').textContent = `Do ${hm(now + BOARD_AHEAD)}: ${ahead.length} vlakov, ${lateN ? `${lateN} mešká` : 'všetky načas'}`;
    $('count').textContent = 'Časy podľa cestovného poriadku, meškanie podľa skutočnej polohy vlaku.';
    $('fidsRows').innerHTML = items.length ? items.slice(0, state.boardShown).map(e => {
      const [cls, label] = STATUS[e.status];
      const c = e.delay != null ? catOf(e.delay) : 0;
      const late = e.delay > 5 && !['gone', 'arrived'].includes(e.status);
      const stText = e.status === 'late' ? `⚠ Mešká ${fmtMin(e.delay)}`
        : e.status === 'now' && mode === 'arr' ? 'Prichádza teraz' : label;
      const big = mode === 'dep' ? e.dest : e.origin;
      const ends = mode === 'dep' ? e.origin : e.dest;
      const sub = norm(ends) === norm(state.station)
        ? (mode === 'dep' ? 'Začína v tejto stanici' : 'Končí v tejto stanici')
        : (mode === 'dep' ? `Ide z: ${e.origin}` : `Pokračuje do: ${e.dest}`);
      const via = viaTicker(mode === 'dep' ? 'Zastavuje' : 'Predtým', e.via);
      return `<li class="fl fl--${cls} ${late ? 'is-late' : ''}" data-type="${esc(e.type)}" data-num="${esc(e.number)}" tabindex="0" role="button" title="Zobraziť trasu a polohu vlaku"
          style="--k:var(--c${c})" aria-label="${esc(e.type)} ${esc(e.number)} ${mode === 'dep' ? 'do' : 'z'} ${esc(big)}, ${hm(e.plan)}, ${esc(stText)}">
        <span class="fl__time">${flap(hm(e.plan))}</span>
        <span class="fl__exp">${late ? `<span class="fl__lbl">nový čas</span>${hm(e.exp)}` : ''}</span>
        <span class="fl__train"><b class="tchip tchip--${e.group}">${esc(e.type)}</b><span class="fl__num">${esc(e.number)}</span>
          <small>${esc([TYPE_LABEL[e.type], e.name].filter(Boolean).join(' · '))}</small></span>
        <span class="fl__dir"><b class="fl__dest">${esc(big)}</b><small>${esc(sub)}</small>${via}</span>
        <span class="fl__st"><span class="st st--${cls}">${esc(stText)}</span>${late ? `<small class="st__new">nový čas <b>${hm(e.exp)}</b></small>` : ''}${e.carrier && e.carrier !== 'ZSSK' ? `<small>${esc(e.carrier)}</small>` : ''}</span>
      </li>`;
    }).join('') : `<li class="empty">V najbližších ${BOARD_AHEAD / 60} hodinách tu podľa filtra nič ${mode === 'dep' ? 'neodchádza' : 'neprichádza'}.</li>`;
    const rest = items.length - state.boardShown;
    $('fidsMore').hidden = rest <= 0;
    $('fidsMore').textContent = `Zobraziť ďalšie spoje (${rest})`;
  }

  /* ---------------- ďalšia stanica ---------------- */

  // kódy staníc z cestovného poriadku (GTFS stop_id / stop_code) -> názov
  // index sa zostaví vždy aj bez cestovného poriadku (vtedy prázdny); predtým tu bez neho zostal null
  let codeIdx = null, codeIdxFor;
  function codeIndex() {
    if (!codeIdx || codeIdxFor !== state.tt) {
      codeIdxFor = state.tt;
      codeIdx = new Map();
      (state.tt?.stations || []).forEach(([name, , , codes]) =>
        (codes || []).forEach(c => codeIdx.set(String(c).replace(/^0+/, ''), name)));
    }
    return codeIdx;
  }

  // ŽSR niekedy namiesto názvu ďalšej stanice pošle len jej číslo: preložíme ho na názov,
  // inak ďalšiu stanicu určíme z polohy vlaku a jeho trasy; keď to nejde, nezobrazíme nič
  function resolveNext(t) {
    const v = String(t.next || '').trim();
    if (v && !/^\d+$/.test(v)) return v;
    if (v) {
      const hit = codeIndex().get(v.replace(/^0+/, ''));
      if (hit) return hit;
    }
    const tt = state.tt, num = digits(t.number);
    const trip = tt && num && (tt.trips.find(x => digits(x[1]) === num && x[0] === t.type) || tt.trips.find(x => digits(x[1]) === num));
    if (trip) {
      const stops = trip[3].map(([si]) => { const [name, lat, lon] = tt.stations[si]; return { name, lat, lon }; });
      const w = trainWhere(t, stops);
      if (w?.at != null) return stops[w.at + 1]?.name || '';
      if (w?.between != null) return stops[w.between + 1].name;
    }
    return '';
  }

  /* ---------------- zastávky, ktoré sa striedajú ako na staničnej tabuli ---------------- */

  const VIA_PAGE = 3;          // zastávok naraz
  const VIA_MS = 4000;         // ako dlho je jedna skupina zastávok na tabuli

  // riadok „Zastavuje: A, B, C…“; dlhší zoznam sa po skupinách strieda (čítačka dostane celý zoznam)
  function viaTicker(label, names) {
    if (!names || !names.length) return '';
    const pages = [];
    for (let i = 0; i < names.length; i += VIA_PAGE) pages.push(names.slice(i, i + VIA_PAGE).join(', '));
    const full = `${label}: ${names.join(', ')}`;
    if (pages.length === 1 || REDUCED_MOTION.matches) return `<small class="via">${esc(full)}</small>`;
    return `<small class="via via--tick" aria-label="${esc(full)}" data-pages="${esc(JSON.stringify(pages))}" data-i="0">`
      + `${esc(label)}: <span class="via__txt" aria-hidden="true">${esc(pages[0])}</span>`
      + `<span class="via__pg" aria-hidden="true">1/${pages.length}</span></small>`;
  }

  function tickVia() {
    ROOT.querySelectorAll('.via--tick').forEach(el => {
      let pages;
      try { pages = JSON.parse(el.dataset.pages); } catch { return; }
      const i = (Number(el.dataset.i) + 1) % pages.length;
      el.dataset.i = String(i);
      const txt = el.querySelector('.via__txt'), pg = el.querySelector('.via__pg');
      txt.classList.add('is-out');
      setTimeout(() => {
        txt.textContent = pages[i];
        pg.textContent = `${i + 1}/${pages.length}`;
        txt.classList.remove('is-out');
      }, 250);
    });
  }

  // zastávky vlaku na trati od ďalšej stanice po cieľ (bez cieľa), podľa cestovného poriadku
  function upcomingStops(t) {
    const tt = state.tt, num = digits(t.number);
    if (!tt || !num) return [];
    const trip = tt.trips.find(x => digits(x[1]) === num && x[0] === t.type) || tt.trips.find(x => digits(x[1]) === num);
    if (!trip) return [];
    const stops = trip[3].map(([si]) => { const [name, lat, lon] = tt.stations[si]; return { name, lat, lon }; });
    const w = trainWhere(t, stops);
    const from = w ? (w.at != null ? w.at + 1 : w.between + 1) : 1;
    return stops.slice(from, -1).map(s => s.name);
  }

  /* ---------------- detail vlaku ---------------- */

  let openKey = null;

  function openTrain(type, number) {
    openKey = { type, number };
    renderTrain();
    const d = $('trainDlg');
    if (!d.open) d.showModal();
    d.querySelector('.td-close')?.focus();
    // zoznam zastávok posunieme tak, aby bolo vidno, kde je vlak
    const list = d.querySelector('.td-stops'), here = d.querySelector('.td-run, .td-stop.is-cur');
    if (list && here) list.scrollTop = here.offsetTop - list.clientHeight / 2 + here.offsetHeight / 2;
  }

  // kde je vlak: { at: index stanice } alebo { between: index stanice pred vlakom }
  function trainWhere(t, stops) {
    if (!t || t.lat == null || stops.length < 2 || stops.some(s => s.lat == null)) return null;
    const pos = [t.lat, t.lon];
    const near = stops.findIndex(s => km(pos, [s.lat, s.lon]) < 1.5);
    if (near >= 0) return { at: near };
    const a = along(stops.map(s => [s.lat, s.lon]), pos);
    return { between: Math.min(Math.floor(a.s), stops.length - 2) };
  }

  function renderTrain() {
    if (!openKey) return;
    const num = digits(openKey.number);
    const t = state.trains.find(x => digits(x.number) === num && x.type === openKey.type) || state.trains.find(x => digits(x.number) === num) || null;
    const tt = state.tt;
    const trip = tt && (tt.trips.find(x => digits(x[1]) === num && x[0] === openKey.type) || tt.trips.find(x => digits(x[1]) === num));
    const type = trip?.[0] || t?.type || openKey.type, name = trip?.[2] || t?.name || '';
    const carrier = t?.carrier || trip?.[4] || '';
    const stops = trip ? trip[3].map(([si, arr, dep]) => {
      const [nm, lat, lon] = tt.stations[si];
      return { name: nm, lat, lon, arr, dep };
    }) : [];
    const delay = t ? t.delay : null;
    const now = nowMin();
    const where = trainWhere(t, stops);
    const last = stops.length - 1;
    const passedIdx = !where ? (t ? -1 : stops.length && now > (stops[last].arr ?? 0) + 2 ? last : -1)
      : where.at != null ? where.at - 1 : where.between;
    const c = delay != null ? catOf(delay) : 0;

    // hlavná veta: kde je vlak
    let headline, detail = '';
    if (where?.at != null) {
      const st = stops[where.at].name;
      headline = where.at === 0 ? `Vlak stojí vo východiskovej stanici ${st}.`
        : where.at === last ? `Vlak dorazil do cieľovej stanice ${st}.`
        : `Vlak práve stojí v stanici ${st}.`;
      if (where.at < last) detail = `Ďalšia zastávka: ${stops[where.at + 1].name}, príchod asi o ${hm(stops[where.at + 1].arr + (delay || 0))}.`;
    } else if (where?.between != null) {
      const a = stops[where.between], b = stops[where.between + 1];
      headline = `Vlak práve prechádza úsekom ${a.name} → ${b.name}.`;
      detail = `Ďalšia zastávka: ${b.name}, príchod asi o ${hm(b.arr + (delay || 0))}.`;
    } else if (t) {
      headline = t.info ? `Posledná hlásená poloha: ${t.info}.` : 'Vlak je na trati, presná poloha nie je známa.';
      if (t.next) detail = `Ďalšia stanica: ${t.next}.`;
    } else if (stops.length && now < stops[0].dep) {
      headline = 'Vlak ešte nevyrazil.';
      detail = `Odchod z východiskovej stanice ${stops[0].name} o ${hm(stops[0].dep)}.`;
    } else if (stops.length) {
      headline = `Vlak už dorazil do cieľovej stanice ${stops[last].name}.`;
    } else {
      headline = 'O tomto vlaku teraz nemáme údaje.';
    }

    // vybraná stanica cestujúceho
    const mine = state.station ? stops.findIndex(s => norm(s.name) === norm(state.station)) : -1;
    let mineText = '';
    if (mine >= 0) {
      const s = stops[mine], plan = mine === last ? s.arr : (s.dep ?? s.arr);
      mineText = mine <= passedIdx
        ? `Stanicou ${s.name} už prešiel.`
        : where?.at === mine ? `Vlak je práve vo vašej stanici ${s.name}.`
        : `${mine === last ? `Do stanice ${s.name} príde` : `Zo stanice ${s.name} odchádza`} ${delay > 5 ? `asi o <b>${hm(plan + delay)}</b> (podľa cestovného poriadku ${hm(plan)})` : `o <b>${hm(plan)}</b>`}.`;
    }

    const delayBadge = delay == null ? '<span class="td-delay td-delay--none">Meškanie zatiaľ nie je známe</span>'
      : delay > 5 ? `<span class="td-delay" style="--k:var(--c${c})">⚠ Mešká ${fmtMin(delay)}</span>`
      : '<span class="td-delay td-delay--ok">✓ Ide načas</span>';

    const trainSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="13" rx="3"/><path d="M5 10h14M9 20l-2 2m8-2 2 2M8.5 13.5h.01M15.5 13.5h.01"/></svg>';
    const rows = stops.map((s, k) => {
      const plan = k === last ? s.arr : (s.dep ?? s.arr);
      const done = k <= passedIdx, cur = where?.at === k;
      const future = !done && !cur;
      const time = future && delay > 5 ? `<s>${hm(plan)}</s><b style="color:var(--c${c})">${hm(plan + delay)}</b>` : `<b>${hm(plan)}</b>`;
      const what = k === 0 ? 'odchod' : k === last ? 'príchod' : (s.dep != null && s.arr != null && s.dep - s.arr >= 5 ? `stojí ${s.dep - s.arr} min, príchod ${hm(s.arr)}` : '');
      const li = `<li class="td-stop ${done ? 'is-done' : ''} ${cur ? 'is-cur' : ''} ${k === mine ? 'is-mine' : ''}">
          <span class="td-dot" aria-hidden="true">${done ? '✓' : ''}</span>
          <span class="td-name">${esc(s.name)}${k === mine ? ' <em>vaša stanica</em>' : ''}${cur ? ' <em class="td-here-tag">vlak je tu</em>' : ''}${what ? `<small>${what}</small>` : ''}</span>
          <span class="td-time">${time}</span>
        </li>`;
      const marker = where?.between === k
        ? `<li class="td-run">${trainSvg}<span>Vlak je tu, na trati do stanice ${esc(stops[k + 1].name)}</span></li>` : '';
      return li + marker;
    }).join('');

    $('trainBody').innerHTML = `
      <div class="td-head">
        <div>
          <p class="td-kind"><b class="tchip tchip--${groupOf({ type })}">${esc(type)}</b> ${esc([TYPE_LABEL[type], carrier].filter(Boolean).join(' · '))}</p>
          <h2 id="tdTitle" class="td-title">${esc(type)} ${esc(openKey.number)}${name ? ` <span>${esc(name)}</span>` : ''}</h2>
          ${stops.length ? `<p class="td-route">${esc(stops[0].name)} → ${esc(stops[last].name)}</p>` : t?.route ? `<p class="td-route">${esc(t.route)}</p>` : ''}
        </div>
        <button type="button" class="btn td-close" data-close>Zavrieť</button>
      </div>
      <div class="td-now">
        ${delayBadge}
        <p class="td-headline">${esc(headline)}</p>
        ${detail ? `<p class="td-detail">${esc(detail)}</p>` : ''}
        ${mineText ? `<p class="td-mine">${mineText}</p>` : ''}
        ${t?.disruption ? `<p class="td-warn">Mimoriadnosť: ${esc(t.disruption)}</p>` : ''}
        ${t?.nad?.length ? `<p class="td-warn">Náhradná autobusová doprava: ${esc(t.nad.join(' – '))}</p>` : ''}
      </div>
      ${stops.length ? `<div><h3 class="td-h3 td-stops-h"><span>Trasa vlaku</span><span class="muted small">odchod</span></h3><ol class="td-stops">${rows}</ol></div>`
        : '<p class="muted">Zastávky vlaku nie sú k dispozícii, chýba cestovný poriadok.</p>'}
      <div class="td-actions">
        ${t && t.lat != null ? `<button type="button" class="btn" data-map="${esc(t.id)}">Ukázať na mape</button>` : ''}
        <button type="button" class="btn btn--primary" data-close>Zavrieť</button>
      </div>
      <p class="muted small">Poloha podľa posledného hlásenia ŽSR (${state.updated ? fmtTime(state.updated) : '—'}). Časy príchodov sú odhad: čas z cestovného poriadku + aktuálne meškanie.</p>`;
  }

  /* ---------------- render ---------------- */

  // každá časť stránky sa vykreslí samostatne: chyba v jednej (napr. v grafe) nezastaví ostatné
  const failed = [];
  function safe(part, fn) {
    try { fn(); } catch (err) {
      console.error(`Meškanie vlakov – ${part}:`, err);
      failed.push(`${part}: ${(err && err.message) || err}`);
    }
  }

  function render() {
    failed.length = 0;
    [['stav', renderStatus], ['zoznam staníc', fillStations], ['výber stanice', renderStation], ['filter druhov', renderSeg],
      ['štatistiky druhov', renderGroups], ['súhrn', renderKpis], ['rozdelenie', renderDist], ['graf', renderTrend],
      ['dopravcovia', renderCarriers], ['tabuľa', renderList], ['mapa', renderDots], ['detail vlaku', renderTrain]]
      .forEach(([part, fn]) => safe(part, fn));
    if (failed.length) {
      const n = $('notice');
      n.hidden = false;
      n.dataset.kind = 'error';
      n.textContent = `Časť stránky sa nepodarilo zobraziť (${failed.join('; ')}). Ostatné údaje sú v poriadku. Pošlite prosím tento text správcovi webu.`;
    }
  }

  function renderStatus() {
    const labels = { live: 'Živé dáta', stale: 'Oneskorené dáta', demo: 'Ukážkové dáta', error: 'Výpadok spojenia', loading: 'Načítavam…' };
    $('pill').dataset.state = state.mode;
    $('pillText').textContent = labels[state.mode];
    $('updated').textContent = state.updated ? `údaje z ${fmtTime(state.updated)}` : 'načítavam';
    const msg = {
      demo: PREVIEW
        ? '<b>Ukážka rozhrania.</b> Vlaky a meškania sú vymyslené, ale realistické. Naostro stránka každé 2 minúty načíta dáta z mapy polohy vlakov ŽSR.'
        : CFG.noDataNotice || '<b>Ukážkové dáta.</b> Súbor <code>data/trains.json</code> zatiaľ neexistuje. Spustite <code>python3 scripts/fetch_zsr.py --loop</code> alebo GitHub Action „Meškanie vlakov“.',
      stale: `Posledné dáta sú z ${state.updated && fmtTime(state.updated)}. Zdroj ŽSR sa pravdepodobne neaktualizuje.`,
      error: 'Nové dáta sa nepodarilo načítať. Zobrazujem posledný platný stav, ďalší pokus o 2 minúty.',
    }[state.mode];
    const n = $('notice');
    n.hidden = !msg;
    if (msg) { n.innerHTML = msg; n.dataset.kind = state.mode; }
  }

  function delta(cur, prev, { unit = '', dp = 0, upIsGood = false } = {}) {
    if (prev == null) return '&nbsp;';
    const d = cur - prev;
    if (Math.abs(d) < (dp ? 0.05 : 0.5)) return `bez zmeny za ${STEP}`;
    const good = upIsGood ? d > 0 : d < 0;
    return `<span class="${good ? 'good' : 'bad'}">${d > 0 ? '▲' : '▼'} ${fmtNum(Math.abs(d), dp)}${unit}</span> za ${STEP}`;
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
    $q('.side').classList.toggle('has-cat', state.cat !== null);
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
    const typesOf = g => g.types.filter(t => state.trains.some(x => x.type === t)).join(' · ') || g.types.slice(0, 3).join(' · ');
    const s24 = state.stats24;
    if (s24) {
      // za posledných 24 h celej siete (každá jazda vlaku raz, s posledným známym meškaním)
      const since = new Date(s24.since);
      const full = Date.now() - since > 23 * 3600e3;
      $('grpSince').textContent = full ? '· posledných 24 hodín, celá sieť' : `· od ${fmtTime(since)}, celá sieť`;
      const card24 = (id, label, types, s) => {
        const on = state.group === id || (!state.group && id === '');
        return `<button type="button" class="grp ${id ? '' : 'grp--all'}" data-group="${id}" aria-pressed="${on}" ${s && s.n ? '' : 'disabled'}>
          <span class="grp__head"><span class="grp__name">${label}</span><span class="grp__types">${types}</span></span>
          <span class="grp__v">${s && s.n ? fmtNum(s.onTime) + '<small>% načas</small>' : '<small>žiadne vlaky</small>'}</span>
          <span class="grp__bar">${(s?.c || []).map((n, i) => (n ? `<span style="flex-grow:${n};background:var(--c${i})"></span>` : '')).join('')}</span>
          <span class="grp__meta"><span><b>${fmtNum(s?.n || 0)}</b> jázd</span><span>${fmtAvg(s || { avg: 0 })}</span><span>max <b>${fmtMin(s?.max || 0)}</b></span></span>
        </button>`;
      };
      $('groups').innerHTML = card24('', 'Všetky', 'všetky druhy', s24.all) +
        GROUPS.map(g => card24(g.id, g.label, typesOf(g), s24.g?.[g.id])).join('');
      return;
    }
    $('grpSince').textContent = '· teraz na trati';
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
    const base = boardActive()
      ? boardEntries(state.boardMode).filter(e => !['gone', 'arrived'].includes(e.status)).map(e => ({ type: e.type }))
      : stationScope();
    const btn = (id, label, n) => `<button type="button" data-group="${id}" aria-pressed="${(state.group || '') === id}">${label}<span>${n}</span></button>`;
    $('groupSeg').innerHTML = btn('', 'Všetky', base.length) +
      GROUPS.map(g => btn(g.id, g.label, base.filter(t => groupOf(t) === g.id).length)).join('');
  }

  let stationsFilled = null;
  function fillStations() {
    const rows = stationRows();
    if (stationsFilled === rows) return;
    stationsFilled = rows;
    $('stationList').innerHTML = rows.map(x => x[0]).sort((a, b) => a.localeCompare(b, 'sk'))
      .map(n => `<option value="${esc(n)}"></option>`).join('');
  }

  function renderStation() {
    const st = state.station;
    $('station').value = st;
    $('stationClear').hidden = !st;
    $('quick').innerHTML = QUICK_STATIONS.map(n =>
      `<button type="button" data-station="${esc(n)}" aria-pressed="${!!st && findStation(n)?.[0] === st}">${esc(n)}</button>`).join('');
    $('thPos').textContent = 'Kam ide';
    $('boardTitle').textContent = st ? `Stanica ${st}` : 'Vlaky na trati';
    $('stationHelp').textContent = st
      ? 'Tabuľa ukazuje vlaky na najbližšie 4 hodiny. Inú stanicu vyberiete nižšie alebo napíšete do poľa.'
      : 'Vyberte stanicu a uvidíte, kedy z nej vlaky odchádzajú a koľko meškajú.';
    const board = boardActive();
    $q('.board').classList.toggle('is-fids', board);
    $('fids').hidden = !board;
    $('modeBar').hidden = !st;
    ROOT.querySelectorAll('.fids__tabs [data-mode]').forEach(b => b.setAttribute('aria-selected', String(b.dataset.mode === state.boardMode)));
    const hint = $('modeHint');
    hint.hidden = !st || board;
    if (st && !board) {
      hint.innerHTML = 'Presné časy odchodov a príchodov ukáže až cestovný poriadok, ktorý sa zatiaľ nenačítal. Zatiaľ vidíte vlaky na trati '
        + (state.boardMode === 'dep' ? 'smerom k stanici, ktoré budú pokračovať ďalej' : 'smerom do stanice')
        + '. Rýchliky nemusia v každej stanici zastaviť.'
        + (CFG.adminUrl ? ` <a href="${esc(CFG.adminUrl)}" target="_top">Nahrať cestovný poriadok (vidí len správca webu)</a>` : '');
      $('fidsNote').textContent = '';
    }
    const bar = $('stbar');
    bar.hidden = !st || board;
    if (!st || board) return;
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
    const match = findStation(name);
    state.station = match ? match[0] : '';
    state.boardShown = BOARD_PAGE;
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
    if (boardActive()) { renderBoard(); return; }
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
      const [from, to] = routeEnds(t.route);
      const stops = upcomingStops(t);
      const time = reportTime(t);
      // bez vlastného času hlásenia ukážeme, kedy sme polohu stiahli (údaje sa obnovujú každé 2 minúty)
      const dataTime = state.updated && !isNaN(state.updated) ? TZ_FMT.format(state.updated) : '--:--';
      const where = String(t.info || '').replace(TIME_RE, '').replace(/\s+/g, ' ').trim();
      // udalosť hlásenia („Odchod Trenčín“) a pri meškaní pôvodný čas: CasPlan zo ŽSR, inak skutočný čas mínus meškanie
      const event = time && where ? where.charAt(0).toLowerCase() + where.slice(1) : '';
      const planned = time && t.delay > 5
        ? (reportTime({ time: t.timePlan }) || hm(Number(time.slice(0, 2)) * 60 + Number(time.slice(3)) - t.delay)) : '';
      return `<li class="row ${sel ? 'sel' : ''}" data-id="${esc(t.id)}" tabindex="0" role="button" aria-expanded="${sel}"
          aria-label="${esc(trainLabel(t))}${to ? ' do ' + esc(to) : ''}, meškanie ${fmtMin(t.delay)}">
        <div class="row__air">
          <span class="ra__time"><span class="fl__time">${flap(planned || time || dataTime)}</span>
            <small>${time ? esc(event || 'posledné hlásenie') : 'poloha zistená'}</small>
            ${planned ? '<small>podľa cestovného poriadku</small>' : ''}</span>
          <span class="ra__delay"><span class="dly k${c}">${t.delay <= 0 ? '0' : '+' + t.delay}<small>min</small></span>${planned ? `<small class="ra__new">nový čas <b>${time}</b></small>` : ''}</span>
          <span class="ra__train"><b class="tchip tchip--${groupOf(t)}">${esc(t.type || 'Vlak')}</b><span class="fl__num">${esc(t.number)}</span>
            <small>${esc([TYPE_LABEL[t.type], t.name].filter(Boolean).join(' · '))}</small></span>
          <span class="ra__dir">${to ? `<b class="fl__dest">${esc(to)}</b>` : '<b class="muted">Cieľ neznámy</b>'}
            ${from ? `<small>Ide z: ${esc(from)}</small>` : ''}
            <small>${relBadge(t)}${event ? (t.next && !stops.length ? `Ďalej: ${esc(t.next)}` : '') : `${where ? `Posledná poloha: ${esc(where)}` : 'Poloha neznáma'}${t.next && !stops.length ? ` · ďalej ${esc(t.next)}` : ''}`}</small>
            ${viaTicker('Zastavuje', stops)}</span>
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
      $q(`.row[data-id="${CSS.escape(id)}"]`)?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    } else if (state.active) {
      const dot = $q(`.dot[data-id="${CSS.escape(id)}"]`);
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
      if (b) setStation(findStation(b.dataset.station)?.[0] === state.station ? '' : b.dataset.station);
    });
    $('station').addEventListener('input', e => {
      const v = e.target.value.trim();
      if (!v && state.station) setStation('');
      else if (stationRows().some(x => norm(x[0]) === norm(v))) setStation(v);
    });
    $('station').addEventListener('keydown', e => {
      if (e.key !== 'Enter') return;
      const v = norm(e.target.value.trim());
      const hit = v && (stationRows().find(x => norm(x[0]) === v) || stationRows().find(x => norm(x[0]).startsWith(v)));
      if (hit) { setStation(hit[0]); e.target.blur(); }
    });
    $('stationClear').addEventListener('click', () => { setStation(''); $('station').focus(); });
    $('stbar').addEventListener('change', e => {
      if (e.target.id === 'showPassed') { state.showPassed = e.target.checked; refreshAll(); }
    });
    $q('.fids__tabs').addEventListener('click', e => {
      const b = e.target.closest('[data-mode]');
      if (!b) return;
      state.boardMode = b.dataset.mode;
      state.boardShown = BOARD_PAGE;
      refreshAll();
    });
    $('fidsMore').addEventListener('click', () => { state.boardShown += BOARD_PAGE; renderBoard(); });
    $('fidsRows').addEventListener('click', e => {
      const li = e.target.closest('.fl[data-num]');
      if (li) openTrain(li.dataset.type, li.dataset.num);
    });
    $('fidsRows').addEventListener('keydown', e => {
      const li = e.target.closest('.fl[data-num]');
      if (li && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openTrain(li.dataset.type, li.dataset.num); }
    });
    $('trainDlg').addEventListener('click', e => {
      if (e.target === $('trainDlg') || e.target.closest('[data-close]')) $('trainDlg').close();
      const m = e.target.closest('[data-map]');
      if (m) {
        $('trainDlg').close();
        const t = state.trains.find(x => x.id === m.dataset.map);
        state.active = m.dataset.map;
        renderDots();
        $('map').scrollIntoView({ block: 'center', behavior: 'smooth' });
        const dot = $q(`.dot[data-id="${CSS.escape(m.dataset.map)}"]`);
        if (dot && t) setTimeout(() => showTip(t, dot), 400);
      }
    });
    $('trainDlg').addEventListener('close', () => { openKey = null; });
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
    $('bigText').addEventListener('click', () => setBig(!HOST.classList.contains('big')));
    const openById = id => { const t = state.trains.find(x => x.id === id); if (t) openTrain(t.type, t.number); };
    $('rows').addEventListener('click', e => { const li = e.target.closest('.row'); if (li) openById(li.dataset.id); });
    $('rows').addEventListener('keydown', e => {
      if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('row')) { e.preventDefault(); openById(e.target.dataset.id); }
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
      openById(d.dataset.id);
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && Date.now() >= state.nextAt) load(); });
  }

  function tick() {
    const left = Math.max(0, state.nextAt - Date.now()), s = Math.ceil(left / 1000);
    const [hh, mm] = hm(nowMin()).split(':');
    if ($('clockH').textContent !== hh) $('clockH').textContent = hh;
    if ($('clockM').textContent !== mm) $('clockM').textContent = mm;
    // dvojbodka bliká raz za sekundu ako na staničných hodinách (pri obmedzení pohybu svieti stále)
    $('colon').classList.toggle('is-off', !REDUCED_MOTION.matches && new Date().getSeconds() % 2 === 1);
    $('countdown').textContent = s > 60 ? `${Math.ceil(s / 60)} min` : `${s} s`;
    if (left === 0 && !document.hidden) { state.nextAt = Date.now() + REFRESH_MS; load(); }
    const minute = Math.floor(Date.now() / 60000);
    if (minute !== lastMinute) { lastMinute = minute; if (boardActive()) safe('tabuľa', renderBoard); }
  }
  let lastMinute = Math.floor(Date.now() / 60000);

  /* ---------------- ukážkové dáta ---------------- */

  // ukážkový cestovný poriadok: linky, takt a číslovanie spojov
  const MAIN = 'BA TT LE TN PU PB ZA VR RK LM PP SN KY KE';
  const MAIN_NAMES = ['Tatran', 'Kriváň', 'Šarišan', 'Chopok', 'Gerlach', 'Hornád', 'Spiš', 'Rysy', 'Dunaj', 'Torysa', 'Ďumbier', 'Poľana', 'Javorina', 'Inovec'];
  const DEMO_SERVICES = [
    // Bratislava – Košice: striedanie Ex / R / IC každú hodinu
    { l: MAIN, from: 305, to: 1085, every: 60, kind: i => ['Ex', 'R', 'IC'][i % 3], speed: k => (k === 'R' ? 72 : 82), dwell: 2,
      num: (i, back, k) => (k === 'IC' ? 500 : 600) + 2 * i + (back ? 0 : 1), name: (i, k) => (k === 'IC' ? '' : MAIN_NAMES[i % MAIN_NAMES.length]) },
    { l: MAIN, from: 350, to: 1060, every: 240, kind: () => 'RJ', speed: () => 82, dwell: 2, num: (i, back) => 1010 + 2 * i + (back ? 0 : 1), carrier: 'RegioJet' },
    { l: MAIN, from: 460, to: 1000, every: 480, kind: () => 'LE', speed: () => 82, dwell: 2, num: (i, back) => 1350 + 2 * i + (back ? 0 : 1), carrier: 'Leo Express' },
    { l: 'BA TT LE TN', from: 300, to: 1320, every: 60, kind: () => 'REX', speed: () => 62, dwell: 1, num: (i, back) => 1800 + 2 * i + (back ? 0 : 1) },
    { l: 'ZA VR RK LM PP', from: 290, to: 1290, every: 60, kind: () => 'Os', speed: () => 52, dwell: 1, num: (i, back) => 3300 + 2 * i + (back ? 0 : 1) },
    { l: 'BA GA NZ ST', from: 400, to: 1160, every: 120, kind: () => 'EC', speed: () => 80, dwell: 2, num: (i, back) => 270 + 2 * i + (back ? 0 : 1),
      name: i => ['Hungaria', 'Metropolitan', 'Avala', 'Jaroslav Hašek', 'Slovan', 'Pannonia', 'Bathory'][i % 7] },
    { l: 'BA GA NZ', from: 290, to: 1330, every: 30, kind: () => 'Os', speed: () => 55, dwell: 1, num: (i, back) => 2000 + 2 * i + (back ? 0 : 1) },
    { l: 'NZ LV ZV BB', from: 330, to: 1170, every: 120, kind: () => 'R', speed: () => 66, dwell: 2, num: (i, back) => 830 + 2 * i + (back ? 0 : 1), name: i => ['Hron', 'Sitno', 'Vepor'][i % 3] },
    { l: 'ZV LC RS RV KE', from: 310, to: 1150, every: 180, kind: () => 'R', speed: () => 62, dwell: 2, num: (i, back) => 930 + 2 * i + (back ? 0 : 1), name: i => ['Rimava', 'Gemeran', 'Novohrad'][i % 3] },
    { l: 'BA SE KU', from: 300, to: 1320, every: 30, kind: () => 'Os', speed: () => 52, dwell: 1, num: (i, back) => 2500 + 2 * i + (back ? 0 : 1) },
    { l: 'ZA CA', from: 380, to: 1200, every: 180, kind: () => 'EC', speed: () => 70, dwell: 2, num: (i, back) => 120 + 2 * i + (back ? 0 : 1), name: i => ['Beskydy', 'Praha', 'Košičan', 'Ostravan', 'Morava'][i % 5] },
    { l: 'ZA CA', from: 300, to: 1320, every: 30, kind: () => 'Os', speed: () => 48, dwell: 1, num: (i, back) => 3400 + 2 * i + (back ? 0 : 1) },
    { l: 'KE MI HE', from: 320, to: 1250, every: 120, kind: () => 'R', speed: () => 64, dwell: 2, num: (i, back) => 1940 + 2 * i + (back ? 0 : 1), name: i => ['Zemplín', 'Laborec'][i % 2] },
    { l: 'KE KY PO BJ', from: 300, to: 1320, every: 30, kind: i => (i % 2 ? 'Os' : 'REX'), speed: k => (k === 'REX' ? 60 : 50), dwell: 1, num: (i, back, k) => (k === 'REX' ? 1700 : 8400) + 2 * i + (back ? 0 : 1) },
    { l: 'KE CT', from: 330, to: 1290, every: 60, kind: () => 'Os', speed: () => 55, dwell: 1, num: (i, back) => 8600 + 2 * i + (back ? 0 : 1) },
    { l: 'KE CT', from: 560, to: 1000, every: 440, kind: () => 'EN', speed: () => 60, dwell: 3, num: (i, back) => 144 + 2 * i + (back ? 0 : 1), name: () => 'Kyiv Express' },
    { l: 'LE NI', from: 300, to: 1320, every: 30, kind: () => 'Os', speed: () => 48, dwell: 1, num: (i, back) => 4100 + 2 * i + (back ? 0 : 1) },
    { l: 'NI TO PD', from: 330, to: 1230, every: 180, kind: () => 'R', speed: () => 58, dwell: 2, num: (i, back) => 1630 + 2 * i + (back ? 0 : 1), name: i => ['Ponitrie', 'Bojnice'][i % 2] },
    { l: 'NZ KN', from: 300, to: 1320, every: 60, kind: () => 'Os', speed: () => 48, dwell: 1, num: (i, back) => 4400 + 2 * i + (back ? 0 : 1) },
    { l: 'PO VK HE', from: 300, to: 1260, every: 60, kind: () => 'Os', speed: () => 48, dwell: 1, num: (i, back) => 8700 + 2 * i + (back ? 0 : 1) },
    { l: 'BB VR', from: 310, to: 1250, every: 120, kind: i => (i % 2 ? 'Os' : 'R'), speed: k => (k === 'R' ? 58 : 48), dwell: 1, num: (i, back, k) => (k === 'R' ? 1750 : 5300) + 2 * i + (back ? 0 : 1), name: (i, k) => (k === 'R' ? 'Turiec' : '') },
  ];
  const CARRIER = { RJ: 'RegioJet', LE: 'Leo Express' };
  const ISSUES = ['Porucha rušňa', 'Obmedzenie rýchlosti na trati', 'Čakanie na prípoj', 'Porucha zabezpečovacieho zariadenia', 'Mimoriadna udalosť na trati', 'Neskorý príchod súpravy'];

  function rng(seed) { return () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296); }
  const hash = str => [...str].reduce((h, c) => (Math.imul(h, 31) + c.charCodeAt(0)) >>> 0, 7);

  function demoDelay(r) {
    const x = r();
    if (x < 0.7) return Math.floor(r() * 6);
    if (x < 0.86) return 6 + Math.floor(r() * 10);
    if (x < 0.94) return 16 + Math.floor(r() * 15);
    if (x < 0.985) return 31 + Math.floor(r() * 30);
    return 61 + Math.floor(r() * 75);
  }

  // rovnaký formát ako data/timetable.json zo skriptu
  function demoTimetable() {
    const keys = Object.keys(ST), idx = Object.fromEntries(keys.map((k, i) => [k, i]));
    const trips = [];
    DEMO_SERVICES.forEach(sv => {
      const line = sv.l.split(' ');
      [false, true].forEach(back => {
        const seq = back ? line.slice().reverse() : line;
        for (let i = 0, t0 = sv.from + (back ? 7 : 0); t0 <= sv.to; i++, t0 += sv.every) {
          const kind = sv.kind(i), speed = sv.speed(kind);
          let t = t0;
          const stops = seq.map((k, j) => {
            if (j > 0) t += Math.max(3, Math.round((km(ST[seq[j - 1]].slice(1), ST[k].slice(1)) * 1.25 / speed) * 60));
            const arr = j === 0 ? null : t;
            if (j > 0 && j < seq.length - 1) t += sv.dwell;
            return [idx[k], arr, j === seq.length - 1 ? null : t];
          });
          trips.push([kind, String(sv.num(i, back, kind)), sv.name ? sv.name(i, kind) : '', stops, sv.carrier || CARRIER[kind] || 'ZSSK']);
        }
      });
    });
    return { date: 'demo', stations: keys.map(k => ST[k].slice()), trips };
  }

  // vlaky práve na trati podľa cestovného poriadku a vymysleného meškania
  function demoTrains(tt) {
    const now = nowMin(), slot = Math.floor(Date.now() / REFRESH_MS);
    const out = [];
    tt.trips.forEach(([type, number, name, stops, carrier]) => {
      const r = rng(hash(type + number));
      const base = demoDelay(r);
      let delay = base > 5 ? Math.max(6, base + Math.round(Math.sin(slot * 0.7 + hash(number)) * 3)) : base;
      if (['EC', 'EN'].includes(type) && r() < 0.55) delay += 8 + Math.floor(r() * 22);   // prípoje zo zahraničia
      if (['Ex', 'IC'].includes(type) && delay > 30) delay = Math.round(delay / 2);
      const te = now - delay;
      const first = stops[0][2], last = stops[stops.length - 1][1];
      if (te < first || te > last) return;
      const st = k => tt.stations[stops[k][0]];
      let lat, lon, info, next;
      for (let k = 0; k < stops.length - 1; k++) {
        const arr = stops[k][1] ?? stops[k][2], dep = stops[k][2], nextArr = stops[k + 1][1];
        if (te >= arr && te <= dep) {
          [, lat, lon] = st(k); info = `Príchod ${st(k)[0]} ${hm(arr + delay)}`; next = st(k + 1)[0]; break;
        }
        if (te > dep && te < nextArr) {
          const f = (te - dep) / (nextArr - dep), a = st(k), b = st(k + 1);
          lat = a[1] + (b[1] - a[1]) * f; lon = a[2] + (b[2] - a[2]) * f;
          info = `Odchod ${a[0]} ${hm(dep + delay)}`; next = b[0]; break;
        }
      }
      if (lat == null) { const z = st(stops.length - 1); [, lat, lon] = z; info = `Príchod ${z[0]} ${hm(last + delay)}`; next = ''; }
      out.push({
        id: type + number, type, number, name,
        route: `${st(0)[0]} – ${st(stops.length - 1)[0]}`,
        carrier, delay, delayText: '', info, next,
        lat: +lat.toFixed(4), lon: +lon.toFixed(4),
        path: stops.map((_, k) => st(k).slice(1)),
        disruption: delay > 30 ? ISSUES[hash(number) % ISSUES.length] : '',
        note: '', nad: [],
      });
    });
    return out;
  }

  // ukážkové štatistiky za 24 h: každý spoj cestovného poriadku raz (dnešné doteraz + včerajšie od tohto času)
  function demoStats24(tt) {
    const runs = tt.trips.map(([type, number]) => {
      const r = rng(hash(type + number + 'day'));
      let d = demoDelay(r);
      if (['EC', 'EN'].includes(type) && r() < 0.5) d += 8 + Math.floor(r() * 20);
      return { g: groupOf({ type }), d, m: d + (d > 5 ? Math.floor(r() * 6) : 0) };
    });
    const st = list => {
      const n = list.length, c = [0, 0, 0, 0, 0];
      list.forEach(x => c[catOf(x.d)]++);
      return { n, avg: n ? list.reduce((a, x) => a + x.d, 0) / n : 0, onTime: n ? (100 * c[0]) / n : 0, max: list.reduce((a, x) => Math.max(a, x.m), 0), c };
    };
    const g = {};
    GROUPS.forEach(gr => { g[gr.id] = st(runs.filter(x => x.g === gr.id)); });
    return { since: new Date(Date.now() - 24 * 3600e3).toISOString(), all: st(runs), g };
  }

  function demoHistory(now) {
    const r = rng(11), end = Date.now(), pts = [];
    for (let i = 719; i >= 0; i--) {
      const t = new Date(end - i * 2 * 60000), hr = t.getHours() + t.getMinutes() / 60;
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

  state.station = store.get('station');   // overí sa po načítaní zoznamu staníc
  state.group = GROUPS.some(g => g.id === store.get('group')) ? store.get('group') : null;
  function setBig(on) {
    HOST.classList.toggle('big', on);
    $('bigText').setAttribute('aria-pressed', String(on));
    store.set('big', on ? '1' : '');
  }
  setBig(store.get('big') === '1');
  drawBase();
  bind();
  load();
  setInterval(tick, 1000);
  setInterval(() => safe('zastávky', tickVia), VIA_MS);
  tick();
})();
