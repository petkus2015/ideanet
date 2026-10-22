# IDEANET — WordPress téma

Tá istá stránka ako statická verzia v koreni repozitára, ale celý obsah
sa upravuje vo WordPresse. Žiadny plugin na vlastné polia (ACF a spol.)
nie je potrebný — všetko stojí na jadre WordPressu.

- **Potrebuje:** WordPress 6.4+, PHP 7.4+
- **Odskúšané na:** WordPress 7.1.3, PHP 8.4
- **Zložka témy:** `wp-theme/ideanet`

## Inštalácia

1. Zložku `wp-theme/ideanet` zabaľte do ZIP (alebo ju nahrajte cez FTP do
   `wp-content/themes/ideanet`).
2. Vzhľad → Témy → Pridať novú → Nahrať tému.
3. Aktivovať.

Pri prvej aktivácii sa web sám naplní ukážkovým obsahom — tými istými
videami, cenami a textami, aké má statická verzia. Nič z toho nie je
natvrdo v kóde: všetko sa dá prepísať alebo zmazať. Vytvoria sa aj štyri
menu, obe stránky s ponukou na jednu stranu a priradia sa na svoje miesta.

Ukážkový obsah sa nahráva **iba raz**. Ak ho zmažete a chcete ho späť,
zmažte v databáze možnosť `ideanet_seeded` a tému znovu aktivujte.

## Kde sa čo upravuje

V administrácii je vľavo položka **IDEANET** a v nej rozcestník.

| Čo chcete zmeniť | Kde to je |
|---|---|
| Texty, nadpisy, ceny v kalkulačke, kontakt, odkazy na siete | Vzhľad → **Prispôsobiť** → IDEANET |
| Menu hore, spodnú lištu na mobile, rozbalovacie menu, pätičku | Vzhľad → **Menu** |
| Videá v portfóliu | **Reels** |
| Ukážky vizuálov | **Vizuály a identita** |
| Ceny a balíky v cenníku | **Balíky a ceny** |
| Referencie klientov | **Referencie** |
| Tri karty v sekcii Služby | **Služby** |
| Kroky v sekcii Proces | **Proces** |
| Podmienky spolupráce | **Podmienky** |
| Dopyty, ktoré prišli z formulára | **Prijaté dopyty** |
| Fotku vedľa mena | Prispôsobiť → IDEANET → Značka a hlavička |

### Pridanie videa

**Reels → Pridať nový.** Nadpis je názov na karte, **Výpis** (vpravo dole,
prípadne cez Nastavenia → Výpis) je popis pod ním.

- **Náhľadový obrázok** je plagát videa, na výšku 9:16. Ak ho nemáte,
  dá sa použiť pole *Plagát — náhradný obrázok*.
- **Video (MP4)** nahráte cez tlačidlo *Vybrať z knižnice*. Ak je súbor
  veľký, nahrajte ho radšej inam a sem vložte priamu adresu.
- *Video (WebM)* je nepovinné — je to druhý formát pre staršie prehliadače.
- **Poradie** kariet riadi pole *Poradie* v boxe Vlastnosti stránky.

Keď video nevyplníte, karta zostane na plagáte a nespadne.

### Zmena cien

Ceny nie sú na jednom mieste náhodou — každý balík je samostatná položka
v **Balíky a ceny**:

- **Záložka cenníka** rozhoduje, či sa objaví pri firmách, agentúrach
  alebo eventoch.
- **Podoba** je buď *karta* (veľká dlaždica s odrážkami, ako START/GROW/PRO)
  alebo *riadok* (úzky riadok s cenou vpravo, ako white-label položky).
- **Zaradiť do bloku** zoskupí riadky pod vlastný medzinadpis (Jednorazová
  produkcia, Podcast, Vizuály, Grafika na event). Riadok bez bloku sa
  vypíše v hlavnom zozname záložky.

Každý balík sa automaticky objaví aj vo výbere **„O čo máte záujem"**
v kontaktnom formulári, aj na stránke s ponukou na jednu stranu. Cenu
teda stačí zmeniť na jednom mieste.

### Kalkulačka

Prispôsobiť → IDEANET → **Kalkulačka — ceny**. Sú tam všetky čísla:
cena prvého Reelu, cena každého ďalšieho, poldeň, celý deň, koľko Reels
je v ktorom bloku v cene, sadzba za kilometer aj mestá bez poplatku.

**Doplnky** sa píšu po riadkoch, stĺpce oddeľuje zvislá čiara:

```
Same-day dodanie — ešte v deň natáčania | 50 | percento
RAW materiál z natáčania | 80 | jednorazovo
Správa sociálnych sietí | 300 | mesacne
```

