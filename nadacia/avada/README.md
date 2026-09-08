# Avada bloky — Nadácia Anjelské krídla

Jednostránka rozdelená na bloky pre **Avada Builder** (WordPress). Každý súbor je
jedna sekcia — vložíte ju do stránky a ďalej ju upravujete klikaním v builderi.

> **Jednoduchšia cesta:** tie isté sekcie sú aj ako WordPress plugin —
> `../wordpress-plugin/nadacia-anjelske-kridla-bloky.zip`. Po inštalácii a jednom
> kliknutí sa objavia v Avada Library aj so štýlmi a obrázkami, takže nemusíte
> nič kopírovať ani nahrávať. Tento priečinok je pre prípad, že chcete bloky
> vkladať ručne alebo si ich upraviť pred vložením.

> **Overte si vzhľad po vložení.** Bloky sú napísané v shortcode syntaxi Avada
> Builderu (rad 7.x). Neznáme atribúty Avada ticho ignoruje a chýbajúce dopĺňa
> z Global Options, takže import prejde — ale konkrétne rozostupy či veľkosti
> si po vložení prejdite v builderi. Bloky neboli testované na živej inštalácii.

## Postup

### 1. Farby a písma (raz na začiatku)

**Avada → Options → Colors:**

| Nastavenie | Hodnota |
|---|---|
| Primary Color | `#28afc3` |
| Text Color | `#2a4750` |
| Headings Color | `#0a1f26` |
| Link Color | `#14707f` |
| Link Hover Color | `#0a1f26` |

**Avada → Options → Header:** Header Background Color `#28afc3`.

**Avada → Options → Menu → Main Menu:** farba písma `#ffffff`, farba pri prejdení
myšou `#ffffff`, pozadie rozbaľovacieho menu `#28afc3` a jeho text `#ffffff`.
V časti **Mobile Menu** rovnako: pozadie `#28afc3`, text `#ffffff`.

**Avada → Options → Typography:** nadpisy `Fraunces` (700), text `Inter` (400/600).
Obe sú na Google Fonts, Avada ich ponúkne v zozname.

**Avada → Options → Custom CSS:** vložte celý obsah `custom-css.css`.
Bez neho budú fungovať všetky bloky, ale nadpisíky sekcií, číslované kroky,
údaje pre vyhlásenie a číslo účtu budú vyzerať ako obyčajný text.

### 2. Obrázky a dokumenty

