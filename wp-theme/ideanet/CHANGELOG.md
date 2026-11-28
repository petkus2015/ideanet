# Čo sa v téme menilo

Čísla verzií idú zdola nahor. Verziu nainštalovanej témy nájdete
v administrácii: **IDEANET → Kde čo upraviť → Verzia témy …**, alebo vo
**Vzhľad → Témy** pod názvom témy.

Pri nahrávaní novej témy cez Vzhľad → Témy → Pridať novú → Nahrať tému
WordPress ukáže obe čísla vedľa seba — súčasné a nahrávané —, takže je
vidieť, čo sa čím nahrádza.

## 1.21.0

- Pod menom v hlavičke aj v pätičke je **Freelancer** namiesto
  IDEANET. Obchodné meno **Peter Miškus — Ideanet** sa presunulo tam,
  kam patrí — do údajov v pätičke.
- Nový blok **Kontaktné a fakturačné údaje** v pätičke: obchodné meno,
  adresa, IČO, DIČ, e-mail, telefón a riadok o DPH. Adresa, IČO a DIČ
  sú prázdne, vyplňte ich v Prispôsobiť → IDEANET → 11 · Pätička.
  Prázdne pole sa nevypíše. E-mail a telefón sa berú z *Kontakt
  a siete*, aby neboli uložené dvakrát.
- Z copyrightu v pätičke zmizlo „— IDEANET", meno firmy je o riadok
  nižšie.
- **Vlna pri kliknutí je preč.** Rozbiehala sa pri každom kliknutí
  kdekoľvek na stránke, aj pri obyčajnom kliku do prázdna.

## 1.20.0

- Štvrtá cesta spolupráce — **Kontrakt**. Pre klientov, ktorí nechcú
  video po kampaniach, ale priebežne; rozsah sa dohaduje, preto karta
  vedie rovno na kontakt, nie do cenníka.
- **Ceny z kariet služieb sú preč.** Patria do cenníka a karty naň aj
  tak odkazujú; dve ceny na jednej stránke sa rozchádzajú hneď, ako sa
  jedna z nich zmení. Pole *Cena* vystriedal *Riadok pod odrážkami* —
  krátka veta na spodku karty, napríklad *Polovičný úväzok alebo
  dohodou*. Ceny, ktoré nasadila téma, sa pri aktualizácii zmažú;
  čo ste si tam napísali sami, zostane v databáze, len sa už
  nezobrazí.
- Nadpis sekcie hovoril o troch cestách a o tom, že každá má vlastný
  cenník. Oboje sa opravilo — ak ste si text neprepísali.
- V odrážke *NDA za 30 €* bola tiež cena, je z nej *NDA na požiadanie*.
- Pole *Otvorí v cenníku* ponúkalo tri záložky, ktoré v cenníku už
  nie sú. Zostal výber **Cenník** alebo **Kontakt**.

## 1.19.4

- Skrolovanie stránky cez pás už pásom nepohýba. Keď kurzor prešiel
  cez reels, vizuály, weby alebo fotosady, pás si zvislé točenie
  zobral pre seba — stránka zastala a pás odišiel nabok. Teraz sa
  pás hne jedine gestom do strán.
- Kolieskom myši sa pás už neposunie vôbec; na to sú šípky po
  stranách, guličky pod pásom a ťahanie myšou. Na touchpade
  a dotykovom displeji funguje šmyk do strán ako predtým.

## 1.19.3

- Sekcia **Fotky** je posúvací pás ako vizuály a weby, nie mriežka
  dlaždíc. Karta má pomer 4:5 a názov, popis aj štítok ležia na nej;
  klik ju otvorí, ale ťahanie pásu už nie.
- Počet fotiek na karte sa už skloňuje — *1 fotka*, *3 fotky*,
  *10 fotiek*. Predtým to slovo dopĺňalo CSS, ktoré počet nevie
  prečítať, takže tam stálo aj „3 fotiek“.
- Popis na karte sa nad dva riadky oreže, aby dlhý text kartu
  nerozbil.
- Vo vete pod nadpisom sekcie bol preklep „zakázke“.

## 1.19.2

- Ukážky v sekcii **Webdizajn** majú rovnaký tvar ako ukážky vizuálov:
  pomer 4:5 a názov, popis aj tlačidlo položené priamo na karte, nie
  pod ňou. Zvislé karty 9:16 boli dvakrát také vysoké ako široké
  a z pásu sa tým stal stĺp.
- Popis na karte sa nad dva riadky oreže, aby dlhý text kartu
  nerozbil. Celý text má zákazník na webe za tlačidlom.
