#!/usr/bin/env python3
"""Skúšobný skript (len na výpis do záznamu behu, nič neukladá): ceny Wizz Air do SAE z Viedne,
Bratislavy a Budapešti. Spúšťa ho workflow .github/workflows/lacne-letenky-test.yml."""
from __future__ import annotations

import datetime as dt
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import update_deals as u  # noqa: E402


def main() -> None:
    today = dt.datetime.now(u.VIENNA).date()
    only = {"AUH": ("Abu Dhabi", "AE"), "DXB": ("Dubai", "AE"), "DWC": ("Dubai", "AE")}
    for origin in ("BUD", "BTS", "VIE"):
        print(f"=== Wizz Air {origin} -> SAE ===")
        deals, errs = u.fetch_wizz([origin], today, only=only)
        for d in deals:
            print("  ponuka:", d["dest"], d["price"], d["currency"], d["depart"], d["return"], d["nights"], "noci")
        print("  chyby:", errs or "-", "| ponúk:", len(deals))


if __name__ == "__main__":
    main()
