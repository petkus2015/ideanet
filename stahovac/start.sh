#!/usr/bin/env bash
# Sťahovač videí – spustenie na Macu / Linuxe
cd "$(dirname "$0")"
PY=$(command -v python3 || command -v python)
if [ -z "$PY" ]; then
  echo "Chýba Python 3. Stiahnite ho z https://www.python.org/downloads/ a spustite znova."
  read -r -p "Stlačte Enter…" _; exit 1
fi
if [ ! -d .venv ]; then
  echo "Prvé spustenie: inštalujem potrebné balíčky (1–2 minúty)…"
  "$PY" -m venv .venv || { echo "Nepodarilo sa vytvoriť prostredie."; read -r -p "Enter…" _; exit 1; }
fi
. .venv/bin/activate
python -m pip install -q --upgrade pip
python -m pip install -q --upgrade -r requirements.txt   # vždy aj najnovší yt-dlp
STAHOVAC_OPEN_BROWSER=1 python app.py
