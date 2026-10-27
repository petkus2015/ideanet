<?php
/**
 * Vykreslenie prvkov (shortcodov).
 *
 * Každá funkcia mg_render_{názov} dostane atribúty a obsah a vráti HTML.
 *
 * @package MedialnaGramotnost
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Úvod a nadpisy ---------- */

function mg_render_hero( $a, $content ) {
	$is_page = 'page' === $a['size'];
	$html    = '';
	if ( '' !== trim( $a['crumbs'] ) ) {
		$html .= '<p class="mg-crumbs">' . mg_inline( $a['crumbs'], false ) . '</p>';
	}
	if ( '' !== trim( $a['eyebrow'] ) ) {
		$html .= '<p class="mg-eyebrow">' . esc_html( $a['eyebrow'] ) . '</p>';
	}
	$title = mg_inline( $a['title'], false );
	if ( '' !== trim( $a['highlight'] ) ) {
		$title .= ' <span class="mg-mark">' . mg_inline( $a['highlight'], false ) . '</span>';
	}
	$html .= '<h1>' . $title . '</h1>';
	if ( '' !== trim( $a['lead'] ) ) {
		$html .= '<p class="mg-lead">' . mg_text( $a['lead'] ) . '</p>';
	}
	$buttons = '';
	if ( '' !== trim( $a['button1_text'] ) ) {
		$buttons .= '<a class="mg-btn"' . mg_href( $a['button1_link'] ) . '>' . esc_html( $a['button1_text'] ) . '</a>';
	}
	if ( '' !== trim( $a['button2_text'] ) ) {
		$buttons .= '<a class="mg-btn mg-ghost"' . mg_href( $a['button2_link'] ) . '>' . esc_html( $a['button2_text'] ) . '</a>';
	}
	if ( $buttons ) {
		$html .= '<div class="mg-btn-row">' . $buttons . '</div>';
	}
	return mg_wrap( 'mg_hero', $a, $html, $is_page ? 'mg-page-hero' : 'mg-hero-text' );
}

function mg_render_heading( $a, $content ) {
	$left = '';
	if ( '' !== trim( $a['eyebrow'] ) ) {
		$left .= '<p class="mg-eyebrow">' . esc_html( $a['eyebrow'] ) . '</p>';
	}
	$left .= '<h2>' . mg_inline( $a['title'], false ) . '</h2>';
	$right = '';
	if ( '' !== trim( $a['text'] ) ) {
		$right .= '<p>' . mg_text( $a['text'] ) . '</p>';
	}
	if ( '' !== trim( $a['button_text'] ) ) {
		$right .= '<a class="mg-btn mg-ghost"' . mg_href( $a['button_link'] ) . '>' . esc_html( $a['button_text'] ) . '</a>';
	}
	$html = '<div class="mg-section-head"><div>' . $left . '</div>' . ( $right ? '<div class="mg-stack">' . $right . '</div>' : '' ) . '</div>';
	return mg_wrap( 'mg_heading', $a, $html, 'mg-heading' . ( 'light' === $a['tone'] ? ' mg-on-dark' : '' ) );
}

/* ---------- Ukážky a podvody ---------- */

function mg_render_example( $a, $content ) {
	return mg_wrap( 'mg_example', $a, mg_example_block( $a, $content ) );
}

function mg_render_scam( $a, $content ) {
	$labels = mg_split( $a['labels'] );
	$tags   = '';
	foreach ( $labels as $i => $l ) {
		$tags .= '<span class="mg-tag' . ( 0 === $i ? ' mg-red' : '' ) . '">' . esc_html( $l ) . '</span>';
	}
	$html = '<div class="mg-info">';
	if ( $tags ) {
		$html .= '<div class="mg-tags">' . $tags . '</div>';
	}
	$html .= '<h2>' . mg_inline( $a['title'], false ) . '</h2>';
	$html .= '<div class="mg-rich">' . wpautop( do_shortcode( wp_kses_post( $content ) ) ) . '</div>';
	if ( '' !== trim( $a['source'] ) ) {
		$html .= '<p class="mg-src">' . esc_html__( 'Zdroj:', 'medialna-gramotnost' ) . ' ' . mg_inline( $a['source'], false ) . '</p>';
	}
	$html .= '</div>';
	return mg_wrap( 'mg_scam', $a, $html, 'mg-scam-info', array(
		'mg-group' => $a['filter_group'],
		'mg-tags'  => str_replace( ',', ' ', $a['filter_tags'] ),
		'mg-scope' => $a['hide_scope'],
	) );
}

