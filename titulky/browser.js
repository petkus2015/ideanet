"use strict";
const TF_URL = "https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.7.6";
const MODELS = { tiny: "Xenova/whisper-tiny", base: "Xenova/whisper-base", small: "Xenova/whisper-small" };
const LANGS = { sk: "slovak", cs: "czech", en: "english" };
const MOCK = new URLSearchParams(location.search).has("mock"); // len na skúšanie bez modelu

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
];
const state = { aspect: "9:16", style: { ...PRESETS[0], x: 0.5, y: 0.84, position: "bottom", preset: "modern", highlight: false },
  segments: [], file: null, url: null, duration: 0, downloads: [] };
const video = $("video");

const show = (view) => { for (const v of ["upload", "busy", "editor"]) $("view-" + v).hidden = v !== view; };
const fmtTime = (s) => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, "0")}`;
const rgbOf = (hex) => [1, 3, 5].map((n) => parseInt(hex.slice(n, n + 2), 16)).join(",");
const setBusy = (title, text = "", pct = null) => {
  show("busy"); $("busy-title").textContent = title; $("busy-text").textContent = text;
  $("busy-bar").hidden = pct === null;
  if (pct !== null) $("busy-bar").firstElementChild.style.width = pct + "%";
};
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
let asr = null, asrId = null;
async function recognize(file, modelKey, lang) {
  setBusy("Načítavam zvuk…");
  if (MOCK) {
    await Promise.race([state.meta, new Promise((r) => setTimeout(r, 3000))]);
    const d = state.duration || 12, out = []; let t = 0.3, i = 1;
    while (t + 1.5 < d) { const text = `Ukážkový titulok číslo ${i++}`; out.push({ start: t, end: t + 2, text, words: estimateWords(text, t, t + 2) }); t += 2.3; }
    return out;
  }
  const audio = await decodeAudio(file);
  if (!state.duration) state.duration = audio.length / 16000;
  const id = MODELS[modelKey];
  if (asrId !== id) {
    setBusy("Sťahujem model…", "Stiahne sa len raz, potom ho prehliadač použije znova.", 0);
    let mod;
    try { mod = await import(TF_URL); }
    catch (e) { throw new Error("Nepodarilo sa načítať knižnicu na rozpoznávanie (cdn.jsdelivr.net). Skontroluj pripojenie na internet alebo blokovač reklám. Technická chyba: " + String(e && e.message || e).slice(0, 150)); }
    const files = {};
    const progress = (p) => {
      if (p.status !== "progress" || !p.total) return;
      files[p.file] = { loaded: p.loaded, total: p.total };
      const all = Object.values(files), loaded = all.reduce((a, f) => a + f.loaded, 0), total = all.reduce((a, f) => a + f.total, 0);
      setBusy("Sťahujem model…", `${Math.round(loaded / 1048576)} z ${Math.round(total / 1048576)} MB`, loaded / total * 100);
    };
    // Najprv sa overí, či sa prehliadač vôbec dostane na zdroj modelov.
    let reach = null;
    try { await fetch(`https://huggingface.co/${id}/resolve/main/config.json`, { method: "GET" }); }
    catch (e) { reach = e; }
    // Pokusy: bežne, bez vyrovnávacej pamäte prehliadača, cez zrkadlo.
    const attempts = [
      { label: "bežne", cache: true, host: "https://huggingface.co/" },
      { label: "bez pamäte prehliadača", cache: false, host: "https://huggingface.co/" },
      { label: "cez zrkadlo", cache: false, host: "https://hf-mirror.com/" },
    ];
    let lastErr = null;
    for (const a of attempts) {
      try {
        mod.env.allowLocalModels = false;
        try { mod.env.backends.onnx.wasm.numThreads = 1; mod.env.backends.onnx.wasm.proxy = false; } catch {}
        mod.env.useBrowserCache = a.cache;
        mod.env.remoteHost = a.host;
        for (const k of Object.keys(files)) delete files[k];
        asr = await mod.pipeline("automatic-speech-recognition", id, { progress_callback: progress });
        lastErr = null; break;
      } catch (e) { lastErr = e; console.error("Model:", a.label, e); }
    }
    if (lastErr) {
      // Zisti, ktorá časť sa nedá stiahnuť: zdroj modelu alebo súbor behového prostredia (wasm).
      const probe = async (url) => { try { await fetch(url, { method: "GET", cache: "no-store" }); return "ok"; } catch { return "NEDOSTUPNÉ"; } };
      let wasmBase = ""; try { const wp = mod.env.backends.onnx.wasm.wasmPaths; wasmBase = typeof wp === "string" ? wp : (wp && (wp.wasm || wp.mjs) ? String(wp.wasm || wp.mjs).replace(/[^/]*$/, "") : ""); } catch {}
      const checks = [`huggingface.co: ${await probe(`https://huggingface.co/${id}/resolve/main/config.json`)}`, `cdn.jsdelivr.net: ${await probe("https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.7.6/package.json")}`];
      if (wasmBase) checks.push(`wasm: ${await probe(wasmBase + "ort-wasm-simd-threaded.jsep.wasm")}`);
      console.error("Diagnostika:", checks.join(", "), "Prehliadač:", navigator.userAgent, "Protokol:", location.protocol);
      lastErr.diag = checks.join(", ") + ` · ${/^((?!chrome|android).)*safari/i.test(navigator.userAgent) ? "Safari" : "iný prehliadač"} · ${location.protocol}`;
      const detail = String(lastErr && lastErr.message || lastErr).slice(0, 200);
      const inApp = location.protocol === "about:" || /Claude|FBAN|FBAV|Instagram|Line\//.test(navigator.userAgent) || (/iPhone|iPad/.test(navigator.userAgent) && !/Safari\//.test(navigator.userAgent));
      const hint = inApp
        ? "Stránka beží vo vstavanom prehliadači aplikácie, ktorý blokuje stiahnutie modelu z huggingface.co. Otvor ju v Safari alebo Chrome."
        : reach
        ? "Prehliadač sa nedostal na huggingface.co (blokuje ho sieť, VPN, firewall alebo blokovač reklám). Skús inú sieť alebo vypni blokovač."
        : location.protocol === "file:"
          ? "Skús súbor otvoriť cez lokálny server: v priečinku so súborom spusti `python3 -m http.server 8000` a otvor http://localhost:8000/titulky-browser.html."
          : "Skús obnoviť stránku alebo menší model (tiny).";
      throw new Error(`Model sa nepodarilo stiahnuť. ${hint} Technická chyba: ${detail} (${lastErr.diag})`);
    }
    asrId = id;
  }
  setBusy("Rozpoznávam reč…", "Pri dlhšom videu to môže trvať niekoľko minút. Nechaj túto kartu otvorenú.");
  const opts = { task: "transcribe", chunk_length_s: 30, stride_length_s: 5 };
  if (LANGS[lang]) opts.language = LANGS[lang];
  try { // časy po slovách (zvýraznenie slov)
    const wres = await asr(audio, { ...opts, return_timestamps: "word" });
    const words = (wres.chunks || []).map((c) => ({ w: c.text.trim(), start: c.timestamp[0] ?? 0, end: c.timestamp[1] ?? state.duration })).filter((o) => o.w);
    if (words.length) return groupWords(words);
  } catch { /* model nevie časy po slovách, použije sa odhad */ }
  const res = await asr(audio, { ...opts, return_timestamps: true });
  const chunks = res.chunks && res.chunks.length ? res.chunks : [{ timestamp: [0, state.duration], text: res.text }];
  return chunks.flatMap((c) => {
    const [s, e] = c.timestamp;
    return c.text.trim() ? splitChunk(c.text, s ?? 0, e ?? state.duration).map((g) => ({ ...g, words: estimateWords(g.text, g.start, g.end) })) : [];
  });
}

