#!/usr/bin/env python3
"""Aktualizuje lacné letenky z Viedne (VIE) a Bratislavy (BTS) kamkoľvek.

Zdroj: momondo.co.uk – "Explore" (kamkoľvek) endpoint, ktorý používa mapa
https://www.momondo.co.uk/explore. Výsledok zapíše do:

    data/deals.json   – dáta pre plugin (fetch)
    data/deals.js     – rovnaké dáta ako `window.LACNE_LETENKY_DATA`
                        (náhľad funguje aj po otvorení cez file://)

Použitie:
    python3 update_deals.py              # stiahne čerstvé ceny z momondo
    python3 update_deals.py --sample     # vygeneruje ukážkové dáta (offline)
    python3 update_deals.py --from-file odpoved.json --origin VIE
                                         # spracuje uloženú odpoveď API

Bez závislostí – iba štandardná knižnica Pythonu.
"""
from __future__ import annotations

import argparse
import datetime as dt
import gzip
import json
import random
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

try:
    from zoneinfo import ZoneInfo
    VIENNA = ZoneInfo("Europe/Vienna")
except Exception:  # pragma: no cover - zoneinfo chýba len na starých Pythonoch
    VIENNA = dt.timezone(dt.timedelta(hours=1))

ROOT = Path(__file__).resolve().parent.parent
DATA_DIR = ROOT / "data"
SITE = "https://www.momondo.co.uk"
EXPLORE_PATH = "/s/horizon/exploreapi/destinations"

ORIGINS = {
    "VIE": {"city": "Viedeň", "name": "Vienna International"},
    "BTS": {"city": "Bratislava", "name": "M. R. Štefánik"},
}

# Časy aktualizácie (Europe/Vienna) – musia sedieť s .github/workflows/lacne-letenky.yml
SCHEDULE = [("rano", "07:00"), ("obed", "12:00"), ("vecer", "18:00")]

