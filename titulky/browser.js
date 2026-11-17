"use strict";
const TF_URL = "https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.7.6";
const LANGS = { sk: "slovak", cs: "czech", en: "english" };
const DEMO = false; // true v ukážke na webe: vzorové titulky a výsledok sa prehrá priamo na stránke
const MOCK = DEMO || new URLSearchParams(location.search).has("mock"); // len na skúšanie bez modelu
const SAMPLE = ["Ahoj, vitajte pri ukážke aplikácie.", "Nahráte video a titulky sa vytvoria automaticky.", "Vyberiete štýl z ponuky,", "zmeníte farbu textu, font alebo podklad,", "a práve vyslovené slovo sa zvýrazní."];

const $ = (id) => document.getElementById(id);
const OUT = { "9:16": [1080, 1920], "1:1": [1080, 1080], "16:9": [1920, 1080] };
// Kvalita exportu: Originál = rozlíšenie nahratého videa, najviac 1080p; 4K len ak bolo nahraté video v 4K;
// Menší súbor = 720p. Nad 720p sa video nezväčšuje viac, než koľko pixelov má originál vo zvolenom výreze.
const QUALITY_CAP = { orig: 1080, "4k": 2160, small: 720 };
const is4kSource = () => Math.min(video.videoWidth || 0, video.videoHeight || 0) >= 2000;
function exportSize(aspect, q = state.quality) {
  const [ow, oh] = OUT[aspect], r = ow / oh, z = state.crop[aspect]?.zoom || 1;
  const vw = video.videoWidth || ow, vh = video.videoHeight || oh;
  const visW = Math.min(vw, vh * r) / z, visH = visW / r; // koľko pixelov originálu pokrýva výrez
  const cap = q === "4k" && !is4kSource() ? 1080 : QUALITY_CAP[q] || 1080;
  const short = Math.min(cap, Math.max(720, Math.round(Math.min(visW, visH)))); // aspoň 720p, aby titulky ostali ostré
  const k = short / Math.min(ow, oh);
  return { W: Math.round(ow * k / 2) * 2, H: Math.round(oh * k / 2) * 2 };
}
function refreshQuality() {
  const has4k = is4kSource();
  if (state.quality === "4k" && !has4k) state.quality = "orig";
  document.querySelector('#quality [data-q="4k"]').hidden = !has4k;
  document.querySelectorAll("#quality .opt").forEach((b) => { const on = b.dataset.q === state.quality; b.classList.toggle("on", on); b.setAttribute("aria-checked", String(on)); });
  const src = Math.min(video.videoWidth || 0, video.videoHeight || 0);
  $("qOrig").textContent = src ? (Math.min(src, 1080) + "p") : "do 1080p";
  const { W, H } = exportSize(state.aspect);
  $("qualityInfo").textContent = `Formát ${state.aspect}: ${W}×${H} px, snímky za sekundu ako v origináli.` +
    (state.quality === "4k" ? " 4K export trvá dlhšie a na slabších zariadeniach môže sekať." : "") +
    ((state.crop[state.aspect]?.zoom || 1) > 1.05 && state.quality !== "small" ? " Priblížený výrez používa menej pixelov originálu, obraz môže byť menej ostrý." : "");
}
const FONTS = ["Inter", "Montserrat", "Poppins", "Bebas Neue", "Roboto Slab", "Arial", "Georgia"];
const PRESETS = [
  { hl: "#ffe066", id: "modern",  name: "Modern",  font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 60, size: 6.5, bold: true },
  { hl: "#ffe066", id: "klasik",  name: "Klasik",  font: "Arial", color: "#ffffff", bg: "#000000", bgOpacity: 0, size: 6, bold: true },
  { hl: "#ffffff", id: "kino",    name: "Kino",    font: "Montserrat", color: "#ffe066", bg: "#000000", bgOpacity: 0, size: 6.5, bold: true },
  { hl: "#ffe066", id: "vyrazny", name: "Výrazný", font: "Poppins", color: "#ffffff", bg: "#6c5cff", bgOpacity: 100, size: 7, bold: true },
  { hl: "#ffe066", id: "jemny",   name: "Jemný",   font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 35, size: 5.5, bold: false },
  { hl: "#6c5cff", id: "svetly",  name: "Svetlý",  font: "Montserrat", color: "#111111", bg: "#ffffff", bgOpacity: 90, size: 6, bold: true },
  { hl: "#00b8fc", id: "modry", name: "PS blue", font: "Montserrat", italic: true, weight: 800, color: "#ffffff", stroke: "#00007d", strokeW: 0.2, lineH: 1.35, bg: "#000000", bgOpacity: 0, size: 6.8, bold: true, highlight: true, y: 0.5, position: "middle" },
  { hl: "#ffe066", id: "dynamic", name: "Dynamický", dynamic: true, roam: true, caps: true, y: 0.6, font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 0, size: 8, bold: true },
];
const state = { aspect: "9:16", style: { ...PRESETS[0], preset: "modern", highlight: false, on: true },
  segments: [], file: null, url: null, duration: 0, out: null, results: {}, busyExport: false, playable: null, job: 0, pos: {}, audio: null,
  spk: [], spkStyle: {}, cur: 1, tab: "subs", quality: "orig", parts: [], partSel: 0, crop: {}, tool: "captions",
  waveform: null, tlZoomVal: 1, tlPxPerSec: 100, tlDragging: false, collapsedSpk: new Set(), activeSeg: -1, segFilter: "", sel: new Set(), selAnchor: -1 };
