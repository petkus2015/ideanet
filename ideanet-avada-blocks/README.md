# IDEANET — bloky pre Avada Builder

WordPress plugin, ktorý pridá do Fusion Builder (Avada Builder) sadu
editovateľných blokov pre web IDEANET — hero, služby, portfóliové
karusely, proces, cenník, školenia, referencie a kontakt. Všetky texty,
obrázky, videá aj opakovateľné položky (referencie, ukážky, kroky,
cenové balíky) sa dopĺňajú priamo v builderi, bez úpravy kódu.

Vizuálne vychádza z pôvodného statického webu (priečinok `../index.html`
a spol. v tomto repozitári) — tmavá paleta, akcent `#ff0056`, písma
Inter + Instrument Serif (kurzíva), rovnaký karusel (desktop viac
kariet vedľa seba, mobil 1,5 karty so swajpovaním). Interaktívne prvky
(karty, tlačidlá, karusel, cenník, referencie, kroky procesu…) majú
jemné hover efekty (nadvihnutie, tieň, farebné zvýraznenie, pohyb
šípky) — pozri sekciu *Technické poznámky*.

**Cieľová kompatibilita:** Avada **7.15.5**, Avada Builder (Fusion
Builder) **3.15.5**. Registrácia blokov používa dlhodobo dokumentované,
verejné Fusion Builder Element API (`fusion_builder_map()` +
`add_shortcode()`) — rovnaké, aké Avada popisuje vo svojej oficiálnej
dokumentácii pre vlastné/tretie-strany elementy. Keďže tento kód nebol
spustený priamo proti živej inštancii Avady 7.15.5, **plugin obsahuje
vstavanú diagnostickú stránku** (Nástroje → IDEANET bloky), ktorá po
aktivácii priamo na vašom serveri overí, či sa všetko zaregistrovalo
a vykresľuje správne — pozri sekciu *Diagnostika* nižšie. Ak by na danej
verzii niečo nesedelo, táto stránka to ukáže okamžite a presne, bez
ďalšieho hádania.

## Čo plugin obsahuje a čo nie

Toto sú **obsahové bloky** — hero, karty, karusely, cenník atď. —
nie kompletná šablóna stránky. Hlavičku (navigácia, logo), pätičku
a farby pozadia celej stránky rieši vlastný Header/Footer Builder
Avady; tento plugin sa nimi zámerne nezaoberá, aby sa neprelínal
s nastaveniami témy.

## Inštalácia

1. Celý priečinok `ideanet-avada-blocks` zazipujte (musí byť zazipovaný
   priamo tento priečinok, nie jeho obsah zvlášť).
2. WordPress admin → **Pluginy → Pridať nový → Nahrať plugin** → vyberte
   zip → **Inštalovať** → **Aktivovať**.
3. Podmienka: aktívna téma **Avada** so zapnutým **Fusion Builderom**
   (bežná predvolená konfigurácia). Bez neho sa bloky v builderi
   nezobrazia — plugin vás na to upozorní hláškou v administrácii.
4. Otvorte ľubovoľnú stránku v **Avada Builderi** → v zozname prvkov
   nájdete kategóriu **IDEANET** s ôsmimi blokmi popísanými nižšie.

**Odporúčanie:** každý blok vložte do **Full Width Row** (Avada Builder
→ pridať riadok → Full Width), nie do bežného orámovaného kontajnera —
bloky majú vlastné plné pozadie a počítajú s tým, že siahajú od okraja
po okraj obrazovky.

## Diagnostika (Nástroje → IDEANET bloky)

Hneď po aktivácii choďte do **WordPress admin → Nástroje → IDEANET bloky**.
Táto stránka bez hádania ukáže:

- je aktívna téma rozpoznaná ako Avada a akú má verziu,
- existuje `fusion_builder_map()` a trieda `FusionBuilder` (teda beží
  Fusion Builder),
- koľkokrát sa odpálil hook `fusion_builder_before_init`,
- či sa reálne zaregistroval každý z ôsmich shortcodov
  (`add_shortcode()`),
- **samo-test** — každý shortcode sa priamo na vašom serveri skutočne
  vykreslí s testovacími dátami (`do_shortcode()`) a stránka nahlási
  OK/zlyhanie aj s PHP chybovou hláškou, ak nejaká nastala,
- dole je **kopírovateľný textový report** so všetkým vyššie — označte,
  skopírujte, pošlite, keď niečo nesedí. Ušetrí to kolo manuálneho
  hľadania v `debug.log`.

## Zoznam blokov

