<?php
/**
 * Editovateľné polia (meta boxy) pre príklady, karty a testy.
 *
 * Polia sú opísané v mgt_fields(); vykreslenie aj uloženie je spoločné.
 * Otázky testov sa ukladajú ako zoznam (JSON) a v administrácii sa dajú
 * pridávať, mazať a presúvať.
 *
 * @package MedialnaGramotnostTema
 */

defined( 'ABSPATH' ) || exit;

/**
 * Typy ukážok správ.
 */
function mgt_mock_types() {
	return array(
		'sms'      => 'SMS',
		'whatsapp' => 'WhatsApp / Messenger / chat',
		'email'    => 'E-mail',
		'facebook' => 'Facebook príspevok',
		'web'      => 'Webová stránka',
		'call'     => 'Telefonát (prepis)',
	);
}

/**
 * Polia ukážky správy (spoločné pre príklad aj otázku trenažéra).
 */
function mgt_mock_fields() {
	return array(
		'type'         => array( 'select', 'Typ ukážky', 'sms', mgt_mock_types() ),
		'sender'       => array( 'text', 'Odosielateľ / volajúci / stránka', '', null, 'Napr. +421 9xx xxx xxx, Moja banka, Ekonomické Správy Dnes.' ),
		'subtitle'     => array( 'text', 'Podnadpis', '', null, 'SMS: „Textová správa“, Facebook: „Sponzorované · 🌐“, chat: „online“, hovor: číslo a čas.' ),
		'avatar'       => array( 'text', 'Písmeno v avatare', '', null, 'Prázdne = prvé písmeno odosielateľa.' ),
		'avatar_color' => array( 'text', 'Farba avatara', '', null, 'Napr. #b91c1c' ),
		'time'         => array( 'text', 'Čas (SMS)', '', null, 'Napr. Dnes 09:41' ),
		'subject'      => array( 'text', 'Predmet (e-mail)', '' ),
		'address'      => array( 'text', 'Adresa odosielateľa (e-mail)', '' ),
		'url'          => array( 'text', 'Adresa stránky (web)', '' ),
		'message'      => array( 'code', 'Text správy', '', null, 'Každý riadok = bublina alebo odsek. ==text== označí varovný znak. Skratky: „> text“ vaša odpoveď, „! text“ systémové upozornenie, „btn: text“ tlačidlo, „img: popis | #farba“ obrázok, „link: doména | nadpis“, „stats: vľavo | vpravo“, „foot: text“, „title:“, „input:“, „alert:“ (web), „Meno: text“ (hovor).' ),
		'notes'        => array( 'code', 'Vysvetlenia varovných znakov', '', null, 'Jedno vysvetlenie na riadok, v rovnakom poradí ako ==zvýraznenia== (vrátane zvýraznení v odosielateľovi, predmete či adrese).' ),
	);
}

/**
 * Definícia polí podľa typu obsahu.
 */
