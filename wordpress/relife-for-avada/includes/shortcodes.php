<?php
/**
 * Shortcody (použiteľné v Avada Builderi cez element Code Block alebo Shortcode).
 *
 * [relife_hero] [relife_sidecard] [relife_quick count="4"] [relife_today count="4"]
 * [relife_explained count="2"] [relife_pillars] [relife_versus] [relife_sections]
 * [relife_formats] [relife_guides count="4"] [relife_polls] [relife_saved] [relife_newsletter]
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Obalí výstup do tmavého bloku .rl-scope.
 */
function rlav_wrap( string $html, string $extra = '' ): string {
	return '<div class="rl-scope ' . esc_attr( $extra ) . '">' . $html . '</div>';
}

function rlav_count( mixed $value, int $default, int $max = 24 ): int {
	$n = (int) $value;
	return $n > 0 ? min( $n, $max ) : $default;
}

/** [relife_sidecard] */
function rlav_sc_sidecard(): string {
	return rlav_wrap( rlav_sidecard_html() );
}
add_shortcode( 'relife_sidecard', 'rlav_sc_sidecard' );

/** [relife_hero] – hlavný článok + anketa/nálada vedľa neho. */
function rlav_sc_hero(): string {
	$hero = rlav_hero_post();
	ob_start();
	echo '<section class="rl-hero"><div class="rl-hero__grid">';
	if ( $hero instanceof WP_Post ) {
		$sec   = rlav_primary_section( $hero->ID );
		$color = rlav_section_color( $sec );
		$fmt   = rlav_format_label( $hero->ID );
		?>
		<a class="rl-herocard" href="<?php echo esc_url( (string) get_permalink( $hero ) ); ?>">
			<span class="rl-herocard__bg" style="background:var(--rl-ultraviolet)">
				<?php if ( has_post_thumbnail( $hero ) ) : ?>
					<?php echo get_the_post_thumbnail( $hero, 'large', array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<span class="rl-herocard__big" aria-hidden="true"><?php echo esc_html( $sec ? mb_substr( $sec->name, 0, 2 ) : 'RE' ); ?></span>
				<?php endif; ?>
			</span>
			<span class="rl-badge"><?php esc_html_e( 'VÝBER REDAKCIE', 'relife-avada' ); ?></span>
			<span class="rl-herocard__body">
				<span class="rl-herocard__title"><?php echo esc_html( get_the_title( $hero ) ); ?></span>
				<span class="rl-herocard__meta">
					<b style="color:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $sec ? $sec->name : '' ); ?></b>
					<span><?php echo esc_html( implode( ' · ', array_filter( array( $fmt, sprintf( /* translators: %d minutes */ __( '%d min čítania', 'relife-avada' ), rlav_reading_time( $hero->ID ) ) ) ) ) ); ?></span>
				</span>
				<span class="rl-herocard__dek"><?php echo esc_html( wp_trim_words( (string) get_the_excerpt( $hero ), 28 ) ); ?></span>
			</span>
		</a>
		<?php
	}
	echo rlav_sidecard_html(); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div></section>';
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_hero', 'rlav_sc_hero' );

/** [relife_quick count="4"] */
function rlav_sc_quick( array|string $atts = array() ): string {
	$a   = shortcode_atts( array( 'count' => 4 ), (array) $atts, 'relife_quick' );
	$ids = rlav_posts_by_format( 'quick-take', rlav_count( $a['count'], 4 ) );
	if ( empty( $ids ) ) {
		return '';
	}
	ob_start();
	?>
	<section class="rl-block">
		<div class="rl-sechead"><h2 class="rl-sechead__title rl-accent">QUICK TAKE</h2><span class="rl-muted"><?php esc_html_e( 'Najnovšie za 60 sekúnd', 'relife-avada' ); ?></span></div>
		<div class="rl-grid rl-grid--4 rl-scroll-m">
			<?php foreach ( $ids as $id ) : ?>
				<?php $qs = rlav_primary_section( $id ); ?>
				<a class="rl-quick" href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>">
					<span class="rl-tag" style="background:<?php echo esc_attr( rlav_section_color( $qs ) ); ?>"><?php echo esc_html( $qs ? $qs->name : '' ); ?></span>
					<span class="rl-quick__title"><?php echo esc_html( get_the_title( $id ) ); ?></span>
					<span class="rl-meta"><?php echo esc_html( sprintf( /* translators: %d minutes */ __( '%d min', 'relife-avada' ), rlav_reading_time( $id ) ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_quick', 'rlav_sc_quick' );

/** [relife_today count="4"] – články podľa nálady. */
function rlav_sc_today( array|string $atts = array() ): string {
	$a     = shortcode_atts( array( 'count' => 4 ), (array) $atts, 'relife_today' );
	$limit = rlav_count( $a['count'], 4, 12 );
	$q     = new WP_Query(
		array(
			'posts_per_page'      => 14,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
		)
	);
	ob_start();
	?>
	<section class="rl-block" id="dnes">
		<div class="rl-sechead">
			<h2 class="rl-sechead__title js-today-title" data-default="<?php esc_attr_e( 'Čo dnes stojí za tvoju pozornosť?', 'relife-avada' ); ?>"><?php esc_html_e( 'Čo dnes stojí za tvoju pozornosť?', 'relife-avada' ); ?></h2>
		</div>
		<div class="rl-moodstatus js-mood-status" hidden>
			<span class="js-mood-text"></span>
			<button type="button" class="rl-pill rl-pill--outline js-mood-clear"><?php esc_html_e( 'Zrušiť výber', 'relife-avada' ); ?></button>
		</div>
		<div class="rl-grid rl-grid--4 js-today" data-limit="<?php echo (int) $limit; ?>">
			<?php foreach ( $q->posts as $id ) { rlav_card( (int) $id ); } ?>
		</div>
		<p class="rl-muted js-today-empty" hidden></p>
	</section>
	<?php
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_today', 'rlav_sc_today' );

/** [relife_explained count="2"] */
function rlav_sc_explained( array|string $atts = array() ): string {
	$a   = shortcode_atts( array( 'count' => 2 ), (array) $atts, 'relife_explained' );
	$ids = rlav_posts_by_format( 'explained', rlav_count( $a['count'], 2, 6 ) );
	if ( empty( $ids ) ) {
		return '';
	}
	ob_start();
	?>
	<section class="rl-block">
		<h2 class="rl-sechead__title"><?php esc_html_e( 'Vysvetlené jednoducho', 'relife-avada' ); ?></h2>
		<div class="rl-grid rl-grid--2">
			<?php foreach ( $ids as $id ) : ?>
				<?php $es = rlav_primary_section( $id ); ?>
				<a class="rl-wide" href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>">
					<span class="rl-wide__media" style="background:<?php echo esc_attr( rlav_section_color( $es ) ); ?>"><?php if ( has_post_thumbnail( $id ) ) { echo get_the_post_thumbnail( $id, 'medium_large', array( 'alt' => '' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="rl-wide__body">
						<span class="rl-tag" style="background:<?php echo esc_attr( rlav_section_color( $es ) ); ?>"><?php echo esc_html( $es ? $es->name : '' ); ?></span>
						<span class="rl-wide__title"><?php echo esc_html( get_the_title( $id ) ); ?></span>
						<span class="rl-meta"><?php echo esc_html( rlav_format_label( $id ) . ' · ' . sprintf( /* translators: %d minutes */ __( '%d min čítania', 'relife-avada' ), rlav_reading_time( $id ) ) ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_explained', 'rlav_sc_explained' );

/** [relife_pillars] – TECH, HEALTH, MONEY. */
function rlav_sc_pillars(): string {
	$sections = rlav_sections();
	ob_start();
	echo '<section class="rl-block"><div class="rl-grid rl-grid--3">';
	foreach ( array( 'tech', 'health', 'money' ) as $slug ) {
		if ( ! isset( $sections[ $slug ] ) ) {
			continue;
		}
		$sec   = $sections[ $slug ];
		$ids   = rlav_posts_by_section( $slug, 4 );
		$lead  = array_shift( $ids );
		$pterm = get_category_by_slug( $slug );
		?>
		<div class="rl-pillar">
			<div class="rl-pillar__bar" style="background:<?php echo esc_attr( $sec['color'] ); ?>"></div>
			<div class="rl-pillar__head"><a href="<?php echo esc_url( $pterm instanceof WP_Term ? (string) get_category_link( $pterm ) : home_url( '/' ) ); ?>"><h3><?php echo esc_html( $sec['name'] ); ?></h3></a></div>
			<p class="rl-muted"><?php echo esc_html( $sec['desc'] ); ?></p>
			<?php
			if ( $lead ) {
				rlav_card( (int) $lead );
			}
			?>
			<div class="rl-list"><?php foreach ( $ids as $id ) { rlav_list_item( (int) $id ); } ?></div>
		</div>
		<?php
	}
	echo '</div></section>';
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_pillars', 'rlav_sc_pillars' );

/** [relife_versus] – najnovší článok vo formáte Versus. */
function rlav_sc_versus(): string {
	$ids = rlav_posts_by_format( 'versus', 1 );
	if ( empty( $ids ) ) {
		return '';
	}
	$id = $ids[0];
	ob_start();
	?>
	<section class="rl-block">
		<div class="rl-versus">
			<div>
				<span class="rl-badge rl-badge--accent">VERSUS</span>
				<h2 class="rl-versus__title"><?php echo esc_html( get_the_title( $id ) ); ?></h2>
				<p class="rl-muted"><?php echo esc_html( wp_trim_words( (string) get_the_excerpt( $id ), 30 ) ); ?></p>
				<a class="rl-pill rl-pill--accent" href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>"><?php esc_html_e( 'Prečítať porovnanie →', 'relife-avada' ); ?></a>
			</div>
			<div class="rl-versus__cards">
				<div class="rl-vs rl-vs--a"><h3>ETF</h3><dl><dt><?php esc_html_e( 'RIZIKO', 'relife-avada' ); ?></dt><dd><?php esc_html_e( 'Vyššie, hodnota kolíše', 'relife-avada' ); ?></dd><dt><?php esc_html_e( 'ČASOVÝ HORIZONT', 'relife-avada' ); ?></dt><dd><?php esc_html_e( 'Skôr roky než mesiace', 'relife-avada' ); ?></dd><dt><?php esc_html_e( 'HODÍ SA NA', 'relife-avada' ); ?></dt><dd><?php esc_html_e( 'Dlhodobé ciele', 'relife-avada' ); ?></dd></dl></div>
				<div class="rl-vs rl-vs--b"><h3><?php esc_html_e( 'Sporiaci účet', 'relife-avada' ); ?></h3><dl><dt><?php esc_html_e( 'RIZIKO', 'relife-avada' ); ?></dt><dd><?php esc_html_e( 'Nízke', 'relife-avada' ); ?></dd><dt><?php esc_html_e( 'ČASOVÝ HORIZONT', 'relife-avada' ); ?></dt><dd><?php esc_html_e( 'Kedykoľvek', 'relife-avada' ); ?></dd><dt><?php esc_html_e( 'HODÍ SA NA', 'relife-avada' ); ?></dt><dd><?php esc_html_e( 'Rezervu a krátke ciele', 'relife-avada' ); ?></dd></dl></div>
			</div>
		</div>
	</section>
	<?php
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_versus', 'rlav_sc_versus' );

/** [relife_sections] – dlaždice všetkých sekcií. */
function rlav_sc_sections(): string {
	ob_start();
	echo '<section class="rl-block"><h2 class="rl-sechead__title">' . esc_html__( 'Objav svoj ďalší krok', 'relife-avada' ) . '</h2><div class="rl-tiles">';
	foreach ( rlav_sections() as $slug => $sec ) {
		$term = get_category_by_slug( $slug );
		?>
		<a class="rl-tile" style="background:<?php echo esc_attr( $sec['color'] ); ?>" href="<?php echo esc_url( $term instanceof WP_Term ? (string) get_category_link( $term ) : home_url( '/' ) ); ?>">
			<span class="rl-tile__initial" aria-hidden="true"><?php echo esc_html( mb_substr( $sec['name'], 0, 1 ) ); ?></span>
			<span class="rl-tile__name"><?php echo esc_html( ucfirst( strtolower( $sec['name'] ) ) ); ?></span>
			<span class="rl-tile__line"><?php echo esc_html( $sec['desc'] ); ?></span>
		</a>
		<?php
	}
	echo '</div></section>';
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_sections', 'rlav_sc_sections' );

/** [relife_formats] – šesť formátov. */
function rlav_sc_formats(): string {
	ob_start();
	echo '<section class="rl-block"><h2 class="rl-sechead__title">' . esc_html__( 'Šesť formátov. Vždy vieš, čo dostaneš.', 'relife-avada' ) . '</h2><div class="rl-grid rl-grid--3 rl-formats">';
	$n = 0;
	foreach ( rlav_formats() as $slug => $f ) {
		++$n;
		$link = get_term_link( $slug, 'relife_format' );
		?>
		<a class="rl-format" href="<?php echo esc_url( is_wp_error( $link ) ? home_url( '/' ) : (string) $link ); ?>">
			<span class="rl-format__top"><span class="rl-badge rl-badge--accent"><?php echo esc_html( $f['name'] ); ?></span><span class="rl-format__no"><?php echo esc_html( sprintf( '%02d', $n ) ); ?></span></span>
			<h3><?php echo esc_html( $f['title'] ); ?></h3>
			<p><?php echo esc_html( $f['desc'] ); ?></p>
		</a>
		<?php
	}
	echo '</div></section>';
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_formats', 'rlav_sc_formats' );

/** [relife_guides count="4"] – Ultimate Guides. */
function rlav_sc_guides( array|string $atts = array() ): string {
	$a   = shortcode_atts( array( 'count' => 4 ), (array) $atts, 'relife_guides' );
	$ids = rlav_posts_by_format( 'ultimate-guide', rlav_count( $a['count'], 4, 12 ) );
	if ( empty( $ids ) ) {
		return '';
	}
	ob_start();
	echo '<section class="rl-block"><h2 class="rl-sechead__title">Ultimate Guides</h2><div class="rl-grid rl-grid--4">';
	foreach ( $ids as $id ) {
		$gs = rlav_primary_section( $id );
		?>
		<a class="rl-guide" style="background:<?php echo esc_attr( rlav_section_color( $gs ) ); ?>" href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>">
			<span class="rl-tag rl-tag--dark"><?php echo esc_html( $gs ? $gs->name : '' ); ?></span>
			<span><span class="rl-guide__title"><?php echo esc_html( get_the_title( $id ) ); ?></span><span class="rl-guide__meta"><?php esc_html_e( 'Pravidelne aktualizované →', 'relife-avada' ); ?></span></span>
		</a>
		<?php
	}
	echo '</div></section>';
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_guides', 'rlav_sc_guides' );

/** [relife_polls] – staršie ankety s výsledkami a filtrom. */
function rlav_sc_polls(): string {
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
	$secs = rlav_sections();
	ob_start();
	?>
	<section class="rl-block">
		<p class="rl-kicker"><?php esc_html_e( 'RELIFE HLASUJE', 'relife-avada' ); ?></p>
		<h2 class="rl-sechead__title"><?php esc_html_e( 'Staršie ankety', 'relife-avada' ); ?></h2>
		<div class="rl-filters js-poll-filters" role="group" aria-label="<?php esc_attr_e( 'Filter ankiet', 'relife-avada' ); ?>">
			<button type="button" class="rl-filter is-active" data-filter="all" aria-pressed="true"><?php esc_html_e( 'Všetky', 'relife-avada' ); ?></button>
			<?php foreach ( $secs as $slug => $s ) : ?>
				<button type="button" class="rl-filter" data-filter="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><?php echo esc_html( $s['name'] ); ?></button>
			<?php endforeach; ?>
		</div>
		<div class="rl-grid rl-grid--2 js-polls">
			<?php while ( $closed->have_posts() ) : $closed->the_post(); ?>
				<?php
				$pid     = (int) get_the_ID();
				$slug    = (string) get_post_meta( $pid, 'relife_poll_section', true );
				$options = rlav_poll_options( $pid );
				$max     = $options ? max( array_column( $options, 'pct' ) ) : 0;
				$total   = array_sum( array_column( $options, 'votes' ) );
				$color   = $secs[ $slug ]['color'] ?? 'var(--rl-accent)';
				?>
				<article class="rl-pollcard js-pollcard" data-section="<?php echo esc_attr( $slug ); ?>">
					<div class="rl-pollcard__top">
						<?php if ( isset( $secs[ $slug ] ) ) : ?><span class="rl-tag" style="background:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $secs[ $slug ]['name'] ); ?></span><?php endif; ?>
						<span class="rl-muted"><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( sprintf( /* translators: %s number of votes */ __( '%s hlasov', 'relife-avada' ), number_format_i18n( $total ) ) ); ?></span>
					</div>
					<h3><?php the_title(); ?></h3>
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
			<p class="rl-muted"><?php esc_html_e( 'Zatiaľ nemáme uzavreté ankety.', 'relife-avada' ); ?></p>
		<?php endif; ?>
	</section>
	<?php
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_polls', 'rlav_sc_polls' );

/** [relife_saved] – uložené články z prehliadača čitateľa. */
function rlav_sc_saved(): string {
	ob_start();
	?>
	<section class="rl-block">
		<p class="rl-kicker"><?php esc_html_e( 'TVOJA KNIŽNICA', 'relife-avada' ); ?></p>
		<h2 class="rl-sechead__title"><?php esc_html_e( 'Uložené', 'relife-avada' ); ?></h2>
		<p class="rl-muted"><?php esc_html_e( 'Články ukladáme len v cache tvojho prehliadača. Nesynchronizujú sa medzi zariadeniami.', 'relife-avada' ); ?></p>
		<div class="rl-grid rl-grid--4 js-saved-list" aria-live="polite"></div>
		<p class="rl-muted js-saved-empty" hidden><?php esc_html_e( 'Zatiaľ nemáš nič uložené. Klepni na záložku pri článku.', 'relife-avada' ); ?></p>
	</section>
	<?php
	return rlav_wrap( (string) ob_get_clean() );
}
add_shortcode( 'relife_saved', 'rlav_sc_saved' );

/** [relife_newsletter] */
function rlav_sc_newsletter(): string {
	$action = (string) rlav_option( 'newsletter_action' );
	ob_start();
	?>
	<div class="rl-newsletter" id="newsletter">
		<h2 class="rl-newsletter__title"><?php esc_html_e( 'RELIFE Weekly', 'relife-avada' ); ?></h2>
		<p><?php esc_html_e( 'Týždenný výber najdôležitejších tém a praktických odporúčaní. Raz týždenne, bez spamu.', 'relife-avada' ); ?></p>
		<form class="rl-newsletter__form" method="post" action="<?php echo esc_url( '' !== $action ? $action : '#newsletter' ); ?>">
			<label class="screen-reader-text" for="rl-email"><?php esc_html_e( 'E-mail', 'relife-avada' ); ?></label>
			<input id="rl-email" type="email" name="email" required placeholder="<?php esc_attr_e( 'Tvoja e-mailová adresa', 'relife-avada' ); ?>" autocomplete="email">
			<button type="submit"><?php esc_html_e( 'Odoberať', 'relife-avada' ); ?></button>
		</form>
	</div>
	<?php
	return rlav_wrap( (string) ob_get_clean(), 'rl-scope--bare' );
}
add_shortcode( 'relife_newsletter', 'rlav_sc_newsletter' );