- Veta pod nadpisom sekcie hovorila „tlačidlom pod ňou"; tlačidlo je
  teraz na karte. Kto si vetu prepísal, tomu zostane jeho.

## 1.19.1

- Ukážky v sekcii **Webdizajn** sú menšie. Pomer 9:16 z nich robil karty
  dvakrát také vysoké ako široké, takže na obrazovku sa zmestili sotva
  dve; teraz sú vidieť všetky štyri naraz.
- Štítok kategórie na ukážke má tmavé pozadie. Ležal na screenshote
  webu, a ten býva svetlý — fialová na fialovej sa naň strácala.

## 1.19.0

- Nová sekcia **Fotky** pod Reels: dlaždice so sadami fotiek od
  klientov. Jedna dlaždica je jedna zákazka, klik otvorí celú sadu
  v prehliadači so šípkami, miniatúrami, klávesnicou a listovaním
  prstom.
- Nový typ obsahu **Fotky — sady klientov**. Fotky sa vyberajú naraz
  v knižnici médií, poradie sa mení potiahnutím miniatúry a krížikom sa
  fotka zo sady vyhodí (z knižnice sa nezmaže).
- Dve ukážkové sady sa doplnia aj na weboch, ktoré tému mali zapnutú
  skôr. Sú to len farebné výplne — nahraďte ich vlastnými fotkami.
- Nadpis sekcie sa mení v Prispôsobiť → Nadpisy sekcií, dá sa tam aj
  vypnúť. Kým nie je založená ani jedna sada, sekcia sa nezobrazí.

## 1.18.0

- Ukážky webov sú listovací pás, nie mriežka — šípky, guličky, ťahanie
  myšou aj prstom, rovnako ako pri videách a vizuáloch. Ťahanie pásu
  už nespustí video ani neotvorí odkaz na web klienta.
- Pri pár kartách na širokej obrazovke, kde nie je čo listovať, sa
  ovládanie skryje a karty sa vycentrujú. Platí pre všetky tri pásy.
- Ukážkové weby sú štyri (kaviareň, fitness, klinika, stavebná firma),
  aby bolo na páse čo listovať.
- Na mobile sa zafarbí lišta prehliadača podľa webu — hore podľa úvodu,
  po odskrolovaní tmavo. Obe farby sa dajú zmeniť v Prispôsobiť →
  1 · Značka a hlavička. Doteraz tam bol čierny pruh.
- Z CSS vypadol duplicitný blok so štýlmi vizuálov. Bol tam dvakrát
  znak po znaku rovnaký, takže úprava jednej kópie by sa tichým
  prepísaním stratila.

## 1.17.0

- Nová sekcia **Webdizajn** na úvodnej stránke, hneď pod vizuálmi.
  Zvislé ukážky 9:16: klik spustí video prejdenia webu, pod ním je
  tlačidlo na živý náhľad. Ukážky sa pridávajú v IDEANET → Webdizajn —
  ukážky; kým tam nič nie je, sekcia sa nezobrazí.
- Dve ukážkové videá sú v téme a doplnia sa aj na weboch, ktoré tému
  mali zapnutú skôr. Odkaz na živý web v nich mieri na vašu domovskú
  stránku — nahraďte ho adresou klienta.
- Nadpis, vetu aj text tlačidla nájdete v Prispôsobiť → IDEANET →
  Nadpisy sekcií; sekciu sa dá tam aj vypnúť.

## 1.16.0

- Z ponuky redizajnu vypadol limit na počet podstránok. Veta v „Čo
  dostanete" aj v „Pre koho ponuka je" sa skrátila a pole „Koľko
  podstránok je v cene" zmizlo aj so zástupným {podstranok}.
- Na weboch, ktoré podstránku už mali, sa obe vety prepíšu samy — ale
  len ak ste ich nemenili. Vlastný text zostáva.
- Počet voľných miest sa na takom webe nastaví na 4 z 10. Ak ste
  s číslom už hýbali, nechá sa tak; na čerstvej inštalácii zostáva 10.

## 1.15.0

- Úvodná obrazovka má farebné pozadie — magenta, fialová a modrá sa
  prelínajú okolo nadpisu a smerom nadol sa strácajú, takže zvyšok webu
  zostáva tmavý. To isté má aj úvod podstránky s ponukou redizajnu.
- Oprava: na podstránkach, kde je v hlavičke len značka a jedno
  tlačidlo, tlačidlo lepilo na logo namiesto toho, aby bolo vpravo.

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
