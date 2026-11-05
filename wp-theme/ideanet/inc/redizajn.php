<?php
/**
 * Podstránka s ponukou redizajnu — texty sa upravujú priamo pri stránke.
 *
 * Texty tejto podstránky nie sú v Prispôsobiť, ale v boxoch pod editorom
 * stránky, rozdelené po sekciách tak, ako idú na webe. Kto upravuje
 * ponuku, má všetko na jednom mieste a nemusí ho hľadať v nastaveniach.
 *
 * Hodnoty sa ukladajú ako vlastné polia stránky. Keď pole zostane
 * prázdne, použije sa predvolený text z defaults.php.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Šablóna, pri ktorej sa boxy zobrazujú.
 */
const IDEANET_RED_TEMPLATE = 'page-templates/redizajn.php';

/**
 * Polia rozdelené do boxov podľa sekcií stránky.
 *
 * Každé pole je: kľúč => array( popis, typ, nápoveda ).
 * Typ je text, area alebo number.
 *
 * @return array<string,array{0:string,1:array}>
 */
function ideanet_red_boxes() {
	$lines = __( 'Jedna položka na riadok.', 'ideanet' );
	$cols  = __( 'Jedna položka na riadok, stĺpce oddeľte zvislou čiarou |', 'ideanet' );

	return array(

		'cisla' => array(
			__( 'Ponuka — cena, termín a počty', 'ideanet' ),
			array(
				'red_price'      => array( __( 'Akčná cena', 'ideanet' ), 'text', __( 'Doplní sa všade, kde je v texte {cena}.', 'ideanet' ) ),
				'red_price_pred' => array( __( 'Cena pred akciou', 'ideanet' ), 'text', __( 'Zobrazí sa preškrtnutá. V texte {cena_pred}.', 'ideanet' ) ),
				'red_price_unit' => array( __( 'Text pod cenou', 'ideanet' ), 'text' ),
				'red_term'       => array( __( 'Dokedy ponuka platí', 'ideanet' ), 'text', __( 'Napríklad 26. – 30. 11. V texte {termin}.', 'ideanet' ) ),
				'red_slots'      => array( __( 'Koľko miest je v ponuke', 'ideanet' ), 'number', __( 'V texte {miest}.', 'ideanet' ) ),
				'red_slots_left' => array( __( 'Koľko miest ešte zostáva', 'ideanet' ), 'number', __( 'Toto číslo znižujte, ako sa miesta obsadzujú. V texte {volnych}.', 'ideanet' ) ),
				'red_pages'      => array( __( 'Koľko podstránok je v cene', 'ideanet' ), 'number', __( 'V texte {podstranok}.', 'ideanet' ) ),
				'red_days'       => array( __( 'Za koľko dní je web hotový', 'ideanet' ), 'number', __( 'V texte {dni}.', 'ideanet' ) ),
			),
		),

		'uvod' => array(
			__( 'Úvod stránky', 'ideanet' ),
			array(
				'red_flag'     => array( __( 'Štítok nad nadpisom', 'ideanet' ), 'text', __( 'Prázdne = štítok sa nezobrazí.', 'ideanet' ) ),
				'red_eyebrow'  => array( __( 'Malý riadok nad nadpisom', 'ideanet' ), 'text' ),
				'red_title'    => array( __( 'Nadpis', 'ideanet' ), 'text' ),
				'red_title_hl' => array( __( 'Nadpis — farebná časť', 'ideanet' ), 'text', __( 'Doplní sa na koniec nadpisu.', 'ideanet' ) ),
				'red_lead'     => array( __( 'Odsek pod nadpisom', 'ideanet' ), 'area' ),
				'red_cta'      => array( __( 'Hlavné tlačidlo', 'ideanet' ), 'text' ),
				'red_cta2'     => array( __( 'Druhé tlačidlo', 'ideanet' ), 'text' ),
				'red_facts'    => array( __( 'Tri fakty pod tlačidlami', 'ideanet' ), 'area', $cols ),
			),
		),

		'poznate' => array(
			__( 'Sekcia „Poznáte to?"', 'ideanet' ),
			array(
				'red_poznate_eyebrow' => array( __( 'Malý riadok', 'ideanet' ), 'text' ),
				'red_poznate_title'   => array( __( 'Nadpis', 'ideanet' ), 'text' ),
				'red_poznate_text'    => array( __( 'Text', 'ideanet' ), 'area', __( 'Prázdne = celá sekcia sa nezobrazí.', 'ideanet' ) ),
			),
		),

		'dostanete' => array(
			__( 'Sekcia „Čo dostanete"', 'ideanet' ),
			array(
				'red_gets_eyebrow' => array( __( 'Malý riadok', 'ideanet' ), 'text' ),
				'red_gets_title'   => array( __( 'Nadpis', 'ideanet' ), 'text' ),
				'red_gets_note'    => array( __( 'Veta vpravo', 'ideanet' ), 'area' ),
				'red_gets'         => array( __( 'Zoznam', 'ideanet' ), 'area', $lines . ' ' . __( 'Prázdne = celá sekcia sa nezobrazí.', 'ideanet' ) ),
			),
		),

		'ako' => array(
			__( 'Sekcia „Ako to prebieha"', 'ideanet' ),
			array(
				'red_ako_eyebrow' => array( __( 'Malý riadok', 'ideanet' ), 'text' ),
				'red_ako_title'   => array( __( 'Nadpis', 'ideanet' ), 'text' ),
				'red_ako_note'    => array( __( 'Veta vpravo', 'ideanet' ), 'area' ),
				'red_ako'         => array( __( 'Kroky', 'ideanet' ), 'area', $cols . ' ' . __( 'Čísla krokov sa doplnia samy.', 'ideanet' ) ),
			),
		),

		'ukazky' => array(
			__( 'Sekcia „Pred a po"', 'ideanet' ),
			array(
				'red_ukazky_eyebrow' => array( __( 'Malý riadok', 'ideanet' ), 'text' ),
				'red_ukazky_title'   => array( __( 'Nadpis', 'ideanet' ), 'text' ),
				'red_ukazky_note'    => array( __( 'Veta vpravo', 'ideanet' ), 'area' ),
				'red_ukazky_foot'    => array( __( 'Poznámka pod ukážkami', 'ideanet' ), 'area', __( 'Prázdne = nezobrazí sa. Samotné ukážky pridávate v IDEANET → Redizajn — ukážky.', 'ideanet' ) ),
				'red_label_pred'     => array( __( 'Štítok na pôvodnom obrázku', 'ideanet' ), 'text' ),
				'red_label_po'       => array( __( 'Štítok na novom obrázku', 'ideanet' ), 'text' ),
			),
		),

		'cena' => array(
			__( 'Sekcia „Cena"', 'ideanet' ),
			array(
				'red_cena_eyebrow' => array( __( 'Malý riadok', 'ideanet' ), 'text' ),
				'red_cena_title'   => array( __( 'Nadpis', 'ideanet' ), 'text' ),
				'red_deal_when'    => array( __( 'Riadok s termínom', 'ideanet' ), 'text' ),
				'red_cena_note'    => array( __( 'Čo v cene nie je', 'ideanet' ), 'area' ),
			),
		),

		'komu' => array(
			__( 'Sekcia „Pre koho ponuka je"', 'ideanet' ),
			array(
				'red_prekoho_eyebrow' => array( __( 'Malý riadok', 'ideanet' ), 'text' ),
				'red_prekoho_title'   => array( __( 'Nadpis', 'ideanet' ), 'text' ),
				'red_prekoho_note'    => array( __( 'Veta vpravo', 'ideanet' ), 'area' ),
				'red_prekoho'         => array( __( 'Zoznam', 'ideanet' ), 'area', $lines ),
				'red_prekoho_grab'    => array( __( 'Veta o ponuke na mieru', 'ideanet' ), 'area', __( 'Prázdne = celý rámček sa nezobrazí.', 'ideanet' ) ),
				'red_prekoho_cta'     => array( __( 'Tlačidlo v rámčeku', 'ideanet' ), 'text' ),
			),
		),

		'otazky' => array(
			__( 'Sekcia „Časté otázky"', 'ideanet' ),
			array(
				'red_faq_eyebrow' => array( __( 'Malý riadok', 'ideanet' ), 'text' ),
				'red_faq_title'   => array( __( 'Nadpis', 'ideanet' ), 'text' ),
				'red_faq'         => array( __( 'Otázky a odpovede', 'ideanet' ), 'area', $cols . ' ' . __( 'Riadok: otázka | odpoveď. Prázdne = celá sekcia sa nezobrazí.', 'ideanet' ) ),
			),
		),

		'dopyt' => array(
			__( 'Záverečná výzva a formulár', 'ideanet' ),
			array(
				'red_end_title'    => array( __( 'Výzva — nadpis', 'ideanet' ), 'text' ),
				'red_end_text'     => array( __( 'Výzva — text', 'ideanet' ), 'area' ),
				'red_end_cta'      => array( __( 'Výzva — tlačidlo', 'ideanet' ), 'text' ),
				'red_form_eyebrow' => array( __( 'Formulár — malý riadok', 'ideanet' ), 'text' ),
				'red_form_title'   => array( __( 'Formulár — nadpis', 'ideanet' ), 'text' ),
				'red_form_note'    => array( __( 'Formulár — text nad formulárom', 'ideanet' ), 'area' ),
				'red_form_option'  => array( __( 'Formulár — predvolená voľba vo výbere', 'ideanet' ), 'text', __( 'Pridá sa do výberu „o čo máte záujem" a na tejto stránke je prednastavená. Prázdne = voľba sa nepridá.', 'ideanet' ) ),
			),
		),
	);
}

