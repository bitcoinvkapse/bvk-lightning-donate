<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function bvkld_uninstall_cleanup() {
	$bvkld_options = array(
		'bvkld_enabled',
		'bvkld_lightning_address',
		'bvkld_amounts',
		'bvkld_title',
		'bvkld_subtitle',
		'bvkld_post_types',
		'bvkld_primary_color',
		'bvkld_background_color',
		'bvkld_wallet_link_text',
		'bvkld_wallet_link_url',
	);

	foreach ( $bvkld_options as $bvkld_opt ) {
		delete_option( $bvkld_opt );
	}
}

bvkld_uninstall_cleanup();