# ISO kód -> (anglický názov, slovenský názov, región)
COUNTRIES = {
    "AT": ("Austria", "Rakúsko", "Európa"), "SK": ("Slovakia", "Slovensko", "Európa"),
    "CZ": ("Czech Republic", "Česko", "Európa"), "HU": ("Hungary", "Maďarsko", "Európa"),
    "PL": ("Poland", "Poľsko", "Európa"), "DE": ("Germany", "Nemecko", "Európa"),
    "CH": ("Switzerland", "Švajčiarsko", "Európa"), "IT": ("Italy", "Taliansko", "Európa"),
    "ES": ("Spain", "Španielsko", "Európa"), "PT": ("Portugal", "Portugalsko", "Európa"),
    "FR": ("France", "Francúzsko", "Európa"), "BE": ("Belgium", "Belgicko", "Európa"),
    "NL": ("Netherlands", "Holandsko", "Európa"), "LU": ("Luxembourg", "Luxembursko", "Európa"),
    "GB": ("United Kingdom", "Spojené kráľovstvo", "Európa"), "IE": ("Ireland", "Írsko", "Európa"),
    "DK": ("Denmark", "Dánsko", "Európa"), "NO": ("Norway", "Nórsko", "Európa"),
    "SE": ("Sweden", "Švédsko", "Európa"), "FI": ("Finland", "Fínsko", "Európa"),
    "IS": ("Iceland", "Island", "Európa"), "EE": ("Estonia", "Estónsko", "Európa"),
    "LV": ("Latvia", "Lotyšsko", "Európa"), "LT": ("Lithuania", "Litva", "Európa"),
    "GR": ("Greece", "Grécko", "Európa"), "CY": ("Cyprus", "Cyprus", "Európa"),
    "MT": ("Malta", "Malta", "Európa"), "HR": ("Croatia", "Chorvátsko", "Európa"),
    "SI": ("Slovenia", "Slovinsko", "Európa"), "RS": ("Serbia", "Srbsko", "Európa"),
    "BA": ("Bosnia and Herzegovina", "Bosna a Hercegovina", "Európa"),
    "ME": ("Montenegro", "Čierna Hora", "Európa"), "AL": ("Albania", "Albánsko", "Európa"),
    "MK": ("North Macedonia", "Severné Macedónsko", "Európa"), "XK": ("Kosovo", "Kosovo", "Európa"),
    "BG": ("Bulgaria", "Bulharsko", "Európa"), "RO": ("Romania", "Rumunsko", "Európa"),
    "MD": ("Moldova", "Moldavsko", "Európa"), "UA": ("Ukraine", "Ukrajina", "Európa"),
    "TR": ("Turkey", "Turecko", "Európa"), "GE": ("Georgia", "Gruzínsko", "Ázia"),
    "AM": ("Armenia", "Arménsko", "Ázia"), "AZ": ("Azerbaijan", "Azerbajdžan", "Ázia"),
    "IL": ("Israel", "Izrael", "Ázia"), "JO": ("Jordan", "Jordánsko", "Ázia"),
    "AE": ("United Arab Emirates", "Spojené arabské emiráty", "Ázia"),
    "QA": ("Qatar", "Katar", "Ázia"), "OM": ("Oman", "Omán", "Ázia"),
    "SA": ("Saudi Arabia", "Saudská Arábia", "Ázia"), "BH": ("Bahrain", "Bahrajn", "Ázia"),
    "KW": ("Kuwait", "Kuvajt", "Ázia"), "LB": ("Lebanon", "Libanon", "Ázia"),
    "IN": ("India", "India", "Ázia"), "LK": ("Sri Lanka", "Srí Lanka", "Ázia"),
    "MV": ("Maldives", "Maldivy", "Ázia"), "NP": ("Nepal", "Nepál", "Ázia"),
    "TH": ("Thailand", "Thajsko", "Ázia"), "VN": ("Vietnam", "Vietnam", "Ázia"),
    "KH": ("Cambodia", "Kambodža", "Ázia"), "MY": ("Malaysia", "Malajzia", "Ázia"),
    "SG": ("Singapore", "Singapur", "Ázia"), "ID": ("Indonesia", "Indonézia", "Ázia"),
    "PH": ("Philippines", "Filipíny", "Ázia"), "CN": ("China", "Čína", "Ázia"),
    "HK": ("Hong Kong", "Hongkong", "Ázia"), "TW": ("Taiwan", "Taiwan", "Ázia"),
    "JP": ("Japan", "Japonsko", "Ázia"), "KR": ("South Korea", "Južná Kórea", "Ázia"),
    "UZ": ("Uzbekistan", "Uzbekistan", "Ázia"), "KZ": ("Kazakhstan", "Kazachstan", "Ázia"),
    "EG": ("Egypt", "Egypt", "Afrika"), "MA": ("Morocco", "Maroko", "Afrika"),
    "TN": ("Tunisia", "Tunisko", "Afrika"), "DZ": ("Algeria", "Alžírsko", "Afrika"),
    "KE": ("Kenya", "Keňa", "Afrika"), "TZ": ("Tanzania", "Tanzánia", "Afrika"),
    "ZA": ("South Africa", "Južná Afrika", "Afrika"), "MU": ("Mauritius", "Maurícius", "Afrika"),
    "SC": ("Seychelles", "Seychely", "Afrika"), "CV": ("Cape Verde", "Kapverdy", "Afrika"),
    "ET": ("Ethiopia", "Etiópia", "Afrika"), "NA": ("Namibia", "Namíbia", "Afrika"),
    "US": ("United States", "USA", "Amerika"), "CA": ("Canada", "Kanada", "Amerika"),
    "MX": ("Mexico", "Mexiko", "Amerika"), "CU": ("Cuba", "Kuba", "Amerika"),
    "DO": ("Dominican Republic", "Dominikánska republika", "Amerika"),
    "JM": ("Jamaica", "Jamajka", "Amerika"), "CR": ("Costa Rica", "Kostarika", "Amerika"),
    "PA": ("Panama", "Panama", "Amerika"), "CO": ("Colombia", "Kolumbia", "Amerika"),
    "PE": ("Peru", "Peru", "Amerika"), "BR": ("Brazil", "Brazília", "Amerika"),
    "AR": ("Argentina", "Argentína", "Amerika"), "CL": ("Chile", "Čile", "Amerika"),
    "AU": ("Australia", "Austrália", "Oceánia"), "NZ": ("New Zealand", "Nový Zéland", "Oceánia"),
    "FJ": ("Fiji", "Fidži", "Oceánia"),
}
BY_EN_NAME = {v[0].lower(): k for k, v in COUNTRIES.items()}
BY_EN_NAME.update({"usa": "US", "uk": "GB", "czechia": "CZ", "türkiye": "TR",
                   "turkiye": "TR", "uae": "AE", "korea, south": "KR"})

