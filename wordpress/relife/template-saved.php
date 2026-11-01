<?php
/**
 * Template Name: Uložené články
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="rl-wrap rl-block">
	<p class="rl-kicker"><?php esc_html_e( 'TVOJA KNIŽNICA', 'relife' ); ?></p>
	<h1 class="rl-pagetitle"><?php esc_html_e( 'Uložené', 'relife' ); ?></h1>
	<p class="rl-muted rl-lead"><?php esc_html_e( 'Články ukladáme len v cache tvojho prehliadača. Nesynchronizujú sa medzi zariadeniami.', 'relife' ); ?></p>
	<div class="rl-grid rl-grid--4 js-saved-list" aria-live="polite"></div>
	<p class="rl-muted js-saved-empty" hidden><?php esc_html_e( 'Zatiaľ nemáš nič uložené. Klepni na záložku pri článku.', 'relife' ); ?></p>
	<noscript><p class="rl-muted"><?php esc_html_e( 'Na zobrazenie uložených článkov zapni JavaScript.', 'relife' ); ?></p></noscript>
</section>
<?php
get_footer();
