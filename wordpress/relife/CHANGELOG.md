# Zmeny témy RELIFE

Verzia je v hlavičke `style.css` (`Version:`). Podľa nej sa pridáva `?ver=` k CSS a JS, takže po aktualizácii sa nenačíta stará kópia z cache.

## 1.31.0
- **Lacné letenky: výber sekcií.** Dá sa zobraziť len vybraná sekcia – napr. iba najlacnejšie letenky **na predĺžený víkend**: shortcode `[lacne_letenky sections="weekend"]` (aj `sekcie="vikend"`), `sections="long"` / `pobyt` = dlhší pobyt do 3 mesiacov, `sections="asia"` / `azia` = Ázia a Emiráty; viac sekcií oddelíte čiarkou (`sections="weekend,long"`). Widget Lacné letenky má zaškrtávacie políčka sekcií (v úzkom ľavom stĺpci predvolene predĺžený víkend). Bez zadania sa ukážu všetky sekcie ako doteraz. Ak sekcia víkendu nemá ponuky, ukáže sa správa namiesto prázdneho bloku.

## 1.30.0
- **Výber kategórií pre úvodnú stránku:** Vzhľad → Prispôsobiť → RELIFE – Úvodná stránka. Pre každý z troch stĺpcov sekcie „Vedieť viac. Žiť lepšie.“ vyberiete ľubovoľnú kategóriu (v zozname je počet článkov) alebo ponecháte „Automaticky“ (TECH, HEALTH, MONEY). Názov, popis (z popisu kategórie), farba (pri úprave kategórie: „Farba sekcie“) a odkaz sa berú z vybranej kategórie.

## 1.29.0
- **Oprava: sekcia „Vedieť viac. Žiť lepšie.“ nenačítala HEALTH a MONEY.** Téma hľadala články len v kategóriách s presným slugom `tech`, `health`, `money`. Ak majú kategórie na webe iný slug alebo názov (napr. „zdravie“, „peniaze“, „health-2“), stĺpec ostal prázdny. Teraz sa kategória nájde aj podľa prezývky/názvu/slugu s číslom a berie sa tá s najviac článkami (`relife_section_term`, rozšíriteľné filtrom `relife_section_aliases`). Platí pre sekciu na úvodnej, odkazy v menu, farby sekcií a odporúčané články.
- Ak je pilier stále prázdny, administrátorovi sa ukáže poznámka, aký slug kategórie má použiť.

## 1.28.0
- **Stránkovanie** (kategórie, archívy, hľadanie): vycentrované moderné tlačidlá – „Späť“, čísla strán v kruhoch (aktuálna strana v akcentovej farbe), „Ďalej“; na mobile len šípky a čísla.
- **Mobilné spodné menu:** položka „Hľadať“ je nahradená položkou **Letenky** (odkaz na stránku Lacné letenky, aktívna na tej stránke). Hľadanie ostáva v ikone lupy v hlavičke. Ak stránka Lacné letenky neexistuje, ostáva „Hľadať“.

## 1.27.0
- **Reklama v článku sa vkladá do poslednej tretiny** (od ~67 % dĺžky textu; vždy zostane aspoň jeden odsek za ňou). Poloha sa dá zmeniť: Vzhľad → Prispôsobiť → RELIFE – Reklamy → Poloha reklamy v článku (koniec prvej tretiny / stred / posledná tretina). Pole „Vložiť reklamu po odseku číslo“ (ak je vyplnené) má prednosť.
- **Ľavý stĺpec článku ako oblasť pre widgety** (Vzhľad → Widgety → Ľavý stĺpec článku, desktop, pod odporúčanými článkami). Widget **Lacné letenky** má v ňom úzku verziu (3 karty so zoznamom najlacnejších ponúk a odkazom na stránku Lacné letenky). Úzku verziu dá vložiť aj shortcode: `[lacne_letenky compact="1" limit="3"]`.

