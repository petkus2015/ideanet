#!/usr/bin/env bash
# Zostaví WordPress plugin: skopíruje aktuálny blok (CSS/JS) a zabalí ho do dist/lacne-letenky.zip.
# Súbor ZIP nahráte vo WordPresse cez Pluginy → Pridať nový → Nahrať plugin.
set -euo pipefail
cd "$(dirname "$0")"
SRC=../plugins/lacne-letenky
cp "$SRC/lacne-letenky.css" lacne-letenky/assets/lacne-letenky.css
cp "$SRC/lacne-letenky.js"  lacne-letenky/assets/lacne-letenky.js
mkdir -p dist
rm -f dist/lacne-letenky.zip
zip -rq dist/lacne-letenky.zip lacne-letenky -x '*.DS_Store'
echo "Hotovo: wordpress/dist/lacne-letenky.zip"
