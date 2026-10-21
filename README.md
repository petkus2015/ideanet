# IDEANET — one-page web pre tvorcu reels

Statická jednostránka pre videografa, ktorý pre klientov **natáča a strihá vertikálny obsah**
(sám nestojí pred kamerou). Okrem interaktívneho portfólia obsahuje kompletnú ponuku —
mesačné balíky pre firmy, white-label cenník pre agentúry, eventy, kalkulačku ceny
a podmienky spolupráce.

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
app.js              interakcie, dáta portfólia, kalkulačka
ponuka/             samostatné ponuky na poslanie klientovi
docs/               stratégia a cenník v textovej podobe
assets/posters/     náhľady kariet 9:16 (SVG)
assets/videos/      videá portfólia
```

## Obsah stránky

| Sekcia | Čo obsahuje |
|---|---|
| Hero | pozicionovanie „prídem → natočím → zostrihám → máte obsah“ |
| Portfólio | interaktívny pás vertikálnych videí |
| Vizuály | druhý pás s ukážkami grafiky a identít v pomere 4:5 |
| Služby | tri cesty spolupráce (firmy / agentúry / eventy) + čo je vždy v cene |
| Pre koho | segmenty, kde video priamo pomáha predávať |
| Proces | štyri kroky od zadania po dodanie |
| Cenník | prepínač troch pohľadov — firmy, agentúry (white-label), eventy |
| Kalkulačka | interaktívny konfigurátor — počet Reels, celodenná účasť, doplnky a doprava |
| Podmienky | osem krátkych kariet + odkaz na plnú ponuku |
| Kontakt | dopytový formulár s predvyplneným balíkom |

## Odkazy na sociálne siete

Ikony sú v pätičke a v rozbalenom mobilnom menu — Instagram, TikTok,
YouTube a Facebook. **Adresy sú zástupné** (`ideanet.studio`), treba ich
prepísať na skutočné profily; nachádzajú sa na dvoch miestach
v `index.html`, v blokoch s triedou `socials`.

Pridanie ďalšej siete znamená skopírovať jeden `<a>` s vlastnou
`aria-label` a cestou ikony.

## Svetlo pod kurzorom

Za obsahom sa pohybuje jemné svetlo, ktoré sa k myši približuje, nie ju
kopíruje (`#cursorGlow`). Leží na `z-index:-1`, takže nič netónuje ani
neblokuje klikanie.

Pri kliknutí sa od kurzoru rozbehne tenký prstenec (`.ripple`). Ten je
naopak nad obsahom, inak by ho karty prekryli práve tam, kde človek
klikne; `pointer-events:none` ho drží mimo cesty.

Oboje sa na dotykových zariadeniach a pri `prefers-reduced-motion`
vôbec nezapína.

## Portrét v značke

Vedľa mena je v hlavičke aj v pätičke kruhový portrét, ktorý sa pri
prechode myšou nakloní. `assets/foto-peter.svg` je **zástupný obrázok** —
nahraďte ho vlastnou fotkou:

1. nahrajte štvorcovú fotku, napríklad `assets/foto-peter.jpg`
2. v `index.html` prepíšte `src` v oboch `.brand__mark` (hlavička a pätička)

Fotka sa orezáva cez `object-fit:cover`, takže štvorec nie je podmienka,
ale pri inom pomere sa oreže na stred.

## Konfigurátor zákazky

Kalkulačka počíta jednorazovú produkciu z počtu Reels:

- prvý Reel: **160 €**, natáčanie do 2 hodín
- každý ďalší: **+95 €** a **+1 hodina** natáčania
- celodenná účasť (do 8 hodín): **420 € vrátane 3 Reels**, ďalšie +95 €

Prírastok 95 € nie je náhodný — je odvodený z cenníka tak, aby
3 Reels vyšli presne na 350 €, ako sú uvedené v ponuke.

Doplnky sú zaškrtávacie: same-day (+50 %), RAW (+80 €), grafika na event
(+180 €) a správa sociálnych sietí (+300 €/mes.). Tá posledná sa do
jednorazovej sumy nesčítava a uvádza sa zvlášť, lebo je mesačná.

