<?php
/**
 * Template Name: Kam na jedlo
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$city_slug  = (string) get_post_meta( get_the_ID(), '_rl_places_city', true );
$city       = '' !== $city_slug ? get_term_by( 'slug', $city_slug, 'relife_place_city' ) : null;
$city_name  = $city instanceof WP_Term ? $city->name : '';
$places     = relife_places_data( $city_slug );
$maps_url   = $city instanceof WP_Term ? (string) get_term_meta( $city->term_id, 'rl_maps_url', true ) : '';
$all_cities = relife_places_cities();
$palette  = array( '#FF3D8B', '#3CFFD0', '#FFE14D', '#8F5CFF', '#FF7A2B', '#A6FF4D' );

// Kategórie, ktoré sa naozaj používajú, s počtom podnikov.
$cat_counts = array();
foreach ( $places as $pl ) {
	foreach ( $pl['cats'] as $t ) {
		$cat_counts[ $t->slug ] = array( 'name' => $t->name, 'n' => ( $cat_counts[ $t->slug ]['n'] ?? 0 ) + 1 );
	}
}
uasort( $cat_counts, static fn( $a, $b ): int => $b['n'] <=> $a['n'] );
$has_price = (bool) array_filter( $places, static fn( $p ): bool => $p['price'] > 0 );
$any_rating = (bool) array_filter( $places, static fn( $p ): bool => '' !== $p['rating'] );
$cat_color = array();
$ci        = 0;
foreach ( array_keys( $cat_counts ) as $slug ) {
	$cat_color[ $slug ] = $palette[ $ci++ % count( $palette ) ];
}

/**
 * Jednoduché vektorové ikony (obrys, currentColor) podľa názvu kategórie.
 */
$cat_icon = static function ( string $name ): string {
	$p = array(
		'cup'    => '<path d="M4 9h12v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M16 10h1.5a2.5 2.5 0 0 1 0 5H16"/><path d="M8 3v3M12 3v3"/>',
		'bowl'   => '<path d="M3 12h18a9 9 0 0 1-18 0z"/><path d="M8 4c0 1.5 1 1.5 1 3M13 4c0 1.5 1 1.5 1 3"/>',
		'cocktl' => '<path d="M4 5h16l-8 9z"/><path d="M12 14v6M8 20h8"/>',
		'beer'   => '<path d="M6 7h9v13H6z"/><path d="M15 10h2.5a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5H15"/><path d="M6 7c0-2 2-3 4-2 1-1.5 4-1 5 2"/>',
		'wine'   => '<path d="M8 3h8l-.5 6a3.5 3.5 0 0 1-7 0z"/><path d="M12 12.5V20M8.5 20h7"/>',
		'pizza'  => '<path d="M3 7c5-3 13-3 18 0L12 21z"/><circle cx="10" cy="10" r="1"/><circle cx="14.5" cy="11" r="1"/><circle cx="12" cy="15" r="1"/>',
		'cutl'   => '<path d="M7 3v6a2 2 0 0 0 4 0V3M9 11v10"/><path d="M17 3c-2 2-2.5 5-2.5 8H17v10"/>',
		'noodle' => '<path d="M4 12h16a8 8 0 0 1-16 0z"/><path d="M9 3l4 8M13 2l4 9"/>',
		'sushi'  => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.5"/>',
		'burger' => '<path d="M4 10a8 6 0 0 1 16 0z"/><path d="M3 13.5h18"/><path d="M4 17h16v1a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/>',
		'bread'  => '<path d="M5 11a4 4 0 0 1 3-6h8a4 4 0 0 1 3 6v8H5z"/><path d="M9 12v3M12 12v3M15 12v3"/>',
		'cake'   => '<path d="M4 12h16v8H4z"/><path d="M4 15.5c2 1.5 4-1.5 8 0s6 1.5 8 0"/><path d="M12 5v4"/>',
		'leaf'   => '<path d="M5 19c0-9 5-14 14-14 0 9-5 14-14 14z"/><path d="M5 19c3-5 6-8 10-10"/>',
		'sun'    => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6L7 7M17 17l1.4 1.4M18.4 5.6L17 7M7 17l-1.4 1.4"/>',
		'cone'   => '<path d="M7 11h10l-5 10z"/><path d="M7 11a5 5 0 0 1 10 0"/>',
		'taco'   => '<path d="M3 17a9 9 0 0 1 18 0z"/><path d="M8 13l1 1M12 11l1 1M15 14l1 1"/>',
		'fish'   => '<path d="M3 12c3-5 9-6 14-2l4-3v10l-4-3c-5 4-11 3-14-2z"/><circle cx="8" cy="11" r=".6"/>',
		'grid'   => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>',
		'pin'    => '<path d="M12 21s-6-5.5-6-11a6 6 0 0 1 12 0c0 5.5-6 11-6 11z"/><circle cx="12" cy="10" r="2.2"/>',
	);
	$map = array(
		'vsetko' => 'grid', 'kaviaren' => 'cup', 'kava' => 'cup', 'bistro' => 'bowl', 'bar' => 'cocktl', 'pub' => 'beer', 'pivo' => 'beer', 'vino' => 'wine',
		'pizza' => 'pizza', 'talian' => 'cutl', 'azijsk' => 'noodle', 'sushi' => 'sushi', 'burg' => 'burger', 'pekaren' => 'bread', 'cukrar' => 'cake',
		'zdrav' => 'leaf', 'vegan' => 'leaf', 'ranajk' => 'sun', 'zmrzlin' => 'cone', 'street' => 'taco', 'mexick' => 'taco', 'ryb' => 'fish',
		'obed' => 'cutl', 'restaur' => 'cutl', 'steak' => 'cutl', 'maso' => 'cutl', 'slovensk' => 'cutl',
	);
	$n   = remove_accents( mb_strtolower( $name ) );
	$key = 'pin';
	foreach ( $map as $k => $icon ) {
		if ( false !== strpos( $n, $k ) ) {
			$key = $icon;
			break;
		}
	}
	return '<svg class="rl-ic" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p[ $key ] . '</svg>';
};

