# IDEANET — web pre videografa, grafika a správcu sociálnych sietí

Statická jednostránka, ktorá prezentuje tri služby (**videografia**, **grafický dizajn**,
**správa sociálnych sietí**) a ukážky prác v karuseloch.

Čistý tmavý dizajn, bez build kroku a bez závislostí — HTML, CSS a vanilla JavaScript.

## Spustenie

```bash
python3 -m http.server 8000
# otvorte http://localhost:8000
```

Nasadenie: nahrajte obsah priečinka na akýkoľvek statický hosting
(GitHub Pages, Netlify, Vercel, FTP).

## Štruktúra

```
index.html          obsah a štruktúra stránky
styles.css          dizajn (tmavá paleta, mätový akcent)
app.js              dáta portfólia + interakcie
assets/posters/     náhľady kariet (SVG) — video 9:16, grafika 4:5, social 9:16
assets/videos/      ukážkové klipy
```

## Karusely

Tri nezávislé karusely — videografia, grafika, sociálne siete.

| Šírka obrazovky | Kariet naraz |
|---|---|
| ≥ 1181 px (desktop) | **5,5** |
| 981 – 1180 px | 4,5 |
| 721 – 980 px | 3,5 |
| 561 – 720 px | 2,5 |
| ≤ 560 px (mobil) | **1,5** |

Polovičná karta na kraji naznačuje, že pás pokračuje ďalej.
Počet mení jediná premenná `--per` v `styles.css` (sekcia *Responzívne*),
medzeru medzi kartami premenná `--gap`.

### Ovládanie

| Ovládanie | Čo robí |
|---|---|
| swajpovanie prstom doprava/doľava | posúva pás (mobil aj tablet) |
| ťahanie myšou | posúva pás, po pustení dorovná na kartu |
| bočné šípky | posun o jednu obrazovku kariet (na mobile skryté) |
| šípky ← → na klávesnici | posun o jednu kartu (keď má pás fokus) |
| klik na kartu | spustí a zastaví ukážku videa |
| tlačidlo v rohu karty | otvorí ukážku na celú obrazovku (Esc zavrie, ← → prepína) |
| prejdenie myšou | na desktope spustí tichý náhľad |

Prehráva sa vždy len jedno video naraz; keď karta opustí viditeľnú časť pásu,
prehrávanie sa zastaví.

## Ako doplniť vlastný obsah

Všetky ukážky sú v poli `DATA` na začiatku `app.js` — tri sekcie:
`video`, `graphic` a `social`. Poradie v poli = poradie na webe.

### Video a sociálne siete

1. Nahrajte **MP4 na výšku (9:16)** do `assets/videos/`, napr. `reel-01.mp4`.
2. Ako `poster` použite snímku z videa (JPG aj SVG sú v poriadku).

```js
{ id:'v1', cat:'Gastro', title:'Ranná káva',
  desc:'Brand film pre mestskú kaviareň.', dur:'0:18',
  poster:'assets/posters/video-01.svg',
  video:'assets/videos/reel-01.mp4',      // vaše video (má prednosť)
  demo:'assets/videos/reel-01.webm' },    // zástupný klip
```

Prehliadač si vyberie prvý zdroj, ktorý vie prehrať — kým `.mp4` nedodáte,
prehrá sa zástupný `.webm` klip. Keď chýbajú oba, karta zostane na plagáte
a zobrazí sa poznámka.

### Grafika

```js
{ id:'g1', cat:'Identita', title:'NORDA Studio',
  desc:'Logo, značkový systém a pravidlá používania.', meta:'Logo',
  image:'assets/posters/graphic-01.svg' },   // pomer 4:5
```

Karty grafiky sa neprehrávajú — klik otvorí ukážku na celú obrazovku.

### Hero ukážka

Klip v hlavičke je `#heroVideo` v `index.html`. Nahraďte
`assets/videos/hero.mp4` svojím videom (kým neexistuje, prehrá sa zástupný klip).

## Prispôsobenie

- **Farby, rádiusy, šírka obsahu, počet kariet** — premenné v `:root` v `styles.css`.
  Celá paleta je tmavá a odvodená z tokenov: `--canvas` (pozadie), `--paper`
  (karty), `--tint` (tónovaná sekcia), `--ink` / `--ink-2` / `--ink-3` (text),
  `--accent` + `--accent-2` (akcent, pre text na akcentnej ploche slúži
  `--accent-ink`) a `--warm` (teplý detail). Zmena odtieňa značky = prepísať
  `--accent`, `--accent-2` a `--warm`; zvyšok webu sa prispôsobí sám.
- **Texty, ceny, kontakt** — priamo v `index.html`.
- **Formulár** momentálne otvára e-mailového klienta (`mailto:`).
  Pre odosielanie na server nahraďte blok `location.href = 'mailto:…'`
  v `app.js` volaním `fetch()` na váš endpoint.
- E-mail `ahoj@ideanet.sk` a telefón sú zástupné — vymeňte ich v `index.html`
  (sekcia kontakt, pätička) aj v `app.js` (odosielanie formulára).

## Dostupnosť a výkon

- Responzívne od 320 px po veľké monitory.
- Ovládanie klávesnicou, `aria` popisy, viditeľný focus, rešpektuje
  `prefers-reduced-motion`.
- Videá sa načítavajú až pri prehratí (`preload="none"`), obrázky lazy-load.
- Žiadne knižnice ani sledovacie skripty.

## Ukážkové podklady

Všetky plagáty a klipy v `assets/` sú zástupné — vygenerované len preto,
aby web vyzeral kompletne. Nahraďte ich vlastnými prácami.
