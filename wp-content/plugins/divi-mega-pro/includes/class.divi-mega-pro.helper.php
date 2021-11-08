<?php

	class DiviMegaPro_Helper extends DiviMegaPro {
		
		public function __construct() {
			
		}
		
		public static function get_all_wordpress_menus(){
			return get_terms( 'nav_menu', array( 'hide_empty' => true ) ); 
		}
		
		
		public static function prepareMenu( $key = NULL )
		{
			try {
				
				if ( !$key ) {
					
					throw new InvalidArgumentException( 'DiviMegaPro_Helper::prepareMenu > Required var $key');
					
				}
				
				// it is an url with hash divimegapros?
				if ( strpos( $key, "#" ) !== false ) {
					
					$exploded_url = explode( "#", $key );
					
					if ( isset( $exploded_url[1] ) ) {
						
						$key = str_replace( 'divimegapro-', '', $exploded_url[1] );
						
						return $key;
					}
				}
				
				$pos = strpos( $key, 'divimegapro-' );
				if ( $pos !== false ) {
					
					$key = substr( $key, $pos );
					$key = preg_replace( '/[^0-9.]/', '', $key );
				}
				else {
					
					return NULL;
				}
				
				if ( $key == '' ) {
					return NULL;
				}
				
				if ( !self::barIsPublished( $key ) ) {
					
					return NULL;
				}
				
				return $key;
				
			} catch (Exception $e) {
				
				DiviMegaPro::log( $e );
				
				return NULL;
			}
		}
		
		
		public static function searchDMPs( $content = NULL ) {
			
			$divimegapros_in_content = array();
			
			try {
				
				if ( !$content ) {
					
					throw new InvalidArgumentException( 'DiviMegaPro_Helper::searchDMPs > Required var $content');
				}
				
				$matches = array();
				$pattern = '/id="(.*?divimegapro\-[0-9]+)"/';
				preg_match_all($pattern, $content, $matches);
				
				$found_dmps_byid = $matches[1];
				
				$matches = array();
				$pattern = '/(\S+)class=["\']?((?:.divimegapro\-[0-9]+(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/';
				preg_match_all($pattern, $content, $matches);
				
				$found_dmps_byclass = $matches[2];
				
				$matches = array();
				$pattern = '/.*class\s*=\s*["\'].*divimegapro\-[0-9]+/';
				preg_match_all($pattern, $content, $matches);
				
				$found_dmps_byclass_2 = $matches[0];
				
				$matches = array();
				$pattern = '/href="\#(.*?divimegapro\-[0-9]+)"/';
				preg_match_all($pattern, $content, $matches);
				
				$found_dmps_byhrefhash = $matches[1];
				
				$matches = array();
				$pattern = '/url="#(.*?divimegapro\-[0-9]+)"/';
				preg_match_all($pattern, $content, $matches);
				
				$found_dmps_byurlhash = $matches[1];
				
				$matches = array();
				$pattern = '/(?=<[^>]+(?=[\s+\"\']divimegapro\-[0-9]+[\s+\"\']).+)([^>]+>)/';
				preg_match_all($pattern, $content, $matches);
				
				$found_dmps_onanyattr = $matches[0];
				
				$divimegapros_found = $found_dmps_byid + $found_dmps_byclass + $found_dmps_byclass_2 + $found_dmps_byhrefhash + $found_dmps_byurlhash + $found_dmps_onanyattr;
				
				if ( is_array( $divimegapros_found ) && count( $divimegapros_found ) > 0 ) {
				
					$divimegapros_in_content = array_flip( array_filter( array_map( 'self::prepareMenu', $divimegapros_found ) ) );
				}
				
			} catch (Exception $e) {
			
				DiviMegaPro::log( $e );
				
				return $divimegapros_in_content;
			}
				
			return $divimegapros_in_content;
		}
		
		
		public static function searchForDMPsInPost( $post = NULL, $avoidRenderTags = 0 ) {
			
			$divimegapros_in_post = array();
			
			try {
				
				if ( !$post ) {
					
					throw new InvalidArgumentException( 'DiviMegaPro_Helper::searchForDMPsInPost > Required var $post');
				}
				
				if ( ! isset( $post->ID ) ) {
					
					throw new InvalidArgumentException( 'DiviMegaPro_Helper::searchForDMPsInPost > Couldn\'t found property $post->ID');
				}
				
				$post_content = DiviMegaPro_Controller::getRender( $post->ID, $avoidRenderTags );
				
				if ( !$post_content ) {
					
					throw new InvalidArgumentException( 'DiviMegaPro_Helper::searchForDMPsInPost > Post ID: ' . $post->ID . ' Not found');
				}
				
				$post_content = $post_content['output'];
				
				$divimegapros_in_post = self::searchDMPs( $post_content );
				
			} catch (Exception $e) {
			
				DiviMegaPro::log( $e );
				
				return $divimegapros_in_post;
			}
			
			return $divimegapros_in_post;
		}
		
		
		private static function barIsPublished( $key ) {
			
			try {
			
				$post = get_post_status( $key );
			
			} catch (Exception $e) {
			
				DiviMegaPro::log( $e );
				
				return FALSE;
			}
			
			if ( $post != 'publish' ) {
				
				return FALSE;
			}
			
			return TRUE;
		}
		
		
		// Fastest way to check if a string is JSON
		public static function isJson($string) {
		
			json_decode($string);
			
			return ( json_last_error() == JSON_ERROR_NONE );
		}
		
		
		public static function getDiviStylesManager() {
			
			if ( wp_doing_ajax() || wp_doing_cron() || ( is_admin() && ! is_customize_preview() ) ) {
				return;
			}

			/** @see ET_Core_SupportCenter::toggle_safe_mode */
			if ( et_core_is_safe_mode_active() ) {
				return;
			}
			
			$all_resources = ET_Core_PageResource::get_resources();
			
			$enqueued_resources = array();
			
			foreach( $all_resources as $resource ) {
				
				if ( $resource->enqueued === true ) {
					
					$enqueued_resources[] = $resource;
				}
			}
			
			return $enqueued_resources;
		}
		
		
		public static function avoidRenderTags( $content = NULL, $restore = false ) {
			
			if ( !$content ) {
				
				return '';
			}
			
			try {
				
				if ( !$restore ) {
					
					$content = str_replace( '[et_pb_video', '[et_pb_video_divimegaprotemp', $content );
					$content = str_replace( '[/et_pb_video]', '[/et_pb_video_divimegaprotemp]', $content );
					
					$content = str_replace( '[et_pb_contact_form', '[et_pb_contact_form_divimegaprotemp', $content );
					$content = str_replace( '[/et_pb_contact_form]', '[/et_pb_contact_form_divimegaprotemp]', $content );
					
					$content = str_replace( '[woocommerce_checkout]', '[woocommerce_checkout_divimegaprotemp]', $content );
					$content = str_replace( '[woocommerce_my_account]', '[woocommerce_my_account_divimegaprotemp]', $content );
					
					$content = str_replace( '[et_pb_wc_add_to_cart', '[et_pb_wc_add_to_cart_divimegaprotemp]', $content );
					
					$content = str_replace( '[ultimatemember', '[ultimatemember_divimegaprotemp]', $content );
					
					$content = str_replace( '[give_form', '[give_form_divimegaprotemp]', $content );
					
					$content = str_replace( '[ninja_form', '[ninja_form_divimegaprotemp]', $content );
				
				} else {
					
					$content = str_replace( '[et_pb_video_divimegaprotemp', '[et_pb_video', $content );
					$content = str_replace( '[/et_pb_video_divimegaprotemp]', '[/et_pb_video]', $content );
					
					$content = str_replace( '[et_pb_contact_form_divimegaprotemp', '[et_pb_contact_form', $content );
					$content = str_replace( '[/et_pb_contact_form_divimegaprotemp]', '[/et_pb_contact_form]', $content );
					
					$content = str_replace( '[woocommerce_checkout_divimegaprotemp]', '[woocommerce_checkout]', $content );
					$content = str_replace( '[woocommerce_my_account_divimegaprotemp]', '[woocommerce_my_account]', $content );
					$content = str_replace( '[et_pb_wc_add_to_cart_divimegaprotemp', '[et_pb_wc_add_to_cart]', $content );
					
					$content = str_replace( '[ultimatemember_divimegaprotemp', '[ultimatemember]', $content );
					
					$content = str_replace( '[give_form_divimegaprotemp', '[give_form]', $content );
					
					$content = str_replace( '[ninja_form_divimegaprotemp', '[ninja_form]', $content );
				}
			
			} catch ( Exception $e ) {
			
				DiviOverlays::log( $e );
			}
			
			return $content;
		}
	}
	
	