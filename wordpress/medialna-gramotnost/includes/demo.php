<?php
/**
 * Ukážkové stránky: celý obsah webu Mediálna gramotnosť poskladaný z prvkov pluginu.
 *
 * Pri aktívnom Avada Builderi sa stránky vytvoria z kontajnerov a stĺpcov Avada
 * ([fusion_builder_container] …), inak zo záložného [mg_section].
 * Odkazy {{url:slug}} sa po vytvorení nahradia skutočnými adresami stránok.
 *
 * @package MedialnaGramotnost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vytvorí shortcode s bezpečne zapísanými atribútmi.
 */
function mg_sc( $tag, $atts = array(), $content = null ) {
	$s = '[' . $tag;
	foreach ( $atts as $k => $v ) {
		$v  = str_replace( array( '"', '[', ']' ), array( '&quot;', '&#91;', '&#93;' ), (string) $v );
		$s .= ' ' . $k . '="' . $v . '"';
	}
	$s .= ']';
	if ( null !== $content ) {
		$s .= $content . '[/' . $tag . ']';
	}
	return $s;
}

/**
 * Viac potomkov naraz: mg_items('mg_card', [ [atts, content], … ]).
 */
function mg_items( $tag, $rows ) {
	$out = '';
	foreach ( $rows as $r ) {
		$out .= mg_sc( $tag, $r[0], isset( $r[1] ) ? $r[1] : '' );
	}
	return $out;
}

/**
 * Obsah všetkých ukážkových stránok.
 */