| Blok | Shortcode | Na čo slúži |
|---|---|---|
| IDEANET — Navigácia | `[ideanet_nav]` | Sticky in-page menu s odkazmi na sekcie stránky, logom/menom a voliteľným tlačidlom — nie je to hlavná hlavička webu (tú rieši Avada Header Builder), pozri nižšie |
| IDEANET — Hero | `[ideanet_hero]` | Úvodná sekcia s nadpisom, dvoma tlačidlami a karuselom posledných realizácií |
| IDEANET — O mne | `[ideanet_about]` | Väčšia osobná fotka (vľavo/vpravo) + krátky príbeh, zvýraznené body a tlačidlo |
| IDEANET — Služby | `[ideanet_services]` | Tri (aj viac) karty disciplín s ikonou, popisom a zoznamom bodov |
| IDEANET — Portfólio karusel | `[ideanet_carousel]` | Znovupoužiteľný karusel ukážok — vložte ho trikrát (video, grafika, sociálne siete), zakaždým s inými nastaveniami |
| IDEANET — Proces | `[ideanet_process]` | Očíslované kroky spolupráce |
| IDEANET — Cenník | `[ideanet_pricing]` | Cenové balíky, jeden môže byť zvýraznený ako odporúčaný |
| IDEANET — Školenia pre firmy | `[ideanet_training]` | Sekcia „pre koho / čo si odnesú” + karta formátov a cien |
| IDEANET — Referencie | `[ideanet_testimonials]` | Citáty klientov |
| IDEANET — Kontakt | `[ideanet_contact]` | Kontaktné údaje, box dostupnosti a formulár (odosielanie cez `mailto:`) |

### O mne — osobná fotka a krátky príbeh

Blok **IDEANET — O mne** je samostatná sekcia (odporúčame vložiť ju
hneď za Hero) s dvojstĺpcovým layoutom: na jednej strane väčšia
portrétová fotka (pomer 4:5, jemný zaoblený rám a mäkký farebný
odlesk za ňou), na druhej kicker, nadpis, krátky príbeh, zoznam
zvýraznených bodov (napr. roky skúseností, počet projektov) a
voliteľné tlačidlo. Pole **„Strana fotky (desktop)”** určuje, či je
fotka vpravo (predvolené) alebo vľavo — na mobile sa vždy zobrazí
hore, nad textom. Fotka reaguje na pohyb kurzora jemným 3D náklonom,
rovnako ako karty služieb a cenníka. Ak pole „Fotka” necháte prázdne,
zobrazí sa len text bez prázdneho miesta po fotke.

### Navigácia — sticky in-page menu

Blok **IDEANET — Navigácia** je doplnková lišta priamo v obsahu
stránky (nie hlavná hlavička webu — tú aj naďalej rieši Avada Header
Builder). Pri scrollovaní zostáva prilepená hore, zvýrazní odkaz
sekcie, ktorá je práve vo výreze (scrollspy) pomocou jedného kĺzavého
podčiarknutia, ktoré sa plynule presúva medzi položkami (aj pri
prejdení myšou — dočasne „skočí” pod položku pod kurzorom a po
odídení sa vráti k aktívnej), a na mobile sa zbalí do hamburger menu.

Aby odkazy fungovali, cieľová sekcia musí mať nastavenú **rovnakú
kotvu** v poli „Kotva pre odkazy” daného bloku. Service/Cenník/
Referencie/Kontakt majú predvolené kotvy `sluzby` / `cennik` /
`referencie` / `kontakt`; Proces a Školenia kotvu nemajú predvolenú —
ak na ne chcete odkazovať, vyplňte ju ručne a zhodne nastavte aj
odkaz v položke menu. Portfólio karusely majú kotvu `video` / `grafika`
/ `social` (nemenené).

Položky menu sa dajú pridávať rovnako ako ostatné repeatery — buď
priamo v Avada Builderi (pole „Položky menu”), alebo spoľahlivejšie
cez **Nástroje → IDEANET obsah → Navigácia — položky menu**.

**Logo a meno.** Od verzie **1.7.0** má blok dve nové nepovinné polia:
**„Logo”** (nahrajte obrázok, odporúčaná výška okolo 64 px, PNG/SVG s
priehľadným pozadím) a **„Podtitul”**. Značka má zvyčajný formát
osobného branda „Meno **by** Štúdio” — pole **„Meno”** (predvolene
„Peter Miškus”) sa zobrazí tučným písmom ako hlavný text, pole
**„Podtitul”** (predvolene „by IDEANET”) pod ním menším, tlmeným
písmom. Logo, meno aj podtitul sú nepovinné — môžete mať len text, len
logo, ľubovoľnú kombináciu, alebo nič z toho (blok sa jednoducho
prispôsobí tomu, čo je vyplnené). Na mobile sa podtitul skrýva, aby
ostalo miesto pre menu a tlačidlo.

