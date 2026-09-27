#!/usr/bin/env python3
"""Skúšobný skript: ako dostať z momondo ceny do SAE (a Indie), ktoré mapa „kamkoľvek“ nevracia.

Spúšťa ho workflow .github/workflows/lacne-letenky-test.yml – iba vypíše výsledky do záznamu
behu, nič neukladá. Použitie lokálne: python3 probe_sources.py
"""
from __future__ import annotations

import json
import re
import sys
import urllib.parse
import urllib.request
import gzip
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import update_deals as u  # noqa: E402

TARGETS = {"SAE": ["DXB", "DWC", "AUH", "SHJ"], "India": ["DEL", "BOM"]}
# výrezy mapy (lat/lon): SAE a India
BOXES = {
    "SAE": dict(topRightLat=27.5, topRightLon=57.5, bottomLeftLat=22.0, bottomLeftLon=51.0),
    "India": dict(topRightLat=35.0, topRightLon=90.0, bottomLeftLat=8.0, bottomLeftLon=68.0),
}


def get(url: str, headers: dict | None = None) -> tuple[int, str]:
    req = urllib.request.Request(url, headers=headers or u.HEADERS)
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            raw = r.read()
            if r.headers.get("Content-Encoding") == "gzip":
                raw = gzip.decompress(raw)
            return r.status, raw.decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, ""
    except Exception as e:  # noqa: BLE001
        return 0, str(e)


def explore(origin: str, **over) -> str:
    params = {
        "airport": origin, "budget": "", "depart": "", "return": "", "duration": "",
        "exactDates": "false", "flightMaxStops": "", "stopsFilterActive": "false",
        "topRightLat": "", "topRightLon": "", "bottomLeftLat": "", "bottomLeftLon": "",
        "zoomLevel": "2", "selectedMarker": "", "themeCode": "", "selectedDestination": "",
        "currency": "EUR",
    }
    params.update({k: str(v) for k, v in over.items()})
    return u.SITE + u.EXPLORE_PATH + "?" + urllib.parse.urlencode(params)


def report(label: str, url: str, codes: list[str]) -> None:
    code, body = get(url)
    try:
        data = json.loads(body)
    except ValueError:
        print(f"[{label}] HTTP {code}, nie JSON ({len(body)} B)")
        return
    items = data.get("destinations") or []
    hits = [d for d in items if (u.pick(d, "airport.shortName") or "") in codes]
    shown = ", ".join(
        f"{u.pick(d, 'airport.shortName')} {u.pick(d, 'flightInfo.price')} {u.pick(d, 'flightInfo.currencyCode') or ''}"
        f" {u.pick(d, 'departd')}-{u.pick(d, 'returnd')}" for d in hits) or "—"
    print(f"[{label}] HTTP {code}, destinácií {len(items)}, zhoda: {shown}")


def explore_on(site: str, origin: str) -> str:
    return explore(origin).replace(u.SITE, site)


def show_json(label: str, url: str, headers: dict | None = None, find=("DXB", "AUH", "DWC", "SHJ")) -> None:
    code, body = get(url, headers)
    snippet = body[:300].replace("\n", " ")
    hits = [c for c in find if c in body]
    print(f"[{label}] HTTP {code}, {len(body)} B, obsahuje {hits or '—'}: {snippet}")


def main() -> None:
    routes = [("VIE", "DXB"), ("VIE", "AUH"), ("BTS", "DXB")]

    # 1) ten istý systém v iných krajinách – mapa „kamkoľvek“ pre iný trh
    for site in ("https://www.momondo.at", "https://www.momondo.de", "https://www.kayak.ae",
                 "https://www.kayak.com", "https://www.kayak.co.uk"):
        for origin in ("VIE", "BTS"):
            report(f"{site} {origin} kamkoľvek", explore_on(site, origin), ["DXB", "DWC", "AUH", "SHJ"])

    # 2) Aviasales – cenový kalendár konkrétnej trasy
    for o, d in routes:
        show_json(f"aviasales calendar {o}-{d}",
                  f"https://min-prices.aviasales.ru/calendar_preload?origin={o}&destination={d}&one_way=false",
                  {"User-Agent": u.HEADERS["User-Agent"], "Accept": "application/json"})
        show_json(f"aviasales matrix {o}-{d}",
                  "https://min-prices.aviasales.ru/price_matrix?" + urllib.parse.urlencode({
                      "origin_iata": o, "destination_iata": d, "depart_start": "2026-11-01",
                      "return_start": "2026-11-08", "depart_range": 6, "return_range": 6,
                      "affiliate": "false", "market": "sk"}),
                  {"User-Agent": u.HEADERS["User-Agent"], "Accept": "application/json"})

    # 3) momondo – vyhľadávanie konkrétnej trasy (HTML)
    for o, d in routes:
        code, html = get(f"{u.SITE}/flight-search/{o}-{d}/2026-11-10/2026-11-17?sort=price_a",
                         {**u.HEADERS, "Accept": "text/html"})
        prices = re.findall(r"£\s?\d[\d,]*|€\s?\d[\d,]*|\"price\"\s*:\s*\{?[^,]{0,40}", html)[:6]
        print(f"[momondo trasa {o}-{d}] HTTP {code}, {len(html)} B, ceny: {prices or '—'}, "
              f"captcha/bot: {'áno' if re.search('captcha|bot', html, re.I) else 'nie'}")


if __name__ == "__main__":
    main()