function mg_demo_pages() {
	$u = function ( $slug ) {
		return '{{url:' . $slug . '}}';
	};
	$pages = array();

	/* =========================== DOMOV =========================== */
	$pages['medialna-gramotnost'] = array(
		'title'    => 'Mediálna gramotnosť',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'large',
						'eyebrow'      => 'Podvody · dezinformácie · overovanie',
						'title'        => 'Neklikni hneď.',
						'highlight'    => 'Over si to.',
						'lead'         => 'Ukážeme vám na skutočných typoch správ, ako vyzerá podvodná SMS, e-mail, reklama na Facebooku či falošný telefonát. A čo robiť, keď sa to stane.',
						'button1_text' => 'Vyskúšať trenažér',
						'button1_link' => $u( 'trenazer' ),
						'button2_text' => 'Prišla mi podozrivá správa',
						'button2_link' => $u( 'overit-spravu' ) . '#kontrola',
					) ),
					mg_sc( 'mg_example', array(
						'label'    => 'Ukážka podľa varovania SK-CERT',
						'type'     => 'sms',
						'sender'   => '+212 6 41 xx xx xx',
						'subtitle' => 'SMS · Textová správa',
						'avatar'   => '+',
						'time'     => 'Dnes 09:41',
					), "Slovenska posta: Vas balik bol vrateny do skladu pre ==nespravnu adresu==. ==Doplatte poplatok 1,20 EUR== do 24 hodin: ==sk-posta.balik-info.top/sk==\n---\nNečakaný balík a problém s adresou. Slovenská pošta takto poplatky nevyberá.\nDrobná suma má znížiť ostražitosť. Podvodník chce číslo karty a SMS kód, ktorým si vašu kartu pridá do Apple Pay alebo Google Pay.\nStránka patrí doméne <b>balik-info.top</b>, nie pošte. Číslo odosielateľa je zahraničné." ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array( 'title' => 'S čím vám pomôžeme?' ) ) .
					mg_sc( 'mg_cards', array( 'columns' => '4' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'message', 'title' => 'Prišla mi podozrivá správa', 'link_text' => 'Skontrolovať správu →', 'link' => $u( 'overit-spravu' ) . '#kontrola' ), 'Odpovedzte na 7 otázok a zistíte, či ide o podvod.' ),
						array( array( 'icon' => 'doc', 'title' => 'Chcem spoznať podvody', 'link_text' => 'Otvoriť atlas →', 'link' => $u( 'atlas-podvodov' ) ), 'Atlas 12 najčastejších podvodov s ukážkami.' ),
						array( array( 'icon' => 'search', 'title' => 'Nerozumiem, čomu veriť', 'link_text' => 'Dezinformácie a AI →', 'link' => $u( 'dezinformacie' ) ), 'Dezinformácie, manipulácia, deepfakes a algoritmy.' ),
						array( array( 'icon' => 'alert', 'style' => 'danger', 'title' => 'Už sa mi to stalo', 'link_text' => 'Čo robiť teraz →', 'link' => $u( 'stalo-sa-mi-to' ) ), 'Zadali ste kartu alebo poslali peniaze? Konajte hneď.' ),
					) ) ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array(
						'eyebrow' => 'Ak si zapamätáte len jedno',
						'title'   => 'Tri pravidlá proti každému podvodu',
						'text'    => 'Fungujú pri SMS, e-maile, telefonáte aj na sociálnych sieťach. Podvodníci menia príbehy, ale tieto tri veci sa nemenia.',
					) ) .
					mg_sc( 'mg_steps', array( 'layout' => 'columns' ), mg_items( 'mg_step', array(
						array( array( 'label' => 'Pravidlo 1', 'title' => 'Zastav sa' ), 'Strach, nádej alebo súrnosť sú signál spomaliť. Žiadna skutočná banka ani úrad od vás nechce rozhodnutie do 10 minút.' ),
						array( array( 'label' => 'Pravidlo 2', 'title' => 'Over inou cestou' ), 'Nevolajte na číslo zo správy a neklikajte na odkaz. Zavolajte na číslo z karty, zmluvy alebo oficiálneho webu.' ),
						array( array( 'label' => 'Pravidlo 3', 'title' => 'Kódy nikomu' ), 'PIN, heslo, kód zo SMS ani údaje z karty nikomu nediktujte. Ani „banke“, ani „polícii“.' ),
					) ) ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array( 'title' => 'Aktuálne časté podvody na Slovensku', 'text' => 'Vybrali sme ich z varovaní SK-CERT, Národnej banky Slovenska a Polície SR.' ) ) .
					mg_sc( 'mg_warnings', array(), mg_items( 'mg_warning', array(
						array( array( 'tag' => 'SMS', 'title' => 'Falošná Slovenská pošta a kuriéri', 'link' => $u( 'atlas-podvodov' ) . '#sms-balik' ), 'SMS o balíku a doplatku. Cieľom je karta a SMS kód. Varovanie SK-CERT.' ),
						array( array( 'tag' => 'Telefonát', 'title' => 'Falošný bankár alebo policajt', 'link' => $u( 'atlas-podvodov' ) . '#falosny-bankar' ), '„Váš účet je napadnutý, presuňte peniaze na bezpečný účet.“ Varovanie Polície SR.' ),
						array( array( 'tag' => 'Facebook', 'title' => 'Investície s deepfake videom známych osobností', 'link' => $u( 'atlas-podvodov' ) . '#investicie-deepfake' ), 'Vymyslené rozhovory a videá politikov a moderátorov. Varovanie NBS.' ),
						array( array( 'tag' => 'WhatsApp', 'title' => '„Ahoj mami, mám nové číslo“', 'link' => $u( 'atlas-podvodov' ) . '#ahoj-mami' ), 'Podvodník sa vydáva za vaše dieťa a pýta peniaze. Varovanie Polície SR.' ),
						array( array( 'tag' => 'Bazár', 'title' => 'Falošný kupujúci na Bazoši a Marketplace', 'link' => $u( 'atlas-podvodov' ) . '#bazar' ), '„Pošlem kuriéra, peniaze si prevezmite cez odkaz.“ Varovanie Polície SR.' ),
					) ) ),
				),
			),
			array(
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'title' => 'Prečo na tom záleží', 'text' => 'Čísla z európskych inštitúcií. Odkazy na pôvodné správy nájdete v Zdrojoch.', 'button_text' => 'Zdroje', 'button_link' => $u( 'zdroje' ) ) ) .
					mg_sc( 'mg_stats', array(), mg_items( 'mg_stat', array(
						array( array( 'number' => '60 %', 'source' => 'ENISA Threat Landscape 2025' ), 'kybernetických útokov v EÚ začína phishingom, teda podvodnou správou.' ),
						array( array( 'number' => '36 %', 'source' => 'Flash Eurobarometer FL014EP, 2025' ), 'Európanov sa podľa vlastných slov stretlo s dezinformáciami „často“ alebo „veľmi často“ za posledný týždeň.' ),
						array( array( 'number' => 'č. 1', 'source' => 'Europol IOCTA 2025 a 2026' ), 'Podvody sú podľa Europolu najrýchlejšie rastúcou oblasťou organizovanej kriminality na internete. Pomáha im umelá inteligencia.' ),
					) ) ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array( 'title' => 'Nemáte čas? Pochopte to za minútu', 'button_text' => 'Všetkých 12 kariet', 'button_link' => $u( 'rychle-materialy' ) ) ) .
					mg_sc( 'mg_cards', array( 'columns' => '3' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'none', 'eyebrow' => 'Karta 2 · 1 min', 'title' => 'Volá „banka“ alebo „polícia“', 'remember' => 'Bezpečný účet neexistuje.', 'link_text' => '', 'link' => $u( 'rychle-materialy' ) . '#k2' ), '' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Karta 7 · 1 min', 'title' => 'Ako prečítať webovú adresu', 'remember' => 'Rozhoduje časť pred prvou lomkou.', 'link_text' => '', 'link' => $u( 'rychle-materialy' ) . '#k7' ), '' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Karta 8 · 1 min', 'title' => 'Overenie správy v 4 krokoch', 'remember' => 'Najprv over, potom zdieľaj.', 'link_text' => '', 'link' => $u( 'rychle-materialy' ) . '#k8' ), '' ),
					) ) ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array( 'title' => 'Pripravené pre vás', 'text' => 'Rovnaké princípy, iné príklady. Písmo si zväčšíte tlačidlom A+.' ) ) .
					mg_sc( 'mg_cards', array( 'columns' => '2' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'shield', 'eyebrow' => 'Pre seniorov a ich rodiny', 'title' => 'Jednoducho a bez ponáhľania', 'link_text' => 'Sprievodca pre seniorov →', 'link' => $u( 'pre-seniorov' ) ), 'Telefonáty od „banky“, „polície“ a „vnúčat“. Čo povedať a kedy zložiť. Rodinné heslo.' ),
						array( array( 'icon' => 'phone', 'eyebrow' => 'Pre mladých', 'title' => 'Sociálne siete, hry a brigády', 'link_text' => 'Sprievodca pre mladých →', 'link' => $u( 'pre-mladych' ) ), 'Vydieranie fotkami, „brigáda“, z ktorej je pranie peňazí, influenceri a algoritmy.' ),
					) ) ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_cta', array(
						'title'       => 'Spoznáte podvod? Vyskúšajte si to.',
						'text'        => '12 ukážok SMS, e-mailov, správ z WhatsAppu a Facebooku. Niektoré sú podvod, niektoré sú v poriadku. Po každej uvidíte vysvetlenie.',
						'button_text' => 'Spustiť trenažér',
						'button_link' => $u( 'trenazer' ),
					) ),
				),
			),
		),
	);

	/* =========================== ATLAS PODVODOV =========================== */
	$scams    = mg_demo_scams();
	$sections = array(
		array(
			'cols' => array(
				mg_sc( 'mg_hero', array(
					'size'      => 'page',
					'crumbs'    => 'Domov / Atlas podvodov',
					'eyebrow'   => '',
					'title'     => 'Atlas podvodov',
					'highlight' => '',
					'lead'      => '12 podvodov, s ktorými sa ľudia na Slovensku stretávajú najčastejšie. Pri každom je ukážka, varovné znaky a čo robiť. Tlačidlom „Ukázať varovné znaky“ ich zvýrazníte priamo v ukážke.',
					'button1_text' => '',
				) ) .
				mg_sc( 'mg_callout', array( 'style' => 'warn' ), '<p><b>Ukážky sú vytvorené pre výučbu.</b> Napodobňujú skutočné podvodné správy, o ktorých informovali SK-CERT, NBS, Polícia SR a Europol. Mená, čísla a adresy sú upravené alebo skrátené.</p>' ) .
				mg_sc( 'mg_filter', array( 'group' => 'atlas', 'label' => 'Kanál:' ) ),
			),
		),
	);
	foreach ( $scams as $s ) {
		$sections[] = array(
			'cols' => array(
				mg_sc( 'mg_scam', array(
					'id'           => $s['id'],
					'labels'       => $s['labels'],
					'title'        => $s['title'],
					'source'       => $s['source'],
					'filter_group' => 'atlas',
					'filter_tags'  => $s['tags'],
				), $s['text'] ),
				mg_sc( 'mg_example', $s['example'], $s['message'] ),
			),
		);
	}
	$sections[] = array(
		'cols' => array(
			mg_sc( 'mg_cta', array(
				'title'       => 'Viete ich už spoznať?',
				'text'        => 'Otestujte sa na 12 ukážkach. Niektoré sú v poriadku, niektoré podvod.',
				'button_text' => 'Spustiť trenažér',
				'button_link' => $u( 'trenazer' ),
			) ),
		),
	);
	$pages['atlas-podvodov'] = array( 'title' => 'Atlas podvodov', 'sections' => $sections );

	/* =========================== TRENAŽÉR =========================== */
	$pages['trenazer'] = array(
		'title'    => 'Trenažér podvodov',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Trenažér',
						'eyebrow'      => '',
						'title'        => 'Trenažér: podvod, alebo nie?',
						'highlight'    => '',
						'lead'         => '12 ukážok, ktoré by vám mohli prísť do mobilu alebo e-mailu. Pri každej sa rozhodnite. Potom uvidíte, čo ste mohli prehliadnuť.',
						'button1_text' => '',
					) ) .
					mg_sc( 'mg_trainer', array( 'more_link' => $u( 'atlas-podvodov' ) ), mg_demo_trainer_items() ) .
					mg_sc( 'mg_callout', array(), '<p>Ukážky sú vytvorené pre výučbu podľa skutočných podvodov, o ktorých informovali SK-CERT, NBS, Polícia SR a Europol. Mená a adresy sú vymyslené alebo skrátené. Niektoré správy sú zámerne v poriadku. Nie každá správa je podvod.</p>' ),
				),
			),
		),
	);

	/* =========================== OVERIŤ SPRÁVU =========================== */
	$pages['overit-spravu'] = array(
		'title'    => 'Overiť správu',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Overiť správu',
						'eyebrow'      => '',
						'title'        => 'Overte si to skôr, než kliknete',
						'highlight'    => '',
						'lead'         => 'Tri nástroje: kontrola podozrivej správy, kontrola webovej adresy a štyri kroky na overenie správ a fotiek.',
						'button1_text' => 'Kontrola správy',
						'button1_link' => '#kontrola',
						'button2_text' => 'Kontrola adresy',
						'button2_link' => '#adresa',
					) ),
				),
			),
			array(
				'id'   => 'kontrola',
				'cols' => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Nástroj 1', 'title' => 'Kontrola podozrivej správy', 'text' => 'Máte pred sebou SMS, e-mail, správu alebo telefonát? Odpovedzte na otázky. Nič sa nikam neodosiela.' ) ) .
					mg_sc( 'mg_checker', array(), mg_items( 'mg_checker_question', array(
						array( array( 'question' => 'Je správa nečakaná alebo od neznámeho odosielateľa či z nového čísla?', 'weight' => '2' ) ),
						array( array( 'question' => 'Tlačí vás na rýchlu akciu (do 24 hodín, inak…)?', 'weight' => '3' ) ),
						array( array( 'question' => 'Vyvoláva strach, alebo naopak sľubuje výhru, zisk či vrátenie peňazí?', 'weight' => '3' ) ),
						array( array( 'question' => 'Obsahuje odkaz, ktorý nevedie na oficiálny web odosielateľa?', 'weight' => '3' ) ),
						array( array( 'question' => 'Pýta si heslo, PIN, kód zo SMS alebo údaje z karty?', 'weight' => '5', 'hard' => 'yes' ) ),
						array( array( 'question' => 'Chce, aby ste poslali peniaze, presunuli úspory alebo nainštalovali aplikáciu?', 'weight' => '5', 'hard' => 'yes' ) ),
						array( array( 'question' => 'Žiada vás, aby ste o tom nikomu nehovorili?', 'weight' => '3' ) ),
					) ) ),
				),
			),
			array(
				'id'    => 'adresa',
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Nástroj 2', 'title' => 'Komu naozaj patrí odkaz?', 'text' => 'Podvodníci dávajú známe mená na začiatok adresy. Vložte adresu (stačí ju opísať, neotvárajte ju) a nástroj ju rozoberie.' ) ) .
					mg_sc( 'mg_url_checker' ),
				),
			),
			array(
				'id'   => 'styri-kroky',
				'cols' => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Na správy, fotky a videá', 'title' => 'Štyri kroky overenia', 'text' => 'Postup používajú overovatelia faktov (metóda SIFT). Zvládnete ho za pár minút v mobile.' ) ) .
					mg_sc( 'mg_steps', array( 'layout' => 'list' ), mg_items( 'mg_step', array(
						array( array( 'title' => 'Zastav sa' ), 'Ak vo vás správa vyvolá silnú emóciu (hnev, strach, nadšenie), nezdieľajte ju hneď. Opýtajte sa: viem, kto to napísal a prečo?' ),
						array( array( 'title' => 'Over zdroj' ), 'Otvorte novú kartu a vyhľadajte, kto stojí za webom alebo profilom. Má redakciu, impresum, autora? Ako dlho existuje? Čo o ňom píšu iní?' ),
						array( array( 'title' => 'Nájdi lepšie pokrytie' ), 'Píšu o tom aj iné, nezávislé médiá alebo oficiálne inštitúcie? Ak dôležitú správu nemá nikto iný, je to varovanie. Pozrite aj weby overovateľov faktov.' ),
						array( array( 'title' => 'Vráť sa k pôvodnému kontextu' ), 'Fotka môže byť skutočná, ale z iného roku či krajiny. Citát môže byť vytrhnutý. Nájdite pôvodný zdroj, napríklad spätným vyhľadávaním obrázka.' ),
					) ) ),
				),
			),
			array(
				'id'    => 'nastroje',
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'title' => 'Užitočné nástroje a overovatelia', 'text' => 'Bezplatné a dôveryhodné. Odkazy sa otvoria v novom okne.' ) ) .
					mg_sc( 'mg_cards', array( 'columns' => '3' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'none', 'eyebrow' => 'Fotky', 'title' => 'Google Lens a TinEye', 'link_text' => 'tineye.com →', 'link' => 'https://tineye.com' ), 'Spätné vyhľadávanie obrázka ukáže, kde a kedy sa fotka objavila prvýkrát.' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Videá', 'title' => 'InVID-WeVerify', 'link_text' => 'weverify.eu →', 'link' => 'https://weverify.eu/verification-plugin/' ), 'Doplnok do prehliadača na overovanie videí a fotiek. Vznikol vo výskumných projektoch financovaných EÚ.' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Európa', 'title' => 'EDMO a EUvsDisinfo', 'link_text' => 'edmo.eu →', 'link' => 'https://edmo.eu' ), 'Európske observatórium digitálnych médií a databáza vyvrátených dezinformácií.' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Slovensko', 'title' => 'AFP Fakty a Demagog.sk', 'link_text' => 'fakty.afp.com →', 'link' => 'https://fakty.afp.com' ), 'Slovenskí overovatelia faktov: vyvracajú virálne správy a kontrolujú výroky politikov.' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Financie', 'title' => 'Zoznam NBS', 'link_text' => 'subjekty.nbs.sk →', 'link' => 'https://subjekty.nbs.sk' ), 'Pred investíciou si overte, či má firma povolenie Národnej banky Slovenska.' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Nahlásenie', 'title' => 'SK-CERT', 'link_text' => 'sk-cert.sk →', 'link' => 'https://www.sk-cert.sk/sk/rady-a-navody/nahlasit-incident/index.html' ), 'Podvodné stránky a phishing môžete nahlásiť národnému centru kybernetickej bezpečnosti.' ),
					) ) ),
				),
			),
		),
	);

	/* =========================== DEZINFORMÁCIE =========================== */
	$pages['dezinformacie'] = array(
		'title'    => 'Dezinformácie a AI',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Dezinformácie a AI',
						'eyebrow'      => '',
						'title'        => 'Ako nás médiá a siete dokážu oklamať',
						'highlight'    => '',
						'lead'         => 'Dezinformácie, manipulačné triky, deepfakes a algoritmy. Čo hovorí EÚ, ako to spoznať a ako si nastaviť vlastný informačný svet.',
						'button1_text' => 'Kvíz',
						'button1_link' => '#kviz',
					) ),
				),
			),
			array(
				'id'   => 'pojmy',
				'cols' => array(
					mg_sc( 'mg_callout', array( 'title' => 'Definícia Európskej komisie', 'size' => 'large' ), '<p>Dezinformácia je preukázateľne nepravdivá alebo zavádzajúca informácia, ktorá sa vytvára a šíri pre ekonomický zisk alebo zámerné klamanie verejnosti a môže spôsobiť verejnú škodu.</p><p><small>Európska komisia, oznámenie „Boj proti online dezinformáciám: európsky prístup“, COM(2018) 236 (vlastný preklad)</small></p>' ) .
					mg_sc( 'mg_cards', array( 'columns' => '3' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'none', 'eyebrow' => 'Omyl', 'title' => 'Misinformácia', 'link_text' => '' ), 'Nepravdivá informácia, ktorú niekto šíri bez zlého úmyslu, lebo jej sám verí. Napríklad babka prepošle „zaručenú radu“ na liečbu.' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Zámer', 'title' => 'Dezinformácia', 'link_text' => '' ), 'Nepravda šírená zámerne, aby oklamala, zarobila peniaze alebo poškodila. Napríklad vymyslený „únik dokumentu“ pred voľbami.' ),
						array( array( 'icon' => 'none', 'eyebrow' => 'Zneužitie', 'title' => 'Malinformácia', 'link_text' => '' ), 'Pravdivá informácia použitá na ublíženie, napríklad zverejnenie súkromnej adresy alebo vytrhnutie z kontextu.' ),
					) ) ) .
					mg_sc( 'mg_callout', array(), '<p>Podľa Európskej komisie medzi dezinformácie <b>nepatrí</b> satira a paródia, novinárske chyby, ani jasne označené názory a komentáre. Názor, s ktorým nesúhlasíte, ešte nie je dezinformácia.</p>' ) .
					mg_sc( 'mg_stats', array(), mg_items( 'mg_stat', array(
						array( array( 'number' => '36 %', 'source' => 'Flash Eurobarometer FL014EP, prieskum o sociálnych médiách 2025' ), 'Európanov sa podľa vlastných slov stretlo s dezinformáciami „často“ alebo „veľmi často“ za posledný týždeň.' ),
						array( array( 'number' => '66 %', 'source' => 'Flash Eurobarometer FL014EP, 2025' ), 'Európanov si myslí, že sa s dezinformáciami stretli aspoň niekedy. Takmer tretina si nie je istá, že by ich spoznala.' ),
						array( array( 'number' => '80 %+', 'source' => 'ENISA Threat Landscape 2025' ), 'pozorovaného sociálneho inžinierstva vo svete začiatkom roka 2025 tvorili podľa ENISA kampane s pomocou umelej inteligencie.' ),
					) ) ),
				),
			),
			array(
				'id'   => 'techniky',
				'cols' => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Mentálne očkovanie', 'title' => 'Šesť techník, ktoré sa stále opakujú', 'text' => 'Kto pozná trik, ľahšie ho odhalí. Témy sa menia (zdravie, voľby, vojna, energie), techniky zostávajú.' ) ) .
					mg_sc( 'mg_cards', array( 'columns' => '3' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'none', 'title' => 'Emocionálny jazyk', 'link_text' => '' ), 'ŠOKUJÚCE, hanba, zrada, „toto vám tají“. Silné slová vypnú kritické myslenie a zvýšia zdieľanie.' ),
						array( array( 'icon' => 'none', 'title' => 'Falošná dilema', 'link_text' => '' ), '„Buď ste s nami, alebo ste proti národu.“ Zložitý problém zúži na dve krajné možnosti.' ),
						array( array( 'icon' => 'none', 'title' => 'Obetný baránok', 'link_text' => '' ), 'Za všetko môže jedna skupina. Jednoduchý vinník namiesto zložitých príčin.' ),
						array( array( 'icon' => 'none', 'title' => 'Útok na osobu', 'link_text' => '' ), 'Namiesto argumentu sa útočí na človeka: jeho vzhľad, pôvod, minulosť. O veci samotnej sa nedozviete nič.' ),
						array( array( 'icon' => 'none', 'title' => 'Konšpirácia', 'link_text' => '' ), '„Nič nie je náhoda, za tým stoja oni.“ Akýkoľvek protidôkaz sa vysvetlí ako súčasť sprisahania.' ),
						array( array( 'icon' => 'none', 'title' => 'Falošný expert a kontext', 'link_text' => '' ), '„Lekár varuje…“ bez mena, alebo skutočný expert mimo svojho odboru. Pravdivá fotka s vymysleným popisom.' ),
					) ) ),
				),
			),
			array(
				'id'    => 'ukazka',
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Rozbor', 'title' => 'Ako vyzerá manipulatívny príspevok', 'text' => 'Príspevok je vymyslený pre výučbu, ale skladá sa z presne tých prvkov, ktoré overovatelia faktov nachádzajú v skutočných dezinformáciách. Zapnite varovné znaky.' ) ) .
					mg_sc( 'mg_callout', array(), '<p><b>Ako by to vyzeralo pri overení:</b> vyhľadáte „zákaz kúrenia drevom 2027“ a nájdete, či existuje návrh zákona, kto ho predložil a čo v ňom naozaj je. Pozriete, či o tom píše aj iné médium a overovatelia faktov.</p>' ),
					mg_sc( 'mg_example', array(
						'label'        => 'Ukážka · Facebook',
						'type'         => 'facebook',
						'sender'       => '==Slobodný Hlas Pravdy==',
						'subtitle'     => 'pred 5 h · 🌐',
						'avatar'       => 'S',
						'avatar_color' => '#334155',
					), "==🔴 TOTO VÁM V TELEVÍZII NEPOVEDIA!== Od roku 2027 vám ==Brusel zakáže kúriť drevom== a bude chodiť kontrolovať domácnosti. ==Potvrdil to istý európsky úradník==. ==Kto mlčí, je spoluvinník!== ZDIEĽAJ, KÝM TO NEZMAŽÚ!!!\nimg: ==[foto: polícia pred rodinným domom – v skutočnosti zo zásahu v inej krajine v roku 2019]== | #7f1d1d\nstats: 8,7 tis. | 3,1 tis. zdieľaní\n---\nNeznáma stránka s „pravdou“ v názve, bez redakcie a autora.\nEmocionálny jazyk a tvrdenie, že „médiá to tajia“.\nObetný baránok a zveličenie. Skutočné pravidlá bývajú oveľa užšie (napr. normy na nové kotly).\nAnonymný „úradník“: nedá sa overiť.\nFalošná dilema: kto nezdieľa, je vinný.\nPravdivá fotka vo vymyslenom kontexte. Odhalí ju spätné vyhľadávanie obrázka." ),
				),
			),
			array(
				'id'   => 'deepfake',
				'cols' => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Umelá inteligencia', 'title' => 'Deepfakes: keď nemožno veriť ani očiam', 'text' => 'Deepfake je video, fotka alebo hlas vytvorený či upravený umelou inteligenciou tak, aby vyzeral ako skutočný.' ) ) .
					mg_sc( 'mg_cards', array( 'columns' => '2' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'search', 'title' => 'Na čo sa pozerať', 'link_text' => '' ), "Pery nesedia so zvukom, zuby a jazyk sú rozmazané. Zvláštne žmurkanie, „plávajúce“ okraje tváre. Deformované ruky, náušnice a text v pozadí. Hlas bez dýchania. Najdôležitejšie: kto to zverejnil prvý?" ),
						array( array( 'icon' => 'book', 'title' => 'Čo hovorí právo EÚ', 'link_text' => '' ), 'Podľa článku 50 Aktu o umelej inteligencii musí byť od 2. augusta 2026 obsah typu deepfake označený ako umelo vytvorený alebo upravený. Pozor: podvodníci pravidlá nedodržiavajú. Chýbajúce označenie neznamená, že video je pravé.' ),
						array( array( 'icon' => 'phone', 'title' => 'Klonovaný hlas v telefóne', 'link_text' => '' ), 'Pár sekúnd hlasu z videa na sociálnej sieti stačí na napodobnenie. Europol upozorňuje, že generatívna AI robí podvody osobnejšími. Dohodnite si s rodinou heslo, ktoré poznáte len vy.' ),
						array( array( 'icon' => 'alert', 'title' => 'Falošné reklamy so známymi tvárami', 'link_text' => 'Pozrite ukážku →', 'link' => $u( 'atlas-podvodov' ) . '#investicie-deepfake' ), 'Národná banka Slovenska varuje pred investičnými ponukami s deepfake videami slovenských politikov, hudobníkov a ďalších známych osobností.' ),
					) ) ),
				),
			),
			array(
				'id'    => 'algoritmy',
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Sociálne siete', 'title' => 'Prečo vidíte práve to, čo vidíte' ) ) .
					mg_sc( 'mg_callout', array(), '<p>Algoritmus vám ukazuje to, pri čom sa zastavíte, čo komentujete a zdieľate. Najviac pozornosti priťahuje hnev a strach. Postupne tak môžete skončiť v <b>informačnej bubline</b>.</p><p>Akt o digitálnych službách (DSA) platí v celej EÚ. Veľmi veľké platformy musia ponúknuť aspoň jednu možnosť zobrazenia obsahu, ktorá nie je založená na vašom profilovaní. Každá platforma vám musí umožniť nahlásiť nezákonný obsah.</p>' ),
					mg_sc( 'mg_heading', array( 'title' => 'Ako si „vyčistiť“ feed' ) ) .
					mg_sc( 'mg_steps', array( 'layout' => 'list' ), mg_items( 'mg_step', array(
						array( array( 'title' => 'Prepnite na chronologický feed' ), 'Na Facebooku a Instagrame hľadajte voľbu „Najnovšie“ alebo „Sledované“.' ),
						array( array( 'title' => 'Sledujte rôzne zdroje' ), 'Pridajte si aspoň dve seriózne médiá s rôznym pohľadom.' ),
						array( array( 'title' => 'Nekomentujte hnevom' ), 'Aj nahnevaný komentár hovorí algoritmu „chcem viac“. Radšej zvoľte „Nezaujíma ma“.' ),
						array( array( 'title' => 'Nahlasujte' ), 'Podvodné reklamy a nezákonný obsah nahláste cez tri bodky pri príspevku.' ),
					) ) ),
				),
			),
			array(
				'id'   => 'kviz',
				'cols' => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Kvíz', 'title' => 'Spoznáte techniku manipulácie?', 'text' => 'Päť vymyslených príspevkov. Ku každému vyberte, aký trik autor použil.' ) ),
					mg_sc( 'mg_quiz', array(), mg_items( 'mg_quiz_question', array(
						array( array( 'answers' => 'Falošná dilema; Falošný expert; Obetný baránok', 'correct' => '1', 'explanation' => 'Svet sa zúžil na dve možnosti. Pritom sa dá starať o deti a mať iný názor na protest.' ), '„Buď podporíš náš protest, alebo ti je jedno, čo bude s tvojimi deťmi.“' ),
						array( array( 'answers' => 'Útok na osobu; Obetný baránok; Konšpirácia', 'correct' => '2', 'explanation' => 'Zložitý problém (personál, financovanie, starnutie populácie) sa zvalí na jednu skupinu.' ), '„Nemocnice sú plné? Za všetko môžu ľudia, čo prišli zo zahraničia.“' ),
						array( array( 'answers' => 'Útok na osobu; Emocionálny jazyk; Falošná dilema', 'correct' => '1', 'explanation' => 'Útok na súkromie a vzhľad namiesto odpovede na jeho argumenty.' ), '„Ten ekonóm má tri rozvody a nosí smiešne okuliare. Prečo by sme mu verili?“' ),
						array( array( 'answers' => 'Obetný baránok; Falošný expert; Falošná dilema', 'correct' => '2', 'explanation' => 'Bezmenný „lekár“ bez zdroja a štúdie. Zdravotné rady overujte u svojho lekára.' ), '„Lekár z Ameriky varuje: tento čaj lieči cukrovku lepšie než inzulín!“' ),
						array( array( 'answers' => 'Konšpirácia; Útok na osobu; Falošný expert', 'correct' => '1', 'explanation' => 'Typické konšpiračné myslenie: nič nie je náhoda a každý, kto nesúhlasí, je súčasťou sprisahania.' ), '„Výpadok elektriny nebola porucha. Plánovali to roky, aby nás pripravili o slobodu. Kto tvrdí opak, je platený.“' ),
					) ) ),
				),
			),
		),
	);

	/* =========================== SENIORI =========================== */
	$pages['pre-seniorov'] = array(
		'title'    => 'Pre seniorov',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Pre seniorov',
						'eyebrow'      => '',
						'title'        => 'Bezpečne s telefónom a internetom',
						'highlight'    => '',
						'lead'         => 'Jednoduché rady bez cudzích slov. Ak sa vám text zdá malý, stlačte tlačidlo A+.',
						'button1_text' => '',
					) ) .
					mg_sc( 'mg_a11y', array( 'label' => 'Väčšie písmo:' ) ) .
					mg_sc( 'mg_callout', array( 'style' => 'danger', 'size' => 'large', 'title' => 'Banka, polícia ani pošta vás nikdy nebudú žiadať o PIN, heslo, kód zo SMS ani o presun peňazí.' ), '<p>Kto to od vás chce, je podvodník. Aj keď znie milo a vie vaše meno.</p>' ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array( 'title' => 'Šesť zlatých pravidiel' ) ) .
					mg_sc( 'mg_steps', array( 'layout' => 'list' ), mg_items( 'mg_step', array(
						array( array( 'title' => 'Nikam sa neponáhľajte' ), 'Podvodník tlačí: „hneď“, „do hodiny“, „inak prídete o všetko“. Skutočná banka vám dá čas.' ),
						array( array( 'title' => 'Zložte a zavolajte sami' ), 'Keď volá „banka“ alebo „polícia“, zložte. Potom zavolajte na číslo zo zadnej strany karty alebo na 158.' ),
						array( array( 'title' => 'Kódy nikomu' ), 'Kód zo SMS je ako podpis. Kto ho nadiktuje, potvrdí platbu.' ),
						array( array( 'title' => 'Neklikajte na odkazy v SMS' ), 'Pri balíkoch, preplatkoch a výhrach. Radšej sa opýtajte vnúčat alebo zavolajte na poštu.' ),
						array( array( 'title' => 'Peniaze nikomu do ruky' ), 'Žiadny „kuriér“, „policajt“ ani „advokát“ si nechodí po hotovosť ani zlato.' ),
						array( array( 'title' => 'Povedzte to blízkym' ), 'Podvodník chce, aby ste mlčali. Práve preto o tom povedzte rodine.' ),
					) ) ),
				),
			),
			array(
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'title' => 'Najčastejšie telefonáty a čo povedať', 'button_text' => 'Ukážky podvodov', 'button_link' => $u( 'atlas-podvodov' ) ) ) .
					mg_sc( 'mg_cards', array( 'columns' => '2' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'phone', 'eyebrow' => '„Volám z banky, váš účet je napadnutý“', 'title' => 'Povedzte: „Ďakujem, zavolám si do banky sám.“ A zložte.', 'link_text' => '' ), 'Potom vytočte číslo zo zadnej strany platobnej karty. Ak bolo všetko v poriadku, banka vám to potvrdí.' ),
						array( array( 'icon' => 'shield', 'eyebrow' => '„Tu polícia, potrebujeme vašu pomoc“', 'title' => 'Povedzte: „Prídem na najbližšie oddelenie.“ A zložte.', 'link_text' => '' ), 'Polícia nevyšetruje po telefóne s vašimi peniazmi. Zavolajte na 158 a overte si to.' ),
						array( array( 'icon' => 'users', 'eyebrow' => '„Babi, mal som nehodu, potrebujem peniaze“', 'title' => 'Povedzte: „Zavolám ti späť.“ A zavolajte na uložené číslo.', 'link_text' => '' ), 'Opýtajte sa niečo, čo vie len vaše vnúča, alebo použite rodinné heslo. Hlas sa dá napodobniť umelou inteligenciou.' ),
						array( array( 'icon' => 'monitor', 'eyebrow' => '„Tu technická podpora, váš počítač má vírus“', 'title' => 'Povedzte: „Nemám záujem.“ A zložte.', 'link_text' => '' ), 'Nikdy si na cudzí pokyn neinštalujte žiadny program. Microsoft ani banka vám sami od seba nevolajú.' ),
					) ) ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array( 'title' => 'Rodinné heslo' ) ) .
					mg_sc( 'mg_callout', array(), '<p>Dohodnite sa s deťmi a vnúčatami na jednom slove, ktoré nikde nepíšete. Napríklad meno prvého psa alebo obľúbené jedlo.</p><p>Keď vám niekto „z rodiny“ volá alebo píše o peniaze, opýtajte sa na heslo. Podvodník ho nebude vedieť, aj keby znel ako vaše vnúča.</p>' ),
					mg_sc( 'mg_callout', array( 'style' => 'ok', 'title' => 'Pre deti a vnúčatá' ), '<p>Prejdite si túto stránku s rodičmi a starými rodičmi. Zapíšte im na papier číslo na banku a na vás. Vyskúšajte spolu trenažér a vytlačte im kartičku do peňaženky.</p>' ) .
					mg_sc( 'mg_cta', array( 'title' => 'Kartička do peňaženky', 'text' => 'Tri pravidlá a dôležité čísla na vystrihnutie.', 'button_text' => 'Otvoriť kartičku', 'button_link' => $u( 'rychle-materialy' ) . '#penazenka' ) ),
				),
			),
			array(
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'title' => 'Už ste niečo zadali alebo poslali?', 'text' => 'Nehanbite sa. Podvodníci sú profesionáli a obeťou sa môže stať každý. Dôležité je konať rýchlo.', 'button_text' => 'Postup krok za krokom', 'button_link' => $u( 'stalo-sa-mi-to' ) ) ) .
					mg_sc( 'mg_contacts', array(), mg_items( 'mg_contact', array(
						array( array( 'label' => '1. Banka', 'number' => 'Číslo na karte' ), 'Zablokujte kartu a účet. Čím skôr, tým väčšia šanca vrátiť peniaze.' ),
						array( array( 'label' => '2. Polícia', 'number' => '158' ), 'Nahláste podvod. Zoberte si so sebou správy a výpisy.' ),
						array( array( 'label' => 'Pomoc obetiam', 'number' => '0850 111 321', 'small' => 'yes' ), 'Linka pomoci obetiam, nonstop.' ),
					) ) ),
				),
			),
		),
	);

	/* =========================== MLADÍ =========================== */
	$pages['pre-mladych'] = array(
		'title'    => 'Pre mladých',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Pre mladých',
						'eyebrow'      => '',
						'title'        => 'Online ťa nikto nevidí.',
						'highlight'    => 'Podvodník áno.',
						'lead'         => 'Na mladých cielia podvodníci inak: cez hry, brigády, flirt a influencerov. Tu je, ako to spoznať a čo robiť.',
						'button1_text' => 'Otestuj sa v trenažéri',
						'button1_link' => $u( 'trenazer' ),
					) ) .
					mg_sc( 'mg_cards', array( 'columns' => '2' ), mg_items( 'mg_card', array(
						array( array( 'icon' => 'alert', 'style' => 'danger', 'eyebrow' => 'Najvážnejšie', 'title' => 'Vydieranie intímnymi fotkami', 'link_text' => 'Ako to vyzerá →', 'link' => $u( 'atlas-podvodov' ) . '#vydieranie' ), 'Niekto si získa dôveru, vymení si fotky a potom vydiera. Neplať, nekomunikuj ďalej, urob snímky obrazovky a povedz to dospelému. Nie je to tvoja vina.' ),
						array( array( 'icon' => 'alert', 'eyebrow' => 'Trestný čin', 'title' => '„Brigáda“ cez tvoj účet', 'link_text' => 'Ako to vyzerá →', 'link' => $u( 'atlas-podvodov' ) . '#praca-mula' ), 'Prijmeš peniaze a pošleš ďalej? To je pranie peňazí. Europol každý rok odhalí tisíce takýchto „peňažných múl“, veľa z nich sú študenti.' ),
						array( array( 'icon' => 'users', 'eyebrow' => 'Účty', 'title' => '„Si to ty na videu?“', 'link_text' => 'Ako to vyzerá →', 'link' => $u( 'atlas-podvodov' ) . '#ukradnuty-ucet' ), 'Správa od kamaráta s odkazom. Jeho účet už ovláda niekto iný. Neprihlasuj sa cez odkaz a zapni si dvojstupňové overenie.' ),
						array( array( 'icon' => 'doc', 'eyebrow' => 'Nákupy', 'title' => 'Tenisky za 19,90 €', 'link_text' => 'Ako to vyzerá →', 'link' => $u( 'atlas-podvodov' ) . '#falosny-eshop' ), 'Falošné e-shopy z reklám na Instagrame a TikToku. Ak je cena neuveriteľná, väčšinou nie je pravdivá.' ),
					) ) ),
				),
			),
			array(
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'title' => 'Hry, skiny a „free“ meny' ) ) .
					mg_sc( 'mg_callout', array(), '<p>„Zadarmo V-Bucks“, „Robux generátor“, výmena skinov za lepšie. Väčšinou chcú tvoje prihlasovacie údaje, aby ti ukradli účet aj všetko, čo si si kúpil.</p><ul><li>Generátory hernej meny neexistujú.</li><li>Nikdy sa neprihlasuj do hry cez odkaz od niekoho iného.</li><li>Pri výmenách obchoduj len cez oficiálny systém hry.</li><li>Zapni si dvojstupňové overenie aj v herných účtoch.</li></ul>' ),
					mg_sc( 'mg_heading', array( 'title' => 'Influenceri a reklama' ) ) .
					mg_sc( 'mg_callout', array(), '<p>Keď influencer chváli produkt, často je to platená spolupráca. Mal by ju označiť (#reklama, „platené partnerstvo“). Ak to neurobí, ide o skrytú reklamu.</p><ul><li>Pýtaj sa: zarába na tom, že mi to odporúča?</li><li>Pozor na „investičné tipy“ a kurzy rýchleho zbohatnutia.</li><li>Recenzie si over aj inde než pod jeho videom.</li></ul>' ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array( 'title' => 'Tvoj feed nie je celý svet', 'button_text' => 'Ako si nastaviť feed', 'button_link' => $u( 'dezinformacie' ) . '#algoritmy' ) ) .
					mg_sc( 'mg_callout', array(), '<p>Algoritmus ti ukazuje to, pri čom sa zastavíš. Ak sa zastavuješ pri hneve, dostaneš viac hnevu. Ak pri konšpiráciách, budeš mať pocit, že im verí každý.</p>' ),
					mg_sc( 'mg_heading', array( 'title' => 'AI a deepfakes', 'button_text' => 'Ako spoznať deepfake', 'button_link' => $u( 'dezinformacie' ) . '#deepfake' ) ) .
					mg_sc( 'mg_callout', array(), '<p>Upravené fotky spolužiakov, falošné nahé fotky či napodobnený hlas nie sú vtip. Môže ísť o šikanu aj trestný čin. Takýto obsah nezdieľaj, nahlás ho a povedz to dospelému.</p>' ),
				),
			),
			array(
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'title' => 'Keď potrebuješ pomoc', 'button_text' => 'Čo robiť, keď sa to stalo', 'button_link' => $u( 'stalo-sa-mi-to' ) ) ) .
					mg_sc( 'mg_contacts', array(), mg_items( 'mg_contact', array(
						array( array( 'label' => 'Linka detskej istoty', 'number' => '116 111' ), 'Nonstop, zadarmo a anonymne.' ),
						array( array( 'label' => 'IPčko', 'number' => 'ipcko.sk', 'small' => 'yes' ), 'Chat a e-mail so psychológom, anonymne.' ),
						array( array( 'label' => 'Polícia', 'number' => '158' ), 'Pri vydieraní alebo ak ti niekto ukradol peniaze.' ),
					) ) ),
				),
			),
		),
	);

	/* =========================== STALO SA MI TO =========================== */
	$pages['stalo-sa-mi-to'] = array(
		'title'    => 'Stalo sa mi to',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Stalo sa mi to',
						'eyebrow'      => '',
						'title'        => 'Stalo sa vám to? Konajte hneď.',
						'highlight'    => '',
						'lead'         => 'Nehanbite sa. Obeťou podvodu sa môže stať každý. Rýchla reakcia často zachráni peniaze aj účty. Vyberte, čo sa stalo.',
						'button1_text' => '',
					) ) .
					mg_sc( 'mg_help_tree', array(), mg_items( 'mg_help_option', array(
						array( array( 'title' => 'Zadal som údaje z karty alebo kód', 'subtitle' => 'na stránke z SMS, e-mailu či inzerátu', 'heading' => 'Zadali ste údaje z karty alebo kód zo SMS' ), "Hneď volajte banke | Číslo je na zadnej strane karty a v aplikácii. Povedzte, že ide o podvod, a nechajte kartu zablokovať.\nSkontrolujte platby a Apple Pay / Google Pay | Požiadajte banku, aby odstránila neznáme zariadenia, do ktorých bola karta pridaná.\nZmeňte heslo do bankovníctva | Ak ste ho zadali, zmeňte ho cez oficiálnu aplikáciu.\nNahláste to polícii | Na čísle 158 alebo osobne. Uschovajte si SMS, e-maily a výpisy.\nNahláste stránku SK-CERT | Pomôžete, aby ju zablokovali aj pre iných." ),
						array( array( 'title' => 'Poslal som peniaze', 'subtitle' => 'prevodom, v hotovosti, v kryptomenách', 'heading' => 'Poslali ste peniaze podvodníkovi' ), "Hneď volajte svojej banke | Požiadajte o zastavenie alebo vrátenie platby. Pri okamžitých platbách rozhodujú minúty.\nPodajte trestné oznámenie | Na polícii (158). Prineste výpis, číslo účtu príjemcu, správy a čísla telefónov.\nUž nič ďalšie neposielajte | Podvodníci často sľubujú „vrátenie“ za poplatok. Aj to je podvod.\nPozor na „pomocníkov“ | Firmy, ktoré za poplatok sľubujú vrátiť stratené peniaze, sú často ďalší podvodníci." ),
						array( array( 'title' => 'Niekto mal prístup k počítaču', 'subtitle' => 'nainštaloval som program na jeho pokyn', 'heading' => 'Niekto sa pripojil k vášmu počítaču alebo mobilu' ), "Odpojte internet | Vypnite Wi-Fi alebo vytiahnite kábel.\nVolajte banke | Zablokujte prístup do internetbankingu a karty.\nOdinštalujte program | AnyDesk, TeamViewer alebo iný program, ktorý ste inštalovali na pokyn volajúceho.\nZ iného zariadenia zmeňte heslá | E-mail, banka, sociálne siete.\nDajte zariadenie skontrolovať | Odborníkovi, ktorého poznáte, a nahláste podvod na 158." ),
						array( array( 'title' => 'Ukradli mi účet', 'subtitle' => 'Facebook, Instagram, e-mail, hra', 'heading' => 'Ukradli vám účet na sociálnej sieti alebo e-mail' ), "Skúste obnoviť prístup | Cez oficiálnu stránku obnovy (napr. facebook.com/hacked alebo „Zabudnuté heslo“).\nVarujte priateľov | Inou cestou im dajte vedieť, aby neklikali na odkazy a neposielali peniaze.\nZmeňte heslá | Najmä ak ste rovnaké heslo používali aj inde.\nZapnite dvojstupňové overenie | Najlepšie cez aplikáciu, nie SMS." ),
						array( array( 'title' => 'Niekto ma vydiera', 'subtitle' => 'fotkami, videom, informáciami', 'heading' => 'Niekto vás vydiera' ), "Neplaťte | Platba vydieranie nezastaví. Vydierač si zvyčajne pýta ďalšie peniaze.\nPrestaňte komunikovať | Neodpovedajte, ale účet ešte nemažte.\nUložte dôkazy | Snímky obrazovky správ, mena profilu a odkazov.\nPovedzte to niekomu, komu veríte | Rodičovi, učiteľovi, kamarátovi. Mladí môžu volať na Linku detskej istoty 116 111.\nNahláste to | Polícii (158) a platforme. Pri fotkách maloletých je to vážny trestný čin." ),
						array( array( 'title' => 'Len mi prišla podozrivá správa', 'subtitle' => 'nič som nezadal ani neposlal', 'heading' => 'Prišla vám podozrivá správa', 'style' => 'normal', 'button_text' => 'Skontrolovať správu', 'button_link' => $u( 'overit-spravu' ) . '#kontrola' ), "Neklikajte a neodpovedajte | Ani „STOP“. Odpoveď potvrdí, že číslo je aktívne.\nOverte inou cestou | Zavolajte na známe číslo alebo sa prihláste cez aplikáciu.\nNahláste a vymažte | E-mail označte ako spam alebo phishing. Odkaz môžete nahlásiť SK-CERT. Varujte blízkych." ),
					) ) ),
				),
			),
			array(
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'title' => 'Dôležité kontakty' ) ) .
					mg_sc( 'mg_contacts', array(), mg_items( 'mg_contact', array(
						array( array( 'label' => 'Vaša banka', 'number' => 'Číslo na zadnej strane karty', 'small' => 'yes' ), 'Ako prvé, ak ide o peniaze alebo kartu. Nonstop.' ),
						array( array( 'label' => 'Polícia SR', 'number' => '158' ), 'Nahlásenie podvodu a trestné oznámenie.' ),
						array( array( 'label' => 'Tiesňová linka', 'number' => '112' ), 'Pri bezprostrednom ohrození.' ),
						array( array( 'label' => 'Linka pomoci obetiam', 'number' => '0850 111 321', 'small' => 'yes' ), 'Podpora pre obete trestných činov, nonstop.' ),
						array( array( 'label' => 'Linka detskej istoty', 'number' => '116 111' ), 'Pre deti a mladých, nonstop a anonymne.' ),
						array( array( 'label' => 'SK-CERT', 'number' => 'incident@nbu.gov.sk', 'small' => 'yes' ), 'Nahlásenie phishingu a podvodných stránok. Pri phishingu pošlite celú adresu stránky. Formulár je na sk-cert.sk.' ),
					) ) ),
				),
			),
		),
	);

	/* =========================== RÝCHLE MATERIÁLY =========================== */
	$pages['rychle-materialy'] = array(
		'title'    => 'Rýchle materiály',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Rýchle materiály',
						'eyebrow'      => '',
						'title'        => 'Pochopte to za minútu',
						'highlight'    => '',
						'lead'         => '12 krátkych kariet. Každá má príklad, varovné znaky, čo robiť a jednu vetu na zapamätanie. Vhodné do školy aj na rozhovor so starými rodičmi.',
						'button1_text' => 'Karty v PDF (5 strán A4)',
						'button1_link' => '{{pdf}}',
						'button2_text' => 'Kartička do peňaženky',
						'button2_link' => '#penazenka',
					) ) .
					mg_sc( 'mg_filter', array( 'group' => 'karty', 'label' => 'Pre koho:', 'options' => 'all|Všetky karty; seniori|Pre seniorov; mladi|Pre mladých; media|Médiá a dezinformácie' ) ) .
					mg_sc( 'mg_lessons', array(), mg_demo_lessons( $u ) ),
				),
			),
			array(
				'id'    => 'penazenka',
				'style' => 'alt',
				'cols'  => array(
					mg_sc( 'mg_heading', array( 'eyebrow' => 'Pre seniorov a ich rodiny', 'title' => 'Kartička do peňaženky', 'text' => 'Vytlačte, vystrihnite a doplňte čísla perom. Kartička patrí k platobnej karte, aby bola po ruke, keď niekto volá. Tip pre vnúčatá: vyplňte ju spolu so starými rodičmi.' ) ),
					mg_sc( 'mg_wallet' ),
				),
			),
		),
	);

	/* =========================== SLOVNÍK =========================== */
	$terms = array(
		array( 'Algoritmus', 'Program, ktorý rozhoduje, čo uvidíte vo feede. Odporúča obsah podľa toho, pri čom sa zastavíte a s čím reagujete.' ),
		array( 'Boti', 'Automatické alebo falošné účty, ktoré hromadne lajkujú, zdieľajú a komentujú, aby niečo vyzeralo populárnejšie.' ),
		array( 'Clickbait', 'Titulok, ktorý sľubuje viac, než článok obsahuje, aby ste naň klikli.' ),
		array( 'Deepfake', 'Video, fotka alebo hlas vytvorený či upravený umelou inteligenciou, aby vyzeral ako skutočný. Podľa Aktu o AI ho treba od 2. 8. 2026 označiť.' ),
		array( 'Dezinformácia', 'Preukázateľne nepravdivá alebo zavádzajúca informácia šírená zámerne pre zisk alebo klamanie verejnosti (definícia Európskej komisie).' ),
		array( 'Dvojstupňové overenie (2FA)', 'Prihlásenie heslom a ešte jedným kódom, napríklad z aplikácie. Aj keď niekto zistí heslo, bez kódu sa neprihlási.' ),
		array( 'DSA – Akt o digitálnych službách', 'Nariadenie EÚ, ktoré ukladá platformám povinnosti: umožniť nahlásiť nezákonný obsah, vysvetliť odporúčanie obsahu a obmedziť riziká.' ),
		array( 'Fact-checking (overovanie faktov)', 'Novinárska metóda overenia, či je tvrdenie pravdivé, pomocou zdrojov, dát a odborníkov.' ),
		array( 'Informačná bublina', 'Stav, keď vidíte najmä obsah, ktorý potvrdzuje váš názor, a máte pocit, že tak zmýšľajú všetci.' ),
		array( 'Malinformácia', 'Pravdivá informácia použitá na ublíženie, napríklad zverejnenie súkromných údajov alebo vytrhnutie z kontextu.' ),
		array( 'Misinformácia', 'Nepravdivá informácia šírená bez zlého úmyslu, lebo jej človek sám verí.' ),
		array( 'Natívna reklama', 'Platený obsah, ktorý vyzerá ako bežný článok alebo príspevok.' ),
		array( 'Peňažná mula', 'Človek, ktorý cez svoj účet posiela ďalej peniaze z trestnej činnosti. Je to pranie peňazí a je trestné.' ),
		array( 'Pharming', 'Presmerovanie na falošnú stránku aj vtedy, keď adresu napíšete správne (napr. po napadnutí zariadenia či siete).' ),
		array( 'Phishing', 'Podvodná správa (najčastejšie e-mail), ktorá sa vydáva za dôveryhodnú firmu a chce vaše heslá, údaje z karty alebo peniaze.' ),
		array( 'Pig butchering', 'Podvod, pri ktorom si podvodník dlho buduje dôveru (často romantickú) a potom obeť nahovorí na falošnú investíciu.' ),
		array( 'Quishing', 'Phishing cez QR kód, napríklad nalepený na parkovacom automate alebo v liste. Po naskenovaní vedie na falošnú stránku.' ),
		array( 'Romance scam', 'Podvod cez falošný vzťah na zoznamke či sociálnej sieti, ktorý končí žiadosťou o peniaze.' ),
		array( 'Sextortion', 'Vydieranie intímnymi fotkami alebo videom. Obeť nemá platiť, má uložiť dôkazy a vyhľadať pomoc.' ),
		array( 'Smishing', 'Phishing cez SMS, napríklad falošná správa o balíku alebo doplatku.' ),
		array( 'Sociálne inžinierstvo', 'Manipulácia ľudí (strach, dôvera, súrnosť), aby sami prezradili údaje alebo poslali peniaze.' ),
		array( 'Spoofing', 'Sfalšovanie odosielateľa, napríklad čísla volajúceho alebo e-mailovej adresy, aby vyzeral ako banka.' ),
		array( 'Troll', 'Účet, ktorý zámerne provokuje a rozdeľuje diskusiu. Niekedy je platený alebo koordinovaný.' ),
		array( 'Vishing', 'Podvodný telefonát (voice phishing), napríklad falošný bankár alebo policajt.' ),
	);
	$rows  = array();
	foreach ( $terms as $t ) {
		$rows[] = array( array( 'term' => $t[0] ), $t[1] );
	}
	$pages['slovnik'] = array(
		'title'    => 'Slovník pojmov',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Slovník',
						'eyebrow'      => '',
						'title'        => 'Slovník pojmov',
						'highlight'    => '',
						'lead'         => 'Phishing, smishing, deepfake a ďalšie slová, ktoré počujete v správach, vysvetlené jednoducho.',
						'button1_text' => '',
					) ) .
					mg_sc( 'mg_glossary', array(), mg_items( 'mg_term', $rows ) ),
				),
			),
		),
	);

	/* =========================== ZDROJE =========================== */
	$pages['zdroje'] = array(
		'title'    => 'Zdroje a metodika',
		'sections' => array(
			array(
				'cols' => array(
					mg_sc( 'mg_hero', array(
						'size'         => 'page',
						'crumbs'       => 'Domov / Zdroje a metodika',
						'eyebrow'      => '',
						'title'        => 'Zdroje a metodika',
						'highlight'    => '',
						'lead'         => 'Obsah webu vychádza z inštitúcií Európskej únie a slovenských úradov. Tu nájdete, odkiaľ sú čísla, varovania a pravidlá.',
						'button1_text' => '',
					) ),
				),
			),
			array(
				'cols' => array(
					mg_sc( 'mg_heading', array( 'title' => 'Európska únia' ) ) .
					mg_sc( 'mg_sources', array(), mg_items( 'mg_source', array(
						array( array( 'title' => 'ENISA Threat Landscape 2025', 'link_text' => 'enisa.europa.eu (PDF)', 'link' => 'https://www.enisa.europa.eu/sites/default/files/2026-01/ENISA%20Threat%20Landscape%202025_v1.2.pdf' ), 'Agentúra EÚ pre kybernetickú bezpečnosť. Phishing ako vstup v 60 % prienikov, podiel kampaní s AI.' ),
						array( array( 'title' => 'Europol IOCTA 2025 „Steal, deal and repeat“', 'link_text' => 'europol.europa.eu', 'link' => 'https://www.europol.europa.eu/media-press/newsroom/news/steal-deal-repeat-cybercriminals-cash-in-your-data' ), 'Obchod s ukradnutými údajmi, phishing, generatívna AI v sociálnom inžinierstve.' ),
						array( array( 'title' => 'Europol IOCTA 2026', 'link_text' => 'europol.europa.eu (PDF)', 'link' => 'https://www.europol.europa.eu/cms/sites/default/files/documents/IOCTA-2026.pdf' ), 'Podvody ako najrýchlejšie rastúca oblasť organizovanej kriminality na internete.' ),
						array( array( 'title' => 'Europol: správa o online podvodoch', 'link_text' => 'europol.europa.eu', 'link' => 'https://www.europol.europa.eu/media-press/newsroom/news/europol-publishes-iocta-spotlight-report-online-fraud-schemes' ), 'Investičné podvody, „pig butchering“, podvodné e-shopy.' ),
						array( array( 'title' => 'Europol a Eurojust: akcie EMMA', 'link_text' => 'eurojust.europa.eu', 'link' => 'https://www.eurojust.europa.eu/news/over-1-500-money-mules-identified-worldwide-money-laundering-sting' ), 'Verbovanie mladých ako peňažných múl cez sociálne siete.' ),
						array( array( 'title' => 'Europol: kampaň „Say No!“', 'link_text' => 'europol.europa.eu', 'link' => 'https://www.europol.europa.eu/media-press/newsroom/news/europol%E2%80%99s-%E2%80%98say-no%E2%80%99-campaign-travels-to-western-balkans-0' ), 'Vydieranie detí a mladých intímnym obsahom, rady pre obete.' ),
						array( array( 'title' => 'Európska komisia, COM(2018) 236', 'link_text' => 'eur-lex.europa.eu', 'link' => 'https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX%3A52018DC0236' ), 'Definícia dezinformácie a čo do nej nepatrí.' ),
						array( array( 'title' => 'Flash Eurobarometer FL014EP: Social Media Survey 2025', 'link_text' => 'data.europa.eu', 'link' => 'https://data.europa.eu/data/datasets/s3592_fl014ep_eng?locale=en' ), 'Vnímaná expozícia dezinformáciám v EÚ.' ),
						array( array( 'title' => 'Akt o umelej inteligencii, čl. 50', 'link_text' => 'digital-strategy.ec.europa.eu', 'link' => 'https://digital-strategy.ec.europa.eu/en/policies/code-practice-ai-generated-content' ), 'Povinnosť označovať deepfake obsah od 2. 8. 2026.' ),
						array( array( 'title' => 'Akt o digitálnych službách (DSA)', 'link_text' => 'digital-strategy.ec.europa.eu', 'link' => 'https://digital-strategy.ec.europa.eu/en/policies/digital-services-act-package' ), 'Nahlasovanie nezákonného obsahu, odporúčacie systémy bez profilovania.' ),
						array( array( 'title' => 'EDMO a EUvsDisinfo', 'link_text' => 'edmo.eu', 'link' => 'https://edmo.eu' ), 'Európske observatórium digitálnych médií (vrátane hubu CEDMO) a databáza vyvrátených dezinformácií.' ),
						array( array( 'title' => 'Better Internet for Kids', 'link_text' => 'better-internet-for-kids.europa.eu', 'link' => 'https://better-internet-for-kids.europa.eu' ), 'Iniciatíva EÚ pre bezpečnejší internet pre deti a mladých.' ),
					) ) ),
					mg_sc( 'mg_heading', array( 'title' => 'Slovensko' ) ) .
					mg_sc( 'mg_sources', array(), mg_items( 'mg_source', array(
						array( array( 'title' => 'SK-CERT: kampaň zneužívajúca Slovenskú poštu', 'link_text' => 'sk-cert.sk', 'link' => 'https://www.sk-cert.sk/en/urgent-warning-against-a-phishing-campaign-that-abuses-the-identity-of-slovenska-posta/index.html' ), 'Mechanizmus podvodu s kartou a SMS kódom (Apple Pay / Google Pay).' ),
						array( array( 'title' => 'SK-CERT: nahlásenie incidentu', 'link_text' => 'sk-cert.sk', 'link' => 'https://www.sk-cert.sk/sk/rady-a-navody/nahlasit-incident/index.html' ), 'Formulár a e-mail na nahlasovanie phishingu.' ),
						array( array( 'title' => 'Národná banka Slovenska: deepfake videá', 'link_text' => 'nbs.sk', 'link' => 'https://nbs.sk/aktuality/upozornenie-coraz-castejsie-deepfake-videa-testuju-nasu-obozretnost/' ), 'Investičné podvody so zneužitými známymi osobnosťami.' ),
						array( array( 'title' => 'Polícia SR: falošní bankári a policajti', 'link_text' => 'tasr.sk', 'link' => 'https://www.tasr.sk/tasr-clanok/TASR:2026061600000171' ), '„Bezpečný účet“, úvery vo vašom mene, kuriéri po hotovosť (správa TASR).' ),
						array( array( 'title' => 'Polícia SR: „Ahoj mami, mám nové číslo“', 'link_text' => 'pravda.sk', 'link' => 'https://uzitocna.pravda.sk/seniori/clanok/818149-pise-vam-dieta-z-noveho-cisla-policia-varuje-pred-podvodom-nerobte-tuto-vec-pridete-o-peniaze/' ), 'Podvod cez WhatsApp a SMS (správa Pravdy).' ),
						array( array( 'title' => 'Polícia SR: podvody na inzertných portáloch', 'link_text' => 'pravda.sk', 'link' => 'https://uzitocna.pravda.sk/spotrebitel/clanok/789066-predaval-cez-bazar-a-takmer-prisiel-o-peniaze-podvodnik-sa-vydaval-za-kupujuceho-policia-upozornuje-na-novy-trik/' ), 'Falošní kupujúci a „platobné brány“ (správa Pravdy).' ),
						array( array( 'title' => 'Linka detskej istoty', 'link_text' => 'ldi.sk', 'link' => 'https://ldi.sk' ), '116 111, nonstop.' ),
					) ) ),
					mg_sc( 'mg_callout', array( 'title' => 'Ako vznikali ukážky' ), '<p>Ukážky SMS, e-mailov, chatov a príspevkov sme vytvorili pre výučbu. Zachovávajú postupy a formulácie zo skutočných varovaní. Mená, čísla, účty a adresy sú vymyslené alebo skrátené (napr. „xx xx“), aby sa nedali zneužiť.</p>' ) .
					mg_sc( 'mg_callout', array( 'style' => 'warn', 'title' => 'Aktualizácia' ), '<p>Podvodníci menia príbehy každý týždeň. Obsah odporúčame pravidelne dopĺňať o nové varovania SK-CERT, NBS a Polície SR.</p>' ),
				),
			),
		),
	);

	return apply_filters( 'mg_demo_pages', $pages );
}

