# Kontrola GDPR lišty z Google AdSense

Nástroj zistí, prečo sa na relife.sk nezobrazuje lišta súhlasu (správa o súhlase Google Funding Choices) pre účet `pub-2577354057353027`, a navrhne opravu.

## 1) Najrýchlejšie: v prehliadači (bez inštalácie)

1. Otvorte `https://relife.sk` v **novom anonymnom okne** (bez prihlásenia do WordPressu, bez VPN, vypnutý blokovač reklám).
2. Počkajte ~10 sekúnd (reklamný skript sa načíta až po načítaní stránky).
3. Stlačte **F12** → karta **Console**.
4. Vložte celý obsah súboru `adsense-cmp-check.js` a stlačte **Enter**.

Vypíše sa zoznam kontrol (✅ v poriadku, ⚠️ upozornenie, ❌ chyba) a na konci **VÝSLEDOK** a **NÁVRH OPRAVY**.

## 2) V príkazovom riadku (podrobnejšie, aj pohľad zo servera)

```bash
npm i playwright && npx playwright install chromium     # jednorazovo
node adsense-cmp-check.node.js https://relife.sk
# voliteľné: --mobile  --wait 15  --screenshot lista.png  --headed  --pub 2577354057353027
```

Spúšťajte zo slovenskej/EÚ siete: Google ukazuje správu len návštevníkom z EHP, UK a Švajčiarska. Navyše skontroluje surové HTML zo servera (cache), hlavičku CSP a priamo odpoveď Google pre vaše ID.

## Čo nástroj kontroluje

1. Je na stránke skript `adsbygoogle.js` a so správnym ID vydavateľa?
2. Načítal sa a nezablokoval ho blokovač/CSP?
3. Vyžiadal si Google správu (`fundingchoicesmessages.google.com`)? Ak nie, správa nie je publikovaná.
4. Je lišta v stránke a je viditeľná, alebo Google hlási „žiadna aktívna správa“ (`googlefcInactive`)?
5. TCF rozhranie (`__tcfapi`), už uložený súhlas (cookies `FCCDCF`, `FCNEC`), konflikt s inou cookie lištou (aj s lištou témy RELIFE), `ads.txt`, prihlásený admin, región.

## Najčastejšie príčiny (podľa skúseností)

| Zistenie | Oprava |
|---|---|
| Google si správu nevyžiadal / `googlefcInactive` | AdSense → **Súkromie a správy** → **Európske predpisy** → vytvoriť správu a **Publikovať**; stránka relife.sk musí byť v zozname Stránky a Pripravená. Po publikovaní počkať 1–2 h. |
| Skript AdSense na stránke chýba | Customizer → RELIFE – Reklamy: ID vydavateľa alebo celý kód do hlavičky; vyprázdniť cache Starcache. |
| Iné ID vydavateľa | Opraviť ID. |
| Už ste súhlas dali (cookie `FCCDCF`) | Testovať v anonymnom okne. |
| Mimo EÚ / VPN | Testovať zo SK. |
| Iná cookie lišta | Ponechať jednu (v Customizeri vypnúť „Zobraziť cookie lištu“, ak má ísť len Google lišta). |
| CSP / blokovač | Povoliť `pagead2.googlesyndication.com`, `fundingchoicesmessages.google.com`, `*.gstatic.com`. |

Nástroj nič nemení ani neodosiela údaje; len číta stránku a robí požiadavky na `/ads.txt` a Google Funding Choices.
