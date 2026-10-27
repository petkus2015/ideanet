<?php
/**
 * Hlavička.
 *
 * @package MedialnaGramotnostTema
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="mgt-skip" href="#obsah">Preskočiť na obsah</a>

<?php if ( mgt_mod( 'alert_show' ) ) : ?>
	<div class="mgt-alert">
		<div class="mgt-wrap">
			<p><?php echo wp_kses( mgt_mod( 'alert_text' ), mgt_inline_tags() ); ?></p>
			<?php
			$mgt_help = mgt_link( mgt_mod( 'alert_link' ), mgt_page_url( 'stalo-sa-mi-to' ) );
			if ( $mgt_help && mgt_mod( 'alert_link_text' ) ) :
				?>
				<a href="<?php echo esc_url( $mgt_help ); ?>"><?php echo esc_html( mgt_mod( 'alert_link_text' ) ); ?> <span aria-hidden="true">→</span></a>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<header class="mgt-header">
	<div class="mgt-wrap mgt-header-in">
		<div class="mgt-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="mgt-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="mgt-wordmark-name"><?php bloginfo( 'name' ); ?></span>
					<?php if ( get_bloginfo( 'description' ) ) : ?>
						<span class="mgt-wordmark-tag"><?php bloginfo( 'description' ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>
		</div>

		<nav class="mgt-nav" id="mgt-nav" aria-label="Hlavné menu">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'mgt-menu',
					'fallback_cb'    => 'mgt_menu_fallback',
					'depth'          => 2,
				)
			);
			?>
		</nav>

		<div class="mgt-tools">
			<button type="button" class="mg-size-btn mgt-tool" aria-label="Zväčšiť písmo">A+</button>
			<button type="button" class="mgt-tool mgt-menu-btn" aria-expanded="false" aria-controls="mgt-nav">
				<span class="mgt-burger" aria-hidden="true"></span><span class="mgt-menu-label">Menu</span>
			</button>
		</div>
	</div>
</header>

<main id="obsah" class="mgt-main">