/**
 * 12 podvodov do atlasu.
 */
function mg_demo_scams() {
	return array(
		array(
			'id'      => 'sms-balik',
			'labels'  => 'SMS; Smishing; Veľmi časté',
			'title'   => 'Falošná Slovenská pošta a kuriéri',
			'tags'    => 'sms,seniori,mladi',
			'source'  => 'SK-CERT, varovanie pred kampaňou zneužívajúcou identitu Slovenskej pošty; Slovenská pošta',
			'text'    => '<p>Príde SMS, že balík čaká na doplatok alebo opravu adresy. Odkaz vedie na stránku, ktorá vyzerá ako pošta. Tam zadáte číslo karty a potom aj kód zo SMS. Podľa SK-CERT si tým podvodník pridá vašu kartu do Apple Pay alebo Google Pay a platí ňou.</p><h3>Varovné znaky</h3><ul><li>Nečakáte žiadny balík, alebo neviete, od koho je.</li><li>Žiadosť o drobný poplatok (1–3 €).</li><li>Odkaz nevedie na posta.sk, číslo odosielateľa je často zahraničné.</li><li>Termín „do 24 hodín“.</li></ul><h3>Čo robiť</h3><ul><li>Na odkaz neklikajte. Stav zásielky si overte na webe alebo v aplikácii prepravcu, ktorú si otvoríte sami.</li><li>Ak ste zadali kartu, <b>hneď volajte banke</b> a kartu zablokujte.</li></ul>',
			'example' => array( 'label' => 'Ukážka · SMS', 'type' => 'sms', 'sender' => '==+212 6 41 xx xx xx==', 'subtitle' => 'Textová správa', 'avatar' => '+', 'time' => 'Dnes 08:12' ),
			'message' => "Slovenska posta: Vasa zasielka SK48291 je pozastavena. ==Doplatte clo 1,49 EUR== a potvrdte adresu ==do 24 hodin==, inak bude vratena: ==posta-sk.zasielka-overenie.top==\n---\nZahraničné číslo (+212 je Maroko). Pošta takto nepíše.\nDrobný poplatok je návnada. Skutočný cieľ sú údaje z karty a SMS kód.\nČasový nátlak.\nSkutočná doména je <b>zasielka-overenie.top</b>. „posta-sk“ na začiatku je len prídavok.",
		),
		array(
			'id'      => 'phishing-banka',
			'labels'  => 'E-mail; Phishing',
			'title'   => 'E-mail „z banky“: účet bude zablokovaný',
			'tags'    => 'email,seniori',
			'source'  => 'ENISA Threat Landscape 2025; Polícia SR',
			'text'    => '<p>E-mail napodobňuje vašu banku, s logom aj farbami. Tvrdí, že účet bude obmedzený, ak „neoveríte údaje“. Odkaz vedie na falošné prihlásenie do internetbankingu. Phishing je podľa agentúry EÚ ENISA najčastejší spôsob, ako útočníci začínajú útok.</p><h3>Varovné znaky</h3><ul><li>Adresa odosielateľa nie je z domény banky.</li><li>Neosobné oslovenie („Vážený klient“).</li><li>Hrozba a krátky termín.</li><li>Tlačidlo na prihlásenie priamo v e-maile.</li></ul><h3>Čo robiť</h3><ul><li>Do bankovníctva sa prihlasujte len cez aplikáciu alebo adresu, ktorú si napíšete sami.</li><li>Podozrivý e-mail prepošlite svojej banke a vymažte.</li></ul>',
			'example' => array( 'label' => 'Ukážka · E-mail', 'type' => 'email', 'subject' => '==Bezpečnostné upozornenie: obmedzenie účtu==', 'sender' => 'Klientske centrum banky', 'address' => '==security@ib-overenie-klienta.com==', 'avatar' => 'B', 'avatar_color' => '#0f4c81' ),
			'message' => "==Vážený klient,==\nzaznamenali sme neobvyklé prihlásenie do vášho internetbankingu. Z bezpečnostných dôvodov bude účet ==obmedzený do 12 hodín==, ak neoveríte svoju totožnosť.\nbtn: ==Overiť totožnosť==\nfoot: Tento e-mail bol vygenerovaný automaticky, neodpovedajte naň.\n---\nStrašenie už v predmete.\nDoména <b>ib-overenie-klienta.com</b> nepatrí žiadnej banke.\nBanka vás pozná menom. Neosobné oslovenie znamená hromadnú rozosielku.\nHrozba a krátky termín majú zabrániť tomu, aby ste premýšľali.\nBanky neposielajú odkazy na prihlásenie. Toto tlačidlo vedie na falošnú stránku.",
		),
		array(
			'id'      => 'falosny-bankar',
			'labels'  => 'Telefonát; Vishing; Veľké škody',
			'title'   => 'Falošný bankár alebo policajt',
			'tags'    => 'telefon,seniori',
			'source'  => 'Polícia SR, varovania pred falošnými bankármi a policajtmi (2025–2026)',
			'text'    => '<p>Volá „bezpečnostné oddelenie banky“ alebo „polícia“. Vraj niekto napadol váš účet a peniaze treba presunúť na „bezpečný účet“. Niekedy vás nahovoria na úver vo vašom mene alebo na výber hotovosti pre „kuriéra“. Na displeji môže byť aj skutočné číslo banky, dá sa sfalšovať.</p><h3>Varovné znaky</h3><ul><li>„Bezpečný účet“ neexistuje. Banka ani polícia peniaze nepresúvajú.</li><li>Pýtajú si kód zo SMS, PIN alebo prihlasovacie údaje.</li><li>Chcú, aby ste si nainštalovali aplikáciu.</li><li>Zakazujú vám to s niekým prebrať.</li></ul><h3>Čo robiť</h3><ul><li>Zložte. Potom sami zavolajte na číslo zo zadnej strany karty.</li><li>Polícia SR nikdy nežiada údaje k bankovníctvu ani presun peňazí.</li></ul>',
			'example' => array( 'label' => 'Ukážka · Prepis hovoru', 'type' => 'call', 'sender' => 'Banka – bezpečnosť', 'subtitle' => '+421 2 xxxx xxxx · prichádzajúci hovor 04:12' ),
			'message' => "Volajúci: Dobrý deň, tu Martin z bezpečnostného oddelenia vašej banky. ==Zaznamenali sme pokus o krádež z vášho účtu.==\nVy: Preboha, čo mám robiť?\nVolajúci: Kvôli ochrane musíme peniaze presunúť ==na bezpečný účet==. Teraz vám príde SMS, ==nadiktujte mi kód==.\nVolajúci: ==Nikomu o tom nehovorte, ani v pobočke==, zamestnanec môže byť zapojený.\n---\nVyvolanie strachu hneď na začiatku.\n„Bezpečný účet“ je vždy účet podvodníka.\nKód zo SMS potvrdzuje platbu. Kto ho nadiktuje, zaplatí.\nIzolácia od rodiny a pobočky je typický znak podvodu.",
		),
		array(
			'id'      => 'investicie-deepfake',
			'labels'  => 'Facebook, YouTube; Deepfake; Investičný podvod',
			'title'   => 'Investície s deepfake videom známej osobnosti',
			'tags'    => 'socialne,seniori',
			'source'  => 'Národná banka Slovenska, upozornenie na deepfake videá; Europol IOCTA',
			'text'    => '<p>Platená reklama alebo „článok“ ukazuje známeho politika, moderátora či športovca. Odporúča zaručenú investíciu, často do kryptomien. Video je vyrobené umelou inteligenciou. Po registrácii volá „finančný poradca“, ktorý pýta vklad (často 250 €) a neskôr ďalšie peniaze.</p><h3>Varovné znaky</h3><ul><li>Sľub vysokého zisku bez rizika.</li><li>Známa tvár, ktorá by takú ponuku nikdy nerobila.</li><li>Web mimo oficiálnych stránok, s koncovkami ako .app, .tech alebo .ru.</li><li>Vypnuté komentáre, „ponuka len dnes“.</li></ul><h3>Čo robiť</h3><ul><li>Reklamu nahláste platforme. Neregistrujte sa a nedávajte telefón.</li><li>Pred investíciou si overte firmu v zozname NBS.</li></ul>',
			'example' => array( 'label' => 'Ukážka · Sponzorovaný príspevok', 'type' => 'facebook', 'sender' => 'Ekonomické Správy Dnes', 'subtitle' => 'Sponzorované · 🌐', 'avatar' => 'E', 'avatar_color' => '#b91c1c' ),
			'message' => "==ŠOKUJÚCE: Známy moderátor prezradil v priamom prenose==, ako zarába ==3 000 € týždenne==. Banky chcú tento rozhovor stiahnuť!\nimg: ==▶ [video: moderátor v štúdiu, hlas mierne nesedí s perami]== | #9f1239\nlink: ==quantum-profit-ai.app== | Začnite s vkladom 250 € ešte dnes\nstats: 2,4 tis. | ==Komentáre sú vypnuté==\n---\nSenzačný titulok a zneužité meno známej osoby.\nIstý a vysoký zisk neexistuje.\nDeepfake: pery nesedia so zvukom, tvár je rozmazaná na okrajoch, hlas je monotónny.\nCudzia doména, ktorá nepatrí banke ani regulovanej firme.\nVypnuté komentáre: varovania od iných ľudí by tu boli vidieť.",
		),
		array(
			'id'      => 'ahoj-mami',
			'labels'  => 'WhatsApp, SMS; Vydávanie sa za blízkeho',
			'title'   => '„Ahoj mami, mám nové číslo“',
			'tags'    => 'chat,seniori',
			'source'  => 'Polícia SR, varovanie pred podvodom „syn/dcéra v tiesni“ (2026)',
			'text'    => '<p>Neznáme číslo sa vydáva za vaše dieťa alebo vnúča. Starý mobil sa vraj rozbil. Niekedy si píše aj niekoľko dní a potom príde súrna žiadosť: zaplatiť faktúru, poslať peniaze, zadať kartu. S umelou inteligenciou môže prísť aj hlasová správa s napodobneným hlasom.</p><h3>Varovné znaky</h3><ul><li>Nové, neznáme číslo.</li><li>Súrna prosba o peniaze na cudzí účet.</li><li>„Nemôžem volať“, „píš sem“.</li></ul><h3>Čo robiť</h3><ul><li>Zavolajte dieťaťu na jeho <b>staré číslo</b>, ktoré poznáte.</li><li>Opýtajte sa niečo, čo vie len on (alebo použite rodinné heslo).</li><li>Peniaze neposielajte a na odkazy neklikajte.</li></ul>',
			'example' => array( 'label' => 'Ukážka · WhatsApp', 'type' => 'whatsapp', 'sender' => '+44 7700 9xx xxx', 'subtitle' => 'online', 'avatar' => '?' ),
			'message' => "! ==Toto číslo nie je vo vašich kontaktoch==\nAhoj mami, toto je moje nové číslo, starý mobil mi spadol do vody 😩 uloz si ho\n> Ahoj zlatko, čo sa stalo?\nPotrebujem dnes ==zaplatiť nájom 640 €==, banka na novom mobile ešte nefunguje. ==Pošleš na tento účet? Zajtra vrátim== SK31 xxxx xxxx\n==Teraz nemôžem volať==, som v práci ❤️\n---\nNeznáme číslo. Prvý krok je vždy zavolať na staré číslo.\nŽiadosť o peniaze hneď na začiatku.\nÚčet patrí cudzej osobe, nie vášmu dieťaťu.\nVyhýbanie sa hovoru: podvodník nemá hlas vášho dieťaťa.",
		),
		array(
			'id'      => 'bazar',
			'labels'  => 'Bazoš, Marketplace; Falošný kupujúci',
			'title'   => 'Falošný kupujúci na bazári',
			'tags'    => 'chat,web',
			'source'  => 'Polícia SR, varovania pred podvodmi na inzertných portáloch (2025)',
			'text'    => '<p>Predávate na Bazoši alebo Facebook Marketplace. Kupujúci nezjednáva, je „v zahraničí“ a pošle kuriéra. Platbu vraj už poslal, stačí si ju „prevziať“ cez odkaz. Stránka vyzerá ako kuriér alebo bazár a pýta číslo karty, kód zo SMS alebo prihlásenie do banky.</p><h3>Varovné znaky</h3><ul><li>Žiadne otázky o tovare, žiadne zjednávanie.</li><li>Kuriér, ktorého si objednáva kupujúci.</li><li>Odkaz na „prijatie platby“.</li></ul><h3>Čo robiť</h3><ul><li>Na prijatie peňazí stačí číslo účtu (IBAN). Nikdy nie karta, PIN ani kód.</li><li>Komunikujte len cez platformu a predávajte osobne.</li></ul>',
			'example' => array( 'label' => 'Ukážka · Správa od kupujúceho', 'type' => 'sms', 'sender' => 'Lukas K.', 'subtitle' => 'záujem o: Detský bicykel 24"', 'avatar' => 'L' ),
			'message' => "Dobry den, beriem ==za plnu cenu==. Som pracovne v Nemecku, ==posielam kuriera==.\nPlatba je uz odoslana, potvrdte prijem tu: ==bazos-sk.bezpecna-platba.live/prijem==\nMusite zadat ==cislo karty a kod z SMS== aby boli peniaze pripisane.\n---\nKupujúci bez otázok a zjednávania.\nKuriér zo zahraničia, ktorého organizuje kupujúci.\nFalošná „platobná brána“. Skutočná doména je <b>bezpecna-platba.live</b>.\nKód zo SMS slúži na potvrdenie platby z vášho účtu, nie na prijatie peňazí.",
		),
		array(
			'id'      => 'ukradnuty-ucet',
			'labels'  => 'Facebook, Instagram; Krádež účtu',
			'title'   => '„Si to ty na videu?“ a falošná podpora Meta',
			'tags'    => 'socialne,mladi',
			'source'  => 'Securitymagazin.sk; Europol IOCTA 2025 (krádeže a obchod s údajmi)',
			'text'    => '<p>Kamarát vám pošle odkaz s otázkou „Nie si to ty na tomto videu?“. Jeho účet už ovláda podvodník. Odkaz vedie na falošné prihlásenie. Správcom stránok zase chodí „upozornenie od Meta“, že stránka bude zmazaná pre porušenie pravidiel.</p><h3>Varovné znaky</h3><ul><li>Šokujúca otázka a odkaz bez ďalšieho kontextu.</li><li>Žiadosť o prihlásenie po kliknutí na odkaz.</li><li>„Meta“ píše z bežného profilu alebo cudzej domény.</li></ul><h3>Čo robiť</h3><ul><li>Napíšte kamarátovi inou cestou, že mu niekto ukradol účet.</li><li>Zapnite si dvojstupňové overenie (kód z aplikácie).</li><li>Upozornenia od Meta kontrolujte v Centre účtov, nie cez odkaz.</li></ul>',
			'example' => array( 'label' => 'Ukážka · Messenger', 'type' => 'whatsapp', 'sender' => 'Tomáš Novák', 'subtitle' => 'Messenger', 'avatar' => 'T', 'avatar_color' => '#a855f7' ),
			'message' => "==Toto si ty?? 😳😳==\n==fb-video.watch-now.click/v/77125==\n---\nŠokujúca správa bez kontextu, aj keď je od známeho.\nOdkaz nevedie na facebook.com, ale na falošné prihlásenie. Kto sa prihlási, stratí účet a podvodník pokračuje s jeho priateľmi.",
		),
		array(
			'id'      => 'falosny-eshop',
			'labels'  => 'Web, reklama; Falošný e-shop',
			'title'   => 'Falošný e-shop s obrovskými zľavami',
			'tags'    => 'web,socialne,mladi',
			'source'  => 'Europol IOCTA, online podvody v elektronickom obchode',
			'text'    => '<p>Reklama ponúka značkové topánky, elektroniku alebo „výpredaj pre zatvorenie predajne“ so zľavou 70–90 %. E-shop je nový, nemá kontakty ani obchodné podmienky. Tovar nepríde alebo príde lacný falzifikát.</p><h3>Varovné znaky</h3><ul><li>Neuveriteľná cena a odpočítavanie času.</li><li>Chýba IČO, adresa, reklamačný poriadok.</li><li>Len platba vopred alebo prevodom do zahraničia.</li></ul><h3>Čo robiť</h3><ul><li>Vyhľadajte názov obchodu so slovom „podvod“ alebo „recenzie“.</li><li>Plaťte kartou alebo na dobierku, nie prevodom cudzej osobe.</li><li>Ak ste platili kartou, požiadajte banku o reklamáciu platby (chargeback).</li></ul>',
			'example' => array( 'label' => 'Ukážka · Webová stránka', 'type' => 'web', 'url' => '==tenisky-vypredaj-sk.shop==' ),
			'message' => "title: ==ZATVÁRAME PREDAJŇU · Koniec akcie o 00:14:59==\nBežecké tenisky Air Pro: <s>149,00 €</s> ==19,90 €==\nPlatba: ==len prevodom vopred== · Kontakt: formulár\nbtn: Kúpiť teraz\n---\nNová doména bez histórie a značky.\nUmelý nátlak odpočítavaním.\nZľava vyše 80 % na značkový tovar.\nPlatba len vopred a žiadne kontakty: peniaze sa už nevrátia.",
		),
		array(
			'id'      => 'romanca',
			'labels'  => 'Zoznamky, sociálne siete; Romance scam',
			'title'   => 'Láska na diaľku, ktorá skončí investíciou',
			'tags'    => 'socialne,chat,seniori',
			'source'  => 'Europol, IOCTA a správa o online podvodoch',
			'text'    => '<p>Niekto sympatický sa ozve „omylom“ alebo na zoznamke. Píše týždne, buduje dôveru. Potom príde príbeh o colnici a nemocnici, alebo „spoločná investícia“ do kryptomien. Europol tento podvod volá „pig butchering“.</p><h3>Varovné znaky</h3><ul><li>Nikdy sa nechce stretnúť ani zapnúť kameru.</li><li>Rýchle vyznania lásky.</li><li>Rozhovor sa stočí k peniazom alebo investícii.</li></ul><h3>Čo robiť</h3><ul><li>Fotku profilu vyhľadajte cez Google Lens. Často patrí inej osobe.</li><li>Nikdy neposielajte peniaze niekomu, koho ste nestretli osobne.</li></ul>',
			'example' => array( 'label' => 'Ukážka · Chat po 3 týždňoch', 'type' => 'whatsapp', 'sender' => 'Daniel ❤️', 'subtitle' => 'online', 'avatar' => 'D', 'avatar_color' => '#d97706' ),
			'message' => "Dobré ráno, moja láska ☀️ ==Kamera mi stále nefunguje==, prepáč.\nMôj strýko mi ukázal platformu, kde som za mesiac ==zarobil 9 000 $==. Chcem, aby sme mali spoločnú budúcnosť.\n==Skús vložiť aspoň 1 000 €, pomôžem ti== 🙏\n---\nVyhýbanie sa videohovoru: osoba neexistuje alebo vyzerá inak.\nUkážka „zisku“ má vzbudiť dôveru.\nPrvá žiadosť o peniaze. Po nej príde ďalšia a ďalšia.",
		),
		array(
			'id'      => 'praca-mula',
			'labels'  => 'Telegram, Instagram; Peňažná mula; Trestný čin',
			'title'   => '„Brigáda“: prijmi peniaze a pošli ďalej',
			'tags'    => 'chat,socialne,mladi',
			'source'  => 'Europol, European Money Mule Action (EMMA), kampaň #DontBeAMule',
			'text'    => '<p>Ponuka ľahkého zárobku: na váš účet prídu peniaze, vy ich pošlete ďalej a časť si necháte. Ide o pranie peňazí z podvodov. Europol každoročne v akcii EMMA odhalí tisíce takzvaných peňažných múl a varuje, že zločinci verbujú najmä mladých cez sociálne siete.</p><h3>Varovné znaky</h3><ul><li>Vysoký zárobok za minimálnu prácu.</li><li>Práca spočíva v prijímaní a posielaní peňazí.</li><li>Žiadosť o prístup k účtu alebo karte.</li></ul><h3>Čo robiť</h3><ul><li>Nikdy nepožičiavajte svoj účet ani kartu. Zodpovedáte za to vy.</li><li>Ak ste už do toho spadli, hneď to povedzte banke a polícii.</li></ul>',
			'example' => array( 'label' => 'Ukážka · Instagram, správa', 'type' => 'sms', 'sender' => 'easy.money.jobs.sk', 'subtitle' => 'Instagram · Žiadosť o správu', 'avatar' => '$', 'avatar_color' => '#db2777' ),
			'message' => "Čau! Zarob si ==až 500 € týždenne== z mobilu 💸 aj pre študentov 16+\nStačí mať bankový účet. ==Prijmeš platbu, pošleš ju ďalej== a 15 % je tvojich.\n==Pošli mi foto karty z oboch strán== pre overenie 😉\n---\nNeprimeraný zárobok, cielený na mladých.\nToto je pranie peňazí. Za ukradnuté peniaze na vašom účte môžete niesť trestnú zodpovednosť.\nKto má fotku karty, môže ňou platiť.",
		),
		array(
			'id'      => 'vydieranie',
			'labels'  => 'Snapchat, Instagram, hry; Sextortion',
			'title'   => 'Vydieranie intímnymi fotkami',
			'tags'    => 'socialne,chat,mladi',
			'source'  => 'Europol, kampaň „Say No!“; Better Internet for Kids (EÚ)',
			'text'    => '<p>Neznámy profil (často „rovesník“ alebo atraktívne dievča či chlapec) si získa dôveru a vymení si intímne fotky. Potom príde hrozba: zaplať, inak to pošleme rodine a spolužiakom. Niekedy ide o fotky upravené umelou inteligenciou. Europol na tento trestný čin upozorňuje kampaňou „Say No!“.</p><h3>Varovné znaky</h3><ul><li>Nový kontakt, ktorý rýchlo prejde na flirt.</li><li>Žiadosť presunúť sa do inej aplikácie.</li><li>Tlak na fotky alebo video.</li></ul><h3>Čo robiť</h3><ul><li><b>Neplaťte</b> a prestaňte komunikovať.</li><li>Urobte snímky obrazovky a profil nahláste.</li><li>Povedzte to dospelému, ktorému veríte. Nie je to vaša vina. Volajte 116 111 alebo políciu.</li></ul>',
			'example' => array( 'label' => 'Ukážka · Chat', 'type' => 'sms', 'sender' => 'nina_2009', 'subtitle' => 'pridaná pred 2 dňami', 'avatar' => 'N', 'avatar_color' => '#0ea5e9' ),
			'message' => "si fakt zlatý 😍 ==poďme radšej na snap==\n==pošli mi fotku, ja ti tiež== 🙈\n==Mám tvoje fotky a zoznam tvojich priateľov. Pošli 200 € do hodiny==, inak ich uvidí celá škola.\n---\nPresun do aplikácie, kde správy miznú.\nTlak na intímne fotky od človeka, ktorého nepoznáte.\nVydieranie. Platba nič nevyrieši, vydierač si zvyčajne pýta ďalej. Hľadajte pomoc.",
		),
		array(
			'id'      => 'technicka-podpora',
			'labels'  => 'Web, telefonát; Tech support scam',
			'title'   => 'Falošná technická podpora',
			'tags'    => 'web,telefon,seniori',
			'source'  => 'ENISA, sociálne inžinierstvo; Polícia SR',
			'text'    => '<p>Na obrazovke vyskočí hlásenie, že počítač je napadnutý, a číslo „podpory Microsoftu“. Niekedy zavolajú priamo. „Technik“ vás navedie nainštalovať program na vzdialený prístup (napr. AnyDesk) a potom sa prihlási do vášho bankovníctva.</p><h3>Varovné znaky</h3><ul><li>Hlasné varovanie, blikanie, zákaz vypnúť počítač.</li><li>Telefónne číslo priamo v okne.</li><li>Žiadosť o inštaláciu programu na vzdialenú správu.</li></ul><h3>Čo robiť</h3><ul><li>Zatvorte prehliadač (prípadne reštartujte počítač). Nevolajte.</li><li>Ak ste niekomu dali prístup, odpojte internet, volajte banke a dajte počítač skontrolovať.</li></ul>',
			'example' => array( 'label' => 'Ukážka · Vyskakovacie okno', 'type' => 'web', 'url' => '==windows-defender-alert.online==/sk' ),
			'message' => "alert: <b>⚠ Windows Defender – kritická hrozba</b>\nalert: ==Váš počítač je zablokovaný. Neodpájajte ho!==\nalert: Trojan:Win32/BankStealer zistený\nalert: <b>==Volajte podporu: +421 2 xxx xx xxx==</b>\n---\nAdresa nepatrí Microsoftu. Skutočný antivírus nebeží na webe.\nStrašenie a zákaz vypnúť počítač. Okno sa dá zavrieť.\nMicrosoft nedáva telefónne čísla do vyskakovacích okien.",
		),
	);
}

