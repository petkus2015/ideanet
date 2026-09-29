<?php
/**
 * IDEANET — Referencie (citáty klientov).
 *
 * Shortcode: [ideanet_testimonials]
 * Repeater:  quotes (citát, meno, pozícia/firma)
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Testimonials {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_testimonials', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Referencie', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_testimonials',
				'icon'      => 'fusiona-quote-right',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kicker (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'kicker',
						'value'      => '',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => '',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená časť (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => '',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Podnadpis (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'sub',
						'value'      => '',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Referencie', 'ideanet-avada-blocks' ),
						'param_name'  => 'quotes',
						'value'       => array(
							array(
								'type'       => 'textarea',
								'heading'    => __( 'Citát', 'ideanet-avada-blocks' ),
								'param_name' => 'quote',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Meno', 'ideanet-avada-blocks' ),
								'param_name' => 'name',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Pozícia / firma', 'ideanet-avada-blocks' ),
								'param_name' => 'role',
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
				'kicker'           => '',
				'heading'          => '',
				'heading_emphasis' => '',
				'sub'              => '',
				'quotes'           => array(),
			),
			$atts,
			'ideanet_testimonials'
		);

		$quotes = ideanet_blocks_multiple( $args['quotes'] );

		ob_start();
		?>
		<section class="ib-scope">
			<div class="ib-wrap">
				<?php echo ideanet_blocks_head( $args['kicker'], $args['heading'], $args['heading_emphasis'], $args['sub'] ); ?>

				<?php if ( $quotes ) : ?>
					<div class="ib-quotes">
						<?php foreach ( $quotes as $q ) : ?>
							<figure class="ib-quote ib-reveal">
								<blockquote>„<?php echo esc_html( $q['quote'] ?? '' ); ?>“</blockquote>
								<?php if ( ! empty( $q['name'] ) || ! empty( $q['role'] ) ) : ?>
									<figcaption>
										<?php if ( ! empty( $q['name'] ) ) : ?><b><?php echo esc_html( $q['name'] ); ?></b><?php endif; ?>
										<?php if ( ! empty( $q['role'] ) ) : ?><span><?php echo esc_html( $q['role'] ); ?></span><?php endif; ?>
									</figcaption>
								<?php endif; ?>
							</figure>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Testimonials();
