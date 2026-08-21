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
styles.css        dizajn (krémová + zlatá/ružová, tmavý indigo text)
app.js            slider, galéria, lightbox, formulár, kopírovanie údajov
assets/img/       obrázky (hero-*.svg, gal-*.svg, card-*.svg, about.svg)
```

## Obrázky — ako nasadiť skutočné fotky

Obrázky v `assets/img/` sú **ilustračné vektorové scény**, nie fotografie.
Nahradiť ich vlastnými fotkami je otázka dvoch krokov:

1. Fotky nahrajte do `assets/img/` (odporúčaný formát `.webp` alebo `.jpg`,
   šírka 1600–2000 px, hero na šírku 16:10, galéria na šírku 4:3 a na výšku 3:4).
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
```

Kompozícia dlaždíc sa skladá automaticky (`grid-auto-flow:dense`), takže
poradie a počet fotiek môžete meniť ľubovoľne. Fotky v galérii sú orezané
so zameraním na spodnú časť (`object-position:center 82%`) — ak budú vaše
fotky komponované inak, zmeňte túto hodnotu v `styles.css`.

**Pri fotkách rodín a detí nezabudnite na písomný súhlas so zverejnením.**

## Čo pred spustením doplniť

| Kde | Čo |
|---|---|
| `index.html` — sekcia *Príbehy* | ohlasy sú ilustračné, nahraďte ich skutočnými so súhlasom rodín |
| `index.html` — pätička, sekcia *Kontakt* | odkazy na Facebook, Instagram a YouTube vedú zatiaľ na domovské stránky sietí |
| `assets/img/` | ilustračné scény nahraďte fotografiami |
| `app.js` — `form.addEventListener('submit')` | formulár otvára e-mailového klienta (`mailto:`); pre odosielanie na server nahraďte záver handlera volaním `fetch()` na váš endpoint |

Overené údaje nadácie, ktoré web už obsahuje: IČO 50622510, sídlo
Slovenských dobrovoľníkov 135/30, 010 03 Žilina, IBAN
SK37 1100 0000 0029 4603 3398, e-mail info@nadaciaanjelskekridla.sk,
telefón +421 948 661 333, registrácia MV SR 22. 12. 2016 č. 203/Na-2002/1152.
Pred spustením ich odporúčame ešte raz porovnať s aktuálnymi dokumentmi nadácie.

## Interakcie

| Prvok | Ovládanie |
|---|---|
| hero slider | šípky, bodky, klávesy ← →, potiahnutie prstom, tlačidlo pauzy; sám sa zastaví pri prejdení myšou a pri prepnutí záložky |
| galéria | klik alebo Enter otvorí lightbox; ← → prepína, Esc zatvára, potiahnutie prstom prepína |
| údaje 2 % a IBAN | tlačidlo *Kopírovať* vloží hodnotu do schránky |
| formulár | validácia na strane prehliadača + zvýraznenie chybných polí |

## Prispôsobenie

- **Farby, rádiusy, šírka obsahu, typografia** — premenné v `:root` v `styles.css`.
- **Texty** — priamo v `index.html` (hero a galéria v `app.js`).
- **Rýchlosť slidera** — konštanta `DUR` v `app.js` (predvolene 6500 ms).

## Dostupnosť a výkon

- Responzívne od 320 px po veľké monitory (`clamp()`, flexibilné mriežky).
- Ovládanie klávesnicou, `aria` popisy, udržanie fokusu v lightboxe, viditeľný focus.
- Rešpektuje `prefers-reduced-motion` — vypne animácie aj automatický slider.
- Obrázky sa načítavajú postupne (`loading="lazy"`), prvá snímka hero prioritne.
- Bez JavaScriptu zostane obsah čitateľný (animácie odhaľovania sa zapínajú až s JS).