/**
 * Všetky polia naraz, bez rozdelenia do boxov.
 *
 * @return array
 */
function ideanet_red_all_fields() {
	$out = array();

	foreach ( ideanet_red_boxes() as $box ) {
		$out += $box[1];
	}

	return $out;
}

/**
 * ID stránky, z ktorej sa texty berú.
 *
 * Najprv práve zobrazená stránka, inak prvá stránka s touto šablónou —
 * aby {cena} a podobné fungovali aj v menu alebo vo formulári inde
 * na webe.
 *
 * @return int
 */
function ideanet_red_page_id() {
	static $zaloha = null;

	$id = get_queried_object_id();
	if ( $id && IDEANET_RED_TEMPLATE === get_page_template_slug( $id ) ) {
		return (int) $id;
	}

	if ( null === $zaloha ) {
		/* Uložené ID, aby sa pri každom načítaní nehľadalo podľa šablóny. */
		$zaloha = (int) get_option( 'ideanet_red_page' );

		if ( ! $zaloha || IDEANET_RED_TEMPLATE !== get_page_template_slug( $zaloha ) ) {
			$stranka = function_exists( 'ideanet_redizajn_stranka' ) ? ideanet_redizajn_stranka() : null;
			$zaloha  = $stranka ? (int) $stranka->ID : 0;
			update_option( 'ideanet_red_page', $zaloha, false );
		}
	}

	return $zaloha;
}

