<?php
/**
 * Dependency-free uninstall checks, with isolated processes for constants/exit.
 * Run: php tests/uninstall.test.php
 */

error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
	throw new ErrorException( $message, 0, $severity, $file, $line );
} );

$mode = isset( $argv[1] ) ? $argv[1] : 'all';
$checks = 0;

function check_same( $expected, $actual, $message ) {
	global $checks;
	++$checks;
	if ( $expected !== $actual ) {
		throw new RuntimeException( $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) );
	}
}

if ( 'all' === $mode ) {
	$total = 0;
	foreach ( [ 'guard', 'single', 'multisite' ] as $scenario ) {
		$output = [];
		$status = 0;
		$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . escapeshellarg( $scenario );
		exec( $command, $output, $status );
		check_same( 0, $status, 'Uninstall scenario succeeds: ' . $scenario );
		$text = implode( "\n", $output );
		check_same( 1, preg_match( '/^PASS: (\d+) uninstall checks \(' . $scenario . '\)$/', $text, $match ), 'Each isolated scenario reports its assertions.' );
		$total += (int) $match[1];
	}
	echo 'PASS: ' . ( $total + $checks ) . " uninstall checks (all scenarios)\n";
	exit( 0 );
}

check_same( true, in_array( $mode, [ 'guard', 'single', 'multisite' ], true ), 'The scenario is known.' );
$deleted = [];
$queries = [];
$api_calls = 0;
$site_ids = range( 3, 207 ); // 205 sites, with IDs independent of paging offsets.
$site_options = [];
foreach ( $site_ids as $site_id ) {
	$site_options[ $site_id ] = [ 'mk_to_top_options' => [ 'btn_width' => 40 ], 'unrelated' => 'keep' ];
}
$single_options = [ 'mk_to_top_options' => [ 'btn_width' => 40 ], 'unrelated' => 'keep' ];

function is_multisite() {
	global $mode, $api_calls;
	++$api_calls;
	return 'multisite' === $mode;
}

function get_sites( $args ) {
	global $queries, $site_ids, $api_calls;
	++$api_calls;
	check_same( 'ids', $args['fields'], 'The site query fetches only IDs.' );
	check_same( 100, $args['number'], 'The site query uses bounded batches.' );
	check_same( 'id', $args['orderby'], 'Pagination has a stable ordering.' );
	check_same( 'ASC', $args['order'], 'Pagination follows ascending IDs.' );
	$queries[] = $args;
	return array_slice( $site_ids, $args['offset'], $args['number'] );
}

function delete_option( $name ) {
	global $mode, $single_options, $deleted, $api_calls;
	++$api_calls;
	check_same( 'single', $mode, 'Single-site deletion is not used for a network uninstall.' );
	check_same( 'mk_to_top_options', $name, 'Only the plugin option is deleted.' );
	$deleted[] = $name;
	unset( $single_options[ $name ] );
	return true;
}

function delete_blog_option( $site_id, $name ) {
	global $mode, $site_options, $deleted, $api_calls;
	++$api_calls;
	check_same( 'multisite', $mode, 'Per-site deletion is used only in Multisite.' );
	check_same( 'mk_to_top_options', $name, 'Only the plugin option is deleted from each site.' );
	check_same( true, is_int( $site_id ) && isset( $site_options[ $site_id ] ), 'The deletion targets a real integer site ID.' );
	$deleted[] = $site_id;
	unset( $site_options[ $site_id ][ $name ] );
	return true;
}

if ( 'guard' === $mode ) {
	register_shutdown_function( function () {
		global $api_calls, $checks;
		check_same( 0, $api_calls, 'Direct access exits before calling any WordPress API.' );
		echo 'PASS: ' . $checks . " uninstall checks (guard)\n";
	} );
	( function () {
		require dirname( __DIR__ ) . '/uninstall.php';
	} )();
	throw new RuntimeException( 'Direct access must stop execution.' );
}

define( 'WP_UNINSTALL_PLUGIN', 'mk-to-top/mk-to-top-button.php' );
// Isolate the included file's local variables from the fixtures above, as
// WordPress does when including uninstall.php inside uninstall_plugin().
( function () {
	require dirname( __DIR__ ) . '/uninstall.php';
} )();

if ( 'single' === $mode ) {
	check_same( [ 'mk_to_top_options' ], $deleted, 'A single-site uninstall deletes its option once.' );
	check_same( [ 'unrelated' => 'keep' ], $single_options, 'A single-site uninstall preserves unrelated settings.' );
	check_same( [], $queries, 'A single-site uninstall does not query network sites.' );
} else {
	check_same( $site_ids, $deleted, 'All 205 sites are processed exactly once, including the partial last batch.' );
	check_same( [ 0, 100, 200 ], array_column( $queries, 'offset' ), 'Pagination advances across every batch.' );
	foreach ( $site_ids as $site_id ) {
		check_same( [ 'unrelated' => 'keep' ], $site_options[ $site_id ], 'Multisite deletion removes plugin data and preserves unrelated settings.' );
	}
	check_same( true, isset( $single_options['mk_to_top_options'] ), 'The Multisite branch does not delete an unrelated current-site mock option.' );
}

echo 'PASS: ' . $checks . ' uninstall checks (' . $mode . ")\n";
