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

**Najrýchlejšia cesta: kliknite na text priamo v náhľade.** Vo
Vzhľad → Prispôsobiť sa pri každom texte objaví modrá ceruzka. Kliknutím
na ňu sa vľavo otvorí práve to pole — nemusíte ho v zozname hľadať.
Funguje to pri nadpisoch, vetách, cenách, kontakte, formulári aj pri
telefóne v úvode.

Videá, vizuály a ceny sú položky obsahu, nie texty — tie ceruzku nemajú.
Keď ste prihlásený a pozeráte sa na web, máte ich v hornej lište pod
**IDEANET**: Reels, Vizuály, Balíky a ceny, Menu aj prijaté dopyty.

Keď niečo meníte radšej zo zoznamu: v administrácii je vľavo položka
**IDEANET**. Prvá podpoložka
**Kde čo upraviť** je rozcestník — vypíše všetky sekcie aj typy obsahu
a každý odkaz vedie rovno na miesto, kde sa to mení. Dve veci, ktoré sa
hľadajú najčastejšie, sú hneď pod ňou: **Kontakt a siete** a **Formulár**.

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
| E-mail, telefón, prezývku, fakty pod kontaktom | IDEANET → **Kontakt a siete** |
| Kam chodia dopyty, popisy polí, hlášky formulára | IDEANET → **Formulár** |

### Video v úvodnej obrazovke

Prispôsobiť → IDEANET → **Úvodná obrazovka**. Dole sú tri polia:
*Video v telefóne (MP4)*, nepovinný *WebM* a *Prvý obrázok videa*.

Nahrajte krátku vertikálnu slučku (9:16, pár sekúnd, bez zvuku —
prehráva sa automaticky a stlmene). Kým je pole prázdne, v telefóne
zostane animovaná obrazovka s tlačidlom, takže sa nič nepokazí.

> **Dve miesta, kde sa to dá prehliadnuť.** Okno s médiami sa otvára na
> záložke **Nahrať súbory**. Ak už video v knižnici máte, prepnite hore
> na **Knižnica médií** — inak to vyzerá, že tam nič nie je. A po výbere
> treba ešte hore kliknúť na **Zverejniť**, inak sa zmena neuloží.

Telefón sa videu prispôsobí: video sedí hore v pomere **9:16**, celé
a neorezané, a dole zostane voľný pás s popisom. Ak nahráte iný pomer
(napríklad 16:9), oreže sa — do telefónu patrí vertikál.

Komu má systém vypnuté animácie, tomu sa video nespustí a uvidí prvý
obrázok. Preto sa oplatí ho vyplniť.

### Pridanie videa

**Reels → Pridať nový.** Nadpis je názov na karte, **Výpis** (vpravo dole,
prípadne cez Nastavenia → Výpis) je popis pod ním.

- **Náhľadový obrázok** je plagát videa, na výšku 9:16. Ak ho nemáte,
  dá sa použiť pole *Plagát — náhradný obrázok*.
- **Video (MP4)** nahráte cez tlačidlo *Vybrať z knižnice*. Ak je súbor
  veľký, nahrajte ho radšej inam a sem vložte priamu adresu.
- *Video (WebM)* je nepovinné — je to druhý formát pre staršie prehliadače.
- **Dĺžku nevypĺňajte** — prečíta sa sama z videa, hneď ako prehliadač
  načíta jeho popis. Pole slúži len vtedy, keď video ešte nemáte alebo
  chcete ukázať iný čas. Kým nie je ani jedno, bublina s časom sa
  nezobrazí vôbec.
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

### Ponuka redizajnu webov

Samostatná podstránka s akčnou ponukou na redizajn prezentačného webu.
Vznikla sama pod názvom **Redizajn webov** a používa šablónu
**Ponuka redizajnu webov**. Odkaz na ňu je v hornom menu, v rozbaľovacom
menu na mobile (tlačidlo **Viac**) aj v pätičke. V spodnej lište na mobile
nie je — tam sú štyri ikony a tlačidlo Viac. Ak ho niekde nechcete,
vyhoďte položku vo **Vzhľad → Menu**; sama sa späť nepridá.

