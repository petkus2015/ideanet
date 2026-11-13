# Adventuro — cestovateľský blog (mobile-first návrh)

Statický web bez závislostí. Spustenie: `python3 -m http.server 8000` v tomto priečinku → http://localhost:8000

- **Dizajn**: hero s pozdravom a vyhľadávaním, kategórie (chips), karusel obľúbených miest, veľká príbehová karta, bento dlaždice, plávajúci dock dole.
- **Režim**: Auto (tma 19:00–07:00) → Svetlý → Tmavý; tlačidlo vpravo hore, voľba sa pamätá.
- **Uložené články**: záložka pri článku → ID v `localStorage`, obsah v Cache API (`adventuro-saved-v1`); čítanie aj offline (`sw.js` cacheuje shell).
- **Obrázky**: zatiaľ SVG ilustrácie (`scene()` v `app.js`); reálne fotky pridajte ako `<img>` do `.card__art`.
- **Články**: pole `ARTICLES` v `app.js`.

## Ako aplikácia
Web je PWA: `manifest.webmanifest` + ikony, režim `standalone` (bez adresného riadku), úzky stĺpec šírky mobilu aj na desktope, offline cez `sw.js`.
Nainštalovať ide po nasadení na HTTPS (Chrome/Android: tlačidlo Inštalovať v sekcii O blogu, iPhone: Zdieľať → Pridať na plochu).

## Responzivita
- ≤359 px: krytové displeje foldov a iPhone mini (menšie okraje, dock bez popisov)
- iPhone na šírku: nižší hero, bezpečné okraje výrezu
- ≥600 px: rozložený fold/tablet, stĺpec 820 px, karty v dvoch stĺpcoch
- Dvojobrazovkové zariadenia (`horizontal-viewport-segments:2`, Surface Duo): obsah sa delí v ohybe; text vľavo, ilustrácia vpravo, karty sa nikdy nerozkladajú cez ohyb

## Fotky
Karty, úvodný obrázok a články zobrazia `img/<id>.jpg`, ak existuje (inak ilustráciu). Fotky z Wikimedia Commons stiahneš na vlastnom počítači: `python3 scripts/fetch_photos.py`, autorov skopíruj z `img/credits.json` do `CREDITS` v `app.js`.