function mg_render_filter( $a, $content ) {
	$html = '<div class="mg-filters" role="group">';
	if ( '' !== trim( $a['label'] ) ) {
		$html .= '<span class="mg-label">' . esc_html( $a['label'] ) . '</span>';
	}
	foreach ( mg_pairs( $a['options'] ) as $i => $opt ) {
		$html .= '<button class="mg-chip" type="button" data-filter="' . esc_attr( $opt[0] ) . '" aria-pressed="' . ( 0 === $i ? 'true' : 'false' ) . '">' . esc_html( $opt[1] ? $opt[1] : $opt[0] ) . '</button>';
	}
	$html .= '</div><p class="mg-muted mg-filter-empty" hidden>' . esc_html( $a['empty_text'] ) . '</p>';
	return mg_wrap( 'mg_filter', $a, $html, '', array( 'mg-filter-group' => $a['group'] ) );
}

/* ---------- Karty, kroky, varovania, čísla ---------- */

function mg_render_cards( $a, $content ) {
	$cols = in_array( $a['columns'], array( '2', '3', '4' ), true ) ? $a['columns'] : '4';
	return mg_wrap( 'mg_cards', $a, '<div class="mg-grid mg-g' . $cols . '">' . mg_children( $content ) . '</div>' );
}

function mg_render_card( $a, $content ) {
	$tag   = '' !== trim( $a['link'] ) ? 'a' : 'div';
	$class = 'mg-card' . ( 'danger' === $a['style'] ? ' mg-danger-card' : '' );
	$html  = '<' . $tag . ' class="' . $class . '"' . ( 'a' === $tag ? mg_href( $a['link'] ) : '' ) . '>';
	if ( 'none' !== $a['icon'] && mg_icon( $a['icon'] ) ) {
		$html .= '<span class="mg-ico">' . mg_icon( $a['icon'] ) . '</span>';
	}
	if ( '' !== trim( $a['eyebrow'] ) ) {
		$html .= '<p class="mg-eyebrow">' . esc_html( $a['eyebrow'] ) . '</p>';
	}
	$html .= '<h3>' . mg_inline( $a['title'], false ) . '</h3>';
	if ( '' !== trim( $content ) ) {
		$html .= '<p>' . mg_text( $content ) . '</p>';
	}
	if ( '' !== trim( $a['remember'] ) ) {
		$html .= '<p class="mg-remember mg-remember-small">' . mg_inline( $a['remember'], false ) . '</p>';
	}
	if ( '' !== trim( $a['link_text'] ) && 'a' === $tag ) {
		$html .= '<span class="mg-go">' . esc_html( $a['link_text'] ) . '</span>';
	}
	return $html . '</' . $tag . '>';
}

function mg_render_steps( $a, $content ) {
	mg_ctx_push( 'mg_steps', $a );
	$inner = mg_children( $content );
	mg_ctx_pop( 'mg_steps' );
	if ( 'columns' === $a['layout'] ) {
		return mg_wrap( 'mg_steps', $a, '<div class="mg-rules">' . $inner . '</div>', 'mg-on-dark' );
	}
	return mg_wrap( 'mg_steps', $a, '<ol class="mg-steps' . ( 'red' === $a['layout'] ? ' mg-red' : '' ) . '">' . $inner . '</ol>' );
}

function mg_render_step( $a, $content ) {
	if ( 'columns' === mg_ctx( 'mg_steps', 'layout' ) ) {
		$html = '<div class="mg-rule">';
		if ( '' !== trim( $a['label'] ) ) {
			$html .= '<span class="mg-n">' . esc_html( $a['label'] ) . '</span>';
		}
		return $html . '<h3>' . mg_inline( $a['title'], false ) . '</h3><p>' . mg_text( $content ) . '</p></div>';
	}
	return '<li><div><b>' . mg_inline( $a['title'], false ) . '</b>' . mg_text( $content ) . '</div></li>';
}

