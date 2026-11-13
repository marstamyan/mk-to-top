<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MK_To_Top_Public {

	public function __construct() {
		add_action( 'wp_footer', [ $this, 'render_scroll_button' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_styles() {
		wp_enqueue_style(
			'mk-to-top-style',
			plugin_dir_url( __FILE__ ) . 'css/mk-to-top-button.css',
			[],
			MK_TO_TOP_VERSION
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'mk-to-top-script',
			plugin_dir_url( __FILE__ ) . 'js/mk-to-top-button.js',
			[],
			MK_TO_TOP_VERSION,
			true
		);
	}

	public function render_scroll_button() {

		$options = mk_to_top_get_options();

		echo '<button type="button" class="mk-to-top-btn" id="mk-to-top-btn" hidden tabindex="-1" aria-hidden="true"';
		foreach ( $options as $key => $value ) {
			$attribute = 'data-mk-' . str_replace( '_', '-', $key );
			if ( 'btn_mobile' === $key ) {
				$value = '1' === $value ? 'true' : 'false';
			}
			echo ' ' . esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}
		echo ' aria-label="' . esc_attr__( 'Scroll to top', 'mk-to-top' ) . '"></button>';
	}
}
