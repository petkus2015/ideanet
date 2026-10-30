# Čo sa v téme menilo

Čísla verzií idú zdola nahor. Verziu nainštalovanej témy nájdete
v administrácii: **IDEANET → Kde čo upraviť → Verzia témy …**, alebo vo
**Vzhľad → Témy** pod názvom témy.

Pri nahrávaní novej témy cez Vzhľad → Témy → Pridať novú → Nahrať tému
WordPress ukáže obe čísla vedľa seba — súčasné a nahrávané —, takže je
vidieť, čo sa čím nahrádza.

## 1.3.0

- Príplatok za dodanie v deň natáčania je 30 % namiesto 50 %. Zmenené
  všade, kde sa spomínal — v kalkulačke, v podmienkach aj v príklade
  (z 390 € je tak 507 €, nie 585 €).

## 1.2.0

**Kalkulačka**

- Pod sumou je „konečná cena" namiesto „jednorazovo, bez DPH".
- RAW materiál z natáčania stojí 20 € namiesto 80 €.
- Z doplnkov vypadli grafika na event, vizuály pre siete a správa
  sociálnych sietí. Zostali same-day dodanie a RAW materiál.
- Veta nad kalkulačkou hovorila o cenách bez DPH, čo si s „konečnou
  cenou" odporovalo — teraz hovorí, že sa k cene nič nepripočítava.

## 1.1.0

Prvé číslované vydanie. Verzia 1.0.0 bola rozpracovaná a nosila to isté
číslo cez viacero vydaní, preto sa nedalo rozlíšiť, čo je novšie. Od
tohto vydania sa číslo mení pri každej zmene.

**Úvodná obrazovka**

- Do telefónu sa dá nahrať vertikálne video (MP4, voliteľne WebM, prvý
  obrázok). Prehráva sa samo a stlmene; pri vypnutých animáciách stojí
  na prvom obrázku. Bez nahratého videa zostáva pôvodná animovaná
  obrazovka.
- Telefón sa videu prispôsobí: video sedí hore v pomere 9:16, celé
  a neorezané, dole zostane voľný pás s popisom.
- Na mobile (do 860 px) sa pravý blok úvodu nezobrazuje, nadpis je tak
  hneď v prvej obrazovke.

**Portfólio**

- Dĺžka videa na karte sa číta zo súboru, nevypĺňa sa ručne. Počas
  prehrávania odpočítava zostávajúci čas.
- Klik na kartu, ktorá nie je v strede, video spustil a presun pásu ho
  hneď zase vypol. Opravené.
- Na dotyku sa ťuknutie vyhodnocuje priamo z dotykových udalostí.
  Prehliadač pri ťuknutí s posunom prsta na vodorovnom páse klik vôbec
  nepošle, takže prehrávanie na telefóne nefungovalo.
- Rozbalenie na celú výšku je samá ikona pri pravom okraji karty.
- Keď sa video nedá prehrať, karta to napíše a poradí formát. Predtým
  tvrdila, že video treba doplniť, hoci tam bolo.
- Typ súboru sa určuje z prípony, nie z poľa, do ktorého sa vložil.
- Adresy súborov na vlastnej doméne sa zrovnajú so schémou webu, aby ich
  prehliadač na https stránke nezablokoval.

**Cenník**

- Sekcia Férové ceny obsahuje kalkulačku namiesto balíkov. Balíky
  zostávajú v administrácii — plnia výber vo formulári aj ponuku na
  jednu stranu.
- Kalkulačka prestala byť samostatná sekcia; kotva `#kalkulacka`
  zostala. V menu tým zanikla zdvojená položka.

**Úpravy obsahu**

- V náhľade v Prispôsobiť je pri každom texte ceruzka, ktorá otvorí
  práve to pole. 41 miest.
- Rozcestník v administrácii vypíše všetky sekcie aj typy obsahu
  s priamym odkazom; Kontakt a Formulár sú aj v ľavom stĺpci.
- Skratky v hornej lište na webe — Reels, Vizuály, Balíky, Menu, Dopyty.

**Vzhľad**

- Odbory v sekcii Pre koho sú bez emoji.
- Karty sa pri prejdení myšou neprefarbujú po okraji, len nadvihnú
  a rozsvietia.
- Sekcia s vizuálmi má prehodený nadpis a riadok nad ním.
- Menší odstup medzi sekciami.

**Aktualizácie**

- Téma sa aktualizuje priamo vo WordPresse z vydaní v repozitári
  (hlavička `Update URI`). Nové vydanie sa ukáže vo Vzhľad → Témy.

## 1.0.0

Prvá podoba témy: celý obsah, menu, videá, obrázky, ceny aj formulár sa
upravujú vo WordPresse, bez zásahu do kódu a bez ďalších pluginov.
