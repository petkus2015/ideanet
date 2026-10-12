"""Počasie v cieľovej destinácii v deň odletu – k ponukám v deals.json a v tabuľkách pre plugin.

Zdroj: Open-Meteo (zadarmo, bez kľúča).
- predpoveď na 16 dní pre každú destináciu (k = "f")
- ďalej: typické počasie v danom období z posledných 3 rokov (archive API), po 10-dňových
  úsekoch roka (36 úsekov): priemer denných maxím a najčastejšie počasie (k = "c")

Výstup (data/):
- weather.json  – pre každú destináciu predpoveď (16 dní) a klimatická tabuľka (36 úsekov);
                  plugin z nej dopočíta počasie ľubovoľnej ponuky, aj keď ju našlo až živé hľadanie
- climate.json  – uložená klimatická tabuľka (počíta sa raz, potom sa len dopĺňa o nové letiská)
- airports.json – súradnice letísk z OurAirports (verejná doména)

Hodnota v tabuľkách je text „teplota + písmeno počasia“, napr. "32r": s slnečno, p polooblačno,
c oblačno, f hmla, r dážď, n sneh, t búrky.
"""
from __future__ import annotations

import csv
import datetime as dt
import io
import json
import urllib.parse
import time
import urllib.request
import re
from collections import Counter
from pathlib import Path

FORECAST_API = "https://api.open-meteo.com/v1/forecast"
ARCHIVE_API = "https://archive-api.open-meteo.com/v1/archive"
AIRPORTS_CSV = "https://davidmegginson.github.io/ourairports-data/airports.csv"
FORECAST_DAYS = 16  # Open-Meteo dáva predpoveď na 16 dní (dnes + 15)
YEARS = 3           # klimatická tabuľka: posledné 3 roky
CHUNK = 20          # letísk v jednej požiadavke pre predpoveď
CLIMATE_CHUNK = 8   # letísk v jednej požiadavke pre archív (je pomalší)
MAX_NEW_CLIMATE = 120 # koľko nových letísk sa klimaticky dopočíta za jeden beh (zvyšok nabudúce)
MAX_DESTS = 450     # koľko najlacnejších destinácií sa dostane do tabuliek

KIND_CODE = {"sun": "s", "partly": "p", "cloud": "c", "fog": "f", "rain": "r", "snow": "n", "storm": "t"}
CODE_KIND = {v: k for k, v in KIND_CODE.items()}

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


def _get(url: str, params: dict, attempts: int = 4) -> object:
    req = urllib.request.Request(url + "?" + urllib.parse.urlencode(params), headers=HEADERS)
    for attempt in range(attempts):
        try:
            with urllib.request.urlopen(req, timeout=90) as resp:
                return json.loads(resp.read().decode("utf-8"))
        except Exception:  # noqa: BLE001 – archív Open-Meteo občas nestihne odpovedať
            if attempt == attempts - 1:
                raise
            time.sleep(5 * (attempt + 1))
    return None


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
    cache: dict[str, list[float] | None] = {}
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
        for code in missing:
            cache.setdefault(code, None)  # neznámy kód – nabudúce už CSV kvôli nemu nesťahujeme
        log(f"Počasie: súradnice {found} nových letísk z OurAirports")
        path.write_text(json.dumps(dict(sorted(cache.items())), separators=(",", ":")) + "\n", "utf-8")
    return {k: v for k, v in cache.items() if v}


CITY_CODES = {"LON": "LHR", "MIL": "MXP", "ROM": "FCO", "STO": "ARN", "PAR": "CDG", "OSL": "OSL",
              "BER": "BER", "MOW": "SVO", "TYO": "NRT", "SEL": "ICN", "BKK": "BKK", "NYC": "JFK",
              "BUH": "OTP", "REK": "KEF", "WAS": "IAD", "JKT": "CGK", "OSA": "KIX", "BJS": "PEK",
              "EAP": "BSL", "SHA": "PVG", "RIO": "GIG", "SAO": "GRU", "CHI": "ORD", "YTO": "YYZ", "YMQ": "YUL"}


def pack(t, kind: str | None) -> str:
    return f"{round(t)}{KIND_CODE.get(kind or 'cloud', 'c')}"


def unpack(text: str | None) -> tuple[int, str] | None:
    m = re.fullmatch(r"(-?\d+)([spcfrnt])", text or "")
    return (int(m.group(1)), CODE_KIND[m.group(2)]) if m else None


