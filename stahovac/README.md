# Sťahovač videí

Webová aplikácia, ktorá vyzerá a správa sa ako mobilná (dá sa pridať na plochu ako PWA).
Vložíte odkaz z **TikToku, Instagramu, YouTube alebo Facebooku**, aplikácia zistí, či je za
odkazom video, ukáže náhľad a ponúkne ho na stiahnutie v **pôvodnej (originálnej) kvalite**.

- **Originál** – najvyššie dostupné rozlíšenie, obraz ani zvuk sa neprekódujú
  (len sa spoja / prebalia do MP4 cez `ffmpeg -c copy`).
- Nižšie rozlíšenia, **MP4 (H.264)** pre staršie telefóny (ak je originál VP9/AV1), **iba zvuk**.
- Priebeh sťahovania, zrušenie, história posledných odkazov, tlačidlo „Vložiť“ zo schránky.
- Na Androide sa po inštalácii zobrazí v ponuke **Zdieľať** – odkaz sa otvorí rovno v aplikácii.

Na desktope sa aplikácia zobrazí v ráme telefónu, na mobile cez celú obrazovku.

## Prečo je potrebný server

Prehliadač nevie video z týchto platforiem vytiahnuť sám (CORS, podpisy odkazov, oddelené
stopy obrazu a zvuku). Preto beží malý Python backend s [yt-dlp](https://github.com/yt-dlp/yt-dlp)
a `ffmpeg`. Statický hosting (GitHub Pages) **nestačí**.

## Spustenie lokálne

```bash
cd stahovac
pip install -r requirements.txt     # + nainštalovaný ffmpeg (apt install ffmpeg / brew install ffmpeg)
python app.py                       # http://localhost:8080
```

Z telefónu v rovnakej Wi-Fi: `http://<IP-počítača>:8080`.

## Nasadenie (Docker)

```bash
docker build -t stahovac stahovac
docker run -p 8080:8080 stahovac
```

Funguje na Render, Railway, Fly.io, VPS… Pre PWA (inštalácia na plochu) treba HTTPS.
Spúšťajte s **jedným workerom** (`gunicorn -w 1 --threads 8`) – úlohy sa držia v pamäti.

**yt-dlp pravidelne aktualizujte** (`pip install -U yt-dlp`) – platformy často menia svoje
stránky a staršia verzia prestane fungovať.

## Nastavenia (premenné prostredia)

| Premenná | Predvolene | Význam |
|---|---|---|
| `PORT` | 8080 | port servera |
| `STAHOVAC_MAX_ACTIVE` | 3 | max. súbežných sťahovaní |
| `STAHOVAC_MAX_MB` | 2048 | max. veľkosť súboru v MB |
| `STAHOVAC_JOB_TTL` | 1800 | po koľkých sekundách sa stiahnutý súbor zo servera zmaže |
| `STAHOVAC_TMP` | systémový temp | priečinok na dočasné súbory |
| `STAHOVAC_EXTRA_HOSTS` | – | ďalšie povolené domény (napr. na testovanie) |

## API

| | |
|---|---|
| `POST /api/info` `{url}` | údaje o videu a ponúkané kvality |
| `POST /api/download` `{url, spec, audio}` | spustí sťahovanie, vráti `{id}` |
| `GET /api/jobs/<id>` | stav a priebeh |
| `GET /api/jobs/<id>/file` | hotový súbor |
| `DELETE /api/jobs/<id>` | zrušenie |

## Obmedzenia

- Súkromné videá a obsah vyžadujúci prihlásenie sa stiahnuť nedajú.
- Instagram a Facebook niekedy blokujú požiadavky z dátových centier – vtedy pomôže spustenie
  na domácom počítači alebo aktualizácia yt-dlp.
- Sťahujte len obsah, ku ktorému máte práva, alebo na osobné použitie v súlade s podmienkami platformy.