// ---------- nahratie ----------
const VIDEO_EXT = /\.(mp4|m4v|mov|mkv|webm|avi|ts|mts|m2ts|3gp|mpg|mpeg|ogv|hevc|h265|265)$/i;
function playableBanner() {
  const bad = state.playable === false;
  $("noplay").hidden = !bad;
  $("export").disabled = bad;
  if (bad) { // bez prehrateľného videa sa dajú stiahnuť aspoň titulky
    const mk = (data, type) => { const u = URL.createObjectURL(new Blob([data], { type })); state.downloads.push(u); return u; };
    $("dl-srt").href = mk(toSrt(state.segments), "application/x-subrip"); $("dl-srt").download = "titulky.srt";
    $("dl-vtt").href = mk(toVtt(state.segments), "text/vtt"); $("dl-vtt").download = "titulky.vtt";
    $("dl-mp4").hidden = true; $("downloads").hidden = false;
  } else $("dl-mp4").hidden = false;
}
async function pickFile(file) {
  $("upload-error").hidden = true;
  if (!file.type.startsWith("video/") && !VIDEO_EXT.test(file.name)) return showError("Vyber súbor s videom (MP4, MOV, MKV, WEBM a podobne).");
  if (state.url) URL.revokeObjectURL(state.url);
  state.file = file; state.url = URL.createObjectURL(file); state.duration = 0; state.playable = null;
  setBusy("Čítam zvuk…");
  state.meta = new Promise((resolve) => {
    video.onloadedmetadata = () => {
      state.playable = video.videoWidth > 0; // zvuk bez obrazu = kodek sa nedá dekódovať
      if (isFinite(video.duration)) state.duration = video.duration;
      resolve();
    };
    video.onerror = () => { state.playable = false; resolve(); };
  });
  video.preload = "auto"; video.src = state.url; video.load();
  try { state.segments = await recognize(file, $("model").value, $("lang").value); }
  catch (e) { return showError(e.message || "Rozpoznanie zlyhalo."); }
  await Promise.race([state.meta, new Promise((r) => setTimeout(r, 3000))]);
  if (state.playable === null) state.playable = false;
  buildSegments(); show("editor"); applyPreview(); resetExport(); playableBanner();
}

