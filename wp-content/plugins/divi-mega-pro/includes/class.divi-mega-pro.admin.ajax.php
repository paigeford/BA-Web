<?php

	class DiviMegaPro_Admin_Ajax {
	
		public function __construct() {
			
		}
		
		public static function call_get_posts() {
			
			check_ajax_referer( 'divilife_divimegapro', 'nonce' );
			
			if ( isset( $_POST['q'] ) ) {
			
				$q = sanitize_text_field( wp_unslash( $_POST['q'] ) );
			
			} else {
				
				return;
			}
			
			
			if ( isset( $_POST['page'] ) ) {
				
				$page = (int) $_POST['page'];
				
			} else {
				
				$page = 1;
			}
			
			$args = array(
				'q' => $q,
				'page' => $page
			);
			
			$posts = DiviMegaPro_Admin_Controller::get_posts( $args );
			
			if ( isset( $posts['total_count'] ) ) {
			
				header( 'Content-type: application/json' );
				$data = wp_json_encode(
				
					array(
						'total_count' => $posts['total_count'],
						'items' => $posts['items']
					)
				);
				
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				die( $data );
			}
		}
		
	} // end DiviMegaPro_Ajax