const video = $("video");
// Menu krokov je stále hore. Po nahratí videa sa ním prepína medzi časťami editora:
// Titulkovanie (text, rečníci, štýl, poloha titulkov), Úprava (formát, výrez a posun videa), Export.
let curView = "upload";
const hasProject = () => !!state.out;
function updateTabs() {
  const active = curView === "upload" ? "upload" : curView === "busy" ? "subs" : state.tab;
  document.querySelectorAll("#steps .step").forEach((b) => {
    b.classList.toggle("on", b.dataset.tab === active);
    b.disabled = b.dataset.tab !== "upload" && (curView === "busy" || !hasProject());
  });
}
const show = (view) => {
  curView = view;
  for (const v of ["upload", "busy", "editor"]) $("view-" + v).hidden = v !== view;
  $("panel").hidden = view !== "editor";
  if (view === "editor") applyTab(); else updateTabs();
};
function applyTab() { // zobrazí v editore len to, čo patrí k zvolenému kroku
  const t = state.tab;
  $("pane-subs").hidden = t !== "subs"; $("pane-cut").hidden = t !== "cut"; $("pane-edit").hidden = t !== "edit"; $("pane-export").hidden = t !== "export";
  $("timelineCard").hidden = t === "export"; $("textCard").hidden = t !== "subs";
  $("timelineCard").classList.toggle("video-only", t === "edit"); // v Úprave len prehrávanie a posun v čase, bez titulkov
  $("timelineCard").classList.toggle("cut-mode", t === "cut"); // v Strihu časti videa a prechody namiesto titulkov
  if (t === "cut") renderCutUi();
  if (t !== "subs" && state.sel && state.sel.size) clearSel();
  state.tool = t === "edit" ? "crop" : t === "subs" ? "captions" : "none";
  $("pvHint").textContent = t === "cut" ? "Prehrávanie preskakuje vystrihnuté časti a ukazuje prechody tak, ako budú v exporte."
    : t === "edit" ? "Ťahaním v náhľade posúvaš video, kolieskom myši alebo posuvníkom ho približuješ. Výrez platí pre zvolený formát."
    : t === "subs" ? "Titulky presunieš ťahaním v náhľade. Posunie sa poloha rečníka, ktorého titulok práve vidíš."
    : "Export beží v reálnom čase, nechaj kartu otvorenú.";
  if (state.out) markActive();
  updateTabs();
}
function setTab(t) {
  if (t === "upload") { show("upload"); return; }
  if (!hasProject()) return;
  state.tab = t; show("editor");
}
const fmtTime = (s) => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, "0")}`;
const rgbOf = (hex) => [1, 3, 5].map((n) => parseInt(hex.slice(n, n + 2), 16)).join(",");
const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
const segmentAt = (t) => state.segments.findIndex((s) => t >= s.start && t < s.end);
// Priebeh sa skladá z troch krokov, každý má svoj podiel na celkových percentách:
// čítanie zvuku (0 – 12 %), model (12 – 55 %, skáče hneď na 55 % ak je už uložený), rozpoznanie reči (55 – 100 %).
const PHASES = { audio: [0, 12], model: [12, 55], asr: [55, 100] };
const phasePct = (phase, frac) => { const [a, b] = PHASES[phase]; return a + (b - a) * clamp(frac, 0, 1); };
let busyPct = 0;
const setBusy = (title, text = "", pct = null) => {
  show("busy"); $("busy-title").textContent = title || "Spracúvam…"; $("busy-text").textContent = text;
  if (pct !== null) busyPct = Math.max(busyPct, pct); // percentá nikdy neklesnú, nech to nevyzerá, že sa appka vrátila
  $("busy-fill").style.width = Math.round(busyPct) + "%";
  $("busy-pct").textContent = Math.round(busyPct) + " %";
};
const resetBusy = () => { busyPct = 0; };
const showError = (msg) => { show("upload"); const e = $("upload-error"); e.textContent = msg; e.hidden = false; };

// ---------- slová a ich časy ----------
function estimateWords(text, start, end) {
  const ws = text.trim().split(/\s+/).filter(Boolean), total = ws.reduce((a, w) => a + w.length, 0) || 1;
  let t = start;
  return ws.map((w) => { const d = (end - start) * w.length / total; const o = { w, start: t, end: t + d }; t += d; return o; });
}
// Slová titulku: ak sa text nezmenil v počte slov, platia pôvodné časy, inak sa čas rozdelí podľa dĺžky slov.
function wordsFor(seg) {
  const ws = seg.text.trim().split(/\s+/).filter(Boolean);
  if (seg.words && seg.words.length === ws.length) return seg.words.map((o, i) => ({ ...o, w: ws[i] }));
  return estimateWords(seg.text, seg.start, seg.end);
}
function groupWords(words) {
  const segs = []; let cur = [];
  const flush = () => { if (cur.length) { segs.push({ start: cur[0].start, end: cur[cur.length - 1].end, text: cur.map((o) => o.w).join(" "), words: cur }); cur = []; } };
  for (const o of words) {
    if (cur.length) {
      const chars = cur.reduce((a, x) => a + x.w.length + 1, 0) + o.w.length;
      if (chars > 32 || o.end - cur[0].start > 4 || o.start - cur[cur.length - 1].end > 0.8) flush();
    }
    cur.push(o);
    if (/[.!?…]$/.test(o.w)) flush();
  }
  flush();
  segs.forEach((g, i) => { if (i + 1 < segs.length) g.end = Math.min(g.end, segs[i + 1].start); g.end = Math.max(g.end, g.start + 0.3); });
  return segs;
}

// ---------- rozpoznanie reči (Whisper v prehliadači) ----------
function toMono(buf) {
  const n = buf.numberOfChannels, out = new Float32Array(buf.length);
  for (let c = 0; c < n; c++) { const d = buf.getChannelData(c); for (let i = 0; i < out.length; i++) out[i] += d[i] / n; }
  return out;
}
const decodeBuffer = async (arrayBuffer) => {
  const ctx = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 16000 });
  try { return toMono(await ctx.decodeAudioData(arrayBuffer)); } finally { ctx.close(); }
};
// Záloha: ak prehliadač nevie rozbaliť zvuk zo súboru, zvuk sa nahrá pri prehrávaní videa (trvá dĺžku videa).
async function captureAudio(url) {
  const tv = document.createElement("video"); tv.src = url; tv.playsInline = true;
  await new Promise((res, rej) => { tv.onloadedmetadata = res; tv.onerror = () => rej(new Error("play")); });
  const ac = new (window.AudioContext || window.webkitAudioContext)();
  const dest = ac.createMediaStreamDestination();
  ac.createMediaElementSource(tv).connect(dest);
  const rec = new MediaRecorder(dest.stream), chunks = [];
  rec.ondataavailable = (e) => e.data.size && chunks.push(e.data);
  const stopped = new Promise((r) => (rec.onstop = r));
  rec.start(); await ac.resume(); await tv.play();
  const iv = setInterval(() => setBusy("Čítam zvuk…", `Prehliadač číta zvuk v reálnom čase: ${fmtTime(tv.currentTime)} z ${fmtTime(tv.duration)}`, phasePct("audio", tv.currentTime / tv.duration)), 400);
  await new Promise((r) => (tv.onended = r));
  clearInterval(iv); rec.stop(); await stopped; ac.close();
  return decodeBuffer(await new Blob(chunks).arrayBuffer());
}
async function decodeAudio(file) {
  try { return await decodeBuffer(await file.arrayBuffer()); } catch { /* skúsi sa záloha */ }
  try { setBusy("Čítam zvuk…", "Prehliadač nevie rozbaliť zvuk priamo, číta ho pri prehrávaní."); return await captureAudio(state.url); }
  catch { throw new Error("Zo súboru sa nepodarilo prečítať zvuk. Súbor je asi poškodený, bez zvuku alebo má zvukový kodek, ktorý prehliadač nepozná. Skús ho previesť do MP4 (H.264 + AAC)."); }
}
// Rozdelí dlhý úsek textu na titulky (najviac 32 znakov), čas rozdelí podľa dĺžky textu.
function splitChunk(text, start, end) {
  const words = text.trim().split(/\s+/).filter(Boolean), groups = [];
  let cur = [];
  const len = (g) => g.join(" ").length;
  for (const w of words) {
    if (cur.length && len(cur) + 1 + w.length > 32) { groups.push(cur); cur = []; }
    cur.push(w);
    if (/[.!?…]$/.test(w)) { groups.push(cur); cur = []; }
  }
  if (cur.length) groups.push(cur);
  const total = groups.reduce((a, g) => a + len(g), 0) || 1;
  let t = start;
  return groups.map((g) => { const d = (end - start) * len(g) / total; const s = { start: t, end: t + d, text: g.join(" ") }; t += d; return s; });
}
let asr = null, asrId = null, tf = null;
// Model sa ukladá do vlastnej trvalej pamäte (IndexedDB), nie len do vyrovnávacej pamäte prehliadača,
// aby sa pri ďalšom nahratí videa (aj po obnovení stránky) nesťahoval znova.
const MODEL_KEY = "titulky.model";
function openModelDb() {
  return new Promise((res, rej) => {
    const r = indexedDB.open("titulky-model-cache", 1);
    r.onupgradeneeded = () => r.result.createObjectStore("files");
    r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error);
  });
}
const keyOf = (req) => (typeof req === "string" ? req : req.url);
const idbCache = {
  async match(req) {
    const db = await openModelDb();
    return new Promise((res) => {
      const r = db.transaction("files").objectStore("files").get(keyOf(req));
      r.onsuccess = () => res(r.result ? new Response(r.result.blob, { headers: r.result.headers }) : undefined);
      r.onerror = () => res(undefined);
    });
  },
  async put(req, response) {
    const blob = await response.blob(), headers = {};
    response.headers.forEach((v, k) => { headers[k] = v; });
    const db = await openModelDb();
    return new Promise((res, rej) => {
      const tx = db.transaction("files", "readwrite");
      tx.objectStore("files").put({ blob, headers }, keyOf(req));
      tx.oncomplete = () => res(); tx.onerror = () => rej(tx.error);
    });
  },
};
// Model môže ležať na tvojom serveri (priečinok models/ vedľa stránky), vtedy sa sťahuje odtiaľ a nie z huggingface.co.
const LOCAL_BASE = new URL("models/", location.href).href;
const localCheck = {};
function hasLocal(id) {
  if (location.protocol === "file:") return Promise.resolve(false);
  if (!localCheck[id]) {
    localCheck[id] = fetch(LOCAL_BASE + id + "/config.json", { cache: "no-store" })
      .then(async (r) => r.ok && (await r.json(), true)).catch(() => false);
  }
  return localCheck[id];
}
async function preferLocal(list) {
  const flags = await Promise.all(list.map((x) => hasLocal(x.id)));
  return list.map((x, i) => ({ x, l: flags[i], i })).sort((a, b) => (b.l - a.l) || (a.i - b.i)).map((o) => o.x);
}
const loadingModels = {};
function ensureModel(spec, quiet) { // jedno načítanie naraz, hotový model sa drží v pamäti
  if (asr && asrId === spec.id) return Promise.resolve(asr);
  if (!loadingModels[spec.id]) {
    loadingModels[spec.id] = loadModel(spec, quiet)
      .then((m) => { asr = m; asrId = spec.id; try { localStorage.setItem(MODEL_KEY, spec.id); } catch {} showModelState(); return m; })
      .finally(() => { delete loadingModels[spec.id]; });
  } else if (!quiet) setBusy("Načítavam model…", "", phasePct("model", 0));
  return loadingModels[spec.id];
}
async function showModelState() {
  const el = $("model-state"); if (!el) return;
  let have = null; try { have = localStorage.getItem(MODEL_KEY); } catch {}
  const first = (await preferLocal(await bestModels()))[0];
  if (first && await hasLocal(first.id)) el.textContent = "Model je na serveri tejto stránky, sťahuje sa odtiaľ a ukladá sa v prehliadači.";
  else el.textContent = have ? "Model je uložený v tomto prehliadači a nesťahuje sa znova." : "Pri prvom použití sa model stiahne (stovky MB), potom sa uloží a nesťahuje sa znova.";
}
// Ak je model už uložený, načíta sa na pozadí hneď po otvorení stránky, aby nahratie videa nečakalo.
async function warmUpModel() {
  if (MOCK) return;
  let have = null; try { have = localStorage.getItem(MODEL_KEY); } catch {}
  if (!have) return;
  try { const spec = (await preferLocal(await bestModels())).find((x) => x.id === have); if (spec) await ensureModel(spec, true); } catch { /* načíta sa pri nahratí */ }
}
// Vyberie sa vždy najpresnejší model, ktorý prehliadač zvládne: large-v3-turbo na grafickej karte (WebGPU),
// inak small. Ak zlyhá najpresnejší, skúsi sa ďalší.
async function bestModels() {
  const list = [];
  if (navigator.gpu) {
    try {
      if (await navigator.gpu.requestAdapter()) list.push({ id: "onnx-community/whisper-large-v3-turbo", label: "large-v3-turbo", opts: { device: "webgpu", dtype: { encoder_model: "fp16", decoder_model_merged: "q4" } } });
    } catch { /* WebGPU nie je k dispozícii */ }
  }
  list.push({ id: "Xenova/whisper-small", label: "small", opts: {} });
  return list;
}
async function explainFailure(mod, id, lastErr, reach) {
  const probe = async (url) => { try { await fetch(url, { method: "GET", cache: "no-store" }); return "ok"; } catch { return "NEDOSTUPNÉ"; } };
  let wasmBase = ""; try { const wp = mod.env.backends.onnx.wasm.wasmPaths; wasmBase = typeof wp === "string" ? wp : (wp && (wp.wasm || wp.mjs) ? String(wp.wasm || wp.mjs).replace(/[^/]*$/, "") : ""); } catch {}
  const hfUrl = "https://huggingface.co/" + id + "/resolve/main/config.json";
  const cdnUrl = "https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.7.6/package.json";
  const checks = ["huggingface.co: " + (await probe(hfUrl)), "cdn.jsdelivr.net: " + (await probe(cdnUrl))];
  if (wasmBase) checks.push(`wasm: ${await probe(wasmBase + "ort-wasm-simd-threaded.jsep.wasm")}`);
  console.error("Diagnostika:", checks.join(", "), "Prehliadač:", navigator.userAgent, "Protokol:", location.protocol);
  const diag = checks.join(", ") + ` · ${/^((?!chrome|android).)*safari/i.test(navigator.userAgent) ? "Safari" : "iný prehliadač"} · ${location.protocol}`;
  const detail = String(lastErr && lastErr.message || lastErr).slice(0, 200);
  const inApp = location.protocol === "about:" || /Claude|FBAN|FBAV|Instagram|Line\//.test(navigator.userAgent) || (/iPhone|iPad/.test(navigator.userAgent) && !/Safari\//.test(navigator.userAgent));
  const hint = inApp
    ? "Stránka beží vo vstavanom prehliadači aplikácie, ktorý blokuje stiahnutie modelu z huggingface.co. Otvor ju v Safari alebo Chrome."
    : reach
    ? "Prehliadač sa nedostal na huggingface.co (blokuje ho sieť, VPN, firewall alebo blokovač reklám). Skús inú sieť alebo vypni blokovač."
    : location.protocol === "file:"
      ? "Skús súbor otvoriť cez lokálny server: v priečinku so súborom spusti `python3 -m http.server 8000` a otvor http://localhost:8000/titulky-browser.html."
      : "Skús obnoviť stránku.";
  return new Error(`Model sa nepodarilo načítať. ${hint} Technická chyba: ${detail} (${diag})`);
}
async function loadModel(spec, quiet) {
  if (!quiet) setBusy("Načítavam model…", "", phasePct("model", 0));
  try { await navigator.storage?.persist?.(); } catch {} // aby prehliadač model nevymazal
  if (!tf) {
    try { tf = await import(TF_URL); }
    catch (e) { throw new Error("Nepodarilo sa načítať knižnicu na rozpoznávanie (cdn.jsdelivr.net). Skontroluj pripojenie na internet alebo blokovač reklám. Technická chyba: " + String(e && e.message || e).slice(0, 150)); }
  }
  const files = {};
  const progress = (p) => { // reálne percentá len pri skutočnom sťahovaní; uložený model sa načíta bez neho (žiadne p.total)
    if (p.status !== "progress" || !p.total) return;
    files[p.file] = { loaded: p.loaded, total: p.total };
    const all = Object.values(files), loaded = all.reduce((a, f) => a + f.loaded, 0), total = all.reduce((a, f) => a + f.total, 0);
    if (!quiet) setBusy("Sťahujem model…", `${spec.label}: ${Math.round(loaded / 1048576)} z ${Math.round(total / 1048576)} MB (len pri prvom použití)`, phasePct("model", loaded / total));
  };
  let reach = null;
  try { await fetch(`https://huggingface.co/${spec.id}/resolve/main/config.json`, { method: "GET" }); } catch (e) { reach = e; }
  let idbOk = false; try { await openModelDb(); idbOk = true; } catch {}
  const local = await hasLocal(spec.id);
  const attempts = [
    ...(local ? [{ label: "vlastný server", cache: idbOk ? "idb" : "none", host: "https://huggingface.co/", local: true }] : []), // trvalá pamäť modelu, pamäť prehliadača, bez pamäte, cez zrkadlo
    ...(idbOk ? [{ label: "uložený model", cache: "idb", host: "https://huggingface.co/" }] : []),
    { label: "pamäť prehliadača", cache: "browser", host: "https://huggingface.co/" },
    { label: "bez pamäte", cache: "none", host: "https://huggingface.co/" },
    { label: "cez zrkadlo", cache: "none", host: "https://hf-mirror.com/" },
  ];
  let lastErr = null;
  for (const a of attempts) {
    try {
      tf.env.allowLocalModels = !!a.local; tf.env.allowRemoteModels = !a.local; if (a.local) tf.env.localModelPath = LOCAL_BASE;
      try { tf.env.backends.onnx.wasm.numThreads = 1; tf.env.backends.onnx.wasm.proxy = false; } catch {}
      tf.env.useCustomCache = a.cache === "idb"; if (a.cache === "idb") tf.env.customCache = idbCache;
      tf.env.useBrowserCache = a.cache === "browser"; tf.env.remoteHost = a.host;
      for (const k of Object.keys(files)) delete files[k];
      return await tf.pipeline("automatic-speech-recognition", spec.id, { ...spec.opts, progress_callback: progress });
    } catch (e) { lastErr = e; console.error("Model:", spec.label, a.label, e); }
  }
  throw await explainFailure(tf, spec.id, lastErr, reach);
}
// Transformers.js nedáva priebeh po častiach počas samotného rozpoznávania (len pri sťahovaní modelu),
// preto sa percentá odhadujú podľa uplynutého času a dĺžky videa: čím ďalej sa dostane, tým pomalšie
// rastú, a nikdy nedosiahnu 100 %, kým rozpoznávanie naozaj neskončí.
function startAsrProgress(durationSec, spec) {
  const factor = spec?.opts?.device === "webgpu" ? 0.6 : 2.2; // odhadovaný násobok reálneho času behu
  const est = Math.max(6, durationSec * factor), t0 = performance.now();
  const iv = setInterval(() => {
    const el = (performance.now() - t0) / 1000, frac = 1 - Math.exp(-el / (est / 2));
    setBusy("Rozpoznávam reč…", "Pri dlhšom videu to môže trvať niekoľko minút. Nechaj túto kartu otvorenú.", phasePct("asr", Math.min(frac, 0.96)));
  }, 400);
  return () => clearInterval(iv);
}
async function transcribeWith(model, audio, lang, spec) {
  const stop = startAsrProgress(audio.length / 16000, spec);
  try {
    const opts = { task: "transcribe", chunk_length_s: 30, stride_length_s: 5 };
    if (LANGS[lang]) opts.language = LANGS[lang];
    try { // časy po slovách (zvýraznenie slov)
      const wres = await model(audio, { ...opts, return_timestamps: "word" });
      const words = (wres.chunks || []).map((c) => ({ w: c.text.trim(), start: c.timestamp[0] ?? 0, end: c.timestamp[1] ?? state.duration })).filter((o) => o.w);
      if (words.length) return groupWords(words);
    } catch { /* model nevie časy po slovách, použije sa odhad */ }
    const res = await model(audio, { ...opts, return_timestamps: true });
    const chunks = res.chunks && res.chunks.length ? res.chunks : [{ timestamp: [0, state.duration], text: res.text }];
    return chunks.flatMap((c) => {
      const [s, e] = c.timestamp;
      return c.text.trim() ? splitChunk(c.text, s ?? 0, e ?? state.duration).map((g) => ({ ...g, words: estimateWords(g.text, g.start, g.end) })) : [];
    });
  } finally { stop(); }
}
async function recognize(file, _unused, lang) {
  resetBusy();
  setBusy("Načítavam zvuk…", "", phasePct("audio", 0));
  state.audio = null;
  if (MOCK) {
    try { state.audio = await decodeBuffer(await file.arrayBuffer()); } catch { /* bez zvuku sa rečníci nerozlišujú */ }
    setBusy("Načítavam zvuk…", "", phasePct("audio", 1));
    setBusy("Načítavam model…", "", phasePct("model", 1));
    await Promise.race([state.meta, new Promise((r) => setTimeout(r, 3000))]);
    const d = state.duration || 12, out = []; let t = 0.3, i = 1;
    const total = Math.max(1, d - 2);
    while (t + 1.5 < d) {
      setBusy("Rozpoznávam reč…", "Pri dlhšom videu to môže trvať niekoľko minút. Nechaj túto kartu otvorenú.", phasePct("asr", t / total));
      const text = SAMPLE[i++ % SAMPLE.length]; out.push({ start: t, end: t + 2, text, words: estimateWords(text, t, t + 2) }); t += 2.3;
      await new Promise((r) => setTimeout(r, 120)); // len aby bolo v teste vidno priebeh percent
    }
    setBusy("Rozpoznávam reč…", "", phasePct("asr", 1));
    return out;
  }
  const audio = await decodeAudio(file);
  state.audio = audio;
  if (!state.duration) state.duration = audio.length / 16000;
  setBusy("Načítavam zvuk…", "", phasePct("audio", 1));
  let lastError = null;
  for (const spec of await preferLocal(await bestModels())) {
    try {
      const model = await ensureModel(spec, false);
      setBusy("Rozpoznávam reč…", "", phasePct("model", 1));
      const segs = await transcribeWith(model, audio, lang, spec);
      setBusy("Rozpoznávam reč…", "", phasePct("asr", 1));
      return segs;
    } catch (e) { lastError = e; asr = null; asrId = null; console.error("Model", spec.label, e); }
  }
  throw lastError;
}

// ---------- nahratie ----------
const VIDEO_EXT = /\.(mp4|m4v|mov|mkv|webm|avi|ts|mts|m2ts|3gp|mpg|mpeg|ogv|hevc|h265|265)$/i;
async function pickFile(file) {
  $("upload-error").hidden = true;
  if (!file.type.startsWith("video/") && !VIDEO_EXT.test(file.name)) return showError("Vyber súbor s videom (MP4, MOV, MKV, WEBM a podobne).");
  const job = ++state.job; // nový projekt alebo ďalšie nahratie zruší výsledky staršej úlohy
  if (state.url) URL.revokeObjectURL(state.url);
  state.file = file; state.url = URL.createObjectURL(file); state.duration = 0; state.playable = null;
  setBusy("Čítam zvuk…");
  state.meta = new Promise((resolve) => {
    video.onloadedmetadata = () => {
      if (job !== state.job) return resolve();
      state.playable = video.videoWidth > 0; // zvuk bez obrazu = kodek sa nedá dekódovať
      if (state.playable) state.aspect = detectAspect(video.videoWidth, video.videoHeight); // uhádne formát podľa videa
      refreshQuality();
      if (isFinite(video.duration)) state.duration = video.duration;
      resolve();
    };
    video.onerror = () => { if (job === state.job) state.playable = false; resolve(); };
  });
  video.preload = "auto"; video.src = state.url; video.load();
  try { state.segments = await recognize(file, null, $("lang").value); }
  catch (e) { if (job !== state.job) return; return showError(e.message || "Rozpoznanie zlyhalo."); }
  if (job !== state.job) return;
  await Promise.race([state.meta, new Promise((r) => setTimeout(r, 3000))]);
  if (job !== state.job) return;
  if (state.playable === null) state.playable = false;
  bumpDyn(); initEditor();
}

// ---------- editor ----------
const ASPECTS = [{ k: "9:16", name: "Krátke video", icon: '<rect x="7" y="2.5" width="10" height="19" rx="2"/>' },
  { k: "1:1", name: "Príspevok", icon: '<rect x="4.5" y="4.5" width="15" height="15" rx="2"/>' },
  { k: "16:9", name: "Web", icon: '<rect x="2.5" y="7" width="19" height="10" rx="2"/>' }];
