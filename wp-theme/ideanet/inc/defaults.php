<?php
/**
 * Predvolené hodnoty pre všetky texty, čísla a odkazy.
 *
 * Toto je jediné miesto, kde sú texty napísané natvrdo. Všetko sa dá
 * prepísať v Prispôsobiť bez zásahu do kódu — predvolené hodnoty slúžia
 * len na to, aby web po aktivácii témy nebol prázdny.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vráti pole všetkých predvolených hodnôt.
 *
 * @return array<string,string>
 */
function ideanet_defaults() {
	static $d = null;
	if ( null !== $d ) {
		return $d;
	}

	$d = array(

		/* ── značka ────────────────────────────── */
		'brand_name'      => 'Peter Miškus',
		'brand_co'        => 'IDEA',
		'brand_co_bold'   => 'NET',

		/* ── hero ──────────────────────────────── */
		'hero_eyebrow'    => 'Som videotvorca, grafik a content creator · BA · ZA · Brno',
		'hero_title'      => 'Prídem, natočím,',
		'hero_title_2'    => 'zostrihám a',
		'hero_title_hl'   => 'máte obsah',
		'hero_lead'       => 'Mesačná tvorba vertikálneho videa pre firmy, ktoré nechcú riešiť produkciu a nechcú obsah vygenerovaný cez AI. Raz mesačne prídem na dve až tri hodiny a vy máte hotové Reels na celý mesiac — na Instagram, TikTok aj YouTube Shorts.',
		'hero_flow'       => "Nápady a témy\nNatáčací blok\nStrih a titulky\nHotový obsah",
		'hero_cta1'       => 'Spočítať si cenu',
		'hero_cta1_url'   => '#cennik',
		'hero_cta2'       => 'Pozrieť tvorbu',
		'hero_cta2_url'   => '#portfolio',
		'hero_chips'      => "9:16 / 16:9\nTitulky\nSame-day alebo do 24 h\nHudba\nEfekty FX, SFX & AI FX\nThumbnail",
		'hero_caption'    => 'natáčam · strihám · dodávam',
		'hero_video'      => '',
		'hero_webm'       => '',
		'hero_poster'     => '',
		'hero_marquee'    => 'Gastro, Fitness, Reality, Beauty, Kliniky, Hotely, Developeri, Eventy, E-shopy',

		/* ── hlavičky sekcií ───────────────────── */
		'portfolio_eyebrow' => 'Portfólio',
		'portfolio_title'   => 'Tvorba na výšku',
		'portfolio_note'    => 'Potiahnite prstom alebo myšou, skrolujte kolieskom, alebo použite šípky. Klik na kartu spustí ukážku, ikona vpravo dole otvorí video na celú výšku.',

		'vizualy_eyebrow' => 'Nielen video',
		'vizualy_title'   => 'Vizuály a identita',
		'vizualy_note'    => 'Logo, farby, písma a šablóny pre siete. Aby profil vyzeral ako jedna značka, nie ako zbierka príspevkov od rôznych ľudí.',

		'sluzby_eyebrow' => 'Služby',
		'sluzby_title'   => 'Tri spôsoby spolupráce',
		'sluzby_note'    => 'Vyberte si, čo sedí na vašu situáciu. Každá cesta má vlastný cenník — kliknutím sa dostanete rovno k nemu.',

		'vcene_title' => 'Čo je vždy v cene',
		'vcene_note'  => 'Rovnaký štandard pri každom balíku — nič z tohto nie je príplatok.',
		'vcene_items' => "Profesionálne nastavená mobilná video technika\nVertikál 9:16\nVýber najlepších záberov\nStrih\nZákladný color grading\nHudba\nTitulky a jednoduché texty\nExport 1080 × 1920 / 4K\n1 kolo revízií\nDodanie cez cloud do 24 hodín alebo podľa dohody",

		'prekoho_eyebrow' => 'Pre koho',
		'prekoho_title'   => 'Video, ktoré prináša zákazníkov a udržuje komunitu',
		'prekoho_note'    => 'Najlepšie to funguje tam, kde má značka čo ukazovať a video priamo pomáha predávať. Ak sa v niektorom bode nájdete, viem vám poslať konkrétne nápady.',
		'prekoho_items'   => "Gastro, kaviarne a nové prevádzky\nFitness centrá a tréneri\nRealitní makléri\nBarber, kaderníctva a beauty\nZubné a estetické kliniky\nHotely a apartmány\nDeveloperi\nEventové agentúry\nLokálne e-shopy a značky",

		'proces_eyebrow' => 'Proces',
		'proces_title'   => 'Štyri kroky, žiadny chaos',
		'proces_note'    => 'Vopred viete, čo sa deje, kedy to bude a čo dostanete.',

		'referencie_eyebrow' => 'Referencie',
		'referencie_title'   => 'Čo hovoria klienti',
		'referencie_note'    => '',

		'cennik_eyebrow' => 'Cenník',
		'cennik_title'   => 'Férové ceny',
		'cennik_note'    => 'Poskladajte si zákazku a hneď vidíte, čo stojí jedno hotové video vrátane natáčania, strihu aj dodania. Nie som platca DPH, k cene sa nič nepripočítava.',

		'podmienky_eyebrow' => 'Podmienky',
		'podmienky_title'   => 'Férové podmienky',
		'podmienky_note'    => 'Osem viet, ktoré si odsúhlasíme dopredu.',

		'kontakt_eyebrow' => 'Kontakt',
		'kontakt_title'   => 'Napíšte mi, čo potrebujete',
		'kontakt_note'    => 'Ak mi pošlete odkaz na váš Instagram, vrátim sa rovno s dvomi či tromi konkrétnymi nápadmi na Reels a s cenou. Ozvať sa mi môžete aj na Instagrame — <a href="https://instagram.com/petermiskus">@petermiskus</a>.',

		/* ── cenník: záložky a panely ──────────── */
		'tab_firmy'          => 'Pre firmy',
		'tab_firmy_sub'      => 'priamy klient',
		'tab_agentury'       => 'Pre agentúry',
		'tab_agentury_sub'   => 'white-label',
		'tab_eventy'         => 'Eventy',
		'tab_eventy_sub'     => 'same-day',

		'firmy_intro'     => 'Raz mesačne prídem na dve až tri hodiny a vy máte obsah na celý mesiac. Témy navrhnem ja.',
		'agentury_intro'  => 'Preberám produkčnú časť Reels pre vašich klientov. S klientom komunikujete vy, faktúru dostávate odo mňa.',
		'eventy_intro'    => 'Pri evente si kupujete môj čas v konkrétny deň — a pri same-day aj rýchlosť.',

		'firmy_jednorazovo_title' => 'Jednorazová produkcia',
		'firmy_jednorazovo_note'  => 'Keď chcete najprv vyskúšať, ako to celé funguje. Každý ďalší Reel +95 € a +1 hodina natáčania — poskladáte si to v <a href="#kalkulacka">kalkulačke</a>.',
		'firmy_podcast_title'     => 'Podcast u vás',
		'firmy_podcast_note'      => 'Prídem s technikou k vám, nemusíte nikam cestovať ani si prenajímať štúdio.',
		'firmy_podcast_foot'      => 'Každá ďalšia epizóda natočená v ten istý deň je lacnejšia — technika aj svetlo už stoja. Pri pravidelnom podcaste nacením sériu individuálne.',
		'firmy_vizualy_title'     => 'Tvorba vizuálov a identity',
		'firmy_vizualy_note'      => 'Aby profil vyzeral ako jedna značka, nie ako zbierka príspevkov od rôznych ľudí.',
		'firmy_vizualy_foot'      => 'Ak identitu už máte, stačia vizuály — pracujem s vašimi farbami, písmami a logom. Ak nemáte nič, oplatí sa začať identitou: všetko ostatné, video aj grafika, z nej potom vychádza.',

		'addon_tag'   => 'Doplnok',
		'addon_title' => 'Správa sociálnych sietí',
		'addon_text'  => 'Máte hotové videá — ale kto ich bude publikovať, písať texty a odpovedať v komentároch? Videoprodukciu a správu profilu držím oddelene, aby bolo jasné, čo je v ktorej cene.',
		'addon_price' => '+ 300 – 500 €',
		'addon_unit'  => '/ mesiac k balíku',

		'partner_tag'   => 'Pravidelná spolupráca',
		'partner_title' => 'Partner',
		'partner_text'  => 'Pre agentúry, ktoré potrebujú video každý mesiac. Namiesto objednávania zákazky po zákazke máte rezervovanú produkčnú kapacitu — prakticky externé video oddelenie bez zamestnanca.',
		'partner_items' => "Rezervovaná produkčná kapacita\nAž 4 klientske natáčania mesačne\nPribližne 15 – 20 Reels podľa rozsahu\nPrioritné termíny\nWhite-label — klient nevie, že som externista\nJedna mesačná faktúra",
		'partner_pre'   => 'od',
		'partner_price' => '1 200 €',
		'partner_unit'  => '/ mesiac',
		'partner_cta'   => 'Ozvem sa vám',
		'partner_note'  => 'Pri 4+ natáčaniach mesačne viem nastaviť individuálnu paušálnu cenu.',

		'agentury_notes_title' => 'Dôležité pri white-label spolupráci',
		'agentury_notes'       => "Klient je váš | Vašich klientov neoslovujem priamo — zákaz obchádzania agentúry beriem ako samozrejmosť a pokojne ho dám do zmluvy.\nNDA za 30 € | Ak nechcete, aby bolo verejne známe, kto videá vyrába, videá do portfólia nedávam. Jednorazový poplatok 30 € za celú spoluprácu.\nJedna faktúra mesačne | Pri rozbehnutej spolupráci nefakturujem každú zákazku zvlášť. Päť produkcií za august = jedna faktúra na konci mesiaca.",

		'eventy_hour_title' => 'Každá ďalšia hodina na mieste',
		'eventy_hour_note'  => 'účtuje sa po začatých polhodinách',
		'eventy_hour_price' => '70 € / h',
		'eventy_graf_title' => 'Grafika na event',
		'eventy_graf_note'  => 'Aby event vyzeral jednotne ešte predtým, než sa začne natáčať.',
		'eventy_foot'       => 'Video, ktoré vyjde ešte v ten večer, zachytí moment, keď majú ľudia akciu v hlave. O týždeň je z neho archív — preto je same-day drahší.',

		/* ── kalkulačka ────────────────────────── */
		'calc_first'      => '160',
		'calc_next'       => '95',
		'calc_half'       => '280',
		'calc_half_reels' => '2',
		'calc_half_hours' => '4',
		'calc_day'        => '420',
		'calc_day_reels'  => '3',
		'calc_day_hours'  => '8',
		'calc_first_hours' => '2',
		'calc_km_rate'    => '0,40',
		'calc_rush'       => '50',
		'calc_min'        => '1',
		'calc_max'        => '8',
		'calc_start'      => '3',
		'calc_free_cities' => 'Bratislava, Žilina a Brno',
		'calc_reels_label' => 'Koľko Reels potrebujete',
		'calc_modes_label' => 'Rezervácia času',
		'calc_mode_none'   => 'Bez rezervácie — platí sa podľa počtu Reels',
		'calc_mode_half'   => 'Poldeň — som u vás do 4 hodín',
		'calc_mode_day'    => 'Celý deň — som u vás do 8 hodín',
		'calc_modes_hint'  => 'Pre eventy, otvorenia a natáčacie dni, keď treba byť k dispozícii celý čas. V cene sú 2 Reels pri poldni a 3 pri celom dni, každý ďalší + 95 €.',
		'calc_addons_label' => 'Doplnky',
		'calc_addons'      => "Same-day dodanie — ešte v deň natáčania | 30 | percento\nRAW materiál z natáčania | 20 | jednorazovo",
		'calc_km_label'    => 'Vzdialenosť mimo Bratislavy, Žiliny a Brna <i>(km jedným smerom)</i>',
		'calc_km_hint'     => '{mesta} sú v cene. Mimo nich {sadzba} € / km tam aj späť, alebo podľa dohody.',
		'calc_out_label'   => 'Orientačná cena',
		'calc_unit'        => 'konečná cena',
		'calc_cta'         => 'Poslať tento dopyt',
		'calc_disc'        => 'Presnú cenu potvrdím po krátkom hovore — závisí od miesta, rozsahu a termínu.',
		'calc_tip_from'    => '4',
		'calc_tip'         => 'Pri takomto objeme sa oplatí pozrieť <a href="#cennik">mesačný balík</a> — okrem videí je v ňom aj návrh tém a rezervovaný termín každý mesiac.',

		/* ── kontakt a formulár ────────────────── */
		'contact_email'  => 'ahoj@ideanet.sk',
		'contact_phone'  => '+421 900 000 000',
		'contact_social' => '@petermiskus',
		'contact_social_url' => 'https://instagram.com/petermiskus',
		'contact_facts'  => "BA · ZA · Brno | doprava v cene\ndo 24 h | alebo same-day\nNie som platca DPH |",

		'form_to'        => '',
		'form_subject'   => 'Dopyt z webu',
		'form_name'      => 'Meno / značka',
		'form_name_ph'   => 'Jana Nováková',
		'form_email'     => 'E-mail',
		'form_email_ph'  => 'jana@znacka.sk',
		'form_type'      => 'O čo máte záujem',
		'form_msg'       => 'Detaily',
		'form_msg_ph'    => 'Odbor, mesto, termín, odkaz na Instagram…',
		'form_submit'    => 'Odoslať dopyt',
		'form_ok'        => 'Ďakujem, dopyt mi prišiel. Ozvem sa do 24 hodín.',
		'form_err'       => 'Dopyt sa nepodarilo odoslať. Napíšte mi prosím priamo na {email}.',
		'form_gdpr'      => '',

		/* ── siete ─────────────────────────────── */
		'social_instagram' => 'https://instagram.com/petermiskus',
		'social_tiktok'    => 'https://tiktok.com/@ideanet.studio',
		'social_youtube'   => 'https://youtube.com/@ideanet.studio',
		'social_facebook'  => 'https://facebook.com/ideanet.studio',

		/* ── podstránka: redizajn webov ────────── */
		/* Čísla a sumy sú na jednom mieste a do textov sa dopĺňajú cez
		   {cena}, {cena_pred}, {termin}, {miest}, {volnych} a {dni}.
		   Nedá sa tak stať, že v úvode bude iná cena ako v cenníku. */
		'red_price'      => '600 €',
		'red_price_pred' => '890 €',
		'red_price_unit' => 'bez DPH · celý balík',
		'red_term'       => '26. – 30. 11.',
		'red_slots'      => '10',
		'red_slots_left' => '10',
		'red_days'       => '7',

		'red_flag'     => 'Black Friday',
		'red_eyebrow'  => 'Redizajn prezentačných webov',
		'red_title'    => 'Web, ktorý reprezentuje vašu firmu.',
		'red_title_hl' => 'Za {dni} dní.',
		'red_lead'     => 'Moderná, rýchla a prehľadná vizitka vašej firmy za fixnú cenu {cena} namiesto {cena_pred}. Black Friday ponuka platí {termin} a je obmedzená na {miest} zákazníkov.',
		'red_cta'      => 'Požiadať o miesto',
		'red_cta2'     => 'Čo je v cene',
		'red_facts'    => "{cena} | fixná cena, bez prekvapení\n{dni} pracovných dní | od dodania podkladov\n{miest} miest | platí {termin}",

		'red_poznate_eyebrow' => 'Prečo to riešiť',
		'red_poznate_title'   => 'Poznáte to?',
		'red_poznate_text'    => 'Váš web už nezodpovedá tomu, čo robíte. Na mobile je neprehľadný. Načítava sa pomaly. Dizajn pamätá lepšie časy. A zákazník si o vašej firme vytvorí názor skôr, než vám zavolá.',

		'red_gets_eyebrow' => 'V cene',
		'red_gets_title'   => 'Čo dostanete',
		'red_gets_note'    => 'Jeden balík, jedna cena. Nič z toho nie je príplatok.',
		'red_gets'         => "Nový dizajn vášho WordPress webu\nMobilné zobrazenie a rýchle načítanie\nKontaktný formulár, mapa, odkaz na recenzie a GDPR lišta\nŠkolenie v administrácii, aby ste si web vedeli upravovať sami\nOdovzdanie do {dni} pracovných dní od dodania podkladov",

		'red_ako_eyebrow' => 'Postup',
		'red_ako_title'   => 'Ako to prebieha',
		'red_ako_note'    => 'Vopred viete, čo sa deje, kedy to bude a čo dostanete.',
		'red_ako'         => "Pošlete adresu webu | Do 24 hodín sa vám ozvem s krátkou analýzou: čo na webe zlepšiť a čo z toho stihneme v balíku.\nDodáte podklady | Logo, texty, fotky a prístupy k webu. Čo nemáte, povieme si, ako to dorobiť.\nNávrh a schválenie | Hlavnú stránku vidíte do 2 dní. Pripomienky pošlete naraz v jednom kole.\nSpustenie | Web ide naživo do {dni} pracovných dní od dodania podkladov, vrátane presmerovaní.",

		'red_ukazky_eyebrow' => 'Redizajn webu',
		'red_ukazky_title'   => 'Ukážky klientov',
		'red_ukazky_note'    => 'Weby, ktoré som robil. Prehľadné, rýchle a čitateľné na mobile.',
		'red_ukazky_foot'    => '',
		'red_ukazky_cta'     => 'Pozrieť web',

		'red_cena_eyebrow' => 'Cena',
		'red_cena_title'   => 'Fixná cena, nie odhad',
		'red_deal_when'    => 'Black Friday: {termin} · {miest} miest',
		'red_cena_note'    => 'Cena zahŕňa 1 kolo pripomienok. Texty, logo, e-shop a viac jazykov sú mimo balíka.',

		'red_prekoho_eyebrow' => 'Pre koho',
		'red_prekoho_title'   => 'Pre koho ponuka je',
		'red_prekoho_note'    => 'Ponuka je nacenená na jeden typ webu, preto môže mať fixnú cenu.',
		'red_prekoho'         => "WordPress prezentačné weby bez e-shopu\nFirmy, ktoré web majú a potrebujú ho zmodernizovať\nWeby, ktoré majú zostať na rovnakej adrese a udržať pozície v Google",
		'red_prekoho_grab'    => 'Väčšie weby, e-shopy a viacjazyčné projekty rád nacením na mieru. Napíšte mi rozsah.',
		'red_prekoho_cta'     => 'Napísať rozsah',

		'red_faq_eyebrow' => 'Otázky',
		'red_faq_title'   => 'Časté otázky',
		'red_faq'         => "Stratím pozície v Google? | Nie. Adresy podstránok zostanú rovnaké, a kde sa zmenia, nastavím trvalé presmerovanie. Prenesiem aj nadpisy a popisy stránok a po spustení skontrolujem Search Console.\nČo ak nemám texty a fotky? | Použijem tie, ktoré sú na webe teraz, a poviem vám, čo sa oplatí dofotiť alebo prepísať. Písanie textov a fotenie nie sú v cene balíka, viem ich však dohodnúť zvlášť.\nOdkedy plynie tých {dni} dní? | Od chvíle, keď mám všetky podklady a prístupy, nie od objednávky. Preto je dobré mať logo, texty a prístupy pripravené dopredu.\nMôžem si web upravovať sám? | Áno. Web zostáva vo WordPresse a na konci si spolu prejdeme administráciu, aby ste vedeli meniť texty, obrázky aj podstránky bez mojej pomoci.\nPostaráte sa o web aj po spustení? | Správu webu (aktualizácie, zálohy a drobné úpravy) viem zabezpečiť mesačne. Povinná nie je a dohodneme ju až podľa toho, čo reálne potrebujete.",

		'red_end_title' => 'Zostáva {volnych} z {miest} miest.',
		'red_end_text'  => 'Pošlite adresu webu a dozviete sa, čo na ňom zlepšiť.',
		'red_end_cta'   => 'Požiadať o miesto',

		'red_form_eyebrow' => 'Dopyt',
		'red_form_title'   => 'Požiadať o miesto',
		'red_form_note'    => 'Napíšte mi adresu webu a čo vám na ňom vadí. Do 24 hodín sa vám ozvem s krátkou analýzou, aj keď sa pre redizajn nerozhodnete.',
		'red_form_option'  => 'Redizajn prezentačného webu',

		/* ── stránka 404 ───────────────────────── */
		'e404_eyebrow'     => 'Chyba 404',
		'e404_title'       => 'Táto stránka tu nie je',
		'e404_text'        => 'Možno sa presunula, alebo je v odkaze preklep. Skúste hľadanie, niektorý z odkazov nižšie, alebo mi rovno napíšte.',
		'e404_search'      => 'Hľadať na webe',
		'e404_search_ph'   => 'Napríklad cenník alebo Reels',
		'e404_search_btn'  => 'Hľadať',
		'e404_links_title' => 'Kam ďalej',
		'e404_cta'         => 'Späť na úvod',

		/* ── pätička ───────────────────────────── */
		'footer_text' => '© {rok} Peter Miškus — IDEANET · videotvorca, grafik a content creator.',
		'skip_label'  => 'Preskočiť na portfólio',
		'nav_cta'     => 'Nezáväzný dopyt',
	);

	return $d;
}