HEADERS = {
    "User-Agent": ("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                   "(KHTML, like Gecko) Chrome/128.0 Safari/537.36"),
    "Accept": "application/json, text/plain, */*",
    "Accept-Language": "en-GB,en;q=0.9",
    "Accept-Encoding": "gzip",
    "Referer": SITE + "/explore",
}


# ---------------------------------------------------------------- momondo ---

def explore_url(origin: str) -> str:
    params = {
        "airport": origin, "budget": "", "depart": "", "return": "", "duration": "",
        "exactDates": "false", "flightMaxStops": "", "stopsFilterActive": "false",
        "topRightLat": "", "topRightLon": "", "bottomLeftLat": "", "bottomLeftLon": "",
        "zoomLevel": "2", "selectedMarker": "", "themeCode": "", "selectedDestination": "",
    }
    return SITE + EXPLORE_PATH + "?" + urllib.parse.urlencode(params)


def fetch_json(url: str, retries: int = 3) -> dict:
    last: Exception | None = None
    for attempt in range(retries):
        try:
            req = urllib.request.Request(url, headers=HEADERS)
            with urllib.request.urlopen(req, timeout=40) as resp:
                raw = resp.read()
                if resp.headers.get("Content-Encoding") == "gzip":
                    raw = gzip.decompress(raw)
                return json.loads(raw.decode("utf-8"))
        except (urllib.error.URLError, json.JSONDecodeError, TimeoutError) as exc:
            last = exc
            time.sleep(2 ** (attempt + 1))
    raise RuntimeError(f"momondo neodpovedalo ({url}): {last}")


def pick(obj, *paths, default=None):
    """Vráti prvú existujúcu hodnotu z niekoľkých možných ciest ("a.b.c")."""
    for path in paths:
        cur = obj
        for key in path.split("."):
            if isinstance(cur, dict) and key in cur:
                cur = cur[key]
            else:
                cur = None
                break
        if cur not in (None, ""):
            return cur
    return default


def parse_date(value) -> dt.date | None:
    if not value:
        return None
    s = str(value).strip()[:10].replace("-", "")
    try:
        return dt.datetime.strptime(s[:8], "%Y%m%d").date()
    except ValueError:
        return None


def search_link(origin: str, dest: str, depart: dt.date | None, ret: dt.date | None) -> str:
    if depart and ret:
        return f"{SITE}/flight-search/{origin}-{dest}/{depart:%Y-%m-%d}/{ret:%Y-%m-%d}?sort=price_a"
    if depart:
        return f"{SITE}/flight-search/{origin}-{dest}/{depart:%Y-%m-%d}?sort=price_a"
    return f"{SITE}/explore/{origin}-{dest}"


def country_info(code: str | None, name: str | None) -> tuple[str, str, str]:
    code = (code or "").upper()
    if code not in COUNTRIES and name:
        code = BY_EN_NAME.get(name.strip().lower(), code)
    if code in COUNTRIES:
        _, sk, region = COUNTRIES[code]
        return code, sk, region
    return code, name or "", "Svet"


def parse_destinations(payload: dict, origin: str) -> list[dict]:
    items = payload.get("destinations") or payload.get("results") or []
    deals = []
    for d in items:
        dest = pick(d, "airport.shortName", "airport.code", "airportCode", "destination")
        price = pick(d, "flightInfo.price", "price", "flightInfo.lowestPrice")
        if not dest or price is None:
            continue
        try:
            price = round(float(price))
        except (TypeError, ValueError):
            continue
        depart = parse_date(pick(d, "departd", "departDate", "flightInfo.departDate"))
        ret = parse_date(pick(d, "returnd", "returnDate", "flightInfo.returnDate"))
        code, country, region = country_info(
            pick(d, "country.id", "country.code", "countryCode"),
            pick(d, "country.name", "countryName"))
        stops = pick(d, "flightInfo.maxStops", "flightMaxStops", "maxStops", "stops")
        try:
            stops = int(stops) if stops is not None else None
        except (TypeError, ValueError):
            stops = None
        deals.append({
            "origin": origin,
            "dest": str(dest).upper(),
            "city": pick(d, "city.name", "cityName", default=str(dest)),
            "country": country,
            "countryCode": code,
            "region": region,
            "price": price,
            "currency": pick(d, "flightInfo.currencyCode", "currency", "currencyCode",
                             default=payload.get("currency") or "GBP"),
            "depart": depart.isoformat() if depart else None,
            "return": ret.isoformat() if ret else None,
            "nights": (ret - depart).days if depart and ret else None,
            "stops": stops,
            "url": search_link(origin, str(dest).upper(), depart, ret),
        })
    return deals


# ----------------------------------------------------------- ukážkové dáta ---

SAMPLE_ROUTES = [
    # origin, dest, city, country code, base price (GBP), stops
    ("VIE", "BCN", "Barcelona", "ES", 38, 0), ("VIE", "LTN", "Londýn", "GB", 29, 0),
    ("VIE", "FCO", "Rím", "IT", 34, 0), ("VIE", "ATH", "Atény", "GR", 57, 0),
    ("VIE", "LIS", "Lisabon", "PT", 86, 0), ("VIE", "DXB", "Dubaj", "AE", 214, 0),
    ("VIE", "BKK", "Bangkok", "TH", 412, 1), ("VIE", "JFK", "New York", "US", 338, 0),
    ("VIE", "NRT", "Tokio", "JP", 598, 1), ("VIE", "HRG", "Hurghada", "EG", 139, 0),
    ("VIE", "KEF", "Reykjavík", "IS", 118, 1), ("VIE", "CPT", "Kapské Mesto", "ZA", 521, 1),
    ("VIE", "SYD", "Sydney", "AU", 889, 1), ("VIE", "CUN", "Cancún", "MX", 544, 1),
    ("VIE", "MLE", "Malé", "MV", 463, 1), ("VIE", "TBS", "Tbilisi", "GE", 96, 0),
    ("BTS", "STN", "Londýn", "GB", 22, 0), ("BTS", "BGY", "Miláno", "IT", 19, 0),
    ("BTS", "DUB", "Dublin", "IE", 41, 0), ("BTS", "PMI", "Mallorca", "ES", 64, 0),
    ("BTS", "AGP", "Málaga", "ES", 72, 0), ("BTS", "SAW", "Istanbul", "TR", 48, 0),
    ("BTS", "RAK", "Marrákeš", "MA", 99, 1), ("BTS", "CRL", "Brusel", "BE", 27, 0),
    ("BTS", "SKG", "Solún", "GR", 45, 0), ("BTS", "TFS", "Tenerife", "ES", 118, 0),
]


def sample_deals(now: dt.datetime) -> list[dict]:
    rnd = random.Random(now.strftime("%Y%m%d%H"))
    deals = []
    for origin, dest, city, code, base, stops in SAMPLE_ROUTES:
        depart = now.date() + dt.timedelta(days=rnd.randint(10, 95))
        ret = depart + dt.timedelta(days=rnd.choice([3, 4, 5, 7, 7, 10, 14]))
        price = max(9, round(base * rnd.uniform(0.85, 1.15)))
        _, country, region = country_info(code, None)
        deals.append({
            "origin": origin, "dest": dest, "city": city, "country": country,
            "countryCode": code, "region": region, "price": price, "currency": "GBP",
            "depart": depart.isoformat(), "return": ret.isoformat(),
            "nights": (ret - depart).days, "stops": stops,
            "url": search_link(origin, dest, depart, ret),
            # ukážka trendu: časť trás "zlacnela" od minulej aktualizácie
            "prevPrice": price + rnd.randint(4, 30) if rnd.random() < 0.3 else None,
        })
    return deals


# ------------------------------------------------------------------ výstup ---

def load_previous() -> dict:
    try:
        return json.loads((DATA_DIR / "deals.json").read_text("utf-8"))
    except (OSError, json.JSONDecodeError):
        return {}


def slot_for(now: dt.datetime) -> str:
    minutes = now.hour * 60 + now.minute
    best = min(SCHEDULE, key=lambda s: abs(int(s[1][:2]) * 60 + int(s[1][3:]) - minutes))
    return best[0]


def next_update(now: dt.datetime) -> dt.datetime:
    for day in range(2):
        for _, hhmm in SCHEDULE:
            h, m = map(int, hhmm.split(":"))
            cand = (now + dt.timedelta(days=day)).replace(hour=h, minute=m, second=0, microsecond=0)
            if cand > now + dt.timedelta(minutes=30):
                return cand
    return now + dt.timedelta(hours=8)


def build(deals: list[dict], now: dt.datetime, sample: bool, errors: list[str]) -> dict:
    prev = load_previous()
    prev_prices = {(d["origin"], d["dest"]): d["price"] for d in prev.get("deals", [])
                   if not prev.get("sample")}

    # Na každú trasu necháme najlacnejšiu ponuku.
    best: dict[tuple[str, str], dict] = {}
    for d in deals:
        key = (d["origin"], d["dest"])
        if key not in best or d["price"] < best[key]["price"]:
            best[key] = d
    out = sorted(best.values(), key=lambda d: d["price"])
    for d in out:
        d.setdefault("prevPrice", prev_prices.get((d["origin"], d["dest"])))

    return {
        "source": "momondo.co.uk",
        "sample": sample,
        "updatedAt": now.isoformat(timespec="minutes"),
        "slot": slot_for(now),
        "nextUpdate": next_update(now).isoformat(timespec="minutes"),
        "schedule": [{"id": s, "time": t} for s, t in SCHEDULE],
        "timezone": "Europe/Vienna",
        "origins": [{"code": k, **v} for k, v in ORIGINS.items()],
        "errors": errors,
        "deals": out,
    }


def write(data: dict) -> None:
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    text = json.dumps(data, ensure_ascii=False, indent=1)
    (DATA_DIR / "deals.json").write_text(text + "\n", "utf-8")
    (DATA_DIR / "deals.js").write_text(
        "/* Vygenerované skriptom scripts/update_deals.py – needitovať ručne. */\n"
        f"window.LACNE_LETENKY_DATA = {text};\n", "utf-8")


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--sample", action="store_true", help="vygeneruje ukážkové dáta bez siete")
    ap.add_argument("--from-file", type=Path, help="spracuje uloženú odpoveď explore API")
    ap.add_argument("--origin", default="VIE", help="letisko pre --from-file (predvolene VIE)")
    args = ap.parse_args()

    now = dt.datetime.now(VIENNA)
    errors: list[str] = []

    if args.sample:
        write(build(sample_deals(now), now, True, errors))
        print("Zapísané ukážkové dáta.")
        return 0

    deals: list[dict] = []
    if args.from_file:
        deals = parse_destinations(json.loads(args.from_file.read_text("utf-8")), args.origin.upper())
    else:
        for origin in ORIGINS:
            try:
                found = parse_destinations(fetch_json(explore_url(origin)), origin)
                print(f"{origin}: {len(found)} destinácií")
                deals += found
            except Exception as exc:  # jedno letisko nesmie zhodiť celú aktualizáciu
                errors.append(f"{origin}: {exc}")
                print(f"{origin}: CHYBA {exc}", file=sys.stderr)

    if not deals:
        # Staré dáta nechávame tak – plugin radšej ukáže posledné známe ceny.
        print("Žiadne ponuky – dáta sa neprepisujú.", file=sys.stderr)
        return 1

    write(build(deals, now, False, errors))
    print(f"Zapísaných {len(deals)} ponúk ({now:%Y-%m-%d %H:%M} Europe/Vienna).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