**Modernejšia lišta.** Okrem kĺzavého podčiarknutia (od v1.5.0) má
lišta od verzie **1.7.0** aj:
- **tenký farebný pásik dole**, ktorý ukazuje priebeh prečítania
  stránky (0–100 % podľa toho, koľko bolo odscrollované) — bežný
  moderný prvok na jednostránkových weboch;
- **zjemnenie pri scrollovaní** — po odscrollovaní sa lišta aj logo
  mierne zmenšia (menej miesta na obrazovke), s plynulým prechodom.

### Portfólio karusel — jeden blok, tri použitia

Na pôvodnom webe boli tri karusely (Videografia 9:16 video, Grafický
dizajn 4:5 obrázky, Sociálne siete 9:16 video). V builderi je to **ten
istý blok** vložený trikrát — líši sa len nastavením:

- **Videografia** — režim „Video“, pomer `9/16`, kotva `video`
- **Grafický dizajn** — režim „Iba obrázok“, pomer `4/5`, kotva `grafika`
- **Sociálne siete** — režim „Video“, pomer `9/16`, kotva `social`

Pole „Kotva pre odkazy” nastaví `id` sekcie, takže na ňu môžete
odkazovať z iného bloku (napr. tlačidlo „Ukážky videí” v Službách
smeruje na `#video`). Toto pole **slúži výhradne na odkazovanie** —
nerozhoduje o tom, odkiaľ sa berie obsah (pozri nižšie).

Ak chcete, aby konkrétna inštancia karuselu čerpala obsah zo stránky
**Nástroje → IDEANET obsah**, vyberte príslušnú možnosť v poli
**„Zdroj obsahu z Nástroje → IDEANET obsah”** priamo pri danej
inštancii (Videografia / Grafický dizajn / Sociálne siete / Žiadny).
Toto pole je **nezávislé od Kotvy** — zámerne, aby sa zabránilo
situácii, keď si nová inštancia karuselu (napr. s režimom „Iba
obrázok”) omylom „ťahá” obsah určený pre inú (napr. kým nezmeníte
Kotvu z jej predvolenej hodnoty `video`, karusel by inak nesprávne
zobrazoval kategórie/položky z Videografie). Pri pridaní nového
karuselu preto Zdroj obsahu buď nechajte prázdny (spravujete obsah
len tu v builderi/cez defaulty), alebo ho vyberte explicitne.

### Prepojenie Školení s formulárom

Tlačidlo „Mám záujem o školenie” má pole **„Hodnota, ktorá sa
predvyplní v kontaktnom formulári”**. Pri kliknutí sa v najbližšom
kontaktnom formulári na stránke automaticky zaškrtne zodpovedajúca
položka v poli „Zoznam služieb” — hodnoty sa musia zhodovať presne
(napr. `Školenia pre tím` v oboch blokoch).

## Ako pridať referencie, videá a obrázky

Existujú **dva spôsoby**, ako naplniť bloky vlastným obsahom (realizácie,
portfólio, cenník, referencie...). Odporúčame prvý — je spoľahlivý
nezávisle od Avada Builderu.

### Spôsob 1 (odporúčaný): Nástroje → IDEANET obsah

Niektoré inštalácie Avady majú problém so zobrazením tlačidla
**„Add Item”** priamo v Avada Builderi (repeater pole sa ukáže, ale
nedá sa doň nič pridať — ide o chybu v JS vrstve Fusion Builderu,
mimo kontroly tohto pluginu). Táto admin stránka to rieši úplne —
funguje nezávisle od Avada Builderu a jeho JS.

1. V admine choďte na **IDEANET obsah** (vlastná položka v ľavom menu,
   ikona mriežky).
2. Stránka má sekciu pre každý blok (Hero, Služby, Portfólio ×3,
   Proces, Cenník, Školenia, Referencie). Kliknutím na názov hore
   na stránke sa na danú sekciu rýchlo preskočí.
3. V sekcii kliknite **„+ Pridať položku”** — pridá sa nový riadok
   s poľami pre daný blok.
4. Vyplňte textové polia a pri obrázku/videu kliknite **„Vybrať”** —
   otvorí sa Knižnica médií WordPressu.
5. Kliknite **„Uložiť”** pod danou sekciou (každá sekcia sa ukladá
   samostatne).

Len čo takto uložíte aspoň jednu položku v niektorej sekcii, **táto
položka sa zobrazí na webe namiesto obsahu z Avada Builderu aj
namiesto vstavaného ukážkového obsahu** — má najvyššiu prioritu.
Ak sekciu znova vyprázdnite (zmažete všetky riadky a uložíte), blok
sa vráti k obsahu z Avada Builderu, alebo k defaultom, ak ani tam nič
nie je.

