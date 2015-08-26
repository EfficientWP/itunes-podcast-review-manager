<?php

/* FOR DATA PROCESSING */

function iprm_deactivate() {
	wp_clear_scheduled_hook( 'iprm_schedule' );
	iprm_delete_option( 'iprm_settings' );
	iprm_delete_option( 'iprm_active_product' );
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
	$user_ID = get_current_user_id();
	return delete_user_meta( $user_ID, $meta_key );

}
function iprm_get_option( $option ) {
	$user_ID = get_current_user_id();
	return get_user_meta( $user_ID, $option, TRUE );

}
function iprm_update_option( $meta_key, $meta_value ) {

	$user_ID = get_current_user_id();
	return update_user_meta( $user_ID, $meta_key, $meta_value );


}