const ICON_DL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>';
const POS_Y = { top: 0.14, middle: 0.5, bottom: 0.84 };
const PV_MAX_H = 280;
// Poloha titulkov je samostatná pre každý formát (9:16, 1:1, 16:9); ostatné nastavenie štýlu je spoločné.
const defaultPos = () => ({ x: 0.5, y: state.style.y ?? 0.84, position: state.style.position ?? (state.style.y ? null : "bottom") });
// Formát sa pri nahratí videa uhádne z jeho rozmerov (najbližší pomer strán), používateľ ho potom môže zmeniť ikonkou.
function detectAspect(w, h) {
  const ratio = Math.log(w / h);
  let best = "9:16", bestDiff = Infinity;
  for (const k in OUT) { const [ow, oh] = OUT[k], diff = Math.abs(ratio - Math.log(ow / oh)); if (diff < bestDiff) { bestDiff = diff; best = k; } }
  return best;
}
// ---------- rečníci ----------
// Predvolene jeden rečník a jeden štýl. Ďalších rečníkov pridáva a odoberá používateľ; každý má meno,
// farbu (na časovej osi a v zozname), vlastný štýl titulkov a vlastnú polohu pre každý formát.
// Rečník s id 1 je hlavný: jeho štýl je state.style a nedá sa odobrať.
const SPK_COLORS = ["#6c5cff", "#19b8e8", "#f5a524", "#ef5da8", "#22c55e", "#f97316"];
const newSpk = (id) => ({ id, name: "Rečník " + id, color: SPK_COLORS[(id - 1) % SPK_COLORS.length] });
const multiSpk = () => state.spk.length > 1;
const spkById = (id) => state.spk.find((x) => x.id === id) || state.spk[0] || newSpk(1);
const spkColor = (id) => spkById(id).color;
const spkCount = (id) => state.segments.reduce((a, x) => a + ((x.speaker || 1) === id ? 1 : 0), 0);
function resetSpeakers() { state.spk = [newSpk(1)]; state.spkStyle = {}; state.cur = 1; state.collapsedSpk.clear(); }
function defaultSpeakerStyle() { // nový rečník dostane iný štýl, ako majú ostatní, aby bol rozdiel hneď vidno
  const used = new Set([state.style.preset, ...Object.values(state.spkStyle).map((x) => x.preset)]);
  const p = PRESETS.find((x) => !x.dynamic && !used.has(x.id)) || PRESETS.find((x) => x.id !== state.style.preset) || PRESETS[0];
  return { ...p, preset: p.id, highlight: state.style.highlight, caps: p.caps ?? state.style.caps };
}
function addSpeaker(select = true) {
  const id = Math.max(0, ...state.spk.map((x) => x.id)) + 1;
  state.spk.push(newSpk(id)); state.spkStyle[id] = defaultSpeakerStyle();
  if (select) state.cur = id;
  return id;
}
function removeSpeaker(id) {
  if (id === 1 || !state.spk.some((x) => x.id === id)) return;
  state.segments.forEach((x) => { if ((x.speaker || 1) === id) x.speaker = 1; }); // jeho titulky prejdú na hlavného rečníka
  state.spk = state.spk.filter((x) => x.id !== id); delete state.spkStyle[id];
  Object.keys(state.pos).forEach((k) => { if (k.endsWith("|" + id)) delete state.pos[k]; });
  state.collapsedSpk.delete(id);
  if (state.cur === id) state.cur = 1;
  speakersChanged();
}
function speakersChanged() { // po každej zmene rečníkov sa prekreslí všetko, čo od nich závisí
  bumpDyn(); invalidateResults(); renderSpkList(); renderSelSpk(); buildSegments(); renderChips(tlDuration(), state.tlPxPerSec); syncControls(); refreshSel();
}
// Štýl, ktorý sa práve upravuje v paneli: vybraného rečníka v zozname.
const activeStyle = () => (state.cur !== 1 && state.spkStyle[state.cur]) ? state.spkStyle[state.cur] : state.style;
function speakerStyle(sp, seg) { // štýl titulku: vlastný štýl úseku > štýl rečníka > hlavný štýl
  const base = (sp !== 1 && state.spkStyle[sp]) ? state.spkStyle[sp] : state.style;
  const p = seg && seg.styleOv ? PRESETS.find((x) => x.id === seg.styleOv) : null;
  return p ? { ...p, preset: p.id, highlight: p.highlight ?? base.highlight, caps: p.caps ?? base.caps, on: state.style.on } : { ...base, on: state.style.on };
}
function getPos(k, sp = 1) { // poloha titulkov: pre každý formát a pre každého rečníka zvlášť
  if (!state.pos[k]) state.pos[k] = defaultPos();
  if (sp === 1 || !state.spkStyle[sp]) return state.pos[k];
  const key = k + "|" + sp;
  if (!state.pos[key]) state.pos[key] = { ...state.pos[k] }; // nový rečník začína na mieste hlavného
  return state.pos[key];
}
const editSpk = () => (state.spkStyle[state.cur] ? state.cur : 1); // rečník, ktorého polohu menia tlačidlá Hore/Stred/Dole
const styleFor = (k, sp = 1, seg = null) => ({ ...speakerStyle(sp, seg), ...getPos(k, sp) });

function playableBanner() {
  const bad = state.playable === false;
  $("noplay").hidden = !bad;
  $("exportAll").disabled = bad; $("exportCur").disabled = bad;
  if (state.out) state.out.dl.disabled = bad;
}
function saveUrl(url, name) {
  const a = document.createElement("a"); a.href = url; a.download = name; document.body.append(a); a.click(); a.remove();
}
function saveText(text, name, type) {
  const u = URL.createObjectURL(new Blob([text], { type })); saveUrl(u, name); setTimeout(() => URL.revokeObjectURL(u), 4000);
}
function reflectResult() { // premietne uložený výsledok (ak už bol tento formát vyexportovaný) do viditeľnej karty
  if (!state.out) return;
  const r = state.results[state.out.k];
  state.out.dl.classList.toggle("ready", !!r);
  state.out.dl.title = r ? "Stiahnuť hotové video" : "Exportovať a stiahnuť";
  state.out.prog.hidden = true;
}
function invalidateResults() { // zmena štýlu/orezu/textu spraví doterajšie exporty neplatnými
  if (state.busyExport) return;
  for (const k of Object.keys(state.results)) { URL.revokeObjectURL(state.results[k].url); delete state.results[k]; }
  if (state.out) { const r = state.out.el.querySelector(".out-result"); if (r) r.remove(); }
  reflectResult();
}
// Vidno vždy len jeden formát naraz (podľa state.aspect); karta sa znova postaví pri každom prepnutí ikonkou.
function buildOut() {
  const a = ASPECTS.find((x) => x.k === state.aspect), k = a.k;
  const box = $("outs"); box.textContent = "";
  const el = document.createElement("div"); el.className = "out"; el.dataset.a = k;
  el.innerHTML = `<div class="out-head"><span>${a.name} (${k})</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">${a.icon}</svg></div>
    <div class="pv"><canvas aria-label="Náhľad ${k}"></canvas></div><div class="out-time">0:00 / 0:00</div>
    <div class="out-btns"><button class="play">▶ Prehrať</button><button class="dl" title="Exportovať a stiahnuť" aria-label="Exportovať a stiahnuť ${k}">${ICON_DL}</button></div>
    <div class="out-prog" hidden><div class="bar"><i></i></div><span></span></div>`;
  box.append(el);
  const o = { k, el, cv: el.querySelector("canvas"), pv: el.querySelector(".pv"), time: el.querySelector(".out-time"),
    dl: el.querySelector(".dl"), play: el.querySelector(".play"), prog: el.querySelector(".out-prog"), drag: null };
  o.play.onclick = () => { if (video.paused) video.play().catch(() => {}); else video.pause(); };
  o.dl.onclick = () => { const r = state.results[k]; if (r) saveUrl(r.url, `titulky-${k.replace(":", "x")}.${r.ext}`); else exportAspects([k]); };
  // titulky sa presúvajú ťahaním priamo v náhľade
  const move = (e) => {
    const r = o.cv.getBoundingClientRect(), q = getPos(k, o.drag.spk);
    q.x = clamp((e.clientX - r.left) / r.width, 0, 1); q.y = clamp((e.clientY - r.top) / r.height, 0, 1); q.position = null;
    markActive(); invalidateResults();
  };
  const pan = (e) => { // posun videa (orez) ťahaním
    const q = state.crop[k], vw = video.videoWidth, vh = video.videoHeight, r = o.cv.getBoundingClientRect(); if (!vw || !vh) return;
    const iw = Math.max(r.width, r.height * vw / vh) * q.zoom, ih = Math.max(r.width * vh / vw, r.height) * q.zoom;
    q.cx = clamp(o.drag.cx - (e.clientX - o.drag.x) / iw, r.width / 2 / iw, 1 - r.width / 2 / iw);
    q.cy = clamp(o.drag.cy - (e.clientY - o.drag.y) / ih, r.height / 2 / ih, 1 - r.height / 2 / ih);
    invalidateResults();
  };
  o.cv.addEventListener("pointerdown", (e) => {
    if (state.tool === "crop") { const q = state.crop[k]; o.drag = { mode: "pan", x: e.clientX, y: e.clientY, cx: q.cx, cy: q.cy }; }
    else if (state.tool === "captions" && state.style.on) { // presúva sa rečník, ktorého titulok je práve vidno
      const i = segmentAt(video.currentTime), spk = i >= 0 ? (state.segments[i].speaker || 1) : editSpk();
      if (multiSpk() && spk !== state.cur) { state.cur = spk; renderSpkList(); syncControls(); }
      o.drag = { mode: "caption", x: e.clientX, y: e.clientY, active: false, spk };
    }
    else return;
    o.cv.setPointerCapture(e.pointerId); e.preventDefault();
  });
  o.cv.addEventListener("pointermove", (e) => {
    if (!o.drag) return;
    if (o.drag.mode === "pan") return pan(e);
    if (!o.drag.active && Math.hypot(e.clientX - o.drag.x, e.clientY - o.drag.y) > 4) o.drag.active = true;
    if (o.drag.active) move(e);
  });
  o.cv.addEventListener("wheel", (e) => { // koliesko myši približuje v režime orezu
    if (state.tool !== "crop") return;
    e.preventDefault(); const q = state.crop[k];
    q.zoom = clamp(q.zoom * (e.deltaY < 0 ? 1.08 : 1 / 1.08), 1, 3); markActive(); invalidateResults();
  }, { passive: false });
  const end = () => { o.drag = null; };
  o.cv.addEventListener("pointerup", end); o.cv.addEventListener("pointercancel", end);
  state.out = o;
  reflectResult();
}
// ---------- časová os: vlnovka, pravítko, posúvateľné a naťahovateľné titulky ----------
function computeWaveform() {
  const audio = state.audio;
  if (!audio || !audio.length) { state.waveform = null; return; }
  const N = 600, bucket = Math.max(1, Math.floor(audio.length / N)), peaks = [];
  for (let i = 0; i < audio.length; i += bucket) {
    let m = 0; for (let j = i, e = Math.min(audio.length, i + bucket); j < e; j++) { const v = Math.abs(audio[j]); if (v > m) m = v; }
    peaks.push(m);
  }
  const max = Math.max(0.001, ...peaks);
  state.waveform = peaks.map((v) => v / max);
}
function tlDuration() { return state.duration || video.duration || 1; }
function fmtTimeline(s) { return fmtTime(s); }
function drawRuler(dur, px) {
  const cv = $("tlRuler"); if (!cv) return;
  const dpr = window.devicePixelRatio || 1, w = Math.max(1, Math.round(dur * px));
  cv.width = Math.round(w * dpr); cv.height = Math.round(24 * dpr); cv.style.width = w + "px";
  const ctx = cv.getContext("2d"); ctx.setTransform(dpr, 0, 0, dpr, 0, 0); ctx.clearRect(0, 0, w, 24);
  const step = px > 60 ? 1 : px > 25 ? 2 : px > 10 ? 5 : px > 4 ? 10 : 30;
  ctx.strokeStyle = "rgba(255,255,255,.18)"; ctx.fillStyle = "rgba(255,255,255,.55)"; ctx.font = "10px Inter,sans-serif"; ctx.textBaseline = "top";
  for (let t = 0; t <= dur; t += step) {
    const x = t * px;
    ctx.beginPath(); ctx.moveTo(x + 0.5, 15); ctx.lineTo(x + 0.5, 24); ctx.stroke();
    ctx.fillText(fmtTimeline(t), x + 3, 2);
  }
}
function drawWave(dur, px) {
  const cv = $("tlWave"); if (!cv) return;
  const dpr = window.devicePixelRatio || 1, w = Math.max(1, Math.round(dur * px)), h = 46;
  cv.width = Math.round(w * dpr); cv.height = Math.round(h * dpr); cv.style.width = w + "px";
  const ctx = cv.getContext("2d"); ctx.setTransform(dpr, 0, 0, dpr, 0, 0); ctx.clearRect(0, 0, w, h);
  const peaks = state.waveform;
  if (peaks && peaks.length) {
    ctx.fillStyle = "rgba(108,92,255,.6)";
    const n = peaks.length;
    for (let x = 0; x < w; x++) { const v = peaks[Math.floor(x / w * n)] || 0, bh = Math.max(1, v * h); ctx.fillRect(x, (h - bh) / 2, 1, bh); }
  } else { ctx.fillStyle = "rgba(255,255,255,.08)"; ctx.fillRect(0, h / 2 - 1, w, 2); }
}
function wireChip(el, s, i, hl, hr) {
  let mode = null, startX = 0, startS = 0, startE = 0;
  const prevEnd = () => (i > 0 ? state.segments[i - 1].end : 0);
  const nextStart = () => (i + 1 < state.segments.length ? state.segments[i + 1].start : tlDuration());
  const begin = (m) => (e) => { mode = m; state.tlDragging = true; startX = e.clientX; startS = s.start; startE = s.end; el.setPointerCapture(e.pointerId); e.stopPropagation(); };
  hl.addEventListener("pointerdown", begin("l")); hr.addEventListener("pointerdown", begin("r"));
  el.addEventListener("pointerdown", (e) => {
    if (e.target !== el) return;
    const mod = e.shiftKey || e.ctrlKey || e.metaKey;
    if (mod || !state.sel.has(i)) selectSeg(i, e);
    if (mod) { e.stopPropagation(); return; } // výber s klávesom nepresúva titulok
    begin("m")(e); state.activeSeg = i; video.currentTime = s.start + 0.01; markActiveSeg();
    const row = document.querySelector(`.segment[data-i="${i}"]`); if (row) row.scrollIntoView({ block: "nearest" }); // klik na titulok na osi = zámerný presun, tu sa scrollovať má
  });
  el.addEventListener("pointermove", (e) => {
    if (!mode) return;
    const dt = (e.clientX - startX) / state.tlPxPerSec;
    if (mode === "l") s.start = clamp(startS + dt, prevEnd(), s.end - 0.1);
    else if (mode === "r") s.end = clamp(startE + dt, s.start + 0.1, nextStart());
    else { const span = startE - startS; let ns = clamp(startS + dt, prevEnd(), nextStart() - span); s.start = ns; s.end = ns + span; }
    el.style.left = Math.round(s.start * state.tlPxPerSec) + "px"; el.style.width = Math.max(10, Math.round((s.end - s.start) * state.tlPxPerSec)) + "px";
  });
  const stop = () => { if (!mode) return; mode = null; state.tlDragging = false; bumpDyn(); invalidateResults(); };
  el.addEventListener("pointerup", stop); el.addEventListener("pointercancel", stop);
}
function renderChips(dur, px) {
  const box = $("tlChips"); if (!box) return;
  box.style.width = Math.round(dur * px) + "px"; box.textContent = "";
  state.segments.forEach((s, i) => {
    const el = document.createElement("div"); el.className = "chip" + (i === state.activeSeg ? " active" : "") + (state.sel.has(i) ? " sel" : "") + (s.styleOv ? " ov" : ""); el.dataset.i = i;
    el.style.left = Math.round(s.start * px) + "px"; el.style.width = Math.max(10, Math.round((s.end - s.start) * px)) + "px";
    el.style.background = spkColor(s.speaker || 1); el.title = s.text;
    const label = document.createElement("span"); label.className = "chip-label"; label.textContent = s.text.slice(0, 36);
    const hl = document.createElement("div"); hl.className = "h l"; const hr = document.createElement("div"); hr.className = "h r";
    el.append(label, hl, hr); wireChip(el, s, i, hl, hr); box.append(el);
  });
}
function renderTimeline() {
  if (!$("tlScroll")) return;
  const dur = tlDuration(), avail = $("tlScroll").clientWidth || 600;
  const px = Math.max(10, avail / dur * state.tlZoomVal);
  state.tlPxPerSec = px;
  drawRuler(dur, px); drawWave(dur, px); renderChips(dur, px); renderCuts(dur, px);
  $("tlDur").textContent = fmtTimeline(dur);
}
function selectSeg(i, e) { // klik = jeden titulok, Shift+klik = úsek, Ctrl/Cmd+klik = pridať/odobrať
  if (e && e.shiftKey && state.selAnchor >= 0) {
    const a = Math.min(state.selAnchor, i), b = Math.max(state.selAnchor, i);
    if (!(e.ctrlKey || e.metaKey)) state.sel.clear();
    for (let k = a; k <= b; k++) state.sel.add(k);
  } else if (e && (e.ctrlKey || e.metaKey)) {
    if (state.sel.has(i)) state.sel.delete(i); else state.sel.add(i);
    state.selAnchor = i;
  } else { state.sel.clear(); state.sel.add(i); state.selAnchor = i; }
  refreshSel();
}
function clearSel() { state.sel.clear(); state.selAnchor = -1; refreshSel(); }
function refreshSel() {
  document.querySelectorAll(".chip").forEach((c) => c.classList.toggle("sel", state.sel.has(+c.dataset.i)));
  document.querySelectorAll(".segment").forEach((r) => r.classList.toggle("sel", state.sel.has(+r.dataset.i)));
  const bar = $("selBar"); if (!bar) return;
  const ids = [...state.sel].sort((a, b) => a - b).filter((k) => state.segments[k]);
  bar.hidden = ids.length === 0;
  if (!ids.length) return;
  const a = state.segments[ids[0]], b = state.segments[ids[ids.length - 1]];
  $("selInfo").textContent = `Vybraté: ${ids.length} ${ids.length === 1 ? "titulok" : ids.length < 5 ? "titulky" : "titulkov"} · ${fmtTime(a.start)}–${fmtTime(b.end)}`;
  const spks = new Set(ids.map((k) => state.segments[k].speaker || 1)), ovs = new Set(ids.map((k) => state.segments[k].styleOv || ""));
  document.querySelectorAll("#selSpk [data-spk]").forEach((x) => { const on = spks.size === 1 && spks.has(+x.dataset.spk); x.classList.toggle("on", on); x.setAttribute("aria-pressed", String(on)); });
  document.querySelectorAll("#selStyles .stile").forEach((x) => x.classList.toggle("on", ovs.size === 1 && ovs.has(x.dataset.id)));
}
function assignSpeaker(n) {
  const ids = [...state.sel]; if (!ids.length) return;
  if (n === "new") n = addSpeaker(true);
  ids.forEach((k) => { state.segments[k].speaker = n; });
  speakersChanged();
}
function assignStyle(id) {
  const ids = [...state.sel]; if (!ids.length) return;
  ids.forEach((k) => { state.segments[k].styleOv = id || null; });
  bumpDyn(); invalidateResults(); buildSegments(); renderChips(tlDuration(), state.tlPxPerSec); refreshSel();
}
function markActiveSeg() {
  document.querySelectorAll(".chip").forEach((c) => c.classList.toggle("active", +c.dataset.i === state.activeSeg));
  document.querySelectorAll(".segment").forEach((r) => r.classList.toggle("active", +r.dataset.i === state.activeSeg));
}
function syncTimelinePlayhead() {
  const ph = $("tlPlayhead"); if (!ph) return;
  const x = video.currentTime * state.tlPxPerSec;
  ph.style.left = Math.round(x) + "px"; $("tlCur").textContent = fmtTimeline(video.currentTime);
  const sc = $("tlScroll");
  if (sc && !video.paused && (x < sc.scrollLeft + 24 || x > sc.scrollLeft + sc.clientWidth - 24)) sc.scrollLeft = Math.max(0, x - sc.clientWidth * 0.3);
}

