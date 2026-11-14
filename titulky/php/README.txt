TITULKY: verzia pre PHP webhosting (FTP)
=========================================

Čo nahrať (do jedného priečinka, napr. subdoména captions.tvojweb.sk):
  index.php, app.html

Požiadavky:
  - PHP 7.4 alebo novšie (stačí obyčajný webhosting)
  - HTTPS (certifikát)
  - Návštevník potrebuje Chrome, Edge alebo Safari a internet

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
