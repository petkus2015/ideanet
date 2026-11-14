"use strict";
const $ = (id) => document.getElementById(id);
const OUT = { "9:16": [1080, 1920], "1:1": [1080, 1080], "16:9": [1920, 1080] };

const PRESETS = [
  { id: "modern",  name: "Modern",   font: "Inter", color: "#ffffff", bg: "#000000", bgOpacity: 60, size: 6.5, bold: true },
  { id: "klasik",  name: "Klasik",   font: "Liberation Sans", color: "#ffffff", bg: "#000000", bgOpacity: 0, size: 6, bold: true },
  { id: "kino",    name: "Kino",     font: "DejaVu Sans", color: "#ffe066", bg: "#000000", bgOpacity: 0, size: 6.5, bold: true },
  { id: "vyrazny", name: "Výrazný",  font: "Inter", color: "#ffffff", bg: "#6c5cff", bgOpacity: 100, size: 7, bold: true },
  { id: "jemny",   name: "Jemný",    font: "Carlito", color: "#ffffff", bg: "#000000", bgOpacity: 35, size: 5.5, bold: false },
  { id: "svetly",  name: "Svetlý",   font: "Inter", color: "#111111", bg: "#ffffff", bgOpacity: 90, size: 6, bold: true },
];

const state = {
  config: null, jobId: localStorage.getItem("titulky.job"), job: null,
  aspect: "9:16", style: { ...PRESETS[0], position: "bottom", preset: "modern" },
  segments: [], timer: null,
};