function updateChip(i) {
  const c = document.querySelector(`.chip[data-i="${i}"]`); if (!c) return;
  const s = state.segments[i];
  c.title = s.text; const label = c.querySelector(".chip-label"); if (label) label.textContent = s.text.slice(0, 36);
}
function toggleSpkGroup(n) {
  if (state.collapsedSpk.has(n)) state.collapsedSpk.delete(n); else state.collapsedSpk.add(n);
  applySegFilter();
  document.querySelectorAll(`.spk-group[data-s="${n}"]`).forEach((h) => h.classList.toggle("collapsed", state.collapsedSpk.has(n)));
}
function applySegFilter() {
  const q = state.segFilter;
  document.querySelectorAll(".segment").forEach((row) => {
    const i = +row.dataset.i, s = state.segments[i], collapsed = state.collapsedSpk.has(s.speaker || 1);
    const matches = !q || s.text.toLowerCase().includes(q);
    row.hidden = collapsed || !matches;
  });
}
function buildSegments() {
  const box = $("segments"); box.textContent = "";
  box.classList.toggle("spk-on", multiSpk());
  if (!state.segments.length) { box.textContent = "V tomto videu sa nenašla žiadna reč."; return; }
  let lastSpeaker = null;
  state.segments.forEach((s, i) => {
    const n = s.speaker || 1, who = spkById(n);
    if (multiSpk() && n !== lastSpeaker) { // nová skupina, keď sa v poradí strieda rečník
      lastSpeaker = n;
      const h = document.createElement("div"); h.className = "spk-group" + (state.collapsedSpk.has(n) ? " collapsed" : ""); h.dataset.s = n; h.style.setProperty("--c", who.color);
      const dot = document.createElement("i"); dot.className = "dot"; const nm = document.createElement("span"); nm.textContent = who.name;
      const chev = document.createElement("span"); chev.className = "chev"; chev.textContent = "▾";
      h.append(dot, nm, chev); h.onclick = () => toggleSpkGroup(n);
      box.append(h);
    }
    const row = document.createElement("div"); row.className = "segment" + (state.sel.has(i) ? " sel" : ""); row.dataset.i = i; row.dataset.s = n; row.style.setProperty("--c", who.color);
    const t = document.createElement("button"); t.textContent = fmtTime(s.start); t.title = "Klik: prehrať odtiaľto a vybrať · Shift+klik: vybrať úsek";
    t.onclick = (e) => {
      selectSeg(i, e);
      if (!(e.shiftKey || e.ctrlKey || e.metaKey)) { video.currentTime = s.start + 0.01; video.play(); }
      state.activeSeg = i; markActiveSeg();
    };
    if (s.styleOv) { const p = PRESETS.find((x) => x.id === s.styleOv); if (p) { const tag = document.createElement("span"); tag.className = "ovtag"; tag.textContent = p.name; t.append(tag); } }
    const ta = document.createElement("textarea"); ta.value = s.text; ta.rows = 1; ta.setAttribute("aria-label", "Text titulku " + (i + 1));
    const fit = () => { ta.style.height = "auto"; ta.style.height = ta.scrollHeight + "px"; };
    ta.oninput = () => { s.text = ta.value; fit(); bumpDyn(); invalidateResults(); updateChip(i); };
    const sp = document.createElement("button"); sp.type = "button"; sp.className = "spk"; sp.dataset.i = i; sp.dataset.s = n; sp.style.setProperty("--c", who.color);
    sp.setAttribute("aria-haspopup", "menu"); sp.title = who.name + " · klikni a vyber iného rečníka"; sp.setAttribute("aria-label", sp.title);
    const dot = document.createElement("i"); dot.className = "dot"; const lab = document.createElement("span"); lab.textContent = who.name;
    sp.append(dot, lab); sp.insertAdjacentHTML("beforeend", '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>');
    sp.onclick = (e) => { e.stopPropagation(); openSpkMenu(sp, (id) => { s.speaker = id === "new" ? addSpeaker(true) : id; speakersChanged(); }, n); };
    row.append(t, ta, sp); box.append(row); requestAnimationFrame(fit);
  });
  applySegFilter(); refreshSel();
}
// Rozbaľovacie menu pri titulku: zoznam rečníkov + „Nový rečník“.
function placeSpkMenu(m) { // menu ostane pri svojom tlačidle aj pri posúvaní stránky
  const r = m._btn.getBoundingClientRect(), mr = m.getBoundingClientRect();
  if (r.bottom < 0 || r.top > innerHeight) { closeSpkMenu(); return; }
  const top = r.bottom + 6 + mr.height > innerHeight - 8 ? Math.max(8, r.top - 6 - mr.height) : r.bottom + 6;
  m.style.top = top + "px"; m.style.left = clamp(r.right - mr.width, 8, innerWidth - mr.width - 8) + "px";
}
function closeSpkMenu() { const m = document.querySelector(".spk-menu"); if (m) { const b = m._btn; m.remove(); if (b) b.setAttribute("aria-expanded", "false"); } }
function openSpkMenu(btn, onPick, current) {
  const was = document.querySelector(".spk-menu"); closeSpkMenu(); if (was && was._btn === btn) return;
  const m = document.createElement("div"); m.className = "spk-menu"; m.setAttribute("role", "menu"); m._btn = btn;
  const item = (label, color, on, fn) => {
    const b = document.createElement("button"); b.type = "button"; b.setAttribute("role", "menuitemradio"); b.setAttribute("aria-checked", String(!!on));
    if (color) { const d = document.createElement("i"); d.className = "dot"; d.style.setProperty("--c", color); b.append(d); }
    else b.insertAdjacentHTML("beforeend", '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>');
    const t = document.createElement("span"); t.textContent = label; b.append(t);
    if (on) { const c = document.createElement("span"); c.className = "check"; c.textContent = "✓"; b.append(c); }
    b.onclick = (e) => { e.stopPropagation(); closeSpkMenu(); fn(); };
    m.append(b); return b;
  };
  state.spk.forEach((x) => item(x.name, x.color, x.id === current, () => onPick(x.id)));
  m.append(document.createElement("hr"));
  item("Nový rečník", null, false, () => onPick("new"));
  document.body.append(m); btn.setAttribute("aria-expanded", "true");
  placeSpkMenu(m);
  (m.querySelector('[aria-checked="true"]') || m.querySelector("button")).focus({ preventScroll: true });
}
// Zoznam rečníkov v paneli Titulky: výber (koho štýl a polohu upravujem), premenovanie, odobratie.
function renderSpkList() {
  const box = $("spkList"); if (!box) return; box.textContent = "";
  state.spk.forEach((x) => {
    const row = document.createElement("div"); row.className = "spk-row" + (x.id === state.cur ? " on" : ""); row.style.setProperty("--c", x.color);
    row.setAttribute("role", "option"); row.setAttribute("aria-selected", String(x.id === state.cur)); row.tabIndex = 0; row.dataset.spk = x.id;
    const dot = document.createElement("i"); dot.className = "dot";
    const name = document.createElement("input"); name.className = "spk-name"; name.value = x.name; name.maxLength = 24; name.setAttribute("aria-label", "Meno rečníka"); name.title = "Klikni a prepíš meno";
    name.oninput = () => { x.name = name.value.trim() || "Rečník " + x.id; renderSelSpk(); buildSegments(); syncControls(); };
    name.onkeydown = (e) => { if (e.key === "Enter") name.blur(); };
    name.onblur = () => { name.value = x.name; };
    const meta = document.createElement("span"); meta.className = "spk-meta"; const c = spkCount(x.id); meta.textContent = c + (c === 1 ? " titulok" : c > 1 && c < 5 ? " titulky" : " titulkov");
    row.append(dot, name, meta);
    if (x.id === 1) { const b = document.createElement("span"); b.className = "spk-badge"; b.textContent = "Hlavný"; row.append(b); }
    else {
      const del = document.createElement("button"); del.type = "button"; del.className = "spk-del"; del.title = "Odobrať rečníka (jeho titulky prejdú na hlavného)"; del.setAttribute("aria-label", "Odobrať " + x.name);
      del.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>';
      del.onclick = (e) => { e.stopPropagation(); removeSpeaker(x.id); };
      row.append(del);
    }
    const pick = () => { if (state.cur !== x.id) { state.cur = x.id; renderSpkList(); syncControls(); } };
    row.onclick = pick; row.onkeydown = (e) => { if ((e.key === "Enter" || e.key === " ") && e.target === row) { e.preventDefault(); pick(); } };
    box.append(row);
  });
  $("spkInfo").textContent = multiSpk()
    ? "Štýl a pozícia nižšie platia pre vybraného rečníka. Rečníka titulku zmeníš značkou pri texte alebo výberom úseku na časovej osi."
    : "Všetky titulky patria jednému rečníkovi. Ak vo videu rozpráva viac ľudí, pridaj ďalšieho rečníka s vlastným štýlom.";
}
function renderSelSpk() { // tlačidlá rečníkov v lište výberu
  const box = $("selSpk"); if (!box) return; box.textContent = "";
  state.spk.forEach((x) => {
    const b = document.createElement("button"); b.type = "button"; b.className = "mini-btn"; b.dataset.spk = x.id; b.style.setProperty("--c", x.color);
    const d = document.createElement("i"); d.className = "dot"; const t = document.createElement("span"); t.textContent = x.name;
    b.append(d, t); b.onclick = () => assignSpeaker(x.id); box.append(b);
  });
  const nb = document.createElement("button"); nb.type = "button"; nb.className = "mini-btn"; nb.title = "Pridať nového rečníka a priradiť mu vybrané titulky";
  nb.innerHTML = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Nový</span>';
  nb.onclick = () => assignSpeaker("new"); box.append(nb);
}
function initEditor() {
  state.pos = {}; state.crop = {}; state.tool = "captions"; state.results = {}; state.tab = "subs";
  ASPECTS.forEach((a) => { state.pos[a.k] = defaultPos(); state.crop[a.k] = { zoom: 1, cx: 0.5, cy: 0.5 }; });
  resetSpeakers(); state.segments.forEach((s) => { s.speaker = 1; }); state.activeSeg = -1; state.sel.clear(); state.selAnchor = -1;
  computeWaveform();
  buildOut(); buildSegments(); show("editor"); syncControls(); markActive(); playableBanner(); $("export-error").hidden = true;
  renderTimeline();
  renderSpkList(); renderSelSpk(); $("spkDetect").disabled = !state.audio;
  resetCuts(); renderCutUi();
}

