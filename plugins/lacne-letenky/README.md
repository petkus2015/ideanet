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
| `data-limit` | počet kariet (predvolene 8) |
| `data-title` | vlastný nadpis |
| `data-fonts="false"` | nenačítavať Google Fonts |
| `data-theme="dark"` / `"light"` | vynútiť tému (inak podľa systému) |

Alebo z JavaScriptu: `LacneLetenky.mount(element, { src, limit, title })`.

## Čo blok zobrazuje

Iba ponuky z **poslednej úspešnej aktualizácie** z momondo.co.uk – bez filtrov a bez vymyslených cien:

- **Bangkok** má vlastnú zvýraznenú kartu: najlacnejšia ponuka z VIE alebo BTS na letisko
  BKK alebo DMK. Ak ju momondo pri aktualizácii nevráti, blok napíše, že ponuku nenašiel,
  a ponúkne odkaz na vyhľadávanie. Ďalšie sledované mestá sa pridávajú do `WATCH`
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

1. pre VIE aj BTS zavolá momondo explore endpoint
   (`/s/horizon/exploreapi/destinations?airport=VIE…`),
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
python3 plugins/lacne-letenky/scripts/update_deals.py
```

Kým neprebehne prvá úspešná aktualizácia, `deals.json` je prázdny a blok ukazuje len správu,
že ponuky sa pripravujú.

> Explore endpoint nie je oficiálne verejné API. Ak ho momondo zmení alebo zablokuje
> požiadavky z GitHub Actions, parser (`parse_destinations`) je potrebné upraviť.