/**
 * 12 ukážok trenažéra.
 */
function mg_demo_trainer_items() {
	$items = array(
		array( array( 'verdict' => 'scam', 'kind' => 'SMS', 'type' => 'sms', 'sender' => '+212 6 41 xx xx xx', 'avatar' => '+', 'subtitle' => 'SMS', 'time' => 'Dnes 09:41' ), "Slovenska posta: Vas balik nemohol byt doruceny pre ==neuplnu adresu==. ==Doplatte 1,20 EUR== a aktualizujte udaje do 24 hod: ==posta-sk.dorucenie-balik.top==\n---\nNečakaný balík a výzva na drobný doplatok. Slovenská pošta poplatky cez SMS nevyberá.\nMalá suma má znížiť ostražitosť. Cieľom sú údaje z karty a SMS kód, ktorým podvodník pridá vašu kartu do svojho mobilu.\nSkutočná adresa je <b>dorucenie-balik.top</b>, nie pošta. Číslo odosielateľa je zahraničné." ),
		array( array( 'verdict' => 'safe', 'kind' => 'SMS', 'type' => 'sms', 'sender' => 'Moja banka', 'avatar' => 'B', 'subtitle' => 'SMS', 'time' => 'Dnes 18:13' ), "Platba kartou *4417: 23,40 EUR, LIDL, 30.09. 18:12. Ak ste platbu nevykonali, volajte cislo uvedene na zadnej strane karty.\n---\nSpráva len informuje o platbe, ktorú ste urobili. Nemá odkaz a nič od vás nechce.\nOdporúča volať číslo z vašej karty, nie číslo v správe. Tak píšu skutočné banky." ),
		array( array( 'verdict' => 'scam', 'kind' => 'E-mail', 'type' => 'email', 'subject' => 'Oznámenie o vrátení preplatku dane', 'sender' => 'Finančná správa SR', 'address' => '==noreply@fs-vratka-online.com==', 'avatar' => 'FS', 'avatar_color' => '#0b6e4f' ), "Vážený daňovník,\npo kontrole vášho daňového priznania vám vzniká nárok na vrátenie ==preplatku 248,60 €==.\nPre pripísanie sumy ==potvrďte údaje platobnej karty do 48 hodín==, inak nárok zaniká.\nbtn: ==Prevziať preplatok==\n---\nAdresa odosielateľa nie je z domény štátu (.gov.sk / financnasprava.sk).\nNečakané peniaze sú návnada. Štát neposiela preplatky na základe e-mailu.\nČasový nátlak a žiadosť o kartu. Na prijatie peňazí nikdy netreba zadávať údaje z karty.\nTlačidlo vedie mimo oficiálny web. Prihlasujte sa vždy cez adresu, ktorú si napíšete sami." ),
		array( array( 'verdict' => 'scam', 'kind' => 'WhatsApp', 'type' => 'whatsapp', 'sender' => '+44 7700 9xx xxx', 'avatar' => '?', 'subtitle' => 'online' ), "! Toto číslo nie je vo vašich kontaktoch\n==Ahoj mami, toto je moje nové číslo==, starý mobil sa mi rozbil 😩\nPotrebujem nutne zaplatiť faktúru, z novej appky to zatiaľ nejde. ==Pošleš mi 780 € na tento účet? Dnes ti to vrátim==\n==Nevolaj, mám slabý signál==, píš sem ❤️\n---\n„Nové číslo“ je klasický začiatok podvodu „syn/dcéra v tiesni“. Polícia SR naň opakovane upozorňuje.\nSúrna žiadosť o peniaze na cudzí účet.\nSnaha zabrániť overeniu hlasom. Zavolajte dieťaťu na jeho staré číslo." ),
		array( array( 'verdict' => 'scam', 'kind' => 'Facebook', 'type' => 'facebook', 'sender' => 'Ekonomické Správy Dnes', 'subtitle' => 'Sponzorované · 🌐', 'avatar' => 'E', 'avatar_color' => '#b91c1c' ), "==Známy moderátor prekvapil v priamom prenose:== „Vďaka tejto platforme zarábam ==3 000 € týždenne== bez práce.“ Banky chcú, aby ste to nevedeli! ==Ponuka platí len dnes.==\nimg: [video: moderátor v štúdiu, logo televízie]\nlink: ==smart-invest-ai.app== | Zaregistrujte sa a začnite s 250 €\nstats: 1,2 tis. | Komentáre sú vypnuté\n---\nZneužitie tváre známej osoby. NBS upozorňuje, že podvodníci používajú deepfake videá politikov, športovcov a moderátorov.\nSľub vysokého a istého zisku bez rizika neexistuje.\nNátlak „len dnes“.\nCudzia doména (.app) namiesto webu banky alebo regulovaného obchodníka. Minimálny vklad 250 € je typický." ),
		array( array( 'verdict' => 'safe', 'kind' => 'E-mail', 'type' => 'email', 'subject' => 'Rodičovské združenie – štvrtok 17:00', 'sender' => 'Mgr. Jana Kováčová', 'address' => 'kovacova@zs-lipova.edu.sk', 'avatar' => 'JK', 'avatar_color' => '#7c3aed' ), "Dobrý deň,\npozývam vás na triedne rodičovské združenie 5.B vo štvrtok 9. 10. o 17:00 v triede č. 12. Budeme hovoriť o lyžiarskom kurze.\nAk nemôžete prísť, stačí mi odpísať.\nS pozdravom, Jana Kováčová, triedna učiteľka\n---\nOdosielateľa poznáte a adresa sedí so školou.\nNežiada peniaze, heslá ani kliknutie na odkaz. Nie je tu nátlak." ),
		array( array( 'verdict' => 'scam', 'kind' => 'Chat bazár', 'type' => 'whatsapp', 'sender' => 'Peter (kupujúci)', 'avatar' => 'P', 'subtitle' => 'naposledy online pred 1 min' ), "Dobrý deň, kočík ešte máte? Beriem ho ==bez zjednávania==.\nSom v zahraničí, ==pošlem kuriéra==. Platbu som už uhradil, prevezmite si ju tu: ==dpd-sk.platba-prijem.shop/94812==\nTreba tam ==zadať číslo karty a kód zo SMS==, aby vám prišli peniaze.\n---\nKupujúci nezjednáva a ani nechce tovar vidieť.\nKuriér zo zahraničia a „už zaplatené“ sú typický scenár, pred ktorým varuje Polícia SR.\nOdkaz nevedie na stránku kuriéra (skutočná doména je <b>platba-prijem.shop</b>).\nNa prijatie peňazí netreba kartu ani kód. Zadaním dávate podvodníkovi prístup k svojim peniazom." ),
		array( array( 'verdict' => 'scam', 'kind' => 'Messenger', 'type' => 'whatsapp', 'sender' => 'Zuzka Horváthová', 'avatar' => 'Z', 'subtitle' => 'Messenger' ), "==Pozri, nie si to ty na tomto videu?? 😱==\n==video-fb.watch-clip.live/v=8812==\n---\nŠokujúca otázka od známeho človeka. Jeho účet je pravdepodobne ukradnutý a správa ide hromadne všetkým priateľom.\nOdkaz vedie na falošné prihlásenie do Facebooku. Kto sa prihlási, príde o účet. Napíšte Zuzke inou cestou." ),
		array( array( 'verdict' => 'safe', 'kind' => 'SMS', 'type' => 'sms', 'sender' => 'Google', 'avatar' => 'G', 'subtitle' => 'SMS', 'situation' => 'Situácia: práve sa prihlasujete do svojho e-mailu a stránka si pýta kód.' ), "G-482913 je váš overovací kód Google. Nikomu ho neposkytujte.\n---\nKód ste si vyžiadali vy, práve teraz, na stránke, ktorú ste otvorili sami.\nPozor: ak by kód prišiel <b>bez toho, aby ste sa prihlasovali</b>, niekto pozná vaše heslo. Kód nikomu nediktujte a heslo si zmeňte." ),
		array( array( 'verdict' => 'scam', 'kind' => 'Web', 'type' => 'web', 'url' => '==microsoft-podpora-alert.online==/sk/' ), "alert: <b>⚠ VÁŠ POČÍTAČ JE NAPADNUTÝ</b>\nalert: ==Neodpájajte ani nevypínajte počítač!== Boli zistené vírusy, ktoré kradnú bankové údaje.\nalert: ==Okamžite volajte technickú podporu Microsoft: +421 2 xxx xx xxx==\n---\nAdresa nepatrí spoločnosti Microsoft.\nStrašenie a zákaz vypnúť počítač. Okno sa dá zavrieť a počítač reštartovať.\nSkutočná firma vám nikdy nenapíše číslo do vyskakovacieho okna. Na telefóne vás „technik“ navedie nainštalovať program na vzdialený prístup." ),
		array( array( 'verdict' => 'scam', 'kind' => 'Telegram', 'type' => 'whatsapp', 'sender' => 'HR Manager – Práca z domu', 'avatar' => 'HR', 'subtitle' => 'Telegram' ), "Ahoj! Hľadáme brigádnikov 16+. ==150 € denne, 1 hodina práce z mobilu== 💸\nÚloha: ==na tvoj účet prídu platby od klientov, ty ich pošleš ďalej== a 10 % si necháš.\n==Nikomu o tom nehovor==, je to exkluzívna ponuka.\n---\nNeprimerane vysoký zárobok za nič.\nPosielanie cudzích peňazí cez svoj účet je pranie špinavých peňazí. Europol varuje, že takto verbujú „peňažné muly“, najmä mladých.\nŽiadosť o utajenie je vždy varovanie." ),
		array( array( 'verdict' => 'scam', 'kind' => 'E-mail', 'type' => 'email', 'subject' => '==Posledná výzva:== vaše predplatné bude zrušené', 'sender' => 'StreamPlus', 'address' => '==billing@streamplus-support-team.net==', 'avatar' => 'S', 'avatar_color' => '#e11d48' ), "Dobrý deň zákazník,\nvašu poslednú platbu sa nepodarilo spracovať. ==Aktualizujte platobné údaje do 24 hodín==, inak bude váš účet zrušený.\nbtn: Aktualizovať platbu\n---\nStrašenie v predmete.\nDoména nepatrí službe. Pridané slová ako support, team či secure sú častý trik.\nNeosobné oslovenie a nátlak na zadanie karty. Skontrolujte to v aplikácii služby, nie cez odkaz." ),
	);
	return mg_items( 'mg_trainer_item', $items );
}