## 1.26.0
- **Oprava: percentá v ankete.** Percentá sa doteraz počítali len zo základných počtov zadaných v administrácii, hlas čitateľa sa nikam nezapisoval, takže pri anketách bez zadaných počtov bolo všade 0 % a po hlasovaní sa nič nezmenilo. Teraz sa hlasy **skutočne počítajú na serveri** (`POST /wp-json/relife/v1/vote`) a pripočítavajú sa k základu z administrácie; po hlasovaní sa hneď ukážu aktuálne percentá, pri opätovnom otvorení (aj zo cache stránky) sa načítajú čerstvé. Limit 5 hlasov na anketu za 24 hodín z jedného zariadenia; uzavretá anketa hlasy neprijíma.
- Staršie ankety (stránka Ankety) ukazujú výsledky vrátane hlasov čitateľov.

## 1.25.0
- **Lacné letenky zabudované v téme** (pôvodne samostatný plugin „Lacné letenky VIE · BTS“ 1.16.0): shortcode `[lacne_letenky]`, blok „Lacné letenky“ v editore, widget a nastavenia v *Nastavenia → Lacné letenky*. Vzhľad je prispôsobený dizajnu RELIFE (tmavé karty, tyrkysový akcent, písma a zaoblenie témy; nič sa nesťahuje z Google Fonts).
- Stránka **Lacné letenky** (`/lacne-letenky/`, šablóna „Lacné letenky“) sa vytvorí sama; odkaz v pätičke a v mobilnom menu.
- Ak je zároveň aktívny samostatný plugin, téma svoju kópiu nenačíta (bez duplicít). Ak predvolený zdroj cien (`HEAD`) ešte nemá súbor, použije sa záložný zo vetvy s pluginom.

## 1.24.1
- **Oprava: reklama v strede článku sa nezobrazovala** pri článkoch, ktoré nie sú napísané v blokovom editore (obsah vložený z iného systému, napr. tagDiv, je „klasický“ HTML bez blokov). Vkladanie reklamy teraz pracuje s hotovým HTML, takže funguje pre blokový aj klasický obsah; odseky v zoznamoch, citáciách, tabuľkách a pod obrázkami sa nepočítajú.
- Ak sa reklama nevloží, v zdrojovom kóde článku je komentár `RELIFE reklama: …` s dôvodom (málo odsekov alebo chýba ID jednotky/kód reklamy).

## 1.24.0
- **Odporúčané články v ľavom stĺpci článku (desktop):** pod obsahom („V ČLÁNKU“) sa zobrazia 3 odporúčané články z tej istej kategórie (z najnovších vyhrávajú tie, ktoré čitatelia najviac ukladajú), s obrázkom a názvom. Celý ľavý stĺpec sa drží pri skrolovaní. Zobrazuje sa aj vtedy, keď článok nemá obsah z nadpisov. Na tablete a mobile sa stĺpec skrýva (na mobile je plávajúca výzva na ďalší článok).

## 1.23.0
- **Responzivita pre iPhone, skladacie telefóny a Surface Duo:**
  - `viewport-fit=cover` a bezpečné okraje (`env(safe-area-inset-*)`) pre výrez/Dynamic Island v krajine: obsah, spodné menu, cookie lišta a karta ďalšieho článku nezasahujú pod výrez.
  - Rozložený Fold / malý tablet (761–1100 px): navigácia sekcií je v samostatnom posuvnom riadku (už sa nezalamuje na tri riadky).
  - 560–760 px (Duo jedna obrazovka, Fold rozložený): 2 stĺpce kariet.
  - Úzke obrazovky (≤360 px, vonkajší displej Galaxy Z Fold, iPhone SE): menšie nadpisy a okraje, 1 stĺpec kariet.
  - Telefón na šírku: nižšia hlavička, malá spodná lišta, posuvná cookie lišta.
  - Surface Duo rozložený cez dve obrazovky: medzera kariet sa zhoduje so záves­om (`viewport-segment-*`).
  - `text-size-adjust` proti nafúknutiu textu po otočení.

## 1.22.0
- **Plávajúca výzva na ďalší článok (mobil):** pri čítaní článku sa v poslednej tretine textu nad spodným menu zobrazí karta „Ďalší zaujímavý článok“ s obrázkom, sekciou a názvom. Vyberie sa článok z tej istej kategórie (z najnovších vyhráva ten, ktorý čitatelia najviac ukladajú). Dá sa zavrieť (pre danú návštevu už nevyskočí), skrýva sa pri päte stránky, na desktope sa nezobrazuje. Udalosti `relife_next_shown` a `relife_next_click` idú do Google tagu.