/**
 * Text ponuky redizajnu — najprv z poľa pri stránke, potom predvolený.
 *
 * @param string $key Kľúč nastavenia.
 * @return string
 */
function ideanet_red_opt( $key ) {
	$id = ideanet_red_page_id();

	if ( $id ) {
		$value = get_post_meta( $id, '_ideanet_' . $key, true );
		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}

	return ideanet_opt( $key );
}

/**
 * Prepíše texty ponuky do polí pri stránke.
 *
 * Kým sa texty upravovali v Prispôsobiť, boli uložené ako nastavenia
 * témy. Aby po prechode na úpravu pri stránke nikto nestratil, čo si
 * prepísal — a aby v boxoch nesvietili prázdne polia —, hodnoty sa raz
 * prekopírujú k stránke. Prepisuje sa len to, čo pri stránke ešte nie je.
 *
 * @param int $page_id ID podstránky.
 */
function ideanet_red_prefill( $page_id ) {
	foreach ( ideanet_red_all_fields() as $key => $field ) {
		$name = '_ideanet_' . $key;

		if ( '' !== (string) get_post_meta( $page_id, $name, true ) ) {
			continue;
		}

		$value = ideanet_opt( $key );
		if ( '' !== $value ) {
			update_post_meta( $page_id, $name, $value );
		}
	}
}

