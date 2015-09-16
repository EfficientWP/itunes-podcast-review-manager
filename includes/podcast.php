<?php

/* PODCAST CLASS 

*  FUNCTIONS:
*  display_itunes_feed_summary()
*  display_page_reviews()
*  get_itunes_feed_contents()

*/

class IPRM_Podcast {
	public $reviews = array( );
	public $review_cache_history = array( );
	public $settings = array( );
	public $itunes_url = '';
	public $itunes_id = '';
	public $itunes_feed_image = '';
	public $itunes_feed_name = '';
	public $itunes_feed_artist = '';
	public $itunes_feed_summary = '';
	
	function __construct($url) {
	
		$this->settings = $this->get_itunes_metadata($url);
		$this->itunes_url = $url;
		$this->itunes_id = $this->settings['itunes_id'];
		$this->itunes_feed_image = $this->settings['itunes_feed_image'];
		$this->itunes_feed_name = $this->settings['itunes_feed_name'];
		$this->itunes_feed_artist = $this->settings['itunes_feed_artist'];
		$this->itunes_feed_summary = $this->settings['itunes_feed_summary'];
		$this->review_cache_history = unserialize (iprm_get_option( 'iprm_review_cache_history' . $this->itunes_id ));
		
		$file = WP_PLUGIN_DIR  . "/itunes-podcast-review-manager/cache/cache_$this->itunes_id.cache";
		
		if (file_exists ( $file ) ) {
			$this->reviews = unserialize (file_get_contents($file)); 
		}else{
			$this->reviews = [];
		}
		
		
		/* WRITE TO DB NEW LIST OF PODCASTS AND WHAT ONE IS ACTIVE */
		if ($url != 'http://itunes.apple.com/us/'){
			iprm_update_option( 'iprm_active_product' , $url );
		}
		$podcastArray = iprm_get_option( 'iprm_podcasts' );
		if (!is_array ( $podcastArray )){
			$podcastArray = array();
		}
		$key = array_search($url,$podcastArray);
		
		if (!$key) { /*NOT FOUND IN EXISITNG DB*/
			$podcastArray[] = $url;
		}
		
		$podcastArray  = array_unique($podcastArray);
		
		/* DONT WRITE DEFAULT TO DB */
		if (($url != 'http://itunes.apple.com/us/' ) && (isSet ($this->itunes_id))){
			iprm_update_option( 'iprm_podcasts', $podcastArray );
		}
		
	}
	
