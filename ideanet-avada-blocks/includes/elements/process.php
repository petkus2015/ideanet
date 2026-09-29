<?php
/**
 * IDEANET — Proces (kroky spolupráce).
 *
 * Shortcode: [ideanet_process]
 * Repeater:  steps (číslo, názov, popis)
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Process {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_process', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Proces', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_process',
				'icon'      => 'fusiona-list-alt',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Kicker', 'ideanet-avada-blocks' ),
						'param_name' => 'kicker',
						'value'      => 'Proces',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => 'Od prvej správy',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená časť', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => 'po hotový obsah',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Podnadpis', 'ideanet-avada-blocks' ),
						'param_name' => 'sub',
						'value'      => 'Žiadne zbytočné kolá. Štyri kroky, jasné termíny, dva kolá pripomienok.',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Kroky', 'ideanet-avada-blocks' ),
						'param_name'  => 'steps',
						'value'       => array(
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Číslo (napr. 01)', 'ideanet-avada-blocks' ),
								'param_name' => 'number',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Názov kroku', 'ideanet-avada-blocks' ),
								'param_name' => 'title',
								'value'      => '',
							),
							array(
								'type'       => 'textarea',
								'heading'    => __( 'Popis', 'ideanet-avada-blocks' ),
								'param_name' => 'description',
								'value'      => '',
							),
						),
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Poznámka pod krokmi (nepovinné)', 'ideanet-avada-blocks' ),
						'param_name' => 'note',
						'value'      => 'Uvedené časy sú orientačné — konkrétne termíny a rozsah dodávky si vždy potvrdíme podľa dohody a rozsahu prác.',
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
				'steps'            => array(),
				'note'             => '',
			),
			$atts,
			'ideanet_process'
		);

		$steps = ideanet_blocks_multiple( $args['steps'] );

		ob_start();
		?>
		<section class="ib-scope">
			<div class="ib-wrap">
				<?php echo ideanet_blocks_head( $args['kicker'], $args['heading'], $args['heading_emphasis'], $args['sub'] ); ?>

				<?php if ( $steps ) : ?>
					<ol class="ib-steps">
						<?php foreach ( $steps as $i => $step ) : ?>
							<li class="ib-step ib-reveal">
								<span class="ib-step__no"><?php echo esc_html( ( $step['number'] ?? '' ) !== '' ? $step['number'] : sprintf( '%02d', $i + 1 ) ); ?></span>
								<?php if ( ! empty( $step['title'] ) ) : ?><h3><?php echo esc_html( $step['title'] ); ?></h3><?php endif; ?>
								<?php if ( ! empty( $step['description'] ) ) : ?><p><?php echo esc_html( $step['description'] ); ?></p><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>

				<?php if ( $args['note'] ) : ?>
					<p class="ib-note ib-reveal" style="margin-top:32px;max-width:62ch"><?php echo esc_html( $args['note'] ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Process();