// ---------- kreslenie snímky (rovnaké pre náhľady aj export) ----------
// Orez: crop = { zoom >= 1, cx, cy } - priblíženie a bod zdrojového videa (0 až 1), ktorý je v strede záberu.
function cropRect(vw, vh, W, H, crop) {
  const z = crop?.zoom || 1, s = Math.max(W / vw, H / vh) * z, iw = vw * s, ih = vh * s;
  const cx = clamp(crop?.cx ?? 0.5, W / 2 / iw, 1 - W / 2 / iw), cy = clamp(crop?.cy ?? 0.5, H / 2 / ih, 1 - H / 2 / ih);
  return { iw, ih, dx: W / 2 - cx * iw, dy: H / 2 - cy * ih };
}
// ---------- strih: časti videa a prechody ----------
// Video sa delí na časti (v čase originálu). Časť môže byť vystrihnutá (off). Za každou ponechanou časťou môže byť
// prechod do ďalšej ponechanej časti. Titulky ostávajú v čase originálu: čo sa neprehrá, to sa ani neukáže.
// Prechody používajú jedno video (bez prekrývania záberov): koniec časti odchádza, začiatok ďalšej prichádza.
const TRANSITIONS = [
  { id: "none", name: "Bez prechodu" }, { id: "fade", name: "Stmievačka" }, { id: "white", name: "Záblesk" },
  { id: "blur", name: "Rozmazanie" }, { id: "zoom", name: "Priblíženie" }, { id: "whip", name: "Švih" },
];
const trName = (id) => (TRANSITIONS.find((x) => x.id === id) || TRANSITIONS[0]).name;
function resetCuts() { state.parts = [{ start: 0, end: tlDuration(), off: false, tr: "none", td: 0.6 }]; state.partSel = 0; }
const partAt = (t) => { const i = state.parts.findIndex((p) => t < p.end); return i < 0 ? state.parts.length - 1 : i; };
const nextKept = (i) => { for (let j = i + 1; j < state.parts.length; j++) if (!state.parts[j].off) return j; return -1; };
const prevKept = (i) => { for (let j = i - 1; j >= 0; j--) if (!state.parts[j].off) return j; return -1; };
const hasCuts = () => state.parts.length > 1 || (state.parts[0] && state.parts[0].off);
const outDuration = () => state.parts.reduce((a, p) => a + (p.off ? 0 : p.end - p.start), 0);
function srcToOut(t) { // čas originálu -> čas vo výslednom videu (null, ak je to vystrihnuté)
  let acc = 0;
  for (const p of state.parts) { if (t < p.end) return p.off ? null : acc + Math.max(0, t - p.start); if (!p.off) acc += p.end - p.start; }
  return acc;
}
// Kam pokračovať z času t: null = normálne ďalej, číslo = preskočiť na tento čas, "end" = koniec videa.
function cutStep(t) {
  if (!hasCuts()) return null;
  const i = partAt(t), p = state.parts[i];
  if (!p.off && t < p.end - 0.03) return null;
  const j = nextKept(i);
  if (j < 0) return p.off || t >= p.end - 0.03 ? "end" : null;
  if (!p.off && j === i + 1) return null; // nasledujúca časť nadväzuje, netreba skákať
  return state.parts[j].start + 0.001;
}
function firstKeptStart() { const p = state.parts.find((x) => !x.off); return p ? p.start : 0; }
function trSpan(i) { // dĺžka polovice prechodu za časťou i (odchod na jej konci aj príchod na začiatku ďalšej)
  const p = state.parts[i], j = nextKept(i); if (j < 0 || p.tr === "none") return 0;
  const q = state.parts[j];
  return Math.min(p.td / 2, (p.end - p.start) / 2, (q.end - q.start) / 2);
}
function transFx(t) { // aký prechod práve beží v čase t: { type, p (0..1), out (odchod = true) }
  if (!hasCuts()) return null;
  const i = partAt(t), part = state.parts[i]; if (part.off) return null;
  let best = null;
  const d1 = trSpan(i); if (d1 > 0 && part.end - t < d1) best = { type: part.tr, p: 1 - (part.end - t) / d1, out: true };
  const k = prevKept(i);
  if (k >= 0) { const d0 = trSpan(k); if (d0 > 0 && nextKept(k) === i && t - part.start < d0) { const p = 1 - (t - part.start) / d0; if (!best || p > best.p) best = { type: state.parts[k].tr, p, out: false }; } }
  if (best) best.p = clamp(best.p, 0, 1);
  return best;
}
function cutSegments() { // titulky pre SRT/VTT v čase výsledného videa
  if (!hasCuts()) return state.segments;
  const out = [];
  for (const s of state.segments) {
    const pieces = state.parts.filter((p) => !p.off && p.end > s.start && p.start < s.end);
    if (!pieces.length) continue;
    const a = srcToOut(Math.max(s.start, pieces[0].start)), b = srcToOut(Math.min(s.end, pieces[pieces.length - 1].end) - 0.001);
    if (a != null && b != null && b - a > 0.25) out.push({ ...s, start: a, end: b + 0.001 });
  }
  return out;
}
function cutsChanged() { invalidateResults(); renderCutUi(); }
function splitAt(t) {
  const i = partAt(t), p = state.parts[i];
  if (t - p.start < 0.2 || p.end - t < 0.2) { $("cutHint").textContent = "Tu sa rozdeliť nedá, strih je príliš blízko. Posuň video trochu ďalej."; return; }
  state.parts.splice(i + 1, 0, { start: t, end: p.end, off: p.off, tr: p.tr, td: p.td });
  p.end = t; p.tr = "none"; state.partSel = i;
  $("cutHint").textContent = `Rozdelené v čase ${fmtTimeMs(t)}. Vyber časť a vystrihni ju, alebo nižšie nastav prechod medzi časťami.`;
  cutsChanged();
}
function toggleOff(i) {
  const p = state.parts[i]; if (!p) return;
  if (!p.off && state.parts.filter((x) => !x.off).length === 1) { $("cutHint").textContent = "Celé video vystrihnúť nejde, aspoň jedna časť musí ostať."; return; }
  p.off = !p.off;
  $("cutHint").textContent = p.off ? `Časť ${i + 1} je vystrihnutá. Prehrávanie aj export ju preskočia.` : `Časť ${i + 1} je späť vo videu.`;
  if (p.off && video.currentTime >= p.start && video.currentTime < p.end) { const j = nextKept(i) >= 0 ? nextKept(i) : prevKept(i); if (j >= 0) video.currentTime = state.parts[j].start + 0.001; }
  cutsChanged();
}
const fmtTimeMs = (s) => `${Math.floor(s / 60)}:${(s % 60).toFixed(1).padStart(4, "0")}`;
function selectPart(i, seek = false) {
  state.partSel = clamp(i, 0, state.parts.length - 1);
  if (seek) video.currentTime = state.parts[state.partSel].start + 0.001;
  renderCutUi();
}
function renderCuts(dur, px) { // vrstva na časovej osi: vystrihnuté časti, miesta strihu, prechody, vybraná časť
  const box = $("tlCuts"); if (!box) return; box.textContent = ""; box.style.width = Math.round(dur * px) + "px";
  state.parts.forEach((p, i) => {
    const l = Math.round(p.start * px), w = Math.max(1, Math.round((p.end - p.start) * px));
    if (p.off) { const d = document.createElement("div"); d.className = "cut-off"; d.style.left = l + "px"; d.style.width = w + "px"; const sp = document.createElement("span"); sp.textContent = "Vystrihnuté"; d.append(sp); box.append(d); }
    if (i > 0) { const c = document.createElement("div"); c.className = "cut-line"; c.style.left = l + "px"; box.append(c); }
    if (i === state.partSel && state.parts.length > 1) { const sl = document.createElement("div"); sl.className = "cut-sel"; sl.style.left = l + "px"; sl.style.width = w + "px"; box.append(sl); }
    if (!p.off && p.tr !== "none" && nextKept(i) >= 0) {
      const b = document.createElement("button"); b.type = "button"; b.className = "cut-tr"; b.style.left = Math.round(p.end * px) + "px"; b.textContent = "✦";
      b.title = "Prechod: " + trName(p.tr) + " · klikni a uprav"; b.setAttribute("aria-label", b.title);
      b.onclick = (e) => { e.stopPropagation(); selectPart(i); };
      b.onpointerdown = (e) => e.stopPropagation();
      box.append(b);
    }
  });
}
function renderCutUi() {
  if (!state.parts.length) return;
  renderCuts(tlDuration(), state.tlPxPerSec);
  const list = $("parts"); if (!list) return; list.textContent = "";
  state.parts.forEach((p, i) => {
    const r = document.createElement("div"); r.className = "part" + (p.off ? " off" : "") + (i === state.partSel ? " on" : "");
    r.setAttribute("role", "option"); r.setAttribute("aria-selected", String(i === state.partSel)); r.tabIndex = 0;
    const n = document.createElement("span"); n.className = "pn"; n.textContent = i + 1;
    const t = document.createElement("span"); t.className = "pt";
    const tt = document.createElement("span"); tt.className = "ptt"; tt.textContent = `${fmtTimeMs(p.start)} – ${fmtTimeMs(p.end)}`; t.append(tt);
    if (!p.off && p.tr !== "none" && nextKept(i) >= 0) { const tr = document.createElement("span"); tr.className = "ptr"; tr.textContent = "→ " + trName(p.tr); t.append(tr); }
    const d = document.createElement("span"); d.className = "pd"; d.textContent = p.off ? "vystrihnuté" : (p.end - p.start).toFixed(1).replace(".", ",") + " s";
    r.append(n, t, d);
    r.onclick = () => selectPart(i, true);
    r.onkeydown = (e) => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); selectPart(i, true); } };
    list.append(r);
  });
  $("cutLen").textContent = fmtTime(outDuration()) + (hasCuts() ? ` (z ${fmtTime(tlDuration())})` : "");
  $("cutReset").hidden = !hasCuts();
  const sel = state.parts[state.partSel];
  $("cutDelLabel").textContent = sel && sel.off ? "Vrátiť časť" : "Vystrihnúť časť";
  $("cutDel").disabled = state.parts.length < 2 && !(sel && sel.off);
  // prechod za vybranou časťou
  const j = sel && !sel.off ? nextKept(state.partSel) : -1, can = j >= 0;
  $("trLabel").textContent = can ? `Prechod: časť ${state.partSel + 1} → ${j + 1}` : "Prechod";
  document.querySelectorAll("#trGrid .trt").forEach((b) => { const on = can && b.dataset.tr === sel.tr; b.classList.toggle("on", on); b.setAttribute("aria-checked", String(on)); b.disabled = !can; });
  $("trDur").disabled = !can || sel.tr === "none"; if (can) $("trDur").value = sel.td;
  $("trDurOut").textContent = (can ? sel.td : 0.6).toFixed(1).replace(".", ",") + " s";
  $("trHint").textContent = can ? "Prechod sa prejaví na konci tejto časti a na začiatku nasledujúcej. Pusti video kúsok pred strihom a uvidíš ho v náhľade."
    : state.parts.length < 2 ? "Prechod sa pridáva medzi dve časti. Najprv video rozdeľ tlačidlom Rozdeliť tu."
    : "Vyber ponechanú časť, za ktorou nasleduje ďalšia, a nastav jej prechod.";
}
// Efekt prechodu pri kreslení snímky. Video sa kreslí cez drawVideo, prekrytie (čierna/biela) až po titulkoch.
function drawVideoFx(ctx, vid, W, H, r, fx) {
  if (!fx || fx.type === "fade" || fx.type === "white" || fx.type === "none") { ctx.drawImage(vid, r.dx, r.dy, r.iw, r.ih); return; }
  const e = fx.p * fx.p; ctx.save();
  if (fx.type === "blur" && ctx.filter !== undefined) ctx.filter = `blur(${(e * Math.min(W, H) * 0.04).toFixed(1)}px)`;
  if (fx.type === "zoom") { const k = 1 + 0.7 * e; ctx.translate(W / 2, H / 2); ctx.scale(k, k); ctx.translate(-W / 2, -H / 2); }
  if (fx.type === "whip") { ctx.translate((fx.out ? -1 : 1) * e * W * 0.6, 0); if (ctx.filter !== undefined) ctx.filter = `blur(${(e * W * 0.02).toFixed(1)}px)`; }
  ctx.drawImage(vid, r.dx, r.dy, r.iw, r.ih);
  if (fx.type === "whip") ctx.drawImage(vid, r.dx + (fx.out ? 1 : -1) * W * 0.6, r.dy, r.iw, r.ih); // druhá kópia, aby za pohybom neostala čierna
  ctx.restore();
}
function drawFxOverlay(ctx, W, H, fx) {
  if (!fx) return;
  if (fx.type === "fade") { ctx.fillStyle = `rgba(0,0,0,${fx.p.toFixed(3)})`; ctx.fillRect(0, 0, W, H); }
  if (fx.type === "white") { ctx.fillStyle = `rgba(255,255,255,${(fx.p * fx.p).toFixed(3)})`; ctx.fillRect(0, 0, W, H); }
}
const fxGain = (fx) => (fx && fx.type === "fade" ? 1 - fx.p : 1); // stmievačka stíši aj zvuk

function renderFrame(ctx, vid, t, W, H, stOf, crop) {
  ctx.globalAlpha = 1; ctx.textAlign = "left"; ctx.textBaseline = "alphabetic"; ctx.shadowColor = "transparent"; ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
  if (ctx.filter !== undefined) ctx.filter = "none";
  if ("letterSpacing" in ctx) ctx.letterSpacing = "0px";
  ctx.fillStyle = "#000"; ctx.fillRect(0, 0, W, H);
  const vw = vid.videoWidth, vh = vid.videoHeight;
  const fx = transFx(t);
  if (vw && vh && vid.readyState >= 2) { const r = cropRect(vw, vh, W, H, crop); drawVideoFx(ctx, vid, W, H, r, fx); }
  if (stOf(1).on) {
    drawDynamic(ctx, t, W, H, stOf);
    const i = segmentAt(t);
    if (i >= 0 && state.segments[i].text.trim()) { const sg = state.segments[i], st = stOf(sg.speaker || 1, sg); if (!st.dynamic) drawCaption(ctx, sg, t, W, H, st); }
  }
  drawFxOverlay(ctx, W, H, fx);
}
let lastActive = -2;
function loop() {
  if (!$("view-editor").hidden) {
    const t = video.currentTime, dpr = window.devicePixelRatio || 1, label = `${fmtTime(t)} / ${fmtTime(state.duration || video.duration || 0)}`;
    const o = state.out;
    if (o) {
      const [ow, oh] = OUT[o.k], ratio = ow / oh, avail = o.pv.clientWidth || 100;
      const h = Math.round(Math.min(PV_MAX_H, avail / ratio)), w = Math.round(h * ratio);
      if (o.cv.style.width !== w + "px" || o.cv.style.height !== h + "px") { o.cv.style.width = w + "px"; o.cv.style.height = h + "px"; }
      if (o.cv.width !== Math.round(w * dpr) || o.cv.height !== Math.round(h * dpr)) { o.cv.width = Math.round(w * dpr); o.cv.height = Math.round(h * dpr); }
      const ctx = o.cv.getContext("2d"); ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      if (!video.paused && !video.seeking) { // prehrávanie preskakuje vystrihnuté časti
        const step = cutStep(t);
        if (step === "end") { video.pause(); video.currentTime = firstKeptStart() + 0.001; }
        else if (step != null) video.currentTime = step;
      }
      renderFrame(ctx, video, t, w, h, (sp, seg) => styleFor(o.k, sp, seg), state.crop[o.k]);
      video.volume = fxGain(transFx(t));
      if (hasCuts() && state.parts[partAt(t)].off) { // zastavené vo vystrihnutej časti: jasne to ukáž
        ctx.fillStyle = "rgba(6,9,22,.72)"; ctx.fillRect(0, 0, w, h);
        ctx.fillStyle = "#ff9aab"; ctx.font = `600 ${Math.round(Math.min(w, h) * 0.06)}px Inter,sans-serif`; ctx.textAlign = "center"; ctx.textBaseline = "middle";
        ctx.fillText("Vystrihnuté", w / 2, h / 2); ctx.textAlign = "left";
      }
      o.time.textContent = label;
      o.play.textContent = video.paused ? "▶ Prehrať" : "■ Stop";
    }
    $("tlPlay").textContent = video.paused ? "▶" : "❚❚";
    syncTimelinePlayhead();
    const i = segmentAt(t);
    if (i !== lastActive && !state.tlDragging) {
      lastActive = i; state.activeSeg = i; markActiveSeg();
      // zámerne sa tu nescrolluje zoznam titulkov — pri prehrávaní by to „skákalo“, ak si v zozname vyscrolloval inam
    }
  }
  requestAnimationFrame(loop);
}

