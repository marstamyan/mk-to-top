<?php
/**
 * Dependency-free regression checks for the real option and frontend code.
 * Run: php tests/options.test.php
 * WordPress is deliberately stubbed here; integration checks run separately.
 */

error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
	throw new ErrorException( $message, 0, $severity, $file, $line );
} );

define( 'ABSPATH', __DIR__ . '/' );

$checks         = 0;
$stored_options = [];
$option_reads   = [];
$hooks          = [];

function check_same( $expected, $actual, $message ) {
	global $checks;
	++$checks;
	if ( $expected !== $actual ) {
		throw new RuntimeException( $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) );
	}
}

// Match the WordPress API contracts needed by the actual plugin files.
function sanitize_hex_color( $color ) {
	if ( '' === $color ) {
		return '';
	}
	return preg_match( '/^#([a-fA-F0-9]{3}){1,2}$/', $color ) ? $color : null;
}

function get_option( $name, $default = false ) {
	global $stored_options, $option_reads;
	$option_reads[] = $name;
	return $stored_options;
}

function add_action( $hook, $callback ) {
	global $hooks;
	$hooks[ $hook ][] = $callback;
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
}

function esc_attr__( $text, $domain ) {
	check_same( 'mk-to-top', $domain, 'The button uses the plugin translation domain.' );
	return esc_attr( $text . ' "quoted" <safe> & \'single\'' );
}

require dirname( __DIR__ ) . '/includes/mk-to-top-options.php';
require dirname( __DIR__ ) . '/public/class-mk-to-top-public.php';

$defaults = [
	'btn_width'      => 40,
	'btn_height'     => 40,
	'btn_icon_color' => '#ffffff',
	'btn_bg_color'   => '#000000',
	'btn_position'   => 'right',
	'btn_offset'     => 30,
	'btn_show_at'    => 300,
	'btn_mobile'     => '1',
];

check_same( $defaults, mk_to_top_get_defaults(), 'Fresh installs retain the documented defaults.' );
check_same( $defaults, mk_to_top_normalize_options( [] ), 'Missing stored keys use defaults.' );

foreach ( [ null, false, true, 0, 42, 1.5, '', 'corrupt', new stdClass() ] as $corrupt ) {
	check_same( $defaults, mk_to_top_normalize_options( $corrupt ), 'Corrupt top-level stored options recover without warnings.' );
	$stored_options = $corrupt;
	check_same( $defaults, mk_to_top_get_options(), 'Reading corrupt stored options recovers without warnings.' );
}

$ranges = [
	'btn_width'   => [ 20, 200 ],
	'btn_height'  => [ 20, 200 ],
	'btn_offset'  => [ 0, 500 ],
	'btn_show_at' => [ 0, 5000 ],
];
foreach ( $ranges as $key => $range ) {
	list( $minimum, $maximum ) = $range;
	foreach ( [ $minimum, $maximum, (string) $minimum, '  ' . $maximum . '  ' ] as $value ) {
		check_same( (int) trim( (string) $value ), mk_to_top_normalize_options( [ $key => $value ] )[ $key ], $key . ' accepts integer boundaries and form strings.' );
	}
	foreach ( [ $minimum - 1, -999999, '-99999999999999999999999999999999999999' ] as $value ) {
		check_same( $minimum, mk_to_top_normalize_options( [ $key => $value ] )[ $key ], $key . ' clamps values below its lower bound.' );
	}
	foreach ( [ $maximum + 1, 999999, '99999999999999999999999999999999999999' ] as $value ) {
		check_same( $maximum, mk_to_top_normalize_options( [ $key => $value ] )[ $key ], $key . ' clamps values above its upper bound.' );
	}
	foreach ( [ null, false, true, [], [ 40 ], new stdClass(), 42.5, '', '40px', '1e2', '42.5', '42junk', 'NaN' ] as $value ) {
		check_same( $defaults[ $key ], mk_to_top_normalize_options( [ $key => $value ] )[ $key ], $key . ' rejects malformed types and non-integer values.' );
	}
}

$valid_colors = [
	'#abc',
	'#abcdef',
	'rgb(0,0,0)',
	'rgb(255, 255, 255)',
	'rgb( 1 , 2 , 3 )',
	'rgba(10,20,30,0)',
	'rgba(10, 20, 30, 1)',
	'rgba(10,20,30,0.25)',
	'rgba(10,20,30,.5)',
	'rgba(10,20,30,1.00)',
];
$invalid_colors = [
	null, false, true, 123, [], [ '#fff' ], new stdClass(), '',
	'red', 'transparent', '#abcd', '#fffffff', '#xyz',
	'rgb(256,0,0)', 'rgb(0,999,0)', 'rgba(0,0,999,1)',
	'rgb(-1,0,0)', 'rgb(1.5,2,3)', 'rgb(1,2)', 'rgb(1,2,3,0.5)',
	'rgba(1,2,3)', 'rgba(1,2,3,-0.1)', 'rgba(1,2,3,1.1)',
	'rgba(1,2,3,2)', 'rgba(1,2,3,0.5,0)',
	'#fff; color:red', 'url(javascript:alert(1))', '" onmouseover="alert(1)',
	"#fff\ninvalid",
];
foreach ( [ 'btn_icon_color', 'btn_bg_color' ] as $key ) {
	foreach ( $valid_colors as $value ) {
		check_same( $value, mk_to_top_normalize_options( [ $key => $value ] )[ $key ], $key . ' preserves valid legacy colors.' );
	}
	check_same( '#abcdef', mk_to_top_normalize_options( [ $key => '  #ABCDEF  ' ] )[ $key ], $key . ' normalizes color whitespace and case.' );
	foreach ( $invalid_colors as $value ) {
		check_same( $defaults[ $key ], mk_to_top_normalize_options( [ $key => $value ] )[ $key ], $key . ' rejects malformed or injected colors without warnings.' );
	}
}

