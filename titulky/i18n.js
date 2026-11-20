// ---------- jazyk rozhrania podľa prehliadača ----------
// Zdrojové texty sú po slovensky. Pre iný jazyk prehliadača sa texty v stránke preložia slovníkom nižšie,
// aj tie, ktoré aplikácia zmení počas behu (MutationObserver). Čeština a angličtina; iné jazyky dostanú angličtinu.
// Poradie: ?lang=… v adrese, potom voľba v Nastaveniach (uložená v prehliadači), potom jazyk prehliadača.
const UI_LANGS = { sk: "Slovenčina", cs: "Čeština", en: "English", es: "Español", ar: "العربية", tr: "Türkçe", hi: "हिन्दी" };
let savedLang = null; try { savedLang = localStorage.getItem("titulky-lang"); } catch {}
let LANG = (() => {
  const q = new URLSearchParams(location.search).get("lang");
  for (const l of [q, savedLang, ...(navigator.languages || []), navigator.language]) {
    const c = String(l || "").slice(0, 2).toLowerCase();
    if (UI_LANGS[c]) return c;
    if (c === "cz") return "cs";
  }
  return "en";
})();
let LOCALE, LI;
const LANG_COL = { cs: 0, en: 1, es: 2, ar: 3, tr: 4, hi: 5 }; // stĺpec v slovníku
const setLangVars = () => { LOCALE = { sk: "sk-SK", cs: "cs-CZ", en: "en-GB", es: "es-ES", ar: "ar-AE", tr: "tr-TR", hi: "hi-IN" }[LANG]; LI = LANG_COL[LANG] ?? 1; document.documentElement.dir = LANG === "ar" ? "rtl" : "ltr"; };
setLangVars();
const decTxt = (v) => String(v).replace(".", ["sk", "cs", "es", "tr"].includes(LANG) ? "," : ".");
// Slovník: slovenský text → [čeština, angličtina, španielčina, arabčina, turečtina, hindčina]
const DICT = {
  "Titulky do videa": ["Titulky do videa","Video Subtitles","Subtítulos para vídeo","ترجمة الفيديو","Video Altyazıları","वीडियो सबटाइटल"],
  "Zmeň svoj obsah na formáty pripravené na zverejnenie a pridaj automatické titulky.": ["Převeď svůj obsah do formátů připravených ke zveřejnění a přidej automatické titulky.","Turn your content into ready-to-post formats and add automatic subtitles.","Convierte tu contenido en formatos listos para publicar y añade subtítulos automáticos.","حوّل محتواك إلى تنسيقات جاهزة للنشر وأضف ترجمة تلقائية.","İçeriğini paylaşıma hazır formatlara dönüştür ve otomatik altyazı ekle.","अपनी सामग्री को पोस्ट करने लायक फ़ॉर्मैट में बदलें और अपने आप सबटाइटल जोड़ें।"],
  "Ukážka: titulky sú vzorové, video ostáva vo vašom prehliadači.": ["Ukázka: titulky jsou vzorové, video zůstává ve vašem prohlížeči.","Demo: sample subtitles, the video stays in your browser.","Demo: subtítulos de ejemplo, el vídeo se queda en tu navegador.","عرض تجريبي: ترجمات نموذجية، والفيديو يبقى في متصفحك.","Demo: örnek altyazılar, video tarayıcında kalır.","डेमो: नमूना सबटाइटल, वीडियो आपके ब्राउज़र में ही रहता है।"],
  "+ Nový projekt": ["+ Nový projekt","+ New project","+ Nuevo proyecto","+ مشروع جديد","+ Yeni proje","+ नया प्रोजेक्ट"],
  "Titulkovanie": ["Titulkování","Subtitles","Subtítulos","الترجمة","Altyazılar","सबटाइटल"],
  "Knižnica": ["Knihovna","Library","Biblioteca","المكتبة","Kütüphane","लाइब्रेरी"],
  "Značka": ["Značka","Brand","Marca","العلامة","Marka","ब्रांड"],
  "Plán": ["Plán","Plan","Plan","الخطة","Plan","प्लान"],
  "Nastavenia": ["Nastavení","Settings","Ajustes","الإعدادات","Ayarlar","सेटिंग्स"],
  "Jazyk rozhrania": ["Jazyk rozhraní","Interface language","Idioma de la interfaz","لغة الواجهة","Arayüz dili","इंटरफ़ेस की भाषा"],
  "Pro": ["Pro","Pro","Pro","Pro","Pro","Pro"],
  "2 € mesačne": ["2 € měsíčně","€2 per month","2 € al mes","2 € شهريًا","Aylık 2 €","€2 प्रति माह"],
  "Bez limitu exportov": ["Bez limitu exportů","Unlimited exports","Exportaciones ilimitadas","تصدير بلا حدود","Sınırsız dışa aktarma","असीमित एक्सपोर्ट"],
  "Kroky": ["Kroky","Steps","Pasos","الخطوات","Adımlar","चरण"],
  "Nahratie": ["Nahrání","Upload","Subir","الرفع","Yükleme","अपलोड"],
  "Pridaj video": ["Přidej video","Add a video","Añade un vídeo","أضف فيديو","Video ekle","वीडियो जोड़ें"],
  "Text, rečníci, štýl": ["Text, mluvčí, styl","Text, speakers, style","Texto, hablantes, estilo","النص والمتحدثون والنمط","Metin, konuşmacılar, stil","टेक्स्ट, वक्ता, स्टाइल"],
  "Úprava": ["Úpravy","Edit","Edición","التحرير","Düzenleme","एडिट"],
  "Formát, výrez, strih": ["Formát, výřez, střih","Format, crop, cut","Formato, recorte, corte","التنسيق والقص والتقطيع","Format, kırpma, kesme","फ़ॉर्मैट, क्रॉप, कट"],
  "Export": ["Export","Export","Exportar","التصدير","Dışa aktar","एक्सपोर्ट"],
  "Stiahni súbory": ["Stáhni soubory","Download files","Descarga los archivos","نزّل الملفات","Dosyaları indir","फ़ाइलें डाउनलोड करें"],
  "1. Jazyk videa": ["1. Jazyk videa","1. Video language","1. Idioma del vídeo","1. لغة الفيديو","1. Video dili","1. वीडियो की भाषा"],
  "Slovenčina": ["Slovenština","Slovak","Eslovaco","السلوفاكية","Slovakça","स्लोवाक"],
  "Rozpoznať automaticky": ["Rozpoznat automaticky","Detect automatically","Detectar automáticamente","تعرّف تلقائي","Otomatik algıla","अपने आप पहचानें"],
  "Čeština": ["Čeština","Czech","Checo","التشيكية","Çekçe","चेक"],
  "Angličtina": ["Angličtina","English","Inglés","الإنجليزية","İngilizce","अंग्रेज़ी"],
  "2. Vyber video alebo ho pretiahni sem": ["2. Vyber video nebo ho přetáhni sem","2. Choose a video or drop it here","2. Elige un vídeo o arrástralo aquí","2. اختر فيديو أو اسحبه إلى هنا","2. Bir video seç veya buraya sürükle","2. वीडियो चुनें या यहाँ खींचकर छोड़ें"],
  "MP4, MOV, WEBM": ["MP4, MOV, WEBM","MP4, MOV, WEBM","MP4, MOV, WEBM","MP4, MOV, WEBM","MP4, MOV, WEBM","MP4, MOV, WEBM"],
  "MP4, MOV, WEBM · zostáva vo vašom prehliadači": ["MP4, MOV, WEBM · zůstává ve vašem prohlížeči","MP4, MOV, WEBM · stays in your browser","MP4, MOV, WEBM · se queda en tu navegador","MP4, MOV, WEBM · يبقى في متصفحك","MP4, MOV, WEBM · tarayıcında kalır","MP4, MOV, WEBM · आपके ब्राउज़र में ही रहता है"],
  "Rozpoznanie používa automaticky najpresnejší model, ktorý tvoj prehliadač zvládne.": ["Rozpoznávání automaticky používá nejpřesnější model, který tvůj prohlížeč zvládne.","Recognition automatically uses the most accurate model your browser can handle.","El reconocimiento usa automáticamente el modelo más preciso que tu navegador puede manejar.","يستخدم التعرّف تلقائيًا أدق نموذج يستطيع متصفحك تشغيله.","Tanıma, tarayıcının kaldırabileceği en doğru modeli otomatik olarak kullanır.","पहचान अपने आप सबसे सटीक मॉडल का उपयोग करती है जिसे आपका ब्राउज़र चला सकता है।"],
  "Model je uložený v tomto prehliadači a nesťahuje sa znova.": ["Model je uložený v tomto prohlížeči a znovu se nestahuje.","The model is saved in this browser and won't be downloaded again.","El modelo está guardado en este navegador y no se volverá a descargar.","النموذج محفوظ في هذا المتصفح ولن يُنزَّل مرة أخرى.","Model bu tarayıcıda kayıtlı ve tekrar indirilmeyecek.","मॉडल इस ब्राउज़र में सेव है और दोबारा डाउनलोड नहीं होगा।"],
  "Model je na serveri tejto stránky, sťahuje sa odtiaľ a ukladá sa v prehliadači.": ["Model je na serveru této stránky, stahuje se odtud a ukládá se v prohlížeči.","The model is on this site's server; it downloads from there and is saved in the browser.","El modelo está en el servidor de este sitio; se descarga de allí y se guarda en el navegador.","النموذج موجود على خادم هذا الموقع، يُنزَّل منه ويُحفظ في المتصفح.","Model bu sitenin sunucusunda; oradan indirilir ve tarayıcıda saklanır.","मॉडल इस साइट के सर्वर पर है, वहीं से डाउनलोड होकर ब्राउज़र में सेव होता है।"],
  "Pri prvom použití sa model stiahne (stovky MB), potom sa uloží a nesťahuje sa znova.": ["Při prvním použití se model stáhne (stovky MB), pak se uloží a znovu se nestahuje.","On first use the model downloads (hundreds of MB), then it's saved and not downloaded again.","La primera vez se descarga el modelo (cientos de MB), después se guarda y no se vuelve a descargar.","عند الاستخدام الأول يُنزَّل النموذج (مئات الميغابايت)، ثم يُحفظ ولا يُنزَّل مرة أخرى.","İlk kullanımda model indirilir (yüzlerce MB), sonra kaydedilir ve tekrar indirilmez.","पहली बार मॉडल डाउनलोड होता है (सैकड़ों MB), फिर सेव हो जाता है और दोबारा डाउनलोड नहीं होता।"],
  "Vyber súbor s videom (MP4, MOV, MKV, WEBM a podobne).": ["Vyber soubor s videem (MP4, MOV, MKV, WEBM a podobně).","Choose a video file (MP4, MOV, MKV, WEBM and similar).","Elige un archivo de vídeo (MP4, MOV, MKV, WEBM y similares).","اختر ملف فيديو (MP4 وMOV وMKV وWEBM وما شابه).","Bir video dosyası seç (MP4, MOV, MKV, WEBM ve benzeri).","एक वीडियो फ़ाइल चुनें (MP4, MOV, MKV, WEBM आदि)।"],
  "Spracúvam…": ["Zpracovávám…","Processing…","Procesando…","جارٍ المعالجة…","İşleniyor…","प्रोसेस हो रहा है…"],
  "Čítam zvuk…": ["Čtu zvuk…","Reading audio…","Leyendo el audio…","جارٍ قراءة الصوت…","Ses okunuyor…","ऑडियो पढ़ा जा रहा है…"],
  "Načítavam zvuk…": ["Načítám zvuk…","Loading audio…","Cargando el audio…","جارٍ تحميل الصوت…","Ses yükleniyor…","ऑडियो लोड हो रहा है…"],
  "Načítavam model…": ["Načítám model…","Loading model…","Cargando el modelo…","جارٍ تحميل النموذج…","Model yükleniyor…","मॉडल लोड हो रहा है…"],
  "Sťahujem model…": ["Stahuji model…","Downloading model…","Descargando el modelo…","جارٍ تنزيل النموذج…","Model indiriliyor…","मॉडल डाउनलोड हो रहा है…"],
  "Rozpoznávam reč…": ["Rozpoznávám řeč…","Recognizing speech…","Reconociendo el habla…","جارٍ التعرّف على الكلام…","Konuşma tanınıyor…","बोली पहचानी जा रही है…"],
  "Prehliadač nevie rozbaliť zvuk priamo, číta ho pri prehrávaní.": ["Prohlížeč neumí rozbalit zvuk přímo, čte ho při přehrávání.","The browser can't decode the audio directly, it reads it while playing.","El navegador no puede decodificar el audio directamente, lo lee mientras se reproduce.","لا يستطيع المتصفح فك ترميز الصوت مباشرة، فيقرؤه أثناء التشغيل.","Tarayıcı sesi doğrudan çözemiyor, oynatırken okuyor.","ब्राउज़र ऑडियो को सीधे डिकोड नहीं कर सकता, इसे चलाते समय पढ़ता है।"],
  "Pri dlhšom videu to môže trvať niekoľko minút. Nechaj túto kartu otvorenú.": ["U delšího videa to může trvat několik minut. Nech tuto kartu otevřenou.","A longer video may take several minutes. Keep this tab open.","Un vídeo largo puede tardar varios minutos. Mantén esta pestaña abierta.","قد يستغرق الفيديو الطويل عدة دقائق. أبقِ هذه الصفحة مفتوحة.","Uzun bir video birkaç dakika sürebilir. Bu sekmeyi açık tut.","लंबे वीडियो में कुछ मिनट लग सकते हैं। यह टैब खुला रखें।"],
  "V tomto videu sa nenašla žiadna reč.": ["V tomto videu nebyla nalezena žádná řeč.","No speech was found in this video.","No se encontró habla en este vídeo.","لم يُعثر على كلام في هذا الفيديو.","Bu videoda konuşma bulunamadı.","इस वीडियो में कोई बोली नहीं मिली।"],
  "Rozpoznanie zlyhalo.": ["Rozpoznání selhalo.","Recognition failed.","El reconocimiento falló.","فشل التعرّف.","Tanıma başarısız oldu.","पहचान विफल रही।"],
  "Zo súboru sa nepodarilo prečítať zvuk. Súbor je asi poškodený, bez zvuku alebo má zvukový kodek, ktorý prehliadač nepozná. Skús ho previesť do MP4 (H.264 + AAC).": ["Ze souboru se nepodařilo přečíst zvuk. Soubor je asi poškozený, bez zvuku nebo má zvukový kodek, který prohlížeč nezná. Zkus ho převést do MP4 (H.264 + AAC).","Couldn't read audio from the file. It may be damaged, silent or use an audio codec the browser doesn't know. Try converting it to MP4 (H.264 + AAC).","No se pudo leer el audio del archivo. Puede estar dañado, sin sonido o usar un códec de audio que el navegador no conoce. Prueba a convertirlo a MP4 (H.264 + AAC).","تعذّرت قراءة الصوت من الملف. قد يكون تالفًا أو بلا صوت أو يستخدم ترميزًا صوتيًا لا يعرفه المتصفح. جرّب تحويله إلى MP4 (H.264 + AAC).","Dosyadan ses okunamadı. Dosya bozuk, sessiz olabilir veya tarayıcının tanımadığı bir ses kodeği kullanıyor olabilir. MP4'e (H.264 + AAC) dönüştürmeyi dene.","फ़ाइल से ऑडियो नहीं पढ़ा जा सका। फ़ाइल खराब हो सकती है, बिना आवाज़ की हो सकती है या ऐसा ऑडियो कोडेक हो सकता है जिसे ब्राउज़र नहीं जानता। इसे MP4 (H.264 + AAC) में बदलकर देखें।"],
  "Prehliadač sa nedostal na huggingface.co (blokuje ho sieť, VPN, firewall alebo blokovač reklám). Skús inú sieť alebo vypni blokovač.": ["Prohlížeč se nedostal na huggingface.co (blokuje ho síť, VPN, firewall nebo blokovač reklam). Zkus jinou síť nebo vypni blokovač.","The browser couldn't reach huggingface.co (blocked by the network, VPN, firewall or an ad blocker). Try another network or turn off the blocker.","El navegador no pudo acceder a huggingface.co (lo bloquea la red, una VPN, un cortafuegos o un bloqueador de anuncios). Prueba otra red o desactiva el bloqueador.","لم يتمكن المتصفح من الوصول إلى huggingface.co (تحجبه الشبكة أو VPN أو جدار حماية أو مانع إعلانات). جرّب شبكة أخرى أو أوقف المانع.","Tarayıcı huggingface.co'ya ulaşamadı (ağ, VPN, güvenlik duvarı veya reklam engelleyici engelliyor). Başka bir ağ dene veya engelleyiciyi kapat.","ब्राउज़र huggingface.co तक नहीं पहुँच सका (नेटवर्क, VPN, फ़ायरवॉल या ऐड ब्लॉकर रोक रहा है)। दूसरा नेटवर्क आज़माएँ या ब्लॉकर बंद करें।"],
  "Stránka beží vo vstavanom prehliadači aplikácie, ktorý blokuje stiahnutie modelu z huggingface.co. Otvor ju v Safari alebo Chrome.": ["Stránka běží ve vestavěném prohlížeči aplikace, který blokuje stažení modelu z huggingface.co. Otevři ji v Safari nebo Chrome.","The page runs in an app's built-in browser, which blocks the model download from huggingface.co. Open it in Safari or Chrome.","La página se ejecuta en el navegador integrado de una app, que bloquea la descarga del modelo desde huggingface.co. Ábrela en Safari o Chrome.","تعمل الصفحة داخل المتصفح المدمج في تطبيق يحجب تنزيل النموذج من huggingface.co. افتحها في Safari أو Chrome.","Sayfa, model indirmeyi huggingface.co'dan engelleyen bir uygulamanın yerleşik tarayıcısında çalışıyor. Safari veya Chrome'da aç.","पेज किसी ऐप के बिल्ट-इन ब्राउज़र में चल रहा है, जो huggingface.co से मॉडल डाउनलोड रोकता है। इसे Safari या Chrome में खोलें।"],
  "Skús obnoviť stránku.": ["Zkus obnovit stránku.","Try reloading the page.","Prueba a recargar la página.","جرّب إعادة تحميل الصفحة.","Sayfayı yenilemeyi dene.","पेज रीलोड करके देखें।"],
  "Skús súbor otvoriť cez lokálny server: v priečinku so súborom spusti `python3 -m http.server 8000` a otvor http://localhost:8000/titulky-browser.html.": ["Zkus soubor otevřít přes lokální server: ve složce se souborem spusť `python3 -m http.server 8000` a otevři http://localhost:8000/titulky-browser.html.","Try opening the file through a local server: in its folder run `python3 -m http.server 8000` and open http://localhost:8000/titulky-browser.html.","Prueba a abrir el archivo mediante un servidor local: en su carpeta ejecuta `python3 -m http.server 8000` y abre http://localhost:8000/titulky-browser.html.","جرّب فتح الملف عبر خادم محلي: في مجلده شغّل `python3 -m http.server 8000` ثم افتح http://localhost:8000/titulky-browser.html.","Dosyayı yerel bir sunucu üzerinden açmayı dene: klasöründe `python3 -m http.server 8000` çalıştır ve http://localhost:8000/titulky-browser.html adresini aç.","फ़ाइल को लोकल सर्वर से खोलकर देखें: उसके फ़ोल्डर में `python3 -m http.server 8000` चलाएँ और http://localhost:8000/titulky-browser.html खोलें।"],
  "Nepodarilo sa načítať knižnicu na rozpoznávanie (cdn.jsdelivr.net). Skontroluj pripojenie na internet alebo blokovač reklám. Technická chyba:": ["Nepodařilo se načíst knihovnu pro rozpoznávání (cdn.jsdelivr.net). Zkontroluj připojení k internetu nebo blokovač reklam. Technická chyba:","Couldn't load the recognition library (cdn.jsdelivr.net). Check your internet connection or ad blocker. Technical error:","No se pudo cargar la biblioteca de reconocimiento (cdn.jsdelivr.net). Revisa tu conexión a internet o el bloqueador de anuncios. Error técnico:","تعذّر تحميل مكتبة التعرّف (cdn.jsdelivr.net). تحقق من اتصالك بالإنترنت أو من مانع الإعلانات. خطأ تقني:","Tanıma kütüphanesi yüklenemedi (cdn.jsdelivr.net). İnternet bağlantını veya reklam engelleyiciyi kontrol et. Teknik hata:","पहचान लाइब्रेरी (cdn.jsdelivr.net) लोड नहीं हो सकी। अपना इंटरनेट कनेक्शन या ऐड ब्लॉकर जाँचें। तकनीकी त्रुटि:"],
  "Diagnostika:": ["Diagnostika:","Diagnostics:","Diagnóstico:","التشخيص:","Tanılama:","डायग्नोस्टिक्स:"],
  "Prehliadač:": ["Prohlížeč:","Browser:","Navegador:","المتصفح:","Tarayıcı:","ब्राउज़र:"],
  "Protokol:": ["Protokol:","Protocol:","Protocolo:","البروتوكول:","Protokol:","प्रोटोकॉल:"],
  "Model:": ["Model:","Model:","Modelo:","النموذج:","Model:","मॉडल:"],
  "Rečníci:": ["Mluvčí:","Speakers:","Hablantes:","المتحدثون:","Konuşmacılar:","वक्ता:"],
  "pamäť prehliadača": ["paměť prohlížeče","browser storage","almacenamiento del navegador","تخزين المتصفح","tarayıcı depolaması","ब्राउज़र स्टोरेज"],
  "bez pamäte": ["bez paměti","no storage","sin almacenamiento","بلا تخزين","depolama yok","कोई स्टोरेज नहीं"],
  "uložený model": ["uložený model","saved model","modelo guardado","نموذج محفوظ","kayıtlı model","सेव किया मॉडल"],
  "vlastný server": ["vlastní server","own server","servidor propio","خادم خاص","kendi sunucu","अपना सर्वर"],
  "iný prehliadač": ["jiný prohlížeč","another browser","otro navegador","متصفح آخر","başka bir tarayıcı","दूसरा ब्राउज़र"],
  "NEDOSTUPNÉ": ["NEDOSTUPNÉ","UNAVAILABLE","NO DISPONIBLE","غير متاح","KULLANILAMAZ","उपलब्ध नहीं"],
  "Prehliadač nevie prehrať obraz tohto videa (pravdepodobne kodek H.265/HEVC alebo 10-bitový H.264). Titulky sa rozpoznali zo zvuku a môžeš ich opraviť a stiahnuť ako SRT alebo VTT. Náhľady a export videa nie sú k dispozícii. Skús Safari alebo Chrome na Macu (HEVC prehrávajú), alebo video prevedieš do MP4 s H.264 a nahráš znova.": ["Prohlížeč neumí přehrát obraz tohoto videa (pravděpodobně kodek H.265/HEVC nebo 10bitový H.264). Titulky se rozpoznaly ze zvuku a můžeš je opravit a stáhnout jako SRT nebo VTT. Náhledy a export videa nejsou k dispozici. Zkus Safari nebo Chrome na Macu (HEVC přehrávají), nebo video převeď do MP4 s H.264 a nahraj znovu.","The browser can't play this video's picture (probably H.265/HEVC or 10-bit H.264). Subtitles were recognized from the audio and you can fix them and download them as SRT or VTT. Previews and video export aren't available. Try Safari or Chrome on a Mac (they play HEVC), or convert the video to MP4 with H.264 and upload it again.","El navegador no puede reproducir la imagen de este vídeo (probablemente H.265/HEVC o H.264 de 10 bits). Los subtítulos se reconocieron del audio y puedes corregirlos y descargarlos como SRT o VTT. Las vistas previas y la exportación de vídeo no están disponibles. Prueba Safari o Chrome en un Mac (reproducen HEVC), o convierte el vídeo a MP4 con H.264 y súbelo de nuevo.","لا يستطيع المتصفح عرض صورة هذا الفيديو (غالبًا H.265/HEVC أو H.264 بعمق 10 بت). تم التعرّف على الترجمة من الصوت ويمكنك تصحيحها وتنزيلها بصيغة SRT أو VTT. المعاينة وتصدير الفيديو غير متاحين. جرّب Safari أو Chrome على Mac (يشغّلان HEVC)، أو حوّل الفيديو إلى MP4 بترميز H.264 وارفعه من جديد.","Tarayıcı bu videonun görüntüsünü oynatamıyor (muhtemelen H.265/HEVC veya 10 bit H.264). Altyazılar sesten tanındı; düzeltip SRT veya VTT olarak indirebilirsin. Önizlemeler ve video dışa aktarma kullanılamıyor. Mac'te Safari veya Chrome dene (HEVC oynatırlar) ya da videoyu H.264'lü MP4'e dönüştürüp tekrar yükle.","ब्राउज़र इस वीडियो की तस्वीर नहीं चला सकता (शायद H.265/HEVC या 10-बिट H.264)। सबटाइटल ऑडियो से पहचाने गए हैं, आप उन्हें सुधारकर SRT या VTT के रूप में डाउनलोड कर सकते हैं। प्रीव्यू और वीडियो एक्सपोर्ट उपलब्ध नहीं हैं। Mac पर Safari या Chrome आज़माएँ (वे HEVC चलाते हैं), या वीडियो को H.264 वाले MP4 में बदलकर फिर से अपलोड करें।"],
  "Video sa nepodarilo načítať.": ["Video se nepodařilo načíst.","Couldn't load the video.","No se pudo cargar el vídeo.","تعذّر تحميل الفيديو.","Video yüklenemedi.","वीडियो लोड नहीं हो सका।"],
  "Tento prehliadač nevie nahrávať video. Použi Chrome, Edge alebo Firefox.": ["Tento prohlížeč neumí nahrávat video. Použij Chrome, Edge nebo Firefox.","This browser can't record video. Use Chrome, Edge or Firefox.","Este navegador no puede grabar vídeo. Usa Chrome, Edge o Firefox.","هذا المتصفح لا يستطيع تسجيل الفيديو. استخدم Chrome أو Edge أو Firefox.","Bu tarayıcı video kaydedemiyor. Chrome, Edge veya Firefox kullan.","यह ब्राउज़र वीडियो रिकॉर्ड नहीं कर सकता। Chrome, Edge या Firefox इस्तेमाल करें।"],
  "Krátke video": ["Krátké video","Short video","Vídeo corto","فيديو قصير","Kısa video","छोटा वीडियो"],
  "Príspevok": ["Příspěvek","Post","Publicación","منشور","Gönderi","पोस्ट"],
  "Web": ["Web","Web","Web","ويب","Web","वेब"],
  "▶ Prehrať": ["▶ Přehrát","▶ Play","▶ Reproducir","▶ تشغيل","▶ Oynat","▶ चलाएँ"],
  "■ Stop": ["■ Stop","■ Stop","■ Detener","■ إيقاف","■ Durdur","■ रोकें"],
  "Exportovať a stiahnuť": ["Exportovat a stáhnout","Export and download","Exportar y descargar","تصدير وتنزيل","Dışa aktar ve indir","एक्सपोर्ट करें और डाउनलोड करें"],
  "Stiahnuť hotové video": ["Stáhnout hotové video","Download finished video","Descargar el vídeo terminado","تنزيل الفيديو الجاهز","Hazır videoyu indir","तैयार वीडियो डाउनलोड करें"],
  "Titulky presunieš ťahaním v náhľade. Posunie sa poloha rečníka, ktorého titulok práve vidíš.": ["Titulky přesuneš tažením v náhledu. Posune se pozice mluvčího, jehož titulek právě vidíš.","Drag subtitles in the preview to move them. It moves the position of the speaker whose subtitle you see.","Arrastra los subtítulos en la vista previa para moverlos. Se mueve la posición del hablante cuyo subtítulo ves.","اسحب الترجمة في المعاينة لنقلها. يتغيّر موضع المتحدث الذي ترى ترجمته.","Altyazıları taşımak için önizlemede sürükle. Altyazısını gördüğün konuşmacının konumu taşınır.","सबटाइटल को खिसकाने के लिए प्रीव्यू में खींचें। जिस वक्ता का सबटाइटल दिख रहा है, उसकी जगह बदलती है।"],
  "Ťahaním v náhľade posúvaš video, kolieskom myši ho približuješ. Prehrávanie preskakuje vystrihnuté časti a ukazuje prechody ako v exporte.": ["Tažením v náhledu posouváš video, kolečkem myši ho přibližuješ. Přehrávání přeskakuje vystřižené části a ukazuje přechody jako v exportu.","Drag in the preview to move the video, use the mouse wheel to zoom. Playback skips cut parts and shows transitions as in the export.","Arrastra en la vista previa para mover el vídeo, usa la rueda del ratón para acercar. La reproducción salta las partes cortadas y muestra las transiciones como en la exportación.","اسحب في المعاينة لتحريك الفيديو، واستخدم عجلة الفأرة للتكبير. يتخطى التشغيل الأجزاء المقصوصة ويعرض الانتقالات كما في التصدير.","Videoyu taşımak için önizlemede sürükle, yakınlaştırmak için fare tekerleğini kullan. Oynatma kesilen bölümleri atlar ve geçişleri dışa aktarmadaki gibi gösterir.","वीडियो खिसकाने के लिए प्रीव्यू में खींचें, ज़ूम के लिए माउस व्हील इस्तेमाल करें। प्लेबैक कटे हिस्से छोड़ देता है और ट्रांज़िशन एक्सपोर्ट जैसे दिखाता है।"],
  "Export beží v reálnom čase, nechaj kartu otvorenú.": ["Export běží v reálném čase, nech kartu otevřenou.","Export runs in real time, keep the tab open.","La exportación se hace en tiempo real, mantén la pestaña abierta.","يتم التصدير في الوقت الفعلي، أبقِ الصفحة مفتوحة.","Dışa aktarma gerçek zamanlı çalışır, sekmeyi açık tut.","एक्सपोर्ट रियल टाइम में होता है, टैब खुला रखें।"],
  "Prehrať alebo pozastaviť": ["Přehrát nebo pozastavit","Play or pause","Reproducir o pausar","تشغيل أو إيقاف مؤقت","Oynat veya duraklat","चलाएँ या रोकें"],
  "Prehrať alebo zastaviť": ["Přehrát nebo zastavit","Play or stop","Reproducir o detener","تشغيل أو إيقاف","Oynat veya durdur","चलाएँ या बंद करें"],
  "Prehrať / zastaviť (medzerník)": ["Přehrát / zastavit (mezerník)","Play / stop (space)","Reproducir / detener (espacio)","تشغيل / إيقاف (مسافة)","Oynat / durdur (boşluk)","चलाएँ / रोकें (स्पेस)"],
  "Rýchlosť prehrávania": ["Rychlost přehrávání","Playback speed","Velocidad de reproducción","سرعة التشغيل","Oynatma hızı","प्लेबैक स्पीड"],
  "Rýchlosť náhľadu": ["Rychlost náhledu","Preview speed","Velocidad de la vista previa","سرعة المعاينة","Önizleme hızı","प्रीव्यू स्पीड"],
  "Oddialiť časovú os": ["Oddálit časovou osu","Zoom out timeline","Alejar la línea de tiempo","تصغير الخط الزمني","Zaman çizelgesini uzaklaştır","टाइमलाइन ज़ूम आउट"],
  "Priblížiť časovú os": ["Přiblížit časovou osu","Zoom in timeline","Acercar la línea de tiempo","تكبير الخط الزمني","Zaman çizelgesini yakınlaştır","टाइमलाइन ज़ूम इन"],
  "Priblíženie časovej osi": ["Přiblížení časové osy","Timeline zoom","Zoom de la línea de tiempo","تكبير الخط الزمني","Zaman çizelgesi yakınlaştırma","टाइमलाइन ज़ूम"],
  "Ťahaním v páse titulkov vyberieš úsek (alebo klik a Shift+klik na titulky), potom mu priradíš rečníka a štýl. Titulok presunieš ťahaním, ťahaním za okraj upravíš začiatok alebo koniec. Klik na pravítko alebo vlnovku pretočí video, medzerník spustí a zastaví prehrávanie.": ["Tažením v pásu titulků vybereš úsek (nebo klik a Shift+klik na titulky), pak mu přiřadíš mluvčího a styl. Titulek přesuneš tažením, tažením za okraj upravíš začátek nebo konec. Klik na pravítko nebo vlnovku přetočí video, mezerník spustí a zastaví přehrávání.","Drag across the subtitle lane to select a range (or click and Shift+click subtitles), then assign a speaker and style. Drag a subtitle to move it, drag its edge to change the start or end. Click the ruler or waveform to seek, space plays and pauses.","Arrastra por la franja de subtítulos para seleccionar un tramo (o clic y Mayús+clic en los subtítulos) y asígnale un hablante y un estilo. Arrastra un subtítulo para moverlo y su borde para cambiar el inicio o el final. Haz clic en la regla o en la onda para saltar, la barra espaciadora reproduce y pausa.","اسحب على شريط الترجمة لتحديد مقطع (أو انقر ثم Shift+نقر على الترجمات)، ثم عيّن له متحدثًا ونمطًا. اسحب الترجمة لنقلها، واسحب حافتها لتغيير البداية أو النهاية. انقر على المسطرة أو الموجة للانتقال، ومفتاح المسافة يشغّل ويوقف.","Bir aralık seçmek için altyazı şeridinde sürükle (veya altyazılara tıkla ve Shift+tıkla), ardından konuşmacı ve stil ata. Altyazıyı taşımak için sürükle, başlangıcı veya sonu değiştirmek için kenarını sürükle. Atlamak için cetvele veya dalga formuna tıkla, boşluk tuşu oynatır ve duraklatır.","कोई हिस्सा चुनने के लिए सबटाइटल पट्टी पर खींचें (या सबटाइटल पर क्लिक और Shift+क्लिक करें), फिर उसे वक्ता और स्टाइल दें। सबटाइटल खिसकाने के लिए उसे खींचें, शुरुआत या अंत बदलने के लिए उसका किनारा खींचें। जगह बदलने के लिए रूलर या वेवफ़ॉर्म पर क्लिक करें, स्पेस से चलता और रुकता है।"],
  "Zrušiť výber": ["Zrušit výběr","Clear selection","Quitar selección","إلغاء التحديد","Seçimi temizle","चयन हटाएँ"],
  "Rečník": ["Mluvčí","Speaker","Hablante","المتحدث","Konuşmacı","वक्ता"],
  "Štýl": ["Styl","Style","Estilo","النمط","Stil","स्टाइल"],
  "Podľa rečníka": ["Podle mluvčího","By speaker","Según el hablante","حسب المتحدث","Konuşmacıya göre","वक्ता के अनुसार"],
  "Zrušiť vlastný štýl, použiť štýl rečníka": ["Zrušit vlastní styl, použít styl mluvčího","Remove custom style, use the speaker's style","Quitar el estilo propio y usar el del hablante","إزالة النمط المخصص واستخدام نمط المتحدث","Özel stili kaldır, konuşmacının stilini kullan","अपनी स्टाइल हटाएँ, वक्ता की स्टाइल इस्तेमाल करें"],
  "Pridať nového rečníka a priradiť mu vybrané titulky": ["Přidat nového mluvčího a přiřadit mu vybrané titulky","Add a new speaker and assign the selected subtitles","Añadir un hablante nuevo y asignarle los subtítulos seleccionados","إضافة متحدث جديد وتعيين الترجمات المحددة له","Yeni bir konuşmacı ekle ve seçili altyazıları ata","नया वक्ता जोड़ें और चुने हुए सबटाइटल उसे दें"],
  "Nový": ["Nový","New","Nuevo","جديد","Yeni","नया"],
  "Text titulkov": ["Text titulků","Subtitle text","Texto de los subtítulos","نص الترجمة","Altyazı metni","सबटाइटल टेक्स्ट"],
  "Oprav chyby v rozpoznanom texte. Klik na čas skočí na dané miesto vo videu.": ["Oprav chyby v rozpoznaném textu. Klik na čas skočí na dané místo ve videu.","Fix mistakes in the recognized text. Click a time to jump there in the video.","Corrige los errores del texto reconocido. Haz clic en un tiempo para saltar a ese punto del vídeo.","صحّح الأخطاء في النص المتعرَّف عليه. انقر على وقت للانتقال إليه في الفيديو.","Tanınan metindeki hataları düzelt. Videoda o noktaya gitmek için bir zamana tıkla.","पहचाने गए टेक्स्ट की गलतियाँ सुधारें। वीडियो में उस जगह जाने के लिए समय पर क्लिक करें।"],
  "Hľadať v titulkoch": ["Hledat v titulcích","Search subtitles","Buscar en los subtítulos","البحث في الترجمة","Altyazılarda ara","सबटाइटल में खोजें"],
  "Hľadať v texte titulkov…": ["Hledat v textu titulků…","Search subtitle text…","Buscar en el texto…","ابحث في نص الترجمة…","Altyazı metninde ara…","सबटाइटल टेक्स्ट में खोजें…"],
  "Klik: prehrať odtiaľto a vybrať · Shift+klik: vybrať úsek": ["Klik: přehrát odsud a vybrat · Shift+klik: vybrat úsek","Click: play from here and select · Shift+click: select range","Clic: reproducir desde aquí y seleccionar · Mayús+clic: seleccionar tramo","نقر: تشغيل من هنا وتحديد · Shift+نقر: تحديد مقطع","Tıkla: buradan oynat ve seç · Shift+tıkla: aralık seç","क्लिक: यहाँ से चलाएँ और चुनें · Shift+क्लिक: हिस्सा चुनें"],
  "Vystrihnuté": ["Vystřiženo","Cut","Cortado","مقصوص","Kesildi","कटा हुआ"],
  "Titulky": ["Titulky","Subtitles","Subtítulos","الترجمة","Altyazılar","सबटाइटल"],
  "Titulky zapnuté": ["Titulky zapnuté","Subtitles on","Subtítulos activados","الترجمة مفعّلة","Altyazılar açık","सबटाइटल चालू"],
  "Rečníci": ["Mluvčí","Speakers","Hablantes","المتحدثون","Konuşmacılar","वक्ता"],
  "Pridať": ["Přidat","Add","Añadir","إضافة","Ekle","जोड़ें"],
  "Pridať rečníka": ["Přidat mluvčího","Add speaker","Añadir hablante","إضافة متحدث","Konuşmacı ekle","वक्ता जोड़ें"],
  "Rozpoznať rečníkov podľa hlasu": ["Rozpoznat mluvčí podle hlasu","Detect speakers by voice","Detectar hablantes por la voz","التعرّف على المتحدثين من الصوت","Konuşmacıları sesten algıla","आवाज़ से वक्ता पहचानें"],
  "Počúvam hlasy…": ["Poslouchám hlasy…","Listening to voices…","Escuchando las voces…","جارٍ الاستماع إلى الأصوات…","Sesler dinleniyor…","आवाज़ें सुनी जा रही हैं…"],
  "Hlavný": ["Hlavní","Main","Principal","رئيسي","Ana","मुख्य"],
  "Meno rečníka": ["Jméno mluvčího","Speaker name","Nombre del hablante","اسم المتحدث","Konuşmacı adı","वक्ता का नाम"],
  "Klikni a prepíš meno": ["Klikni a přepiš jméno","Click to rename","Haz clic para renombrar","انقر لإعادة التسمية","Yeniden adlandırmak için tıkla","नाम बदलने के लिए क्लिक करें"],
  "Odobrať rečníka (jeho titulky prejdú na hlavného)": ["Odebrat mluvčího (jeho titulky přejdou na hlavního)","Remove speaker (their subtitles go to the main speaker)","Quitar hablante (sus subtítulos pasan al principal)","إزالة المتحدث (تنتقل ترجماته إلى المتحدث الرئيسي)","Konuşmacıyı kaldır (altyazıları ana konuşmacıya geçer)","वक्ता हटाएँ (उसके सबटाइटल मुख्य वक्ता को मिलेंगे)"],
  "Nový rečník": ["Nový mluvčí","New speaker","Nuevo hablante","متحدث جديد","Yeni konuşmacı","नया वक्ता"],
  "Všetky titulky patria jednému rečníkovi. Ak vo videu rozpráva viac ľudí, pridaj ďalšieho rečníka s vlastným štýlom.": ["Všechny titulky patří jednomu mluvčímu. Pokud ve videu mluví víc lidí, přidej dalšího mluvčího s vlastním stylem.","All subtitles belong to one speaker. If more people talk in the video, add another speaker with their own style.","Todos los subtítulos pertenecen a un hablante. Si en el vídeo habla más gente, añade otro hablante con su propio estilo.","كل الترجمات لمتحدث واحد. إذا تحدث أكثر من شخص في الفيديو، أضف متحدثًا آخر بنمطه الخاص.","Tüm altyazılar tek bir konuşmacıya ait. Videoda daha fazla kişi konuşuyorsa kendi stiliyle başka bir konuşmacı ekle.","सभी सबटाइटल एक वक्ता के हैं। अगर वीडियो में ज़्यादा लोग बोलते हैं, तो अपनी स्टाइल वाला दूसरा वक्ता जोड़ें।"],
  "Štýl a pozícia nižšie platia pre vybraného rečníka. Rečníka titulku zmeníš značkou pri texte alebo výberom úseku na časovej osi.": ["Styl a pozice níže platí pro vybraného mluvčího. Mluvčího titulku změníš štítkem u textu nebo výběrem úseku na časové ose.","The style and position below apply to the selected speaker. Change a subtitle's speaker with the tag next to the text or by selecting a range on the timeline.","El estilo y la posición de abajo se aplican al hablante seleccionado. Cambia el hablante de un subtítulo con la etiqueta junto al texto o seleccionando un tramo en la línea de tiempo.","النمط والموضع أدناه للمتحدث المحدد. غيّر متحدث الترجمة بالوسم بجانب النص أو بتحديد مقطع على الخط الزمني.","Aşağıdaki stil ve konum seçili konuşmacı için geçerlidir. Bir altyazının konuşmacısını metnin yanındaki etiketle veya zaman çizelgesinde bir aralık seçerek değiştir.","नीचे दी गई स्टाइल और जगह चुने हुए वक्ता पर लागू होती है। सबटाइटल का वक्ता टेक्स्ट के पास वाले टैग से या टाइमलाइन पर हिस्सा चुनकर बदलें।"],
  "Rozpoznané 2 hlasy. Skontroluj priradenie v zozname titulkov, zlé zmeníš značkou pri texte.": ["Rozpoznány 2 hlasy. Zkontroluj přiřazení v seznamu titulků, špatné změníš štítkem u textu.","2 voices detected. Check the assignment in the subtitle list and fix wrong ones with the tag next to the text.","Se detectaron 2 voces. Revisa la asignación en la lista de subtítulos y corrige las incorrectas con la etiqueta junto al texto.","تم التعرّف على صوتين. راجع التعيين في قائمة الترجمة وصحّح الخاطئ بالوسم بجانب النص.","2 ses algılandı. Altyazı listesindeki atamayı kontrol et, yanlış olanları metnin yanındaki etiketle düzelt.","2 आवाज़ें पहचानी गईं। सबटाइटल सूची में बँटवारा जाँचें और गलत वालों को टेक्स्ट के पास वाले टैग से ठीक करें।"],
  "Rozpoznaný len jeden hlas. Ďalšieho rečníka môžeš pridať ručne tlačidlom Pridať.": ["Rozpoznán jen jeden hlas. Dalšího mluvčího můžeš přidat ručně tlačítkem Přidat.","Only one voice detected. You can add another speaker manually with Add.","Solo se detectó una voz. Puedes añadir otro hablante manualmente con Añadir.","تم التعرّف على صوت واحد فقط. يمكنك إضافة متحدث آخر يدويًا بزر إضافة.","Yalnızca bir ses algılandı. Ekle ile elle başka bir konuşmacı ekleyebilirsin.","केवल एक आवाज़ पहचानी गई। जोड़ें बटन से आप दूसरा वक्ता खुद जोड़ सकते हैं।"],
  "Pozícia": ["Pozice","Position","Posición","الموضع","Konum","जगह"],
  "Hore": ["Nahoře","Top","Arriba","أعلى","Üst","ऊपर"],
  "Stred": ["Střed","Middle","Centro","الوسط","Orta","बीच"],
  "Dole": ["Dole","Bottom","Abajo","أسفل","Alt","नीचे"],
  "Titulky môžeš aj potiahnuť priamo v náhľade.": ["Titulky můžeš také přetáhnout přímo v náhledu.","You can also drag subtitles right in the preview.","También puedes arrastrar los subtítulos directamente en la vista previa.","يمكنك أيضًا سحب الترجمة مباشرة في المعاينة.","Altyazıları doğrudan önizlemede de sürükleyebilirsin.","आप सबटाइटल को सीधे प्रीव्यू में भी खींच सकते हैं।"],
  "Vzhľad (veľkosť)": ["Vzhled (velikost)","Appearance (size)","Aspecto (tamaño)","المظهر (الحجم)","Görünüm (boyut)","रूप (आकार)"],
  "Veľkosť titulkov": ["Velikost titulků","Subtitle size","Tamaño de los subtítulos","حجم الترجمة","Altyazı boyutu","सबटाइटल का आकार"],
  "Farba a font": ["Barva a font","Color and font","Color y fuente","اللون والخط","Renk ve yazı tipi","रंग और फ़ॉन्ट"],
  "Farba textu": ["Barva textu","Text color","Color del texto","لون النص","Metin rengi","टेक्स्ट का रंग"],
  "Font": ["Font","Font","Fuente","الخط","Yazı tipi","फ़ॉन्ट"],
  "Nahrať vlastný font": ["Nahrát vlastní font","Upload your own font","Subir tu propia fuente","ارفع خطك الخاص","Kendi yazı tipini yükle","अपना फ़ॉन्ट अपलोड करें"],
  "Všetky fonty s ukážkou v Značke →": ["Všechny fonty s ukázkou ve Značce →","All fonts with previews in Brand →","Todas las fuentes con vista previa en Marca →","كل الخطوط مع المعاينة في العلامة ←","Marka'da önizlemeli tüm yazı tipleri →","ब्रांड में प्रीव्यू के साथ सभी फ़ॉन्ट →"],
  "Súbor TTF, OTF, WOFF alebo WOFF2. Font ostane len v tomto prehliadači.": ["Soubor TTF, OTF, WOFF nebo WOFF2. Font zůstane jen v tomto prohlížeči.","A TTF, OTF, WOFF or WOFF2 file. The font stays only in this browser.","Archivo TTF, OTF, WOFF o WOFF2. La fuente solo se queda en este navegador.","ملف TTF أو OTF أو WOFF أو WOFF2. يبقى الخط في هذا المتصفح فقط.","TTF, OTF, WOFF veya WOFF2 dosyası. Yazı tipi yalnızca bu tarayıcıda kalır.","TTF, OTF, WOFF या WOFF2 फ़ाइल। फ़ॉन्ट केवल इसी ब्राउज़र में रहेगा।"],
  "Vlastný font nahráš v Značke, bude dostupný pre všetkých.": ["Vlastní font nahraješ ve Značce, bude dostupný pro všechny.","Upload your own font in Brand, it will be available to everyone.","Sube tu propia fuente en Marca, estará disponible para todos.","ارفع خطك الخاص في العلامة، وسيكون متاحًا للجميع.","Kendi yazı tipini Marka'da yükle, herkes kullanabilecek.","अपना फ़ॉन्ट ब्रांड में अपलोड करें, यह सबके लिए उपलब्ध होगा।"],
  "Podklad": ["Podklad","Background","Fondo","الخلفية","Arka plan","बैकग्राउंड"],
  "Priehľadnosť podkladu": ["Průhlednost podkladu","Background opacity","Opacidad del fondo","شفافية الخلفية","Arka plan opaklığı","बैकग्राउंड की पारदर्शिता"],
  "0 % = bez podkladu, text dostane obrys.": ["0 % = bez podkladu, text dostane obrys.","0% = no background, the text gets an outline.","0 % = sin fondo, el texto lleva contorno.","0 % = بلا خلفية، ويحصل النص على حدّ خارجي.","%0 = arka plan yok, metne kontur eklenir.","0% = कोई बैकग्राउंड नहीं, टेक्स्ट को आउटलाइन मिलती है।"],
  "Pokročilé": ["Pokročilé","Advanced","Avanzado","متقدم","Gelişmiş","एडवांस्ड"],
  "Zvýrazňovať práve vyslovené slovo": ["Zvýrazňovat právě vyslovené slovo","Highlight the word being spoken","Resaltar la palabra que se pronuncia","تمييز الكلمة المنطوقة","Söylenen kelimeyi vurgula","बोले जा रहे शब्द को हाइलाइट करें"],
  "Farba zvýraznenia": ["Barva zvýraznění","Highlight color","Color de resaltado","لون التمييز","Vurgu rengi","हाइलाइट का रंग"],
  "Dynamický štýl: meniť polohu pri každej fráze": ["Dynamický styl: měnit pozici u každé fráze","Dynamic style: change position for each phrase","Estilo dinámico: cambiar la posición en cada frase","النمط الديناميكي: تغيير الموضع مع كل عبارة","Dinamik stil: her ifadede konumu değiştir","डायनैमिक स्टाइल: हर वाक्यांश पर जगह बदलें"],
  "Dynamický štýl: VEĽKÉ PÍSMENA (kapitálky)": ["Dynamický styl: VELKÁ PÍSMENA","Dynamic style: CAPITAL LETTERS","Estilo dinámico: MAYÚSCULAS","النمط الديناميكي: أحرف كبيرة","Dinamik stil: BÜYÜK HARFLER","डायनैमिक स्टाइल: बड़े अक्षर"],
  "Vlastný štýl (upravený)": ["Vlastní styl (upravený)","Custom style (edited)","Estilo propio (editado)","نمط مخصص (معدّل)","Özel stil (düzenlendi)","अपनी स्टाइल (बदली हुई)"],
  "Modern": ["Moderní","Modern","Moderno","عصري","Modern","मॉडर्न"],
  "Klasik": ["Klasik","Classic","Clásico","كلاسيكي","Klasik","क्लासिक"],
  "Kino": ["Kino","Cinema","Cine","سينما","Sinema","सिनेमा"],
  "Výrazný": ["Výrazný","Bold","Intenso","بارز","Belirgin","बोल्ड"],
  "Jemný": ["Jemný","Soft","Suave","ناعم","Yumuşak","सॉफ़्ट"],
  "Svetlý": ["Světlý","Light","Claro","فاتح","Açık","लाइट"],
  "PS blue": ["PS blue","PS blue","PS blue","PS blue","PS blue","PS blue"],
  "Dynamický": ["Dynamický","Dynamic","Dinámico","ديناميكي","Dinamik","डायनैमिक"],
  "Ahoj svet": ["Ahoj světe","Hello world","Hola mundo","مرحبا بالعالم","Merhaba dünya","नमस्ते दुनिया"],
  "Ahoj": ["Ahoj","Hello","Hola","مرحبا","Merhaba","नमस्ते"],
  "svet": ["světe","world","mundo","بالعالم","dünya","दुनिया"],
  "Úprava videa": ["Úpravy videa","Video editing","Edición de vídeo","تحرير الفيديو","Video düzenleme","वीडियो एडिटिंग"],
  "Pomer strán": ["Poměr stran","Aspect ratio","Relación de aspecto","نسبة العرض إلى الارتفاع","En boy oranı","आस्पेक्ट रेशियो"],
  "Rozpoznané z nahraného videa. Zmeň ikonkou, výrez videa nastavíš nižšie.": ["Rozpoznáno z nahraného videa. Změň ikonkou, výřez videa nastavíš níže.","Detected from the uploaded video. Change it with the icons, set the crop below.","Detectado del vídeo subido. Cámbialo con los iconos, el recorte se ajusta abajo.","تم اكتشافها من الفيديو المرفوع. غيّرها بالأيقونات، واضبط القص أدناه.","Yüklenen videodan algılandı. Simgelerle değiştir, kırpmayı aşağıda ayarla.","अपलोड किए वीडियो से पहचाना गया। आइकन से बदलें, क्रॉप नीचे सेट करें।"],
  "Výrez a posun videa": ["Výřez a posun videa","Crop and position","Recorte y posición","القص والموضع","Kırpma ve konum","क्रॉप और जगह"],
  "Priblíženie": ["Přiblížení","Zoom","Zoom","التكبير","Yakınlaştırma","ज़ूम"],
  "Priblíženie videa": ["Přiblížení videa","Video zoom","Zoom del vídeo","تكبير الفيديو","Video yakınlaştırma","वीडियो ज़ूम"],
  "Obnoviť orez vybraného formátu": ["Obnovit ořez vybraného formátu","Reset crop for this format","Restablecer el recorte de este formato","إعادة ضبط القص لهذا التنسيق","Bu formatın kırpmasını sıfırla","इस फ़ॉर्मैट का क्रॉप रीसेट करें"],
  "Ťahaním po náhľade posúvaš video, kolieskom myši alebo posuvníkom približuješ. Orez platí len pre vybraný formát.": ["Tažením po náhledu posouváš video, kolečkem myši nebo posuvníkem přibližuješ. Ořez platí jen pro vybraný formát.","Drag on the preview to move the video, zoom with the mouse wheel or the slider. The crop applies only to the selected format.","Arrastra en la vista previa para mover el vídeo, acerca con la rueda del ratón o el control deslizante. El recorte solo se aplica al formato seleccionado.","اسحب على المعاينة لتحريك الفيديو، وكبّر بعجلة الفأرة أو بالمنزلق. يُطبَّق القص على التنسيق المحدد فقط.","Videoyu taşımak için önizlemede sürükle, fare tekerleği veya kaydırıcıyla yakınlaştır. Kırpma yalnızca seçili formata uygulanır.","वीडियो खिसकाने के लिए प्रीव्यू पर खींचें, माउस व्हील या स्लाइडर से ज़ूम करें। क्रॉप केवल चुने हुए फ़ॉर्मैट पर लागू होता है।"],
  "Inšpektor strihu": ["Inspektor střihu","Cut inspector","Inspector de corte","مُراجِع التقطيع","Kesim denetçisi","कट इंस्पेक्टर"],
  "Vyber klip na časovej osi a uprav jeho rýchlosť, efekt a filter. Šedý štvorček medzi klipmi otvorí prechod.": ["Vyber klip na časové ose a uprav jeho rychlost, efekt a filtr. Šedý čtvereček mezi klipy otevře přechod.","Select a clip on the timeline to adjust its speed, effect and filter. The grey square between clips opens the transition.","Selecciona un clip en la línea de tiempo para ajustar su velocidad, efecto y filtro. El cuadrado gris entre clips abre la transición.","حدّد مقطعًا على الخط الزمني لضبط سرعته وتأثيره وفلتره. المربع الرمادي بين المقاطع يفتح الانتقال.","Hızını, efektini ve filtresini ayarlamak için zaman çizelgesinde bir klip seç. Klipler arasındaki gri kare geçişi açar.","स्पीड, इफ़ेक्ट और फ़िल्टर बदलने के लिए टाइमलाइन पर एक क्लिप चुनें। क्लिप के बीच का धूसर चौकोर ट्रांज़िशन खोलता है।"],
  "Nástroje strihu": ["Nástroje střihu","Cut tools","Herramientas de corte","أدوات التقطيع","Kesim araçları","कट टूल"],
  "Rozdeliť": ["Rozdělit","Split","Dividir","تقسيم","Böl","बाँटें"],
  "Vymazať": ["Smazat","Delete","Eliminar","حذف","Sil","हटाएँ"],
  "Rozdeliť klip na prehrávacej hlave (S)": ["Rozdělit klip na přehrávací hlavě (S)","Split clip at the playhead (S)","Dividir el clip en el cabezal (S)","تقسيم المقطع عند رأس التشغيل (S)","Klibi oynatma kafasında böl (S)","प्लेहेड पर क्लिप बाँटें (S)"],
  "Vymazať vybraný klip, medzera sa zatvorí (Del)": ["Smazat vybraný klip, mezera se zavře (Del)","Delete the selected clip, the gap closes (Del)","Eliminar el clip seleccionado, el hueco se cierra (Supr)","حذف المقطع المحدد، وتُغلق الفجوة (Del)","Seçili klibi sil, boşluk kapanır (Del)","चुनी हुई क्लिप हटाएँ, खाली जगह भर जाती है (Del)"],
  "Späť (Ctrl+Z)": ["Zpět (Ctrl+Z)","Undo (Ctrl+Z)","Deshacer (Ctrl+Z)","تراجع (Ctrl+Z)","Geri al (Ctrl+Z)","पूर्ववत (Ctrl+Z)"],
  "Znova (Ctrl+Shift+Z)": ["Znovu (Ctrl+Shift+Z)","Redo (Ctrl+Shift+Z)","Rehacer (Ctrl+Mayús+Z)","إعادة (Ctrl+Shift+Z)","Yinele (Ctrl+Shift+Z)","फिर से करें (Ctrl+Shift+Z)"],
  "Video 1": ["Video 1","Video 1","Vídeo 1","فيديو 1","Video 1","वीडियो 1"],
  "Audio 1": ["Audio 1","Audio 1","Audio 1","صوت 1","Ses 1","ऑडियो 1"],
  "Klik na klip ho vyberie, ťahaním ho presunieš. Oranžové okraje vybraného klipu ho orežú. Štvorček medzi klipmi pridá prechod. S = rozdeliť, Del = vymazať.": ["Klik na klip ho vybere, tažením ho přesuneš. Oranžové okraje vybraného klipu ho ořežou. Čtvereček mezi klipy přidá přechod. S = rozdělit, Del = smazat.","Click a clip to select it, drag to move it. The orange edges of the selected clip trim it. The square between clips adds a transition. S = split, Del = delete.","Haz clic en un clip para seleccionarlo y arrástralo para moverlo. Los bordes naranjas del clip seleccionado lo recortan. El cuadrado entre clips añade una transición. S = dividir, Supr = eliminar.","انقر على مقطع لتحديده واسحبه لنقله. الحواف البرتقالية للمقطع المحدد تقصّه. المربع بين المقاطع يضيف انتقالًا. S = تقسيم، Del = حذف.","Seçmek için bir klibe tıkla, taşımak için sürükle. Seçili klibin turuncu kenarları onu kırpar. Klipler arasındaki kare geçiş ekler. S = böl, Del = sil.","क्लिप चुनने के लिए उस पर क्लिक करें, खिसकाने के लिए खींचें। चुनी हुई क्लिप के नारंगी किनारे उसे ट्रिम करते हैं। क्लिप के बीच का चौकोर ट्रांज़िशन जोड़ता है। S = बाँटें, Del = हटाएँ।"],
  "Ťahaním orežeš začiatok": ["Tažením ořízneš začátek","Drag to trim the start","Arrastra para recortar el inicio","اسحب لقص البداية","Başlangıcı kırpmak için sürükle","शुरुआत ट्रिम करने के लिए खींचें"],
  "Ťahaním orežeš koniec": ["Tažením ořízneš konec","Drag to trim the end","Arrastra para recortar el final","اسحب لقص النهاية","Sonu kırpmak için sürükle","अंत ट्रिम करने के लिए खींचें"],
  "Pridať prechod": ["Přidat přechod","Add transition","Añadir transición","إضافة انتقال","Geçiş ekle","ट्रांज़िशन जोड़ें"],
  "Prehrávacia hlava nie je na žiadnom klipe.": ["Přehrávací hlava není na žádném klipu.","The playhead isn't on any clip.","El cabezal no está sobre ningún clip.","رأس التشغيل ليس على أي مقطع.","Oynatma kafası hiçbir klibin üzerinde değil.","प्लेहेड किसी क्लिप पर नहीं है।"],
  "Tu sa rozdeliť nedá, je to príliš blízko okraja klipu.": ["Tady se rozdělit nedá, je to příliš blízko okraje klipu.","Can't split here, it's too close to the clip edge.","Aquí no se puede dividir, está demasiado cerca del borde del clip.","لا يمكن التقسيم هنا، قريب جدًا من حافة المقطع.","Burada bölünemez, klip kenarına çok yakın.","यहाँ नहीं बाँटा जा सकता, क्लिप के किनारे के बहुत पास है।"],
  "Rozdelené. Vybraný je klip za strihom: vymaž ho, presuň, alebo mu daj efekt.": ["Rozděleno. Vybraný je klip za střihem: smaž ho, přesuň, nebo mu dej efekt.","Split. The clip after the cut is selected: delete it, move it or add an effect.","Dividido. Está seleccionado el clip tras el corte: elimínalo, muévelo o añádele un efecto.","تم التقسيم. المقطع بعد القطع محدد: احذفه أو انقله أو أضف إليه تأثيرًا.","Bölündü. Kesimden sonraki klip seçili: sil, taşı veya efekt ekle.","बाँट दिया। कट के बाद वाली क्लिप चुनी गई है: इसे हटाएँ, खिसकाएँ या इफ़ेक्ट दें।"],
  "Posledný klip vymazať nejde. Kratší ho spravíš ťahaním za biele okraje.": ["Poslední klip smazat nejde. Kratší ho uděláš tažením za okraje.","The last clip can't be deleted. Shorten it by dragging its edges.","El último clip no se puede eliminar. Acórtalo arrastrando sus bordes.","لا يمكن حذف المقطع الأخير. قصّره بسحب حوافه.","Son klip silinemez. Kenarlarını sürükleyerek kısalt.","आख़िरी क्लिप हटाई नहीं जा सकती। किनारे खींचकर इसे छोटा करें।"],
  "Klip je vymazaný, ostatné sa posunuli k sebe. Späť ho vrátiš tlačidlom Späť.": ["Klip je smazaný, ostatní se posunuly k sobě. Zpět ho vrátíš tlačítkem Zpět.","Clip deleted, the others moved together. Bring it back with Undo.","Clip eliminado, los demás se juntaron. Recupéralo con Deshacer.","تم حذف المقطع وتجمّعت المقاطع الأخرى. استعده بزر تراجع.","Klip silindi, diğerleri birleşti. Geri al ile geri getir.","क्लिप हटा दी गई, बाकी आपस में मिल गईं। पूर्ववत से वापस लाएँ।"],
  "Klip je orezaný.": ["Klip je oříznutý.","Clip trimmed.","Clip recortado.","تم قص المقطع.","Klip kırpıldı.","क्लिप ट्रिम हो गई।"],
  "Vrátené späť.": ["Vráceno zpět.","Undone.","Deshecho.","تم التراجع.","Geri alındı.","पूर्ववत किया गया।"],
  "Znova použité.": ["Znovu použito.","Redone.","Rehecho.","تمت الإعادة.","Yinelendi.","फिर से किया गया।"],
  "Video je opäť celé a bez efektov. Späť to vrátiš tlačidlom Späť.": ["Video je opět celé a bez efektů. Zpět to vrátíš tlačítkem Zpět.","The video is whole again and without effects. Undo brings it back.","El vídeo vuelve a estar entero y sin efectos. Deshacer lo revierte.","أصبح الفيديو كاملًا من جديد وبلا تأثيرات. التراجع يعيده.","Video yeniden tam ve efektsiz. Geri al ile geri dönebilirsin.","वीडियो फिर से पूरा और बिना इफ़ेक्ट का है। पूर्ववत से वापस लाएँ।"],
  "Rýchlosť": ["Rychlost","Speed","Velocidad","السرعة","Hız","स्पीड"],
  "Rýchlosť klipu": ["Rychlost klipu","Clip speed","Velocidad del clip","سرعة المقطع","Klip hızı","क्लिप स्पीड"],
  "Efekt": ["Efekt","Effect","Efecto","التأثير","Efekt","इफ़ेक्ट"],
  "Efekt klipu": ["Efekt klipu","Clip effect","Efecto del clip","تأثير المقطع","Klip efekti","क्लिप इफ़ेक्ट"],
  "Filter": ["Filtr","Filter","Filtro","الفلتر","Filtre","फ़िल्टर"],
  "Filter klipu": ["Filtr klipu","Clip filter","Filtro del clip","فلتر المقطع","Klip filtresi","क्लिप फ़िल्टर"],
  "Na všetky klipy": ["Na všechny klipy","Apply to all clips","Aplicar a todos los clips","تطبيق على كل المقاطع","Tüm kliplere uygula","सभी क्लिप पर लागू करें"],
  "Prechod": ["Přechod","Transition","Transición","الانتقال","Geçiş","ट्रांज़िशन"],
  "Na všetky prechody": ["Na všechny přechody","Apply to all transitions","Aplicar a todas las transiciones","تطبيق على كل الانتقالات","Tüm geçişlere uygula","सभी ट्रांज़िशन पर लागू करें"],
  "Dĺžka prechodu": ["Délka přechodu","Transition length","Duración de la transición","مدة الانتقال","Geçiş süresi","ट्रांज़िशन की अवधि"],
  "Dĺžka výsledného videa": ["Délka výsledného videa","Final video length","Duración del vídeo final","مدة الفيديو النهائي","Son video süresi","अंतिम वीडियो की अवधि"],
  "Obnoviť pôvodné video": ["Obnovit původní video","Restore original video","Restaurar el vídeo original","استعادة الفيديو الأصلي","Orijinal videoyu geri yükle","मूल वीडियो वापस लाएँ"],
  "Žiadny": ["Žádný","None","Ninguno","بلا","Yok","कोई नहीं"],
  "Stmievačka": ["Stmívačka","Fade","Fundido","تلاشٍ","Karartma","फ़ेड"],
  "Záblesk": ["Záblesk","Flash","Destello","وميض","Parlama","फ़्लैश"],
  "Rozmazanie": ["Rozmazání","Blur","Desenfoque","تمويه","Bulanıklık","ब्लर"],
  "Švih": ["Švih","Whip","Barrido","انزلاق سريع","Savrulma","व्हिप"],
  "Otočenie": ["Otočení","Spin","Giro","دوران","Döndürme","स्पिन"],
  "Glitch": ["Glitch","Glitch","Glitch","Glitch","Glitch","ग्लिच"],
  "Zoom": ["Zoom","Zoom","Zoom","تكبير","Yakınlaştırma","ज़ूम"],
  "Pulz": ["Pulz","Pulse","Pulso","نبض","Nabız","पल्स"],
  "Otras": ["Otřes","Shake","Temblor","اهتزاز","Sarsıntı","शेक"],
  "VHS": ["VHS","VHS","VHS","VHS","VHS","VHS"],
  "Pôvodný": ["Původní","Original","Original","الأصلي","Orijinal","मूल"],
  "Živý": ["Živý","Vivid","Vivo","حيوي","Canlı","चटक"],
  "Teplý": ["Teplý","Warm","Cálido","دافئ","Sıcak","गर्म"],
  "Studený": ["Studený","Cool","Frío","بارد","Soğuk","ठंडा"],
  "Čiernobiely": ["Černobílý","B&W","B/N","أبيض وأسود","S/B","श्वेत-श्याम"],
  "Vintage": ["Vintage","Vintage","Vintage","عتيق","Vintage","विंटेज"],
  "Film": ["Film","Film","Cine","سينمائي","Film","फ़िल्म"],
  "Kvalita videa": ["Kvalita videa","Video quality","Calidad del vídeo","جودة الفيديو","Video kalitesi","वीडियो क्वालिटी"],
  "Kvalita exportu": ["Kvalita exportu","Export quality","Calidad de exportación","جودة التصدير","Dışa aktarma kalitesi","एक्सपोर्ट क्वालिटी"],
  "Originál": ["Originál","Original","Original","الأصلي","Orijinal","मूल"],
  "do 1080p": ["do 1080p","up to 1080p","hasta 1080p","حتى 1080p","1080p'ye kadar","1080p तक"],
  "Menší súbor": ["Menší soubor","Smaller file","Archivo más pequeño","ملف أصغر","Daha küçük dosya","छोटी फ़ाइल"],
  "Video s titulkami": ["Video s titulky","Video with subtitles","Vídeo con subtítulos","فيديو مع ترجمة","Altyazılı video","सबटाइटल वाला वीडियो"],
  "Exportovať tento formát": ["Exportovat tento formát","Export this format","Exportar este formato","تصدير هذا التنسيق","Bu formatı dışa aktar","यह फ़ॉर्मैट एक्सपोर्ट करें"],
  "Exportovať všetky formáty (9:16, 1:1, 16:9)": ["Exportovat všechny formáty (9:16, 1:1, 16:9)","Export all formats (9:16, 1:1, 16:9)","Exportar todos los formatos (9:16, 1:1, 16:9)","تصدير كل التنسيقات (9:16، 1:1، 16:9)","Tüm formatları dışa aktar (9:16, 1:1, 16:9)","सभी फ़ॉर्मैट एक्सपोर्ट करें (9:16, 1:1, 16:9)"],
  "Exportovať všetko": ["Exportovat vše","Export all","Exportar todo","تصدير الكل","Tümünü dışa aktar","सब एक्सपोर्ट करें"],
  "Zrušiť export": ["Zrušit export","Cancel export","Cancelar exportación","إلغاء التصدير","Dışa aktarmayı iptal et","एक्सपोर्ट रद्द करें"],
  "Export je zrušený.": ["Export je zrušený.","Export cancelled.","Exportación cancelada.","تم إلغاء التصدير.","Dışa aktarma iptal edildi.","एक्सपोर्ट रद्द हो गया।"],
  "Export zlyhal.": ["Export selhal.","Export failed.","La exportación falló.","فشل التصدير.","Dışa aktarma başarısız oldu.","एक्सपोर्ट विफल रहा।"],
  "Pripravujem…": ["Připravuji…","Preparing…","Preparando…","جارٍ التحضير…","Hazırlanıyor…","तैयारी हो रही है…"],
  "Hotovo, stiahni tlačidlom ⬇": ["Hotovo, stáhni tlačítkem ⬇","Done, download with ⬇","Listo, descárgalo con ⬇","تم، نزّله بزر ⬇","Hazır, ⬇ ile indir","हो गया, ⬇ से डाउनलोड करें"],
  "Len titulky": ["Jen titulky","Subtitles only","Solo subtítulos","الترجمة فقط","Yalnızca altyazılar","केवल सबटाइटल"],
  "Stiahnuť SRT": ["Stáhnout SRT","Download SRT","Descargar SRT","تنزيل SRT","SRT indir","SRT डाउनलोड करें"],
  "Stiahnuť VTT": ["Stáhnout VTT","Download VTT","Descargar VTT","تنزيل VTT","VTT indir","VTT डाउनलोड करें"],
  "Nič sa neukladá na server. Po zatvorení stránky je všetko preč, preto si súbory stiahni.": ["Nic se neukládá na server. Po zavření stránky je vše pryč, proto si soubory stáhni.","Nothing is stored on the server. When you close the page everything is gone, so download your files.","No se guarda nada en el servidor. Al cerrar la página todo desaparece, así que descarga tus archivos.","لا يُحفظ شيء على الخادم. عند إغلاق الصفحة يختفي كل شيء، لذا نزّل ملفاتك.","Sunucuda hiçbir şey saklanmaz. Sayfayı kapattığında her şey silinir, bu yüzden dosyalarını indir.","सर्वर पर कुछ भी सेव नहीं होता। पेज बंद करने पर सब हट जाता है, इसलिए अपनी फ़ाइलें डाउनलोड कर लें।"],
  " 4K export trvá dlhšie a na slabších zariadeniach môže sekať.": [" Export 4K trvá déle a na slabších zařízeních se může sekat."," 4K export takes longer and may stutter on weaker devices."," La exportación en 4K tarda más y puede ir a saltos en dispositivos menos potentes."," يستغرق تصدير 4K وقتًا أطول وقد يتقطع على الأجهزة الأضعف."," 4K dışa aktarma daha uzun sürer ve zayıf cihazlarda takılabilir."," 4K एक्सपोर्ट में ज़्यादा समय लगता है और कमज़ोर डिवाइस पर अटक सकता है।"],
  " Priblížený výrez používa menej pixelov originálu, obraz môže byť menej ostrý.": [" Přiblížený výřez používá méně pixelů originálu, obraz může být méně ostrý."," A zoomed crop uses fewer pixels of the original, the picture may be less sharp."," Un recorte ampliado usa menos píxeles del original, la imagen puede verse menos nítida."," القص المكبَّر يستخدم بكسلات أقل من الأصل، وقد تكون الصورة أقل وضوحًا."," Yakınlaştırılmış kırpma orijinalin daha az pikselini kullanır, görüntü daha az net olabilir."," ज़ूम किया क्रॉप मूल के कम पिक्सेल इस्तेमाल करता है, तस्वीर कम साफ़ हो सकती है।"],
  "Knižnica štýlov": ["Knihovna stylů","Style library","Biblioteca de estilos","مكتبة الأنماط","Stil kütüphanesi","स्टाइल लाइब्रेरी"],
  "Všetky štýly titulkov, ktoré Titulkovač má, v pohybe. Klikni na Použiť a štýl sa nastaví pre vybraného rečníka.": ["Všechny styly titulků, které Titulkovač má, v pohybu. Klikni na Použít a styl se nastaví pro vybraného mluvčího.","All subtitle styles in motion. Click Use to set the style for the selected speaker.","Todos los estilos de subtítulos en movimiento. Haz clic en Usar para aplicar el estilo al hablante seleccionado.","كل أنماط الترجمة متحركة. انقر على استخدام لتطبيق النمط على المتحدث المحدد.","Tüm altyazı stilleri hareket halinde. Stili seçili konuşmacıya uygulamak için Kullan'a tıkla.","सभी सबटाइटल स्टाइल चलते हुए। चुने हुए वक्ता पर स्टाइल लगाने के लिए इस्तेमाल करें पर क्लिक करें।"],
  "← Späť na video": ["← Zpět na video","← Back to video","← Volver al vídeo","→ العودة إلى الفيديو","← Videoya dön","← वीडियो पर वापस"],
  "Použiť": ["Použít","Use","Usar","استخدام","Kullan","इस्तेमाल करें"],
  "Použitý": ["Použitý","In use","En uso","مستخدَم","Kullanımda","इस्तेमाल में"],
  "Takto budú vyzerať": ["Takhle budou vypadat","This is how","Así se verán","هكذا ستبدو","Videodaki altyazıların","ऐसे दिखेंगे"],
  "tvoje titulky vo videu": ["tvoje titulky ve videu","your subtitles will look","tus subtítulos en el vídeo","ترجمتك في الفيديو","böyle görünecek","वीडियो में आपके सबटाइटल"],
  "Fonty pre titulky. Nájdeš ich aj vo výbere fontu pri štýle titulkov.": ["Fonty pro titulky. Najdeš je i ve výběru fontu u stylu titulků.","Fonts for subtitles. You'll also find them in the font picker in the subtitle style.","Fuentes para subtítulos. También las encontrarás en el selector de fuente del estilo.","خطوط للترجمة. تجدها أيضًا في منتقي الخط في نمط الترجمة.","Altyazılar için yazı tipleri. Bunları stilin yazı tipi seçicisinde de bulursun.","सबटाइटल के लिए फ़ॉन्ट। ये आपको स्टाइल के फ़ॉन्ट चयन में भी मिलेंगे।"],
  "Fonty pre titulky. Nahraté fonty ostanú len v tomto prehliadači a nájdeš ich aj vo výbere fontu pri štýle titulkov.": ["Fonty pro titulky. Nahrané fonty zůstanou jen v tomto prohlížeči a najdeš je i ve výběru fontu u stylu titulků.","Fonts for subtitles. Uploaded fonts stay only in this browser; you'll also find them in the font picker.","Fuentes para subtítulos. Las fuentes subidas se quedan solo en este navegador; también están en el selector de fuente.","خطوط للترجمة. الخطوط المرفوعة تبقى في هذا المتصفح فقط، وتجدها أيضًا في منتقي الخط.","Altyazılar için yazı tipleri. Yüklenen yazı tipleri yalnızca bu tarayıcıda kalır; yazı tipi seçicide de bulunur.","सबटाइटल के लिए फ़ॉन्ट। अपलोड किए फ़ॉन्ट केवल इसी ब्राउज़र में रहते हैं; ये फ़ॉन्ट चयन में भी मिलेंगे।"],
  "Spoločné fonty pre titulky. Nahraný font uvidia a môžu použiť všetci používatelia. Z jednej IP adresy sa dá nahrať jeden font za týždeň.": ["Společné fonty pro titulky. Nahraný font uvidí a mohou použít všichni uživatelé. Z jedné IP adresy lze nahrát jeden font za týden.","Shared subtitle fonts. Every user can see and use an uploaded font. One font per week can be uploaded from one IP address.","Fuentes compartidas para subtítulos. Todos los usuarios pueden ver y usar una fuente subida. Se puede subir una fuente por semana desde cada dirección IP.","خطوط ترجمة مشتركة. يمكن لجميع المستخدمين رؤية الخط المرفوع واستخدامه. يمكن رفع خط واحد أسبوعيًا من كل عنوان IP.","Ortak altyazı yazı tipleri. Yüklenen bir yazı tipini tüm kullanıcılar görebilir ve kullanabilir. Bir IP adresinden haftada bir yazı tipi yüklenebilir.","साझा सबटाइटल फ़ॉन्ट। अपलोड किया गया फ़ॉन्ट सभी उपयोगकर्ता देख और इस्तेमाल कर सकते हैं। एक IP पते से हफ़्ते में एक फ़ॉन्ट अपलोड किया जा सकता है।"],
  "Pridať font": ["Přidat font","Add font","Añadir fuente","إضافة خط","Yazı tipi ekle","फ़ॉन्ट जोड़ें"],
  "— pretiahni sem súbor alebo klikni": ["— přetáhni sem soubor nebo klikni","— drop a file here or click","— suelta aquí un archivo o haz clic","— أفلت ملفًا هنا أو انقر","— buraya bir dosya bırak veya tıkla","— फ़ाइल यहाँ छोड़ें या क्लिक करें"],
  "TTF, OTF, WOFF alebo WOFF2": ["TTF, OTF, WOFF nebo WOFF2","TTF, OTF, WOFF or WOFF2","TTF, OTF, WOFF o WOFF2","TTF أو OTF أو WOFF أو WOFF2","TTF, OTF, WOFF veya WOFF2","TTF, OTF, WOFF या WOFF2"],
  "TTF, OTF, WOFF alebo WOFF2, najviac 15 MB": ["TTF, OTF, WOFF nebo WOFF2, nejvýše 15 MB","TTF, OTF, WOFF or WOFF2, up to 15 MB","TTF, OTF, WOFF o WOFF2, hasta 15 MB","TTF أو OTF أو WOFF أو WOFF2، حتى 15 ميغابايت","TTF, OTF, WOFF veya WOFF2, en fazla 15 MB","TTF, OTF, WOFF या WOFF2, अधिकतम 15 MB"],
  "Potvrdzujem, že tento font môžem zdieľať (je voľne šíriteľný alebo mám naň práva). Bude dostupný všetkým používateľom.": ["Potvrzuji, že tento font mohu sdílet (je volně šiřitelný nebo k němu mám práva). Bude dostupný všem uživatelům.","I confirm I may share this font (it's freely distributable or I hold the rights). It will be available to all users.","Confirmo que puedo compartir esta fuente (es de libre distribución o tengo los derechos). Estará disponible para todos los usuarios.","أؤكد أنه يحق لي مشاركة هذا الخط (يمكن توزيعه بحرية أو أملك حقوقه). سيكون متاحًا لجميع المستخدمين.","Bu yazı tipini paylaşabileceğimi onaylıyorum (serbestçe dağıtılabilir veya haklarına sahibim). Tüm kullanıcılara açık olacak.","मैं पुष्टि करता/करती हूँ कि मैं यह फ़ॉन्ट साझा कर सकता/सकती हूँ (यह मुक्त रूप से वितरित किया जा सकता है या इसके अधिकार मेरे पास हैं)। यह सभी उपयोगकर्ताओं के लिए उपलब्ध होगा।"],
  "Text ukážky": ["Text ukázky","Sample text","Texto de ejemplo","نص المعاينة","Örnek metin","नमूना टेक्स्ट"],
  "Ahoj, toto sú moje titulky": ["Ahoj, tohle jsou moje titulky","Hello, these are my subtitles","Hola, estos son mis subtítulos","مرحبا، هذه ترجماتي","Merhaba, bunlar benim altyazılarım","नमस्ते, ये मेरे सबटाइटल हैं"],
  "Vstavaný": ["Vestavěný","Built-in","Integrada","مدمج","Yerleşik","बिल्ट-इन"],
  "Vlastný": ["Vlastní","Custom","Propia","مخصص","Özel","अपना"],
  "Spoločný": ["Společný","Shared","Compartida","مشترك","Ortak","साझा"],
  "Odobrať font": ["Odebrat font","Remove font","Quitar fuente","إزالة الخط","Yazı tipini kaldır","फ़ॉन्ट हटाएँ"],
  "Najprv potvrď, že font môžeš zdieľať so všetkými.": ["Nejdřív potvrď, že font můžeš sdílet se všemi.","First confirm that you may share the font with everyone.","Primero confirma que puedes compartir la fuente con todos.","أكّد أولًا أنه يحق لك مشاركة الخط مع الجميع.","Önce yazı tipini herkesle paylaşabileceğini onayla.","पहले पुष्टि करें कि आप फ़ॉन्ट सबके साथ साझा कर सकते हैं।"],
  "Nahrávam font na server…": ["Nahrávám font na server…","Uploading font…","Subiendo la fuente…","جارٍ رفع الخط…","Yazı tipi yükleniyor…","फ़ॉन्ट अपलोड हो रहा है…"],
  "Nahranie fontu zlyhalo.": ["Nahrání fontu selhalo.","Font upload failed.","No se pudo subir la fuente.","فشل رفع الخط.","Yazı tipi yüklenemedi.","फ़ॉन्ट अपलोड विफल रहा।"],
  "Spojenie so serverom zlyhalo. Skús to znova.": ["Spojení se serverem selhalo. Zkus to znovu.","Connection to the server failed. Try again.","Falló la conexión con el servidor. Inténtalo de nuevo.","فشل الاتصال بالخادم. حاول مرة أخرى.","Sunucuyla bağlantı başarısız oldu. Tekrar dene.","सर्वर से कनेक्शन विफल रहा। फिर से कोशिश करें।"],
  "Server neodpovedal správne.": ["Server neodpověděl správně.","The server responded incorrectly.","El servidor no respondió correctamente.","لم يستجب الخادم بشكل صحيح.","Sunucu doğru yanıt vermedi.","सर्वर ने सही जवाब नहीं दिया।"],
  "Tento súbor sa nepodarilo načítať ako font.": ["Tento soubor se nepodařilo načíst jako font.","This file couldn't be loaded as a font.","Este archivo no se pudo cargar como fuente.","تعذّر تحميل هذا الملف كخط.","Bu dosya yazı tipi olarak yüklenemedi.","यह फ़ाइल फ़ॉन्ट के रूप में लोड नहीं हो सकी।"],
  "Font je príliš veľký (najviac 15 MB).": ["Font je příliš velký (nejvýše 15 MB).","The font is too large (max 15 MB).","La fuente es demasiado grande (máx. 15 MB).","الخط كبير جدًا (الحد الأقصى 15 ميغابايت).","Yazı tipi çok büyük (en fazla 15 MB).","फ़ॉन्ट बहुत बड़ा है (अधिकतम 15 MB)।"],
  "Toto nie je font. Prijímame len súbory TTF, OTF, WOFF a WOFF2.": ["Tohle není font. Přijímáme jen soubory TTF, OTF, WOFF a WOFF2.","This isn't a font. Only TTF, OTF, WOFF and WOFF2 files are accepted.","Esto no es una fuente. Solo se aceptan archivos TTF, OTF, WOFF y WOFF2.","هذا ليس خطًا. تُقبل ملفات TTF وOTF وWOFF وWOFF2 فقط.","Bu bir yazı tipi değil. Yalnızca TTF, OTF, WOFF ve WOFF2 dosyaları kabul edilir.","यह फ़ॉन्ट नहीं है। केवल TTF, OTF, WOFF और WOFF2 फ़ाइलें स्वीकार की जाती हैं।"],
  "Font musí mať od 1 kB do 10 MB.": ["Font musí mít od 1 kB do 10 MB.","The font must be between 1 kB and 10 MB.","La fuente debe tener entre 1 kB y 10 MB.","يجب أن يكون حجم الخط بين 1 كيلوبايت و10 ميغابايت.","Yazı tipi 1 kB ile 10 MB arasında olmalıdır.","फ़ॉन्ट 1 kB से 10 MB के बीच होना चाहिए।"],
  "Font je príliš veľký.": ["Font je příliš velký.","The font is too large.","La fuente es demasiado grande.","الخط كبير جدًا.","Yazı tipi çok büyük.","फ़ॉन्ट बहुत बड़ा है।"],
  "Súbor sa nepodarilo nahrať.": ["Soubor se nepodařilo nahrát.","The file couldn't be uploaded.","No se pudo subir el archivo.","تعذّر رفع الملف.","Dosya yüklenemedi.","फ़ाइल अपलोड नहीं हो सकी।"],
  "Knižnica fontov je plná.": ["Knihovna fontů je plná.","The font library is full.","La biblioteca de fuentes está llena.","مكتبة الخطوط ممتلئة.","Yazı tipi kütüphanesi dolu.","फ़ॉन्ट लाइब्रेरी भर गई है।"],
  "Font sa nepodarilo uložiť na server.": ["Font se nepodařilo uložit na server.","The font couldn't be saved on the server.","No se pudo guardar la fuente en el servidor.","تعذّر حفظ الخط على الخادم.","Yazı tipi sunucuya kaydedilemedi.","फ़ॉन्ट सर्वर पर सेव नहीं हो सका।"],
  "Neplatná požiadavka.": ["Neplatný požadavek.","Invalid request.","Solicitud no válida.","طلب غير صالح.","Geçersiz istek.","अमान्य अनुरोध।"],
  "Server nevie vytvoriť priečinok data/. Nastav mu práva na zápis.": ["Server neumí vytvořit složku data/. Nastav mu práva k zápisu.","The server can't create the data/ folder. Give it write permission.","El servidor no puede crear la carpeta data/. Dale permiso de escritura.","لا يستطيع الخادم إنشاء المجلد data/. امنحه صلاحية الكتابة.","Sunucu data/ klasörünü oluşturamıyor. Yazma izni ver.","सर्वर data/ फ़ोल्डर नहीं बना सकता। उसे लिखने की अनुमति दें।"],
  "Ahoj, vitajte pri ukážke aplikácie.": ["Ahoj, vítejte u ukázky aplikace.","Hi, welcome to the app demo.","Hola, bienvenidos a la demo de la aplicación.","مرحبًا، أهلًا بكم في العرض التجريبي للتطبيق.","Merhaba, uygulama demosuna hoş geldiniz.","नमस्ते, ऐप के डेमो में आपका स्वागत है।"],
  "Nahráte video a titulky sa vytvoria automaticky.": ["Nahrajete video a titulky se vytvoří automaticky.","Upload a video and subtitles are created automatically.","Sube un vídeo y los subtítulos se crean automáticamente.","ارفع فيديو وستُنشأ الترجمة تلقائيًا.","Bir video yükleyin, altyazılar otomatik oluşturulsun.","वीडियो अपलोड करें और सबटाइटल अपने आप बन जाएँगे।"],
  "Vyberiete štýl z ponuky,": ["Vyberete styl z nabídky,","Pick a style from the menu,","Elige un estilo del menú,","اختر نمطًا من القائمة،","Menüden bir stil seçin,","मेनू से एक स्टाइल चुनें,"],
  "zmeníte farbu textu, font alebo podklad,": ["změníte barvu textu, font nebo podklad,","change the text color, font or background,","cambia el color del texto, la fuente o el fondo,","غيّر لون النص أو الخط أو الخلفية،","metin rengini, yazı tipini veya arka planı değiştirin,","टेक्स्ट का रंग, फ़ॉन्ट या बैकग्राउंड बदलें,"],
  "a práve vyslovené slovo sa zvýrazní.": ["a právě vyslovené slovo se zvýrazní.","and the spoken word gets highlighted.","y la palabra pronunciada se resalta.","وتُميَّز الكلمة المنطوقة.","ve söylenen kelime vurgulanır.","और बोला गया शब्द हाइलाइट होता है।"],
  "Španielčina": ["Španělština","Spanish","Español","الإسبانية","İspanyolca","स्पेनिश"],
  "Arabčina": ["Arabština","Arabic","Árabe","العربية","Arapça","अरबी"],
  "Turečtina": ["Turečtina","Turkish","Turco","التركية","Türkçe","तुर्की"],
  "Hindčina": ["Hindština","Hindi","Hindi","الهندية","Hintçe","हिन्दी"],
  "Po nahratí videa sa tu zobrazí text titulkov.": ["Po nahrání videa se tu zobrazí text titulků.", "The subtitle text appears here after you upload a video.", "El texto de los subtítulos aparecerá aquí cuando subas un vídeo.", "سيظهر نص الترجمة هنا بعد رفع الفيديو.", "Video yükledikten sonra altyazı metni burada görünür.", "वीडियो अपलोड करने के बाद सबटाइटल टेक्स्ट यहाँ दिखेगा।"],
  "Titulkovač": ["Titulkovač", "Titulkovač", "Titulkovač", "Titulkovač", "Titulkovač", "Titulkovač"],
  "Rečník a štýl vybraných titulkov": ["Mluvčí a styl vybraných titulků", "Speaker and style of selected subtitles", "Hablante y estilo de los subtítulos seleccionados", "المتحدث ونمط الترجمات المحددة", "Seçili altyazıların konuşmacısı ve stili", "चुने हुए सबटाइटल का वक्ता और स्टाइल"],
  "Poloha ukážok": ["Pozice ukázek", "Preview position", "Posición de las muestras", "موضع المعاينات", "Önizleme konumu", "प्रीव्यू की जगह"],
  "Spodná tretina": ["Spodní třetina", "Lower third", "Tercio inferior", "الثلث السفلي", "Alt üçte bir", "निचला तिहाई"],
  "Animácia": ["Animace", "Animation", "Animación", "الحركة", "Animasyon", "एनिमेशन"],
  "Žiadna": ["Žádná", "None", "Ninguna", "بلا", "Yok", "कोई नहीं"],
  "Pop": ["Pop", "Pop", "Pop", "ظهور", "Pop", "पॉप"],
  "Karaoke": ["Karaoke", "Karaoke", "Karaoke", "كاريوكي", "Karaoke", "कराओके"],
  "Rámček": ["Rámeček", "Box", "Caja", "إطار", "Kutu", "बॉक्स"],
  "Skok": ["Skok", "Bounce", "Rebote", "قفزة", "Zıplama", "उछाल"],
  "Písací stroj": ["Psací stroj", "Typewriter", "Máquina de escribir", "آلة كاتبة", "Daktilo", "टाइपराइटर"],
  "Plynulé slová": ["Plynulá slova", "Fade in", "Aparición suave", "ظهور تدريجي", "Yumuşak giriş", "फ़ेड इन"],
  "Vysunutie": ["Vysunutí", "Slide up", "Deslizar", "انزلاق", "Kayma", "स्लाइड"],
  "Zväčšenie": ["Zvětšení", "Word zoom", "Zoom de palabra", "تكبير الكلمة", "Kelime büyütme", "शब्द ज़ूम"],
  "Žiara": ["Záře", "Glow", "Brillo", "توهج", "Parıltı", "ग्लो"],
  "Animácia platí pre bežné štýly, Dynamický štýl má vlastnú.": ["Animace platí pro běžné styly, Dynamický styl má vlastní.", "Animation applies to regular styles; the Dynamic style has its own.", "La animación se aplica a los estilos normales; el estilo Dinámico tiene la suya.", "تنطبق الحركة على الأنماط العادية، وللنمط الديناميكي حركته الخاصة.", "Animasyon normal stillere uygulanır; Dinamik stilin kendi animasyonu var.", "एनिमेशन सामान्य स्टाइल पर लागू होता है; डायनैमिक स्टाइल का अपना है।"],
  "Animácia titulkov": ["Animace titulků", "Subtitle animation", "Animación de subtítulos", "حركة الترجمة", "Altyazı animasyonu", "सबटाइटल एनिमेशन"],
  "Animácie titulkov": ["Animace titulků", "Subtitle animations", "Animaciones de subtítulos", "حركات الترجمة", "Altyazı animasyonları", "सबटाइटल एनिमेशन"],
  "Štýly titulkov": ["Styly titulků", "Subtitle styles", "Estilos de subtítulos", "أنماط الترجمة", "Altyazı stilleri", "सबटाइटल स्टाइल"],
};// Texty s premennými časťami: [vzor, { jazyk: šablóna alebo funkcia }]; $1… sa doplní (a ak je v slovníku, aj preloží).
const pl = (n, one, few, many) => (+n === 1 ? one : +n > 1 && +n < 5 ? few : many);
const PATTERNS = [
  [/^Klip (\d+)$/, { cs: "Klip $1", en: "Clip $1", es: "Clip $1", ar: "مقطع $1", tr: "Klip $1", hi: "क्लिप $1" }],
  [/^(.+) \((\d+:\d+)\)$/, { cs: "$1 ($2)", en: "$1 ($2)", es: "$1 ($2)", ar: "$1 ($2)", tr: "$1 ($2)", hi: "$1 ($2)" }],
  [/^Klip (\d+) z (\d+)$/, { cs: "Klip $1 z $2", en: "Clip $1 of $2", es: "Clip $1 de $2", ar: "المقطع $1 من $2", tr: "Klip $1 / $2", hi: "क्लिप $1 / $2" }],
  [/^Klip (\d+), (.+)$/, { cs: "Klip $1, $2", en: "Clip $1, $2", es: "Clip $1, $2", ar: "مقطع $1، $2", tr: "Klip $1, $2", hi: "क्लिप $1, $2" }],
  [/^(\d+) (klip|klipy|klipov)$/, { cs: (r) => r[1] + " " + pl(r[1], "klip", "klipy", "klipů"), en: (r) => r[1] + (r[1] === "1" ? " clip" : " clips"), es: (r) => r[1] + (r[1] === "1" ? " clip" : " clips"), ar: (r) => "المقاطع: " + r[1], tr: (r) => r[1] + " klip", hi: (r) => r[1] + " क्लिप" }],
  [/^(\d+) (titulok|titulky|titulkov)$/, { cs: (r) => r[1] + " " + pl(r[1], "titulek", "titulky", "titulků"), en: (r) => r[1] + (r[1] === "1" ? " subtitle" : " subtitles"), es: (r) => r[1] + (r[1] === "1" ? " subtítulo" : " subtítulos"), ar: (r) => "الترجمات: " + r[1], tr: (r) => r[1] + " altyazı", hi: (r) => r[1] + " सबटाइटल" }],
  [/^Rečník (\d+)$/, { cs: "Mluvčí $1", en: "Speaker $1", es: "Hablante $1", ar: "المتحدث $1", tr: "Konuşmacı $1", hi: "वक्ता $1" }],
  [/^Prechod: klip (\d+) → (\d+)$/, { cs: "Přechod: klip $1 → $2", en: "Transition: clip $1 → $2", es: "Transición: clip $1 → $2", ar: "الانتقال: المقطع $1 ← $2", tr: "Geçiş: klip $1 → $2", hi: "ट्रांज़िशन: क्लिप $1 → $2" }],
  [/^Prechod: (.+)$/, { cs: "Přechod: $1", en: "Transition: $1", es: "Transición: $1", ar: "الانتقال: $1", tr: "Geçiş: $1", hi: "ट्रांज़िशन: $1" }],
  [/^Štýl — (.+)$/, { cs: "Styl — $1", en: "Style — $1", es: "Estilo — $1", ar: "النمط — $1", tr: "Stil — $1", hi: "स्टाइल — $1" }],
  [/^Štýl (.+) pre vybrané titulky$/, { cs: "Styl $1 pro vybrané titulky", en: "Style $1 for selected subtitles", es: "Estilo $1 para los subtítulos seleccionados", ar: "النمط $1 للترجمات المحددة", tr: "Seçili altyazılar için $1 stili", hi: "चुने हुए सबटाइटल के लिए स्टाइल $1" }],
  [/^Štýl (.+)$/, { cs: "Styl $1", en: "Style $1", es: "Estilo $1", ar: "النمط $1", tr: "Stil $1", hi: "स्टाइल $1" }],
  [/^Pozícia — (.+) · (.+)$/, { cs: "Pozice — $1 · $2", en: "Position — $1 · $2", es: "Posición — $1 · $2", ar: "الموضع — $1 · $2", tr: "Konum — $1 · $2", hi: "जगह — $1 · $2" }],
  [/^Pozícia · (.+)$/, { cs: "Pozice · $1", en: "Position · $1", es: "Posición · $1", ar: "الموضع · $1", tr: "Konum · $1", hi: "जगह · $1" }],
  [/^Odobrať font (.+)$/, { cs: "Odebrat font $1", en: "Remove font $1", es: "Quitar la fuente $1", ar: "إزالة الخط $1", tr: "$1 yazı tipini kaldır", hi: "फ़ॉन्ट $1 हटाएँ" }],
  [/^Odobrať (.+)$/, { cs: "Odebrat $1", en: "Remove $1", es: "Quitar $1", ar: "إزالة $1", tr: "$1 kaldır", hi: "$1 हटाएँ" }],
  [/^Animácia (.+)$/, { cs: "Animace $1", en: "Animation $1", es: "Animación $1", ar: "الحركة $1", tr: "Animasyon $1", hi: "एनिमेशन $1" }],
  [/^Animovaná ukážka štýlu (.+)$/, { cs: "Animovaná ukázka stylu $1", en: "Animated preview of style $1", es: "Vista previa animada del estilo $1", ar: "معاينة متحركة للنمط $1", tr: "$1 stilinin animasyonlu önizlemesi", hi: "स्टाइल $1 का एनिमेटेड प्रीव्यू" }],
  [/^Text titulku (\d+)$/, { cs: "Text titulku $1", en: "Subtitle text $1", es: "Texto del subtítulo $1", ar: "نص الترجمة $1", tr: "Altyazı metni $1", hi: "सबटाइटल टेक्स्ट $1" }],
  [/^Náhľad (.+)$/, { cs: "Náhled $1", en: "Preview $1", es: "Vista previa $1", ar: "معاينة $1", tr: "Önizleme $1", hi: "प्रीव्यू $1" }],
  [/^Exportovať a stiahnuť (.+)$/, { cs: "Exportovat a stáhnout $1", en: "Export and download $1", es: "Exportar y descargar $1", ar: "تصدير وتنزيل $1", tr: "$1 dışa aktar ve indir", hi: "$1 एक्सपोर्ट और डाउनलोड करें" }],
  [/^(.+) · klikni a vyber iného rečníka$/, { cs: "$1 · klikni a vyber jiného mluvčího", en: "$1 · click to choose another speaker", es: "$1 · haz clic para elegir otro hablante", ar: "$1 · انقر لاختيار متحدث آخر", tr: "$1 · başka konuşmacı seçmek için tıkla", hi: "$1 · दूसरा वक्ता चुनने के लिए क्लिक करें" }],
  [/^Vybraté: (\d+) \S+ · (.+)$/, { cs: (r) => `Vybráno: ${r[1]} ${pl(r[1], "titulek", "titulky", "titulků")} · ${r[2]}`, en: (r) => `Selected: ${r[1]} ${r[1] === "1" ? "subtitle" : "subtitles"} · ${r[2]}`, es: (r) => `Seleccionados: ${r[1]} ${r[1] === "1" ? "subtítulo" : "subtítulos"} · ${r[2]}`, ar: (r) => `المحدد: ${r[1]} · ${r[2]}`, tr: (r) => `Seçili: ${r[1]} altyazı · ${r[2]}`, hi: (r) => `चुने गए: ${r[1]} सबटाइटल · ${r[2]}` }],
  [/^Exportujem… (\d+) %$/, { cs: "Exportuji… $1 %", en: "Exporting… $1%", es: "Exportando… $1 %", ar: "جارٍ التصدير… $1%", tr: "Dışa aktarılıyor… %$1", hi: "एक्सपोर्ट हो रहा है… $1%" }],
  [/^Exportujem formát (\d+) z (\d+) \((.+)\)…(?: (\d+) %)?$/, {
    cs: (r) => `Exportuji formát ${r[1]} z ${r[2]} (${r[3]})…` + (r[4] ? ` ${r[4]} %` : ""), en: (r) => `Exporting format ${r[1]} of ${r[2]} (${r[3]})…` + (r[4] ? ` ${r[4]}%` : ""),
    es: (r) => `Exportando formato ${r[1]} de ${r[2]} (${r[3]})…` + (r[4] ? ` ${r[4]} %` : ""), ar: (r) => `جارٍ تصدير التنسيق ${r[1]} من ${r[2]} (${r[3]})…` + (r[4] ? ` ${r[4]}%` : ""),
    tr: (r) => `Format ${r[1]} / ${r[2]} (${r[3]}) dışa aktarılıyor…` + (r[4] ? ` %${r[4]}` : ""), hi: (r) => `फ़ॉर्मैट ${r[1]} / ${r[2]} (${r[3]}) एक्सपोर्ट हो रहा है…` + (r[4] ? ` ${r[4]}%` : "") }],
  [/^Formát (\S+): (\d+)×(\d+) px, snímky za sekundu ako v origináli\.(.*)$/, {
    cs: (r) => `Formát ${r[1]}: ${r[2]}×${r[3]} px, snímky za sekundu jako v originálu.` + trParts(r[4]), en: (r) => `Format ${r[1]}: ${r[2]}×${r[3]} px, frame rate as in the original.` + trParts(r[4]),
    es: (r) => `Formato ${r[1]}: ${r[2]}×${r[3]} px, mismos fotogramas por segundo que el original.` + trParts(r[4]), ar: (r) => `التنسيق ${r[1]}: ${r[2]}×${r[3]} بكسل، بمعدل الإطارات نفسه في الأصل.` + trParts(r[4]),
    tr: (r) => `Format ${r[1]}: ${r[2]}×${r[3]} px, saniyedeki kare sayısı orijinaldeki gibi.` + trParts(r[4]), hi: (r) => `फ़ॉर्मैट ${r[1]}: ${r[2]}×${r[3]} px, फ़्रेम रेट मूल जैसा।` + trParts(r[4]) }],
  [/^Klip ide rýchlosťou (.+), trvá (.+)\.$/, { cs: "Klip běží rychlostí $1, trvá $2.", en: "The clip plays at $1 and lasts $2.", es: "El clip va a $1 y dura $2.", ar: "يعمل المقطع بسرعة $1 ومدته $2.", tr: "Klip $1 hızında oynatılıyor, süresi $2.", hi: "क्लिप $1 स्पीड पर चलती है, अवधि $2।" }],
  [/^Klip je presunutý na (\d+)\. miesto\.$/, { cs: "Klip je přesunutý na $1. místo.", en: "Clip moved to position $1.", es: "Clip movido a la posición $1.", ar: "تم نقل المقطع إلى الموضع $1.", tr: "Klip $1. sıraya taşındı.", hi: "क्लिप स्थान $1 पर ले जाई गई।" }],
  [/^Efekt (.+) je na všetkých klipoch\.$/, { cs: "Efekt $1 je na všech klipech.", en: "Effect $1 is on all clips.", es: "El efecto $1 está en todos los clips.", ar: "التأثير $1 مطبّق على كل المقاطع.", tr: "$1 efekti tüm kliplerde.", hi: "इफ़ेक्ट $1 सभी क्लिप पर है।" }],
  [/^Filter (.+) je na všetkých klipoch\.$/, { cs: "Filtr $1 je na všech klipech.", en: "Filter $1 is on all clips.", es: "El filtro $1 está en todos los clips.", ar: "الفلتر $1 مطبّق على كل المقاطع.", tr: "$1 filtresi tüm kliplerde.", hi: "फ़िल्टर $1 सभी क्लिप पर है।" }],
  [/^Prechod (.+) je medzi všetkými klipmi\.$/, { cs: "Přechod $1 je mezi všemi klipy.", en: "Transition $1 is between all clips.", es: "La transición $1 está entre todos los clips.", ar: "الانتقال $1 بين كل المقاطع.", tr: "$1 geçişi tüm klipler arasında.", hi: "ट्रांज़िशन $1 सभी क्लिप के बीच है।" }],
  [/^(\d+:\d+) z (\d+:\d+)$/, { cs: "$1 z $2", en: "$1 of $2", es: "$1 de $2", ar: "$1 من $2", tr: "$1 / $2", hi: "$1 / $2" }],
  [/^Font „(.+)“ je nahraný a dostupný pre všetkých\.$/, { cs: "Font „$1“ je nahraný a dostupný pro všechny.", en: "Font “$1” is uploaded and available to everyone.", es: "La fuente «$1» se subió y está disponible para todos.", ar: "تم رفع الخط «$1» وهو متاح للجميع.", tr: "“$1” yazı tipi yüklendi ve herkes kullanabilir.", hi: "फ़ॉन्ट “$1” अपलोड हो गया और सबके लिए उपलब्ध है।" }],
  [/^Font „(.+)“ je odobratý\.$/, { cs: "Font „$1“ je odebraný.", en: "Font “$1” removed.", es: "Fuente «$1» quitada.", ar: "تمت إزالة الخط «$1».", tr: "“$1” yazı tipi kaldırıldı.", hi: "फ़ॉन्ट “$1” हटा दिया गया।" }],
  [/^Font „(.+)“ je pridaný a vybraný\.$/, { cs: "Font „$1“ je přidaný a vybraný.", en: "Font “$1” added and selected.", es: "Fuente «$1» añadida y seleccionada.", ar: "تمت إضافة الخط «$1» واختياره.", tr: "“$1” yazı tipi eklendi ve seçildi.", hi: "फ़ॉन्ट “$1” जोड़ा और चुना गया।" }],
  [/^Font „(.+)“ je pridaný\.$/, { cs: "Font „$1“ je přidaný.", en: "Font “$1” added.", es: "Fuente «$1» añadida.", ar: "تمت إضافة الخط «$1».", tr: "“$1” yazı tipi eklendi.", hi: "फ़ॉन्ट “$1” जोड़ा गया।" }],
  [/^Tento font už v knižnici je ako „(.+)“\. Limit sa ti nezapočítal\.$/, { cs: "Tento font už v knihovně je jako „$1“. Limit se ti nezapočítal.", en: "This font is already in the library as “$1”. It didn't count toward your limit.", es: "Esta fuente ya está en la biblioteca como «$1». No contó para tu límite.", ar: "هذا الخط موجود بالفعل في المكتبة باسم «$1». لم يُحتسب ضمن حدّك.", tr: "Bu yazı tipi kütüphanede zaten “$1” olarak var. Sınırına sayılmadı.", hi: "यह फ़ॉन्ट लाइब्रेरी में पहले से “$1” नाम से है। यह आपकी सीमा में नहीं गिना गया।" }],
  [/^Titulky(?: rečníka (.+))? teraz používajú font „(.+)“\.$/, {
    cs: (r) => `Titulky${r[1] ? " mluvčího " + r[1] : ""} teď používají font „${r[2]}“.`, en: (r) => `Subtitles${r[1] ? " of speaker " + r[1] : ""} now use the font “${r[2]}”.`,
    es: (r) => `Los subtítulos${r[1] ? " del hablante " + r[1] : ""} ahora usan la fuente «${r[2]}».`, ar: (r) => `${r[1] ? "ترجمات المتحدث " + r[1] : "الترجمة"} تستخدم الآن الخط «${r[2]}».`,
    tr: (r) => `${r[1] ? r[1] + " konuşmacısının altyazıları" : "Altyazılar"} artık “${r[2]}” yazı tipini kullanıyor.`, hi: (r) => `${r[1] ? "वक्ता " + r[1] + " के सबटाइटल" : "सबटाइटल"} अब फ़ॉन्ट “${r[2]}” इस्तेमाल करते हैं।` }],
  [/^Z tvojej IP adresy už bol tento týždeň nahraný font\. Ďalší môžeš nahrať (.+)\.$/, { cs: "Z tvé IP adresy už byl tento týden nahrán font. Další můžeš nahrát $1.", en: "A font was already uploaded from your IP address this week. You can upload another on $1.", es: "Esta semana ya se subió una fuente desde tu dirección IP. Podrás subir otra el $1.", ar: "تم رفع خط من عنوان IP الخاص بك هذا الأسبوع. يمكنك رفع خط آخر في $1.", tr: "Bu hafta IP adresinden zaten bir yazı tipi yüklendi. Bir sonrakini $1 tarihinde yükleyebilirsin.", hi: "इस हफ़्ते आपके IP पते से एक फ़ॉन्ट पहले ही अपलोड हो चुका है। अगला आप $1 को अपलोड कर सकते हैं।" }],
  [/^Len súbory fontov TTF, OTF, WOFF alebo WOFF2, najviac (\d+) MB · 1 font za týždeň$/, { cs: "Jen soubory fontů TTF, OTF, WOFF nebo WOFF2, nejvýše $1 MB · 1 font za týden", en: "Font files only: TTF, OTF, WOFF or WOFF2, up to $1 MB · 1 font per week", es: "Solo archivos de fuente TTF, OTF, WOFF o WOFF2, hasta $1 MB · 1 fuente por semana", ar: "ملفات الخطوط فقط: TTF أو OTF أو WOFF أو WOFF2، حتى $1 ميغابايت · خط واحد أسبوعيًا", tr: "Yalnızca TTF, OTF, WOFF veya WOFF2 yazı tipi dosyaları, en fazla $1 MB · haftada 1 yazı tipi", hi: "केवल TTF, OTF, WOFF या WOFF2 फ़ॉन्ट फ़ाइलें, अधिकतम $1 MB · हफ़्ते में 1 फ़ॉन्ट" }],
  [/^Font je príliš veľký \(najviac (\d+) MB\)\.$/, { cs: "Font je příliš velký (nejvýše $1 MB).", en: "The font is too large (max $1 MB).", es: "La fuente es demasiado grande (máx. $1 MB).", ar: "الخط كبير جدًا (الحد الأقصى $1 ميغابايت).", tr: "Yazı tipi çok büyük (en fazla $1 MB).", hi: "फ़ॉन्ट बहुत बड़ा है (अधिकतम $1 MB)।" }],
  [/^„(.+)“ nie je font\. .+$/, { cs: "„$1“ není font. Přijímáme jen soubory TTF, OTF, WOFF a WOFF2.", en: "“$1” isn't a font. Only TTF, OTF, WOFF and WOFF2 files are accepted.", es: "«$1» no es una fuente. Solo se aceptan archivos TTF, OTF, WOFF y WOFF2.", ar: "«$1» ليس خطًا. تُقبل ملفات TTF وOTF وWOFF وWOFF2 فقط.", tr: "“$1” bir yazı tipi değil. Yalnızca TTF, OTF, WOFF ve WOFF2 dosyaları kabul edilir.", hi: "“$1” फ़ॉन्ट नहीं है। केवल TTF, OTF, WOFF और WOFF2 फ़ाइलें स्वीकार की जाती हैं।" }],
  [/^Prehliadač číta zvuk v reálnom čase: (.+) z (.+)$/, { cs: "Prohlížeč čte zvuk v reálném čase: $1 z $2", en: "The browser reads the audio in real time: $1 of $2", es: "El navegador lee el audio en tiempo real: $1 de $2", ar: "يقرأ المتصفح الصوت في الوقت الفعلي: $1 من $2", tr: "Tarayıcı sesi gerçek zamanlı okuyor: $1 / $2", hi: "ब्राउज़र ऑडियो रियल टाइम में पढ़ रहा है: $1 / $2" }],
  [/^(.+): (\d+) z (\d+) MB \(len pri prvom použití\)$/, { cs: "$1: $2 z $3 MB (jen při prvním použití)", en: "$1: $2 of $3 MB (first use only)", es: "$1: $2 de $3 MB (solo la primera vez)", ar: "$1: $2 من $3 ميغابايت (في المرة الأولى فقط)", tr: "$1: $2 / $3 MB (yalnızca ilk kullanımda)", hi: "$1: $2 / $3 MB (केवल पहली बार)" }],
  [/^Model sa nepodarilo načítať\. (.*) Technická chyba: (.+)$/, {
    cs: (r) => "Model se nepodařilo načíst. " + trParts(r[1]) + " Technická chyba: " + r[2], en: (r) => "Couldn't load the model. " + trParts(r[1]) + " Technical error: " + r[2],
    es: (r) => "No se pudo cargar el modelo. " + trParts(r[1]) + " Error técnico: " + r[2], ar: (r) => "تعذّر تحميل النموذج. " + trParts(r[1]) + " خطأ تقني: " + r[2],
    tr: (r) => "Model yüklenemedi. " + trParts(r[1]) + " Teknik hata: " + r[2], hi: (r) => "मॉडल लोड नहीं हो सका। " + trParts(r[1]) + " तकनीकी त्रुटि: " + r[2] }],
];
function trParts(s) { // preloží text po vetách (pre texty poskladané z viacerých viet)
  if (!s) return s || "";
  const lead = s.match(/^\s*/)[0];
  return lead + s.trim().split(/(?<=[.!?])\s+/).map((x) => tr(x)).join(" ");
}
function tr(s) {
  if (LANG === "sk" || !s) return s;
  const d = DICT[s]; if (d) return d[LI];
  const key = Object.keys(LANG_COL)[LI];
  for (const [re, out] of PATTERNS) {
    const r = s.match(re); if (!r) continue;
    const o = out[key];
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
