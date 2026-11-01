# Mediálna gramotnosť – WordPress plugin pre Avada Builder

Plugin `medialna-gramotnost/` prenáša web z priečinka `medialnagramotnost/` do WordPressu.
Každá časť webu je samostatný prvok, ktorý sa vkladá a upravuje v **Avada Builderi**.
Bez Avady fungujú prvky ako shortcody.

Hotový balík na nahratie: `medialna-gramotnost.zip`.

## Inštalácia

1. WordPress → **Pluginy → Pridať nový → Nahrať plugin** → `medialna-gramotnost.zip` → Aktivovať.
2. **Nastavenia → Mediálna gramotnosť → Vytvoriť 11 ukážkových stránok**.
   Pri aktívnej Avade vzniknú stránky z kontajnerov a stĺpcov Avada (`[fusion_builder_container]`)
   s prvkami pluginu. Odkazy medzi stránkami sa nastavia automaticky.
3. Stránky sú **koncepty**. Skontrolujte ich, zverejnite a pridajte do menu Avada.
4. V nastaveniach môžete zapnúť lištu „Prišli ste o peniaze?“ a plávajúce tlačidlo **A+** na celom webe,
   zmeniť farby alebo použiť písma témy.

Logo sa v Avade nastavuje v téme, nie v plugine: **Avada → Options → Header → Logo** (nahrajte súčasné logo z medialnagramotnost.sk, ideálne aj verziu Retina a logo pre mobil). Ak web už beží na WordPresse, logo nájdete v Knižnici médií.

Opakovaný import stránky prepíše (nevytvára duplikáty). Svoje úpravy preto robte až po importe.

## Podstránka = niekoľko kontajnerov

Každá podstránka je v Avada Builderi poskladaná z mála ucelených prvkov, nie z desiatok drobných blokov:

- **Domov** začína jedným prvkom *MG Úvod stránky*, ktorý má ukážku podvodnej SMS priamo v sebe
  (karta **Ukážka správy** → „Zobraziť ukážku vedľa textu“). Netreba skladať stĺpce.
- **Atlas podvodov** má 3 kontajnery: úvod, upozornenie a jeden prvok *MG Atlas podvodov*.
  Filter, všetkých 12 podvodov aj ich ukážky sú položky tohto jedného prvku.
  Nový podvod pridáte tlačidlom **+ Pridať položku**.
- Trenažér, kvíz, kontrola správy, rýchle karty, slovník a kontakty sú tiež vždy jeden prvok s položkami.

Plugin nepridáva vlastné typy príspevkov ani ďalšie menu v administrácii. Všetko sa upravuje
priamo na stránke v builderi, okrem jednej obrazovky nastavení.

### MG Atlas podvodov

- Prvok má popisy oboch riadkov filtra (napr. `sms|SMS; email|E-mail`), text tlačidla „Všetko“,
  nadpisy „Varovné znaky“ a „Čo robiť“.
- Položka (jeden podvod) má dve karty:
  - **Obsah**: nadpis, kotva, štítky, ako to prebieha, varovné znaky a rady (oddelené `;`),
    zdroj a kategórie pre filter (napr. `sms seniori`);
  - **Ukážka správy**: typ, odosielateľ, predmet, adresa a text správy (syntax nižšie).

## Prvky v Avada Builderi

V Avada Builderi kliknite na **+ Prvok** a vyhľadajte „MG“.

