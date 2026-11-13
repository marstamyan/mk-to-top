<?php
/**
 * Shared defaults and validation for saved and rendered options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mk_to_top_get_defaults() {
	return [
		'btn_width'      => 40,
		'btn_height'     => 40,
		'btn_icon_color' => '#ffffff',
		'btn_bg_color'   => '#000000',
		'btn_position'   => 'right',
		'btn_offset'     => 30,
		'btn_show_at'    => 300,
		'btn_mobile'     => '1',
	];
}

function mk_to_top_sanitize_integer( $value, $default, $min, $max ) {
	if ( ! is_int( $value ) && ! is_string( $value ) ) {
		return $default;
	}

	$value = trim( (string) $value );
	if ( ! preg_match( '/^-?\d+$/D', $value ) ) {
		return $default;
	}

	return (int) max( $min, min( $max, (float) $value ) );
}

function mk_to_top_sanitize_color( $value, $default ) {
	if ( ! is_string( $value ) ) {
		return $default;
	}

	$value = strtolower( trim( $value ) );
	$hex   = sanitize_hex_color( $value );
	if ( $hex ) {
		return $hex;
	}

	// Preserve valid rgb/rgba values saved by version 1.0.
	if ( ! preg_match( '/^(rgb|rgba)\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(0(?:\.\d+)?|1(?:\.0+)?|\.\d+)\s*)?\)$/D', $value, $matches ) ) {
		return $default;
	}

	$has_alpha = isset( $matches[5] );
	if ( ( 'rgba' === $matches[1] ) !== $has_alpha ) {
		return $default;
	}
	foreach ( [ 2, 3, 4 ] as $channel ) {
		if ( (int) $matches[ $channel ] > 255 ) {
			return $default;
		}
	}

	return $value;
}

function mk_to_top_normalize_options( $input ) {
	$defaults = mk_to_top_get_defaults();
	$input    = is_array( $input ) ? $input : [];
	$options  = $defaults;
	$ranges   = [
		'btn_width'   => [ 20, 200 ],
		'btn_height'  => [ 20, 200 ],
		'btn_offset'  => [ 0, 500 ],
		'btn_show_at' => [ 0, 5000 ],
	];

	foreach ( $ranges as $key => $range ) {
		$options[ $key ] = mk_to_top_sanitize_integer( $input[ $key ] ?? $defaults[ $key ], $defaults[ $key ], $range[0], $range[1] );
	}
	foreach ( [ 'btn_icon_color', 'btn_bg_color' ] as $key ) {
		$options[ $key ] = mk_to_top_sanitize_color( $input[ $key ] ?? $defaults[ $key ], $defaults[ $key ] );
	}
	if ( isset( $input['btn_position'] ) && in_array( $input['btn_position'], [ 'left', 'right' ], true ) ) {
		$options['btn_position'] = $input['btn_position'];
	}
	if ( isset( $input['btn_mobile'] ) ) {
		$options['btn_mobile'] = in_array( $input['btn_mobile'], [ true, 1, '1' ], true ) ? '1' : '0';
	}

	return $options;
}

function mk_to_top_get_options() {
	return mk_to_top_normalize_options( get_option( 'mk_to_top_options', [] ) );
}

function mk_to_top_sanitize_options( $input ) {
	if ( ! is_array( $input ) ) {
		return mk_to_top_get_options();
	}

	// An unchecked checkbox is omitted by the browser. Explicit '0' must also
	// stay disabled when WordPress sanitizes a new option a second time.
	$input['btn_mobile'] = $input['btn_mobile'] ?? '0';
	return mk_to_top_normalize_options( $input );
}
