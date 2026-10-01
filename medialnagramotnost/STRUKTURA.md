# Mediálna gramotnosť — vzdelávací web

Statický web (HTML, CSS, JavaScript bez závislostí) o podvodoch, dezinformáciách a overovaní
informácií. Je určený mladým aj seniorom. Obsah vychádza z overených zdrojov EÚ (ENISA, Europol,
Európska komisia, Eurobarometer, Akt o AI, DSA, EDMO) a slovenských inštitúcií (SK-CERT, NBS,
Polícia SR). Zoznam zdrojov je v `zdroje.html`.

## Spustenie

```bash
cd medialnagramotnost
python3 -m http.server 8000
# otvorte http://localhost:8000
```

Nasadenie: obsah priečinka nahrajte na akýkoľvek statický hosting.

## Stránky

| Súbor | Obsah | Interaktívne prvky |
|---|---|---|
| `index.html` | Domov: rozcestník podľa situácie, 3 pravidlá, aktuálne podvody, čísla z EÚ | ukážka SMS so zvýraznením varovných znakov |
| `podvody.html` | Atlas 12 podvodov (SMS, e-mail, telefonát, Facebook, WhatsApp, bazár, e-shop, romance, peňažné muly, vydieranie, tech support) | filter podľa kanála a cieľovej skupiny, varovné znaky v každej ukážke |
| `trenazer.html` | Trenažér: 12 správ, podvod alebo v poriadku (3 sú v poriadku) | priebežné hodnotenie a výsledok |
| `overit.html` | Kontrola podozrivej správy, kontrola webovej adresy, 4 kroky overenia, nástroje | dotazník s ukazovateľom rizika, rozbor URL (trik so zavináčom, subdomény, IP) |
| `dezinformacie.html` | Definícia EK, 6 manipulačných techník, rozbor príspevku, deepfakes a Akt o AI, algoritmy a DSA | kvíz manipulačných techník |
| `seniori.html` | 6 zlatých pravidiel, čo povedať pri telefonátoch, rodinné heslo | — |
| `mladi.html` | Vydieranie, falošné brigády, hry, influenceri, AI | — |
| `pomoc.html` | „Stalo sa mi to“: postup podľa situácie a kontakty | rozhodovací strom so 6 situáciami |
| `slovnik.html` | 24 pojmov | vyhľadávanie |
| `zdroje.html` | Zdroje a metodika | — |
| `koncept-v1.html` | Prvý koncept domovskej stránky (archív) | — |

Hlavička a pätička sa vkladajú z `assets/site.js`, takže sa navigácia mení na jednom mieste.

## UX princípy

- **Začína sa situáciou, nie teóriou.** Domov sa pýta „S čím vám pomôžeme?“ (prišla mi správa / chcem sa učiť / nerozumiem, čomu veriť / už sa mi to stalo).
- **Učenie na ukážkach.** Každý podvod má vernú maketu (SMS, e-mail, Facebook, WhatsApp, web, prepis hovoru) a tlačidlo *Ukázať varovné znaky*. Znaky sú očíslované v ukážke aj v zozname.
- **Pomoc je stále po ruke.** Horný pruh na každej stránke odkazuje na *Stalo sa mi to*; táto položka je v menu červená.
- **Seniori:** tlačidlo **A+** zväčšuje písmo v troch stupňoch (zapamätá sa), písmo Atkinson Hyperlegible je navrhnuté pre slabozrakých, tlačidlá majú aspoň 44–48 px, texty sú krátke a bez cudzích slov.
- **Prístupnosť:** svetlý a tmavý režim, ovládanie klávesnicou, viditeľný fokus, `prefers-reduced-motion`, odkaz „Preskočiť na obsah“.
- **Dôvera:** pri každom podvode a čísle je uvedený zdroj; ukážky majú vymyslené alebo skrátené čísla a adresy.

## Údržba obsahu

- Nový podvod: skopírujte jeden `<article class="scam-item">` v `podvody.html`. Varovné znaky označte `<span class="flag">` v poradí, v akom idú položky `<li>` v `.flag-list`. Číslovanie doplní skript.
- Nová ukážka do trenažéra: pridajte objekt do poľa `T` v `assets/site.js` (makety `M.sms`, `M.wa`, `M.mail`, `M.fb`, `M.web`).
- Varovania SK-CERT, NBS a Polície SR odporúčame kontrolovať aspoň raz mesačne a dopĺňať ich do sekcie *Aktuálne časté podvody* na domovskej stránke.