Portfólio karusel má na tejto stránke tri samostatné sekcie
(Videografia, Grafický dizajn, Sociálne siete). Aby konkrétna
inštancia karuselu na stránke (v builderi) vedela, z ktorej z nich
má čerpať, musíte pri nej v builderi nastaviť pole **„Zdroj obsahu
z Nástroje → IDEANET obsah”** na zodpovedajúcu možnosť — bez tohto
nastavenia sa obsah z tejto stránky na daný karusel nenaviaže (pozri
sekciu *Portfólio karusel — jeden blok, tri použitia* nižšie).

### Spôsob 2: priamo v Avada Builderi

Ak vám v builderi tlačidlo „Add Item” funguje, môžete obsah pridávať
aj priamo tam (a tento obsah sa použije, pokiaľ v danej sekcii nemáte
nič uložené cez Spôsob 1 vyššie):

1. Otvorte stránku v **Avada Builderi** (tlačidlo „Edit with Avada
   Builder” hore na stránke, alebo z editácie stránky).
2. Nájdite na stránke blok, do ktorého chcete pridať obsah, a kliknite
   naň — zobrazí sa ikona ozubeného kolieska/ceruzky, kliknite na ňu.
3. V paneli nastavení prvku nájdete repeater pole — podľa bloku sa
   volá napr. „Realizácie v karuseli” (Hero), „Ukážky” (Portfólio
   karusel), „Karty služieb”, „Referencie” atď.
4. Kliknite na tlačidlo **„Add Item”** pod repeaterom — pridá sa
   prázdny riadok s vlastnými poľami.
5. Vyplňte polia, pri obrázku/videu nahrajte súbor cez Knižnicu médií.
6. Potvrďte v paneli prvku a stránku uložte/publikujte v builderi.

**Dôležité:** defaultné ukážkové položky (napr. „Ranná káva”, „Séria
pre klub”…) **nie sú v builderi vidieť ako položky na úpravu** — sú to
vstavané záložné hodnoty. Keď chcete vlastný obsah, nemusíte nič
mazať — stačí pridať vlastné položky (jedným z dvoch spôsobov vyššie)
a defaultné sa automaticky prestanú zobrazovať.

## Predvolený (defaultný) obsah

Od verzie **1.1.0** každý blok s repeaterom (Realizácie v Hero, Karty
služieb, Ukážky v Portfólio karuseli — video aj grafika, Kroky
procesu, Cenové balíky, Formáty školení, Referencie) zobrazuje
kompletný ukážkový obsah **hneď po vložení**, aj keď do neho ešte
nepridáte ani jednu vlastnú položku cez „Add Item”. Náhľadové obrázky
v defaultnom obsahu sú generované priamo na serveri (farebný gradient
v palete webu), takže sa nespoliehajú na žiadny externý súbor či
sieťové pripojenie.

Toto rieši situáciu, kedy bol blok v builderi vložený, ale ešte nikto
nestihol doplniť reálne videá/fotky/referencie — stránka dovtedy nie
je prázdna, ale pôsobí hotovo a predáva ukážkový príklad toho, ako má
obsah vyzerať.

**Dôležité:** akonáhle do repeatera pridáte čo i len jednu vlastnú
položku tlačidlom „Add Item” a stránku uložíte, defaultný obsah sa
pre daný blok úplne nahradí vašimi položkami (nepridáva sa k nim —
nahrádza ich). Ak teda chcete vidieť len svoje tri realizácie v Hero
karuseli, netreba mazať nič navyše — stačí mať v builderi pridané
presne tie tri.

Textové polia mimo repeaterov (nadpisy, úvodný text, popisy v sekcii
Školenia a pod.) toto pravidlo nepotrebujú — Avada Builder do nich
pri vložení bloku automaticky predvyplní rovnaký ukážkový text, aký
vidíte v poli „value” v builderi; stačí ho prepísať vlastným.

## Technické poznámky

- Interaktívne prvky (karty služieb, cenníkové balíky, referencie,
  kroky procesu, karuselové dlaždice, tlačidlá, odkazy, chips vo
  formulári) majú jemné `transition`-based hover efekty — nadvihnutie
  s tieňom, farebné zvýraznenie okraja, posun šípky/čísla, jemné
  pretočenie ikony. Všetky zdieľajú rovnaké časovanie (`--ib-ease` v
  `:root` bloku `.ib-scope`) pre konzistentný pocit naprieč webom.
- Shortcody sú zaregistrované klasickým, dlhodobo zdokumentovaným
  Fusion Builder Element API (`fusion_builder_map()` +
  `add_shortcode()`), ktoré Avada udržiava spätne kompatibilné už
  mnoho verzií. Opakovateľné polia používajú natívny Fusion Builder
  typ `'multiple'` (rovnaký mechanizmus ako vstavané prvky Avady,
  napr. Testimonials).
