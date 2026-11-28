"""Rýchle testy bez modelu a bez ffmpeg: python3 tests/test_basic.py"""
import json, os, sys, tempfile, time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.parent))
os.environ["TITULKY_DATA"] = tempfile.mkdtemp()
os.environ["TITULKY_RETENTION_HOURS"] = "24"

import asr, render, server  # noqa: E402


def test_group_words():
    words = [(0, .4, "Ahoj"), (.4, .8, "svet."), (3, 3.4, "Druhá"), (3.4, 3.9, "veta")]
    segs = asr.group_words(words)
    assert [s["text"] for s in segs] == ["Ahoj svet.", "Druhá veta"]
    assert segs[0]["end"] <= segs[1]["start"]


def test_srt_vtt():
    segs = [{"start": 1.5, "end": 3.25, "text": "Ahoj"}]
    assert "00:00:01,500 --> 00:00:03,250" in render.to_srt(segs)
    assert render.to_vtt(segs).startswith("WEBVTT") and "00:00:01.500" in render.to_vtt(segs)


def test_style_validation():
    s = render.clean_style({"color": "red", "font": "Nope", "bgOpacity": 999, "position": "x"}, ["Inter"])
    assert s["color"] == "#ffffff" and s["font"] == "Inter" and s["bgOpacity"] == 100 and s["position"] == "bottom"


def test_ass_colors():
    assert render._ass_color("#ff8800", 100) == "&H000088FF"   # BGR, plná nepriehľadnosť
    assert render._ass_color("#000000", 0) == "&HFF000000"     # úplne priehľadná
    ass = render.build_ass([{"start": 0, "end": 1, "text": "a{b}\nc"}],
                           render.clean_style({}, ["Inter"]), 1080, 1920)
    assert "a(b)\\Nc" in ass and "PlayResY: 1920" in ass


def test_retention_sweep():
    now = time.time()
    for name, created, exported, gone in [("a" * 32, now, None, False),
                                          ("b" * 32, now - 25 * 3600, None, True),
                                          ("c" * 32, now - 30 * 3600, now - 1 * 3600, False),
                                          ("d" * 32, now - 30 * 3600, now - 25 * 3600, True)]:
        d = server.DATA / name
        d.mkdir()
        meta = {"status": "exported", "created_at": created, "segments": []}
        if exported:
            meta["exported_at"] = exported
        (d / "meta.json").write_text(json.dumps(meta))
        (d / "source.mp4").write_bytes(b"x")
    server.sweep()
    for name, gone in [("a" * 32, False), ("b" * 32, True), ("c" * 32, False), ("d" * 32, True)]:
        assert (not (server.DATA / name).exists()) == gone, name


if __name__ == "__main__":
    for n, f in list(globals().items()):
        if n.startswith("test_"):
            f(); print("ok", n)
