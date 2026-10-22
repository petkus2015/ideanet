<?php
/**
 * Diskusia pod príspevkom.
 *
 * Web je jednostránkový, komentáre sa tu bežne nepoužívajú — šablóna tu
 * je preto, lebo bez nej WordPress hlási, že téma je neúplná, a aby
 * diskusia vyzerala ako zvyšok webu, keby ju niekto zapol.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section class="comments" id="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments__title">
			<?php
			$count = get_comments_number();
			if ( '1' === (string) $count ) {
				esc_html_e( 'Jeden komentár', 'ideanet' );
			} else {
				printf(
					/* translators: %s: počet komentárov. */
					esc_html( _n( '%s komentár', '%s komentárov', $count, 'ideanet' ) ),
					esc_html( number_format_i18n( $count ) )
				);
			}
			?>
		</h2>

		<ol class="comments__list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 44,
				)
			);
			?>
		</ol>

		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="comments__closed"><?php esc_html_e( 'Diskusia je uzavretá.', 'ideanet' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_submit'  => 'btn btn--primary',
			'title_reply'   => __( 'Napíšte komentár', 'ideanet' ),
			'label_submit'  => __( 'Odoslať komentár', 'ideanet' ),
		)
	);
	?>
</section>
