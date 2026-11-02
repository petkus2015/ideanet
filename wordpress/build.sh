#!/usr/bin/env bash
# Zbalí tému a plugin do zipov s číslom verzie z hlavičky (style.css / relife-for-avada.php)
# a pregeneruje samostatný blueprint pre WordPress Playground.
# Použitie: ./build.sh   (spustiť z priečinka wordpress/)
set -euo pipefail
cd "$(dirname "$0")"

theme_v=$(grep -m1 '^Version:' relife/style.css | awk '{print $2}')
plugin_v=$(grep -m1 'Version:' relife-for-avada/relife-for-avada.php | awk '{print $NF}')

rm -f relife-*.zip relife.zip relife-for-avada-*.zip relife-for-avada.zip
zip -qr "relife-${theme_v}.zip" relife -x 'relife/playground/*'
zip -qr "relife-for-avada-${plugin_v}.zip" relife-for-avada -x 'relife-for-avada/playground/*'

python3 - <<'PY'
import os, json
root = 'relife'
def tree(path):
    d = {}
    for n in sorted(os.listdir(path)):
        if n in ('playground', 'screenshot.png'):
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
echo "Plugin: relife-for-avada-${plugin_v}.zip"
