<?php
/**
 * Pomocné funkcie pre šablóny.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prezývky kategórií: ak na webe kategória nemá slug z RELIFE zoznamu (napr. „zdravie“ namiesto „health“),
 * nájde sa podľa názvu/slugu. Rozšíriteľné filtrom `relife_section_aliases`.
 *
 * @return array<string, string[]>
 */
function relife_section_aliases(): array {
	return (array) apply_filters(
		'relife_section_aliases',
		array(
			'trendy' => array( 'trend', 'trendy', 'trendy-a-kultura', 'kultura' ),
			'tech'   => array( 'technologie', 'technologia', 'tech-news', 'technika', 'it' ),
			'health' => array( 'zdravie', 'zdravi', 'zdravie-a-fitness', 'zdravy-zivot', 'fitness' ),
			'money'  => array( 'peniaze', 'financie', 'finance', 'osobne-financie', 'investovanie', 'ekonomika' ),
			'travel' => array( 'cestovanie', 'cestovatel', 'cestovatelstvo' ),
			'style'  => array( 'styl', 'lifestyle', 'moda', 'dizajn' ),
			'life'   => array( 'zivot', 'zivotny-styl', 'osobny-rozvoj' ),
		)
	);
}

/**
 * Kategória, ktorá zodpovedá sekcii RELIFE (tech, health, money, …). Preferuje presný slug, ak v ňom sú články,
 * inak nájde kategóriu s prezývkou alebo slugom s číslom (health-2) a s najviac článkami.
 */
function relife_section_term( string $slug ): ?WP_Term {
	static $map = null;
	if ( null === $map ) {
		$map   = array();
		$all   = get_categories( array( 'hide_empty' => false ) );
		$aliases = relife_section_aliases();
		$norm  = static fn( string $v ): string => strtolower( remove_accents( $v ) );
		foreach ( array_keys( relife_sections() ) as $sec ) {
			$cands = array_merge( array( $sec ), array_map( $norm, $aliases[ $sec ] ?? array() ) );
			$exact = null;
			$best  = null;
			foreach ( $all as $t ) {
				if ( $t->slug === $sec ) {
					$exact = $t;
					continue;
				}
				$slug_n = $norm( $t->slug );
				$name_n = sanitize_title( $norm( $t->name ) );
				$hit    = in_array( $slug_n, $cands, true ) || in_array( $name_n, $cands, true ) || (bool) preg_match( '/^' . preg_quote( $sec, '/' ) . '-\d+$/', $slug_n );
				if ( $hit && ( null === $best || $t->count > $best->count ) ) {
					$best = $t;
				}
			}
			if ( $exact instanceof WP_Term && ( $exact->count > 0 || null === $best ) ) {
				$map[ $sec ] = $exact;
			} else {
				$map[ $sec ] = $best;
			}
		}
	}
	$t = $map[ $slug ] ?? null;
	return $t instanceof WP_Term ? $t : null;
}

/**
 * Slug sekcie RELIFE, do ktorej kategória patrí (alebo prázdny reťazec).
 */
function relife_term_section_slug( WP_Term $term ): string {
	foreach ( array_keys( relife_sections() ) as $sec ) {
		$t = relife_section_term( $sec );
		if ( $t instanceof WP_Term && $t->term_id === $term->term_id ) {
			return $sec;
		}
	}
	return '';
}

/**
 * Hlavná sekcia (kategória) článku. Prednosť má sekcia z RELIFE zoznamu.
 */
function relife_primary_section( int $post_id = 0 ): ?WP_Term {
	$post_id = $post_id > 0 ? $post_id : (int) get_the_ID();
	$terms   = get_the_category( $post_id );
	if ( empty( $terms ) ) {
		return null;
	}
	$known = array_keys( relife_sections() );
	foreach ( $terms as $term ) {
		if ( in_array( $term->slug, $known, true ) || '' !== relife_term_section_slug( $term ) ) {
			return $term;
		}
	}
	$default = (int) get_option( 'default_category' );
	foreach ( $terms as $term ) {
		if ( $term->term_id !== $default ) {
			return $term;
		}
	}
	return null;
}

/**
 * Farba sekcie: najprv term meta, potom predvolená podľa slugu.
 */
