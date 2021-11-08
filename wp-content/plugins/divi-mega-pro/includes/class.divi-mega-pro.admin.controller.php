<?php

	class DiviMegaPro_Admin_Controller {
		
		protected static $_show_errors = FALSE;
		
		/**
		 * Holds the values to be used in the fields callbacks
		 */
		public static $options;
		
		public static function get_posts( $args = null ) {
			
			$post_types = get_post_types( array( 'public' => true ) );
			
			$excluded_post_types = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'et_pb_layout', 'divi_bars', 'divi_overlay', 'divi_mega_pro', 'customize_changeset' );
			
			$post_types = array_diff( $post_types, $excluded_post_types );
			
			if ( isset( $args['q'] ) ) {
				
				$q = $args['q'];
			}
			else {
				
				$q = '';
			}
			
			if ( isset( $args['page'] ) ) {
				
				$page = $args['page'];
			}
			else {
				
				$page = 1;
			}
			
			$args = array(
				'post_title_like' => $q,
				'post_type' => $post_types,
				'cache_results'  => false,
				'posts_per_page' => 7,
				'paged' => $page,
				'orderby' => 'id',
				'order' => 'DESC'
			);
			
			$posts = array();
			
			$total_count = 0;
			
			$query = new WP_Query( $args );
			
			$get_posts = $query->get_posts();
			
			$posts = array_merge( $posts, $get_posts );
			
			$total_count = (int) $query->found_posts;
			
			$posts = self::keysToLower( $posts );
			
			return array( 'total_count' => $total_count, 'items' => $posts );
		}
		
		
		private static function keysToLower( &$obj )
		{
			$type = (int) is_object($obj) - (int) is_array($obj);
			
			if ($type === 0) return $obj;
			
			foreach ($obj as $key => &$val) {
				
				$element = self::keysToLower($val);
				
				switch ($type) {
					
					case 1:
					
						if (!is_int($key) && $key !== ($keyLowercase = strtolower($key)))
						{
							unset($obj->{$key});
							$key = $keyLowercase;
						}
						$obj->{$key} = $element;
						
						break;
						
					case -1:
					
						if (!is_int($key) && $key !== ($keyLowercase = strtolower($key)))
						{
							unset($obj[$key]);
							$key = $keyLowercase;
						}
						$obj[$key] = $element;
						
						break;
				}
			}
			return $obj;
		}
		
		
		public static function add_meta_boxes() {
			
			
			if ( isset( $_GET['page'] ) && $_GET['page'] === 'divimegapro-settings' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended 
				
				return;
			}
			
			$screen = get_current_screen();
			
			if ( $screen->post_type == 'divi_mega_pro' ) {
					
				$status = get_option( 'divilife_edd_divimegapro_license_status' );
				$check_license = divilife_edd_divimegapro_check_license( TRUE );
				
				if ( ( isset( $check_license->license ) && $check_license->license != 'valid' && 'add' == $screen->action )
					|| ( isset( $check_license->license ) && isset( $_GET['action'] ) && $check_license->license != 'valid' && 'edit' === $_GET['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					|| ( $status === false && 'add' == $screen->action ) 
					|| ( $status === false && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					) {
					
					$message = '';
					$base_url = admin_url( 'edit.php?post_type=divi_mega_pro&page=divimegapro-settings' );
					$redirect = add_query_arg( array( 'message' => rawurlencode( $message ), 'divilife' => 'divimegapro' ), $base_url );
					
					wp_safe_redirect( $redirect );
					exit();
				}
				
				add_meta_box( 
					'divimegapros_displaylocations_metabox1', 
					esc_html__( 'Display Locations', 'DiviMegaPro' ), 
					array( 'DiviMegaPro_Admin_Controller', 'divimegapros_displaylocations_callback' ),
					'divi_mega_pro', 
					'side'
				);		
				
				add_meta_box( 
					'divimegapros_animation_metabox2', 
					esc_html__( 'Mega Pro Animation', 'DiviMegaPro' ), 
					array( 'DiviMegaPro_Admin_Controller', 'divimegapros_animation_callback' ), 
					'divi_mega_pro', 
					'side'
				);
				
				add_meta_box( 
					'divimegapros_manualtriggers3', 
					esc_html__( 'Mega Pro Triggers', 'DiviMegaPro' ), 
					array( 'DiviMegaPro_Admin_Controller', 'divimegapros_triggers_callback' ), 
					'divi_mega_pro', 
					'side'
				);
				
				add_meta_box( 
					'divimegapros_displaysettings_metabox4', 
					esc_html__( 'Mega Pro Display Settings', 'DiviMegaPro' ), 
					array( 'DiviMegaPro_Admin_Controller', 'divimegapros_displaysettings_callback' ), 
					'divi_mega_pro', 
					'side'
				);
				
				add_meta_box( 
					'divimegapros_closecustoms_meta_box5', 
					esc_html__( 'Close Button Customizations', 'DiviMegaPro' ), 
					array( 'DiviMegaPro_Admin_Controller', 'divimegapros_closecustoms_callback' ), 
					'divi_mega_pro', 
					'side' 
				);
				
				add_meta_box( 
					'divimegapros_moresettings_metabox6', 
					esc_html__( 'Mega Pro Additional Settings', 'DiviMegaPro' ), 
					array( 'DiviMegaPro_Admin_Controller', 'divimegapros_moresettings_callback' ),
					'divi_mega_pro', 
					'side' 
				);
				
				add_meta_box( 
					'divimegapros_color_picker', 
					esc_html__( 'Mega Pro Background', 'DiviMegaPro' ), 
					array( 'DiviMegaPro_Admin_Controller', 'divimegapros_colorbox_callback' ), 
					'divi_mega_pro'
				);
			}
		}
		
		
		public static function dmp_removefields_from_customfieldsmetabox( $protected, $meta_key ) {
			
			if ( function_exists( 'get_current_screen' ) ) {
				
				$screen = get_current_screen();
				
				$remove = $protected;
				
				if ( $screen !== null ) {
				
					if ( $meta_key == 'dmp_animation'
						|| $meta_key == 'dmp_arrowfeature_color'
						|| $meta_key == 'dmp_arrowfeature_height'
						|| $meta_key == 'dmp_arrowfeature_type'
						|| $meta_key == 'dmp_arrowfeature_width' 
						|| $meta_key == 'dmp_bg_color'
						|| $meta_key == 'dmp_closebtn_bg_color'
						|| $meta_key == 'dmp_closebtn_borderradius' 
						|| $meta_key == 'dmp_closebtn_fontsize' 
						|| $meta_key == 'dmp_closebtn_padding' 
						|| $meta_key == 'dmp_closebtn_text_color' 
						|| $meta_key == 'dmp_css_selector' 
						|| $meta_key == 'dmp_css_selector_at_pages' 
						|| $meta_key == 'dmp_css_selector_at_pages_selected' 
						|| $meta_key == 'dmp_css_selector_at_pagesexception_selected' 
						|| $meta_key == 'dmp_force_render'
						|| $meta_key == 'dmp_customizeclosebtn'
						|| $meta_key == 'dmp_enable_arrow'
						|| $meta_key == 'dmp_enabledesktop'
						|| $meta_key == 'dmp_enablemobile'
						|| $meta_key == 'dmp_exitdelay'
						|| $meta_key == 'dmp_exittype'
						|| $meta_key == 'dmp_font_color'
						|| $meta_key == 'dmp_margintopbottom'
						|| $meta_key == 'dmp_megaprowidth'
						|| $meta_key == 'dmp_megaprowidth_custom'
						|| $meta_key == 'dmp_megaprofixedheight'
						|| $meta_key == 'dmp_placement'
						|| $meta_key == 'dmp_mpa_disablemobile'
						|| $meta_key == 'dmp_mpa_disabletablet'
						|| $meta_key == 'dmp_mpa_disabledesktop'
						|| $meta_key == 'dmp_triggertype'
						) {
							
						$remove = true;
					}
				}
				
				return $remove;
			}
		}
		
		
		public static function divimegapros_animation_callback( $post ) {
			
			wp_nonce_field( 'divimegapros_animation', 'divimegapros_animation_nonce' );
			
			$dmp_animation = get_post_meta( $post->ID, 'dmp_animation', true );
			$dmp_animation_list = array(
				'shift-away'   => esc_html__( 'Shift Away', 'DiviMegaPro' ),
				'shift-toward'    => esc_html__( 'Shift Toward', 'DiviMegaPro' ),
				'perspective' => esc_html__( 'Perspective', 'DiviMegaPro' ),
				'fade' => esc_html__( 'Fade', 'DiviMegaPro' ),
				'scale' => esc_html__( 'Scale', 'DiviMegaPro' )
			);
			?>
			<div class="divilife_meta_box">
				<p>
					<label for="dmp_animation" class="dmp-label-animation"><?php esc_html_e( 'Choose Animation', 'DiviMegaPro' ); ?>: </label>
					<select id="dmp_animation" name="dmp_animation" class="chosen">
					<?php
					foreach ( $dmp_animation_list as $animation_value => $animation_name ) {
						printf( '<option value="%2$s"%3$s>%1$s</option>',
							esc_html( $animation_name ),
							esc_attr( $animation_value ),
							selected( $animation_value, $dmp_animation, false )
						);
					} ?>
					</select>
				</p>
			</div> 
			<?php
		}
		
		
		public static function divimegapros_displaylocations_callback( $post ) {
		
			wp_nonce_field( 'divimegapros_displaylocations', 'divimegapros_displaylocations_nonce' );
			
			$at_pages = get_post_meta( $post->ID, 'dmp_css_selector_at_pages', true );
			$selectedpages = get_post_meta( $post->ID, 'dmp_css_selector_at_pages_selected' );
			$selectedexceptpages = get_post_meta( $post->ID, 'dmp_css_selector_at_pagesexception_selected' );
			$force_render = get_post_meta( $post->ID, 'dmp_force_render' );
			
			if ( !isset( $force_render[0] ) ) {
				
				$force_render[0] = '0';
			}
			
			if ( $at_pages == '' ) {
				
				$at_pages = 'all';
			}
			
			?>
			<script type="text/javascript">
			var divilife_divimegapro = "<?php print wp_create_nonce( 'divilife_divimegapro' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>";
			</script>
			<div class="divilife_meta_box">
				<div class="at_pages">
					<select name="dmp_css_selector_at_pages" class="at_pages chosen dmp-filter-by-pages" data-dropdownshowhideblock="1">
						<option value="all"<?php if ( $at_pages == 'all' ) { ?> selected="selected"<?php } ?> data-showhideblock=".do-list-exceptionpages-container"><?php esc_html_e( 'All pages', 'DiviMegaPro' ); ?></option>
						<option value="specific"<?php if ( $at_pages == 'specific' ) { ?> selected="selected"<?php } ?> data-showhideblock=".do-list-pages-container"><?php esc_html_e( 'Only specific pages', 'DiviMegaPro' ); ?></option>
					</select>
					<div class="do-list-pages-container<?php if ( $at_pages == 'specific' ) { ?> do-show<?php } ?>">
						<select name="dmp_css_selector_at_pages_selected[]" class="do-list-pages" data-placeholder="<?php esc_html_e( 'Choose posts or pages', 'DiviMegaPro' ); ?>..." multiple tabindex="3">
						<?php
							if ( isset( $selectedpages[0] ) && is_array( $selectedpages[0] ) ) {
								
								foreach( $selectedpages[0] as $selectedidx => $selectedvalue ) {
									
									$post_title = get_the_title( $selectedvalue );
									
									print '<option value="' . esc_attr( $selectedvalue ) . '" selected="selected">' . esc_attr( $post_title ) . '</option>';
								}
							}
						?>
						</select>
					</div>
					<div class="do-list-exceptionpages-container<?php if ( $at_pages == 'all' ) { ?> do-show<?php } ?>">
						<h4 class="db-exceptedpages"><?php esc_html_e( 'Add Exceptions', 'DiviMegaPro' ); ?>:</h4>
						<select name="dmp_css_selector_at_pagesexception_selected[]" class="do-list-pages" data-placeholder="<?php esc_html_e( 'Choose posts or pages', 'DiviMegaPro' ); ?>..." multiple tabindex="3">
						<?php
							if ( isset( $selectedexceptpages[0] ) && is_array( $selectedexceptpages[0] ) ) {
								
								foreach( $selectedexceptpages[0] as $selectedidx => $selectedvalue ) {
									
									$post_title = get_the_title( $selectedvalue );
									
									print '<option value="' . esc_attr( $selectedvalue ) . '" selected="selected">' . esc_attr( $post_title ) . '</option>';
								}
							}
						?>
						</select>
					</div>
					<p>
						<input name="dmp_force_render" type="checkbox" id="dmp_force_render" value="1" <?php checked( $force_render[0], 1 ); ?>> <?php esc_html_e( 'Force render', 'DiviMegaPro' ); ?>
					</p>
				</div>
				<div class="clear"></div> 
			</div>
			<?php
		}
		
		
		public static function divimegapros_triggers_callback( $post ) {
			
			wp_nonce_field( 'divimegapros_triggers', 'divimegapros_triggers_nonce' );
			
			$dmp_css_selector = get_post_meta( $post->ID, 'dmp_css_selector', true );
			
			$screen = get_current_screen();
			
			if ( 'add' != $screen->action ) {
			?>
			<div class="divilife_meta_box">
				<p>
					<label class="label-color-field"><p>Mega Pro <?php esc_html_e( 'Unique Class', 'DiviMegaPro' ); ?>:</label> divimegapro-<?php print et_core_esc_previously( $post->ID ) ?></p>
				</p>
			</div> 
			<?php
			}
			?>
			<div class="divilife_meta_box">
				<p>
					<label>CSS <?php esc_html_e( 'Selector Trigger', 'DiviMegaPro' ); ?>:</label>
					<input class="dmp_css_selector" type="text" name="dmp_css_selector" value="<?php print esc_html( $dmp_css_selector ); ?>"/>
				</p>
				<div class="clear"></div> 
			</div>
			<?php
		}
		
		
		public static function divimegapros_displaysettings_callback( $post ) {
		
			wp_nonce_field( 'divimegapros_displaysettings', 'divimegapros_displaysettings_nonce' );
			
			$dmp_margintopbottom = get_post_meta( $post->ID, 'dmp_margintopbottom', true );
			$dmp_placement = get_post_meta( $post->ID, 'dmp_placement', true );
			$dmp_megaprowidth = get_post_meta( $post->ID, 'dmp_megaprowidth', true );
			$dmp_megaprowidth_custom = get_post_meta( $post->ID, 'dmp_megaprowidth_custom', true );
			$dmp_megaprofixedheight = get_post_meta( $post->ID, 'dmp_megaprofixedheight', true );
			$enable_arrow = get_post_meta( $post->ID, 'dmp_enable_arrow' );
			$arrowtype = get_post_meta( $post->ID, 'dmp_arrowfeature_type', true );
			$arrowcolor = get_post_meta( $post->ID, 'dmp_arrowfeature_color', true );
			$arrowwidth = get_post_meta( $post->ID, 'dmp_arrowfeature_width', true );
			$arrowheight = get_post_meta( $post->ID, 'dmp_arrowfeature_height', true );
			
			if ( $dmp_placement == '' ) {
				
				$dmp_placement = 'bottom';
			}
			
			$dmp_ats = array(
				'top' => esc_html__( 'Up', 'DiviMegaPro' ),
				'bottom' => esc_html__( 'Down', 'DiviMegaPro' ),
				'right' => esc_html__( 'Right', 'DiviMegaPro' ),
				'left' => esc_html__( 'Left', 'DiviMegaPro' )
			);
			?>
			<div class="divilife_meta_box">
				<p class="dmp_placement et_pb_single_title">
					<label for="dmp_placement"><?php esc_html_e( 'Display Direction', 'DiviMegaPro' ); ?>:</label>
					<select id="dmp_placement" name="dmp_placement" class="dmp_placement chosen">
					<?php
					foreach ( $dmp_ats as $at_value => $at_name ) {
						printf( '<option value="%2$s"%3$s>%1$s</option>',
							esc_html( $at_name ),
							esc_attr( $at_value ),
							selected( $at_value, $dmp_placement, false )
						);
					} ?>
					</select>
				</p>
			</div>
			<div class="divilife_meta_box">
				<p>
					<label><?php esc_html_e( 'Margin Top/Bottom', 'DiviMegaPro' ); ?>:</label>
					<input class="dmp_margintopbottom" type="text" name="dmp_margintopbottom" value="<?php print esc_attr( $dmp_margintopbottom ); ?>"/>
				</p>
				<div class="clear"></div> 
			</div>
			<?php
			
			if ( !isset( $dmp_megaprowidth ) ) {
				
				$dmp_megaprowidth = '100';
			}
			
			if ( $dmp_megaprowidth == '' ) {
				
				$dmp_megaprowidth = '100';
			}
			
			$dmp_ats = array(
				'100' => esc_attr( '100%' ),
				'75' => esc_attr( '75%' ),
				'50' => esc_attr( '50%' ),
				'25' => esc_attr( '25%' )
			);
			
			
			if ( !isset( $enable_arrow[0] ) ) {
				
				$enable_arrow[0] = '0';
			}
			
			if ( !isset( $arrowtype ) ) {
				
				$arrowtype = 'sharp';
			}
			
			if ( $arrowtype == '' ) {
				
				$arrowtype = 'sharp';
			}
			
			if ( $arrowcolor == '' ) {
				
				$arrowcolor = '#333';
			}
			
			if ( $arrowwidth == '' ) {
				
				$arrowwidth = 10;
			}
			
			if ( $arrowheight == '' ) {
				
				$arrowheight = 10;
			}
			?>
			<div class="divilife_meta_box">
				<p>
					<label for="dmp_megaprowidth">Mega Pro <?php esc_html_e( 'Width', 'DiviMegaPro' ); ?>:</label>
					<select id="dmp_megaprowidth" name="dmp_megaprowidth" class="dmp_megaprowidth chosen" data-dropdownshowhideblock="1">
						<?php
						foreach ( $dmp_ats as $at_value => $at_name ) {
							printf( '<option value="%2$s"%3$s>%1$s</option>',
								esc_html( $at_name ),
								esc_attr( $at_value ),
								selected( $at_value, $dmp_megaprowidth, false )
							);
						} ?>
						<option value="custom"<?php if ( $dmp_megaprowidth == 'custom' ) { ?> selected="selected"<?php } ?> data-showhideblock=".mmw-container"><?php esc_html_e( 'Custom', 'DiviMegaPro' ); ?></option>
					</select>
				</p>
				<div class="mmw-container hide-container<?php if ( $dmp_megaprowidth == 'custom' ) { ?> do-show<?php } ?>">
					<input class="dmp_megaprowidth_custom" type="text" name="dmp_megaprowidth_custom" value="<?php print esc_attr( $dmp_megaprowidth_custom ); ?>"/>
				</div>
				<div class="clear"></div> 
			</div>
			<div class="divilife_meta_box">
				<p>
					<label for="dmp_megaprofixedheight">Mega Pro <?php esc_html_e( 'Maximum Height', 'DiviMegaPro' ); ?>:</label>
				</p>
				<div class="mmw-container">
					<input class="dmp_megaprofixedheight" type="text" name="dmp_megaprofixedheight" value="<?php print esc_attr( $dmp_megaprofixedheight ); ?>"/>
				</div>
				<div class="clear"></div> 
			</div>
			<div class="divilife_meta_box">
				<p>
					<input name="dmp_enable_arrow" type="checkbox" id="dmp_enablearrow" value="1" data-showhideblock=".enable_arrow" <?php checked( $enable_arrow[0], 1 ); ?> /> <?php esc_html_e( 'Enable Arrow', 'DiviMegaPro' ); ?>
				</p>
				<div class="enable_arrow hide-container<?php if ( $enable_arrow[0] == 1 ) { ?> do-show<?php } ?>">
					<div class="divilife_meta_box">
						<p>
							<label for="dmp_arrowfeature_type"><?php esc_html_e( 'Type', 'DiviMegaPro' ); ?>:</label>
							<select id="dmp_arrowfeature_type" name="dmp_arrowfeature_type" class="dmp_arrowfeature-type chosen" data-dropdownshowhideblock="1">
								<option value="sharp"<?php if ( $arrowtype == 'sharp' ) { ?> selected="selected"<?php } ?> data-showhideblock=".dmp_arrowfeature-preview-sharp"><?php esc_html_e( 'Triangle', 'DiviMegaPro' ); ?></option>
								<option value="round"<?php if ( $arrowtype == 'round' ) { ?> selected="selected"<?php } ?> data-showhideblock=".dmp_arrowfeature-preview-round"><?php esc_html_e( 'Round', 'DiviMegaPro' ); ?></option>
							</select>
						</p>
						<div class="clear"></div> 
					</div>
					<div class="divilife_meta_box">
						<p>
							<label><?php esc_html_e( 'Color', 'DiviMegaPro' ); ?>:</label>
							<input class="dmp_arrowfeature-color" type="text" name="dmp_arrowfeature_color" value="<?php echo esc_attr( $arrowcolor ); ?>"/>
						</p>
						<div class="clear"></div> 
					</div> 
					<div class="divilife_meta_box">
						<p>
							<label><?php esc_html_e( 'Width', 'DiviMegaPro' ); ?>:</label>
							<input class="dmp_arrowfeature-width" type="text" name="dmp_arrowfeature_width" value="<?php echo esc_attr( $arrowwidth ); ?>" readonly="readonly" >
						</p>
						<div id="dmp_slider-arrowfeature-width" class="dmp_slider-bar"></div>
					</div>
					<div class="divilife_meta_box">
						<p>
							<label><?php esc_html_e( 'Height', 'DiviMegaPro' ); ?>:</label>
							<input class="dmp_arrowfeature-height" type="text" name="dmp_arrowfeature_height" value="<?php echo esc_attr( $arrowheight ); ?>" readonly="readonly" >
						</p>
						<div id="dmp_slider-arrowfeature-height" class="dmp_slider-bar"></div>
					</div>
					<div class="divilife_meta_box">
						<p>
							<label><?php esc_html_e( 'Preview', 'DiviMegaPro' ); ?>:</label>
						</p>
						<div class="dmp_arrowfeature-preview">
							<div class="dmp_arrowfeature-preview-sharp hide-container<?php if ( $arrowtype == 'sharp' ) { ?> do-show<?php } ?>">
								<div class="tippy-arrow"></div>
							</div>
							<div class="dmp_arrowfeature-preview-round hide-container<?php if ( $arrowtype == 'round' ) { ?> do-show<?php } ?>">
								<svg viewBox="0 0 18 7" xmlns="http://www.w3.org/2000/svg"><path d="M0 7s2.021-.015 5.253-4.218C6.584 1.051 7.797.007 9 0c1.203-.007 2.416 1.035 3.761 2.782C16.012 7.005 18 7 18 7H0z"/></svg>
							</div>
							<div class="dmp_arrowfeature-emptycontent"></div>
						</div>
					</div>
				</div>
				<div class="clear"></div> 
			</div>
			<?php
		}
		
		
		public static function divimegapros_closecustoms_callback( $post ) {
			
			wp_nonce_field( 'divimegapros_closecustoms', 'divimegapros_closecustoms_nonce' );
			
			$textcolor = get_post_meta( $post->ID, 'dmp_closebtn_text_color', true );
			$bgcolor = get_post_meta( $post->ID, 'dmp_closebtn_bg_color', true );
			$fontsize = get_post_meta( $post->ID, 'dmp_closebtn_fontsize', true );
			$borderradius = get_post_meta( $post->ID, 'dmp_closebtn_borderradius', true );
			$padding = get_post_meta( $post->ID, 'dmp_closebtn_padding', true );
			
			if ( $fontsize == '' ) {
				
				$fontsize = 25;
			}
			
			$dmp_enabledesktop = get_post_meta( $post->ID, 'dmp_enabledesktop' );
			if ( !isset( $dmp_enabledesktop[0] ) ) {
				
				$dmp_enabledesktop[0] = '0';
			}
			
			$dmp_enablemobile = get_post_meta( $post->ID, 'dmp_enablemobile' );
			if ( !isset( $dmp_enablemobile[0] ) ) {
				
				$dmp_enablemobile[0] = '0';
			}
			
			$customizeclosebtn = get_post_meta( $post->ID, 'dmp_customizeclosebtn' );
			if ( !isset( $customizeclosebtn[0] ) ) {
				
				$customizeclosebtn[0] = '0';
			}
			
			$disable_dmp_mobile = get_post_meta( $post->ID, 'dmp_mpa_disablemobile' );
			$disable_dmp_desktop = get_post_meta( $post->ID, 'dmp_mpa_disabledesktop' );
			
			if ( !isset( $disable_dmp_mobile[0] ) ) {
				
				$disable_dmp_mobile[0] = '0';
			}
			
			if ( !isset( $disable_dmp_desktop[0] ) ) {
				
				$disable_dmp_desktop[0] = '0';
			}
			
			?>
			<div class="divilife_meta_box dmp_enabledesktop<?php if ( $disable_dmp_desktop[0] == 1 ) { ?> do-hide<?php } ?>">
				<p>
					<input name="dmp_enabledesktop" type="checkbox" id="dmp_enabledesktop" value="1" <?php checked( $dmp_enabledesktop[0], 1 ); ?> /> <?php esc_html_e( 'Enable on Desktop', 'DiviMegaPro' ); ?>
				</p>
			</div>
			<div class="divilife_meta_box dmp_enablemobile<?php if ( $disable_dmp_mobile[0] == 1 ) { ?> do-hide<?php } ?>">
				<p>
					<input name="dmp_enablemobile" type="checkbox" id="dmp_enablemobile" value="1" <?php checked( $dmp_enablemobile[0], 1 ); ?> /> <?php esc_html_e( 'Enable on Mobile', 'DiviMegaPro' ); ?>
				</p>
			</div>
			
			<div class="divilife_meta_box">
				<p>
					<input name="dmp_customizeclosebtn" type="checkbox" id="dmp_customizeclosebtn" value="1" data-showhideblock=".enable_customizations" <?php checked( $customizeclosebtn[0], 1 ); ?> /> <?php esc_html_e( 'Customize Close Button', 'DiviMegaPro' ); ?>
				</p>
				<div class="enable_customizations<?php if ( $customizeclosebtn[0] == 1 ) { ?> do-show<?php } ?>">
					<div class="divilife_meta_box">
						<p>
							<label class="label-color-field"><?php esc_html_e( 'Text color', 'DiviMegaPro' ); ?>:</label>
							<input class="dmp_closebtn-text-color" type="text" name="dmp_closebtn_text_color" value="<?php echo esc_attr( $textcolor ); ?>"/>
						</p>
						<div class="clear"></div> 
					</div> 
					<div class="divilife_meta_box">
						<p>
							<label class="label-color-field"><?php esc_html_e( 'Background color', 'DiviMegaPro' ); ?>:</label>
							<input class="dmp_closebtn-bg-color" type="text" name="dmp_closebtn_bg_color" value="<?php echo esc_attr( $bgcolor ); ?>"/>
						</p>
						<div class="clear"></div> 
					</div>
					<div class="divilife_meta_box">
						<p>
							<label><?php esc_html_e( 'Font size', 'DiviMegaPro' ); ?>:</label>
							<input class="dmp_closebtn_fontsize" type="text" name="dmp_closebtn_fontsize" value="<?php echo esc_attr( $fontsize ); ?>" readonly="readonly" > px
						</p>
						<div id="dmp_slider-closebtn-fontsize" class="dmp_slider-bar"></div>
					</div>
					<div class="divilife_meta_box">
						<p>
							<label><?php esc_html_e( 'Border radius', 'DiviMegaPro' ); ?>:</label>
							<input class="dmp_closebtn_borderradius" type="text" name="dmp_closebtn_borderradius" value="<?php echo esc_attr( $borderradius ); ?>" readonly="readonly" > %
						</p>
						<div id="dmp_slider-closebtn-borderradius" class="dmp_slider-bar"></div>
					</div>
					<div class="divilife_meta_box">
						<p>
							<label><?php esc_html_e( 'Padding', 'DiviMegaPro' ); ?>:</label>
							<input class="dmp_closebtn_padding" type="text" name="dmp_closebtn_padding" value="<?php echo esc_attr( $padding ); ?>" readonly="readonly" > px
						</p>
						<div id="dmp_slider-closebtn-padding" class="dmp_slider-bar"></div>
					</div>
					<div class="divilife_meta_box">
						<p>
							<label><?php esc_html_e( 'Preview', 'DiviMegaPro' ); ?>:</label>
						</p>
						<button type="button" class="divimegapro-customclose-btn"><span>&times;</span></button>
					</div>
				</div>
				<div class="clear"></div> 
			</div>
			<?php
		}
		
		
		public static function divimegapros_moresettings_callback( $post ) {
		
			wp_nonce_field( 'divimegapros_moresettings', 'divimegapros_moresettings_nonce' );
			
			$disablemobile = get_post_meta( $post->ID, 'dmp_mpa_disablemobile' );
			$disabletablet = get_post_meta( $post->ID, 'dmp_mpa_disabletablet' );
			$disabledesktop = get_post_meta( $post->ID, 'dmp_mpa_disabledesktop' );
			
			$dmp_triggertype = get_post_meta( $post->ID, 'dmp_triggertype', true );
			$dmp_exittype = get_post_meta( $post->ID, 'dmp_exittype', true );
			$dmp_exitdelay = get_post_meta( $post->ID, 'dmp_exitdelay', true );
			$dmp_css_selector = get_post_meta( $post->ID, 'dmp_css_selector', true );
			
			$divimegapro_placement = get_post_meta( $post->ID, 'dmp_post_placement', true );
			
			if ( !isset( $disablemobile[0] ) ) {
				
				$disablemobile[0] = '0';
			}
			
			if ( !isset( $disabletablet[0] ) ) {
				
				$disabletablet[0] = '0';
			}
			
			if ( !isset( $disabledesktop[0] ) ) {
				
				$disabledesktop[0] = '0';
			}
			
			if ( $dmp_triggertype == '' ) {
				
				$dmp_triggertype = 'hover';
			}
			
			if ( $dmp_exittype == '' ) {
				
				$dmp_exittype = 'hover';
			}
			?>
			<div class="divilife_meta_box">
				<p>
					<input name="dmp_mpa_disablemobile" type="checkbox" id="dmp_mpa_disablemobile" value="1" <?php checked( $disablemobile[0], 1 ); ?> /> <?php esc_html_e( 'Disable On Mobile', 'DiviMegaPro' ); ?>
				</p>
				<div class="clear"></div> 
			</div>
			
			<div class="divilife_meta_box">
				<p>
					<input name="dmp_mpa_disabletablet" type="checkbox" id="dmp_mpa_disabletablet" value="1" <?php checked( $disabletablet[0], 1 ); ?> /> <?php esc_html_e( 'Disable On Tablet', 'DiviMegaPro' ); ?>
				</p>
				<div class="clear"></div> 
			</div>
			
			<div class="divilife_meta_box">
				<p>
					<input name="dmp_mpa_disabledesktop" type="checkbox" id="dmp_mpa_disabledesktop" value="1" <?php checked( $disabledesktop[0], 1 ); ?> /> <?php esc_html_e( 'Disable On Desktop', 'DiviMegaPro' ); ?>
				</p>
				<div class="clear"></div> 
			</div>
			
			<div class="divilife_meta_box">
				<p class="dmp_triggertype et_pb_single_title">
					<label for="dmp_triggertype"><?php esc_html_e( 'Trigger Type', 'DiviMegaPro' ); ?>:</label>
					<select id="dmp_triggertype" name="dmp_triggertype" class="dmp_triggertype chosen">
						<option value="hover"<?php if ( $dmp_triggertype == 'hover' ) { ?> selected="selected"<?php } ?>>
						<?php esc_html_e( 'Hover', 'DiviMegaPro' ); ?></option>
						<option value="click"<?php if ( $dmp_triggertype == 'click' ) { ?> selected="selected"<?php } ?>>
						<?php esc_html_e( 'Click', 'DiviMegaPro' ); ?></option>
					</select>
				</p>
				<p class="dmp_exittype et_pb_single_title">
					<label for="dmp_exittype"><?php esc_html_e( 'Exit Type', 'DiviMegaPro' ); ?>:</label>
					<select id="dmp_exittype" name="dmp_exittype" class="dmp_exittype chosen" data-dropdownshowhideblock="1">
						<option value="hover"<?php if ( $dmp_exittype == 'hover' ) { ?> selected="selected"<?php } ?> data-showhideblock=".ed-container"><?php esc_html_e( 'Hover', 'DiviMegaPro' ); ?></option>
						<option value="click"<?php if ( $dmp_exittype == 'click' ) { ?> selected="selected"<?php } ?>>
						<?php esc_html_e( 'Click', 'DiviMegaPro' ); ?></option>
					</select>
				</p>
				<div class="ed-container hide-container<?php if ( $dmp_exittype == 'hover' ) { ?> do-show<?php } ?>">
					<label><?php esc_html_e( 'Exit Delay', 'DiviMegaPro' ); ?>:</label>
					<input class="dmp_exitdelay" type="text" name="dmp_exitdelay" value="<?php echo esc_attr( $dmp_exitdelay ); ?>"/>
				</div>
			</div>
			<?php
		}
		
		
		public static function divimegapros_colorbox_callback( $post ) {
			
			wp_nonce_field( 'divimegapros_color_box', 'divimegapros_color_box_nonce' );
			$color = get_post_meta( $post->ID, 'dmp_bg_color', true );
			$fontcolor = get_post_meta( $post->ID, 'dmp_font_color', true );
			?>
			<div class="divilife_meta_box">
				<p>
					<label class="label-color-field"><?php esc_html_e( 'Select Background Color', 'DiviMegaPro' ); ?>: </label>
					<input class="cs-wp-color-picker" type="text" name="dmp_bg_color" value="<?php echo esc_attr( $color ); ?>"/>
				</p>
				<div class="clear"></div> 
			</div> 
			<?php
		}
		
		
		public static function save_post( $post_id, $post ) {
			
			global $pagenow;
			
			// Only set for post_type = divimegapro
			if ( 'divi_mega_pro' !== $post->post_type ) {
				return;
			}
			
			if ( 'post.php' !== $pagenow ) return $post_id;
			
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;
			
			$post_type = get_post_type_object( $post->post_type );
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
				return $post_id;
			
			if ( !isset( $_POST['divimegapros_displaylocations_nonce'] ) ) {
				
				return;
			}
			
			$nonce = sanitize_text_field( wp_unslash( $_POST['divimegapros_displaylocations_nonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'divimegapros_displaylocations' ) ) {
				
				 die(); 
			}
			
			$post_value = '';
			
			$post_at_pages = '';
			
			
			/* Display Locations */
			if ( isset( $_POST['dmp_css_selector_at_pages'] ) ) {
				
				$post_at_pages = sanitize_option( 'dmp_css_selector_at_pages', wp_unslash( $_POST['dmp_css_selector_at_pages'] ) );
				update_post_meta( $post_id, 'dmp_css_selector_at_pages', $post_at_pages );
			}
			
			if ( $post_at_pages == 'specific' ) {
				
				if ( isset( $_POST['dmp_css_selector_at_pages_selected'] ) ) {
					
					$post_value = sanitize_option( 'dmp_css_selector_at_pages_selected', wp_unslash( $_POST['dmp_css_selector_at_pages_selected'] ) );
					update_post_meta( $post_id, 'dmp_css_selector_at_pages_selected', $post_value );
				}
				else {
					
					update_post_meta( $post_id, 'dmp_css_selector_at_pages_selected', '' );
				}
			}
			else {
				
				update_post_meta( $post_id, 'dmp_css_selector_at_pages_selected', '' );
			}
			
			if ( isset( $_POST['dmp_css_selector_at_pagesexception_selected'] ) ) {
				
				$post_value = sanitize_option( 'dmp_css_selector_at_pagesexception_selected', wp_unslash( $_POST['dmp_css_selector_at_pagesexception_selected'] ) );
				update_post_meta( $post_id, 'dmp_css_selector_at_pagesexception_selected', $post_value );
			}
			else {
				
				update_post_meta( $post_id, 'dmp_css_selector_at_pagesexception_selected', '' );
			}
			
			if ( isset( $_POST['dmp_force_render'] ) ) {
				
				$dmp_force_render = 1;
				
			} else {
				
				$dmp_force_render = 0;
			}
			update_post_meta( $post_id, 'dmp_force_render', $dmp_force_render );
			
			
			/* Mega Pro Animation */
			if ( isset( $_POST['dmp_animation'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_animation'] ) );
				update_post_meta( $post_id, 'dmp_animation', $post_value );
			}
			
			
			/* Mega Pro Triggers */
			if ( isset( $_POST['dmp_css_selector'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_css_selector'] ) );
				update_post_meta( $post_id, 'dmp_css_selector', $post_value );
			}
			
			
			/* Mega Pro Display Settings */
			if ( isset( $_POST['dmp_placement'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_placement'] ) );
				update_post_meta( $post_id, 'dmp_placement', $post_value );
			}
			
			if ( isset( $_POST['dmp_margintopbottom'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_margintopbottom'] ) );
				update_post_meta( $post_id, 'dmp_margintopbottom', $post_value );
			}
			
			if ( isset( $_POST['dmp_megaprowidth'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_megaprowidth'] ) );
				update_post_meta( $post_id, 'dmp_megaprowidth', $post_value );
			}
			
			if ( isset( $_POST['dmp_megaprowidth_custom'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_megaprowidth_custom'] ) );
				update_post_meta( $post_id, 'dmp_megaprowidth_custom', $post_value );
			}
			
			if ( isset( $_POST['dmp_megaprofixedheight'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_megaprofixedheight'] ) );
				update_post_meta( $post_id, 'dmp_megaprofixedheight', $post_value );
			}
			
			
			/* Close Button Customizations */
			if ( isset( $_POST['dmp_enabledesktop'] ) ) {
				
				$dmp_enabledesktop = 1;
				
			} else {
				
				$dmp_enabledesktop = 0;
			}
			update_post_meta( $post_id, 'dmp_enabledesktop', $dmp_enabledesktop );
			
			if ( isset( $_POST['dmp_enablemobile'] ) ) {
				
				$dmp_enablemobile = 1;
				
			} else {
				
				$dmp_enablemobile = 0;
			}
			update_post_meta( $post_id, 'dmp_enablemobile', $dmp_enablemobile );
			
			if ( isset( $_POST['dmp_customizeclosebtn'] ) ) {
				
				$dmp_customizeclosebtn = 1;
				
			} else {
				
				$dmp_customizeclosebtn = 0;
			}
			update_post_meta( $post_id, 'dmp_customizeclosebtn', $dmp_customizeclosebtn );
			
			if ( isset( $_POST['dmp_closebtn_text_color'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_closebtn_text_color'] ) );
				update_post_meta( $post_id, 'dmp_closebtn_text_color', $post_value );
			}
			
			if ( isset( $_POST['dmp_closebtn_bg_color'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_closebtn_bg_color'] ) );
				update_post_meta( $post_id, 'dmp_closebtn_bg_color', $post_value );
			}
			
			if ( isset( $_POST['dmp_closebtn_fontsize'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_closebtn_fontsize'] ) );
				update_post_meta( $post_id, 'dmp_closebtn_fontsize', $post_value );
			}
			
			if ( isset( $_POST['dmp_closebtn_borderradius'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_closebtn_borderradius'] ) );
				update_post_meta( $post_id, 'dmp_closebtn_borderradius', $post_value );
			}
			
			if ( isset( $_POST['dmp_closebtn_padding'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_closebtn_padding'] ) );
				update_post_meta( $post_id, 'dmp_closebtn_padding', $post_value );
			}
			
			
			/* Mega Pro Arrow Feature */
			if ( isset( $_POST['dmp_enable_arrow'] ) ) {
				
				$dmp_enable_arrow = 1;
				
			} else {
				
				$dmp_enable_arrow = 0;
			}
			update_post_meta( $post_id, 'dmp_enable_arrow', $dmp_enable_arrow );
			
			if ( isset( $_POST['dmp_arrowfeature_type'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_arrowfeature_type'] ) );
				update_post_meta( $post_id, 'dmp_arrowfeature_type', $post_value );
			}
			
			if ( isset( $_POST['dmp_arrowfeature_color'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_arrowfeature_color'] ) );
				update_post_meta( $post_id, 'dmp_arrowfeature_color', $post_value );
			}
			
			if ( isset( $_POST['dmp_arrowfeature_width'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_arrowfeature_width'] ) );
				update_post_meta( $post_id, 'dmp_arrowfeature_width', $post_value );
			}
			
			if ( isset( $_POST['dmp_arrowfeature_height'] ) ) {
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_arrowfeature_height'] ) );
				update_post_meta( $post_id, 'dmp_arrowfeature_height', $post_value );
			}
			
			
			/* Mega Pro Additional Settings */
			if ( isset( $_POST['dmp_triggertype'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_triggertype'] ) );
				update_post_meta( $post_id, 'dmp_triggertype', $post_value );
			}
			
			if ( isset( $_POST['dmp_mpa_disablemobile'] ) ) {
				
				$dmp_mpa_disablemobile = 1;
				
			} else {
				
				$dmp_mpa_disablemobile = 0;
			}
			
			if ( isset( $_POST['dmp_mpa_disabletablet'] ) ) {
				
				$dmp_mpa_disabletablet = 1;
				
			} else {
				
				$dmp_mpa_disabletablet = 0;
			}
			
			if ( isset( $_POST['dmp_mpa_disabledesktop'] ) ) {
				
				$dmp_mpa_disabledesktop = 1;
				
			} else {
				
				$dmp_mpa_disabledesktop = 0;
			}
			
			update_post_meta( $post_id, 'dmp_mpa_disablemobile', $dmp_mpa_disablemobile );
			update_post_meta( $post_id, 'dmp_mpa_disabletablet', $dmp_mpa_disabletablet );
			update_post_meta( $post_id, 'dmp_mpa_disabledesktop', $dmp_mpa_disabledesktop );
			
			if ( isset( $_POST['dmp_exittype'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_exittype'] ) );
				update_post_meta( $post_id, 'dmp_exittype', $post_value );
			}
			
			if ( isset( $_POST['dmp_exitdelay'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_exitdelay'] ) );
				update_post_meta( $post_id, 'dmp_exitdelay', $post_value );
			}
			
			
			/* Mega Pro Background */
			if ( isset( $_POST['dmp_bg_color'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_bg_color'] ) );
				update_post_meta( $post_id, 'dmp_bg_color', $post_value );
			}
			
			if ( isset( $_POST['dmp_font_color'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['dmp_font_color'] ) );
				update_post_meta( $post_id, 'dmp_font_color', $post_values );
			}
			
			DiviMegaPro_Admin::clear_cache( $post_id );
			
			// This function only clear all Divi Builder files starting with 'et-core-unified'
			DiviMegaPro_Admin::clear_cache( 'all', 'all' );
			
			return $post_id;
		}
		
		
		public static function add_admin_submenu() {
			
			$settings_page = 'divilife_edd_divimegapro_license';
			
			$pass = true;
			
			if ( DIVI_MEGA_PRO_UPDATER === TRUE ) {
				
				$pass = false;
				
				if ( isset( $_POST['option_page'] ) ) {
					
					$option_page = sanitize_text_field( wp_unslash( $_POST['option_page'] ) );
					
					if ( $option_page === $settings_page && isset( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification -- logic for nonce checks are below
					
						$pass = true;
					}
				}
			}
			
			if ( $pass === true ) {
				
				if ( isset( $_POST['_wpnonce'] ) ) {
					
					$wpnonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
					
					if ( wp_verify_nonce( $wpnonce, 'dmp_nonce' ) ) {
						
						self::dmp_save_data();
					}
				}
			}
			
			// Admin page
			add_submenu_page( 'edit.php?post_type=divi_mega_pro', 'Divi Mega Pro', 'Settings', 'edit_posts', 'divimegapro-settings', array( 'DiviMegaPro_Admin_Controller', 'admin_settings' ) );
		}
		
		
		public static function admin_settings() {
			
			add_action( 'admin_init', 'et_epanel_css_admin' );
			
			self::display_configuration_page();
		}
		
		
		private static function dmp_save_data() {
			
			check_admin_referer( 'dmp_nonce' );

			if ( ! current_user_can( 'edit_theme_options' ) ) {
				wp_die();
			}
			
			$divimegapro_enable_cache = array();
			
			if ( isset( $_POST['divimegapro_enable_cache'] ) ) {
				
				$divimegapro_enable_cache = sanitize_option( 'divimegapro_enable_cache', wp_unslash( $_POST['divimegapro_enable_cache'] ) );
			}
			
			update_option( 'divimegapro_enable_cache', $divimegapro_enable_cache );
			
			$divimegapro_singleton = array();
			
			if ( isset( $_POST['divimegapro_singleton'] ) ) {
				
				$divimegapro_singleton = sanitize_option( 'divimegapro_singleton', wp_unslash( $_POST['divimegapro_singleton'] ) );
			}
			
			update_option( 'divimegapro_singleton', $divimegapro_singleton );
			
			if ( DIVI_MEGA_PRO_UPDATER === TRUE ) {
				
				if ( isset( $_POST['divilife_edd_divimegapro_license_key'] ) && $_POST['divilife_edd_divimegapro_license_key'] !== '*******' ) {
				
					$license_key = '';
					
					if ( isset( $_POST['divilife_edd_divimegapro_license_key'] ) ) {
						
						$license_key = sanitize_text_field( wp_unslash( $_POST['divilife_edd_divimegapro_license_key'] ) );
					}
					
					if ( strlen( $license_key ) > 25 ) {
						
						update_option( 'divilife_edd_divimegapro_license_key', $license_key );
						
						divilife_edd_divimegapro_activate_license();
					}
					else {
						
						update_option( 'divilife_edd_divimegapro_license_key', '' );
						update_option( 'divilife_edd_divimegapro_license_status', '' );
						
						divilife_edd_divimegapro_deactivate_license();
					}
				}
			}
			
			$base_url = admin_url( 'edit.php?post_type=divi_mega_pro&page=divimegapro-settings' );
			
			$redirect = add_query_arg( array( 'divilife' => 'divimegapro' ), $base_url );
			
			wp_safe_redirect( $redirect );
			exit();
		}
		
		
		public static function display_configuration_page() {
			
			DiviMegaPro_Admin::$options = get_option( 'dmp_settings' );
			
			if ( DIVI_MEGA_PRO_UPDATER === TRUE ) {
				
				$license = get_option( 'divilife_edd_divimegapro_license_key' );
				$status  = get_option( 'divilife_edd_divimegapro_license_status' );
				$check_license = divilife_edd_divimegapro_check_license( TRUE );
				
				if ( $license != '' ) {
					
					$license = '*******';
				}
				
				$daysleft = 0;
				
				if ( isset( $check_license->expires ) && $check_license->expires != 'lifetime' ) {
					
					$license_expires = strtotime( $check_license->expires );
					$now = strtotime('now');
					$timeleft = $license_expires - $now;
					
					$daysleft = round( ( ( $timeleft / 24 ) / 60 ) / 60 );
					if ( $daysleft > 0 ) {
						
						$daysleft = '( ' . ( ( $daysleft > 1 ) ? $daysleft . ' days left' : $daysleft . ' day left' ) . ' )';
						
					} else {
						
						$daysleft = '';
					}
				}
			}
			
			
			$divimegapro_enable_cache = get_option( 'divimegapro_enable_cache' );
			
			if ( !isset( $divimegapro_enable_cache[0] ) ) {
				
				$divimegapro_enable_cache = 0;
			}
			
			// Singleton feature
			$divimegapro_singleton = get_option( 'divimegapro_singleton' );
			
			if ( !isset( $divimegapro_singleton[0] ) ) {
				
				$divimegapro_singleton[0] = '';
			}
			
			$header = in_array( 'header', $divimegapro_singleton );
			$content = in_array( 'content', $divimegapro_singleton );
			$footer = in_array( 'footer', $divimegapro_singleton );
			
			?>
		<div id="wrapper">
		  <div id="panel-wrap">
		  
				<form method="post" action="options.php">
				
					<div id="epanel-wrapper">
						<div id="epanel" class="et-onload">
							<div id="epanel-content-wrap">
								<div id="epanel-header" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
									<h1 id="epanel-title"><?php esc_html_e( 'Divi Mega Pro - Settings', 'DiviMegaPro' ); ?></h1>
								</div>
								<div id="epanel-content">
								
									<div class="et-tab-content ui-widget-content ui-corner-bottom" aria-hidden="false">
									
										<div class="et-epanel-box et-epanel-box__checkbox-list">
										
											<div class="et-box-title">
											<h3><?php esc_html_e( 'Enable Cache', 'DiviMegaPro' ); ?></h3>
											</div>
											
											<div class="et-box-content et-epanel-box-small-1">
												<div class="et-box-content--list">
													<div class="et-box-content">
													
														<input type="checkbox" class="et-checkbox yes_no_button do-hide" name="divimegapro_enable_cache" id="divimegapro_enable_cache" value="1" <?php checked( $divimegapro_enable_cache, 1 ); ?>>
														<div class="et_pb_yes_no_button<?php if ( $divimegapro_enable_cache === '1' ) { ?>  et_pb_on_state<?php } else { ?> et_pb_off_state<?php } ?>">
															<span class="et_pb_value_text et_pb_on_value"><?php esc_html_e( 'Enabled', 'DiviMegaPro' ); ?></span>
															<span class="et_pb_button_slider"></span>
															<span class="et_pb_value_text et_pb_off_value"><?php esc_html_e( 'Disabled', 'DiviMegaPro' ); ?></span>
														</div>
													</div>
												</div>
											</div>
										
										</div>
									
										<div class="et-epanel-box et-epanel-box__checkbox-list">
										
											<div class="et-box-title">
											<h3><?php esc_html_e( 'Enable Snazzy Slide Transition', 'DiviMegaPro' ); ?></h3>
											<p>
When enabled, the Snazzy Slide Transition will smoothly change the content within a mega menu or mega tooltip while keeping the main container visible. <br> <b>Please note: this effect will force the trigger type and exit type to be in hover mode.</b> <a href="https://divilife.com/docs/divi-mega-pros-snazzy-slide-transition-feature" target="_blank">Learn More</a></p>
											</div>
											
											<div class="et-box-content et-epanel-box-small-2">
												<div class="et-box-content--list">
													<div class="et-box-content">
														<span class="et-panel-box__checkbox-list-label"><?php esc_html_e( 'Header', 'DiviMegaPro' ); ?></span>
														
														<input type="checkbox" class="et-checkbox yes_no_button do-hide" name="divimegapro_singleton[]" id="divimegapro_singleton" value="header" <?php checked( $header, 1 ); ?>>
														<div class="et_pb_yes_no_button<?php if ( $header === true ) { ?>  et_pb_on_state<?php } else { ?> et_pb_off_state<?php } ?>">
															<span class="et_pb_value_text et_pb_on_value"><?php esc_html_e( 'Enabled', 'DiviMegaPro' ); ?></span>
															<span class="et_pb_button_slider"></span>
															<span class="et_pb_value_text et_pb_off_value"><?php esc_html_e( 'Disabled', 'DiviMegaPro' ); ?></span>
														</div>
													</div>
													
													<div class="et-box-content">
														<span class="et-panel-box__checkbox-list-label"><?php esc_html_e( 'Content', 'DiviMegaPro' ); ?></span>
														<input type="checkbox" class="et-checkbox yes_no_button do-hide" name="divimegapro_singleton[]" id="divimegapro_singleton" value="content" <?php checked( $content, 1 ); ?>>
														<div class="et_pb_yes_no_button<?php if ( $content === true ) { ?>  et_pb_on_state<?php } else { ?> et_pb_off_state<?php } ?>">
															<span class="et_pb_value_text et_pb_on_value"><?php esc_html_e( 'Enabled', 'DiviMegaPro' ); ?></span>
															<span class="et_pb_button_slider"></span>
															<span class="et_pb_value_text et_pb_off_value"><?php esc_html_e( 'Disabled', 'DiviMegaPro' ); ?></span>
														</div>
													</div>
													
													<div class="et-box-content">
														<span class="et-panel-box__checkbox-list-label"><?php esc_html_e( 'Footer', 'DiviMegaPro' ); ?></span>
														<input type="checkbox" class="et-checkbox yes_no_button do-hide" name="divimegapro_singleton[]" id="divimegapro_singleton" value="footer" <?php checked( $footer, 1 ); ?>>
														<div class="et_pb_yes_no_button<?php if ( $footer === true ) { ?>  et_pb_on_state<?php } else { ?> et_pb_off_state<?php } ?>">
															<span class="et_pb_value_text et_pb_on_value"><?php esc_html_e( 'Enabled', 'DiviMegaPro' ); ?></span>
															<span class="et_pb_button_slider"></span>
															<span class="et_pb_value_text et_pb_off_value"><?php esc_html_e( 'Disabled', 'DiviMegaPro' ); ?></span>
														</div>
													</div>
												</div>
											</div>
										
										</div>
										
										<?php if ( DIVI_MEGA_PRO_UPDATER === TRUE ) { ?>
										
											<div class="et-epanel-box">
												<?php settings_fields('divilife_edd_divimegapro_license'); ?>
												<div class="et-box-title"><h3><?php esc_html_e( 'License Key', 'DiviMegaPro' ); ?></h3></div>
												<div class="et-box-content">
													<label class="description" for="divilife_edd_divimegapro_license_key"></label>
													<input id="divilife_edd_divimegapro_license_key" name="divilife_edd_divimegapro_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
												</div>
											</div>
											
											<?php if ( FALSE !== $license && $check_license->license != 'invalid' ) { ?>
											
												<div class="et-epanel-box">
													<div class="et-box-title"><h3><?php esc_html_e( 'License Status', 'DiviMegaPro' ); ?></h3></div>
													<div class="et-box-content">
														<?php 
															if ( $status !== false && $check_license->license == 'valid' ) {
																
																print '
																<p class="inputs"><span class="jquery-checkbox"><span class="mark"></span></span></p><br><br>';
															}
															else {
																
																if ( $check_license->license == 'expired' ) {
																
																	print '<span class="dashicons dashicons-no-alt dashicons-fail dashicons-large"></span>';
																	print '&nbsp;&nbsp;<span class="small-text">( Expired on ' . gmdate( 'F d, Y', strtotime( $check_license->expires ) ) . ' )</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
																}
																
																if ( $check_license->license == NULL && $status !== false ) {
																	
																	print '<span class="dashicons dashicons-no-alt dashicons-fail dashicons-large"></span>';
																	print '&nbsp;&nbsp;<span class="small-text">( Cannot retrieve license status from Divi Life server. Please contact Divi Life support. )</span>';
																}
															}
														?>
													</div>
												</div>
												<?php
												
												if ( $status !== false ) { 
													
													if ( $daysleft != '' && $check_license->license == 'valid' ) { ?>
													<div class="et-epanel-box">
														<div class="et-box-title"><h3><?php esc_html_e('License Expires on', 'DiviMegaPro' ); ?></h3></div>
														<div class="et-box-content">
															<h4 class="no-margin">
																<?php print esc_html__( gmdate( 'F d, Y', strtotime( $check_license->expires ) ) ); ?>
																<?php print esc_html__( $daysleft ); ?>
															</h4>
														</div>
													</div>
													<?php
													}
												}
												?>
											
											<?php } ?>
										<?php } ?>
										
									</div>
					
								</div> <!-- end epanel-content div -->
							</div> <!-- end epanel-content-wrap div -->
						</div> <!-- end epanel div -->
					</div> <!-- end epanel-wrapper div -->
					
					<div id="epanel-bottom">
						<?php wp_nonce_field( 'dmp_nonce' ); ?>
						<button class="et-save-button" name="dmp_save" id="dmp-save"><?php esc_html_e( 'Save Changes', 'DiviMegaPro' ); ?></button>
					</div>

				</form>
				
			</div> <!-- end panel-wrap div -->
		</div> <!-- end wrapper div -->
			<?php
		
		}
		
	} // end DiviMegaPro_Admin_Controller