- `jednorazovo` — pripočíta sa pevná suma,
- `percento` — pripočíta sa percento zo základnej ceny,
- `mesacne` — do jednorazovej sumy sa nepočíta, uvedie sa zvlášť.

Rovnako fungujú aj ostatné viacstĺpcové polia (odbory v sekcii Pre koho,
fakty pod kontaktom, karty pri white-label spolupráci).

### Formulár

Prispôsobiť → IDEANET → **Formulár**. Nastavíte tam adresu, kam dopyty
chodia (prázdne = e-mail z kontaktu), predmet, popisy polí aj hlášky.

Každý dopyt sa **najprv uloží** ako položka v *Prijaté dopyty* a až potom
sa posiela e-mail. Keď hosting e-maily neodošle — čo sa stáva —, dopyt sa
nestratí. Formulár funguje aj s vypnutým JavaScriptom. Proti robotom má
skryté pole a jednominútovú brzdu na jednu IP adresu.

### Menu

Vzhľad → Menu. Téma má štyri miesta:

- **Hlavné menu (hore)** — odkazy v hlavičke,
- **Spodná lišta na mobile** — najviac štyri položky, piate je tlačidlo *Viac*,
- **Rozbalovacie menu na mobile** — čo sa otvorí pod tlačidlom *Viac*,
- **Pätička**.

Pri každej položke si v spodnej lište môžete vybrať **ikonu**. Keď ju
nevyberiete, téma ju odhadne z odkazu.

Odkazy na sekcie úvodnej stránky píšte ako vlastný odkaz s mriežkou,
napríklad `https://vasadomena.sk/#cennik`. Na úvodnej stránke sa z toho
sama urobí kotva, takže skrolovanie aj zvýrazňovanie práve čítanej sekcie
funguje; z podstránky odkaz vedie späť na úvod.

### Vypnutie sekcie

Prispôsobiť → IDEANET → **Nadpisy sekcií**. Pri každej sekcii je
zaškrtávacie políčko. Odškrtnutím zmizne zo stránky aj z náhľadu.

### Ponuka na jednu stranu (PDF)

Dve stránky, ktoré vznikli pri aktivácii. Používajú šablónu **Ponuka na
jednu stranu (tlač / PDF)** a v boxe vpravo sa im nastavuje, ktorý cenník
vypíšu. Text okolo napíšete normálne v editore. Ceny si berú z rovnakých
balíkov ako cenník, takže sa to nemá ako rozísť.

Pri tlači (Ctrl+P → Uložiť ako PDF) sa tmavé pozadie, menu aj tlačidlá
vypnú a strana vyjde čitateľná na bielom papieri.

## Úprava vzhľadu

Nerobte to priamo v `style.css` — ten súbor sa generuje. Buď
Prispôsobiť → **Doplnkové CSS**, alebo si vytvorte detskú tému
(child theme).

Ak vzhľad meníte v kóde, patrí to do `styles.css` v koreni repozitára
(spoločný dizajn so statickou verziou) alebo do `theme-extra.css`
(len pre WordPress — príspevky, stránky, diskusia, tlač). Potom spustite:

```bash
./wp-theme/build-style.sh
```

Skript poskladá `wp-theme/ideanet/style.css` z hlavičky témy, spoločného
dizajnu a doplnkov. Tak sa obe verzie nerozídu.

## Ako je to postavené

```
wp-theme/
├── build-style.sh         poskladá style.css témy zo spoločného dizajnu
├── theme-header.css       hlavička témy
├── theme-extra.css        štýly navyše (príspevky, diskusia, tlač)
└── ideanet/
    ├── style.css          generované: hlavička + dizajn + doplnky
    ├── functions.php      načítanie častí
    ├── inc/
    │   ├── defaults.php       predvolené texty a čísla
    │   ├── helpers.php        pomocníci pre šablóny, menu, ikony
    │   ├── content-types.php  Reels, Vizuály, Balíky… a ich vlastné polia
    │   ├── setup.php          podpora témy, štýly, dáta pre JavaScript
    │   ├── customizer.php     celý panel IDEANET v Prispôsobiť
    │   ├── pricing.php        výpis cenníka
    │   ├── form.php           spracovanie dopytu
    │   └── demo.php           ukážkový obsah po aktivácii
    ├── template-parts/    jednotlivé sekcie úvodnej stránky
    ├── page-templates/    ponuka na jednu stranu
    └── assets/js/app.js   interakcie (pásy, kalkulačka, formulár)
```

Obsah sa do JavaScriptu dostáva cez `window.IDEANET` — portfólio aj čísla
kalkulačky skladá PHP v `inc/setup.php` (`ideanet_js_data`). V `app.js`
preto netreba nič meniť, keď sa mení cenník alebo pribudne video.
