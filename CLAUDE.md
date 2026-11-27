# Pokyny pre Claude

## Tvorba webov – vždy Apple dizajn

Pri každej práci na weboch (nové stránky, úpravy UI, štýly, komponenty) postupuj podľa Apple Human Interface Guidelines:

1. Ak je zapnutý plugin **HIG Compliance Auditor** (alebo iný Apple/iOS dizajn plugin), načítaj jeho skill skôr, ako začneš písať kód, a na konci ním skontroluj výsledok (cieľ: Web/PWA).
2. Ak plugin zapnutý nie je, drž sa týchto zásad priamo:
   - **Typografia:** systémový font stack (`-apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif`), jasná hierarchia, veľkorysý riadkový odstup.
   - **Priestor:** veľa bieleho miesta, mriežka v násobkoch 8 px, obsah v centre pozornosti.
   - **Farby:** striedma paleta, jedna akcentová farba, podpora svetlého aj tmavého režimu (`prefers-color-scheme`).
   - **Prvky:** zaoblené rohy, jemné tiene, materiály s rozmazaním (`backdrop-filter`), dotykové ciele aspoň 44×44 px.
   - **Pohyb:** jemné, krátke animácie s plynulým easingom; rešpektuj `prefers-reduced-motion`.
   - **Prístupnosť:** kontrast podľa WCAG AA, sémantické HTML, ovládanie klávesnicou, viditeľný focus.
   - **Responzivita:** mobile-first, bez horizontálneho scrollu na telefóne.

## Ďalšie dizajnové skilly – podľa uváženia

Ak sú zapnuté, používaj ich tam, kde dávajú zmysel (Apple dizajn má pri vizuálnom štýle vždy prednosť):

- **frontend-design** – pri tvorbe nových stránok a komponentov.
- **Design** (`design:*`) – `design-critique` na kontrolu hotového návrhu, `accessibility-review` pred odovzdaním, `ux-copy` na texty tlačidiel a chýb, `design-system` pri väčších projektoch.
- **VectorLab UI/UX Skills** – detaily: typografia, rozostupy, farby, animácie, formuláre.
- **A11y Enforcer** – záverečná kontrola prístupnosti (WCAG 2.2).
- **UI/UX Behaves** – stavy obrazoviek: chyby, prázdne stavy, dialógy, formuláre.

Pri malej úprave stačí jeden relevantný skill, pri novom webe alebo redizajne použi tvorbu + kontrolu (napr. frontend-design → design-critique → A11y Enforcer).