get_header();
?>
<section class="rl-places-hero">
	<div class="rl-wrap">
		<p class="rl-kicker"><?php echo esc_html( ( '' !== $city_name ? mb_strtoupper( $city_name ) : __( 'SLOVENSKO', 'relife' ) ) . ' · ' . __( 'JEDLO', 'relife' ) ); ?></p>
		<h1 class="rl-pagetitle"><?php the_title(); ?></h1>
		<p class="rl-muted rl-lead"><?php esc_html_e( 'Overené podniky, kam sa oplatí zájsť na jedlo. Vyber si kategóriu alebo cenu a nájdeš to pravé miesto.', 'relife' ); ?></p>
		<?php if ( count( $all_cities ) > 1 ) : ?>
			<div class="rl-places__chips rl-places__cities" role="navigation" aria-label="<?php esc_attr_e( 'Mesto', 'relife' ); ?>">
				<?php foreach ( $all_cities as $c ) : $cp = (int) get_term_meta( $c->term_id, 'rl_page_id', true ); if ( ! $cp || 'publish' !== get_post_status( $cp ) ) { continue; } ?>
					<a class="rl-chip<?php echo $c->slug === $city_slug ? ' is-on' : ''; ?>" href="<?php echo esc_url( (string) get_permalink( $cp ) ); ?>"<?php echo $c->slug === $city_slug ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $maps_url ) : ?>
			<a class="rl-pill rl-pill--outline" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Celý zoznam v Google Mapách ↗', 'relife' ); ?></a>
		<?php endif; ?>
	</div>
</section>

