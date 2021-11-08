<?php

	class DiviMegaPro_Model extends DiviMegaPro {
		
		protected static $_show_errors = FALSE;
		
		public function __construct() {
			
			
		}
		
		
		public static function getDiviMegaPros( $type ) {
			
			global $wp_query;
			
			$posts = array();
			
			switch ( $type ) {
				
				case 'css_trigger':
				
					$args = array(
						'meta_key'   => 'dmp_css_selector',
						'meta_value' => '',
						'meta_compare' => '!=',
						'post_type' => 'divi_mega_pro',
						'posts_per_page' => -1,
						'post_status' => 'publish',
						'cache_results'  => false
					);
					$query = new WP_Query( $args );
					
					$posts = $query->get_posts();
					
					break;
					
				case 'customizeclosebtn':
					
					$args = array(
						'meta_key'   => 'dmp_customizeclosebtn',
						'meta_value' => '',
						'meta_compare' => '!=',
						'post_type' => 'divi_mega_pro',
						'posts_per_page' => -1,
						'post_status' => 'publish',
						'cache_results'  => false
					);
					$query = new WP_Query( $args );
					
					$posts = $query->get_posts();
					
					break;
				
				case 'enable_arrow':
				
					$args = array(
						'meta_key'   => 'dmp_enable_arrow',
						'meta_value' => '',
						'meta_compare' => '!=',
						'post_type' => 'divi_mega_pro',
						'posts_per_page' => -1,
						'post_status' => 'publish',
						'cache_results'  => false
					);
					$query = new WP_Query( $args );
					
					$posts = $query->get_posts();
					
					break;
					
				case 'force_render':
				
					$args = array(
						'meta_key'   => 'dmp_force_render',
						'meta_value' => '1',
						'meta_compare' => '==',
						'post_type' => 'divi_mega_pro',
						'posts_per_page' => -1,
						'post_status' => 'publish',
						'cache_results'  => false
					);
					$query = new WP_Query( $args );
					
					$posts = $query->get_posts();
					
					break;
				
				default:
				
					return 'First parameter is required. For e.g.: "css_trigger", "enableurltrigger", "customizeclosebtn", "enable_arrow"';
			}
			
			return $posts;
		}
		
	} // end DiviMegaPro_Model
	