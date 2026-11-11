<?php
/**
 * Reklama Google AdSense: jednotka v strede článku, možnosť vypnúť pre vybrané články.
 *
 * @package Relife
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function relife_adsense_client(): string {
	$id = strtolower( trim( (string) get_theme_mod( 'relife_adsense_client', '' ) ) );
	if ( preg_match( '/^pub-\d{8,20}$/', $id ) ) {
		$id = 'ca-' . $id;
	}
	return (bool) preg_match( '/^ca-pub-\d{8,20}$/', $id ) ? $id : '';
}

function relife_adsense_slot(): string {
	$slot = trim( (string) get_theme_mod( 'relife_adsense_slot', '' ) );
	return (bool) preg_match( '/^\d{6,20}$/', $slot ) ? $slot : '';
}

/**
 * Smie sa na tejto stránke zobraziť reklama?
 */
function relife_ads_code( string $which ): string {
	return trim( (string) get_theme_mod( 'relife_ads_code_' . $which, '' ) );
}

function relife_ads_enabled(): bool {
	if ( ( '' === relife_adsense_client() && '' === relife_ads_code( 'head' ) && '' === relife_ads_code( 'unit' ) ) || is_admin() || is_feed() || is_customize_preview() || is_preview() ) {
		return false;
	}
	if ( ! relife_is_cmp_test() && get_theme_mod( 'relife_ads_skip_admins', false ) && is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return false;
	}
	if ( is_singular() && get_post_meta( (int) get_queried_object_id(), 'relife_no_ads', true ) ) {
		return false;
	}
	return (bool) apply_filters( 'relife_ads_enabled', true );
}

/**
 * HTML reklamnej jednotky (rezervované miesto, aby sa stránka neposúvala).
 */
function relife_ad_unit_html(): string {
	$code = relife_ads_code( 'unit' );
	if ( '' !== $code ) {
		return '<div class="rl-ad" aria-label="' . esc_attr__( 'Reklama', 'relife' ) . '"><span class="rl-ad__label">' . esc_html__( 'Reklama', 'relife' ) . '</span>' . $code . '</div>';
	}
	$slot = relife_adsense_slot();
	return '<div class="rl-ad" aria-label="' . esc_attr__( 'Reklama', 'relife' ) . '"><span class="rl-ad__label">' . esc_html__( 'Reklama', 'relife' ) . '</span>'
		. '<ins class="adsbygoogle" style="display:block;text-align:center" data-ad-layout="in-article" data-ad-format="fluid" data-ad-client="' . esc_attr( relife_adsense_client() ) . '" data-ad-slot="' . esc_attr( $slot ) . '"></ins></div>';
}

/**
 * Vloží reklamu do textu článku (po zadanom odseku, predvolene približne do stredu).
 * Beží pred spracovaním blokov, takže pracuje s vrchnými blokmi a nerozbije zoznamy ani citácie.
 */
