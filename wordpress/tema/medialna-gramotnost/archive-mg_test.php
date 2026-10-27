<?php
/**
 * Zoznam testov.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
mgt_page_head( 'Testy', 'Vyskúšajte si, či spoznáte podvod a manipuláciu. Po každej otázke uvidíte vysvetlenie.', 'Testy' );
?>
<div class="mgt-wrap">
	<ul class="mgt-testlist">
		<?php
		while ( have_posts() ) :
			the_post();
			$mgt_n    = count( mgt_test_questions( get_the_ID() ) );
			$mgt_type = 'kviz' === mgt_meta( get_the_ID(), 'test_type' ) ? 'Kvíz' : 'Podvod, alebo nie?';
			?>
			<li>
				<a href="<?php the_permalink(); ?>">
					<span class="mgt-testlist-meta"><?php echo esc_html( $mgt_type . ' · ' . $mgt_n . ' ' . ( 1 === $mgt_n ? 'otázka' : ( $mgt_n < 5 ? 'otázky' : 'otázok' ) ) ); ?></span>
					<span class="mgt-testlist-title"><?php the_title(); ?></span>
					<?php if ( has_excerpt() ) : ?>
						<span class="mgt-testlist-text"><?php echo esc_html( get_the_excerpt() ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endwhile; ?>
	</ul>
</div>
<?php
get_footer();
