<?php

add_action( 'admin_init', 'mk_to_top_register_settings' );

function mk_to_top_register_settings() {

	register_setting( 'mk_to_top_settings_group', 'mk_to_top_options', [ 
		'sanitize_callback' => 'mk_to_top_sanitize_options'
	] );

	add_settings_section(
		'mk_to_top_main_section',
		__( 'Button Settings', 'mk-to-top' ),
		'__return_false',
		'mk-to-top-settings'
	);

	$fields = [ 
		'btn_width' => [ 'label' => 'Button Width (px)', 'type' => 'number', 'min' => 20, 'max' => 200 ],
		'btn_height' => [ 'label' => 'Button Height (px)', 'type' => 'number', 'min' => 20, 'max' => 200 ],
		'btn_icon_color' => [ 'label' => 'Icon Color', 'type' => 'color' ],
		'btn_bg_color' => [ 'label' => 'Background Color', 'type' => 'color' ],
		'btn_position' => [ 'label' => 'Position', 'type' => 'radio', 'options' => [ 'left' => 'Left', 'right' => 'Right' ] ],
		'btn_offset' => [ 'label' => 'Offset from side (px)', 'type' => 'number', 'min' => 0, 'max' => 500 ],
		'btn_show_at' => [ 'label' => 'Show After Scroll (px)', 'type' => 'number', 'min' => 0, 'max' => 5000 ],
		'btn_mobile' => [ 'label' => 'Show on Mobile?', 'type' => 'checkbox' ]
	];

	foreach ( $fields as $name => $args ) {
		add_settings_field(
			"mk_to_top_{$name}",
			esc_html__( $args['label'], 'mk-to-top' ),
			'mk_to_top_render_field',
			'mk-to-top-settings',
			'mk_to_top_main_section',
			array_merge( $args, [ 'name' => $name ] )
		);
	}
}

function mk_to_top_render_field( $args ) {
	$options = get_option( 'mk_to_top_options' );
	$name = $args['name'];
	$value = $options[ $name ] ?? mk_to_top_get_default( $name );

	switch ( $args['type'] ) {
		case 'number':
			printf(
				'<input type="number" name="mk_to_top_options[%1$s]" value="%2$s" min="%3$s" max="%4$s" />',
				esc_attr( $name ),
				esc_attr( $value ),
				esc_attr( $args['min'] ),
				esc_attr( $args['max'] )
			);
			break;

		case 'color':
			printf(
				'<input type="text" class="mk-to-top-color-field" name="mk_to_top_options[%1$s]" value="%2$s" />',
				esc_attr( $name ),
				esc_attr( $value )
			);
			break;

		case 'radio':
			foreach ( $args['options'] as $val => $label ) {
				printf(
					'<label class="mk-to-top-radio"><input type="radio" name="mk_to_top_options[%1$s]" value="%2$s" %3$s> %4$s</label><br>',
					esc_attr( $name ),
					esc_attr( $val ),
					checked( $value, $val, false ),
					esc_html( $label )
				);
			}
			break;

		case 'checkbox':
			printf(
				'<input type="checkbox" name="mk_to_top_options[%1$s]" value="1" %2$s />',
				esc_attr( $name ),
				checked( $value, '1', false )
			);
			break;
	}
}

function mk_to_top_get_default( $name ) {
	$defaults = [ 
		'btn_width' => '40',
		'btn_height' => '40',
		'btn_icon_color' => '#ffffff',
		'btn_bg_color' => '#000000',
		'btn_position' => 'right',
		'btn_offset' => '30',
		'btn_show_at' => '300',
		'btn_mobile' => '1'
	];

	return $defaults[ $name ] ?? '';
}

function mk_to_top_sanitize_options( $input ) {
	$sanitized = [];

	$sanitized['btn_width'] = absint( $input['btn_width'] ?? 40 );
	$sanitized['btn_height'] = absint( $input['btn_height'] ?? 40 );
	$sanitized['btn_icon_color'] = sanitize_color_flexible( $input['btn_icon_color'] ?? '#ffffff' );
	$sanitized['btn_bg_color'] = sanitize_color_flexible( $input['btn_bg_color'] ?? '#000000' );
	$sanitized['btn_position'] = in_array( $input['btn_position'], [ 'left', 'right' ] ) ? $input['btn_position'] : 'right';
	$sanitized['btn_offset'] = absint( $input['btn_offset'] ?? 30 );
	$sanitized['btn_show_at'] = absint( $input['btn_show_at'] ?? 300 );
	$sanitized['btn_mobile'] = isset( $input['btn_mobile'] ) ? '1' : '0';

	return $sanitized;
}

function sanitize_color_flexible( $color ) {
	if ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
		return sanitize_hex_color( $color );
	}

	if ( preg_match( '/^rgba?\((\d{1,3}), ?(\d{1,3}), ?(\d{1,3})(, ?(0|1|0\.\d+))?\)$/', $color ) ) {
		return $color;
	}

	return '#000000';
}