Sadzby sú konštanty na začiatku bloku *9) KALKULAČKA* v `app.js`:

```js
const FIRST = 160, NEXT = 95, DAY = 420, DAY_REELS = 3,
      KM_RATE = .4, RAW = 80, GRAPHICS = 180, SOCIAL = 300, RUSH = .5;
```

## Cenník

Ceny sú v `index.html` uvedené priamo v texte, kalkulačka ich číta
z atribútov `value` v `#calcPkg`. **Pri zmene ceny upravte obe miesta.**

**Firmy — mesačne:** START 390 € · GROW 690 € · PRO 990 €
**Firmy — jednorazovo:** 1 Reel 160 € · 3 Reels 350 €
**Podcast:** Podcast u vás, epizóda do 70 minút 400 €
**Agentúry (white-label):** Reel Basic 120 € · Content Mini 250 € · Content Day 390 €
· Content Plus 550 € · Same-Day Event 290 € · Event Plus 450 € · Partner od 1 200 €/mes.
**Eventy:** Event Mini 290 € · Event 450 € · Event Same-Day 590 € · ďalšia hodina 70 €
**Grafika na event:** 180 € za sadu formátov, 2 kolá revízií
**Vizuály a identita:** Vizuály pre siete 280 € · Vizuálna identita 690 €

Sadzby v kalkulačke sú konštanty na začiatku bloku *9) KALKULAČKA* v `app.js`:

```js
const KM_RATE = .4, HOUR_RATE = 60, RAW_FEE = 80;
```

Príplatky za expres (30 % / 50 %) sú v `value` atribútoch `#calcSpeed`.

## Prepínač cenníka

Tri panely (`#panel-firmy`, `#panel-agentury`, `#panel-eventy`) prepína
`role="tablist"` — myšou aj šípkami ← →. Karty v sekcii Služby majú
`data-tab` a otvoria príslušný pohľad. Každé tlačidlo balíka nesie
`data-package` — po kliknutí predvyplní výber v kontaktnom formulári.

## Stratégia a ponuka v textovej podobe

V priečinku `docs/` sú dva interné dokumenty — nie sú určené klientom.

| Súbor | Obsah |
|---|---|
| `docs/strategia.md` | pozicionovanie, štyri zdroje príjmu, koho a ako oslovovať, white-label model, čo nerobiť, plán na prvý týždeň, čo ešte treba rozhodnúť |
| `docs/ponuka.md` | celý cenník a podmienky v texte plus hotové texty na skopírovanie do správy alebo e-mailu |

## Ponuky na poslanie klientovi

V priečinku `ponuka/` sú dve samostatné jednostránkové ponuky. Sú úplne
sebestačné — štýly majú vnútri súboru a nenačítavajú nič zvonku, takže sa dajú
poslať e-mailom ako príloha, vytlačiť alebo uložiť do PDF (tlačidlo **Uložiť
ako PDF** v hlavičke, pred tlačou sa automaticky rozbalia všetky otázky).

| Súbor | Pre koho | Čím argumentuje |
|---|---|---|
| `ponuka/pre-firmy.html` | menšie firmy a živnostníci | mesačné balíky, priebeh spolupráce, FAQ pre klientov, ktorí video ešte nerobili |
| `ponuka/pre-agentury.html` | agentúry a väčšie firmy | white-label model, rozdelenie práce, cenník produkcie, paušál Partner a výpočet marže |

Obe majú `<meta name="robots" content="noindex">` — sú určené na priame
poslanie, nie do vyhľadávania.

**Pozor pri úprave cien:** každý súbor si nesie vlastnú kópiu štýlov aj čísel.
Pri zmene cenníka treba upraviť `index.html`, `#calcPkg` v `index.html`
a obe ponuky v `ponuka/`.

## Interakcie portfólia