Priamy odkaz na úpravu tej stránky je v **IDEANET → Kde čo upraviť →
Podstránka s ponukou redizajnu webov**.

**Texty sa upravujú priamo pri stránke**, nie v Prispôsobiť. Otvorte
**Stránky → Redizajn webov** a pod editorom nájdete boxy v tom poradí,
ako sekcie idú na webe: *Ponuka — cena, termín a počty*, *Úvod stránky*,
*Poznáte to?*, *Čo dostanete*, *Ako to prebieha*, *Ukážky klientov*, *Cena*,
*Pre koho ponuka je*, *Časté otázky* a *Záverečná výzva a formulár*.

Prázdne pole znamená, že sa použije predvolený text z témy. Pri
niektorých poliach prázdna hodnota skryje celú sekciu — je to napísané
pri danom poli.

Táto jedna stránka sa otvára v klasickom editore. Blokový editor dáva
takéto boxy do zasunutej lišty na spodku obrazovky, ktorú treba najprv
vytiahnuť; v klasickom sú rovno pod sebou. Ostatných stránok sa to
netýka.

Cena, termín a počty sú uložené len raz a do textov sa dopĺňajú samy:

| V texte napíšete | Doplní sa |
| --- | --- |
| `{cena}` | akčná cena |
| `{cena_pred}` | cena pred akciou (preškrtnutá) |
| `{termin}` | dokedy ponuka platí |
| `{miest}` | koľko miest je v ponuke |
| `{volnych}` | koľko miest ešte zostáva |
| `{podstranok}` | koľko podstránok je v cene |
| `{dni}` | za koľko dní je web hotový |

Keď sa teda zmení cena, stačí ju prepísať v jednom poli a zmení sa
v úvode aj v cenníku naraz. **Koľko miest ešte zostáva** je vlastné
pole — po každej objednávke ho znížte a záverečná výzva sa prepíše.

**Ukážky klientov** sa pridávajú v IDEANET → **Redizajn — ukážky
klientov**: nadpis je názov klienta alebo odboru, obsah je jedna veta
o výsledku a do poľa sa vyberie snímka hotového webu. Keď vyplníte aj
**Odkaz na web**, pod ukážkou pribudne tlačidlo, ktoré ten web otvorí
v novom okne; ukážke bez odkazu tlačidlo nepribudne. Jeho názov sa mení
v boxe *Ukážky klientov* pri stránke. Kým tam nič nie je, sekcia
s ukážkami sa na podstránke vôbec nezobrazí.

Snímka webu: pomer **16 : 11** na šírku, odporúčane 1600 × 1100 px.
Čokoľvek nahráte sa oreže na tento pomer zhora, takže z vysokej snímky
celej stránky zostane hlavička a prvá obrazovka.

Dopyt z tejto podstránky má vo výbere prednastavené **Redizajn
prezentačného webu**, takže sa v prijatých dopytoch dá odlíšiť od dopytu
na video. Názov tej voľby sa mení v boxe *Záverečná výzva a formulár*.

### Stránka 404

Zobrazí sa, keď niekto príde na adresu, ktorá na webe neexistuje —
preklep v odkaze, zmazaná stránka, starý odkaz z Googlu. Nie je to
stránka v Stránkach, je to šablóna témy, takže sa nedá zmazať a je tam
vždy.

Okrem nadpisu má hľadanie a odkazy na hlavné sekcie, aby sa z nej dalo
niekam kliknúť. Odkazy sa berú z **horného menu**, takže ich netreba
udržiavať dvakrát. Texty sú v Prispôsobiť → IDEANET → **10 · Stránka
404**; keby ste nechceli odkazy, vymažte *Nadpis nad odkazmi*.

Vyskúšate ju tak, že si za adresu webu dopíšete nezmysel, napríklad
`vasadomena.sk/toto-tu-nie-je`.

## Ktorú verziu mám nainštalovanú

Číslo verzie je v administrácii v **IDEANET → Kde čo upraviť → Verzia
témy …** a vo **Vzhľad → Témy** pod názvom témy. Keď nahrávate novú
tému cez Vzhľad → Témy → Pridať novú → Nahrať tému, WordPress ukáže obe
čísla vedľa seba — súčasné a nahrávané —, takže je vidieť, čo sa čím
nahrádza.