def bucket(day: dt.date) -> int:
    """10-dňový úsek roka 0–35 (mesiac × 3 + tretina mesiaca)."""
    return (day.month - 1) * 3 + min((day.day - 1) // 10, 2)


def forecast_tables(dests: list[str], coords: dict, today: dt.date, log=print) -> dict[str, list[str]]:
    """Predpoveď na FORECAST_DAYS dní od dnes; kľúč = letisko, hodnota = zoznam textov (prázdny = neznáme)."""
    out: dict[str, list[str]] = {}
    failed = 0
    for i in range(0, len(dests), CHUNK):
        part = dests[i:i + CHUNK]
        try:
            res = _multi(FORECAST_API, [tuple(coords[c]) for c in part],
                         {"daily": "weather_code,temperature_2m_max", "forecast_days": FORECAST_DAYS,
                          "timezone": "auto"})
        except Exception as exc:  # noqa: BLE001 – počasie je doplnok, ponuky sa zapíšu aj bez neho
            failed += 1
            log(f"Počasie: predpoveď, letiská {i + 1}–{i + len(part)} zlyhala ({exc})")
            continue
        for code, block in zip(part, res):
            ser = _series(block)
            row = []
            for n in range(FORECAST_DAYS):
                t, c = ser.get((today + dt.timedelta(days=n)).isoformat(), (None, None))
                row.append(pack(t, kind_of(c)) if t is not None else "")
            if any(row):
                out[code] = row
    if failed:
        log(f"Počasie: {failed} dávok predpovede zlyhalo")
    return out


def climate_tables(dests: list[str], coords: dict, today: dt.date, log=print) -> dict[str, list[str]]:
    """Typické počasie po 10-dňových úsekoch roka z posledných YEARS rokov (archív Open-Meteo)."""
    start = today - dt.timedelta(days=365 * YEARS)
    end = today - dt.timedelta(days=7)  # archív má oneskorenie pár dní
    out: dict[str, list[str]] = {}
    failed = 0
    for i in range(0, len(dests), CLIMATE_CHUNK):
        part = dests[i:i + CLIMATE_CHUNK]
        try:
            res = _multi(ARCHIVE_API, [tuple(coords[c]) for c in part],
                         {"daily": "weather_code,temperature_2m_max", "start_date": start.isoformat(),
                          "end_date": end.isoformat(), "timezone": "auto"})
        except Exception as exc:  # noqa: BLE001
            failed += 1
            log(f"Počasie: archív, letiská {i + 1}–{i + len(part)} zlyhal ({exc})")
            continue
        for code, block in zip(part, res):
            temps: list[list[float]] = [[] for _ in range(36)]
            kinds: list[list[str]] = [[] for _ in range(36)]
            for day, (t, c) in _series(block).items():
                if t is None:
                    continue
                b = bucket(dt.date.fromisoformat(day))
                temps[b].append(t)
                k = kind_of(c)
                if k:
                    kinds[b].append(k)
            row = [pack(sum(x) / len(x), Counter(k).most_common(1)[0][0] if k else "cloud") if x else ""
                   for x, k in zip(temps, kinds)]
            if sum(1 for r in row if r) >= 30:
                out[code] = row
    if failed:
        log(f"Počasie: {failed} dávok archívu zlyhalo")
    return out


def lookup(dest: str, day: dt.date, today: dt.date, fc: dict, cl: dict) -> dict | None:
    """Počasie destinácie v daný deň: predpoveď, inak typické počasie z klimatickej tabuľky."""
    idx = (day - today).days
    row = fc.get(dest)
    if row and 0 <= idx < len(row) and unpack(row[idx]):
        t, kind = unpack(row[idx])
        return {"t": t, "c": kind, "k": "f"}
    row = cl.get(dest)
    if row and unpack(row[bucket(day)]):
        t, kind = unpack(row[bucket(day)])
        return {"t": t, "c": kind, "k": "c"}
    return None


def _read_json(path: Path) -> dict:
    try:
        return json.loads(path.read_text("utf-8"))
    except (OSError, ValueError):
        return {}


def add_weather(data: dict, today: dt.date, data_dir: Path, pool: list[str] | None = None, log=print) -> None:
    """Doplní deal["weather"] = {"t": °C max, "c": ikona, "k": "f" predpoveď | "c" typické počasie}
    a zapíše data/weather.json + data/climate.json pre plugin."""
    deals = list(data.get("deals") or []) + [w["deal"] for w in data.get("watch") or [] if w.get("deal")]
    deals = [d for d in deals if d.get("depart")]
    shown = list(dict.fromkeys(d["dest"] for d in deals))  # najprv to, čo sa v bloku zobrazí
    order = shown + [c for c in (pool or []) if c not in shown]
    order = order[:MAX_DESTS]
    if not order:
        return
    coords = load_airports(data_dir / "airports.json", set(order), log)
    dests = [c for c in order if c in coords]
    unknown = [c for c in order if c not in coords]
    if unknown:
        log(f"Počasie: bez súradníc {len(unknown)} letísk ({', '.join(unknown[:12])}{'…' if len(unknown) > 12 else ''})")

    fc = forecast_tables(dests, coords, today, log)

    stored = _read_json(data_dir / "climate.json")
    cl: dict[str, list[str]] = dict(stored.get("d") or {})
    todo = [c for c in dests if c not in cl][:MAX_NEW_CLIMATE]
    if todo:
        new = climate_tables(todo, coords, today, log)
        cl.update(new)
        log(f"Počasie: klimatická tabuľka pre {len(new)}/{len(todo)} nových letísk, spolu {len(cl)}")
        (data_dir / "climate.json").write_text(json.dumps(
            {"years": YEARS, "updatedAt": today.isoformat(), "d": dict(sorted(cl.items()))},
            ensure_ascii=False, separators=(",", ":")) + "\n", "utf-8")

    table = {c: {"f": fc.get(c, []), "c": cl.get(c, [])} for c in dests if c in fc or c in cl}
    (data_dir / "weather.json").write_text(json.dumps(
        {"from": today.isoformat(), "updatedAt": dt.datetime.now(dt.timezone.utc).isoformat(timespec="minutes"),
         "days": FORECAST_DAYS, "d": table}, ensure_ascii=False, separators=(",", ":")) + "\n", "utf-8")

    done = 0
    for d in deals:
        w = lookup(d["dest"], dt.date.fromisoformat(d["depart"]), today, fc, cl)
        if w:
            d["weather"] = w
            done += 1
        else:
            d.pop("weather", None)
    log(f"Počasie: {done}/{len(deals)} ponúk, tabuľky pre {len(table)} letísk "
        f"({len(fc)} s predpoveďou, {len(cl)} s klimatickou tabuľkou)")
