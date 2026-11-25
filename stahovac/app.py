"""Sťahovač videí — backend.

Z odkazu na TikTok, Instagram, YouTube alebo Facebook zistí, či sa za ním
nachádza video, ponúkne dostupné kvality (vrátane originálu bez prekódovania)
a stiahne zvolený súbor. Video sa nikdy neprekóduje – obraz a zvuk sa len
spoja do kontajnera (mp4, prípadne mkv/webm, ak to kodeky vyžadujú).

Spustenie:  python app.py           (http://localhost:8080)
Produkcia:  gunicorn -w 1 --threads 8 -b 0.0.0.0:8080 app:app
            (jeden worker – úlohy sa držia v pamäti procesu)
"""

from __future__ import annotations

import json
import os
import re
import shutil
import subprocess
import tempfile
import threading
import time
import uuid
from urllib.parse import urlparse

from flask import Flask, abort, jsonify, request, send_file, send_from_directory
import yt_dlp

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
STATIC_DIR = os.path.join(BASE_DIR, "static")
WORK_DIR = os.environ.get("STAHOVAC_TMP") or os.path.join(tempfile.gettempdir(), "stahovac")
JOB_TTL = int(os.environ.get("STAHOVAC_JOB_TTL", "1800"))          # s, potom sa súbor zmaže
MAX_ACTIVE = int(os.environ.get("STAHOVAC_MAX_ACTIVE", "3"))       # súbežné sťahovania
MAX_FILESIZE = int(os.environ.get("STAHOVAC_MAX_MB", "2048")) * 1024 * 1024

os.makedirs(WORK_DIR, exist_ok=True)

PLATFORMS = {
    "youtube": ("youtube.com", "youtu.be", "youtube-nocookie.com"),
    "tiktok": ("tiktok.com",),
    "instagram": ("instagram.com", "instagr.am"),
    "facebook": ("facebook.com", "fb.watch", "fb.com"),
}
# Ďalšie povolené domény (napr. na testy): STAHOVAC_EXTRA_HOSTS="example.com,localhost"
EXTRA_HOSTS = tuple(h.strip().lower() for h in os.environ.get("STAHOVAC_EXTRA_HOSTS", "").split(",") if h.strip())

app = Flask(__name__, static_folder=None)


# --------------------------------------------------------------------------- pomocné

class UserError(Exception):
    """Chyba, ktorú zobrazíme používateľovi."""

    def __init__(self, message: str, status: int = 400):
        super().__init__(message)
        self.status = status


def detect_platform(url: str) -> str:
    try:
        parsed = urlparse(url.strip())
    except ValueError:
        raise UserError("Toto nevyzerá ako platný odkaz.")
    if parsed.scheme not in ("http", "https") or not parsed.hostname:
        raise UserError("Vložte celý odkaz začínajúci na https://")
    host = parsed.hostname.lower()

    def matches(domain: str) -> bool:
        return host == domain or host.endswith("." + domain)

    for name, domains in PLATFORMS.items():
        if any(matches(d) for d in domains):
            return name
    if any(matches(d) for d in EXTRA_HOSTS):
        return "other"
    raise UserError("Podporované sú odkazy z TikToku, Instagramu, YouTube a Facebooku.")


def base_opts() -> dict:
    return {
        "quiet": True,
        "no_warnings": True,
        "noplaylist": True,
        "noprogress": True,
        "socket_timeout": 20,
        "retries": 3,
        "geo_bypass": True,
        "http_headers": {"Accept-Language": "sk,en;q=0.8"},
    }


def friendly_error(exc: Exception) -> str:
    text = re.sub(r"\x1b\[[0-9;]*m", "", str(exc))
    low = text.lower()
    if "private" in low or "login" in low or "log in" in low or "cookies" in low:
        return "Video je súkromné alebo vyžaduje prihlásenie, nedá sa stiahnuť."
    if "unsupported url" in low:
        return "Na tomto odkaze sa nenašlo video."
    if "not available" in low or "unavailable" in low or "removed" in low or "404" in low:
        return "Video už nie je dostupné (bolo zmazané alebo je obmedzené)."
    if "no video formats" in low or "no formats" in low:
        return "Za odkazom sa nenachádza video (možno ide o fotku)."
    if "timed out" in low or "timeout" in low:
        return "Platforma neodpovedala včas. Skúste to znova."
    if "max-filesize" in low or "larger than max" in low:
        return "Video je príliš veľké."
    return "Video sa nepodarilo načítať. Skontrolujte odkaz a skúste znova."


