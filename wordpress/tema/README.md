# Mediálna gramotnosť – téma pre WordPress

Samostatná téma (nepotrebuje Avadu ani iný plugin). Obsah sa spravuje v administrácii
ako bežné príspevky, cez polia formulára.

Balík na nahratie: `medialna-gramotnost-tema.zip`.

## Inštalácia

1. **Vzhľad → Témy → Pridať novú → Nahrať tému** → `medialna-gramotnost-tema.zip` → Aktivovať.
2. **Vzhľad → Ukážkový obsah → Importovať**. Vytvorí:
   - 12 príkladov podvodov, 3 testy, 12 rýchlych kariet, 24 pojmov,
   - stránky Domov, Overiť správu, Dezinformácie, Pre seniorov, Pre mladých, Stalo sa mi to, Zdroje,
   - hlavné menu a menu v pätičke.

   Import nastaví domovskú stránku. Ak boli adresy webu v tvare `?p=123`, prepne ich na pekné adresy.
3. **Vzhľad → Prispôsobiť → Identita webu**: nahrajte logo.
4. **Vzhľad → Prispôsobiť → Mediálna gramotnosť**: texty domovskej stránky, lišta pomoci, farby, kontakty.

Opakovaný import prepíše položky z importu (podľa adresy). Vaše vlastné príklady, testy a karty zostanú.

## Čo sa kde upravuje

| V administrácii | Čo tam je | Kde sa zobrazí |
|---|---|---|
| **Príklady podvodov** | názov, popis (editor), štítky, zdroj, ukážka správy (typ, odosielateľ, text, vysvetlenia varovných znakov), kategórie *Kanál* a *Pre koho*, voľba „Zobraziť v Aktuálne podvody“ | `/podvody/` (atlas s filtrom), detail príkladu, domovská stránka, testy |
| **Testy** | typ (trenažér alebo kvíz), úvod, texty tlačidiel a výsledkov, zhrnutie, **otázky** (pridať, odstrániť, presunúť šípkami), **výber príkladov z atlasu** | `/testy/` a stránka testu; do stránky sa vloží aj `[mg_test id="123"]` |
| **Rýchle karty** | označenie, štítok, príklad, dva stĺpce bodov, žltá veta, rýchla otázka, odkaz, *Pre koho* | `/materialy/` (filter, tlač, PDF) |
| **Slovník** | pojem a vysvetlenie | `/slovnik/` s vyhľadávaním |
| **Stránky** | bežný blokový editor; nástroje sú v blokoch *Shortcode* | Overiť správu, Dezinformácie, Seniori, Mladí, Stalo sa mi to, Zdroje |
| **Vzhľad → Prispôsobiť** | logo, farby, lišta pomoci, všetky texty domovskej stránky, kontakty, pätička | celý web |
| **Vzhľad → Menu** | položky menu; triedou `mgt-urgent` bude položka červená | hlavička, pätička |

### Ako napísať ukážku podvodnej správy

Pole **Text správy**: každý riadok je jedna bublina alebo odsek. Varovný znak označíte `==takto==`.
Do poľa **Vysvetlenia varovných znakov** napíšte ku každému zvýrazneniu jeden riadok v rovnakom poradí.
Počítadlo pod poľami hneď ukáže, či počty sedia.

Skratky v texte správy:

| Zápis | Výsledok |
|---|---|
| `> text` | vaša odpoveď v chate (bublina vpravo) |
| `! text` | systémové upozornenie (WhatsApp) |
| `btn: text` | tlačidlo v e-maile alebo na webe |
| `img: popis \| #farba` | obrázok alebo video vo Facebook príspevku |
| `link: doména \| nadpis` | náhľad odkazu pod príspevkom |
| `stats: vľavo \| vpravo` | počty reakcií |
| `foot: text` | pätička e-mailu |
| `title:` `input:` `alert:` | nadpis, vstupné pole, vyskakovacie okno na webe |
| `Volajúci: text` | riadok prepisu hovoru |

### Nástroje do stránok (blok Shortcode)

`[mg_checker]…` kontrola podozrivej správy · `[mg_url_checker]` kontrola webovej adresy ·
`[mg_help_tree]…` čo robiť (rozhodovací strom) · `[mg_steps]…` kroky · `[mg_callout]` upozornenie ·
`[mg_cards]…` karty s odkazom · `[mg_kontakty]` kontakty z Prispôsobenia · `[mg_wallet]` kartička do peňaženky ·
`[mg_sources]…` zdroje · `[mg_test id="…"]` test · `[mg_priklad slug="sms-balik"]` ukážka jedného príkladu.
Hotové príklady zápisu sú v importovaných stránkach.

## Vzhľad

Redakčný štýl verejnoprávneho webu: pätkové nadpisy (Newsreader), písmo navrhnuté pre čitateľnosť
(Atkinson Hyperlegible Next), linky namiesto kariet, obdĺžnikové tlačidlá a žltá len ako zvýrazňovač
varovných znakov. Tlačidlo **A+** v hlavičke zväčší písmo v troch stupňoch. Hlavná farba, zvýrazňovač
a tmavá farba sa menia v Prispôsobení.

## Súbory

```
style.css, functions.php           hlavička témy, načítanie
header.php, footer.php             hlavička (logo, menu, A+, lišta pomoci), pätička (kontakty, menu)
front-page.php                     domovská stránka zo sekcií v Prispôsobení
archive-mg_priklad.php             atlas podvodov s filtrom; single-mg_priklad.php detail
archive-mg_test.php, single-mg_test.php     testy
archive-mg_karta.php, single-mg_karta.php   rýchle karty
archive-mg_pojem.php, single-mg_pojem.php   slovník
page.php, single.php, index.php, 404.php, taxonomy.php
inc/cpt.php                        typy obsahu a kategórie
inc/meta.php                       polia v administrácii, otázky testov
inc/customizer.php                 Prispôsobiť → Mediálna gramotnosť
inc/template-tags.php              vykreslenie príkladov, testov, kariet
inc/mock.php, render.php, helpers.php, elements.php   makety správ a nástroje (shortcody)
inc/demo.php, demo-data.php        ukážkový obsah
assets/css/components.css, theme.css, admin.css, editor.css
assets/js/theme.js, admin.js
```

## Čo je overené

- WordPress 6.5, PHP 8.3: aktivácia, import, všetkých 15 adries vracia 200, žiadne chyby PHP.
- Prehliadač (1440 px a 390 px): žiadne pretekanie; trenažér, test pre seniorov (príklady z atlasu),
  kvíz, filter atlasu a kariet, kontrola správy a adresy, rozhodovací strom, slovník, A+, mobilné menu.
- Administrácia (klasický formulár): nový príklad s ukážkou a kategóriami sa uloží a zobrazí v atlase
  aj na domovskej stránke; v teste sa pridá otázka, presunie poradie a pripojí príklad z atlasu.
- Blokový editor som v testovacom prostredí nespustil (chýbali mu skripty). Polia sú klasické meta boxy,
  ktoré blokový editor zobrazuje pod obsahom. Pred nasadením ich skontrolujte aj tam.
