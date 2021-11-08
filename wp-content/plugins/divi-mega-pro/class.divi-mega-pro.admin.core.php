<?php

	class DiviMegaPro_Admin {
		
		private static $_show_errors = FALSE;
		private static $initiated = FALSE;
		private static $helper_admin = NULL;
		
		public static $helper = NULL;
		
		/**
		 * Holds the values to be used in the fields callbacks
		 */
		public static $options;
		
		/**
		 * @var \WP_Filesystem_Base|null
		 */
		public static $wpfs;
		
		/**
		 * @var ET_Core_Data_Utils
		 */
		public static $data_utils;
		
		/**
		 * Divi Mega Pro post type.
		 *
		 * @var string
		 */
		protected static $post_type = 'divi_mega_pro';
		
		public static function init() {
			
			if ( ! self::$initiated ) {
				
				global $wp_filesystem;
				self::$wpfs = $wp_filesystem;
				
				self::$data_utils = new ET_Core_Data_Utils();
				
				self::load_resources();
				
				self::init_hooks();
			}
		}
		
		
		private static function init_hooks() {
			
			self::$initiated = true;
			
			self::$helper = new DiviMegaPro_Helper();
			
			self::$helper_admin = new DiviMegaPro_Admin_Helper();
			
			// Admin styles/scripts
			add_action( 'admin_init', array( 'DiviMegaPro_Admin', 'register_assets' ) );
			add_action( 'admin_enqueue_scripts', array( 'DiviMegaPro_Admin', 'include_assets'), '999');
			
			// Add custom column in post type
			add_filter( 'manage_edit-divi_mega_pro_columns', array( 'DiviMegaPro_Admin', 'setup_divimegapros_columns') ) ;
			add_action( 'manage_divi_mega_pro_posts_custom_column', array( 'DiviMegaPro_Admin', 'manage_divimegapros_columns' ), 10, 2 );
			
			// Add meta boxes
			add_action( 'add_meta_boxes', array( 'DiviMegaPro_Admin_Controller', 'add_meta_boxes') );
			
			// Hide meta boxes
			add_filter( 'is_protected_meta', array( 'DiviMegaPro_Admin_Controller', 'dmp_removefields_from_customfieldsmetabox' ), 10, 2);
			
			// Add Divi Theme Builder
			add_filter( 'et_builder_post_type_blacklist', array( 'DiviMegaPro_Admin', 'filter_post_type_blacklist') );
			add_filter( 'et_builder_third_party_post_types', array( 'DiviMegaPro_Admin', 'filter_third_party_post_types') );
			add_filter( 'et_builder_post_types', array( 'DiviMegaPro_Admin', 'filter_builder_post_types') );
			add_filter( 'et_fb_post_types', array( 'DiviMegaPro_Admin', 'filter_builder_post_types') );
			add_filter( 'et_builder_fb_enabled_for_post', array( 'DiviMegaPro_Admin', 'filter_fb_enabled_for_post'), 10, 2 );
			
			// Save post fields
			add_action( 'save_post_' . self::$post_type, array( 'DiviMegaPro_Admin_Controller', 'save_post' ), 10, 2 );
			
			add_action( 'admin_menu', array( 'DiviMegaPro_Admin_Controller', 'add_admin_submenu' ), 5 );
			
			add_action( 'wp_ajax_nopriv_ajax_dmp_listposts', array( 'DiviMegaPro_Admin_Ajax', 'call_get_posts' ) );
			add_action( 'wp_ajax_ajax_dmp_listposts', array( 'DiviMegaPro_Admin_Ajax', 'call_get_posts' ) );
			
			// Register settings
			add_action( 'admin_init', array( 'DiviMegaPro_Admin', 'register_divimegapro_settings' ) );
			
			add_action( 'switch_theme', array( 'DiviMegaPro_Admin', 'clear_cache') );
			add_action( 'activated_plugin', array( 'DiviMegaPro_Admin', 'clear_cache'), 10, 0 );
			add_action( 'deactivated_plugin', array( 'DiviMegaPro_Admin', 'clear_cache'), 10, 0 );
			add_action( 'et_core_page_resource_auto_clear', array( 'DiviMegaPro_Admin', 'clear_cache') );
			add_action( 'wp_ajax_et_core_page_resource_clear', array( 'DiviMegaPro_Admin', 'clear_cache') );
		}
		
		
		public static function clear_cache( $post_id = '*', $owner = 'custom-divimegapro' ) {
			
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			
			if ( ! wp_doing_cron() && ! current_user_can( 'edit_posts' ) ) {
				return;
			}
			
			if ( ! ET_Core_PageResource::can_write_to_filesystem() ) {
				return;
			}
			
			$_post_id = 'all' === $post_id ? '*' : $post_id;
			$_owner   = 'all' === $owner ? 'core-unified' : $owner;

			$cache_dir = self::$data_utils->normalize_path( ET_Core_PageResource::get_cache_directory() );
			
			$files = array_merge(
				(array) glob( "{$cache_dir}/et-{$_owner}-[0-9]*-*" ),
				(array) glob( "{$cache_dir}/{$_post_id}/et-{$_owner}-[0-9]*-*" ),
				(array) glob( "{$cache_dir}/*/et-{$_owner}-*-tb-{$_post_id}-*" ),
				(array) glob( "{$cache_dir}/*/et-{$_owner}-*-tb-for-{$_post_id}-*" )
			);
			
			foreach( (array) $files as $file ) {
				
				$file = self::$data_utils->normalize_path( $file );
				
				if ( ! self::$data_utils->starts_with( $file, $cache_dir ) ) {
					// File is not located inside cache directory so skip it.
					continue;
				}
				
				if ( is_file( $file ) ) {
					self::$wpfs->delete( $file );
				}
			}
			
			// Clear cache managed by 3rd-party cache plugins
			$post_id = ! empty( $post_id ) && absint( $post_id ) > 0 ? $post_id : '';
			et_core_clear_wp_cache( $post_id );
		}
		
		
		protected static function load_resources() {
			
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.admin.controller.php' );
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.admin.helper.php' );
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.admin.model.php' );
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.admin.ajax.php' );
			require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/includes/class.divi-mega-pro.helper.php' );
		}
		
		
		public static function register_assets( $hook ) {
			
			wp_register_style( 'divi-mega-pro-wp-color-picker', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/admin/cs-wp-color-picker.min.css', array( 'wp-color-picker' ), '1.0.0', 'all' );
			wp_register_script( 'divi-mega-pro-wp-color-picker', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/js/admin/cs-wp-color-picker.min.js', array( 'wp-color-picker' ), '1.0.0', true );
			
			wp_register_style( 'divi-mega-pro-select2', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/admin/select2.min.css', array(), '4.0.6', 'all' );
			wp_register_script( 'divi-mega-pro-select2', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/js/admin/select2.full.min.js', array('jquery'), '4.0.6', true );
			wp_register_style( 'divi-mega-pro-admin-bootstrap', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/admin/bootstrap.css', array(), '1.0.0', 'all' );
			wp_register_style( 'divi-mega-pro-admin-bootstrap-select', '//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/css/bootstrap-select.min.css', array(), '1.0.0', 'all' );
			wp_register_style( 'divi-mega-pro-select2-bootstrap', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/admin/select2-bootstrap.min.css', array('divi-mega-pro-admin-bootstrap'), '1.0.0', 'all' );
			
			// Force jQuery UI because Divi won't include it when Builder is not enabled/active
			wp_register_style( 'jquery_ui_css', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/admin/jquery-ui-1.12.1.custom.css', array(), '1.12.1', 'all' );
			
			wp_register_style( 'divi-mega-pro-divipanel', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/admin/panel.min.css', array(), '1.0.0', 'all' );
			
			wp_register_style( 'divi-mega-pro-admin', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/css/admin/admin.css', array(), '1.0.0', 'all' );
			wp_register_script( 'divi-mega-pro-admin-functions', DIVI_MEGA_PRO_PLUGIN_URL . 'assets/js/admin/admin-functions.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-slider', 'divi-mega-pro-select2' ), '1.0.0', true );
		}
		
		
		public static function include_assets( $hook ) {
			
			$screen = get_current_screen();
			
			if ( $screen->post_type != self::$post_type ) {
				return;
			}
			
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'divi-mega-pro-wp-color-picker');
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'divi-mega-pro-wp-color-picker');
			
			wp_enqueue_style( 'divi-mega-pro-select2' );
			wp_enqueue_style( 'divi-mega-pro-select2-bootstrap' );
			wp_enqueue_script( 'divi-mega-pro-select2' );
			
			wp_enqueue_style( 'divi-mega-pro-admin-bootstrap' );
			wp_enqueue_style( 'divi-mega-pro-admin-bootstrap-select' );
			
			// Force jQuery UI because Divi won't include it when Builder is not enabled/active
			wp_enqueue_style( 'jquery_ui_css' );
			
			wp_enqueue_style( 'divi-mega-pro-divipanel' );
			wp_enqueue_style( 'divi-mega-pro-admin' );
			wp_enqueue_script( 'divi-mega-pro-admin-functions' );
		}
		
		
		public static function setup_divimegapros_columns( $columns ) {

			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => __( 'Title' ),
				'unique_identifier' => __( 'Unique Mega Pro Class' ),
				'author' => __( 'Author' ),
				'date' => __( 'Date' )
			);

			return $columns;
		}
		
		
		public static function manage_divimegapros_columns( $column, $post_id ) {
			
			global $post;
			
			switch( $column ) {
				
				case 'unique_identifier':
				
					$unique_class = 'divimegapro-' . $post->ID;
					
					print et_core_intentionally_unescaped( $unique_class, 'fixed_string' );
					
					break;
					
				default:
				
					break;
			}
		}
		
		
		public static function register_divimegapro_settings( $args ) {
			
			register_setting( 
				'divimegapro_settings', 
				'dmp_settings', 
				array( 'DiviBars_Admin', 'sanitize' ) 
			);
		}
		
		
		public static function print_description_settings() {
			
			print '';
		}
		
		/**
		 * Sanitize each setting field as needed
		 *
		 * @param array $input Contains all settings fields as array keys
		 */
		public static function sanitize( $input ) {
			
			$new_input = array();
			
			if ( isset( $input['dmp_custom_elems'] ) ) {
				
				$new_input['dmp_custom_elems'] = sanitize_text_field( $input['dmp_custom_elems'] );
			}
			
			if ( isset( $input['dmp_timezone'] ) ) {
				
				$new_input['dmp_timezone'] = sanitize_text_field( $input['dmp_timezone'] );
			}
			
			return $new_input;
		}
		
		public static function parse_fields_callback( $options ) {
			
			$field_type = isset( $options['type'] ) ? esc_attr( $options['type'] ) : '';
			
			$field_name = $optionname = isset( $options['name'] ) ? esc_attr( $options['name'] ) : '';
			
			$field_default_value = isset( $options['default_value'] ) ? esc_attr( $options['default_value'] ) : '';
			
			if ( 'text' == $field_type ) {
				
				printf(
					'<input type="text" id="' . $field_name . '" name="dmp_settings[' . $field_name . ']" value="%s" />',
					isset( self::$options[ $field_name ] ) ? esc_attr( self::$options[ $field_name ] ) : et_core_esc_previously( $field_default_value )
				);
			}
			else if ( 'select' == $field_type ) {
				
				$valid_options = array();
				
				$selected = isset( self::$options[ $field_name ] ) ? esc_attr( self::$options[ $field_name ] ) : $field_default_value;
				
				if ( $selected != $field_default_value ) {
					
					$field_default_value = $selected;
				}
				
				?>
				<select name="dmp_settings[<?php print et_core_esc_previously( $field_name ); ?>]" data-defaultvalue="<?php print et_core_esc_previously( $field_default_value ) ?>" class="select-<?php print et_core_esc_previously( $options['name'] ) ?>">
				<?php
				
				if ( isset( $options['options'] ) ) {
				
					foreach ( $options['options'] as $option ) {
						
						?>
						<option <?php selected( $selected, $option['value'] ); ?> value="<?php print et_core_esc_previously( $option['value'] ); ?>"><?php print et_core_esc_previously( $option['title'] ); ?></option>
						<?php
					}
				}
				
				?>
				</select>
				<?php
			}
		}
			
			
		/**
		 * Filter the post type blacklist if the post type is not supported.
		 *
		 * @since 3.10
		 *
		 * @param string[] $post_types
		 *
		 * @return string[]
		 */
		public static function filter_post_type_blacklist( $post_types ) {
			
			$post_types[] = self::$post_type;

			return $post_types;
		}

		/**
		 * Filter the supported post type whitelist if the post type is supported.
		 *
		 * @since 3.10
		 *
		 * @param string[] $post_types
		 *
		 * @return string[]
		 */
		public static function filter_third_party_post_types( $post_types ) {
			
			$post_types[] = self::$post_type;

			return $post_types;
		}

		/**
		 * Filter the enabled post type list if the post type has been enabled but the content
		 * filter has been changed back to the unsupported one.
		 *
		 * @since 3.10
		 *
		 * @param string[] $post_types
		 *
		 * @return string[]
		 */
		public static function filter_builder_post_types( $post_types ) {
			
			$post_types[] = self::$post_type;
			
			return $post_types;
		}

		/**
		 * Disable the FB for a given post if the builder was enabled but the
		 * content filter was switched after that.
		 *
		 * @since 3.10
		 *
		 * @param boolean $enabled
		 * @param integer $post_id
		 *
		 * @return boolean
		 */
		public static function filter_fb_enabled_for_post( $enabled, $post_id ) {
			
			$enabled = true;

			return $enabled;
		}
		
	} // end DiviMegaPro_Controller