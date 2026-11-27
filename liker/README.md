# Zlatý Žĺtok — web vaječných likérov

Animovaný one-page e-shop s vianočným vaječným likérom v hlavnej úlohe.
Platba **kartou cez Stripe Checkout** alebo **na dobierku**.
Bez build kroku a bez npm závislostí — HTML, CSS, JS a malý Node server.

## Spustenie

```bash
cd liker
cp .env.example .env      # doplňte Stripe kľúče
node server.js            # Node 18+
# otvorte http://localhost:8080
```

Bez `STRIPE_SECRET_KEY` beží platba kartou v **DEMO režime** — objednávka sa uloží
a zobrazí sa stránka poďakovania s označením „Demo“. Dobierka funguje vždy.

## Štruktúra

```
index.html           stránka (príbeh fľaše, likéry, odpočet, servírovanie, objednávka)
styles.css           dizajn v Apple štýle (systémové písmo, svetlý aj tmavý režim, vianočná červená ako akcent)
art.js               SVG ilustrácie: fľaša, pohár, fľaky (generované, žiadne obrázky)
app.js               scroll scéna, košík, formulár
dakujeme.html        poďakovanie po objednávke / platbe
data/products.json   produkty, ceny (v centoch), doprava, poplatok za dobierku
server.js            statické súbory + API objednávok + Stripe
orders/              uložené objednávky (JSON, mimo gitu)
```

## Produkty a ceny

Všetko je v `data/products.json`. Ceny sú v **centoch** (`1690` = 16,90 €).
Server prepočíta sumu vždy sám z tohto súboru — cenám z prehliadača neverí.
Farby etikety (`colors`) sa priamo premietnu do nakreslenej fľaše.

## Stripe

1. V [Stripe Dashboard](https://dashboard.stripe.com/apikeys) skopírujte **Secret key** do `STRIPE_SECRET_KEY`.
2. Nastavte `PUBLIC_URL` na verejnú adresu webu (kam sa zákazník vráti po platbe).
3. Webhook: **Developers → Webhooks → Add endpoint**
   `https://vasadomena.sk/api/stripe-webhook`, udalosti `checkout.session.completed`
   a `checkout.session.async_payment_succeeded`. Signing secret vložte do `STRIPE_WEBHOOK_SECRET`.
   Lokálne: `stripe listen --forward-to localhost:8080/api/stripe-webhook`.
4. Apple Pay / Google Pay zapnete v Stripe v **Settings → Payment methods**.

Ak webhook chýba, platba sa aj tak potvrdí pri návrate na `dakujeme.html`
(server si stav overí priamo v Stripe).

## Objednávky

Každá objednávka sa uloží do `orders/ZZ-RRMMDD-XXXXXX.json` so stavom
`new_cod` (dobierka), `awaiting_payment` → `paid` (karta) alebo `demo`.
Ak chcete dostávať upozornenia, nastavte `ORDER_WEBHOOK_URL` (napr. scenár v Make/Zapier,
ktorý pošle e-mail alebo správu do Slacku) — server tam pošle JSON každej novej a zaplatenej objednávky.

## API

| Metóda | Cesta | Popis |
|---|---|---|
| POST | `/api/order` | vytvorí objednávku; vráti `url` na Stripe alebo na poďakovanie |
| GET | `/api/order-status?order=…&session_id=…` | stav objednávky pre stránku poďakovania |
| POST | `/api/stripe-webhook` | Stripe webhook (overený podpis) |

Najjednoduchšie je, keď `server.js` servíruje aj samotný web (jedna doména, žiadne CORS).
Na statickom hostingu bez servera (napr. GitHub Pages) sa stránka zobrazí, no objednávku neodošle.

## Pred spustením naostro

- doplňte prevádzkovateľa, IČO, obchodné podmienky, reklamačný poriadok a GDPR v pätičke,
- skontrolujte licenciu na predaj liehovín a spotrebnú daň,
- texty o surovinách a várkach v `index.html` sú ukážkové — upravte podľa skutočnosti.

## Animácie

- **Príbeh fľaše** — sekcia `#story` je 4,2× vysoká obrazovka, scéna je `position: sticky`.
  Kapitoly a kľúčové snímky fľaše/pohára sú v `app.js` (`CH`, `BOTTLE_KEYS`, `GLASS_KEYS`, `SPLATS`).
- Rešpektuje `prefers-reduced-motion`.
- Na mobile sa fľaša zmenší, texty idú pod ňu, rad likérov sa posúva prstom.

## Ukážka pre klienta v jednom súbore

```bash
python3 build-ukazka.py   # vytvorí zlaty-zltok-ukazka.html
```

Celý web (CSS, JS, produkty) v jednom HTML súbore. Otvorí sa dvojklikom bez servera,
košík funguje a objednávka sa len nasimuluje (zobrazí poďakovanie s označením „Ukážka“).
Písma sa načítajú z Google Fonts, takže offline sa použije náhradné písmo.
