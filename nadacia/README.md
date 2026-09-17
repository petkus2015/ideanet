# Nadácia Anjelské krídla — moderný web

Čistá, moderná jednostránka pre nadáciu: hero slider, galéria s lightboxom,
sekcia 2 % z dane, darcovská sekcia s kopírovaním IBAN a kontaktný formulár.

Bez build kroku a bez závislostí — čisté HTML, CSS a JavaScript.

## Spustenie

```bash
cd nadacia
python3 -m http.server 8000
# otvorte http://localhost:8000
```

Nasadenie: nahrajte obsah priečinka `nadacia/` na akýkoľvek statický hosting
(GitHub Pages, Netlify, Vercel, FTP).

## Štruktúra

```
index.html        obsah a texty stránky
styles.css        dizajn (biela + tyrkysová #28afc3, doplnková červená #f9193e)
app.js            slider, galéria, lightbox, formulár, kopírovanie údajov
assets/img/       obrázky (hero-*.svg, gal-*.svg, card-*.svg, about.svg) a logo
assets/dokumenty/ tlačivá na stiahnutie (PDF) — podrobnosti v README priečinka
avada/            tá istá jednostránka ako bloky pre Avada Builder (WordPress)
wordpress-plugin/ inštalačný ZIP pluginu, ktorý bloky pridá do Avada Library
```

## Obrázky — ako nasadiť skutočné fotky

Obrázky v `assets/img/` sú **ilustračné vektorové scény**, nie fotografie.
Nahradiť ich vlastnými fotkami je otázka dvoch krokov:

1. Fotky nahrajte do `assets/img/` (odporúčaný formát `.webp` alebo `.jpg`,
   šírka 1600–2000 px, **hero na výšku v pomere 4:5**, galéria na šírku 4:3 a na výšku 3:4).
2. Upravte cesty v `app.js` — polia `SLIDES` (hero) a `GALLERY` (galéria) —
   a v `index.html` obrázky sekcie *O nás* a *Projekty*.

```js
// app.js — hero
{ img:'assets/img/hero-1.jpg',
  alt:'Popis fotky pre čítačky obrazovky',
  eyebrow:'Nadácia Anjelské krídla',
  title:'Pomoc, ktorá nesie ďalej',
  text:'Jedna až dve vety.',
  cta:[{t:'Chcem pomôcť', href:'#podpora', k:'light'}] }

// app.js — galéria (span: 'w2' širšia dlaždica, 'h2' vyššia, '' základná)
{ src:'assets/img/gal-01.jpg', cap:'Popis fotky', span:'w2' }

// app.js — články v karuseli
{ img:'assets/img/clanok-01.jpg', meta:'Pomoc rodinám', title:'Titulok článku', href:'/clanok/…' }
```

Kompozícia dlaždíc sa skladá automaticky (`grid-auto-flow:dense`), takže
poradie a počet fotiek môžete meniť ľubovoľne. Fotky v galérii sú orezané
so zameraním na spodnú časť (`object-position:center 82%`) — ak budú vaše
fotky komponované inak, zmeňte túto hodnotu v `styles.css`.

**Pri fotkách rodín a detí nezabudnite na písomný súhlas so zverejnením.**

## Logo nadácie

Logo je už nasadené:

| Súbor | Kde sa používa |
|---|---|
| `assets/img/logo.png` | celé logo aj s rukou písaným názvom — v hlavičke aj v pätičke |
| `assets/img/logo-mark.png` | samotná značka bez názvu — záloha, ak by ste chceli v hlavičke len krídla so srdcom |

Logo je biele s červeným srdcom, teda určené na tmavý podklad — v hlavičke je
tyrkysové pozadie, takže vynikne bez akejkoľvek dlaždice. V hlavičke aj v pätičke
je celé logo vrátane rukou písaného názvu, preto sa vedľa neho nesádže názov
znova. Ak by ste chceli v hlavičke len krídla so srdcom, v `app.js` stačí
v riadku pre hlavičku vymeniť `LOGO_FULL` za `LOGO_MARK`.

Ak logo vymeníte, zachovajte oba súbory a rovnaké názvy. Cesty sú v konštantách
`LOGO_MARK` a `LOGO_FULL` na začiatku `app.js`, veľkosti v `styles.css`
(`.brand__mark--img` pre hlavičku, `.foot .brand__mark--full` pre pätičku).
Keby súbory chýbali, web sa nerozbije — vráti sa ku kreslenej značke.

Pre ostrejšie zobrazenie na retina displejoch sa hodí logo vo väčšom rozlíšení
(súčasné má 120 × 120 px) alebo vo formáte SVG.

## Čo pred spustením doplniť

