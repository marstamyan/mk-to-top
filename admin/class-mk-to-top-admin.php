<?php
class MK_To_Top_Admin {

	public function __construct() {

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		require_once plugin_dir_path( __FILE__ ) . 'mk-to-top-admin-settings.php';
	}

	public function add_settings_page() {
		add_menu_page(
			'MK To Top Settings',
			'MK To Top',
			'manage_options',
			'mk-to-top-settings',
			[ $this, 'render_settings_page' ],
			'dashicons-arrow-up-alt',
			90
		);
	}

	public function enqueue_assets( $hook ) {
		if ( $hook !== 'toplevel_page_mk-to-top-settings' ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'mk-to-top-admin-style',
			plugin_dir_url( __FILE__ ) . 'css/mk-to-top-admin.css',
			[],
			'1.0'
		);

		wp_enqueue_script(
			'mk-to-top-admin-script',
			plugin_dir_url( __FILE__ ) . 'js/mk-to-top-admin.js',
			[ 'jquery', 'wp-color-picker' ],
			'1.0',
			true
		);
	}

	public function render_settings_page() {
		echo '<div class="wrap mk-to-top-settings-wrap">';
		echo '<h1>' . esc_html__( 'MK To Top Button Settings', 'mk-to-top' ) . '</h1>';
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
			settings_errors();
		}
		echo '<form method="post" action="options.php">';
		settings_fields( 'mk_to_top_settings_group' );
		do_settings_sections( 'mk-to-top-settings' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}
}