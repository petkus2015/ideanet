"""Rozpoznanie reči (faster-whisper) a zloženie slov do titulkov."""
import os
import threading

MAX_CHARS = 32      # najviac znakov v jednom titulku
MAX_SECONDS = 4.0   # najdlhšie zobrazenie jedného titulku
GAP_SECONDS = 0.8   # pauza, pri ktorej sa titulok láme

_model = None
_model_lock = threading.Lock()


def _get_model():
    global _model
    with _model_lock:
        if _model is None:
            from faster_whisper import WhisperModel
            name = os.environ.get("TITULKY_MODEL", "small")
            _model = WhisperModel(name, device="cpu", compute_type="int8")
        return _model


def group_words(words):
    """words: zoznam (start, koniec, text). Vráti zoznam titulkov."""
    segments, cur = [], []

    def flush():
        if cur:
            segments.append({"start": cur[0][0], "end": cur[-1][1],
                             "text": " ".join(w[2] for w in cur)})
            cur.clear()

    for start, end, text in words:
        if not text:
            continue
        if cur:
            chars = sum(len(w[2]) + 1 for w in cur) + len(text)
            too_long = chars > MAX_CHARS or end - cur[0][0] > MAX_SECONDS
            gap = start - cur[-1][1] > GAP_SECONDS
            if too_long or gap:
                flush()
        cur.append((start, end, text))
        if text[-1] in ".!?…":
            flush()
    flush()

    for i, seg in enumerate(segments):  # žiadne prekrývanie titulkov
        if i + 1 < len(segments):
            seg["end"] = min(seg["end"], segments[i + 1]["start"])
        seg["end"] = max(seg["end"], seg["start"] + 0.3)
        seg["start"], seg["end"] = round(seg["start"], 2), round(seg["end"], 2)
    return segments


def mock_segments(duration):
    """Ukážkové titulky na vyskúšanie bez modelu (TITULKY_ASR=mock)."""
    out, t, i = [], 0.0, 1
    while t < duration - 0.5:
        out.append({"start": round(t, 2), "end": round(min(t + 1.8, duration), 2),
                    "text": f"Ukážkový titulok číslo {i}"})
        t += 2.0
        i += 1
    return out


def transcribe(path, language, duration):
    """Vráti (titulky, zistený jazyk)."""
    if os.environ.get("TITULKY_ASR") == "mock":
        return mock_segments(duration), language or "sk"
    model = _get_model()
    lang = None if language in (None, "", "auto") else language
    segs, info = model.transcribe(path, language=lang, word_timestamps=True,
                                  vad_filter=True)
    words = []
    for s in segs:
        for w in (s.words or []):
            words.append((w.start, w.end, w.word.strip()))
    return group_words(words), info.language