<section class="rl-wrap rl-block rl-places js-places" data-sort="<?php echo $any_rating ? 'rating' : 'name'; ?>">
	<?php if ( $places ) : ?>
		<div class="rl-places__chips" role="group" aria-label="<?php esc_attr_e( 'Kategórie', 'relife' ); ?>">
			<button type="button" class="rl-chip is-on js-places-cat" data-cat="" aria-pressed="true"><span class="rl-chip__ic"><?php echo $cat_icon( 'vsetko' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php esc_html_e( 'Všetko', 'relife' ); ?> <small><?php echo (int) count( $places ); ?></small></button>
			<?php foreach ( $cat_counts as $slug => $c ) : ?>
				<button type="button" class="rl-chip js-places-cat" data-cat="<?php echo esc_attr( $slug ); ?>" aria-pressed="false" style="--c:<?php echo esc_attr( $cat_color[ $slug ] ); ?>"><span class="rl-chip__ic"><?php echo $cat_icon( $c['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php echo esc_html( $c['name'] ); ?> <small><?php echo (int) $c['n']; ?></small></button>
			<?php endforeach; ?>
		</div>
		<?php if ( $has_price ) : ?>
			<div class="rl-places__chips rl-places__chips--price" role="group" aria-label="<?php esc_attr_e( 'Cena', 'relife' ); ?>">
				<span class="rl-places__label"><?php esc_html_e( 'Cena', 'relife' ); ?></span>
				<button type="button" class="rl-chip is-on js-places-price" data-price="" aria-pressed="true"><?php esc_html_e( 'Všetky', 'relife' ); ?></button>
				<?php foreach ( array( 1 => '€', 2 => '€€', 3 => '€€€', 4 => '€€€€' ) as $k => $lbl ) : ?>
					<button type="button" class="rl-chip js-places-price" data-price="<?php echo (int) $k; ?>" aria-pressed="false"><?php echo esc_html( $lbl ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<p class="rl-places__count" aria-live="polite"><span class="js-places-n"><?php echo (int) count( $places ); ?></span> <?php esc_html_e( 'podnikov', 'relife' ); ?></p>

		<div class="rl-grid rl-grid--3 rl-places__grid js-places-grid">
			<?php foreach ( $places as $i => $pl ) : ?>
				<?php
				$slugs = array_map( static fn( $t ): string => $t->slug, $pl['cats'] );
				$first = $pl['cats'] ? $pl['cats'][0]->slug : '';
				$color = $first && isset( $cat_color[ $first ] ) ? $cat_color[ $first ] : $palette[ $i % count( $palette ) ];
				$r     = '' !== $pl['rating'] ? (float) $pl['rating'] : 0.0;
				?>
				<article class="rl-place js-place" style="--c:<?php echo esc_attr( $color ); ?>" data-name="<?php echo esc_attr( mb_strtolower( $pl['name'] ) ); ?>" data-cats="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>" data-rating="<?php echo esc_attr( (string) $r ); ?>" data-price="<?php echo (int) $pl['price']; ?>">
					<div class="rl-place__media">
						<?php if ( '' !== $pl['thumb'] ) { echo $pl['thumb']; } else { // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="rl-place__initial" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $pl['name'], 0, 1 ) ) ); ?></span>
						<?php } ?>
						<?php if ( $r > 0 ) : ?><span class="rl-place__rating" aria-label="<?php echo esc_attr( sprintf( /* translators: %s rating */ __( 'Hodnotenie %s z 5', 'relife' ), number_format_i18n( $r, 1 ) ) ); ?>">★ <?php echo esc_html( number_format_i18n( $r, 1 ) ); ?></span><?php endif; ?>
					</div>
					<div class="rl-place__body">
						<?php if ( $pl['cats'] ) : ?>
							<div class="rl-place__tags"><?php foreach ( $pl['cats'] as $t ) : ?><span class="rl-place__tag"><?php echo $cat_icon( $t->name ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $t->name ); ?></span><?php endforeach; ?></div>
						<?php endif; ?>
						<h2 class="rl-place__name"><?php echo esc_html( $pl['name'] ); ?><?php if ( $pl['price'] > 0 ) : ?> <small class="rl-place__price"><?php echo esc_html( str_repeat( '€', $pl['price'] ) ); ?></small><?php endif; ?></h2>
						<?php if ( '' !== $pl['desc'] ) : ?><p class="rl-place__desc"><?php echo esc_html( $pl['desc'] ); ?></p><?php endif; ?>
						<div class="rl-place__foot">
							<?php if ( '' !== $pl['address'] ) : ?><span class="rl-place__addr"><?php echo esc_html( $pl['address'] ); ?></span><?php else : ?><span></span><?php endif; ?>
							<?php if ( '' !== $pl['url'] ) : ?><a class="rl-place__map" href="<?php echo esc_url( $pl['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Mapa', 'relife' ); ?> ↗</a><?php endif; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<p class="rl-muted rl-places__empty js-places-empty" hidden><?php esc_html_e( 'Pre tento výber sme nič nenašli. Skús inú kategóriu alebo hľadanie.', 'relife' ); ?></p>
	<?php else : ?>
		<div class="rl-places__none">
			<p><strong><?php esc_html_e( 'Zoznam podnikov sa pripravuje.', 'relife' ); ?></strong></p>
			<?php if ( current_user_can( 'edit_pages' ) ) : ?>
				<p class="rl-muted"><?php esc_html_e( 'Admin: zoznam z Google Máp načítate v menu Podniky (jedlo) → Mestá a zoznamy.', 'relife' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
<?php
get_footer();
