<?php
/**
 * Definícia všetkých prvkov pluginu.
 *
 * Z tohto jedného zoznamu sa generujú:
 *  - WordPress shortcody (s predvolenými hodnotami),
 *  - prvky v Avada Builderi (fusion_builder_map),
 *  - dokumentácia v Nastavenia → Mediálna gramotnosť.
 *
 * Typy polí: text, textarea, select, yesno, link, checkboxes, number.
 * Obsah prvku (medzi [tag] a [/tag]) opisuje kľúč 'content'.
 *
 * Syntax riadkov v ukážkach správ:
 *   ==text==          zvýrazní varovný znak (očíslovaný)
 *   > text            vlastná správa (zelená/modrá bublina vpravo)
 *   ! text            systémové upozornenie (WhatsApp)
 *   btn: text         tlačidlo v e-maile alebo na webe
 *   img: text         obrázok/video (popis)
 *   link: doména | nadpis   náhľad odkazu na Facebooku
 *   stats: vľavo | vpravo   počty reakcií pod príspevkom
 *   foot: text        pätička e-mailu
 *   title: text       veľký nadpis na webovej stránke
 *   input: text       vstupné pole na webovej stránke
 *   alert: text       blok vyskakovacieho okna (web)
 *   Meno: text        riadok v prepise hovoru (typ „Telefonát“)
 *   ---               oddelí správu od vysvetliviek varovných znakov
 *
 * @package MedialnaGramotnost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Spoločné polia pre ukážku správy (používa prvok Ukážka aj položka trenažéra).
 */
function mg_mock_params() {
	return array(
		array( 'type', 'select', 'Typ ukážky', 'sms', array(
			'sms'      => 'SMS',
			'whatsapp' => 'WhatsApp / Messenger',
			'email'    => 'E-mail',
			'facebook' => 'Facebook príspevok',
			'web'      => 'Webová stránka',
			'call'     => 'Telefonát (prepis)',
		) ),
		array( 'sender', 'text', 'Odosielateľ / názov', '+421 9xx xxx xxx', null, 'Číslo, meno, stránka alebo volajúci.' ),
		array( 'subtitle', 'text', 'Podnadpis', '', null, 'SMS: „Textová správa“, Facebook: „Sponzorované · 🌐“, chat: „online“.' ),
		array( 'avatar', 'text', 'Písmeno v avatare', '', null, 'Jeden alebo dva znaky. Prázdne = prvé písmeno odosielateľa.' ),
		array( 'avatar_color', 'text', 'Farba avatara', '', null, 'Napr. #b91c1c. Prázdne = predvolená.' ),
		array( 'time', 'text', 'Čas správy (SMS)', '', null, 'Napr. „Dnes 09:41“.' ),
		array( 'subject', 'text', 'Predmet e-mailu', '', null, 'Len pre e-mail. Môžete použiť ==zvýraznenie==.' ),
		array( 'address', 'text', 'E-mailová adresa odosielateľa', '', null, 'Len pre e-mail. Napr. ==noreply@podvod.com==' ),
		array( 'url', 'text', 'Adresa stránky', '', null, 'Len pre web. Napr. ==microsoft-alert.online==/sk' ),
	);
}

/**
 * Zoznam prvkov.
 */
