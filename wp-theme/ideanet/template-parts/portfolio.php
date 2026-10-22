<?php
/**
 * Portfólio — karty vykresľuje JavaScript z dát v IDEANET.reels.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="section section--portfolio" id="portfolio">
	<div class="wrap">
		<?php ideanet_section_head( 'portfolio' ); ?>
	</div>

	<div class="reels" id="reels">
		<button class="reels__arrow reels__arrow--prev" id="prevReel" aria-label="<?php esc_attr_e( 'Predchádzajúce video', 'ideanet' ); ?>" aria-controls="rail">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>

		<div class="rail" id="rail" tabindex="0" role="listbox" aria-label="<?php esc_attr_e( 'Portfólio vertikálnych videí', 'ideanet' ); ?>"></div>

		<button class="reels__arrow reels__arrow--next" id="nextReel" aria-label="<?php esc_attr_e( 'Ďalšie video', 'ideanet' ); ?>" aria-controls="rail">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>

		<div class="rail__dots" id="railDots" role="tablist" aria-label="<?php esc_attr_e( 'Prejsť na video', 'ideanet' ); ?>"></div>
	</div>
</section>
