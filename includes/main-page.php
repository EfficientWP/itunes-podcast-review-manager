<?php

/* ADMIN PAGE */

add_action( 'admin_menu', 'iprm_add_main_page_link' );


function iprm_main_page() {
	/* LOAD CURRENT */
	
	
	if (iprm_get_option( 'iprm_active_product' )) {
		$podcast = new IPRM_Podcast(iprm_get_option( 'iprm_active_product' ));
	}else{
	/* COULD NOT LOAD, USE DEFAULT */
		$podcast = new IPRM_Podcast( 'http://itunes.apple.com/us/'); 
	}	
	
	
	
	/* DISABLES FOR NON-ADMINISTRATORS */
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	

	$alert = '';
	$notice = '';

	/* LOAD CURRENT SELECTED PRODUCT */
	if (isset( $_POST["iprm_product_select"] )) {
		if (isset ($_POST["iprm_update_product"])){
		$podcast =  new IPRM_Podcast ( $_POST["iprm_product_select"] );
		}else if (isset ($_POST["iprm_delete_product"])) {
			/* DEV FAILSAFE.. NEED NEW CONSTRUCTOR THAT TAKES 0 */
			$podcast =  new IPRM_Podcast ( 'http://itunes.apple.com/us/' );
			
			$productToDeleteURL = $_POST["iprm_product_select"];
			
			iprm_delete_product($productToDeleteURL);
			
			$notice = __( 'Product removed: ' . $_POST["iprm_product_select"], 'iprm_domain' );
	
		}
	}else {
		$podcast = new IPRM_Podcast(iprm_get_option( 'iprm_active_product' ));
	}
	

	// IF NEW ITUNES URL IS ENTERED, UPDATE ACTIVE
	
	if ( isset( $_POST["iprm_update_url"] ) && ( $_POST["iprm_add_url"] != '' ) ) {
		
		if (filter_var($_POST["iprm_add_url"], FILTER_VALIDATE_URL) && ($_POST["iprm_add_url"] !== 'http://itunes.apple.com/us/')) {
		$itunes_url = esc_url( $_POST["iprm_add_url"] );
		iprm_update_option( 'iprm_active_product', $itunes_url );
		$podcast =  new IPRM_Podcast ( $itunes_url );
		if ($podcast->itunes_id != ''){
		$notice = __( 'Your iTunes URL has successfully been updated.', 'iprm_domain' );
		}else {
			$alert = __( 'iTunes URL could not be updated.  Please check the URL and try again.', 'iprm_domain' ) . '<br />';
			$alert .= '<i>' . __( 'Example: http://itunes.apple.com/us/podcast/professional-wordpress-podcast/id885696994.', 'iprm_domain' ) . '</i>';
		}
		}else {
			$alert = __( 'Invalid iTunes URL.', 'iprm_domain' ) . '<br />';
			$alert .= '<i>' . __( 'Example: http://itunes.apple.com/us/podcast/professional-wordpress-podcast/id885696994.', 'iprm_domain' ) . '</i>';
		}
	}
	
	
	/* IF NO ITUNES URL IS FOUND, DISPLAY NOTICE ASKING FOR THE URL */
	elseif ( $podcast->itunes_id == '' ) {
		$notice = __( 'Please enter your iTunes URL.', 'iprm_domain' ) . '<br />';
		$notice .= '<i>' . __( 'Example: http://itunes.apple.com/us/podcast/professional-wordpress-podcast/id885696994.', 'iprm_domain' ) . '</i>';
	}
	/* IF CHECK MANUALLY BUTTON IS PRESSED, CHECK FOR REVIEWS */
	if ( isset( $_POST["iprm_check_manually"] ) && ( $_POST["iprm_check_manually"] != '' ) ) {
		$podcast->get_itunes_feed_contents();
	}
	
	/* IF RESET ALL BUTTON IS PRESSED, DELETE OPTIONS AND CRON JOBS */

	elseif ( isset( $_POST["iprm_reset_all_data"] ) && ( $_POST["iprm_reset_all_data"] != '' ) ) {
		
		$podcastArray = iprm_get_option( 'iprm_podcasts' );
		
		
		/* IF ITS EMPTY IT EQUALS 1 FOR SOME REASON */
		if (is_array($podcastArray)){
		
			forEach ($podcastArray as $url_str) {
				
				iprm_delete_product($url_str);
			}
			
		}
		
		iprm_delete_option( 'iprm_active_product' );
		
	//	wp_clear_scheduled_hook( 'iprm_schedule' );
		
		
		$notice = __( 'All settings and cache have been cleared.', 'iprm_domain' );
	}
	
	/* IF CACHE IS EMPTY, CHECK FOR NEW REVIEWS */
	if ( empty( $podcast->reviews ) && (filter_var($podcast->itunes_url, FILTER_VALIDATE_URL)) ) {
		
		$podcast->get_itunes_feed_contents();
		
	}
	if ( empty( $podcast->reviews ) && (isSet ($podcast->itunes_id ))) {
		$alert = __( 'No reviews found for this podcast.', 'iprm_domain' );
	}
	
	/* START OUTPUT */
	
	echo "<div id='iprm_wrapper'>";

	echo iprm_display_alert( $alert );
	
	echo iprm_display_notice( $notice );
	if (isSet ($podcast->itunes_id)){
	echo $podcast->display_itunes_feed_summary();
	};
	
	ob_start(); ?>
	
		<div id="iprm_settings_bar" class="iprm_tab">
		<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">
			<div id="iprm_input_settings">
				<div id="iprm_input_settings_left">
				<h2>Settings</h2>
				<h3>Product Url</h3>	
				<p>Please note that adding a new product may take a few moments.</p>
				<input type="url" id="iprm_add_url" name="iprm_add_url" size="80" value="<?php echo $podcast->itunes_url; ?>">
				
				<input class="iprm_button_small" type="submit" name="iprm_update_url" value="ADD PRODUCT">
				
				
				</div>
			</div>
			<div id="iprm_crawl_settings">
				<div id="iprm_input_settings_right">
				<h2>History</h2>
				<p><b><?php _e( 'Recent History:', 'iprm_domain' ); ?></b><br />
					<?php
					$i = 1;
					if ( is_array( $podcast->review_cache_history ) ) {
						foreach ( array_reverse( $podcast->review_cache_history ) as $item ) {
							$i++;
							echo $item['time'] . ' Reviews: ' . $item['count'] . '<br />';
							if ( $i > 5 ) {
								break;
							}
						}
					}
					?></p>
					
				<p>Reviews automatically update every 4 hours.</p>


				<input class="iprm_button_small" type="submit" name="iprm_check_manually" id="iprm_check_manually_btn"value="<?php _e( 'CHECK MANUALLY', 'iprm_domain' ); ?>">
				<input class="iprm_button_small" type="submit" name="iprm_reset_all_data" id="iprm_reset_all_data_btn" value="<?php _e( 'RESET ALL DATA', 'iprm_domain' ); ?>">	
				</div>
			</div>
		</form>
	</div>
		<?php	/* DISPLAY REVIEWS FROM CACHE */
			if ( !empty( $podcast->reviews ) ) {
				echo $podcast->display_page_reviews();
			}
		?>
		
	</div>
	<footer>
	<p style="color: #ecf0f1; text-align: right;">Flag icons by <a href="http://www.icondrawer.com" target="_blank">IconDrawer</a>.</p>
	</footer>
	<?php
	echo ob_get_clean();
}
function iprm_add_main_page_link() {
	add_menu_page( 'Podcast Reviews', 'Podcast Reviews', 'manage_options', 'iprm_main_page', 'iprm_main_page', 'dashicons-star-filled' );
}