## 1.21.0
- **Štatistika uložených článkov na Nástenke:** miniaplikácia „RELIFE – Najviac ukladané články (TOP 15)“ so zoradením od najviac uložených, sekciou a stĺpcovým ukazovateľom. Pri uložení/odstránení záložky pošle prehliadač len ID článku a smer (`POST /wp-json/relife/v1/save`), bez osobných údajov; ochrana proti zneužitiu (1 zmena za 5 s na článok, 60 za hodinu na zariadenie). Počet je orientačný a počíta sa od spustenia tejto verzie.

## 1.20.0
- **Nástroj v administrácii: Nástroje → Kontrola AdSense (GDPR).** Jedným klikom otvorí web ako návštevník (v skrytom rámci), skontroluje skript AdSense, ID vydavateľa, požiadavku na Google Funding Choices, prvky lišty (aj „googlefcInactive“), TCF, uložený súhlas, konflikty s inými cookie lištami, ads.txt a región; zo servera overí HTML bez JavaScriptu, hlavičku CSP, cache, odpoveď Google pre ID vydavateľa a aktívne pluginy. Zobrazí **najpravdepodobnejšiu príčinu a návrh opravy**.
- Test obchádza pravidlá „neukazovať prihláseným redaktorom“ (len pre test správcu), nepoužíva cache stránok a volitelne zmaže uložený súhlas v prehliadači.

## 1.19.3
- Vkladanie malých štýlov pluginov (kk Star Ratings a pod.) funguje aj pre štýly s relatívnymi adresami obrázkov/písiem (adresy sa prepíšu na úplné).
- Štýl a skript kk Star Ratings sa načítavajú len v článkoch (nie na úvodnej, v archívoch a pod.).

## 1.19.2
- Tmavé pozadie sa nastaví hneď na začiatku `<head>` (pred zvyškom štýlov), aby sa pri načítaní nezobrazovali biele snímky (Speed Index).

## 1.19.1
- Malé štýly pluginov (TablePress `tablepress-combined.min.css`, kk Star Ratings a pod., do 15 KB, bez relatívnych adries) sa vkladajú priamo do stránky namiesto samostatnej blokujúcej požiadavky. Vypnutie: filter `relife_inline_small_styles`; limit veľkosti: `relife_inline_style_max`.

## 1.19.0
- **Výkon (blokovanie vykreslenia):** hlavný štýl témy sa vkladá priamo do `<head>` (bez blokujúcej požiadavky na `relife.css`); štýly blokov WordPressu (`wp-block-library`) sa v článkoch načítavajú neblokujúco. Vypnutie vkladania: filter `relife_inline_css` (`__return_false`).

## 1.18.1
- Inzerujte u nás: sekcia „Ako sa môžeme spojiť“ je teraz **Naša ponuka** a obsahuje len **Publikovanie článkov** a **Písanie článkov na zadanú tému** (bannery a newsletter odstránené). Rovnako sa upravili možnosti vo formulári. Neupravená stránka z 1.18.0 sa po aktualizácii prepíše automaticky.

## 1.18.0
- **Stránka „Inzerujte u nás“ sa upravuje priamo v Stránkach** (nie vo Vzhľad → Prispôsobiť): texty „Prečo publikovať u nás“ a „Ako sa môžeme spojiť“ sú bloky v editore stránky; úvodný text, údaje grafu a e-mail pre dopyty sú v poli „Inzerujte u nás: úvod, graf a kontakt“ pod editorom. Staršia stránka z 1.17.0 sa po aktualizácii doplní predvoleným obsahom. Sekcia „RELIFE – Inzercia“ v Customizeri zanikla.

## 1.17.0
- **Stránka „Inzerujte u nás“** (`/inzercia/`, vytvorí sa sama): úvod, **interaktívny graf** zobrazení vo funkciách generatívnej AI (hover/dotyk/klávesnica, prepínač Mesačne/Spolu, dáta z Google Search Console), sekcia **Prečo publikovať u nás** (overené, zaujímavé, optimalizované pre AEO, transparentné), ponuka spolupráce a kontaktný formulár (e-mail správcu alebo adresa z Customizeru; ochrana pred spamom).
- Údaje grafu a e-mail: Vzhľad → Prispôsobiť → RELIFE – Inzercia (riadok `mesiac|hodnota`).
- Odkaz na stránku v **pätičke** a v **mobilnom menu**.

