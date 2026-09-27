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


def main() -> None:
    for origin in ("VIE", "BTS"):
        for name, codes in TARGETS.items():
            box = BOXES[name]
            print(f"\n=== {origin} → {name} ({', '.join(codes)}) ===")
            report("svet", explore(origin), codes)
            for z in (4, 5, 6):
                report(f"výrez zoom {z}", explore(origin, zoomLevel=z, **box), codes)
            report("výrez + selectedDestination", explore(origin, zoomLevel=5, selectedDestination=codes[0], **box), codes)
            for dep in ("202611", "20261101", "2026-11"):
                report(f"výrez + depart={dep}", explore(origin, zoomLevel=5, depart=dep, **box), codes)

    # stránka trasy (HTML) – hľadáme v nej ceny
    for path in ("/flight-routes/vienna-vie/dubai-dxb", "/flight-routes/vienna-vie/abu-dhabi-auh",
                 "/flight-routes/bratislava-bts/dubai-dxb"):
        code, html = get(u.SITE + path, {**u.HEADERS, "Accept": "text/html"})
        prices = re.findall(r"£\s?\d[\d,]*|€\s?\d[\d,]*|\"price\"\s*:\s*\"?\d+", html)[:8]
        print(f"\n[trasa {path}] HTTP {code}, {len(html)} B, ceny: {prices or '—'}")


if __name__ == "__main__":
    main()
