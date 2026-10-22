<?php
/**
 * Spracovanie kontaktného formulára.
 *
 * Dopyt sa vždy najprv uloží ako položka v administrácii a až potom sa
 * posiela e-mail. Keď hosting e-maily neodošle, dopyt sa nestratí.
 *
 * @package IDEANET
 */

defined( 'ABSPATH' ) || exit;

/**
 * Overí a spracuje odoslaný formulár.
 *
 * @return array{ok:bool,message:string,errors:array<string,string>}
 */
function ideanet_handle_form() {
	$fail = function ( $message, $errors = array() ) {
		return array(
			'ok'      => false,
			'message' => $message,
			'errors'  => $errors,
		);
	};

	$nonce = isset( $_POST['ideanet_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ideanet_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'ideanet_dopyt' ) ) {
		return $fail( __( 'Platnosť formulára vypršala. Obnovte stránku a skúste to znova.', 'ideanet' ) );
	}

	/* Pasca na roboty: pole je skryté, človek ho nevyplní. */
	$trap = isset( $_POST['ideanet_web'] ) ? trim( (string) wp_unslash( $_POST['ideanet_web'] ) ) : '';
	if ( '' !== $trap ) {
		return array(
			'ok'      => true,
			'message' => ideanet_tokens( ideanet_opt( 'form_ok' ) ),
			'errors'  => array(),
		);
	}

	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
	$msg   = isset( $_POST['msg'] ) ? sanitize_textarea_field( wp_unslash( $_POST['msg'] ) ) : '';

	$errors = array();
	if ( '' === $name ) {
		$errors['name'] = __( 'Doplňte meno alebo názov značky.', 'ideanet' );
	}
	if ( ! is_email( $email ) ) {
		$errors['email'] = __( 'Zadajte platný e-mail.', 'ideanet' );
	}
	if ( mb_strlen( $msg ) < 10 ) {
		$errors['msg'] = __( 'Napíšte aspoň pár viet o projekte.', 'ideanet' );
	}
	if ( $errors ) {
		return $fail( __( 'Skontrolujte prosím zvýraznené polia.', 'ideanet' ), $errors );
	}

	/* Jednoduchá brzda proti zaplaveniu formulára z jednej adresy. */
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'ideanet_dopyt_' . md5( $ip );
	if ( $ip && get_transient( $key ) ) {
		return $fail( __( 'Dopyt mi práve prišiel. Ak chcete poslať ďalší, skúste o minútu.', 'ideanet' ) );
	}
	set_transient( $key, 1, MINUTE_IN_SECONDS );

	/* translators: 1: meno odosielateľa, 2: o čo má záujem. */
	$title = sprintf( __( '%1$s — %2$s', 'ideanet' ), $name, $type ? $type : __( 'dopyt', 'ideanet' ) );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'ideanet_dopyt',
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_content' => $msg,
		),
		true
	);

	if ( ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_ideanet_od_mena', $name );
		update_post_meta( $post_id, '_ideanet_od_email', $email );
		update_post_meta( $post_id, '_ideanet_od_typ', $type );
		update_post_meta( $post_id, '_ideanet_od_ip', $ip );
	}

	$to = ideanet_opt( 'form_to' );
	if ( ! is_email( $to ) ) {
		$to = ideanet_opt( 'contact_email' );
	}
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$body = sprintf(
		"%s: %s\n%s: %s\n%s: %s\n\n%s\n",
		__( 'Meno', 'ideanet' ),
		$name,
		__( 'E-mail', 'ideanet' ),
		$email,
		__( 'O čo má záujem', 'ideanet' ),
		$type,
		$msg
	);

	$sent = wp_mail(
		$to,
		ideanet_opt( 'form_subject' ) . ' — ' . $name,
		$body,
		array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $name . ' <' . $email . '>',
		)
	);

	/* E-mail môže zlyhať na hostingu — dopyt je už uložený, takže
	   odosielateľovi to hlásime ako úspech len vtedy, keď sa podaril
	   aspoň jeden z dvoch krokov. */
	if ( ! $sent && is_wp_error( $post_id ) ) {
		return $fail( ideanet_tokens( ideanet_opt( 'form_err' ) ) );
	}

	return array(
		'ok'      => true,
		'message' => ideanet_tokens( ideanet_opt( 'form_ok' ) ),
		'errors'  => array(),
	);
}

/**
 * Odpoveď pre JavaScript.
 */
function ideanet_ajax_form() {
	$result = ideanet_handle_form();

	if ( $result['ok'] ) {
		wp_send_json_success( $result );
	}

	wp_send_json_error( $result );
}
add_action( 'wp_ajax_ideanet_dopyt', 'ideanet_ajax_form' );
add_action( 'wp_ajax_nopriv_ideanet_dopyt', 'ideanet_ajax_form' );

/**
 * To isté bez JavaScriptu — vráti sa späť na stránku s parametrom.
 */
function ideanet_post_form() {
	$result = ideanet_handle_form();
	$back   = wp_get_referer();
	if ( ! $back ) {
		$back = home_url( '/' );
	}

	$back = add_query_arg( 'dopyt', $result['ok'] ? 'ok' : 'chyba', remove_query_arg( 'dopyt', $back ) );

	wp_safe_redirect( $back . '#kontakt' );
	exit;
}
add_action( 'admin_post_ideanet_dopyt', 'ideanet_post_form' );
add_action( 'admin_post_nopriv_ideanet_dopyt', 'ideanet_post_form' );

/**
 * Zoznam možností do výberu „o čo máte záujem" — skladá sa z balíkov.
 *
 * @return array<string,string[]>
 */
function ideanet_form_options() {
	$groups = array(
		'firmy'    => ideanet_opt( 'tab_firmy' ),
		'agentury' => ideanet_opt( 'tab_agentury' ),
		'eventy'   => ideanet_opt( 'tab_eventy' ),
	);

	$out = array();

	foreach ( $groups as $group => $label ) {
		$items = array();
		foreach ( ideanet_items( 'ideanet_balik', 60, array( 'key' => '_ideanet_group', 'value' => $group ) ) as $post ) {
			$items[] = ideanet_balik_label( $post );
		}
		if ( $items ) {
			$out[ $label ] = $items;
		}
	}

	$out[ __( 'Vlastná zostava', 'ideanet' ) ] = array( __( 'Vlastná zostava podľa kalkulačky', 'ideanet' ) );
	$out[ __( 'Iné', 'ideanet' ) ]             = array( __( 'Zatiaľ neviem, poraďte mi', 'ideanet' ) );

	return $out;
}

/**
 * Popis balíka tak, ako sa objaví vo výbere a v e-maile.
 *
 * @param WP_Post $post Balík.
 * @return string
 */
function ideanet_balik_label( $post ) {
	$price = ideanet_meta( $post->ID, 'price' );
	$unit  = ideanet_meta( $post->ID, 'unit' );
	$name  = get_the_title( $post );

	if ( ! $price ) {
		return $name;
	}

	return trim( $name . ' — ' . $price . ( $unit ? ' ' . $unit : '' ) );
}
