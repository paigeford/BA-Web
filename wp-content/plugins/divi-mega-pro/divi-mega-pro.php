<?php
/*
Plugin Name: Divi Mega Pro
Plugin URL: https://divilife.com/
Description: Create mega menus and tooltips from Divi Builder
Version: 1.9.1.1
Author: Divi Life — Tim Strifler
Author URI: https://divilife.com
*/

// Make sure we don't expose any info if called directly or may someone integrates this plugin in a theme
if ( class_exists('DiviMegaPro') || !defined('ABSPATH') || !function_exists( 'add_action' ) ) {
	
	return;
}

$all_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

$current_theme = wp_get_theme();

if ( ( $current_theme->get( 'Name' ) !== 'Divi' && $current_theme->get( 'Template' ) !== 'Divi' ) 
	&& ( $current_theme->get( 'Name' ) !== 'Extra' && $current_theme->get( 'Template' ) !== 'Extra' ) ) {
	
	if ( stripos( implode( $all_plugins ), 'divi-builder.php' ) === false ) {
		
		function dmp_divibuilder_required() {
			
			$class = 'notice notice-error';
			$message = __( 'Divi Mega Pro requires plugin: Divi Builder', 'DiviMegaPro' );
			
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
		add_action( 'admin_notices', 'dmp_divibuilder_required' );
		
		return;
	}
}

define( 'DIVI_MEGA_PRO_VERSION', '1.9.1.1');
define( 'DIVI_MEGA_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'DIVI_MEGA_PRO_PLUGIN_NAME', trim( dirname( DIVI_MEGA_PRO_PLUGIN_BASENAME ), '/' ) );
define( 'DIVI_MEGA_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DIVI_MEGA_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define( 'DIVI_MEGA_PRO_SERVER_TIMEZONE', 'UTC');
define( 'DIVI_MEGA_PRO_SCHEDULING_DATETIME_FORMAT', 'm\/d\/Y g:i A');

require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/class.divi-mega-pro.core.php' );

add_action( 'init', array( 'DiviMegaPro', 'init' ) );
	
if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	
	require_once( DIVI_MEGA_PRO_PLUGIN_DIR . '/class.divi-mega-pro.admin.core.php' );
	add_action( 'init', array( 'DiviMegaPro_Admin', 'init' ) );
}


register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
register_activation_hook( __FILE__, 'divimegapro_flush_rewrites' );
function divimegapro_flush_rewrites() {
	
	DiviMegaPro::register_cpt();
	flush_rewrite_rules();
	
	$divimegapro_enable_cache = get_option( 'divimegapro_enable_cache' );
	if ( !isset( $divimegapro_enable_cache[0] ) ) {
		
		update_option( 'divimegapro_enable_cache', 1 );
	}
}

$edd_updater = DIVI_MEGA_PRO_PLUGIN_DIR . 'updater.php';
$edd_updater_admin = DIVI_MEGA_PRO_PLUGIN_DIR . 'updater-admin.php';

if ( file_exists( $edd_updater ) && file_exists( $edd_updater_admin ) ) {

	// Load the API Key library if it is not already loaded
	if ( ! class_exists( 'edd_divimegapro' ) ) {
		
		require_once( $edd_updater );
		require_once( $edd_updater_admin );
	}
	
	define( 'DIVI_MEGA_PRO_UPDATER', TRUE );
}
else {
	
	define( 'DIVI_MEGA_PRO_UPDATER', FALSE );
}
