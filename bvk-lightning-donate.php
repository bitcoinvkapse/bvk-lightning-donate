<?php
/**
 * Plugin Name:       BVK Lightning Donate
 * Plugin URI:        https://bitcoinvkapse.cz/bvk-lightning-donate/
 * Description:       Lightning Network donate widget zobrazující se pod články. Přijímej satoshi přímo z webu přes Lightning Address.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Tomáš Krause
 * Author URI:        https://bitcoinvkapse.cz/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       bvk-lightning-donate
 * Domain Path:       /languages
 */

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License.
 *
 * See LICENSE file or https://opensource.org/licenses/MIT for full text.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BVKLD_VERSION', '1.1.0' );
define( 'BVKLD_PATH', plugin_dir_path( __FILE__ ) );
define( 'BVKLD_URL', plugin_dir_url( __FILE__ ) );
define( 'BVKLD_FILE', __FILE__ );

add_action( 'plugins_loaded', function() {
	$locale = apply_filters( 'plugin_locale', determine_locale(), 'bvk-lightning-donate' );
	load_textdomain( 'bvk-lightning-donate', plugin_dir_path( __FILE__ ) . 'languages/' . $locale . '.mo' );
} );

require_once BVKLD_PATH . 'includes/settings.php';
require_once BVKLD_PATH . 'includes/widget.php';

function bvkld_is_enabled() {
	return (bool) get_option( 'bvkld_enabled', 0 );
}

add_shortcode( 'bvk-lightning-donate', 'bvkld_shortcode_handler' );
function bvkld_shortcode_handler() {
	if ( ! bvkld_is_enabled() ) {
		return '';
	}
	$address = bvkld_get_lightning_address();
	if ( empty( $address ) ) {
		return '';
	}
	return bvkld_render_widget();
}

add_filter( 'the_content', 'bvkld_append_widget_to_content', 20 );
function bvkld_append_widget_to_content( $content ) {
	if ( ! bvkld_is_enabled() ) {
		return $content;
	}

	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$address = bvkld_get_lightning_address();
	if ( empty( $address ) ) {
		return $content;
	}

	$post_types = (array) get_option( 'bvkld_post_types', array( 'post' ) );
	if ( ! in_array( get_post_type(), $post_types, true ) ) {
		return $content;
	}

	if ( bvkld_post_has_shortcode() ) {
		return $content;
	}

	return $content . bvkld_render_widget();
}

function bvkld_post_has_shortcode() {
	$post = get_post();
	return $post && has_shortcode( $post->post_content, 'bvk-lightning-donate' );
}

function bvkld_get_lightning_address() {
	$address = get_option( 'bvkld_lightning_address', '' );
	return apply_filters( 'bvkld_lightning_address', $address, get_post() );
}

add_action( 'wp_enqueue_scripts', 'bvkld_enqueue_assets' );
function bvkld_enqueue_assets() {
	if ( ! bvkld_is_enabled() ) {
		return;
	}

	if ( ! is_singular() ) {
		return;
	}

	$address = bvkld_get_lightning_address();
	if ( empty( $address ) ) {
		return;
	}

	$post_types = (array) get_option( 'bvkld_post_types', array( 'post' ) );
	$post_type  = get_post_type();
	$is_auto    = in_array( $post_type, $post_types, true );

	if ( ! $is_auto && ! bvkld_post_has_shortcode() ) {
		return;
	}

	wp_enqueue_script( 'bvkld-qrcode', BVKLD_URL . 'assets/qrcode.min.js', array(), BVKLD_VERSION, true );
	wp_enqueue_script( 'bvkld-donate', BVKLD_URL . 'assets/donate.js', array( 'bvkld-qrcode' ), BVKLD_VERSION, true );
	wp_enqueue_style( 'bvkld-donate', BVKLD_URL . 'assets/donate.css', array(), BVKLD_VERSION );

	$amounts_raw = get_option( 'bvkld_amounts', '1000,5000,21000' );
	$amounts     = bvkld_parse_amounts( $amounts_raw );

	$post_slug = get_post_field( 'post_name', get_post() );

	wp_localize_script( 'bvkld-donate', 'bvkldConfig', array(
		'address'  => $address,
		'amounts'  => $amounts,
		'pageSlug' => $post_slug ? $post_slug : '',
		'i18n'    => array(
			'copied'        => __( 'Copied!', 'bvk-lightning-donate' ),
			'copyFailed'    => __( 'Copy failed', 'bvk-lightning-donate' ),
			'errorGeneric'  => __( 'Could not load payment details. Copy the Lightning address and send manually.', 'bvk-lightning-donate' ),
			'errorAmount'   => __( 'Enter a valid amount in sats', 'bvk-lightning-donate' ),
			'errorRange'    => __( 'Amount is outside the wallet\'s allowed range', 'bvk-lightning-donate' ),
			'scanQR'        => __( 'Scan QR with your mobile Lightning wallet', 'bvk-lightning-donate' ),
			'loading'       => __( 'Loading…', 'bvk-lightning-donate' ),
		),
	) );

	$primary    = bvkld_sanitize_hex_color( get_option( 'bvkld_primary_color', '#f7931a' ), '#f7931a' );
	$background = bvkld_sanitize_hex_color( get_option( 'bvkld_background_color', '#fdf4e3' ), '#fdf4e3' );

	$custom_css = sprintf(
		'.bvkld-wrap{--bvkld-primary:%s;--bvkld-bg:%s;}',
		$primary,
		$background
	);
	wp_add_inline_style( 'bvkld-donate', $custom_css );
}

function bvkld_parse_amounts( $raw ) {
	$parts   = array_map( 'trim', explode( ',', (string) $raw ) );
	$amounts = array();
	foreach ( $parts as $p ) {
		$n = intval( $p );
		if ( $n > 0 ) {
			$amounts[] = $n;
		}
	}
	if ( empty( $amounts ) ) {
		$amounts = array( 1000, 5000, 21000 );
	}
	return $amounts;
}

function bvkld_sanitize_hex_color( $color, $fallback = '#000000' ) {
	$color = trim( (string) $color );
	if ( preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color ) ) {
		return $color;
	}
	return $fallback;
}
