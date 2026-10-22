<?php
/**
 * Cenník — výpis balíkov.
 *
 * Všetky balíky sa načítajú jedným dotazom a triedia sa v PHP. Je ich
 * rádovo desiatky, takže je to lacnejšie než dotaz na každý blok zvlášť.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Všetky balíky, načítané raz za načítanie stránky.
 *
 * @return WP_Post[]
 */
function ideanet_all_baliky() {
	static $all = null;

	if ( null === $all ) {
		$all = ideanet_items( 'ideanet_balik', 200 );
	}

	return $all;
}

/**
 * Balíky vyfiltrované podľa záložky, podoby a bloku.
 *
 * @param string $group  Záložka cenníka.
 * @param string $layout plan alebo tier.
 * @param string $block  Názov bloku, prázdny = hlavný zoznam.
 * @return WP_Post[]
 */
function ideanet_baliky( $group, $layout, $block = '' ) {
	$out = array();

	foreach ( ideanet_all_baliky() as $post ) {
		if ( ideanet_meta( $post->ID, 'group' ) !== $group ) {
			continue;
		}
		$post_layout = ideanet_meta( $post->ID, 'layout' );
		if ( ( $post_layout ? $post_layout : 'tier' ) !== $layout ) {
			continue;
		}
		if ( ideanet_meta( $post->ID, 'block' ) !== $block ) {
			continue;
		}
		$out[] = $post;
	}

	return $out;
}

/**
 * Veľká karta balíka.
 *
 * @param WP_Post $post    Balík.
 * @param bool    $primary Či má mať plné tlačidlo.
 */
