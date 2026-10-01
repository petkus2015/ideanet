# Mediálna gramotnosť — návrh štruktúry a UX/UI

Koncept redizajnu vzdelávacieho webu o mediálnej gramotnosti
(kritické hodnotenie informácií, overovanie zdrojov, porozumenie mediálnemu obsahu).
Ukážka domovskej stránky je v [`index.html`](index.html). Je to návrh, nie oficiálny web.

## 1. Ciele a cieľové skupiny

| Skupina | Čo potrebuje | Hlavná cesta na webe |
|---|---|---|
| Žiaci ZŠ a SŠ (12–19) | krátke interaktívne lekcie, kvízy, príklady zo sociálnych sietí | Témy → Lekcia → Kvíz |
| Učitelia | hotové hodiny, pracovné listy, prepojenie na ŠVP | Pre učiteľov → Materiály podľa ročníka |
| Rodičia | ako sa rozprávať s deťmi o sieťach, algoritmoch a súkromí | Sprievodca pre rodičov |
| Seniori | jednoduché návody, podvody, falošné reklamy, hoaxy v reťazových správach | Bezpečne online (väčšie písmo, krátke kroky) |
| Široká verejnosť | rýchlo si overiť konkrétnu správu | Overovač + Rozbory hoaxov |

**Hlavná úloha webu:** naučiť návyk „zastav sa a over si to“ a dať na to praktické nástroje.

## 2. Informačná architektúra (sitemap)

```
Domov
├── Témy
│   ├── Dezinformácie a hoaxy
│   ├── Overovanie zdrojov
│   ├── Manipulačné techniky (emócie, clickbait, falošná autorita)
│   ├── Umelá inteligencia a deepfakes
│   ├── Algoritmy a sociálne siete (bubliny, odporúčania)
│   ├── Reklama, natívna reklama a influenceri
│   ├── Súkromie a digitálna stopa
│   └── Nenávistné prejavy a kyberšikana
├── Overovač
│   ├── Interaktívny postup „4 kroky overenia“
│   ├── Spätné vyhľadávanie obrázkov (návod)
│   ├── Kontrola webu a domény
│   └── Kontrolný zoznam na stiahnutie (PDF)
├── Rozbory (blog s rozbormi aktuálnych tvrdení)
│   └── Detail rozboru: tvrdenie → overenie → verdikt → zdroje
├── Pre učiteľov
│   ├── Plány hodín (filtrovanie: ročník, predmet, dĺžka, téma)
│   ├── Pracovné listy a prezentácie
│   ├── Prepojenie na ŠVP
│   └── Školenia a webináre
├── Pre rodičov
├── Pre seniorov (zjednodušený režim)
├── Kvízy a výzvy
├── Slovník pojmov (A–Z)
├── O projekte · Partneri · Kontakt
└── Nahlásiť podozrivý obsah (formulár)
```

## 3. Domovská stránka (poradie sekcií)

1. **Hlavička:** logo, navigácia (Témy, Overovač, Rozbory, Pre učiteľov, Slovník), vyhľadávanie, tlačidlo *Materiály pre učiteľov*.
2. **Úvodná sekcia s interaktívnym príkladom:** falošný príspevok zo sociálnej siete. Používateľ klikaním hľadá varovné signály a potom zvolí verdikt. Pointu webu tak zažije hneď.
3. **Rozcestník podľa publika:** Žiaci · Učitelia · Rodičia · Seniori.
4. **Metóda 4 krokov:** Zastav sa → Over zdroj → Hľadaj inde → Skontroluj kontext.
5. **Témy:** karty s úrovňou, dĺžkou a typom obsahu (lekcia, video, kvíz).
6. **Rozbor týždňa:** jedno tvrdenie s pečiatkou verdiktu (Pravdivé / Zavádzajúce / Nepravdivé / Neoveriteľné).
7. **Rýchly kvíz:** 3 otázky s okamžitou spätnou väzbou.
8. **Pre učiteľov:** najnovšie materiály na stiahnutie s formátom a ročníkom.
9. **Slovník:** výber pojmov v rozbaľovacom zozname.
10. **Newsletter + pätička:** kontakt, partneri, prístupnosť, nahlásenie obsahu.

## 4. UX princípy

- **Učenie skúsenosťou.** Každá téma začína reálnym (ilustračným) príkladom, až potom nasleduje teória.
- **Jednotný jazyk verdiktov.** Štyri farebne a textovo odlíšené pečiatky. Farba nikdy nie je jediný nosič významu.
- **Krátke kroky.** Lekcie majú 5–10 minút, ukazovateľ postupu a zhrnutie „Čo si zapamätať“.
- **Filtre pre učiteľov.** Ročník, predmet, dĺžka hodiny a téma. Každý materiál ukazuje formát a veľkosť súboru.
- **Prístupnosť (WCAG 2.2 AA).** Kontrast, ovládanie klávesnicou, viditeľný fokus, `prefers-reduced-motion`, svetlý aj tmavý režim, režim väčšieho písma pre seniorov.
- **Dôvera.** Pri každom rozbore sú zdroje, dátum overenia, autor a postup opravy chýb.
- **Mobile-first.** Väčšina žiakov príde z mobilu: jednostĺpcové rozloženie, veľké dotykové plochy.

## 5. Vizuálny systém (v ukážke)

- **Farby:** atramentová modrá (text), kobaltová (akcia), žltá ako zvýrazňovač (označenie podozrivých častí textu), sémantické farby verdiktov.
- **Písmo:** Bricolage Grotesque (nadpisy), Literata (čítanie), JetBrains Mono (zdroje, URL, metadáta). Všetky podporujú slovenskú diakritiku.
- **Motív:** zvýrazňovač a pečiatka verdiktu, nástroje overovateľa faktov.

## 6. Technické odporúčania

- Statický generátor (Astro / Eleventy) + headless CMS (napr. Strapi, Directus) na správu tém, rozborov a materiálov.
- Typy obsahu: Téma, Lekcia, Rozbor (tvrdenie, verdikt, zdroje), Materiál (súbor, ročník, predmet), Pojem.
- Vyhľadávanie: Pagefind (statický index) alebo Meilisearch.
- Štruktúrované dáta `ClaimReview` (schema.org) pri rozboroch, aby ich vyhľadávače zobrazovali ako overenie faktov.
- Analytika bez cookies (Plausible / Matomo), aby web sám dodržiaval to, čo učí o súkromí.
