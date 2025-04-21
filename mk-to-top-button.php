<?php
/**
 * Plugin Name: MK To Top Button
 * Description: A simple scroll-to-top button with customizable settings via the admin panel.
 * Version: 1.0
 * Author: Mamikon
 * Author URI: https://linkedin.com/in/mamikon-arustamyan-3969301ab?/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: mk-to-top
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MK_TO_TOP_VERSION', '1.0' );
define( 'MK_TO_TOP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MK_TO_TOP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MK_TO_TOP_PLUGIN_DIR . 'admin/class-mk-to-top-admin.php';
require_once MK_TO_TOP_PLUGIN_DIR . 'public/class-mk-to-top-public.php';

add_action( 'plugins_loaded', function () {
	if ( is_admin() ) {
		new MK_To_Top_Admin();
	} else {
		new MK_To_Top_Public();
	}
} );
