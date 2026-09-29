# IDEANET — bloky pre Avada Builder

WordPress plugin, ktorý pridá do Fusion Builder (Avada Builder) sadu
editovateľných blokov pre web IDEANET — hero, služby, portfóliové
karusely, proces, cenník, školenia, referencie a kontakt. Všetky texty,
obrázky, videá aj opakovateľné položky (referencie, ukážky, kroky,
cenové balíky) sa dopĺňajú priamo v builderi, bez úpravy kódu.

Vizuálne vychádza z pôvodného statického webu (priečinok `../index.html`
a spol. v tomto repozitári) — tmavá paleta, akcent `#ff0056`, písma
Inter + Instrument Serif, rovnaký karusel (desktop viac kariet vedľa
seba, mobil 1,5 karty so swajpovaním).

## Čo plugin obsahuje a čo nie

Toto sú **obsahové bloky** — hero, karty, karusely, cenník atď. —
nie kompletná šablóna stránky. Hlavičku (navigácia, logo), pätičku
a farby pozadia celej stránky rieši vlastný Header/Footer Builder
Avady; tento plugin sa nimi zámerne nezaoberá, aby sa neprelínal
s nastaveniami témy.

## Inštalácia

1. Celý priečinok `ideanet-avada-blocks` zazipujte (musí byť zazipovaný
   priamo tento priečinok, nie jeho obsah zvlášť).
2. WordPress admin → **Pluginy → Pridať nový → Nahrať plugin** → vyberte
   zip → **Inštalovať** → **Aktivovať**.
3. Podmienka: aktívna téma **Avada** so zapnutým **Fusion Builderom**
   (bežná predvolená konfigurácia). Bez neho sa bloky v builderi
   nezobrazia — plugin vás na to upozorní hláškou v administrácii.
4. Otvorte ľubovoľnú stránku v **Avada Builderi** → v zozname prvkov
   nájdete kategóriu **IDEANET** s ôsmimi blokmi popísanými nižšie.

**Odporúčanie:** každý blok vložte do **Full Width Row** (Avada Builder
→ pridať riadok → Full Width), nie do bežného orámovaného kontajnera —
bloky majú vlastné plné pozadie a počítajú s tým, že siahajú od okraja
po okraj obrazovky.

## Zoznam blokov

| Blok | Shortcode | Na čo slúži |
|---|---|---|
| IDEANET — Hero | `[ideanet_hero]` | Úvodná sekcia s nadpisom, dvoma tlačidlami a karuselom posledných realizácií |
| IDEANET — Služby | `[ideanet_services]` | Tri (aj viac) karty disciplín s ikonou, popisom a zoznamom bodov |
| IDEANET — Portfólio karusel | `[ideanet_carousel]` | Znovupoužiteľný karusel ukážok — vložte ho trikrát (video, grafika, sociálne siete), zakaždým s inými nastaveniami |
| IDEANET — Proces | `[ideanet_process]` | Očíslované kroky spolupráce |
| IDEANET — Cenník | `[ideanet_pricing]` | Cenové balíky, jeden môže byť zvýraznený ako odporúčaný |
| IDEANET — Školenia pre firmy | `[ideanet_training]` | Sekcia „pre koho / čo si odnesú” + karta formátov a cien |
| IDEANET — Referencie | `[ideanet_testimonials]` | Citáty klientov |
| IDEANET — Kontakt | `[ideanet_contact]` | Kontaktné údaje, box dostupnosti a formulár (odosielanie cez `mailto:`) |

### Portfólio karusel — jeden blok, tri použitia

Na pôvodnom webe boli tri karusely (Videografia 9:16 video, Grafický
dizajn 4:5 obrázky, Sociálne siete 9:16 video). V builderi je to **ten
istý blok** vložený trikrát — líši sa len nastavením:

