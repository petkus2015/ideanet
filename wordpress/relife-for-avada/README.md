# RELIFE pre Avadu (plugin)

Magazínové prvky RELIFE ako plugin pre tému **Avada**. Téma sa nemení. Plugin pridáva obsahový model, komponenty ako shortcody (aj ako elementy Avada Builderu), mobilné spodné menu a doplnky v článku.

- **WordPress:** 6.2 alebo novší, **PHP:** 8.1 – 8.4
- Komponenty sú tmavé bloky (`.rl-scope`), ktoré nemenia štýly Avady okolo nich.

## Inštalácia

1. Pluginy → Pridať nový → Nahrať plugin → `relife-for-avada.zip` → Aktivovať.
2. Nastavenia → RELIFE pre Avadu → *Importovať ukážkový obsah* (vytvorí sekcie, formáty, články, ankety a stránky „Staršie ankety“ a „Uložené“; nemení nastavenia webu ani menu).
3. V Avada Builderi vložte element **Code Block** alebo **Shortcode** a napíšte shortcode. Ak je Avada Builder aktívny, objavia sa aj elementy „RELIFE – …“.

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
- V článkoch pridá odpoveď v skratke, čas čítania, uloženie, zdroje a označenie spolupráce (vypnuteľné).
- Hlas v ankete, nálada a uložené články sa ukladajú len v prehliadači čitateľa (localStorage a Cache API).
- Plugin sa nenačíta, ak je aktívna samostatná téma RELIFE (obe používajú rovnaké typy obsahu).

## Obmedzenia

- Integrácia s Avada Builderom (elementy cez `fusion_builder_map`) je napísaná podľa dokumentácie, ale **neskúšaná proti skutočnej Avade**. Shortcody fungujú nezávisle.
- „Tmavý režim Avady“ je jednoduché prekrytie bežných prvkov. Správne farby nastavíte v Avada → Options → Colors.
- Počty hlasov v ankete zadáva admin ručne. Hlas čitateľa nejde na server.
