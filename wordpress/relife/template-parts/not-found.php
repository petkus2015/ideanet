<?php
/**
 * Obsah stránky 404 (používa 404.php aj šablóna stránky „Stránka 404“).
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$latest = get_posts(
	array(
		'numberposts'         => 4,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'fields'              => 'ids',
		'no_found_rows'       => true,
	)
);
?>
<section class="rl-404">
	<div class="rl-wrap rl-404__inner">
		<p class="rl-404__code" aria-hidden="true">404</p>
		<p class="rl-kicker"><?php esc_html_e( 'STRÁNKA NEEXISTUJE', 'relife' ); ?></p>
		<h1 class="rl-404__title"><?php esc_html_e( 'Tu sme nič nenašli.', 'relife' ); ?></h1>
		<p class="rl-muted rl-lead"><?php esc_html_e( 'Odkaz môže byť starý alebo preklepnutý. Skús hľadanie, vyber si sekciu alebo sa vráť na úvod.', 'relife' ); ?></p>

		<div class="rl-searchbox rl-404__search"><?php get_search_form(); ?></div>

		<ul class="rl-404__sections" aria-label="<?php esc_attr_e( 'Sekcie', 'relife' ); ?>">
			<?php foreach ( relife_sections() as $slug => $s ) : ?>
				<?php
				$term = get_category_by_slug( $slug );
				$url  = $term instanceof WP_Term ? get_category_link( $term ) : '';
				if ( '' === $url || is_wp_error( $url ) ) {
					continue;
				}
				?>
				<li><a class="rl-404__chip" href="<?php echo esc_url( $url ); ?>" style="--c:<?php echo esc_attr( $s['color'] ); ?>"><?php echo esc_html( $s['name'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>

		<a class="rl-pill rl-pill--accent" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Späť na domov', 'relife' ); ?></a>
	</div>
</section>

<?php if ( $latest ) : ?>
<section class="rl-wrap rl-block">
	<div class="rl-sechead"><h2 class="rl-sechead__title"><?php esc_html_e( 'Možno ťa zaujme', 'relife' ); ?></h2></div>
	<div class="rl-grid rl-grid--4">
		<?php
		foreach ( $latest as $pid ) {
			relife_card( (int) $pid );
		}
		?>
	</div>
</section>
<?php endif; ?>