- **Videografia** — režim „Video“, pomer `9/16`, kotva `video`
- **Grafický dizajn** — režim „Iba obrázok“, pomer `4/5`, kotva `grafika`
- **Sociálne siete** — režim „Video“, pomer `9/16`, kotva `social`

Pole „Kotva pre odkazy” nastaví `id` sekcie, takže na ňu môžete
odkazovať z iného bloku (napr. tlačidlo „Ukážky videí” v Službách
smeruje na `#video`).

### Prepojenie Školení s formulárom

Tlačidlo „Mám záujem o školenie” má pole **„Hodnota, ktorá sa
predvyplní v kontaktnom formulári”**. Pri kliknutí sa v najbližšom
kontaktnom formulári na stránke automaticky zaškrtne zodpovedajúca
položka v poli „Zoznam služieb” — hodnoty sa musia zhodovať presne
(napr. `Školenia pre tím` v oboch blokoch).

## Ako pridať referencie, videá a obrázky

Repeater polia (Realizácie, Karty služieb, Ukážky, Kroky, Cenové
balíky, Formáty, Referencie) majú v Avada Builderi tlačidlo
**„Add Item“** — každá pridaná položka má vlastné polia na text
a tlačidlo na výber obrázka/videa z Knižnice médií. Poradie položiek
sa dá v builderi preusporiadať ťahaním.

- **Video** — nahrajte MP4 priamo cez tlačidlo pri poli „Video“.
- **Obrázok / náhľad** — rovnako cez Knižnicu médií.
- **Referencie** — blok Referencie, pridajte položku, vyplňte citát,
  meno a pozíciu/firmu.

## Technické poznámky

- Shortcody sú zaregistrované klasickým, dlhodobo zdokumentovaným
  Fusion Builder Element API (`fusion_builder_map()` +
  `add_shortcode()`), ktoré Avada udržiava spätne kompatibilné už
  mnoho verzií. Opakovateľné polia používajú natívny Fusion Builder
  typ `'multiple'` (rovnaký mechanizmus ako vstavané prvky Avady,
  napr. Testimonials).
- Ak by sa v niektorej novšej/staršej verzii Avady prvok v builderi
  nezobrazil alebo by opakovateľné pole vyzeralo inak, najpravdepodobnejšie
  ide o rozdiel práve v UI repeatera (`'multiple'`) — zvyšok (registrácia,
  vykresľovanie, štýly) je na verzii Avady nezávislý. V takom prípade
  overte vo Fusion Builder → Elements, či sa bloky IDEANET vôbec
  ponúkajú, a skúste stránku uložiť a znova načítať.
- CSS aj JS sú zaškatuľkované pod `.ib-scope` / `.ib-` triedy, aby sa
  neprelínali so štýlmi témy Avada. Farby a rozostupy sú CSS premenné
  v `assets/css/blocks.css` (`:root` blok `.ib-scope`) — zmena vzhľadu
  celého pluginu je zmena pár premenných na jednom mieste.
- JS (`assets/js/blocks.js`) je nezávislý od jQuery a funguje pre
  ľubovoľný počet blokov na stránke naraz (karusel, lightbox aj
  formulár sú viacinštančné, nič nie je naviazané na pevné ID).
- Formulár v bloku Kontakt momentálne otvára e-mailového klienta
  (`mailto:`), presne ako na pôvodnom statickom webe. Pre odosielanie
  na server (napr. cez `wp_mail()` alebo externú službu) je potrebné
  upraviť `initContact()` v `assets/js/blocks.js` a/alebo pridať
  spracovanie na strane PHP.
- Ikony v bloku Služby sú vstavaná sada šiestich SVG ikon
  (`includes/helpers.php` → `ideanet_blocks_icon_set()`) plus možnosť
  vložiť vlastné SVG cez voľbu „Vlastné SVG“.

## Požiadavky

- WordPress 5.9+
- PHP 7.4+
- Téma Avada s aktívnym Fusion Builderom
