<?php
/**
 * Stránka sa nenašla.
 *
 * Nestačí povedať, že tu nič nie je — človek sa odtiaľto musí vedieť
 * dostať ďalej. Preto hľadanie, odkazy na hlavné sekcie a kontakt.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

get_header();

$odkazy = ideanet_menu_items( 'primary', ideanet_default_sections() );
$email  = ideanet_opt( 'contact_email' );
?>
<main id="top" class="section lost">
	<div class="wrap">
		<p class="eyebrow reveal"><span class="dot"></span> <?php echo esc_html( ideanet_opt( 'e404_eyebrow' ) ); ?></p>
		<h1 class="reveal"><?php ideanet_text( 'e404_title' ); ?></h1>
		<p class="lead reveal"><?php ideanet_text( 'e404_text' ); ?></p>

		<form class="lost__search reveal" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<div class="field">
				<label for="lost-s"><?php echo esc_html( ideanet_opt( 'e404_search' ) ); ?></label>
				<input type="search" id="lost-s" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( ideanet_opt( 'e404_search_ph' ) ); ?>">
			</div>
			<button class="btn btn--ghost" type="submit"><?php echo esc_html( ideanet_opt( 'e404_search_btn' ) ); ?></button>
		</form>

		<?php if ( $odkazy ) : ?>
			<p class="lost__sub reveal"><?php echo esc_html( ideanet_opt( 'e404_links_title' ) ); ?></p>
			<ul class="lost__links reveal" role="list">
				<?php foreach ( $odkazy as $odkaz ) : ?>
					<li><a href="<?php echo esc_url( $odkaz['url'] ); ?>"><?php echo esc_html( $odkaz['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="lost__cta reveal">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( ideanet_opt( 'e404_cta' ) ); ?></a>
			<?php if ( $email ) : ?>
				<a class="btn btn--ghost" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
