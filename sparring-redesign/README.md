# Sparring: návrh redizajnu (prototyp domovskej stránky)

Klikateľný prototyp novej homepage pre **sparring.io**. Je to čisté HTML, CSS a JS bez knižníc.

```bash
cd sparring-redesign && python3 -m http.server 8000   # http://localhost:8000
```

## Koncept

Stránka stojí na pôvodnom obsahu a značke Sparring: navy #142643, coral #FF7075, teal #00ACAD,
pôvodná ilustrácia mapy, logá klientov a obrázky článkov. Texty sú prevzaté z webu bez úprav.
Písmo je IBM Plex Serif + Sans. Pri nasadení ho možno nahradiť brandovým Rooney + Komet (Adobe Fonts)
zmenou dvoch premenných v `:root`.

Vizuál je zámerne triezvy, bez prvkov typických pre AI generované šablóny. Nie sú tu:
- žiariace gradienty ani sklenené (blur) panely
- monospace popisky ani číslovanie scén
- pulzujúce bodky ani vymyslené widgety
- farebná kurzíva v nadpisoch

**Filmové prvky** (všetky sa vypnú pri `prefers-reduced-motion`):

| Kde | Efekt |
|---|---|
| Načítanie | Čierne pásy sa otvoria (raz za session, < 1,2 s) |
| Hero | Širokouhlý záber: čierne pásy hore a dole sa pri scrolle stiahnu, mapa má jemný parallax |
| Celá stránka | Jemné filmové zrno |
| Expertise | Odvetvia na filmovom páse s perforáciou, pri scrolle sa posúva do strany (na mobile swipe) |
| Referencie | Citácie ako titulky na plátne 2.39:1 |
| Pätička | Odkazy usporiadané ako záverečné titulky |

## Zachovanie SEO pozícií

Pozície sa strácajú hlavne pri zmene **URL, obsahu, interného prelinkovania a rýchlosti**.
Prototyp je preto navrhnutý tak, aby sa žiadna z týchto vecí nezhoršila.

### Overené 1:1 so živým webom (skript porovnal zdrojový kód)

- `<title>`, meta description, robots, canonical, OG a Twitter tagy: **identické**
- hreflang (en-GB / en-US / sk / cs) **zachovaný** a doplnený `x-default`
- **H1 identický**: „Your legal partner from idea to exit“
- Všetky H2 z pôvodnej stránky zostali, bez nových. Odvetvia sú teraz **H3** (predtým H4).
- **Všetky interné odkazy z pôvodnej homepage sú zachované** (0 chýbajúcich), vrátane autorov a archívov
- Texty sekcií prevzaté doslovne z pôvodného webu
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

## Čo treba pri nasadení doriešiť

- Obrázky a logá sú načítané priamo z `sparring.io/wp-content/uploads/`. Vo WordPresse sa použijú tie isté súbory z knižnice médií.
- Formuláre nič neodosielajú. Treba ich napojiť na pôvodný formulár (Elementor Forms / reCAPTCHA).
