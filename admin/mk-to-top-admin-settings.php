<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', 'mk_to_top_register_settings' );

function mk_to_top_register_settings() {
	register_setting( 'mk_to_top_settings_group', 'mk_to_top_options', [
		'type'              => 'array',
		'default'           => mk_to_top_get_defaults(),
		'sanitize_callback' => 'mk_to_top_sanitize_options',
	] );

	add_settings_section(
		'mk_to_top_main_section',
		__( 'Button settings', 'mk-to-top' ),
		'__return_false',
		'mk-to-top-settings'
	);

	$fields = [
		'btn_width' => [
			'label' => __( 'Base button width (px)', 'mk-to-top' ),
			'type' => 'number', 'min' => 20, 'max' => 200,
			'description' => __( 'Width and height adapt between 75% and 125% of the base size to fit the viewport.', 'mk-to-top' ),
		],
		'btn_height' => [
			'label' => __( 'Base button height (px)', 'mk-to-top' ),
			'type' => 'number', 'min' => 20, 'max' => 200,
		],
		'btn_icon_color' => [ 'label' => __( 'Icon color', 'mk-to-top' ), 'type' => 'color' ],
		'btn_bg_color' => [ 'label' => __( 'Background color', 'mk-to-top' ), 'type' => 'color' ],
		'btn_position' => [
			'label' => __( 'Position', 'mk-to-top' ), 'type' => 'radio',
			'options' => [ 'left' => __( 'Left', 'mk-to-top' ), 'right' => __( 'Right', 'mk-to-top' ) ],
		],
		'btn_offset' => [
			'label' => __( 'Base bottom and side offset (px)', 'mk-to-top' ),
			'type' => 'number', 'min' => 0, 'max' => 500,
			'description' => __( 'The offset adapts between 75% and 125% of this value.', 'mk-to-top' ),
		],
		'btn_show_at' => [
			'label' => __( 'Show after scrolling (px)', 'mk-to-top' ),
			'type' => 'number', 'min' => 0, 'max' => 5000,
		],
		'btn_mobile' => [
			'label' => __( 'Show on mobile', 'mk-to-top' ), 'type' => 'checkbox',
			'description' => __( 'Show the button on viewports 768 pixels wide or narrower.', 'mk-to-top' ),
		],
	];

	foreach ( $fields as $name => $args ) {
		$args['name'] = $name;
		if ( 'radio' !== $args['type'] ) {
			$args['label_for'] = 'mk-to-top-' . $name;
		}
		add_settings_field( "mk_to_top_{$name}", $args['label'], 'mk_to_top_render_field', 'mk-to-top-settings', 'mk_to_top_main_section', $args );
	}
}

function mk_to_top_render_field( $args ) {
	$options = mk_to_top_get_options();
	$name    = $args['name'];
	$value   = $options[ $name ];
	$id      = 'mk-to-top-' . $name;
	$description_id = $id . '-description';
	$described_by = empty( $args['description'] ) ? '' : ' aria-describedby="' . esc_attr( $description_id ) . '"';

	switch ( $args['type'] ) {
		case 'number':
			printf(
				'<input id="%1$s" type="number" name="mk_to_top_options[%2$s]" value="%3$s" min="%4$s" max="%5$s" step="1" required%6$s />',
				esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_attr( $args['min'] ), esc_attr( $args['max'] ), $described_by
			);
			break;
		case 'color':
			printf(
				'<input id="%1$s" type="text" class="mk-to-top-color-field" name="mk_to_top_options[%2$s]" value="%3$s" />',
				esc_attr( $id ), esc_attr( $name ), esc_attr( $value )
			);
			break;
		case 'radio':
			echo '<fieldset><legend class="screen-reader-text">' . esc_html( $args['label'] ) . '</legend>';
			foreach ( $args['options'] as $val => $label ) {
				printf(
					'<label class="mk-to-top-radio"><input type="radio" name="mk_to_top_options[%1$s]" value="%2$s" %3$s> %4$s</label> ',
					esc_attr( $name ), esc_attr( $val ), checked( $value, $val, false ), esc_html( $label )
				);
			}
			echo '</fieldset>';
			break;
		case 'checkbox':
			printf(
				'<input id="%1$s" type="checkbox" name="mk_to_top_options[%2$s]" value="1" %3$s%4$s />',
				esc_attr( $id ), esc_attr( $name ), checked( $value, '1', false ), $described_by
			);
			break;
	}
	if ( ! empty( $args['description'] ) ) {
		echo '<p class="description" id="' . esc_attr( $description_id ) . '">' . esc_html( $args['description'] ) . '</p>';
	}
}
