<?php
/**
 * Pätička.
 *
 * @package MedialnaGramotnostTema
 */
?>
</main>

<footer class="mgt-footer">
	<div class="mgt-wrap">
		<div class="mgt-footer-grid">
			<div class="mgt-footer-about">
				<p class="mgt-footer-name"><?php bloginfo( 'name' ); ?></p>
				<p><?php echo wp_kses( mgt_mod( 'footer_text' ), mgt_inline_tags() ); ?></p>
			</div>
			<div class="mgt-footer-contacts">
				<p class="mgt-footer-h">Keď potrebujete pomoc</p>
				<dl>
					<?php foreach ( mgt_contacts() as $c ) : ?>
						<div><dt><?php echo esc_html( $c[0] ); ?></dt><dd><?php echo esc_html( $c[1] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			</div>
			<nav class="mgt-footer-nav" aria-label="Menu v pätičke">
				<p class="mgt-footer-h">Na webe</p>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => 'mgt_menu_fallback',
					)
				);
				?>
			</nav>
		</div>
		<p class="mgt-footer-bottom">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
