<?php
class Mk_To_Top_Public {

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

		$options = get_option( 'mk_to_top_options', [] );
		$get = function ($key, $default) use ($options) {
			return isset( $options[ $key ] ) ? esc_attr( $options[ $key ] ) : $default;
		};

		$width = $get( 'btn_width', '40' );
		$height = $get( 'btn_height', '40' );
		$icon_color = $get( 'btn_icon_color', '#fff' );
		$bg_color = $get( 'btn_bg_color', '#000' );
		$position = $get( 'btn_position', 'right' );
		$offset = $get( 'btn_offset', '30' );
		$show_at = $get( 'btn_show_at', '300' );
		$mobile = $get( 'btn_mobile', '1' );

		echo '<button class="mk-to-top-btn" id="mk-to-top-btn"
            data-mk-btn-width="' . $width . '"
            data-mk-btn-height="' . $height . '"
            data-mk-btn-icon-color="' . $icon_color . '"
            data-mk-btn-bg-color="' . $bg_color . '"
            data-mk-btn-position="' . $position . '"
            data-mk-btn-offset="' . $offset . '"
            data-mk-btn-show-at="' . $show_at . '"
            data-mk-btn-mobile="' . ( $mobile === '1' ? 'true' : 'false' ) . '"
            aria-label="Scroll to Top"
        >
        </button>';
	}
}
