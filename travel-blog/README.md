# Adventuro — cestovateľský blog (mobile-first návrh)

Statický web bez závislostí. Spustenie: `python3 -m http.server 8000` v tomto priečinku → http://localhost:8000

- **Dizajn**: hero s pozdravom a vyhľadávaním, kategórie (chips), karusel obľúbených miest, veľká príbehová karta, bento dlaždice, plávajúci dock dole.
- **Režim**: Auto (tma 19:00–07:00) → Svetlý → Tmavý; tlačidlo vpravo hore, voľba sa pamätá.
- **Uložené články**: záložka pri článku → ID v `localStorage`, obsah v Cache API (`adventuro-saved-v1`); čítanie aj offline (`sw.js` cacheuje shell).
- **Obrázky**: zatiaľ SVG ilustrácie (`scene()` v `app.js`); reálne fotky pridajte ako `<img>` do `.card__art`.
- **Články**: pole `ARTICLES` v `app.js`.
