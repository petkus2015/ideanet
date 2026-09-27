"""Počasie v cieľovej destinácii v deň príletu (odletu tam) – k ponukám v deals.json.

Zdroj: Open-Meteo (zadarmo, bez kľúča).
- do 15 dní od dnes: predpoveď (forecast API), k = "f"
- ďalej: odhad z minulých rokov – priemer denných maxím v rovnakom období (±3 dni)
  za posledné 2 roky (archive API), k = "c"

Súradnice letísk: OurAirports (verejná doména), uložené v data/airports.json, aby sa
veľký CSV súbor sťahoval len pri novom letisku.
"""
from __future__ import annotations

import csv
import datetime as dt
import io
import json
import urllib.parse
import urllib.request
from collections import Counter
from pathlib import Path

FORECAST_API = "https://api.open-meteo.com/v1/forecast"
ARCHIVE_API = "https://archive-api.open-meteo.com/v1/archive"
AIRPORTS_CSV = "https://davidmegginson.github.io/ourairports-data/airports.csv"
FORECAST_DAYS = 16  # Open-Meteo dáva predpoveď na 16 dní (dnes + 15)
SPREAD = 3          # odhad z minulých rokov: ±3 dni okolo dátumu
YEARS = 2
CHUNK = 25          # letísk v jednej požiadavke

HEADERS = {"User-Agent": "lacne-letenky/1.0 (+https://github.com/petkus2015/ideanet)"}


def kind_of(code) -> str | None:
    """WMO kód počasia -> ikona v bloku."""
    if code is None:
        return None
    c = int(code)
    if c in (0, 1):
        return "sun"
    if c == 2:
        return "partly"
    if c == 3:
        return "cloud"
    if c in (45, 48):
        return "fog"
    if 71 <= c <= 77 or c in (85, 86):
        return "snow"
    if c >= 95:
        return "storm"
    if 51 <= c <= 67 or 80 <= c <= 82:
        return "rain"
    return "cloud"


def _get(url: str, params: dict) -> object:
    req = urllib.request.Request(url + "?" + urllib.parse.urlencode(params), headers=HEADERS)
    with urllib.request.urlopen(req, timeout=60) as resp:
        return json.loads(resp.read().decode("utf-8"))


def _multi(url: str, coords: list[tuple[float, float]], params: dict) -> list[dict]:
    """Viac letísk v jednej požiadavke; Open-Meteo vráti zoznam (pri jednom mieste objekt)."""
    p = dict(params)
    p["latitude"] = ",".join(f"{lat:.3f}" for lat, _ in coords)
    p["longitude"] = ",".join(f"{lon:.3f}" for _, lon in coords)
    res = _get(url, p)
    return res if isinstance(res, list) else [res]


def _series(block: dict) -> dict[str, tuple]:
    daily = block.get("daily") or {}
    return {day: (t, c) for day, t, c in zip(daily.get("time") or [],
                                             daily.get("temperature_2m_max") or [],
                                             daily.get("weather_code") or [])}


def load_airports(path: Path, needed: set[str], log=print) -> dict[str, list[float]]:
    cache: dict[str, list[float]] = {}
    if path.exists():
        try:
            cache = json.loads(path.read_text("utf-8"))
        except ValueError:
            cache = {}
    missing = {c for c in needed if c not in cache}
    # mestské kódy (LON, MIL, ROM…) nie sú letiská – potrebujeme ich hlavné letisko
    missing |= {CITY_CODES[c] for c in missing if c in CITY_CODES and CITY_CODES[c] not in cache}
    if missing:
        req = urllib.request.Request(AIRPORTS_CSV, headers=HEADERS)
        with urllib.request.urlopen(req, timeout=90) as resp:
            text = resp.read().decode("utf-8", "replace")
        found = 0
        for row in csv.DictReader(io.StringIO(text)):
            code = (row.get("iata_code") or "").strip().upper()
            if code in missing and code not in cache:
                try:
                    cache[code] = [round(float(row["latitude_deg"]), 3), round(float(row["longitude_deg"]), 3)]
                    found += 1
                except (KeyError, ValueError):
                    pass
        for city, main in CITY_CODES.items():
            if city in missing and main in cache:
                cache[city] = cache[main]
        log(f"Počasie: súradnice {found} nových letísk z OurAirports")
        path.write_text(json.dumps(dict(sorted(cache.items())), separators=(",", ":")) + "\n", "utf-8")
    return cache


