TITULKY: verzia pre PHP webhosting (FTP)
=========================================

Čo nahrať (do jedného priečinka, napr. subdoména captions.tvojweb.sk):
  index.php, app.html

Požiadavky:
  - PHP 7.4 alebo novšie (stačí obyčajný webhosting)
  - HTTPS (certifikát)
  - Návštevník potrebuje Chrome, Edge alebo Safari a internet

Modely na vlastnom FTP (odporúčané – návštevníci ich potom nesťahujú z huggingface.co):
  Subgen používa dva modely: rozpoznávanie reči (Whisper) a preklad titulkov (NLLB-200).
  1. Na počítači nainštaluj Python (python.org). Na Windows pri inštalácii zaškrtni "Add Python to PATH".
  2. V priečinku so stiahni_model.py otvor príkazový riadok a spusti:
        pip install huggingface_hub
        python3 stiahni_model.py          (na Windows: py stiahni_model.py)
     Pridaj --turbo, ak chceš aj presnejší model large-v3-turbo (približne 1,5 GB, pre prehliadače s WebGPU).
  3. Vznikne priečinok models/ (spolu približne 1 GB, s --turbo vyše 2,5 GB; skript vypíše presnú veľkosť). Nahraj ho cez FTP celý,
     aj s podpriečinkami, vedľa index.php. Over, že existuje napr. models/Xenova/whisper-small/config.json.
  4. Hotovo. Stránka sama nájde models/ a pod výberom videa napíše, že model je na serveri.
  Ak priečinok models/ nie je, modely sa sťahujú z huggingface.co ako predtým.

Ak sa stránka nenačíta:
  1. Otvor adresu s ?diag=1 na konci, napr. https://captions.tvojweb.sk/?diag=1
     Ak sa vypíše PHP verzia a "app.html: ok", server funguje.
  2. Ak PHP nefunguje (biela stránka alebo chyba 500), zmaž index.php a premenuj app.html
     na index.html. Aplikácia nepotrebuje PHP, funguje aj ako obyčajná stránka.
  3. Ak je sub-doména nasmerovaná na iný priečinok, súbory daj do toho priečinka.
  4. Ak sa stránka zobrazí, ale po výbere videa sa nič nestane, nad stránkou sa objaví
     červený pruh s chybou. Pošli ju vývojárovi.

Ako to funguje:
  Server len zobrazí stránku. Rozpoznanie reči (model Whisper) aj export videa bežia
  v prehliadači návštevníka. Video sa na server nenahráva. Pri prvom použití si prehliadač
  stiahne model (40 až 250 MB) z huggingface.co a zapamätá si ho.

Spoločné fonty (Značka):
  Font, ktorý niekto nahrá v časti Značka, sa uloží na server do priečinka data/ a je dostupný
  pre všetkých používateľov. Pravidlá:
  - prijímajú sa len skutočné fonty (TTF, OTF, WOFF, WOFF2), overené podľa obsahu súboru, najviac 10 MB,
  - z jednej IP adresy jeden font za 7 dní (IP sa neukladá, len jej zašifrovaný odtlačok),
  - nahrávajúci musí potvrdiť, že font môže zdieľať (licencia). Za fonty na serveri zodpovedá prevádzkovateľ webu.
  Priečinok data/ si server vytvorí sám, potrebuje právo na zápis (overíš cez ?diag=1).
  Zmazanie fontu: cez FTP zmaž súbor v data/fonts/ a jeho riadok v data/fonts.json.
  Nastavenia (limit, veľkosť, počet) sú na začiatku časti "spoločné fonty" v index.php.
  Ak je web za proxy (napr. Cloudflare), všetci môžu mať rovnakú IP. Vtedy daj vedieť vývojárovi.

Účty a predplatné (Stripe, 4,90 € ročne):
  Nahrať video a titulkovať môže každý. Export videa je len pre prihlásených s platným predplatným.
  Registrácia: e-mail -> platba cez Stripe Payment Link -> návrat na web -> server si platbu overí
  priamo v Stripe a používateľ si nastaví heslo. Prihlásenie potom e-mailom a heslom.
  Nastavenie (raz):
  1. Stripe -> Products: produkt "Subgen" za 4,90 € ročne (Recurring, Yearly).
  2. Stripe -> Payment Links -> nový odkaz na tento produkt. V časti "After payment" zvoľ
     "Don't show confirmation page" a presmerovanie na:
        https://subgen.ideanet.sk/?platba={CHECKOUT_SESSION_ID}
  3. Stripe -> Developers -> API keys -> "Create restricted key": práva Read pre Checkout Sessions
     a Subscriptions (stačí to, nič iné). Skopíruj kľúč rk_live_...
  4. Súbor config-vzor.php premenuj na config.php, vlož doň kľúč a odkaz z kroku 2, nahraj cez FTP.
     config.php pri ďalších aktualizáciách NEPREPISUJ (v ZIP-e nie je).
  5. Odporúčané: Stripe -> Developers -> Webhooks -> endpoint https://subgen.ideanet.sk/?auth=webhook
     s udalosťami invoice.paid a customer.subscription.deleted. "Signing secret" (whsec_...) daj do config.php.
     Vďaka tomu sa ročné obnovenie a zrušenie predplatného prejaví v účte samo.
  6. Over cez ?diag=1: má ukázať "config.php: ok, Stripe kľúč: nastavený, platobný odkaz: nastavený".
  Účty sú v data/users.php (heslá len ako bezpečný hash). Obnova hesla posiela e-mail cez PHP mail().
  Pozor: export beží v prehliadači, takže zručný používateľ by ho vedel obísť úpravou kódu stránky.
  Pre bežných používateľov to stačí; úplná ochrana by vyžadovala export na serveri.

Nie je súčasťou: predplatné 2 € mesačne, aplikácia je voľne dostupná.

Preklad titulkov na mobile
  Mobil nezvládne veľký model NLLB, preto sa na ňom prekladá malými modelmi (~120 MB) cez angličtinu
  (napr. slovenčina -> angličtina -> čeština). Model sa stiahne len pre zvolené jazyky a po preklade sa uvoľní z pamäte.
  Nahraj na FTP do models/ priečinky:
    models/subgen/opus-mt-sk-en, models/subgen/opus-mt-en-sk   (slovenčina, prevedené pre Subgen, len zo ZIP-u)
    models/Xenova/opus-mt-XX-en a opus-mt-en-XX pre cs, es, ar, hi, zh, pl-en, tr-en a opus-mt-en-mul (poľština, turečtina)
  Na mobile sa používa aj menší model rozpoznávania reči models/Xenova/whisper-base.
