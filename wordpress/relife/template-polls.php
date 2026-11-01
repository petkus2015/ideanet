<?php
/**
 * Template Name: Staršie ankety
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$closed = new WP_Query(
	array(
		'post_type'      => 'relife_poll',
		'posts_per_page' => 24,
		'no_found_rows'  => true,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array( 'key' => 'relife_poll_status', 'value' => 'closed' ),
		),
	)
);
$active = relife_active_poll();
$secs   = relife_sections();
?>
<section class="rl-wrap rl-block">
	<p class="rl-kicker"><?php esc_html_e( 'RELIFE HLASUJE', 'relife' ); ?></p>
	<h1 class="rl-pagetitle"><?php esc_html_e( 'Staršie ankety', 'relife' ); ?></h1>
	<p class="rl-muted rl-lead"><?php esc_html_e( 'Uzavreté ankety a ich výsledky. Pozri, čo si myslia ostatní čitatelia.', 'relife' ); ?></p>

	<?php if ( $active instanceof WP_Post ) : ?>
		<a class="rl-activepoll" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span>
				<span class="rl-kicker rl-kicker--ink"><?php esc_html_e( 'AKTÍVNA ANKETA', 'relife' ); ?></span>
				<span class="rl-activepoll__q"><?php echo esc_html( get_the_title( $active ) ); ?></span>
			</span>
			<span class="rl-pill rl-pill--dark"><?php esc_html_e( 'Hlasovať →', 'relife' ); ?></span>
		</a>
	<?php endif; ?>

	<div class="rl-filters js-poll-filters" role="group" aria-label="<?php esc_attr_e( 'Filter ankiet', 'relife' ); ?>">
		<button type="button" class="rl-filter is-active" data-filter="all" aria-pressed="true"><?php esc_html_e( 'Všetky', 'relife' ); ?></button>
		<?php foreach ( $secs as $slug => $s ) : ?>
			<button type="button" class="rl-filter" data-filter="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><?php echo esc_html( $s['name'] ); ?></button>
		<?php endforeach; ?>
	</div>

	<div class="rl-grid rl-grid--2 js-polls">
		<?php while ( $closed->have_posts() ) : $closed->the_post(); ?>
			<?php
			$pid     = (int) get_the_ID();
			$slug    = (string) get_post_meta( $pid, 'relife_poll_section', true );
			$options = relife_poll_options( $pid );
			$max     = $options ? max( array_column( $options, 'pct' ) ) : 0;
			$total   = array_sum( array_column( $options, 'votes' ) );
			$color   = $secs[ $slug ]['color'] ?? 'var(--rl-accent)';
			?>
			<article class="rl-pollcard js-pollcard" data-section="<?php echo esc_attr( $slug ); ?>">
				<div class="rl-pollcard__top">
					<?php if ( isset( $secs[ $slug ] ) ) : ?><span class="rl-tag" style="background:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $secs[ $slug ]['name'] ); ?></span><?php endif; ?>
					<span class="rl-muted"><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( sprintf( /* translators: %s number of votes */ __( '%s hlasov', 'relife' ), number_format_i18n( $total ) ) ); ?></span>
				</div>
				<h2><?php the_title(); ?></h2>
				<?php foreach ( $options as $opt ) : ?>
					<div class="rl-result<?php echo $opt['pct'] === $max ? ' is-win' : ''; ?>">
						<div class="rl-result__row"><span><?php echo esc_html( $opt['label'] ); ?></span><span><?php echo (int) $opt['pct']; ?> %</span></div>
						<div class="rl-result__bar"><span style="width:<?php echo (int) $opt['pct']; ?>%"></span></div>
					</div>
				<?php endforeach; ?>
			</article>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>
	<?php if ( ! $closed->have_posts() ) : ?>
		<p class="rl-muted"><?php esc_html_e( 'Zatiaľ nemáme uzavreté ankety.', 'relife' ); ?></p>
	<?php endif; ?>
</section>
<?php
get_footer();
