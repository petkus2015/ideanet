# Čo sa v téme menilo

Čísla verzií idú zdola nahor. Verziu nainštalovanej témy nájdete
v administrácii: **IDEANET → Kde čo upraviť → Verzia témy …**, alebo vo
**Vzhľad → Témy** pod názvom témy.

Pri nahrávaní novej témy cez Vzhľad → Témy → Pridať novú → Nahrať tému
WordPress ukáže obe čísla vedľa seba — súčasné a nahrávané —, takže je
vidieť, čo sa čím nahrádza.

## 1.14.0

- Stránka 404 je poriadna: okrem nadpisu má hľadanie, odkazy na hlavné
  sekcie (berú sa z horného menu) a kontakt. Doteraz na nej bola len
  jedna veta a tlačidlo na úvod.
- Jej texty sa upravujú v Prispôsobiť → IDEANET → 10 · Stránka 404.
  Ostatné sekcie sa posunuli o jednu — pätička je teraz 11.

## 1.13.0

- Ukážka, ktorá má vyplnený odkaz na web, má pod textom tlačidlo, ktoré
  ten web otvorí v novom okne. Názov tlačidla sa mení pri stránke
  v boxe „Ukážky klientov"; predvolene je to „Pozrieť web".
- Názov ukážky už nie je odkaz — odkazom je to tlačidlo.

## 1.12.0

- Sekcia s ukážkami už neukazuje pôvodný web, len hotový. Volá sa
  „Ukážky klientov" a každá ukážka má jednu snímku namiesto dvojice.
- Typ obsahu sa premenoval na „Redizajn — ukážky klientov" a zostalo mu
  jedno pole na obrázok. Pri existujúcich ukážkach sa snímka hotového
  webu presunie do nového poľa sama, tá pôvodná sa odpojí. V knižnici
  médií zostávajú oba obrázky.
- Nadpis sekcie sa prepíše len vtedy, keď ste ho nemenili. Vlastný text
  zostáva.

## 1.11.0

- Texty podstránky s ponukou redizajnu sa upravujú priamo pri stránke
  v Stránky → Redizajn webov, v boxoch rozdelených po sekciách, nie
  v Prispôsobiť. Sekcie 11 a 11a z Prispôsobiť zmizli.
- Čo ste mali v Prispôsobiť prepísané, sa pri aktualizácii prekopíruje
  k stránke, takže sa nič nestratí.
- Tá jedna stránka sa otvára v klasickom editore — blokový editor dáva
  takéto boxy do zasunutej lišty na spodku obrazovky.
- Prázdne pole znamená predvolený text z témy; pri niektorých poliach
  skryje celú sekciu.

## 1.10.0

- Oprava: podstránka s ponukou redizajnu vznikala len pri úplne prvej
  aktivácii témy. Na weboch, ktoré tému mali zapnutú skôr, sa teda
  nevytvorila nikdy — a keďže nebola, nepribudol ani odkaz v menu.
  Teraz sa stránka, jedna ukážka aj odkazy v menu dorobia pri prvom
  otvorení administrácie po aktualizácii.
- Podstránka sa hľadá podľa šablóny, nie podľa adresy, takže sa dá
  premenovať bez toho, aby sa stratila.
- V IDEANET → Kde čo upraviť pribudol priamy odkaz na úpravu tej
  podstránky.

## 1.9.0

- Podstránka s ponukou redizajnu pribudla do horného menu (ako
  „Redizajn") a do rozbaľovacieho menu na mobile pod tlačidlom Viac
  (ako „Redizajn webov"). V oboch je hneď za cenníkom.
- Na weboch, ktoré tému mali zapnutú skôr, sa odkaz doplní sám pri
  prvom otvorení administrácie. Kto ho tam nechce, vyhodí ho vo
  Vzhľad → Menu a späť sa už nepridá.

## 1.8.0

- V ponuke redizajnu zmizli dlhé pomlčky, vety sú prepísané na bodky,
  čiarky a zátvorky.
- Zo zoznamu „Čo dostanete" vypadol bod o zachovaných pozíciách
  v Google. Otázka o pozíciách zostáva medzi častými otázkami.
- Ukážka pred a po je pri aktivácii len jedna a je na celú šírku, takže
  sa na snímkach dá naozaj niečo porovnať.

## 1.7.0

- Ponuky na jednu stranu („Ponuka pre firmy" a „Ponuka pre agentúry")
  sú preč — cenník aj podmienky sú na úvodnej stránke a dve kópie tých
  istých čísel sa len rozchádzali. Zmizol odkaz na ne pod podmienkami,
  šablóna stránky aj box, v ktorom sa vyberal cenník.
- Stránky, ktoré tú šablónu používali, sa pri prvom otvorení
  administrácie presunú do koša. Ak ich chcete späť, sú v Stránky → Kôš.
- V Prispôsobiť zmizla sekcia „8 · Podmienky — pätka" a štyri polia
  s odkazmi na ponuky v sekcii cenníka. Ostatné sekcie sa prečíslovali,
  redizajn webov je teraz 11 a 11a.
- Z viet pri cenníku a podmienkach vypadlo „Podrobnosti sú v ponuke na
  stiahnutie".

## 1.6.0

- Pribudla podstránka **Ponuka redizajnu webov** — akčná ponuka na
  redizajn prezentačného webu s cenou, termínom, počtom voľných miest,
  ukážkami pred/po, častými otázkami a formulárom. Pri aktivácii sa
  stránka založí sama a odkaz na ňu pribudne do pätičky.
- Nový typ obsahu **Redizajn — ukážky**: dvojica obrázkov pred a po
  s jednou vetou o výsledku.
- Cena, termín a počty na tej podstránke sú uložené raz a do textov sa
  dopĺňajú cez {cena}, {cena_pred}, {termin}, {miest}, {volnych},
  {podstranok} a {dni}, takže sa úvod a cenník nemajú ako rozísť.
- Vo výbere „o čo máte záujem" pribudla voľba pre redizajn webu
  a na podstránke je prednastavená.
- Formulár je teraz samostatná časť šablóny, používa ho úvodná stránka
  aj nová podstránka.

## 1.5.0

- Riadok nad nadpisom v úvode znie „Som videotvorca, grafik a content
  creator · BA · ZA · Brno".

## 1.4.0

- Nadpis sekcie s podmienkami je „Férové podmienky".
- V kontakte vypadol sľub „Odpisujem do 24 hodín" a pribudla možnosť
  ozvať sa na Instagrame (@petermiskus). Prezývka pri kontakte aj ikona
  Instagramu vedú na ten profil; TikTok, YouTube a Facebook sú stále
  zástupné.

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