function relife_section_color( ?WP_Term $term ): string {
	if ( null === $term ) {
		return '#3CFFD0';
	}
	$custom = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'relife_color', true ) );
	if ( is_string( $custom ) && '' !== $custom ) {
		return $custom;
	}
	$sections = relife_sections();
	$sec      = isset( $sections[ $term->slug ] ) ? $term->slug : relife_term_section_slug( $term );
	return $sections[ $sec ]['color'] ?? '#3CFFD0';
}

/**
 * Názov formátu článku (QUICK TAKE, EXPLAINED …).
 */
function relife_format_label( int $post_id = 0 ): string {
	$post_id = $post_id > 0 ? $post_id : (int) get_the_ID();
	$terms   = get_the_terms( $post_id, 'relife_format' );
	if ( ! is_array( $terms ) || empty( $terms ) ) {
		return '';
	}
	$formats = relife_formats();
	$term    = $terms[0];
	return $formats[ $term->slug ]['name'] ?? mb_strtoupper( $term->name );
}

/**
 * Odhadovaný čas čítania v minútach.
 */
function relife_reading_time( int $post_id = 0 ): int {
	$post_id = $post_id > 0 ? $post_id : (int) get_the_ID();
	$content = (string) get_post_field( 'post_content', $post_id );
	$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ), 0, 'áäčďéíĺľňóôŕšťúýžÁÄČĎÉÍĹĽŇÓÔŔŠŤÚÝŽ' );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Tlačidlo „Uložiť článok" (stav rieši JavaScript v prehliadači).
 */
function relife_save_button( int $post_id, string $class = '' ): void {
	printf(
		'<button type="button" class="rl-save js-save %1$s" data-id="%2$d" data-url="%3$s" aria-pressed="false" aria-label="%4$s"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-6-4-6 4z"/></svg></button>',
		esc_attr( $class ),
		$post_id,
		esc_url( (string) get_permalink( $post_id ) ),
		esc_attr__( 'Uložiť článok', 'relife' )
	);
}

/**
 * Karta článku. Prijíma ID príspevku a voliteľné nastavenia.
 *
 * @param array{size?:string,save?:bool} $args
 */
function relife_card( int $post_id, array $args = array() ): void {
	$args    = wp_parse_args( $args, array( 'size' => 'card', 'save' => true ) );
	$section = relife_primary_section( $post_id );
	$color   = relife_section_color( $section );
	$name    = $section ? $section->name : '';
	$slugs   = array();
	foreach ( get_the_category( $post_id ) as $c ) {
		$slugs[] = $c->slug;
	}
	$format = relife_format_label( $post_id );
	?>
	<div class="rl-card js-card" data-id="<?php echo (int) $post_id; ?>" data-sections="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>">
		<a class="rl-card__link" href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>">
			<span class="rl-card__media" style="background:<?php echo esc_attr( $color ); ?>">
				<?php if ( has_post_thumbnail( $post_id ) ) : ?>
					<?php echo get_the_post_thumbnail( $post_id, 'relife-card', array( 'loading' => 'lazy', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<span class="rl-card__initial" aria-hidden="true"><?php echo esc_html( mb_substr( $name, 0, 1 ) ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $name ) : ?>
					<span class="rl-tag rl-tag--dark"><?php echo esc_html( $name ); ?></span>
				<?php endif; ?>
			</span>
			<h3 class="rl-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
			<span class="rl-meta">
				<?php echo esc_html( implode( ' · ', array_filter( array( $format, sprintf( /* translators: %d minutes */ __( '%d min', 'relife' ), relife_reading_time( $post_id ) ) ) ) ) ); ?>
			</span>
		</a>
		<?php
		if ( $args['save'] ) {
			relife_save_button( $post_id );
		}
		?>
	</div>
	<?php
}

/**
 * Ďalšie články do bočných zoznamov (len text).
 */
function relife_list_item( int $post_id ): void {
	$format = relife_format_label( $post_id );
	?>
	<a class="rl-list__item" href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>">
		<?php if ( '' !== $format ) : ?>
			<span class="rl-list__format"><?php echo esc_html( $format ); ?></span>
		<?php endif; ?>
		<span class="rl-list__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
	</a>
	<?php
}

/**
 * Do nadpisov h2 v článku pridá kotvy (pre obsah článku).
 */
function relife_heading_anchors( string $content ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$used = array();
	return (string) preg_replace_callback(
		'#<h2([^>]*)>(.*?)</h2>#is',
		static function ( array $m ) use ( &$used ): string {
			if ( preg_match( '/\sid=/', $m[1] ) ) {
				return $m[0];
			}
			$base = sanitize_title( wp_strip_all_tags( $m[2] ) );
			$base = '' !== $base ? $base : 'sekcia';
			$id   = $base;
			$n    = 2;
			while ( in_array( $id, $used, true ) ) {
				$id = $base . '-' . $n;
				++$n;
			}
			$used[] = $id;
			return '<h2' . $m[1] . ' id="' . esc_attr( $id ) . '">' . $m[2] . '</h2>';
		},
		$content
	);
}
add_filter( 'the_content', 'relife_heading_anchors', 9 );

/**
 * Obsah článku z nadpisov h2.
 *
 * @return array<int, array{id:string,text:string}>
 */
function relife_toc( string $content ): array {
	$items = array();
	if ( preg_match_all( '#<h2[^>]*\sid="([^"]+)"[^>]*>(.*?)</h2>#is', $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $m ) {
			$items[] = array( 'id' => $m[1], 'text' => wp_strip_all_tags( $m[2] ) );
		}
	}
	return $items;
}

/**
 * Štruktúrované dáta (schema.org Article).
 */
function relife_schema_json_ld(): void {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	$data = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'Article',
		'headline'         => get_the_title( $post ),
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'author'           => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', (int) $post->post_author ) ),
		'publisher'        => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) ),
		'mainEntityOfPage' => get_permalink( $post ),
	);
	if ( has_post_thumbnail( $post ) ) {
		$data['image'] = get_the_post_thumbnail_url( $post, 'relife-hero' );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_head', 'relife_schema_json_ld' );

/**
 * Aktívna anketa (najnovšia s označením „Aktívna").
 */
function relife_active_poll(): ?WP_Post {
	$q = new WP_Query(
		array(
			'post_type'      => 'relife_poll',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'relife_poll_status',
					'value'   => 'closed',
					'compare' => '!=',
				),
			),
		)
	);
	return $q->have_posts() ? $q->posts[0] : null;
}