- Ak by sa v niektorej novšej/staršej verzii Avady prvok v builderi
  nezobrazil alebo by opakovateľné pole vyzeralo inak, najpravdepodobnejšie
  ide o rozdiel práve v UI repeatera (`'multiple'`) — zvyšok (registrácia,
  vykresľovanie, štýly) je na verzii Avady nezávislý. V takom prípade
  overte vo Fusion Builder → Elements, či sa bloky IDEANET vôbec
  ponúkajú, a skúste stránku uložiť a znova načítať.
- CSS aj JS sú zaškatuľkované pod `.ib-scope` / `.ib-` triedy, aby sa
  neprelínali so štýlmi témy Avada. Farby a rozostupy sú CSS premenné
  v `assets/css/blocks.css` (`:root` blok `.ib-scope`) — zmena vzhľadu
  celého pluginu je zmena pár premenných na jednom mieste.
- JS (`assets/js/blocks.js`) je nezávislý od jQuery a funguje pre
  ľubovoľný počet blokov na stránke naraz (karusel, lightbox aj
  formulár sú viacinštančné, nič nie je naviazané na pevné ID).
- Formulár v bloku Kontakt momentálne otvára e-mailového klienta
  (`mailto:`), presne ako na pôvodnom statickom webe. Pre odosielanie
  na server (napr. cez `wp_mail()` alebo externú službu) je potrebné
  upraviť `initContact()` v `assets/js/blocks.js` a/alebo pridať
  spracovanie na strane PHP.
- Ikony v bloku Služby sú vstavaná sada šiestich SVG ikon
  (`includes/helpers.php` → `ideanet_blocks_icon_set()`) plus možnosť
  vložiť vlastné SVG cez voľbu „Vlastné SVG“.

## Riešenie problémov

**Chcem v Hero aj svoju fotku.**
Vo verzii **1.9.0** mal blok Hero malý okrúhly portrét priamo v
úvodnom texte. Od verzie **2.0.0** je namiesto toho samostatný blok
**IDEANET — O mne** s podstatne väčšou fotkou — pozri nižšie.

**Chcem pri kliknutí vlnový efekt ako má Claude.**
Od verzie **1.6.0** pri kliknutí (alebo ťuknutí na mobile) na tlačidlo,
kartu služby/cenníka/referencie, dlaždicu v portfóliovom karuseli alebo
odkaz v navigácii vznikne jemný ružový impulz presne z miesta kliknutia,
ktorý sa rozšíri a vyblední (~0,65 s). Na rozdiel od klasického Material
ripple nemá tvrdý kruhový okraj — je to mäkký radiálny gradient, ktorý
stmieva do priehľadna ešte vo vlastnom polomere, takže nepotrebuje
orezanie (`overflow:hidden`) na rodičovi a bezpečne funguje aj na
cenníkovej karte so štítkom „Najčastejšia voľba“, ktorý zámerne presahuje
cez jej horný okraj. Implementované ako jedna globálna JS delegácia
(`initRipple()` v `assets/js/blocks.js`), takže funguje aj na obsahu,
ktorý pribudne neskôr. Rešpektuje `prefers-reduced-motion` (efekt sa
vôbec nevytvorí).

**Odlesk v Hero nesleduje kurzor / náklon kariet pri prejdení myšou nefunguje.**
Od verzie **1.5.0** má Hero svetlo, ktoré sa jemne presúva za kurzorom
(mimo hover zostáva pôvodný statický odlesk), karty služieb/cenníka/
referencií/portfólia majú jemný 3D náklon podľa polohy kurzora a
hlavné tlačidlo v Hero sa jemne „priťahuje” ku kurzoru. Tieto efekty
boli v kóde pripravené už skôr, ale neboli zapojené do CSS — navyše
po zavedení pozadia na celú šírku (v1.3.0) sa `.ib-hero` presunulo na
rovnaký element ako `.ib-scope`, čím sa JS inicializácia (hľadala
`.ib-hero` len medzi potomkami) prestala spúšťať úplne. Oboje je od
v1.5.0 opravené. Efekty bežia len na myši (nie na dotyku) a rešpektujú
`prefers-reduced-motion`. Pri kartách, ktoré sú súčasťou "objavenia pri
scrolli" (`.ib-reveal`), bola navyše potrebná oprava CSS špecifickosti
— `.ib-reveal.in{transform:none}` mala rovnakú špecifickosť ako
pôvodné pravidlo pre nadvihnutie pri hoveri a pri rovnosti vyhrávala
podľa poradia v súbore, takže hover efekt po dokončení scroll animácie
prestal fungovať (platí to aj spätne pre jednoduché nadvihnutie bez
náklonu z verzie 1.2.0).

