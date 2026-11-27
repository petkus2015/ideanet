# Reklamné kreatívy — Black Friday redizajn

Tri statické reklamy pre Meta Ads (Facebook + Instagram), každá v dvoch
formátoch: **1080 × 1080** do feedu a **1080 × 1350** na mobil. Hotové
obrázky sú v `out/`.

| Súbor | O čom je |
| --- | --- |
| `a-cena` | Cena 600 € namiesto 890 €, štyri voľné miesta |
| `b-predpo` | Ten istý web pred a po, vedľa seba v telefónoch |
| `c-zoznam` | Čo je v cene — štyri body |

## Zmena dátumu akcie

Dátum nie je napísaný v HTML, je to zástupný text `{{TERMIN}}`.
Všetkých šesť obrázkov sa prekreslí jedným príkazom:

```bash
node render.js 'do 15. 12.'
```

Bez parametra sa použije `do 30. 11.`

## Zmena textov

Texty sú priamo v `a-cena.html`, `b-predpo.html` a `c-zoznam.html`,
spoločný vzhľad je v `spolocne.css`. Po úprave znovu spustite
`node render.js`.

## Obrázky v ukážke pred/po

`stary-1.jpg` a `novy-1.jpg` sú **vymyslené ukážky**, nie skutočný web
klienta. `stary-1.jpg` sa prekresľuje zo `stary-web.html` pri každom
spustení `render.js`. Ak máte súhlas skutočného klienta, nahraďte oba
súbory jeho screenshotmi (pomer 9:16, napríklad 1080 × 1920).