CITY_CODES = {"LON": "LHR", "MIL": "MXP", "ROM": "FCO", "STO": "ARN", "PAR": "CDG", "OSL": "OSL",
              "BER": "BER", "MOW": "SVO", "TYO": "NRT", "SEL": "ICN", "BKK": "BKK", "NYC": "JFK",
              "BUH": "OTP", "REK": "KEF", "WAS": "IAD", "JKT": "CGK", "OSA": "KIX"}


def forecast(coords: list[tuple[float, float]]) -> list[dict[str, tuple]]:
    out = []
    for i in range(0, len(coords), CHUNK):
        part = coords[i:i + CHUNK]
        res = _multi(FORECAST_API, part, {"daily": "weather_code,temperature_2m_max",
                                          "forecast_days": FORECAST_DAYS, "timezone": "auto"})
        out += [_series(b) for b in res]
    return out


def climate(coords: list[tuple[float, float]], start: dt.date, end: dt.date) -> list[dict[str, tuple]]:
    """Denné dáta za rovnaké obdobie v minulých rokoch (kľúč = dátum v minulom roku)."""
    out = [dict() for _ in coords]
    for back in range(1, YEARS + 1):
        s = _shift(start - dt.timedelta(days=SPREAD), -back)
        e = _shift(end + dt.timedelta(days=SPREAD), -back)
        for i in range(0, len(coords), CHUNK):
            part = coords[i:i + CHUNK]
            res = _multi(ARCHIVE_API, part, {"daily": "weather_code,temperature_2m_max",
                                             "start_date": s.isoformat(), "end_date": e.isoformat(),
                                             "timezone": "auto"})
            for j, b in enumerate(res):
                out[i + j].update(_series(b))
    return out


def _shift(day: dt.date, years: int) -> dt.date:
    try:
        return day.replace(year=day.year + years)
    except ValueError:  # 29. február
        return day.replace(year=day.year + years, day=28)


def estimate(series: dict[str, tuple], day: dt.date) -> dict | None:
    temps, kinds = [], []
    for back in range(1, YEARS + 1):
        center = _shift(day, -back)
        for off in range(-SPREAD, SPREAD + 1):
            t, c = series.get((center + dt.timedelta(days=off)).isoformat(), (None, None))
            if t is not None:
                temps.append(t)
            k = kind_of(c)
            if k:
                kinds.append(k)
    if len(temps) < 3:
        return None
    return {"t": round(sum(temps) / len(temps)), "c": Counter(kinds).most_common(1)[0][0] if kinds else None, "k": "c"}


def add_weather(data: dict, today: dt.date, airports_path: Path, log=print) -> None:
    """Doplní deal["weather"] = {"t": °C max, "c": ikona, "k": "f" predpoveď | "c" odhad}."""
    deals = list(data.get("deals") or []) + [w["deal"] for w in data.get("watch") or [] if w.get("deal")]
    deals = [d for d in deals if d.get("depart")]
    if not deals:
        return
    coords = load_airports(airports_path, {d["dest"] for d in deals}, log)
    dests = sorted({d["dest"] for d in deals if d["dest"] in coords})
    unknown = sorted({d["dest"] for d in deals} - set(dests))
    if unknown:
        log(f"Počasie: bez súradníc {', '.join(unknown)}")
    last_fc = today + dt.timedelta(days=FORECAST_DAYS - 1)

    near = sorted({d["dest"] for d in deals if d["dest"] in coords and
                   dt.date.fromisoformat(d["depart"]) <= last_fc})
    fc = {}
    if near:
        try:
            fc = dict(zip(near, forecast([tuple(coords[c]) for c in near])))
        except Exception as exc:  # noqa: BLE001 – počasie je doplnok, ponuky sa zapíšu aj bez neho
            log(f"Počasie: predpoveď zlyhala ({exc})")

    far_days = [dt.date.fromisoformat(d["depart"]) for d in deals if d["dest"] in coords]
    cl = {}
    if far_days:
        try:
            cl = dict(zip(dests, climate([tuple(coords[c]) for c in dests], min(far_days), max(far_days))))
        except Exception as exc:  # noqa: BLE001
            log(f"Počasie: odhad z minulých rokov zlyhal ({exc})")

    done = 0
    for d in deals:
        day = dt.date.fromisoformat(d["depart"])
        w = None
        t, c = fc.get(d["dest"], {}).get(d["depart"], (None, None))
        if t is not None and day <= last_fc:
            w = {"t": round(t), "c": kind_of(c), "k": "f"}
        elif d["dest"] in cl:
            w = estimate(cl[d["dest"]], day)
        if w:
            d["weather"] = w
            done += 1
        else:
            d.pop("weather", None)
    log(f"Počasie: {done}/{len(deals)} ponúk ({len(fc)} letísk s predpoveďou, {len(cl)} s odhadom)")
