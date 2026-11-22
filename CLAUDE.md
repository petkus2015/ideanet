# CLAUDE.md — pravidlá pre projekty v tomto repe

Repo obsahuje viac samostatných webov: `/` (IDEANET portfólio), `bratislava/`, `vlaky/`
a WordPress plugin `wordpress/meskanie-vlakov/`. Všetky weby sú **čisté HTML + CSS + JS,
bez build kroku a bez npm závislostí**. Tento princíp drž — nepridávaj React, Tailwind,
bundler ani `package.json`, pokiaľ to používateľ výslovne nežiada.

Texty na weboch píš po slovensky.

## Tvorba a úpravy webov

Pri každej práci na vzhľade, nových stránkach alebo animáciách použi skill
**`moderny-web`** (`.claude/skills/moderny-web/SKILL.md`): dizajnový systém → animácie
cez Motion → prepis komponentov z 21st.dev → kontrola pred odovzdaním.

## Konkrétne v tomto repe

- **IDEANET (`/`)** — animácie sú v `motion-fx.js` (knižnica Motion 14.10.1 z jsDelivr
  s `integrity`). Ak sa nenačíta, `app.js` použije záložné objavovanie cez
  IntersectionObserver. Nové animované prvky označ triedou `.reveal`; prvky v
  `.cards`, `.steps`, `.quotes`, `.plans` naskakujú postupne ako skupina.
- Farby a rozmery sú CSS premenné v `:root` na začiatku `styles.css`
  (`--magenta`, `--violet`, `--text`, `--muted`…) — používaj ich.
- Spustenie: `python3 -m http.server 8000` v koreni repa.