function show(view) {
  for (const v of ["upload", "busy", "editor"]) $("view-" + v).hidden = v !== view;
}
const api = async (url, opts) => {
  const r = await fetch(url, opts);
  if (!r.ok) {
    let msg = r.statusText;
    try { msg = (await r.json()).detail || msg; } catch {}
    const e = new Error(msg); e.status = r.status; throw e;
  }
  return r.json();
};
const fmtTime = (s) => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, "0")}`;
const fmtDate = (t) => new Date(t * 1000).toLocaleString("sk-SK", { day: "numeric", month: "numeric", hour: "2-digit", minute: "2-digit" });

// ---------- nahratie ----------
async function upload(file) {
  $("upload-error").hidden = true;
  if (file.size > state.config.max_upload_mb * 1048576) {
    return showUploadError(`Súbor je väčší ako ${state.config.max_upload_mb} MB.`);
  }
  show("busy");
  $("busy-title").textContent = "Nahrávam video…";
  $("busy-text").textContent = "";
  $("busy-bar").hidden = false;
  const fd = new FormData();
  fd.append("file", file);
  fd.append("language", $("lang").value);
  try {
    const res = await new Promise((resolve, reject) => {
      const x = new XMLHttpRequest();
      x.open("POST", "/api/jobs");
      x.upload.onprogress = (e) => { if (e.lengthComputable) $("busy-bar").firstElementChild.style.width = (e.loaded / e.total * 100) + "%"; };
      x.onload = () => {
        let body = {}; try { body = JSON.parse(x.responseText); } catch {}
        x.status < 300 ? resolve(body) : reject(new Error(body.detail || "Nahratie zlyhalo."));
      };
      x.onerror = () => reject(new Error("Nahratie zlyhalo. Skontroluj pripojenie."));
      x.send(fd);
    });
    state.jobId = res.id;
    localStorage.setItem("titulky.job", res.id);
    poll();
  } catch (e) {
    show("upload");
    showUploadError(e.message);
  }
}
function showUploadError(msg) { const el = $("upload-error"); el.textContent = msg; el.hidden = false; }

// ---------- sledovanie stavu ----------
async function poll() {
  clearTimeout(state.timer);
  let job;
  try { job = await api(`/api/jobs/${state.jobId}`); }
  catch (e) {
    if (e.status === 404) return reset(state.jobId ? "Pracovná verzia už bola odstránená." : "");
    state.timer = setTimeout(poll, 3000); return;
  }
  state.job = job;
  if (job.status === "transcribing") {
    show("busy");
    $("busy-title").textContent = "Rozpoznávam reč…";
    $("busy-text").textContent = "Pri dlhšom videu to môže chvíľu trvať.";
    $("busy-bar").hidden = true;
    state.timer = setTimeout(poll, 2000);
  } else if (job.status === "error") {
    reset("Spracovanie zlyhalo: " + job.error);
  } else {
    openEditor(job);
    if (job.status === "exporting") state.timer = setTimeout(poll, 1000);
  }
}
function reset(message) {
  editorReady = false;
  clearTimeout(state.timer);
  localStorage.removeItem("titulky.job");
  state.jobId = null; state.job = null;
  $("video").removeAttribute("src");
  show("upload");
  if (message) showUploadError(message);
}

// ---------- editor ----------
let editorReady = false;
function openEditor(job) {
  show("editor");
  const exporting = job.status === "exporting";
  $("export").disabled = exporting;
  $("export-status").hidden = !exporting;
  if (exporting) {
    $("export-status").firstElementChild.firstElementChild.style.width = job.progress + "%";
    $("export-status").lastElementChild.textContent = `Exportujem… ${job.progress} %`;
  }
  const done = job.status === "exported" && job.export;
  $("downloads").hidden = !done;
  if (done) {
    for (const k of ["mp4", "srt", "vtt"]) $("dl-" + k).href = `/api/jobs/${job.id}/download/${k}`;
  }
  $("expiry").textContent = done
    ? `Pracovná verzia sa odstráni ${fmtDate(job.expires_at)} (${job.retention_hours} h po exporte). Stiahni si súbory.`
    : `Ak video neexportuješ, pracovná verzia sa odstráni ${fmtDate(job.expires_at)}.`;
  if (job.error && !exporting) { $("export-error").textContent = job.error; $("export-error").hidden = false; }
  else $("export-error").hidden = true;
  if (editorReady) return;
  editorReady = true;
  state.segments = job.segments;
  $("video").src = `/api/jobs/${job.id}/video`;
  buildSegments();
  applyPreview();
}

function buildSegments() {
  const box = $("segments");
  box.textContent = "";
  state.segments.forEach((s, i) => {
    const row = document.createElement("div");
    row.className = "segment"; row.dataset.i = i;
    const t = document.createElement("button");
    t.textContent = fmtTime(s.start);
    t.onclick = () => { $("video").currentTime = s.start + 0.01; $("video").play(); };
    const ta = document.createElement("textarea");
    ta.value = s.text; ta.rows = 1;
    const fit = () => { ta.style.height = "auto"; ta.style.height = ta.scrollHeight + "px"; };
    ta.oninput = () => { s.text = ta.value; fit(); saveSoon(); updateCaption(); };
    row.append(t, ta); box.append(row);
    requestAnimationFrame(fit);
  });
  if (!state.segments.length) box.textContent = "V tomto videu sa nenašla žiadna reč.";
}
let saveTimer;
function saveSoon() {
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => api(`/api/jobs/${state.jobId}/segments`, {
    method: "PUT", headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ texts: state.segments.map((s) => s.text) }),
  }).catch(() => {}), 500);
}

// ---------- náhľad titulkov ----------
function applyPreview() {
  const [w, h] = OUT[state.aspect];
  const stage = $("stage");
  stage.style.aspectRatio = `${w} / ${h}`;
  stage.style.width = "auto";
  updateCaption();
}
function currentSegment() {
  const t = $("video").currentTime;
  return state.segments.findIndex((s) => t >= s.start && t < s.end);
}
function updateCaption() {
  const st = state.style, cap = $("cap");
  const [w, h] = OUT[state.aspect];
  const k = $("stage").clientWidth / w;
  const px = Math.min(w, h) * st.size / 100 * k;
  const i = currentSegment();
  document.querySelectorAll(".segment").forEach((r) => r.classList.toggle("active", +r.dataset.i === i));
  cap.textContent = "";
  if (i < 0) return;
  const span = document.createElement("span");
  span.textContent = state.segments[i].text;
  cap.style.fontSize = px + "px";
  cap.style.fontFamily = `"${st.font}", sans-serif`;
  cap.style.fontWeight = st.bold ? 700 : 400;
  span.style.color = st.color;
  const rgb = [1, 3, 5].map((n) => parseInt(st.bg.slice(n, n + 2), 16)).join(",");
  if (st.bgOpacity > 0) {
    span.style.background = `rgba(${rgb},${st.bgOpacity / 100})`;
    span.style.padding = `${px * .08}px ${px * .3}px`;
    span.style.textShadow = "none";
  } else {
    span.style.background = "none";
    const o = Math.max(1, px * .07);
    span.style.textShadow = [[-o,0],[o,0],[0,-o],[0,o],[-o,-o],[o,o],[-o,o],[o,-o]].map(([x,y]) => `${x}px ${y}px 0 #000`).join(",");
  }
  cap.style.top = cap.style.bottom = cap.style.transform = "";
  if (st.position === "top") cap.style.top = "12%";
  else if (st.position === "bottom") cap.style.bottom = "12%";
  else { cap.style.top = "50%"; cap.style.transform = "translateY(-50%)"; }
  cap.append(span);
}
function loop() { updateCaption(); requestAnimationFrame(loop); }

