# Zmeny témy RELIFE

Verzia je v hlavičke `style.css` (`Version:`). Podľa nej sa pridáva `?ver=` k CSS a JS, takže po aktualizácii sa nenačíta stará kópia z cache.

## 1.3.3
- Logo v hlavičke je väčšie: predvolená výška 72 px (predtým 56), na mobile 54 px, najväčšia šírka 340 px (mobil 230 px). Výšku zmeníte v Customizeri (24 až 140 px). Ak ste tam výšku už nastavili, ostáva vaša hodnota.

## 1.3.2
- Newsletter: pole na e-mail má biele pozadie, tmavý text a viditeľný kurzor aj pri automatickom vypĺňaní (predtým bol text čierny na tmavom pozadí).

## 1.3.1
- **Oprava hlavnej príčiny:** `the_custom_logo()` vypisuje vlastný odkaz, ktorý sme vkladali do ďalšieho odkazu. Prehliadač vnorené odkazy rozbije a logo vypadlo z kontajnera, preto zaberalo celú šírku. Teraz je logo v `<div>`.
- Logo: kontajner v hlavičke má pevný strop šírky (260 px, na mobile 190 px) a obrázok max. výšku, ktorú nič neprebije. Pomáha, aj keď cache alebo optimalizačný plugin pridá vlastné rozmery.
- Mobil: v hlavnom článku a v bloku Versus sa nezobrazuje ukážkový text (perex), ostáva nadpis, kategória a čas čítania.
- Logo sa načíta v menšej veľkosti (`sizes`) a bez lenivého načítania.

## 1.3.0
- Nahrané logo sa prispôsobí maximálnej výške (predvolene 56 px, na mobile o štvrtinu menej). Nastavenie v Customizeri.
- Oprava: stránka na mobile sa už nedá posúvať do strany pri dlhých slovách v kartách a pri navigácii.

## 1.2.0
- Stredové tlačidlo spodného menu na mobile otvára menu. Ikona menu sa po otvorení zmení na X.
- Hľadanie je na druhej záložke spodného menu.

## 1.1.0
- Rozbaľovacie menu Sekcie v spodnom menu na mobile (piliere, ďalšie sekcie, formáty, Ultimate Guides).
- Filmy a seriály v podtémach sekcie TRENDY.

## 1.0.0
- Prvé vydanie: 7 sekcií, 6 formátov, anketa a staršie ankety, výber nálady, uložené články v prehliadači, šablóna článku, ukážkový obsah.
