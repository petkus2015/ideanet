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

$places   = relife_places_data();
$maps_url = (string) get_theme_mod( 'relife_places_maps_url', 'https://maps.app.goo.gl/JgE1WtDw7LtKG1dD8?g_st=i' );
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

get_header();
?>
<section class="rl-places-hero">
	<div class="rl-wrap">
		<p class="rl-kicker"><?php esc_html_e( 'BRATISLAVA · JEDLO', 'relife' ); ?></p>
		<h1 class="rl-pagetitle"><?php the_title(); ?></h1>
		<p class="rl-muted rl-lead"><?php esc_html_e( 'Overené podniky v Bratislave, kam sa oplatí zájsť na jedlo. Vyber si kategóriu alebo cenu a nájdeš to pravé miesto.', 'relife' ); ?></p>
		<?php if ( '' !== $maps_url ) : ?>
			<a class="rl-pill rl-pill--outline" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Celý zoznam v Google Mapách ↗', 'relife' ); ?></a>
		<?php endif; ?>
	</div>
</section>

<section class="rl-wrap rl-block rl-places js-places" data-sort="<?php echo $any_rating ? 'rating' : 'name'; ?>">
	<?php if ( $places ) : ?>
		<div class="rl-places__chips" role="group" aria-label="<?php esc_attr_e( 'Kategórie', 'relife' ); ?>">
			<button type="button" class="rl-chip is-on js-places-cat" data-cat="" aria-pressed="true"><?php esc_html_e( 'Všetko', 'relife' ); ?> <small><?php echo (int) count( $places ); ?></small></button>
			<?php foreach ( $cat_counts as $slug => $c ) : ?>
				<button type="button" class="rl-chip js-places-cat" data-cat="<?php echo esc_attr( $slug ); ?>" aria-pressed="false" style="--c:<?php echo esc_attr( $cat_color[ $slug ] ); ?>"><?php echo esc_html( $c['name'] ); ?> <small><?php echo (int) $c['n']; ?></small></button>
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
							<div class="rl-place__tags"><?php foreach ( $pl['cats'] as $t ) : ?><span class="rl-place__tag"><?php echo esc_html( $t->name ); ?></span><?php endforeach; ?></div>
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
				<p class="rl-muted"><?php esc_html_e( 'Admin: podniky pridáte v menu Podniky (jedlo) → Pridať podnik, alebo hromadne cez Nástroje → Import podnikov.', 'relife' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
<?php
get_footer();
