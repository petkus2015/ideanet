=== Lacné letenky VIE · BTS ===
Tags: letenky, lety, ryanair, wizzair, momondo
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPL-2.0-or-later

Blok s najlacnejšími letenkami z Viedne a Bratislavy: Bangkok, Dubaj, Abu Dhabí a ponuky kamkoľvek.

== Popis ==

Ceny 3× denne (7:00, 12:00, 18:00) porovnáva GitHub Actions z momondo.co.uk, ryanair.com
a wizzair.com; z každej trasy sa ukáže najlacnejšia spiatočná letenka s odletom do 3 mesiacov.
Plugin si výsledok (deals.json) načíta, uloží do cache a zobrazí v bloku s tlačidlom
„Vyhľadaj aktuálne lacné letenky“.

Vloženie na stránku:

* tagDiv Composer (téma Newspaper) – prvok „Lacné letenky“, alebo shortcode v prvku „Column text“
* editor blokov – blok „Lacné letenky“
* shortcode – [lacne_letenky] alebo [lacne_letenky limit="12" title="Kam lacno z Viedne"]
* Vzhľad → Widgety – widget „Lacné letenky“

Nastavenia: Nastavenia → Lacné letenky (zdroj dát, cache, počet kariet, písmo, stav cien).

== Inštalácia ==

1. Pluginy → Pridať nový → Nahrať plugin → lacne-letenky.zip → Inštalovať → Aktivovať.
2. Nastavenia → Lacné letenky → „Načítať ceny teraz“ a skontrolujte stav cien.
3. Vložte blok na stránku niektorým zo spôsobov vyššie.

== Changelog ==

= 1.0.2 =
* Blok začína rovno ponukami (bez hlavičky); tlačidlo „Vyhľadaj aktuálne lacné letenky“ je pod ponukami.

= 1.0.1 =
* Keď zdroj cien ešte nemá dáta, blok ukáže „Ponuky sa pripravujú“ namiesto chyby 503; nastavenia vysvetlia príčinu.

= 1.0.0 =
* Prvé vydanie.
