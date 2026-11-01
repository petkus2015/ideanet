<?php
/**
 * Domovská stránka. Texty sa upravujú vo Vzhľad → Prispôsobiť → Mediálna gramotnosť,
 * zoznamy (aktuálne podvody, karty) sa skladajú z príkladov a kariet.
 *
 * @package MedialnaGramotnostTema
 */

get_header();

$mgt_atlas  = get_post_type_archive_link( 'mg_priklad' );
$mgt_tests  = get_post_type_archive_link( 'mg_test' );
$mgt_cards  = get_post_type_archive_link( 'mg_karta' );
$mgt_cta    = (int) mgt_mod( 'cta_test' );
$mgt_cta_ln = $mgt_cta ? get_permalink( $mgt_cta ) : $mgt_tests;
$mgt_check  = mgt_page_url( 'overit-spravu' );
?>

<section class="mgt-hero">
	<div class="mgt-wrap mgt-hero-grid">
		<div class="mgt-hero-text">
			<?php if ( mgt_mod( 'hero_eyebrow' ) ) : ?>
				<p class="mgt-kicker"><?php echo esc_html( mgt_mod( 'hero_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h1><?php echo esc_html( mgt_mod( 'hero_title' ) ); ?>
				<?php if ( mgt_mod( 'hero_highlight' ) ) : ?>
					<span class="mgt-hl"><?php echo esc_html( mgt_mod( 'hero_highlight' ) ); ?></span>
				<?php endif; ?>
			</h1>
			<div class="mgt-lead"><?php echo wp_kses_post( wpautop( mgt_mod( 'hero_lead' ) ) ); ?></div>
			<p class="mgt-actions">
				<?php if ( mgt_mod( 'hero_btn1_text' ) ) : ?>
					<a class="mgt-btn" href="<?php echo esc_url( mgt_link( mgt_mod( 'hero_btn1_link' ), $mgt_cta_ln ) ); ?>"><?php echo esc_html( mgt_mod( 'hero_btn1_text' ) ); ?></a>
				<?php endif; ?>
				<?php if ( mgt_mod( 'hero_btn2_text' ) ) : ?>
					<a class="mgt-link-arrow" href="<?php echo esc_url( mgt_link( mgt_mod( 'hero_btn2_link' ), $mgt_check ) ); ?>"><?php echo esc_html( mgt_mod( 'hero_btn2_text' ) ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<?php
		$mgt_ex = (int) mgt_mod( 'hero_example' );
		if ( ! $mgt_ex ) {
			$mgt_first = get_posts( array( 'post_type' => 'mg_priklad', 'numberposts' => 1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
			$mgt_ex    = $mgt_first ? $mgt_first[0]->ID : 0;
		}
		if ( $mgt_ex ) :
			?>
			<div class="mgt-hero-demo mg">
				<?php echo mgt_priklad_example( $mgt_ex ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<section class="mgt-section">
	<div class="mgt-wrap">
		<h2 class="mgt-h2"><?php echo esc_html( mgt_mod( 'paths_title' ) ); ?></h2>
		<div class="mgt-paths">
			<?php
			$mgt_defaults = array(
				1 => $mgt_check ? $mgt_check . '#kontrola' : '',
				2 => $mgt_atlas,
				3 => mgt_page_url( 'dezinformacie' ),
				4 => mgt_page_url( 'stalo-sa-mi-to' ),
			);
			for ( $i = 1; $i <= 4; $i++ ) :
				$href = mgt_link( mgt_mod( "path{$i}_link" ), $mgt_defaults[ $i ] );
				?>
				<a class="mgt-path<?php echo 4 === $i ? ' mgt-path-urgent' : ''; ?>" href="<?php echo esc_url( $href ); ?>">
					<span class="mgt-path-title"><?php echo esc_html( mgt_mod( "path{$i}_title" ) ); ?></span>
					<span class="mgt-path-text"><?php echo esc_html( mgt_mod( "path{$i}_text" ) ); ?></span>
				</a>
			<?php endfor; ?>
		</div>
	</div>
</section>

<section class="mgt-band">
	<div class="mgt-wrap">
		<div class="mgt-band-head">
			<h2 class="mgt-h2"><?php echo esc_html( mgt_mod( 'rules_title' ) ); ?></h2>
			<p><?php echo esc_html( mgt_mod( 'rules_text' ) ); ?></p>
		</div>
		<div class="mgt-rules">
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
				<div class="mgt-rule">
					<h3><?php echo esc_html( mgt_mod( "rule{$i}_title" ) ); ?></h3>
					<p><?php echo esc_html( mgt_mod( "rule{$i}_text" ) ); ?></p>
				</div>
			<?php endfor; ?>
		</div>
	</div>
</section>

<?php
$mgt_warn = get_posts(
	array(
		'post_type'   => 'mg_priklad',
		'numberposts' => 8,
		'meta_key'    => '_mg_home', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'  => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
	)
);
if ( $mgt_warn ) :
	?>
	<section class="mgt-section">
		<div class="mgt-wrap mgt-split">
			<div>
				<h2 class="mgt-h2"><?php echo esc_html( mgt_mod( 'warnings_title' ) ); ?></h2>
				<p class="mgt-muted"><?php echo esc_html( mgt_mod( 'warnings_text' ) ); ?></p>
				<p><a class="mgt-link-arrow" href="<?php echo esc_url( $mgt_atlas ); ?>">Všetky príklady podvodov</a></p>
			</div>
			<ol class="mgt-news">
				<?php foreach ( $mgt_warn as $w ) : ?>
					<li>
						<a href="<?php echo esc_url( $mgt_atlas . '#' . $w->post_name ); ?>">
							<span class="mgt-news-tag"><?php echo esc_html( mgt_meta( $w->ID, 'home_tag' ) ); ?></span>
							<span class="mgt-news-title"><?php echo esc_html( get_the_title( $w ) ); ?></span>
							<span class="mgt-news-text"><?php echo esc_html( mgt_meta( $w->ID, 'home_text' ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
<?php endif; ?>

<section class="mgt-section mgt-section-alt">
	<div class="mgt-wrap">
		<h2 class="mgt-h2"><?php echo esc_html( mgt_mod( 'stats_title' ) ); ?></h2>
		<div class="mgt-figures">
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
				<figure>
					<strong><?php echo esc_html( mgt_mod( "stat{$i}_num" ) ); ?></strong>
					<figcaption><?php echo esc_html( mgt_mod( "stat{$i}_text" ) ); ?> <cite><?php echo esc_html( mgt_mod( "stat{$i}_source" ) ); ?></cite></figcaption>
				</figure>
			<?php endfor; ?>
		</div>
	</div>
</section>

<?php
$mgt_karty = get_posts( array( 'post_type' => 'mg_karta', 'numberposts' => max( 1, (int) mgt_mod( 'cards_count' ) ), 'orderby' => 'menu_order', 'order' => 'ASC' ) );
if ( $mgt_karty ) :
	?>
	<section class="mgt-section">
		<div class="mgt-wrap">
			<div class="mgt-head-row">
				<h2 class="mgt-h2"><?php echo esc_html( mgt_mod( 'cards_title' ) ); ?></h2>
				<a class="mgt-link-arrow" href="<?php echo esc_url( $mgt_cards ); ?>">Všetky karty</a>
			</div>
			<div class="mgt-minicards">
				<?php foreach ( $mgt_karty as $k ) : ?>
					<a class="mgt-minicard" href="<?php echo esc_url( $mgt_cards . '#' . $k->post_name ); ?>">
						<span class="mgt-minicard-title"><?php echo esc_html( get_the_title( $k ) ); ?></span>
						<?php if ( mgt_meta( $k->ID, 'remember' ) ) : ?>
							<span class="mgt-minicard-remember"><?php echo esc_html( mgt_meta( $k->ID, 'remember' ) ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="mgt-section mgt-section-tight">
	<div class="mgt-wrap mgt-audiences">
		<?php
		$mgt_aud_def = array( 1 => mgt_page_url( 'pre-seniorov' ), 2 => mgt_page_url( 'pre-mladych' ) );
		for ( $i = 1; $i <= 2; $i++ ) :
			?>
			<a class="mgt-audience" href="<?php echo esc_url( mgt_link( mgt_mod( "aud{$i}_link" ), $mgt_aud_def[ $i ] ) ); ?>">
				<span class="mgt-audience-title"><?php echo esc_html( mgt_mod( "aud{$i}_title" ) ); ?></span>
				<span><?php echo esc_html( mgt_mod( "aud{$i}_text" ) ); ?></span>
			</a>
		<?php endfor; ?>
	</div>
</section>

<section class="mgt-section">
	<div class="mgt-wrap mgt-cta">
		<div>
			<h2 class="mgt-h2"><?php echo esc_html( mgt_mod( 'cta_title' ) ); ?></h2>
			<p><?php echo esc_html( mgt_mod( 'cta_text' ) ); ?></p>
		</div>
		<a class="mgt-btn" href="<?php echo esc_url( $mgt_cta_ln ); ?>">Spustiť test</a>
	</div>
</section>

<?php
// Obsah stránky nastavenej ako domovská (ak nejaký má) sa zobrazí pod sekciami.
if ( 'page' === get_option( 'show_on_front' ) ) {
	while ( have_posts() ) {
		the_post();
		if ( '' !== trim( get_the_content() ) ) {
			echo '<section class="mgt-section"><div class="mgt-wrap mgt-content">';
			the_content();
			echo '</div></section>';
		}
	}
}
get_footer();