/**
 * Obchodná logika hero: pripnutý príspevok, inak najnovší.
 */
function relife_hero_post(): ?WP_Post {
	$sticky = get_option( 'sticky_posts' );
	$args   = array(
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( is_array( $sticky ) && ! empty( $sticky ) ) {
		$args['post__in'] = array_map( 'intval', $sticky );
		$args['orderby']  = 'date';
	}
	$q = new WP_Query( $args );
	return $q->have_posts() ? $q->posts[0] : null;
}

/**
 * Príspevky podľa formátu.
 *
 * @return array<int, int> ID príspevkov.
 */
function relife_posts_by_format( string $slug, int $count, array $exclude = array() ): array {
	$q = new WP_Query(
		array(
			'posts_per_page'      => $count,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
			'post__not_in'        => $exclude,
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array( 'taxonomy' => 'relife_format', 'field' => 'slug', 'terms' => $slug ),
			),
		)
	);
	/** @var array<int,int> $ids */
	$ids = array_map( 'intval', $q->posts );
	return $ids;
}

/**
 * Príspevky podľa sekcie.
 *
 * @return array<int, int> ID príspevkov.
 */
function relife_posts_by_section( string $slug, int $count, array $exclude = array() ): array {
	$q = new WP_Query(
		array(
			'posts_per_page'      => $count,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
			'post__not_in'        => $exclude,
			'cat'                 => ( relife_section_term( $slug ) instanceof WP_Term ) ? relife_section_term( $slug )->term_id : 0,
			'category_name'       => ( relife_section_term( $slug ) instanceof WP_Term ) ? '' : $slug,
		)
	);
	/** @var array<int,int> $ids */
	$ids = array_map( 'intval', $q->posts );
	return $ids;
}

/**
 * Stránkovanie v štýle RELIFE (vycentrované, tlačidlá Predchádzajúca / číslo / Ďalšia).
 */
function relife_pagination(): void {
	$arrow_l = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
	$arrow_r = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'end_size'           => 1,
			'prev_text'          => $arrow_l . '<span class="rl-pg__label">' . esc_html__( 'Späť', 'relife' ) . '</span>',
			'next_text'          => '<span class="rl-pg__label">' . esc_html__( 'Ďalej', 'relife' ) . '</span>' . $arrow_r,
			'screen_reader_text' => __( 'Stránkovanie', 'relife' ),
			'aria_label'         => __( 'Stránkovanie', 'relife' ),
		)
	);
}
