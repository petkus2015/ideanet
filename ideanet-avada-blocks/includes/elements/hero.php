<?php
/**
 * IDEANET — Hero (úvodná sekcia s karuselom posledných realizácií).
 *
 * Shortcode: [ideanet_hero]
 * Repeater:  slides (kategória, názov, dĺžka, náhľad, video)
 *
 * @package Ideanet_Avada_Blocks
 */

defined( 'ABSPATH' ) || exit;

class Ideanet_Blocks_Hero {

	public function __construct() {
		add_action( 'fusion_builder_before_init', array( $this, 'map' ) );
		add_shortcode( 'ideanet_hero', array( $this, 'render' ) );
	}

	public function map() {
		if ( ! function_exists( 'fusion_builder_map' ) ) {
			return;
		}
		fusion_builder_map(
			array(
				'name'      => __( 'IDEANET — Hero', 'ideanet-avada-blocks' ),
				'shortcode' => 'ideanet_hero',
				'icon'      => 'fusiona-headline',
				'category'  => __( 'IDEANET', 'ideanet-avada-blocks' ),
				'params'    => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Eyebrow (malý text nad nadpisom)', 'ideanet-avada-blocks' ),
						'param_name' => 'eyebrow',
						'value'      => 'Videotvorca & content creator · video, grafika, sociálne siete',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — bežný text', 'ideanet-avada-blocks' ),
						'param_name' => 'heading',
						'value'      => 'Potrebujete obsah dnes,',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nadpis — zvýraznená (kurzívna) časť', 'ideanet-avada-blocks' ),
						'param_name' => 'heading_emphasis',
						'value'      => 'kým je event ešte aktuálny?',
					),
					array(
						'type'       => 'textarea',
						'heading'    => __( 'Úvodný text', 'ideanet-avada-blocks' ),
						'param_name' => 'lead',
						'value'      => 'Som videotvorca a content creator v jednom — natáčam, strihám a nasadzujem ešte v ten istý deň. Kým vaše podujatie žije, prvé videá aj grafika už bežia na sieťach, bez čakania na troch dodávateľov.',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Hlavné tlačidlo — text', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_text',
						'value'      => 'Overiť voľný termín',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Hlavné tlačidlo — odkaz', 'ideanet-avada-blocks' ),
						'param_name' => 'cta_link',
						'value'      => '#kontakt',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Vedľajšie tlačidlo — text', 'ideanet-avada-blocks' ),
						'param_name' => 'cta2_text',
						'value'      => 'Pozrieť ukážky',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Vedľajšie tlačidlo — odkaz', 'ideanet-avada-blocks' ),
						'param_name' => 'cta2_link',
						'value'      => '#praca',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Poznámka pod tlačidlami', 'ideanet-avada-blocks' ),
						'param_name' => 'note',
						'value'      => 'Termíny dodania platia podľa dohody a rozsahu prác.',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Štítok nad karuselom', 'ideanet-avada-blocks' ),
						'param_name' => 'reel_heading',
						'value'      => 'Posledné realizácie',
					),
					array(
						'type'       => 'range',
						'heading'    => __( 'Kariet naraz — desktop', 'ideanet-avada-blocks' ),
						'param_name' => 'cards_desktop',
						'min'        => '1',
						'max'        => '5',
						'step'       => '1',
						'value'      => '3',
					),
					array(
						'type'       => 'range',
						'heading'    => __( 'Kariet naraz — mobil', 'ideanet-avada-blocks' ),
						'param_name' => 'cards_mobile',
						'min'        => '1',
						'max'        => '2',
						'step'       => '0.5',
						'value'      => '1.5',
					),
					array(
						'type'        => 'multiple',
						'heading'     => __( 'Realizácie v karuseli', 'ideanet-avada-blocks' ),
						'description' => __( 'Pridajte posledné práce — odporúčané 3 až 6 položiek.', 'ideanet-avada-blocks' ),
						'param_name'  => 'slides',
						'value'       => array(
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Kategória', 'ideanet-avada-blocks' ),
								'param_name' => 'category',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Názov', 'ideanet-avada-blocks' ),
								'param_name' => 'title',
								'value'      => '',
							),
							array(
								'type'       => 'textfield',
								'heading'    => __( 'Dĺžka (napr. 0:18)', 'ideanet-avada-blocks' ),
								'param_name' => 'meta',
								'value'      => '',
							),
							array(
								'type'       => 'upload',
								'heading'    => __( 'Náhľadový obrázok', 'ideanet-avada-blocks' ),
								'param_name' => 'poster',
								'value'      => '',
							),
							array(
								'type'       => 'upload',
								'heading'    => __( 'Video (MP4)', 'ideanet-avada-blocks' ),
								'param_name' => 'video',
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
				'eyebrow'          => '',
				'heading'          => '',
				'heading_emphasis' => '',
				'lead'             => '',
				'cta_text'         => '',
				'cta_link'         => '#kontakt',
				'cta2_text'        => '',
				'cta2_link'        => '#praca',
				'note'             => '',
				'reel_heading'     => '',
				'cards_desktop'    => '3',
				'cards_mobile'     => '1.5',
				'slides'           => array(),
			),
			$atts,
			'ideanet_hero'
		);

		$slides = ideanet_blocks_multiple( $args['slides'] );
		$uid    = ideanet_blocks_uid( 'ib-hero' );

		ob_start();
		?>
		<section class="ib-scope">
			<div class="ib-hero ib-wrap" id="<?php echo esc_attr( $uid ); ?>">
				<div class="ib-hero__inner">
					<div>
						<?php if ( $args['eyebrow'] ) : ?>
							<p class="ib-kicker ib-reveal"><?php echo esc_html( $args['eyebrow'] ); ?></p>
						<?php endif; ?>

						<h1 class="ib-reveal">
							<?php echo esc_html( $args['heading'] ); ?>
							<?php if ( $args['heading_emphasis'] ) : ?>
								<br><em><?php echo esc_html( $args['heading_emphasis'] ); ?></em>
							<?php endif; ?>
						</h1>

						<?php if ( $args['lead'] ) : ?>
							<p class="ib-hero__lead ib-reveal"><?php echo wp_kses_post( $args['lead'] ); ?></p>
						<?php endif; ?>

						<div class="ib-hero__cta ib-reveal">
							<?php if ( $args['cta_text'] ) : ?>
								<a class="ib-btn" href="<?php echo esc_url( $args['cta_link'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
							<?php endif; ?>
							<?php if ( $args['cta2_text'] ) : ?>
								<a class="ib-btn ib-btn--ghost" href="<?php echo esc_url( $args['cta2_link'] ); ?>"><?php echo esc_html( $args['cta2_text'] ); ?></a>
							<?php endif; ?>
						</div>

						<?php if ( $args['note'] ) : ?>
							<p class="ib-note ib-reveal"><?php echo esc_html( $args['note'] ); ?></p>
						<?php endif; ?>
					</div>

					<?php if ( $slides ) : ?>
						<div class="ib-carousel ib-reveal" data-mode="video" data-autoplay="1">
							<?php if ( $args['reel_heading'] ) : ?>
								<p class="ib-hero__reel-lab"><?php echo esc_html( $args['reel_heading'] ); ?></p>
							<?php endif; ?>
							<div class="ib-rail-wrap">
								<div class="ib-rail" role="listbox" aria-label="<?php echo esc_attr( $args['reel_heading'] ?: __( 'Posledné realizácie', 'ideanet-avada-blocks' ) ); ?>" tabindex="0">
									<?php
									foreach ( $slides as $slide ) {
										echo ideanet_blocks_render_tile(
											array(
												'mode'     => 'video',
												'category' => $slide['category'] ?? '',
												'title'    => $slide['title'] ?? '',
												'meta'     => $slide['meta'] ?? '',
												'poster'   => $slide['poster'] ?? '',
												'video'    => $slide['video'] ?? '',
											)
										); // Escapované vnútri ideanet_blocks_render_tile().
									}
									?>
									<span class="ib-rail-end" aria-hidden="true"></span>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<style>
			#<?php echo esc_attr( $uid ); ?>{ --ib-per: <?php echo esc_attr( $args['cards_desktop'] ); ?>; }
			@media (max-width:560px){ #<?php echo esc_attr( $uid ); ?>{ --ib-per: <?php echo esc_attr( $args['cards_mobile'] ); ?>; } }
		</style>
		<?php
		return ob_get_clean();
	}
}

new Ideanet_Blocks_Hero();
