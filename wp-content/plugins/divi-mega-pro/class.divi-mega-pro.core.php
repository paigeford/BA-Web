<?php

	class DiviMegaPro {
		
		private static $initiated = false;
		
		/**
		 * Holds an instance of DiviMegaPro Helper class
		 *
		 * @since 1.0
		 * @var DiviMegaPro_Helper
		 */
		public static $helper;
		
		protected static $divimegaproList;
		
		protected static $isMobileDevice = false;
		
		protected static $isTabletDevice = false;
		
		public static function init() {
			
			if ( ! self::$initiated ) {
				
				self::load_resources();
				
				self::$helper = new DiviMegaPro_Helper();
				
				self::init_hooks();
				
				// Register the Custom Divi Mega Pro Post Type
				self::register_cpt();
				
				self::enable_divicpt_option();
			}
		}
		
		/**
		 * Initializes WordPress hooks
		 */
		protected static function init_hooks() {
			
			self::$initiated = true;
			
			// Register widget
			add_action( 'widgets_init', array( 'DiviMegaPro', 'register_widget') );
			
			$pos = false;
			// Check if this was called from License page
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				
				$referer = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
				
				$pos = strpos( $referer, 'page=divimegapro-settings' );
			}
			
			add_action( 'customize_preview_init',  array( 'DiviMegaPro', 'customize_preview_js') );
			
			// This is not required on post edit or license page
			if ( !is_admin() && !self::is_divi_builder_enabled() && $pos === false ) {
				
				// Add styles
				add_action( 'wp_print_styles', array( 'DiviMegaPro', 'add_styles') );
				
				// Add scripts
				add_action( 'wp_enqueue_scripts', array( 'DiviMegaPro', 'add_scripts') );
				
				// Ajax ready
				add_action( 'wp_head', array( 'DiviMegaPro', 'add_var_ajaxurl') );
				add_action( 'wp_ajax_ajax_divimegapros_callback', array( 'DiviMegaPro_Ajax', 'ajax_divimegapros_callback') );
				add_action( 'wp_ajax_nopriv_ajax_divimegapros_callback', array( 'DiviMegaPro_Ajax', 'ajax_divimegapros_callback') );
				
				// Check for possible required dependencies
				add_action( 'wp_head', array( 'DiviMegaPro', 'checkAllDiviMegaPros'), 7 );
				
				// Divi Mega Pro call
				add_action( 'wp_footer', array( 'DiviMegaPro', 'getAllDiviMegaPros'), 7 );
				add_action( 'wp_footer', array( 'DiviMegaPro_Controller', 'showDiviMegaPro' ), 8 );
			}
		}
		
		
		protected static function load_wc_scripts() {
			
			if ( ! class_exists( 'WC_Frontend_Scripts' ) && function_exists( 'et_core_is_fb_enabled' ) && ! et_core_is_fb_enabled() ) {
				return;
			}
			
			// Simply enqueue the scripts; All of them have been registered
			if ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) {
				wp_enqueue_script( 'wc-add-to-cart' );
			}
			
			if ( current_theme_supports( 'wc-product-gallery-zoom' ) ) {
				wp_enqueue_script( 'zoom' );
			}
			if ( current_theme_supports( 'wc-product-gallery-slider' ) ) {
				wp_enqueue_script( 'flexslider' );
			}
			if ( current_theme_supports( 'wc-product-gallery-lightbox' ) ) {
				wp_enqueue_script( 'photoswipe-ui-default' );
				wp_enqueue_style( 'photoswipe-default-skin' );
				
				add_action( 'wp_footer', 'woocommerce_photoswipe' );
			}
			wp_enqueue_script( 'wc-single-product' );
			
			if ( 'geolocation_ajax' === get_option( 'woocommerce_default_customer_address' ) ) {
				$ua = strtolower( wc_get_user_agent() ); // Exclude common bots from geolocation by user agent.
				
				if ( ! strstr( $ua, 'bot' ) && ! strstr( $ua, 'spider' ) && ! strstr( $ua, 'crawl' ) ) {
					wp_enqueue_script( 'wc-geolocation' );
				}
			}
			
			wp_enqueue_script( 'woocommerce' );
			wp_enqueue_script( 'wc-cart-fragments' );
			
			// Enqueue style
			$wc_styles = WC_Frontend_Scripts::get_styles();
			
			foreach ( $wc_styles as $style_handle => $wc_style ) {
				if ( ! isset( $wc_style['has_rtl'] ) ) {
					$wc_style['has_rtl'] = false;
				}
				
				wp_enqueue_style( $style_handle, $wc_style['src'], $wc_style['deps'], $wc_style['version'], $wc_style['media'], $wc_style['has_rtl'] );
			}
		}
		
		
		public static function checkAllDiviMegaPros() {
			
			$render = false;
			$divimegapros_in_current = DiviMegaPro_Controller::showDiviMegaPro( $render );
			
			$classes = get_body_class();
			
			if ( !in_array( 'woocommerce', $classes ) 
				&& !in_array( 'woocommerce-page', $classes ) 
				&& function_exists( 'et_builder_has_woocommerce_module' ) ) {
				
				if ( is_array( $divimegapros_in_current ) && count( $divimegapros_in_current ) > 0 ) {
					
					foreach( $divimegapros_in_current as $divimegapro_id ) {
					
						$divimegapro_id = (int) $divimegapro_id;
						
						$post = get_post( $divimegapro_id );
						
						$has_wc_module = et_builder_has_woocommerce_module( $post->post_content );
						
						if ( $has_wc_module === true ) {
							
							add_filter( 'body_class', function( $classes ) {
								
								$classes[] = 'woocommerce';
								$classes[] = 'woocommerce-page';
								
								return $classes;
							} );
							
							// Load WooCommerce related scripts
							self::load_wc_scripts();
							
							return;
						}
					}
				}
			}
			
			
			// Support Slider Revolution by ThemePunch
			// Reset global vars to prevent any conflicts
			global $rs_material_icons_css, $rs_material_icons_css_parsed;
			
			$rs_material_icons_css = false;
			$rs_material_icons_css_parsed = false;
			
			
			// Support Gravity Forms Styles Pro
			// Restore dequeue Gravity Forms styles
			wp_enqueue_style( 'gforms_css' );
			wp_enqueue_style( 'gforms_reset_css' );
			wp_enqueue_style( 'gforms_formsmain_css' );
			wp_enqueue_style( 'gforms_ready_class_css' );
			wp_enqueue_style( 'gforms_browsers_css' );
		}
		
		
		public static function getAllDiviMegaPros() {
			
			$function_exception = 'showDiviMegaPro';
			
			$divimegapros = array();
			
			try {
				
				// Server-Side Device Detection with Browscap
				require_once( plugin_dir_path( __FILE__ ) . 'includes/php-libraries/Browscap/Browscap.php' );
				$browscap = new Browscap( plugin_dir_path( __FILE__ ) . '/includes/php-libraries/Browscap/Cache/' );
				$browscap->doAutoUpdate = false;
				$current_browser = $browscap->getBrowser();
				
				self::$isMobileDevice = $current_browser->isMobileDevice;
				
				self::$isTabletDevice = $current_browser->isTablet;
				
				// WooCommerce support
				// By using 'et_builder_render_layout' filter, normally WooCommerce unset all notices.
				// Better backup and restore it later
				if ( function_exists( 'wc_get_notices' ) ) {
					
					$bkp_wc_notices = wc_get_notices();
				}
				
				/* Search Divi divimegapro in current post */
				global $post;
				
				$divimegapros_in_post = array();
				
				if ( is_object( $post ) ) {
					
					$divimegapros_in_post = DiviMegaPro_Helper::searchForDMPsInPost( $post, 1 );
				}
				
				
				/* Search Divi divimegapro in active menus */
				$theme_locations = get_nav_menu_locations();
				
				$divimegapros_in_menus = array();
				
				$divimegapros['divimegapros_in_menus'] = array();
				
				if ( is_array( $theme_locations ) && count( $theme_locations ) > 0 ) {
					
					$divimegapros_in_menus = array();
					
					foreach( $theme_locations as $theme_location => $theme_location_value ) {
						
						$menu = get_term( $theme_locations[$theme_location], 'nav_menu' );
						
						// menu exists?
						if( !is_wp_error($menu) ) {
							
							$menu_term_id = $menu->term_id;
							
							// Support WPML for menus
							if ( function_exists( 'icl_object_id' ) ) {
								$menu_term_id = icl_object_id( $menu->term_id, 'nav_menu' );
							}
							
							$menu_items = wp_get_nav_menu_items( $menu_term_id );
							
							foreach ( (array) $menu_items as $it_key => $menu_item ) {
								
								$url = $menu_item->url;
								
								if ( $url ) {
									
									$extract_id = self::$helper->prepareMenu( $url );
									
									if ( $extract_id ) {
										
										$divimegapros_in_menus[ $extract_id ] = 1;
									}
								}
								
								/* Search Divi divimegapro in menu classes */
								if ( isset( $menu_item->classes[0] ) && $menu_item->classes[0] != '' && count( $menu_item->classes ) > 0 ) {
									
									foreach ( $menu_item->classes as $cl_key => $class ) {
										
										if ( $class != '' ) {
											
											$extract_id = self::$helper->prepareMenu( $class );
											
											if ( $extract_id ) {
											
												$divimegapros_in_menus[ $extract_id ] = 1;
											}
										}
									}
								}
								
								/* Search Divi divimegapro in Link Relationship (XFN) */
								if ( !empty( $menu_item->xfn ) ) {
									
									$extract_id = self::$helper->prepareMenu( $menu_item->xfn );
									
									if ( $extract_id ) {
									
										$divimegapros_in_menus[ $extract_id ] = 1;
									}
								}
							}
						}
						else {
							
							
						}
					}
				}
				$divimegapros_in_menus = array_filter( $divimegapros_in_menus );
				$divimegapros['divimegapros_in_menus'] = $divimegapros_in_menus;
				
				
				/* Search CSS Triggers in all Divi divimegapros */
				$divimegapros_with_css_trigger = array();
				
				$divimegapros['css_trigger'] = array();
				
				$posts = DiviMegaPro_Model::getDiviMegaPros('css_trigger');
				
				if ( isset( $posts[0] ) ) {
					
					foreach( $posts as $dmm_post ) {
						
						$post_id = $dmm_post->ID;
						
						$get_css_selector = get_post_meta( $post_id, 'dmp_css_selector' );
						
						$css_selector = $get_css_selector[0];
						
						if ( $css_selector != '' ) {
							
							$divimegapros_with_css_trigger[ $post_id ] = $css_selector;
						}
					}
					
					$divimegapros['css_trigger'] = $divimegapros_with_css_trigger;
				}
				
				
				/* Search in all Divi Active Widgets */
				$divimegapros_in_widgets = array();
				
				if ( function_exists( 'et_builder_get_widget_areas' ) ) {
					
					$et_widgets = et_builder_get_widget_areas_list();
					
					ob_start();
					foreach ( $et_widgets as $et_widget => $et_widget_name ) {
						
						if ( is_active_sidebar( $et_widget ) ) {
							
							dynamic_sidebar( $et_widget );
						}
						
					}
					$output = ob_get_clean();
					
					$divimegapros_in_widgets = DiviMegaPro_Helper::searchDMPs( $output );
				}
				
				
				/* Search in all Divi Layouts */
				$divimegapros_in_layouts = array();
				
				if ( function_exists( 'et_theme_builder_frontend_render_layout' ) ) {
					
					$layouts = et_theme_builder_get_template_layouts();
					
					$content = '';
					
					if ( is_array( $layouts ) && array_filter( $layouts ) ) {
						
						foreach( $layouts as $layout_type => $layout_ ) {
							
							if ( isset( $layout_['id'] ) && $layout_['enabled'] === true && $layout_['id'] !== 0 ) {
								
								$layout_id = $layout_['id'];
						
								$layout = get_post( $layout_id );
								
								if ( null !== $layout || $layout->post_type === $layout_type ) {
									
									$layout = DiviMegaPro_Helper::avoidRenderTags( $layout->post_content );
									
									$content .= et_builder_render_layout( $layout );
								}
							}
						}
						
						$divimegapros_in_layouts = DiviMegaPro_Helper::searchDMPs( $content );
					}
				}
				
				/* Search for all Divi Mega pros with render forced */
				$divimegapros_force_render = array();
				
				$posts = DiviMegaPro_Model::getDiviMegaPros('force_render');
				
				if ( isset( $posts[0] ) ) {
					
					foreach( $posts as $dmm_post ) {
						
						$post_id = $dmm_post->ID;
						
						$divimegapros_force_render[ $post_id ] = 1;
					}
				}
				
				
				// WooCommerce support
				// Restore all WC notices in case there were any
				if ( function_exists( 'wc_set_notices' ) ) {
					
					$all_wc_notices = wc_get_notices();
					
					if ( is_array( $all_wc_notices ) && empty( $all_wc_notices )
						 && is_array( $bkp_wc_notices ) && count( $bkp_wc_notices ) > 0 ) {
						
						wc_set_notices( $bkp_wc_notices );
					}
				}
				
				$divimegapros['ids'] = $divimegapros_in_post + $divimegapros_in_menus + $divimegapros_with_css_trigger + $divimegapros_in_widgets + $divimegapros_in_layouts + $divimegapros_force_render;
				
				if ( is_array( $divimegapros['ids'] ) && count( $divimegapros['ids'] ) > 0 ) {
					
					add_filter( 'body_class', function ( $classes )
					{
						$classes[] = 'divimegapro-active';
						return $classes;
						
					}, 20, 2 );
				}
			
			} catch (Exception $e) {
			
				self::log( $e );
			}
			
			self::$divimegaproList = $divimegapros;
		}
		
		
		protected static function load_resources() {
			
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.controller.php' );
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.model.php' );
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.helper.php' );
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.ajax.php' );
		}
		
		
		public static function register_cpt() {
			
			$labels = array(
				'name' => _x( 'Divi Mega Pro', 'divi_mega_pro' ),
				'singular_name' => _x( 'Divi Mega Pro', 'divi_mega_pro' ),
				'add_new' => _x( 'Add New', 'divi_mega_pro' ),
				'add_new_item' => _x( 'Add New Divi Mega Pro', 'divi_mega_pro' ),
				'edit_item' => _x( 'Edit Divi Mega Pro', 'divi_mega_pro' ),
				'new_item' => _x( 'New Divi Mega Pro', 'divi_mega_pro' ),
				'view_item' => _x( 'View Divi Mega Pro', 'divi_mega_pro' ),
				'search_items' => _x( 'Search Divi Mega Pro', 'divi_mega_pro' ),
				'not_found' => _x( 'No Divi Mega Pro found', 'divi_mega_pro' ),
				'not_found_in_trash' => _x( 'No Divi Mega Pro found in Trash', 'divi_mega_pro' ),
				'parent_item_colon' => _x( 'Parent Divi Mega Pro:', 'divi_mega_pro' ),
				'menu_name' => _x( 'Divi Mega Pro', 'divi_mega_pro' ),
			);
			
			$args = array(
				'labels' => $labels,
				'hierarchical' => true,
				'supports' => array( 'title', 'editor', 'author' ),
				'public' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'menu_position' => 5,
				'show_in_nav_menus' => true,
				'exclude_from_search' => true,
				'has_archive' => true,
				'query_var' => true,
				'can_export' => true,
				'rewrite' => true,
				'capability_type' => 'post'
			);
			
			register_post_type( 'divi_mega_pro', $args );
		}
		
		
		public static function enable_divicpt_option() {
			
			$divi_post_types = et_get_option( 'et_pb_post_type_integration', array() );
			
			if ( !isset( $divi_post_types['divi_mega_pro'] )
				|| ( isset( $divi_post_types['divi_mega_pro'] ) && $divi_post_types['divi_mega_pro'] == 'off' ) ) {
				
				$divi_post_types['divi_mega_pro'] = 'on';
				
				et_update_option( 'et_pb_post_type_integration', $divi_post_types, false, '', '' );
			}
		}
		
		
		public static function register_widget() {
			
			register_sidebar( array(
				'name' => __( 'Divi Mega Pro - Global', 'theme-slug' ),
				'id' => 'divi-mega-pro_global_widget',
				'description' => __( '', 'theme-slug' )
			) );
		}
		
		
		// Register all required stylesheets
		public static function add_styles() {
			
			wp_register_style('DiviMegaPro-main', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/main.css' );
			wp_enqueue_style('DiviMegaPro-main');
			
			wp_register_style('DiviMegaPro-main-media-query', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/main-media-query.css' );
			wp_enqueue_style('DiviMegaPro-main-media-query');
			
			wp_register_style('DiviMegaPro-tippy-animations', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/libraries/tippy/css/animations.css' );
			wp_enqueue_style('DiviMegaPro-tippy-animations');
		}
		
		
		// Register all required scripts
		public static function add_scripts() {
			
			if ( !self::is_divi_builder_enabled() ) {
				
				wp_register_script('DiviMegaPro-popper', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/js/popper-1.16.1.min.js', array('jquery'));
				wp_enqueue_script('DiviMegaPro-popper');
				
				wp_register_script('DiviMegaPro-tippy', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/js/tippy-5.2.1.min.js', array('jquery'));
				wp_enqueue_script('DiviMegaPro-tippy');
				
				wp_register_script('DiviMegaPro-main', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/js/main.js', array('jquery', 'DiviMegaPro-popper', 'DiviMegaPro-tippy'), DIVI_MEGA_PRO_VERSION, TRUE);
				wp_enqueue_script('DiviMegaPro-main');
				
				wp_register_script('DiviMegaPro-main-helper', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/js/main.helper.js', array('jquery', 'DiviMegaPro-main'), DIVI_MEGA_PRO_VERSION, TRUE );
				wp_enqueue_script('DiviMegaPro-main-helper');
			}
		}
		
		
		public static function customize_preview_js() {
			
			$theme_version = DIVI_MEGA_PRO_VERSION;
			
			wp_enqueue_script( 'DiviMegaPro-customizer', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/js/customizer.js', array( 'customize-preview', 'DiviMegaPro-popper', 'DiviMegaPro-tippy', 'DiviMegaPro-main', 'DiviMegaPro-main-helper' ), $theme_version, true );
		}
		
		
		public static function add_var_ajaxurl() {
		?>
		<script type="text/javascript">
		var ajax_url = '<?php print esc_url( admin_url('admin-ajax.php') ); ?>';
		</script>
		<?php
		}
		
		
		public static function is_divi_builder_enabled() {
			
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['et_fb'] ) ) {
				
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$divi_builder_enabled = sanitize_text_field( wp_unslash( $_GET['et_fb'] ) );
				
				// is divi theme builder ?
				if ( $divi_builder_enabled === '1' ) {
					
					return TRUE;
				}
			}
			
			return FALSE;
		}
		
		
		/**
		 * Log debugging infoormation to the error log.
		 *
		 * @param string $e The Exception object
		 */
		public static function log( $e = FALSE ) {
			
			$data_log = $e;
			
			if ( is_object( $e ) ) {
				
				$data_log = sprintf( "Exception: \n %s \n", $e->getMessage() . "\r\n\r\n" . $e->getFile() . "\r\n" . 'Line:' . $e->getLine() );
			}
			
			if ( apply_filters( 'divimegapros_log', defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {
				
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				$log = print_r( compact( 'data_log' ), true );
				
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $log );
			}
		}
	}
	