def has_video(fmt: dict) -> bool:
    vcodec = fmt.get("vcodec")
    if vcodec == "none":
        return False
    if vcodec is None and fmt.get("height") is None and fmt.get("acodec") not in (None, "none"):
        return False
    # obrázkové „formáty“ (storyboardy) nechceme
    if fmt.get("format_note") == "storyboard" or (fmt.get("ext") in ("mhtml", "jpg", "png", "webp")):
        return False
    return True


def has_audio(fmt: dict) -> bool:
    acodec = fmt.get("acodec")
    return acodec not in ("none",) and not (acodec is None and fmt.get("vcodec") not in (None, "none") and fmt.get("height"))


def fmt_size(fmt: dict) -> int | None:
    size = fmt.get("filesize") or fmt.get("filesize_approx")
    if size:
        return int(size)
    tbr, dur = fmt.get("tbr"), fmt.get("_duration")
    if tbr and dur:
        return int(tbr * 1000 / 8 * dur)
    return None


def short_codec(codec: str | None) -> str:
    if not codec or codec == "none":
        return ""
    c = codec.lower()
    for prefix, label in (("avc", "H.264"), ("h264", "H.264"), ("hev", "H.265"), ("hvc", "H.265"),
                          ("h265", "H.265"), ("vp09", "VP9"), ("vp9", "VP9"), ("av01", "AV1"),
                          ("vp8", "VP8"), ("bytevc1", "H.265")):
        if c.startswith(prefix):
            return label
    return codec.split(".")[0].upper()


def pick_video_entry(info: dict) -> dict:
    """Pri kolekciách (napr. Instagram carousel) vyber prvé video."""
    if info.get("_type") in ("playlist", "multi_video") or info.get("entries"):
        for entry in info.get("entries") or []:
            if entry and any(has_video(f) for f in (entry.get("formats") or [entry])):
                return entry
        raise UserError("Za odkazom sa nenachádza video (možno ide o fotku).")
    return info


def build_options(info: dict) -> list[dict]:
    """Zoznam ponúkaných kvalít. Prvá je vždy originál (najvyššia kvalita)."""
    duration = info.get("duration")
    formats = info.get("formats") or [info]
    for f in formats:
        f["_duration"] = duration

    videos = [f for f in formats if has_video(f) and (f.get("url") or f.get("fragments") or f.get("manifest_url"))]
    if not videos:
        raise UserError("Za odkazom sa nenachádza video (možno ide o fotku).")
    audios = [f for f in formats if not has_video(f) and has_audio(f)]
    best_audio = max(audios, key=lambda f: (f.get("abr") or f.get("tbr") or 0), default=None)
    best_audio_size = fmt_size(best_audio) if best_audio else 0

    def rank(f: dict):
        return (f.get("height") or 0, f.get("width") or 0, f.get("fps") or 0, f.get("tbr") or 0,
                1 if has_audio(f) else 0)

    def describe(f: dict) -> dict:
        muxed = has_audio(f)
        size = fmt_size(f)
        if size and not muxed and best_audio_size:
            size += best_audio_size
        w, h = f.get("width"), f.get("height")
        return {
            "width": w,
            "height": h,
            "fps": round(f["fps"]) if f.get("fps") else None,
            "codec": short_codec(f.get("vcodec")),
            "size": size,
            "spec": f["format_id"] if (muxed or not best_audio) else f"{f['format_id']}+{best_audio['format_id']}",
        }

    best = max(videos, key=rank)
    original = describe(best)
    original.update(id="original", label="Originál", hint="Najvyššia kvalita, bez prekódovania",
                    spec="bv*+ba/b")
    options = [original]

    # jedna možnosť na každé nižšie rozlíšenie (podľa kratšej strany – funguje aj pre video na výšku)
    by_res: dict[int, dict] = {}
    for f in videos:
        if not f.get("height"):
            continue
        key = min(f["height"], f.get("width") or f["height"])
        if key not in by_res or rank(f) > rank(by_res[key]):
            by_res[key] = f
    best_key = min(best.get("height") or 0, best.get("width") or best.get("height") or 0)
    for key in sorted(by_res, reverse=True):
        if key >= best_key:
            continue
        opt = describe(by_res[key])
        opt.update(id=f"r{key}", label=f"{key}p", hint="Menší súbor")
        options.append(opt)
        if len(options) >= 6:
            break

    # MP4 s H.264 – prehrá každý telefón (pri YouTube býva originál vo VP9/AV1)
    if original["codec"] not in ("H.264", "") and any(
            (f.get("vcodec") or "").startswith("avc") for f in videos):
        avc = max((f for f in videos if (f.get("vcodec") or "").startswith("avc")), key=rank)
        opt = describe(avc)
        opt.update(id="compat", label="MP4 (H.264)", hint="Prehrá každý telefón",
                   spec="bv*[vcodec^=avc]+ba[ext=m4a]/bv*[vcodec^=avc]+ba/b[ext=mp4]")
        options.append(opt)

    options.append({"id": "audio", "label": "Iba zvuk", "hint": "Originálna zvuková stopa",
                    "spec": "ba/b", "size": best_audio_size or None, "audio": True,
                    "codec": (best_audio or {}).get("acodec", "").split(".")[0].upper() if best_audio else ""})
    return options


