<?php
/**
 * Vykreslenie obsahu z typov obsahu (príklady, testy, karty) a časti šablón.
 *
 * @package MedialnaGramotnostTema
 */

defined( 'ABSPATH' ) || exit;

/**
 * Atribúty makety z polí príkladu alebo otázky.
 */
function mgt_mock_atts( $src ) {
	$a = array();
	foreach ( array_keys( mgt_mock_fields() ) as $k ) {
		$a[ $k ] = isset( $src[ $k ] ) ? (string) $src[ $k ] : '';
	}
	if ( ! isset( mgt_mock_types()[ $a['type'] ] ) ) {
		$a['type'] = 'sms';
	}
	return $a;
}

/**
 * Obsah správy pre maketu: text + oddeľovač + vysvetlenia.
 */
function mgt_mock_content( $message, $notes ) {
	return trim( (string) $message ) . ( '' !== trim( (string) $notes ) ? "\n---\n" . trim( (string) $notes ) : '' );
}

/**
 * Polia príkladu ako pole.
 */
function mgt_priklad_data( $post_id ) {
	$d = array();
	foreach ( array_keys( mgt_mock_fields() ) as $k ) {
		$d[ $k ] = mgt_meta( $post_id, $k );
	}
	return $d;
}

/**
 * Ukážka správy z príkladu podvodu (s tlačidlom varovných znakov).
 */
function mgt_priklad_example( $post_id, $open = false ) {
	$d                   = mgt_priklad_data( $post_id );
	$a                   = mgt_mock_atts( $d );
	$a['label']          = mgt_meta( $post_id, 'label' );
	$a['button_text']    = 'Ukázať varovné znaky';
	$a['button_text_on'] = 'Skryť varovné znaky';
	$a['open']           = $open ? 'yes' : 'no';
	return mgt_example_block( $a, mgt_mock_content( $d['message'], $d['notes'] ) );
}

/**
 * Slugy kategórií pre filter.
 */
function mgt_term_slugs( $post_id, $tax ) {
	$t = get_the_terms( $post_id, $tax );
	return $t && ! is_wp_error( $t ) ? wp_list_pluck( $t, 'slug' ) : array();
}

/**
 * Filter nad zoznamom (tlačidlá podľa kategórií).
 */
function mgt_filter_bar( $group, $groups ) {
	$html = '<div class="mg-el-filter mgt-filter" data-mg-filter-group="' . esc_attr( $group ) . '" role="group" aria-label="Filter">';
	$first = true;
	foreach ( $groups as $label => $tax ) {
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) );
		if ( ! $terms || is_wp_error( $terms ) ) {
			continue;
		}
		$html .= '<div class="mg-filters"><span class="mg-label">' . esc_html( $label ) . '</span>';
		if ( $first ) {
			$html .= '<button class="mg-chip" type="button" data-filter="all" aria-pressed="true">Všetko</button>';
			$first = false;
		}
		foreach ( $terms as $t ) {
			$html .= '<button class="mg-chip" type="button" data-filter="' . esc_attr( $t->slug ) . '" aria-pressed="false">' . esc_html( $t->name ) . '</button>';
		}
		$html .= '</div>';
	}
	return $html . '<p class="mg-muted mg-filter-empty" hidden>Pre tento filter zatiaľ nemáme príklad.</p></div>';
}

/**
 * Jeden príklad v atlase (text + ukážka).
 */
