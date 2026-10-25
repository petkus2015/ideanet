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

---

# Meškanie vlakov — `vlaky/`

Prehľad aktuálneho meškania vlakov na Slovensku, obnovovaný **každých 5 minút**.
Dáta pochádzajú z mapy polohy vlakov ŽSR ([mapa.zsr.sk](https://mapa.zsr.sk/),
nedokumentované API — bez záruky).

**Čo stránka ukazuje:** počet vlakov v prevádzke, podiel vlakov načas (≤ 5 min),
priemerné a najväčšie meškanie so zmenou za posledných 5 min, rozdelenie meškania
po kategóriách, trend za 24 h, filtrovateľný zoznam (hľadanie, dopravca, kategória,
zoradenie), schému železničnej siete s polohou vlakov a tabuľu vlakov v štýle
staničnej tabule (tmavý dizajn).
Hore sa dá prepínať druh vlaku a každá karta ukazuje jeho štatistiky (podiel načas,
priemerné a maximálne meškanie, rozdelenie):

| Skupina | Označenia ŽSR |
|---|---|
| Regionálne | Os, REX, Zr, Sp, MOs |
| Rýchliky | R |
| Expresy a IC | Ex, IC, SC, RJ, LE |
| Medzinárodné | EC, EN, RJX, ICE, NJ |

Vybraná skupina filtruje súhrn, rozdelenie, graf za 24 h, mapu aj tabuľu.

**Tabuľa vlakov je hneď na začiatku.** Predvolene sú hore rýchliky a expresy
(zoradené podľa meškania), potom medzinárodné a regionálne vlaky.

**Výber stanice** (pole „Stanica“ alebo rýchle tlačidlá) zobrazí vlaky, ktoré do
stanice ešte prídu alebo v nej práve stoja, s meškaním. Vlaky, ktoré už prešli, sa
dajú zapnúť prepínačom. Smer sa určuje z trasy vlaku (`Trasa` z ŽSR, v dátach ako
`path`). Keď trasa chýba, použije sa cieľová a ďalšia stanica. Výber stanice
a druhu vlaku si prehliadač zapamätá. Zoznam staníc je v `ST` vo `vlaky/app.js`.
Členenie je v `GROUPS` vo `vlaky/app.js` a v `scripts/fetch_zsr.py`. Odpočet do ďalšej aktualizácie je v hlavičke.

## Ako to funguje

Prehliadač nemôže volať API ŽSR priamo (CORS + session cookies), preto dáta
sťahuje skript a stránka číta statický súbor:

```
scripts/fetch_zsr.py   ──►  vlaky/data/trains.json   (aktuálny stav)
                            vlaky/data/history.json  (KPI za 24 h)
vlaky/index.html       ──►  číta JSON, každých 5 min ho znova načíta
```

## Lokálne spustenie

```bash
python3 scripts/fetch_zsr.py --loop &   # sťahuje dáta každých 5 min
python3 -m http.server 8000             # http://localhost:8000/vlaky/
```

Kým `trains.json` neexistuje, stránka zobrazí jasne označené **ukážkové dáta**
(vynútiť ich možno aj cez `?demo`).

## Nasadenie na GitHub Pages

Workflow `.github/workflows/vlaky.yml` beží každých 5 minút: stiahne dáta
a nasadí celý web (vrátane `vlaky/`) na GitHub Pages. Históriu KPI si preberá
z poslednej nasadenej verzie, takže sa necommituje do gitu.

1. V repozitári: **Settings → Pages → Source: GitHub Actions**.
2. Workflow beží z vetvy `main` (cron spúšťa len predvolenú vetvu).

Poznámka: GitHub plánované behy niekedy oneskorí o pár minút. Ak by ŽSR
blokovalo IP adresy GitHubu, spustite `fetch_zsr.py --loop` na vlastnom serveri
a priečinok `vlaky/` servírujte odtiaľ.