# --------------------------------------------------------------------------- úlohy

JOBS: dict[str, dict] = {}
JOBS_LOCK = threading.Lock()


def active_jobs() -> int:
    return sum(1 for j in JOBS.values() if j["status"] in ("queued", "downloading", "processing"))


MP4_VIDEO = {"h264", "hevc", "av1", "vp9", "mpeg4"}
MP4_AUDIO = {"aac", "mp3", "opus", "ac3", "eac3", "flac", "alac"}


def remux_to_mp4(path: str) -> str:
    """MKV/WebM prebalí do MP4 bez prekódovania (-c copy), ak to kodeky dovolia.

    Telefóny (najmä iPhone) MKV neotvoria, MP4 áno. Kvalita sa nemení.
    """
    if path.lower().endswith(".mp4") or not shutil.which("ffmpeg"):
        return path
    try:
        probe = subprocess.run(
            ["ffprobe", "-v", "error", "-show_entries", "stream=codec_type,codec_name", "-of", "json", path],
            capture_output=True, text=True, timeout=60, check=True)
        streams = json.loads(probe.stdout).get("streams", [])
        vcodecs = {s["codec_name"] for s in streams if s.get("codec_type") == "video"}
        acodecs = {s["codec_name"] for s in streams if s.get("codec_type") == "audio"}
        if not vcodecs or not vcodecs <= MP4_VIDEO or not acodecs <= MP4_AUDIO:
            return path
        target = os.path.splitext(path)[0] + ".mp4"
        cmd = ["ffmpeg", "-y", "-v", "error", "-i", path, "-map", "0:v", "-map", "0:a?", "-c", "copy",
               "-movflags", "+faststart"]
        if "hevc" in vcodecs:
            cmd += ["-tag:v", "hvc1"]  # aby HEVC prehral aj iPhone
        subprocess.run(cmd + [target], capture_output=True, timeout=600, check=True)
        os.remove(path)
        return target
    except Exception as exc:  # noqa: BLE001 – pri neúspechu ponecháme pôvodný súbor
        app.logger.info("remux skipped: %s", exc)
        return path


def run_job(job_id: str, url: str, spec: str, audio_only: bool) -> None:
    job = JOBS[job_id]
    out_dir = os.path.join(WORK_DIR, job_id)
    os.makedirs(out_dir, exist_ok=True)
    streams_seen: list[str] = []

    def on_progress(d: dict) -> None:
        if job.get("cancel"):
            raise yt_dlp.utils.DownloadCancelled("cancelled")
        name = d.get("filename") or d.get("tmpfilename") or ""
        if name and name not in streams_seen:
            streams_seen.append(name)
        if d["status"] == "downloading":
            total = d.get("total_bytes") or d.get("total_bytes_estimate")
            done = d.get("downloaded_bytes") or 0
            job.update(status="downloading", stream=len(streams_seen),
                       percent=round(done / total * 100, 1) if total else None,
                       downloaded=done, total=total, speed=d.get("speed"), eta=d.get("eta"))
        elif d["status"] == "finished":
            job.update(percent=100)

    def on_post(d: dict) -> None:
        if d["status"] == "started":
            job.update(status="processing")

    opts = base_opts()
    opts.update(
        format=spec,
        outtmpl={"default": os.path.join(out_dir, "%(title).80B [%(id)s].%(ext)s")},
        merge_output_format="mp4/mkv",   # mp4, ak to kodeky dovolia, inak mkv – vždy bez prekódovania
        progress_hooks=[on_progress],
        postprocessor_hooks=[on_post],
        max_filesize=MAX_FILESIZE,
        concurrent_fragment_downloads=4,
        windowsfilenames=True,
    )
    if audio_only:
        opts["merge_output_format"] = None

    try:
        with yt_dlp.YoutubeDL(opts) as ydl:
            info = ydl.extract_info(url, download=True)
            info = pick_video_entry(info) if not audio_only else info
        files = [f for f in os.listdir(out_dir) if not f.endswith((".part", ".ytdl", ".tmp"))]
        if not files:
            raise UserError("Video sa nepodarilo stiahnuť.")
        path = max((os.path.join(out_dir, f) for f in files), key=os.path.getsize)
        if not audio_only:
            job.update(status="processing")
            path = remux_to_mp4(path)
        job.update(status="done", percent=100, path=path, filename=os.path.basename(path),
                   size=os.path.getsize(path), finished=time.time())
    except UserError as exc:
        job.update(status="error", error=str(exc), finished=time.time())
    except yt_dlp.utils.DownloadCancelled:
        job.update(status="cancelled", finished=time.time())
        shutil.rmtree(out_dir, ignore_errors=True)
    except Exception as exc:  # noqa: BLE001 – chybu prekladáme pre používateľa
        app.logger.warning("download failed: %s", exc)
        job.update(status="error", error=friendly_error(exc), finished=time.time())
        shutil.rmtree(out_dir, ignore_errors=True)


