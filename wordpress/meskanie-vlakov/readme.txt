=== Meškanie vlakov ===
Tags: vlaky, meškanie, odchody, ŽSR, cestovný poriadok
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.8
License: GPLv2 or later

Tabuľa odchodov a príchodov s aktuálnym meškaním vlakov na Slovensku.

== Popis ==

Plugin pridá shortcode `[meskanie_vlakov]`, ktorý na stránke zobrazí:

* tabuľu odchodov a príchodov vybranej stanice v štýle letiskovej tabule (plánovaný čas, nový čas, cieľ, stav),
* detail vlaku s trasou a s tým, kde sa vlak práve nachádza,
* prehľad všetkých vlakov na trati, štatistiky podľa druhu vlaku a mapu.

Dáta sťahuje server, nie prehliadač návštevníka:

* poloha a meškanie vlakov z mapa.zsr.sk každé 2 minúty (WP-Cron),
* cestovný poriadok na aktuálny deň z otvorených GTFS dát ŽSR (CC0) raz denne.

Aplikácia beží v Shadow DOM, takže štýly témy ju neovplyvnia a ona neovplyvní tému.

== Inštalácia ==

1. V administrácii: Pluginy → Pridať nový → Nahrať plugin → vyberte `meskanie-vlakov.zip`.
2. Aktivujte plugin. Prvé stiahnutie dát prebehne do 2 minút (alebo ho spustite ručne v Nastavenia → Meškanie vlakov).
3. Vytvorte stránku a vložte do nej shortcode `[meskanie_vlakov]` (blok Shortcode).

Voliteľné parametre:

* `sirka="plna"` – tabuľa na celú šírku okna,
* `stanice="Žilina, Vrútky, Martin"` – vlastné tlačidlá častých staníc.

== Často kladené otázky ==

= Dáta sa neaktualizujú =

WP-Cron sa spúšťa pri návštevách webu. Pri malej návštevnosti nastavte na hostingu systémový cron, ktorý každé 2 minúty otvorí `https://vas-web.sk/wp-cron.php`.

= Cestovný poriadok sa nezostaví =

Ak server ŽSR súbor hostingu nevydá, stiahnite gtfs.zip v prehliadači a nahrajte ho v Nastavenia → Meškanie vlakov. Na serveri musí byť PHP rozšírenie zip a dostatok pamäte (aspoň 256 MB). Chyba sa zobrazí v Nastavenia → Meškanie vlakov.

= Tabuľa sa nezobrazí alebo stále píše „Načítavam…“ =

Vymažte cache optimalizačného pluginu a Cloudflare. Ak to nepomôže, v nastaveniach optimalizačného pluginu vylúčte z odkladania, zlučovania a minifikácie súbor `meskanie-vlakov/assets/meskanie-vlakov.js`.

= Ochrana súkromia =

Písma z Google Fonts sa dajú vypnúť v nastaveniach. Plugin nič neukladá o návštevníkoch; v prehliadači si pamätá len vybranú stanicu a veľkosť písma.

== Changelog ==

= 1.1.8 =
* Mobil: tabuľa od kraja po kraj, bez bielych okrajov témy.
* Záložky Odchody / Príchody pri stanici vždy, aj bez cestovného poriadku (vlaky na trati smerom k stanici).
* Správca webu vidí pri chýbajúcom cestovnom poriadku odkaz na jeho nahratie.

= 1.1.7 =
* Cieľová stanica bežným čitateľným písmom (malé písmená, tučné) namiesto preklápacích písmen; čas zostáva v dlaždiciach.

= 1.1.6 =
* Zoznam vlakov na trati v štýle letiskovej tabule: čas posledného hlásenia a cieľ v preklápacích písmenách.

= 1.1.5 =
* Dopravcovia skrátene: „Železničná spoločnosť Slovensko, a.s.“ ako ZSSK, bez právnej formy (a.s., s.r.o.).

= 1.1.4 =
* Oprava: „Cannot read properties of null (reading 'get')“, keď chýba cestovný poriadok a ŽSR pošle ďalšiu stanicu ako číslo.

= 1.1.3 =
* Cieľová stanica na tabuli ako preklápacie písmená (biele), rovnako ako čas.
* Chyba v jednej časti stránky už nezastaví ostatné; hlásenie pomenuje časť, ktorá zlyhala.

= 1.1.2 =
* Tabuľa sa spustí aj s optimalizačnými pluginmi (WP Rocket, LiteSpeed Cache, Autoptimize, SiteGround Optimizer, Jetpack Boost, Cloudflare Rocket Loader).
* Jeden skript namiesto dvoch; spustenie až po načítaní obsahu stránky.
* Pri chybe sa zobrazí jej popis namiesto trvalého „Načítavam…“.

= 1.1.1 =
* Cestovný poriadok sa sťahuje ako z prehliadača a pred spracovaním sa overí, že ide o ZIP; chyba ukáže, čo server vrátil.
* Ručné nahratie súboru gtfs.zip v nastaveniach a záloha posledného platného súboru.
* Po neúspešnom stiahnutí cestovného poriadku ďalší pokus až o hodinu.

= 1.1.0 =
* Aktualizácia dát každé 2 minúty.
* Ďalšia stanica sa zobrazuje názvom (ŽSR niekedy posiela len číslo stanice).
* Fluidný dizajn pre desktop.

= 1.0.0 =
* Prvá verzia.
