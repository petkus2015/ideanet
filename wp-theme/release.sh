#!/bin/sh
# Pripraví nové vydanie témy.
#
#   ./wp-theme/release.sh 1.1.0
#
# Zmení verziu v hlavičke témy, poskladá style.css, zabalí tému do
# ideanet-theme.zip a vypíše, čo spraviť ďalej. Nič nepushuje sám —
# vydanie je vidieť navonok, tak nech to má posledné slovo človek.
set -e
cd "$(dirname "$0")"

VERZIA="$1"
if [ -z "$VERZIA" ]; then
  echo "Použitie: ./wp-theme/release.sh <verzia>   (napríklad 1.1.0)" >&2
  exit 1
fi
if ! echo "$VERZIA" | grep -qE '^[0-9]+(\.[0-9]+)*$'; then
  echo "Verzia musí byť číslo, napríklad 1.1.0 — dostal som „$VERZIA\"." >&2
  exit 1
fi

STARA=$(sed -n 's/^Version: *//p' theme-header.css | head -1)
echo "Verzia $STARA → $VERZIA"

sed -i.bak "s/^Version: .*/Version: $VERZIA/" theme-header.css && rm -f theme-header.css.bak
./build-style.sh

rm -f ideanet-theme.zip
zip -rq ideanet-theme.zip ideanet -x '*.DS_Store' '*/.git/*'
echo "Zabalené: $(pwd)/ideanet-theme.zip ($(du -h ideanet-theme.zip | cut -f1))"

cat <<KONIEC

Zostáva:

  1. Skontrolujte zmenu a zapíšte ju:
       git add -A && git commit -m "Téma $VERZIA"
       git push

  2. Vytvorte vydanie a priložte k nemu ten ZIP:
       gh release create theme-v$VERZIA wp-theme/ideanet-theme.zip \\
         --title "Téma $VERZIA" --notes "Čo je nové…"

     Alebo na GitHube: Releases → Draft a new release → značka
     theme-v$VERZIA → k vydaniu priložte súbor ideanet-theme.zip.

Do pár hodín sa aktualizácia sama ukáže vo WordPresse vo Vzhľad → Témy.
Kto nechce čakať, klikne v administrácii na IDEANET → Zistiť aktualizácie.

Názov priloženého súboru musí zostať ideanet-theme.zip — podľa neho si ho
WordPress nájde. Značka smie byť theme-v$VERZIA aj v$VERZIA alebo $VERZIA.
KONIEC
