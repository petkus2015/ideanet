=== IDEANET Optimalizácia obrázkov ===
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPL-2.0-or-later

Prevedie JPEG/PNG obrázky použité v článkoch na WebP alebo AVIF, opraví odkazy a zmaže staré súbory.

== Použitie ==
1. Nainštalujte a aktivujte plugin.
2. Médiá → Optimalizácia obrázkov: zvoľte formát, kvalitu, maximálnu šírku.
3. Urobte zálohu a kliknite na „Optimalizovať obrázky“ (alebo `wp ideanet-images optimize`).

== Čo plugin robí ==
* Nájde JPEG/PNG prílohy použité ako hlavný obrázok alebo v obsahu článkov (nie stránok). Ak je taký obrázok aj na stránke, odkaz sa tam len opraví.
* Prevedie hlavný súbor a znova vygeneruje všetky veľkosti v novom formáte (ID prílohy ostáva rovnaké).
* Nahradí odkazy v obsahu, excerptoch, post meta (vrátane serializovaných dát) a nastaveniach.
* Po úspechu zmaže pôvodný súbor a všetky jeho veľkosti. Ak by bol nový súbor väčší, pôvodný ostane.

== Čistenie nepoužitých obrázkov ==
Médiá → Optimalizácia obrázkov → „Vyhľadať nepoužité obrázky“. Zobrazí obrázky, ktoré nie sú v žiadnom článku, stránke, hlavnom obrázku, post/term/user meta, menu, logu, ikone webu, widgetoch ani nastaveniach. Mažú sa až po potvrdení, každý obrázok sa pred zmazaním znova overí. Obrázky mladšie než zadaný počet dní (predvolene 30) sa nikdy nemažú.
Plugin nevidí obrázky natvrdo v súboroch témy ani v tabuľkách iných pluginov; vlastné miesta použitia možno doplniť filtrom `ino_image_is_used`.
CLI: `wp ideanet-images cleanup [--dry-run] [--yes]`.

== Zmenšeniny a osirelé súbory ==
* „Miniatúry v celom uploads“: prehľadá všetky priečinky pod wp-content/uploads, nájde miniatúry meno-300x200.jpg (aj .png, .webp…) s väčším hlavným súborom a skutočne zhodnými rozmermi, nahradí odkazy na ne hlavným súborom a miniatúry zmaže.
* „Osirelé súbory“: obrázky v uploads/RRRR/MM, ktoré nepatria žiadnej prílohe a nikde sa nepoužívajú.
Obe čistenia najprv zobrazia zoznam a mažú až po potvrdení.

== Obmedzenia ==
* Animované GIFy a SVG sa nemenia.
* Odkazy na staré URL z iných webov prestanú fungovať.
* Server potrebuje GD alebo Imagick s podporou WebP (AVIF voliteľne).
