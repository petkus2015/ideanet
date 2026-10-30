(() => {
  const ICONS = {
    pamiatky: '<path d="M4 21V8h3v3h2V8h2v3h2V8h3v13M10 21v-4h4v4"/>',
    vyhlady: '<path d="M12 3v4M9 7h6l-1 14h-4zM8 11h8"/>',
    priroda: '<path d="M12 21v-6M12 3l6 8h-3l4 6H5l4-6H6z"/>',
    kultura: '<path d="M4 5h16v14H4zM4 15l5-4 4 3 3-2 4 3"/>',
    gastro: '<path d="M5 8h11v6a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5zM16 9h2a2 2 0 0 1 0 5h-2M8 3v2M12 3v2"/>',
    restauracie: '<path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10M17 3c-2 2-3 4-3 7h3v11"/>',
    aktivity: '<path d="M13 3 5 14h6l-1 7 8-11h-6z"/>',
    rodina: '<circle cx="12" cy="12" r="9"/><path d="M8.5 10h.01M15.5 10h.01M8 15c1 1.5 2.5 2 4 2s3-.5 4-2"/>',
    walk: '<circle cx="13" cy="4.5" r="1.6"/><path d="M10 21l2-6-3-3 2-5 3 3h3M7 12l2-4"/>',
    bus: '<path d="M5 4h14v13H5zM5 11h14M8 20v-3M16 20v-3M8 14h.01M16 14h.01"/>',
    taxi: '<path d="M5 16v-4l2-5h10l2 5v4zM5 16v3h3v-3M16 16v3h3v-3M8 12h8M10 4h4"/>',
    train: '<path d="M7 3h10v13H7zM7 10h10M9 21l2-5M15 21l-2-5M10 13h.01M14 13h.01"/>',
    nav: '<path d="M3 11l18-8-8 18-2-8z"/>'
  };

  const CATS = [
    { id: 'pamiatky', name: 'Pamiatky a história', desc: 'Hrady, brány a paláce, ktoré písali dejiny Uhorska.', c: '#c2410c', c2: '#f59e0b' },
    { id: 'vyhlady', name: 'Výhľady', desc: 'Miesta, odkiaľ uvidíte Dunaj, Rakúsko aj Malé Karpaty.', c: '#1f5eff', c2: '#7aa2ff' },
    { id: 'priroda', name: 'Príroda a parky', desc: 'Lesy, záhrady a nábrežie na oddych od mesta.', c: '#15803d', c2: '#65d19a' },
    { id: 'kultura', name: 'Kultúra a umenie', desc: 'Galérie, múzeá a moderné umenie pri Dunaji.', c: '#7c3aed', c2: '#c084fc' },
    { id: 'gastro', name: 'Kaviarne a trhy', desc: 'Káva, koláče, trhy a víno z okolia.', c: '#e11d48', c2: '#ff8a65' },
    { id: 'restauracie', name: 'Reštaurácie a bary', desc: 'Slovenská kuchyňa, pivovary a podniky na večer.', c: '#b45309', c2: '#fbbf24' },
    { id: 'aktivity', name: 'Zaujímavé aktivity', desc: 'Plavby, bicykel, rafting, prehliadky a sezónne akcie.', c: '#0f766e', c2: '#2dd4bf' },
    { id: 'rodina', name: 'Rodina a zábava', desc: 'Zvieratá, voda a aktivity pre deti aj dospelých.', c: '#0891b2', c2: '#5eead4' }
  ];

  const GROUPS = [
    { id: 'vidiet', name: 'Čo vidieť', desc: 'Pamiatky, výhľady, príroda a umenie.', cats: ['pamiatky', 'vyhlady', 'priroda', 'kultura'], c: '#1f5eff' },
    { id: 'robit', name: 'Čo robiť', desc: 'Aktivity, výlety a zábava pre rodiny.', cats: ['aktivity', 'rodina'], c: '#0f766e' },
    { id: 'jest', name: 'Jedlo a pitie', desc: 'Kaviarne, trhy, reštaurácie a bary.', cats: ['gastro', 'restauracie'], c: '#e11d48' }
  ];
  const WALK = ['Michalská brána','Katedrála sv. Martina','Primaciálny palác','Stará radnica','Modrý kostolík','Bratislavský hrad','Vyhliadka Bratislavského hradu','Slovenská národná galéria','Slovenské národné divadlo','Sochy v uliciach','Stará tržnica','Kaffee Mayer','Hviezdoslavovo námestie','Medická záhrada','Obchodná ulica','Dunajské nábrežie','Eurovea','Pešie prehliadky mesta','Prešporáčik','Vianočné trhy','Bratislavský Meštiansky Pivovar','Slovak Pub','Plavba loďou po Dunaji'];
  const FAR = ['Danubiana Meulensteen Art Museum','Divoká voda Čunovo','Rusovce a kaštieľ','Cyklotrasa do Hainburgu','Malokarpatská vínna cesta','Víkendový výlet do Viedne'];
  const TRAIN = ['Malokarpatská vínna cesta','Víkendový výlet do Viedne','Cyklotrasa do Hainburgu'];
  const GO = {
    walk: 'Z centra sa dá dôjsť pešo.',
    mhd: 'Najpohodlnejšie MHD alebo taxi.',
    far: 'Mimo centra: použite MHD, vlak alebo taxi.'
  };
  const ORIGINS = [
    { v: '', l: 'Moja poloha' },
    { v: 'Bratislava hlavná stanica', l: 'Hlavná stanica' },
    { v: 'Hlavné námestie Bratislava', l: 'Hlavné námestie' },
    { v: 'Nový most Bratislava', l: 'Nový most' },
    { v: 'Letisko M. R. Štefánika Bratislava', l: 'Letisko' }
  ];
  /* Orientačné návrhy liniek (DPB). Linky a trasy sa menia, preto vždy odkaz na plánovač. */
  const MHD = {
    'Hrad Devín': 'Autobus 29 od zastávky Nový most až na Devín.',
    'Botanická záhrada UK': 'Od Mosta SNP autobusom 29 alebo električkou 4 na zastávku Botanická záhrada.',
    'Bratislavská ZOO': 'Autobusom 30 alebo 37 na zastávku ZOO (Mlynská dolina).',
    'Železná studnička': 'Autobus 43 na zastávku Železná studnička.',
    'Kamzík': 'Do zastávky Koliba, odtiaľ autobus 144 na Kamzík. Alebo pešo cez les alebo lanovkou zo Železnej studničky.',
    'Turistika z Kamzíka': 'Do zastávky Koliba, odtiaľ autobus 144 na Kamzík.'
  };
  /* Fotky: súbor photos/<slug>.jpg (pomer 3:2, min. 800 px). Ak chýba, karta ukáže farebný gradient. */
  const slug = n => n.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  const photo = n => `photos/${slug(n)}.jpg`;
  const goOf = n => WALK.includes(n) ? 'walk' : FAR.includes(n) ? 'far' : 'mhd';

  const PLACES = [
    { n: 'Bratislavský hrad', cat: 'pamiatky', a: 'Staré Mesto', t: 'Biela dominanta nad Dunajom. Z nádvoria a vyhliadkových terás je výhľad na mesto aj na rakúsku hranicu. V areáli sú expozície Slovenského národného múzea.', tags: ['výhľad', 'história', 'múzeum'] },
    { n: 'Hrad Devín', cat: 'pamiatky', a: 'Devín', t: 'Ruiny hradu na skale presne tam, kde sa Morava vlieva do Dunaja. Miesto s tisícročnou históriou a jeden z najkrajších výhľadov v okolí.', tags: ['ruiny', 'rieka', 'výlet'] },
    { n: 'Michalská brána', cat: 'pamiatky', a: 'Staré Mesto', t: 'Jediná zachovaná brána stredovekého opevnenia. Z veže je pohľad na uličky centra, vnútri je expozícia zbraní.', tags: ['brána', 'veža', 'centrum'] },
    { n: 'Katedrála sv. Martina', cat: 'pamiatky', a: 'Staré Mesto', t: 'Gotická katedrála pri Dunaji. V rokoch 1563 až 1830 sa v nej korunovali uhorskí králi a kráľovné.', tags: ['gotika', 'korunovácie'] },
    { n: 'Primaciálny palác', cat: 'pamiatky', a: 'Staré Mesto', t: 'Klasicistický palác na Primaciálnom námestí so slávnou Zrkadlovou sieňou, v ktorej sa v roku 1805 podpísal Prešporský mier.', tags: ['palác', 'klasicizmus'] },
    { n: 'Stará radnica', cat: 'pamiatky', a: 'Hlavné námestie', t: 'Zmes gotiky a renesancie priamo na Hlavnom námestí. Dnes sídli v nej Múzeum mesta Bratislavy a z veže je dobrý výhľad.', tags: ['námestie', 'múzeum'] },
    { n: 'Modrý kostolík', cat: 'pamiatky', a: 'Staré Mesto', t: 'Secesný kostol sv. Alžbety celý v modrobielom odtieni. Vyzerá ako z cukrárskej výkladnej skrine.', tags: ['secesia', 'fotogenické'] },
    { n: 'Slavín', cat: 'vyhlady', a: 'Staré Mesto', t: 'Pamätník a vojenský cintorín na vŕšku nad centrom. Nezabudnuteľný výhľad na mesto, hrad aj Dunaj.', tags: ['pamätník', 'výhľad', 'západ slnka'] },
    { n: 'Most SNP s UFO', cat: 'vyhlady', a: 'Petržalka', t: 'Most so vzhľadom lietajúceho taniera. Na vyhliadke vo výške približne 95 m uvidíte celé mesto a v reštaurácii si môžete sadnúť na večeru.', tags: ['symbol mesta', 'reštaurácia', 'večer'] },
    { n: 'Kamzík', cat: 'vyhlady', a: 'Nové Mesto', t: 'Televízna veža v Malých Karpatoch s vyhliadkou a reštauráciou. Pod ňou je lesopark, kam sa chodí na výlety.', tags: ['veža', 'les', 'výlet'] },
    { n: 'Vyhliadka Bratislavského hradu', cat: 'vyhlady', a: 'Staré Mesto', t: 'Terasy pod hradom patria k najlepším miestam na pohľad na Dunaj a Nový most. Ideálne pre fotografiu na pamiatku.', tags: ['fotografia', 'Dunaj'] },
    { n: 'Sad Janka Kráľa', cat: 'priroda', a: 'Petržalka', t: 'Jeden z najstarších verejných parkov v strednej Európe z roku 1774. Rozľahlý park s ihriskami a tienistými cestičkami pri Dunaji.', tags: ['park', 'prechádzka'] },
    { n: 'Železná studnička', cat: 'priroda', a: 'Nové Mesto', t: 'Údolie s jazierkami v lesoch Malých Karpát. Východisko pre výlety na Kamzík a obľúbené miesto na behanie či grilovanie.', tags: ['les', 'turistika'] },
    { n: 'Botanická záhrada UK', cat: 'priroda', a: 'Karlova Ves', t: 'Záhrada s rastlinami z celého sveta a sklenníkmi, na brehu Dunaja. Pokojné miesto na oddych.', tags: ['záhrada', 'Dunaj'] },
    { n: 'Dunajské nábrežie', cat: 'priroda', a: 'Staré Mesto', t: 'Promenáda pri Dunaji, kde sa chodí na bicykli, na behanie alebo len tak sedieť pri vode. Na západ slnka výborná.', tags: ['promenáda', 'bicykel'] },
    { n: 'Danubiana Meulensteen Art Museum', cat: 'kultura', a: 'Čunovo', t: 'Moderné umenie na polostrove v Dunaji. Budova je sama o sebe zážitok a výhľad z kaviarne na vodu stojí za cestu.', tags: ['galéria', 'moderné umenie', 'Dunaj'] },
    { n: 'Slovenská národná galéria', cat: 'kultura', a: 'Staré Mesto', t: 'Hlavná galéria krajiny na Nábreží Ľ. Štúra, s expozíciami slovenského výtvarného umenia od stredoveku po súčasnosť.', tags: ['galéria', 'umenie'] },
    { n: 'Slovenské národné divadlo', cat: 'kultura', a: 'Staré Mesto', t: 'Neorenesančná budova z roku 1886 s opernými, baletnými a činoherným predstaveniami. Večer pri fontáne Ganymeda.', tags: ['opera', 'balet', 'večer'] },
    { n: 'Sochy v uliciach', cat: 'kultura', a: 'Staré Mesto', t: 'Čumil z kanála, Napoleonov vojak na lavičke či Schöne Náci. Mesto je plné hravých sôch, ktoré si treba vyfotiť.', tags: ['zábava', 'fotografia', 'bezplatné'] },
    { n: 'Stará tržnica', cat: 'gastro', a: 'Staré Mesto', t: 'Historická tržnica s pouličným jedlom, víkendovými trhmi a priestorom na podujatia. Dobré miesto na gurmánsku zastávku.', tags: ['trh', 'street food'] },
    { n: 'Kaffee Mayer', cat: 'gastro', a: 'Hlavné námestie', t: 'Viedenská kaviareň od roku 1873 priamo na Hlavnom námestí, známa torty a kávou. Klasika pre každého návštevníka.', tags: ['káva', 'koláče', 'tradícia'] },
    { n: 'Eurovea', cat: 'gastro', a: 'Staré Mesto', t: 'Obchodné centrum s promenádou pri Dunaji. Obchody, reštaurácie a kaviarne s výhľadom na vodu.', tags: ['promenáda', 'nákupy', 'reštaurácie'] },
    { n: 'Malokarpatská vínna cesta', cat: 'gastro', a: 'Pezinok a okolie', t: 'Vinárstva len pár kilometrov za mestom. Ochutnávky, pivnice a víkendové výlety medzi vinicami.', tags: ['víno', 'výlet', 'ochutnávky'] },
    { n: 'Slovak Pub', cat: 'restauracie', a: 'Obchodná ulica', t: 'Klasická slovenská kuchyňa v centre mesta: bryndzové halušky, kapustnica a výber domácich piv. Dobrá voľba na prvé zoznámenie s miestnymi jedlami.', tags: ['slovenská kuchyňa', 'pivo', 'tradícia'] },
    { n: 'Bratislavský Meštiansky Pivovar', cat: 'restauracie', a: 'Staré Mesto', t: 'Pivovar s vlastnou reštauráciou v centre. Pivo sa varí priamo v budove a hodí sa k nemu poriadne jedlo.', tags: ['pivovar', 'pivo', 'večera'] },
    { n: 'Hviezdoslavovo námestie', cat: 'restauracie', a: 'Staré Mesto', t: 'Alejou stromov lemované námestie pri divadle s množstvom reštaurácií, kaviarní a terás. V lete sa tu sedí do neskorého večera.', tags: ['terasy', 'večer', 'centrum'] },
    { n: 'Medická záhrada', cat: 'restauracie', a: 'Staré Mesto', t: 'Zelený vnútroblok pri centre s letnými podujatiami, kaviarňou a priestorom na posedenie pod stromami.', tags: ['záhrada', 'leto', 'drink'] },
    { n: 'Obchodná ulica', cat: 'restauracie', a: 'Staré Mesto', t: 'Pešia zóna s obchodmi, pubmi a lokálnymi podnikmi. Dobré miesto na obed alebo pivo po prehliadke centra.', tags: ['pešia zóna', 'puby'] },
    { n: 'Reštaurácia UFO', cat: 'restauracie', a: 'Petržalka', t: 'Večera nad mestom v reštaurácii s vyhliadkou na vrchole Mosta SNP. Rezervujte si stôl vopred, najmä na západ slnka.', tags: ['výhľad', 'večera', 'rezervácia'] },
    { n: 'Plavba loďou po Dunaji', cat: 'aktivity', a: 'Nábrežie', t: 'Z Bratislavy chodia lode do Viedne aj okružné plavby k Devínu. Mesto vyzerá z vody inak a cesta je pohodlná.', tags: ['loď', 'Viedeň', 'Dunaj'] },
    { n: 'Prešporáčik', cat: 'aktivity', a: 'Staré Mesto', t: 'Turistický vláčik, ktorý prevezie návštevníkov po historickom centre. Vhodný pre rodiny a pre tých, čo sa nechcú veľa chodiť.', tags: ['vláčik', 'centrum', 'rodiny'] },
    { n: 'Pešie prehliadky mesta', cat: 'aktivity', a: 'Staré Mesto', t: 'Sprievodcovia ukážu centrum, príbehy sôch a skryté dvory. Prehliadky sú v slovenčine aj v cudzích jazykoch.', tags: ['sprievodca', 'história'] },
    { n: 'Turistika z Kamzíka', cat: 'aktivity', a: 'Malé Karpaty', t: 'Značené trasy lesom z Kamzíka k Železnej studničke a ďalej do Malých Karpát. Ideálne na polovicu dňa mimo ruchu mesta.', tags: ['turistika', 'príroda'] },
    { n: 'Vianočné trhy', cat: 'aktivity', a: 'Hlavné námestie', t: 'Každý december pôsobia v centre trhy s punčom, medovníkmi a remeselnými stánkami. Najlepšia nálada je po zotmení.', tags: ['december', 'trhy', 'sezónne'] },
    { n: 'Víkendový výlet do Viedne', cat: 'aktivity', a: 'Okolie', t: 'Viedeň je len asi hodinu vlakom, loďou alebo autom. Pri dlhšom pobyte stojí za zvážiť jednodňový výlet.', tags: ['výlet', 'Rakúsko'] },
    { n: 'Bratislavská ZOO', cat: 'rodina', a: 'Mlynská dolina', t: 'Zoologická záhrada v zalesnenom údolí. Pre rodiny je to najlepší program na pol dňa.', tags: ['zvieratá', 'deti'] },
    { n: 'Divoká voda Čunovo', cat: 'aktivity', a: 'Čunovo', t: 'Areál vodných športov s olympijským kanálom. Rafting, kajaky a celé leto aj pre návštevníkov bez skúseností.', tags: ['rafting', 'adrenalín', 'leto'] },
    { n: 'Rusovce a kaštieľ', cat: 'rodina', a: 'Rusovce', t: 'Kaštieľ s anglickým parkom na južnom okraji mesta, blízko hraníc s Maďarskom a Rakúskom. Pokojný výlet na bicykli.', tags: ['park', 'kaštieľ', 'bicykel'] },
    { n: 'Cyklotrasa do Hainburgu', cat: 'aktivity', a: 'Petržalka', t: 'Dunajská cyklotrasa vedie cez Čunovo až do Rakúska. Rovinatá trasa, ideálna na celodenný výlet.', tags: ['bicykel', 'Rakúsko'] }
  ];

  const $ = s => document.querySelector(s);
  const catOf = id => CATS.find(c => c.id === id);
  const groupOf = catId => GROUPS.find(g => g.cats.includes(catId));
  const icon = id => `<svg class="i" viewBox="0 0 24 24" aria-hidden="true">${ICONS[id]}</svg>`;
  const esc = s => s.replace(/[&<>"]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));
  const q = p => encodeURIComponent(p.n + ' Bratislava');
  const dir = (p, mode, from) => 'https://www.google.com/maps/dir/?api=1&destination=' + q(p) + (from ? '&origin=' + encodeURIComponent(from) : '') + (mode ? '&travelmode=' + mode : '');

  let favs = new Set();
  try { favs = new Set(JSON.parse(localStorage.getItem('ba-favs') || '[]')); } catch (e) {}
  const saveFavs = () => { try { localStorage.setItem('ba-favs', JSON.stringify([...favs])); } catch (e) {} };

  const state = { group: 'all', cat: 'all', q: '', onlyFavs: false };

  function renderGroups() {
    $('#groupGrid').innerHTML = GROUPS.map(g => {
      const cats = g.cats.map(catOf);
      const n = PLACES.filter(p => g.cats.includes(p.cat)).length;
      return `<section class="group" style="--c:${g.c}" id="g-${g.id}">
        <header><h3>${g.name}</h3><p>${g.desc}</p></header>
        <div class="sub">${cats.map(c => `<button class="cat" data-group="${g.id}" data-cat="${c.id}" style="--c:${c.c}">
          <span class="ico">${icon(c.id)}</span><span><b>${c.name}</b><small>${PLACES.filter(p => p.cat === c.id).length} miest</small></span></button>`).join('')}</div>
        <button class="more" data-group="${g.id}" data-cat="all">Všetko z kategórie (${n}) →</button></section>`;
    }).join('');
  }

  function renderTabs() {
    const g = [{ id: 'all', name: 'Všetko' }, ...GROUPS];
    $('#tabs').innerHTML = g.map(x => `<button class="tab" role="tab" data-group="${x.id}" data-cat="all" aria-selected="${state.group === x.id}">${x.name}</button>`).join('');
    const cats = state.group === 'all' ? CATS : CATS.filter(c => GROUPS.find(g => g.id === state.group).cats.includes(c.id));
    $('#chips').innerHTML = cats.length > 1 ? [{ id: 'all', name: 'Všetky podkategórie' }, ...cats].map(c =>
      `<button class="chip" data-group="${state.group}" data-cat="${c.id}" aria-pressed="${state.cat === c.id}">${c.name}</button>`).join('') : '';
  }

  function renderCards() {
    const text = state.q.trim().toLowerCase();
    const list = PLACES.filter(p =>
      (state.group === 'all' || groupOf(p.cat).id === state.group) &&
      (state.cat === 'all' || p.cat === state.cat) &&
      (!state.onlyFavs || favs.has(p.n)) &&
      (!text || (p.n + ' ' + p.a + ' ' + p.t + ' ' + p.tags.join(' ')).toLowerCase().includes(text)));
    $('#cards').innerHTML = list.map(p => {
      const c = catOf(p.cat);
      return `<article class="card-wrap">
        <button class="card" data-n="${esc(p.n)}" style="--c:${c.c};--c2:${c.c2}">
          <div class="card-art">${icon(p.cat)}<img src="${photo(p.n)}" alt="" loading="lazy" onerror="this.remove()"><span class="badge">${c.name}</span></div>
          <div class="card-body"><h3>${esc(p.n)}</h3><div class="area">${esc(p.a)}</div><p>${esc(p.t)}</p></div></button>
        <button class="heart" data-fav="${esc(p.n)}" aria-label="Uložiť ${esc(p.n)}" aria-pressed="${favs.has(p.n)}"><svg viewBox="0 0 24 24"><path d="M12 21s-7-4.6-9.3-9A5.2 5.2 0 0 1 12 6a5.2 5.2 0 0 1 9.3 6c-2.3 4.4-9.3 9-9.3 9z"/></svg></button>
        <a class="navbtn" href="${dir(p)}" target="_blank" rel="noopener" aria-label="Navigovať: ${esc(p.n)}">${icon('nav')}Navigovať</a></article>`;
    }).join('');
    $('#empty').hidden = list.length > 0;
    const g = state.group === 'all' ? 'všetkých kategórií' : state.cat !== 'all' ? catOf(state.cat).name : GROUPS.find(x => x.id === state.group).name;
    const n = list.length;
    $('#resultInfo').textContent = `${n} ${n === 1 ? 'miesto' : n > 1 && n < 5 ? 'miesta' : 'miest'} z ${g}${state.onlyFavs ? ' (uložené)' : ''}`;
  }

  function updateFavUi() {
    $('#favCount').textContent = favs.size;
    $('#favToggle').setAttribute('aria-pressed', state.onlyFavs);
  }
  function toggleFav(name) { favs.has(name) ? favs.delete(name) : favs.add(name); saveFavs(); updateFavUi(); renderCards(); }

  function openModal(name) {
    const p = PLACES.find(x => x.n === name), c = catOf(p.cat), m = $('#modal');
    const go = goOf(p.n), train = TRAIN.includes(p.n);
    m.style.setProperty('--c', c.c); m.style.setProperty('--c2', c.c2);
    $('#mArt').innerHTML = icon(p.cat) + `<img src="${photo(p.n)}" alt="${esc(p.n)}" onerror="this.remove()">`;
    $('#mMeta').textContent = `${groupOf(p.cat).name} · ${c.name} · ${p.a}`;
    $('#mTitle').textContent = p.n;
    $('#mText').textContent = p.t;
    $('#mTags').innerHTML = p.tags.map(t => `<li>${esc(t)}</li>`).join('');
    $('#mHint').textContent = GO[go];
    const tile = (id, label, href, rec) => `<a class="mode${rec ? ' rec' : ''}" href="${href}" target="_blank" rel="noopener">${icon(id)}<span>${label}</span>${rec ? '<em>odporúčame</em>' : ''}</a>`;
    $('#mModes').innerHTML =
      tile('walk', 'Pešo', dir(p, 'walking'), go === 'walk') +
      tile('bus', 'MHD', dir(p, 'transit'), go === 'mhd') +
      tile('taxi', 'Taxi', 'https://m.uber.com/ul/?action=setPickup&pickup=my_location&dropoff[nickname]=' + q(p), go === 'far' && !train) +
      (train ? tile('train', 'Vlak', 'https://www.zssk.sk/', go === 'far') : '');
    const line = MHD[p.n];
    $('#mLine').hidden = go === 'walk' && !line;
    $('#mLineText').textContent = line || 'Konkrétnu linku a prestup nájdete v plánovači podľa toho, odkiaľ idete.';
    $('#mFrom').innerHTML = ORIGINS.map(o => `<option value="${esc(o.v)}">${o.l}</option>`).join('');
    const setFrom = () => { const u = dir(p, 'transit', $('#mFrom').value); $('#mPlan').href = u; const t = $('#mModes a:nth-child(2)'); if (t) t.href = u; };
    $('#mFrom').onchange = setFrom; setFrom();
    $('#mTaxi').innerHTML = 'Taxi si objednáte v aplikácii <a href="https://bolt.eu/sk-sk/" target="_blank" rel="noopener">Bolt</a> alebo Uber. Cenu uvidíte pred jazdou.' + (train ? ' Vlaky odchádzajú z hlavnej stanice (Bratislava hl. st.) a stanice Petržalka.' : '');
    $('#mNav').href = dir(p);
    $('#mMap').href = 'https://www.google.com/maps/search/?api=1&query=' + q(p);
    const f = $('#mFav');
    const sync = () => { f.textContent = favs.has(p.n) ? 'Uložené ✓' : 'Uložiť'; };
    f.onclick = () => { toggleFav(p.n); sync(); };
    sync();
    m.showModal();
  }

  function setFilter(group, cat) { state.group = group; state.cat = cat; renderTabs(); renderCards(); }

  document.addEventListener('click', e => {
    const fav = e.target.closest('[data-fav]');
    if (fav) return toggleFav(fav.dataset.fav);
    if (e.target.closest('.navbtn')) return;
    const card = e.target.closest('.card');
    if (card) return openModal(card.dataset.n);
    const f = e.target.closest('[data-group]');
    if (f) {
      setFilter(f.dataset.group, f.dataset.cat);
      if (f.closest('.group')) $('#miesta').scrollIntoView({ behavior: 'smooth' });
    }
    if (e.target === $('#modal')) $('#modal').close();
  });
  $('#q').addEventListener('input', e => { state.q = e.target.value; renderCards(); });
  $('#favToggle').addEventListener('click', () => {
    state.onlyFavs = !state.onlyFavs; updateFavUi(); renderCards();
    if (state.onlyFavs) $('#miesta').scrollIntoView({ behavior: 'smooth' });
  });

  renderGroups(); renderTabs(); renderCards(); updateFavUi();
})();
