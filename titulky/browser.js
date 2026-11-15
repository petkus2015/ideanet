"use strict";
const TF_URL = "https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.7.6";
const LANGS = { sk: "slovak", cs: "czech", en: "english" };
const DEMO = false; // true v ukážke na webe: vzorové titulky a výsledok sa prehrá priamo na stránke
const MOCK = DEMO || new URLSearchParams(location.search).has("mock"); // len na skúšanie bez modelu
const SAMPLE = ["Ahoj, vitajte pri ukážke aplikácie.", "Nahráte video a titulky sa vytvoria automaticky.", "Vyberiete štýl z ponuky,", "zmeníte farbu textu, font alebo podklad,", "a práve vyslovené slovo sa zvýrazní."];

const $ = (id) => document.getElementById(id);
const OUT = { "9:16": [1080, 1920], "1:1": [1080, 1080], "16:9": [1920, 1080] };
const EXPORT_SHORT = 720; // kratšia strana exportu v pixeloch
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
  segments: [], file: null, url: null, duration: 0, outs: [], busyExport: false, playable: null, job: 0, pos: {}, audio: null, spkOn: false, spk2: "modry", speakers: 1 };
const video = $("video");
const STEP_OF = { upload: 0, busy: 1, editor: 2 };
const setStep = (i) => document.querySelectorAll("#steps .step").forEach((s, n) => s.classList.toggle("on", n === i));
const show = (view) => {
  for (const v of ["upload", "busy", "editor"]) $("view-" + v).hidden = v !== view;
  $("panel").hidden = view !== "editor"; setStep(STEP_OF[view]);
};
const fmtTime = (s) => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, "0")}`;
const rgbOf = (hex) => [1, 3, 5].map((n) => parseInt(hex.slice(n, n + 2), 16)).join(",");
const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
const segmentAt = (t) => state.segments.findIndex((s) => t >= s.start && t < s.end);
const BUSY = "Nahrávam a titulkujem video…";
const BUSY_TEXT = "Môže to trvať niekoľko minút (pri prvom použití dlhšie). Nechaj túto kartu otvorenú.";
// Priebeh technických krokov (sťahovanie modelu a pod.) sa používateľovi neukazuje, vždy je vidno len jednu správu.
const setBusy = (_title, _text = "", _pct = null) => { show("busy"); $("busy-title").textContent = BUSY; $("busy-text").textContent = BUSY_TEXT; };
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
  const iv = setInterval(() => setBusy("Čítam zvuk…", `Prehliadač číta zvuk v reálnom čase: ${fmtTime(tv.currentTime)} z ${fmtTime(tv.duration)}`, tv.currentTime / tv.duration * 100), 400);
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
  } else if (!quiet) setBusy();
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
  const checks = [`huggingface.co: ${await probe(`https://huggingface.co/${id}/resolve/main/config.json`)}`, `cdn.jsdelivr.net: ${await probe("https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.7.6/package.json")}`];
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
  if (!quiet) setBusy();
  try { await navigator.storage?.persist?.(); } catch {} // aby prehliadač model nevymazal
  if (!tf) {
    try { tf = await import(TF_URL); }
    catch (e) { throw new Error("Nepodarilo sa načítať knižnicu na rozpoznávanie (cdn.jsdelivr.net). Skontroluj pripojenie na internet alebo blokovač reklám. Technická chyba: " + String(e && e.message || e).slice(0, 150)); }
  }
  const files = {};
  const progress = (p) => { // ukazuje sa len pri skutočnom sťahovaní, uložený model sa načíta bez neho
    if (p.status !== "progress" || !p.total) return;
    files[p.file] = { loaded: p.loaded, total: p.total };
    const all = Object.values(files), loaded = all.reduce((a, f) => a + f.loaded, 0), total = all.reduce((a, f) => a + f.total, 0);
    void loaded; void total; // priebeh sa nezobrazuje
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
async function transcribeWith(model, audio, lang) {
  setBusy("Rozpoznávam reč…", "Pri dlhšom videu to môže trvať niekoľko minút. Nechaj túto kartu otvorenú.");
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
}
async function recognize(file, _unused, lang) {
  setBusy("Načítavam zvuk…");
  state.audio = null;
  if (MOCK) {
    try { state.audio = await decodeBuffer(await file.arrayBuffer()); } catch { /* bez zvuku sa rečníci nerozlišujú */ }
    await Promise.race([state.meta, new Promise((r) => setTimeout(r, 3000))]);
    const d = state.duration || 12, out = []; let t = 0.3, i = 1;
    while (t + 1.5 < d) { const text = SAMPLE[i++ % SAMPLE.length]; out.push({ start: t, end: t + 2, text, words: estimateWords(text, t, t + 2) }); t += 2.3; }
    return out;
  }
  const audio = await decodeAudio(file);
  state.audio = audio;
  if (!state.duration) state.duration = audio.length / 16000;
  let lastError = null;
  for (const spec of await preferLocal(await bestModels())) {
    try {
      const model = await ensureModel(spec, false);
      return await transcribeWith(model, audio, lang);
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
const PV_MAX_H = 200;
const outFor = (k) => state.outs.find((o) => o.k === k);
// Poloha titulkov je samostatná pre každý formát (9:16, 1:1, 16:9); ostatné nastavenie štýlu je spoločné.
const defaultPos = () => ({ x: 0.5, y: state.style.y ?? 0.84, position: state.style.position ?? (state.style.y ? null : "bottom") });
function speakerStyle(sp) { // štýl titulkov pre daného rečníka (rečník 2 môže mať vlastný štýl z ponuky)
  const main = state.style;
  if (!state.spkOn || sp !== 2) return main;
  const p = PRESETS.find((x) => x.id === state.spk2);
  return p ? { ...p, preset: p.id, highlight: p.highlight ?? main.highlight, caps: p.caps ?? main.caps, on: main.on } : main;
}
const styleFor = (k, sp = 1) => ({ ...speakerStyle(sp), ...(state.pos[k] || defaultPos()) });

function playableBanner() {
  const bad = state.playable === false;
  $("noplay").hidden = !bad;
  $("exportAll").disabled = bad;
  state.outs.forEach((o) => { o.dl.disabled = bad; });
}
function saveUrl(url, name) {
  const a = document.createElement("a"); a.href = url; a.download = name; document.body.append(a); a.click(); a.remove();
}
function saveText(text, name, type) {
  const u = URL.createObjectURL(new Blob([text], { type })); saveUrl(u, name); setTimeout(() => URL.revokeObjectURL(u), 4000);
}
function invalidateExports() {
  if (state.busyExport) return;
  for (const o of state.outs) {
    if (o.result) { URL.revokeObjectURL(o.result.url); o.result = null; o.dl.classList.remove("ready"); o.dl.title = "Exportovať a stiahnuť"; }
    const r = o.el.querySelector(".out-result"); if (r) r.remove();
  }
}
function buildOuts() {
  const box = $("outs"); box.textContent = ""; state.outs = [];
  for (const a of ASPECTS) {
    const el = document.createElement("div"); el.className = "out"; el.dataset.a = a.k;
    el.innerHTML = `<div class="out-head"><span>${a.name} (${a.k})</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">${a.icon}</svg></div>
      <div class="pv"><canvas aria-label="Náhľad ${a.k}"></canvas></div><div class="out-time">0:00 / 0:00</div>
      <div class="out-btns"><button class="edit">Upraviť</button><button class="dl" title="Exportovať a stiahnuť" aria-label="Exportovať a stiahnuť ${a.k}">${ICON_DL}</button></div>
      <div class="out-prog" hidden><div class="bar"><i></i></div><span></span></div>`;
    box.append(el);
    const o = { k: a.k, el, cv: el.querySelector("canvas"), pv: el.querySelector(".pv"), time: el.querySelector(".out-time"),
      dl: el.querySelector(".dl"), prog: el.querySelector(".out-prog"), result: null, drag: null };
    el.querySelector(".edit").onclick = () => { state.aspect = a.k; syncControls(); };
    o.dl.onclick = () => { if (o.result) saveUrl(o.result.url, `titulky-${a.k.replace(":", "x")}.${o.result.ext}`); else exportAspects([a.k]); };
    // titulky sa presúvajú ťahaním v ktoromkoľvek náhľade
    const move = (e) => {
      const r = o.cv.getBoundingClientRect(), q = state.pos[o.k];
      q.x = clamp((e.clientX - r.left) / r.width, 0, 1); q.y = clamp((e.clientY - r.top) / r.height, 0, 1); q.position = null;
      state.aspect = o.k; markActive(); invalidateExports();
    };
    o.cv.addEventListener("pointerdown", (e) => { if (!state.style.on) return; state.aspect = o.k; markActive(); o.drag = { x: e.clientX, y: e.clientY, active: false }; o.cv.setPointerCapture(e.pointerId); e.preventDefault(); });
    o.cv.addEventListener("pointermove", (e) => {
      if (!o.drag) return;
      if (!o.drag.active && Math.hypot(e.clientX - o.drag.x, e.clientY - o.drag.y) > 4) o.drag.active = true;
      if (o.drag.active) move(e);
    });
    const end = () => { o.drag = null; };
    o.cv.addEventListener("pointerup", end); o.cv.addEventListener("pointercancel", end);
    state.outs.push(o);
  }
}
function buildSegments() {
  const box = $("segments"); box.textContent = "";
  if (!state.segments.length) { box.textContent = "V tomto videu sa nenašla žiadna reč."; return; }
  state.segments.forEach((s, i) => {
    const row = document.createElement("div"); row.className = "segment"; row.dataset.i = i;
    const t = document.createElement("button"); t.textContent = fmtTime(s.start);
    t.onclick = () => { video.currentTime = s.start + 0.01; video.play(); };
    const ta = document.createElement("textarea"); ta.value = s.text; ta.rows = 1; ta.setAttribute("aria-label", "Text titulku " + (i + 1));
    const fit = () => { ta.style.height = "auto"; ta.style.height = ta.scrollHeight + "px"; };
    ta.oninput = () => { s.text = ta.value; fit(); bumpDyn(); invalidateExports(); };
    const sp = document.createElement("button"); sp.className = "spk"; sp.dataset.i = i; sp.dataset.s = s.speaker || 1; sp.textContent = s.speaker || 1; sp.title = "Rečník (klikni pre zmenu)";
    sp.onclick = () => { s.speaker = s.speaker === 2 ? 1 : 2; sp.textContent = s.speaker; sp.dataset.s = s.speaker; bumpDyn(); invalidateExports(); };
    row.append(t, ta, sp); box.append(row); requestAnimationFrame(fit);
  });
}
function initEditor() {
  state.pos = {}; ASPECTS.forEach((a) => { state.pos[a.k] = defaultPos(); });
  state.speakers = 1; state.spkOn = false;
  buildOuts(); buildSegments(); show("editor"); syncControls(); playableBanner(); $("export-error").hidden = true;
  const job = state.job;
  setTimeout(() => { // rozpoznanie rečníkov na pozadí, editor sa otvorí hneď
    if (job !== state.job) return;
    try { state.speakers = detectSpeakers(); } catch (e) { console.error("Rečníci:", e); state.speakers = 1; }
    state.spkOn = state.speakers > 1;
    if (state.spkOn && state.spk2 === state.style.preset) state.spk2 = state.style.preset === "kino" ? "modry" : "kino";
    bumpDyn(); refreshSpeakerUi();
  }, 50);
}

// ---------- kreslenie snímky (rovnaké pre náhľady aj export) ----------
function renderFrame(ctx, vid, t, W, H, stOf) {
  ctx.globalAlpha = 1; ctx.textAlign = "left"; ctx.textBaseline = "alphabetic"; ctx.shadowColor = "transparent"; ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
  if (ctx.filter !== undefined) ctx.filter = "none";
  if ("letterSpacing" in ctx) ctx.letterSpacing = "0px";
  ctx.fillStyle = "#000"; ctx.fillRect(0, 0, W, H);
  const vw = vid.videoWidth, vh = vid.videoHeight;
  if (vw && vh && vid.readyState >= 2) { const s = Math.max(W / vw, H / vh); ctx.drawImage(vid, (W - vw * s) / 2, (H - vh * s) / 2, vw * s, vh * s); }
  if (!stOf(1).on) return;
  drawDynamic(ctx, t, W, H, stOf);
  const i = segmentAt(t);
  if (i >= 0 && state.segments[i].text.trim()) { const sg = state.segments[i], st = stOf(sg.speaker || 1); if (!st.dynamic) drawCaption(ctx, sg, t, W, H, st); }
}
let lastActive = -2, seeking = false;
function loop() {
  if (!$("view-editor").hidden) {
    const t = video.currentTime, dpr = window.devicePixelRatio || 1, label = `${fmtTime(t)} / ${fmtTime(state.duration || video.duration || 0)}`;
    for (const o of state.outs) {
      const [ow, oh] = OUT[o.k], ratio = ow / oh, avail = o.pv.clientWidth || 100;
      const h = Math.round(Math.min(PV_MAX_H, avail / ratio)), w = Math.round(h * ratio);
      if (o.cv.style.width !== w + "px" || o.cv.style.height !== h + "px") { o.cv.style.width = w + "px"; o.cv.style.height = h + "px"; }
      if (o.cv.width !== Math.round(w * dpr) || o.cv.height !== Math.round(h * dpr)) { o.cv.width = Math.round(w * dpr); o.cv.height = Math.round(h * dpr); }
      const ctx = o.cv.getContext("2d"); ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      renderFrame(ctx, video, t, w, h, (sp) => styleFor(o.k, sp));
      o.time.textContent = label;
    }
    if (!seeking) { $("seek").max = state.duration || video.duration || 0; $("seek").value = t; }
    $("playBtn").textContent = video.paused ? "▶" : "❚❚";
    const i = segmentAt(t);
    if (i !== lastActive) { lastActive = i; document.querySelectorAll(".segment").forEach((r) => r.classList.toggle("active", +r.dataset.i === i)); }
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
function detectSpeakers() {
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
  if (Math.min(n0, n1) < Math.max(2, 0.12 * X.length) || between / (within || 1) < 1.8 || f0gap < 0.12) return 1;
  const first = lab[0]; // rečník 1 = ten, kto hovorí v prvom titulku
  let prev = 1;
  segs.forEach((sg, i) => { const j = idx.indexOf(i); if (j >= 0) prev = lab[j] === first ? 1 : 2; sg.speaker = prev; });
  return 2;
}
function refreshSpeakerUi() {
  const found = state.speakers;
  $("spkInfo").textContent = found > 1
    ? "Zistení 2 rečníci. Pre rečníka 2 vyber štýl nižšie. Ak sa niekto zle priradil, prepni ho číslom pri jeho titulku."
    : "Zistený jeden rečník. Ak ich je viac, zapni rozlišovanie a priraď titulky ručne číslom pri texte.";
  document.querySelectorAll(".spk").forEach((b) => { const sg = state.segments[+b.dataset.i]; b.textContent = sg.speaker || 1; b.dataset.s = sg.speaker || 1; });
  syncControls();
}

// ---------- vlastný font (nahratý súbor, ostáva v tomto prehliadači) ----------
function fontsDb() {
  return new Promise((res, rej) => {
    const r = indexedDB.open("titulky-fonts", 1);
    r.onupgradeneeded = () => r.result.createObjectStore("fonts");
    r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error);
  });
}
const customFonts = new Set();
function addFontOption(name) {
  if ([...$("font").options].some((o) => o.value === name)) return;
  const o = document.createElement("option"); o.value = o.textContent = name; $("font").append(o);
}
async function registerFont(name, buf) {
  const face = new FontFace(name, buf, { weight: "100 900", style: "normal" }); // jeden súbor platí pre všetky hrúbky
  await face.load(); document.fonts.add(face); customFonts.add(name); addFontOption(name);
}
async function loadSavedFonts() {
  try {
    const db = await fontsDb();
    const all = await new Promise((res, rej) => { const q = db.transaction("fonts").objectStore("fonts").getAll(); q.onsuccess = () => res(q.result); q.onerror = () => rej(q.error); });
    for (const f of all) { try { await registerFont(f.name, f.buf.slice(0)); } catch { /* poškodený súbor sa preskočí */ } }
  } catch { /* bez trvalého úložiska ostanú fonty len do zatvorenia stránky */ }
}
async function uploadFont(file) {
  $("fontMsg").textContent = "";
  if (!/\.(ttf|otf|woff2?)$/i.test(file.name)) { $("fontMsg").textContent = "Použi súbor TTF, OTF, WOFF alebo WOFF2."; return; }
  if (file.size > 15 * 1048576) { $("fontMsg").textContent = "Font je príliš veľký (najviac 15 MB)."; return; }
  const name = "Vlastný " + file.name.replace(/\.[^.]+$/, "").replace(/[^\p{L}\p{N} _-]/gu, " ").trim().slice(0, 40);
  try {
    const buf = await file.arrayBuffer();
    await registerFont(name, buf.slice(0));
    try { const db = await fontsDb(); db.transaction("fonts", "readwrite").objectStore("fonts").put({ name, buf }, name); } catch {}
    state.style.font = name; state.style.preset = null; syncControls();
    $("fontMsg").textContent = `Font „${name}“ je pridaný a vybraný.`;
  } catch { $("fontMsg").textContent = "Tento súbor sa nepodarilo načítať ako font."; }
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
  const o = state.style;
  state.style = { ...p, preset: p.id, highlight: p.highlight ?? o.highlight, caps: p.caps ?? o.caps, on: o.on };
  if (p.y !== undefined) ASPECTS.forEach((a) => { state.pos[a.k] = { x: 0.5, y: p.y, position: p.position ?? null }; });
  syncControls();
}
function markActive() {
  const q = state.pos[state.aspect];
  document.querySelectorAll("#positions .opt").forEach((b) => b.classList.toggle("on", !!q && b.dataset.v === q.position));
  document.querySelectorAll("#aspects .opt").forEach((b) => b.classList.toggle("on", b.dataset.v === state.aspect));
  state.outs.forEach((o) => o.el.classList.toggle("on", o.k === state.aspect));
}
function syncControls() {
  const st = state.style;
  $("color").value = st.color; $("bg").value = st.bg; $("bgOpacity").value = st.bgOpacity;
  $("bgOpacityOut").textContent = Math.round(st.bgOpacity) + " %"; $("size").value = st.size; $("sizeOut").textContent = st.size;
  $("font").value = st.font; $("highlight").checked = !!st.highlight; $("hl").value = st.hl; $("hl").disabled = !st.highlight;
  $("roam").checked = !!st.roam; $("caps").checked = !!st.caps;
  $("subsOn").setAttribute("aria-checked", String(st.on));
  document.querySelectorAll("#styles .tile").forEach((t) => t.classList.toggle("on", t.dataset.id === st.preset));
  document.querySelectorAll("#styles2 .tile").forEach((t) => t.classList.toggle("on", t.dataset.id === state.spk2));
  $("spkOn").setAttribute("aria-checked", String(state.spkOn)); $("styles2box").hidden = !state.spkOn; $("segments").classList.toggle("spk-on", state.spkOn);
  $("styleCustom").hidden = !!st.preset;
  markActive(); invalidateExports();
}

// ---------- export: video + titulky nahrané z plátna ----------
async function recordAspect(aspect, onProgress, job) {
  if (!window.MediaRecorder) throw new Error("Tento prehliadač nevie nahrávať video. Použi Chrome, Edge alebo Firefox.");
  const [ow, oh] = OUT[aspect], k = EXPORT_SHORT / Math.min(ow, oh);
  const W = Math.round(ow * k / 2) * 2, H = Math.round(oh * k / 2) * 2, st = styleFor(aspect), stOf = (sp) => styleFor(aspect, sp);
  await Promise.all([st, stOf(2)].flatMap((x) => [400, 700, 800].map((wt) => document.fonts.load(`${x.italic ? "italic " : ""}${wt} 40px "${x.font}"`).catch(() => {}))));
  const canvas = document.createElement("canvas"); canvas.width = W; canvas.height = H;
  const ctx = canvas.getContext("2d");
  const ev = document.createElement("video"); ev.src = state.url; ev.playsInline = true;
  await new Promise((res, rej) => { ev.onloadeddata = res; ev.onerror = () => rej(new Error("Video sa nepodarilo načítať.")); });
  const stream = canvas.captureStream(30);
  const ac = new (window.AudioContext || window.webkitAudioContext)();
  try {
    const dest = ac.createMediaStreamDestination();
    ac.createMediaElementSource(ev).connect(dest);
    dest.stream.getAudioTracks().forEach((t) => stream.addTrack(t));
  } catch {}
  const types = ["video/mp4;codecs=avc1,mp4a.40.2", "video/webm;codecs=vp9,opus", "video/webm;codecs=vp8,opus", "video/webm"];
  const mime = types.find((t) => MediaRecorder.isTypeSupported(t)) || "";
  const rec = new MediaRecorder(stream, { mimeType: mime || undefined, videoBitsPerSecond: 6e6 });
  const chunks = []; rec.ondataavailable = (e) => e.data.size && chunks.push(e.data);
  const finished = new Promise((res) => (rec.onstop = res));
  let live = true;
  const draw = () => {
    if (!live) return;
    if (job !== state.job) { live = false; ev.pause(); try { rec.stop(); } catch {} return; } // nový projekt: export sa zruší
    renderFrame(ctx, ev, ev.currentTime, W, H, stOf);
    onProgress(Math.min(99, Math.round(ev.currentTime / (ev.duration || 1) * 100)));
    ev.requestVideoFrameCallback ? ev.requestVideoFrameCallback(draw) : requestAnimationFrame(draw);
  };
  ev.onended = () => { live = false; setTimeout(() => rec.stop(), 150); };
  await ac.resume(); rec.start(500); draw(); await ev.play();
  await finished; ac.close();
  const ext = rec.mimeType.includes("mp4") ? "mp4" : "webm";
  if (job !== state.job) return null;
  return { url: URL.createObjectURL(new Blob(chunks, { type: rec.mimeType || "video/webm" })), ext };
}
async function exportAspects(list) {
  if (state.busyExport || state.playable === false) return;
  const job = state.job, outs = state.outs;
  state.busyExport = true; $("exportAll").disabled = true; $("export-error").hidden = true; setStep(3);
  try {
    for (const k of list) {
      const o = outs.find((x) => x.k === k); if (!o || job !== state.job) break;
      const bar = o.prog.querySelector("i"), label = o.prog.querySelector("span");
      o.prog.hidden = false; bar.style.width = "0%"; label.textContent = "Pripravujem…";
      try {
        const r = await recordAspect(k, (p) => { bar.style.width = p + "%"; label.textContent = `Exportujem… ${p} %`; }, job);
        if (!r || job !== state.job) { if (r) URL.revokeObjectURL(r.url); break; }
        o.result = r; o.dl.classList.add("ready"); o.dl.title = "Stiahnuť hotové video";
        if (DEMO) { // v ukážke sa sťahovanie nedá, výsledok sa prehrá priamo v karte
          const v = document.createElement("video"); v.className = "out-result"; v.controls = true; v.playsInline = true; v.src = r.url; o.el.insertBefore(v, o.prog);
        }
        label.textContent = "Hotovo, stiahni tlačidlom ⬇";
        o.prog.hidden = false; bar.style.width = "100%";
      } catch (e) { if (job === state.job) { $("export-error").textContent = e.message || "Export zlyhal."; $("export-error").hidden = false; } o.prog.hidden = true; }
    }
  } finally {
    state.busyExport = false;
    if (job === state.job) $("exportAll").disabled = state.playable === false;
  }
}

// ---------- štart ----------
function reset() {
  state.job++; state.busyExport = false; $("exportAll").disabled = false; $("export-error").hidden = true;
  video.pause(); video.removeAttribute("src"); invalidateExports();
  if (state.url) { URL.revokeObjectURL(state.url); state.url = null; }
  state.segments = []; state.outs = []; state.playable = null; state.audio = null; state.spkOn = false; state.speakers = 1; bumpDyn(); $("upload-error").hidden = true; $("file").value = ""; show("upload");
}
function setup() {
  FONTS.forEach((f) => { const o = document.createElement("option"); o.value = o.textContent = f; $("font").append(o); });
  PRESETS.forEach((p) => { // ukážky štýlov
    const t = document.createElement("button"); t.className = "tile"; t.dataset.id = p.id; t.setAttribute("aria-label", "Štýl " + p.name);
    const smp = document.createElement("div"); smp.className = "tsample"; renderSample(smp, { ...p, on: true }, 0.9);
    const nm = document.createElement("span"); nm.className = "tname"; nm.textContent = p.name;
    t.append(smp, nm); t.onclick = () => applyPreset(p); $("styles").append(t);
  });
  document.querySelectorAll("#aspects .opt").forEach((b) => b.onclick = () => { state.aspect = b.dataset.v; syncControls(); });
  document.querySelectorAll("#positions .opt").forEach((b) => b.onclick = () => { const q = state.pos[state.aspect]; if (!q) return; q.position = b.dataset.v; q.x = 0.5; q.y = POS_Y[b.dataset.v]; syncControls(); });
  PRESETS.forEach((p) => {
    const t = document.createElement("button"); t.className = "tile"; t.dataset.id = p.id; t.setAttribute("aria-label", "Štýl rečníka 2: " + p.name);
    const smp = document.createElement("div"); smp.className = "tsample"; renderSample(smp, { ...p, on: true }, 0.9);
    const nm = document.createElement("span"); nm.className = "tname"; nm.textContent = p.name;
    t.append(smp, nm); t.onclick = () => { state.spk2 = p.id; bumpDyn(); syncControls(); }; $("styles2").append(t);
  });
  $("spkOn").onclick = () => { state.spkOn = !state.spkOn; bumpDyn(); syncControls(); };
  const custom = () => { state.style.preset = null; syncControls(); };
  $("subsOn").onclick = () => { state.style.on = !state.style.on; syncControls(); };
  $("color").oninput = (e) => { state.style.color = e.target.value; custom(); };
  $("bg").oninput = (e) => { state.style.bg = e.target.value; custom(); };
  $("bgOpacity").oninput = (e) => { state.style.bgOpacity = +e.target.value; custom(); };
  $("size").oninput = (e) => { state.style.size = +e.target.value; custom(); };
  $("font").onchange = (e) => { state.style.font = e.target.value; custom(); };
  $("highlight").onchange = (e) => { state.style.highlight = e.target.checked; syncControls(); };
  $("hl").oninput = (e) => { state.style.hl = e.target.value; syncControls(); };
  $("roam").onchange = (e) => { state.style.roam = e.target.checked; syncControls(); };
  $("caps").onchange = (e) => { state.style.caps = e.target.checked; syncControls(); };
  $("exportAll").onclick = () => exportAspects(ASPECTS.map((a) => a.k));
  $("dl-srt").onclick = () => saveText(toSrt(state.segments), "titulky.srt", "application/x-subrip");
  $("dl-vtt").onclick = () => saveText(toVtt(state.segments), "titulky.vtt", "text/vtt");
  $("newproject").onclick = reset;
  $("fontUp").onclick = () => $("fontFile").click();
  $("fontFile").onchange = (e) => { if (e.target.files[0]) uploadFont(e.target.files[0]); e.target.value = ""; };
  loadSavedFonts();
  $("playBtn").onclick = () => { if (video.paused) video.play().catch(() => {}); else video.pause(); };
  $("seek").addEventListener("input", (e) => { seeking = true; video.currentTime = +e.target.value; });
  $("seek").addEventListener("change", () => { seeking = false; });
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
  const all = [];
  for (const seg of state.segments) {
    let cur = [];
    const flush = () => { if (cur.length) { all.push({ words: cur, start: cur[0].start, end: cur[cur.length - 1].end, speaker: seg.speaker || 1 }); cur = []; } };
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
  dynCache = { ver: dynVer, chunks: all };
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
    const st = stOf(c.speaker); if (st.dynamic) drawChunk(ctx, c, t, W, H, st);
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
