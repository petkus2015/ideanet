# Titulky

Webová aplikácia: nahraj video, automaticky sa otitulkuje (faster-whisper), vyber štýl
z ponuky, uprav farbu textu, font alebo podklad a exportuj MP4 s vpálenými titulkami
(plus SRT a VTT). Pomer strán 9:16, 1:1 alebo 16:9.

## Spustenie

Potrebuje Python 3.10+ a `ffmpeg` (s libass).

```bash
cd titulky
pip install -r requirements.txt
python3 server.py            # http://127.0.0.1:8000
```

Pri prvom rozpoznaní sa stiahne model Whisper (predvolene `small`, približne 0,5 GB).

| Premenná | Predvolene | Význam |
| --- | --- | --- |
| `TITULKY_MODEL` | `small` | model Whisper (`tiny`, `base`, `small`, `medium`, `large-v3`) |
| `TITULKY_RETENTION_HOURS` | `24` | po koľkých hodinách sa pracovná verzia zmaže |
| `TITULKY_MAX_UPLOAD_MB` | `1000` | najväčšie nahrávané video |
| `TITULKY_DATA` | `titulky/data` | kde sa ukladajú pracovné verzie |
| `TITULKY_ASR` | | `mock` = ukážkové titulky bez modelu (len na skúšanie) |
| `HOST`, `PORT` | `127.0.0.1`, `8000` | adresa servera |

## Verzia bez servera

`titulky-browser.html` je jeden samostatný súbor: otvor ho dvojklikom v Chrome alebo Edge.
Rozpoznanie reči (Whisper cez transformers.js) aj export (nahranie z plátna) bežia v prehliadači,
nič sa neposiela na server. Pri prvom použití potrebuje internet na stiahnutie modelu
(40 až 250 MB podľa výberu) a písem. Export beží v reálnom čase a vyrobí WebM alebo MP4
podľa prehliadača. V náhľade sa dajú titulky presunúť myšou a zapnúť sa dá zvýrazňovanie práve vyslovených slov (časy slov dá model Whisper, ak ich nevie, odhadnú sa z dĺžky slov).
Štýl Dynamický vychádza z TikTok titulkov: fráza má 1 až 3 slová, slová sa objavujú po jednom podľa reči (rozmazanie a posun zdola), prvý riadok je väčší, všetky slová majú jeden font, štýl je predvolene VEĽKÝMI písmenami (vypína sa v paneli) a fráza sa na konci rozmaže a odplynie nahor. Poloha frázy sa dá meniť zľava doprava pri každej fráze.
Vznikne z `static/` príkazom `python3 build_browser.py`.

## Verzia pre PHP webhosting (FTP)

Priečinok `php/` obsahuje `index.php`, `app.html` a `.htaccess`. Nahraj ich cez FTP do priečinka na webe
(napríklad `/titulky`) a otvor `https://tvojweb.sk/titulky/`. Server iba zobrazí stránku (a presmeruje na https),
rozpoznanie reči aj export beží v prehliadači návštevníka. `app.html` sa generuje príkazom `python3 build_browser.py` zo šablóny `browser.html` a skriptu `browser.js`.

## Nahrávanie z mobilu

Verzia so serverom funguje v mobilnom prehliadači (Safari, Chrome): rozhranie sa prispôsobí
šírke, video sa nahráva s ukazovateľom priebehu a iPhone videá vo formáte HEVC (.MOV) si server
prevedie na náhľad sám. Rozpoznanie beží na serveri, takže telefón nič nesťahuje.

- **Skúška doma:** `HOST=0.0.0.0 python3 server.py`, potom v telefóne v tej istej Wi-Fi otvor
  `http://<adresa počítača>:8000`.
- **Nasadenie na web:** `docker build -t titulky .` a spusti na hosting s Dockerom (Render, Fly.io,
  Railway, vlastný server) s trvalým diskom pripojeným na `/data` a HTTPS. Odporúčané je aspoň
  2 GB pamäte pre model `small`. Dockerfile som tu nemohol zostaviť, preto ho pri prvom nasadení skontroluj.
- Limit nahrávaného súboru je `TITULKY_MAX_UPLOAD_MB` (predvolene 1000). Ak pred aplikáciou
  stojí proxy (nginx, Cloudflare), nastav aj tam rovnaký alebo vyšší limit.

## Formáty videa (H.264, H.265)

Verzia so serverom prijme akékoľvek video, ktoré číta ffmpeg. Ak prehliadač video neprehrá
(H.265/HEVC, 10-bitové video), server vyrobí H.264 náhľad a export ide z pôvodného súboru.
Verzia bez servera rozpozná titulky zo zvuku aj vtedy, keď prehliadač neprehrá obraz, ale
náhľad a export videa vtedy nie sú k dispozícii (ostane SRT a VTT).

## Mazanie po 24 hodinách

Pracovná verzia (nahraté video, titulky, nastavenia, exportované súbory) sa zmaže
24 hodín po poslednom exporte. Ak sa video nikdy neexportuje, zmaže sa 24 hodín po nahratí.
Čistenie beží na pozadí každých 5 minút a pri štarte servera. Používateľ môže video
zmazať aj hneď tlačidlom.

## Súbory

```
server.py     API a mazanie po lehote
asr.py        rozpoznanie reči a zloženie slov do titulkov
render.py     SRT, VTT, ASS a vypálenie titulkov cez ffmpeg
static/       rozhranie (HTML, CSS, JavaScript bez build kroku)
tests/        python3 tests/test_basic.py
```

## Čo zatiaľ nie je

- Predplatné 2 € mesačne: platby a prihlásenie nie sú hotové, aplikácia je bez účtov.
- Inteligentný výrez na tvár pri 9:16 a 1:1: výrez je vždy na stred.
- Úprava časov titulkov (upravuje sa iba text).