function relife_insert_ad_in_content( string $content ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || ! relife_ads_enabled() ) {
		return $content;
	}
	if ( '' === relife_adsense_slot() && '' === relife_ads_code( 'unit' ) ) {
		return $content . "\n<!-- RELIFE reklama: v strede článku sa nevkladá, lebo nie je zadané ID jednotky ani kód reklamy -->";
	}
	if ( ! apply_filters( 'relife_ad_in_content', true ) ) {
		return $content;
	}

	/*
	 * Pracuje s hotovým HTML (po bloku aj po wpautop), preto funguje pre blokový aj starý („klasický“, vložený z iného systému) obsah.
	 * Odseky sa počítajú len na najvyššej úrovni (nie v zoznamoch, citáciách, tabuľkách ani obrázkoch).
	 */
	$void   = array( 'img', 'br', 'hr', 'input', 'meta', 'link', 'source', 'area', 'col', 'embed', 'param', 'track', 'wbr' );
	$ends   = array(); // pozície konca odsekov na najvyššej úrovni
	$depth  = 0;
	if ( preg_match_all( '#<script\b.*?</script>|<style\b.*?</style>|<!--.*?-->|<(/?)([a-z][a-z0-9]*)\b[^>]*?(/?)>#is', $content, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m as $t ) {
			if ( ! isset( $t[2] ) || '' === $t[2][0] ) {
				continue; // skript, štýl alebo komentár
			}
			$tag     = strtolower( $t[2][0] );
			$closing = '/' === $t[1][0];
			$self    = '/' === $t[3][0];
			if ( in_array( $tag, $void, true ) || $self ) {
				continue;
			}
			if ( $closing ) {
				$depth = max( 0, $depth - 1 );
				if ( 'p' === $tag && 0 === $depth ) {
					$ends[] = $t[0][1] + strlen( $t[0][0] );
				}
			} else {
				++$depth;
			}
		}
	}
	$min = (int) apply_filters( 'relife_ad_min_paragraphs', 4 );
	if ( count( $ends ) < $min ) {
		return $content . "\n<!-- RELIFE reklama: preskočená, v článku je málo odsekov (" . count( $ends ) . ' < ' . $min . ') -->';
	}
	$n   = (int) get_theme_mod( 'relife_ads_after_paragraph', 0 );
	$n   = $n > 0 ? min( $n, count( $ends ) - 1 ) : (int) floor( count( $ends ) / 2 );
	$pos = $ends[ max( 1, $n ) - 1 ];

	return substr( $content, 0, $pos ) . "\n" . relife_ad_unit_html() . "\n" . substr( $content, $pos );
}
add_filter( 'the_content', 'relife_insert_ad_in_content', 20 );

/**
 * Skript AdSense sa načíta až po načítaní stránky (nebrzdí LCP).
 */