function mgt_fields( $post_type ) {
	switch ( $post_type ) {
		case 'mg_priklad':
			return array(
				'mgt_box_atlas' => array(
					'title'  => 'Karta v atlase',
					'fields' => array(
						'labels' => array( 'text', 'Štítky', '', null, 'Oddeľte bodkočiarkou, napr. SMS; Smishing; Veľmi časté. Prvý je červený.' ),
						'source' => array( 'text', 'Zdroj', '', null, 'Napr. SK-CERT, varovanie z 03/2025.' ),
						'label'  => array( 'text', 'Popis nad ukážkou', 'Ukážka' ),
					),
				),
				'mgt_box_mock'  => array(
					'title'  => 'Ukážka správy',
					'fields' => mgt_mock_fields(),
				),
				'mgt_box_home'  => array(
					'title'   => 'Domovská stránka',
					'context' => 'side',
					'fields'  => array(
						'home'      => array( 'checkbox', 'Zobraziť v „Aktuálne podvody“', '' ),
						'home_tag'  => array( 'text', 'Štítok', '' ),
						'home_text' => array( 'textarea', 'Krátky popis', '' ),
					),
				),
			);

		case 'mg_karta':
			$styles = array( 'bad' => 'Červený nadpis', 'good' => 'Zelený nadpis', 'plain' => 'Bez farby' );
			return array(
				'mgt_box_karta' => array(
					'title'  => 'Obsah karty',
					'fields' => array(
						'number'     => array( 'text', 'Označenie', 'KARTA 1 · 1 MIN' ),
						'tag'        => array( 'text', 'Štítok', '' ),
						'tag_red'    => array( 'checkbox', 'Červený štítok', '' ),
						'looks'      => array( 'textarea', 'Ako to vyzerá (príklad)', '' ),
						'col1_title' => array( 'text', 'Stĺpec 1 – nadpis', 'Varovné znaky' ),
						'col1_style' => array( 'select', 'Stĺpec 1 – farba', 'bad', $styles ),
						'col1'       => array( 'textarea', 'Stĺpec 1 – body', '', null, 'Jeden bod na riadok.' ),
						'col2_title' => array( 'text', 'Stĺpec 2 – nadpis', 'Čo urobiť' ),
						'col2_style' => array( 'select', 'Stĺpec 2 – farba', 'good', $styles ),
						'col2'       => array( 'textarea', 'Stĺpec 2 – body', '', null, 'Jeden bod na riadok.' ),
						'note'       => array( 'textarea', 'Poznámka pod stĺpcami', '' ),
						'remember'   => array( 'text', 'Zapamätaj si (žltá veta)', '' ),
						'question'   => array( 'text', 'Rýchla otázka', '' ),
						'answer'     => array( 'textarea', 'Odpoveď', '' ),
						'link_text'  => array( 'text', 'Text odkazu', '' ),
						'link'       => array( 'text', 'Odkaz (adresa)', '' ),
					),
				),
			);

		case 'mg_test':
			return array(
				'mgt_box_test' => array(
					'title'  => 'Nastavenie testu',
					'fields' => array(
						'test_type'   => array( 'select', 'Typ testu', 'trenazer', array( 'trenazer' => 'Trenažér: podvod alebo v poriadku', 'kviz' => 'Kvíz: výber z odpovedí' ) ),
						'intro'       => array( 'textarea', 'Úvodný text', '' ),
						'question'    => array( 'text', 'Otázka pri každej položke', 'Je táto správa podvod?', null, 'Pri kvíze napr. „Akú techniku manipulácie autor použil?“' ),
						'hint'        => array( 'text', 'Pomôcka pod otázkou', 'Všímajte si odosielateľa, odkaz a čo od vás chce.' ),
						'scam_label'  => array( 'text', 'Tlačidlo „podvod“ (trenažér)', 'Podvod' ),
						'safe_label'  => array( 'text', 'Tlačidlo „v poriadku“ (trenažér)', 'Je v poriadku' ),
						'result_high' => array( 'text', 'Výsledok 90 % a viac', 'Výborne. Podvodníci to s vami budú mať ťažké.' ),
						'result_mid'  => array( 'text', 'Výsledok 60–89 %', 'Dobrý základ. Pozrite si typy, pri ktorých ste sa pomýlili.' ),
						'result_low'  => array( 'text', 'Výsledok pod 60 %', 'Nevadí, presne na to je test. Skúste to znova.' ),
						'summary'     => array( 'textarea', 'Čo si zapamätať (po teste)', '', null, 'Jeden bod na riadok.' ),
					),
				),
				'mgt_box_examples' => array(
					'title'  => 'Pridať príklady z atlasu (trenažér)',
					'fields' => array(
						'examples' => array( 'posts', 'Príklady podvodov, ktoré sa zaradia do testu ako „podvod“', array(), 'mg_priklad' ),
					),
				),
				'mgt_box_questions' => array(
					'title'  => 'Otázky',
					'fields' => array(
						'questions' => array( 'repeater', 'Otázky', array() ),
					),
				),
			);
	}
	return array();
}

/**
 * Hodnota poľa s predvolenou hodnotou.
 */
