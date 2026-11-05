<?php
/**
 * Template Name: Inzerujte u nás
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data   = relife_adv_data();
$status = isset( $_GET['adv'] ) ? sanitize_key( wp_unslash( $_GET['adv'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

get_header();
?>
<section class="rl-adv-hero">
	<div class="rl-wrap">
		<p class="rl-kicker"><?php esc_html_e( 'INZEROVANIE', 'relife' ); ?></p>
		<h1 class="rl-pagetitle"><?php esc_html_e( 'Inzerujte u nás', 'relife' ); ?></h1>
		<p class="rl-muted rl-lead"><?php esc_html_e( 'Publikujeme overené a zaujímavé články, optimalizované pre odpovede vyhľadávačov a generatívnej AI. Vaša značka sa tak objaví tam, kde ľudia hľadajú odpovede, a naša viditeľnosť rastie z mesiaca na mesiac.', 'relife' ); ?></p>
		<a class="rl-pill rl-pill--accent" href="#kontakt"><?php esc_html_e( 'Pošlite nám dopyt', 'relife' ); ?></a>
	</div>
</section>

<?php if ( $data ) : ?>
<section class="rl-wrap rl-block">
	<div class="rl-sechead">
		<h2 class="rl-sechead__title"><?php esc_html_e( 'Rastie naša viditeľnosť v generatívnej AI', 'relife' ); ?></h2>
	</div>
	<div class="rl-chartcard js-chart" data-points="<?php echo esc_attr( (string) wp_json_encode( $data ) ); ?>" data-unit="<?php esc_attr_e( 'zobrazení', 'relife' ); ?>">
		<div class="rl-chartcard__head">
			<div>
				<p class="rl-chartcard__label"><?php esc_html_e( 'Zobrazenia vo funkciách generatívnej AI', 'relife' ); ?></p>
				<p class="rl-chartcard__total"><span class="js-chart-total"><?php echo esc_html( number_format_i18n( array_sum( array_column( $data, 'value' ) ) ) ); ?></span></p>
			</div>
			<div class="rl-seg" role="group" aria-label="<?php esc_attr_e( 'Zobrazenie grafu', 'relife' ); ?>">
				<button type="button" class="rl-seg__btn is-on" data-mode="month" aria-pressed="true"><?php esc_html_e( 'Mesačne', 'relife' ); ?></button>
				<button type="button" class="rl-seg__btn" data-mode="cumulative" aria-pressed="false"><?php esc_html_e( 'Spolu', 'relife' ); ?></button>
			</div>
		</div>
		<div class="rl-chart js-chart-area" aria-hidden="true"></div>
		<table class="screen-reader-text">
			<caption><?php esc_html_e( 'Zobrazenia vo funkciách generatívnej AI podľa mesiacov', 'relife' ); ?></caption>
			<?php foreach ( $data as $p ) : ?>
				<tr><th scope="row"><?php echo esc_html( $p['label'] ); ?></th><td><?php echo esc_html( number_format_i18n( $p['value'] ) ); ?></td></tr>
			<?php endforeach; ?>
		</table>
		<p class="rl-chartcard__src"><?php esc_html_e( 'Zdroj: Google Search Console. Údaje sú zaokrúhlené.', 'relife' ); ?></p>
	</div>
</section>
<?php endif; ?>

<section class="rl-wrap rl-block">
	<div class="rl-sechead"><h2 class="rl-sechead__title"><?php esc_html_e( 'Prečo publikovať u nás', 'relife' ); ?></h2></div>
	<p class="rl-muted rl-lead"><?php esc_html_e( 'Nepíšeme do šuflíka. Každý článok je overený, zaujímavý a pripravený tak, aby ho našli ľudia aj odpoveďové systémy.', 'relife' ); ?></p>
	<div class="rl-grid rl-grid--4 rl-adv-why">
		<div class="rl-adv-offer"><span class="rl-adv-why__n" aria-hidden="true">01</span><h3><?php esc_html_e( 'Overené', 'relife' ); ?></h3><p><?php esc_html_e( 'Tvrdenia podkladáme zdrojmi a uvádzame ich pod článkom. Čitateľ vie, odkiaľ informácia pochádza, a vaša značka stojí vedľa dôveryhodného obsahu.', 'relife' ); ?></p></div>
		<div class="rl-adv-offer"><span class="rl-adv-why__n" aria-hidden="true">02</span><h3><?php esc_html_e( 'Zaujímavé', 'relife' ); ?></h3><p><?php esc_html_e( 'Píšeme o témach, ktoré ľudia naozaj hľadajú: technológie, zdravie, peniaze, cestovanie a štýl. Prehľadne, bez zbytočnej vaty.', 'relife' ); ?></p></div>
		<div class="rl-adv-offer"><span class="rl-adv-why__n" aria-hidden="true">03</span><h3><?php esc_html_e( 'Optimalizované pre AEO', 'relife' ); ?></h3><p><?php esc_html_e( 'Každý článok začína odpoveďou v skratke, má jasnú štruktúru a štruktúrované dáta. Vďaka tomu ho Google a generatívne AI ľahko citujú a zobrazujú.', 'relife' ); ?></p></div>
		<div class="rl-adv-offer"><span class="rl-adv-why__n" aria-hidden="true">04</span><h3><?php esc_html_e( 'Transparentné', 'relife' ); ?></h3><p><?php esc_html_e( 'Spoluprácu vždy jasne označíme. Čitatelia nám veria, a preto veria aj partnerom, ktorých odporúčame.', 'relife' ); ?></p></div>
	</div>
</section>

<section class="rl-wrap rl-block">
	<div class="rl-sechead"><h2 class="rl-sechead__title"><?php esc_html_e( 'Ako sa môžeme spojiť', 'relife' ); ?></h2></div>
	<div class="rl-grid rl-grid--3 rl-adv-offers">
		<div class="rl-adv-offer"><h3><?php esc_html_e( 'Sponzorovaný článok', 'relife' ); ?></h3><p><?php esc_html_e( 'Redakčne spracovaný obsah v štýle RELIFE, jasne označený ako spolupráca.', 'relife' ); ?></p></div>
		<div class="rl-adv-offer"><h3><?php esc_html_e( 'Bannery a reklamné plochy', 'relife' ); ?></h3><p><?php esc_html_e( 'Reklama v texte článkov a na úvodnej stránke, ktorá nenarúša čítanie.', 'relife' ); ?></p></div>
		<div class="rl-adv-offer"><h3><?php esc_html_e( 'Newsletter Weekly', 'relife' ); ?></h3><p><?php esc_html_e( 'Zmienka alebo odporúčanie vo výbere týždňa pre naše čitateľky a čitateľov.', 'relife' ); ?></p></div>
	</div>
</section>

<section id="kontakt" class="rl-wrap rl-block">
	<div class="rl-sechead"><h2 class="rl-sechead__title"><?php esc_html_e( 'Kontakt', 'relife' ); ?></h2></div>
	<?php if ( 'ok' === $status ) : ?>
		<p class="rl-adv-note rl-adv-note--ok" role="status"><?php esc_html_e( 'Ďakujeme, dopyt sme prijali. Ozveme sa vám čo najskôr.', 'relife' ); ?></p>
	<?php elseif ( 'err' === $status ) : ?>
		<p class="rl-adv-note rl-adv-note--err" role="alert"><?php esc_html_e( 'Správu sa nepodarilo odoslať. Skontrolujte údaje a skúste to o chvíľu znova.', 'relife' ); ?></p>
	<?php endif; ?>
	<form class="rl-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="relife_adv">
		<?php wp_nonce_field( 'relife_adv', 'relife_adv_nonce' ); ?>
		<p class="rl-form__hp" aria-hidden="true"><label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
		<div class="rl-form__row">
			<label class="rl-field"><span><?php esc_html_e( 'Meno', 'relife' ); ?></span><input type="text" name="name" required autocomplete="name"></label>
			<label class="rl-field"><span><?php esc_html_e( 'E-mail', 'relife' ); ?></span><input type="email" name="email" required autocomplete="email"></label>
		</div>
		<div class="rl-form__row">
			<label class="rl-field"><span><?php esc_html_e( 'Firma (voliteľné)', 'relife' ); ?></span><input type="text" name="company" autocomplete="organization"></label>
			<label class="rl-field"><span><?php esc_html_e( 'Záujem', 'relife' ); ?></span>
				<select name="topic">
					<option><?php esc_html_e( 'Sponzorovaný článok', 'relife' ); ?></option>
					<option><?php esc_html_e( 'Bannery a reklamné plochy', 'relife' ); ?></option>
					<option><?php esc_html_e( 'Newsletter Weekly', 'relife' ); ?></option>
					<option><?php esc_html_e( 'Iné', 'relife' ); ?></option>
				</select>
			</label>
		</div>
		<label class="rl-field"><span><?php esc_html_e( 'Správa', 'relife' ); ?></span><textarea name="message" rows="5" required></textarea></label>
		<button type="submit" class="rl-pill rl-pill--accent"><?php esc_html_e( 'Odoslať dopyt', 'relife' ); ?></button>
	</form>
</section>
<?php
get_footer();