function mgt_priklad_item( $post ) {
	$id     = $post->ID;
	$labels = mgt_split( mgt_meta( $id, 'labels' ) );
	$tags   = array_merge( mgt_term_slugs( $id, 'mg_kanal' ), mgt_term_slugs( $id, 'mg_skupina' ) );
	$out    = '<article class="mgt-case" id="' . esc_attr( $post->post_name ) . '" data-mg-group="atlas" data-mg-tags="' . esc_attr( implode( ' ', $tags ) ) . '" data-mg-scope="element">';
	$out   .= '<div class="mgt-case-text">';
	if ( $labels ) {
		$out .= '<p class="mgt-case-labels">';
		foreach ( $labels as $i => $l ) {
			$out .= '<span' . ( 0 === $i ? ' class="mgt-hot"' : '' ) . '>' . esc_html( $l ) . '</span>';
		}
		$out .= '</p>';
	}
	$out .= '<h2><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h2>';
	$out .= '<div class="mgt-prose">' . apply_filters( 'the_content', $post->post_content ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$src  = mgt_meta( $id, 'source' );
	if ( $src ) {
		$out .= '<p class="mgt-source">Zdroj: ' . mgt_inline( $src, false ) . '</p>';
	}
	$out .= '</div><div class="mgt-case-demo">' . mgt_priklad_example( $id ) . '</div></article>';
	return $out;
}

/**
 * Otázky testu: najprv vybrané príklady z atlasu, potom vlastné otázky.
 */
function mgt_test_questions( $post_id ) {
	$type = mgt_meta( $post_id, 'test_type' );
	$qs   = array();
	if ( 'kviz' !== $type ) {
		foreach ( (array) mgt_meta( $post_id, 'examples', array() ) as $pid ) {
			if ( 'mg_priklad' !== get_post_type( $pid ) || 'publish' !== get_post_status( $pid ) ) {
				continue;
			}
			$d              = mgt_priklad_data( $pid );
			$terms          = get_the_terms( $pid, 'mg_kanal' );
			$d['verdict']   = 'scam';
			$d['kind']      = $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
			$d['situation'] = '';
			$qs[]           = $d;
		}
	}
	$own = mgt_meta( $post_id, 'questions', array() );
	return array_merge( $qs, is_array( $own ) ? $own : array() );
}

/**
 * Celý test (trenažér alebo kvíz).
 */
function mgt_render_test_post( $post_id ) {
	$type = mgt_meta( $post_id, 'test_type' );
	$qs   = mgt_test_questions( $post_id );
	if ( ! $qs ) {
		return '<p class="mg-muted">Test zatiaľ nemá žiadne otázky.</p>';
	}

	if ( 'kviz' === $type ) {
		$items = '';
		foreach ( $qs as $q ) {
			$answers = array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) $q['answers'] ) ), 'strlen' ) );
			$correct = max( 1, (int) $q['correct'] ) - 1;
			$items  .= '<div class="mg-quiz-q" data-correct="' . (int) $correct . '"><blockquote>' . mgt_text( $q['text'] ) . '</blockquote><div class="mg-answers">';
			foreach ( $answers as $i => $ans ) {
				$items .= '<button type="button" data-i="' . (int) $i . '">' . esc_html( $ans ) . '</button>';
			}
			$items .= '</div><p class="mg-quiz-ex" hidden>' . mgt_text( $q['explanation'] ) . '</p></div>';
		}
		return '<div class="mg mg-el-quiz mg-quiz-wrap" data-prompt="' . esc_attr( mgt_meta( $post_id, 'question' ) ) . '" data-label="Otázka" data-next="Ďalšia otázka" data-result="' . esc_attr( mgt_meta( $post_id, 'result_mid' ) ) . '"><div class="mg-quiz" aria-live="polite"></div><div class="mg-quiz-items" hidden>' . $items . '</div></div>';
	}

	$items = '';
	foreach ( $qs as $q ) {
		$a     = mgt_mock_atts( $q );
		$a['label'] = '';
		$a['button_text'] = '';
		$a['button_text_on'] = '';
		$inner = '';
		if ( ! empty( $q['situation'] ) ) {
			$inner .= '<p class="mg-example-label mg-situation">' . mgt_inline( $q['situation'], false ) . '</p>';
		}
		$inner .= mgt_example_block( $a, mgt_mock_content( isset( $q['message'] ) ? $q['message'] : '', isset( $q['notes'] ) ? $q['notes'] : '' ), false );
		$items .= '<div class="mg-t-item" data-verdict="' . ( 'safe' === ( isset( $q['verdict'] ) ? $q['verdict'] : '' ) ? 'safe' : 'scam' ) . '" data-kind="' . esc_attr( isset( $q['kind'] ) ? $q['kind'] : '' ) . '">' . $inner . '</div>';
	}
	$summary = '';
	foreach ( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) mgt_meta( $post_id, 'summary' ) ) ), 'strlen' ) as $s ) {
		$summary .= '<li>' . mgt_inline( $s, false ) . '</li>';
	}
	$data = array(
		'question'    => mgt_meta( $post_id, 'question' ),
		'hint'        => mgt_meta( $post_id, 'hint' ),
		'scam'        => mgt_meta( $post_id, 'scam_label' ),
		'safe'        => mgt_meta( $post_id, 'safe_label' ),
		'result-high' => mgt_meta( $post_id, 'result_high' ),
		'result-mid'  => mgt_meta( $post_id, 'result_mid' ),
		'result-low'  => mgt_meta( $post_id, 'result_low' ),
	);
	$attr = '';
	foreach ( $data as $k => $v ) {
		$attr .= ' data-' . $k . '="' . esc_attr( $v ) . '"';
	}
	$more = get_post_type_archive_link( 'mg_priklad' );
	return '<div class="mg mg-el-trainer mg-trainer-wrap"' . $attr . '><div class="mg-t-progress" aria-hidden="true"></div><div class="mg-trainer"><div class="mg-t-stage"></div><div class="mg-t-panel mg-t-side" aria-live="polite"></div></div><div class="mg-t-items">' . $items . '</div><template class="mg-t-summary"><ul>' . $summary . '</ul><a class="mg-btn mg-ghost" href="' . esc_url( $more ) . '">Príklady podvodov</a></template></div>';
}