	function get_itunes_metadata($url) {
		
		preg_match ( '([0-9][0-9][0-9]+)', $url, $matches );		
		
		/* ONLY CONTINUE IF WE HAVE GOOD URL AND PARSED AN ID */
		if ( (filter_var($url, FILTER_VALIDATE_URL)) && (isSet ( $matches[0])) ){			
	
		/* POPULATE METADATA ARRAY */
		$metadataArray['itunes_id'] = $matches[0];
		
						
		/* BLACK MAGIC ? */
		$itunes_json1 = json_encode( wp_remote_get( $url ) );
		$data1 = json_decode( $itunes_json1, TRUE );
		$url_xml = $data1['body'];
		
		$titleDiv = iprm_get_contents_inside_tag( $url_xml, '<div id="title" class="intro">', '</div>' );	
		$itunes_feed_name = iprm_get_contents_inside_tag( $titleDiv, '<h1>', '</h1>' );
		if ($itunes_feed_name != "EMPTYSTR") {
		$metadataArray['itunes_feed_name'] = $itunes_feed_name;
		} ELSE {
			$itunes_feed_name = iprm_get_contents_inside_tag( $titleDiv, '<h1 itemprop="name">', '</h1>' );
			$metadataArray['itunes_feed_name'] = $itunes_feed_name;
		}
		
		$itunes_feed_artist = iprm_get_contents_inside_tag( $titleDiv, '<h2>', '</h2>' );
		if ($itunes_feed_artist != "EMPTYSTR") {
			$metadataArray['itunes_feed_artist'] = $itunes_feed_artist;
		} ELSE {
			$itunes_feed_artist = iprm_get_contents_inside_tag( $titleDiv, '<h2 itemprop="name">', '</h2>' );
			$metadataArray['itunes_feed_artist'] = $itunes_feed_artist;
		}
	
		
		$summaryDiv = iprm_get_contents_inside_tag( $url_xml, '<div metrics-loc="Titledbox_Description" class="product-review">', '</div>' );

		
		$itunes_feed_summary = iprm_get_contents_inside_tag( $summaryDiv, '<p>', '</p>' );
		if ($itunes_feed_summary != "EMPTYSTR") {
			$metadataArray['itunes_feed_summary'] = $itunes_feed_summary;
		} ELSE { /* SUMMARY MAY NOT BE PRESENT */
			$metadataArray['itunes_feed_summary'] = "";
		}
		
		/* CANT GET ART THIS WAY, HAVE TO CHECK XML FEED */
		$url_xml = 'https://itunes.apple.com/us/rss/customerreviews/id=' . $metadataArray['itunes_id'] . '/xml';
		$itunes_json1 = json_encode( wp_remote_get( $url_xml ) );
		$data1 = json_decode( $itunes_json1, TRUE );
		$url_xml = $data1['body'];
		
		$itunes_feed_image = iprm_get_contents_inside_tag( $url_xml, '<im:image height="170">', '</im:image>' );
		$metadataArray['itunes_feed_image'] = $itunes_feed_image;
				
		return $metadataArray;
		
		}else {
			return false;
		}		
	}
	
	function display_itunes_feed_summary() {
		$output = '';
		/* CHECKS TO MAKE SURE ITUNES PODCAST URL IS DEFINED */
		if ( $this->itunes_url != '' ) {
			$output = "
			<div class='iprm_panel' id='iprm_metadata'>
				<div id='iprm_meta_img'>
					<img src='$this->itunes_feed_image'>
				</div>
				<div class='iprm_panel_content'>
					<h2>$this->itunes_feed_name</h2>
					<p>$this->itunes_feed_artist</p>
					<p>$this->itunes_feed_summary</p>
				</div>
			</div>";	
		}
		return $output;
	}
	