// ---------- editor ----------
function buildSegments() {
  const box = $("segments"); box.textContent = "";
  if (!state.segments.length) { box.textContent = "V tomto videu sa nenašla žiadna reč."; return; }
  state.segments.forEach((s, i) => {
    const row = document.createElement("div"); row.className = "segment"; row.dataset.i = i;
    const t = document.createElement("button"); t.textContent = fmtTime(s.start);
    t.onclick = () => { video.currentTime = s.start + 0.01; video.play(); };
    const ta = document.createElement("textarea"); ta.value = s.text; ta.rows = 1; ta.setAttribute("aria-label", "Text titulku " + (i + 1));
    const fit = () => { ta.style.height = "auto"; ta.style.height = ta.scrollHeight + "px"; };
    ta.oninput = () => { s.text = ta.value; fit(); };
    row.append(t, ta); box.append(row); requestAnimationFrame(fit);
  });
}
function applyPreview() { const [w, h] = OUT[state.aspect]; const st = $("stage"); st.style.aspectRatio = `${w} / ${h}`; st.style.width = "auto"; updateCaption(); }
const segmentAt = (t) => state.segments.findIndex((s) => t >= s.start && t < s.end);
const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
function updateCaption() {
  if ($("view-editor").hidden) return;
  const st = state.style, cap = $("cap"), stage = $("stage"), [w, h] = OUT[state.aspect];
  const k = stage.clientWidth / w, px = Math.min(w, h) * st.size / 100 * k, t = video.currentTime;
  let i = segmentAt(t), ghost = false;
  document.querySelectorAll(".segment").forEach((r) => r.classList.toggle("active", +r.dataset.i === i));
  if (i < 0 && video.paused && state.segments.length) { // v pauze ukáž najbližší titulok, aby sa dal presúvať
    ghost = true; i = state.segments.reduce((b, s, n) => Math.abs(s.start - t) < Math.abs(state.segments[b].start - t) ? n : b, 0);
  }
  if (cap.dragging) { if (i < 0) return; }
  cap.textContent = ""; if (i < 0) { cap.hidden = true; return; }
  cap.hidden = false;
  const seg = state.segments[i], words = wordsFor(seg);
  let active = -1; words.forEach((o, n) => { if (t >= o.start) active = n; });
  const outer = document.createElement("span");
  words.forEach((o, n) => {
    if (n) outer.append(" ");
    const sp = document.createElement("span"); sp.textContent = o.w;
    if (st.highlight && n === active && !ghost) { sp.style.color = st.hl; sp.style.display = "inline-block"; sp.style.transform = "scale(1.08)"; }
    outer.append(sp);
  });
  cap.style.fontSize = px + "px"; cap.style.fontFamily = `"${st.font}", sans-serif`; cap.style.fontWeight = st.bold ? 700 : 400;
  cap.style.opacity = ghost ? 0.45 : 1; cap.style.outline = ghost ? "1px dashed #fff" : "none";
  outer.style.color = st.color;
  if (st.bgOpacity > 0) { outer.style.background = `rgba(${rgbOf(st.bg)},${st.bgOpacity / 100})`; outer.style.padding = `${px * .08}px ${px * .3}px`; outer.style.textShadow = "none"; }
  else { outer.style.background = "none"; const o = Math.max(1, px * .07);
    outer.style.textShadow = [[-o,0],[o,0],[0,-o],[0,o],[-o,-o],[o,o],[-o,o],[o,-o]].map(([x,y]) => `${x}px ${y}px 0 #000`).join(","); }
  cap.append(outer);
  const sw = stage.clientWidth, sh = stage.clientHeight, cw = cap.offsetWidth, ch = cap.offsetHeight;
  cap.style.left = clamp(st.x * sw, cw / 2, Math.max(cw / 2, sw - cw / 2)) + "px";
  cap.style.top = clamp(st.y * sh, ch / 2, Math.max(ch / 2, sh - ch / 2)) + "px";
}
const POS_Y = { top: 0.14, middle: 0.5, bottom: 0.84 };
function setupDrag() {
  const cap = $("cap"), stage = $("stage");
  cap.addEventListener("pointerdown", (e) => { cap.dragging = true; cap.setPointerCapture(e.pointerId); cap.style.cursor = "grabbing"; e.preventDefault(); });
  cap.addEventListener("pointermove", (e) => {
    if (!cap.dragging) return;
    const r = stage.getBoundingClientRect();
    state.style.x = clamp((e.clientX - r.left) / r.width, 0, 1);
    state.style.y = clamp((e.clientY - r.top) / r.height, 0, 1);
    state.style.position = null;
    document.querySelectorAll("#positions button").forEach((b) => b.classList.remove("on"));
  });
  const end = () => { cap.dragging = false; cap.style.cursor = "grab"; };
  cap.addEventListener("pointerup", end); cap.addEventListener("pointercancel", end);
}
function syncControls() {
  const st = state.style;
  $("color").value = st.color; $("bg").value = st.bg; $("bgOpacity").value = st.bgOpacity;
  $("bgOpacityOut").textContent = Math.round(st.bgOpacity) + " %"; $("size").value = st.size; $("font").value = st.font;
  document.querySelectorAll("#positions button").forEach((b) => b.classList.toggle("on", b.dataset.v === st.position));
  $("highlight").checked = st.highlight; $("hl").value = st.hl; $("hl").disabled = !st.highlight;
  document.querySelectorAll("#aspects button").forEach((b) => b.classList.toggle("on", b.dataset.v === state.aspect));
  document.querySelectorAll(".preset").forEach((b) => b.classList.toggle("on", b.dataset.id === st.preset));
  applyPreview();
}