/**
 * 12 rýchlych kariet.
 */
function mg_demo_lessons( $u ) {
	$a = $u( 'atlas-podvodov' );
	$rows = array(
		array( 'k1', 'KARTA 1 · 1 MIN', 'SMS', 'yes', 'SMS o balíku', '„Vaša zásielka čaká. Doplatte 1,49 € do 24 hodín: posta-sk.zasielka-overenie.top“', 'Nečakáte balík; Malý doplatok; Cudzia adresa odkazu', 'Neklikať; Stav overiť v appke prepravcu; Zadali ste kartu? Volať banke', 'Pošta ani kuriér nevyberajú poplatky cez odkaz v SMS.', 'Rýchla otázka: prečo chcú aj kód zo SMS?', 'Kódom potvrdíte pridanie vašej karty do mobilu podvodníka (Apple Pay, Google Pay). Potom platí vašou kartou. Upozorňuje na to SK-CERT.', 'Pozrieť ukážku →', $a . '#sms-balik', 'seniori,mladi' ),
		array( 'k2', 'KARTA 2 · 1 MIN', 'Telefonát', 'yes', 'Volá „banka“ alebo „polícia“', '„Váš účet je napadnutý. Presuňte peniaze na bezpečný účet a nadiktujte mi kód.“', 'Strach a ponáhľanie; „Bezpečný účet“; Pýta kód, PIN, heslo; „Nikomu to nehovorte“', 'Zložiť; Zavolať na číslo z karty; Povedať rodine', 'Bezpečný účet neexistuje. Banka ani polícia nepresúvajú vaše peniaze.', 'Rýchla otázka: na displeji svieti číslo banky. Je to banka?', 'Nemusí. Číslo volajúceho sa dá sfalšovať. Zložte a zavolajte sami.', 'Pozrieť ukážku →', $a . '#falosny-bankar', 'seniori' ),
		array( 'k3', 'KARTA 3 · 1 MIN', 'WhatsApp', 'yes', '„Ahoj mami, mám nové číslo“', '„Ahoj mami, rozbil sa mi mobil. Pošleš mi dnes 640 €? Teraz nemôžem volať.“', 'Neznáme číslo; Súrne peniaze; Nechce volať', 'Zavolať na staré číslo; Opýtať sa rodinné heslo; Nič neposielať', 'Nové číslo + prosba o peniaze = najprv zavolaj na staré číslo.', 'Rýchla otázka: čo je rodinné heslo?', 'Slovo, ktoré poznáte len vy a vaši blízki. Podvodník ho nevie, aj keby napodobnil hlas umelou inteligenciou.', 'Pozrieť ukážku →', $a . '#ahoj-mami', 'seniori' ),
		array( 'k4', 'KARTA 4 · 1 MIN', 'E-mail', 'yes', 'Phishingový e-mail', '„Vážený klient, váš účet bude obmedzený do 12 hodín. Overte totožnosť.“', 'Čudná adresa odosielateľa; Neosobné oslovenie; Hrozba a termín; Tlačidlo na prihlásenie', 'Prihlásiť sa cez appku, nie odkaz; Označiť ako phishing', 'Do banky sa prihlasuj len cez aplikáciu alebo adresu, ktorú si napíšeš sám.', 'Rýchla otázka: aký častý je phishing?', 'Podľa agentúry EÚ ENISA začína phishingom asi 60 % kybernetických útokov.', 'Pozrieť ukážku →', $a . '#phishing-banka', 'seniori,mladi' ),
		array( 'k5', 'KARTA 5 · 1 MIN', 'Facebook', 'yes', 'Známa tvár radí investovať', 'Reklama: „Známy moderátor prezradil, ako zarába 3 000 € týždenne. Začnite s 250 €.“', 'Istý a vysoký zisk; Známa osobnosť; Cudzí web (.app, .tech); Vypnuté komentáre', 'Neregistrovať sa; Nahlásiť reklamu; Firmu overiť v zozname NBS', 'Video môže byť deepfake. Istý zisk bez rizika neexistuje.', 'Rýchla otázka: kto na to upozorňuje?', 'Národná banka Slovenska varuje pred deepfake videami slovenských politikov a hudobníkov v podvodných reklamách.', 'Pozrieť ukážku →', $a . '#investicie-deepfake', 'seniori,media' ),
		array( 'k6', 'KARTA 6 · 1 MIN', 'Bazár', 'yes', 'Predávam na bazári', '„Beriem bez zjednávania, pošlem kuriéra. Platbu si prevezmite cez tento odkaz.“', 'Nezjednáva, je v zahraničí; Kuriér od kupujúceho; Odkaz „na prijatie platby“', 'Dať len IBAN; Predávať osobne; Písať len cez platformu', 'Na prijatie peňazí nikdy netreba kartu ani kód zo SMS.', 'Rýchla otázka: čo stačí kupujúcemu poslať?', 'Iba číslo účtu (IBAN). Údaje z karty slúžia na platenie, nie na prijímanie peňazí.', 'Pozrieť ukážku →', $a . '#bazar', 'seniori,mladi' ),
		array( 'k7', 'KARTA 7 · 1 MIN', 'Web', 'no', 'Ako prečítať webovú adresu', 'https://posta-sk.<b>zasielka-overenie.top</b>/platba', 'Známe meno na začiatku; Zavináč @ v adrese; Veľa pomlčiek; Čísla namiesto mena', 'Nájdi prvú lomku /; Pozri, čo je tesne pred ňou; To je skutočný vlastník', 'Rozhoduje posledná časť pred prvou lomkou, nie začiatok adresy.', 'Rýchla otázka: komu patrí ib.banka.sk.login-overenie.com?', 'Doméne login-overenie.com, nie banke. „banka.sk“ je len pridaný začiatok.', 'Vyskúšať kontrolu adresy →', $u( 'overit-spravu' ) . '#adresa', 'seniori,mladi', 'Triky podvodníkov', 'Ako čítať' ),
		array( 'k8', 'KARTA 8 · 1 MIN', 'Overovanie', 'no', 'Overenie správy v 4 krokoch', 'Virálny príspevok: „TOTO VÁM V TELEVÍZII NEPOVEDIA! Zdieľaj, kým to zmažú!“', '<b>Zastav sa</b> pri emócii; <b>Over zdroj</b>: kto to píše?; <b>Hľadaj inde</b>: píšu o tom iní?; <b>Kontext</b>: odkiaľ je fotka?', 'Krik a výkričníky; „Médiá to tajia“; Anonymný expert', 'Keď ťa správa nahnevá alebo vystraší, najprv over, potom zdieľaj.', 'Rýchla otázka: ako zistím, odkiaľ je fotka?', 'Spätným vyhľadávaním obrázka, napríklad cez Google Lens alebo TinEye. Ukáže, kde sa fotka objavila prvýkrát.', 'Viac o overovaní →', $u( 'overit-spravu' ) . '#styri-kroky', 'mladi,media', 'Postup', 'Varovné znaky', 'good', 'bad' ),
		array( 'k9', 'KARTA 9 · 1 MIN', 'Dezinformácie', 'no', 'Omyl, lož, alebo zneužitie?', '', 'Nepravda bez zlého úmyslu; Napr. babka prepošle „zaručenú radu“', 'Nepravda šírená zámerne; Pre zisk alebo oklamanie ľudí', 'Nesúhlas nie je dezinformácia. Rozhoduje, či je to preukázateľne nepravdivé.', 'Rýchla otázka: koľko Európanov sa s dezinformáciami stretáva často?', 'Podľa Eurobarometra 2025 je to 36 % (za posledný týždeň „často“ alebo „veľmi často“).', 'Viac o dezinformáciách →', $u( 'dezinformacie' ) . '#pojmy', 'media,mladi', 'Misinformácia', 'Dezinformácia', 'plain', 'plain', 'Satira, novinárska chyba ani označený názor nie sú dezinformácia (Európska komisia).' ),
		array( 'k10', 'KARTA 10 · 1 MIN', 'AI', 'no', 'Spoznaj deepfake', 'Video politika, ktorý hovorí niečo šokujúce. Pery mierne nesedia so zvukom.', 'Pery a zuby; Okraje tváre, ruky; Hlas bez dýchania', 'Kto to zverejnil prvý?; Je to na oficiálnom účte?; Píšu o tom médiá?', 'Neveríme očiam, overujeme zdroj.', 'Rýchla otázka: musí byť deepfake označený?', 'Áno. Od 2. augusta 2026 to vyžaduje Akt EÚ o umelej inteligencii (čl. 50). Podvodníci to však nedodržiavajú.', 'Viac o deepfakes →', $u( 'dezinformacie' ) . '#deepfake', 'media,mladi,seniori', 'Na čo sa pozrieť' ),
		array( 'k11', 'KARTA 11 · 1 MIN', 'Pre mladých', 'yes', 'Vydieranie a „ľahká brigáda“', '', 'Neplať; Prestaň písať; Urob snímky obrazovky; Povedz to dospelému, volaj 116 111', 'Je to pranie peňazí; Trestne zodpovedáš ty; Účet ani kartu nepožičiavaj', 'Nie je to tvoja vina, ale je to tvoj účet. Pomoc existuje.', 'Rýchla otázka: kto varuje pred „peňažnými mulami“?', 'Europol. V akciách EMMA každoročne odhalí tisíce ľudí, ktorí cez svoj účet posielali špinavé peniaze. Veľa z nich sú mladí.', 'Sprievodca pre mladých →', $u( 'pre-mladych' ), 'mladi', 'Vydieranie fotkami', '„Prijmi a pošli ďalej“', 'bad', 'bad' ),
		array( 'k12', 'KARTA 12 · 1 MIN', 'Stalo sa', 'yes', 'Naletel som. Čo teraz?', '', '<b>Banka</b>: číslo na karte; Zablokovať kartu; Zmeniť heslá', '<b>Polícia 158</b>; Uložiť správy a výpisy; Nahlásiť stránku SK-CERT', 'Rozhodujú minúty. Najprv banka, potom polícia. Nehanbite sa.', 'Rýchla otázka: firma sľubuje vrátiť peniaze za poplatok. Áno?', 'Nie. „Vymáhači“ stratených peňazí za poplatok sú často ďalší podvodníci.', 'Postup krok za krokom →', $u( 'stalo-sa-mi-to' ), 'seniori,mladi', 'Hneď', 'Potom', 'good', 'good' ),
	);
	$items = array();
	foreach ( $rows as $r ) {
		$items[] = array(
			array(
				'anchor'       => $r[0],
				'number'       => $r[1],
				'tag'          => $r[2],
				'tag_red'      => $r[3],
				'title'        => $r[4],
				'looks'        => $r[5],
				'col1'         => $r[6],
				'col2'         => $r[7],
				'remember'     => $r[8],
				'question'     => $r[9],
				'answer'       => $r[10],
				'link_text'    => $r[11],
				'link'         => $r[12],
				'filter_group' => 'karty',
				'filter_tags'  => $r[13],
				'col1_title'   => isset( $r[14] ) ? $r[14] : 'Varovné znaky',
				'col2_title'   => isset( $r[15] ) ? $r[15] : 'Čo urobiť',
				'col1_style'   => isset( $r[16] ) ? $r[16] : 'bad',
				'col2_style'   => isset( $r[17] ) ? $r[17] : 'good',
				'note'         => isset( $r[18] ) ? $r[18] : '',
			),
		);
	}
	return mg_items( 'mg_lesson', $items );
}