// ---------- rečníci: odhad podľa výšky hlasu a zafarbenia ----------
// Jednoduchý odhad bez ďalšieho modelu: pre každý titulok sa z jeho zvuku zmeria základná výška hlasu (F0)
// a spektrálny odtlačok (MFCC), potom sa titulky rozdelia do dvoch skupín. Spoľahlivé pri odlišných hlasoch
// (napr. muž a žena), pri podobných hlasoch môže chybovať, vtedy sa rečník prepne ručne.
function fftInPlace(re, im) {
  const n = re.length;
  for (let i = 1, j = 0; i < n; i++) { let bit = n >> 1; for (; j & bit; bit >>= 1) j ^= bit; j ^= bit; if (i < j) { [re[i], re[j]] = [re[j], re[i]]; [im[i], im[j]] = [im[j], im[i]]; } }
  for (let len = 2; len <= n; len <<= 1) {
    const ang = -2 * Math.PI / len, wr = Math.cos(ang), wi = Math.sin(ang), h = len / 2;
    for (let i = 0; i < n; i += len) {
      let cr = 1, ci = 0;
      for (let j = 0; j < h; j++) {
        const a = i + j, b = a + h, vr = re[b] * cr - im[b] * ci, vi = re[b] * ci + im[b] * cr;
        re[b] = re[a] - vr; im[b] = im[a] - vi; re[a] += vr; im[a] += vi;
        const nr = cr * wr - ci * wi; ci = cr * wi + ci * wr; cr = nr;
      }
    }
  }
}
const SPK = { N: 512, HOP: 256, SR: 16000, MEL: 24, NC: 12 };
let melBank = null;
function buildMel() {
  const { N, SR, MEL } = SPK, hz2mel = (f) => 2595 * Math.log10(1 + f / 700), mel2hz = (m) => 700 * (Math.pow(10, m / 2595) - 1);
  const lo = hz2mel(80), hi = hz2mel(6000), pts = Array.from({ length: MEL + 2 }, (_, i) => mel2hz(lo + (hi - lo) * i / (MEL + 1)));
  const bins = pts.map((f) => Math.floor((N + 1) * f / SR)), bank = [];
  for (let m = 1; m <= MEL; m++) {
    const f = new Float32Array(N / 2 + 1);
    for (let k = bins[m - 1]; k < bins[m]; k++) f[k] = (k - bins[m - 1]) / Math.max(1, bins[m] - bins[m - 1]);
    for (let k = bins[m]; k < bins[m + 1]; k++) f[k] = (bins[m + 1] - k) / Math.max(1, bins[m + 1] - bins[m]);
    bank.push(f);
  }
  return bank;
}
function segmentFeatures(audio, start, end) {
  const { N, HOP, SR, MEL, NC } = SPK;
  if (!melBank) melBank = buildMel();
  const a = Math.max(0, Math.floor(start * SR)), b = Math.min(audio.length - N, Math.floor(end * SR));
  if (b - a < N * 4) return null;
  const all = []; for (let p = a; p <= b; p += HOP) all.push(p);
  const step = Math.max(1, Math.ceil(all.length / 90)), pos = all.filter((_, i) => i % step === 0);
  const rms = pos.map((p) => { let e = 0; for (let i = 0; i < N; i++) e += audio[p + i] * audio[p + i]; return Math.sqrt(e / N); });
  const thr = Math.max(0.008, 0.25 * Math.max(...rms));
  const f0s = [], sums = new Float64Array(NC); let voiced = 0;
  const win = Float32Array.from({ length: N }, (_, i) => 0.54 - 0.46 * Math.cos(2 * Math.PI * i / (N - 1)));
  pos.forEach((p, idx) => {
    if (rms[idx] < thr) return;
    // F0: autokorelácia na 8 kHz, rozsah 70 až 400 Hz
    const x = new Float32Array(N / 2); let mean = 0;
    for (let i = 0; i < N / 2; i++) { x[i] = (audio[p + 2 * i] + audio[p + 2 * i + 1]) / 2; mean += x[i]; }
    mean /= x.length; for (let i = 0; i < x.length; i++) x[i] -= mean;
    let r0 = 0; for (let i = 0; i < x.length; i++) r0 += x[i] * x[i];
    if (r0 < 1e-9) return;
    const ac = []; let rmax = 0;
    for (let lag = 20; lag <= 115; lag++) { let r = 0; for (let i = 0; i + lag < x.length; i++) r += x[i] * x[i + lag]; r /= r0; ac[lag] = r; if (r > rmax) rmax = r; }
    if (rmax < 0.45) return;
    let best = 0; for (let lag = 21; lag < 115; lag++) if (ac[lag] >= 0.9 * rmax && ac[lag] >= ac[lag - 1] && ac[lag] >= ac[lag + 1]) { best = lag; break; }
    if (!best) return;
    f0s.push(8000 / best);
    // MFCC
    const re = new Float64Array(N), im = new Float64Array(N);
    for (let i = 0; i < N; i++) re[i] = audio[p + i] * win[i];
    fftInPlace(re, im);
    const pw = new Float64Array(N / 2 + 1); for (let k = 0; k <= N / 2; k++) pw[k] = re[k] * re[k] + im[k] * im[k];
    const lm = melBank.map((f) => { let e = 0; for (let k = 0; k < f.length; k++) e += f[k] * pw[k]; return Math.log(e + 1e-10); });
    for (let c = 1; c <= NC; c++) { let v = 0; for (let m = 0; m < MEL; m++) v += lm[m] * Math.cos(Math.PI * c * (m + 0.5) / MEL); sums[c - 1] += v; }
    voiced++;
  });
  if (voiced < 8) return null;
  f0s.sort((u, v) => u - v);
  return [Math.log2(f0s[f0s.length >> 1]), ...Array.from(sums, (v) => v / voiced)];
}
// Rozdelí titulky na dvoch rečníkov (1 = ten, kto hovorí prvý). Vráti počet nájdených rečníkov.
function detectSpeakers(dbgOut) {
  const segs = state.segments, audio = state.audio;
  segs.forEach((s) => { s.speaker = 1; });
  if (!audio || segs.length < 4) return 1;
  const feats = segs.map((s) => segmentFeatures(audio, s.start, s.end)), idx = feats.map((f, i) => (f ? i : -1)).filter((i) => i >= 0);
  if (idx.length < 4) return 1;
  const D = feats[idx[0]].length, mu = Array(D).fill(0), sd = Array(D).fill(0);
  idx.forEach((i) => feats[i].forEach((v, d) => { mu[d] += v / idx.length; }));
  idx.forEach((i) => feats[i].forEach((v, d) => { sd[d] += (v - mu[d]) ** 2 / idx.length; }));
  const X = idx.map((i) => feats[i].map((v, d) => (v - mu[d]) / (Math.sqrt(sd[d]) || 1) * (d === 0 ? 4 : 1))); // výška hlasu má väčšiu váhu
  const dist = (p, q) => Math.sqrt(p.reduce((a, v, d) => a + (v - q[d]) ** 2, 0));
  let lo = 0, hi = 0; X.forEach((p, i) => { if (p[0] < X[lo][0]) lo = i; if (p[0] > X[hi][0]) hi = i; });
  let c = [X[lo].slice(), X[hi].slice()], lab = new Array(X.length).fill(0);
  for (let it = 0; it < 25; it++) {
    lab = X.map((p) => (dist(p, c[0]) <= dist(p, c[1]) ? 0 : 1));
    const nc = [0, 1].map((g) => { const m = Array(D).fill(0); let n = 0; X.forEach((p, i) => { if (lab[i] === g) { n++; p.forEach((v, d) => { m[d] += v; }); } }); return n ? m.map((v) => v / n) : c[g]; });
    if (nc.every((q, g) => dist(q, c[g]) < 1e-6)) break; c = nc;
  }
  const n1 = lab.filter((v) => v === 1).length, n0 = X.length - n1;
  const within = X.reduce((a, p, i) => a + dist(p, c[lab[i]]), 0) / X.length, between = dist(c[0], c[1]);
  const f0gap = Math.abs((c[0][0] - c[1][0]) / 4 * Math.sqrt(sd[0] || 1)); // rozdiel výšky hlasu v oktávach
  // Oddelenosť výšky hlasu: rozdiel priemerov oproti rozptylu v skupinách. Jeden hlas rozdelený nasilu
  // vychádza okolo 2,5, dvaja rôzni ľudia výrazne viac. Bez jasného rozdielu ostáva jeden rečník.
  const f0 = (g) => idx.map((i, j) => (lab[j] === g ? feats[i][0] : null)).filter((v) => v !== null);
  const stat = (a) => { const m = a.reduce((x, v) => x + v, 0) / a.length; return [m, Math.sqrt(a.reduce((x, v) => x + (v - m) ** 2, 0) / a.length)]; };
  const [m0, s0] = stat(f0(0)), [m1, s1] = stat(f0(1)), sep = Math.abs(m0 - m1) / (Math.sqrt((s0 * s0 + s1 * s1) / 2) || 1e-6);
  if (dbgOut) dbgOut.push({ n0, n1, ratio: between / (within || 1), f0gap, sep });
  if (idx.length < 6 || Math.min(n0, n1) < Math.max(3, 0.15 * X.length) || between / (within || 1) < 1.8 || f0gap < 0.33 || sep < 2.8) return 1;
  const first = lab[0]; // rečník 1 = ten, kto hovorí v prvom titulku
  let prev = 1;
  segs.forEach((sg, i) => { const j = idx.indexOf(i); if (j >= 0) prev = lab[j] === first ? 1 : 2; sg.speaker = prev; });
  return 2;
}
// Voliteľné: rozpoznať druhý hlas a rozdeliť titulky (spúšťa sa len tlačidlom, nikdy automaticky).
function runDetectSpeakers() {
  const btn = $("spkDetect"); btn.disabled = true;
  const label = btn.lastChild.textContent; btn.lastChild.textContent = " Počúvam hlasy…";
  setTimeout(() => {
    let found = 1;
    const before = state.segments.map((s) => s.speaker || 1);
    try { found = detectSpeakers(); } catch (e) { console.error("Rečníci:", e); found = 1; }
    if (found > 1) {
      const second = state.spk[1] ? state.spk[1].id : addSpeaker(false);
      state.segments.forEach((s) => { s.speaker = s.speaker === 2 ? second : 1; });
      state.cur = second; speakersChanged();
      $("spkInfo").textContent = "Rozpoznané 2 hlasy. Skontroluj priradenie v zozname titulkov, zlé zmeníš značkou pri texte.";
    } else {
      state.segments.forEach((s, i) => { s.speaker = before[i]; });
      renderSpkList();
      $("spkInfo").textContent = "Rozpoznaný len jeden hlas. Ďalšieho rečníka môžeš pridať ručne tlačidlom Pridať.";
    }
    btn.lastChild.textContent = label; btn.disabled = !state.audio;
  }, 30);
}

// ---------- stránky v bočnej lište: Titulkovanie (práca s videom), Knižnica štýlov, Značka (fonty) ----------
let page = "work";
function setPage(p) {
  page = p; closeSpkMenu();
  if (p !== "work") video.pause();
  $("content").classList.toggle("alt-page", p !== "work");
  $("page-brand").hidden = p !== "brand"; $("page-library").hidden = p !== "library";
  document.querySelectorAll(".nav[data-page]").forEach((a) => { const on = a.dataset.page === p; a.classList.toggle("on", on); if (on) a.setAttribute("aria-current", "page"); else a.removeAttribute("aria-current"); });
  if (p === "brand") renderBrand();
  if (p === "library") { renderLibrary(); requestAnimationFrame(libLoop); }
  scrollTo(0, 0);
}
// Značka: len fonty. Každý font má živú ukážku textu, vlastné sa dajú pridať a odobrať.
function renderBrand() {
  const grid = $("fontGrid"); grid.textContent = "";
  const text = $("fontSampleText").value.trim() || "Ahoj, toto sú moje titulky", cur = activeStyle().font;
  [...customFonts, ...FONTS].forEach((name) => {
    const own = customFonts.has(name);
    const c = document.createElement("div"); c.className = "fcard" + (name === cur ? " on" : "");
    const smp = document.createElement("div"); smp.className = "fsample"; smp.textContent = text; smp.style.fontFamily = `"${name}", sans-serif`; smp.style.fontWeight = name === "Bebas Neue" ? 400 : 700;
    const meta = document.createElement("div"); meta.className = "fmeta";
    const nm = document.createElement("b"); nm.textContent = name; nm.title = name;
    const tag = document.createElement("span"); tag.className = "ftag" + (own ? " own" : ""); tag.textContent = own ? "Vlastný" : "Vstavaný";
    meta.append(nm, tag);
    if (own) {
      const del = document.createElement("button"); del.type = "button"; del.className = "spk-del"; del.title = "Odobrať font"; del.setAttribute("aria-label", "Odobrať font " + name);
      del.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>';
      del.onclick = () => removeFont(name); meta.append(del);
    }
    const use = document.createElement("button"); use.type = "button"; use.className = "fuse"; use.textContent = name === cur ? "Použitý" : "Použiť";
    use.onclick = () => { const st = activeStyle(); st.font = name; st.preset = null; bumpDyn(); syncControls(); renderBrand(); $("brandMsg").textContent = `Titulky${multiSpk() ? " rečníka " + spkById(state.cur).name : ""} teraz používajú font „${name}“.`; };
    meta.append(use);
    c.append(smp, meta); grid.append(c);
  });
}
async function removeFont(name) {
  customFonts.delete(name); const f = fontFaces.get(name); if (f) { document.fonts.delete(f); fontFaces.delete(name); }
  [...$("font").options].forEach((o) => { if (o.value === name) o.remove(); });
  [state.style, ...Object.values(state.spkStyle)].forEach((st) => { if (st.font === name) st.font = "Inter"; });
  try { const db = await fontsDb(); db.transaction("fonts", "readwrite").objectStore("fonts").delete(name); } catch {}
  bumpDyn(); syncControls(); renderBrand(); $("brandMsg").textContent = `Font „${name}“ je odobratý.`;
}
// Knižnica: animovaná ukážka každého štýlu titulkov, kreslená tým istým kódom ako náhľad a export.
const LIB_LOOP = 4.6;
const LIB_SEGS = (() => {
  const mk = (text, start, end) => { const ws = text.split(" "), d = (end - start) / ws.length; return { text, start, end, speaker: 1, words: ws.map((w, i) => ({ w, start: start + i * d, end: start + (i + 1) * d })) }; };
  return [mk("Takto budú vyzerať", 0.3, 1.9), mk("tvoje titulky vo videu", 2.05, 3.9)];
})();
let LIB_CHUNKS = null;
let libCards = [];
function renderLibrary() {
  const grid = $("libGrid"); grid.textContent = ""; libCards = []; LIB_CHUNKS = LIB_CHUNKS || buildChunks(LIB_SEGS);
  const curPreset = activeStyle().preset;
  PRESETS.forEach((p) => {
    const c = document.createElement("div"); c.className = "lcard" + (p.id === curPreset ? " on" : "");
    const cv = document.createElement("canvas"); cv.width = 400; cv.height = 500; cv.setAttribute("role", "img"); cv.setAttribute("aria-label", "Animovaná ukážka štýlu " + p.name);
    const foot = document.createElement("div"); foot.className = "lfoot";
    const nm = document.createElement("b"); nm.textContent = p.name;
    const use = document.createElement("button"); use.type = "button"; use.className = "fuse"; use.textContent = p.id === curPreset ? "Použitý" : "Použiť";
    use.onclick = () => { applyPreset(p); setPage("work"); };
    foot.append(nm, use); c.append(cv, foot); grid.append(c);
    libCards.push({ cv, ctx: cv.getContext("2d"), st: { ...p, preset: p.id, on: true, x: 0.5, y: p.y ?? 0.78, position: null, highlight: p.highlight ?? true } });
  });
}
function libBackdrop(ctx, W, H, t) { // pohyblivé farebné pozadie namiesto videa
  const g = ctx.createLinearGradient(0, 0, W, H), a = (t / LIB_LOOP) * Math.PI * 2;
  g.addColorStop(0, `hsl(${235 + 20 * Math.sin(a)},45%,22%)`); g.addColorStop(1, `hsl(${200 + 25 * Math.cos(a)},50%,14%)`);
  ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);
  ctx.fillStyle = "rgba(255,255,255,.05)"; ctx.beginPath(); ctx.arc(W * (0.5 + 0.2 * Math.sin(a)), H * 0.38, W * 0.28, 0, Math.PI * 2); ctx.fill();
}
function libLoop(now) {
  if (page !== "library") return;
  const t = (now / 1000) % LIB_LOOP;
  for (const c of libCards) {
    const { ctx, st } = c, W = c.cv.width, H = c.cv.height;
    ctx.save(); libBackdrop(ctx, W, H, t);
    if (st.dynamic) { for (const ch of LIB_CHUNKS) if (t >= ch.start - ENTER && t < ch.exitStart + ch.exitDur) drawChunk(ctx, ch, t, W, H, st); }
    else { const sg = LIB_SEGS.find((x) => t >= x.start && t < x.end + 0.25); if (sg) drawCaption(ctx, sg, t, W, H, st); }
    ctx.restore();
  }
  requestAnimationFrame(libLoop);
}

// ---------- vlastný font (nahratý súbor, ostáva v tomto prehliadači) ----------
function fontsDb() {
  return new Promise((res, rej) => {
    const r = indexedDB.open("titulky-fonts", 1);
    r.onupgradeneeded = () => r.result.createObjectStore("fonts");
    r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error);
  });
}
const customFonts = new Set(), fontFaces = new Map();
function addFontOption(name) {
  if ([...$("font").options].some((o) => o.value === name)) return;
  const o = document.createElement("option"); o.value = o.textContent = name; $("font").append(o);
}
async function registerFont(name, buf) {
  const face = new FontFace(name, buf, { weight: "100 900", style: "normal" }); // jeden súbor platí pre všetky hrúbky
  await face.load(); document.fonts.add(face); customFonts.add(name); fontFaces.set(name, face); addFontOption(name);
}
async function loadSavedFonts() {
  try {
    const db = await fontsDb();
    const all = await new Promise((res, rej) => { const q = db.transaction("fonts").objectStore("fonts").getAll(); q.onsuccess = () => res(q.result); q.onerror = () => rej(q.error); });
    for (const f of all) { try { await registerFont(f.name, f.buf.slice(0)); } catch { /* poškodený súbor sa preskočí */ } }
  } catch { /* bez trvalého úložiska ostanú fonty len do zatvorenia stránky */ }
}
async function uploadFont(file, fromBrand = false) {
  const msg = (t) => { $(fromBrand ? "brandMsg" : "fontMsg").textContent = t; };
  msg("");
  if (!/\.(ttf|otf|woff2?)$/i.test(file.name)) { msg(`„${file.name}“ nie je font. Použi súbor TTF, OTF, WOFF alebo WOFF2.`); return; }
  if (file.size > 15 * 1048576) { msg("Font je príliš veľký (najviac 15 MB)."); return; }
  const name = "Vlastný " + file.name.replace(/\.[^.]+$/, "").replace(/[^\p{L}\p{N} _-]/gu, " ").trim().slice(0, 40);
  try {
    const buf = await file.arrayBuffer();
    await registerFont(name, buf.slice(0));
    try { const db = await fontsDb(); db.transaction("fonts", "readwrite").objectStore("fonts").put({ name, buf }, name); } catch {}
    if (fromBrand) { msg(`Font „${name}“ je pridaný.`); renderBrand(); return; }
    const st = activeStyle(); st.font = name; st.preset = null; syncControls();
    msg(`Font „${name}“ je pridaný a vybraný.`);
  } catch { msg("Tento súbor sa nepodarilo načítať ako font."); }
}