function mg_render_warnings( $a, $content ) {
	return mg_wrap( 'mg_warnings', $a, '<div class="mg-warn-list">' . mg_children( $content ) . '</div>' );
}

function mg_render_warning( $a, $content ) {
	$tag = '' !== trim( $a['link'] ) ? 'a' : 'div';
	return '<' . $tag . ' class="mg-warn-item"' . ( 'a' === $tag ? mg_href( $a['link'] ) : '' ) . '><span class="mg-tag mg-red">' . esc_html( $a['tag'] ) . '</span><span><b>' . mg_inline( $a['title'], false ) . '</b><p>' . mg_text( $content ) . '</p></span><span class="mg-go">' . esc_html( $a['link_text'] ) . '</span></' . $tag . '>';
}

function mg_render_stats( $a, $content ) {
	return mg_wrap( 'mg_stats', $a, '<div class="mg-stats">' . mg_children( $content ) . '</div>' );
}

function mg_render_stat( $a, $content ) {
	return '<div class="mg-stat"><strong>' . esc_html( $a['number'] ) . '</strong><p>' . mg_text( $content ) . '</p>' . ( '' !== trim( $a['source'] ) ? '<span class="mg-cite">' . mg_inline( $a['source'], false ) . '</span>' : '' ) . '</div>';
}

function mg_render_callout( $a, $content ) {
	$style = in_array( $a['style'], array( 'warn', 'danger', 'ok' ), true ) ? ' mg-' . $a['style'] : '';
	$html  = '<div class="mg-callout' . $style . ( 'large' === $a['size'] ? ' mg-large' : '' ) . '">';
	if ( '' !== trim( $a['title'] ) ) {
		$html .= '<b>' . mg_inline( $a['title'], false ) . '</b>';
	}
	$html .= '<div class="mg-rich">' . wpautop( do_shortcode( wp_kses_post( $content ) ) ) . '</div></div>';
	return mg_wrap( 'mg_callout', $a, $html );
}

function mg_render_cta( $a, $content ) {
	$html = '<div class="mg-callout mg-cta-band"><div class="mg-stack"><h2>' . mg_inline( $a['title'], false ) . '</h2>' . ( '' !== trim( $a['text'] ) ? '<p class="mg-muted">' . mg_text( $a['text'] ) . '</p>' : '' ) . '</div>';
	if ( '' !== trim( $a['button_text'] ) ) {
		$html .= '<a class="mg-btn"' . mg_href( $a['button_link'] ) . '>' . esc_html( $a['button_text'] ) . '</a>';
	}
	return mg_wrap( 'mg_cta', $a, $html . '</div>' );
}

/* ---------- Trenažér ---------- */

function mg_render_trainer( $a, $content ) {
	$summary = '';
	foreach ( mg_split( $a['summary'] ) as $s ) {
		$summary .= '<li>' . mg_inline( $s, false ) . '</li>';
	}
	$html  = '<div class="mg-t-progress" aria-hidden="true"></div>';
	$html .= '<div class="mg-trainer"><div class="mg-t-stage"></div><div class="mg-t-panel mg-t-side" aria-live="polite"></div></div>';
	$html .= '<div class="mg-t-items">' . mg_children( $content ) . '</div>';
	$html .= '<template class="mg-t-summary"><ul>' . $summary . '</ul>' . ( '' !== trim( $a['more_text'] ) ? '<a class="mg-btn mg-ghost"' . mg_href( $a['more_link'] ) . '>' . esc_html( $a['more_text'] ) . '</a>' : '' ) . '</template>';
	return mg_wrap( 'mg_trainer', $a, $html, 'mg-trainer-wrap', array(
		'question'    => $a['question'],
		'hint'        => $a['hint'],
		'scam'        => $a['scam_label'],
		'safe'        => $a['safe_label'],
		'result-high' => $a['result_high'],
		'result-mid'  => $a['result_mid'],
		'result-low'  => $a['result_low'],
	) );
}