Čo sa v ktorej verzii zmenilo, je v [`ideanet/CHANGELOG.md`](ideanet/CHANGELOG.md).
Ten súbor sa inštaluje spolu s témou, takže sa dá prečítať aj priamo
na hostingu.

## Aktualizácie

Téma nie je v katalógu na wordpress.org, ale aktualizuje sa rovnako ako
hociktorá iná: keď vyjde nové vydanie, WordPress ho sám nájde a vo
**Vzhľad → Témy** ponúkne *Aktualizovať*. Jedno kliknutie, nič sa
nenahráva ručne.

Stojí to na hlavičke `Update URI` v `style.css`, ktorú WordPress pozná
od verzie 6.1, a na vydaniach v tomto repozitári. Kontroluje sa to
dvakrát denne; kto nechce čakať, klikne v administrácii na
**IDEANET → Kde čo upraviť → Verzia témy … — zistiť, či je novšia**.

**Obsah a nastavenia sa pri aktualizácii nestratia.** Texty, ceny, menu
aj videá sú v databáze, nie v súboroch témy. Prepíšu sa len súbory témy.
Ukážkový obsah sa znovu nepridá.

### Ako vydať novú verziu

```bash
./wp-theme/release.sh 1.1.0
```

Skript zmení verziu v hlavičke témy, poskladá `style.css`, zabalí tému
do `wp-theme/ideanet-theme.zip` a vypíše, čo ďalej — zapísať zmenu
a vytvoriť vydanie. **Odmietne pracovať**, keď v
`ideanet/CHANGELOG.md` chýba oddiel pre tú verziu alebo keď sa číslo
oproti poslednému vydaniu nezmenilo — inak by sa vydania nedali
rozlíšiť:

```bash
git add -A && git commit -m "Téma 1.1.0" && git push
gh release create theme-v1.1.0 wp-theme/ideanet-theme.zip \
  --title "Téma 1.1.0" --notes "Čo je nové…"
```

Na GitHube sa to isté dá spraviť cez *Releases → Draft a new release*:
značka `theme-v1.1.0`, k vydaniu priložiť súbor `ideanet-theme.zip`.

Dve veci musia sedieť, inak sa aktualizácia neponúkne:

- **priložený súbor sa musí volať `ideanet-theme.zip`** — podľa toho si
  ho WordPress nájde. Zdrojový archív, ktorý GitHub pribalí sám,
  obsahuje celý repozitár aj so statickou verziou webu a témou by nebol;
- **verzia v hlavičke témy musí byť vyššia** než nainštalovaná. O to sa
  stará `release.sh`.

Značka smie byť `theme-v1.1.0`, `v1.1.0` aj `1.1.0`.

### Keby bol repozitár súkromný

Do `wp-config.php` patrí prístupový token:

```php
define( 'IDEANET_UPDATE_TOKEN', 'github_pat_…' );
```

Iný repozitár sa nastaví cez `define( 'IDEANET_UPDATE_REPO', 'kto/co' );`.

### Ručná cesta

Keď aktualizácia z nejakého dôvodu nejde (napríklad hosting nepustí
WordPress na internet), funguje aj staré dobré nahratie: Vzhľad → Témy →
Pridať novú → Nahrať tému, vybrať `ideanet-theme.zip` a potvrdiť
nahradenie. Obsah ani nastavenia sa tým nestratia.

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
├── release.sh             pripraví nové vydanie (verzia + ZIP)
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
    │   ├── updater.php        aktualizácie z vydaní na GitHube
    │   └── demo.php           ukážkový obsah po aktivácii
    ├── template-parts/    jednotlivé sekcie úvodnej stránky
    ├── page-templates/    šablóna podstránky s ponukou redizajnu
    └── assets/js/app.js   interakcie (pásy, kalkulačka, formulár)
```

Obsah sa do JavaScriptu dostáva cez `window.IDEANET` — portfólio aj čísla
kalkulačky skladá PHP v `inc/setup.php` (`ideanet_js_data`). V `app.js`
preto netreba nič meniť, keď sa mení cenník alebo pribudne video.
