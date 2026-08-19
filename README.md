# IDEANET — one-page web pre tvorcu reels

Statická jednostránka pre videografa, ktorý pre klientov **natáča a strihá vertikálny obsah**
(sám nestojí pred kamerou). Ťažiskom je interaktívne portfólio videí na výšku.

Bez build kroku a bez závislostí — čisté HTML, CSS a JavaScript.

## Spustenie

```bash
python3 -m http.server 8000
# otvorte http://localhost:8000
```

Nasadenie: nahrajte obsah priečinka na akýkoľvek statický hosting
(GitHub Pages, Netlify, Vercel, FTP).

## Štruktúra

```
index.html          obsah stránky
styles.css          dizajn (tmavá modrá → čierna, magenta akcent)
app.js              interakcie + dáta portfólia
assets/posters/     náhľady kariet 9:16 (SVG)
assets/videos/      videá portfólia
```

## Ako vymeniť videá za vlastné

1. Nahrajte **MP4 na výšku (9:16)** do `assets/videos/`, napr. `reel-01.mp4`.
2. V `app.js` upravte pole `REELS` — cesty, názov, popis, kategóriu, dĺžku a štítky.
3. Ako plagát (`poster`) použite snímku z videa; SVG v `assets/posters/` sú len zástupné.

```js
{ id:'r1', cat:'Gastro', title:'Ranná káva',
  desc:'Brand film pre mestskú kaviareň.', dur:'0:18',
  poster:'assets/posters/reel-01.svg',
  video:'assets/videos/reel-01.mp4',      // vaše video (má prednosť)
  demo:'assets/videos/reel-01.webm',      // zástupný klip
  tags:['Brand film','Grading','Zvuk'] }
```

Prehliadač si vyberie prvý zdroj, ktorý vie prehrať — kým `.mp4` nedodáte,
prehrá sa zástupný `.webm` klip. Keď chýbajú oba, karta zostane na plagáte
a zobrazí sa poznámka.

## Interakcie portfólia

| Ovládanie | Čo robí |
|---|---|
| ťahanie prstom / myšou | posúva pás videí |
| koliesko myši | posúva pás, na kraji pokračuje skrolovanie stránky |
| šípky ← → alebo bočné tlačidlá | posun o jednu kartu |
| klik na kartu / Enter | spustí a zastaví ukážku |
| tlačidlo **Zvuk** | zapne alebo vypne zvuk |
| tlačidlo **Rozbaliť** | otvorí video na celú výšku (Esc zavrie, ← → prepína) |

Prehráva sa vždy len jedno video; pri odchode karty zo stredu alebo pásu
z obrazovky sa prehrávanie zastaví.

## Prispôsobenie

- **Farby, rádiusy, šírka obsahu** — premenné v `:root` v `styles.css`.
- **Texty, ceny, kontakt** — priamo v `index.html`.
- **Formulár** momentálne otvára e-mailového klienta (`mailto:`).
  Pre odosielanie na server nahraďte záver `submit` handlera v `app.js`
  volaním `fetch()` na váš endpoint.

## Dostupnosť a výkon

- Plne responzívne od 320 px po veľké monitory (`clamp()`, flexibilné mriežky).
- Ovládanie klávesnicou, `aria` popisy, viditeľný focus, respektuje
  `prefers-reduced-motion`.
- Videá sa načítavajú až pri prehratí (`preload="none"`), obrázky lazy-load.