## 1.16.0
- **Chyby 404 (Search Console):** téma samé presmeruje (301) staré adresy, ktoré neexistujú, ale ich článok/stránka existuje podľa názvu (slug), napr. `/kategoria/tech/clanok.html` → `/clanok/`; staré adresy kategórií a značiek; `sitemap.xml` → `wp-sitemap.xml`.
- Ručné presmerovania: Vzhľad → Prispôsobiť → RELIFE → Presmerovania (riadok `/stara/ => /nova/`).
- Protokol 404 adries: Nástroje → RELIFE 404 adresy (počet, čas, odkiaľ), aby bolo vidieť, čo treba presmerovať.

## 1.15.1
- Téma sama vytvorí stránku **Stránka nenájdená** (`/stranka-404/`, šablóna „Stránka 404“, noindex) – pri aktivácii alebo po prvom otvorení administrácie po aktualizácii. Obsah je spoločný s chybovou stránkou 404 (`template-parts/not-found.php`). Neexistujúce adresy ďalej zobrazujú 404.php so stavom 404.

## 1.15.0
- **Stránka 404:** nový dizajn s veľkým obrysovým „404“, textom, vyhľadávaním, tlačidlami siedmich sekcií, tlačidlom späť na domov a štyrmi najnovšími článkami. Vráti správny stav 404 (nie je indexovaná).

## 1.14.0
- **Tabuľky v článkoch:** tmavý dizajn v štýle témy (zaoblený rám, hlavička v akcentovej farbe, jemné pruhovanie, výrazný prvý stĺpec), na mobile sa tabuľka posúva do strany, ak je široká. Z vložených tabuliek (napr. zo starého builderu) sa odstránia pevné farby (červená hlavička, biele riadky) a šírky; prvý riadok s `<th>` sa stane hlavičkou.
- Nový štýl bloku Tabuľka: **Porovnanie (Versus)** so zvýrazneným prvým stĺpcom a deličom stĺpcov. Popis tabuľky sa zobrazí pod ňou.

## 1.13.1
- Reklamy: v Customizeri (RELIFE – Reklamy) sa dá vložiť **celý kód z AdSense** – skript do hlavičky aj celý kód reklamnej jednotky do stredu článku. ID vydavateľa a jednotky ostávajú ako alternatíva. Vypnutie pri článku funguje aj pre vložený kód.

## 1.13.0
- **Reklamy Google AdSense:** Vzhľad → Prispôsobiť → RELIFE – Reklamy (AdSense). Zadáte ID vydavateľa (`ca-pub-…`) a ID reklamnej jednotky v texte; reklama sa vloží do každého článku približne do stredu (alebo po odseku č. N). Články s menej ako 4 odsekmi ju v texte nemajú.
- Pri každom článku je box **Reklamy → Nezobrazovať reklamy v tomto článku** (vtedy sa nenačíta ani skript AdSense).
- Skript AdSense sa načíta až po načítaní stránky; miesto reklamy je rezervované (bez posunu rozloženia) a nezaplnená reklama sa skryje.
- `ads.txt` vytvorí téma sama (ak súbor na serveri neexistuje).

## 1.12.0
- Karty článkov majú ďalšie veľkosti obrázkov (320 a 480 px) a presný `sizes`, takže mobil nesťahuje 640 px obrázok pre kartu širokú ~180 px. Po nahraní pregenerujte miniatúry.

## 1.11.0
- **Optimalizácia obrázkov bez viditeľnej straty kvality:** nahraté JPEG/PNG sa ukladajú ako WebP (kvalita 82; AVIF 60, JPEG 85), len ak ich server podporuje. Maximálny rozmer originálu 2000 px. Vypnutie: filter `relife_optimize_images`; iný formát: filter `relife_image_format` (napr. `image/avif`).
- Lenivé načítanie (lazy) až od tretieho obrázka na stránke, prvé dva sa načítajú hneď.