**Chcem pri videu pridať aj „pre koho (firma)” a tagy, kto je na videu.**
Od verzie **1.4.0** majú repeatery pre video (Hero realizácie,
Portfólio „Videografia” aj „Sociálne siete”) dve nové nepovinné polia:
**„Pre koho (firma)”** (zobrazí sa pod názvom v dlaždici aj v
lightboxe) a **„Tagy — kto je na videu”** (oddeľte bodkočiarkou `;`,
zobrazia sa ako štítky v lightboxe). Portfólio „Grafický dizajn” tieto
polia nemá — nejde o video.

**Chcem pri videu označiť, či som natáčal, strihal alebo písal scenár.**
Od verzie **1.8.0** majú tie isté repeatery (Hero realizácie, Portfólio
„Videografia” aj „Sociálne siete”) tri nové prepínače Áno/Nie:
**„Natáčal”**, **„Strihal”** a **„Písal scenár”** — zaškrtnite len tie,
ktoré pri danej realizácii sedia (môžete mať napr. len „Strihal”, ak
ste dostali hotové zábery od klienta). Pri každom sa automaticky
zobrazí malá ikonka — kamera / nožnice / dokument — ako odznak v ľavom
dolnom rohu dlaždice v mriežke a ako štítok s popiskom v lightboxe po
kliknutí. Ak nie je zaškrtnuté nič, žiadne ikonky sa nezobrazia. V
**Nástroje → IDEANET obsah** sú tie isté tri polia dostupné ako
zaškrtávacie políčka.

**Dlaždice videa v mriežke už neprehrávajú tichý náhľad pri prejdení myšou.**
Od verzie **1.4.0** dlaždica (aj pri videu, aj keď je zdrojové video
na šírku) vždy zobrazuje len statický náhľadový obrázok — video sa
prehrá až po kliknutí, v lightboxe s plným prehrávačom a zvukom. Do
staršej verzie sa pri prejdení myšou na desktope spúšťal tichý
automatický náhľad priamo v dlaždici; to sa zámerne odstránilo kvôli
jednoduchšiemu a predvídateľnejšiemu správaniu (najmä pri videách na
šírku, kde tichý náhľad v 9:16 dlaždici pôsobil orezane).

**Zatváracie tlačidlo (×) v lightboxe je dole na strede, nie hore vpravo.**
Od verzie **1.4.0** je to zámer — na mobile sa tak lepšie trafí
palcom pri jednoručnom ovládaní a vytvára spolu so šípkami
Predchádzajúce/Ďalšie jeden prehľadný ovládací pás dole na obrazovke.