| Ovládanie | Čo robí |
|---|---|
| ťahanie prstom | natívne skrolovanie prehliadača aj s jeho zotrvačnosťou |
| ťahanie myšou | posúva pás; po švihu doletí zotrvačnosťou na najbližšiu kartu |
| koliesko myši | posúva pás voľne, po zastavení dojde na kartu; na kraji pokračuje skrolovanie stránky |
| šípky ← → alebo bočné tlačidlá | posun o jednu kartu |
| klik na kartu / Enter | spustí a zastaví ukážku |
| tlačidlo **Zvuk** | zapne alebo vypne zvuk |
| tlačidlo **Rozbaliť** | otvorí video na celú výšku (Esc zavrie, ← → prepína) |

Prehráva sa vždy len jedno video; pri odchode karty zo stredu alebo pásu
z obrazovky sa prehrávanie zastaví.

### Navigácia

Lišta hore je priehľadná, po odskrolovaní sa odlepí od okraja a zmení sa
na plávajúcu kapsulu (`is-stuck`). Pri skrolovaní dole sa odsunie preč
(`is-away`), pri pohybe hore sa vráti — spúšťa sa až po prvej obrazovke,
aby hneď na začiatku neposkakovala.

Pod odkazmi pláva značka (`#navPill`), ktorá sa presúva pod kurzor
a po odídení myši sa vráti na práve čítanú sekciu. Pozíciu počíta
JavaScript z `offsetLeft` a `offsetWidth` odkazu, takže sa prepočítava
pri zmene šírky okna aj po načítaní písiem.

Na mobile hore zostáva len značka a navigáciu preberá **plávajúca lišta
pri spodnom okraji** (`.tabbar`) — palec na ňu dosiahne, na vršok displeja
nie. Má päť miest: Portfólio, Cenník, Kalkulačka, Kontakt a Viac.
Vlastná plávajúca značka sleduje čítanú sekciu; keď je čítaná sekcia,
ktorá v lište nie je (Služby, Podmienky), značka sa schová namiesto toho,
aby ukazovala inam.

Tlačidlo **Viac** otvára celoobrazovkové menu so všetkými šiestimi
sekciami, postupným odkrývaním položiek a vlastným zatváracím tlačidlom.
Stránka pod ním sa zamkne.

Pozadie oboch líšt je takmer nepriehľadné a rozostrenie sa pridáva len
cez `@supports`, aby lišty zostali čitateľné aj tam, kde prehliadač
`backdrop-filter` nevie.

Úplne hore je tenký ukazovateľ prečítanej časti stránky.

### Ako je riešená plynulosť

Pás sa nikdy nehýbe dvoma spôsobmi naraz:

- **Dotyk** si rieši prehliadač sám — má vlastné zotrvačné skrolovanie
  aj CSS snap a čokoľvek vlastné by bolo horšie.
- **Myš, koliesko a tlačidlá** vedie jediná časovo riadená animácia
  (`easeOutCubic`). Počas nej sa CSS snap vypína triedou `is-gliding`
  a vracia sa až vtedy, keď pás stojí presne na karte.

Dôležité je poradie: snap sa musí vypnúť **skôr**, než sa prvýkrát
prečíta `scrollLeft`. Čítanie si vynúti prepočet layoutu a snap v tej
chvíli stihne pásom šklbnúť.

Geometria kariet sa meria raz (`measure()`), nie pri každom snímku —
čítanie `offsetLeft` počas skrolovania bol druhý zdroj trhania.

## Prispôsobenie

- **Farby, rádiusy, šírka obsahu** — premenné v `:root` v `styles.css`.
- **Texty, ceny, kontakt** — priamo v `index.html` (ceny aj v `#calcPkg`).
- **Formulár** momentálne otvára e-mailového klienta (`mailto:`).
  Pre odosielanie na server nahraďte záver `submit` handlera v `app.js`
  volaním `fetch()` na váš endpoint.

## Dostupnosť a výkon

- Plne responzívne od 320 px po veľké monitory (`clamp()`, flexibilné mriežky).
- Ovládanie klávesnicou, `aria` popisy, viditeľný focus, respektuje
  `prefers-reduced-motion`.
- Videá sa načítavajú až pri prehratí (`preload="none"`), obrázky lazy-load.