## 1.10.0
- **Výkon (Core Web Vitals, LCP a Speed Index na mobile):**
  - Písma Inter a Bricolage Grotesque sú teraz v téme (`assets/fonts`, latin + latin-ext pre slovenčinu) a `@font-face` je priamo v `<head>`; preload hlavných súborov. Preč je blokujúca požiadavka na Google Fonts.
  - Preload LCP obrázka (hero na úvodnej stránke, titulný obrázok v článku) so správnym `srcset`/`sizes`; nová veľkosť `relife-hero-m` (800×450) pre mobil.
  - Logo už nemá `fetchpriority=high`, aby nekonkurovalo LCP obrázku.
  - Google tag sa načíta až po načítaní stránky (meranie ostáva).
  - Odstránené nepotrebné veci WordPressu: emoji skripty, wp-embed, RSD/wlwmanifest/shortlink/generator, dashicons pre návštevníkov, blokové štýly mimo článkov.
  - V zipe sú štýly skrátené (bez komentárov a medzier).

## 1.9.0
- **Oprava: do Google Analytics nechodili dáta.** Režim súhlasu bol predvolene „odmietnuť“, ale téma nemala lištu, cez ktorú by návštevník mohol súhlasiť, takže sa nič nemerelo. Pridaná cookie lišta Prijať / Odmietnuť (Customizer → RELIFE → Zobraziť cookie lištu). Voľba sa pamätá a dá sa zmeniť cez „Nastavenia cookies“ v pätičke.
- Diagnostika: ak je Google tag ID zadané, ale tag sa nevypísal, v zdrojovom kóde stránky je komentár `RELIFE Google tag: vypnuté, …` s dôvodom (napríklad prihlásený administrátor, neplatné ID).

## 1.8.0
- Odrážky a zoznamy v článkoch: odrážky v tvare kosoštvorca s akcentovou farbou, vnorené zoznamy s kruhovými a štvorcovými odrážkami, číslované zoznamy s číslom v kruhu (vnorené s písmenami a, b, c), zoznam pojmov.
- Štýly zoznamu v editore blokov (Zoznam → Štýly): **Zaškrtnutia**, **Krížiky (bez)**, **Kroky (číslovaný)** s prepojenou čiarou a **V rámčeku**.
- Položky, ktoré začínajú ✓ alebo ✗ (aj `x-`), automaticky dostanú zaškrtnutie alebo krížik, značka sa z textu odstráni.

## 1.7.0
- Google tag: vo Vzhľad → Prispôsobiť → RELIFE → Google tag ID zadáte `G-…` (Google Analytics 4), `GTM-…` (Tag Manager) alebo `AW-…`. Načíta sa asynchrónne v `<head>`, takže nebrzdí vykresľovanie.
- Režim súhlasu (Consent Mode v2) je predvolene zapnutý: kým návštevník nedá súhlas, nič sa nesleduje. Súhlas nastavíte cez cookie plugin alebo volaním `relifeConsent.grant()` / `relifeConsent.deny()`.
- Prihlásení redaktori a administrátori sa nesledujú (vypnuteľné).
- Udalosti pre analytiku: `relife_poll_vote`, `relife_mood_select`, `relife_save_article`.

## 1.6.0
- Logo v pätičke: vo Vzhľad → Prispôsobiť → RELIFE → Logo v pätičke (a Maximálna výška loga v pätičke). Bez vybraného loga sa zobrazí text RELIFE a heslo ako doteraz. Rozmery sa rezervujú vopred (bez posunu stránky).

## 1.5.0
Optimalizácia Cumulative Layout Shift (CLS):
- **Logo:** pomer strán a prirodzená výška loga sa zapíšu do CSS vopred, takže hlavička má pevnú výšku ešte pred načítaním obrázka. V teste sa tým odstránil jediný namerany posun (0,0059 → 0).
- **Písma:** náhradné písma `Inter Fallback` a `Bricolage Fallback` majú rozmery nastavené podľa skutočných písiem (size-adjust, ascent/descent-override), takže po načítaní webfontu sa text nepreskupí. Pridané predpripojenie na Google Fonts.
- **Úvodná stránka:** zoznam „Čo dnes stojí za tvoju pozornosť?“ sa vykreslí hneď so 4 kartami (predtým 14, 10 sa skrylo až skriptom). Nálada, nadpis podľa nálady a uložené články sa zistia ešte pred vykreslením, miesto pre stavový pruh a text ankety je rezervované.
- **Uložené:** zoznam má vopred rezervovanú výšku, prázdny stav je v HTML od začiatku.
- **Obrázky:** titulný obrázok (úvod, článok) má vysokú prioritu načítania, ostatné dekódujú asynchrónne. Všetky obrázky majú rozmery (width/height) a pomer strán v CSS.