function mg_render_trainer_item( $a, $content ) {
	$a['label']          = '';
	$a['button_text']    = '';
	$a['button_text_on'] = '';
	$html                = '';
	if ( '' !== trim( $a['situation'] ) ) {
		$html .= '<p class="mg-example-label mg-situation">' . mg_inline( $a['situation'], false ) . '</p>';
	}
	$html .= mg_example_block( $a, $content, false );
	return '<div class="mg-t-item" data-verdict="' . esc_attr( 'safe' === $a['verdict'] ? 'safe' : 'scam' ) . '" data-kind="' . esc_attr( $a['kind'] ) . '">' . $html . '</div>';
}

/* ---------- Kontrola správy ---------- */

function mg_render_checker( $a, $content ) {
	$texts = array(
		'high-title' => $a['high_title'],
		'high-text'  => $a['high_text'],
		'mid-title'  => $a['mid_title'],
		'mid-text'   => $a['mid_text'],
		'low-title'  => $a['low_title'],
		'low-text'   => $a['low_text'],
		'empty'      => $a['empty_text'],
	);
	$html  = '<div class="mg-trainer"><div class="mg-q-list">' . mg_children( $content ) . '</div>';
	$html .= '<div class="mg-t-panel mg-ck-panel"><p class="mg-eyebrow">' . esc_html( $a['result_title'] ) . '</p>';
	$html .= '<div class="mg-meter" aria-hidden="true"><i style="left:0%"></i></div>';
	$html .= '<div class="mg-ck-result mg-verdict-box" aria-live="polite"><p class="mg-muted">' . esc_html( $a['empty_text'] ) . '</p></div>';
	$html .= '<button class="mg-btn mg-ghost mg-ck-reset" type="button">' . esc_html( $a['reset_text'] ) . '</button>';
	if ( '' !== trim( $a['note'] ) ) {
		$html .= '<p class="mg-muted mg-small">' . esc_html( $a['note'] ) . '</p>';
	}
	$html .= '</div></div>';
	return mg_wrap( 'mg_checker', $a, $html, 'mg-checker', $texts );
}

function mg_render_checker_question( $a, $content ) {
	$w = max( 1, min( 5, (int) $a['weight'] ) );
	return '<div class="mg-q" data-w="' . $w . '"' . ( 'yes' === $a['hard'] ? ' data-hard="1"' : '' ) . '><p>' . mg_inline( $a['question'], false ) . '</p><div class="mg-yn"><button type="button" data-v="1" aria-pressed="false">' . esc_html__( 'Áno', 'medialna-gramotnost' ) . '</button><button type="button" data-v="0" aria-pressed="false">' . esc_html__( 'Nie', 'medialna-gramotnost' ) . '</button></div></div>';
}

/* ---------- Kontrola adresy ---------- */

function mg_render_url_checker( $a, $content ) {
	static $n = 0;
	$n++;
	$id    = 'mg-url-' . $n;
	$html  = '<div class="mg-trainer"><div class="mg-url-tool"><label for="' . $id . '" class="mg-eyebrow">' . esc_html( $a['input_label'] ) . '</label>';
	$html .= '<input id="' . $id . '" class="mg-url-input" type="text" inputmode="url" autocomplete="off" spellcheck="false" value="' . esc_attr( $a['default_url'] ) . '">';
	$ex    = mg_pairs( $a['examples'] );
	if ( $ex ) {
		$html .= '<div class="mg-filters"><span class="mg-label">' . esc_html__( 'Skúste:', 'medialna-gramotnost' ) . '</span>';
		foreach ( $ex as $e ) {
			$html .= '<button class="mg-chip" type="button" data-url="' . esc_attr( $e[1] ? $e[1] : $e[0] ) . '">' . esc_html( $e[0] ) . '</button>';
		}
		$html .= '</div>';
	}
	$html .= '</div><div class="mg-stack"><div class="mg-url-out" aria-live="polite"></div><div class="mg-stack mg-url-notes"></div>';
	if ( '' !== trim( $a['tip'] ) ) {
		$html .= '<div class="mg-callout"><p>' . mg_text( $a['tip'] ) . '</p></div>';
	}
	$html .= '</div></div>';
	return mg_wrap( 'mg_url_checker', $a, $html, 'mg-url-checker' );
}

