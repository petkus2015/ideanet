<?php
/**
 * Slovník pojmov s vyhľadávaním.
 *
 * @package MedialnaGramotnostTema
 */

get_header();
mgt_page_head( 'Slovník pojmov', 'Phishing, smishing, deepfake a ďalšie slová, ktoré počujete v správach, vysvetlené jednoducho.', 'Slovník' );
?>
<div class="mgt-wrap">
	<div class="mg mg-el-glossary mg-stack-l">
		<div class="mg-gloss-search"><label for="mgt-gloss" class="mgt-label">Hľadať pojem</label><input id="mgt-gloss" class="mg-search-input" type="search" placeholder="napr. phishing" autocomplete="off"></div>
		<p class="mg-muted mg-gloss-empty" hidden>Tento pojem v slovníku zatiaľ nemáme.</p>
		<div class="mg-gloss">
			<?php
			while ( have_posts() ) {
				the_post();
				echo '<details id="' . esc_attr( get_post_field( 'post_name' ) ) . '"><summary>' . esc_html( get_the_title() ) . '</summary><div>' . wp_kses_post( apply_filters( 'the_content', get_the_content() ) ) . '</div></details>';
			}
			?>
		</div>
	</div>
</div>
<?php
get_footer();
