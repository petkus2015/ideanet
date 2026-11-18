// ---------- jazyk rozhrania podľa prehliadača ----------
// Zdrojové texty sú po slovensky. Pre iný jazyk prehliadača sa texty v stránke preložia slovníkom nižšie,
// aj tie, ktoré aplikácia zmení počas behu (MutationObserver). Čeština a angličtina; iné jazyky dostanú angličtinu.
// Poradie: ?lang=… v adrese, potom voľba v Nastaveniach (uložená v prehliadači), potom jazyk prehliadača.
const UI_LANGS = { sk: "Slovenčina", cs: "Čeština", en: "English" };
let savedLang = null; try { savedLang = localStorage.getItem("titulky-lang"); } catch {}
let LANG = (() => {
  const q = new URLSearchParams(location.search).get("lang");
  for (const l of [q, savedLang, ...(navigator.languages || []), navigator.language]) {
    const c = String(l || "").slice(0, 2).toLowerCase();
    if (c === "sk" || c === "cs" || c === "en") return c;
    if (c === "cz") return "cs";
  }
  return "en";
})();
let LOCALE, LI;
const setLangVars = () => { LOCALE = { sk: "sk-SK", cs: "cs-CZ", en: "en-GB" }[LANG]; LI = LANG === "cs" ? 0 : 1; }; // LI = stĺpec v slovníku: [čeština, angličtina]
setLangVars();
const decTxt = (v) => String(v).replace(".", LANG === "en" ? "." : ",");
const DICT = {
  // ---- hlavička, menu, kroky ----
  "Titulky do videa": ["Titulky do videa", "Video Subtitles"],
  "Zmeň svoj obsah na formáty pripravené na zverejnenie a pridaj automatické titulky.": ["Převeď svůj obsah do formátů připravených ke zveřejnění a přidej automatické titulky.", "Turn your content into ready-to-post formats and add automatic subtitles."],
  "Ukážka: titulky sú vzorové, video ostáva vo vašom prehliadači.": ["Ukázka: titulky jsou vzorové, video zůstává ve vašem prohlížeči.", "Demo: sample subtitles, the video stays in your browser."],
  "+ Nový projekt": ["+ Nový projekt", "+ New project"],
  "Titulkovanie": ["Titulkování", "Subtitles"], "Knižnica": ["Knihovna", "Library"], "Značka": ["Značka", "Brand"],
  "Plán": ["Plán", "Plan"], "Nastavenia": ["Nastavení", "Settings"], "Jazyk rozhrania": ["Jazyk rozhraní", "Interface language"], "Pro": ["Pro", "Pro"], "2 € mesačne": ["2 € měsíčně", "€2 per month"], "Bez limitu exportov": ["Bez limitu exportů", "Unlimited exports"],
  "Kroky": ["Kroky", "Steps"],
  "Nahratie": ["Nahrání", "Upload"], "Pridaj video": ["Přidej video", "Add a video"],
  "Text, rečníci, štýl": ["Text, mluvčí, styl", "Text, speakers, style"],
  "Úprava": ["Úpravy", "Edit"], "Formát, výrez, strih": ["Formát, výřez, střih", "Format, crop, cut"],
  "Export": ["Export", "Export"], "Stiahni súbory": ["Stáhni soubory", "Download files"],
  // ---- nahratie ----
  "1. Jazyk videa": ["1. Jazyk videa", "1. Video language"],
  "Slovenčina": ["Slovenština", "Slovak"], "Rozpoznať automaticky": ["Rozpoznat automaticky", "Detect automatically"], "Čeština": ["Čeština", "Czech"], "Angličtina": ["Angličtina", "English"],
  "2. Vyber video alebo ho pretiahni sem": ["2. Vyber video nebo ho přetáhni sem", "2. Choose a video or drop it here"],
  "MP4, MOV, WEBM": ["MP4, MOV, WEBM", "MP4, MOV, WEBM"],
  "MP4, MOV, WEBM · zostáva vo vašom prehliadači": ["MP4, MOV, WEBM · zůstává ve vašem prohlížeči", "MP4, MOV, WEBM · stays in your browser"],
  "Rozpoznanie používa automaticky najpresnejší model, ktorý tvoj prehliadač zvládne.": ["Rozpoznávání automaticky používá nejpřesnější model, který tvůj prohlížeč zvládne.", "Recognition automatically uses the most accurate model your browser can handle."],
  "Model je uložený v tomto prehliadači a nesťahuje sa znova.": ["Model je uložený v tomto prohlížeči a znovu se nestahuje.", "The model is saved in this browser and won't be downloaded again."],
  "Model je na serveri tejto stránky, sťahuje sa odtiaľ a ukladá sa v prehliadači.": ["Model je na serveru této stránky, stahuje se odtud a ukládá se v prohlížeči.", "The model is on this site's server; it downloads from there and is saved in the browser."],
  "Pri prvom použití sa model stiahne (stovky MB), potom sa uloží a nesťahuje sa znova.": ["Při prvním použití se model stáhne (stovky MB), pak se uloží a znovu se nestahuje.", "On first use the model downloads (hundreds of MB), then it's saved and not downloaded again."],
  "Vyber súbor s videom (MP4, MOV, MKV, WEBM a podobne).": ["Vyber soubor s videem (MP4, MOV, MKV, WEBM a podobně).", "Choose a video file (MP4, MOV, MKV, WEBM and similar)."],
  // ---- spracovanie ----
  "Spracúvam…": ["Zpracovávám…", "Processing…"], "Čítam zvuk…": ["Čtu zvuk…", "Reading audio…"], "Načítavam zvuk…": ["Načítám zvuk…", "Loading audio…"],
  "Načítavam model…": ["Načítám model…", "Loading model…"], "Sťahujem model…": ["Stahuji model…", "Downloading model…"], "Rozpoznávam reč…": ["Rozpoznávám řeč…", "Recognizing speech…"],
  "Prehliadač nevie rozbaliť zvuk priamo, číta ho pri prehrávaní.": ["Prohlížeč neumí rozbalit zvuk přímo, čte ho při přehrávání.", "The browser can't decode the audio directly, it reads it while playing."],
  "Pri dlhšom videu to môže trvať niekoľko minút. Nechaj túto kartu otvorenú.": ["U delšího videa to může trvat několik minut. Nech tuto kartu otevřenou.", "A longer video may take several minutes. Keep this tab open."],
  "V tomto videu sa nenašla žiadna reč.": ["V tomto videu nebyla nalezena žádná řeč.", "No speech was found in this video."],
  "Rozpoznanie zlyhalo.": ["Rozpoznání selhalo.", "Recognition failed."],
  "Zo súboru sa nepodarilo prečítať zvuk. Súbor je asi poškodený, bez zvuku alebo má zvukový kodek, ktorý prehliadač nepozná. Skús ho previesť do MP4 (H.264 + AAC).": ["Ze souboru se nepodařilo přečíst zvuk. Soubor je asi poškozený, bez zvuku nebo má zvukový kodek, který prohlížeč nezná. Zkus ho převést do MP4 (H.264 + AAC).", "Couldn't read audio from the file. It may be damaged, silent or use an audio codec the browser doesn't know. Try converting it to MP4 (H.264 + AAC)."],
  "Prehliadač sa nedostal na huggingface.co (blokuje ho sieť, VPN, firewall alebo blokovač reklám). Skús inú sieť alebo vypni blokovač.": ["Prohlížeč se nedostal na huggingface.co (blokuje ho síť, VPN, firewall nebo blokovač reklam). Zkus jinou síť nebo vypni blokovač.", "The browser couldn't reach huggingface.co (blocked by the network, VPN, firewall or an ad blocker). Try another network or turn off the blocker."],
  "Stránka beží vo vstavanom prehliadači aplikácie, ktorý blokuje stiahnutie modelu z huggingface.co. Otvor ju v Safari alebo Chrome.": ["Stránka běží ve vestavěném prohlížeči aplikace, který blokuje stažení modelu z huggingface.co. Otevři ji v Safari nebo Chrome.", "The page runs in an app's built-in browser, which blocks the model download from huggingface.co. Open it in Safari or Chrome."],
  "Skús obnoviť stránku.": ["Zkus obnovit stránku.", "Try reloading the page."],
  "Skús súbor otvoriť cez lokálny server: v priečinku so súborom spusti `python3 -m http.server 8000` a otvor http://localhost:8000/titulky-browser.html.": ["Zkus soubor otevřít přes lokální server: ve složce se souborem spusť `python3 -m http.server 8000` a otevři http://localhost:8000/titulky-browser.html.", "Try opening the file through a local server: in its folder run `python3 -m http.server 8000` and open http://localhost:8000/titulky-browser.html."],
  "Nepodarilo sa načítať knižnicu na rozpoznávanie (cdn.jsdelivr.net). Skontroluj pripojenie na internet alebo blokovač reklám. Technická chyba:": ["Nepodařilo se načíst knihovnu pro rozpoznávání (cdn.jsdelivr.net). Zkontroluj připojení k internetu nebo blokovač reklam. Technická chyba:", "Couldn't load the recognition library (cdn.jsdelivr.net). Check your internet connection or ad blocker. Technical error:"],
  "Diagnostika:": ["Diagnostika:", "Diagnostics:"], "Prehliadač:": ["Prohlížeč:", "Browser:"], "Protokol:": ["Protokol:", "Protocol:"], "Model:": ["Model:", "Model:"], "Rečníci:": ["Mluvčí:", "Speakers:"],
  "pamäť prehliadača": ["paměť prohlížeče", "browser storage"], "bez pamäte": ["bez paměti", "no storage"], "uložený model": ["uložený model", "saved model"], "vlastný server": ["vlastní server", "own server"],
  "iný prehliadač": ["jiný prohlížeč", "another browser"], "NEDOSTUPNÉ": ["NEDOSTUPNÉ", "UNAVAILABLE"],
  "Prehliadač nevie prehrať obraz tohto videa (pravdepodobne kodek H.265/HEVC alebo 10-bitový H.264). Titulky sa rozpoznali zo zvuku a môžeš ich opraviť a stiahnuť ako SRT alebo VTT. Náhľady a export videa nie sú k dispozícii. Skús Safari alebo Chrome na Macu (HEVC prehrávajú), alebo video prevedieš do MP4 s H.264 a nahráš znova.": ["Prohlížeč neumí přehrát obraz tohoto videa (pravděpodobně kodek H.265/HEVC nebo 10bitový H.264). Titulky se rozpoznaly ze zvuku a můžeš je opravit a stáhnout jako SRT nebo VTT. Náhledy a export videa nejsou k dispozici. Zkus Safari nebo Chrome na Macu (HEVC přehrávají), nebo video převeď do MP4 s H.264 a nahraj znovu.", "The browser can't play this video's picture (probably H.265/HEVC or 10-bit H.264). Subtitles were recognized from the audio and you can fix them and download them as SRT or VTT. Previews and video export aren't available. Try Safari or Chrome on a Mac (they play HEVC), or convert the video to MP4 with H.264 and upload it again."],
  "Video sa nepodarilo načítať.": ["Video se nepodařilo načíst.", "Couldn't load the video."],
  "Tento prehliadač nevie nahrávať video. Použi Chrome, Edge alebo Firefox.": ["Tento prohlížeč neumí nahrávat video. Použij Chrome, Edge nebo Firefox.", "This browser can't record video. Use Chrome, Edge or Firefox."],
  // ---- náhľad a časová os ----
  "Krátke video": ["Krátké video", "Short video"], "Príspevok": ["Příspěvek", "Post"], "Web": ["Web", "Web"],
  "▶ Prehrať": ["▶ Přehrát", "▶ Play"], "■ Stop": ["■ Stop", "■ Stop"],
  "Exportovať a stiahnuť": ["Exportovat a stáhnout", "Export and download"], "Stiahnuť hotové video": ["Stáhnout hotové video", "Download finished video"],
  "Titulky presunieš ťahaním v náhľade. Posunie sa poloha rečníka, ktorého titulok práve vidíš.": ["Titulky přesuneš tažením v náhledu. Posune se pozice mluvčího, jehož titulek právě vidíš.", "Drag subtitles in the preview to move them. It moves the position of the speaker whose subtitle you see."],
  "Ťahaním v náhľade posúvaš video, kolieskom myši ho približuješ. Prehrávanie preskakuje vystrihnuté časti a ukazuje prechody ako v exporte.": ["Tažením v náhledu posouváš video, kolečkem myši ho přibližuješ. Přehrávání přeskakuje vystřižené části a ukazuje přechody jako v exportu.", "Drag in the preview to move the video, use the mouse wheel to zoom. Playback skips cut parts and shows transitions as in the export."],
  "Export beží v reálnom čase, nechaj kartu otvorenú.": ["Export běží v reálném čase, nech kartu otevřenou.", "Export runs in real time, keep the tab open."],
  "Prehrať alebo pozastaviť": ["Přehrát nebo pozastavit", "Play or pause"], "Prehrať alebo zastaviť": ["Přehrát nebo zastavit", "Play or stop"], "Prehrať / zastaviť (medzerník)": ["Přehrát / zastavit (mezerník)", "Play / stop (space)"],
  "Rýchlosť prehrávania": ["Rychlost přehrávání", "Playback speed"], "Rýchlosť náhľadu": ["Rychlost náhledu", "Preview speed"],
  "Oddialiť časovú os": ["Oddálit časovou osu", "Zoom out timeline"], "Priblížiť časovú os": ["Přiblížit časovou osu", "Zoom in timeline"], "Priblíženie časovej osi": ["Přiblížení časové osy", "Timeline zoom"],
  "Ťahaním v páse titulkov vyberieš úsek (alebo klik a Shift+klik na titulky), potom mu priradíš rečníka a štýl. Titulok presunieš ťahaním, ťahaním za okraj upravíš začiatok alebo koniec. Klik na pravítko alebo vlnovku pretočí video, medzerník spustí a zastaví prehrávanie.": ["Tažením v pásu titulků vybereš úsek (nebo klik a Shift+klik na titulky), pak mu přiřadíš mluvčího a styl. Titulek přesuneš tažením, tažením za okraj upravíš začátek nebo konec. Klik na pravítko nebo vlnovku přetočí video, mezerník spustí a zastaví přehrávání.", "Drag across the subtitle lane to select a range (or click and Shift+click subtitles), then assign a speaker and style. Drag a subtitle to move it, drag its edge to change the start or end. Click the ruler or waveform to seek, space plays and pauses."],
  "Zrušiť výber": ["Zrušit výběr", "Clear selection"], "Rečník": ["Mluvčí", "Speaker"], "Štýl": ["Styl", "Style"],
  "Podľa rečníka": ["Podle mluvčího", "By speaker"], "Zrušiť vlastný štýl, použiť štýl rečníka": ["Zrušit vlastní styl, použít styl mluvčího", "Remove custom style, use the speaker's style"],
  "Pridať nového rečníka a priradiť mu vybrané titulky": ["Přidat nového mluvčího a přiřadit mu vybrané titulky", "Add a new speaker and assign the selected subtitles"],
  "Nový": ["Nový", "New"],
  "Text titulkov": ["Text titulků", "Subtitle text"], "Oprav chyby v rozpoznanom texte. Klik na čas skočí na dané miesto vo videu.": ["Oprav chyby v rozpoznaném textu. Klik na čas skočí na dané místo ve videu.", "Fix mistakes in the recognized text. Click a time to jump there in the video."],
  "Hľadať v titulkoch": ["Hledat v titulcích", "Search subtitles"], "Hľadať v texte titulkov…": ["Hledat v textu titulků…", "Search subtitle text…"],
  "Klik: prehrať odtiaľto a vybrať · Shift+klik: vybrať úsek": ["Klik: přehrát odsud a vybrat · Shift+klik: vybrat úsek", "Click: play from here and select · Shift+click: select range"],
  "Vystrihnuté": ["Vystřiženo", "Cut"],
  // ---- panel titulkov ----
  "Titulky": ["Titulky", "Subtitles"], "Titulky zapnuté": ["Titulky zapnuté", "Subtitles on"],
  "Rečníci": ["Mluvčí", "Speakers"], "Pridať": ["Přidat", "Add"], "Pridať rečníka": ["Přidat mluvčího", "Add speaker"],
  "Rozpoznať rečníkov podľa hlasu": ["Rozpoznat mluvčí podle hlasu", "Detect speakers by voice"], "Počúvam hlasy…": ["Poslouchám hlasy…", "Listening to voices…"],
  "Hlavný": ["Hlavní", "Main"], "Meno rečníka": ["Jméno mluvčího", "Speaker name"], "Klikni a prepíš meno": ["Klikni a přepiš jméno", "Click to rename"],
  "Odobrať rečníka (jeho titulky prejdú na hlavného)": ["Odebrat mluvčího (jeho titulky přejdou na hlavního)", "Remove speaker (their subtitles go to the main speaker)"],
  "Nový rečník": ["Nový mluvčí", "New speaker"],
  "Všetky titulky patria jednému rečníkovi. Ak vo videu rozpráva viac ľudí, pridaj ďalšieho rečníka s vlastným štýlom.": ["Všechny titulky patří jednomu mluvčímu. Pokud ve videu mluví víc lidí, přidej dalšího mluvčího s vlastním stylem.", "All subtitles belong to one speaker. If more people talk in the video, add another speaker with their own style."],
  "Štýl a pozícia nižšie platia pre vybraného rečníka. Rečníka titulku zmeníš značkou pri texte alebo výberom úseku na časovej osi.": ["Styl a pozice níže platí pro vybraného mluvčího. Mluvčího titulku změníš štítkem u textu nebo výběrem úseku na časové ose.", "The style and position below apply to the selected speaker. Change a subtitle's speaker with the tag next to the text or by selecting a range on the timeline."],
  "Rozpoznané 2 hlasy. Skontroluj priradenie v zozname titulkov, zlé zmeníš značkou pri texte.": ["Rozpoznány 2 hlasy. Zkontroluj přiřazení v seznamu titulků, špatné změníš štítkem u textu.", "2 voices detected. Check the assignment in the subtitle list and fix wrong ones with the tag next to the text."],
  "Rozpoznaný len jeden hlas. Ďalšieho rečníka môžeš pridať ručne tlačidlom Pridať.": ["Rozpoznán jen jeden hlas. Dalšího mluvčího můžeš přidat ručně tlačítkem Přidat.", "Only one voice detected. You can add another speaker manually with Add."],
  "Pozícia": ["Pozice", "Position"], "Hore": ["Nahoře", "Top"], "Stred": ["Střed", "Middle"], "Dole": ["Dole", "Bottom"],
  "Titulky môžeš aj potiahnuť priamo v náhľade.": ["Titulky můžeš také přetáhnout přímo v náhledu.", "You can also drag subtitles right in the preview."],
  "Vzhľad (veľkosť)": ["Vzhled (velikost)", "Appearance (size)"], "Veľkosť titulkov": ["Velikost titulků", "Subtitle size"],
  "Farba a font": ["Barva a font", "Color and font"], "Farba textu": ["Barva textu", "Text color"], "Font": ["Font", "Font"],
  "Nahrať vlastný font": ["Nahrát vlastní font", "Upload your own font"], "Všetky fonty s ukážkou v Značke →": ["Všechny fonty s ukázkou ve Značce →", "All fonts with previews in Brand →"],
  "Súbor TTF, OTF, WOFF alebo WOFF2. Font ostane len v tomto prehliadači.": ["Soubor TTF, OTF, WOFF nebo WOFF2. Font zůstane jen v tomto prohlížeči.", "A TTF, OTF, WOFF or WOFF2 file. The font stays only in this browser."],
  "Vlastný font nahráš v Značke, bude dostupný pre všetkých.": ["Vlastní font nahraješ ve Značce, bude dostupný pro všechny.", "Upload your own font in Brand, it will be available to everyone."],
  "Podklad": ["Podklad", "Background"], "Priehľadnosť podkladu": ["Průhlednost podkladu", "Background opacity"], "0 % = bez podkladu, text dostane obrys.": ["0 % = bez podkladu, text dostane obrys.", "0% = no background, the text gets an outline."],
  "Pokročilé": ["Pokročilé", "Advanced"], "Zvýrazňovať práve vyslovené slovo": ["Zvýrazňovat právě vyslovené slovo", "Highlight the word being spoken"], "Farba zvýraznenia": ["Barva zvýraznění", "Highlight color"],
  "Dynamický štýl: meniť polohu pri každej fráze": ["Dynamický styl: měnit pozici u každé fráze", "Dynamic style: change position for each phrase"],
  "Dynamický štýl: VEĽKÉ PÍSMENA (kapitálky)": ["Dynamický styl: VELKÁ PÍSMENA", "Dynamic style: CAPITAL LETTERS"],
  "Vlastný štýl (upravený)": ["Vlastní styl (upravený)", "Custom style (edited)"],
  // názvy štýlov
  "Modern": ["Moderní", "Modern"], "Klasik": ["Klasik", "Classic"], "Kino": ["Kino", "Cinema"], "Výrazný": ["Výrazný", "Bold"], "Jemný": ["Jemný", "Soft"], "Svetlý": ["Světlý", "Light"], "PS blue": ["PS blue", "PS blue"], "Dynamický": ["Dynamický", "Dynamic"],
  "Ahoj svet": ["Ahoj světe", "Hello world"], "Ahoj": ["Ahoj", "Hello"], "svet": ["světe", "world"],
  // ---- úprava a strih ----
  "Úprava videa": ["Úpravy videa", "Video editing"], "Pomer strán": ["Poměr stran", "Aspect ratio"],
  "Rozpoznané z nahraného videa. Zmeň ikonkou, výrez videa nastavíš nižšie.": ["Rozpoznáno z nahraného videa. Změň ikonkou, výřez videa nastavíš níže.", "Detected from the uploaded video. Change it with the icons, set the crop below."],
  "Výrez a posun videa": ["Výřez a posun videa", "Crop and position"], "Priblíženie": ["Přiblížení", "Zoom"], "Priblíženie videa": ["Přiblížení videa", "Video zoom"],
  "Obnoviť orez vybraného formátu": ["Obnovit ořez vybraného formátu", "Reset crop for this format"],
  "Ťahaním po náhľade posúvaš video, kolieskom myši alebo posuvníkom približuješ. Orez platí len pre vybraný formát.": ["Tažením po náhledu posouváš video, kolečkem myši nebo posuvníkem přibližuješ. Ořez platí jen pro vybraný formát.", "Drag on the preview to move the video, zoom with the mouse wheel or the slider. The crop applies only to the selected format."],
  "Inšpektor strihu": ["Inspektor střihu", "Cut inspector"],
  "Vyber klip na časovej osi a uprav jeho rýchlosť, efekt a filter. Šedý štvorček medzi klipmi otvorí prechod.": ["Vyber klip na časové ose a uprav jeho rychlost, efekt a filtr. Šedý čtvereček mezi klipy otevře přechod.", "Select a clip on the timeline to adjust its speed, effect and filter. The grey square between clips opens the transition."],
  "Nástroje strihu": ["Nástroje střihu", "Cut tools"], "Rozdeliť": ["Rozdělit", "Split"], "Vymazať": ["Smazat", "Delete"],
  "Rozdeliť klip na prehrávacej hlave (S)": ["Rozdělit klip na přehrávací hlavě (S)", "Split clip at the playhead (S)"],
  "Vymazať vybraný klip, medzera sa zatvorí (Del)": ["Smazat vybraný klip, mezera se zavře (Del)", "Delete the selected clip, the gap closes (Del)"],
  "Späť (Ctrl+Z)": ["Zpět (Ctrl+Z)", "Undo (Ctrl+Z)"], "Znova (Ctrl+Shift+Z)": ["Znovu (Ctrl+Shift+Z)", "Redo (Ctrl+Shift+Z)"],
  "Video 1": ["Video 1", "Video 1"], "Audio 1": ["Audio 1", "Audio 1"],
  "Klik na klip ho vyberie, ťahaním ho presunieš. Oranžové okraje vybraného klipu ho orežú. Štvorček medzi klipmi pridá prechod. S = rozdeliť, Del = vymazať.": ["Klik na klip ho vybere, tažením ho přesuneš. Oranžové okraje vybraného klipu ho ořežou. Čtvereček mezi klipy přidá přechod. S = rozdělit, Del = smazat.", "Click a clip to select it, drag to move it. The orange edges of the selected clip trim it. The square between clips adds a transition. S = split, Del = delete."],
  "Ťahaním orežeš začiatok": ["Tažením ořízneš začátek", "Drag to trim the start"], "Ťahaním orežeš koniec": ["Tažením ořízneš konec", "Drag to trim the end"],
  "Pridať prechod": ["Přidat přechod", "Add transition"],
  "Prehrávacia hlava nie je na žiadnom klipe.": ["Přehrávací hlava není na žádném klipu.", "The playhead isn't on any clip."],
  "Tu sa rozdeliť nedá, je to príliš blízko okraja klipu.": ["Tady se rozdělit nedá, je to příliš blízko okraje klipu.", "Can't split here, it's too close to the clip edge."],
  "Rozdelené. Vybraný je klip za strihom: vymaž ho, presuň, alebo mu daj efekt.": ["Rozděleno. Vybraný je klip za střihem: smaž ho, přesuň, nebo mu dej efekt.", "Split. The clip after the cut is selected: delete it, move it or add an effect."],
  "Posledný klip vymazať nejde. Kratší ho spravíš ťahaním za biele okraje.": ["Poslední klip smazat nejde. Kratší ho uděláš tažením za okraje.", "The last clip can't be deleted. Shorten it by dragging its edges."],
  "Klip je vymazaný, ostatné sa posunuli k sebe. Späť ho vrátiš tlačidlom Späť.": ["Klip je smazaný, ostatní se posunuly k sobě. Zpět ho vrátíš tlačítkem Zpět.", "Clip deleted, the others moved together. Bring it back with Undo."],
  "Klip je orezaný.": ["Klip je oříznutý.", "Clip trimmed."], "Vrátené späť.": ["Vráceno zpět.", "Undone."], "Znova použité.": ["Znovu použito.", "Redone."],
  "Video je opäť celé a bez efektov. Späť to vrátiš tlačidlom Späť.": ["Video je opět celé a bez efektů. Zpět to vrátíš tlačítkem Zpět.", "The video is whole again and without effects. Undo brings it back."],
  "Rýchlosť": ["Rychlost", "Speed"], "Rýchlosť klipu": ["Rychlost klipu", "Clip speed"], "Efekt": ["Efekt", "Effect"], "Efekt klipu": ["Efekt klipu", "Clip effect"],
  "Filter": ["Filtr", "Filter"], "Filter klipu": ["Filtr klipu", "Clip filter"], "Na všetky klipy": ["Na všechny klipy", "Apply to all clips"],
  "Prechod": ["Přechod", "Transition"], "Na všetky prechody": ["Na všechny přechody", "Apply to all transitions"], "Dĺžka prechodu": ["Délka přechodu", "Transition length"],
  "Dĺžka výsledného videa": ["Délka výsledného videa", "Final video length"], "Obnoviť pôvodné video": ["Obnovit původní video", "Restore original video"],
  "Žiadny": ["Žádný", "None"], "Stmievačka": ["Stmívačka", "Fade"], "Záblesk": ["Záblesk", "Flash"], "Rozmazanie": ["Rozmazání", "Blur"], "Švih": ["Švih", "Whip"], "Otočenie": ["Otočení", "Spin"], "Glitch": ["Glitch", "Glitch"],
  "Zoom": ["Zoom", "Zoom"], "Pulz": ["Pulz", "Pulse"], "Otras": ["Otřes", "Shake"], "VHS": ["VHS", "VHS"],
  "Pôvodný": ["Původní", "Original"], "Živý": ["Živý", "Vivid"], "Teplý": ["Teplý", "Warm"], "Studený": ["Studený", "Cool"], "Čiernobiely": ["Černobílý", "B&W"], "Vintage": ["Vintage", "Vintage"], "Film": ["Film", "Film"],
  // ---- export ----
  "Kvalita videa": ["Kvalita videa", "Video quality"], "Kvalita exportu": ["Kvalita exportu", "Export quality"], "Originál": ["Originál", "Original"], "do 1080p": ["do 1080p", "up to 1080p"], "Menší súbor": ["Menší soubor", "Smaller file"],
  "Video s titulkami": ["Video s titulky", "Video with subtitles"], "Exportovať tento formát": ["Exportovat tento formát", "Export this format"],
  "Exportovať všetky formáty (9:16, 1:1, 16:9)": ["Exportovat všechny formáty (9:16, 1:1, 16:9)", "Export all formats (9:16, 1:1, 16:9)"], "Exportovať všetko": ["Exportovat vše", "Export all"],
  "Zrušiť export": ["Zrušit export", "Cancel export"], "Export je zrušený.": ["Export je zrušený.", "Export cancelled."], "Export zlyhal.": ["Export selhal.", "Export failed."],
  "Pripravujem…": ["Připravuji…", "Preparing…"], "Hotovo, stiahni tlačidlom ⬇": ["Hotovo, stáhni tlačítkem ⬇", "Done, download with ⬇"],
  "Len titulky": ["Jen titulky", "Subtitles only"], "Stiahnuť SRT": ["Stáhnout SRT", "Download SRT"], "Stiahnuť VTT": ["Stáhnout VTT", "Download VTT"],
  "Nič sa neukladá na server. Po zatvorení stránky je všetko preč, preto si súbory stiahni.": ["Nic se neukládá na server. Po zavření stránky je vše pryč, proto si soubory stáhni.", "Nothing is stored on the server. When you close the page everything is gone, so download your files."],
  " 4K export trvá dlhšie a na slabších zariadeniach môže sekať.": [" Export 4K trvá déle a na slabších zařízeních se může sekat.", " 4K export takes longer and may stutter on weaker devices."],
  " Priblížený výrez používa menej pixelov originálu, obraz môže byť menej ostrý.": [" Přiblížený výřez používá méně pixelů originálu, obraz může být méně ostrý.", " A zoomed crop uses fewer pixels of the original, the picture may be less sharp."],
  // ---- knižnica a značka ----
  "Knižnica štýlov": ["Knihovna stylů", "Style library"],
  "Všetky štýly titulkov, ktoré Titulkovač má, v pohybe. Klikni na Použiť a štýl sa nastaví pre vybraného rečníka.": ["Všechny styly titulků, které Titulkovač má, v pohybu. Klikni na Použít a styl se nastaví pro vybraného mluvčího.", "All subtitle styles in motion. Click Use to set the style for the selected speaker."],
  "← Späť na video": ["← Zpět na video", "← Back to video"], "Použiť": ["Použít", "Use"], "Použitý": ["Použitý", "In use"],
  "Takto budú vyzerať": ["Takhle budou vypadat", "This is how"], "tvoje titulky vo videu": ["tvoje titulky ve videu", "your subtitles will look"],
  "Fonty pre titulky. Nájdeš ich aj vo výbere fontu pri štýle titulkov.": ["Fonty pro titulky. Najdeš je i ve výběru fontu u stylu titulků.", "Fonts for subtitles. You'll also find them in the font picker in the subtitle style."],
  "Fonty pre titulky. Nahraté fonty ostanú len v tomto prehliadači a nájdeš ich aj vo výbere fontu pri štýle titulkov.": ["Fonty pro titulky. Nahrané fonty zůstanou jen v tomto prohlížeči a najdeš je i ve výběru fontu u stylu titulků.", "Fonts for subtitles. Uploaded fonts stay only in this browser; you'll also find them in the font picker."],
  "Spoločné fonty pre titulky. Nahraný font uvidia a môžu použiť všetci používatelia. Z jednej IP adresy sa dá nahrať jeden font za týždeň.": ["Společné fonty pro titulky. Nahraný font uvidí a mohou použít všichni uživatelé. Z jedné IP adresy lze nahrát jeden font za týden.", "Shared subtitle fonts. Every user can see and use an uploaded font. One font per week can be uploaded from one IP address."],
  "Pridať font": ["Přidat font", "Add font"], "— pretiahni sem súbor alebo klikni": ["— přetáhni sem soubor nebo klikni", "— drop a file here or click"],
  "TTF, OTF, WOFF alebo WOFF2": ["TTF, OTF, WOFF nebo WOFF2", "TTF, OTF, WOFF or WOFF2"], "TTF, OTF, WOFF alebo WOFF2, najviac 15 MB": ["TTF, OTF, WOFF nebo WOFF2, nejvýše 15 MB", "TTF, OTF, WOFF or WOFF2, up to 15 MB"],
  "Potvrdzujem, že tento font môžem zdieľať (je voľne šíriteľný alebo mám naň práva). Bude dostupný všetkým používateľom.": ["Potvrzuji, že tento font mohu sdílet (je volně šiřitelný nebo k němu mám práva). Bude dostupný všem uživatelům.", "I confirm I may share this font (it's freely distributable or I hold the rights). It will be available to all users."],
  "Text ukážky": ["Text ukázky", "Sample text"], "Ahoj, toto sú moje titulky": ["Ahoj, tohle jsou moje titulky", "Hello, these are my subtitles"],
  "Vstavaný": ["Vestavěný", "Built-in"], "Vlastný": ["Vlastní", "Custom"], "Spoločný": ["Společný", "Shared"], "Odobrať font": ["Odebrat font", "Remove font"],
  "Najprv potvrď, že font môžeš zdieľať so všetkými.": ["Nejdřív potvrď, že font můžeš sdílet se všemi.", "First confirm that you may share the font with everyone."],
  "Nahrávam font na server…": ["Nahrávám font na server…", "Uploading font…"], "Nahranie fontu zlyhalo.": ["Nahrání fontu selhalo.", "Font upload failed."],
  "Spojenie so serverom zlyhalo. Skús to znova.": ["Spojení se serverem selhalo. Zkus to znovu.", "Connection to the server failed. Try again."], "Server neodpovedal správne.": ["Server neodpověděl správně.", "The server responded incorrectly."],
  "Tento súbor sa nepodarilo načítať ako font.": ["Tento soubor se nepodařilo načíst jako font.", "This file couldn't be loaded as a font."], "Font je príliš veľký (najviac 15 MB).": ["Font je příliš velký (nejvýše 15 MB).", "The font is too large (max 15 MB)."],
  // chyby zo servera (index.php)
  "Toto nie je font. Prijímame len súbory TTF, OTF, WOFF a WOFF2.": ["Tohle není font. Přijímáme jen soubory TTF, OTF, WOFF a WOFF2.", "This isn't a font. Only TTF, OTF, WOFF and WOFF2 files are accepted."],
  "Font musí mať od 1 kB do 10 MB.": ["Font musí mít od 1 kB do 10 MB.", "The font must be between 1 kB and 10 MB."], "Font je príliš veľký.": ["Font je příliš velký.", "The font is too large."],
  "Súbor sa nepodarilo nahrať.": ["Soubor se nepodařilo nahrát.", "The file couldn't be uploaded."], "Knižnica fontov je plná.": ["Knihovna fontů je plná.", "The font library is full."],
  "Font sa nepodarilo uložiť na server.": ["Font se nepodařilo uložit na server.", "The font couldn't be saved on the server."], "Neplatná požiadavka.": ["Neplatný požadavek.", "Invalid request."],
  "Server nevie vytvoriť priečinok data/. Nastav mu práva na zápis.": ["Server neumí vytvořit složku data/. Nastav mu práva k zápisu.", "The server can't create the data/ folder. Give it write permission."],
  // vzorové titulky v ukážke
  "Ahoj, vitajte pri ukážke aplikácie.": ["Ahoj, vítejte u ukázky aplikace.", "Hi, welcome to the app demo."],
  "Nahráte video a titulky sa vytvoria automaticky.": ["Nahrajete video a titulky se vytvoří automaticky.", "Upload a video and subtitles are created automatically."],
  "Vyberiete štýl z ponuky,": ["Vyberete styl z nabídky,", "Pick a style from the menu,"], "zmeníte farbu textu, font alebo podklad,": ["změníte barvu textu, font nebo podklad,", "change the text color, font or background,"],
  "a práve vyslovené slovo sa zvýrazní.": ["a právě vyslovené slovo se zvýrazní.", "and the spoken word gets highlighted."],
};
// Texty s premennými časťami: [vzor, [čeština, angličtina]]; $1… sa doplní (a ak je v slovníku, aj preloží).
const pl = (n, one, few, many) => (+n === 1 ? one : +n > 1 && +n < 5 ? few : many);
const PATTERNS = [
  [/^Klip (\d+)$/, ["Klip $1", "Clip $1"]],
  [/^(.+) \((\d+:\d+)\)$/, ["$1 ($2)", "$1 ($2)"]],
  [/^Klip (\d+) z (\d+)$/, ["Klip $1 z $2", "Clip $1 of $2"]],
  [/^Klip (\d+), (.+)$/, ["Klip $1, $2", "Clip $1, $2"]],
  [/^(\d+) (klip|klipy|klipov)$/, [(r) => r[1] + " " + pl(r[1], "klip", "klipy", "klipů"), (r) => r[1] + (r[1] === "1" ? " clip" : " clips")]],
  [/^(\d+) (titulok|titulky|titulkov)$/, [(r) => r[1] + " " + pl(r[1], "titulek", "titulky", "titulků"), (r) => r[1] + (r[1] === "1" ? " subtitle" : " subtitles")]],
  [/^Rečník (\d+)$/, ["Mluvčí $1", "Speaker $1"]],
  [/^Prechod: klip (\d+) → (\d+)$/, ["Přechod: klip $1 → $2", "Transition: clip $1 → $2"]],
  [/^Prechod: (.+)$/, ["Přechod: $1", "Transition: $1"]],
  [/^Štýl — (.+)$/, ["Styl — $1", "Style — $1"]],
  [/^Štýl (.+) pre vybrané titulky$/, ["Styl $1 pro vybrané titulky", "Style $1 for selected subtitles"]],
  [/^Štýl (.+)$/, ["Styl $1", "Style $1"]],
  [/^Pozícia — (.+) · (.+)$/, ["Pozice — $1 · $2", "Position — $1 · $2"]],
  [/^Pozícia · (.+)$/, ["Pozice · $1", "Position · $1"]],
  [/^Odobrať font (.+)$/, ["Odebrat font $1", "Remove font $1"]],
  [/^Odobrať (.+)$/, ["Odebrat $1", "Remove $1"]],
  [/^Animovaná ukážka štýlu (.+)$/, ["Animovaná ukázka stylu $1", "Animated preview of style $1"]],
  [/^Text titulku (\d+)$/, ["Text titulku $1", "Subtitle text $1"]],
  [/^Náhľad (.+)$/, ["Náhled $1", "Preview $1"]],
  [/^Exportovať a stiahnuť (.+)$/, ["Exportovat a stáhnout $1", "Export and download $1"]],
  [/^(.+) · klikni a vyber iného rečníka$/, ["$1 · klikni a vyber jiného mluvčího", "$1 · click to choose another speaker"]],
  [/^Vybraté: (\d+) \S+ · (.+)$/, [(r) => `Vybráno: ${r[1]} ${pl(r[1], "titulek", "titulky", "titulků")} · ${r[2]}`, (r) => `Selected: ${r[1]} ${r[1] === "1" ? "subtitle" : "subtitles"} · ${r[2]}`]],
  [/^Exportujem… (\d+) %$/, ["Exportuji… $1 %", "Exporting… $1%"]],
  [/^Exportujem formát (\d+) z (\d+) \((.+)\)…(?: (\d+) %)?$/, [(r) => `Exportuji formát ${r[1]} z ${r[2]} (${r[3]})…` + (r[4] ? ` ${r[4]} %` : ""), (r) => `Exporting format ${r[1]} of ${r[2]} (${r[3]})…` + (r[4] ? ` ${r[4]}%` : "")]],
  [/^Formát (\S+): (\d+)×(\d+) px, snímky za sekundu ako v origináli\.(.*)$/, [(r) => `Formát ${r[1]}: ${r[2]}×${r[3]} px, snímky za sekundu jako v originálu.` + trParts(r[4]), (r) => `Format ${r[1]}: ${r[2]}×${r[3]} px, frame rate as in the original.` + trParts(r[4])]],
  [/^Klip ide rýchlosťou (.+), trvá (.+)\.$/, ["Klip běží rychlostí $1, trvá $2.", "The clip plays at $1 and lasts $2."]],
  [/^Klip je presunutý na (\d+)\. miesto\.$/, ["Klip je přesunutý na $1. místo.", "Clip moved to position $1."]],
  [/^Efekt (.+) je na všetkých klipoch\.$/, ["Efekt $1 je na všech klipech.", "Effect $1 is on all clips."]],
  [/^Filter (.+) je na všetkých klipoch\.$/, ["Filtr $1 je na všech klipech.", "Filter $1 is on all clips."]],
  [/^Prechod (.+) je medzi všetkými klipmi\.$/, ["Přechod $1 je mezi všemi klipy.", "Transition $1 is between all clips."]],
  [/^(\d+:\d+) z (\d+:\d+)$/, ["$1 z $2", "$1 of $2"]],
  [/^Font „(.+)“ je nahraný a dostupný pre všetkých\.$/, ["Font „$1“ je nahraný a dostupný pro všechny.", "Font “$1” is uploaded and available to everyone."]],
  [/^Font „(.+)“ je odobratý\.$/, ["Font „$1“ je odebraný.", "Font “$1” removed."]],
  [/^Font „(.+)“ je pridaný a vybraný\.$/, ["Font „$1“ je přidaný a vybraný.", "Font “$1” added and selected."]],
  [/^Font „(.+)“ je pridaný\.$/, ["Font „$1“ je přidaný.", "Font “$1” added."]],
  [/^Tento font už v knižnici je ako „(.+)“\. Limit sa ti nezapočítal\.$/, ["Tento font už v knihovně je jako „$1“. Limit se ti nezapočítal.", "This font is already in the library as “$1”. It didn't count toward your limit."]],
  [/^Titulky(?: rečníka (.+))? teraz používajú font „(.+)“\.$/, [(r) => `Titulky${r[1] ? " mluvčího " + r[1] : ""} teď používají font „${r[2]}“.`, (r) => `Subtitles${r[1] ? " of speaker " + r[1] : ""} now use the font “${r[2]}”.`]],
  [/^Z tvojej IP adresy už bol tento týždeň nahraný font\. Ďalší môžeš nahrať (.+)\.$/, ["Z tvé IP adresy už byl tento týden nahrán font. Další můžeš nahrát $1.", "A font was already uploaded from your IP address this week. You can upload another on $1."]],
  [/^Len súbory fontov TTF, OTF, WOFF alebo WOFF2, najviac (\d+) MB · 1 font za týždeň$/, ["Jen soubory fontů TTF, OTF, WOFF nebo WOFF2, nejvýše $1 MB · 1 font za týden", "Font files only: TTF, OTF, WOFF or WOFF2, up to $1 MB · 1 font per week"]],
  [/^Font je príliš veľký \(najviac (\d+) MB\)\.$/, ["Font je příliš velký (nejvýše $1 MB).", "The font is too large (max $1 MB)."]],
  [/^„(.+)“ nie je font\. .+$/, ["„$1“ není font. Přijímáme jen soubory TTF, OTF, WOFF a WOFF2.", "“$1” isn't a font. Only TTF, OTF, WOFF and WOFF2 files are accepted."]],
  [/^Prehliadač číta zvuk v reálnom čase: (.+) z (.+)$/, ["Prohlížeč čte zvuk v reálném čase: $1 z $2", "The browser reads the audio in real time: $1 of $2"]],
  [/^(.+): (\d+) z (\d+) MB \(len pri prvom použití\)$/, ["$1: $2 z $3 MB (jen při prvním použití)", "$1: $2 of $3 MB (first use only)"]],
  [/^Model sa nepodarilo načítať\. (.*) Technická chyba: (.+)$/, [(r) => "Model se nepodařilo načíst. " + trParts(r[1]) + " Technická chyba: " + r[2], (r) => "Couldn't load the model. " + trParts(r[1]) + " Technical error: " + r[2]]],
];
function trParts(s) { // preloží text po vetách (pre texty poskladané z viacerých viet)
  if (!s) return s || "";
  const lead = s.match(/^\s*/)[0];
  return lead + s.trim().split(/(?<=[.!?])\s+/).map((x) => tr(x)).join(" ");
}
function tr(s) {
  if (LANG === "sk" || !s) return s;
  const d = DICT[s]; if (d) return d[LI];
  for (const [re, out] of PATTERNS) {
    const r = s.match(re); if (!r) continue;
    const o = out[LI];
    return typeof o === "function" ? o(r) : o.replace(/\$(\d)/g, (_, k) => { const g = r[+k] ?? ""; return DICT[g] ? DICT[g][LI] : g; });
  }
  return s;
}
// Preklad stránky: statické texty hneď, zmenené texty a nové prvky priebežne.
const I18N_SKIP = ".chip-label, .fsample, textarea, script, style, [data-noi18n]";
const trSkip = (el) => !!(el && el.closest && el.closest(I18N_SKIP));
// Pri každom preloženom texte sa pamätá slovenský originál, aby sa dal jazyk prepnúť aj späť za behu.
const ORIG = new WeakMap();
function trTextNode(n) {
  const v = n.nodeValue; if (!v || !/[A-Za-zÀ-ž]{2}/.test(v) || trSkip(n.parentElement)) return;
  const rec = ORIG.get(n), src = rec && rec.out === v ? rec.src : v;
  const t = src.trim(), out = src.replace(t, tr(t));
  if (out !== src || rec) ORIG.set(n, { src, out });
  if (out !== v) n.nodeValue = out;
}
function trAttrs(el) {
  if (trSkip(el)) return;
  let recs = ORIG.get(el);
  for (const a of ["title", "aria-label", "placeholder"]) {
    const v = el.getAttribute(a); if (!v) continue;
    const rec = recs && recs[a], src = rec && rec.out === v ? rec.src : v, out = tr(src);
    if (out !== src || rec) { if (!recs) ORIG.set(el, (recs = {})); recs[a] = { src, out }; }
    if (out !== v) el.setAttribute(a, out);
  }
}
function trTree(root) {
  if (root.nodeType === 3) { trTextNode(root); return; }
  if (root.nodeType !== 1 || trSkip(root)) return;
  trAttrs(root);
  const w = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT, { acceptNode: (n) => (n.nodeType === 1 && n.matches(I18N_SKIP) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT) });
  for (let n = w.nextNode(); n; n = w.nextNode()) { if (n.nodeType === 3) trTextNode(n); else trAttrs(n); }
}
const TITLE_SK = document.title;
function setLang(l) { // prepnutie jazyka rozhrania za behu (z Nastavení)
  LANG = UI_LANGS[l] ? l : "sk"; setLangVars();
  try { localStorage.setItem("titulky-lang", LANG); } catch {}
  document.documentElement.lang = LANG; document.title = tr(TITLE_SK);
  trTree(document.body);
  document.dispatchEvent(new Event("langchange"));
}
document.documentElement.lang = LANG;
document.title = tr(TITLE_SK);
if (LANG !== "sk") trTree(document.body);
{
  new MutationObserver((ms) => {
    for (const m of ms) {
      if (m.type === "characterData") trTextNode(m.target);
      else if (m.type === "attributes") trAttrs(m.target);
      else m.addedNodes.forEach(trTree);
    }
  }).observe(document.body, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ["title", "aria-label", "placeholder"] });
}
