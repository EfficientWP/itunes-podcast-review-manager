<?php

/* FOR DATA PROCESSING */

function iprm_deactivate() {
	wp_clear_scheduled_hook( 'iprm_schedule' );
	iprm_delete_option( 'iprm_settings' );
	iprm_delete_option( 'iprm_active_product' );
	iprm_delete_option( 'iprm_current_version' );
	$podcastArray = iprm_get_option( 'iprm_podcasts' );
		
	/* REMOVE CACHE FROM DB */
	if (is_array($podcastArray)){
		
		forEach ($podcastArray as $url_str) {
			iprm_delete_product($url_str);
		}		
	}
	iprm_delete_option( 'iprm_podcasts' );
}

function iprm_delete_option( $meta_key ) {
	return delete_option( $meta_key );
}
function iprm_get_option( $option ) {
	return get_option( $option, FALSE );
}
function iprm_update_option( $meta_key, $meta_value ) {
	return update_option( $meta_key, $meta_value );
}