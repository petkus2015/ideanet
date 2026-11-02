<?php
/**
 * Doplnky pre jednotlivý článok v Avade: odpoveď v skratke, čas čítania, zdroje, označenie spolupráce.
 *
 * @package Relife_Avada
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rlav_single_filter( string $content ): string {
	if ( empty( rlav_option( 'single' ) ) || ! empty( rlav_option( 'article_template' ) ) || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$post_id    = (int) get_the_ID();
	$answer     = (string) get_post_meta( $post_id, 'relife_answer', true );
	$sources    = (string) get_post_meta( $post_id, 'relife_sources', true );
	$disclosure = (string) get_post_meta( $post_id, 'relife_disclosure', true );
	$section    = rlav_primary_section( $post_id );
	$format     = rlav_format_label( $post_id );

	ob_start();
	?>
	<div class="rl-scope rl-single-top">
		<div class="rl-article__tags">
			<?php if ( $section ) : ?><span class="rl-tag" style="background:<?php echo esc_attr( rlav_section_color( $section ) ); ?>"><?php echo esc_html( $section->name ); ?></span><?php endif; ?>
			<?php if ( '' !== $format ) : ?><span class="rl-tag rl-tag--accent"><?php echo esc_html( $format ); ?></span><?php endif; ?>
			<span class="rl-meta rl-single-top__time"><?php echo esc_html( sprintf( /* translators: %d minutes */ __( '%d min čítania', 'relife-avada' ), rlav_reading_time( $post_id ) ) ); ?></span>
			<span class="rl-single-top__save"><?php rlav_save_button( $post_id, 'rl-save--inline' ); ?></span>
		</div>
		<?php if ( '' !== $answer ) : ?>
			<div class="rl-answer">
				<div class="rl-kicker"><?php esc_html_e( 'ODPOVEĎ V SKRATKE', 'relife-avada' ); ?></div>
				<p><?php echo esc_html( $answer ); ?></p>
			</div>
		<?php endif; ?>
	</div>
	<?php
	$top = (string) ob_get_clean();

	$bottom = '';
	if ( '' !== $sources || '' !== $disclosure ) {
		ob_start();
		?>
		<div class="rl-scope rl-single-bottom">
			<div class="rl-sources">
				<?php if ( '' !== $sources ) : ?>
					<div class="rl-kicker rl-kicker--ink"><?php esc_html_e( 'ZDROJE', 'relife-avada' ); ?></div>
					<ol>
						<?php foreach ( preg_split( '/\r\n|\r|\n/', $sources ) ?: array() as $line ) : ?>
							<?php if ( '' !== trim( $line ) ) : ?><li><?php echo esc_html( trim( $line ) ); ?></li><?php endif; ?>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
				<?php if ( '' !== $disclosure ) : ?>
					<p class="rl-disclosure"><?php echo esc_html( $disclosure ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		$bottom = (string) ob_get_clean();
	}
	return $top . $content . $bottom;
}
add_filter( 'the_content', 'rlav_single_filter', 12 );

/**
 * Vlastná šablóna článku RELIFE (vypnuteľná v nastaveniach).
 */
function rlav_single_template( string $template ): string {
	if ( ! empty( rlav_option( 'article_template' ) ) && is_singular( 'post' ) ) {
		$file = RLAV_DIR . 'templates/single-post.php';
		if ( is_readable( $file ) ) {
			return $file;
		}
	}
	return $template;
}
add_filter( 'template_include', 'rlav_single_template', 99 );
