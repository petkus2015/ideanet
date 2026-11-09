<?php
/**
 * Plávajúca výzva na ďalší zaujímavý článok z tej istej kategórie (mobil, posledná tretina čítania).
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vyberie ďalší článok z hlavnej kategórie: spomedzi najnovších vyhráva ten, ktorý čitatelia najviac ukladajú.
 */
function relife_next_article_id( int $post_id ): int {
	$cache = 'relife_next_' . $post_id;
	$hit   = get_transient( $cache );
	if ( false !== $hit ) {
		return (int) $hit;
	}
	$section = relife_primary_section( $post_id );
	$args    = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'post__not_in'        => array( $post_id ),
		'posts_per_page'      => 8,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
		'orderby'             => 'date',
		'order'               => 'DESC',
	);
	if ( $section instanceof WP_Term ) {
		$args['cat'] = $section->term_id;
	}
	$ids = get_posts( $args );
	if ( ! $ids && isset( $args['cat'] ) ) {
		unset( $args['cat'] );
		$ids = get_posts( $args );
	}
	$best = 0;
	$top  = -1;
	foreach ( $ids as $id ) {
		$n = (int) get_post_meta( (int) $id, 'relife_saves', true );
		if ( $n > $top ) {
			$top  = $n;
			$best = (int) $id;
		}
	}
	set_transient( $cache, $best, 10 * MINUTE_IN_SECONDS );
	return $best;
}

function relife_next_article_markup(): void {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$next = relife_next_article_id( (int) get_queried_object_id() );
	if ( ! $next ) {
		return;
	}
	$sec   = relife_primary_section( $next );
	$color = function_exists( 'relife_section_color' ) ? relife_section_color( $sec ) : '#3CFFD0';
	?>
	<aside class="rl-next js-next" role="complementary" aria-label="<?php esc_attr_e( 'Ďalší zaujímavý článok', 'relife' ); ?>" data-post="<?php echo (int) get_queried_object_id(); ?>" hidden>
		<a class="rl-next__link" href="<?php echo esc_url( (string) get_permalink( $next ) ); ?>">
			<span class="rl-next__media" style="background:<?php echo esc_attr( $color ); ?>">
				<?php if ( has_post_thumbnail( $next ) ) { echo get_the_post_thumbnail( $next, 'relife-card-s', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '64px' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</span>
			<span class="rl-next__txt">
				<small><?php esc_html_e( 'Ďalší zaujímavý článok', 'relife' ); ?><?php echo $sec instanceof WP_Term ? ' · <b style="color:' . esc_attr( $color ) . '">' . esc_html( $sec->name ) . '</b>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></small>
				<strong><?php echo esc_html( get_the_title( $next ) ); ?></strong>
			</span>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
		</a>
		<button type="button" class="rl-next__close js-next-close" aria-label="<?php esc_attr_e( 'Zavrieť', 'relife' ); ?>">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</aside>
	<?php
}
add_action( 'wp_footer', 'relife_next_article_markup', 5 );

/**
 * Odporúčané články z hlavnej kategórie (n kusov): spomedzi najnovších vyhrávajú tie, ktoré čitatelia najviac ukladajú.
 *
 * @return int[]
 */
function relife_related_ids( int $post_id, int $n = 3 ): array {
	$cache = 'relife_rel_' . $post_id . '_' . $n;
	$hit   = get_transient( $cache );
	if ( is_array( $hit ) ) {
		return array_map( 'intval', $hit );
	}
	$section = relife_primary_section( $post_id );
	$args    = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'post__not_in'        => array( $post_id ),
		'posts_per_page'      => 12,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
		'orderby'             => 'date',
		'order'               => 'DESC',
	);
	if ( $section instanceof WP_Term ) {
		$args['cat'] = $section->term_id;
	}
	$ids = get_posts( $args );
	$pos = array_flip( $ids );
	usort(
		$ids,
		static function ( $a, $b ) use ( $pos ): int {
			$sa = (int) get_post_meta( (int) $a, 'relife_saves', true );
			$sb = (int) get_post_meta( (int) $b, 'relife_saves', true );
			return $sb <=> $sa ?: $pos[ $a ] <=> $pos[ $b ];
		}
	);
	$ids = array_slice( array_map( 'intval', $ids ), 0, $n );
	set_transient( $cache, $ids, 10 * MINUTE_IN_SECONDS );
	return $ids;
}