| Kde | Čo |
|---|---|
| `index.html` — sekcia *Príbehy* | ohlasy sú ilustračné, nahraďte ich skutočnými so súhlasom rodín |
| `index.html` — pätička, sekcia *Kontakt* | odkazy na Facebook, Instagram a YouTube vedú zatiaľ na domovské stránky sietí |
| `assets/img/` | ilustračné scény nahraďte fotografiami |
| `assets/dokumenty/` | doplňte 3 PDF tlačivá: `ziadost-o-prispevok.pdf`, `suhlas-ochrana-osobnych-udajov.pdf`, `vyhlasenie-2-percenta.pdf` (viď README v priečinku) |
| `app.js` — `form.addEventListener('submit')` | formulár otvára e-mailového klienta (`mailto:`); pre odosielanie na server nahraďte záver handlera volaním `fetch()` na váš endpoint |
| `app.js` — `UCET` | IBAN transparentného účtu (už vyplnený) a odkaz na jeho výpis — ten zatiaľ vedie len na stránku banky |

Overené údaje nadácie, ktoré web už obsahuje: IČO 50622510, sídlo
Slovenských dobrovoľníkov 135/30, 010 03 Žilina, IBAN
SK37 1100 0000 0029 4603 3398, e-mail info@nadaciaanjelskekridla.sk,
telefón +421 948 661 333, registrácia MV SR 22. 12. 2016 č. 203/Na-2002/1152.
Pred spustením ich odporúčame ešte raz porovnať s aktuálnymi dokumentmi nadácie.

## Interakcie

| Prvok | Ovládanie |
|---|---|
| hero banner | prehráva sa sám hneď po načítaní; vľavo sa mení text a tlačidlá, vpravo fotky v pomere 4:5. Ovládanie sú dve šípky a tlačidlo stop/play. Posun šípkami, klávesmi ← → a prstom po fotke prehrávanie nezastaví, len reštartuje odpočet. Sám sa pozastaví pri prejdení myšou a pri prepnutí záložky. |
| karusel článkov | 4,5 karty na desktope a 1,5 na mobile; posun prstom, šípkami, guličkami aj klávesmi ← → |
| galéria | klik alebo Enter otvorí lightbox; ← → prepína, Esc zatvára, potiahnutie prstom prepína |
| údaje 2 % a IBAN | tlačidlo *Kopírovať* vloží hodnotu do schránky |
| partneri | logá v mriežke, firma bez loga sa vypíše názvom; zoznam je v poli `PARTNERI` v `app.js` |
| tlačivá na stiahnutie | tlačidlo stiahne PDF; kým súbor v `assets/dokumenty/` chýba, zobrazí sa odkaz na e-mail nadácie namiesto chybovej stránky |
| formulár | validácia na strane prehliadača + zvýraznenie chybných polí; pri téme *Žiadosť o pomoc* pribudne pole na prílohy |
| transparentný účet | vlastný IBAN s tlačidlom *Kopírovať* a odkazom na výpis; údaje nastavíte v objekte `UCET` v `app.js` |

## Verzia pre WordPress (Avada)

Sú dve možnosti, obe vedú k tomu istému — sekcie sa dajú skladať v Avada Builderi:

- **Plugin (odporúčame)** — `wordpress-plugin/nadacia-anjelske-kridla-bloky.zip`
  nainštalujete cez Pluginy → Pridať nový → Nahrať plugin. Po aktivácii kliknete
  na *Bloky nadácie → Importovať* a sekcie sa objavia v Avada Library, aj so
  štýlmi a ilustračnými obrázkami. Návod je v `wordpress-plugin/nadacia-anjelske-kridla-bloky/README.md`.
- **Ručne** — priečinok `avada/` obsahuje tie isté sekcie ako textové súbory
  so shortcodmi, ktoré sa vkladajú cez klasický editor. Podrobnosti v `avada/README.md`.

### Prílohy k žiadosti

Pri téme *Žiadosť o pomoc* sa vo formulári objaví pole na prílohy. Keďže stránka
odosiela cez `mailto:`, prehliadač samotné súbory pripojiť nevie — do správy sa
doplnia ich názvy a návštevník ich pripojí k otvorenému e-mailu. Ak chcete, aby
sa súbory naozaj nahrávali, formulár napojte na serverový endpoint
(alebo použite formulár Avady, ktorý pole Upload podporuje).

## Prispôsobenie

- **Farby, rádiusy, šírka obsahu, typografia** — premenné v `:root` v `styles.css`.
  Hlavná tyrkysová je `--brand` (#28afc3), jej tmavší odtieň na text `--brand-dk`,
  doplnková červená `--red` (#f9193e). Identita (logo, ikony, kroky, čísla) používa
  prechod `--grad`, hlavné tlačidlá červený `--grad-cta`.
- **Texty** — priamo v `index.html` (hero a galéria v `app.js`).
- **Rýchlosť slidera** — konštanta `DUR` v `app.js` (predvolene 6500 ms).

## Dostupnosť a výkon

- Responzívne od 320 px po veľké monitory (`clamp()`, flexibilné mriežky).
- Ovládanie klávesnicou, `aria` popisy, udržanie fokusu v lightboxe, viditeľný focus.
- Rešpektuje `prefers-reduced-motion` — vypne animácie aj automatický slider.
- Obrázky sa načítavajú postupne (`loading="lazy"`), prvá snímka hero prioritne.
- Bez JavaScriptu zostane obsah čitateľný (animácie odhaľovania sa zapínajú až s JS).
