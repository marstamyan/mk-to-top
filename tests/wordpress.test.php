<?php
/**
 * Run against a disposable WordPress database with the plugin active:
 * wp eval-file wp-content/plugins/mk-to-top/tests/wordpress.test.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! function_exists( 'mk_to_top_get_options' ) ) {
	fwrite( STDERR, "Run this file with WP-CLI and MK To Top active.\n" );
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/admin/mk-to-top-admin-settings.php';
mk_to_top_register_settings();

$original = get_option( 'mk_to_top_options', null );
$checks   = 0;
$check    = function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$checks;
};

set_error_handler( function ( $severity, $message, $file, $line ) {
	if ( error_reporting() & $severity ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	return false;
} );

try {
	delete_option( 'mk_to_top_options' );
	$check( mk_to_top_get_options() === mk_to_top_get_defaults(), 'Missing options must render defaults.' );

	$input = mk_to_top_get_defaults();
	unset( $input['btn_mobile'] );
	update_option( 'mk_to_top_options', $input );
	$check( '0' === get_option( 'mk_to_top_options' )['btn_mobile'], 'First save must keep an unchecked mobile setting disabled.' );
	update_option( 'mk_to_top_options', get_option( 'mk_to_top_options' ) );
	$check( '0' === get_option( 'mk_to_top_options' )['btn_mobile'], 'Repeated sanitization must keep mobile disabled.' );

	update_option( 'mk_to_top_options', [
		'btn_width' => '-20', 'btn_height' => '999999',
		'btn_offset' => '-5', 'btn_show_at' => '999999',
		'btn_icon_color' => [], 'btn_bg_color' => 'rgb(999,0,0)',
		'btn_position' => [], 'unknown' => 'discard',
	] );
	$actual = get_option( 'mk_to_top_options' );
	$check( 20 === $actual['btn_width'] && 200 === $actual['btn_height'], 'Dimensions must be bounded in the database.' );
	$check( 0 === $actual['btn_offset'] && 5000 === $actual['btn_show_at'], 'Offset and threshold must be bounded in the database.' );
	$check( '#ffffff' === $actual['btn_icon_color'] && '#000000' === $actual['btn_bg_color'], 'Invalid color values must use safe defaults.' );
	$check( 'right' === $actual['btn_position'] && ! isset( $actual['unknown'] ), 'Invalid positions and unknown keys must not be saved.' );

	$input = array_merge( mk_to_top_get_defaults(), [ 'btn_position' => 'left', 'btn_bg_color' => 'rgba(10, 20, 30, 0.5)' ] );
	update_option( 'mk_to_top_options', $input );
	$check( $input === get_option( 'mk_to_top_options' ), 'Supported existing settings must survive saving.' );

	ob_start();
	( new MK_To_Top_Public() )->render_scroll_button();
	$markup = ob_get_clean();
	$check( false !== strpos( $markup, 'type="button"' ) && false !== strpos( $markup, 'hidden tabindex="-1"' ), 'Initial button must be hidden and non-submitting.' );
	$check( false !== strpos( $markup, 'data-mk-btn-position="left"' ) && false !== strpos( $markup, 'data-mk-btn-mobile="true"' ), 'Saved options must reach the frontend.' );

	WP_CLI::success( "{$checks} WordPress integration checks passed on WordPress " . get_bloginfo( 'version' ) . ', PHP ' . PHP_VERSION . '.' );
} finally {
	restore_error_handler();
	if ( null === $original ) {
		delete_option( 'mk_to_top_options' );
	} else {
		update_option( 'mk_to_top_options', $original );
	}
}