/**
 * Poskladá obsah stránky zo sekcií (Avada kontajnery alebo záložné sekcie).
 */
function mg_demo_build( $sections, $avada ) {
	$out = '';
	foreach ( $sections as $s ) {
		$cols  = $s['cols'];
		$style = isset( $s['style'] ) ? $s['style'] : '';
		$id    = isset( $s['id'] ) ? $s['id'] : '';
		if ( $avada ) {
			$atts = array(
				'type'               => 'flex',
				'hundred_percent'    => 'no',
				'padding_top'        => '48px',
				'padding_bottom'     => '48px',
				'background_color'   => 'alt' === $style ? '#e7ecf6' : '',
				'id'                 => $id,
				'admin_label'        => $id,
			);
			$out .= mg_sc( 'fusion_builder_container', $atts ) . '[fusion_builder_row]';
			$type = 1 === count( $cols ) ? '1_1' : '1_2';
			foreach ( $cols as $c ) {
				$out .= mg_sc( 'fusion_builder_column', array( 'type' => $type, 'layout' => $type, 'spacing' => '4%' ), $c );
			}
			$out .= '[/fusion_builder_row][/fusion_builder_container]';
		} else {
			$inner = 1 === count( $cols ) ? $cols[0] : '[mg_cols count="' . count( $cols ) . '"][mg_col]' . implode( '[/mg_col][mg_col]', $cols ) . '[/mg_col][/mg_cols]';
			$out  .= mg_sc( 'mg_section', array( 'style' => $style, 'id' => $id ), $inner );
		}
		$out .= "\n";
	}
	return $out;
}

