"use strict";
const TF_URL = "https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.7.6";
const LANGS = { sk: "slovak", cs: "czech", en: "english", es: "spanish", ar: "arabic", tr: "turkish", hi: "hindi", pl: "polish", zh: "chinese" };
/*I18N*/
const DEMO = false; // true v ukážke na webe: vzorové titulky a výsledok sa prehrá priamo na stránke
const MOCK = DEMO || new URLSearchParams(location.search).has("mock"); // len na skúšanie bez modelu
const SAMPLE_SK = ["Ahoj, vitajte pri ukážke aplikácie.", "Nahráte video a titulky sa vytvoria automaticky.", "Vyberiete štýl z ponuky,", "zmeníte farbu textu, font alebo podklad,", "a práve vyslovené slovo sa zvýrazní."], SAMPLE = SAMPLE_SK.map((x) => tr(x)); // vzorové titulky (ukážka), v jazyku rozhrania

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
const FONTS = ["Inter", "Montserrat", "Poppins", "Bebas Neue", "Roboto Slab", "Arial", "Georgia", "Cairo", "Noto Sans Devanagari", "Noto Sans SC"];
// Písma pre arabčinu, hindčinu a čínštinu: každý štýl ich použije pre znaky, ktoré jeho písmo nemá.
const SCRIPT_FALLBACK = '"Cairo", "Noto Sans Devanagari", "Noto Sans SC", sans-serif';
const SCRIPTS = [ // písmo, ktoré sa pri titulkoch v danom písme ponúkne a nastaví
  { re: /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/, font: "Cairo", name: "arabské" },
  { re: /[\u0900-\u097F]/, font: "Noto Sans Devanagari", name: "hindské" },
  { re: /[\u3400-\u9FFF\uF900-\uFAFF]/, font: "Noto Sans SC", name: "čínske" },
];
function scriptOf(text) { // ktoré písmo prevažuje v titulkoch (null = latinka a pod.)
  let best = null, n = 0;
  for (const sc of SCRIPTS) { const c = (text.match(new RegExp(sc.re.source, "g")) || []).length; if (c > n) { n = c; best = sc; } }
  return n > 5 ? best : null;
}
function loadScriptFonts(text) { // webové písmo sa pre plátno načíta len na požiadanie
  for (const sc of SCRIPTS) if (sc.re.test(text)) for (const w of [400, 700, 800]) document.fonts.load(`${w} 40px "${sc.font}"`, text.slice(0, 200)).catch(() => {});
}
const PRESETS = [
  { hl: "#ffe066", id: "modern",  name: "Modern",  font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 60, size: 6.5, bold: true },
  { hl: "#ffe066", id: "klasik",  name: "Klasik",  font: "Arial", color: "#ffffff", bg: "#000000", bgOpacity: 0, size: 6, bold: true },
  { hl: "#ffffff", id: "kino",    name: "Kino",    font: "Montserrat", color: "#ffe066", bg: "#000000", bgOpacity: 0, size: 6.5, bold: true },
  { hl: "#ffe066", id: "vyrazny", name: "Výrazný", font: "Poppins", color: "#ffffff", bg: "#6c5cff", bgOpacity: 100, size: 7, bold: true },
  { hl: "#ffe066", id: "jemny",   name: "Jemný",   font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 35, size: 5.5, bold: false },
  { hl: "#6c5cff", id: "svetly",  name: "Svetlý",  font: "Montserrat", color: "#111111", bg: "#ffffff", bgOpacity: 90, size: 6, bold: true },
  { hl: "#00b8fc", id: "modry", name: "PS blue", font: "Montserrat", italic: true, weight: 800, color: "#ffffff", stroke: "#00007d", strokeW: 0.2, lineH: 1.35, bg: "#000000", bgOpacity: 0, size: 6.8, bold: true, highlight: true, y: 0.5, position: "middle" },
  { hl: "#f8a0b6", id: "cerveny", name: "PS red", font: "Montserrat", italic: true, weight: 800, color: "#ffffff", stroke: "#f50306", strokeW: 0.2, lineH: 1.35, bg: "#000000", bgOpacity: 0, size: 6.8, bold: true, highlight: true, y: 0.5, position: "middle" }, // ako PS blue, len červený
  { hl: "#ffffff", id: "slowdown", name: "Slow down", font: "Poppins", weight: 600, color: "#ffffff", shadow: true, bg: "#000000", bgOpacity: 0, size: 5.4, bold: true, anim: "slowdown", lineH: 1.3, y: 0.58, position: null },
  { hl: "#ffe066", id: "dynamic", name: "Dynamický", dynamic: true, roam: true, caps: true, y: 0.6, font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 0, size: 8, bold: true },
];
const state = { aspect: "9:16", style: { ...PRESETS[0], preset: "modern", highlight: false, on: true, anim: "none" },
  segments: [], file: null, url: null, duration: 0, out: null, results: {}, busyExport: false, playable: null, job: 0, pos: {}, audio: null,
  spk: [], spkStyle: {}, cur: 1, tab: "subs", quality: "orig", clips: [], clipSel: 0, trSel: -1, undo: [], redo: [], pc: 0, pvRate: 1, autoStop: null, thumbs: null, exportCancel: false, crop: {}, tool: "captions",
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
  $("sideEmpty").hidden = view === "editor"; if (view !== "editor") $("textCard").hidden = true;
  $("content").classList.toggle("ws", view === "editor" && page === "work");
  if (view === "editor") applyTab(); else updateTabs();
};
function applyTab() { // zobrazí v editore len to, čo patrí k zvolenému kroku
  const t = state.tab;
  $("pane-subs").hidden = t !== "subs"; $("pane-cut").hidden = t !== "edit"; $("pane-edit").hidden = t !== "edit"; $("pane-export").hidden = t !== "export";
  $("timelineCard").hidden = t === "export";
  $("textCard").hidden = $("textCard").parentElement === $("sideSubs") ? false : t !== "subs"; // vľavo je stále, na mobile len pri Titulkovaní
  $("timelineCard").classList.toggle("video-only", t === "edit"); // v Úprave len prehrávanie a posun v čase, bez titulkov
  $("timelineCard").classList.toggle("cut-mode", t === "edit"); // v Úprave časti videa a prechody namiesto titulkov
  if (t === "edit") renderCutUi();
  if (t !== "subs" && state.sel && state.sel.size) clearSel();
  state.tool = t === "edit" ? "crop" : t === "subs" ? "captions" : "none";
  $("pvHint").textContent = t === "edit" ? "Ťahaním v náhľade posúvaš video, kolieskom myši ho približuješ. Prehrávanie preskakuje vystrihnuté časti a ukazuje prechody ako v exporte."
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
// Ručné zalomenie riadku (Enter v texte titulku): slovo na začiatku nového riadku dostane br = true.
function lineBreaks(text) {
  const out = [];
  text.trim().split(/\n/).forEach((line, li) => line.trim().split(/\s+/).filter(Boolean).forEach((w, k) => out.push(li > 0 && k === 0)));
  return out;
}
function wordsFor(seg) {
  const ws = seg.text.trim().split(/\s+/).filter(Boolean), br = lineBreaks(seg.text);
  const words = seg.words && seg.words.length === ws.length ? seg.words.map((o, i) => ({ ...o, w: ws[i] })) : estimateWords(seg.text, seg.start, seg.end);
  words.forEach((o, i) => { o.br = !!br[i]; });
  return words;
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
// Zvuk z MP4/MOV (aj HEVC z iPhonu): z kontajnera sa po kúskoch prečíta iba zvuková stopa AAC
// a zabalí sa do ADTS, ktoré vie rozbaliť každý prehliadač. Celé video sa nenačíta do pamäte.
async function readBytes(file, a, b) { return new Uint8Array(await file.slice(a, b).arrayBuffer()); }
const u32 = (d, o) => ((d[o] << 24) | (d[o + 1] << 16) | (d[o + 2] << 8) | d[o + 3]) >>> 0;
const fourcc = (d, o) => String.fromCharCode(d[o], d[o + 1], d[o + 2], d[o + 3]);
function boxes(d, a, b) {
  const out = [];
  for (let o = a; o + 8 <= b;) {
    let size = u32(d, o), hdr = 8;
    if (size === 1) { size = u32(d, o + 8) * 4294967296 + u32(d, o + 12); hdr = 16; } else if (size === 0) size = b - o;
    if (size < hdr || o + size > b) break;
    out.push({ type: fourcc(d, o + 4), s: o + hdr, e: o + size }); o += size;
  }
  return out;
}
const child = (d, box, type) => box && boxes(d, box.s, box.e).find((x) => x.type === type);
const path = (d, box, ...types) => types.reduce((b, t) => child(d, b, t), box);
async function findMoov(file) {
  for (let o = 0; o + 8 <= file.size;) {
    const h = await readBytes(file, o, Math.min(o + 16, file.size));
    let size = u32(h, 0), hdr = 8;
    if (size === 1) { size = u32(h, 8) * 4294967296 + u32(h, 12); hdr = 16; } else if (size === 0) size = file.size - o;
    if (size < hdr) return null;
    if (fourcc(h, 4) === "moov") return size > 200e6 ? null : readBytes(file, o + hdr, o + size);
    o += size;
  }
  return null;
}
// AudioSpecificConfig z esds (deskriptor s tagom 5)
function ascFrom(d, s, e) {
  for (let o = s; o < e - 2; o++) {
    if (d[o] !== 5) continue;
    let len = 0, p = o + 1;
    for (let k = 0; k < 4; k++) { const c = d[p++]; len = (len << 7) | (c & 127); if (!(c & 128)) break; }
    if (len >= 2 && len < 64 && p + len <= e) {
      const ot = d[p] >> 3, fi = ((d[p] & 7) << 1) | (d[p + 1] >> 7), ch = (d[p + 1] >> 3) & 15;
      if (ot >= 1 && ot <= 4 && fi < 13 && ch > 0 && ch < 8) return { ot, fi, ch };
    }
  }
  return null;
}
function audioTrack(d) {
  for (const trak of boxes(d, 0, d.length).filter((b) => b.type === "trak")) {
    const mdia = child(d, trak, "mdia"), hdlr = child(d, mdia, "hdlr");
    if (!hdlr || fourcc(d, hdlr.s + 8) !== "soun") continue;
    const stbl = path(d, mdia, "minf", "stbl"), stsd = child(d, stbl, "stsd");
    if (!stsd) continue;
    const entry = boxes(d, stsd.s + 8, stsd.e)[0];
    if (!entry || entry.type !== "mp4a") continue;
    const ver = (d[entry.s + 8] << 8) | d[entry.s + 9]; // QuickTime verzia zvukového záznamu
    const asc = ascFrom(d, entry.s + 28 + (ver === 1 ? 16 : ver === 2 ? 36 : 0), entry.e);
    if (!asc) continue;
    const stsz = child(d, stbl, "stsz"), stsc = child(d, stbl, "stsc"), stco = child(d, stbl, "stco") || child(d, stbl, "co64");
    if (!stsz || !stsc || !stco) continue;
    const fixed = u32(d, stsz.s + 4), count = u32(d, stsz.s + 8);
    const sizes = new Uint32Array(count);
    for (let i = 0; i < count; i++) sizes[i] = fixed || u32(d, stsz.s + 12 + i * 4);
    const nc = u32(d, stco.s + 4), big = stco.type === "co64", offs = new Array(nc);
    for (let i = 0; i < nc; i++) offs[i] = big ? u32(d, stco.s + 8 + i * 8) * 4294967296 + u32(d, stco.s + 12 + i * 8) : u32(d, stco.s + 8 + i * 4);
    const ns = u32(d, stsc.s + 4), runs = [];
    for (let i = 0; i < ns; i++) runs.push([u32(d, stsc.s + 8 + i * 12) - 1, u32(d, stsc.s + 12 + i * 12)]);
    const chunks = []; let si = 0; // [posun v súbore, prvá vzorka, počet vzoriek]
    for (let r = 0; r < runs.length; r++) {
      const end = r + 1 < runs.length ? runs[r + 1][0] : nc;
      for (let c = runs[r][0]; c < end && si < count; c++) { const n = Math.min(runs[r][1], count - si); chunks.push([offs[c], si, n]); si += n; }
    }
    if (chunks.length) return { asc, sizes, chunks };
  }
  return null;
}
async function extractMp4Audio(file, onProgress) {
  const head = await readBytes(file, 0, 12);
  if (head.length < 12 || !["ftyp", "moov", "wide", "mdat", "free"].includes(fourcc(head, 4))) return null;
  const moov = await findMoov(file);
  if (!moov) return null;
  const t = audioTrack(moov);
  if (!t) return null;
  const { asc, sizes, chunks } = t;
  let total = 0; for (const s of sizes) total += s;
  const out = new Uint8Array(total + sizes.length * 7);
  let w = 0, done = 0, lastP = 0;
  // Susedné kúsky zvuku sa čítajú naraz (najviac ~4 MB), aby čítanie bolo rýchle aj na mobile.
  for (let i = 0; i < chunks.length;) {
    const start = chunks[i][0]; let j = i, end = start;
    while (j < chunks.length) {
      const [off, s0, n] = chunks[j]; let len = 0;
      for (let k = 0; k < n; k++) len += sizes[s0 + k];
      if (j > i && (off < end || off + len - start > 4e6)) break;
      end = off + len; j++;
    }
    const buf = await readBytes(file, start, end);
    for (let c = i; c < j; c++) {
      let p = chunks[c][0] - start;
      for (let k = 0; k < chunks[c][2]; k++) {
        const len = sizes[chunks[c][1] + k], fl = len + 7;
        if (p + len > buf.length) throw new Error("mp4");
        out[w] = 0xff; out[w + 1] = 0xf1;
        out[w + 2] = ((asc.ot - 1) << 6) | (asc.fi << 2) | (asc.ch >> 2);
        out[w + 3] = ((asc.ch & 3) << 6) | (fl >> 11);
        out[w + 4] = (fl >> 3) & 255; out[w + 5] = ((fl & 7) << 5) | 0x1f; out[w + 6] = 0xfc;
        out.set(buf.subarray(p, p + len), w + 7); w += fl; p += len;
      }
    }
    for (let c = i; c < j; c++) for (let k = 0; k < chunks[c][2]; k++) done += sizes[chunks[c][1] + k];
    i = j;
    if (onProgress && performance.now() - lastP > 150) { lastP = performance.now(); onProgress(done / (total || 1)); await new Promise((r) => setTimeout(r)); }
  }
  return out.buffer;
}
// Záloha: ak prehliadač nevie rozbaliť zvuk zo súboru, zvuk sa nahrá pri prehrávaní videa (trvá dĺžku videa).
// iPhone dovolí prehrať zvuk až po klepnutí, preto sa v prípade potreby ukáže tlačidlo.
function waitTap() {
  const b = $("busy-go"); b.hidden = false;
  return new Promise((r) => { b.onclick = () => { b.hidden = true; r(); }; });
}
async function captureAudio(url) {
  const tv = document.createElement("video"); tv.playsInline = true; tv.preload = "auto"; tv.src = url;
  await Promise.race([
    new Promise((res, rej) => { tv.onloadedmetadata = res; tv.onerror = () => rej(new Error("play")); tv.load(); }),
    new Promise((_, rej) => setTimeout(() => rej(new Error("play")), 20000)),
  ]);
  const ac = new (window.AudioContext || window.webkitAudioContext)();
  const dest = ac.createMediaStreamDestination();
  ac.createMediaElementSource(tv).connect(dest);
  const rec = new MediaRecorder(dest.stream), chunks = [];
  rec.ondataavailable = (e) => e.data.size && chunks.push(e.data);
  const stopped = new Promise((r) => (rec.onstop = r));
  try { await ac.resume(); await tv.play(); }
  catch {
    setBusy("Čítam zvuk…", "Klepni na tlačidlo, prehliadač potom prečíta zvuk z videa.");
    await waitTap(); await ac.resume(); await tv.play();
  }
  rec.start();
  const iv = setInterval(() => setBusy("Čítam zvuk…", `Prehliadač číta zvuk v reálnom čase: ${fmtTime(tv.currentTime)} z ${fmtTime(tv.duration)}`, phasePct("audio", tv.currentTime / tv.duration)), 400);
  await new Promise((r) => (tv.onended = r));
  clearInterval(iv); rec.stop(); await stopped; ac.close();
  return decodeBuffer(await new Blob(chunks).arrayBuffer());
}
async function decodeAudio(file) {
  const mb = (file.size / 1048576).toFixed(0);
  try {
    const aac = await extractMp4Audio(file, (f) => setBusy("Čítam zvuk…", `Načítavam zvukovú stopu z videa (${mb} MB)…`, phasePct("audio", f * 0.8)));
    if (aac) { setBusy("Čítam zvuk…", "Rozbaľujem zvuk…", phasePct("audio", 0.85)); return await decodeBuffer(aac); }
  } catch (e) { console.warn("MP4 zvuk", e); }
  // Celé video do pamäte len pri menších súboroch, veľké videá by mobil nezvládol.
  if (file.size < 400e6) {
    try { setBusy("Čítam zvuk…", `Načítavam video (${mb} MB)…`, phasePct("audio", 0.1)); return await decodeBuffer(await file.arrayBuffer()); } catch { /* skúsi sa záloha */ }
  }
  try { setBusy("Čítam zvuk…", "Prehliadač nevie rozbaliť zvuk priamo, číta ho pri prehrávaní."); return await captureAudio(state.url); }
  catch { $("busy-go").hidden = true; throw new Error("Zo súboru sa nepodarilo prečítať zvuk. Súbor je asi poškodený, bez zvuku alebo má zvukový kodek, ktorý prehliadač nepozná. Skús ho previesť do MP4 (H.264 + AAC)."); }
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
    if (spec.onProgress) spec.onProgress(loaded, total);
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
      return await tf.pipeline(spec.task || "automatic-speech-recognition", spec.id, { ...spec.opts, progress_callback: progress });
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
const newSpk = (id) => ({ id, name: tr("Rečník") + " " + id, color: SPK_COLORS[(id - 1) % SPK_COLORS.length] });
const multiSpk = () => state.spk.length > 1;
const spkById = (id) => state.spk.find((x) => x.id === id) || state.spk[0] || newSpk(1);
const spkColor = (id) => spkById(id).color;
const spkCount = (id) => state.segments.reduce((a, x) => a + ((x.speaker || 1) === id ? 1 : 0), 0);
function resetSpeakers() { state.spk = [newSpk(1)]; state.spkStyle = {}; state.cur = 1; state.collapsedSpk.clear(); }
function defaultSpeakerStyle() { // nový rečník dostane iný štýl, ako majú ostatní, aby bol rozdiel hneď vidno
  const used = new Set([state.style.preset, ...Object.values(state.spkStyle).map((x) => x.preset)]);
  const p = PRESETS.find((x) => !x.dynamic && !used.has(x.id)) || PRESETS.find((x) => x.id !== state.style.preset) || PRESETS[0];
  return { ...p, preset: p.id, highlight: state.style.highlight, caps: p.caps ?? state.style.caps, anim: state.style.anim || "none" };
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
  return p ? { ...p, preset: p.id, highlight: p.highlight ?? base.highlight, caps: p.caps ?? base.caps, anim: base.anim, on: state.style.on } : { ...base, on: state.style.on };
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
}
function saveUrl(url, name) {
  const a = document.createElement("a"); a.href = url; a.download = name; document.body.append(a); a.click(); a.remove();
}
const saveVideo = (k) => { const r = state.results[k]; if (r) saveUrl(r.url, `subgen-${k.replace(":", "x")}.${r.ext}`); };
function saveText(text, name, type) {
  const u = URL.createObjectURL(new Blob([text], { type })); saveUrl(u, name); setTimeout(() => URL.revokeObjectURL(u), 4000);
}
function reflectResult() { // premietne uložený výsledok (ak už bol tento formát vyexportovaný) do viditeľnej karty
  if (!state.out) return;
  const r = state.results[state.out.k];
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
    <div class="out-prog" hidden><div class="bar"><i></i></div><span></span></div>`;
  box.append(el);
  const o = { k, el, cv: el.querySelector("canvas"), pv: el.querySelector(".pv"), time: el.querySelector(".out-time"),
    prog: el.querySelector(".out-prog"), drag: null };
  // titulky sa presúvajú ťahaním priamo v náhľade
  const SNAP = 10; // magnet: do 10 px od stredu sa titulok prichytí na stred (vodorovne aj zvisle), Alt magnet vypne
  const move = (e) => {
    const r = o.cv.getBoundingClientRect(), q = getPos(k, o.drag.spk);
    let x = e.clientX - r.left, y = e.clientY - r.top;
    const gx = !e.altKey && Math.abs(x - r.width / 2) < SNAP, gy = !e.altKey && Math.abs(y - r.height / 2) < SNAP;
    if (gx) x = r.width / 2; if (gy) y = r.height / 2;
    o.guides = { x: gx, y: gy };
    q.x = clamp(x / r.width, 0, 1); q.y = clamp(y / r.height, 0, 1); q.position = gy && gx ? "middle" : null;
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
  const end = () => { o.drag = null; o.guides = null; };
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
    ctx.fillStyle = "rgba(47,211,230,.65)";
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
    const label = document.createElement("span"); label.className = "chip-label"; label.dir = "auto"; label.textContent = s.text.slice(0, 36);
    const hl = document.createElement("div"); hl.className = "h l"; const hr = document.createElement("div"); hr.className = "h r";
    el.append(label, hl, hr); wireChip(el, s, i, hl, hr); box.append(el);
  });
}
function renderTimeline() {
  if (!$("tlScroll")) return;
  const dur = tlDuration(), avail = $("tlScroll").clientWidth || 600;
  const px = Math.max(10, avail / dur * state.tlZoomVal);
  state.tlPxPerSec = px;
  drawRuler(dur, px); drawWave(dur, px); renderChips(dur, px);
  if (state.tab === "edit" && !trackBusy) renderClipTrack();
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
  const el = e && e.target && e.target.closest ? e.target.closest(".segment, .chip") : null; // kde ukázať okienko výberu
  selAt = el ? { kind: el.classList.contains("chip") ? "chip" : "segment" } : { kind: "point", x: e ? e.clientX : innerWidth / 2, y: e ? e.clientY : innerHeight / 2 };
  refreshSel();
}
// Okienko výberu (rečník a štýl) sa ukáže pri vybranom titulku: vedľa riadku v zozname, nad titulkom na časovej osi.
let selAt = null;
function placeSelDlg() {
  const bar = $("selBar"); if (!bar || bar.hidden) return;
  const ids = [...state.sel].sort((a, b) => a - b), bw = bar.offsetWidth, bh = bar.offsetHeight;
  let x = innerWidth / 2 - bw / 2, y = innerHeight / 2 - bh / 2;
  const at = selAt || { kind: "segment" };
  const el = at.kind === "point" ? null : document.querySelector(`.${at.kind}[data-i="${ids[0]}"]`);
  const r = el && el.getClientRects().length ? el.getBoundingClientRect() : null;
  const rtl = document.documentElement.dir === "rtl"; // v arabčine je zoznam vpravo, okienko patrí naľavo od neho
  if (r && at.kind === "segment") { x = rtl ? r.left - bw - 10 : r.right + 10; y = r.top; if (x + bw > innerWidth - 8 || x < 8) { x = rtl ? r.right - bw : r.left; y = r.bottom + 8; } }
  else if (r) { x = r.left; y = r.top - bh - 10; if (y < 8) y = r.bottom + 10; }
  else if (at.kind === "point") { x = at.x - bw / 2; y = at.y + 16; }
  bar.style.left = Math.round(clamp(x, 8, innerWidth - bw - 8)) + "px"; bar.style.top = Math.round(clamp(y, 8, innerHeight - bh - 8)) + "px";
}
function clearSel() { state.sel.clear(); state.selAnchor = -1; refreshSel(); }
function refreshSel() {
  document.querySelectorAll(".chip").forEach((c) => c.classList.toggle("sel", state.sel.has(+c.dataset.i)));
  document.querySelectorAll(".segment").forEach((r) => r.classList.toggle("sel", state.sel.has(+r.dataset.i)));
  const bar = $("selBar"); if (!bar) return;
  const ids = [...state.sel].sort((a, b) => a - b).filter((k) => state.segments[k]);
  bar.hidden = ids.length === 0;
  if (!ids.length) return;
  requestAnimationFrame(placeSelDlg);
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
  if (state.tab === "edit") { syncClipPlayhead(); return; }
  const ph = $("tlPlayhead"); if (!ph) return;
  const x = video.currentTime * state.tlPxPerSec;
  ph.style.left = Math.round(x) + "px"; $("tlCur").textContent = fmtTimeline(video.currentTime); $("tlDur").textContent = fmtTimeline(tlDuration());
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
    const ta = document.createElement("textarea"); ta.value = s.text; ta.rows = 1; ta.dir = "auto"; // arabčina sa píše sprava ta.setAttribute("aria-label", "Text titulku " + (i + 1));
    const fit = () => { ta.style.height = "auto"; ta.style.height = ta.scrollHeight + "px"; };
    ta.oninput = () => { s.text = ta.value; fit(); bumpDyn(); invalidateResults(); updateChip(i); loadScriptFonts(ta.value); };
    const sp = document.createElement("button"); sp.type = "button"; sp.className = "spk"; sp.dataset.i = i; sp.dataset.s = n; sp.style.setProperty("--c", who.color);
    sp.setAttribute("aria-haspopup", "menu"); sp.title = who.name + " · klikni a vyber iného rečníka"; sp.setAttribute("aria-label", sp.title);
    const dot = document.createElement("i"); dot.className = "dot"; const lab = document.createElement("span"); lab.textContent = who.name;
    sp.append(dot, lab); sp.insertAdjacentHTML("beforeend", '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>');
    sp.onclick = (e) => { e.stopPropagation(); openSpkMenu(sp, (id) => { s.speaker = id === "new" ? addSpeaker(true) : id; speakersChanged(); }, n); };
    row.onclick = (e) => { if (e.target === row) selectSeg(i, e); }; // klik na riadok mimo textu tiež vyberie titulok
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
    name.oninput = () => { x.name = name.value.trim() || tr("Rečník") + " " + x.id; renderSelSpk(); buildSegments(); syncControls(); };
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
  resetClips(); makeThumbs(state.job); renderCutUi();
  trOriginal = null; $("trUndo").hidden = true; $("trProg").hidden = true; $("trMsg").textContent = ""; $("trSubs").hidden = true;
  applyScriptFont();
}
// Arabské, hindské alebo čínske titulky: načíta sa vhodné písmo a titulkom sa rovno nastaví (dá sa zmeniť).
function applyScriptFont() {
  const all = state.segments.map((s) => s.text).join(" "); loadScriptFonts(all);
  const sc = scriptOf(all); if (!sc) return;
  [state.style, ...Object.values(state.spkStyle)].forEach((st) => { st.font = sc.font; });
  bumpDyn(); syncControls();
  $("fontMsg").textContent = `Pre ${sc.name} titulky je nastavené písmo ${sc.font}.`;
}

// ---------- kreslenie snímky (rovnaké pre náhľady aj export) ----------
// Orez: crop = { zoom >= 1, cx, cy } - priblíženie a bod zdrojového videa (0 až 1), ktorý je v strede záberu.
function cropRect(vw, vh, W, H, crop) {
  const z = crop?.zoom || 1, s = Math.max(W / vw, H / vh) * z, iw = vw * s, ih = vh * s;
  const cx = clamp(crop?.cx ?? 0.5, W / 2 / iw, 1 - W / 2 / iw), cy = clamp(crop?.cy ?? 0.5, H / 2 / ih, 1 - H / 2 / ih);
  return { iw, ih, dx: W / 2 - cx * iw, dy: H / 2 - cy * ih };
}
// ---------- strih ako v CapCut ----------
// Video je zoznam klipov v poradí, v akom idú vo výslednom videu. Každý klip je úsek originálu (start–end),
// má rýchlosť, efekt, filter a prechod do nasledujúceho klipu. Zmazaný klip zmizne a stopa sa zatvorí,
// klipy sa dajú presúvať a orezávať. Titulky ostávajú v čase originálu, takže idú so svojím klipom.
const TRANSITIONS = [
  { id: "none", name: "Žiadny" }, { id: "fade", name: "Stmievačka" }, { id: "white", name: "Záblesk" }, { id: "blur", name: "Rozmazanie" },
  { id: "zoom", name: "Priblíženie" }, { id: "whip", name: "Švih" }, { id: "spin", name: "Otočenie" }, { id: "glitch", name: "Glitch" },
];
const EFFECTS = [
  { id: "none", name: "Žiadny" }, { id: "kenburns", name: "Zoom" }, { id: "pulse", name: "Pulz" },
  { id: "shake", name: "Otras" }, { id: "glitch", name: "Glitch" }, { id: "vhs", name: "VHS" },
];
const FILTERS = [
  { id: "none", name: "Pôvodný" }, { id: "vivid", name: "Živý" }, { id: "warm", name: "Teplý" }, { id: "cool", name: "Studený" },
  { id: "bw", name: "Čiernobiely" }, { id: "vintage", name: "Vintage" }, { id: "film", name: "Film" },
];
const SPEEDS = [0.5, 1, 1.5, 2, 3];
const nameOf = (list, id) => (list.find((x) => x.id === id) || list[0]).name;
const TR_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M4 6v12M20 6v12M8 12h8M13 9l3 3-3 3"/></svg>';
let clipSeq = 1;
const newClip = (start, end, o = {}) => ({ id: clipSeq++, start, end, speed: 1, tr: "none", td: 0.5, fx: "none", filter: "none", ...o });
function resetClips() { state.clips = [newClip(0, tlDuration())]; state.clipSel = 0; state.trSel = -1; state.undo = []; state.redo = []; state.pc = 0; state.autoStop = null; }
const clipOut = (c) => (c.end - c.start) / c.speed; // dĺžka klipu vo výslednom videu
function outStarts() { const a = []; let acc = 0; for (const c of state.clips) { a.push(acc); acc += clipOut(c); } return a; }
const outDuration = () => state.clips.reduce((a, c) => a + clipOut(c), 0);
const clipAtSrc = (t) => state.clips.findIndex((c) => t >= c.start - 1e-4 && t < c.end);
const isEdited = () => { const c = state.clips; return !!c && (c.length !== 1 || c[0].start > 0.01 || c[0].end < tlDuration() - 0.01 || c[0].speed !== 1); };
const firstClipStart = () => (state.clips.length ? state.clips[0].start : 0);
function srcToOut(t) { const i = clipAtSrc(t); if (i < 0) return null; const c = state.clips[i]; return outStarts()[i] + (t - c.start) / c.speed; }
function outToSrc(o) {
  const st = outStarts(), n = state.clips.length;
  for (let i = 0; i < n; i++) { const c = state.clips[i]; if (o < st[i] + clipOut(c) || i === n - 1) return { i, t: clamp(c.start + (o - st[i]) * c.speed, c.start, c.end - 0.001) }; }
  return { i: 0, t: 0 };
}
// Kam ďalej pri prehrávaní z času t, keď hrá klip pc: null = nič, { pc } = už hrá iný klip, { pc, t } = skočiť, "end".
function cutStep(t, pc) {
  if (!isEdited()) return null;
  const n = state.clips.length, c = state.clips[pc];
  if (!c) return { pc: 0, t: state.clips[0].start + 0.001 };
  if (t >= c.start - 0.05 && t < c.end - 0.03) return null; // hrá aktuálny klip
  if (t >= c.end - 0.03 && t < c.end + 0.25) { // aktuálny klip dohral: ďalší podľa poradia, nie podľa času originálu
    const nx = pc + 1; if (nx >= n) return "end";
    if (Math.abs(state.clips[nx].start - c.end) < 0.02) return t >= c.end ? { pc: nx } : null; // nadväzuje, netreba skákať
    return { pc: nx, t: state.clips[nx].start + 0.001 };
  }
  const i = clipAtSrc(t); // prehrávanie posunuté inam: prevezmi klip, v ktorom je
  if (i >= 0) return { pc: i };
  return t < c.start ? { pc, t: c.start + 0.001 } : pc + 1 < n ? { pc: pc + 1, t: state.clips[pc + 1].start + 0.001 } : "end";
}
function trSpan(i) { // polovica prechodu (vo výslednom čase) za klipom i
  const c = state.clips[i], d = state.clips[i + 1]; if (!c || !d || c.tr === "none") return 0;
  return Math.min(c.td / 2, clipOut(c) / 2, clipOut(d) / 2);
}
function transFx(t) {
  if (!state.clips || !state.clips.length) return null;
  const i = clipAtSrc(t); if (i < 0) return null;
  const c = state.clips[i], lo = (t - c.start) / c.speed, rem = (c.end - t) / c.speed;
  let best = null;
  const d1 = trSpan(i); if (d1 > 0 && rem < d1) best = { type: c.tr, p: 1 - rem / d1, out: true };
  const d0 = i > 0 ? trSpan(i - 1) : 0;
  if (d0 > 0 && lo < d0) { const p = 1 - lo / d0; if (!best || p > best.p) best = { type: state.clips[i - 1].tr, p, out: false }; }
  if (best) best.p = clamp(best.p, 0, 1);
  return best;
}
function clipLook(t) {
  if (!state.clips || !state.clips.length) return null;
  const i = clipAtSrc(t); if (i < 0) return null;
  const c = state.clips[i]; if (c.fx === "none" && c.filter === "none") return null;
  return { fx: c.fx, filter: c.filter, lt: (t - c.start) / c.speed, lp: (t - c.start) / Math.max(0.01, c.end - c.start) };
}
function cutSegments() { // titulky pre SRT/VTT v čase výsledného videa (aj po presunutí a zrýchlení klipov)
  if (!isEdited()) return state.segments;
  const st = outStarts(), out = [];
  for (const s of state.segments) {
    let cur = null;
    state.clips.forEach((c, i) => {
      const a = Math.max(s.start, c.start), b = Math.min(s.end, c.end); if (b - a <= 0) return;
      const oa = st[i] + (a - c.start) / c.speed, ob = st[i] + (b - c.start) / c.speed;
      if (cur && Math.abs(cur.end - oa) < 0.02) cur.end = ob; else { if (cur) out.push(cur); cur = { ...s, start: oa, end: ob }; }
    });
    if (cur) out.push(cur);
  }
  return out.filter((x) => x.end - x.start > 0.25).sort((a, b) => a.start - b.start);
}

// --- história (Späť / Znova) ---
function snapshot() { state.undo.push(JSON.stringify(state.clips)); if (state.undo.length > 60) state.undo.shift(); state.redo = []; }
function restoreClips(json) {
  state.clips = JSON.parse(json); clipSeq = Math.max(clipSeq, ...state.clips.map((c) => c.id + 1));
  state.clipSel = Math.min(state.clipSel, state.clips.length - 1); if (state.trSel >= state.clips.length - 1) state.trSel = -1;
}
function undoCut() { if (!state.undo.length) return; state.redo.push(JSON.stringify(state.clips)); restoreClips(state.undo.pop()); cutHint("Vrátené späť."); cutsChanged(); }
function redoCut() { if (!state.redo.length) return; state.undo.push(JSON.stringify(state.clips)); restoreClips(state.redo.pop()); cutHint("Znova použité."); cutsChanged(); }
function cutsChanged() { invalidateResults(); renderCutUi(); }
const cutHint = (t) => { const h = $("cutHint"); if (h) h.textContent = t; };
const secTxt = (s) => decTxt(s.toFixed(1)) + " s";

// --- úpravy klipov ---
function seekOut(o) { const r = outToSrc(o); state.pc = r.i; video.currentTime = r.t; }
function splitAt(t) {
  const i = clipAtSrc(t);
  if (i < 0) { cutHint("Prehrávacia hlava nie je na žiadnom klipe."); return; }
  const c = state.clips[i];
  if (t - c.start < 0.15 * c.speed || c.end - t < 0.15 * c.speed) { cutHint("Tu sa rozdeliť nedá, je to príliš blízko okraja klipu."); return; }
  snapshot();
  state.clips.splice(i + 1, 0, newClip(t, c.end, { speed: c.speed, fx: c.fx, filter: c.filter, tr: c.tr, td: c.td }));
  c.end = t; c.tr = "none"; state.clipSel = i + 1; state.trSel = -1; state.pc = i + 1;
  cutHint("Rozdelené. Vybraný je klip za strihom: vymaž ho, presuň, alebo mu daj efekt.");
  cutsChanged();
}
function deleteClip(i) {
  const c = state.clips[i]; if (!c) return;
  if (state.clips.length < 2) { cutHint("Posledný klip vymazať nejde. Kratší ho spravíš ťahaním za biele okraje."); return; }
  const playing = clipAtSrc(video.currentTime) === i, o = outStarts()[i];
  snapshot(); state.clips.splice(i, 1);
  state.clipSel = Math.min(i, state.clips.length - 1); state.trSel = -1;
  if (playing) seekOut(Math.min(o, outDuration() - 0.01));
  cutHint("Klip je vymazaný, ostatné sa posunuli k sebe. Späť ho vrátiš tlačidlom Späť.");
  cutsChanged();
}
function moveClip(from, to) {
  if (from === to) return;
  snapshot(); const [c] = state.clips.splice(from, 1); state.clips.splice(to, 0, c);
  state.clipSel = to; state.trSel = -1; seekOut(outStarts()[to] + 0.001);
  cutHint(`Klip je presunutý na ${to + 1}. miesto.`);
  cutsChanged();
}
function selectClip(i) { state.clipSel = i; state.trSel = -1; renderCutUi(); }
function selectTransition(i) {
  state.trSel = i; state.clipSel = -1; renderCutUi();
  previewTransition(i, false);
}
function previewTransition(i, play) { // pretočí kúsok pred strih; po zmene prechodu ho hneď prehrá
  const a = state.clips[i], b = state.clips[i + 1]; if (!a || !b) return;
  const lead = Math.min(1.2, clipOut(a) * 0.6);
  video.currentTime = a.end - lead * a.speed; state.pc = i;
  if (play) { state.autoStop = { pc: i + 1, at: b.start + Math.min(1, clipOut(b) * 0.6) * b.speed }; video.play().catch(() => {}); }
}

// --- náhľady snímok do stopy ---
async function makeThumbs(job) {
  state.thumbs = null;
  const dur = tlDuration(), n = clamp(Math.ceil(dur / 1.2), 6, 90), step = dur / n;
  const v = document.createElement("video"); v.muted = true; v.playsInline = true; v.preload = "auto"; v.src = state.url;
  const ok = await new Promise((res) => { v.onloadeddata = () => res(true); v.onerror = () => res(false); setTimeout(() => res(!!v.videoWidth), 8000); });
  if (!ok || !v.videoWidth || job !== state.job) return;
  const th = 96, tw = Math.round(th * clamp(v.videoWidth / v.videoHeight, 0.45, 1.9));
  state.thumbs = { step, list: new Array(n).fill(null), tw, th };
  for (let k = 0; k < n; k++) {
    if (job !== state.job) return;
    await new Promise((res) => { const to = setTimeout(res, 2000); v.onseeked = () => { clearTimeout(to); res(); }; v.currentTime = Math.min(dur - 0.05, (k + 0.5) * step); });
    const c = document.createElement("canvas"); c.width = tw; c.height = th;
    try { c.getContext("2d").drawImage(v, 0, 0, tw, th); state.thumbs.list[k] = c; } catch {}
    if (k % 8 === 7 || k === n - 1) { if (state.tab === "edit" && !trackBusy) renderClipTrack(); }
  }
  v.removeAttribute("src"); v.load();
}
const thumbAt = (t) => { const T = state.thumbs; if (!T) return null; const k = clamp(Math.floor(t / T.step), 0, T.list.length - 1); return T.list[k] || T.list.find(Boolean) || null; };

// --- klipová stopa ---
const CT_PAD = 14;
const fmtTC = (s) => { const f = Math.floor(Math.max(0, s) * 25 + 1e-6), p = (n) => String(n).padStart(2, "0"); return `${p(Math.floor(f / 90000))}:${p(Math.floor(f / 1500) % 60)}:${p(Math.floor(f / 25) % 60)}:${p(f % 25)}`; };
const plural = (n, a, b, c) => n + " " + (n === 1 ? a : n > 1 && n < 5 ? b : c);
let trackBusy = false;
function ctPx() { const avail = ($("ctScroll").clientWidth || 600) - CT_PAD * 2; return Math.max(1, avail / Math.max(0.5, outDuration()) * state.tlZoomVal); }
function drawCtRuler(total, px) { // pravítko s časovým kódom ako v Resolve
  const cv = $("ctRuler"), dpr = window.devicePixelRatio || 1, w = Math.round(total * px + CT_PAD * 2), h = 34;
  cv.width = Math.round(w * dpr); cv.height = Math.round(h * dpr); cv.style.width = w + "px";
  const g = cv.getContext("2d"); g.setTransform(dpr, 0, 0, dpr, 0, 0); g.clearRect(0, 0, w, h);
  const step = [1, 2, 5, 10, 15, 30, 60, 120].find((x) => x * px >= 95) || 300;
  g.strokeStyle = "rgba(255,255,255,.28)"; g.fillStyle = "#b8b8bc"; g.font = "10.5px Menlo,Consolas,monospace"; g.textBaseline = "top";
  for (let k = 0; k * step / 5 <= total + 1e-6; k++) {
    const t = k * step / 5, x = Math.round(CT_PAD + t * px) + 0.5, major = k % 5 === 0;
    g.beginPath(); g.moveTo(x, major ? 4 : 22); g.lineTo(x, h); g.stroke();
    if (major) g.fillText(fmtTC(t), x + 4, 6);
  }
}
function drawStrip(cv, c, w) {
  const dpr = window.devicePixelRatio || 1, h = 50;
  cv.width = Math.max(1, Math.round(w * dpr)); cv.height = Math.round(h * dpr);
  const g = cv.getContext("2d"); g.fillStyle = "#22123f"; g.fillRect(0, 0, cv.width, cv.height);
  const T = state.thumbs; if (!T) return;
  const tw = h * dpr * T.tw / T.th;
  for (let x = 0; x < cv.width; x += tw) {
    const t = c.start + ((x + tw / 2) / dpr) / state.ctPx * c.speed, im = thumbAt(Math.min(t, c.end - 0.01));
    if (im) g.drawImage(im, x, 0, tw, cv.height);
  }
}
function drawClipAudio(cv, c, w) { // vlna zvuku klipu (v jeho rýchlosti)
  const dpr = window.devicePixelRatio || 1, h = 34;
  cv.width = Math.max(1, Math.round(w * dpr)); cv.height = Math.round(h * dpr);
  const g = cv.getContext("2d"); g.setTransform(dpr, 0, 0, dpr, 0, 0); g.clearRect(0, 0, w, h);
  const peaks = state.waveform, dur = tlDuration(); if (!peaks || !peaks.length) return;
  g.fillStyle = "rgba(140,240,255,.85)";
  for (let x = 0; x < w; x++) {
    const t = c.start + (x / state.ctPx) * c.speed, v = peaks[Math.floor(t / dur * peaks.length)] || 0, bh = Math.max(1, v * h * 0.95);
    g.fillRect(x, (h - bh) / 2, 1, bh);
  }
}
function renderClipTrack() {
  const inner = $("ctInner"); if (!inner || !state.clips || !state.clips.length || !$("ctScroll").clientWidth) return;
  const px = state.ctPx = ctPx(), total = outDuration(), st = outStarts(), n = state.clips.length;
  inner.style.width = Math.round(total * px + CT_PAD * 2) + "px";
  drawCtRuler(total, px);
  $("ctVN").textContent = plural(n, "klip", "klipy", "klipov"); $("ctAN").textContent = plural(n, "klip", "klipy", "klipov");
  const box = $("ctClips"), abox = $("ctAClips"); box.textContent = ""; abox.textContent = "";
  state.clips.forEach((c, i) => {
    const sel = i === state.clipSel, w = Math.max(6, clipOut(c) * px), left = (CT_PAD + st[i] * px) + "px";
    const el = document.createElement("div"); el.className = "clip" + (sel ? " sel" : ""); el.dataset.i = i;
    el.setAttribute("role", "button"); el.setAttribute("aria-label", `Klip ${i + 1}, ${secTxt(clipOut(c))}`); el.tabIndex = 0;
    el.style.left = left; el.style.width = w + "px";
    const cv = document.createElement("canvas"); drawStrip(cv, c, w); el.append(cv);
    const tags = document.createElement("div"); tags.className = "cl-tags";
    const tag = (txt, cls = "") => { const s = document.createElement("span"); s.className = "cl-tag " + cls; s.textContent = txt; tags.append(s); };
    if (c.speed !== 1) tag(decTxt(c.speed) + "×", "sp");
    if (c.fx !== "none") tag(nameOf(EFFECTS, c.fx)); if (c.filter !== "none") tag(nameOf(FILTERS, c.filter));
    const nm = document.createElement("div"); nm.className = "cl-name";
    const a = document.createElement("span"); a.textContent = "Klip " + (i + 1); const d = document.createElement("span"); d.className = "cl-dur"; d.textContent = secTxt(clipOut(c));
    nm.append(a, d); el.append(tags, nm);
    if (sel) { const l = document.createElement("div"); l.className = "trim l"; l.title = "Ťahaním orežeš začiatok"; const r = document.createElement("div"); r.className = "trim r"; r.title = "Ťahaním orežeš koniec"; el.append(l, r); wireTrim(l, i, "l"); wireTrim(r, i, "r"); }
    el.onkeydown = (e) => { if (e.key === "Enter") { e.preventDefault(); selectClip(i); } };
    wireClip(el, i); box.append(el);
    const ae = document.createElement("div"); ae.className = "aclip" + (sel ? " sel" : ""); ae.dataset.i = i; ae.style.left = left; ae.style.width = w + "px";
    const acv = document.createElement("canvas"); drawClipAudio(acv, c, w); const al = document.createElement("span"); al.textContent = "Klip " + (i + 1);
    ae.append(acv, al); wireClip(ae, i); abox.append(ae);
  });
  for (let i = 0; i < n - 1; i++) {
    if (state.clipSel === i || state.clipSel === i + 1) continue; // pri vybranom klipe sú na okrajoch úchyty orezania
    const b = document.createElement("button"); b.type = "button"; b.className = "ctr" + (state.clips[i].tr !== "none" ? " on" : "") + (state.trSel === i ? " sel" : "");
    b.style.left = (CT_PAD + st[i + 1] * px) + "px"; b.innerHTML = TR_ICON;
    b.title = state.clips[i].tr !== "none" ? "Prechod: " + nameOf(TRANSITIONS, state.clips[i].tr) : "Pridať prechod"; b.setAttribute("aria-label", b.title);
    b.onpointerdown = (e) => e.stopPropagation(); b.onclick = (e) => { e.stopPropagation(); selectTransition(i); };
    box.append(b);
  }
  syncClipPlayhead(true);
}
function layoutTrack() { // len prepočíta polohy (pri orezávaní), bez nového vykreslenia stopy
  const px = state.ctPx, st = outStarts(), total = outDuration();
  document.querySelectorAll("#ctClips .clip, #ctAClips .aclip").forEach((el) => { const i = +el.dataset.i, c = state.clips[i]; el.style.left = (CT_PAD + st[i] * px) + "px"; el.style.width = Math.max(6, clipOut(c) * px) + "px"; const d = el.querySelector(".cl-dur"); if (d) d.textContent = secTxt(clipOut(c)); });
  document.querySelectorAll("#ctClips .ctr").forEach((b, k) => { b.style.left = (CT_PAD + st[k + 1] * px) + "px"; });
  $("ctInner").style.width = Math.round(total * px + CT_PAD * 2) + "px";
}
function syncClipPlayhead(force) {
  const ph = $("ctPlayhead"); if (!ph || !state.clips || !state.clips.length) return;
  let o = srcToOut(video.currentTime);
  if (o == null) { const c = state.clips[state.pc]; o = c ? outStarts()[state.pc] + (video.currentTime < c.start ? 0 : clipOut(c)) : 0; }
  const x = CT_PAD + o * state.ctPx; ph.style.left = Math.round(x) + "px";
  $("ctTC").textContent = fmtTC(o);
  $("ctPlay").innerHTML = video.paused ? '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5l12 7-12 7z"/></svg>' : '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>';
  const sc = $("ctScroll");
  if (sc && !trackBusy && (!video.paused || force === "follow") && (x < sc.scrollLeft + 30 || x > sc.scrollLeft + sc.clientWidth - 30)) sc.scrollLeft = Math.max(0, x - sc.clientWidth * 0.3);
}
function wireClip(el, i) {
  let start = null;
  el.addEventListener("pointerdown", (e) => {
    if (e.target.classList.contains("trim") || e.button > 0) return;
    start = { x: e.clientX, left: parseFloat(el.style.left), w: parseFloat(el.style.width), drag: false };
    el.setPointerCapture(e.pointerId); e.preventDefault();
  });
  el.addEventListener("pointermove", (e) => {
    if (!start) return;
    const dx = e.clientX - start.x;
    if (!start.drag && Math.abs(dx) > 7 && state.clips.length > 1) { start.drag = true; el.classList.add("dragging"); trackBusy = true; video.pause(); state.autoStop = null; }
    if (!start.drag) return;
    el.style.transform = `translateX(${dx}px)`;
    const mid = start.left + start.w / 2 + dx; let to = 0;
    const row = el.parentElement.querySelectorAll(el.classList.contains("aclip") ? ".aclip" : ".clip");
    row.forEach((o) => { if (o !== el && parseFloat(o.style.left) + parseFloat(o.style.width) / 2 < mid) to++; });
    start.to = to;
    let ins = $("ctIns"); if (!ins) { ins = document.createElement("div"); ins.id = "ctIns"; ins.className = "ct-ins"; $("ctInner").append(ins); }
    const others = [...row].filter((o) => o !== el);
    ins.style.left = (to < others.length ? parseFloat(others[to].style.left) : parseFloat(others[others.length - 1].style.left) + parseFloat(others[others.length - 1].style.width)) + "px";
  });
  const end = (e) => {
    if (!start) return;
    const s = start; start = null; trackBusy = false;
    const ins = $("ctIns"); if (ins) ins.remove();
    if (s.drag) { el.classList.remove("dragging"); el.style.transform = ""; if (s.to != null && s.to !== i) moveClip(i, s.to); else renderClipTrack(); return; }
    if (e.type === "pointercancel") return;
    const o = (s.left - CT_PAD + (e.clientX - el.getBoundingClientRect().left)) / state.ctPx;
    seekOut(clamp(o, outStarts()[i], outStarts()[i] + clipOut(state.clips[i]) - 0.01));
    if (state.clipSel !== i || state.trSel >= 0) selectClip(i);
  };
  el.addEventListener("pointerup", end); el.addEventListener("pointercancel", end);
}
function wireTrim(h, i, side) {
  let s = null;
  h.addEventListener("pointerdown", (e) => {
    e.stopPropagation(); e.preventDefault(); h.setPointerCapture(e.pointerId);
    const c = state.clips[i], others = state.clips.filter((x, k) => k !== i);
    s = { x: e.clientX, start: c.start, end: c.end, before: JSON.stringify(state.clips),
      lo: Math.max(0, ...others.filter((x) => x.end <= c.start + 1e-6).map((x) => x.end)),
      hi: Math.min(tlDuration(), ...others.filter((x) => x.start >= c.end - 1e-6).map((x) => x.start)) };
    trackBusy = true; video.pause();
  });
  h.addEventListener("pointermove", (e) => {
    if (!s) return;
    const c = state.clips[i], dt = (e.clientX - s.x) / state.ctPx * c.speed, min = 0.2 * c.speed;
    if (side === "l") c.start = clamp(s.start + dt, s.lo, c.end - min); else c.end = clamp(s.end + dt, c.start + min, s.hi);
    layoutTrack(); drawStrip(h.parentElement.querySelector("canvas"), c, parseFloat(h.parentElement.style.width));
    video.currentTime = side === "l" ? c.start + 0.001 : c.end - 0.04; state.pc = i;
  });
  const end = () => {
    if (!s) return; const before = s.before; s = null; trackBusy = false;
    if (before !== JSON.stringify(state.clips)) { state.undo.push(before); state.redo = []; cutHint("Klip je orezaný."); }
    cutsChanged();
  };
  h.addEventListener("pointerup", end); h.addEventListener("pointercancel", end);
}

// --- panel strihu ---
function renderCutUi() {
  if (!state.clips || !state.clips.length || !$("pane-cut")) return;
  if (!trackBusy) renderClipTrack();
  const n = state.clips.length, c = state.clipSel >= 0 ? state.clips[state.clipSel] : null, tr = state.trSel >= 0 ? state.clips[state.trSel] : null;
  $("clipPanel").hidden = !c || !!tr; $("trPanel").hidden = !tr; $("cutEmpty").hidden = !!(c || tr);
  $("cutDel").disabled = !c || n < 2; $("cutUndo").disabled = !state.undo.length; $("cutRedo").disabled = !state.redo.length;
  $("cutLen").textContent = fmtTime(outDuration()) + (isEdited() ? ` z ${fmtTime(tlDuration())}` : "");
  $("cutReset").hidden = !isEdited() && !state.clips.some((x) => x.fx !== "none" || x.filter !== "none" || x.tr !== "none");
  if (!$("cutHint").textContent) cutHint("Klik na klip ho vyberie, ťahaním ho presunieš. Oranžové okraje vybraného klipu ho orežú. Štvorček medzi klipmi pridá prechod. S = rozdeliť, Del = vymazať.");
  if (c && !tr) {
    $("clipLabel").textContent = `Klip ${state.clipSel + 1} z ${n}`; $("clipLen").textContent = secTxt(clipOut(c));
    document.querySelectorAll("#speedOpts button").forEach((b) => { const on = +b.dataset.v === c.speed; b.classList.toggle("on", on); b.setAttribute("aria-checked", String(on)); });
    document.querySelectorAll("#fxGrid .trt").forEach((b) => { const on = b.dataset.id === c.fx; b.classList.toggle("on", on); b.setAttribute("aria-checked", String(on)); });
    document.querySelectorAll("#filterGrid .trt").forEach((b) => { const on = b.dataset.id === c.filter; b.classList.toggle("on", on); b.setAttribute("aria-checked", String(on)); });
    const im = thumbAt((c.start + c.end) / 2); // ukážky filtrov a efektov na zábere z vybraného klipu
    document.querySelectorAll("#filterGrid .fv canvas, #fxGrid .fv canvas").forEach((cv) => { const g = cv.getContext("2d"); g.fillStyle = "#1a2140"; g.fillRect(0, 0, cv.width, cv.height); if (im) { const s = Math.max(cv.width / im.width, cv.height / im.height); g.drawImage(im, (cv.width - im.width * s) / 2, (cv.height - im.height * s) / 2, im.width * s, im.height * s); } cv.nextElementSibling.textContent = im ? "" : "A"; });
  }
  if (tr) {
    $("trLabel").textContent = `Prechod: klip ${state.trSel + 1} → ${state.trSel + 2}`;
    document.querySelectorAll("#trGrid .trt").forEach((b) => { const on = b.dataset.id === tr.tr; b.classList.toggle("on", on); b.setAttribute("aria-checked", String(on)); });
    $("trDur").value = tr.td; $("trDur").disabled = tr.tr === "none"; $("trDurOut").textContent = secTxt(tr.td);
  }
}
function setupCut() {
  const tile = (box, item, preview) => {
    const b = document.createElement("button"); b.type = "button"; b.className = "trt"; b.dataset.id = item.id; b.setAttribute("role", "radio");
    b.innerHTML = preview; const nm = document.createElement("span"); nm.textContent = item.name; b.append(nm); box.append(b); return b;
  };
  TRANSITIONS.forEach((x) => tile($("trGrid"), x, `<span class="tv ${x.id}" aria-hidden="true"><i class="a">A</i><i class="b">B</i><i class="w"></i></span>`).onclick = () => {
    const a = state.clips[state.trSel]; if (!a) return; snapshot(); a.tr = x.id; cutsChanged(); if (x.id !== "none") previewTransition(state.trSel, true);
  });
  EFFECTS.forEach((x) => tile($("fxGrid"), x, `<span class="fv ${x.id}" aria-hidden="true"><canvas width="96" height="60"></canvas><i>A</i></span>`).onclick = () => {
    const c = state.clips[state.clipSel]; if (!c) return; snapshot(); c.fx = x.id; cutsChanged();
    seekOut(outStarts()[state.clipSel] + 0.001); if (x.id !== "none") { state.autoStop = { pc: state.clipSel, at: c.start + Math.min(2.5, clipOut(c)) * c.speed }; video.play().catch(() => {}); }
  });
  FILTERS.forEach((x) => tile($("filterGrid"), x, `<span class="fv ${x.id}" aria-hidden="true"><canvas class="fl-${x.id}" width="96" height="60"></canvas><i>A</i></span>`).onclick = () => {
    const c = state.clips[state.clipSel]; if (!c) return; snapshot(); c.filter = x.id; cutsChanged(); if (video.paused) seekOut(clamp(srcToOut(video.currentTime) ?? 0, outStarts()[state.clipSel], outStarts()[state.clipSel] + clipOut(c) - 0.01));
  });
  SPEEDS.forEach((v) => { const b = document.createElement("button"); b.type = "button"; b.dataset.v = v; b.setAttribute("role", "radio"); b.textContent = decTxt(v) + "×";
    b.onclick = () => { const c = state.clips[state.clipSel]; if (!c || c.speed === v) return; snapshot(); c.speed = v; cutHint(`Klip ide rýchlosťou ${b.textContent}, trvá ${secTxt(clipOut(c))}.`); cutsChanged(); };
    $("speedOpts").append(b); });
  $("fxAll").onclick = () => { const c = state.clips[state.clipSel]; if (!c) return; snapshot(); state.clips.forEach((x) => { x.fx = c.fx; }); cutHint(`Efekt ${nameOf(EFFECTS, c.fx)} je na všetkých klipoch.`); cutsChanged(); };
  $("filterAll").onclick = () => { const c = state.clips[state.clipSel]; if (!c) return; snapshot(); state.clips.forEach((x) => { x.filter = c.filter; }); cutHint(`Filter ${nameOf(FILTERS, c.filter)} je na všetkých klipoch.`); cutsChanged(); };
  $("trAll").onclick = () => { const a = state.clips[state.trSel]; if (!a) return; snapshot(); state.clips.forEach((x, k) => { if (k < state.clips.length - 1) { x.tr = a.tr; x.td = a.td; } }); cutHint(`Prechod ${nameOf(TRANSITIONS, a.tr)} je medzi všetkými klipmi.`); cutsChanged(); };
  let trBefore = null;
  $("trDur").addEventListener("pointerdown", () => { trBefore = JSON.stringify(state.clips); });
  $("trDur").oninput = (e) => { const a = state.clips[state.trSel]; if (!a) return; a.td = +e.target.value; $("trDurOut").textContent = secTxt(a.td); invalidateResults(); };
  $("trDur").onchange = () => { if (trBefore && trBefore !== JSON.stringify(state.clips)) { state.undo.push(trBefore); state.redo = []; } trBefore = null; renderCutUi(); previewTransition(state.trSel, true); };
  $("cutSplit").onclick = () => splitAt(video.currentTime);
  $("ctPlay").onclick = () => { if (video.paused) video.play().catch(() => {}); else video.pause(); };
  $("ctSpeed").onchange = (e) => { state.pvRate = +e.target.value; $("tlSpeed").value = e.target.value; };
  const zoomTo = (z) => { state.tlZoomVal = clamp(z, 1, 8); $("ctZoom").value = state.tlZoomVal; $("tlZoom").value = Math.min(6, state.tlZoomVal); renderTimeline(); };
  $("ctZoom").oninput = (e) => zoomTo(+e.target.value);
  $("ctZoomIn").onclick = () => zoomTo(state.tlZoomVal + 0.5); $("ctZoomOut").onclick = () => zoomTo(state.tlZoomVal - 0.5);
  $("cutDel").onclick = () => deleteClip(state.clipSel);
  $("cutUndo").onclick = undoCut; $("cutRedo").onclick = redoCut;
  $("cutReset").onclick = () => { snapshot(); state.clips = [newClip(0, tlDuration())]; state.clipSel = 0; state.trSel = -1; state.pc = 0; cutHint("Video je opäť celé a bez efektov. Späť to vrátiš tlačidlom Späť."); cutsChanged(); };
  // klik na pravítko alebo prázdne miesto v stope = posun prehrávania (ťahaním sa dá „scrubovať“)
  let scrub = false;
  const oAt = (e) => clamp((e.clientX - $("ctInner").getBoundingClientRect().left - CT_PAD) / state.ctPx, 0, Math.max(0, outDuration() - 0.01));
  $("ctInner").addEventListener("pointerdown", (e) => {
    if (e.target.closest(".clip") || e.target.closest(".ctr")) return;
    scrub = true; seekOut(oAt(e)); $("ctInner").setPointerCapture(e.pointerId);
    if (e.target !== $("ctRuler") && (state.clipSel >= 0 || state.trSel >= 0)) { state.clipSel = -1; state.trSel = -1; renderCutUi(); }
  });
  $("ctInner").addEventListener("pointermove", (e) => { if (scrub) seekOut(oAt(e)); });
  $("ctInner").addEventListener("pointerup", () => { scrub = false; });
  new ResizeObserver(() => { if (state.tab === "edit" && !trackBusy) renderClipTrack(); }).observe($("ctScroll"));
}

// --- kreslenie efektov, filtrov a prechodov ---
const CANVAS_FILTER = (() => { try { const g = document.createElement("canvas").getContext("2d"); g.filter = "blur(2px)"; return g.filter === "blur(2px)"; } catch { return false; } })();
const FILTER_CSS = { vivid: "saturate(1.5) contrast(1.1)", warm: "sepia(.22) saturate(1.3) brightness(1.03)", cool: "saturate(1.05) brightness(1.03)",
  bw: "grayscale(1) contrast(1.15)", vintage: "sepia(.5) contrast(.92) brightness(1.05) saturate(.85)", film: "contrast(1.12) saturate(.85)" };
function rng(seed) { let a = seed >>> 0; return () => { a = (a + 0x6D2B79F5) >>> 0; let t = a; t = Math.imul(t ^ (t >>> 15), t | 1); t ^= t + Math.imul(t ^ (t >>> 7), t | 61); return ((t ^ (t >>> 14)) >>> 0) / 4294967296; }; }
function pix(ctx, fn) { ctx.save(); ctx.setTransform(1, 0, 0, 1, 0, 0); if (CANVAS_FILTER) ctx.filter = "none"; fn(ctx.canvas.width, ctx.canvas.height); ctx.restore(); }
const scaleAt = (ctx, W, H, k) => { ctx.translate(W / 2, H / 2); ctx.scale(k, k); ctx.translate(-W / 2, -H / 2); };
function drawVideoLayer(ctx, vid, W, H, r, fx, look, t) {
  ctx.save();
  let blur = 0;
  if (look) {
    if (look.fx === "kenburns") scaleAt(ctx, W, H, 1 + 0.16 * clamp(look.lp, 0, 1));
    if (look.fx === "pulse") { const ph = (look.lt * 2) % 1; scaleAt(ctx, W, H, 1 + 0.08 * Math.pow(1 - ph, 3)); }
    if (look.fx === "shake") { const s = Math.min(W, H) * 0.014, a = look.lt * 34; ctx.translate(W / 2 + Math.sin(a * 1.3) * s + Math.sin(a * 2.9) * s * 0.5, H / 2 + Math.cos(a * 1.7) * s); ctx.rotate(Math.sin(a * 0.9) * 0.012); ctx.scale(1.06, 1.06); ctx.translate(-W / 2, -H / 2); }
  }
  let whip = 0;
  if (fx && fx.type !== "none") {
    const e = fx.p * fx.p, m = Math.min(W, H);
    if (fx.type === "blur") blur = e * m * 0.04;
    if (fx.type === "zoom") scaleAt(ctx, W, H, 1 + 0.7 * e);
    if (fx.type === "whip") { whip = (fx.out ? -1 : 1) * e * W * 0.6; ctx.translate(whip, 0); blur = e * W * 0.02; }
    if (fx.type === "spin") { ctx.translate(W / 2, H / 2); ctx.rotate((fx.out ? 1 : -1) * e * Math.PI / 2); ctx.scale(1 + 0.6 * e, 1 + 0.6 * e); ctx.translate(-W / 2, -H / 2); blur = e * m * 0.015; }
  }
  if (CANVAS_FILTER) { const f = [look && look.filter !== "none" ? FILTER_CSS[look.filter] : "", blur > 0.3 ? `blur(${blur.toFixed(1)}px)` : ""].join(" ").trim(); ctx.filter = f || "none"; }
  ctx.drawImage(vid, r.dx, r.dy, r.iw, r.ih);
  if (whip) ctx.drawImage(vid, r.dx - Math.sign(whip) * W * 0.6 * 1.0, r.dy, r.iw, r.ih); // kópia, aby za pohybom nebola čierna
  ctx.restore();
  if (look && look.filter !== "none") drawFilterTint(ctx, look.filter);
  if (look && look.fx === "glitch" && (look.lt % 1.3) < 0.28) glitchFx(ctx, look.lt, 1);
  if (look && look.fx === "vhs") vhsFx(ctx, look.lt);
  if (fx && fx.type === "glitch") glitchFx(ctx, t, 0.4 + fx.p * 1.4);
}
function drawFilterTint(ctx, f) { // farebný nádych filtra (funguje aj tam, kde plátno nevie CSS filtre)
  pix(ctx, (w, h) => {
    const fill = (op, style, a = 1) => { ctx.globalCompositeOperation = op; ctx.globalAlpha = a; ctx.fillStyle = style; ctx.fillRect(0, 0, w, h); };
    if (!CANVAS_FILTER) { if (f === "bw") fill("saturation", "#808080"); if (f === "vivid") { ctx.globalCompositeOperation = "overlay"; ctx.globalAlpha = 0.35; ctx.drawImage(ctx.canvas, 0, 0); } if (f === "vintage") fill("saturation", "#808080", 0.45); }
    if (f === "warm") fill("soft-light", "rgb(255,150,60)", 0.32);
    if (f === "cool") fill("soft-light", "rgb(50,130,255)", 0.4);
    if (f === "vintage") { fill("soft-light", "rgb(255,200,140)", 0.35); fill("screen", "rgb(40,25,10)", 0.25); }
    if (f === "film") { fill("soft-light", "rgb(0,150,160)", 0.28); ctx.globalCompositeOperation = "source-over"; ctx.globalAlpha = 1; ctx.fillStyle = "#000"; const b = Math.round(h * (w > h ? 0.09 : 0.06)); ctx.fillRect(0, 0, w, b); ctx.fillRect(0, h - b, w, b); }
    if (f === "vintage" || f === "film") { ctx.globalCompositeOperation = "source-over"; ctx.globalAlpha = 1; const g = ctx.createRadialGradient(w / 2, h / 2, Math.min(w, h) * 0.35, w / 2, h / 2, Math.max(w, h) * 0.75); g.addColorStop(0, "rgba(0,0,0,0)"); g.addColorStop(1, "rgba(0,0,0,.45)"); ctx.fillStyle = g; ctx.fillRect(0, 0, w, h); }
  });
}
function glitchFx(ctx, time, strength) { // posunuté pásy obrazu + farebné záblesky
  pix(ctx, (w, h) => {
    const r = rng(Math.floor(time * 14) + 7), n = 4 + Math.floor(r() * 6);
    for (let k = 0; k < n; k++) {
      const y = Math.floor(r() * h), bh = Math.max(2, Math.floor(h * (0.015 + r() * 0.07))), dx = Math.round((r() - 0.5) * w * 0.14 * strength);
      ctx.drawImage(ctx.canvas, 0, y, w, bh, dx, y, w, bh);
      if (r() < 0.5) { ctx.globalCompositeOperation = "screen"; ctx.globalAlpha = 0.35; ctx.fillStyle = r() < 0.5 ? "#ff0050" : "#00e5ff"; ctx.fillRect(0, y, w, bh); ctx.globalCompositeOperation = "source-over"; ctx.globalAlpha = 1; }
    }
    ctx.globalCompositeOperation = "lighten"; ctx.globalAlpha = 0.3 * Math.min(1, strength); ctx.drawImage(ctx.canvas, Math.round(w * 0.008 * strength), 0);
  });
}
function vhsFx(ctx, time) { // retro VHS: vyblednutie, presvit farieb, riadky, šum a skákajúci pás
  pix(ctx, (w, h) => {
    ctx.globalCompositeOperation = "saturation"; ctx.globalAlpha = 0.35; ctx.fillStyle = "#808080"; ctx.fillRect(0, 0, w, h);
    ctx.globalCompositeOperation = "lighten"; ctx.globalAlpha = 0.28; ctx.drawImage(ctx.canvas, Math.max(2, Math.round(w * 0.004)), 0);
    ctx.globalCompositeOperation = "source-over"; ctx.globalAlpha = 1;
    const lh = Math.max(2, Math.round(h / 360)); ctx.fillStyle = "rgba(0,0,0,.16)";
    for (let y = 0; y < h; y += lh * 2) ctx.fillRect(0, y, w, lh);
    const by = ((time * 0.22) % 1.2 - 0.1) * h, bh = h * 0.05;
    ctx.drawImage(ctx.canvas, 0, by, w, bh, Math.round(w * 0.01), by, w, bh);
    ctx.fillStyle = "rgba(255,255,255,.06)"; ctx.fillRect(0, by, w, bh);
    const r = rng(Math.floor(time * 24)); ctx.fillStyle = "rgba(255,255,255,.35)";
    for (let k = 0; k < 40; k++) ctx.fillRect(r() * w, r() * h, Math.max(1, w * 0.004 * r()), Math.max(1, h / 540));
    ctx.font = `600 ${Math.round(Math.min(w, h) * 0.045)}px monospace`; ctx.fillStyle = "rgba(255,255,255,.85)"; ctx.textBaseline = "top";
    if (Math.floor(time * 1.5) % 2 === 0) ctx.fillText("▶ PLAY", Math.round(w * 0.05), Math.round(h * 0.05));
  });
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
  if (vw && vh && vid.readyState >= 2) { const r = cropRect(vw, vh, W, H, crop); drawVideoLayer(ctx, vid, W, H, r, fx, clipLook(t), t); }
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
      if (!video.paused && !video.seeking) { // prehrávanie ide po klipoch v ich poradí, vymazané preskočí
        const step = cutStep(t, state.pc);
        if (step === "end") { video.pause(); state.pc = 0; video.currentTime = firstClipStart() + 0.001; }
        else if (step) { state.pc = step.pc; if (step.t != null) video.currentTime = step.t; }
        if (state.autoStop && state.pc === state.autoStop.pc && t >= state.autoStop.at) { video.pause(); state.autoStop = null; }
      } else if (video.paused) state.autoStop = null;
      const pcc = state.clips[state.pc], rate = (pcc ? pcc.speed : 1) * state.pvRate;
      if (Math.abs(video.playbackRate - rate) > 1e-3) video.playbackRate = rate;
      renderFrame(ctx, video, t, w, h, (sp, seg) => styleFor(o.k, sp, seg), state.crop[o.k]);
      if (o.drag && o.drag.active && o.guides && (o.guides.x || o.guides.y)) { // vodiace čiary magnetu
        ctx.save(); ctx.strokeStyle = "#ff3ea5"; ctx.lineWidth = 1.5; ctx.setLineDash([6, 4]); ctx.beginPath();
        if (o.guides.x) { ctx.moveTo(w / 2, 0); ctx.lineTo(w / 2, h); }
        if (o.guides.y) { ctx.moveTo(0, h / 2); ctx.lineTo(w, h / 2); }
        ctx.stroke(); ctx.restore();
      }
      video.volume = fxGain(transFx(t));
      if (isEdited() && clipAtSrc(t) < 0) { // zastavené vo vystrihnutej časti: jasne to ukáž
        ctx.fillStyle = "rgba(6,9,22,.72)"; ctx.fillRect(0, 0, w, h);
        ctx.fillStyle = "#ff9aab"; ctx.font = `600 ${Math.round(Math.min(w, h) * 0.06)}px Inter,sans-serif`; ctx.textAlign = "center"; ctx.textBaseline = "middle";
        ctx.fillText(tr("Vystrihnuté"), w / 2, h / 2); ctx.textAlign = "left";
      }
      o.time.textContent = label;
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
  page = p; closeSpkMenu(); if (p !== "work" && state.sel && state.sel.size) clearSel();
  if (p !== "work") video.pause();
  $("content").classList.toggle("alt-page", p !== "work");
  $("content").classList.toggle("ws", p === "work" && curView === "editor");
  $("page-brand").hidden = p !== "brand"; $("page-library").hidden = p !== "library";
  document.querySelectorAll(".nav[data-page]").forEach((a) => { const on = a.dataset.page === p; a.classList.toggle("on", on); if (on) a.setAttribute("aria-current", "page"); else a.removeAttribute("aria-current"); });
  if (p === "brand") { updateBrandUpload(); renderBrand(); }
  if (p === "library") { renderLibrary(); requestAnimationFrame(libLoop); }
  scrollTo(0, 0);
}
// Značka: len fonty. Každý font má živú ukážku textu, vlastné sa dajú pridať a odobrať.
function renderBrand() {
  const grid = $("fontGrid"); grid.textContent = "";
  const text = $("fontSampleText").value.trim() || tr("Ahoj, toto sú moje titulky"), cur = activeStyle().font;
  [...sharedFonts, ...customFonts, ...FONTS].forEach((name) => {
    const own = customFonts.has(name), shared = sharedFonts.has(name); ensureFont(name);
    const c = document.createElement("div"); c.className = "fcard" + (name === cur ? " on" : "");
    const smp = document.createElement("div"); smp.className = "fsample"; smp.textContent = text; smp.style.fontFamily = `"${name}", ${SCRIPT_FALLBACK}`; smp.style.fontWeight = name === "Bebas Neue" ? 400 : 700;
    const meta = document.createElement("div"); meta.className = "fmeta";
    const nm = document.createElement("b"); nm.textContent = name; nm.title = name;
    const tag = document.createElement("span"); tag.className = "ftag" + (own ? " own" : shared ? " shared" : ""); tag.textContent = own ? "Vlastný" : shared ? "Spoločný" : "Vstavaný";
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
const libSegs = () => {
  const mk = (text, start, end) => { const ws = text.split(" "), d = (end - start) / ws.length; return { text, start, end, speaker: 1, words: ws.map((w, i) => ({ w, start: start + i * d, end: start + (i + 1) * d })) }; };
  return [mk(tr("Takto budú vyzerať"), 0.3, 1.9), mk(tr("tvoje titulky vo videu"), 2.05, 3.9)];
};
let LIB_SEGS = libSegs();
let LIB_CHUNKS = null;
let libCards = [];
// Všetky ukážky v Knižnici majú titulok na jednej úrovni: v strede, alebo v spodnej tretine (predvolené).
const libY = () => { try { return +(localStorage.getItem("titulky-libY") || 0.67); } catch { return 0.67; } };
function renderLibrary() {
  document.querySelectorAll(".libpos button").forEach((b) => { const on = Math.abs(+b.dataset.y - libY()) < 0.01; b.classList.toggle("on", on); b.setAttribute("aria-checked", String(on)); });
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
    libCards.push({ cv, ctx: cv.getContext("2d"), st: { ...p, preset: p.id, on: true, x: 0.5, y: libY(), position: null, roam: false, highlight: p.highlight ?? true, anim: p.anim || "none" } });
  });
  const ag = $("libAnimGrid"); ag.textContent = ""; // animácie v aktuálnom štýle (alebo Modern, ak je vybraný Dynamický)
  const base = activeStyle().dynamic ? { ...PRESETS[0], preset: PRESETS[0].id } : { ...activeStyle() }, curAnim = activeStyle().anim || "none";
  ANIMS.filter((x) => x.id !== "none").forEach((x) => {
    const c = document.createElement("div"); c.className = "lcard" + (x.id === curAnim ? " on" : "");
    const cv = document.createElement("canvas"); cv.width = 400; cv.height = 500; cv.setAttribute("role", "img"); cv.setAttribute("aria-label", "Animácia " + x.name);
    const foot = document.createElement("div"); foot.className = "lfoot";
    const nm = document.createElement("b"); nm.textContent = x.name;
    const use = document.createElement("button"); use.type = "button"; use.className = "fuse"; use.textContent = x.id === curAnim ? "Použitý" : "Použiť";
    use.onclick = () => { if (activeStyle().dynamic) applyPreset(PRESETS[0]); activeStyle().anim = x.id; syncControls(); setPage("work"); };
    foot.append(nm, use); c.append(cv, foot); ag.append(c);
    libCards.push({ cv, ctx: cv.getContext("2d"), st: { ...base, on: true, x: 0.5, y: libY(), position: null, roam: false, anim: x.id, size: Math.max(base.size, 7) } });
  });
}
function libBackdrop(ctx, W, H, t) { // pohyblivé farebné pozadie namiesto videa
  const g = ctx.createLinearGradient(0, 0, W, H), a = (t / LIB_LOOP) * Math.PI * 2;
  g.addColorStop(0, `hsl(${250 + 20 * Math.sin(a)},50%,22%)`); g.addColorStop(1, `hsl(${205 + 25 * Math.cos(a)},55%,14%)`); // fialovo-modrá ako v prvej verzii
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

// ---------- preklad titulkov (model NLLB-200 v prehliadači) ----------
// Prekladá text titulkov medzi jazykmi, ktoré ponúka aplikácia. Časy ostávajú, rozloženie slov sa prepočíta.
const NLLB = { sk: "slk_Latn", cs: "ces_Latn", en: "eng_Latn", es: "spa_Latn", ar: "arb_Arab", tr: "tur_Latn", hi: "hin_Deva", pl: "pol_Latn", zh: "zho_Hans" };
const TR_NAMES = { sk: "Slovenčina", cs: "Čeština", en: "Angličtina", es: "Španielčina", ar: "Arabčina", tr: "Turečtina", hi: "Hindčina", pl: "Poľština", zh: "Čínština" };
const TR_MOCK = new URLSearchParams(location.search).has("mock"); // len na skúšanie: preklad bez modelu
let translator = null, trOriginal = null, trBusy = false;
function trProgress(p, label) { $("trProg").hidden = false; $("trProg").querySelector("i").style.width = Math.round(p) + "%"; $("trProg").querySelector("span").textContent = label; }
async function runTranslate() {
  if (trBusy || !state.segments.length) return;
  const from = $("trFrom").value, to = $("trTo").value;
  if (from === to) { $("trMsg").textContent = "Vyber iný cieľový jazyk."; return; }
  trBusy = true; $("trGo").disabled = true; $("trMsg").textContent = "";
  const texts = state.segments.map((s) => s.text.replace(/\s*\n\s*/g, " ").trim());
  try {
    let out;
    if (TR_MOCK) { out = texts.map((t) => `[${to}] ${t}`); trProgress(100, "Prekladám… 100 %"); }
    else {
      if (!translator) {
        trProgress(0, "Načítavam prekladový model…");
        translator = await loadModel({ id: "Xenova/nllb-200-distilled-600M", label: "NLLB-200", task: "translation", opts: { dtype: "q8" },
          onProgress: (l, t) => trProgress(l / t * 100, `Sťahujem prekladový model… ${Math.round(l / t * 100)} %`) }, true);
      }
      out = [];
      for (let i = 0; i < texts.length; i += 6) {
        trProgress(i / texts.length * 100, `Prekladám… ${Math.round(i / texts.length * 100)} %`);
        const batch = texts.slice(i, i + 6);
        const res = await translator(batch, { src_lang: NLLB[from], tgt_lang: NLLB[to], max_new_tokens: 256 });
        (Array.isArray(res) ? res : [res]).forEach((r, k) => out.push((r && r.translation_text) || batch[k]));
      }
    }
    if (!trOriginal) trOriginal = state.segments.map((s) => ({ text: s.text, words: s.words }));
    state.segments.forEach((s, i) => { s.text = out[i] || s.text; s.words = null; }); // časy slov sa prepočítajú podľa nového textu
    bumpDyn(); invalidateResults(); buildSegments(); renderChips(tlDuration(), state.tlPxPerSec);
    trProgress(100, `Preklad hotový: ${TR_NAMES[from]} → ${TR_NAMES[to]}.`);
    applyScriptFont();
    $("trUndo").hidden = false; $("trFrom").value = to;
  } catch (e) {
    console.error("Preklad:", e); $("trProg").hidden = true; $("trMsg").textContent = "Preklad zlyhal. " + (e && e.message ? e.message : "");
  } finally { trBusy = false; $("trGo").disabled = false; }
}
function undoTranslate() {
  if (!trOriginal) return;
  state.segments.forEach((s, i) => { if (trOriginal[i]) { s.text = trOriginal[i].text; s.words = trOriginal[i].words; } });
  trOriginal = null; bumpDyn(); invalidateResults(); buildSegments(); renderChips(tlDuration(), state.tlPxPerSec);
  $("trUndo").hidden = true; $("trProg").hidden = true; $("trMsg").textContent = "Pôvodný text je späť.";
  $("trFrom").value = $("lang").value === "auto" ? "sk" : $("lang").value;
}
function setupTranslate() {
  for (const [k, n] of Object.entries(TR_NAMES)) for (const id of ["trFrom", "trTo"]) { const o = document.createElement("option"); o.value = k; o.textContent = n; $(id).append(o); }
  $("trBtn").onclick = () => {
    const open = $("trSubs").hidden; $("trSubs").hidden = !open; $("trBtn").setAttribute("aria-expanded", String(open));
    if (open && !trOriginal) { const src = $("lang").value === "auto" ? "sk" : $("lang").value; $("trFrom").value = src; $("trTo").value = LANG !== src && NLLB[LANG] ? LANG : (src === "en" ? "sk" : "en"); }
  };
  $("trGo").onclick = runTranslate; $("trUndo").onclick = undoTranslate;
}

// ---------- účet: prihlásenie a ročné predplatné (Stripe) ----------
// Export videa je len pre prihlásených s platným predplatným. Registrácia: e-mail → platba cez Stripe
// v novej karte (projekt v tejto karte ostane) → návrat na stránku → heslo. Bez PHP servera (ukážka) sa export neobmedzuje.
const auth = { on: false, user: null, ready: false, payLink: "", price: "", pending: null, sid: "", resetTok: "" };
const canExport = () => !auth.on || !!(auth.user && auth.user.active);
async function authApi(act, body) {
  const r = await fetch("?auth=" + act, body === undefined ? { cache: "no-store" } : { method: "POST", cache: "no-store", headers: { "Content-Type": "application/json", "X-Requested-With": "titulky" }, body: JSON.stringify(body) });
  const j = await r.json().catch(() => ({ error: "Server neodpovedal správne." }));
  if (!r.ok && !j.error) j.error = "Spojenie so serverom zlyhalo. Skús to znova.";
  return j;
}
async function loadAuth() {
  if (DEMO || !/^https?:$/.test(location.protocol)) { renderAcct(); return; }
  try {
    const j = await authApi("me");
    if (j && "user" in j) Object.assign(auth, { on: true, user: j.user, ready: !!j.ready, payLink: j.payLink || "", price: j.price || "" });
  } catch { /* bez PHP: bez účtov */ }
  renderAcct();
  const q = new URLSearchParams(location.search), sid = q.get("platba") || q.get("session_id"), tok = q.get("reset");
  if (sid || tok) { q.delete("platba"); q.delete("session_id"); q.delete("reset"); history.replaceState(null, "", location.pathname + (q.toString() ? "?" + q : "")); }
  if (auth.on && sid) claimPayment(sid);
  if (auth.on && tok) { auth.resetTok = tok; openAuth("reset"); }
}
function renderAcct() {
  const u = auth.user;
  $("acctName").textContent = u ? u.email : "Hosť";
  $("acctPlan").textContent = u ? (u.active ? "Aktívne do " + new Date(u.until * 1000).toLocaleDateString(LOCALE) : "Predplatné vypršalo") : "4,90 € ročne";
  $("acctAv").textContent = u ? u.email[0].toUpperCase() : "?";
  $("loginBtn").disabled = !auth.on; $("loginLbl").textContent = u ? "Odhlásiť sa" : "Prihlásiť sa";
}
function authMsg(t, ok) { $("authMsg").textContent = t || ""; $("authMsg").classList.toggle("ok", !!ok); }
function openAuth(view, then) {
  if (then) auth.pending = then;
  $("authModal").hidden = false; showAuthView(view); authMsg("");
}
function showAuthView(v) {
  document.querySelectorAll("#authModal .mform").forEach((f) => f.classList.toggle("on", f.dataset.v === v));
  const tabs = v === "login" || v === "register";
  $("authTabs").hidden = !tabs;
  document.querySelectorAll("#authTabs button").forEach((b) => { const on = b.dataset.v === v; b.classList.toggle("on", on); b.setAttribute("aria-selected", String(on)); });
  const titles = { login: "Prihlás sa a exportuj", register: "Prihlás sa a exportuj", setpw: "Vitaj v Subgen", forgot: "Zabudnuté heslo?", reset: "Nové heslo", renew: "Predplatné vypršalo", done: "Vitaj v Subgen" };
  $("authTitle").textContent = titles[v] || titles.login;
  $("authSub").hidden = !tabs;
  const f = document.querySelector(`#authModal .mform[data-v="${v}"] input`); if (f) setTimeout(() => f.focus(), 30);
}
function closeAuth() { $("authModal").hidden = true; auth.pending = null; }
function authDone(user) {
  auth.user = user; renderAcct(); showAuthView("done"); authMsg("");
}
function payUrl(email) { // Stripe Payment Link s predvyplneným e-mailom
  return auth.payLink + (auth.payLink.includes("?") ? "&" : "?") + "prefilled_email=" + encodeURIComponent(email || "");
}
async function claimPayment(sid) {
  openAuth("setpw"); authMsg("Overujem platbu…", true); $("setpwEmail").textContent = "";
  const j = await authApi("claim", { session_id: sid });
  if (j.error) { authMsg(j.error); return; }
  if (!j.needsPassword) { authDone(j.user); return; }
  auth.sid = sid; $("setpwEmail").textContent = j.email; authMsg("");
}
async function refreshMe() { // po platbe v inej karte sa táto karta dozvie, že je účet aktívny
  if (!auth.on) return;
  const j = await authApi("me").catch(() => null); if (!j || !("user" in j)) return;
  auth.user = j.user; renderAcct();
  if (!$("authModal").hidden && canExport() && auth.user) authDone(auth.user);
}
function requireAccount(then) { // true = môže exportovať hneď
  if (canExport()) return true;
  if (auth.user && !auth.user.active) { $("renewTxt").textContent = "Predplatné vypršalo " + new Date(auth.user.until * 1000).toLocaleDateString(LOCALE) + ". Obnov ho a exportuj ďalej."; openAuth("renew", then); }
  else openAuth("register", then); // neprihlásenému rovno ponúkne ročnú platbu
  return false;
}
function setupAuth() {
  $("authClose").onclick = closeAuth;
  $("authModal").addEventListener("pointerdown", (e) => { if (e.target === $("authModal")) closeAuth(); });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && !$("authModal").hidden) closeAuth(); });
  document.querySelectorAll("#authTabs button").forEach((b) => b.onclick = () => { showAuthView(b.dataset.v); authMsg(""); });
  document.querySelectorAll("#authModal [data-go]").forEach((b) => b.onclick = () => { showAuthView(b.dataset.go); authMsg(""); });
  $("authDone").onclick = () => { const f = auth.pending; closeAuth(); if (f) f(); };
  const okEmail = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  document.querySelectorAll("#authModal form.mform").forEach((f) => f.addEventListener("submit", async (e) => {
    e.preventDefault(); const v = f.dataset.v, d = Object.fromEntries(new FormData(f)); authMsg("");
    const btn = f.querySelector("button[type=submit]"); btn.disabled = true;
    try {
      if (v === "login") {
        if (!okEmail(d.email)) return authMsg("Zadaj platný e-mail.");
        const j = await authApi("login", { email: d.email, password: d.password });
        if (j.error) return authMsg(j.error);
        auth.user = j.user; renderAcct();
        if (!j.user.active) { $("renewTxt").textContent = "Predplatné vypršalo " + new Date(j.user.until * 1000).toLocaleDateString(LOCALE) + ". Obnov ho a exportuj ďalej."; showAuthView("renew"); return; }
        authDone(j.user);
      }
      if (v === "register" || v === "renew") {
        if (!auth.ready) return authMsg("Platby ešte nie sú na serveri nastavené.");
        const email = v === "renew" ? auth.user.email : d.email;
        if (!okEmail(email)) return authMsg("Zadaj platný e-mail.");
        window.open(payUrl(email), "_blank", "noopener");
        authMsg("Dokonči platbu v novej karte. Keď si potom nastavíš heslo, export sa tu odomkne sám.", true);
      }
      if (v === "setpw" || v === "reset") {
        if ((d.password || "").length < 8) return authMsg("Heslo musí mať aspoň 8 znakov.");
        if (d.password !== d.password2) return authMsg("Heslá sa nezhodujú.");
        const j = v === "setpw" ? await authApi("setpw", { session_id: auth.sid, password: d.password }) : await authApi("reset", { token: auth.resetTok, password: d.password });
        if (j.error) return authMsg(j.error);
        authDone(j.user);
      }
      if (v === "forgot") {
        if (!okEmail(d.email)) return authMsg("Zadaj platný e-mail.");
        const j = await authApi("forgot", { email: d.email });
        if (j.error) return authMsg(j.error);
        authMsg("Ak k tomuto e-mailu existuje účet, poslali sme naň odkaz na nové heslo.", true);
      }
    } finally { btn.disabled = false; }
  }));
  $("loginBtn").onclick = async () => {
    $("setMenu").hidden = true; $("setBtn").setAttribute("aria-expanded", "false");
    if (auth.user) { await authApi("logout", {}); auth.user = null; renderAcct(); } else openAuth("login");
  };
  addEventListener("focus", refreshMe); document.addEventListener("visibilitychange", () => { if (!document.hidden) refreshMe(); });
  loadAuth();
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
// Spoločné fonty na serveri (PHP verzia): nahratý font je dostupný pre všetkých, jeden za týždeň z jednej IP.
// Bez PHP (statická stránka, ukážka) sa fonty ukladajú len v tomto prehliadači.
const fontServer = { on: false, next: 0, days: 7, maxBytes: 10 * 1048576 }, sharedFonts = new Set();
const ensureFont = (name) => { const f = fontFaces.get(name); if (f && f.status === "unloaded") f.load().then(() => { if (page === "brand") renderBrand(); }).catch(() => {}); };
function registerSharedFont(f) {
  if (sharedFonts.has(f.name) || FONTS.includes(f.name)) return;
  const face = new FontFace(f.name, `url("${f.url}")`, { weight: "100 900", style: "normal" }); // načíta sa až pri použití
  document.fonts.add(face); sharedFonts.add(f.name); fontFaces.set(f.name, face); addFontOption(f.name);
}
async function loadSharedFonts() {
  if (DEMO || !/^https?:$/.test(location.protocol)) { updateBrandUpload(); return; }
  try {
    const r = await fetch("?fonts=list", { cache: "no-store" });
    const j = r.ok ? await r.json() : null;
    if (j && Array.isArray(j.fonts)) {
      Object.assign(fontServer, { on: true, next: j.nextUploadAt || 0, days: j.limitDays || 7, maxBytes: j.maxBytes || fontServer.maxBytes });
      j.fonts.forEach(registerSharedFont);
      [state.style, ...Object.values(state.spkStyle)].forEach((st) => ensureFont(st.font));
    }
  } catch { /* stránka bez PHP: fonty ostanú len v prehliadači */ }
  updateBrandUpload(); renderBrand();
}
const limitMsg = () => `Z tvojej IP adresy už bol tento týždeň nahraný font. Ďalší môžeš nahrať ${new Date(fontServer.next * 1000).toLocaleDateString(LOCALE, { day: "numeric", month: "long" })}.`;
function updateBrandUpload() {
  const srv = fontServer.on, limited = srv && fontServer.next * 1000 > Date.now();
  $("brandSub").textContent = srv ? "Spoločné fonty pre titulky. Nahraný font uvidia a môžu použiť všetci používatelia. Z jednej IP adresy sa dá nahrať jeden font za týždeň."
    : "Fonty pre titulky. Nahraté fonty ostanú len v tomto prehliadači a nájdeš ich aj vo výbere fontu pri štýle titulkov.";
  $("fontRules").textContent = srv ? `Len súbory fontov TTF, OTF, WOFF alebo WOFF2, najviac ${Math.round(fontServer.maxBytes / 1048576)} MB · 1 font za týždeň` : "TTF, OTF, WOFF alebo WOFF2, najviac 15 MB";
  $("fontRightsRow").hidden = !srv || limited;
  $("fontDrop").classList.toggle("off", limited);
  if (limited && !$("brandMsg").textContent) $("brandMsg").textContent = limitMsg();
  $("fontMsg").textContent = srv ? "Vlastný font nahráš v Značke, bude dostupný pre všetkých." : "Súbor TTF, OTF, WOFF alebo WOFF2. Font ostane len v tomto prehliadači.";
}
async function uploadShared(file, msg) {
  if (fontServer.next * 1000 > Date.now()) { msg(limitMsg()); return; }
  if (!$("fontRights").checked) { msg("Najprv potvrď, že font môžeš zdieľať so všetkými."); $("fontRights").focus(); return; }
  if (file.size > fontServer.maxBytes) { msg(`Font je príliš veľký (najviac ${Math.round(fontServer.maxBytes / 1048576)} MB).`); return; }
  const fd = new FormData(); fd.append("font", file);
  msg("Nahrávam font na server…");
  try {
    const r = await fetch("?fonts=upload", { method: "POST", body: fd, headers: { "X-Requested-With": "titulky" } });
    const j = await r.json().catch(() => ({ error: "Server neodpovedal správne." }));
    if (j.error === "limit") { fontServer.next = j.nextUploadAt || 0; msg(limitMsg()); updateBrandUpload(); return; }
    if (!r.ok || !j.ok) { msg(j.error || "Nahranie fontu zlyhalo."); return; }
    registerSharedFont(j.font); ensureFont(j.font.name);
    if (!j.duplicate) fontServer.next = j.nextUploadAt || 0;
    $("fontRights").checked = false;
    msg(j.duplicate ? `Tento font už v knižnici je ako „${j.font.name}“. Limit sa ti nezapočítal.` : `Font „${j.font.name}“ je nahraný a dostupný pre všetkých.`);
    updateBrandUpload(); renderBrand();
  } catch { msg("Spojenie so serverom zlyhalo. Skús to znova."); }
}
async function uploadFont(file, fromBrand = false) {
  const msg = (t) => { $(fromBrand ? "brandMsg" : "fontMsg").textContent = t; };
  msg("");
  if (fontServer.on) {
    if (!/\.(ttf|otf|woff2?)$/i.test(file.name)) { msg(`„${file.name}“ nie je font. Prijímame len súbory TTF, OTF, WOFF a WOFF2.`); return; }
    return uploadShared(file, (t) => { $("brandMsg").textContent = t; });
  }
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
  sp.style.cssText = `font-family:"${st.font}",${SCRIPT_FALLBACK};font-weight:${st.weight ?? (st.bold ? 700 : 400)};font-style:${st.italic ? "italic" : "normal"};font-size:${16 * scale}px;color:${st.color}`;
  if (st.bgOpacity > 0) sp.style.background = `rgba(${rgbOf(st.bg)},${st.bgOpacity / 100})`;
  else if (st.shadow) sp.style.textShadow = "0 1px 4px rgba(0,0,0,.7)";
  else { const c = st.stroke || "#000", o = st.stroke ? 2 : 1; sp.style.textShadow = `-${o}px 0 ${c},${o}px 0 ${c},0 -${o}px ${c},0 ${o}px ${c},-${o}px -${o}px ${c},${o}px ${o}px ${c},-${o}px ${o}px ${c},${o}px -${o}px ${c}`; if (st.highlight) { sp.textContent = ""; const a = document.createElement("span"); a.textContent = "Ahoj "; const b2 = document.createElement("span"); b2.textContent = "svet"; b2.style.color = st.hl; sp.append(a, b2); } }
  box.append(sp);
}
// Štýl s vlastnou animáciou (Slow down) ju zapne; pri prechode z neho na iný štýl sa jeho animácia vypne.
const animFor = (p, o) => p.anim || ((PRESETS.find((x) => x.id === o.preset) || {}).anim ? "none" : o.anim || "none");
function applyPreset(p) {
  if (state.cur !== 1 && state.spkStyle[state.cur]) {
    const o = state.spkStyle[state.cur], id = state.cur;
    state.spkStyle[id] = { ...p, preset: p.id, highlight: p.highlight ?? o.highlight, caps: p.caps ?? o.caps, anim: animFor(p, o) };
    if (p.y !== undefined) ASPECTS.forEach((a) => { Object.assign(getPos(a.k, id), { x: 0.5, y: p.y, position: p.position ?? null }); });
  } else {
    const o = state.style;
    state.style = { ...p, preset: p.id, highlight: p.highlight ?? o.highlight, caps: p.caps ?? o.caps, on: o.on, anim: animFor(p, o) };
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
  document.querySelectorAll("#anims .atile").forEach((b) => { const on = (st.anim || "none") === b.dataset.id; b.classList.toggle("on", on); b.setAttribute("aria-checked", String(on)); b.disabled = !!st.dynamic; });
  $("anims").style.opacity = st.dynamic ? 0.45 : 1; $("animHint").hidden = !st.dynamic;
  $("segments").classList.toggle("spk-on", multiSpk());
  $("styleCustom").hidden = !!st.preset;
  markActive(); invalidateResults();
}

// ---------- export: video + titulky nahrané z plátna ----------
async function recordAspect(aspect, onProgress, job) {
  if (!window.MediaRecorder) throw new Error("Tento prehliadač nevie nahrávať video. Použi Chrome, Edge alebo Firefox.");
  const { W, H } = exportSize(aspect), st = styleFor(aspect), stOf = (sp, seg) => styleFor(aspect, sp, seg), crop = { ...state.crop[aspect] };
  { const all = state.segments.map((x) => x.text).join(" "); await Promise.all(SCRIPTS.filter((sc) => sc.re.test(all)).flatMap((sc) => [400, 700, 800].map((w) => document.fonts.load(`${w} 40px "${sc.font}"`, all.slice(0, 300)).catch(() => {})))); } // písma pre arabčinu, hindčinu, čínštinu
  await Promise.all([st, ...state.spk.map((x) => stOf(x.id)), ...state.segments.filter((x) => x.styleOv).map((x) => stOf(x.speaker || 1, x))].flatMap((x) => [400, 700, 800].map((wt) => document.fonts.load(`${x.italic ? "italic " : ""}${wt} 40px "${x.font}"`).catch(() => {}))));
  const canvas = document.createElement("canvas"); canvas.width = W; canvas.height = H;
  const ctx = canvas.getContext("2d");
  const ev = document.createElement("video"); ev.src = state.url; ev.playsInline = true;
  await new Promise((res, rej) => { ev.onloadeddata = res; ev.onerror = () => rej(new Error("Video sa nepodarilo načítať.")); });
  let epc = 0;
  if (isEdited()) { ev.currentTime = firstClipStart() + 0.001; await new Promise((res) => { ev.onseeked = res; }); ev.onseeked = null; }
  ev.playbackRate = state.clips[0] ? state.clips[0].speed : 1;
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
  const total = isEdited() ? outDuration() : (ev.duration || 1);
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
    if (job !== state.job || state.exportCancel) { live = false; ev.pause(); try { rec.stop(); } catch {} return; } // nový projekt alebo Zrušiť export
    const t = ev.currentTime, step = cutStep(t, epc);
    if (step === "end") { finish(); return; }
    if (step) { epc = step.pc; ev.playbackRate = state.clips[epc].speed; if (step.t != null) { jumpTo(step.t); return; } }
    const fx = transFx(t); if (gain) gain.gain.value = fxGain(fx);
    renderFrame(ctx, ev, t, W, H, stOf, crop);
    onProgress(Math.min(99, Math.round((isEdited() ? srcToOut(t) || 0 : t) / total * 100)));
    schedule();
  };
  ev.onended = () => { // koniec súboru nemusí byť koniec videa: po presune klipov môže ďalší klip nasledovať
    const st = cutStep(ev.duration, epc);
    if (live && st && st !== "end" && st.t != null) { epc = st.pc; ev.playbackRate = state.clips[epc].speed; jumpTo(st.t); } else finish();
  };
  await ac.resume(); rec.start(500); draw(); await ev.play();
  await finished; ac.close();
  const ext = rec.mimeType.includes("mp4") ? "mp4" : "webm";
  if (job !== state.job || state.exportCancel) return null;
  return { url: URL.createObjectURL(new Blob(chunks, { type: rec.mimeType || "video/webm" })), ext };
}
// Len jeden formát je vidno naraz, ale "Exportovať všetko" spracuje aj tie ostatné na pozadí (recordAspect
// si vyrába vlastné video/plátno, nepotrebuje viditeľnú kartu); výsledok sa uloží do state.results a ukáže
// sa hneď, ako na daný formát prepneš ikonkou.
async function exportAspects(list) {
  if (state.busyExport || state.playable === false) return;
  if (!requireAccount(() => exportAspects(list))) return; // export len s predplatným
  const job = state.job, multi = list.length > 1;
  state.busyExport = true; state.exportCancel = false; $("exportAll").disabled = true; $("exportCur").disabled = true; $("export-error").hidden = true; $("exportStop").hidden = false; $("exportStatus").textContent = "";
  try {
    for (let idx = 0; idx < list.length; idx++) {
      const k = list[idx]; if (job !== state.job) break;
      if (state.results[k] && !DEMO) { saveVideo(k); continue; } // už vyexportované a nezmenené: len sa znova stiahne
      const live = state.out && state.out.k === k ? state.out : null;
      const bar = live ? live.prog.querySelector("i") : null, label = live ? live.prog.querySelector("span") : null;
      if (live) { live.prog.hidden = false; bar.style.width = "0%"; label.textContent = "Pripravujem…"; }
      if (multi) $("exportStatus").textContent = `Exportujem formát ${idx + 1} z ${list.length} (${k})…`;
      try {
        const r = await recordAspect(k, (p) => {
          if (live) { bar.style.width = p + "%"; label.textContent = `Exportujem… ${p} %`; }
          if (multi) $("exportStatus").textContent = `Exportujem formát ${idx + 1} z ${list.length} (${k})… ${p} %`;
        }, job);
        if (!r || job !== state.job || state.exportCancel) {
          if (r) URL.revokeObjectURL(r.url);
          if (state.exportCancel && live) { live.prog.hidden = true; }
          if (state.exportCancel) $("exportStatus").textContent = "";
          break;
        }
        state.results[k] = r;
        if (live) {
          if (DEMO) { // v ukážke sa sťahovanie nedá, výsledok sa prehrá priamo v karte
            const v = document.createElement("video"); v.className = "out-result"; v.controls = true; v.playsInline = true; v.src = r.url; live.el.insertBefore(v, live.prog);
          }
          label.textContent = DEMO ? "Hotovo" : "Hotovo, video sa stiahlo."; bar.style.width = "100%";
        }
        if (!DEMO) saveVideo(k); // stiahne sa iba po exporte (export je len pre prihlásených s predplatným)
      } catch (e) { if (job === state.job) { $("export-error").textContent = e.message || "Export zlyhal."; $("export-error").hidden = false; } if (live) live.prog.hidden = true; }
    }
  } finally {
    const canceled = state.exportCancel;
    state.busyExport = false; state.exportCancel = false; $("exportStop").hidden = true;
    $("exportStatus").textContent = canceled ? "Export je zrušený." : "";
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
  ANIMS.forEach((x) => { // dlaždice animácií s pohyblivou ukážkou
    const b = document.createElement("button"); b.type = "button"; b.className = "atile"; b.dataset.id = x.id; b.setAttribute("role", "radio"); b.setAttribute("aria-label", "Animácia " + x.name);
    const smp = document.createElement("span"); smp.className = "asmp a-" + x.id;
    const w1 = document.createElement("i"), w2 = document.createElement("i"); w1.textContent = "Ahoj"; w2.textContent = "svet"; smp.append(w1, w2);
    const nm = document.createElement("span"); nm.className = "tname"; nm.textContent = x.name;
    b.append(smp, nm); b.onclick = () => { activeStyle().anim = x.id; syncControls(); if (video.paused && state.out) { const i = segmentAt(video.currentTime); video.currentTime = (i >= 0 ? state.segments[i].start : video.currentTime) + 0.01; video.play().catch(() => {}); } };
    $("anims").append(b);
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
  $("font").onchange = (e) => { activeStyle().font = e.target.value; ensureFont(e.target.value); custom(); };
  $("highlight").onchange = (e) => { activeStyle().highlight = e.target.checked; syncControls(); };
  $("hl").oninput = (e) => { activeStyle().hl = e.target.value; syncControls(); };
  $("roam").onchange = (e) => { activeStyle().roam = e.target.checked; syncControls(); };
  $("caps").onchange = (e) => { activeStyle().caps = e.target.checked; syncControls(); };
  $("exportAll").onclick = () => exportAspects(ASPECTS.map((a) => a.k));
  $("dl-srt").onclick = () => saveText(toSrt(cutSegments()), "titulky.srt", "application/x-subrip");
  $("dl-vtt").onclick = () => saveText(toVtt(cutSegments()), "titulky.vtt", "text/vtt");
  $("newproject").onclick = reset;
  document.querySelectorAll("#steps .step").forEach((b) => b.onclick = () => setTab(b.dataset.tab));
  $("exportCur").onclick = () => exportAspects([state.aspect]);
  $("zoom").oninput = (e) => { const q = state.crop[state.aspect]; if (!q) return; q.zoom = +e.target.value; markActive(); invalidateResults(); };
  $("cropReset").onclick = () => { state.crop[state.aspect] = { zoom: 1, cx: 0.5, cy: 0.5 }; markActive(); invalidateResults(); };
  $("fontUp").onclick = () => { if (fontServer.on) setPage("brand"); else $("fontFile").click(); };
  document.querySelectorAll("#quality .opt").forEach((b) => b.onclick = () => { state.quality = b.dataset.q; invalidateResults(); refreshQuality(); });
  $("fontsAll").onclick = () => setPage("brand");
  // Text titulkov v ľavom stĺpci (na mobile pod časovou osou), menu v Nastaveniach pod ozubeným kolieskom
  const wide = matchMedia("(min-width: 821px)");
  const placeText = () => {
    const card = $("textCard"), home = $("textCardHome");
    if (wide.matches && card.parentElement !== $("sideSubs")) $("sideSubs").append(card);
    if (!wide.matches && card.previousElementSibling !== home) home.after(card);
    if (curView === "editor") applyTab(); else card.hidden = true;
  };
  wide.addEventListener("change", placeText); placeText();
  const closeSet = () => { $("setMenu").hidden = true; $("setBtn").setAttribute("aria-expanded", "false"); };
  $("setBtn").onclick = (e) => { e.stopPropagation(); const open = $("setMenu").hidden; $("setMenu").hidden = !open; $("setBtn").setAttribute("aria-expanded", String(open)); };
  document.addEventListener("pointerdown", (e) => { if (!$("setMenu").hidden && !$("setMenu").contains(e.target) && !$("setBtn").contains(e.target)) closeSet(); });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && !$("setMenu").hidden) { closeSet(); $("setBtn").focus(); } });
  document.querySelectorAll(".setmenu .nav").forEach((a) => a.addEventListener("click", closeSet));
  $("exportStop").onclick = () => { if (state.busyExport) { state.exportCancel = true; $("exportStop").hidden = true; } };
  setupCut();
  document.querySelectorAll(".nav[data-page]").forEach((a) => a.onclick = (e) => { e.preventDefault(); setPage(a.dataset.page); });
  $("brandBack").onclick = $("libBack").onclick = () => setPage("work");
  document.querySelectorAll(".libpos button").forEach((b) => b.onclick = () => { try { localStorage.setItem("titulky-libY", b.dataset.y); } catch {} renderLibrary(); });
  $("brandFile").onchange = async (e) => { const f = e.target.files[0]; if (f) await uploadFont(f, true); e.target.value = ""; };
  $("fontDrop").onkeydown = (e) => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); $("brandFile").click(); } };
  ["dragenter", "dragover"].forEach((ev) => $("fontDrop").addEventListener(ev, (e) => { e.preventDefault(); $("fontDrop").classList.add("over"); }));
  ["dragleave", "drop"].forEach((ev) => $("fontDrop").addEventListener(ev, () => $("fontDrop").classList.remove("over")));
  $("fontDrop").addEventListener("drop", async (e) => { e.preventDefault(); const f = e.dataTransfer.files[0]; if (f) await uploadFont(f, true); });
  $("fontSampleText").addEventListener("input", renderBrand);
  $("fontFile").onchange = (e) => { if (e.target.files[0]) uploadFont(e.target.files[0]); e.target.value = ""; };
  loadSavedFonts(); loadSharedFonts(); setupAuth(); setupTranslate();
  $("fontSampleText").value = tr($("fontSampleText").value);
  Object.entries(UI_LANGS).forEach(([k, n]) => { const o = document.createElement("option"); o.value = k; o.textContent = n; $("uiLang").append(o); });
  $("uiLang").value = LANG;
  $("uiLang").onchange = (e) => setLang(e.target.value);
  document.addEventListener("langchange", () => { // texty kreslené v kóde (plátno, štítky) sa prekreslia v novom jazyku
    $("uiLang").value = LANG; LIB_SEGS = libSegs(); LIB_CHUNKS = null;
    // Predvolené texty, ktoré už sú v dátach (mená rečníkov, ukážkové titulky, text ukážky písma), sa preložia tiež.
    const retr = (v, sk) => (v === sk || (DICT[sk] && DICT[sk].includes(v)) ? tr(sk) : v);
    const spkWords = ["Rečník", ...(DICT["Rečník"] || [])];
    state.spk.forEach((x) => { const i = x.name.lastIndexOf(" "); if (i > 0 && spkWords.includes(x.name.slice(0, i)) && /^\d+$/.test(x.name.slice(i + 1))) x.name = tr("Rečník") + x.name.slice(i); });
    SAMPLE_SK.forEach((sk, i) => { SAMPLE[i] = tr(sk); });
    let segCh = false;
    state.segments.forEach((g) => { for (const sk of SAMPLE_SK) { const t = retr(g.text, sk); if (t !== g.text) { g.text = t; g.words = estimateWords(t, g.start, g.end); segCh = true; break; } } });
    $("fontSampleText").value = retr($("fontSampleText").value, "Ahoj, toto sú moje titulky");
    if (segCh) { bumpDyn(); invalidateResults(); }
    if (state.out || segCh) { buildSegments(); renderSpkList(); renderSelSpk(); renderChips(tlDuration(), state.tlPxPerSec); }
    if (state.out) { syncControls(); renderCutUi(); }
    updateBrandUpload(); renderBrand(); if (page === "library") renderLibrary();
  });
  $("tlPlay").onclick = () => { if (video.paused) video.play().catch(() => {}); else video.pause(); };
  video.addEventListener("ended", () => { // to isté pri náhľade
    if (!isEdited()) return;
    const st = cutStep(video.duration, state.pc);
    if (st && st !== "end" && st.t != null) { state.pc = st.pc; video.currentTime = st.t; video.play().catch(() => {}); }
    else { state.pc = 0; video.currentTime = firstClipStart() + 0.001; }
  });
  $("tlSpeed").onchange = (e) => { state.pvRate = +e.target.value; }; // rýchlosť náhľadu (násobí sa rýchlosťou klipu)
  $("tlZoom").oninput = (e) => { state.tlZoomVal = +e.target.value; renderTimeline(); };
  $("tlZoomIn").onclick = () => { state.tlZoomVal = clamp(state.tlZoomVal + 0.5, 1, 6); $("tlZoom").value = state.tlZoomVal; renderTimeline(); };
  $("tlZoomOut").onclick = () => { state.tlZoomVal = clamp(state.tlZoomVal - 0.5, 1, 6); $("tlZoom").value = state.tlZoomVal; renderTimeline(); };
  new ResizeObserver(() => renderTimeline()).observe($("tlScroll"));
  // klik na pozadie časovej osi (mimo titulku) pretočí video; ťahanie "scrubuje"
  const tAt = (e) => clamp((e.clientX - $("tlInner").getBoundingClientRect().left) / state.tlPxPerSec, 0, tlDuration());
  const scrub = (e) => { video.currentTime = tAt(e); };
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
      if (state.sel.size) { selAt = { kind: "point", x: e.clientX, y: e.clientY - 40 }; placeSelDlg(); }
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
  document.body.append($("selBar")); // plávajúce okienko nad stránkou
  addEventListener("resize", placeSelDlg); document.addEventListener("scroll", () => placeSelDlg(), true);
  document.addEventListener("pointerdown", (e) => { // klik mimo okienka a titulkov výber zruší
    if ($("selBar").hidden || e.target.closest("#selBar, .segments, #tlInner, .spk-menu, .chip")) return;
    clearSel();
  });
  // medzerník = prehrať/zastaviť, Esc = zrušiť výber
  document.addEventListener("keydown", (e) => {
    if ($("view-editor").hidden || page !== "work" || e.defaultPrevented) return;
    const el = e.target, tag = el.tagName, typing = tag === "TEXTAREA" || tag === "SELECT" || el.isContentEditable ||
      (tag === "INPUT" && !["range", "checkbox", "color", "button", "file"].includes(el.type));
    if (e.code === "Space" && !typing) {
      e.preventDefault(); if (tag === "BUTTON" || tag === "INPUT") el.blur();
      if (video.paused) video.play().catch(() => {}); else video.pause();
    } else if (e.key === "Escape" && !typing && state.sel.size) clearSel();
    else if (state.tab === "edit" && !typing && !e.altKey) {
      const mod = e.ctrlKey || e.metaKey;
      if (mod && e.code === "KeyZ") { e.preventDefault(); if (e.shiftKey) redoCut(); else undoCut(); }
      else if (mod && e.code === "KeyY") { e.preventDefault(); redoCut(); }
      else if (!mod && e.code === "KeyS") { e.preventDefault(); splitAt(video.currentTime); }
      else if (!mod && (e.key === "Delete" || e.key === "Backspace") && state.clipSel >= 0) { e.preventDefault(); deleteClip(state.clipSel); }
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
      if (cur.length && (o.br || cur.length >= 3 || len > 20 || o.start - cur[cur.length - 1].end > 0.5)) flush(); // ručné zalomenie začne novú frázu
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
  const rtl = isRtl(c.words.map((o) => o.w).join(" ")); ctx.direction = rtl ? "rtl" : "ltr";
  if ("letterSpacing" in ctx) ctx.letterSpacing = rtl ? "0px" : `${-base * 0.02}px`; // arabské písmo sa nesmie roztrhnúť
  const n = c.words.length;
  const split = n === 1 ? [1] : n === 2 ? (h % 2 ? [1, 1] : [2]) : [h % 2 ? 1 : 2, h % 2 ? 2 : 1];
  const lines = []; let k = 0;
  split.forEach((cnt, li) => {
    const arr = [];
    for (let j = 0; j < cnt; j++, k++) {
      const o = c.words[k], text = st.caps ? o.w.toUpperCase() : o.w.toLowerCase();
      const px = base * (li === 0 ? 1 : 0.82);
      const font = `${st.bold ? 800 : 600} ${px}px "${st.font}", ${SCRIPT_FALLBACK}`; // jeden druh písma pre všetky slová
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
    for (const it of rtl ? [...line].reverse() : line) { // sprava doľava: prvé slovo je vpravo
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
  ctx.direction = "ltr";
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
    if (cur.length && (o.br || w + sp + ww > maxW)) { lines.push({ idx: cur, w }); cur = []; w = 0; } // ručné zalomenie alebo nedostatok miesta
    w += (cur.length ? sp : 0) + ww; cur.push(n);
  });
  if (cur.length) lines.push({ idx: cur, w });
  return lines;
}
// Animácie titulkov (pre bežné štýly; Dynamický štýl má vlastnú). Kreslí sa tým istým kódom v náhľade aj exporte.
const ANIMS = [
  { id: "none", name: "Žiadna" }, { id: "slowdown", name: "Slow down" }, { id: "pop", name: "Pop" }, { id: "karaoke", name: "Karaoke" }, { id: "box", name: "Rámček" },
  { id: "bounce", name: "Skok" }, { id: "typewriter", name: "Písací stroj" }, { id: "fade", name: "Plynulé slová" },
  { id: "slide", name: "Vysunutie" }, { id: "zoom", name: "Zväčšenie" }, { id: "glow", name: "Žiara" },
];
// Písmo sprava doľava (arabčina, hebrejčina, perzština): slová sa kladú sprava, ako sa v tých krajinách píše.
const RTL_RE = /[\u0590-\u08FF\uFB1D-\uFDFF\uFE70-\uFEFF]/;
const isRtl = (text) => RTL_RE.test(text || "");
const easeBack = (k) => { const c = 1.9; return 1 + (c + 1) * Math.pow(k - 1, 3) + c * Math.pow(k - 1, 2); }; // s miernym preskokom
const lumOf = (hex) => { const [r, g, b] = rgbOf(hex).split(",").map(Number); return (0.299 * r + 0.587 * g + 0.114 * b) / 255; };
function drawCaption(ctx, seg, t, W, H, st) {
  const anim = st.anim || "none";
  const px = Math.min(W, H) * st.size / 100, lh = px * (st.lineH ?? 1.25), padX = st.bgOpacity > 0 ? px * .3 : 0;
  ctx.font = `${st.italic ? "italic " : ""}${st.weight ?? (st.bold ? 700 : 400)} ${px}px "${st.font}", ${SCRIPT_FALLBACK}`;
  ctx.textBaseline = "middle"; ctx.lineJoin = "round";
  const words = wordsFor(seg); let active = -1; words.forEach((o, n) => { if (t >= o.start) active = n; });
  const lines = wrapWords(ctx, words, W * 0.84 - 2 * padX), total = lines.length * lh, sp = ctx.measureText(" ").width;
  const blockW = Math.max(...lines.map((l) => l.w)) + 2 * padX;
  const cx = clamp(st.x * W, blockW / 2, Math.max(blockW / 2, W - blockW / 2));
  let top = clamp(st.y * H, total / 2, Math.max(total / 2, H - total / 2)) - total / 2;
  // celý titulok: Vysunutie (zospodu a von), ostatné animácie len krátko doznejú na konci
  let alpha = 1;
  if (anim === "slide") {
    const kin = clamp((t - seg.start) / 0.28, 0, 1), kout = clamp((seg.end + 0.05 - t) / 0.2, 0, 1);
    top += (1 - easeOut(kin)) * lh * 0.9; alpha = Math.min(easeOut(kin), kout);
  }
  if (alpha <= 0) return;
  ctx.save(); ctx.globalAlpha = alpha;
  const pos = [], reveal = ["slowdown", "pop", "fade", "typewriter"].includes(anim); // pri postupnom príchode slov rastie aj podklad
  const rtl = isRtl(seg.text); ctx.direction = rtl ? "rtl" : "ltr"; ctx.textAlign = "left";
  lines.forEach((line, li) => {
    const y = top + li * lh, x0 = cx - line.w / 2, x1 = cx + line.w / 2; let x = rtl ? x1 : x0, wv = 0;
    line.idx.forEach((n) => {
      const ww = ctx.measureText(words[n].w).width;
      if (rtl) { x -= ww; pos[n] = { x, y: y + lh / 2, ww }; if (!reveal || t >= words[n].start) wv = x1 - x; x -= sp; } // sprava doľava
      else { pos[n] = { x, y: y + lh / 2, ww }; if (!reveal || t >= words[n].start) wv = x + ww - x0; x += ww + sp; }
    });
    if (st.bgOpacity > 0 && wv > 0) {
      const bx = rtl ? x1 - wv : x0;
      ctx.fillStyle = `rgba(${rgbOf(st.bg)},${st.bgOpacity / 100})`; ctx.beginPath();
      if (ctx.roundRect) ctx.roundRect(bx - padX, y, wv + 2 * padX, lh, px * .12); else ctx.rect(bx - padX, y, wv + 2 * padX, lh);
      ctx.fill();
    }
  });
  const hl = st.hl || "#ffe066";
  if (anim === "box" && active >= 0) { // farebný rámček, ktorý plynulo prejde na vyslovené slovo
    const a = pos[active], b = pos[Math.max(0, active - 1)], k = active > 0 ? easeOut(clamp((t - words[active].start) / 0.12, 0, 1)) : 1;
    const bx = b.x + (a.x - b.x) * k, bw = b.ww + (a.ww - b.ww) * k, by = b.y + (a.y - b.y) * k, pad = px * 0.18;
    ctx.fillStyle = hl; ctx.beginPath();
    if (ctx.roundRect) ctx.roundRect(bx - pad, by - px * 0.62, bw + 2 * pad, px * 1.24, px * 0.22); else ctx.rect(bx - pad, by - px * 0.62, bw + 2 * pad, px * 1.24);
    ctx.fill();
  }
  const outline = (txt, x, y) => { if (st.bgOpacity <= 0 && anim !== "box" && !st.shadow) { ctx.strokeStyle = st.stroke || "#000"; ctx.lineWidth = px * (st.strokeW ?? .16); ctx.strokeText(txt, x, y); } };
  words.forEach((o, n) => {
    const P = pos[n]; if (!P) return;
    let txt = o.w, scale = 1, dy = 0, a = 1, color = st.highlight && n === active ? hl : st.color, glow = false;
    const since = t - o.start, dur = Math.max(0.05, o.end - o.start);
    if (anim === "slowdown") { if (since < 0) return; const k = easeOut(clamp(since / 0.32, 0, 1)); a = 0.3 + 0.7 * k; dy = (1 - k) * px * 0.3; } // slovo príde sivé a mierne zdola, na svojom mieste v riadku
    if (anim === "pop") { if (since < 0) return; const k = clamp(since / 0.2, 0, 1); scale = k < 1 ? Math.max(0.01, easeBack(k)) : 1; a = clamp(since / 0.08, 0, 1); }
    if (anim === "fade") { if (since < -0.02) return; const k = easeOut(clamp((since + 0.02) / 0.25, 0, 1)); a = k; dy = (1 - k) * px * 0.35; }
    if (anim === "typewriter") { if (since < 0) return; const c = Math.max(1, Math.ceil(clamp(since / Math.min(dur, 0.5), 0, 1) * o.w.length)); txt = o.w.slice(0, c); }
    if (anim === "bounce" && n === active) { dy = -Math.sin(clamp(since / 0.3, 0, 1) * Math.PI) * px * 0.32; color = st.highlight ? hl : color; }
    if (anim === "zoom" && n === active) { scale = 1 + 0.24 * easeOut(clamp(since / 0.12, 0, 1)); color = hl; }
    if (anim === "glow" && n === active) { glow = true; color = hl; }
    if (anim === "box" && n === active) color = lumOf(hl) > 0.6 ? "#111111" : "#ffffff";
    if (anim === "karaoke") color = n < active || (n === active && since >= dur) ? hl : st.color;
    ctx.save(); ctx.globalAlpha = alpha * a;
    const cxw = P.x + P.ww / 2, cy = P.y + dy;
    if (scale !== 1) { ctx.translate(cxw, cy); ctx.scale(scale, scale); ctx.translate(-cxw, -cy); }
    if (glow) { ctx.shadowColor = hl; ctx.shadowBlur = px * 0.7; }
    else if (st.shadow) { ctx.shadowColor = "rgba(0,0,0,.55)"; ctx.shadowBlur = px * 0.28; ctx.shadowOffsetY = px * 0.05; } // jemný tieň namiesto obrysu
    outline(txt, P.x, cy);
    ctx.fillStyle = color; ctx.fillText(txt, P.x, cy);
    if (anim === "karaoke" && n === active && since < dur) { // vyslovované slovo sa plní farbou zľava doprava
      const fw = P.ww * clamp(since / dur, 0, 1);
      ctx.save(); ctx.beginPath(); ctx.rect(rtl ? P.x + P.ww - fw : P.x, cy - px, fw, px * 2); ctx.clip(); // pri arabčine sa plní sprava
      ctx.fillStyle = hl; ctx.fillText(txt, P.x, cy); ctx.restore();
    }
    ctx.restore();
  });
  ctx.restore(); ctx.direction = "ltr";
}
const srtTime = (s, sep) => { const ms = Math.round(s * 1000), p = (n, l = 2) => String(n).padStart(l, "0");
  return `${p(Math.floor(ms / 3600000))}:${p(Math.floor(ms / 60000) % 60)}:${p(Math.floor(ms / 1000) % 60)}${sep}${p(ms % 1000, 3)}`; };
const cueText = (t) => t.split("\n").map((x) => x.trim()).filter(Boolean).join("\n"); // riadky titulku tak, ako sú zalomené v texte
const toSrt = (segs) => segs.map((s, i) => `${i + 1}\n${srtTime(s.start, ",")} --> ${srtTime(s.end, ",")}\n${cueText(s.text)}\n`).join("\n");
const toVtt = (segs) => "WEBVTT\n\n" + segs.map((s) => `${srtTime(s.start, ".")} --> ${srtTime(s.end, ".")}\n${cueText(s.text)}\n`).join("\n");


if (MOCK) window.__test = { captureAudio, decodeBuffer, state, dynChunks, bumpDyn, idbCache, ensureModel, showModelState, hasLocal, preferLocal, bestModels, segmentFeatures, detectSpeakers, uploadFont, customFonts };
setup();
