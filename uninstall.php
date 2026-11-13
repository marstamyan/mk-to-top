<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( is_multisite() ) {
	$offset = 0;
	do {
		$site_ids = get_sites( [ 'fields' => 'ids', 'number' => 100, 'offset' => $offset, 'orderby' => 'id', 'order' => 'ASC' ] );
		foreach ( $site_ids as $site_id ) {
			delete_blog_option( (int) $site_id, 'mk_to_top_options' );
		}
		$offset += count( $site_ids );
	} while ( 100 === count( $site_ids ) );
} else {
	delete_option( 'mk_to_top_options' );
}
