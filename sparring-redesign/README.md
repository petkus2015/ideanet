# Sparring: návrh redizajnu (prototyp domovskej stránky)

Klikateľný prototyp novej homepage pre **sparring.io**. Je to čisté HTML, CSS a JS bez knižníc.

```bash
cd sparring-redesign && python3 -m http.server 8000   # http://localhost:8000
```

## Koncept: „Legal, in cinema“

Segment tvoria startupy, scaleupy, investori a agentúry z oblasti fintech, krypto, gaming a AI.
Klient nekupuje „advokátsku kanceláriu“, ale partnera na celú cestu firmy. Preto je stránka
postavená ako **film o ceste firmy od nápadu po exit**, rozdelený do 7 scén. Tón ostáva
dôveryhodný (serif, pokojné tempo), ale pôsobí moderne a technologicky, čo pri právnikoch nie je bežné.

Brand ostáva rozpoznateľný: zachovaná je **navy #142643, coral #FF7075 a teal #00ACAD**.
V prototype sú použité Google fonty Instrument Serif + Inter Tight. Pri nasadení ich možno
vymeniť späť za brandové **Rooney + Komet** (Adobe Fonts) zmenou dvoch premenných v `:root`.

| Scéna | Obsah | Filmový efekt |
|---|---|---|
| Intro | — | Letterbox (čierne pásy sa otvoria) + odpočet 3-2-1, iba pri prvej návšteve v session, ~1,4 s, preskočiteľné klávesou |
| 01 Opening | H1 „Your legal partner from idea to exit“ | Svetelný kužeľ projektora s prachom (canvas, reaguje na myš), anamorfický lens flare, slová H1 sa zaostria z rozmazania (focus pull) |
| 01 Player | Cesta Idea → Setup → Seed → ESOP → Scale → Exit | UI videoprehrávača so scrubberom a kapitolami; každá kapitola hovorí, čo Sparring v danej fáze robí |
| Logos | „Trusted by…“ | Nekonečný pás mien klientov |
| 02 Expertise | 6 odvetví + Innovations | Horizontálny **filmový pás s perforáciou**, pri scrolle sa „pretáča“ (pinned) |
| 03 Why | 3 piliere ako Act I–III | Postupné zaostrenie pri scrolle |
| 04 Reviews | 5 referencií | Citácie ako titulky, prechody s rack focus, priebeh ako pri „stories“, pauza |
| 05 Action | Let’s get started | **Klapka**, ktorá sa zaklapne pri vstupe do obrazu; čipy rovno predvyplnia tému v kontaktnom formulári |
| 06 Insights | Blog | Ken Burns zoom na náhľadoch |
| 07 Finale + Credits | CTA + pätička | Pätička v štýle záverečných titulkov |

Na celej stránke: jemné filmové zrno, vinetácia a **HUD s timecode** (vľavo dole), ktorý
pri scrolle ukazuje aktuálnu scénu a čas, napr. `SC 02 · EXPERTISE 00:00:47:23`.

**Prístupnosť:** pri `prefers-reduced-motion` sa vypne intro, zrno, canvas, pin aj
autoplay. Ovládanie z klávesnice, `<dialog>` pre kontakt, skip link a ovládateľný slider referencií.
**Mobil:** filmový pás má natívny swipe so scroll-snap, HUD je skrytý a menu je burger.

## Zachovanie SEO pozícií

Pozície sa strácajú hlavne pri zmene **URL, obsahu, interného prelinkovania a rýchlosti**.
Prototyp je preto navrhnutý tak, aby sa žiadna z týchto vecí nezhoršila.

### Overené 1:1 so živým webom (skript porovnal zdrojový kód)

- `<title>`, meta description, robots, canonical, OG a Twitter tagy: **identické**
- hreflang (en-GB / en-US / sk / cs) **zachovaný** a doplnený `x-default`
- **H1 identický**: „Your legal partner from idea to exit“
- Všetky H2 z pôvodnej stránky zostali. Odvetvia sú teraz **H3** (predtým H4), čo je lepšia hierarchia.
- **Všetky interné odkazy z pôvodnej homepage sú zachované** (0 chýbajúcich), vrátane autorov a archívov
- Texty sekcií prevzaté takmer doslovne
- Všetok obsah je priamo v HTML. JS iba animuje a nič nevkladá, takže Google vidí všetko aj bez JS.

