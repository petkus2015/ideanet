# RELIFE pre Avadu (plugin)

Magazínové prvky RELIFE ako plugin pre tému **Avada**. Téma sa nemení. Plugin pridáva obsahový model, komponenty ako shortcody (aj ako elementy Avada Builderu), mobilné spodné menu a doplnky v článku.

- **WordPress:** 6.2 alebo novší, **PHP:** 8.1 – 8.4
- Komponenty sú tmavé bloky (`.rl-scope`), ktoré nemenia štýly Avady okolo nich.

## Inštalácia

1. Pluginy → Pridať nový → Nahrať plugin → `relife-for-avada.zip` → Aktivovať.
2. Nastavenia → RELIFE pre Avadu → *Importovať ukážkový obsah*. Vytvorí sekcie, formáty, články, ankety a **tri hotové stránky v Avada Builderi**:
   - **Domov** (hero s anketou, Quick Take, Dnes podľa nálady, Vysvetlené jednoducho, piliere, Versus, sekcie, formáty, Ultimate Guides, newsletter), voliteľne ju nastaví ako úvodnú stránku,
   - **Ankety** (aktívna anketa vedľa starších ankiet s výsledkami),
   - **Uložené**.
   Stránky sa otvoria v Avada Builderi, každá sekcia je samostatný kontajner so Text Blockom a shortcodom. Import nemení menu ani iné nastavenia webu.
3. Články sa zobrazujú v dizajne RELIFE automaticky (šablóna článku, vypnuteľná v nastaveniach).
4. Vlastné stránky: v Avada Builderi vložte element **Text Block**, **Code Block** alebo **Shortcode** a napíšte shortcode. Ak je Avada Builder aktívny, objavia sa aj elementy „RELIFE – …“.

## Shortcody

| Shortcode | Čo zobrazí |
|---|---|
| `[relife_hero]` | Hlavný článok (pripnutý, inak najnovší) + anketa/nálada vedľa neho |
| `[relife_sidecard]` | Len karta Anketa / Nálada |
| `[relife_quick count="4"]` | Quick Take |
| `[relife_today count="4"]` | „Čo dnes stojí za tvoju pozornosť?“ (filtruje podľa nálady) |
| `[relife_explained count="2"]` | Vysvetlené jednoducho |
| `[relife_pillars]` | TECH, HEALTH, MONEY |
| `[relife_versus]` | Najnovší Versus |
| `[relife_sections]` | Dlaždice sekcií |
| `[relife_formats]` | Šesť formátov |
| `[relife_guides count="4"]` | Ultimate Guides |
| `[relife_polls]` | Staršie ankety s filtrom |
| `[relife_saved]` | Uložené články čitateľa |
| `[relife_newsletter]` | Formulár newslettra |

## Čo plugin robí inak než téma RELIFE

- Spodné menu na mobile (Domov, Hľadať, **Menu**, Uložené, Weekly) a rozbaľovacie menu sekcií sa pridá cez `wp_footer`. Vypnete ho v nastaveniach.
- **Šablóna článku RELIFE**: tagy sekcie a formátu, nadpis, anotácia, autor, uloženie a zdieľanie, obsah z nadpisov, odpoveď v skratke, zdroje, označenie spolupráce, autor a ďalšie články. Hlavička a pätička ostávajú z Avady. Pri vypnutej šablóne sa doplnky pridajú do štandardnej šablóny Avady.
- Hlas v ankete, nálada a uložené články sa ukladajú len v prehliadači čitateľa (localStorage a Cache API).
- Plugin sa nenačíta, ak je aktívna samostatná téma RELIFE (obe používajú rovnaké typy obsahu).

## Obmedzenia

- Hotové stránky v Builderi používajú štandardné elementy Avady (`fusion_builder_container`, `fusion_builder_row`, `fusion_builder_column`, `fusion_text`). **Testované len s ich náhradou na predvolenej téme**, nie s Avadou. Ak sa stránka v Builderi zobrazí inak, otvorte ju a obnovte uložením.
- Skrytie titulku stránky a šablóna 100 % šírky sa nastavujú podľa Avady (kľúče sa medzi verziami líšia), ak ich nepozná, nastavte ich ručne.

- Integrácia s Avada Builderom (elementy cez `fusion_builder_map`) je napísaná podľa dokumentácie, ale **neskúšaná proti skutočnej Avade**. Shortcody fungujú nezávisle.
- „Tmavý režim Avady“ je jednoduché prekrytie bežných prvkov. Správne farby nastavíte v Avada → Options → Colors.
- Počty hlasov v ankete zadáva admin ručne. Hlas čitateľa nejde na server.