**Chcem iné písmo.**
Plugin používa **Inter** (bežný text, nadpisy) a **Instrument Serif**
(kurzívne zvýraznenia), načítavané z Google Fonts cez
`ideanet_blocks_enqueue_assets()` v `includes/helpers.php`. Ak chcete
inú dvojicu, stačí zmeniť URL na riadku s `fonts.googleapis.com`
v tejto funkcii na iné rodiny z [fonts.google.com](https://fonts.google.com)
a nahradiť názvy `'Inter'` / `'Instrument Serif'` v
`assets/css/blocks.css` (jednoduché hromadné nahradenie reťazca).
(V skúšanej verzii 1.3.1 boli dočasne nasadené Manrope + Fraunces —
po spätnej väzbe vrátené na pôvodnú dvojicu vo verzii 1.3.2.)

**Pozadie/odlesk v Hero sekcii nesiaha od okraja po okraj obrazovky.**
Do verzie **1.2.2** zdieľal hlavný `<div>` Hero sekcie súčasne triedy
`.ib-hero` (nosič pozadia/odlesku) aj `.ib-wrap` (vycentrovaný obsah,
max. šírka 1240 px) — pozadie tak bolo nechcene obmedzené na tú istú
šírku ako text a karusel. Od verzie **1.3.0** je `.ib-hero` presunuté
priamo na vonkajšiu `<section>` (bez obmedzenia šírky), zatiaľ čo
vycentrovaný obsah je v samostatnom vnútornom `<div>` s `.ib-wrap`.
Vstavaný odlesk (jemný magenta glow) tak teraz siaha od okraja po
okraj na desktope, tablete aj mobile a jeho veľkosť sa plynule
(`clamp()` naviazaný na `vw`) prispôsobuje šírke obrazovky namiesto
pevných pixelových rozmerov. Ak pridávate vlastné pozadie (napr. cez
Avada Full Width Row s obrázkom na pozadí), táto zmena mu už nestojí
v ceste.

**Kliknutím na video v karuseli sa neprehrá so zvukom.**
Do verzie **1.2.1** klik na dlaždicu videa v karuseli (aj v Hero
„Posledné realizácie") iba prepínal malý tichý (`muted`) náhľad priamo
v dlaždici — rovnaký náhľad, aký sa spúšťa pri prejdení myšou na
desktope. Nebol spôsob, ako sa cez klik dostať k plnému prehrávaču so
zvukom. Od verzie **1.2.2** klik na dlaždicu vždy otvorí lightbox
s plným `<video controls>` prehrávačom (so zvukom, s ovládacími
prvkami) — tichý náhľad pri prejdení myšou zostáva zachovaný ako
samostatná, nezávislá funkcia len pre desktop.

**Najprv choďte na Nástroje → IDEANET bloky** (pozri sekciu *Diagnostika*
vyššie) — tá okamžite ukáže, či je skutočný problém v registrácii
(Fusion Builder nedetegovaný, shortcode sa nezaregistroval) alebo vo
vykresľovaní (shortcode existuje, ale samo-test hlási chybu). Scenáre
nižšie sú pre prípad, že diagnostika sama problém nevysvetlí.

**V repeater poli (napr. „Realizácie v karuseli”, „Cenové balíky”, „Referencie”) sa nedá kliknúť na „Add Item” — nadpis poľa vidno, ale tlačidlo chýba.**
Toto je potvrdený problém na strane **Avada Builderu** (JS vrstva,
ktorá vykresľuje repeater „Add Item”/riadky), nie v PHP registrácii
tohto pluginu — prejaví sa rovnako aj pri **vstavaných** Avada prvkoch
s repeater poľom (napr. Testimonials), nielen pri IDEANET blokoch.
Príčina môže byť zastaraná Avada/Fusion Builder, konflikt s cache/
minifikačným pluginom (skúste dočasne vypnúť minifikáciu JS v admine),
alebo konflikt s iným pluginom. Od verzie **1.2.0** nemusíte čakať na
vyriešenie príčiny: použite **Nástroje → IDEANET obsah** — samostatnú
admin stránku s vlastným formulárom (Pridať/Odstrániť položku,
Knižnica médií pre obrázky/video), ktorá funguje úplne nezávisle od
Avada Builderu a jeho JS. Obsah uložený tu má prioritu pred obsahom
z buildera aj pred defaultmi — pozri sekciu *Ako pridať referencie,
videá a obrázky* vyššie, Spôsob 1.

**Defaultné náhľadové obrázky (poster) sa zobrazujú ako rozbitý obrázok a prehrávanie v karuseli/lightboxe nefunguje.**
Vo verzii **1.1.0** sa predvolené náhľady generovali ako `data:` URI
(SVG zakódované priamo do atribútu `src`), ale vykresľovali sa cez
`esc_url()`. WordPress v `esc_url()` štandardne **odstráni každú URL,
ktorej protokol nie je na zozname povolených** (`wp_allowed_protocols()`)
— a `data:` tam nie je, z bezpečnostných dôvodov. V praxi to znamenalo,
že `src` aj `data-poster` vyšli na reálnom WordPresse prázdne, prehliadač
zobrazil rozbitú ikonu namiesto obrázka a karusel/lightbox nemali z čoho
video alebo ukážku prehrať — presne toto správanie nahlásili prví
testeri. (V lokálnom PHP teste to prejde bez chyby, lebo jednoduchý
testovací shim `esc_url()` toto orezávanie nereplikuje — preto sa chyba
odhalila až na reálnej inštalácii.) Od verzie **1.1.1** nová funkcia
`ideanet_blocks_esc_media_url()` rozlíši vygenerovaný `data:` náhľad
(vždy bezpečný, lebo ho tvorí plugin sám) od skutočnej URL z Knižnice
médií — prvý sa escapuje cez `esc_attr()`, druhý naďalej cez `esc_url()`.
Ak ste si plugin stiahli vo verzii 1.1.0, aktualizujte na 1.1.1.

**Nadpisy/text/tlačidlá majú inú farbu alebo font, než majú — dizajn „nesedí".**
Reálna Avada stránka načítava vlastný „Dynamic CSS" súbor (generovaný
z Theme Options) **až po** `blocks.css` tohto pluginu. Do verzie 1.0.3
sa viaceré základné pravidlá (nadpisy, odkazy, tlačidlá, polia
formulára) spoliehali na dedenie farby/fontu z rodiča namiesto
explicitnej hodnoty — a dedená hodnota v CSS prehráva s akýmkoľvek
explicitným pravidlom témy, aj nízkej špecifickosti. Od verzie **1.0.4**
má každé takéto pravidlo explicitnú farbu/font s `!important` a
dostatočnou špecifickosťou selektora, aby prežilo akékoľvek neskôr
načítané CSS témy. Overené simuláciou nepriateľskej Avada-like
stylesheet (vlastné farby na `h1-h4, p, a, button, input, textarea`)
načítanej po `blocks.css` — výsledné farby, fonty aj rádiusy tlačidiel
zostali presne podľa dizajnu pluginu.

**Karusely / karty služieb / cenník sú na stránke prázdne (bez obsahu).**
Od verzie **1.1.0** by sa toto nemalo stať — pokiaľ do repeatera
(realizácie v Hero, ukážky v Portfólio karuseli, karty v Službách,
kroky procesu, balíky v Cenníku, formáty v Školeniach, citáty v
Referenciách) ešte nepridáte vlastné položky, blok zobrazí kompletný
defaultný obsah namiesto prázdneho miesta (pozri sekciu *Predvolený
(defaultný) obsah* vyššie). Ak sa aj tak zobrazuje prázdne miesto bez
defaultného obsahu, ide o reálnu chybu — pošlite report z Nástroje →
IDEANET bloky (samo-test ukáže presne, ktorý shortcode zlyháva a prečo).

**Zoznam bodov (napr. „Čo si tím odnesie" v Školeniach) sa zobrazí
ako jedna dlhá veta namiesto samostatných bodiek.**
Do verzie 1.0.3 sa tieto polia delili podľa znaku nového riadku, ale
Avada Builder pri ukladaní shortcode atribútov niekedy znaky nového
riadku v textarea poli zlúči do medzery — položky sa tak spojili do
jednej. Od verzie **1.0.4** sa delia bodkočiarkou (`;`), ktorá tento
prechod spoľahlivo prežije — v poliach „Zoznam bodov", „Zahrnuté" a
„Čo si tím odnesie" teraz oddeľujte položky znakom `;`, nie novým
riadkom. Ak ste tieto polia upravovali vo verzii pred 1.0.4, po
aktualizácii ich prepíšte s bodkočiarkami.

**Hero karusel „Posledné realizácie" nezobrazuje na mobile 1,5 karty.**
Vo verziách do 1.0.1 mal blok `.ib-carousel` v Hero elemente priamo
nastavený inline štýl `style="--ib-per:…"`. Inline štýl má vždy
prednosť pred pravidlom v `<style>` bloku — mobilná media query preto
nikdy nemala šancu hodnotu prepnúť. Od verzie **1.0.2** je inline štýl
odstránený, o počet kariet sa stará výhradne responzívny `<style>` blok
(rovnaký mechanizmus ako v Portfólio karuseli, kde tento problém nikdy
nebol). Overené meraním v prehliadači: 390 px → 1,5 karty, 1440 px →
nastavený počet pre desktop.

**Bloky sa zobrazujú úplne bez dizajnu / vyzerajú rozbité.**
Vo verzii 1.0.0 sa CSS a JS vkladali až vnútri `render()` jednotlivých
blokov — teda v momente, keď WordPress už dávno vytlačil `<head>` a
štýly sa tak na stránku nikdy nedostali. Od verzie **1.0.1** sa vkladajú
cez `wp_enqueue_scripts` (main súbor pluginu), čo je správne miesto a
beží ešte pred `wp_head`. Ak vidíte tento problém, aktualizujte na
najnovšiu verziu pluginu.

**Na stránke je vidno text `[ideanet_hero ...]` namiesto bloku.**
Znamená to, že shortcode sa nevykonal — buď je plugin neaktívny
(Pluginy → skontrolujte, či je „IDEANET — bloky pre Avada Builder“
aktivovaný), alebo bol vložený ako obyčajný text/kód mimo Avada
Builder prvku. Riešenie: v Avada Builderi pridajte prvok zo zoznamu
(kategória **IDEANET**), nie surový shortcode do textového poľa.

**Po nahratí zipu sa plugin nedá aktivovať / hlási nekompatibilitu.**
Plugin vyžaduje PHP 7.4+ a WordPress 5.9+ (deklarované v hlavičke
`ideanet-avada-blocks.php`). Skontrolujte verziu PHP u hostingu
(WordPress admin → Nástroje → Stav lokality → Server).

**Biela/prázdna stránka po aktivácii.**
Zapnite v `wp-config.php` dočasne `WP_DEBUG` a `WP_DEBUG_LOG`
a pozrite `wp-content/debug.log` — presná hláška ukáže, v ktorom
súbore a na ktorom riadku PHP padá. Ak sa problém nedá nájsť, pošlite
túto hlášku — je to najrýchlejšia cesta k diagnóze.

## Požiadavky

- WordPress 5.9+
- PHP 7.4+
- Téma Avada s aktívnym Fusion Builderom
