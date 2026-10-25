#!/usr/bin/env python3
"""Stiahne aktuálnu polohu a meškanie vlakov z mapa.zsr.sk a uloží ich
do vlaky/data/trains.json (+ 24 h históriu KPI do history.json).

Použitie:
  python3 scripts/fetch_zsr.py            # jedno stiahnutie
  python3 scripts/fetch_zsr.py --loop     # opakuje každé 2 minúty
  python3 scripts/fetch_zsr.py --out DIR  # iný výstupný priečinok

Raz denne zostaví aj cestovný poriadok na dnes (vlaky/data/timetable.json)
z otvorených GTFS dát ŽSR (CC0). Z neho stránka skladá tabuľu odchodov a príchodov
stanice a pridáva k spojom aktuálne meškanie podľa čísla vlaku.

Zdroj polohy je nedokumentované API ŽSR — volajte ho rozumne (max. raz za pár minút).
Iba štandardná knižnica Pythonu.
"""
import argparse
import csv
import http.cookiejar
import io
import json
import re
import zipfile
import sys
import time
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path
from zoneinfo import ZoneInfo

BASE = "https://mapa.zsr.sk"
API = BASE + "/api/action"
GTFS_URL = "https://www.zsr.sk/files/pre-cestujucich/cestovny-poriadok/gtfs/gtfs.zip"
TZ = ZoneInfo("Europe/Bratislava")
INTERVAL = 120
HISTORY_LEN = 720  # 24 h po 2 min
TIMETABLE_FORMAT = 2  # 2 = stanice s kódmi
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


def gtfs_minutes(value):
    """'HH:MM:SS' (aj 25:10:00 po polnoci) -> minúty od polnoci."""
    m = re.match(r"\s*(\d+):(\d\d)", value or "")
    return int(m.group(1)) * 60 + int(m.group(2)) if m else None


def train_ident(trip, route):
    """Typ, číslo a názov vlaku z GTFS (napr. 'Ex 612 Spiš')."""
    for text in (trip.get("trip_short_name"), route.get("route_short_name"), trip.get("trip_headsign")):
        m = re.match(r"\s*([A-Za-z]{1,4})\s*(\d{1,5})\b\s*(.*)", text or "")
        if m:
            return m.group(1), m.group(2), clean(m.group(3))
    num = re.search(r"\d+", trip.get("trip_short_name") or "")
    typ = re.sub(r"[^A-Za-z]", "", route.get("route_short_name") or "")[:4]
    return typ, num.group() if num else "", ""


def build_timetable(out_path, url=GTFS_URL):
    """Cestovný poriadok na dnes vo formáte, ktorý číta vlaky/app.js:
    {"date", "stations": [[názov, lat, lon, [kódy]]], "trips": [[typ, číslo, názov, [[stanica, príchod, odchod]], dopravca]]}
    """
    today = datetime.now(TZ).date()
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=180) as r:
        z = zipfile.ZipFile(io.BytesIO(r.read()))

    def rows(name):
        if name not in z.namelist():
            return
        with z.open(name) as f:
            yield from csv.DictReader(io.TextIOWrapper(f, "utf-8-sig"))

    ymd, wd = today.strftime("%Y%m%d"), today.strftime("%A").lower()
    services = {r["service_id"] for r in rows("calendar.txt")
                if r.get("start_date", "") <= ymd <= r.get("end_date", "") and r.get(wd) == "1"}
    for r in rows("calendar_dates.txt"):
        if r.get("date") == ymd:
            (services.add if r.get("exception_type") == "1" else services.discard)(r["service_id"])

    agencies = {r.get("agency_id", ""): clean(r.get("agency_name")) for r in rows("agency.txt")}
    routes = {r["route_id"]: r for r in rows("routes.txt")}
    trips = {r["trip_id"]: r for r in rows("trips.txt") if r.get("service_id") in services}

    # nástupištia jednej stanice zlúčime podľa názvu
    stations, st_index, stop_station = [], {}, {}
    for r in rows("stops.txt"):
        name = clean(r.get("stop_name"))
        if not name:
            continue
        if name not in st_index:
            try:
                lat, lon = round(float(r["stop_lat"]), 4), round(float(r["stop_lon"]), 4)
            except (KeyError, ValueError):
                lat = lon = None
            st_index[name] = len(stations)
            stations.append([name, lat, lon, []])
        stop_station[r["stop_id"]] = st_index[name]
        # číselné kódy stanice: ŽSR ich niekedy posiela namiesto názvu ďalšej stanice
        codes = stations[st_index[name]][3]
        for code in re.findall(r"\d{3,}", f'{r.get("stop_id", "")} {r.get("stop_code", "")}'):
            if code.lstrip("0") not in codes:
                codes.append(code.lstrip("0"))

    times = {}
    for r in rows("stop_times.txt"):
        if r["trip_id"] in trips and r["stop_id"] in stop_station:
            times.setdefault(r["trip_id"], []).append((
                int(r.get("stop_sequence") or 0), stop_station[r["stop_id"]],
                gtfs_minutes(r.get("arrival_time")), gtfs_minutes(r.get("departure_time"))))

    out = []
    for trip_id, st in times.items():
        if len(st) < 2:
            continue
        st.sort()
        trip = trips[trip_id]
        route = routes.get(trip.get("route_id"), {})
        typ, num, name = train_ident(trip, route)
        stops = [[s, a if i else None, d if i < len(st) - 1 else None] for i, (_, s, a, d) in enumerate(st)]
        out.append([typ, num, name, stops, agencies.get(route.get("agency_id", ""), "")])
    out.sort(key=lambda t: t[3][0][2] or 0)

    write_json(out_path, {"date": today.isoformat(), "v": TIMETABLE_FORMAT, "source": url, "stations": stations, "trips": out})
    print(f"cestovný poriadok {today}: {len(out)} spojov, {len(stations)} staníc")


def ensure_timetable(out: Path):
    path = out / "timetable.json"
    try:
        tt = json.loads(path.read_text("utf-8"))
        if tt.get("date") == datetime.now(TZ).date().isoformat() and tt.get("v", 1) >= TIMETABLE_FORMAT:
            return
    except (OSError, ValueError, AttributeError):
        pass
    try:
        build_timetable(path)
    except Exception as e:  # bez cestovného poriadku stránka ukáže len polohy vlakov
        print(f"varovanie: cestovný poriadok sa nepodarilo zostaviť: {e}", file=sys.stderr)


def write_json(path, obj):
    tmp = path.with_suffix(".tmp")
    tmp.write_text(json.dumps(obj, ensure_ascii=False, separators=(",", ":")), "utf-8")
    tmp.replace(path)


def run_once(out: Path):
    out.mkdir(parents=True, exist_ok=True)
    ensure_timetable(out)
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