### Vylepšenia

- Schema.org: pribudol **`LegalService` + `Organization`** (knowsAbout, areaServed, award, sameAs) k pôvodným WebSite/WebPage
- Výrazne ľahší DOM: Elementor má menu v HTML 3× a stovky wrapperov
- Core Web Vitals:
  - LCP je H1 text a je vykreslený hneď, intro ho iba prekrýva
  - žiadny autoplay video ani knižnica
  - canvas sa pauzuje mimo obrazovky
  - animuje sa iba `transform`, `opacity` a `filter`, takže nevzniká CLS

### Problémy, ktoré som našiel na súčasnom webe (oplatí sa opraviť aj bez redizajnu)

1. **`category-sitemap.xml` obsahuje relatívne URL** (`/updates?tags=fintech`). Google ich považuje za neplatné, musia byť absolútne.
2. **WAF (firewall hostingu) blokuje požiadavky bez prehliadačového User-Agenta** (HTTP 466). Treba overiť v Google Search Console (Nastavenia → Štatistiky indexovania), že Googlebot nedostáva chyby. Firewall pravdepodobne blokuje aj Ahrefs a Screaming Frog.
3. Väčšina obrázkov na homepage má **prázdny `alt`** (logá klientov, ilustrácie).
4. Odvetvia boli ako **H4** bez nadradeného H3.
5. Na stránke beží Elementor + Elementor Pro + Essential Addons naraz, čo spomaľuje LCP a INP.

### Postup nasadenia bez straty pozícií

1. **Ponechať WordPress, Rank Math a všetky URL.** Meníme iba frontend.
   - Odporúčanie: vlastná ľahká téma (alebo child téma Hello) s týmito šablónami namiesto Elementor stránok.
   - Alternatíva s menším rizikom: postaviť v Elementore a efekty pridať cez custom CSS/JS.
2. **Pred spustením:**
   - Crawl starého webu (Screaming Frog) a export URL, title, meta, H1, canonical, interných odkazov.
   - Export top stránok a dopytov z GSC za 16 mesiacov.
3. **Staging s `noindex`** a heslom. Crawl stagingu a diff oproti starému webu: nesmú chýbať URL, titles ani H1 a nesmú pribudnúť 404.
4. Ak sa niektorá URL predsa mení: **301 presmerovanie 1:1** (Rank Math → Redirections), nikdy nie hromadne na homepage.
5. Spustenie:
   - odstrániť `noindex` zo stagingu, aby sa nedostal do produkcie (najčastejšia chyba)
   - odoslať sitemapy v GSC
   - Lighthouse / PageSpeed (cieľ LCP < 2,5 s, CLS < 0,1, INP < 200 ms)
6. **Monitoring 4–8 týždňov:** GSC (pokrytie, pozície top dopytov), 404 log, Core Web Vitals report.
   Mierne výkyvy prvé 2 týždne sú normálne.
7. Rovnaký dizajn postupne preniesť na podstránky odvetví (`/fintech/`, `/crypto/` …), `/about-us/`, `/pricing/` a blog.

## Čo je v prototype zástupné

- **Mená klientov v páse sú text, nie logá.** Pri nasadení použiť SVG logá z `/wp-content/uploads/` s vyplneným `alt`.
- **Texty kapitol v „playeri“ (Idea → Exit) sú môj návrh copy** a štítky pri odvetviach (MiCA, AI Act…) sú tiež doplnené. Treba ich schváliť so Sparringom.
- **Firmy pri referenciách** (Vision Ventures, Deepnote, LB, Neulogy, GymBeam) sú odvodené z log pri citátoch na pôvodnom webe. Treba ich overiť.
- Náhľady článkov sú gradienty. Nahradiť featured images z WordPressu.
- Formuláre nič neodosielajú. Napojiť na pôvodný formulár (Elementor Forms / reCAPTCHA).
