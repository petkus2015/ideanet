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
| `TITULKY_MAX_UPLOAD_MB` | `500` | najväčšie nahrávané video |
| `TITULKY_DATA` | `titulky/data` | kde sa ukladajú pracovné verzie |
| `TITULKY_ASR` | | `mock` = ukážkové titulky bez modelu (len na skúšanie) |
| `HOST`, `PORT` | `127.0.0.1`, `8000` | adresa servera |

## Verzia bez servera

`titulky-browser.html` je jeden samostatný súbor: otvor ho dvojklikom v Chrome alebo Edge.
Rozpoznanie reči (Whisper cez transformers.js) aj export (nahranie z plátna) bežia v prehliadači,
nič sa neposiela na server. Pri prvom použití potrebuje internet na stiahnutie modelu
(40 až 250 MB podľa výberu) a písem. Export beží v reálnom čase a vyrobí WebM alebo MP4
podľa prehliadača. V náhľade sa dajú titulky presunúť myšou a zapnúť sa dá zvýrazňovanie práve vyslovených slov (časy slov dá model Whisper, ak ich nevie, odhadnú sa z dĺžky slov).
Vznikne z `static/` príkazom `python3 build_browser.py`.

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
