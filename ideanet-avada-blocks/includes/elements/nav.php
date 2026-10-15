<?php
/**
 * IDEANET — Navigácia (sticky in-page menu).
 *
 * Nejde o hlavnú hlavičku/menu webu — tú rieši Avada Header Builder
 * (logo, hlavné menu, prípadne košík/vyhľadávanie). Tento blok je
 * doplnková lišta priamo v obsahu stránky, ktorá pri scrollovaní
 * zostáva prilepená hore a umožňuje odkočiť na jednotlivé sekcie
 * tejto (jednostránkovej) stránky.
 *
 * Shortcode: [ideanet_nav]
 * Repeater:  items (text odkazu, cieľ)
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Nav {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_nav', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Navigácia', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_nav',
				'icon'      => 'fusiona-menu',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Značka / meno (vľavo)', 'ideanet-avada-blocks' ),
						'param_name' => 'brand_text',
						'value'      => 'IDEANET',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Odkaz značky (napr. # alebo #hore)', 'ideanet-avada-blocks' ),
						'param_name' => 'brand_link',
						'value'      => '#',
					),
					array(
						'type'       => 'radio_button_set',
						'heading'    => __( 'Prilepiť navrchu pri scrollovaní', 'ideanet-avada-blocks' ),
						'param_name' => 'sticky',
						'value'      => array(
							'yes' => __( 'Áno', 'ideanet-avada-blocks' ),
							'no'  => __( 'Nie', 'ideanet-avada-blocks' ),
						),
						'default'    => 'yes',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tlačidlo — text (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_text',
						'value'      => 'Dohodnúť termín',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tlačidlo — odkaz', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_link',
						'value'      => '#kontakt',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Položky menu', 'ideanet-avada-blocks' ),
						'description' => __( 'Odkazy by sa mali zhodovať s kotvami ostatných blokov (napr. #sluzby, #cennik) — pozri pole „Kotva pre odkazy“ pri danom bloku.', 'ideanet-avada-blocks' ),
						'param_name'  => 'items',
						'value'       => array(
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Text odkazu', 'ideanet-avada-blocks' ),
								'param_name' => 'label',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Cieľ (napr. #sluzby)', 'ideanet-avada-blocks' ),
								'param_name' => 'link',
								'value'      => '',
							),
						),
					),
				),
			)
		);
	}

	public function render( $atts, $content = '' ) {
		ideanet_blocks_enqueue_assets();

		$args = shortcode_atts(
			array(
				'brand_text' => '',
				'brand_link' => '#',
				'sticky'     => 'yes',
				'cta_text'   => '',
				'cta_link'   => '#kontakt',
				'items'      => array(),
			),
			$atts,
			'ideanet_nav'
		);

		$items  = ideanet_blocks_resolve_items( 'nav_items', $args['items'], ideanet_blocks_default_nav_items() );
		$sticky = 'no' === $args['sticky'] ? 'no' : 'yes';

		ob_start();
		?>
		<header class="ib-scope ib-nav" data-sticky="<?php echo esc_attr( $sticky ); ?>">
			<div class="ib-wrap ib-nav__bar">
				<?php if ( $args['brand_text'] ) : ?>
					<a class="ib-nav__brand" href="<?php echo esc_url( $args['brand_link'] ); ?>"><?php echo esc_html( $args['brand_text'] ); ?></a>
				<?php endif; ?>

				<?php if ( $items ) : ?>
					<nav class="ib-nav__links" aria-label="<?php esc_attr_e( 'Hlavná navigácia', 'ideanet-avada-blocks' ); ?>">
						<?php foreach ( $items as $item ) : ?>
							<?php if ( ! empty( $item['label'] ) ) : ?>
								<a href="<?php echo esc_url( $item['link'] ?? '#' ); ?>" class="ib-nav__link"><?php echo esc_html( $item['label'] ); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>

				<div class="ib-nav__actions">
					<?php if ( $args['cta_text'] ) : ?>
						<a class="ib-btn ib-nav__cta" href="<?php echo esc_url( $args['cta_link'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
					<?php endif; ?>
					<button type="button" class="ib-nav__burger" aria-label="<?php esc_attr_e( 'Otvoriť menu', 'ideanet-avada-blocks' ); ?>" aria-expanded="false">
						<span></span><span></span><span></span>
					</button>
				</div>
			</div>
		</header>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Nav();