/**
 * Rýchla karta z polí.
 */
function mgt_karta_html( $post ) {
	$id = $post->ID;
	$a  = mgt_atts( 'mg_lesson', array() );
	foreach ( array( 'number', 'tag', 'looks', 'col1_title', 'col1_style', 'col2_title', 'col2_style', 'note', 'remember', 'question', 'answer', 'link_text', 'link' ) as $k ) {
		$a[ $k ] = (string) mgt_meta( $id, $k );
	}
	$a['title']        = get_the_title( $post );
	$a['tag_red']      = mgt_meta( $id, 'tag_red' ) ? 'yes' : 'no';
	$a['col1']         = str_replace( array( "\r\n", "\n" ), ';', (string) mgt_meta( $id, 'col1' ) );
	$a['col2']         = str_replace( array( "\r\n", "\n" ), ';', (string) mgt_meta( $id, 'col2' ) );
	$a['anchor']       = $post->post_name;
	$a['filter_group'] = 'karty';
	$a['filter_tags']  = implode( ',', mgt_term_slugs( $id, 'mg_skupina' ) );
	return mgt_render_lesson( $a, '' );
}

/**
 * Úvod podstránky.
 */
function mgt_page_head( $title, $lead = '', $crumb = '' ) {
	echo '<header class="mgt-page-head"><div class="mgt-wrap">';
	if ( $crumb ) {
		echo '<p class="mgt-crumbs"><a href="' . esc_url( home_url( '/' ) ) . '">Domov</a> <span aria-hidden="true">/</span> ' . esc_html( $crumb ) . '</p>';
	}
	echo '<h1>' . esc_html( $title ) . '</h1>';
	if ( $lead ) {
		echo '<div class="mgt-lead">' . wp_kses_post( wpautop( $lead ) ) . '</div>';
	}
	echo '</div></header>';
}

/* ---------- Shortcody pre obsah z typov obsahu ---------- */

function mgt_shortcode_test( $atts ) {
	$a = shortcode_atts( array( 'id' => 0 ), $atts, 'mg_test' );
	$p = get_post( (int) $a['id'] );
	return ( $p && 'mg_test' === $p->post_type ) ? mgt_render_test_post( $p->ID ) : '';
}

function mgt_shortcode_priklad( $atts ) {
	$a = shortcode_atts( array( 'id' => 0, 'slug' => '' ), $atts, 'mg_priklad' );
	$p = $a['slug'] ? get_page_by_path( $a['slug'], OBJECT, 'mg_priklad' ) : get_post( (int) $a['id'] );
	return ( $p && 'mg_priklad' === $p->post_type ) ? '<div class="mg">' . mgt_priklad_example( $p->ID ) . '</div>' : '';
}

function mgt_shortcode_contacts() {
	$html = '<div class="mg mg-el-contacts"><div class="mg-contacts">';
	foreach ( mgt_contacts() as $c ) {
		$html .= '<div class="mg-contact"><span class="mg-eyebrow">' . esc_html( $c[0] ) . '</span><span class="mg-num' . ( mb_strlen( $c[1] ) > 12 ? ' mg-small' : '' ) . '">' . esc_html( $c[1] ) . '</span><p>' . esc_html( $c[2] ) . '</p></div>';
	}
	return $html . '</div></div>';
}

/**
 * Menu, kým nie je nastavené: hlavné sekcie webu.
 */
function mgt_menu_fallback() {
	$items = array(
		array( get_post_type_archive_link( 'mg_priklad' ), 'Podvody' ),
		array( get_post_type_archive_link( 'mg_test' ), 'Testy' ),
		array( mgt_page_url( 'overit-spravu' ), 'Overiť správu' ),
		array( mgt_page_url( 'dezinformacie' ), 'Dezinformácie' ),
		array( get_post_type_archive_link( 'mg_karta' ), 'Materiály' ),
		array( mgt_page_url( 'pre-seniorov' ), 'Seniori' ),
		array( mgt_page_url( 'pre-mladych' ), 'Mladí' ),
		array( mgt_page_url( 'stalo-sa-mi-to' ), 'Stalo sa mi to' ),
	);
	echo '<ul class="mgt-menu">';
	foreach ( $items as $it ) {
		if ( $it[0] ) {
			echo '<li><a href="' . esc_url( $it[0] ) . '">' . esc_html( $it[1] ) . '</a></li>';
		}
	}
	echo '</ul>';
}