foreach ( [ 'left', 'right' ] as $position ) {
	check_same( $position, mk_to_top_normalize_options( [ 'btn_position' => $position ] )['btn_position'], 'Valid button positions survive.' );
}
foreach ( [ true, false, 1, 0, [], new stdClass(), 'LEFT', 'center', 'right;top:0' ] as $position ) {
	check_same( 'right', mk_to_top_normalize_options( [ 'btn_position' => $position ] )['btn_position'], 'Position validation is strict.' );
}

foreach ( [ true, 1, '1' ] as $enabled ) {
	check_same( '1', mk_to_top_sanitize_options( [ 'btn_mobile' => $enabled ] )['btn_mobile'], 'Explicit enabled checkbox values survive.' );
}
foreach ( [ false, 0, '0', '', 'false', [], new stdClass() ] as $disabled ) {
	check_same( '0', mk_to_top_sanitize_options( [ 'btn_mobile' => $disabled ] )['btn_mobile'], 'Explicit disabled or corrupt checkbox values stay disabled.' );
}
$unchecked = $defaults;
$unchecked['btn_mobile'] = '0';
check_same( $unchecked, mk_to_top_sanitize_options( [] ), 'A checkbox omitted from the submitted form is disabled.' );
check_same( $unchecked, mk_to_top_sanitize_options( [ 'btn_mobile' => null ] ), 'A null checkbox is disabled on save.' );

foreach ( [ [], [ 'btn_mobile' => '0' ], [ 'btn_mobile' => false ], [ 'btn_mobile' => '1' ], [ 'btn_mobile' => '0', 'btn_width' => -1, 'btn_bg_color' => [] ] ] as $input ) {
	$once = mk_to_top_sanitize_options( $input );
	$again = $once;
	for ( $pass = 0; $pass < 3; ++$pass ) {
		$again = mk_to_top_sanitize_options( $again );
		check_same( $once, $again, 'Repeated sanitization preserves first-save settings, including unchecked mobile.' );
	}
}

$stored_options = [ 'btn_width' => 75, 'btn_mobile' => '0', 'btn_position' => 'left' ];
$saved = mk_to_top_get_options();
foreach ( [ null, false, 'corrupt', 123, new stdClass() ] as $input ) {
	check_same( $saved, mk_to_top_sanitize_options( $input ), 'A malformed top-level submission preserves valid saved settings.' );
}
check_same( $defaults, mk_to_top_normalize_options( [ 'unknown' => 'secret', 'onclick' => 'alert(1)' ] ), 'Only known setting keys are returned.' );
check_same( [ 'btn_width' => 75, 'btn_mobile' => '0', 'btn_position' => 'left' ], $stored_options, 'Reading options never mutates saved data.' );
check_same( true, count( $option_reads ) > 0 && count( array_unique( $option_reads ) ) === 1 && $option_reads[0] === 'mk_to_top_options', 'Only the plugin option is read.' );

$frontend = new MK_To_Top_Public();
check_same( true, isset( $hooks['wp_footer'], $hooks['wp_enqueue_scripts'] ), 'The frontend registers its WordPress hooks.' );
$stored_options = [
	'btn_width' => 999999,
	'btn_height' => [],
	'btn_icon_color' => '"><script>alert(1)</script>',
	'btn_bg_color' => new stdClass(),
	'btn_position' => 'left',
	'btn_offset' => -20,
	'btn_show_at' => 999999,
	'btn_mobile' => '0',
	'onclick' => 'alert(1)',
];
ob_start();
$frontend->render_scroll_button();
$markup = ob_get_clean();
foreach ( [
	'type="button"', 'id="mk-to-top-btn"', ' hidden ', 'tabindex="-1"', 'aria-hidden="true"',
	'data-mk-btn-width="200"', 'data-mk-btn-height="40"', 'data-mk-btn-icon-color="#ffffff"',
	'data-mk-btn-bg-color="#000000"', 'data-mk-btn-position="left"', 'data-mk-btn-offset="0"',
	'data-mk-btn-show-at="5000"', 'data-mk-btn-mobile="false"',
	'aria-label="Scroll to top &quot;quoted&quot; &lt;safe&gt; &amp; &#039;single&#039;"',
] as $fragment ) {
	check_same( true, strpos( $markup, $fragment ) !== false, 'Rendered markup contains safe, normalized configuration: ' . $fragment );
}
check_same( false, strpos( $markup, '<script' ) !== false, 'Injected markup never reaches the output.' );
check_same( false, strpos( $markup, 'onclick' ) !== false, 'Unknown event-handler settings are discarded.' );
check_same( 1, substr_count( $markup, '<button ' ), 'Exactly one button is rendered.' );

echo 'PASS: ' . $checks . " option/frontend checks\n";