Nahrajte do knižnice médií (Média → Pridať nový) obrázky a PDF tlačivá.
V blokoch sú cesty ako `https://VASA-DOMENA.sk/wp-content/uploads/nadacia/hero-1.jpg`
— nahraďte ich skutočnými adresami z knižnice médií (v detaile súboru je
„Adresa súboru"). Najrýchlejšie hromadne: otvorte súbor bloku v poznámkovom
bloku a nahraďte `https://VASA-DOMENA.sk/wp-content/uploads/nadacia/` za svoju cestu.

Obrázky z návrhu nájdete v `../assets/img/` (sú vo formáte SVG — ak ich chcete
použiť, WordPress povolenie na SVG štandardne nemá; jednoduchšie je nahrať
skutočné fotografie).

### 3. Vloženie blokov

1. Stránky → Pridať novú → názov napr. „Domov".
2. Ak je zapnutý Avada Builder, prepnite sa na **Default Editor**
   (tlačidlo „Toggle Builder" hore).
3. Otvorte súbor bloku, skopírujte **celý obsah** a vložte ho do editora.
   Bloky vkladajte v poradí 01 → 12, jeden pod druhý (alebo naraz celý
   `vsetky-bloky.txt`).
4. Prepnite sa späť na **Avada Builder** — sekcie sa načítajú ako kontajnery,
   stĺpce a elementy, ktoré už upravujete klikaním.
5. Stránku uložte a nastavte ako titulnú: Nastavenia → Zobrazovanie → statická stránka.

**Alternatíva — po jednom do knižnice:** Avada → Library → Add New → typ
*Container*, obsah vložte rovnako cez Default Editor. Blok potom vkladáte na
akúkoľvek stránku cez element „Library Element".

### 4. Menu a kotvy

Kontajnery majú nastavené kotvy (`menu_anchor`): `uvod`, `o-nas`, `ako-pomahame`,
`ziadost-o-pomoc`, `projekty`, `clanky`, `galeria`, `dve-percenta`, `podpora`,
`kontakt`. V menu (Vzhľad → Menu) stačí dať vlastný odkaz `#o-nas` atď.

## Zoznam blokov

| Súbor | Sekcia | Použité elementy Avady |
|---|---|---|
| `01-hero.txt` | úvodný panel: vľavo text a tlačidlá, vpravo posuvač fotiek 4:5 | Title, Text, Button, Image Carousel |
| `01b-hero-meniaci-sa-text.txt` | návod, ako rozhýbať aj text v hero | Avada Slider (nastavuje sa v builderi) |
| `hero-texty.txt` | texty štyroch snímok hero bannera | — |
| `02-cisla.txt` | pás so štyrmi číslami | Counters Box |
| `03-o-nas.txt` | o nadácii + zoznam, komu pomáhame | Image Frame, Title, Text, Checklist, Button |
| `04-ako-pomahame.txt` | štyri spôsoby pomoci | Content Boxes |
| `05-ziadost-o-pomoc.txt` | postup + tlačivá na stiahnutie | Title, Text, Button (odkaz na PDF) |
| `06-projekty.txt` | tri projekty | Image Frame, Title, Text, Button |
| `07-clanky.txt` | náhľad článkov ako mriežka | Blog |
| `07b-clanky-karusel.txt` | návod na karusel článkov | Post Cards (nastavuje sa v builderi) |
| `08-galeria.txt` | galéria s lightboxom | Gallery |
| `09-dve-percenta.txt` | postup + údaje + vyhlásenie na stiahnutie | Title, Text, Button |
| `10-podpora.txt` | tri spôsoby podpory + IBAN | Title, Text, Button |
| `11-pribehy.txt` | ohlasy | Testimonials |
| `13-partneri.txt` | logá partnerov (vkladá sa pred blok 12) | shortcode `[nadacia_partneri]` z pluginu |
| `12-kontakt.txt` | kontaktné údaje + miesto na formulár | Checklist, Social Links, Form |
| `vsetky-bloky.txt` | všetko v jednom súbore | — |

## Transparentný účet a kopírovanie IBAN

Blok 10 obsahuje dva účty — bežný a transparentný (SK23 8330 0000 0027 0189 5491).
V texte bloku ešte nahraďte `ODKAZ-NA-TRANSPARENTNY-UCET` adresou výpisu
v internet bankingu. Ak sa číslo účtu zmení, prepíšte ho na dvoch miestach:
vo výpise a v atribúte `data-ak-copy`, tam bez medzier.

Tlačidlá *Kopírovať IBAN* potrebujú kúsok JavaScriptu, ktorý nesie plugin
z priečinka `../wordpress-plugin/`. Ak bloky vkladáte ručne bez pluginu, buď
plugin doinštalujte, alebo tlačidlá z bloku vymažte — číslo účtu zostane
čitateľné aj tak.

## Partneri

Blok 13 vypisuje logá cez shortcode `[nadacia_partneri]`, ktorý pridáva plugin
z priečinka `../wordpress-plugin/`. Partnerov potom spravujete v administrácii
(**Bloky nadácie → Partneri**) — nahráte logo z knižnice médií, alebo pri firme
bez loga vyplníte len názov, ktorý sa vypíše textom. Zmeny sú na webe hneď,
blok netreba znova vkladať.

Bez pluginu shortcode nič nevypíše — vtedy do sekcie vložte logá ručne
(napríklad elementom Image Carousel alebo stĺpcami s Image Frame).

## Čo treba doplniť po vložení

- **Adresy obrázkov a PDF** namiesto `VASA-DOMENA.sk` (viď krok 2).
- **Kontaktný formulár** — Avada → Forms → Add New, potom ho vložte do
  pravého stĺpca bloku 12 (postup je v komentári priamo v bloku).
- **Odkazy na sociálne siete** v bloku 12 — teraz vedú na domovské stránky sietí.
- **Karusel článkov** podľa `07b-clanky-karusel.txt`, ak chcete posuvač
  namiesto mriežky.
- **Ohlasy v bloku 11** sú ilustračné — nahraďte ich skutočnými so súhlasom rodín.
