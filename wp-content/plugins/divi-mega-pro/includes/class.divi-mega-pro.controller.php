<?php

	class DiviMegaPro_Controller extends DiviMegaPro {
		
		protected static $_show_errors = FALSE;
		
		/**
		 * @var \WP_Filesystem_Base|null
		 */
		public static $wpfs;
		
		/**
		 * @var ET_Core_Data_Utils
		 */
		public static $data_utils;
		
		private static $slug = 'DiviMegaPro-divi-custom-styles';
		
		private static $post_id;
		
		private static $filename;
		
		private static $file_extension;
		
		private static $cache_dir;
		
		public function __construct() {
			
		}
		
		public static function _init( $post_id ) {
			
			$divi_styles = DiviMegaPro::$helper->getDiviStylesManager();
				
			// Don't include DOV custom CSS file if "Output Styles Inline" is enabled
			if ( et_get_option( 'et_pb_css_in_footer', 'off' ) === 'off' && $divi_styles ) {
				
				global $wp_filesystem;
				self::$wpfs = $wp_filesystem;
				
				self::$data_utils = new ET_Core_Data_Utils();
				
				$custom_divi_css = '';
				
				self::$post_id = $post_id;
				
				$custom_divi_css = ET_Builder_Element::get_style();
				
				// Remove #page-container from Divi Cached Inline Styles tag and cloning it to prevent issues
				$custom_divi_css = str_replace( '#page-container ', '', $custom_divi_css );
				
				// Remove .et_pb_extra_column_main from Divi Styles prevent cascade issues with Divi Overlays
				$custom_divi_css = str_replace( '.et_pb_extra_column_main', ' ', $custom_divi_css );
				
				self::$filename = 'et-custom-divimegapro-' . self::$post_id;
				self::$file_extension = '.min.css';
				self::$cache_dir = ET_Core_PageResource::get_cache_directory();
				
				$url = self::createResourceFile( $custom_divi_css );
				
				printf(
					'<link id="dmp-custom-' . et_core_esc_previously( self::$post_id ) . '" rel="stylesheet" href="%1$s" />', // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
					esc_url( set_url_scheme( $url ) )
				);
			}
		}
		
		
		private static function createResourceFile( $data, $check_cache_only = false ) {
			
			// Static resource file doesn't exist
			$time = (string) microtime( true );
			$time = str_replace( '.', '', $time );
			
			$relative_path = '/' . self::$post_id . '/' . self::$filename . '-' . $time . self::$file_extension;
			
			$files = glob( self::$cache_dir . '/' . self::$post_id . '/' . 'et-custom-divimegapro-' . self::$post_id . '-[0-9]*' . self::$file_extension );
			
			$create_resource = true;
			
			if ( $files ) {
				
				$the_files = $files;
				$file = array_pop( $the_files );
				
				$now = time();
				$cache_content_date = filemtime( $file );
				
				$cache_since = $now - $cache_content_date;
				
				// A day passed? refresh cache
				if ( $cache_since > 86400 ) {
					
					// There may be multiple files for this resource. Let's delete the extras.
					foreach ( $files as $extra_file ) {
						
						self::$wpfs->delete( $extra_file );
					}
					
					$create_resource = true;
			
				} else {
					
					$url = et_core_cache_dir()->url;
					$path = self::$data_utils->normalize_path( $file );
					$relative_path = et_()->path( $url, self::$post_id, basename( $path ) );
				
					$create_resource = false;
				}
			}
			
			if ( $create_resource === true && $check_cache_only === true ) {
				
				return false;
			}
			
			if ( $create_resource === true ) {
				
				if ( $data === '' ) {
					
					return 'no data';
				}
				
				$file = self::$cache_dir . $relative_path;
				
				$directoryName = self::$cache_dir . '/' . self::$post_id;
				
				// Check if the directory already exists.
				if ( !is_dir( $directoryName ) ) {
					
					// Directory does not exist, so lets create it.
					mkdir( $directoryName, 0755 );
				}
				
				if ( is_writable( self::$cache_dir ) ) {
					
					self::$wpfs->put_contents( $file, $data, 0644 );
				}
				
				$relative_divi_path  = self::$cache_dir;
				$relative_divi_path .= $relative_path;
				
				$start = strpos( $relative_divi_path, 'et-cache' );
				$parse = substr( $relative_divi_path, $start );
				
				$relative_path = content_url( $parse );
			}
			
			return $relative_path;
		}
		
		
		public static function showDiviMegaPro( $render = true ) {
			
			$render = ( $render === '' ) ? true : false;
			
			// Settings
			self::$helper = new DiviMegaPro_Helper;
			
			$divimegapros_in_current = array();
			
			try {
				
					
				// Singleton feature
				$divimegapro_singleton = get_option( 'divimegapro_singleton' );
				
				$header = 'false';
				$content = 'false';
				$footer = 'false';
				
				if ( isset( $divimegapro_singleton[0] ) ) {
					
					$header = in_array( 'header', $divimegapro_singleton ) ? 'true' : 'false';
					$content = in_array( 'content', $divimegapro_singleton ) ? 'true' : 'false';
					$footer = in_array( 'footer', $divimegapro_singleton ) ? 'true' : 'false';
				}
				?>
				<script>
				var divimegapro_singleton = [];
				divimegapro_singleton['header'] = <?php print et_core_intentionally_unescaped( $header, 'fixed_string' ) ?>;
				divimegapro_singleton['content'] = <?php print et_core_intentionally_unescaped( $content, 'fixed_string' ) ?>;
				divimegapro_singleton['footer'] = <?php print et_core_intentionally_unescaped( $footer, 'fixed_string' ) ?>;
				var divimegapro_singleton_enabled = ( divimegapro_singleton['header'] || divimegapro_singleton['content'] || divimegapro_singleton['footer'] ) ? true : false;
				</script>
				
				<?php
				
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['et_fb'] ) && $render ) {
					
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$divi_builder_enabled = sanitize_text_field( wp_unslash( $_GET['et_fb'] ) );
					
					// is divi theme builder ?
					if ( $divi_builder_enabled === '1' ) {
						
						return;
					}
				}
				
				if ( $render ) {
					
					print '<div id="divimegapro-template"></div>';
					print '<div class="divimegapro-wrapper">';
				
				}
				
				
				/* Search CSS Triggers in all Divi divimegapros */
				if ( isset( DiviMegaPro::$divimegaproList['css_trigger'] ) ) {
					
					$posts = DiviMegaPro::$divimegaproList['css_trigger'];
				}
				else {
					
					$posts = '';
				}
				
				if ( !empty( $posts ) && $render ) {
						
					print '<script>var divimegapros_with_css_trigger = {';
					
					foreach( $posts as $post_id => $css_selector ) {
						
						print '\'' . et_core_esc_previously( $post_id ) . '\': \'' . et_core_esc_previously( $css_selector ) . '\',';
					}
					
					print '};</script>';
				}
				
				
				/* Search Divi divimegapros with Custom Close Buttons */
				if ( $render ) {
					
					$posts = DiviMegaPro_Model::getDiviMegaPros('customizeclosebtn');
					
					if ( isset( $posts[0] ) ) {
						
						print '<style type="text/css">';
						
						foreach( $posts as $dmm_post ) {
							
							$post_id = $dmm_post->ID;
							
							$cbc_textcolor = get_post_meta( $post_id, 'dmp_closebtn_text_color', true );
							$cbc_bgcolor = get_post_meta( $post_id, 'dmp_closebtn_bg_color', true );
							$cbc_fontsize = get_post_meta( $post_id, 'dmp_closebtn_fontsize', true );
							$cbc_borderradius = get_post_meta( $post_id, 'dmp_closebtn_borderradius', true );
							$cbc_padding = get_post_meta( $post_id, 'dmp_closebtn_padding', true );
							
							$customizeclosebtn = get_post_meta( $post_id, 'dmp_customizeclosebtn' );
							if ( isset( $customizeclosebtn[0] ) ) {
								
								$customizeclosebtn = $customizeclosebtn[0];
								
							} else {
								
								continue;
							}
							
							if ( $customizeclosebtn ) {
								
								print '
								.divimegapro-customclose-btn-' . et_core_esc_previously( $post_id ) . ' {
									top:5px !important;
									color:' . esc_attr( $cbc_textcolor ) . ' !important;
									background-color:' . esc_attr( $cbc_bgcolor ) . ' !important;
									font-size:' . esc_attr( $cbc_fontsize ) . 'px !important;
									padding:' . esc_attr( $cbc_padding ) . 'px !important;
									-moz-border-radius:' . esc_attr( $cbc_borderradius ) . '% !important;
									-webkit-border-radius:' . esc_attr( $cbc_borderradius ) . '% !important;
									-khtml-border-radius:' . esc_attr( $cbc_borderradius ) . '% !important;
									border-radius:' . esc_attr( $cbc_borderradius ) . '% !important;
								}
								';
							}
						}
						
						print '</style>';
					}
				}
				
				
				/* Search Divi divimegapros with Arrow Features */
				if ( $render ) {
					
					$posts = DiviMegaPro_Model::getDiviMegaPros('enable_arrow');
					
					if ( isset( $posts[0] ) ) {
						
						print '<style type="text/css">';
						
						foreach( $posts as $dmm_post ) {
							
							$post_id = $dmm_post->ID;
							
							$dmp_arrowfeature_color = esc_attr( get_post_meta( $post_id, 'dmp_arrowfeature_color', true ) );
							
							$dmp_enable_arrow = get_post_meta( $post_id, 'dmp_enable_arrow' );
							if ( isset( $dmp_enable_arrow[0] ) ) {
								
								$dmp_enable_arrow = $dmp_enable_arrow[0];
								
							} else {
								
								continue;
							}
							
							if ( $dmp_enable_arrow ) {
								
								$dmp_arrow_width = esc_attr( get_post_meta( $post_id, 'dmp_arrowfeature_width', true ) );
								if ( !isset( $dmp_arrow_width ) ) {
									
									$dmp_arrow_width = 0;
								}
								
								$dmp_arrow_height = esc_attr( get_post_meta( $post_id, 'dmp_arrowfeature_height', true ) );
								if ( !isset( $dmp_arrow_height ) ) {
									
									$dmp_arrow_height = 0;
								}
								
								$dmp_arrow_width = $dmp_arrow_width * 0.1;
								$dmp_arrow_height = $dmp_arrow_height * 0.1;
								
								print et_core_esc_previously( '
								.dmp-' . $post_id . ' .tippy-svg-arrow {
									fill:' . $dmp_arrowfeature_color . ' !important;
								}
								.dmp-' . $post_id . ' .tippy-arrow,
								.dmp-' . $post_id . ' .tippy-svg-arrow {
									-webkit-transform: scale( ' . $dmp_arrow_width . ', ' . $dmp_arrow_height . ');  /* Saf3.1+, Chrome */
									 -moz-transform: scale( ' . $dmp_arrow_width . ', ' . $dmp_arrow_height . ');  /* FF3.5+ */
									  -ms-transform: scale( ' . $dmp_arrow_width . ', ' . $dmp_arrow_height . ');  /* IE9 */
									   -o-transform: scale( ' . $dmp_arrow_width . ', ' . $dmp_arrow_height . ');  /* Opera 10.5+ */
										  transform: scale( ' . $dmp_arrow_width . ', ' . $dmp_arrow_height . ');
								}
								.tippy-popper.dmp-' . $post_id . '[x-placement^=top] .tippy-arrow {
									border-top-color:' . $dmp_arrowfeature_color . ' !important;
								}
								.tippy-popper.dmp-' . $post_id . '[x-placement^=bottom] .tippy-arrow {
									border-bottom-color:' . $dmp_arrowfeature_color . ' !important;
								}
								.tippy-popper.dmp-' . $post_id . '[x-placement^=left] .tippy-arrow {
									border-left-color:' . $dmp_arrowfeature_color . ' !important;
								}
								.tippy-popper.dmp-' . $post_id . '[x-placement^=right] .tippy-arrow {
									border-right-color:' . $dmp_arrowfeature_color . ' !important;
								}
								' );
							}
						}
						
						print '</style>';
					}
				}
				
				
				if ( isset( DiviMegaPro::$divimegaproList['ids'] ) ) {
					
					$divimegapros = DiviMegaPro::$divimegaproList['ids'];
				}
				else {
					
					$divimegapros = '';
				}
				
				if ( is_array( $divimegapros ) && count( $divimegapros ) > 0 ) {
					
					global $post;
					
					$display_in_current = false;
					
					$current_post_id = 0;
					
					if ( function_exists( 'get_queried_object_id' ) && get_queried_object_id() > 0 ) {
						
						$current_post_id = get_queried_object_id();
					
					} else {
					
						$current_home_post_id = (int) get_option( 'page_on_front' );
						
						$is_home = is_home();
						
						if ( $current_home_post_id == 0 && !$is_home ) {
							
							$current_post_id = get_the_ID();
						}
					}
					
					foreach( $divimegapros as $divimegapro_id => $idx ) {
						
						if ( get_post_status ( $divimegapro_id ) == 'publish' ) {
						
							$at_pages = get_post_meta( $divimegapro_id, 'dmp_css_selector_at_pages' );
							
							$display_in_posts = ( !isset( $at_pages[0] ) ) ? 'all' : $at_pages[0];
							
							if ( $display_in_posts == 'specific' ) {
								
								$display_in_current = false;
								
								$in_posts = get_post_meta( $divimegapro_id, 'dmp_css_selector_at_pages_selected' );
								
								if ( isset( $in_posts[0] ) && $in_posts[0] != '' ) {
								
									foreach( $in_posts[0] as $in_post => $the_id ) {
										
										if ( $the_id == $current_post_id ) {
											
											$display_in_current = true;
											
											break;
										}
									}
								}
							}
							
							if ( $display_in_posts == 'all' ) {
								
								$display_in_current = true;
								
								$except_in_posts = get_post_meta( $divimegapro_id, 'dmp_css_selector_at_pagesexception_selected' );
								
								if ( isset( $except_in_posts[0] ) && $except_in_posts[0] != '' ) {
									
									foreach( $except_in_posts[0] as $in_post => $the_id ) {
										
										if ( $the_id == $current_post_id ) {
											
											$display_in_current = false;
											
											break;
										}
									}
								}
							}
							
							if ( $display_in_current ) {
								
								$disablemobile = get_post_meta( $divimegapro_id, 'dmp_mpa_disablemobile' );
								$disabletablet = get_post_meta( $divimegapro_id, 'dmp_mpa_disabletablet' );
								$disabledesktop = get_post_meta( $divimegapro_id, 'dmp_mpa_disabledesktop' );
								
								if ( isset( $disablemobile[0] ) ) {
									
									$disablemobile = $disablemobile[0];
									
								} else {
									
									$disablemobile = 0;
								}
								
								if ( isset( $disabletablet[0] ) ) {
									
									$disabletablet = $disabletablet[0];
									
								} else {
									
									$disabletablet = 0;
								}
								
								if ( isset( $disabledesktop[0] ) ) {
									
									$disabledesktop = $disabledesktop[0];
									
								} else {
									
									$disabledesktop = 0;
								}
								
								$renderDiviMegaPro = 1;
								if ( $disablemobile && self::$isMobileDevice ) {
									
									$renderDiviMegaPro = 0;
								}
								
								if ( $disabletablet && self::$isTabletDevice ) {
									
									$renderDiviMegaPro = 0;
								}
								
								if ( $disabledesktop && !self::$isMobileDevice && !self::$isTabletDevice ) {
									
									$renderDiviMegaPro = 0;
								}
								
								if ( $renderDiviMegaPro ) {
									
									$divimegapros_in_current[ $divimegapro_id ] = $divimegapro_id;
									
									if ( $render ) {
									
										print et_core_esc_previously( self::render( $divimegapro_id ) );
										
										self::_init( $divimegapro_id );
									}
									
									$dmpswithindmp = self::searchForDMPsWithinDMPs( $divimegapro_id );
									
									if ( count( $dmpswithindmp ) > 0 ) {
										
										foreach( $dmpswithindmp as $dmp_id => $dmp_idx ) {
											
											$divimegapros_in_current[ $dmp_id ] = $dmp_id;
										
											if ( !isset( $divimegapros[$dmp_id] ) && $render ) {
												
												print et_core_esc_previously( self::render( $dmp_id ) );
												
												self::_init( $dmp_id );
											}
										}
									}
								}
							}
						}
					}
				}
				
				if ( $render ) {
					
					print '</div>';
					
					?>
					<script>
					var ajaxurl = "<?php echo et_core_intentionally_unescaped( admin_url( 'admin-ajax.php' ), 'fixed_string' ); ?>";
					var diviAjaxUrl = '<?php print et_core_intentionally_unescaped( plugins_url( 'ajax-handler-wp.php' , __FILE__ ), 'fixed_string' ) ; ?>';
					</script>
					<?php
				}
			
			} catch (Exception $e) {
			
				DiviMegaPro::log( $e );
			}
			
			
			
			if ( get_option( 'dmp_restore_divi_static_css_file', false, false ) === 'on' ) {
				
				// Force "Static CSS File Generation"
				et_update_option( 'et_pb_static_css_file', 'on' );
			}
			
			if ( !$render ) {
				
				return $divimegapros_in_current;
			}
		}
		
		
		private static function searchForDMPsWithinDMPs( $divimegapro_id = NULL ) {
			
			$post = get_post( $divimegapro_id );
			
			/* Search divimegapros within divimegapros */
			if ( $post ) {
				
				$divimegapros_in_post = DiviMegaPro_Helper::searchForDMPsInPost( $post );
				
				if ( is_array( $divimegapros_in_post ) ) {
					
					return $divimegapros_in_post;
				}
				else {
					
					return array();
				}
			}
		}
		
		
		public static function getRender( $post_id = NULL, $avoidRenderTags = 0, $divilifepost = 0 ) {
			
			try {
				
				if ( !is_numeric( $post_id ) ) {
					
					throw new InvalidArgumentException( 'divimegapro_render > $post_id is not numeric');
				}
				
			} catch (Exception $e) {
			
				DiviMegaPro::log( $e );
			}
			
			self::$post_id = (int) $post_id;
			
			self::$filename = 'et-custom-divimegapro-' . self::$post_id;
			self::$file_extension = '.txt';
			self::$cache_dir = ET_Core_PageResource::get_cache_directory();
			
			global $wp_filesystem;
			self::$wpfs = $wp_filesystem;
			
			self::$data_utils = new ET_Core_Data_Utils();
			
			$render = array();
			
			$output = $response = '';
			
			if ( $divilifepost !== 1 ) {
				
				$url = DIVI_MEGA_PRO_PLUGIN_URL . 'getpost.php';
				
				$params = array(
					'divilife_action' => 'getpostdata',
					'wp_root_url' => get_home_path(),
					'post_id' => self::$post_id
				);
				
				$divimegapro_enable_cache = get_option( 'divimegapro_enable_cache' );
				
				if ( !isset( $divimegapro_enable_cache[0] ) ) {
					
					$divimegapro_enable_cache = 0;
				}
				
				if ( $divimegapro_enable_cache === '1' ) {
					
					$check_cache = self::createResourceFile( null, true );
				}
				
				if ( $divimegapro_enable_cache !== '1' ) {
					
					$check_cache = false;
				}
				
				if ( $check_cache === false ) {
				
					$response = wp_remote_post( $url, array( 'timeout' => 10, 'redirection' => 0, 'body' => $params ) );
				}
				
				if ( is_wp_error( $response ) ) {
					
					try {
						
						throw new InvalidArgumentException( 'DiviMegaPro_Controller::getRender > wp_remote_post() failed to call: ' . $url );
						
					} catch (Exception $e) {
					
						DiviMegaPro::log( $e );
					}
				}
				else {
					
					$response_body = wp_remote_retrieve_body( $response );
					
					if ( $divimegapro_enable_cache === '1' ) {
					
						$filecache = self::createResourceFile( $response_body );
						
						if ( $filecache !== '' && $filecache !== false ) {
							
							$files = glob( self::$cache_dir . '/' . self::$post_id . '/' . 'et-custom-divimegapro-' . self::$post_id . '-[0-9]*' . self::$file_extension );
							
							if ( $files ) {
								
								foreach( (array) $files as $file ) {
									
									$file = self::$data_utils->normalize_path( $file );
									
									ob_start();
									include $file;
									$response_body = ob_get_clean();
								}
							}
						}
					}
					
					$response_body = json_decode( $response_body );
					
					if ( $response_body !== NULL ) {
						
						$post_data = $response_body->post_data;
						$output = $response_body->output;
						
					} else {
						
						$post_data = $output = '';
					}
				}
			}
			
			if ( $output === '' || ( isset( $response['response'] ) && $response['response']['code'] === 401 ) ) {
				
				$post_data = get_post( self::$post_id );
				
				$content = $post_data->post_content;
				
				if ( $avoidRenderTags === 1 ) {
				
					$content = DiviMegaPro_Helper::avoidRenderTags( $content );
				}
				
				$output = apply_filters( 'et_builder_render_layout', $content );
			}
			
			// Divi builder layout is rendered only on singular template
			// Force render singular template
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_bfb_new_page = isset( $_GET['is_new_page'] ) && '1' === $_GET['is_new_page'];
			
			if ( !is_singular() && !$is_bfb_new_page && !et_theme_builder_is_layout_post_type( get_post_type( get_the_ID() ) ) ) {
				
				$output = et_builder_get_layout_opening_wrapper() . $output . et_builder_get_layout_closing_wrapper();
			}
			
			$output = str_replace( 'id="et-boc"', '', $output );
			
			if ( strpos( $output, 'id="et-boc"' ) === false ) {
				
				$output = et_builder_get_builder_content_opening_wrapper() . $output . et_builder_get_builder_content_closing_wrapper();
			}
			
			if ( get_option( 'dmp_restore_divi_static_css_file', false, false ) === 'on' ) {
				
				// Restore "Static CSS File Generation"
				et_update_option( 'et_pb_static_css_file', 'on' );
			}
			
			$render['post_data'] = $post_data;
			$render['output'] = $output;
			
			return $render;
		}
		
		
		public static function render( $divimegapro_id = NULL ) {
			
			$render = self::getRender( $divimegapro_id, 0, 1 );
			
			$post_data = $render['post_data'];
			
			$output = $render['output'];
			
			$is_mobile = self::$isMobileDevice;
			
			if ( !$is_mobile ) {
				
				$is_mobile = 0;
			}
			
			
			/* Close Button Customizations */
			$dmp_enabledesktop = get_post_meta( $post_data->ID, 'dmp_enabledesktop', true );
			if ( !isset( $dmp_enabledesktop ) ) {
				
				$dmp_enabledesktop = 0;
			}
			
			$dmp_enablemobile = get_post_meta( $post_data->ID, 'dmp_enablemobile', true );
			if ( !isset( $dmp_enablemobile ) ) {
				
				$dmp_enablemobile = 0;
			}
			
			$dmp_customizeclosebtn = get_post_meta( $post_data->ID, 'dmp_customizeclosebtn' );
			if( !isset( $dmp_customizeclosebtn[0] ) ) {
				
				$dmp_customizeclosebtn[0] = '0';
			}
			
			
			/* Arrow Feature */
			$dmp_enable_arrow = get_post_meta( $post_data->ID, 'dmp_enable_arrow', true );
			if ( !isset( $dmp_enable_arrow ) ) {
				
				$dmp_enable_arrow = 0;
			}
			
			$dmp_arrowfeature_type = get_post_meta( $post_data->ID, 'dmp_arrowfeature_type', true );
			if ( !isset( $dmp_arrowfeature_type ) ) {
				
				$dmp_arrowfeature_type = 0;
			}
			
			
			/* Mega Menu Settings */
			$dmp_animation = get_post_meta( $post_data->ID, 'dmp_animation', true );
			if ( !isset( $dmp_animation ) ) {
				
				$dmp_animation = 'shift-away';
			}
			
			$dmp_placement = get_post_meta( $post_data->ID, 'dmp_placement', true );
			if ( !isset( $dmp_placement ) ) {
				
				$dmp_placement = 'down';
			}
			
			$dmp_margintopbottom = get_post_meta( $post_data->ID, 'dmp_margintopbottom', true );
			if ( !isset( $dmp_margintopbottom ) ) {
				
				$dmp_margintopbottom = 0;
			}
			
			$dmp_megaprowidth = get_post_meta( $post_data->ID, 'dmp_megaprowidth', true );
			if ( !isset( $dmp_megaprowidth ) ) {
				
				$dmp_megaprowidth = '100';
			}
			
			$dmp_megaprowidth_custom = get_post_meta( $post_data->ID, 'dmp_megaprowidth_custom', true );
			if ( !isset( $dmp_megaprowidth_custom ) ) {
				
				$dmp_megaprowidth_custom = '100';
			}
			
			$dmp_megaprofixedheight = get_post_meta( $post_data->ID, 'dmp_megaprofixedheight', true );
			if ( !isset( $dmp_megaprofixedheight ) ) {
				
				$dmp_megaprofixedheight = 0;
			}
			
			$dmp_triggertype = get_post_meta( $post_data->ID, 'dmp_triggertype', true );
			if ( !isset( $dmp_triggertype ) ) {
				
				$dmp_triggertype = 'hover';
			}
			
			$dmp_exittype = get_post_meta( $post_data->ID, 'dmp_exittype', true );
			if ( !isset( $dmp_exittype ) ) {
				
				$dmp_exittype = 'hover';
			}
			
			$dmp_exitdelay = get_post_meta( $post_data->ID, 'dmp_exitdelay', true );
			if ( !isset( $dmp_exitdelay ) ) {
				
				$dmp_exitdelay = 0;
			}
			
			$dmp_bg_color = get_post_meta( $post_data->ID, 'dmp_bg_color', true );
			$dmp_font_color = get_post_meta( $post_data->ID, 'dmp_font_color', true );
			
			$body = $output;
			
			require( DIVI_MEGA_PRO_PLUGIN_DIR . '/templates/divimegapro.php');
		}
		
	} // end DiviMegaPro_Controller