// ---------- export: video + titulky nahrané z plátna ----------
function resetExport() {
  for (const u of state.downloads) URL.revokeObjectURL(u);
  state.downloads = []; $("downloads").hidden = true; $("export-error").hidden = true;
  $("export-hint").hidden = true; $("export-status").hidden = true; $("export").disabled = false;
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
  const px = Math.min(W, H) * st.size / 100, lh = px * 1.25, padX = st.bgOpacity > 0 ? px * .3 : 0;
  ctx.font = `${st.bold ? 700 : 400} ${px}px "${st.font}", sans-serif`;
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
      if (st.bgOpacity <= 0) { ctx.strokeStyle = "#000"; ctx.lineWidth = px * .16; ctx.strokeText(word, x, y + lh / 2); }
      ctx.fillStyle = st.highlight && n === active ? st.hl : st.color; ctx.fillText(word, x, y + lh / 2);
      x += ww + sp;
    });
  });
}
const srtTime = (s, sep) => { const ms = Math.round(s * 1000), p = (n, l = 2) => String(n).padStart(l, "0");
  return `${p(Math.floor(ms / 3600000))}:${p(Math.floor(ms / 60000) % 60)}:${p(Math.floor(ms / 1000) % 60)}${sep}${p(ms % 1000, 3)}`; };