/**
 * Táto podstránka sa upravuje v klasickom editore.
 *
 * Texty sú v boxoch pod editorom a blokový editor ich schováva do
 * zasunutej lišty na spodku obrazovky, ktorú treba najprv vytiahnuť —
 * čiže presne tam, kde ich nikto nehľadá. V klasickom editore sú rovno
 * pod sebou, sekcia po sekcii. Obsah stránky sa tu aj tak nepoužíva na
 * nič iné než na voliteľný odsek navyše.
 *
 * @param bool    $use  Či použiť blokový editor.
 * @param WP_Post $post Upravovaná položka.
 * @return bool
 */
function ideanet_red_classic_editor( $use, $post ) {
	if ( $post && 'page' === $post->post_type && IDEANET_RED_TEMPLATE === get_page_template_slug( $post->ID ) ) {
		return false;
	}

	return $use;
}
add_filter( 'use_block_editor_for_post', 'ideanet_red_classic_editor', 10, 2 );

/**
 * Boxy pod editorom stránky — len pri šablóne s ponukou redizajnu.
 */
function ideanet_red_meta_boxes() {
	global $post;

	if ( ! $post || 'page' !== $post->post_type ) {
		return;
	}
	if ( IDEANET_RED_TEMPLATE !== get_page_template_slug( $post->ID ) ) {
		return;
	}

	$prvy = true;

	foreach ( ideanet_red_boxes() as $slug => $box ) {
		add_meta_box(
			'ideanet-red-' . $slug,
			$box[0],
			'ideanet_red_meta_box_html',
			'page',
			'normal',
			$prvy ? 'high' : 'default',
			array( 'fields' => $box[1], 'first' => $prvy )
		);
		$prvy = false;
	}
}
add_action( 'add_meta_boxes', 'ideanet_red_meta_boxes' );

/**
 * Obsah jedného boxu.
 *
 * @param WP_Post $post Stránka.
 * @param array   $box  Údaje boxu aj s poľami.
 */
function ideanet_red_meta_box_html( $post, $box ) {
	$fields = $box['args']['fields'];

	if ( ! empty( $box['args']['first'] ) ) {
		wp_nonce_field( 'ideanet_red_save', 'ideanet_red_nonce' );
		echo '<p class="description" style="margin:.2rem 0 1rem">'
			. esc_html__( 'Texty tejto podstránky sa upravujú tu, nie v Prispôsobiť. Prázdne pole = použije sa predvolený text.', 'ideanet' )
			. '</p>';
	}

	echo '<table class="form-table" role="presentation"><tbody>';

	foreach ( $fields as $key => $field ) {
		$label = $field[0];
		$type  = isset( $field[1] ) ? $field[1] : 'text';
		$hint  = isset( $field[2] ) ? $field[2] : '';
		$name  = '_ideanet_' . $key;
		$id    = 'ideanet-' . $key;
		$value = (string) get_post_meta( $post->ID, $name, true );

		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';

		if ( 'area' === $type ) {
			printf(
				'<textarea id="%1$s" name="%2$s" rows="%4$d" class="large-text">%3$s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_textarea( $value ),
				(int) max( 3, min( 8, substr_count( $value, "\n" ) + 2 ) )
			);
		} elseif ( 'number' === $type ) {
			printf(
				'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" min="0" step="1">',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value )
			);
		} else {
			printf(
				'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="large-text" style="max-width:36rem">',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value )
			);
		}

		if ( $hint ) {
			echo '<p class="description">' . esc_html( $hint ) . '</p>';
		}

		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * Uloží texty podstránky.
 *
 * @param int $post_id ID stránky.
 */
function ideanet_red_save( $post_id ) {
	if ( ! isset( $_POST['ideanet_red_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ideanet_red_nonce'] ) ), 'ideanet_red_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( ideanet_red_all_fields() as $key => $field ) {
		$name = '_ideanet_' . $key;

		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}

		$raw   = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- čistí sa hneď nižšie podľa typu poľa.
		$value = 'number' === $field[1]
			? preg_replace( '/[^0-9]/', '', (string) $raw )
			: trim( wp_kses( (string) $raw, ideanet_kses() ) );

		if ( '' === $value ) {
			delete_post_meta( $post_id, $name );
		} else {
			update_post_meta( $post_id, $name, $value );
		}
	}
}
add_action( 'save_post_page', 'ideanet_red_save' );