function mgt_meta( $post_id, $key, $default = null ) {
	if ( ! metadata_exists( 'post', $post_id, '_mg_' . $key ) ) {
		if ( null !== $default ) {
			return $default;
		}
		foreach ( mgt_fields( get_post_type( $post_id ) ) as $box ) {
			if ( isset( $box['fields'][ $key ] ) ) {
				return $box['fields'][ $key ][2];
			}
		}
		return '';
	}
	return get_post_meta( $post_id, '_mg_' . $key, true );
}

/**
 * Registrácia meta boxov.
 */
function mgt_add_meta_boxes( $post_type ) {
	foreach ( mgt_fields( $post_type ) as $id => $box ) {
		add_meta_box(
			$id,
			$box['title'],
			'mgt_render_box',
			$post_type,
			isset( $box['context'] ) ? $box['context'] : 'normal',
			'high',
			array( 'fields' => $box['fields'] )
		);
	}
	if ( 'mg_priklad' === $post_type ) {
		add_meta_box( 'mgt_box_help', 'Ako písať ukážku', 'mgt_render_help', $post_type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'mgt_add_meta_boxes' );

/**
 * Jedno pole formulára.
 */
function mgt_field_html( $name, $def, $value ) {
	list( $type, $label ) = $def;
	$opts = isset( $def[3] ) ? $def[3] : null;
	$help = isset( $def[4] ) ? $def[4] : '';
	$id   = 'mgf-' . preg_replace( '/[^a-z0-9]+/i', '-', $name );
	$out  = '<div class="mgt-field mgt-field-' . esc_attr( $type ) . '">';

	switch ( $type ) {
		case 'checkbox':
			$out .= '<label><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( (bool) $value, true, false ) . '> ' . esc_html( $label ) . '</label>';
			break;
		case 'select':
			$out .= '<label for="' . $id . '">' . esc_html( $label ) . '</label><select id="' . $id . '" name="' . esc_attr( $name ) . '" data-field="' . esc_attr( preg_replace( '/.*\[|\]/', '', $name ) ) . '">';
			foreach ( $opts as $k => $v ) {
				$out .= '<option value="' . esc_attr( $k ) . '" ' . selected( $value, $k, false ) . '>' . esc_html( $v ) . '</option>';
			}
			$out .= '</select>';
			break;
		case 'textarea':
		case 'code':
			$rows = 'code' === $type ? 6 : 3;
			$out .= '<label for="' . $id . '">' . esc_html( $label ) . '</label><textarea id="' . $id . '" name="' . esc_attr( $name ) . '" rows="' . $rows . '" class="large-text' . ( 'code' === $type ? ' code' : '' ) . '" data-field="' . esc_attr( preg_replace( '/.*\[|\]/', '', $name ) ) . '">' . esc_textarea( $value ) . '</textarea>';
			break;
		case 'number':
			$out .= '<label for="' . $id . '">' . esc_html( $label ) . '</label><input id="' . $id . '" type="number" min="1" max="10" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
			break;
		default:
			$out .= '<label for="' . $id . '">' . esc_html( $label ) . '</label><input id="' . $id . '" type="text" class="widefat" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" data-field="' . esc_attr( preg_replace( '/.*\[|\]/', '', $name ) ) . '">';
	}
	if ( $help ) {
		$out .= '<p class="description">' . esc_html( $help ) . '</p>';
	}
	return $out . '</div>';
}

/**
 * Polia jednej otázky testu (trenažér aj kvíz; zobrazia sa podľa typu testu).
 */
function mgt_question_fields() {
	return array_merge(
		array(
			'verdict'     => array( 'select', 'Správna odpoveď', 'scam', array( 'scam' => 'Podvod', 'safe' => 'Je v poriadku' ), '', 'trenazer' ),
			'kind'        => array( 'text', 'Kanál (zobrazí sa nad ukážkou)', 'SMS', null, '', 'trenazer' ),
			'situation'   => array( 'text', 'Situácia (nepovinné)', '', null, 'Napr. „Práve sa prihlasujete do e-mailu.“', 'trenazer' ),
			'text'        => array( 'textarea', 'Text príspevku / otázky', '', null, '', 'kviz' ),
			'answers'     => array( 'textarea', 'Odpovede (jedna na riadok)', '', null, '', 'kviz' ),
			'correct'     => array( 'number', 'Správna odpoveď (poradové číslo)', '1', null, '', 'kviz' ),
			'explanation' => array( 'textarea', 'Vysvetlenie', '', null, '', 'kviz' ),
		),
		array_map(
			function ( $f ) {
				$f    = array_pad( $f, 5, '' );
				$f[5] = 'trenazer';
				return $f;
			},
			mgt_mock_fields()
		)
	);
}

function mgt_question_panel( $index, $q ) {
	$title = '';
	if ( ! empty( $q['text'] ) ) {
		$title = $q['text'];
	} elseif ( ! empty( $q['message'] ) ) {
		$title = $q['message'];
	}
	$title = wp_html_excerpt( preg_replace( '/==/', '', (string) $title ), 70, '…' );
	$out   = '<div class="mgt-q"><div class="mgt-q-head"><span class="mgt-q-num"></span><strong class="mgt-q-title">' . esc_html( $title ? $title : 'Nová otázka' ) . '</strong>';
	$out  .= '<span class="mgt-q-tools"><button type="button" class="button mgt-up" aria-label="Posunúť vyššie">↑</button><button type="button" class="button mgt-down" aria-label="Posunúť nižšie">↓</button><button type="button" class="button mgt-toggle">Upraviť</button><button type="button" class="button-link-delete mgt-remove">Odstrániť</button></span></div><div class="mgt-q-body">';
	foreach ( mgt_question_fields() as $key => $def ) {
		$val  = isset( $q[ $key ] ) ? $q[ $key ] : $def[2];
		$out .= '<div class="mgt-only mgt-only-' . esc_attr( $def[5] ) . '">' . mgt_field_html( 'mg_q[' . $index . '][' . $key . ']', $def, $val ) . '</div>';
	}
	return $out . '<p class="mgt-count description"></p></div></div>';
}

/**
 * Vykreslenie meta boxu.
 */
function mgt_render_box( $post, $box ) {
	wp_nonce_field( 'mgt_save_meta', 'mgt_meta_nonce' );
	echo '<div class="mgt-box">';
	foreach ( $box['args']['fields'] as $key => $def ) {
		$value = mgt_meta( $post->ID, $key );
		if ( 'repeater' === $def[0] ) {
			$qs = is_array( $value ) ? $value : array();
			echo '<div class="mgt-repeater">';
			foreach ( $qs as $i => $q ) {
				echo mgt_question_panel( $i, $q ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div><template class="mgt-q-template">' . mgt_question_panel( '__i__', array() ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<p><button type="button" class="button button-primary mgt-add">+ Pridať otázku</button> <span class="description">Otázky sa zobrazia v tomto poradí. Príklady z atlasu sa zaradia pred ne.</span></p>';
			continue;
		}
		if ( 'posts' === $def[0] ) {
			$sel   = array_map( 'intval', is_array( $value ) ? $value : array() );
			$items = get_posts( array( 'post_type' => $def[3], 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'post_status' => array( 'publish', 'draft' ) ) );
			echo '<p class="description">' . esc_html( $def[1] ) . '</p><div class="mgt-posts">';
			if ( ! $items ) {
				echo '<p>Zatiaľ nemáte žiadne príklady podvodov.</p>';
			}
			foreach ( $items as $it ) {
				printf( '<label><input type="checkbox" name="mg_meta[%1$s][]" value="%2$d" %3$s> %4$s</label>', esc_attr( $key ), (int) $it->ID, checked( in_array( $it->ID, $sel, true ), true, false ), esc_html( get_the_title( $it ) ) );
			}
			echo '</div>';
			continue;
		}
		echo mgt_field_html( 'mg_meta[' . $key . ']', $def, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( 'mgt_box_mock' === $box['id'] ) {
		echo '<p class="mgt-count description"></p>';
	}
	echo '</div>';
}

function mgt_render_help() {
	echo '<p><code>==text==</code> zvýrazní varovný znak.</p><p>Do poľa <em>Vysvetlenia</em> napíšte ku každému zvýrazneniu jeden riadok v rovnakom poradí.</p><p><code>&gt; text</code> vaša odpoveď v chate<br><code>! text</code> systémové upozornenie<br><code>btn: text</code> tlačidlo<br><code>img: popis | #farba</code> obrázok<br><code>link: doména | nadpis</code><br><code>stats: vľavo | vpravo</code><br><code>foot: text</code> pätička e-mailu<br><code>title:</code> <code>input:</code> <code>alert:</code> web<br><code>Volajúci: text</code> hovor</p><p>Kategórie <strong>Kanál</strong> a <strong>Pre koho</strong> určujú filter v atlase.</p>';
}

/**
 * Čistenie hodnôt.
 */
function mgt_clean( $type, $v ) {
	if ( 'checkbox' === $type ) {
		return $v ? '1' : '';
	}
	if ( 'number' === $type ) {
		return (string) max( 1, (int) $v );
	}
	$v = wp_unslash( (string) $v );
	if ( in_array( $type, array( 'textarea', 'code' ), true ) ) {
		return trim( wp_kses( $v, mgt_inline_tags() ) );
	}
	return trim( wp_kses( $v, mgt_inline_tags() ) );
}

/**
 * Uloženie polí.
 */
function mgt_save_meta( $post_id, $post ) {
	if ( ! isset( $_POST['mgt_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mgt_meta_nonce'] ), 'mgt_save_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['mg_meta'] ) && is_array( $_POST['mg_meta'] ) ? $_POST['mg_meta'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- čistí sa nižšie.

	foreach ( mgt_fields( $post->post_type ) as $box ) {
		foreach ( $box['fields'] as $key => $def ) {
			if ( 'repeater' === $def[0] ) {
				$qs  = isset( $_POST['mg_q'] ) && is_array( $_POST['mg_q'] ) ? $_POST['mg_q'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$out = array();
				foreach ( $qs as $i => $q ) {
					if ( '__i__' === (string) $i || ! is_array( $q ) ) {
						continue;
					}
					$row = array();
					foreach ( mgt_question_fields() as $qk => $qd ) {
						$row[ $qk ] = mgt_clean( $qd[0], isset( $q[ $qk ] ) ? $q[ $qk ] : '' );
					}
					$out[] = $row;
				}
				update_post_meta( $post_id, '_mg_' . $key, $out );
				continue;
			}
			if ( 'posts' === $def[0] ) {
				$ids = isset( $in[ $key ] ) && is_array( $in[ $key ] ) ? array_map( 'absint', $in[ $key ] ) : array();
				update_post_meta( $post_id, '_mg_' . $key, array_values( array_filter( $ids ) ) );
				continue;
			}
			$val = isset( $in[ $key ] ) ? $in[ $key ] : '';
			if ( 'select' === $def[0] && ! isset( $def[3][ $val ] ) ) {
				$val = $def[2];
			}
			update_post_meta( $post_id, '_mg_' . $key, mgt_clean( $def[0], $val ) );
		}
	}
}
add_action( 'save_post', 'mgt_save_meta', 10, 2 );

/**
 * Skripty a štýly administrácie.
 */
function mgt_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( 'mg_priklad', 'mg_test', 'mg_karta' ), true ) ) {
		return;
	}
	wp_enqueue_style( 'mgt-admin', get_template_directory_uri() . '/assets/css/admin.css', array(), MGT_VERSION );
	wp_enqueue_script( 'mgt-admin', get_template_directory_uri() . '/assets/js/admin.js', array(), MGT_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'mgt_admin_assets' );

/**
 * Stĺpec „Poradie“ a „Typ“ v zoznamoch.
 */
function mgt_admin_columns( $cols ) {
	$cols['mgt_order'] = 'Poradie';
	return $cols;
}
function mgt_admin_column_value( $col, $post_id ) {
	if ( 'mgt_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $post_id );
	}
}
foreach ( array( 'mg_priklad', 'mg_karta', 'mg_test' ) as $mgt_pt ) {
	add_filter( 'manage_' . $mgt_pt . '_posts_columns', 'mgt_admin_columns' );
	add_action( 'manage_' . $mgt_pt . '_posts_custom_column', 'mgt_admin_column_value', 10, 2 );
}