const toSrt = (segs) => segs.map((s, i) => `${i + 1}\n${srtTime(s.start, ",")} --> ${srtTime(s.end, ",")}\n${s.text}\n`).join("\n");
const toVtt = (segs) => "WEBVTT\n\n" + segs.map((s) => `${srtTime(s.start, ".")} --> ${srtTime(s.end, ".")}\n${s.text}\n`).join("\n");

async function exportVideo() {
  resetExport();
  $("export").disabled = true; $("export-status").hidden = false; $("export-hint").hidden = false;
  const bar = $("export-status").firstElementChild.firstElementChild, label = $("export-status").lastElementChild;
  bar.style.width = "0%"; label.textContent = "Pripravujem…";
  try {
    if (!window.MediaRecorder) throw new Error("Tento prehliadač nevie nahrávať video. Použi Chrome, Edge alebo Firefox.");
    const [ow, oh] = OUT[state.aspect], k = EXPORT_SHORT / Math.min(ow, oh);
    const W = Math.round(ow * k / 2) * 2, H = Math.round(oh * k / 2) * 2, st = { ...state.style };
    await Promise.all([400, 700].map((wt) => document.fonts.load(`${wt} 40px "${st.font}"`).catch(() => {})));
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
      const vw = ev.videoWidth, vh = ev.videoHeight, s = Math.max(W / vw, H / vh);
      ctx.fillStyle = "#000"; ctx.fillRect(0, 0, W, H);
      ctx.drawImage(ev, (W - vw * s) / 2, (H - vh * s) / 2, vw * s, vh * s);
      const i = segmentAt(ev.currentTime);
      if (i >= 0 && state.segments[i].text.trim()) drawCaption(ctx, state.segments[i], ev.currentTime, W, H, st);
      const p = Math.min(99, Math.round(ev.currentTime / (ev.duration || 1) * 100));
      bar.style.width = p + "%"; label.textContent = `Exportujem… ${p} %`;
      ev.requestVideoFrameCallback ? ev.requestVideoFrameCallback(draw) : requestAnimationFrame(draw);
    };
    ev.onended = () => { live = false; setTimeout(() => rec.stop(), 150); };
    await ac.resume(); rec.start(500); draw(); await ev.play();
    await finished; ac.close();
    const ext = rec.mimeType.includes("mp4") ? "mp4" : "webm";
    const mk = (data, type) => { const u = URL.createObjectURL(new Blob(Array.isArray(data) ? data : [data], { type })); state.downloads.push(u); return u; };
    const a = $("dl-mp4"); a.href = mk(chunks, rec.mimeType || "video/webm"); a.download = `titulky.${ext}`; a.textContent = `Stiahnuť video (${ext.toUpperCase()})`;
    $("dl-srt").href = mk(toSrt(state.segments), "application/x-subrip"); $("dl-srt").download = "titulky.srt";
    $("dl-vtt").href = mk(toVtt(state.segments), "text/vtt"); $("dl-vtt").download = "titulky.vtt";
    $("downloads").hidden = false; $("export-status").hidden = true; $("export-hint").hidden = true;
    bar.style.width = "100%";
  } catch (e) {
    $("export-error").textContent = e.message || "Export zlyhal."; $("export-error").hidden = false;
    $("export-status").hidden = true; $("export-hint").hidden = true;
  }
  $("export").disabled = false;
}

