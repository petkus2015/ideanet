<?php
/**
 * Karta vedľa hlavného článku: anketa a výber nálady.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$poll = relife_active_poll();
?>
<aside class="rl-sidecard js-sidecard" aria-label="<?php esc_attr_e( 'Anketa a nálada', 'relife' ); ?>">
	<div class="rl-tabs" role="tablist">
		<button type="button" role="tab" class="rl-tabs__btn is-active js-tab-poll" aria-selected="true"><?php esc_html_e( 'Anketa', 'relife' ); ?></button>
		<button type="button" role="tab" class="rl-tabs__btn js-tab-mood" aria-selected="false"><?php esc_html_e( 'Nálada', 'relife' ); ?></button>
	</div>

	<div class="rl-panel js-panel-poll">
		<?php if ( $poll instanceof WP_Post ) : ?>
			<div class="rl-poll js-poll" data-poll="<?php echo (int) $poll->ID; ?>">
				<div class="rl-poll__head">
					<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 20V10M12 20V4M19 20v-7"/></svg>
					<div>
						<div class="rl-poll__kicker"><?php esc_html_e( 'RELIFE HLASUJE', 'relife' ); ?></div>
					</div>
				</div>
				<h2 class="rl-poll__q"><?php echo esc_html( get_the_title( $poll ) ); ?></h2>
				<div class="rl-poll__opts">
					<?php foreach ( relife_poll_options( $poll->ID ) as $i => $opt ) : ?>
						<button type="button" class="rl-opt js-opt" data-index="<?php echo (int) $i; ?>" data-pct="<?php echo (int) $opt['pct']; ?>" aria-pressed="false">
							<span class="rl-opt__row">
								<span class="rl-opt__radio" aria-hidden="true"><i></i></span>
								<span class="rl-opt__label"><?php echo esc_html( $opt['label'] ); ?></span>
								<span class="rl-opt__pct js-pct"></span>
							</span>
							<span class="rl-opt__bar"><span class="js-bar"></span></span>
						</button>
					<?php endforeach; ?>
				</div>
				<p class="rl-poll__note js-poll-note"><?php esc_html_e( 'Výsledky sa zobrazia po tvojom hlasovaní.', 'relife' ); ?></p>
				<a class="rl-outline-link" href="<?php echo esc_url( relife_page_url_by_template( 'template-polls.php' ) ); ?>">
					<?php esc_html_e( 'Pozrieť staršie ankety', 'relife' ); ?> <span aria-hidden="true">→</span>
				</a>
			</div>
		<?php else : ?>
			<p class="rl-muted"><?php esc_html_e( 'Zatiaľ nie je aktívna žiadna anketa.', 'relife' ); ?></p>
			<a class="rl-outline-link" href="<?php echo esc_url( relife_page_url_by_template( 'template-polls.php' ) ); ?>"><?php esc_html_e( 'Pozrieť staršie ankety', 'relife' ); ?> <span aria-hidden="true">→</span></a>
		<?php endif; ?>
	</div>

	<div class="rl-panel js-panel-mood" hidden>
		<div class="rl-poll__kicker"><?php esc_html_e( 'TVOJA NÁLADA', 'relife' ); ?></div>
		<h2 class="rl-poll__q"><?php esc_html_e( 'Akú máš dnes náladu?', 'relife' ); ?></h2>
		<p class="rl-muted"><?php esc_html_e( 'Vyber si a upravíme ti výber článkov a kategórií.', 'relife' ); ?></p>
		<div class="rl-moods">
			<?php foreach ( relife_moods() as $mood ) : ?>
				<button type="button" class="rl-mood js-mood" data-mood="<?php echo esc_attr( $mood['id'] ); ?>" aria-pressed="false">
					<span><?php echo esc_html( $mood['label'] ); ?></span>
					<span class="rl-mood__hint"><?php echo esc_html( strtoupper( implode( ' · ', $mood['sections'] ) ) ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
		<p class="rl-poll__note"><?php esc_html_e( 'Výber si pamätá len tvoj prehliadač.', 'relife' ); ?></p>
	</div>
</aside>
