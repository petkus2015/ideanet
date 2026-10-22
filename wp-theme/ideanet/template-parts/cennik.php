<?php
/**
 * Cenník — tri pohľady podľa typu klienta.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

$tabs = array(
	'firmy'    => array( ideanet_opt( 'tab_firmy' ), ideanet_opt( 'tab_firmy_sub' ) ),
	'agentury' => array( ideanet_opt( 'tab_agentury' ), ideanet_opt( 'tab_agentury_sub' ) ),
	'eventy'   => array( ideanet_opt( 'tab_eventy' ), ideanet_opt( 'tab_eventy_sub' ) ),
);
$first = true;
?>
<section class="section" id="cennik">
	<div class="wrap">
		<?php ideanet_section_head( 'cennik' ); ?>

		<div class="tabs reveal">
			<div class="tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'Cenník podľa typu klienta', 'ideanet' ); ?>">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<button class="tabs__btn<?php echo $first ? ' is-active' : ''; ?>" role="tab"
							id="tab-<?php echo esc_attr( $key ); ?>"
							aria-controls="panel-<?php echo esc_attr( $key ); ?>"
							aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
							<?php echo $first ? '' : 'tabindex="-1"'; ?>
							data-tab="<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $label[0] ); ?> <span><?php echo esc_html( $label[1] ); ?></span>
					</button>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</div>

			<!-- ─── PRE FIRMY ─── -->
			<div class="tabs__panel is-active" role="tabpanel" id="panel-firmy" aria-labelledby="tab-firmy" tabindex="0">
				<p class="panel__intro"><?php ideanet_text( 'firmy_intro' ); ?></p>
				<?php ideanet_panel_link( 'ponuka_firmy_url' ); ?>

				<?php $plany = ideanet_baliky( 'firmy', 'plan' ); ?>
				<?php if ( $plany ) : ?>
					<div class="plans">
						<?php foreach ( $plany as $plan ) : ?>
							<?php ideanet_plan( $plan ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php
				/* Riadky bez zaradenia do bloku — inak by balík pridaný
				   v administrácii nemal na stránke kde vyjsť. */
				$volne = ideanet_baliky( 'firmy', 'tier' );
				?>
				<?php if ( $volne ) : ?>
					<ul class="tiers reveal" role="list">
						<?php foreach ( $volne as $item ) : ?>
							<?php ideanet_tier( $item ); ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php
				ideanet_tier_block( 'firmy', 'jednorazovo', 'firmy_jednorazovo_title', 'firmy_jednorazovo_note' );
				ideanet_tier_block( 'firmy', 'podcast', 'firmy_podcast_title', 'firmy_podcast_note', 'firmy_podcast_foot' );
				ideanet_tier_block( 'firmy', 'vizualy', 'firmy_vizualy_title', 'firmy_vizualy_note', 'firmy_vizualy_foot' );
				?>

				<?php ideanet_addon_box(); ?>
			</div>

			<!-- ─── PRE AGENTÚRY ─── -->
			<div class="tabs__panel" role="tabpanel" id="panel-agentury" aria-labelledby="tab-agentury" tabindex="0" hidden>
				<p class="panel__intro"><?php ideanet_text( 'agentury_intro' ); ?></p>
				<?php ideanet_panel_link( 'ponuka_agentury_url' ); ?>

				<?php $wl = ideanet_baliky( 'agentury', 'tier' ); ?>
				<?php if ( $wl ) : ?>
					<ul class="tiers reveal" role="list">
						<?php foreach ( $wl as $item ) : ?>
							<?php ideanet_tier( $item ); ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php ideanet_partner_box(); ?>

				<?php ideanet_agency_notes(); ?>
			</div>

			<!-- ─── EVENTY ─── -->
			<div class="tabs__panel" role="tabpanel" id="panel-eventy" aria-labelledby="tab-eventy" tabindex="0" hidden>
				<p class="panel__intro"><?php ideanet_text( 'eventy_intro' ); ?></p>

				<?php $eventy = ideanet_baliky( 'eventy', 'plan' ); ?>
				<?php if ( $eventy ) : ?>
					<div class="plans">
						<?php foreach ( $eventy as $plan ) : ?>
							<?php ideanet_plan( $plan ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php ideanet_event_hour(); ?>

				<?php
				ideanet_tier_block( 'eventy', 'grafika', 'eventy_graf_title', 'eventy_graf_note' );
				$ostatne = ideanet_baliky( 'eventy', 'tier' );
				?>
				<?php if ( $ostatne ) : ?>
					<ul class="tiers reveal" role="list">
						<?php foreach ( $ostatne as $item ) : ?>
							<?php ideanet_tier( $item ); ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ideanet_opt( 'eventy_foot' ) ) : ?>
					<p class="panel__foot reveal"><?php ideanet_text( 'eventy_foot' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
