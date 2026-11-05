<?php
/**
 * Template Name: Ponuka redizajnu webov
 *
 * Samostatná podstránka s akčnou ponukou na redizajn prezentačného webu.
 * Všetky texty, ceny, termín aj počet miest sú v Prispôsobiť → IDEANET →
 * 12 · Redizajn webov. Ukážky pred/po sa pridávajú ako položky obsahu
 * v IDEANET → Redizajn — ukážky.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ukazky = ideanet_items( 'ideanet_redizajn', 12 );
$kroky  = ideanet_red_pairs( 'red_ako', 2 );
$fakty  = ideanet_red_pairs( 'red_facts', 2 );
$otazky = ideanet_red_pairs( 'red_faq', 2 );
$gets   = ideanet_red_lines( 'red_gets' );
$komu   = ideanet_red_lines( 'red_prekoho' );
$flag   = ideanet_red_opt( 'red_flag' );
$grab   = ideanet_red_opt( 'red_prekoho_grab' );
$foot   = ideanet_red_opt( 'red_ukazky_foot' );

/* Vo výbere vo formulári má byť rovno redizajn — človek, ktorý sa sem
   dostal, nepíše kvôli videu. */
$GLOBALS['ideanet_form_type'] = ideanet_red_opt( 'red_form_option' );
?>
<main id="top" class="redizajn">

	<section class="section offer">
		<div class="wrap">
			<?php if ( $flag ) : ?>
				<p class="offer__flag reveal"><?php echo esc_html( $flag ); ?></p>
			<?php endif; ?>
			<p class="eyebrow reveal"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_eyebrow' ) ); ?></p>
			<h1 class="reveal">
				<?php ideanet_red_text( 'red_title' ); ?>
				<?php if ( ideanet_red_opt( 'red_title_hl' ) ) : ?>
					<span class="grad"><?php ideanet_red_text( 'red_title_hl' ); ?></span>
				<?php endif; ?>
			</h1>
			<p class="lead reveal"><?php ideanet_red_text( 'red_lead' ); ?></p>

			<div class="offer__cta reveal">
				<a class="btn btn--primary" href="#dopyt"><?php echo esc_html( ideanet_red_opt( 'red_cta' ) ); ?></a>
				<?php if ( ideanet_red_opt( 'red_cta2' ) ) : ?>
					<a class="btn btn--ghost" href="#dostanete"><?php echo esc_html( ideanet_red_opt( 'red_cta2' ) ); ?> <span aria-hidden="true">↓</span></a>
				<?php endif; ?>
			</div>

			<?php if ( $fakty ) : ?>
				<ul class="contact__facts offer__meta reveal" role="list">
					<?php foreach ( $fakty as $fakt ) : ?>
						<li>
							<b><?php echo esc_html( $fakt[0] ); ?></b>
							<?php if ( '' !== $fakt[1] ) : ?>
								<span><?php echo esc_html( $fakt[1] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<?php
	/* Čokoľvek, čo redaktor dopíše priamo pri stránke v editore. */
	while ( have_posts() ) :
		the_post();
		if ( trim( get_the_content() ) ) :
			?>
			<section class="section">
				<div class="wrap entry__body reveal"><?php the_content(); ?></div>
			</section>
			<?php
		endif;
	endwhile;
	?>

	<?php if ( ideanet_red_opt( 'red_poznate_text' ) ) : ?>
		<section class="section section--alt" id="poznate">
			<div class="wrap">
				<div class="section__head reveal">
					<div>
						<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_poznate_eyebrow' ) ); ?></p>
						<h2><?php ideanet_red_text( 'red_poznate_title' ); ?></h2>
					</div>
				</div>
				<p class="lead reveal"><?php ideanet_red_text( 'red_poznate_text' ); ?></p>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $gets ) : ?>
		<section class="section" id="dostanete">
			<div class="wrap">
				<div class="section__head reveal">
					<div>
						<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_gets_eyebrow' ) ); ?></p>
						<h2><?php ideanet_red_text( 'red_gets_title' ); ?></h2>
					</div>
					<?php if ( ideanet_red_opt( 'red_gets_note' ) ) : ?>
						<p class="section__note"><?php ideanet_red_text( 'red_gets_note' ); ?></p>
					<?php endif; ?>
				</div>
				<ul class="gets reveal" role="list">
					<?php foreach ( $gets as $item ) : ?>
						<li><?php echo wp_kses( $item, ideanet_kses() ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $kroky ) : ?>
		<section class="section section--alt" id="ako">
			<div class="wrap">
				<div class="section__head reveal">
					<div>
						<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_ako_eyebrow' ) ); ?></p>
						<h2><?php ideanet_red_text( 'red_ako_title' ); ?></h2>
					</div>
					<?php if ( ideanet_red_opt( 'red_ako_note' ) ) : ?>
						<p class="section__note"><?php ideanet_red_text( 'red_ako_note' ); ?></p>
					<?php endif; ?>
				</div>
				<ol class="steps" role="list">
					<?php foreach ( $kroky as $i => $krok ) : ?>
						<li class="step reveal">
							<span class="step__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<h3><?php echo wp_kses( $krok[0], ideanet_kses() ); ?></h3>
							<p><?php echo wp_kses( $krok[1], ideanet_kses() ); ?></p>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $ukazky ) : ?>
		<section class="section" id="ukazky">
			<div class="wrap">
				<div class="section__head reveal">
					<div>
						<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_ukazky_eyebrow' ) ); ?></p>
						<h2><?php ideanet_red_text( 'red_ukazky_title' ); ?></h2>
					</div>
					<?php if ( ideanet_red_opt( 'red_ukazky_note' ) ) : ?>
						<p class="section__note"><?php ideanet_red_text( 'red_ukazky_note' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="ba">
					<?php
					foreach ( $ukazky as $ukazka ) :
						$pred  = ideanet_media_url( ideanet_meta( $ukazka->ID, 'pred' ) );
						$po    = ideanet_image( $ukazka, 'large', 'po' );
						$nazov = get_the_title( $ukazka );
						$odkaz = ideanet_meta( $ukazka->ID, 'url' );
						$veta  = trim( wp_strip_all_tags( $ukazka->post_content ) );
						?>
						<article class="ba__item reveal">
							<div class="ba__pair">
								<figure class="ba__fig ba__fig--pred">
									<?php if ( $pred ) : ?>
										<img src="<?php echo esc_url( $pred ); ?>" alt="<?php
											/* translators: %s: názov ukážky. */
											echo esc_attr( sprintf( __( 'Pôvodný web: %s', 'ideanet' ), $nazov ) );
										?>" loading="lazy" decoding="async">
									<?php endif; ?>
									<figcaption><?php echo esc_html( ideanet_red_opt( 'red_label_pred' ) ); ?></figcaption>
								</figure>
								<figure class="ba__fig ba__fig--po">
									<?php if ( $po ) : ?>
										<img src="<?php echo esc_url( $po ); ?>" alt="<?php
											/* translators: %s: názov ukážky. */
											echo esc_attr( sprintf( __( 'Nový web: %s', 'ideanet' ), $nazov ) );
										?>" loading="lazy" decoding="async">
									<?php endif; ?>
									<figcaption><?php echo esc_html( ideanet_red_opt( 'red_label_po' ) ); ?></figcaption>
								</figure>
							</div>
							<div class="ba__body">
								<h3>
									<?php if ( $odkaz ) : ?>
										<a href="<?php echo esc_url( $odkaz ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $nazov ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $nazov ); ?>
									<?php endif; ?>
								</h3>
								<?php if ( $veta ) : ?>
									<p><?php echo esc_html( $veta ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<?php if ( $foot ) : ?>
					<p class="section__note reveal" style="margin-top:1.2rem"><?php ideanet_red_text( 'red_ukazky_foot' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="section section--alt" id="cena">
		<div class="wrap">
			<div class="section__head reveal">
				<div>
					<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_cena_eyebrow' ) ); ?></p>
					<h2><?php ideanet_red_text( 'red_cena_title' ); ?></h2>
				</div>
			</div>
			<div class="deal reveal">
				<div>
					<p class="deal__price">
						<?php if ( ideanet_red_opt( 'red_price_pred' ) ) : ?>
							<span class="deal__old"><?php echo esc_html( ideanet_red_opt( 'red_price_pred' ) ); ?></span>
						<?php endif; ?>
						<span class="deal__now"><?php echo esc_html( ideanet_red_opt( 'red_price' ) ); ?></span>
					</p>
					<?php if ( ideanet_red_opt( 'red_price_unit' ) ) : ?>
						<p class="deal__unit"><?php ideanet_red_text( 'red_price_unit' ); ?></p>
					<?php endif; ?>
				</div>
				<div class="deal__body">
					<?php if ( ideanet_red_opt( 'red_deal_when' ) ) : ?>
						<p class="deal__when"><span class="dot"></span> <?php ideanet_red_text( 'red_deal_when' ); ?></p>
					<?php endif; ?>
					<?php if ( ideanet_red_opt( 'red_cena_note' ) ) : ?>
						<p class="deal__note"><?php ideanet_red_text( 'red_cena_note' ); ?></p>
					<?php endif; ?>
				</div>
				<a class="btn btn--primary" href="#dopyt"><?php echo esc_html( ideanet_red_opt( 'red_cta' ) ); ?></a>
			</div>
		</div>
	</section>

	<?php if ( $komu || $grab ) : ?>
		<section class="section" id="komu">
			<div class="wrap">
				<div class="section__head reveal">
					<div>
						<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_prekoho_eyebrow' ) ); ?></p>
						<h2><?php ideanet_red_text( 'red_prekoho_title' ); ?></h2>
					</div>
					<?php if ( ideanet_red_opt( 'red_prekoho_note' ) ) : ?>
						<p class="section__note"><?php ideanet_red_text( 'red_prekoho_note' ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $komu ) : ?>
					<ul class="segments reveal" role="list">
						<?php foreach ( $komu as $item ) : ?>
							<li><?php echo wp_kses( $item, ideanet_kses() ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $grab ) : ?>
					<div class="grab reveal">
						<p><?php ideanet_red_text( 'red_prekoho_grab' ); ?></p>
						<?php if ( ideanet_red_opt( 'red_prekoho_cta' ) ) : ?>
							<div class="grab__links">
								<a class="btn btn--ghost" href="#dopyt"><?php echo esc_html( ideanet_red_opt( 'red_prekoho_cta' ) ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $otazky ) : ?>
		<section class="section section--alt" id="otazky">
			<div class="wrap">
				<div class="section__head reveal">
					<div>
						<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_faq_eyebrow' ) ); ?></p>
						<h2><?php ideanet_red_text( 'red_faq_title' ); ?></h2>
					</div>
				</div>
				<div class="faq reveal">
					<?php foreach ( $otazky as $otazka ) : ?>
						<details>
							<summary><?php echo esc_html( $otazka[0] ); ?></summary>
							<p><?php echo wp_kses( $otazka[1], ideanet_kses() ); ?></p>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="section" id="miesta">
		<div class="wrap">
			<div class="slots reveal">
				<div class="slots__copy">
					<h2><?php ideanet_red_text( 'red_end_title' ); ?></h2>
					<p><?php ideanet_red_text( 'red_end_text' ); ?></p>
				</div>
				<a class="btn btn--primary" href="#dopyt"><?php echo esc_html( ideanet_red_opt( 'red_end_cta' ) ); ?></a>
			</div>
		</div>
	</section>

	<section class="section section--contact" id="dopyt">
		<div class="wrap contact">
			<div class="contact__copy reveal">
				<p class="eyebrow"><span class="dot"></span> <?php echo esc_html( ideanet_red_opt( 'red_form_eyebrow' ) ); ?></p>
				<h2><?php ideanet_red_text( 'red_form_title' ); ?></h2>
				<p class="lead"><?php ideanet_red_text( 'red_form_note' ); ?></p>

				<ul class="contact__list" role="list">
					<?php if ( ideanet_opt( 'contact_email' ) ) : ?>
						<li><span aria-hidden="true">✉</span><a href="mailto:<?php echo esc_attr( ideanet_opt( 'contact_email' ) ); ?>"><?php echo esc_html( ideanet_opt( 'contact_email' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( ideanet_opt( 'contact_phone' ) ) : ?>
						<li><span aria-hidden="true">☎</span><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', ideanet_opt( 'contact_phone' ) ) ); ?>"><?php echo esc_html( ideanet_opt( 'contact_phone' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( ideanet_opt( 'contact_social' ) ) : ?>
						<li><span aria-hidden="true">◎</span><a href="<?php echo esc_url( ideanet_opt( 'contact_social_url' ) ? ideanet_opt( 'contact_social_url' ) : home_url( '/' ) ); ?>"><?php echo esc_html( ideanet_opt( 'contact_social' ) ); ?></a></li>
					<?php endif; ?>
				</ul>
			</div>

			<?php get_template_part( 'template-parts/form' ); ?>
		</div>
	</section>

</main>
<?php
get_footer();
