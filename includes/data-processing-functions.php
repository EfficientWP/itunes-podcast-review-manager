<?php

/* FOR DATA PROCESSING */

function iprm_deactivate() {
	wp_clear_scheduled_hook( 'iprm_schedule' );
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