function ideanet_plan( $post, $primary = false ) {
	$best     = ideanet_meta( $post->ID, 'best' );
	$badge    = ideanet_meta( $post->ID, 'badge' );
	$features = array_filter( array_map( 'trim', preg_split( '/\R/', ideanet_meta( $post->ID, 'features' ) ) ) );
	$cta      = ideanet_meta( $post->ID, 'cta' );
	$label    = ideanet_balik_label( $post );
	?>
	<article class="plan<?php echo $best ? ' plan--best' : ''; ?>">
		<?php if ( $badge ) : ?>
			<span class="plan__badge"><?php echo esc_html( $badge ); ?></span>
		<?php endif; ?>
		<h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
		<p class="plan__price">
			<b><?php echo esc_html( ideanet_meta( $post->ID, 'price' ) ); ?></b>
			<span><?php echo esc_html( ideanet_meta( $post->ID, 'unit' ) ); ?></span>
		</p>
		<?php if ( ideanet_meta( $post->ID, 'desc' ) ) : ?>
			<p class="plan__desc"><?php echo esc_html( ideanet_meta( $post->ID, 'desc' ) ); ?></p>
		<?php endif; ?>
		<?php if ( $features ) : ?>
			<ul>
				<?php foreach ( $features as $feature ) : ?>
					<li><?php echo esc_html( $feature ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( '—' !== $cta ) : ?>
			<a class="btn <?php echo ( $best || $primary ) ? 'btn--primary' : 'btn--ghost'; ?>" href="#kontakt" data-package="<?php echo esc_attr( $label ); ?>">
				<?php echo esc_html( $cta ? $cta : __( 'Mám záujem', 'ideanet' ) ); ?>
			</a>
		<?php endif; ?>
	</article>
	<?php
}

/**
 * Úzky riadok s cenou vpravo.
 *
 * @param WP_Post $post Balík.
 */
function ideanet_tier( $post ) {
	$best  = ideanet_meta( $post->ID, 'best' );
	$star  = ideanet_meta( $post->ID, 'star' );
	$cta   = ideanet_meta( $post->ID, 'cta' );
	$label = ideanet_balik_label( $post );
	?>
	<li class="tier<?php echo $best ? ' tier--best' : ''; ?>">
		<div class="tier__main">
			<b>
				<?php echo esc_html( get_the_title( $post ) ); ?>
				<?php if ( $star ) : ?>
					<i class="tier__star"><?php echo esc_html( $star ); ?></i>
				<?php endif; ?>
			</b>
			<span><?php echo esc_html( ideanet_meta( $post->ID, 'desc' ) ); ?></span>
		</div>
		<span class="tier__price"><?php echo esc_html( ideanet_meta( $post->ID, 'price' ) ); ?></span>
		<?php if ( '—' !== $cta ) : ?>
			<a class="tier__cta" href="#kontakt" data-package="<?php echo esc_attr( $label ); ?>">
				<?php echo esc_html( $cta ? $cta : __( 'Dopyt', 'ideanet' ) ); ?>
			</a>
		<?php endif; ?>
	</li>
	<?php
}

/**
 * Blok riadkov s vlastným medzinadpisom.
 *
 * @param string $group  Záložka cenníka.
 * @param string $block  Názov bloku.
 * @param string $title  Kľúč nadpisu v Prispôsobiť.
 * @param string $note   Kľúč vety pod nadpisom.
 * @param string $foot   Kľúč poznámky pod zoznamom.
 */
function ideanet_tier_block( $group, $block, $title, $note = '', $foot = '' ) {
	$items = ideanet_baliky( $group, 'tier', $block );
	if ( ! $items ) {
		return;
	}
	?>
	<h3 class="sub reveal"><?php echo esc_html( ideanet_opt( $title ) ); ?></h3>
	<?php if ( $note && ideanet_opt( $note ) ) : ?>
		<p class="sub__note reveal"><?php ideanet_text( $note ); ?></p>
	<?php endif; ?>
	<ul class="tiers reveal" role="list">
		<?php foreach ( $items as $item ) : ?>
			<?php ideanet_tier( $item ); ?>
		<?php endforeach; ?>
	</ul>
	<?php if ( $foot && ideanet_opt( $foot ) ) : ?>
		<p class="panel__foot reveal"><?php ideanet_text( $foot ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Odkaz na celú ponuku na jednej strane.
 *
 * @param string $key Kľúč s adresou.
 */
function ideanet_panel_link( $key ) {
	$url = ideanet_opt( $key );
	if ( ! $url ) {
		return;
	}
	?>
	<p class="panel__link">
		<a href="<?php echo esc_url( $url ); ?>">
			<?php echo esc_html( ideanet_opt( 'ponuka_link_text' ) ); ?>
			<span aria-hidden="true">&#8594;</span>
		</a>
	</p>
	<?php
}

/**
 * Pri šablóne „Ponuka na jednu stranu" treba vedieť, ktorý cenník vypísať.
 */
function ideanet_ponuka_box() {
	add_meta_box(
		'ideanet-ponuka',
		__( 'Ponuka na jednu stranu', 'ideanet' ),
		'ideanet_ponuka_box_html',
		'page',
		'side'
	);
}
add_action( 'add_meta_boxes', 'ideanet_ponuka_box' );

/**
 * Obsah boxu.
 *
 * @param WP_Post $post Stránka.
 */
function ideanet_ponuka_box_html( $post ) {
	$value  = get_post_meta( $post->ID, '_ideanet_ponuka_group', true );
	$groups = array(
		'firmy'    => ideanet_opt( 'tab_firmy' ),
		'agentury' => ideanet_opt( 'tab_agentury' ),
		'eventy'   => ideanet_opt( 'tab_eventy' ),
	);

	wp_nonce_field( 'ideanet_ponuka', 'ideanet_ponuka_nonce' );

	echo '<p class="description">' . esc_html__( 'Platí len pre šablónu „Ponuka na jednu stranu". Ceny sa potom berú z Balíkov pre zvolenú záložku.', 'ideanet' ) . '</p>';
	echo '<select name="ideanet_ponuka_group" style="width:100%">';
	foreach ( $groups as $key => $label ) {
		printf(
			'<option value="%1$s"%2$s>%3$s</option>',
			esc_attr( $key ),
			selected( $value, $key, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}

/**
 * Uloží výber.
 *
 * @param int $post_id ID stránky.
 */
function ideanet_ponuka_save( $post_id ) {
	if ( ! isset( $_POST['ideanet_ponuka_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ideanet_ponuka_nonce'] ) ), 'ideanet_ponuka' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$value = isset( $_POST['ideanet_ponuka_group'] ) ? sanitize_key( wp_unslash( $_POST['ideanet_ponuka_group'] ) ) : '';
	if ( in_array( $value, array( 'firmy', 'agentury', 'eventy' ), true ) ) {
		update_post_meta( $post_id, '_ideanet_ponuka_group', $value );
	}
}
add_action( 'save_post_page', 'ideanet_ponuka_save' );

/**
 * Doplnok k mesačným balíkom (správa sociálnych sietí).
 */
function ideanet_addon_box() {
	if ( ! ideanet_opt( 'addon_title' ) ) {
		return;
	}
	?>
	<aside class="addon reveal">
		<div>
			<span class="addon__tag"><?php echo esc_html( ideanet_opt( 'addon_tag' ) ); ?></span>
			<h3><?php echo esc_html( ideanet_opt( 'addon_title' ) ); ?></h3>
			<p><?php ideanet_text( 'addon_text' ); ?></p>
		</div>
		<p class="addon__price">
			<?php echo esc_html( ideanet_opt( 'addon_price' ) ); ?>
			<span><?php echo esc_html( ideanet_opt( 'addon_unit' ) ); ?></span>
		</p>
	</aside>
	<?php
}

/**
 * Blok Partner pre agentúry.
 */
function ideanet_partner_box() {
	if ( ! ideanet_opt( 'partner_title' ) ) {
		return;
	}

	$items = ideanet_lines( 'partner_items' );
	$label = trim( ideanet_opt( 'partner_title' ) . ' — ' . ideanet_opt( 'partner_pre' ) . ' ' . ideanet_opt( 'partner_price' ) . ' ' . ideanet_opt( 'partner_unit' ) );
	?>
	<article class="partner reveal">
		<div class="partner__copy">
			<span class="partner__tag"><?php echo esc_html( ideanet_opt( 'partner_tag' ) ); ?></span>
			<h3><?php echo esc_html( ideanet_opt( 'partner_title' ) ); ?></h3>
			<p><?php ideanet_text( 'partner_text' ); ?></p>
			<?php if ( $items ) : ?>
				<ul role="list">
					<?php foreach ( $items as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="partner__price">
			<span><?php echo esc_html( ideanet_opt( 'partner_pre' ) ); ?></span>
			<b><?php echo esc_html( ideanet_opt( 'partner_price' ) ); ?></b>
			<span><?php echo esc_html( ideanet_opt( 'partner_unit' ) ); ?></span>
			<a class="btn btn--primary btn--full" href="<?php echo esc_url( is_front_page() ? '#kontakt' : home_url( '/#kontakt' ) ); ?>" data-package="<?php echo esc_attr( $label ); ?>">
				<?php echo esc_html( ideanet_opt( 'partner_cta' ) ); ?>
			</a>
			<p class="partner__note"><?php ideanet_text( 'partner_note' ); ?></p>
		</div>
	</article>
	<?php
}

/**
 * Karty „Dôležité pri white-label spolupráci".
 */
function ideanet_agency_notes() {
	$notes = ideanet_pairs( 'agentury_notes', 2 );
	if ( ! $notes ) {
		return;
	}
	?>
	<div class="notes reveal">
		<h3><?php echo esc_html( ideanet_opt( 'agentury_notes_title' ) ); ?></h3>
		<div class="notes__grid">
			<?php foreach ( $notes as $note ) : ?>
				<div>
					<h4><?php echo esc_html( $note[0] ); ?></h4>
					<p><?php echo esc_html( $note[1] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Sadzba za každú ďalšiu hodinu na evente.
 */
function ideanet_event_hour() {
	if ( ! ideanet_opt( 'eventy_hour_title' ) ) {
		return;
	}
	?>
	<ul class="tiers tiers--tight reveal" role="list">
		<li class="tier">
			<div class="tier__main">
				<b><?php echo esc_html( ideanet_opt( 'eventy_hour_title' ) ); ?></b>
				<span><?php echo esc_html( ideanet_opt( 'eventy_hour_note' ) ); ?></span>
			</div>
			<span class="tier__price"><?php echo esc_html( ideanet_opt( 'eventy_hour_price' ) ); ?></span>
		</li>
	</ul>
	<?php
}
