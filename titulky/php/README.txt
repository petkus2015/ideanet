TITULKY: verzia pre PHP webhosting (FTP)
=========================================

Čo nahrať:
  index.php, app.html, .htaccess  ->  napr. do priečinka /titulky na tvojom webe

Adresa potom bude napríklad https://tvojweb.sk/titulky/

Požiadavky:
  - PHP 7.4 alebo novšie (stačí obyčajný webhosting)
  - HTTPS (certifikát, väčšina hostingov ho ponúka zadarmo)
  - Návštevník potrebuje prehliadač Chrome, Edge alebo Safari a internet

Ako to funguje:
  Server len zobrazí stránku. Rozpoznanie reči (model Whisper) aj export videa bežia
  v prehliadači návštevníka. Video sa na tvoj server nenahráva, takže nezaberá miesto
  ani výkon a nie je čo mazať. Pri prvom použití si prehliadač stiahne model (40 až 250 MB
  podľa výberu) z huggingface.co a zapamätá si ho.

Dôležité:
  - Na bežnom webhostingu sa Whisper ani ffmpeg spustiť nedajú, preto sa všetko robí v prehliadači.
  - Predplatné 2 € mesačne nie je súčasťou, aplikácia je voľne dostupná každému, kto pozná adresu.
  - Ak máš .htaccess zakázaný alebo ho server nepodporuje, aplikácia funguje aj bez neho.
