<?php
/**
 * Úvodná stránka: hero + anketa/nálada, Quick Take, dnes, vysvetlené, piliere, versus, sekcie, formáty, guides.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$hero     = relife_hero_post();
$used     = $hero instanceof WP_Post ? array( $hero->ID ) : array();
$sections = relife_sections();
$formats  = relife_formats();

$quick     = relife_posts_by_format( 'quick-take', 4, $used );
$explained = relife_posts_by_format( 'explained', 2, array_merge( $used, $quick ) );

$today_q = new WP_Query(
	array(
		'posts_per_page'      => 14,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
		'post__not_in'        => $used,
	)
);
/** @var array<int,int> $today */
$today = array_map( 'intval', $today_q->posts );

$versus = relife_versus_posts();
$guides = relife_posts_by_format( 'ultimate-guide', 4 );
?>

<section class="rl-hero">
	<div class="rl-wrap rl-hero__grid">
		<?php if ( $hero instanceof WP_Post ) : ?>
			<?php
			$h_sec   = relife_primary_section( $hero->ID );
			$h_color = relife_section_color( $h_sec );
			$h_fmt   = relife_format_label( $hero->ID );
			?>
			<a class="rl-herocard" href="<?php echo esc_url( (string) get_permalink( $hero ) ); ?>">
				<span class="rl-herocard__bg" style="background:var(--rl-ultraviolet)">
					<?php if ( has_post_thumbnail( $hero ) ) : ?>
						<?php echo get_the_post_thumbnail( $hero, 'relife-hero', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php else : ?>
						<span class="rl-herocard__big" aria-hidden="true"><?php echo esc_html( $h_sec ? mb_substr( $h_sec->name, 0, 2 ) : 'RE' ); ?></span>
					<?php endif; ?>
				</span>
				<span class="rl-badge"><?php echo esc_html( relife_home_text( 'hero_badge' ) ); ?></span>
				<span class="rl-herocard__body">
					<h1 class="rl-herocard__title"><?php echo esc_html( get_the_title( $hero ) ); ?></h1>
					<span class="rl-herocard__meta">
						<b style="color:<?php echo esc_attr( $h_color ); ?>"><?php echo esc_html( $h_sec ? $h_sec->name : '' ); ?></b>
						<span><?php echo esc_html( implode( ' · ', array_filter( array( $h_fmt, sprintf( /* translators: %d minutes */ __( '%d min čítania', 'relife' ), relife_reading_time( $hero->ID ) ) ) ) ) ); ?></span>
					</span>
					<span class="rl-herocard__dek"><?php echo esc_html( wp_trim_words( (string) get_the_excerpt( $hero ), 28 ) ); ?></span>
				</span>
			</a>
		<?php endif; ?>

		<?php get_template_part( 'template-parts/poll-card' ); ?>
	</div>
</section>

<?php if ( ! empty( $quick ) ) : ?>
<section class="rl-wrap rl-block">
	<div class="rl-sechead">
		<h2 class="rl-sechead__title rl-accent"><?php echo esc_html( relife_home_text( 'quick_title' ) ); ?></h2>
		<span class="rl-muted"><?php echo esc_html( relife_home_text( 'quick_note' ) ); ?></span>
	</div>
	<div class="rl-grid rl-grid--4 rl-scroll-m">
		<?php foreach ( $quick as $id ) : ?>
			<a class="rl-quick" href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>">
				<?php
				$qs = relife_primary_section( $id );
				?>
				<span class="rl-tag" style="background:<?php echo esc_attr( relife_section_color( $qs ) ); ?>"><?php echo esc_html( $qs ? $qs->name : '' ); ?></span>
				<span class="rl-quick__title"><?php echo esc_html( get_the_title( $id ) ); ?></span>
				<span class="rl-meta"><?php echo esc_html( sprintf( /* translators: %d minutes */ __( '%d min', 'relife' ), relife_reading_time( $id ) ) ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<section class="rl-wrap rl-block rl-light-ish" id="dnes">
	<div class="rl-sechead">
		<h2 class="rl-sechead__title js-today-title" data-default="<?php echo esc_attr( relife_home_text( 'today_title' ) ); ?>" data-mood="<?php echo esc_attr( relife_home_text( 'today_mood' ) ); ?>"><?php echo esc_html( relife_home_text( 'today_title' ) ); ?></h2>
		<?php $posts_page = (int) get_option( 'page_for_posts' ); ?>
		<a class="rl-link" href="<?php echo esc_url( $posts_page > 0 ? (string) get_permalink( $posts_page ) : home_url( '/#dnes' ) ); ?>"><?php echo esc_html( relife_home_text( 'today_link' ) ); ?></a>
	</div>
	<script>(function(){var t=document.querySelector('.js-today-title');if(t&&t.dataset.mood&&document.documentElement.classList.contains('rl-has-mood')){t.textContent=t.dataset.mood}})();</script>
	<div class="rl-moodstatus js-mood-status" hidden>
		<span class="js-mood-text"></span>
		<button type="button" class="rl-pill rl-pill--outline js-mood-clear"><?php esc_html_e( 'Zrušiť výber', 'relife' ); ?></button>
	</div>
	<div class="rl-grid rl-grid--4 js-today" data-limit="4">
		<?php foreach ( $today as $id ) : ?>
			<?php relife_card( $id ); ?>
		<?php endforeach; ?>
	</div>
	<p class="rl-muted js-today-empty" hidden></p>
</section>

<?php if ( ! empty( $explained ) ) : ?>
<section class="rl-wrap rl-block">
	<h2 class="rl-sechead__title"><?php echo esc_html( relife_home_text( 'explained_title' ) ); ?></h2>
	<p class="rl-muted rl-lead"><?php echo esc_html( relife_home_text( 'explained_lead' ) ); ?></p>
	<div class="rl-grid rl-grid--2">
		<?php foreach ( $explained as $id ) : ?>
			<?php $es = relife_primary_section( $id ); ?>
			<a class="rl-wide" href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>">
				<span class="rl-wide__media" style="background:<?php echo esc_attr( relife_section_color( $es ) ); ?>"><?php if ( has_post_thumbnail( $id ) ) { echo get_the_post_thumbnail( $id, 'relife-card', array( 'alt' => '' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="rl-wide__body">
					<span class="rl-tag" style="background:<?php echo esc_attr( relife_section_color( $es ) ); ?>"><?php echo esc_html( $es ? $es->name : '' ); ?></span>
					<span class="rl-wide__title"><?php echo esc_html( get_the_title( $id ) ); ?></span>
					<span class="rl-meta"><?php echo esc_html( relife_format_label( $id ) . ' · ' . sprintf( /* translators: %d minutes */ __( '%d min čítania', 'relife' ), relife_reading_time( $id ) ) ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<section class="rl-wrap rl-block">
	<div class="rl-sechead">
		<h2 class="rl-sechead__title"><?php echo esc_html( relife_home_text( 'pillars_title' ) ); ?></h2>
		<p class="rl-muted rl-sechead__note"><?php echo esc_html( relife_home_text( 'pillars_note' ) ); ?></p>
	</div>
	<div class="rl-grid rl-grid--3">
		<?php foreach ( relife_pillars() as $pillar ) : ?>
			<?php
			$ids  = $pillar['ids'];
			$lead = array_shift( $ids );
			?>
			<div class="rl-pillar">
				<div class="rl-pillar__bar" style="background:<?php echo esc_attr( $pillar['color'] ); ?>"></div>
				<div class="rl-pillar__head">
					<a href="<?php echo esc_url( $pillar['url'] ); ?>"><h3><?php echo esc_html( $pillar['name'] ); ?></h3></a>
				</div>
				<p class="rl-muted"><?php echo esc_html( $pillar['desc'] ); ?></p>
				<?php if ( $lead ) : ?>
					<?php relife_card( (int) $lead, array( 'save' => true ) ); ?>
				<?php elseif ( current_user_can( 'manage_categories' ) ) : ?>
					<p class="rl-muted rl-pillar__empty"><?php echo esc_html( sprintf( /* translators: %s section name */ __( 'Admin: v stĺpci %s zatiaľ nie sú články. Kategóriu vyberiete vo Vzhľad → Prispôsobiť → RELIFE – Úvodná stránka.', 'relife' ), $pillar['name'] ) ); ?></p>
				<?php endif; ?>
				<div class="rl-list">
					<?php foreach ( $ids as $id ) { relife_list_item( (int) $id ); } ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<?php if ( ! empty( $versus ) ) : ?>
<section class="rl-wrap rl-block">
	<div class="rl-versus">
		<div>
			<span class="rl-badge rl-badge--accent"><?php echo esc_html( relife_home_text( 'versus_badge' ) ); ?></span>
			<h2 class="rl-versus__title"><?php echo esc_html( get_the_title( $versus[0] ) ); ?></h2>
			<p class="rl-muted"><?php echo esc_html( wp_trim_words( (string) get_the_excerpt( $versus[0] ), 30 ) ); ?></p>
			<a class="rl-pill rl-pill--accent" href="<?php echo esc_url( (string) get_permalink( $versus[0] ) ); ?>"><?php echo esc_html( relife_home_text( 'versus_button' ) ); ?></a>
		</div>
		<div class="rl-versus__cards">
			<?php foreach ( array( 'a', 'b' ) as $vw ) : ?>
				<?php $vc = relife_versus_card( $vw ); ?>
				<div class="rl-vs rl-vs--<?php echo esc_attr( $vw ); ?>">
					<h3><?php echo esc_html( $vc['title'] ); ?></h3>
					<dl>
						<?php foreach ( $vc['rows'] as $row ) : ?>
							<dt><?php echo esc_html( $row[0] ); ?></dt><dd><?php echo esc_html( $row[1] ); ?></dd>
						<?php endforeach; ?>
					</dl>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="rl-wrap rl-block" id="sekcie">
	<h2 class="rl-sechead__title"><?php echo esc_html( relife_home_text( 'tiles_title' ) ); ?></h2>
	<div class="rl-tiles">
		<?php foreach ( $sections as $slug => $sec ) : ?>
			<?php $term = relife_section_term( $slug ); ?>
			<a class="rl-tile" style="background:<?php echo esc_attr( $sec['color'] ); ?>" href="<?php echo esc_url( $term instanceof WP_Term ? (string) get_category_link( $term ) : home_url( '/' ) ); ?>">
				<span class="rl-tile__initial" aria-hidden="true"><?php echo esc_html( mb_substr( $sec['name'], 0, 1 ) ); ?></span>
				<span class="rl-tile__name"><?php echo esc_html( ucfirst( strtolower( $sec['name'] ) ) ); ?></span>
				<span class="rl-tile__line"><?php echo esc_html( $sec['desc'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<section class="rl-band rl-band--surface">
	<div class="rl-wrap">
		<h2 class="rl-sechead__title"><?php echo esc_html( relife_home_text( 'around_title' ) ); ?></h2>
		<div class="rl-grid rl-grid--4">
			<?php foreach ( array( 'trendy', 'travel', 'style', 'life' ) as $slug ) : ?>
				<?php
				$sec = $sections[ $slug ] ?? null;
				if ( null === $sec ) {
					continue;
				}
				$ids = relife_posts_by_section( $slug, 3 );
				?>
				<div class="rl-col">
					<div class="rl-col__head"><span class="rl-dot" style="background:<?php echo esc_attr( $sec['color'] ); ?>"></span><h3><?php echo esc_html( $sec['name'] ); ?></h3></div>
					<p class="rl-muted"><?php echo esc_html( $sec['desc'] ); ?></p>
					<div class="rl-list"><?php foreach ( $ids as $id ) { relife_list_item( (int) $id ); } ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="rl-band rl-band--black">
	<div class="rl-wrap">
		<h2 class="rl-sechead__title"><?php echo esc_html( relife_home_text( 'formats_title' ) ); ?></h2>
		<div class="rl-grid rl-grid--3 rl-formats">
			<?php $n = 0; foreach ( $formats as $slug => $f ) : ++$n; ?>
				<a class="rl-format" href="<?php echo esc_url( (string) get_term_link( $slug, 'relife_format' ) ); ?>">
					<span class="rl-format__top"><span class="rl-badge rl-badge--accent"><?php echo esc_html( $f['name'] ); ?></span><span class="rl-format__no"><?php echo esc_html( sprintf( '%02d', $n ) ); ?></span></span>
					<h3><?php echo esc_html( $f['title'] ); ?></h3>
					<p><?php echo esc_html( $f['desc'] ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php if ( ! empty( $guides ) ) : ?>
<section class="rl-wrap rl-block">
	<h2 class="rl-sechead__title"><?php echo esc_html( relife_home_text( 'guides_title' ) ); ?></h2>
	<div class="rl-grid rl-grid--4">
		<?php foreach ( $guides as $id ) : ?>
			<?php $gs = relife_primary_section( $id ); ?>
			<a class="rl-guide" style="background:<?php echo esc_attr( relife_section_color( $gs ) ); ?>" href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>">
				<span class="rl-tag rl-tag--dark"><?php echo esc_html( $gs ? $gs->name : '' ); ?></span>
				<span>
					<span class="rl-guide__title"><?php echo esc_html( get_the_title( $id ) ); ?></span>
					<span class="rl-guide__meta"><?php esc_html_e( 'Pravidelne aktualizované →', 'relife' ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