function relife_adsense_head_code(): void {
	if ( '' !== relife_ads_code( 'head' ) && relife_ads_enabled() ) {
		echo "<!-- AdSense -->\n" . relife_ads_code( 'head' ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- kód vkladá administrátor
	}
}
add_action( 'wp_head', 'relife_adsense_head_code', 6 );

function relife_adsense_loader(): void {
	if ( ! relife_ads_enabled() || '' === relife_adsense_client() || '' !== relife_ads_code( 'head' ) || '' !== relife_ads_code( 'unit' ) ) {
		return;
	}
	$src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode( relife_adsense_client() );
	echo "<script>(function(){var u=" . wp_json_encode( $src ) . ";var ins=document.querySelectorAll('ins.adsbygoogle');for(var i=0;i<ins.length;i++){(window.adsbygoogle=window.adsbygoogle||[]).push({})}var l=function(){var s=document.createElement('script');s.async=true;s.crossOrigin='anonymous';s.src=u;document.head.appendChild(s)};var r=function(){if('requestIdleCallback' in window){requestIdleCallback(l,{timeout:3000})}else{setTimeout(l,1500)}};if(document.readyState==='complete'){r()}else{window.addEventListener('load',r)}})();</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_footer', 'relife_adsense_loader', 20 );

/**
 * ads.txt (vyžaduje AdSense), ak súbor na serveri neexistuje.
 */
function relife_serve_ads_txt(): void {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( '/ads.txt' !== $path || '' === relife_adsense_client() ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo 'google.com, ' . esc_html( str_replace( 'ca-', '', relife_adsense_client() ) ) . ", DIRECT, f08c47fec0942fa0\n";
	exit;
}
add_action( 'parse_request', 'relife_serve_ads_txt' );

/**
 * Pole „Nezobrazovať reklamy“ pri článku.
 */
function relife_register_ads_meta(): void {
	register_post_meta(
		'post',
		'relife_no_ads',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'default'           => false,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
		)
	);
}
add_action( 'init', 'relife_register_ads_meta' );

function relife_ads_metabox(): void {
	add_meta_box(
		'relife_ads',
		__( 'Reklamy', 'relife' ),
		static function ( WP_Post $post ): void {
			wp_nonce_field( 'relife_ads_save', 'relife_ads_nonce' );
			echo '<label><input type="checkbox" name="relife_no_ads" value="1"' . checked( (bool) get_post_meta( $post->ID, 'relife_no_ads', true ), true, false ) . '> ' . esc_html__( 'Nezobrazovať reklamy v tomto článku', 'relife' ) . '</label>';
		},
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'relife_ads_metabox' );

function relife_ads_save( int $post_id ): void {
	if ( ! isset( $_POST['relife_ads_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['relife_ads_nonce'] ) ), 'relife_ads_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! empty( $_POST['relife_no_ads'] ) ) {
		update_post_meta( $post_id, 'relife_no_ads', 1 );
	} else {
		delete_post_meta( $post_id, 'relife_no_ads' );
	}
}
add_action( 'save_post_post', 'relife_ads_save' );

/**
 * Customizer: Reklamy.
 */
function relife_ads_customize( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section( 'relife_ads', array( 'title' => __( 'RELIFE – Reklamy (AdSense)', 'relife' ), 'priority' => 31 ) );

	$raw = static fn( $v ): string => current_user_can( 'unfiltered_html' ) ? (string) $v : wp_kses_post( (string) $v );
	$wp_customize->add_setting( 'relife_ads_code_head', array( 'default' => '', 'sanitize_callback' => $raw ) );
	$wp_customize->add_control( 'relife_ads_code_head', array(
		'label'       => __( 'Celý kód AdSense do hlavičky (voliteľné)', 'relife' ),
		'description' => __( 'Vložte celý skript z AdSense (<script async src="…adsbygoogle.js?client=ca-pub-…"></script>). Použije sa namiesto ID nižšie.', 'relife' ),
		'section'     => 'relife_ads',
		'type'        => 'textarea',
	) );
	$wp_customize->add_setting( 'relife_ads_code_unit', array( 'default' => '', 'sanitize_callback' => $raw ) );
	$wp_customize->add_control( 'relife_ads_code_unit', array(
		'label'       => __( 'Celý kód reklamy do stredu článku', 'relife' ),
		'description' => __( 'Vložte celý kód reklamnej jednotky z AdSense (<ins class="adsbygoogle" …></ins> a <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>). Vloží sa do stredu každého článku.', 'relife' ),
		'section'     => 'relife_ads',
		'type'        => 'textarea',
	) );

	$wp_customize->add_setting( 'relife_adsense_client', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'relife_adsense_client', array(
		'label'       => __( 'AdSense ID vydavateľa', 'relife' ),
		'description' => __( 'Tvar ca-pub-1234567890123456 (nájdete v AdSense → Účet). Skript sa načíta len v prípade, že je vyplnené. Súbor ads.txt vytvorí téma sama.', 'relife' ),
		'section'     => 'relife_ads',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'relife_adsense_slot', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'relife_adsense_slot', array(
		'label'       => __( 'ID reklamnej jednotky v článku (data-ad-slot)', 'relife' ),
		'description' => __( 'AdSense → Reklamy → Podľa reklamných jednotiek → Reklama v texte. Ak je prázdne, reklama sa do článku nevkladá (zostanú len automatické reklamy, ak ich zapnete v AdSense).', 'relife' ),
		'section'     => 'relife_ads',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'relife_ads_after_paragraph', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'relife_ads_after_paragraph', array(
		'label'       => __( 'Vložiť reklamu po odseku číslo', 'relife' ),
		'description' => __( '0 = približne v strede článku. Články s menej ako 4 odsekmi reklamu v texte nemajú.', 'relife' ),
		'section'     => 'relife_ads',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 0, 'max' => 50 ),
	) );

	$wp_customize->add_setting( 'relife_ads_skip_admins', array( 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$wp_customize->add_control( 'relife_ads_skip_admins', array(
		'label'   => __( 'Neukazovať reklamy prihláseným redaktorom (ochrana pred neplatnými kliknutiami)', 'relife' ),
		'section' => 'relife_ads',
		'type'    => 'checkbox',
	) );
}
add_action( 'customize_register', 'relife_ads_customize' );
