"""Titulky: nahraj video, dostaň automatické titulky, uprav štýl a exportuj.

Pracovná verzia sa maže RETENTION_HOURS (24 h) po exporte; ak sa nikdy
neexportuje, 24 h po nahratí.
"""
import json
import os
import re
import shutil
import subprocess
import threading
import time
import uuid
from concurrent.futures import ThreadPoolExecutor
from contextlib import asynccontextmanager
from pathlib import Path

from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.responses import FileResponse
from fastapi.staticfiles import StaticFiles

import asr
import render

BASE = Path(__file__).parent
DATA = Path(os.environ.get("TITULKY_DATA", BASE / "data")) / "jobs"
RETENTION = float(os.environ.get("TITULKY_RETENTION_HOURS", "24")) * 3600
MAX_UPLOAD = int(os.environ.get("TITULKY_MAX_UPLOAD_MB", "1000")) * 1024 * 1024
SWEEP_EVERY = int(os.environ.get("TITULKY_SWEEP_SECONDS", "300"))
EXTS = {".mp4", ".mov", ".m4v", ".webm", ".mkv", ".avi", ".ts", ".mts", ".m2ts", ".3gp", ".mpg", ".mpeg"}
ID_RE = re.compile(r"^[0-9a-f]{32}$")
CANDIDATE_FONTS = ["Inter", "DejaVu Sans", "DejaVu Serif", "Liberation Sans",
                   "Liberation Serif", "FreeSans", "Carlito", "Caladea"]

DATA.mkdir(parents=True, exist_ok=True)
pool = ThreadPoolExecutor(max_workers=int(os.environ.get("TITULKY_WORKERS", "2")))
lock = threading.RLock()


def available_fonts():
    try:
        out = subprocess.run(["fc-list", ":", "family"], capture_output=True, text=True).stdout
        have = {f.strip() for line in out.splitlines() for f in line.split(",")}
        fonts = [f for f in CANDIDATE_FONTS if f in have]
    except OSError:
        fonts = []
    return fonts or ["Arial"]


FONTS = available_fonts()


# ---------- pracovné verzie na disku ----------
def job_dir(job_id):
    if not ID_RE.match(job_id):
        raise HTTPException(404, "Neexistuje")
    d = DATA / job_id
    if not d.is_dir():
        raise HTTPException(404, "Pracovná verzia už neexistuje (bola odstránená).")
    return d


def load(job_id):
    d = job_dir(job_id)
    try:
        return json.loads((d / "meta.json").read_text(encoding="utf-8"))
    except (OSError, ValueError):
        raise HTTPException(404, "Neexistuje")


def save(job_id, meta):
    d = DATA / job_id
    if not d.is_dir():  # medzitým zmazané
        return
    with lock:
        tmp = d / "meta.tmp"
        tmp.write_text(json.dumps(meta, ensure_ascii=False), encoding="utf-8")
        tmp.replace(d / "meta.json")


def update(job_id, **fields):
    with lock:
        meta = load(job_id)
        meta.update(fields)
        save(job_id, meta)


def expires_at(meta):
    return (meta.get("exported_at") or meta["created_at"]) + RETENTION


def public(job_id, meta):
    return {
        "id": job_id, "status": meta["status"], "error": meta.get("error"),
        "segments": meta.get("segments", []), "duration": meta.get("duration"),
        "language": meta.get("language"), "export": meta.get("export"),
        "progress": meta.get("progress", 0),
        "expires_at": expires_at(meta), "retention_hours": RETENTION / 3600,
    }


def sweep():
    """Zmaže pracovné verzie, ktorým vypršal čas."""
    now = time.time()
    for d in DATA.iterdir():
        try:
            meta = json.loads((d / "meta.json").read_text(encoding="utf-8"))
            expired = now >= expires_at(meta)
        except (OSError, ValueError, KeyError):
            # rozpracovaný alebo poškodený záznam: zmaž až po lehote podľa času súboru
            expired = now - d.stat().st_mtime >= RETENTION
        if expired and d.is_dir():
            shutil.rmtree(d, ignore_errors=True)


def sweeper():
    while True:
        try:
            sweep()
        except Exception as e:  # nikdy nezastaviť čistenie
            print("sweep:", e)
        time.sleep(SWEEP_EVERY)


@asynccontextmanager
async def lifespan(_):
    sweep()
    threading.Thread(target=sweeper, daemon=True).start()
    yield


app = FastAPI(title="Titulky", lifespan=lifespan)


# ---------- spracovanie na pozadí ----------
def probe_duration(path):
    out = subprocess.run(
        ["ffprobe", "-v", "error", "-show_entries", "format=duration",
         "-of", "default=nw=1:nk=1", str(path)], capture_output=True, text=True).stdout
    return float(out.strip() or 0)


def needs_preview(path):
    """Prehliadač neprehrá H.265, 10-bitové ani iné nezvyčajné video: na náhľad sa vyrobí H.264."""
    out = subprocess.run(
        ["ffprobe", "-v", "error", "-select_streams", "v:0", "-show_entries",
         "stream=codec_name,pix_fmt", "-of", "csv=p=0", str(path)],
        capture_output=True, text=True).stdout.strip().split(",")
    codec, pix = (out + ["", ""])[:2]
    return codec not in ("h264", "vp8", "vp9", "av1") or pix not in ("yuv420p", "yuvj420p")


def make_preview(d, source):
    subprocess.run(
        ["ffmpeg", "-y", "-loglevel", "error", "-i", source,
         "-vf", "scale='if(gt(iw,ih),-2,min(720,iw))':'if(gt(iw,ih),min(720,ih),-2)',format=yuv420p",
         "-c:v", "libx264", "-preset", "veryfast", "-crf", "26", "-c:a", "aac", "-b:a", "128k",
         "-movflags", "+faststart", "preview.mp4"],
        cwd=d, check=True, capture_output=True)


