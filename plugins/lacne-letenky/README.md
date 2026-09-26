# Lacné letenky – plugin (VIE · BTS → kamkoľvek)

Widget s najlacnejšími letenkami z **Viedne (VIE)** a **Bratislavy (BTS)** do celého sveta.
Ceny porovnáva z **momondo.co.uk** (mapa „Explore / kamkoľvek“), **ryanair.com** a **wizzair.com**
a z každej trasy ukáže najlacnejšiu a obnovuje ich
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
| `data-limit` | počet kariet (predvolene 8) |
| `data-title` | vlastný nadpis |
| `data-fonts="false"` | nenačítavať Google Fonts |
| `data-theme="dark"` / `"light"` | vynútiť tému (inak podľa systému) |

Alebo z JavaScriptu: `LacneLetenky.mount(element, { src, limit, title })`.

## Čo blok zobrazuje

Iba ponuky z **poslednej úspešnej aktualizácie** z momondo.co.uk – bez filtrov a bez vymyslených cien:

- **tlačidlo „Vyhľadať lacné letenky“** – jediné ovládanie, bez ručného zadávania. Načíta
  najnovšie ponuky z posledného hľadania na momondo (obíde cache prehliadača) a napíše,
  či pribudli nové, alebo kedy prebehne ďalšie hľadanie.
- hľadajú sa iba lety s **odletom najviac 3 mesiace dopredu** (`HORIZON_DAYS` v skripte)
- **Bangkok je vždy prvý**: najlacnejšia **spiatočná** letenka z VIE alebo BTS na letisko
  BKK alebo DMK. Ak ju momondo pri aktualizácii nevráti, blok napíše, že ponuku nenašiel,
  a ponúkne odkaz na vyhľadávanie. **Dubaj** (DXB, DWC) a **Abu Dhabí** (AUH) sa hľadajú tiež vždy a v bloku sú
  vždy medzi kartami „kamkoľvek“ (zoradené podľa ceny). Ďalšie sledované mestá sa pridávajú do `WATCH`
  v `scripts/update_deals.py`.

- karty zoradené od najlacnejšej (mesto, krajina, trasa VIE/BTS → cieľ, termín, počet nocí, cena v €)
- štítok „Priamy“ a ▼ o koľko ponuka zlacnela od predchádzajúcej aktualizácie
- čas poslednej aktualizácie
- klik na kartu otvorí vyhľadávanie danej trasy a termínu na momondo.co.uk
- lety s odletom dnes alebo skôr sa nezobrazia
- ak sú dáta staršie ako 36 hodín (aktualizácia viackrát zlyhala) alebo ešte žiadne nie sú,
  blok ukáže správu „Práve nemáme aktuálne ponuky“ namiesto starých cien
- svetlá aj tmavá téma, mobilné rozloženie

## Aktualizácia cien

`.github/workflows/lacne-letenky.yml` spúšťa 3× denne
`scripts/update_deals.py`, ktorý:

1. pre VIE aj BTS stiahne ponuky z troch zdrojov:
   - **momondo** – explore endpoint (`/s/horizon/exploreapi/destinations?airport=VIE…`),
   - **Ryanair** – `farfnd/v4/roundTripFares`: najlacnejšie spiatočné lety kamkoľvek,
     odlet do 3 mesiacov, pobyt 2–14 nocí,
   - **Wizz Air** – z mapy liniek zistí destinácie z VIE/BTS a pre každú stiahne cenový
     kalendár (`/Api/search/timetable`) po 30-dňových častiach; vyberie najlacnejšiu
     kombináciu tam + späť s pobytom 2–14 nocí,
   z každej trasy (letisko → letisko) sa ponechá najlacnejšia ponuka a karta vedie
   na web zdroja (momondo, ryanair.com alebo wizzair.com),
2. ceny uloží v **eurách**: od momondo si ich pýta v EUR (`currency=EUR`); ak by momondo
   vrátilo inú menu (momondo.co.uk má predvolene libry), prepočíta ich denným kurzom
   **ECB** a v bloku pribudne poznámka „prepočítané kurzom ECB“. Keď kurz nie je
   dostupný, staré dáta ostanú a beh skončí chybou – ceny v librách sa nikdy nezobrazia,
3. pre sledované destinácie (Bangkok) sa momondo pýta ešte samostatne, aby nechýbali,
   keď ich všeobecný prehľad „kamkoľvek“ nevráti,
4. na každú trasu nechá najlacnejšiu ponuku a doplní odkaz na vyhľadávanie,
5. zapíše `data/deals.json` + `data/deals.js` a commitne ich, ak sa ceny zmenili.

Ak momondo nevráti nič, staré dáta ostanú a beh skončí chybou (uvidíte ho v záložke Actions).
Plánované behy GitHub spúšťa len z **predvolenej vetvy** – workflow začne bežať po zlúčení.
Ručne: Actions → „Lacné letenky – aktualizácia“ → *Run workflow*.

Lokálne:

```bash
python3 plugins/lacne-letenky/scripts/update_deals.py                 # všetky zdroje
python3 plugins/lacne-letenky/scripts/update_deals.py --only ryanair  # jeden zdroj
```

Kým neprebehne prvá úspešná aktualizácia, `deals.json` je prázdny a blok ukazuje len správu,
že ponuky sa pripravujú.

> Ani jeden zo zdrojov nemá oficiálne verejné API – ide o endpointy, ktoré používajú ich weby.
> Keď niektorý zdroj zlyhá alebo zmení formát, ostatné fungujú ďalej; chyby sú v zázname
> behu v Actions a v `errors` v `deals.json`. Parsery: `parse_destinations`, `parse_ryanair`,
> `fetch_wizz`.