/* ---------- Rozhodovací strom ---------- */

function mg_render_help_tree( $a, $content ) {
	return mg_wrap( 'mg_help_tree', $a, '<div class="mg-tree-opts" role="group"></div><div class="mg-tree-items">' . mg_children( $content ) . '</div>', 'mg-tree' );
}

function mg_render_help_option( $a, $content ) {
	static $n = 0;
	$n++;
	$steps = '';
	foreach ( mg_lines( $content ) as $line ) {
		$bits   = array_map( 'trim', explode( '|', $line, 2 ) );
		$steps .= '<li><div><b>' . mg_inline( $bits[0], false ) . '</b>' . mg_inline( isset( $bits[1] ) ? $bits[1] : '', false ) . '</div></li>';
	}
	$html  = '<div class="mg-tree-item" id="mg-tree-' . $n . '">';
	$html .= '<button type="button" class="mg-tree-btn" aria-pressed="false"><b>' . mg_inline( $a['title'], false ) . '</b><span>' . mg_inline( $a['subtitle'], false ) . '</span></button>';
	$html .= '<div class="mg-tree-answer mg-stack"><h2>' . mg_inline( $a['heading'], false ) . '</h2><ol class="mg-steps' . ( 'red' === $a['style'] ? ' mg-red' : '' ) . '">' . $steps . '</ol>';
	if ( '' !== trim( $a['button_text'] ) ) {
		$html .= '<a class="mg-btn mg-ghost"' . mg_href( $a['button_link'] ) . ' style="justify-self:start">' . esc_html( $a['button_text'] ) . '</a>';
	}
	return $html . '</div></div>';
}

/* ---------- Kontakty ---------- */

function mg_render_contacts( $a, $content ) {
	return mg_wrap( 'mg_contacts', $a, '<div class="mg-contacts">' . mg_children( $content ) . '</div>' );
}

function mg_render_contact( $a, $content ) {
	return '<div class="mg-contact"><span class="mg-eyebrow">' . esc_html( $a['label'] ) . '</span><span class="mg-num' . ( 'yes' === $a['small'] ? ' mg-small' : '' ) . '">' . esc_html( $a['number'] ) . '</span><p>' . mg_text( $content ) . '</p></div>';
}

/* ---------- Kvíz ---------- */

function mg_render_quiz( $a, $content ) {
	return mg_wrap( 'mg_quiz', $a, '<div class="mg-quiz" aria-live="polite"></div><div class="mg-quiz-items" hidden>' . mg_children( $content ) . '</div>', 'mg-quiz-wrap', array(
		'prompt' => $a['prompt'],
		'label'  => $a['item_label'],
		'next'   => $a['next_text'],
		'result' => $a['result_text'],
	) );
}

function mg_render_quiz_question( $a, $content ) {
	$answers = mg_split( $a['answers'] );
	$correct = max( 1, (int) $a['correct'] ) - 1;
	$html    = '<div class="mg-quiz-q" data-correct="' . (int) $correct . '"><blockquote>' . mg_text( $content ) . '</blockquote><div class="mg-answers">';
	foreach ( $answers as $i => $ans ) {
		$html .= '<button type="button" data-i="' . (int) $i . '">' . esc_html( $ans ) . '</button>';
	}
	return $html . '</div><p class="mg-quiz-ex" hidden>' . mg_text( $a['explanation'] ) . '</p></div>';
}

/* ---------- Slovník ---------- */

function mg_render_glossary( $a, $content ) {
	static $n = 0;
	$n++;
	$html = '';
	if ( 'yes' === $a['search'] ) {
		$id    = 'mg-gloss-' . $n;
		$html .= '<div class="mg-gloss-search"><label for="' . $id . '" class="mg-eyebrow">' . esc_html( $a['search_label'] ) . '</label><input id="' . $id . '" class="mg-search-input" type="search" placeholder="' . esc_attr( $a['placeholder'] ) . '" autocomplete="off"></div>';
		$html .= '<p class="mg-muted mg-gloss-empty" hidden>' . esc_html( $a['empty_text'] ) . '</p>';
	}
	$html .= '<div class="mg-gloss">' . mg_children( $content ) . '</div>';
	return mg_wrap( 'mg_glossary', $a, $html, 'mg-stack-l' );
}