/**
 * Vytvorí alebo aktualizuje ukážkové stránky ako koncepty.
 */
function mg_import_demo_pages() {
	$avada   = mg_has_avada();
	$pages   = mg_demo_pages();
	$created = get_option( 'mg_demo_pages', array() );
	$created = is_array( $created ) ? $created : array();

	// 1. Zabezpečiť, že každá stránka existuje (kvôli odkazom).
	foreach ( $pages as $slug => $p ) {
		if ( empty( $created[ $slug ] ) || ! get_post( $created[ $slug ] ) ) {
			$created[ $slug ] = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'draft',
					'post_title'  => $p['title'],
					'post_name'   => $slug,
				)
			);
		}
	}

	// 2. Obsah s odkazmi na skutočné adresy.
	kses_remove_filters();
	foreach ( $pages as $slug => $p ) {
		$content = mg_demo_build( $p['sections'], $avada );
		$content = preg_replace_callback(
			'/\{\{url:([a-z0-9-]+)\}\}/',
			function ( $m ) use ( $created ) {
				return isset( $created[ $m[1] ] ) ? get_permalink( $created[ $m[1] ] ) : '#';
			},
			$content
		);
		$content = str_replace( '{{pdf}}', MG_URL . 'assets/pdf/rychle-karty.pdf', $content );
		wp_update_post(
			array(
				'ID'           => $created[ $slug ],
				'post_content' => $content,
			)
		);
		if ( $avada ) {
			update_post_meta( $created[ $slug ], 'fusion_builder_status', 'active' );
		}
	}
	kses_init();

	update_option( 'mg_demo_pages', $created, false );
	return $created;
}