// ---------- štart ----------
function setup() {
  FONTS.forEach((f) => { const o = document.createElement("option"); o.value = o.textContent = f; $("font").append(o); });
  Object.keys(OUT).forEach((a) => { const b = document.createElement("button"); b.dataset.v = a; b.textContent = a; b.onclick = () => { state.aspect = a; syncControls(); }; $("aspects").append(b); });
  PRESETS.forEach((p) => {
    const b = document.createElement("button"); b.className = "preset"; b.dataset.id = p.id;
    const sample = document.createElement("div"); sample.className = "sample";
    const sp = document.createElement("span"); sp.textContent = "Ahoj svet"; sp.style.color = p.color; sp.style.fontFamily = `"${p.font}", sans-serif`; sp.style.fontWeight = p.bold ? 700 : 400;
    if (p.bgOpacity > 0) sp.style.background = `rgba(${rgbOf(p.bg)},${p.bgOpacity / 100})`; else sp.style.textShadow = "-1px 0 #000,1px 0 #000,0 -1px #000,0 1px #000";
    sample.append(sp);
    const name = document.createElement("span"); name.className = "name"; name.textContent = p.name; b.append(sample, name);
    b.onclick = () => { const o = state.style; state.style = { ...p, x: o.x, y: o.y, position: o.position, preset: p.id, highlight: o.highlight }; syncControls(); };
    $("presets").append(b);
  });
  const custom = () => { state.style.preset = null; syncControls(); };
  $("color").oninput = (e) => { state.style.color = e.target.value; custom(); };
  $("bg").oninput = (e) => { state.style.bg = e.target.value; custom(); };
  $("bgOpacity").oninput = (e) => { state.style.bgOpacity = +e.target.value; custom(); };
  $("size").oninput = (e) => { state.style.size = +e.target.value; custom(); };
  $("font").onchange = (e) => { state.style.font = e.target.value; custom(); };
  document.querySelectorAll("#positions button").forEach((b) => b.onclick = () => { state.style.position = b.dataset.v; state.style.x = 0.5; state.style.y = POS_Y[b.dataset.v]; syncControls(); });
  $("highlight").onchange = (e) => { state.style.highlight = e.target.checked; syncControls(); };
  $("hl").oninput = (e) => { state.style.hl = e.target.value; syncControls(); };
  setupDrag();
  new ResizeObserver(updateCaption).observe($("stage"));
  syncControls();
  $("export").onclick = exportVideo;
  $("delete").onclick = () => { $("noplay").hidden = true; video.pause(); video.removeAttribute("src"); resetExport(); state.segments = []; show("upload"); };
  const drop = $("drop");
  $("file").onchange = (e) => e.target.files[0] && pickFile(e.target.files[0]);
  ["dragover", "dragenter"].forEach((n) => drop.addEventListener(n, (e) => { e.preventDefault(); drop.classList.add("over"); }));
  ["dragleave", "drop"].forEach((n) => drop.addEventListener(n, (e) => { e.preventDefault(); drop.classList.remove("over"); }));
  drop.addEventListener("drop", (e) => e.dataTransfer.files[0] && pickFile(e.dataTransfer.files[0]));
  $("drop-hint").textContent = "MP4, MOV, WEBM · zostáva vo vašom prehliadači";
  const loop = () => { updateCaption(); requestAnimationFrame(loop); };
  requestAnimationFrame(loop);
  show("upload");
}
if (MOCK) window.__test = { captureAudio, decodeBuffer, state };
setup();