	function display_page_reviews() {
		$review_number = 0;
		$output = '';
		$rating_total = 0;
		if ( is_admin() )  {
			$sort_colspan = 2;
		}
		else {
			$sort_colspan = 1;
		}
		$sort_colspan = 2;
		/* CHECKS TO MAKE SURE ITUNES PODCAST URL IS DEFINED */
		if ( $this->itunes_url != '' ) {
	
			/* GENERATES TABLE ROWS FOR ALL REVIEWS */
			ob_start(); ?>
			
				
				<div id="iprm_main_table" class="iprm_panel">
					<h2 id="iprm_review_h2">REVIEWS</h2>
					<table id="iprm_main_table_body" class="iprm_table sortable"  border="0" cellpadding="0" cellspacing="0">
						
						<!-- TABLE HEADINGS -->
						<tr>
	
							<th class="unsortable">
								FLAG
							</th>
							<th>
								COUNTRY
								<div id="iprm_COUNTRY_controls" class="iprm_sort_control">
								<a href="#iprm_main_table" id="iprm_sort_country_asc"><span class="dashicons dashicons-arrow-up"></span></a><br>
								<a href="#iprm_main_table" id="iprm_sort_country_asc"><span class="dashicons dashicons-arrow-down"></span></a>
								</div>
							</th>
							<th>
								DATE
								<div id="iprm_DATE_controls" class="iprm_sort_control">
								<a href="#iprm_main_table" id="iprm_sort_DATE_asc"><span class="dashicons dashicons-arrow-up"></span></a><br>
								<a href="#iprm_main_table" id="iprm_sort_DATE_asc"><span class="dashicons dashicons-arrow-down"></span></a>
								</div>
							</th>
							<th>
								RATING
								<div id="iprm_author_controls" class="iprm_sort_control">
								<a href="#iprm_main_table" id="iprm_sort_RATING_asc"><span class="dashicons dashicons-arrow-up"></span></a><br>
								<a href="#iprm_main_table" id="iprm_sort_RATING_asc"><span class="dashicons dashicons-arrow-down"></span></a>
								</div>
							</th>
							<th>
								AUTHOR
								<div id="iprm_AUTHOR_controls" class="iprm_sort_control">
								<a href="#iprm_main_table" id="iprm_sort_AUTHOR_asc"><span class="dashicons dashicons-arrow-up"></span></a><br>
								<a href="#iprm_main_table" id="iprm_sort_AUTHOR_asc"><span class="dashicons dashicons-arrow-down"></span></a>
								</div>
							</th>
							<th>
								TITLE
								<div id="iprm_TITLE_controls" class="iprm_sort_control">
								<a href="#iprm_main_table" id="iprm_sort_TITLE_asc"><span class="dashicons dashicons-arrow-up"></span></a><br>
								<a href="#iprm_main_table" id="iprm_sort_TITLE_asc"><span class="dashicons dashicons-arrow-down"></span></a>
								</div>
							</th>
							<th>
								REVIEW
								<div id="iprm_REVIEW_controls" class="iprm_sort_control">
								<a href="#iprm_main_table" id="iprm_sort_review_asc"><span class="dashicons dashicons-arrow-up"></span></a><br>
								<a href="#iprm_main_table" id="iprm_sort_review_desc"><span class="dashicons dashicons-arrow-down"></span></a>
								</div>
							</th>
						</tr>
						
						<!-- REVIEWS -->
						<?php
							if ( count( $this->reviews ) > 0 ) {
							
							foreach( $this->reviews as $review ) {
								$review_number++;
								$rating_total += $review['rating'];
								$date = date_create( $review['review_date'] );
								$date = date_format( $date, 'Y-m-d' );
								
								if ( strlen( $review['country'] ) == 2 ) {
										$code = $review['country'];
								}
								else {
									$code = iprm_get_country_data( '', $review['country'] );
								}
								$flag_image = 'images/flags/' . $code . '.png';
								$flagTD = '<img src="' . plugins_url( $flag_image, dirname( __FILE__ ) ) . '" />';
								
														
									echo "<tr>";
										echo "<td class='flag'>" . $flagTD . "</td>";
										echo "<td>" . $review['country'] . "</td>";
										echo "<td>" . $date . "</td>";
										echo "<td>" . $review['rating'] . "</td>";
										echo "<td>" . $review['name'] . "</td>";
										echo "<td>" . $review['title'] . "</td>";
										echo "<td>" . $review['content'] . "</td>";
										
									echo "</tr>";
								}
								}else {
									echo "<p>No reviews found.</p>";
								}
						?>
					
					</table>
				
				
				</div>
				
			<?php /* SEND OUTPUT */	
				return ob_get_clean();
		}
		return FALSE;

	} /* END DISPLAY REVIEW FUNCTION */
	
