<?php

	error_reporting(0);
	
	function dmp_trailingslashit( $string ) {
		return dmp_untrailingslashit( $string ) . '/';
	}
	
	function dmp_untrailingslashit( $string ) {
		return rtrim( $string, '/\\' );
	}
	
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $_POST['wp_root_url'] ) ) {
	
		$wp_root_url = sanitize_text_field( $_POST['wp_root_url'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$wp_root_url = htmlentities( dmp_trailingslashit( $wp_root_url ) ); 
		
		// Remove all illegal characters from a url
		$wp_root_url = filter_var( $wp_root_url, FILTER_SANITIZE_URL );
		
		require_once( $wp_root_url . 'wp-load.php' );
		
		wp();
		
		require_once( $wp_root_url . WPINC . '/template-loader.php' );
		
		if ( isset( $_POST['divilife_action'] ) && $_POST['divilife_action'] === 'getpostdata' && function_exists( 'get_post' ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
			
			$render = array();
			
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_POST['post_id'] ) ) {
			
				$postid = sanitize_text_field( wp_unslash( $_POST['post_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				
				$postid = (int) $postid;
				
				$post_data = '';
				$output = '';
				
				if ( is_int( $postid ) && $postid !== 0 && strlen( $postid ) <= 20 ) {
					
					$post_data = get_post( $postid );
					
					$content = $post_data->post_content;
					
					$output = apply_filters( 'et_builder_render_layout', $content );	
				}
				
				$render['post_data'] = $post_data;
				$render['output'] = $output;
				
				header( 'Content-type: application/json' );
				$data = wp_json_encode( $render );
				
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				die( $data );
			}
		}
	}