// ---------- ovládacie prvky ----------
function syncControls() {
  const st = state.style;
  $("color").value = st.color; $("bg").value = st.bg;
  $("bgOpacity").value = st.bgOpacity; $("bgOpacityOut").textContent = Math.round(st.bgOpacity) + " %";
  $("size").value = st.size;
  if ([...$("font").options].some((o) => o.value === st.font)) $("font").value = st.font;
  else st.font = $("font").value;
  document.querySelectorAll("#positions button").forEach((b) => b.classList.toggle("on", b.dataset.v === st.position));
  document.querySelectorAll("#aspects button").forEach((b) => b.classList.toggle("on", b.dataset.v === state.aspect));
  document.querySelectorAll(".preset").forEach((b) => b.classList.toggle("on", b.dataset.id === st.preset));
  applyPreview();
}
function setupControls() {
  const fonts = state.config.fonts;
  fonts.forEach((f) => { const o = document.createElement("option"); o.value = o.textContent = f; o.style.fontFamily = `"${f}"`; $("font").append(o); });
  state.config.aspects.forEach((a) => {
    const b = document.createElement("button"); b.dataset.v = a; b.textContent = a;
    b.onclick = () => { state.aspect = a; syncControls(); };
    $("aspects").append(b);
  });
  PRESETS.forEach((p) => {
    const b = document.createElement("button");
    b.className = "preset"; b.dataset.id = p.id;
    const sample = document.createElement("div"); sample.className = "sample";
    const sp = document.createElement("span"); sp.textContent = "Ahoj svet";
    sp.style.color = p.color; sp.style.fontFamily = `"${p.font}", sans-serif`; sp.style.fontWeight = p.bold ? 700 : 400;
    if (p.bgOpacity > 0) {
      const rgb = [1, 3, 5].map((n) => parseInt(p.bg.slice(n, n + 2), 16)).join(",");
      sp.style.background = `rgba(${rgb},${p.bgOpacity / 100})`;
    } else sp.style.textShadow = "-1px 0 #000,1px 0 #000,0 -1px #000,0 1px #000";
    sample.append(sp);
    const name = document.createElement("span"); name.className = "name"; name.textContent = p.name;
    b.append(sample, name);
    b.onclick = () => {
      const pos = state.style.position;
      state.style = { ...p, position: pos, preset: p.id };
      if (!fonts.includes(p.font)) state.style.font = fonts[0];
      syncControls();
    };
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
  state.style.font = fonts.includes(state.style.font) ? state.style.font : fonts[0];
  syncControls();

  $("export").onclick = async () => {
    $("export-error").hidden = true;
    const { font, color, bg, bgOpacity, size, position, bold } = state.style;
    try {
      await api(`/api/jobs/${state.jobId}/export`, {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ aspect: state.aspect, style: { font, color, bg, bgOpacity, size, position, bold } }),
      });
      $("downloads").hidden = true;
      poll();
    } catch (e) { $("export-error").textContent = e.message; $("export-error").hidden = false; }
  };
  $("delete").onclick = async () => {
    if (!confirm("Naozaj zmazať video a titulky? Nedá sa to vrátiť.")) return;
    try { await api(`/api/jobs/${state.jobId}`, { method: "DELETE" }); } catch {}
    editorReady = false;
    reset("");
  };
}

// ---------- štart ----------
async function init() {
  state.config = await api("/api/config");
  $("retention-note").textContent = `Pracovná verzia sa po exporte ${state.config.retention_hours} h odstráni.`;
  $("drop-hint").textContent = `MP4, MOV, WEBM · do ${state.config.max_upload_mb} MB`;
  setupControls();
  requestAnimationFrame(loop);
  const drop = $("drop");
  $("file").onchange = (e) => e.target.files[0] && upload(e.target.files[0]);
  ["dragover", "dragenter"].forEach((n) => drop.addEventListener(n, (e) => { e.preventDefault(); drop.classList.add("over"); }));
  ["dragleave", "drop"].forEach((n) => drop.addEventListener(n, (e) => { e.preventDefault(); drop.classList.remove("over"); }));
  drop.addEventListener("drop", (e) => e.dataTransfer.files[0] && upload(e.dataTransfer.files[0]));
  if (state.jobId) poll(); else show("upload");
}
init();
