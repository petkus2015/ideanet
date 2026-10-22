#!/bin/sh
# Poskladá style.css témy: hlavička témy + dizajn statickej verzie
# + doplnky, ktoré potrebuje len WordPress. Spustite po každej zmene
# v ../styles.css, nech sa obe verzie nerozídu.
set -e
cd "$(dirname "$0")"
{
  cat theme-header.css
  printf '\n/* ──────────────────────────────────────────────────────────────\n'
  printf '   Štýly sú zhodné so statickou verziou webu. Negenerujte ich\n'
  printf '   ručne — tento súbor skladá build-style.sh zo ../styles.css.\n'
  printf '   Vlastné úpravy patria do detskej témy alebo do Prispôsobiť →\n'
  printf '   Doplnkové CSS.\n'
  printf '   ────────────────────────────────────────────────────────────── */\n\n'
  cat ../styles.css
  printf '\n'
  cat theme-extra.css
} > ideanet/style.css
echo "style.css poskladaný ($(wc -l < ideanet/style.css) riadkov)"
