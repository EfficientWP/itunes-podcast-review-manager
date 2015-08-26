<?php

/* FOR UPGRADE FUNCTIONS */

function iprm_upgrade_check() {
	global $iprm_current_plugin_version;
	global $iprm_settings;
	$iprm_settings_new = $iprm_settings;
	if ( $iprm_current_plugin_version != $iprm_settings_new['iprm_plugin_version'] ) {

		$iprm_settings_new['iprm_plugin_version'] = $iprm_current_plugin_version;
		iprm_update_option( 'iprm_settings', $iprm_settings_new );
	}
}