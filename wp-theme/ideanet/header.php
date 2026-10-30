<?php
/**
 * Hlavička — pozadie, horné menu, spodná lišta a rozbalovacie menu.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip" href="#portfolio"><?php echo esc_html( ideanet_opt( 'skip_label' ) ); ?></a>
<span class="progress" id="scrollProgress" aria-hidden="true"></span>

<div class="bg" aria-hidden="true">
	<span class="orb orb--1"></span>
	<span class="orb orb--2"></span>
	<span class="orb orb--3"></span>
	<span class="grid"></span>
	<span class="noise"></span>
</div>
<span class="glow" id="cursorGlow" aria-hidden="true"></span>

<header class="nav" id="nav">
	<div class="wrap nav__inner">
		<?php ideanet_brand( is_front_page() ? '#top' : home_url( '/' ) ); ?>

		<nav class="nav__links" id="navLinks" aria-label="<?php esc_attr_e( 'Hlavná navigácia', 'ideanet' ); ?>">
			<span class="nav__pill" id="navPill" aria-hidden="true"></span>
			<?php foreach ( ideanet_menu_items( 'primary', ideanet_default_sections() ) as $link ) : ?>
				<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<a class="btn btn--sm nav__cta" href="<?php echo esc_url( is_front_page() ? '#kontakt' : home_url( '/#kontakt' ) ); ?>"><?php echo esc_html( ideanet_opt( 'nav_cta' ) ); ?></a>
	</div>
</header>

<?php
$tabs = ideanet_menu_items(
	'tabbar',
	array(
		'#portfolio' => __( 'Portfólio', 'ideanet' ),
		'#vizualy'   => __( 'Vizuály', 'ideanet' ),
		'#cennik'    => __( 'Cenník', 'ideanet' ),
		'#kontakt'   => __( 'Kontakt', 'ideanet' ),
	)
);
$tabs = array_slice( $tabs, 0, 4 );
?>
<nav class="tabbar" id="tabbar" aria-label="<?php esc_attr_e( 'Hlavná navigácia', 'ideanet' ); ?>">
	<span class="tabbar__pill" id="tabPill" aria-hidden="true"></span>
	<?php
	foreach ( $tabs as $tab ) {
		$icon = $tab['item'] ? ideanet_menu_icon_for( $tab['item'] ) : trim( $tab['url'], '#/' );
		printf(
			'<a href="%1$s"><span class="tabbar__ico">%2$s</span><span class="tabbar__t">%3$s</span></a>',
			esc_url( $tab['url'] ),
			ideanet_icon( $icon ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vlastné SVG z témy.
			esc_html( $tab['label'] )
		);
	}
	?>
	<button type="button" id="moreBtn" aria-controls="mobileMenu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Otvoriť ďalšie sekcie', 'ideanet' ); ?>">
		<span class="tabbar__ico"><?php echo ideanet_icon( 'viac' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vlastné SVG z témy. ?></span>
		<span class="tabbar__t"><?php esc_html_e( 'Viac', 'ideanet' ); ?></span>
	</button>
</nav>

<div class="mobile-menu" id="mobileMenu" hidden>
	<button class="mobile-menu__close" id="menuClose" type="button" aria-label="<?php esc_attr_e( 'Zavrieť menu', 'ideanet' ); ?>">
		<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
	</button>
	<nav aria-label="<?php esc_attr_e( 'Mobilná navigácia', 'ideanet' ); ?>">
		<?php foreach ( ideanet_menu_items( 'mobile', ideanet_default_sections() ) as $i => $link ) : ?>
			<a href="<?php echo esc_url( $link['url'] ); ?>"><i><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></i> <?php echo esc_html( $link['label'] ); ?></a>
		<?php endforeach; ?>
	</nav>
	<div class="mobile-menu__foot">
		<a class="btn btn--primary btn--full" href="<?php echo esc_url( is_front_page() ? '#kontakt' : home_url( '/#kontakt' ) ); ?>"><?php echo esc_html( ideanet_opt( 'nav_cta' ) ); ?></a>
		<p>
			<a href="mailto:<?php echo esc_attr( ideanet_opt( 'contact_email' ) ); ?>"><?php echo esc_html( ideanet_opt( 'contact_email' ) ); ?></a>
			<?php if ( ideanet_opt( 'contact_phone' ) ) : ?>
				· <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', ideanet_opt( 'contact_phone' ) ) ); ?>"><?php echo esc_html( ideanet_opt( 'contact_phone' ) ); ?></a>
			<?php endif; ?>
		</p>
		<?php ideanet_socials_html( 'socials--menu' ); ?>
	</div>
</div>