def cleanup_loop() -> None:
    while True:
        time.sleep(60)
        now = time.time()
        with JOBS_LOCK:
            for job_id, job in list(JOBS.items()):
                if job.get("finished") and now - job["finished"] > JOB_TTL:
                    shutil.rmtree(os.path.join(WORK_DIR, job_id), ignore_errors=True)
                    JOBS.pop(job_id, None)


threading.Thread(target=cleanup_loop, daemon=True).start()


# --------------------------------------------------------------------------- API

@app.errorhandler(UserError)
def handle_user_error(exc: UserError):
    return jsonify(error=str(exc)), exc.status


@app.post("/api/info")
def api_info():
    url = ((request.get_json(silent=True) or {}).get("url") or "").strip()
    if not url:
        raise UserError("Vložte odkaz na video.")
    platform = detect_platform(url)
    opts = base_opts()
    opts["skip_download"] = True
    try:
        with yt_dlp.YoutubeDL(opts) as ydl:
            info = ydl.extract_info(url, download=False)
    except Exception as exc:  # noqa: BLE001
        app.logger.info("info failed: %s", exc)
        raise UserError(friendly_error(exc), 422)
    info = pick_video_entry(info)
    options = build_options(info)
    if info.get("is_live"):
        raise UserError("Živé vysielanie sa nedá stiahnuť.")
    thumb = info.get("thumbnail")
    if not thumb and info.get("thumbnails"):
        thumb = info["thumbnails"][-1].get("url")
    return jsonify(
        platform=platform,
        url=info.get("webpage_url") or url,
        title=info.get("title") or info.get("description") or "Video",
        uploader=info.get("uploader") or info.get("channel") or info.get("creator"),
        duration=info.get("duration"),
        thumbnail=thumb,
        width=options[0].get("width"),
        height=options[0].get("height"),
        options=options,
    )


@app.post("/api/download")
def api_download():
    data = request.get_json(silent=True) or {}
    url = (data.get("url") or "").strip()
    spec = (data.get("spec") or "bv*+ba/b").strip()
    detect_platform(url)
    if len(spec) > 200 or not re.fullmatch(r"[\w\-+/*\[\]^=<>.,: ]+", spec):
        raise UserError("Neplatná kvalita.")
    with JOBS_LOCK:
        if active_jobs() >= MAX_ACTIVE:
            raise UserError("Server práve sťahuje iné videá. Skúste to o chvíľu.", 429)
        job_id = uuid.uuid4().hex
        JOBS[job_id] = {"status": "queued", "percent": 0, "created": time.time()}
    threading.Thread(target=run_job, args=(job_id, url, spec, bool(data.get("audio"))), daemon=True).start()
    return jsonify(id=job_id)


def get_job(job_id: str) -> dict:
    job = JOBS.get(job_id) if re.fullmatch(r"[0-9a-f]{32}", job_id) else None
    if not job:
        abort(404)
    return job


@app.get("/api/jobs/<job_id>")
def api_job(job_id: str):
    job = get_job(job_id)
    public = {k: job.get(k) for k in ("status", "percent", "stream", "downloaded", "total",
                                      "speed", "eta", "filename", "size", "error")}
    return jsonify(public)


@app.delete("/api/jobs/<job_id>")
def api_cancel(job_id: str):
    get_job(job_id)["cancel"] = True
    return jsonify(ok=True)


@app.get("/api/jobs/<job_id>/file")
def api_file(job_id: str):
    job = get_job(job_id)
    if job["status"] != "done" or not os.path.exists(job.get("path", "")):
        abort(404)
    return send_file(job["path"], as_attachment=True, download_name=job["filename"],
                     conditional=True, max_age=0)


# --------------------------------------------------------------------------- frontend

@app.get("/")
def index():
    return send_from_directory(STATIC_DIR, "index.html")


@app.get("/<path:name>")
def static_files(name: str):
    return send_from_directory(STATIC_DIR, name)


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=int(os.environ.get("PORT", "8080")), threaded=True)