def run_transcribe(job_id, language):
    try:
        meta = load(job_id)
        src = DATA / job_id / meta["source"]
        duration = probe_duration(src)
        if duration <= 0:
            raise RuntimeError("Súbor sa nepodarilo prečítať ako video.")
        segments, lang = asr.transcribe(str(src), language, duration)
        preview = None
        if needs_preview(src):
            make_preview(DATA / job_id, meta["source"])
            preview = "preview.mp4"
        update(job_id, status="ready", segments=segments, duration=duration, language=lang,
               preview=preview)
    except HTTPException:
        pass  # medzitým zmazané
    except Exception as e:
        try:
            update(job_id, status="error", error=str(e)[:300])
        except HTTPException:
            pass


def run_export(job_id, style, aspect):
    try:
        meta = load(job_id)
        d = DATA / job_id

        def progress(p):
            try:
                update(job_id, progress=p)
            except HTTPException:
                pass

        out = render.render_video(d, meta["source"], meta["segments"], style, aspect,
                                  meta.get("duration"), progress)
        (d / "titulky.srt").write_text(render.to_srt(meta["segments"]), encoding="utf-8")
        (d / "titulky.vtt").write_text(render.to_vtt(meta["segments"]), encoding="utf-8")
        update(job_id, status="exported", progress=100, exported_at=time.time(),
               export={"video": out, "aspect": aspect})
    except HTTPException:
        pass
    except Exception as e:
        try:
            update(job_id, status="ready", error=f"Export zlyhal: {str(e)[:300]}")
        except HTTPException:
            pass


# ---------- API ----------
@app.get("/api/config")
def config():
    return {"fonts": FONTS, "aspects": list(render.ASPECTS), "retention_hours": RETENTION / 3600,
            "max_upload_mb": MAX_UPLOAD // (1024 * 1024)}


@app.post("/api/jobs")
async def create_job(file: UploadFile = File(...), language: str = Form("auto")):
    ext = Path(file.filename or "").suffix.lower()
    if ext not in EXTS:
        raise HTTPException(400, "Nepodporovaný formát. Použi MP4, MOV, M4V, WEBM, MKV alebo AVI.")
    if language not in ("auto", "sk", "en", "cs"):
        language = "auto"
    job_id = uuid.uuid4().hex
    d = DATA / job_id
    d.mkdir(parents=True)
    source = f"source{ext}"
    size = 0
    try:
        with open(d / source, "wb") as out:
            while chunk := await file.read(1024 * 1024):
                size += len(chunk)
                if size > MAX_UPLOAD:
                    raise HTTPException(413, f"Súbor je väčší ako {MAX_UPLOAD // (1024 * 1024)} MB.")
                out.write(chunk)
    except BaseException:
        shutil.rmtree(d, ignore_errors=True)
        raise
    save(job_id, {"status": "transcribing", "source": source, "created_at": time.time(),
                  "segments": []})
    pool.submit(run_transcribe, job_id, language)
    return {"id": job_id}


@app.get("/api/jobs/{job_id}")
def get_job(job_id: str):
    return public(job_id, load(job_id))


@app.get("/api/jobs/{job_id}/video")
def get_video(job_id: str):
    meta = load(job_id)
    return FileResponse(DATA / job_id / (meta.get("preview") or meta["source"]))


@app.put("/api/jobs/{job_id}/segments")
def put_segments(job_id: str, body: dict):
    meta = load(job_id)
    if meta["status"] not in ("ready", "exported"):
        raise HTTPException(409, "Titulky ešte nie sú hotové.")
    old = meta["segments"]
    texts = body.get("texts")
    if not isinstance(texts, list) or len(texts) != len(old):
        raise HTTPException(400, "Neplatné titulky.")
    for seg, t in zip(old, texts):
        seg["text"] = str(t)[:300]
    update(job_id, segments=old)
    return {"ok": True}


@app.post("/api/jobs/{job_id}/export")
def export(job_id: str, body: dict):
    meta = load(job_id)
    if meta["status"] not in ("ready", "exported"):
        raise HTTPException(409, "Titulky ešte nie sú hotové.")
    aspect = body.get("aspect", "9:16")
    if aspect not in render.ASPECTS:
        raise HTTPException(400, "Neplatný pomer strán.")
    style = render.clean_style(body.get("style"), FONTS)
    update(job_id, status="exporting", progress=0, error=None)
    pool.submit(run_export, job_id, style, aspect)
    return {"ok": True}


@app.get("/api/jobs/{job_id}/download/{kind}")
def download(job_id: str, kind: str):
    meta = load(job_id)
    files = {"mp4": ("out.mp4", "titulky.mp4", "video/mp4"),
             "srt": ("titulky.srt", "titulky.srt", "application/x-subrip"),
             "vtt": ("titulky.vtt", "titulky.vtt", "text/vtt")}
    if kind not in files or not meta.get("export"):
        raise HTTPException(404, "Export ešte nie je hotový.")
    name, dl, mime = files[kind]
    path = DATA / job_id / name
    if not path.is_file():
        raise HTTPException(404, "Súbor neexistuje.")
    return FileResponse(path, media_type=mime, filename=dl)


@app.delete("/api/jobs/{job_id}")
def delete_job(job_id: str):
    shutil.rmtree(job_dir(job_id), ignore_errors=True)
    return {"ok": True}


app.mount("/", StaticFiles(directory=BASE / "static", html=True), name="static")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host=os.environ.get("HOST", "127.0.0.1"),
                port=int(os.environ.get("PORT", "8000")))
