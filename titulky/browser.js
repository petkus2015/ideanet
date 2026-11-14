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
  { id: "modern",  name: "Modern",  font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 60, size: 6.5, bold: true },
  { id: "klasik",  name: "Klasik",  font: "Arial", color: "#ffffff", bg: "#000000", bgOpacity: 0, size: 6, bold: true },
  { id: "kino",    name: "Kino",    font: "Montserrat", color: "#ffe066", bg: "#000000", bgOpacity: 0, size: 6.5, bold: true },
  { id: "vyrazny", name: "Výrazný", font: "Poppins", color: "#ffffff", bg: "#6c5cff", bgOpacity: 100, size: 7, bold: true },
  { id: "jemny",   name: "Jemný",   font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 35, size: 5.5, bold: false },
  { id: "svetly",  name: "Svetlý",  font: "Montserrat", color: "#111111", bg: "#ffffff", bgOpacity: 90, size: 6, bold: true },
];
const state = { aspect: "9:16", style: { ...PRESETS[0], position: "bottom", preset: "modern" },
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

// ---------- rozpoznanie reči (Whisper v prehliadači) ----------
function toMono(buf) {
  const n = buf.numberOfChannels, out = new Float32Array(buf.length);
  for (let c = 0; c < n; c++) { const d = buf.getChannelData(c); for (let i = 0; i < out.length; i++) out[i] += d[i] / n; }
  return out;
}
async function decodeAudio(file) {
  const ctx = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 16000 });
  try { return toMono(await ctx.decodeAudioData(await file.arrayBuffer())); }
  catch { throw new Error("Zo súboru sa nepodarilo prečítať zvuk. Skús MP4 so zvukom AAC."); }
  finally { ctx.close(); }
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
    await new Promise((r) => setTimeout(r, 600));
    const d = state.duration, out = []; let t = 0.3, i = 1;
    while (t + 1.5 < d) { out.push({ start: t, end: t + 2, text: `Ukážkový titulok číslo ${i++}` }); t += 2.3; }
    return out;
  }
  const audio = await decodeAudio(file);
  const id = MODELS[modelKey];
  if (asrId !== id) {
    setBusy("Sťahujem model…", "Stiahne sa len raz, potom ho prehliadač použije znova.", 0);
    let mod;
    try { mod = await import(TF_URL); }
    catch { throw new Error("Nepodarilo sa načítať knižnicu na rozpoznávanie. Skontroluj pripojenie na internet."); }
    const files = {};
    try {
      asr = await mod.pipeline("automatic-speech-recognition", id, {
        progress_callback: (p) => {
          if (p.status !== "progress" || !p.total) return;
          files[p.file] = { loaded: p.loaded, total: p.total };
          const all = Object.values(files), loaded = all.reduce((a, f) => a + f.loaded, 0), total = all.reduce((a, f) => a + f.total, 0);
          setBusy("Sťahujem model…", `${Math.round(loaded / 1048576)} z ${Math.round(total / 1048576)} MB`, loaded / total * 100);
        },
      });
    } catch { throw new Error("Model sa nepodarilo stiahnuť. Skontroluj pripojenie na internet."); }
    asrId = id;
  }
  setBusy("Rozpoznávam reč…", "Pri dlhšom videu to môže trvať niekoľko minút. Nechaj túto kartu otvorenú.");
  const opts = { task: "transcribe", return_timestamps: true, chunk_length_s: 30, stride_length_s: 5 };
  if (LANGS[lang]) opts.language = LANGS[lang];
  const res = await asr(audio, opts);
  const chunks = res.chunks && res.chunks.length ? res.chunks : [{ timestamp: [0, state.duration], text: res.text }];
  return chunks.flatMap((c) => {
    const [s, e] = c.timestamp;
    return c.text.trim() ? splitChunk(c.text, s ?? 0, e ?? state.duration) : [];
  });
}

