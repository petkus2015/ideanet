#!/usr/bin/env python3
"""Stiahne aktuálnu polohu a meškanie vlakov z mapa.zsr.sk a uloží ich
do vlaky/data/trains.json (+ 24 h históriu KPI do history.json).

Použitie:
  python3 scripts/fetch_zsr.py            # jedno stiahnutie
  python3 scripts/fetch_zsr.py --loop     # opakuje každých 5 minút
  python3 scripts/fetch_zsr.py --out DIR  # iný výstupný priečinok

Zdroj je nedokumentované API ŽSR — volajte ho rozumne (max. raz za pár minút).
Iba štandardná knižnica Pythonu.
"""
import argparse
import http.cookiejar
import json
import re
import sys
import time
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

BASE = "https://mapa.zsr.sk"
API = BASE + "/api/action"
INTERVAL = 300
HISTORY_LEN = 288  # 24 h po 5 min
# druhy vlakov — rovnaké členenie ako vo vlaky/app.js
GROUPS = {
    "reg": {"OS", "REX", "ZR", "SP", "MOS"},
    "r": {"R"},
    "ex": {"EX", "IC", "SC", "RJ", "LE"},
    "int": {"EC", "EN", "RJX", "ICE", "NJ"},
}
UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36"


def make_opener():
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    opener.addheaders = [
        ("User-Agent", UA),
        ("Accept", "*/*"),
        ("Accept-Language", "sk,en;q=0.8"),
        ("Origin", BASE),
        ("Referer", BASE + "/index.aspx"),
        ("X-Requested-With", "XMLHttpRequest"),
    ]
    try:  # session cookies
        opener.open(BASE + "/index.aspx", timeout=30).read()
    except Exception as e:
        print(f"varovanie: index.aspx zlyhal: {e}", file=sys.stderr)
    return opener


def fetch_raw(attempts=3):
    last = None
    for i in range(attempts):
        try:
            opener = make_opener()
            body = urllib.parse.urlencode({"action": "gtall"}).encode()
            req = urllib.request.Request(API, data=body, headers={
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"})
            with opener.open(req, timeout=30) as r:
                data = json.loads(r.read().decode("utf-8"))
            if isinstance(data, list):
                return data
            last = f"neočakávaná odpoveď: {type(data).__name__}"
        except Exception as e:
            last = e
        time.sleep(3 * (i + 1))
    raise RuntimeError(f"API ŽSR nedostupné: {last}")


def to_int(v):
    if isinstance(v, (int, float)):
        return int(v)
    m = re.search(r"-?\d+", str(v or ""))
    return int(m.group()) if m else None


def coords(pos):
    """Position býva [lat, lon]; ak je prehodená, otočíme ju."""
    try:
        a, b = float(pos[0]), float(pos[1])
    except (TypeError, ValueError, IndexError):
        return None, None
    if 47 <= a <= 50 and 16 <= b <= 23:
        return round(a, 5), round(b, 5)
    if 47 <= b <= 50 and 16 <= a <= 23:
        return round(b, 5), round(a, 5)
    return None, None


def route_path(trasa, limit=60):
    """Trasa vlaku ako zoznam [lat, lon] v poradí jazdy (zjednodušená na max. `limit` bodov).

    Formát poľa Trasa nie je zdokumentovaný, preto berieme všetky dvojice čísel,
    ktoré vyzerajú ako súradnice na Slovensku, v poradí, v akom prídu.
    """
    pts = []

    def walk(x):
        if isinstance(x, str) and x[:1] in "[{":
            try:
                x = json.loads(x)
            except ValueError:
                return
        if isinstance(x, dict):
            for v in x.values():
                walk(v)
        elif isinstance(x, (list, tuple)):
            if len(x) == 2 and all(isinstance(v, (int, float)) for v in x):
                lat, lon = coords(x)
                if lat is not None and (not pts or pts[-1] != [round(lat, 3), round(lon, 3)]):
                    pts.append([round(lat, 3), round(lon, 3)])
            else:
                for v in x:
                    walk(v)

    walk(trasa)
    if len(pts) > limit:
        step = (len(pts) - 1) / (limit - 1)
        pts = [pts[round(i * step)] for i in range(limit)]
    return pts


def clean(s):
    return re.sub(r"\s+", " ", str(s)).strip() if s not in (None, "") else ""


def normalize(t):
    delay = to_int(t.get("Meska"))
    if delay is None:
        delay = to_int(t.get("MeskaText")) or 0
    lat, lon = coords(t.get("Position"))
    typ, num = clean(t.get("TypVlaku")), clean(t.get("CisloVlaku"))
    return {
        "id": f"{typ}{num}" or clean(t.get("Nazov")),
        "type": typ,
        "number": num,
        "name": clean(t.get("NazovVlaku")),
        "route": clean(t.get("Nazov")),
        "carrier": clean(t.get("Dopravca")),
        "delay": max(delay, 0) if delay is not None else 0,
        "delayText": clean(t.get("MeskaText")),
        "info": clean(t.get("InfoZoStanice")),
        "next": clean(t.get("NextStation")),
        "lat": lat,
        "lon": lon,
        "disruption": clean(t.get("Narusenie")),
        "note": clean(t.get("Poznamka")),
        "nad": [x for x in (clean(t.get("NadOd")), clean(t.get("NadDo"))) if x],
        "path": route_path(t.get("Trasa")),
    }


def group_of(t):
    typ = t["type"].upper()
    return next((g for g, types in GROUPS.items() if typ in types), "other")


def stats(trains):
    n = len(trains)
    d = [t["delay"] for t in trains]
    return {
        "n": n,
        "avg": round(sum(d) / n, 1) if n else 0,
        "onTime": round(100 * sum(1 for x in d if x <= 5) / n, 1) if n else 0,
        "over15": sum(1 for x in d if x > 15),
    }


def summary(trains, ts):
    s = {"t": ts, **stats(trains)}
    s["g"] = {g: stats([t for t in trains if group_of(t) == g]) for g in GROUPS}
    return s


def write_json(path, obj):
    tmp = path.with_suffix(".tmp")
    tmp.write_text(json.dumps(obj, ensure_ascii=False, separators=(",", ":")), "utf-8")
    tmp.replace(path)


def run_once(out: Path):
    out.mkdir(parents=True, exist_ok=True)
    raw = fetch_raw()
    trains = sorted((normalize(t) for t in raw), key=lambda t: -t["delay"])
    ts = datetime.now(timezone.utc).isoformat(timespec="seconds")
    write_json(out / "trains.json", {"updated": ts, "source": API, "trains": trains})

    hist_path = out / "history.json"
    try:
        hist = json.loads(hist_path.read_text("utf-8"))
        if not isinstance(hist, list):
            hist = []
    except (OSError, ValueError):
        hist = []
    hist = (hist + [summary(trains, ts)])[-HISTORY_LEN:]
    write_json(hist_path, hist)
    print(f"{ts}: {len(trains)} vlakov uložených do {out}")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--out", default=str(Path(__file__).resolve().parent.parent / "vlaky" / "data"))
    ap.add_argument("--loop", action="store_true", help=f"opakovať každých {INTERVAL} s")
    args = ap.parse_args()
    out = Path(args.out)
    if not args.loop:
        run_once(out)
        return
    while True:
        try:
            run_once(out)
        except Exception as e:
            print(f"chyba: {e}", file=sys.stderr)
        time.sleep(INTERVAL)


if __name__ == "__main__":
    main()