// ---------- ovládacie prvky ----------
function renderSample(box, st, scale = 1) {
  box.textContent = "";
  const word = (t) => (st.caps ? t.toUpperCase() : t);
  if (st.dynamic) {
    const wrap = document.createElement("div");
    const a = document.createElement("div"); a.textContent = word("text"); a.style.cssText = `font:800 ${19 * scale}px "${st.font}",sans-serif;color:${st.color}`;
    const b = document.createElement("div"); b.textContent = word("effect"); b.style.cssText = `font:800 ${11 * scale}px "${st.font}",sans-serif;color:${st.color}`;
    wrap.append(a, b); box.append(wrap); return;
  }
  const sp = document.createElement("span"); sp.className = "t"; sp.textContent = "Ahoj svet";
  sp.style.cssText = `font-family:"${st.font}",sans-serif;font-weight:${st.weight ?? (st.bold ? 700 : 400)};font-style:${st.italic ? "italic" : "normal"};font-size:${16 * scale}px;color:${st.color}`;
  if (st.bgOpacity > 0) sp.style.background = `rgba(${rgbOf(st.bg)},${st.bgOpacity / 100})`;
  else { const c = st.stroke || "#000", o = st.stroke ? 2 : 1; sp.style.textShadow = `-${o}px 0 ${c},${o}px 0 ${c},0 -${o}px ${c},0 ${o}px ${c},-${o}px -${o}px ${c},${o}px ${o}px ${c},-${o}px ${o}px ${c},${o}px -${o}px ${c}`; if (st.highlight) { sp.textContent = ""; const a = document.createElement("span"); a.textContent = "Ahoj "; const b2 = document.createElement("span"); b2.textContent = "svet"; b2.style.color = st.hl; sp.append(a, b2); } }
  box.append(sp);
}
function applyPreset(p) {
  if (state.cur !== 1 && state.spkStyle[state.cur]) {
    const o = state.spkStyle[state.cur], id = state.cur;
    state.spkStyle[id] = { ...p, preset: p.id, highlight: p.highlight ?? o.highlight, caps: p.caps ?? o.caps };
    if (p.y !== undefined) ASPECTS.forEach((a) => { Object.assign(getPos(a.k, id), { x: 0.5, y: p.y, position: p.position ?? null }); });
  } else {
    const o = state.style;
    state.style = { ...p, preset: p.id, highlight: p.highlight ?? o.highlight, caps: p.caps ?? o.caps, on: o.on };
    if (p.y !== undefined) ASPECTS.forEach((a) => { state.pos[a.k] = { x: 0.5, y: p.y, position: p.position ?? null }; });
  }
  bumpDyn(); syncControls();
}
function markActive() {
  const q = getPos(state.aspect, editSpk());
  $("posLabel").textContent = "Pozícia" + (multiSpk() ? " — " + spkById(editSpk()).name : "") + " · " + state.aspect;
  document.querySelectorAll("#positions .opt").forEach((b) => b.classList.toggle("on", !!q && b.dataset.v === q.position));
  document.querySelectorAll("#aspects .opt").forEach((b) => b.classList.toggle("on", b.dataset.v === state.aspect));
  if (state.out) state.out.cv.classList.toggle("crop", state.tool === "crop");
  const c = state.crop[state.aspect]; if (c) { $("zoom").value = c.zoom; $("zoomOut").textContent = `${state.aspect} · ${c.zoom.toFixed(2)}×`; }
  refreshQuality();
}
function syncControls() {
  const st = activeStyle();
  $("color").value = st.color; $("bg").value = st.bg; $("bgOpacity").value = st.bgOpacity;
  $("bgOpacityOut").textContent = Math.round(st.bgOpacity) + " %"; $("size").value = st.size; $("sizeOut").textContent = st.size;
  $("font").value = st.font; $("highlight").checked = !!st.highlight; $("hl").value = st.hl; $("hl").disabled = !st.highlight;
  $("roam").checked = !!st.roam; $("caps").checked = !!st.caps;
  $("subsOn").setAttribute("aria-checked", String(state.style.on));
  document.querySelectorAll("#styles .tile").forEach((t) => t.classList.toggle("on", t.dataset.id === st.preset));
  $("styleLabel").textContent = "Štýl" + (multiSpk() ? " — " + spkById(state.cur).name : "");
  $("segments").classList.toggle("spk-on", multiSpk());
  $("styleCustom").hidden = !!st.preset;
  markActive(); invalidateResults();
}

// ---------- export: video + titulky nahrané z plátna ----------
async function recordAspect(aspect, onProgress, job) {
  if (!window.MediaRecorder) throw new Error("Tento prehliadač nevie nahrávať video. Použi Chrome, Edge alebo Firefox.");
  const { W, H } = exportSize(aspect), st = styleFor(aspect), stOf = (sp, seg) => styleFor(aspect, sp, seg), crop = { ...state.crop[aspect] };
  await Promise.all([st, ...state.spk.map((x) => stOf(x.id)), ...state.segments.filter((x) => x.styleOv).map((x) => stOf(x.speaker || 1, x))].flatMap((x) => [400, 700, 800].map((wt) => document.fonts.load(`${x.italic ? "italic " : ""}${wt} 40px "${x.font}"`).catch(() => {}))));
  const canvas = document.createElement("canvas"); canvas.width = W; canvas.height = H;
  const ctx = canvas.getContext("2d");
  const ev = document.createElement("video"); ev.src = state.url; ev.playsInline = true;
  await new Promise((res, rej) => { ev.onloadeddata = res; ev.onerror = () => rej(new Error("Video sa nepodarilo načítať.")); });
  if (hasCuts()) { ev.currentTime = firstKeptStart() + 0.001; await new Promise((res) => { ev.onseeked = res; }); ev.onseeked = null; }
  const stream = canvas.captureStream(); // nová snímka pri každej snímke originálu = rovnaká plynulosť (aj 60 fps)
  const ac = new (window.AudioContext || window.webkitAudioContext)();
  let gain = null;
  try {
    const dest = ac.createMediaStreamDestination();
    gain = ac.createGain(); ac.createMediaElementSource(ev).connect(gain); gain.connect(dest);
    dest.stream.getAudioTracks().forEach((t) => stream.addTrack(t));
  } catch {}
  const types = ["video/mp4;codecs=avc1,mp4a.40.2", "video/webm;codecs=vp9,opus", "video/webm;codecs=vp8,opus", "video/webm"];
  const mime = types.find((t) => MediaRecorder.isTypeSupported(t)) || "";
  const rec = new MediaRecorder(stream, { mimeType: mime || undefined, videoBitsPerSecond: Math.round(clamp(W * H * 6, 5e6, 50e6)) }); // ~12 Mb/s pri 1080p, ~50 Mb/s pri 4K
  const chunks = []; rec.ondataavailable = (e) => e.data.size && chunks.push(e.data);
  const finished = new Promise((res) => (rec.onstop = res));
  let live = true, jumping = false;
  const total = hasCuts() ? outDuration() : (ev.duration || 1);
  const schedule = () => (ev.requestVideoFrameCallback ? ev.requestVideoFrameCallback(draw) : requestAnimationFrame(draw));
  const finish = () => { if (!live) return; live = false; ev.pause(); setTimeout(() => rec.stop(), 150); };
  const jumpTo = (t) => { // strih: nahrávanie sa na chvíľu zastaví, aby sa skok do ďalšej časti nenahral ako zamrznutý obraz
    jumping = true; ev.pause(); try { rec.pause(); } catch {}
    ev.addEventListener("seeked", () => {
      if (!live) return;
      renderFrame(ctx, ev, ev.currentTime, W, H, stOf, crop);
      try { rec.resume(); } catch {} jumping = false;
      ev.play().then(schedule, schedule);
    }, { once: true });
    ev.currentTime = t;
  };
  const draw = () => {
    if (!live || jumping) return;
    if (job !== state.job) { live = false; ev.pause(); try { rec.stop(); } catch {} return; } // nový projekt: export sa zruší
    const t = ev.currentTime, step = cutStep(t);
    if (step === "end") { finish(); return; }
    if (step != null) { jumpTo(step); return; }
    const fx = transFx(t); if (gain) gain.gain.value = fxGain(fx);
    renderFrame(ctx, ev, t, W, H, stOf, crop);
    onProgress(Math.min(99, Math.round((hasCuts() ? srcToOut(t) || 0 : t) / total * 100)));
    schedule();
  };
  ev.onended = finish;
  await ac.resume(); rec.start(500); draw(); await ev.play();
  await finished; ac.close();
  const ext = rec.mimeType.includes("mp4") ? "mp4" : "webm";
  if (job !== state.job) return null;
  return { url: URL.createObjectURL(new Blob(chunks, { type: rec.mimeType || "video/webm" })), ext };
}
// Len jeden formát je vidno naraz, ale "Exportovať všetko" spracuje aj tie ostatné na pozadí (recordAspect
// si vyrába vlastné video/plátno, nepotrebuje viditeľnú kartu); výsledok sa uloží do state.results a ukáže
// sa hneď, ako na daný formát prepneš ikonkou.
async function exportAspects(list) {
  if (state.busyExport || state.playable === false) return;
  const job = state.job, multi = list.length > 1;
  state.busyExport = true; $("exportAll").disabled = true; $("exportCur").disabled = true; $("export-error").hidden = true;
  try {
    for (let idx = 0; idx < list.length; idx++) {
      const k = list[idx]; if (job !== state.job) break;
      const live = state.out && state.out.k === k ? state.out : null;
      const bar = live ? live.prog.querySelector("i") : null, label = live ? live.prog.querySelector("span") : null;
      if (live) { live.prog.hidden = false; bar.style.width = "0%"; label.textContent = "Pripravujem…"; }
      if (multi) $("exportStatus").textContent = `Exportujem formát ${idx + 1} z ${list.length} (${k})…`;
      try {
        const r = await recordAspect(k, (p) => {
          if (live) { bar.style.width = p + "%"; label.textContent = `Exportujem… ${p} %`; }
          if (multi) $("exportStatus").textContent = `Exportujem formát ${idx + 1} z ${list.length} (${k})… ${p} %`;
        }, job);
        if (!r || job !== state.job) { if (r) URL.revokeObjectURL(r.url); break; }
        state.results[k] = r;
        if (live) {
          if (DEMO) { // v ukážke sa sťahovanie nedá, výsledok sa prehrá priamo v karte
            const v = document.createElement("video"); v.className = "out-result"; v.controls = true; v.playsInline = true; v.src = r.url; live.el.insertBefore(v, live.prog);
          }
          label.textContent = "Hotovo, stiahni tlačidlom ⬇"; bar.style.width = "100%";
          live.dl.classList.add("ready"); live.dl.title = "Stiahnuť hotové video";
        }
      } catch (e) { if (job === state.job) { $("export-error").textContent = e.message || "Export zlyhal."; $("export-error").hidden = false; } if (live) live.prog.hidden = true; }
    }
  } finally {
    state.busyExport = false; $("exportStatus").textContent = "";
    if (job === state.job) { $("exportAll").disabled = state.playable === false; $("exportCur").disabled = state.playable === false; }
  }
}

