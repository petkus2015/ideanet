<?php
/**
 * Šablóna článku: odpoveď v skratke, obsah, zdroje, autor, ďalšie články.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id    = (int) get_the_ID();
	$section    = relife_primary_section( $post_id );
	$color      = relife_section_color( $section );
	$format     = relife_format_label( $post_id );
	$answer     = (string) get_post_meta( $post_id, 'relife_answer', true );
	$sources    = (string) get_post_meta( $post_id, 'relife_sources', true );
	$disclosure = (string) get_post_meta( $post_id, 'relife_disclosure', true );
	$content    = apply_filters( 'the_content', get_the_content() );
	$toc        = relife_toc( (string) $content );
	?>
	<div class="rl-progress" aria-hidden="true"><span class="js-progress"></span></div>

	<article <?php post_class( 'rl-article' ); ?>>
		<header class="rl-wrap rl-article__head">
			<nav class="rl-crumbs" aria-label="<?php esc_attr_e( 'Omrvinková navigácia', 'relife' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Domov', 'relife' ); ?></a>
				<?php if ( $section ) : ?>
					<span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( (string) get_category_link( $section ) ); ?>"><?php echo esc_html( $section->name ); ?></a>
				<?php endif; ?>
			</nav>
			<div class="rl-article__tags">
				<?php if ( $section ) : ?><span class="rl-tag" style="background:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $section->name ); ?></span><?php endif; ?>
				<?php if ( '' !== $format ) : ?><span class="rl-tag rl-tag--accent"><?php echo esc_html( $format ); ?></span><?php endif; ?>
			</div>
			<h1 class="rl-article__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?><p class="rl-article__dek"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			<div class="rl-byline">
				<?php echo get_avatar( (int) get_the_author_meta( 'ID' ), 44, '', '', array( 'class' => 'rl-avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div>
					<div class="rl-byline__name"><?php the_author(); ?></div>
					<div class="rl-muted"><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( sprintf( /* translators: %d minutes */ __( '%d min čítania', 'relife' ), relife_reading_time( $post_id ) ) ); ?></div>
				</div>
				<div class="rl-byline__actions">
					<?php relife_save_button( $post_id, 'rl-save--inline' ); ?>
					<button type="button" class="rl-pill rl-pill--accent js-share" data-title="<?php echo esc_attr( get_the_title() ); ?>" data-url="<?php echo esc_url( (string) get_permalink() ); ?>"><?php esc_html_e( 'Zdieľať', 'relife' ); ?></button>
				</div>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="rl-wrap rl-article__hero">
				<?php the_post_thumbnail( 'relife-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
				<?php if ( get_the_post_thumbnail_caption() ) : ?><figcaption class="rl-muted"><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></figcaption><?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="rl-wrap rl-article__cols">
			<?php $related = relife_related_ids( $post_id, 3 ); ?>
			<?php if ( ! empty( $toc ) || ! empty( $related ) || is_active_sidebar( 'relife-article-left' ) ) : ?>
				<aside class="rl-sidecol" aria-label="<?php esc_attr_e( 'Obsah a odporúčané články', 'relife' ); ?>">
					<?php if ( ! empty( $toc ) ) : ?>
						<nav class="rl-toc" aria-label="<?php esc_attr_e( 'Obsah článku', 'relife' ); ?>">
							<div class="rl-kicker"><?php esc_html_e( 'V ČLÁNKU', 'relife' ); ?></div>
							<ol>
								<?php foreach ( $toc as $item ) : ?>
									<li><a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a></li>
								<?php endforeach; ?>
							</ol>
						</nav>
					<?php endif; ?>
					<?php if ( ! empty( $related ) ) : ?>
						<div class="rl-rel">
							<div class="rl-kicker"><?php echo $section ? esc_html( sprintf( /* translators: %s section name */ __( 'ODPORÚČAME · %s', 'relife' ), $section->name ) ) : esc_html__( 'ODPORÚČAME', 'relife' ); ?></div>
							<ul>
								<?php foreach ( $related as $rid ) : ?>
									<?php $rs = relife_primary_section( $rid ); ?>
									<li>
										<a class="rl-rel__item" href="<?php echo esc_url( (string) get_permalink( $rid ) ); ?>">
											<span class="rl-rel__media" style="background:<?php echo esc_attr( relife_section_color( $rs ) ); ?>">
												<?php if ( has_post_thumbnail( $rid ) ) { echo get_the_post_thumbnail( $rid, 'relife-card-s', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '72px' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
											</span>
											<span class="rl-rel__title"><?php echo esc_html( get_the_title( $rid ) ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
					<?php if ( is_active_sidebar( 'relife-article-left' ) ) : ?>
						<div class="rl-widgets"><?php dynamic_sidebar( 'relife-article-left' ); ?></div>
					<?php endif; ?>
				</aside>
			<?php endif; ?>

			<div class="rl-article__main">
				<?php if ( '' !== $answer ) : ?>
					<div class="rl-answer">
						<div class="rl-kicker"><?php esc_html_e( 'ODPOVEĎ V SKRATKE', 'relife' ); ?></div>
						<p><?php echo esc_html( $answer ); ?></p>
					</div>
				<?php endif; ?>

				<div class="rl-content"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

				<?php
				$share_url   = rawurlencode( (string) get_permalink( $post_id ) );
				$share_title = rawurlencode( wp_strip_all_tags( get_the_title( $post_id ) ) );
				$share_icons = array(
					'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $share_url, '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>' ),
					'x'        => array( 'X', 'https://twitter.com/intent/tweet?url=' . $share_url . '&text=' . $share_title, '<path d="M4 4l16 16M20 4L4 20"/>' ),
					'whatsapp' => array( 'WhatsApp', 'https://wa.me/?text=' . $share_title . '%20' . $share_url, '<path d="M4 20l1.3-4.2A8 8 0 1 1 8.3 18.8L4 20z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 .8c-.8-.4-1.5-1.1-1.9-1.9l.8-1-1-2L9 9z"/>' ),
					'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $share_url, '<rect x="4" y="9" width="3.5" height="11"/><circle cx="5.8" cy="5.5" r="1.8"/><path d="M11 20V9h3.4v1.6c.7-1.2 2-1.9 3.6-1.9 2.6 0 4 1.7 4 4.6V20h-3.5v-6c0-1.4-.6-2.2-1.8-2.2s-2.2.9-2.2 2.4V20H11z"/>' ),
					'email'    => array( 'E-mail', 'mailto:?subject=' . $share_title . '&body=' . $share_url, '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>' ),
				);
				?>
				<div class="rl-share js-share" data-url="<?php echo esc_attr( (string) get_permalink( $post_id ) ); ?>" data-title="<?php echo esc_attr( wp_strip_all_tags( get_the_title( $post_id ) ) ); ?>">
					<span class="rl-share__label"><?php esc_html_e( 'Zdieľaj článok', 'relife' ); ?></span>
					<div class="rl-share__btns">
						<button type="button" class="rl-share__btn rl-share__btn--native js-share-native" hidden>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3M7.5 7.5L12 3l4.5 4.5"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/></svg>
							<span><?php esc_html_e( 'Zdieľať', 'relife' ); ?></span>
						</button>
						<?php foreach ( $share_icons as $k => $sh ) : ?>
							<a class="rl-share__btn" href="<?php echo esc_url( $sh[1], array( 'https', 'mailto' ) ); ?>" <?php echo 'email' === $k ? '' : 'target="_blank" rel="noopener noreferrer"'; ?> aria-label="<?php echo esc_attr( sprintf( /* translators: %s network */ __( 'Zdieľať na %s', 'relife' ), $sh[0] ) ); ?>">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $sh[2]; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg>
								<span><?php echo esc_html( $sh[0] ); ?></span>
							</a>
						<?php endforeach; ?>
						<button type="button" class="rl-share__btn js-share-copy" data-done="<?php esc_attr_e( 'Skopírované', 'relife' ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>
							<span class="js-share-copy-t"><?php esc_html_e( 'Kopírovať odkaz', 'relife' ); ?></span>
						</button>
					</div>
				</div>

				<?php wp_link_pages(); ?>

				<?php if ( '' !== $sources || '' !== $disclosure ) : ?>
					<div class="rl-sources">
						<?php if ( '' !== $sources ) : ?>
							<div class="rl-kicker rl-kicker--ink"><?php esc_html_e( 'ZDROJE', 'relife' ); ?></div>
							<ol>
								<?php foreach ( preg_split( '/\r\n|\r|\n/', $sources ) ?: array() as $line ) : ?>
									<?php if ( '' !== trim( $line ) ) : ?><li><?php echo esc_html( trim( $line ) ); ?></li><?php endif; ?>
								<?php endforeach; ?>
							</ol>
						<?php endif; ?>
						<?php if ( '' !== $disclosure ) : ?>
							<p class="rl-disclosure"><?php echo esc_html( $disclosure ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="rl-author">
					<?php echo get_avatar( (int) get_the_author_meta( 'ID' ), 56, '', '', array( 'class' => 'rl-avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div>
						<div class="rl-byline__name"><?php the_author(); ?></div>
						<div class="rl-muted"><?php echo esc_html( (string) get_the_author_meta( 'description' ) ); ?></div>
					</div>
				</div>

			</div>
		</div>
	</article>

	<?php
	$related = array();
	if ( $section ) {
		$related = relife_posts_by_section( $section->slug, 4, array( $post_id ) );
	}
	if ( ! empty( $related ) ) :
		?>
		<section class="rl-band rl-band--surface">
			<div class="rl-wrap">
				<h2 class="rl-sechead__title"><?php esc_html_e( 'Čítaj ďalej', 'relife' ); ?></h2>
				<div class="rl-grid rl-grid--4">
					<?php foreach ( $related as $id ) { relife_card( (int) $id ); } ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
<?php endwhile; ?>
<?php
get_footer();