## 1.4.1
- Článok: titulný obrázok má pevný odstup od bloku s autorom a tlačidlami Uložiť a Zdieľať (na mobile 28 px, na desktope 32 px). Odstup už nezávisí od vnútorných okrajov hlavičky.

## 1.4.0
- Videá z YouTube a Vimeo: stačí vložiť odkaz na samostatný riadok v článku (alebo ho prilepiť v editore blokov). Odkaz, ktorý je v odseku sám, sa tiež zmení na video.
- Video je responzívne (pomer 16:9, celá šírka stĺpca) a používa `youtube-nocookie.com` (vypnete filtrom `relife_youtube_nocookie`).
- Formulár komentárov už na mobile nepredlžuje stránku do strany a má tmavý vzhľad.

## 1.3.5
- Obrázky v článkoch sú responzívne: vždy sa zmestia do stĺpca, zachovajú pomer strán, popisy (`wp-caption`) neprečnievajú, obrázky zarovnané vľavo/vpravo sa na mobile zaradia pod seba, galérie sa na mobile zoradia do jedného stĺpca, videá z YouTube a Vimeo majú pomer 16:9.
- Správny `sizes` pre obrázky v texte (760 px alebo celá šírka na mobile).
- Tabuľky a bloky kódu v článku sa posúvajú v sebe, nepredlžujú stránku.

## 1.3.4
- Mobil: sekcie na úvodnej stránke majú opäť rozostup (nadpis „Čo dnes stojí za tvoju pozornosť?“ nebol natlačený na predchádzajúci blok). Mobilné pravidlo pre bočné okraje prepisovalo aj horný a dolný okraj. Oprava platí aj pre hlavičku článku.

## 1.3.3
- Logo v hlavičke je väčšie: predvolená výška 72 px (predtým 56), na mobile 54 px, najväčšia šírka 340 px (mobil 230 px). Výšku zmeníte v Customizeri (24 až 140 px). Ak ste tam výšku už nastavili, ostáva vaša hodnota.

## 1.3.2
- Newsletter: pole na e-mail má biele pozadie, tmavý text a viditeľný kurzor aj pri automatickom vypĺňaní (predtým bol text čierny na tmavom pozadí).

## 1.3.1
- **Oprava hlavnej príčiny:** `the_custom_logo()` vypisuje vlastný odkaz, ktorý sme vkladali do ďalšieho odkazu. Prehliadač vnorené odkazy rozbije a logo vypadlo z kontajnera, preto zaberalo celú šírku. Teraz je logo v `<div>`.
- Logo: kontajner v hlavičke má pevný strop šírky (260 px, na mobile 190 px) a obrázok max. výšku, ktorú nič neprebije. Pomáha, aj keď cache alebo optimalizačný plugin pridá vlastné rozmery.
- Mobil: v hlavnom článku a v bloku Versus sa nezobrazuje ukážkový text (perex), ostáva nadpis, kategória a čas čítania.
- Logo sa načíta v menšej veľkosti (`sizes`) a bez lenivého načítania.

## 1.3.0
- Nahrané logo sa prispôsobí maximálnej výške (predvolene 56 px, na mobile o štvrtinu menej). Nastavenie v Customizeri.
- Oprava: stránka na mobile sa už nedá posúvať do strany pri dlhých slovách v kartách a pri navigácii.

## 1.2.0
- Stredové tlačidlo spodného menu na mobile otvára menu. Ikona menu sa po otvorení zmení na X.
- Hľadanie je na druhej záložke spodného menu.

## 1.1.0
- Rozbaľovacie menu Sekcie v spodnom menu na mobile (piliere, ďalšie sekcie, formáty, Ultimate Guides).
- Filmy a seriály v podtémach sekcie TRENDY.

## 1.0.0
- Prvé vydanie: 7 sekcií, 6 formátov, anketa a staršie ankety, výber nálady, uložené články v prehliadači, šablóna článku, ukážkový obsah.