// ---------- nahratie ----------
function pickFile(file) {
  $("upload-error").hidden = true;
  if (!file.type.startsWith("video/")) return showError("Vyber súbor s videom.");
  if (state.url) URL.revokeObjectURL(state.url);
  state.file = file; state.url = URL.createObjectURL(file);
  video.src = state.url;
  setBusy("Načítavam video…");
  video.onerror = () => showError("Tento formát prehliadač neprehrá. Skús MP4.");
  video.onloadedmetadata = async () => {
    state.duration = video.duration || 0;
    try {
      state.segments = await recognize(file, $("model").value, $("lang").value);
    } catch (e) { return showError(e.message || "Rozpoznanie zlyhalo."); }
    buildSegments(); show("editor"); applyPreview(); resetExport();
  };
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
function updateCaption() {
  if ($("view-editor").hidden) return;
  const st = state.style, cap = $("cap"), [w, h] = OUT[state.aspect];
  const k = $("stage").clientWidth / w, px = Math.min(w, h) * st.size / 100 * k;
  const i = segmentAt(video.currentTime);
  document.querySelectorAll(".segment").forEach((r) => r.classList.toggle("active", +r.dataset.i === i));
  cap.textContent = ""; if (i < 0) return;
  const span = document.createElement("span"); span.textContent = state.segments[i].text;
  cap.style.fontSize = px + "px"; cap.style.fontFamily = `"${st.font}", sans-serif`; cap.style.fontWeight = st.bold ? 700 : 400;
  span.style.color = st.color;
  if (st.bgOpacity > 0) { span.style.background = `rgba(${rgbOf(st.bg)},${st.bgOpacity / 100})`; span.style.padding = `${px * .08}px ${px * .3}px`; span.style.textShadow = "none"; }
  else { span.style.background = "none"; const o = Math.max(1, px * .07);
    span.style.textShadow = [[-o,0],[o,0],[0,-o],[0,o],[-o,-o],[o,o],[-o,o],[o,-o]].map(([x,y]) => `${x}px ${y}px 0 #000`).join(","); }
  cap.style.top = cap.style.bottom = cap.style.transform = "";
  if (st.position === "top") cap.style.top = "12%"; else if (st.position === "bottom") cap.style.bottom = "12%";
  else { cap.style.top = "50%"; cap.style.transform = "translateY(-50%)"; }
  cap.append(span);
}
function syncControls() {
  const st = state.style;
  $("color").value = st.color; $("bg").value = st.bg; $("bgOpacity").value = st.bgOpacity;
  $("bgOpacityOut").textContent = Math.round(st.bgOpacity) + " %"; $("size").value = st.size; $("font").value = st.font;
  document.querySelectorAll("#positions button").forEach((b) => b.classList.toggle("on", b.dataset.v === st.position));
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
function wrapLines(ctx, text, maxW) {
  const lines = []; let cur = "";
  for (const w of text.split(/\s+/)) {
    const next = cur ? cur + " " + w : w;
    if (cur && ctx.measureText(next).width > maxW) { lines.push(cur); cur = w; } else cur = next;
  }
  if (cur) lines.push(cur);
  return lines;
}
function drawCaption(ctx, text, W, H, st) {
  const px = Math.min(W, H) * st.size / 100, lh = px * 1.25;
  ctx.font = `${st.bold ? 700 : 400} ${px}px "${st.font}", sans-serif`;
  ctx.textBaseline = "middle"; ctx.lineJoin = "round";
  const lines = wrapLines(ctx, text, W * 0.84), total = lines.length * lh;
  const top = st.position === "top" ? H * 0.12 : st.position === "middle" ? (H - total) / 2 : H * 0.88 - total;
  lines.forEach((line, i) => {
    const w = ctx.measureText(line).width, x = (W - w) / 2, y = top + i * lh;
    if (st.bgOpacity > 0) {
      ctx.fillStyle = `rgba(${rgbOf(st.bg)},${st.bgOpacity / 100})`;
      ctx.beginPath();
      if (ctx.roundRect) ctx.roundRect(x - px * .3, y, w + px * .6, lh, px * .12); else ctx.rect(x - px * .3, y, w + px * .6, lh);
      ctx.fill();
    } else {
      ctx.strokeStyle = "#000"; ctx.lineWidth = px * .16; ctx.strokeText(line, x, y + lh / 2);
    }
    ctx.fillStyle = st.color; ctx.fillText(line, x, y + lh / 2);
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
      if (i >= 0 && state.segments[i].text.trim()) drawCaption(ctx, state.segments[i].text, W, H, st);
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
    b.onclick = () => { state.style = { ...p, position: state.style.position, preset: p.id }; syncControls(); };
    $("presets").append(b);
  });
  const custom = () => { state.style.preset = null; syncControls(); };
  $("color").oninput = (e) => { state.style.color = e.target.value; custom(); };
  $("bg").oninput = (e) => { state.style.bg = e.target.value; custom(); };
  $("bgOpacity").oninput = (e) => { state.style.bgOpacity = +e.target.value; custom(); };
  $("size").oninput = (e) => { state.style.size = +e.target.value; custom(); };
  $("font").onchange = (e) => { state.style.font = e.target.value; custom(); };
  document.querySelectorAll("#positions button").forEach((b) => b.onclick = () => { state.style.position = b.dataset.v; syncControls(); });
  new ResizeObserver(updateCaption).observe($("stage"));
  syncControls();
  $("export").onclick = exportVideo;
  $("delete").onclick = () => { video.pause(); video.removeAttribute("src"); resetExport(); state.segments = []; show("upload"); };
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
setup();
