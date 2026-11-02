# Zmeny témy RELIFE

Verzia je v hlavičke `style.css` (`Version:`). Podľa nej sa pridáva `?ver=` k CSS a JS, takže po aktualizácii sa nenačíta stará kópia z cache.

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
