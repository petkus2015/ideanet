# Lacné letenky – plugin (VIE · BTS → kamkoľvek)

Widget s najlacnejšími letenkami z **Viedne (VIE)** a **Bratislavy (BTS)** do celého sveta.
Ceny berie z **momondo.co.uk** (mapa „Explore / kamkoľvek“) a obnovuje ich
**3× denne – ráno 7:00, na obed 12:00 a večer 18:00** (viedenský čas).

Náhľad: otvorte `plugins/lacne-letenky/index.html` (funguje aj bez servera).

## Vloženie na web

```html
<link rel="stylesheet" href="/plugins/lacne-letenky/lacne-letenky.css">
<div data-lacne-letenky data-src="/plugins/lacne-letenky/data/deals.json" data-limit="12"></div>
<script src="/plugins/lacne-letenky/lacne-letenky.js" defer></script>
```

| Atribút | Význam |
|---|---|
| `data-src` | cesta k `deals.json` |
| `data-limit` | max. počet zobrazených letov (0 = všetky) |
| `data-title` | vlastný nadpis |
| `data-fonts="false"` | nenačítavať Google Fonts |
| `data-theme="dark"` / `"light"` | vynútiť tému (inak podľa systému) |

Alebo z JavaScriptu: `LacneLetenky.mount(element, { src, limit, title })`.

## Čo plugin vie

- prepínač letiska: obe / Viedeň / Bratislava
- filtre regiónov (Európa, Ázia, Afrika, Amerika, Oceánia) s počtami
- hľadanie podľa mesta, krajiny aj IATA kódu (bez ohľadu na diakritiku)
- posuvník maximálnej ceny, len priame lety
- zoradenie: najlacnejšie, najskorší odlet, najdlhší pobyt, najviac zlacnené
- ▼/▲ zmena ceny oproti predchádzajúcej aktualizácii
- stav aktualizácie (ráno / obed / večer), upozornenie na neaktuálne dáta
- každý riadok vedie priamo na vyhľadávanie danej trasy a termínu na momondo.co.uk
- svetlá aj tmavá téma, mobilné rozloženie

## Aktualizácia cien

`.github/workflows/lacne-letenky.yml` spúšťa 3× denne
`scripts/update_deals.py`, ktorý:

1. pre VIE aj BTS zavolá momondo explore endpoint
   (`/s/horizon/exploreapi/destinations?airport=VIE…`),
2. na každú trasu nechá najlacnejšiu ponuku a doplní odkaz na vyhľadávanie,
3. zapíše `data/deals.json` + `data/deals.js` a commitne ich, ak sa ceny zmenili.

Ak momondo nevráti nič, staré dáta ostanú a beh skončí chybou (uvidíte ho v záložke Actions).
Plánované behy GitHub spúšťa len z **predvolenej vetvy** – workflow začne bežať po zlúčení.
Ručne: Actions → „Lacné letenky – aktualizácia“ → *Run workflow*.

Lokálne:

```bash
python3 plugins/lacne-letenky/scripts/update_deals.py            # živé ceny
python3 plugins/lacne-letenky/scripts/update_deals.py --sample   # ukážkové dáta
```

Kým neprebehne prvá úspešná aktualizácia, plugin zobrazuje **ukážkové ceny**
a jasne ich tak označuje.

> Explore endpoint nie je oficiálne verejné API. Ak ho momondo zmení alebo zablokuje
> požiadavky z GitHub Actions, parser (`parse_destinations`) je potrebné upraviť.