// ---------- štart ----------
function reset() {
  state.job++; state.busyExport = false; $("exportAll").disabled = false; $("export-error").hidden = true; $("exportStatus").textContent = "";
  video.pause(); video.removeAttribute("src"); invalidateResults();
  if (state.url) { URL.revokeObjectURL(state.url); state.url = null; }
  state.segments = []; state.out = null; state.results = {}; state.playable = null; state.audio = null; resetSpeakers();
  bumpDyn(); $("upload-error").hidden = true; $("file").value = ""; show("upload");
}
function setup() {
  FONTS.forEach((f) => { const o = document.createElement("option"); o.value = o.textContent = f; $("font").append(o); });
  PRESETS.forEach((p) => { // ukážky štýlov
    const t = document.createElement("button"); t.className = "tile"; t.dataset.id = p.id; t.setAttribute("aria-label", "Štýl " + p.name);
    const smp = document.createElement("div"); smp.className = "tsample"; renderSample(smp, { ...p, on: true }, 0.9);
    const nm = document.createElement("span"); nm.className = "tname"; nm.textContent = p.name;
    t.append(smp, nm); t.onclick = () => applyPreset(p); $("styles").append(t);
  });
  document.querySelectorAll("#aspects .opt").forEach((b) => b.onclick = () => { state.aspect = b.dataset.v; buildOut(); syncControls(); });
  document.querySelectorAll("#positions .opt").forEach((b) => b.onclick = () => { const q = getPos(state.aspect, editSpk()); q.position = b.dataset.v; q.x = 0.5; q.y = POS_Y[b.dataset.v]; syncControls(); });
  // Rečníci: pridať, voliteľne rozpoznať podľa hlasu, zavrieť menu pri titulku klikom mimo neho.
  $("spkAdd").onclick = () => { addSpeaker(true); speakersChanged(); const r = $("spkList").lastElementChild; if (r) { const n = r.querySelector(".spk-name"); n.focus(); n.select(); } };
  $("spkDetect").onclick = runDetectSpeakers;
  document.addEventListener("pointerdown", (e) => { const m = document.querySelector(".spk-menu"); if (m && !m.contains(e.target) && e.target !== m._btn && !(m._btn && m._btn.contains(e.target))) closeSpkMenu(); });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && document.querySelector(".spk-menu")) { const b = document.querySelector(".spk-menu")._btn; closeSpkMenu(); if (b) b.focus(); e.stopPropagation(); } }, true);
  addEventListener("resize", closeSpkMenu); document.addEventListener("scroll", (e) => { const m = document.querySelector(".spk-menu"); if (m && !m.contains(e.target)) placeSpkMenu(m); }, true);
  const custom = () => { activeStyle().preset = null; syncControls(); };
  $("subsOn").onclick = () => { state.style.on = !state.style.on; syncControls(); };
  $("color").oninput = (e) => { activeStyle().color = e.target.value; custom(); };
  $("bg").oninput = (e) => { activeStyle().bg = e.target.value; custom(); };
  $("bgOpacity").oninput = (e) => { activeStyle().bgOpacity = +e.target.value; custom(); };
  $("size").oninput = (e) => { activeStyle().size = +e.target.value; custom(); };
  $("font").onchange = (e) => { activeStyle().font = e.target.value; custom(); };
  $("highlight").onchange = (e) => { activeStyle().highlight = e.target.checked; syncControls(); };
  $("hl").oninput = (e) => { activeStyle().hl = e.target.value; syncControls(); };
  $("roam").onchange = (e) => { activeStyle().roam = e.target.checked; syncControls(); };
  $("caps").onchange = (e) => { activeStyle().caps = e.target.checked; syncControls(); };
  $("exportAll").onclick = () => exportAspects(ASPECTS.map((a) => a.k));
  $("dl-srt").onclick = () => saveText(toSrt(cutSegments()), "titulky.srt", "application/x-subrip");
  $("dl-vtt").onclick = () => saveText(toVtt(cutSegments()), "titulky.vtt", "text/vtt");
  $("newproject").onclick = reset;
  document.querySelectorAll("#steps .step").forEach((b) => b.onclick = () => setTab(b.dataset.tab));
  $("exportCur").onclick = () => { const r = state.results[state.aspect]; if (r) saveUrl(r.url, `titulky-${state.aspect.replace(":", "x")}.${r.ext}`); else exportAspects([state.aspect]); };
  $("zoom").oninput = (e) => { const q = state.crop[state.aspect]; if (!q) return; q.zoom = +e.target.value; markActive(); invalidateResults(); };
  $("cropReset").onclick = () => { state.crop[state.aspect] = { zoom: 1, cx: 0.5, cy: 0.5 }; markActive(); invalidateResults(); };
  $("fontUp").onclick = () => $("fontFile").click();
  document.querySelectorAll("#quality .opt").forEach((b) => b.onclick = () => { state.quality = b.dataset.q; invalidateResults(); refreshQuality(); });
  $("fontsAll").onclick = () => setPage("brand");
  // strih
  TRANSITIONS.forEach((x) => {
    const b = document.createElement("button"); b.type = "button"; b.className = "trt"; b.dataset.tr = x.id; b.setAttribute("role", "radio");
    b.innerHTML = `<span class="tv ${x.id}" aria-hidden="true"><i class="a">A</i><i class="b">B</i><i class="w"></i></span>`;
    const nm = document.createElement("span"); nm.textContent = x.name; b.append(nm);
    b.onclick = () => { const p = state.parts[state.partSel]; if (!p || p.off || nextKept(state.partSel) < 0) return; p.tr = x.id; cutsChanged(); if (x.id !== "none") video.currentTime = Math.max(p.start, p.end - Math.max(1.2, p.td + 0.6)); };
    $("trGrid").append(b);
  });
  $("trDur").oninput = (e) => { const p = state.parts[state.partSel]; if (!p) return; p.td = +e.target.value; cutsChanged(); };
  $("cutSplit").onclick = () => splitAt(video.currentTime);
  $("cutDel").onclick = () => toggleOff(state.partSel);
  $("cutReset").onclick = () => { resetCuts(); $("cutHint").textContent = "Strihy sú zrušené, video je opäť celé."; cutsChanged(); };
  document.querySelectorAll(".nav[data-page]").forEach((a) => a.onclick = (e) => { e.preventDefault(); setPage(a.dataset.page); });
  $("brandBack").onclick = $("libBack").onclick = () => setPage("work");
  $("brandFile").onchange = async (e) => { for (const f of e.target.files) await uploadFont(f, true); e.target.value = ""; };
  $("fontDrop").onkeydown = (e) => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); $("brandFile").click(); } };
  ["dragenter", "dragover"].forEach((ev) => $("fontDrop").addEventListener(ev, (e) => { e.preventDefault(); $("fontDrop").classList.add("over"); }));
  ["dragleave", "drop"].forEach((ev) => $("fontDrop").addEventListener(ev, () => $("fontDrop").classList.remove("over")));
  $("fontDrop").addEventListener("drop", async (e) => { e.preventDefault(); for (const f of e.dataTransfer.files) await uploadFont(f, true); });
  $("fontSampleText").addEventListener("input", renderBrand);
  $("fontFile").onchange = (e) => { if (e.target.files[0]) uploadFont(e.target.files[0]); e.target.value = ""; };
  loadSavedFonts();
  $("tlPlay").onclick = () => { if (video.paused) video.play().catch(() => {}); else video.pause(); };
  $("tlSpeed").onchange = (e) => { video.playbackRate = +e.target.value; };
  $("tlZoom").oninput = (e) => { state.tlZoomVal = +e.target.value; renderTimeline(); };
  $("tlZoomIn").onclick = () => { state.tlZoomVal = clamp(state.tlZoomVal + 0.5, 1, 6); $("tlZoom").value = state.tlZoomVal; renderTimeline(); };
  $("tlZoomOut").onclick = () => { state.tlZoomVal = clamp(state.tlZoomVal - 0.5, 1, 6); $("tlZoom").value = state.tlZoomVal; renderTimeline(); };
  new ResizeObserver(() => renderTimeline()).observe($("tlScroll"));
  // klik na pozadie časovej osi (mimo titulku) pretočí video; ťahanie "scrubuje"
  const tAt = (e) => clamp((e.clientX - $("tlInner").getBoundingClientRect().left) / state.tlPxPerSec, 0, tlDuration());
  const scrub = (e) => { const t = tAt(e); video.currentTime = t; if (state.tab === "cut" && partAt(t) !== state.partSel) selectPart(partAt(t)); };
  let scrubbing = false, band = null;
  $("tlInner").addEventListener("pointerdown", (e) => {
    if (e.target.closest(".chip")) return;
    if (e.target === $("tlChips")) { // ťahanie v páse titulkov = výber úseku
      band = { t0: tAt(e), add: e.shiftKey || e.ctrlKey || e.metaKey, base: new Set(state.sel) };
      $("tlBand").hidden = false; e.preventDefault(); return;
    }
    scrubbing = true; scrub(e);
  });
  window.addEventListener("pointermove", (e) => {
    if (scrubbing) scrub(e);
    if (band) {
      const t1 = tAt(e), a = Math.min(band.t0, t1), b = Math.max(band.t0, t1), px = state.tlPxPerSec;
      Object.assign($("tlBand").style, { left: Math.round(a * px) + "px", width: Math.max(1, Math.round((b - a) * px)) + "px" });
      state.sel = new Set(band.add ? band.base : []);
      state.segments.forEach((s, k) => { if (s.end > a && s.start < b) state.sel.add(k); });
      refreshSel();
    }
  });
  window.addEventListener("pointerup", (e) => {
    scrubbing = false;
    if (band) {
      if (Math.abs(tAt(e) - band.t0) * state.tlPxPerSec < 4 && !band.add) { clearSel(); video.currentTime = band.t0; } // len klik = zrušiť výber a pretočiť
      else state.selAnchor = [...state.sel].sort((x, y) => x - y)[0] ?? -1;
      band = null; $("tlBand").hidden = true;
    }
  });
  // lišta výberu: rečník a štýl pre vybrané titulky
  [{ id: "", name: "Podľa rečníka" }, ...PRESETS].forEach((p) => {
    const b = document.createElement("button"); b.className = "stile"; b.dataset.id = p.id; b.title = p.id ? "Štýl " + p.name + " pre vybrané titulky" : "Zrušiť vlastný štýl, použiť štýl rečníka";
    if (p.id) { const smp = document.createElement("span"); smp.className = "stsample"; renderSample(smp, { ...p, on: true }, 0.55); b.append(smp); }
    const nm = document.createElement("span"); nm.textContent = p.name; b.append(nm);
    b.onclick = () => assignStyle(p.id); $("selStyles").append(b);
  });
  $("selClear").onclick = clearSel;
  // medzerník = prehrať/zastaviť, Esc = zrušiť výber
  document.addEventListener("keydown", (e) => {
    if ($("view-editor").hidden || page !== "work" || e.defaultPrevented) return;
    const el = e.target, tag = el.tagName, typing = tag === "TEXTAREA" || tag === "SELECT" || el.isContentEditable ||
      (tag === "INPUT" && !["range", "checkbox", "color", "button", "file"].includes(el.type));
    if (e.code === "Space" && !typing) {
      e.preventDefault(); if (tag === "BUTTON" || tag === "INPUT") el.blur();
      if (video.paused) video.play().catch(() => {}); else video.pause();
    } else if (e.key === "Escape" && !typing && state.sel.size) clearSel();
    else if (state.tab === "cut" && !typing && !e.ctrlKey && !e.metaKey && !e.altKey) {
      if (e.code === "KeyS") { e.preventDefault(); splitAt(video.currentTime); }
      else if (e.key === "Delete" || e.key === "Backspace") { e.preventDefault(); toggleOff(state.partSel); }
    }
  });
  $("segSearchBtn").onclick = () => {
    const inp = $("segSearch"); inp.hidden = !inp.hidden;
    if (!inp.hidden) inp.focus(); else { inp.value = ""; state.segFilter = ""; applySegFilter(); }
  };
  $("segSearch").addEventListener("input", (e) => { state.segFilter = e.target.value.toLowerCase(); applySegFilter(); });
  const drop = $("drop");
  $("file").onchange = (e) => e.target.files[0] && pickFile(e.target.files[0]);
  ["dragover", "dragenter"].forEach((n) => drop.addEventListener(n, (e) => { e.preventDefault(); drop.classList.add("over"); }));
  ["dragleave", "drop"].forEach((n) => drop.addEventListener(n, (e) => { e.preventDefault(); drop.classList.remove("over"); }));
  drop.addEventListener("drop", (e) => e.dataTransfer.files[0] && pickFile(e.dataTransfer.files[0]));
  $("drop-hint").textContent = "MP4, MOV, WEBM · zostáva vo vašom prehliadači";
  ["400 20px Inter", "800 20px Inter", "italic 800 20px Montserrat"].forEach((f) => document.fonts.load(f).catch(() => {}));
  syncControls(); requestAnimationFrame(loop); show("upload"); showModelState(); warmUpModel();
}

// ---------- dynamické titulky ----------
// Vzor: slová sa objavujú po jednom podľa reči (rozmazanie + posun zdola), krátka fráza ostane na obraze
// a potom sa rozmaže a odplynie nahor. Fráza má 1 až 3 slová, prvý riadok je väčší, niektoré slová sú
// v pútavom písme a poloha frázy sa môže meniť zľava doprava.
const ENTER = 0.22, EXIT = 0.25;
let dynVer = 0, dynCache = { ver: -1, chunks: [] };
const bumpDyn = () => { dynVer++; };
function dynChunks() {
  if (dynCache.ver === dynVer) return dynCache.chunks;
  dynCache = { ver: dynVer, chunks: buildChunks(state.segments) };
  return dynCache.chunks;
}
function buildChunks(segments) {
  const all = [];
  for (const seg of segments) {
    let cur = [];
    const flush = () => { if (cur.length) { all.push({ words: cur, start: cur[0].start, end: cur[cur.length - 1].end, speaker: seg.speaker || 1, seg }); cur = []; } };
    for (const o of wordsFor(seg)) {
      const len = cur.reduce((a, x) => a + x.w.length + 1, 0) + o.w.length;
      if (cur.length && (cur.length >= 3 || len > 20 || o.start - cur[cur.length - 1].end > 0.5)) flush();
      cur.push(o);
      if (/[.,!?…;:]$/.test(o.w)) flush();
    }
    flush();
  }
  // Odchod frázy sa vždy dokončí skôr, než sa objaví prvé slovo ďalšej frázy (nič sa neprekrýva).
  all.forEach((c, i) => {
    c.idx = i;
    const next = all[i + 1];
    if (next) {
      const dur = clamp(next.start - (c.end + 0.05), 0.08, EXIT);
      c.exitDur = dur; c.exitStart = Math.max(c.start, next.start - dur);
    } else { c.exitDur = EXIT; c.exitStart = c.end + 0.5; }
  });
  return all;
}
const hashStr = (str) => { let h = 0; for (const ch of str) h = (h * 31 + ch.charCodeAt(0)) >>> 0; return h; };
const easeOut = (x) => 1 - Math.pow(1 - x, 3);
const ROAM = [[-0.05, 0], [0.05, -0.012], [0, 0.015], [-0.03, -0.008], [0.04, 0.01]]; // malé posuny okolo zvolenej polohy, fráza ostáva v zornom poli
function drawChunk(ctx, c, t, W, H, st) {
  const base = Math.min(W, H) * st.size / 100, space = base * 0.26;
  const exit = t > c.exitStart ? clamp((t - c.exitStart) / c.exitDur, 0, 1) : 0;
  const h = hashStr(c.words.map((o) => o.w).join(" "));
  ctx.textBaseline = "middle"; ctx.textAlign = "center";
  if ("letterSpacing" in ctx) ctx.letterSpacing = `${-base * 0.02}px`;
  const n = c.words.length;
  const split = n === 1 ? [1] : n === 2 ? (h % 2 ? [1, 1] : [2]) : [h % 2 ? 1 : 2, h % 2 ? 2 : 1];
  const lines = []; let k = 0;
  split.forEach((cnt, li) => {
    const arr = [];
    for (let j = 0; j < cnt; j++, k++) {
      const o = c.words[k], text = st.caps ? o.w.toUpperCase() : o.w.toLowerCase();
      const px = base * (li === 0 ? 1 : 0.82);
      const font = `${st.bold ? 800 : 600} ${px}px "${st.font}", sans-serif`; // jeden druh písma pre všetky slová
      ctx.font = font;
      arr.push({ o, k, font, px, text, w: ctx.measureText(text).width });
    }
    lines.push(arr);
  });
  const widths = lines.map((l) => l.reduce((a, i) => a + i.w, 0) + space * (l.length - 1));
  const heights = lines.map((l) => Math.max(...l.map((i) => i.px)) * 1.05);
  const bw = Math.max(...widths), bh = heights.reduce((a, v) => a + v, 0), pad = W * 0.03;
  const [ox, oy] = st.roam ? ROAM[c.idx % ROAM.length] : [0, 0];
  const cx = clamp(st.x * W + ox * W, bw / 2 + pad, Math.max(bw / 2 + pad, W - bw / 2 - pad));
  const top = clamp(st.y * H + oy * H, bh / 2, Math.max(bh / 2, H - bh / 2)) - bh / 2;
  let active = -1; c.words.forEach((o, i) => { if (t >= o.start) active = i; });
  let y = top;
  lines.forEach((line, li) => {
    let x = cx - widths[li] / 2; const cy = y + heights[li] / 2; y += heights[li];
    for (const it of line) {
      const p = easeOut(clamp((t - (it.k === 0 ? c.start : it.o.start)) / ENTER, 0, 1));
      if (p > 0) {
        ctx.save();
        ctx.globalAlpha = p * (1 - exit); ctx.font = it.font;
        ctx.translate(x + it.w / 2, cy + (1 - p) * base * 0.45 - exit * base * 0.35);
        const sc = (0.95 + 0.05 * p) * (1 + 0.04 * exit); ctx.scale(sc, sc);
        if (ctx.filter !== undefined) ctx.filter = `blur(${((1 - p) * 0.25 + exit * 0.3) * base}px)`;
        ctx.shadowColor = "rgba(0,0,0,.45)"; ctx.shadowBlur = base * 0.16; ctx.shadowOffsetY = base * 0.04;
        ctx.fillStyle = st.highlight && it.k === active ? st.hl : st.color;
        ctx.fillText(it.text, 0, 0);
        ctx.restore();
      }
      x += it.w + space;
    }
  });
  if ("letterSpacing" in ctx) ctx.letterSpacing = "0px";
}
function drawDynamic(ctx, t, W, H, stOf) { // stOf(rečník) -> štýl; kreslia sa len frázy, ktorých štýl je dynamický
  for (const c of dynChunks()) {
    if (t < c.start || t > c.exitStart + c.exitDur) continue;
    const st = stOf(c.speaker, c.seg); if (st.dynamic) drawChunk(ctx, c, t, W, H, st);
  }
}
function wrapWords(ctx, words, maxW) {
  const sp = ctx.measureText(" ").width, lines = []; let cur = [], w = 0;
  words.forEach((o, n) => {
    const ww = ctx.measureText(o.w).width;
    if (cur.length && w + sp + ww > maxW) { lines.push({ idx: cur, w }); cur = []; w = 0; }
    w += (cur.length ? sp : 0) + ww; cur.push(n);
  });
  if (cur.length) lines.push({ idx: cur, w });
  return lines;
}
function drawCaption(ctx, seg, t, W, H, st) {
  const px = Math.min(W, H) * st.size / 100, lh = px * (st.lineH ?? 1.25), padX = st.bgOpacity > 0 ? px * .3 : 0;
  ctx.font = `${st.italic ? "italic " : ""}${st.weight ?? (st.bold ? 700 : 400)} ${px}px "${st.font}", sans-serif`;
  ctx.textBaseline = "middle"; ctx.lineJoin = "round";
  const words = wordsFor(seg); let active = -1; words.forEach((o, n) => { if (t >= o.start) active = n; });
  const lines = wrapWords(ctx, words, W * 0.84 - 2 * padX), total = lines.length * lh, sp = ctx.measureText(" ").width;
  const blockW = Math.max(...lines.map((l) => l.w)) + 2 * padX;
  const cx = clamp(st.x * W, blockW / 2, Math.max(blockW / 2, W - blockW / 2));
  const top = clamp(st.y * H, total / 2, Math.max(total / 2, H - total / 2)) - total / 2;
  lines.forEach((line, li) => {
    const y = top + li * lh; let x = cx - line.w / 2;
    if (st.bgOpacity > 0) {
      ctx.fillStyle = `rgba(${rgbOf(st.bg)},${st.bgOpacity / 100})`; ctx.beginPath();
      if (ctx.roundRect) ctx.roundRect(x - padX, y, line.w + 2 * padX, lh, px * .12); else ctx.rect(x - padX, y, line.w + 2 * padX, lh);
      ctx.fill();
    }
    line.idx.forEach((n) => {
      const word = words[n].w, ww = ctx.measureText(word).width;
      if (st.bgOpacity <= 0) { ctx.strokeStyle = st.stroke || "#000"; ctx.lineWidth = px * (st.strokeW ?? .16); ctx.strokeText(word, x, y + lh / 2); }
      ctx.fillStyle = st.highlight && n === active ? st.hl : st.color; ctx.fillText(word, x, y + lh / 2);
      x += ww + sp;
    });
  });
}
const srtTime = (s, sep) => { const ms = Math.round(s * 1000), p = (n, l = 2) => String(n).padStart(l, "0");
  return `${p(Math.floor(ms / 3600000))}:${p(Math.floor(ms / 60000) % 60)}:${p(Math.floor(ms / 1000) % 60)}${sep}${p(ms % 1000, 3)}`; };
const toSrt = (segs) => segs.map((s, i) => `${i + 1}\n${srtTime(s.start, ",")} --> ${srtTime(s.end, ",")}\n${s.text}\n`).join("\n");
const toVtt = (segs) => "WEBVTT\n\n" + segs.map((s) => `${srtTime(s.start, ".")} --> ${srtTime(s.end, ".")}\n${s.text}\n`).join("\n");


if (MOCK) window.__test = { captureAudio, decodeBuffer, state, dynChunks, bumpDyn, idbCache, ensureModel, showModelState, hasLocal, preferLocal, bestModels, segmentFeatures, detectSpeakers, uploadFont, customFonts };
setup();
