"""Titulky: SRT, VTT, ASS a vypálenie do videa cez ffmpeg."""
import re
import subprocess

ASPECTS = {"9:16": (1080, 1920), "1:1": (1080, 1080), "16:9": (1920, 1080)}
POSITIONS = {"top": 8, "middle": 5, "bottom": 2}  # ASS zarovnanie
HEX = re.compile(r"^#[0-9a-fA-F]{6}$")


def clean_style(raw, fonts):
    """Overí nastavenie štýlu od používateľa a doplní predvolené hodnoty."""
    raw = raw or {}

    def color(key, default):
        v = raw.get(key, default)
        return v if isinstance(v, str) and HEX.match(v) else default

    def num(key, default, lo, hi):
        try:
            v = float(raw.get(key, default))
        except (TypeError, ValueError):
            v = default
        return max(lo, min(hi, v))

    font = raw.get("font")
    if font not in fonts:
        font = fonts[0]
    pos = raw.get("position")
    return {
        "font": font,
        "color": color("color", "#ffffff"),
        "bg": color("bg", "#000000"),
        "bgOpacity": num("bgOpacity", 60, 0, 100),
        "size": num("size", 6.5, 3, 12),   # % kratšej strany videa
        "position": pos if pos in POSITIONS else "bottom",
        "bold": bool(raw.get("bold", True)),
    }


def _ts(sec, sep):
    ms = int(round(sec * 1000))
    h, ms = divmod(ms, 3600000)
    m, ms = divmod(ms, 60000)
    s, ms = divmod(ms, 1000)
    return f"{h:02d}:{m:02d}:{s:02d}{sep}{ms:03d}"


def to_srt(segments):
    out = []
    for i, s in enumerate(segments, 1):
        out.append(f"{i}\n{_ts(s['start'], ',')} --> {_ts(s['end'], ',')}\n{s['text']}\n")
    return "\n".join(out)


def to_vtt(segments):
    out = ["WEBVTT\n"]
    for s in segments:
        out.append(f"{_ts(s['start'], '.')} --> {_ts(s['end'], '.')}\n{s['text']}\n")
    return "\n".join(out)


def _ass_color(hex_color, opacity_pct):
    """#RRGGBB + nepriehľadnosť v % -> &HAABBGGRR."""
    r, g, b = hex_color[1:3], hex_color[3:5], hex_color[5:7]
    alpha = round(255 * (1 - opacity_pct / 100))
    return f"&H{alpha:02X}{b}{g}{r}".upper()


def _ass_time(sec):
    cs = int(round(sec * 100))
    h, cs = divmod(cs, 360000)
    m, cs = divmod(cs, 6000)
    s, cs = divmod(cs, 100)
    return f"{h}:{m:02d}:{s:02d}.{cs:02d}"


def _ass_text(text):
    text = text.replace("\\", "/").replace("{", "(").replace("}", ")")
    return text.replace("\r", "").replace("\n", "\\N")


def build_ass(segments, style, width, height):
    short = min(width, height)
    size = round(short * style["size"] / 100)
    box = style["bgOpacity"] > 0
    margin_v = round(height * 0.12) if style["position"] != "middle" else 0
    margin_h = round(width * 0.08)
    if box:
        border, outline, shadow = 3, round(size * 0.25), 0
        outline_col = _ass_color(style["bg"], style["bgOpacity"])
    else:  # bez podkladu sa text čitateľne obkreslí
        border, outline, shadow = 1, max(2, round(size * 0.07)), 0
        outline_col = "&H00000000"
    head = (
        "[Script Info]\nScriptType: v4.00+\n"
        f"PlayResX: {width}\nPlayResY: {height}\nWrapStyle: 0\nScaledBorderAndShadow: yes\n\n"
        "[V4+ Styles]\n"
        "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, "
        "BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, "
        "BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding\n"
        f"Style: Default,{style['font']},{size},{_ass_color(style['color'], 100)},"
        f"&H000000FF,{outline_col},{outline_col},{-1 if style['bold'] else 0},0,0,0,100,100,0,0,"
        f"{border},{outline},{shadow},{POSITIONS[style['position']]},{margin_h},{margin_h},{margin_v},1\n\n"
        "[Events]\nFormat: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n"
    )
    lines = [
        f"Dialogue: 0,{_ass_time(s['start'])},{_ass_time(s['end'])},Default,,0,0,0,,{_ass_text(s['text'])}"
        for s in segments if s["text"].strip()
    ]
    return head + "\n".join(lines) + "\n"


def render_video(job_dir, source_name, segments, style, aspect, duration, on_progress=None):
    """Vypáli titulky do videa. Vráti názov výstupného súboru v job_dir."""
    width, height = ASPECTS[aspect]
    (job_dir / "subs.ass").write_text(build_ass(segments, style, width, height), encoding="utf-8")
    vf = (f"scale={width}:{height}:force_original_aspect_ratio=increase,"
          f"crop={width}:{height},setsar=1,ass=subs.ass")
    cmd = ["ffmpeg", "-y", "-loglevel", "error", "-i", source_name, "-vf", vf,
           "-c:v", "libx264", "-preset", "veryfast", "-crf", "21", "-pix_fmt", "yuv420p",
           "-c:a", "aac", "-b:a", "160k", "-movflags", "+faststart",
           "-progress", "pipe:1", "-nostats", "out.mp4"]
    proc = subprocess.Popen(cmd, cwd=job_dir, stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE, text=True)
    for line in proc.stdout:
        if line.startswith("out_time_us=") and duration and on_progress:
            try:
                on_progress(min(99, int(int(line.split("=")[1]) / 1e6 / duration * 100)))
            except ValueError:
                pass
    err = proc.stderr.read()
    if proc.wait() != 0:
        raise RuntimeError(err.strip()[-400:] or "ffmpeg zlyhal")
    return "out.mp4"