| Prvok | Čo sa dá upraviť |
|---|---|
| MG Úvod stránky | nadtitulok, nadpis, zvýraznená časť, text, 2 tlačidlá, drobečková navigácia; voliteľne ukážka správy vedľa textu |
| MG Atlas podvodov | filter v dvoch riadkoch; položky: celý podvod (popis, varovné znaky, rady, zdroj, ukážka správy) |
| MG Nadpis sekcie | nadtitulok, nadpis, popis, tlačidlo, svetlý/tmavý text |
| MG Ukážka správy | typ (SMS, WhatsApp, e-mail, Facebook, web, hovor), odosielateľ, avatar, predmet, adresa, text správy s varovnými znakmi a vysvetlivkami |
| MG Popis podvodu | štítky, nadpis, popis s varovnými znakmi a radami (editor), zdroj, kategórie pre filter |
| MG Filter | skupina, tlačidlá filtra |
| MG Karty s odkazom | počet stĺpcov; položky: ikona, nadtitulok, nadpis, text, žltá veta, odkaz, štýl |
| MG Kroky / pravidlá | zoznam, červený zoznam alebo tmavé stĺpce; položky: nadpis, text |
| MG Zoznam varovaní | položky: štítok, nadpis, text, odkaz |
| MG Čísla | položky: číslo, popis, zdroj |
| MG Upozornenie | štýl (info, varovanie, nebezpečenstvo, ok), nadpis, text |
| MG Výzva s tlačidlom | nadpis, text, tlačidlo |
| MG Trenažér | otázka, texty tlačidiel a výsledkov, zhrnutie; položky: ukážka správy + správna odpoveď |
| MG Kontrola správy | texty výsledkov; položky: otázka, váha, „vždy vysoké riziko“ |
| MG Kontrola webovej adresy | predvyplnená adresa, príklady, vysvetlenie |
| MG Čo robiť | položky: situácia, nadpis postupu, kroky, tlačidlo |
| MG Kontakty | položky: názov, číslo, popis |
| MG Kvíz | texty; položky: príspevok, odpovede, správna odpoveď, vysvetlenie |
| MG Slovník | vyhľadávanie; položky: pojem, vysvetlenie |
| MG Rýchle karty | stĺpce, tlačidlo tlače; položky: všetky časti karty |
| MG Kartička do peňaženky | nadpis, pravidlá, čísla, riadky na dopísanie |
| MG Zoznam zdrojov | položky: názov, popis, odkaz |
| MG Lišta pomoci | text, odkaz |
| MG Väčšie písmo | popis pred tlačidlom A+ |

Každý prvok má v karte **Rozšírené** pole *CSS trieda* a *CSS ID (kotva)*.

### Ako písať ukážky správ

```
Slovenska posta: Vas balik caka. ==Doplatte 1,20 EUR== do 24 hodin: ==posta-sk.balik-info.top==
---
Drobný poplatok je návnada. Cieľom sú údaje z karty.
Odkaz nevedie na stránku pošty.
```

- `==text==` zvýrazní a očísluje varovný znak, pod `---` sú vysvetlivky v rovnakom poradí.
- `> text` vlastná správa, `! text` systémové upozornenie (WhatsApp).
- `btn:` tlačidlo, `img: popis | #farba` obrázok, `link: doména | nadpis` náhľad odkazu,
  `stats: vľavo | vpravo`, `foot:` pätička e-mailu, `title:` / `input:` / `alert:` pre web,
  `Volajúci: text` pre prepis hovoru.

### Zoznamy v jednom poli

Polia ako „Stĺpec 1 – body“ alebo „Pravidlá“ oddeľujú položky **bodkočiarkou**:
`Nečakáte balík; Malý doplatok; Cudzia adresa odkazu`.

## Štruktúra pluginu

```
medialna-gramotnost.php     načítanie, shortcody, štýly, lišta pomoci, A+
includes/elements.php       definícia všetkých prvkov a ich polí (jeden zdroj pravdy)
includes/avada.php          registrácia v Avada Builderi (fusion_builder_map)
includes/render.php         HTML výstup prvkov
includes/mock.php           makety SMS, WhatsApp, e-mail, Facebook, web, hovor
includes/helpers.php        bezpečné vypisovanie, zoznamy, odkazy
includes/settings.php       Nastavenia → Mediálna gramotnosť
includes/demo.php           obsah 11 ukážkových stránok
assets/css/mg.css           štýly (triedy mg-*, premenné --mg-*)
assets/js/mg.js             interakcie (číta údaje z HTML, funguje aj v živom editore)
assets/pdf/rychle-karty.pdf karty na tlač
```

Nový prvok pridáte do `mg_elements()` a funkcie `mg_render_{nazov}()`. Avada mapovanie aj shortcode
sa vytvoria automaticky. Témy môžu prvky upraviť filtrami `mg_elements`, `mg_avada_map_args`
a `mg_demo_pages`.

## Čo je overené

- WordPress 6.5 (PHP 8.3, SQLite): aktivácia, import 11 stránok, vykreslenie všetkých prvkov,
  žiadne chyby v logu, žiadne nespracované shortcody.
- V prehliadači (1360 px aj 390 px): žiadne pretekanie, trenažér, filter atlasu aj kariet, kontrola správy,
  kontrola adresy, rozhodovací strom, kvíz, slovník, A+ a lišta pomoci fungujú. Počet varovných znakov
  sedí s vysvetlivkami vo všetkých ukážkach.
- Avada mapovanie overené voči rozhraniu `fusion_builder_map` (35 záznamov, typy polí, predvolené hodnoty,
  rodič/potomok).
- **Neoverené na skutočnej Avade** (platená téma, v testovacom prostredí nebola). Pred nasadením
  skontrolujte hlavne vzhľad v živom editore Avada (Avada Live).
