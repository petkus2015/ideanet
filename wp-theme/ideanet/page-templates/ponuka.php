<?php
/**
 * Template Name: Ponuka na jednu stranu (tlač / PDF)
 *
 * Celá ponuka pre jeden typ klienta na jednej strane. Ceny sa berú
 * z rovnakých balíkov ako cenník na úvodnej stránke, takže sa to nemôže
 * rozísť. Text okolo píšete normálne v editore stránky.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

get_header();

$group = get_post_meta( get_the_ID(), '_ideanet_ponuka_group', true );
if ( ! in_array( $group, array( 'firmy', 'agentury', 'eventy' ), true ) ) {
	$group = 'firmy';
}

$plany  = ideanet_baliky( $group, 'plan' );
$riadky = ideanet_baliky( $group, 'tier' );
$bloky  = array(
	'firmy'  => array(
		array( 'jednorazovo', 'firmy_jednorazovo_title', 'firmy_jednorazovo_note' ),
		array( 'podcast', 'firmy_podcast_title', 'firmy_podcast_note' ),
		array( 'vizualy', 'firmy_vizualy_title', 'firmy_vizualy_note' ),
	),
	'eventy' => array( array( 'grafika', 'eventy_graf_title', 'eventy_graf_note' ) ),
);
$facts = ideanet_items( 'ideanet_podmienka', 20 );
?>
<main id="top" class="section ponuka">
	<div class="wrap entry-wrap">

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<div class="section__head reveal">
				<div>
					<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_opt( 'brand_name' ) ); ?> — <?php echo esc_html( ideanet_opt( 'brand_co' ) . ideanet_opt( 'brand_co_bold' ) ); ?></p>
					<h1><?php the_title(); ?></h1>
				</div>
			</div>

			<?php if ( get_the_content() ) : ?>
				<div class="entry__body reveal"><?php the_content(); ?></div>
			<?php endif; ?>
			<?php
		endwhile;
		?>

		<?php if ( $plany ) : ?>
			<div class="plans">
				<?php foreach ( $plany as $plan ) : ?>
					<?php ideanet_plan( $plan ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $riadky ) : ?>
			<ul class="tiers reveal" role="list">
				<?php foreach ( $riadky as $item ) : ?>
					<?php ideanet_tier( $item ); ?>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php
		if ( isset( $bloky[ $group ] ) ) {
			foreach ( $bloky[ $group ] as $blok ) {
				ideanet_tier_block( $group, $blok[0], $blok[1], $blok[2] );
			}
		}

		/* Doplnkové bloky z cenníka — ponuka na papieri musí povedať to isté
		   čo web, inak sa klient dozvie dve rôzne veci. */
		if ( 'firmy' === $group ) {
			ideanet_addon_box();
		} elseif ( 'agentury' === $group ) {
			ideanet_partner_box();
			ideanet_agency_notes();
		} elseif ( 'eventy' === $group ) {
			ideanet_event_hour();
			if ( ideanet_opt( 'eventy_foot' ) ) {
				echo '<p class="panel__foot">' . wp_kses( ideanet_opt( 'eventy_foot' ), ideanet_kses() ) . '</p>';
			}
		}
		?>

		<?php if ( $facts ) : ?>
			<h2 class="sub reveal"><?php echo esc_html( ideanet_opt( 'podmienky_title' ) ); ?></h2>
			<div class="facts reveal">
				<?php foreach ( $facts as $fact ) : ?>
					<div class="fact">
						<h3><?php echo esc_html( get_the_title( $fact ) ); ?></h3>
						<p><?php echo esc_html( wp_strip_all_tags( $fact->post_content ) ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<p class="ponuka__foot">
			<?php echo esc_html( ideanet_opt( 'contact_email' ) ); ?>
			<?php if ( ideanet_opt( 'contact_phone' ) ) : ?>
				· <?php echo esc_html( ideanet_opt( 'contact_phone' ) ); ?>
			<?php endif; ?>
		</p>
	</div>
</main>
<?php
get_footer();
