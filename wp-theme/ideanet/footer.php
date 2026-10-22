<?php
/**
 * Pätička a prehrávač na celú obrazovku.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="footer">
	<div class="wrap footer__inner">
		<?php ideanet_brand( is_front_page() ? '#top' : home_url( '/' ) ); ?>
		<p><?php echo wp_kses( ideanet_tokens( ideanet_opt( 'footer_text' ) ), ideanet_kses() ); ?></p>
		<nav class="footer__links" aria-label="<?php esc_attr_e( 'Pätička', 'ideanet' ); ?>">
			<?php
			$footer_links = ideanet_menu_items(
				'footer',
				array(
					'#portfolio' => __( 'Portfólio', 'ideanet' ),
					'#vizualy'   => __( 'Vizuály', 'ideanet' ),
					'#cennik'    => __( 'Cenník', 'ideanet' ),
					'#podmienky' => __( 'Podmienky', 'ideanet' ),
					'#kontakt'   => __( 'Kontakt', 'ideanet' ),
				)
			);
			foreach ( $footer_links as $link ) {
				printf( '<a href="%1$s">%2$s</a>', esc_url( $link['url'] ), esc_html( $link['label'] ) );
			}
			?>
		</nav>
		<?php ideanet_socials_html(); ?>
	</div>
</footer>

<div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Prehrávač videa', 'ideanet' ); ?>">
	<button class="lightbox__close" id="lbClose" aria-label="<?php esc_attr_e( 'Zavrieť', 'ideanet' ); ?>">
		<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
	</button>
	<button class="lightbox__nav lightbox__nav--prev" id="lbPrev" aria-label="<?php esc_attr_e( 'Predchádzajúce video', 'ideanet' ); ?>">
		<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</button>
	<figure class="lightbox__stage">
		<div class="lightbox__frame" id="lbFrame"></div>
		<figcaption class="lightbox__meta">
			<h3 id="lbTitle"></h3>
			<p id="lbDesc"></p>
			<span class="tags" id="lbTags"></span>
		</figcaption>
	</figure>
	<button class="lightbox__nav lightbox__nav--next" id="lbNext" aria-label="<?php esc_attr_e( 'Ďalšie video', 'ideanet' ); ?>">
		<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</button>
</div>

<?php wp_footer(); ?>
</body>
</html>
