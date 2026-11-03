#!/usr/bin/env bash
# Zbalí tému (a s --avada aj plugin pre Avadu) do zipov s číslom verzie z hlavičky (style.css / relife-for-avada.php)
# a pregeneruje samostatný blueprint pre WordPress Playground.
# Použitie: ./build.sh   (spustiť z priečinka wordpress/)
set -euo pipefail
cd "$(dirname "$0")"

theme_v=$(grep -m1 '^Version:' relife/style.css | awk '{print $2}')
plugin_v=$(grep -m1 'Version:' relife-for-avada/relife-for-avada.php | awk '{print $NF}')

rm -f relife-[0-9]*.zip relife.zip
# Do zipu ide kópia so skrátenými štýlmi (bez komentárov a medzier), zdrojový súbor v repozitári zostáva čitateľný.
stage=$(mktemp -d)
cp -r relife "$stage/relife"
python3 - "$stage/relife/assets/css/relife.css" <<'MIN'
import re, sys
p = sys.argv[1]
c = open(p, encoding='utf-8').read()
c = re.sub(r'/\*.*?\*/', '', c, flags=re.S)
c = re.sub(r'\s+', ' ', c)
c = re.sub(r'\s*([{};,>])\s*', r'\1', c)
c = c.replace(';}', '}')
open(p, 'w', encoding='utf-8').write(c.strip())
MIN
(cd "$stage" && zip -qr "$OLDPWD/relife-${theme_v}.zip" relife -x 'relife/playground/*')
rm -rf "$stage"

# Plugin pre Avadu sa zabalí len na požiadanie: ./build.sh --avada
if [[ "${1:-}" == "--avada" ]]; then
  rm -f relife-for-avada-*.zip relife-for-avada.zip
  zip -qr "relife-for-avada-${plugin_v}.zip" relife-for-avada -x 'relife-for-avada/playground/*'
  fi

python3 - <<'PY'
import os, json
root = 'relife'
def tree(path):
    d = {}
    for n in sorted(os.listdir(path)):
        if n in ('playground', 'screenshot.png', 'fonts'):
            continue
        fp = os.path.join(path, n)
        d[n] = tree(fp) if os.path.isdir(fp) else open(fp, encoding='utf-8').read()
    return d
p = 'relife-playground-blueprint.json'
bp = json.load(open(p, encoding='utf-8'))
bp['steps'][0]['filesTree']['files'] = tree(root)
json.dump(bp, open(p, 'w', encoding='utf-8'), ensure_ascii=False)
PY

echo "Téma:   relife-${theme_v}.zip"