function mg_render_term( $a, $content ) {
	return '<details><summary>' . esc_html( $a['term'] ) . '</summary><p>' . mg_text( $content ) . '</p></details>';
}

/* ---------- Rýchle karty ---------- */

function mg_render_lessons( $a, $content ) {
	mg_ctx_push( 'mg_lessons', $a );
	$inner = mg_children( $content );
	mg_ctx_pop( 'mg_lessons' );
	$html = '';
	if ( '' !== trim( $a['print_button'] ) ) {
		$html .= '<div class="mg-btn-row mg-no-print"><button class="mg-btn mg-print-btn" type="button" hidden>' . esc_html( $a['print_button'] ) . '</button></div>';
	}
	$html .= '<div class="mg-lessons' . ( '1' === $a['columns'] ? ' mg-one' : '' ) . '">' . $inner . '</div>';
	return mg_wrap( 'mg_lessons', $a, $html, 'mg-stack-l' );
}

function mg_render_lesson( $a, $content ) {
	$looks_label    = mg_ctx( 'mg_lessons', 'looks_label', 'Ako to vyzerá:' );
	$remember_label = mg_ctx( 'mg_lessons', 'remember_label', 'Zapamätaj si:' );

	$col = function ( $title, $style, $items ) {
		$items = mg_split( $items );
		if ( ! $items && '' === trim( $title ) ) {
			return '';
		}
		$out = '<div class="' . ( 'plain' === $style ? '' : 'mg-' . esc_attr( $style ) ) . '">';
		if ( '' !== trim( $title ) ) {
			$out .= '<h3>' . esc_html( $title ) . '</h3>';
		}
		$out .= '<ul>';
		foreach ( $items as $it ) {
			$out .= '<li>' . mg_inline( $it, false ) . '</li>';
		}
		return $out . '</ul></div>';
	};

	$html  = '<article class="mg-lesson"' . ( '' !== trim( $a['anchor'] ) ? ' id="' . esc_attr( $a['anchor'] ) . '"' : '' ) . ' data-mg-group="' . esc_attr( $a['filter_group'] ) . '" data-mg-tags="' . esc_attr( str_replace( ',', ' ', $a['filter_tags'] ) ) . '" data-mg-scope="element">';
	$html .= '<div class="mg-lesson-top"><span class="mg-lesson-no">' . esc_html( $a['number'] ) . '</span>' . ( '' !== trim( $a['tag'] ) ? '<span class="mg-tag' . ( 'yes' === $a['tag_red'] ? ' mg-red' : '' ) . '">' . esc_html( $a['tag'] ) . '</span>' : '' ) . '</div>';
	$html .= '<h2>' . mg_inline( $a['title'], false ) . '</h2>';
	if ( '' !== trim( $a['looks'] ) ) {
		$html .= '<p class="mg-looks" data-label="' . esc_attr( $looks_label ) . '">' . mg_text( $a['looks'] ) . '</p>';
	}
	$cols = $col( $a['col1_title'], $a['col1_style'], $a['col1'] ) . $col( $a['col2_title'], $a['col2_style'], $a['col2'] );
	if ( $cols ) {
		$html .= '<div class="mg-lesson-cols">' . $cols . '</div>';
	}
	if ( '' !== trim( $a['note'] ) ) {
		$html .= '<p class="mg-muted mg-small">' . mg_text( $a['note'] ) . '</p>';
	}
	if ( '' !== trim( $a['remember'] ) ) {
		$html .= '<p class="mg-remember" data-label="' . esc_attr( $remember_label ) . '">' . mg_inline( $a['remember'], false ) . '</p>';
	}
	if ( '' !== trim( $a['question'] ) ) {
		$html .= '<details><summary>' . esc_html( $a['question'] ) . '</summary><p>' . mg_text( $a['answer'] ) . '</p></details>';
	}
	if ( '' !== trim( $a['link_text'] ) ) {
		$html .= '<a class="mg-more"' . mg_href( $a['link'] ) . '>' . esc_html( $a['link_text'] ) . '</a>';
	}
	return $html . '</article>';
}

