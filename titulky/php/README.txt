TITULKY: verzia pre PHP webhosting (FTP)
=========================================

Čo nahrať (do jedného priečinka, napr. subdoména captions.tvojweb.sk):
  index.php, app.html

Požiadavky:
  - PHP 7.4 alebo novšie (stačí obyčajný webhosting)
  - HTTPS (certifikát)
  - Návštevník potrebuje Chrome, Edge alebo Safari a internet

Model na vlastnom FTP (voliteľné, ale odporúčané):
  Prehliadač si model inak sťahuje z huggingface.co, čo niektoré siete a aplikácie blokujú.
  Môžeš ho dať na svoj server, odkiaľ sa potom sťahuje:
  1. Na počítači s internetom spusti:  pip install huggingface_hub  a potom  python3 stiahni_model.py
     (pridaj --turbo pre presnejší model large-v3-turbo, približne 1,5 GB, pre prehliadače s WebGPU)
  2. Vznikne priečinok models/. Nahraj ho cez FTP vedľa index.php (celý, aj s podpriečinkami).
  3. Hotovo. Stránka sama nájde models/ a pod výberom videa napíše, že model je na serveri.
  Ak priečinok models/ nie je, použije sa huggingface.co ako predtým.

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

Nie je súčasťou: predplatné 2 € mesačne, aplikácia je voľne dostupná.
