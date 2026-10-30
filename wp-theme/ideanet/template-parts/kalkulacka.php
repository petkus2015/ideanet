<?php
/**
 * Kalkulačka — výpočet beží v JavaScripte, čísla prichádzajú z Prispôsobiť.
 *
 * Vykresľuje sa vnútri sekcie s cenníkom, preto tu nie je ani <section>,
 * ani vlastný nadpis.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$min   = max( 1, (int) ideanet_num( 'calc_min' ) );
$max   = max( $min, (int) ideanet_num( 'calc_max' ) );
$start = min( $max, max( $min, (int) ideanet_num( 'calc_start' ) ) );
$addons = ideanet_pairs( 'calc_addons', 3 );
?>
<div class="calc reveal" id="kalkulacka">
			<form class="calc__form" id="calcForm">

				<div class="field field--wide">
					<label for="calcReels"><?php echo esc_html( ideanet_opt( 'calc_reels_label' ) ); ?></label>
					<div class="stepper">
						<button type="button" class="stepper__btn" id="reelsDown" aria-label="<?php esc_attr_e( 'Menej Reels', 'ideanet' ); ?>">−</button>
						<input id="calcReels" type="range" min="<?php echo (int) $min; ?>" max="<?php echo (int) $max; ?>" step="1" value="<?php echo (int) $start; ?>" aria-describedby="reelsOut">
						<button type="button" class="stepper__btn" id="reelsUp" aria-label="<?php esc_attr_e( 'Viac Reels', 'ideanet' ); ?>">+</button>
						<output class="stepper__val" id="reelsOut" for="calcReels"><?php echo (int) $start; ?></output>
					</div>
					<small class="hint" id="shootHint"></small>
				</div>

				<fieldset class="field field--wide modes">
					<legend><?php echo esc_html( ideanet_opt( 'calc_modes_label' ) ); ?></legend>
					<label class="radio">
						<input type="radio" name="calcMode" value="none" checked>
						<span><?php echo esc_html( ideanet_opt( 'calc_mode_none' ) ); ?></span>
					</label>
					<label class="radio">
						<input type="radio" name="calcMode" value="half">
						<span><?php echo esc_html( ideanet_opt( 'calc_mode_half' ) ); ?> <b><?php echo esc_html( ideanet_opt( 'calc_half' ) ); ?> €</b></span>
					</label>
					<label class="radio">
						<input type="radio" name="calcMode" value="day">
						<span><?php echo esc_html( ideanet_opt( 'calc_mode_day' ) ); ?> <b><?php echo esc_html( ideanet_opt( 'calc_day' ) ); ?> €</b></span>
					</label>
					<small class="hint"><?php ideanet_text( 'calc_modes_hint' ); ?></small>
				</fieldset>

				<?php if ( $addons ) : ?>
					<fieldset class="field field--wide addons">
						<legend><?php echo esc_html( ideanet_opt( 'calc_addons_label' ) ); ?></legend>
						<?php foreach ( $addons as $i => $addon ) : ?>
							<?php
							list( $label, $amount, $kind ) = $addon;
							$suffix = 'percento' === $kind
								? '+ ' . $amount . ' %'
								: ( 'mesacne' === $kind
									/* translators: %s: suma s menou. */
									? sprintf( __( '+ %s € / mesiac', 'ideanet' ), $amount )
									/* translators: %s: suma s menou. */
									: sprintf( __( '+ %s €', 'ideanet' ), $amount ) );
							?>
							<label class="check">
								<input type="checkbox" id="addon<?php echo (int) $i; ?>" data-addon="<?php echo (int) $i; ?>">
								<span><?php echo esc_html( $label ); ?> <b><?php echo esc_html( $suffix ); ?></b></span>
							</label>
						<?php endforeach; ?>
					</fieldset>
				<?php endif; ?>

				<div class="field field--wide">
					<label for="calcKm"><?php echo wp_kses( ideanet_tokens( ideanet_opt( 'calc_km_label' ) ), ideanet_kses() ); ?></label>
					<input id="calcKm" type="number" min="0" max="500" step="5" value="0" inputmode="numeric">
					<small class="hint"><?php echo wp_kses( ideanet_tokens( ideanet_opt( 'calc_km_hint' ) ), ideanet_kses() ); ?></small>
				</div>
			</form>

			<aside class="calc__out" aria-live="polite">
				<p class="calc__label"><?php echo esc_html( ideanet_opt( 'calc_out_label' ) ); ?></p>
				<p class="calc__total"><b id="calcTotal">0</b> <span>€</span></p>
				<p class="calc__unit" id="calcUnit"><?php echo esc_html( ideanet_opt( 'calc_unit' ) ); ?></p>

				<p class="calc__per" id="calcPer"></p>
				<ul class="calc__rows" id="calcRows"></ul>
				<p class="calc__tip" id="calcTip" hidden></p>

				<a class="btn btn--primary btn--full" href="#kontakt" id="calcCta"><?php echo esc_html( ideanet_opt( 'calc_cta' ) ); ?></a>
				<p class="calc__disc"><?php ideanet_text( 'calc_disc' ); ?></p>
			</aside>
</div>
