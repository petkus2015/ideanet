# Bloky nadácie pre Avada Builder

WordPress plugin, ktorý pridá do Avada Library dvanásť hotových sekcií webu
Nadácie Anjelské krídla.

## Inštalácia

1. Nástenka → **Pluginy → Pridať nový → Nahrať plugin**, vyberte
   `nadacia-anjelske-kridla-bloky.zip` a nainštalujte.
2. Plugin **aktivujte**.
3. V ľavom menu otvorte **Bloky nadácie** a kliknite na
   **Importovať bloky do Avada Library**.

Plugin potrebuje tému **Avada** a aktívny **Avada Builder (Fusion Builder)**.
Bez nich sa dá plugin aktivovať, ale import knižnice nespraví nič — na stránke
pluginu zostanú bloky aspoň na skopírovanie.

## Použitie

1. Stránky → Pridať novú, zapnite Avada Builder.
2. Kliknite na ikonu **Library** v hornej lište buildera, záložka **Containers**.
3. Vyberte sekciu (majú názvy „Nadácia — 01 Hero" až „Nadácia — 12 Kontakt").
4. Vložená sekcia je bežný obsah buildera — texty, farby aj obrázky meníte klikaním.

Odporúčané poradie: 01 → 12. Kotvy v kontajneroch (`uvod`, `o-nas`, `ako-pomahame`,
`ziadost-o-pomoc`, `projekty`, `clanky`, `galeria`, `dve-percenta`, `podpora`,
`kontakt`) fungujú ako ciele pre položky menu, napríklad `#o-nas`.

## Čo doplniť

| Kde | Čo |
|---|---|
| všetky bloky s fotkou | ilustračné obrázky nahraďte fotografiami nadácie |
| blok 05 a 09 | odkazy na PDF tlačivá — nahrajte ich do knižnice médií a opravte odkazy tlačidiel |
| blok 12 | kontaktný formulár vytvorte v Avada → Forms a vložte do pravého stĺpca |
| blok 12 | odkazy na Facebook, Instagram a YouTube |
| blok 11 | ohlasy sú ilustračné — nahraďte ich skutočnými so súhlasom rodín |

## Farby a písma

Bloky používajú tyrkysovú `#28afc3` a doplnkovú červenú `#f9193e` priamo v
nastaveniach elementov, takže fungujú aj bez zásahu do témy. Aby s nimi ladil
zvyšok webu, v **Avada → Options → Colors** odporúčame nastaviť:

| Nastavenie | Hodnota |
|---|---|
| Primary Color | `#28afc3` |
| Text Color | `#2a4750` |
| Headings Color | `#0a1f26` |
| Link Color | `#14707f` |

Písma: nadpisy **Fraunces**, text **Inter** (Avada → Options → Typography).

## Čo plugin obsahuje

```
nadacia-bloky.php            plugin, admin stránka, import do knižnice
bloky/*.txt                  shortcody dvanástich sekcií
assets/css/nadacia-bloky.css štýly nadpisíkov, krokov, údajov a čísla účtu
assets/img/                  ilustračné obrázky a logo
readme.txt                   popis pre WordPress
uninstall.php                upratanie po odinštalovaní (obsah nemaže)
```

## Odinštalovanie

Odstránenie pluginu nezmaže sekcie, ktoré ste už vložili do stránok, ani položky
v Avada Library. Zmizne však CSS a ilustračné obrázky, ktoré plugin nesie —
ak ste ich nechali v sekciách, nahraďte ich pred odstránením vlastnými.
