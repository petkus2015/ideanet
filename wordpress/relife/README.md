# RELIFE – téma pre WordPress

Tmavá magazínová téma pre **RELIFE – Culture. Future. Better Living.**
Klasická PHP téma s `theme.json` (paleta, písma, šírky obsahu v editore blokov).

- **WordPress:** 6.6 alebo novší
- **PHP:** 8.1 – 8.4 (`declare(strict_types=1)`, bez zastaraných konštrukcií)
- Bez build kroku, bez závislostí. Fonty (Bricolage Grotesque, Inter) idú z Google Fonts; vypnete ich filtrom `relife_load_google_fonts` a fonty si nahostujete sami.

## Inštalácia

1. Skopírujte priečinok `relife/` do `wp-content/themes/` (alebo nahrajte `relife.zip` cez *Vzhľad → Témy → Pridať novú*).
2. Aktivujte tému.
3. *Vzhľad → RELIFE ukážkový obsah → Importovať* (alebo `wp relife demo`) vytvorí sekcie, formáty, články, ankety, stránky a menu.

## Čo téma obsahuje

| Funkcia | Ako funguje |
|---|---|
| **7 sekcií** | Kategórie `trendy, tech, health, money, travel, style, life`. Farba sekcie sa dá zmeniť pri úprave kategórie. |
| **6 formátov** | Taxonómia `relife_format`: Quick Take, Explained, Versus, How to, Trend Report, Ultimate Guide. |
| **Hero „Výber redakcie“** | Pripnutý článok (sticky), inak najnovší. |
| **Anketa** | Typ obsahu *Ankety*. Možnosti: `názov \| počet hlasov` na riadok. Čitateľ hlasuje len vo svojom prehliadači (`localStorage`). |
| **Staršie ankety** | Šablóna stránky *Staršie ankety* s filtrom podľa sekcie. |
| **Nálada** | 5 nálad → sekcie, filtrujú „Čo dnes stojí za tvoju pozornosť?“. Upraviteľné filtrom `relife_moods`. |
| **Uložené články** | Záložka pri karte. ID v `localStorage`, kópia stránky v Cache API (`relife-saved-v1`). Šablóna stránky *Uložené články*. |
| **Článok** | Odpoveď v skratke, obsah z nadpisov, zdroje, označenie spolupráce, autor, JSON-LD `Article`. |
| **Mobil** | Spodné menu v štýle aplikácie (Domov, Sekcie, Hľadať, Uložené, Weekly). |

## Poznámky

- Hlasy a uložené články sa **nesynchronizujú** medzi zariadeniami a neposielajú na server. Počty hlasov v ankete zadáva admin ručne.
- Pre skutočné čítanie bez pripojenia treba doplniť service worker, ktorý vracia stránky z Cache API.
- Formulár newslettra odošle e-mail na adresu z *Customizer → RELIFE* (action). Napojte ho na svoju e-mailovú službu.

## Vývoj a ukážka

`playground/blueprint.json` spustí tému vo WordPress Playground (PHP 8.4):

```bash
npx @wp-playground/cli server --php=8.4 --wp=latest \
  --blueprint=playground/blueprint.json \
  --mount=$(pwd):/wordpress/wp-content/themes/relife
```