/* ---------- Kartička do peňaženky ---------- */

function mg_render_wallet( $a, $content ) {
	$html = '<div class="mg-wallet"><h2>' . mg_inline( $a['title'], false ) . '</h2><ol>';
	foreach ( mg_split( $a['rules'] ) as $r ) {
		$html .= '<li>' . mg_inline( $r, false ) . '</li>';
	}
	$html .= '</ol><div class="mg-nums">';
	foreach ( mg_pairs( $a['numbers'] ) as $p ) {
		$html .= '<div><b>' . esc_html( $p[0] ) . '</b><span>' . esc_html( $p[1] ) . '</span></div>';
	}
	$html .= '</div><div class="mg-stack" style="gap:10px">';
	foreach ( mg_split( $a['fields'] ) as $f ) {
		$html .= '<div><span class="mg-muted mg-small">' . esc_html( $f ) . '</span><div class="mg-fill"></div></div>';
	}
	if ( '' !== trim( $a['note'] ) ) {
		$html .= '<p class="mg-muted mg-small">' . esc_html( $a['note'] ) . '</p>';
	}
	return mg_wrap( 'mg_wallet', $a, $html . '</div></div>' );
}

/* ---------- Zdroje, lišta, písmo ---------- */

function mg_render_sources( $a, $content ) {
	return mg_wrap( 'mg_sources', $a, '<ul class="mg-sources-list">' . mg_children( $content ) . '</ul>' );
}

function mg_render_source( $a, $content ) {
	$html = '<li><b>' . mg_inline( $a['title'], false ) . '</b>';
	if ( '' !== trim( $content ) ) {
		$html .= '<span class="mg-muted">' . mg_text( $content ) . '</span>';
	}
	if ( '' !== trim( $a['link'] ) ) {
		$html .= '<a' . mg_href( $a['link'] ) . '>' . esc_html( $a['link_text'] ? $a['link_text'] : $a['link'] ) . '</a>';
	}
	return $html . '</li>';
}

function mg_render_alert_bar( $a, $content ) {
	$link = '' !== trim( $a['link_text'] ) && '' !== trim( $a['link'] ) ? '<a' . mg_href( $a['link'] ) . '>' . esc_html( $a['link_text'] ) . '</a>' : '';
	return mg_wrap( 'mg_alert_bar', $a, '<div class="mg-alert-in"><span>' . mg_inline( $a['text'], false ) . '</span>' . $link . '</div>', 'mg-alert-bar' );
}

function mg_render_a11y( $a, $content ) {
	$label = '' !== trim( $a['label'] ) ? '<span class="mg-muted">' . esc_html( $a['label'] ) . '</span>' : '';
	return mg_wrap( 'mg_a11y', $a, $label . '<button type="button" class="mg-size-btn" aria-label="' . esc_attr__( 'Zväčšiť písmo', 'medialna-gramotnost' ) . '">A+</button>', 'mg-a11y' );
}

/* ---------- Záložné rozloženie (bez Avada Builderu) ---------- */

function mg_render_section( $atts, $content ) {
	$a     = shortcode_atts( array( 'style' => '', 'class' => '', 'id' => '' ), $atts, 'mg_section' );
	$style = in_array( $a['style'], array( 'alt', 'dark' ), true ) ? ' mg-' . $a['style'] : '';
	$id    = $a['id'] ? ' id="' . esc_attr( $a['id'] ) . '"' : '';
	return '<section class="mg mg-section alignwide' . $style . ' ' . esc_attr( $a['class'] ) . '"' . $id . '><div class="mg-section-in">' . mg_children( $content ) . '</div></section>';
}

function mg_render_cols( $atts, $content ) {
	$a = shortcode_atts( array( 'count' => '2' ), $atts, 'mg_cols' );
	return '<div class="mg-cols" style="--mg-cols:' . max( 1, min( 4, (int) $a['count'] ) ) . '">' . mg_children( $content ) . '</div>';
}

function mg_render_col( $atts, $content ) {
	return '<div class="mg-col">' . mg_children( $content ) . '</div>';
}