function mg_elements() {
	static $els = null;
	if ( null !== $els ) {
		return $els;
	}

	$filter_tags = array(
		'sms'      => 'SMS',
		'email'    => 'E-mail',
		'telefon'  => 'Telefonát',
		'socialne' => 'Sociálne siete',
		'chat'     => 'Chat',
		'web'      => 'Web',
		'seniori'  => 'Seniori',
		'mladi'    => 'Mladí',
		'media'    => 'Médiá',
	);
	$icons = array(
		'message' => 'Správa',
		'doc'     => 'Dokument',
		'search'  => 'Lupa',
		'alert'   => 'Výstraha',
		'phone'   => 'Telefón',
		'shield'  => 'Štít',
		'users'   => 'Ľudia',
		'monitor' => 'Monitor',
		'book'    => 'Kniha',
		'none'    => 'Bez ikony',
	);

	$els = array(

		/* ---------- Úvod ---------- */
		'mg_hero' => array(
			'name'   => 'MG Úvod stránky',
			'desc'   => 'Veľký nadpis so zvýraznenou časťou, úvodným textom a dvoma tlačidlami.',
			'icon'   => 'fusiona-header',
			'params' => array(
				array( 'size', 'select', 'Veľkosť', 'large', array( 'large' => 'Domovská stránka', 'page' => 'Podstránka' ) ),
				array( 'crumbs', 'text', 'Drobečková navigácia', '', null, 'Napr. „Domov / Atlas podvodov“. Prázdne = nezobrazí sa.' ),
				array( 'eyebrow', 'text', 'Nadtitulok', 'Podvody · dezinformácie · overovanie' ),
				array( 'title', 'text', 'Nadpis', 'Neklikni hneď.' ),
				array( 'highlight', 'text', 'Zvýraznená časť nadpisu', 'Over si to.' ),
				array( 'lead', 'textarea', 'Úvodný text', 'Ukážeme vám na skutočných typoch správ, ako vyzerá podvodná SMS, e-mail či falošný telefonát.' ),
				array( 'button1_text', 'text', 'Tlačidlo 1 – text', 'Vyskúšať trenažér' ),
				array( 'button1_link', 'link', 'Tlačidlo 1 – odkaz', '#' ),
				array( 'button2_text', 'text', 'Tlačidlo 2 – text', '' ),
				array( 'button2_link', 'link', 'Tlačidlo 2 – odkaz', '' ),
			),
		),

		'mg_heading' => array(
			'name'   => 'MG Nadpis sekcie',
			'desc'   => 'Nadtitulok, nadpis a krátky popis vedľa neho.',
			'icon'   => 'fusiona-font',
			'params' => array(
				array( 'eyebrow', 'text', 'Nadtitulok', '' ),
				array( 'title', 'text', 'Nadpis', 'Nadpis sekcie' ),
				array( 'text', 'textarea', 'Popis vpravo', '' ),
				array( 'button_text', 'text', 'Tlačidlo – text', '' ),
				array( 'button_link', 'link', 'Tlačidlo – odkaz', '' ),
				array( 'tone', 'select', 'Farba textu', 'normal', array( 'normal' => 'Tmavý text', 'light' => 'Svetlý text (na tmavom pozadí)' ) ),
			),
		),

		/* ---------- Ukážky ---------- */
		'mg_example' => array(
			'name'    => 'MG Ukážka správy',
			'desc'    => 'Verná maketa SMS, e-mailu, Facebooku, WhatsAppu, webu alebo hovoru s tlačidlom „Ukázať varovné znaky“.',
			'icon'    => 'fusiona-mobile',
			'params'  => array_merge(
				array(
					array( 'label', 'text', 'Popis nad ukážkou', 'Ukážka · SMS' ),
					array( 'button_text', 'text', 'Text tlačidla', 'Ukázať varovné znaky' ),
					array( 'button_text_on', 'text', 'Text tlačidla po zapnutí', 'Skryť varovné znaky' ),
					array( 'open', 'yesno', 'Zobraziť znaky hneď', 'no' ),
				),
				mg_mock_params()
			),
			'content' => array( 'textarea', 'Správa a vysvetlivky', "Slovenska posta: Vas balik caka. ==Doplatte 1,20 EUR== do 24 hodin: ==posta-sk.balik-info.top==\n---\nDrobný poplatok je návnada. Cieľom sú údaje z karty.\nOdkaz nevedie na stránku pošty.", 'Každý riadok = jedna bublina alebo odsek. ==text== = varovný znak. Pod riadkom --- napíšte vysvetlivky, jednu na riadok, v rovnakom poradí ako ==zvýraznenia==. Ďalšie skratky: „> “ vlastná správa, „btn:“ tlačidlo, „img:“ obrázok, „link: doména | nadpis“, „Meno: text“ pri hovore.' ),
		),

		'mg_scam' => array(
			'name'    => 'MG Popis podvodu',
			'desc'    => 'Textová časť karty podvodu: štítky, nadpis, popis, varovné znaky, čo robiť a zdroj. Vedľa neho vložte „MG Ukážka správy“.',
			'icon'    => 'fusiona-exclamation-triangle',
			'params'  => array(
				array( 'labels', 'text', 'Štítky', 'SMS; Smishing', null, 'Oddeľte bodkočiarkou. Prvý štítok je červený.' ),
				array( 'title', 'text', 'Nadpis', 'Názov podvodu' ),
				array( 'source', 'text', 'Zdroj', '' ),
				array( 'filter_group', 'text', 'Skupina filtra', 'atlas', null, 'Rovnaký názov ako v prvku „MG Filter“.' ),
				array( 'filter_tags', 'checkboxes', 'Kategórie pre filter', 'sms', $filter_tags ),
				array( 'hide_scope', 'select', 'Pri filtrovaní skryť', 'container', array( 'container' => 'Celý kontajner Avada', 'element' => 'Len tento prvok' ) ),
			),
			'content' => array( 'tinymce', 'Popis', '<p>Ako podvod funguje.</p><h3>Varovné znaky</h3><ul><li>…</li></ul><h3>Čo robiť</h3><ul><li>…</li></ul>' ),
		),

		'mg_filter' => array(
			'name'   => 'MG Filter',
			'desc'   => 'Tlačidlá, ktoré na stránke filtrujú „MG Popis podvodu“ alebo „MG Rýchla karta“ podľa kategórie.',
			'icon'   => 'fusiona-filter',
			'params' => array(
				array( 'group', 'text', 'Skupina', 'atlas', null, 'Filtruje prvky s rovnakou skupinou.' ),
				array( 'label', 'text', 'Popis', 'Kanál:' ),
				array( 'options', 'textarea', 'Možnosti', 'all|Všetko; sms|SMS; email|E-mail; telefon|Telefonát; socialne|Sociálne siete; chat|Chat; web|Web; seniori|Seniorov; mladi|Mladých', null, 'kľúč|Text; oddelené bodkočiarkou. Kľúč „all“ zobrazí všetko.' ),
				array( 'empty_text', 'text', 'Text, keď nič nevyhovuje', 'Pre tento filter nemáme ukážku.' ),
			),
		),

		/* ---------- Karty a zoznamy ---------- */
		'mg_cards' => array(
			'name'   => 'MG Karty s odkazom',
			'desc'   => 'Mriežka kariet (napr. rozcestník „S čím vám pomôžeme?“).',
			'icon'   => 'fusiona-th',
			'child'  => 'mg_card',
			'params' => array(
				array( 'columns', 'select', 'Počet stĺpcov', '4', array( '2' => '2', '3' => '3', '4' => '4' ) ),
			),
		),
		'mg_card' => array(
			'name'    => 'Karta',
			'params'  => array(
				array( 'eyebrow', 'text', 'Nadtitulok', '' ),
				array( 'title', 'text', 'Nadpis', 'Nadpis karty' ),
				array( 'icon', 'select', 'Ikona', 'message', $icons ),
				array( 'style', 'select', 'Štýl', 'normal', array( 'normal' => 'Bežná', 'danger' => 'Červená (pomoc)', 'remember' => 'So žltou vetou' ) ),
				array( 'remember', 'text', 'Žltá veta', '' ),
				array( 'link_text', 'text', 'Text odkazu', 'Viac →' ),
				array( 'link', 'link', 'Odkaz', '' ),
			),
			'content' => array( 'textarea', 'Text', 'Krátky popis.' ),
		),

		'mg_steps' => array(
			'name'   => 'MG Kroky / pravidlá',
			'desc'   => 'Očíslované kroky alebo pravidlá v stĺpcoch na tmavom pozadí.',
			'icon'   => 'fusiona-list-ol',
			'child'  => 'mg_step',
			'params' => array(
				array( 'layout', 'select', 'Rozloženie', 'list', array( 'list' => 'Očíslovaný zoznam', 'red' => 'Očíslovaný zoznam (červený)', 'columns' => 'Stĺpce na tmavom pozadí' ) ),
			),
		),
		'mg_step' => array(
			'name'    => 'Krok',
			'params'  => array(
				array( 'label', 'text', 'Označenie (len stĺpce)', '' ),
				array( 'title', 'text', 'Nadpis', 'Zastav sa' ),
			),
			'content' => array( 'textarea', 'Text', 'Popis kroku.' ),
		),

		'mg_warnings' => array(
			'name'   => 'MG Zoznam varovaní',
			'desc'   => 'Riadky s aktuálnymi podvodmi a odkazom na ukážku.',
			'icon'   => 'fusiona-bell',
			'child'  => 'mg_warning',
			'params' => array(),
		),
		'mg_warning' => array(
			'name'    => 'Varovanie',
			'params'  => array(
				array( 'tag', 'text', 'Štítok', 'SMS' ),
				array( 'title', 'text', 'Nadpis', 'Falošná Slovenská pošta' ),
				array( 'link_text', 'text', 'Text odkazu', 'Ukážka →' ),
				array( 'link', 'link', 'Odkaz', '' ),
			),
			'content' => array( 'textarea', 'Text', 'Krátky popis a zdroj varovania.' ),
		),

		'mg_stats' => array(
			'name'   => 'MG Čísla',
			'desc'   => 'Veľké čísla s popisom a zdrojom.',
			'icon'   => 'fusiona-bar-chart',
			'child'  => 'mg_stat',
			'params' => array(),
		),
		'mg_stat' => array(
			'name'    => 'Číslo',
			'params'  => array(
				array( 'number', 'text', 'Číslo', '60 %' ),
				array( 'source', 'text', 'Zdroj', 'ENISA Threat Landscape 2025' ),
			),
			'content' => array( 'textarea', 'Popis', 'kybernetických útokov v EÚ začína phishingom.' ),
		),

		'mg_callout' => array(
			'name'    => 'MG Upozornenie',
			'desc'    => 'Farebný rámček s textom (informácia, varovanie, nebezpečenstvo, v poriadku).',
			'icon'    => 'fusiona-info-circle',
			'params'  => array(
				array( 'style', 'select', 'Štýl', 'info', array( 'info' => 'Informácia', 'warn' => 'Varovanie (žltá)', 'danger' => 'Nebezpečenstvo (červená)', 'ok' => 'V poriadku (zelená)' ) ),
				array( 'title', 'text', 'Nadpis', '' ),
				array( 'size', 'select', 'Veľkosť textu', 'normal', array( 'normal' => 'Bežná', 'large' => 'Veľká' ) ),
			),
			'content' => array( 'tinymce', 'Text', '<p>Text upozornenia.</p>' ),
		),

		'mg_cta' => array(
			'name'   => 'MG Výzva s tlačidlom',
			'desc'   => 'Pás s nadpisom, textom a tlačidlom.',
			'icon'   => 'fusiona-link',
			'params' => array(
				array( 'title', 'text', 'Nadpis', 'Spoznáte podvod? Vyskúšajte si to.' ),
				array( 'text', 'textarea', 'Text', '12 ukážok správ. Po každej uvidíte vysvetlenie.' ),
				array( 'button_text', 'text', 'Tlačidlo – text', 'Spustiť trenažér' ),
				array( 'button_link', 'link', 'Tlačidlo – odkaz', '' ),
			),
		),

		/* ---------- Interaktívne nástroje ---------- */
		'mg_trainer' => array(
			'name'   => 'MG Trenažér',
			'desc'   => 'Postupne zobrazuje ukážky; návštevník rozhoduje „Podvod“ alebo „Je v poriadku“.',
			'icon'   => 'fusiona-check-square-o',
			'child'  => 'mg_trainer_item',
			'params' => array(
				array( 'question', 'text', 'Otázka', 'Je táto správa podvod?' ),
				array( 'hint', 'text', 'Pomôcka pod otázkou', 'Pozorne si ju prečítajte. Všímajte si odosielateľa, odkaz a čo od vás chce.' ),
				array( 'scam_label', 'text', 'Tlačidlo „podvod“', 'Podvod' ),
				array( 'safe_label', 'text', 'Tlačidlo „v poriadku“', 'Je v poriadku' ),
				array( 'result_high', 'text', 'Výsledok 90 % a viac', 'Výborne. Podvodníci to s vami budú mať ťažké. Pošlite trenažér rodine.' ),
				array( 'result_mid', 'text', 'Výsledok 60–89 %', 'Dobrý základ. Prejdite si typy podvodov, pri ktorých ste sa pomýlili.' ),
				array( 'result_low', 'text', 'Výsledok pod 60 %', 'Nevadí, presne na to je trenažér. Pozrite si ukážky podvodov a skúste to znova.' ),
				array( 'summary', 'textarea', 'Čo si zapamätať (na konci)', 'Nátlak a strach sú hlavné nástroje podvodníkov.; Kód zo SMS, PIN ani heslo nikomu nedávajte.; Na prijatie peňazí nikdy netreba údaje z karty.; Overujte inou cestou: zavolajte na známe číslo.', null, 'Body oddeľte bodkočiarkou.' ),
				array( 'more_text', 'text', 'Odkaz na konci – text', 'Atlas podvodov' ),
				array( 'more_link', 'link', 'Odkaz na konci', '' ),
			),
		),
		'mg_trainer_item' => array(
			'name'    => 'Ukážka v trenažéri',
			'params'  => array_merge(
				array(
					array( 'verdict', 'select', 'Správna odpoveď', 'scam', array( 'scam' => 'Podvod', 'safe' => 'Je v poriadku' ) ),
					array( 'kind', 'text', 'Označenie kanála', 'SMS' ),
					array( 'situation', 'text', 'Situácia (nepovinné)', '', null, 'Napr. „Práve sa prihlasujete do e-mailu.“' ),
				),
				mg_mock_params()
			),
			'content' => array( 'textarea', 'Správa a vysvetlivky', "Text správy s ==varovným znakom==\n---\nVysvetlenie varovného znaku." ),
		),

		'mg_checker' => array(
			'name'   => 'MG Kontrola správy',
			'desc'   => 'Otázky Áno/Nie s ukazovateľom rizika a odporúčaním.',
			'icon'   => 'fusiona-tachometer',
			'child'  => 'mg_checker_question',
			'params' => array(
				array( 'result_title', 'text', 'Nadpis výsledku', 'Výsledok' ),
				array( 'empty_text', 'text', 'Text pred odpoveďami', 'Odpovedzte na otázky a výsledok sa ukáže tu.' ),
				array( 'high_title', 'text', 'Vysoké riziko – nadpis', 'Takmer určite ide o podvod.' ),
				array( 'high_text', 'textarea', 'Vysoké riziko – text', 'Na nič neklikajte, neodpisujte a nič neplaťte. Ak ste už niečo zadali, okamžite volajte banke.' ),
				array( 'mid_title', 'text', 'Stredné riziko – nadpis', 'Niečo tu nesedí.' ),
				array( 'mid_text', 'textarea', 'Stredné riziko – text', 'Overte si správu inou cestou: zavolajte na číslo, ktoré poznáte, alebo sa prihláste cez aplikáciu.' ),
				array( 'low_title', 'text', 'Nízke riziko – nadpis', 'Zatiaľ nevidíme typické znaky podvodu.' ),
				array( 'low_text', 'textarea', 'Nízke riziko – text', 'Aj tak buďte opatrní. Ak si nie ste istí, overte si to u odosielateľa známou cestou.' ),
				array( 'reset_text', 'text', 'Tlačidlo „odznova“', 'Začať odznova' ),
				array( 'note', 'text', 'Poznámka pod výsledkom', 'Nástroj vám pomôže rozmýšľať, ale nedá istotu.' ),
			),
		),
		'mg_checker_question' => array(
			'name'   => 'Otázka',
			'params' => array(
				array( 'question', 'text', 'Otázka', 'Tlačí vás na rýchlu akciu?' ),
				array( 'weight', 'number', 'Váha odpovede Áno (1–5)', '3', array( 'min' => 1, 'max' => 5 ) ),
				array( 'hard', 'yesno', 'Áno = vždy vysoké riziko', 'no' ),
			),
		),

		'mg_url_checker' => array(
			'name'   => 'MG Kontrola webovej adresy',
			'desc'   => 'Rozoberie adresu a ukáže skutočného vlastníka stránky.',
			'icon'   => 'fusiona-globe',
			'params' => array(
				array( 'input_label', 'text', 'Popis poľa', 'Webová adresa' ),
				array( 'default_url', 'text', 'Predvyplnená adresa', 'https://posta-sk.zasielka-overenie.top/platba' ),
				array( 'examples', 'textarea', 'Príklady na vyskúšanie', 'posta.sk|https://www.posta.sk/sledovanie-zasielok; banka.sk.login-overenie.com|https://ib.banka.sk.login-overenie.com/sk; trik so zavináčom|https://www.facebook.com@fb-video.watch-now.click/v/77; IP adresa|http://185.23.11.4/ucet/overenie', null, 'Text|adresa; oddelené bodkočiarkou.' ),
				array( 'tip', 'textarea', 'Vysvetlenie pod výsledkom', 'Rozhoduje posledná časť pred prvou lomkou. Všetko pred ňou si môže podvodník napísať, ako chce.' ),
			),
		),

		'mg_help_tree' => array(
			'name'   => 'MG Čo robiť (rozhodovací strom)',
			'desc'   => 'Návštevník vyberie situáciu a zobrazí sa postup krok za krokom.',
			'icon'   => 'fusiona-sitemap',
			'child'  => 'mg_help_option',
			'params' => array(),
		),
		'mg_help_option' => array(
			'name'    => 'Situácia',
			'params'  => array(
				array( 'title', 'text', 'Situácia', 'Zadal som údaje z karty' ),
				array( 'subtitle', 'text', 'Upresnenie', 'na stránke z SMS či e-mailu' ),
				array( 'heading', 'text', 'Nadpis postupu', 'Zadali ste údaje z karty' ),
				array( 'style', 'select', 'Farba krokov', 'red', array( 'red' => 'Červená (súrne)', 'normal' => 'Tmavá' ) ),
				array( 'button_text', 'text', 'Tlačidlo pod postupom', '' ),
				array( 'button_link', 'link', 'Odkaz tlačidla', '' ),
			),
			'content' => array( 'textarea', 'Kroky', "Hneď volajte banke | Číslo je na zadnej strane karty.\nNahláste to polícii | Na čísle 158.", 'Jeden krok na riadok: Nadpis | text.' ),
		),

		'mg_contacts' => array(
			'name'   => 'MG Kontakty',
			'desc'   => 'Dlaždice s telefónnymi číslami a kontaktmi.',
			'icon'   => 'fusiona-phone',
			'child'  => 'mg_contact',
			'params' => array(),
		),
		'mg_contact' => array(
			'name'    => 'Kontakt',
			'params'  => array(
				array( 'label', 'text', 'Názov', 'Polícia SR' ),
				array( 'number', 'text', 'Číslo / kontakt', '158' ),
				array( 'small', 'yesno', 'Menšie písmo (dlhý text)', 'no' ),
			),
			'content' => array( 'textarea', 'Popis', 'Nahlásenie podvodu.' ),
		),

		'mg_quiz' => array(
			'name'   => 'MG Kvíz',
			'desc'   => 'Otázky s výberom odpovede a vysvetlením.',
			'icon'   => 'fusiona-question-circle',
			'child'  => 'mg_quiz_question',
			'params' => array(
				array( 'prompt', 'text', 'Otázka pod textom', 'Akú techniku manipulácie použil autor?' ),
				array( 'item_label', 'text', 'Označenie položky', 'Príspevok' ),
				array( 'next_text', 'text', 'Tlačidlo ďalej', 'Ďalší príspevok' ),
				array( 'result_text', 'text', 'Text pri výsledku', 'Kto pozná techniky manipulácie, ľahšie ich odhalí.' ),
			),
		),
		'mg_quiz_question' => array(
			'name'    => 'Otázka kvízu',
			'params'  => array(
				array( 'answers', 'text', 'Odpovede', 'Falošná dilema; Falošný expert; Obetný baránok', null, 'Oddeľte bodkočiarkou.' ),
				array( 'correct', 'number', 'Správna odpoveď (poradie)', '1', array( 'min' => 1, 'max' => 6 ) ),
				array( 'explanation', 'textarea', 'Vysvetlenie', '' ),
			),
			'content' => array( 'textarea', 'Text príspevku / otázka', 'Buď podporíš náš protest, alebo ti je jedno, čo bude s tvojimi deťmi.' ),
		),

		'mg_glossary' => array(
			'name'   => 'MG Slovník',
			'desc'   => 'Rozbaľovacie pojmy s vyhľadávaním.',
			'icon'   => 'fusiona-book',
			'child'  => 'mg_term',
			'params' => array(
				array( 'search', 'yesno', 'Zobraziť vyhľadávanie', 'yes' ),
				array( 'search_label', 'text', 'Popis vyhľadávania', 'Hľadať pojem' ),
				array( 'placeholder', 'text', 'Ukážkový text v poli', 'napr. phishing' ),
				array( 'empty_text', 'text', 'Text, keď nič nenájde', 'Tento pojem v slovníku zatiaľ nemáme.' ),
			),
		),
		'mg_term' => array(
			'name'    => 'Pojem',
			'params'  => array(
				array( 'term', 'text', 'Pojem', 'Phishing' ),
			),
			'content' => array( 'textarea', 'Vysvetlenie', 'Podvodná správa, ktorá sa vydáva za dôveryhodnú firmu.' ),
		),

		/* ---------- Rýchle materiály ---------- */
		'mg_lessons' => array(
			'name'   => 'MG Rýchle karty',
			'desc'   => 'Krátke vzdelávacie karty „pochopte to za minútu“.',
			'icon'   => 'fusiona-clone',
			'child'  => 'mg_lesson',
			'params' => array(
				array( 'columns', 'select', 'Počet stĺpcov', '2', array( '1' => '1', '2' => '2' ) ),
				array( 'print_button', 'text', 'Tlačidlo tlače', 'Vytlačiť karty', null, 'Prázdne = bez tlačidla.' ),
				array( 'looks_label', 'text', 'Označenie príkladu', 'Ako to vyzerá:' ),
				array( 'remember_label', 'text', 'Označenie žltej vety', 'Zapamätaj si:' ),
			),
		),
		'mg_lesson' => array(
			'name'   => 'Rýchla karta',
			'params' => array(
				array( 'number', 'text', 'Označenie', 'KARTA 1 · 1 MIN' ),
				array( 'tag', 'text', 'Štítok', 'SMS' ),
				array( 'tag_red', 'yesno', 'Červený štítok', 'yes' ),
				array( 'title', 'text', 'Nadpis', 'SMS o balíku' ),
				array( 'looks', 'textarea', 'Ako to vyzerá', '' ),
				array( 'col1_title', 'text', 'Stĺpec 1 – nadpis', 'Varovné znaky' ),
				array( 'col1_style', 'select', 'Stĺpec 1 – farba', 'bad', array( 'bad' => 'Červená', 'good' => 'Zelená', 'plain' => 'Bez farby' ) ),
				array( 'col1', 'textarea', 'Stĺpec 1 – body', 'Nečakáte balík; Malý doplatok', null, 'Oddeľte bodkočiarkou.' ),
				array( 'col2_title', 'text', 'Stĺpec 2 – nadpis', 'Čo urobiť' ),
				array( 'col2_style', 'select', 'Stĺpec 2 – farba', 'good', array( 'bad' => 'Červená', 'good' => 'Zelená', 'plain' => 'Bez farby' ) ),
				array( 'col2', 'textarea', 'Stĺpec 2 – body', 'Neklikať; Volať banke', null, 'Oddeľte bodkočiarkou.' ),
				array( 'note', 'textarea', 'Poznámka pod stĺpcami', '' ),
				array( 'remember', 'text', 'Zapamätaj si', '' ),
				array( 'question', 'text', 'Rýchla otázka', '' ),
				array( 'answer', 'textarea', 'Odpoveď', '' ),
				array( 'link_text', 'text', 'Text odkazu', '' ),
				array( 'link', 'link', 'Odkaz', '' ),
				array( 'filter_group', 'text', 'Skupina filtra', 'karty' ),
				array( 'filter_tags', 'checkboxes', 'Kategórie pre filter', 'seniori', $filter_tags ),
				array( 'anchor', 'text', 'Kotva (id)', '', null, 'Napr. k1, aby sa dalo odkazovať na #k1.' ),
			),
		),

		'mg_wallet' => array(
			'name'   => 'MG Kartička do peňaženky',
			'desc'   => 'Vystrihovacia kartička s pravidlami a číslami.',
			'icon'   => 'fusiona-credit-card',
			'params' => array(
				array( 'title', 'text', 'Nadpis', 'Keď volá „banka“, „polícia“ alebo „vnuk“' ),
				array( 'rules', 'textarea', 'Pravidlá', '<b>Zložím</b> a zavolám sám na známe číslo.; <b>Kódy, PIN a heslá</b> nikomu nediktujem.; <b>Peniaze</b> nikam nepresúvam a nikomu nedávam.', null, 'Oddeľte bodkočiarkou. Povolené je &lt;b&gt;.' ),
				array( 'numbers', 'textarea', 'Čísla', '158|Polícia; 112|Tiesňová linka; 0850 111 321|Pomoc obetiam', null, 'Číslo|popis; oddelené bodkočiarkou.' ),
				array( 'fields', 'textarea', 'Riadky na dopísanie', 'Moja banka (číslo zo zadnej strany karty):; Moja rodina:', null, 'Oddeľte bodkočiarkou.' ),
				array( 'note', 'text', 'Poznámka', 'Naše rodinné heslo poznám naspamäť, nepíšem ho sem.' ),
			),
		),

		/* ---------- Ďalšie ---------- */
		'mg_sources' => array(
			'name'   => 'MG Zoznam zdrojov',
			'desc'   => 'Zoznam zdrojov s popisom a odkazom.',
			'icon'   => 'fusiona-list',
			'child'  => 'mg_source',
			'params' => array(),
		),
		'mg_source' => array(
			'name'    => 'Zdroj',
			'params'  => array(
				array( 'title', 'text', 'Názov', 'ENISA Threat Landscape 2025' ),
				array( 'link_text', 'text', 'Text odkazu', 'enisa.europa.eu' ),
				array( 'link', 'link', 'Odkaz', '' ),
			),
			'content' => array( 'textarea', 'Čo sme z neho použili', '' ),
		),

		'mg_alert_bar' => array(
			'name'   => 'MG Lišta pomoci',
			'desc'   => 'Tmavá lišta „Prišli ste o peniaze?“. Dá sa zapnúť aj na celom webe v nastaveniach.',
			'icon'   => 'fusiona-exclamation',
			'params' => array(
				array( 'text', 'text', 'Text', 'Prišli ste o peniaze alebo údaje? <b>Hneď volajte svojej banke</b> a polícii na <b>158</b>.' ),
				array( 'link_text', 'text', 'Text odkazu', 'Čo robiť krok za krokom →' ),
				array( 'link', 'link', 'Odkaz', '' ),
			),
		),

		'mg_a11y' => array(
			'name'   => 'MG Väčšie písmo',
			'desc'   => 'Tlačidlo A+, ktoré zväčší písmo prvkov pluginu (pre seniorov). Dá sa zapnúť aj ako plávajúce tlačidlo na celom webe.',
			'icon'   => 'fusiona-text-height',
			'params' => array(
				array( 'label', 'text', 'Popis pred tlačidlom', '' ),
			),
		),
	);

	/**
	 * Filter na pridanie alebo úpravu prvkov z témy.
	 */
	$els = apply_filters( 'mg_elements', $els );
	return $els;
}

/**
 * Mapa „rodič → potomok“ a naopak.
 */
function mg_parent_of( $child_tag ) {
	foreach ( mg_elements() as $tag => $el ) {
		if ( isset( $el['child'] ) && $el['child'] === $child_tag ) {
			return $tag;
		}
	}
	return null;
}
