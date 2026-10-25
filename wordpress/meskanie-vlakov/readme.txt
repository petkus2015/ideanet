=== Meškanie vlakov ===
Tags: vlaky, meškanie, odchody, ŽSR, cestovný poriadok
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Tabuľa odchodov a príchodov s aktuálnym meškaním vlakov na Slovensku.

== Popis ==

Plugin pridá shortcode `[meskanie_vlakov]`, ktorý na stránke zobrazí:

* tabuľu odchodov a príchodov vybranej stanice v štýle letiskovej tabule (plánovaný čas, nový čas, cieľ, stav),
* detail vlaku s trasou a s tým, kde sa vlak práve nachádza,
* prehľad všetkých vlakov na trati, štatistiky podľa druhu vlaku a mapu.

Dáta sťahuje server, nie prehliadač návštevníka:

* poloha a meškanie vlakov z mapa.zsr.sk každých 5 minút (WP-Cron),
* cestovný poriadok na aktuálny deň z otvorených GTFS dát ŽSR (CC0) raz denne.

Aplikácia beží v Shadow DOM, takže štýly témy ju neovplyvnia a ona neovplyvní tému.

== Inštalácia ==

1. V administrácii: Pluginy → Pridať nový → Nahrať plugin → vyberte `meskanie-vlakov.zip`.
2. Aktivujte plugin. Prvé stiahnutie dát prebehne do 5 minút (alebo ho spustite ručne v Nastavenia → Meškanie vlakov).
3. Vytvorte stránku a vložte do nej shortcode `[meskanie_vlakov]` (blok Shortcode).

Voliteľné parametre:

* `sirka="plna"` – tabuľa na celú šírku okna,
* `stanice="Žilina, Vrútky, Martin"` – vlastné tlačidlá častých staníc.

== Často kladené otázky ==

= Dáta sa neaktualizujú =

WP-Cron sa spúšťa pri návštevách webu. Pri malej návštevnosti nastavte na hostingu systémový cron, ktorý každých 5 minút otvorí `https://vas-web.sk/wp-cron.php`.

= Cestovný poriadok sa nezostaví =

Na serveri musí byť PHP rozšírenie zip a dostatok pamäte (aspoň 256 MB). Chyba sa zobrazí v Nastavenia → Meškanie vlakov.

= Ochrana súkromia =

Písma z Google Fonts sa dajú vypnúť v nastaveniach. Plugin nič neukladá o návštevníkoch; v prehliadači si pamätá len vybranú stanicu a veľkosť písma.

== Changelog ==

= 1.0.0 =
* Prvá verzia.