	function get_itunes_feed_contents() {
	
		$this->get_itunes_metadata( $this->itunes_url );

		$new_reviews = array( );
		$new_settings = array( );
		/* GET ARRAY OF ALL COUNTRY CODES AND COUNTRY NAMES */
		$country_codes = iprm_get_country_data( '', '' );
		/* CHECKS TO MAKE SURE ITUNES PODCAST URL IS DEFINED */
		
		if ( isSet( $this->itunes_id ) ) {
			
			$urls_to_crawl = array( );

				
			/* CHECK THROUGH THE REVIEW FEEDS FOR EVERY COUNTRY */
			foreach ( $country_codes as $item ) {
				$country_code = $item['code'];
				$url_xml = 'https://itunes.apple.com/' . $country_code . '/rss/customerreviews/id=' . $this->itunes_id . '/xml';
				$urls_to_crawl[] = $url_xml;
				$itunes_json1 = json_encode( wp_remote_get( $url_xml ) );
				$data1 = json_decode( $itunes_json1, TRUE );
				$feed_body1 = $data1['body'];
				$first_review_page_url = iprm_get_contents_inside_tag( $feed_body1, '<link rel="first" href="', '"/>' );
				$last_review_page_url = iprm_get_contents_inside_tag( $feed_body1, '<link rel="last" href="', '"/>' );
				$current_review_page_url = iprm_get_contents_inside_tag( $feed_body1, '<link rel="self" href="', '"/>' );
				
				$last_review_page_url = trim($last_review_page_url);
				$first_review_page_url = trim($first_review_page_url);
				
				if ( strlen( $first_review_page_url ) != 0 ) {
					$firstPage = iprm_get_contents_inside_tag( $first_review_page_url, '/page=', '/id' );
				} else {
					$firstPage = 1;
				}
				
				$countryCodeOnUrl =  iprm_get_contents_inside_tag( $last_review_page_url, '.com/', '/rss' );
				/* NOTE: WILL GIVE US LINKS AS LAST PAGE, THIS ONLY CONSIDERS LAST PAGE IF IT IS IN THE COUNTRY WE ARE INDEXING */
				if ((strlen($last_review_page_url) != 0)  && ($countryCodeOnUrl == $country_code)){
					$lastPage = iprm_get_contents_inside_tag( $last_review_page_url, '/page=', '/id' );	
				}else {
					$lastPage = 1;
				}
				
				$current_entry = iprm_get_contents_inside_tag( $feed_body1, '<entry>', '</entry>' );
				
				/* ONLY CRAWL IF THERE IS AT LEAST ONE REVIEW */
				if ($current_entry != "EMPTYSTR") { 
				
					$urls_to_crawl[] = $current_review_page_url;
				}
								
				if ( $firstPage != $lastPage ) {
					$i = 1;									
					while ($i <= $lastPage) { 
														
						$current_review_page_url = 'https://itunes.apple.com/' . $country_code . '/rss/customerreviews/page='. $i .'/id=' . $this->itunes_id . '/xml';
						
						$urls_to_crawl[] = $current_review_page_url;
						
						$i++;
					}
				}
			}
				
			$urls_to_crawl = array_unique( $urls_to_crawl );
			$limiter = 0;
			foreach ( $urls_to_crawl as $url ) {
				$limiter++;
				if ($limiter > 100) {
					break;
				}
				$itunes_json = json_encode( wp_remote_get( $url ) );
				$data2 = json_decode( $itunes_json, TRUE );
				$feed_body = $data2['body'];
				/* LOOP THROUGH THE RAW CODE */
				while ( strpos( $feed_body, '<entry>' ) !== false ) {
			
					/* LOOK AT CODE IN BETWEEN FIRST INSTANCE OF ENTRY TAGS */
					$opening_tag = '<entry>';
					$closing_tag = '</entry>';
					$pos1 = strpos( $feed_body, $opening_tag );
					$pos2 = strpos( $feed_body, $closing_tag );
					$current_entry = substr( $feed_body, ( $pos1 + strlen( $opening_tag ) ), ( $pos2 - $pos1 - strlen( $opening_tag ) ) );
					
					
					/* GET REVIEW URL AND REVIEW URL COUNTRY CODE */
					$review_url = iprm_get_contents_inside_tag( $current_entry, '<uri>', '</uri>' );
					$review_url_country_code = substr( $review_url, ( strpos( $review_url, 'reviews' ) - 3 ), 2 );
					$name = iprm_get_contents_inside_tag( $current_entry, '<name>', '</name>');
					
					/* ADD NEW REVIEW TO REVIEW ARRAY */
					if ( $current_entry !== '' && $name != '' ) {
					
						$new_review = array( 
							'country' => iprm_get_country_data( $review_url_country_code, '' ),
							'review_date' => iprm_get_contents_inside_tag( $current_entry, '<updated>', '</updated>' ),
							'rating' => iprm_get_contents_inside_tag( $current_entry, '<im:rating>', '</im:rating>' ),
							'name' => iprm_get_contents_inside_tag( $current_entry, '<name>', '</name>' ),
							'title' => iprm_get_contents_inside_tag( $current_entry, '<title>', '</title>' ),
							'content' => iprm_get_contents_inside_tag( $current_entry, '<content type="text">', '</content>' ),
						);
				
						/* CHECK TO MAKE SURE THERE IS A RATING AND NAME BEFORE ADDING REVIEW TO ARRAY */
						if ( ( $new_review['rating'] == '' ) || ( $new_review['name'] == '' ) ||( $new_review['name'] == 'EMPTYSTR' ) ) {
							
						} else{ 
							array_push( $new_reviews, $new_review );
						}
					}
					/* REMOVE CODE AFTER FIRST INSTANCE OF ENTRY TAGS, SO THE NEXT LOOP ITERATION STARTS WITH THE NEXT INSTANCE OF ENTRY TAGS */
					$feed_body = substr( $feed_body, ( $pos2 + strlen( $closing_tag ) ) );
				}
			}
		/* DE-DUPE NEW REVIEWS */
		$new_reviews = iprm_remove_duplicates_from_review_array( $new_reviews  );
		

		/* ADD CACHED REVIEWS TO NEW REVIEWS */
		if (!is_array ($this->reviews)) { $this->reviews = []; }
		
		$this->reviews = array_merge( $this->reviews, $new_reviews );

		/* REMOVE DUPLICATES FROM COMBINED REVIEW ARRAY */
		$this->reviews = iprm_remove_duplicates_from_review_array( $this->reviews  );
		
		/* SORT REVIEWS ARRAY BY DATE */
		
		foreach ( $this->reviews as $key => $row ) {
		    $review_date[$key]  = $row['review_date'];
		    $review_country[$key] = $row['country'];
		    $review_rating[$key] = $row['rating'];
		    $review_name[$key] = $row['name'];
		    $review_title[$key] = $row['title'];
		    $review_content[$key] = $row['content'];
		}
		
		array_multisort( $review_date, SORT_DESC, $review_name, SORT_ASC, $this->reviews );
		
		
		/* ADD TIME AND REVIEW COUNT TO REVIEW CACHE HISTORY */
		$review_count = count( $this->reviews );
		$current_time = current_time( 'mysql' );
		if ( !is_array( $this->review_cache_history ) ) {
			$this->review_cache_history = array( );
		}
		array_push( $this->review_cache_history, array( 'time' => $current_time, 'count' => $review_count ) );
		
		/* REPLACE OLD REVIEW CACHE HISTORY WITH NEW REVIEW CACHE HISTORY */
		
		$serialStr = serialize ($this->review_cache_history);
		$dbSuccess = iprm_update_option(  "iprm_review_cache_history$this->itunes_id", $serialStr );
		
		if (!$dbSuccess) { 
			echo "problem writing history cache";
		}
		
		$serialStr = serialize ($this->reviews);
		/* REPLACE OLD CACHED REVIEWS WITH NEW CACHED REVIEWS */
		$file = WP_PLUGIN_DIR  . "/itunes-podcast-review-manager/cache/cache_$this->itunes_id.cache";
		$fileWrite = file_put_contents($file, $serialStr); 
		
		if ($fileWrite == false) {
			echo "problem writing review cache file";
		}
		
		/* RETURN COMBINED REVIEW ARRAY */
		return $this->reviews;
	} else {
		//echo "invalid itunes url";
	}
	
	}/* END DISPLAY REVIEWS FUNCTION*/
}  /* END CLASS DEFINITION */
