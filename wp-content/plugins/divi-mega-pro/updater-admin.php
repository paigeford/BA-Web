<?php

define( 'DIVILIFE_EDD_DIVIMEGAPRO_URL', 'https://divilife.com' );
define( 'DIVILIFE_EDD_DIVIMEGAPRO_ID', 124151 );
define( 'DIVILIFE_EDD_DIVIMEGAPRO_NAME', 'Divi Mega Pro' );
define( 'DIVILIFE_EDD_DIVIMEGAPRO_AUTHOR', 'Tim Strifler' );
define( 'DIVILIFE_EDD_DIVIMEGAPRO_VERSION', DIVI_MEGA_PRO_VERSION );
define( 'DIVILIFE_EDD_DIVIMEGAPRO_PAGE_SETTINGS', 'divimegapro-settings' );

// the name of the settings page for the license input to be displayed
define( 'DIVILIFE_EDD_DIVIMEGAPRO_LICENSE_PAGE', 'divimegapro-license' );

function divilife_edd_divimegapro_updater() {
	
	// retrieve our license key from the DB
	$license_key = trim( get_option( 'divilife_edd_divimegapro_license_key' ) );
	
	// setup the updater
	$edd_updater = new edd_divimegapro( DIVILIFE_EDD_DIVIMEGAPRO_URL, DIVI_MEGA_PRO_PLUGIN_BASENAME, array(
			'version' 	=> DIVILIFE_EDD_DIVIMEGAPRO_VERSION,
			'license' 	=> $license_key,
			'item_name' => DIVILIFE_EDD_DIVIMEGAPRO_NAME,
			'author' 	=> DIVILIFE_EDD_DIVIMEGAPRO_AUTHOR,
			'beta'		=> false
		)
	);
}
add_action( 'admin_init', 'divilife_edd_divimegapro_updater', 0 );


function divilife_edd_divimegapro_register_option() {
	
	// creates our settings in the options table
	register_setting('divilife_edd_divimegapro_license', 'divilife_edd_divimegapro_license_key', 'divilife_edd_divimegapro_sanitize_license' );
}
add_action('admin_init', 'divilife_edd_divimegapro_register_option');


function divilife_edd_divimegapro_sanitize_license( $new ) {
	
	$old = get_option( 'divilife_edd_divimegapro_license_key' );
	
	if ( $old && $old != $new ) {
		
		delete_option( 'divilife_edd_divimegapro_license_status' ); // new license has been entered, so must reactivate
	}
	
	return $new;
}


function divilife_edd_divimegapro_activate_license() {

	// listen for our activate button to be clicked
	if ( isset( $_POST['divilife_edd_divimegapro_license_key'] ) ) {
		
		$base_url = admin_url( 'edit.php?post_type=divi_mega_pro&page=divimegapro-settings' );
		
		// retrieve the license from the database
		$license = trim( get_option( 'divilife_edd_divimegapro_license_key' ) );
		
		$message = '';
		
		
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => rawurlencode( DIVILIFE_EDD_DIVIMEGAPRO_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( DIVILIFE_EDD_DIVIMEGAPRO_URL, array( 'timeout' => 15, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				
				$message = $response->get_error_message();
				
			} else {
				
				$message = __( 'Cannot retrieve any response from Divi Life server. Please contact Divi Life support.' );
			}

		} else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch( $license_data->error ) {

					case 'expired' :

						$message = sprintf(
							__( 'Your license key expired on %s.' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'revoked' :

						$message = __( 'Your license key has been disabled.' );
						break;

					case 'missing' :

						$message = __( 'Invalid license.' );
						break;

					case 'invalid' :
					case 'site_inactive' :

						$message = __( 'Your license is not active for this URL.' );
						break;

					case 'item_name_mismatch' :

						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), DIVILIFE_EDD_DIVIMEGAPRO_NAME );
						break;

					case 'no_activations_left':

						$message = __( 'Your license key has reached its activation limit.' );
						break;

					default :

						$message = __( 'An error occurred. Please contact Divi Life support.' );
						break;
				}

			}

		}
		
		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			
			update_option( 'divilife_edd_divimegapro_license_key', '' );
			update_option( 'divilife_edd_divimegapro_license_status', '' );
			
			$redirect = add_query_arg( array( 'message' => rawurlencode( $message ), 'divilife' => 'divimegapro' ), $base_url );
			
			wp_safe_redirect( $redirect );
			exit();
		}
		else {
		
			update_option( 'divilife_edd_divimegapro_license_status', $license_data->license );
			wp_safe_redirect( $base_url );
			exit();
		}
	}
}


function divilife_edd_divimegapro_deactivate_license() {

	// listen for our activate button to be clicked
	if ( isset( $_POST['divilife_edd_divimegapro_license_key'] ) ) {
		
		// retrieve the license from the database
		$license = trim( get_option( 'divilife_edd_divimegapro_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => rawurlencode( DIVILIFE_EDD_DIVIMEGAPRO_NAME ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( DIVILIFE_EDD_DIVIMEGAPRO_URL, array( 'timeout' => 15, 'body' => $api_params ) );
		
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.' );
			}

			$base_url = admin_url( 'edit.php?post_type=divi_mega_pro&page=divimegapro-settings' );
			$redirect = add_query_arg( array( 'message' => rawurlencode( $message ), 'divilife' => 'divimegapro' ), $base_url );

			wp_safe_redirect( $redirect );
			exit();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' ) {
			delete_option( 'divilife_edd_divimegapro_license_status' );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=divi_mega_pro&page=divimegapro-settings' ) );
		exit();

	}
}


/**
 * This is a means of catching errors from the activation method above and displaying it to the customer
 */
function divilife_edd_divimegapro_admin_notices() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended 
	if ( isset( $_GET['divilife'] ) && ! empty( $_GET['message'] ) && $_GET['divilife'] === 'divimegapro' ) {
		
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended 
		$message = urldecode( sanitize_text_field( wp_unslash( $_GET['message'] ) ) );
		?>
		<div class="notice notice-error">
			<p><?php print et_core_esc_previously( $message ); ?></p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'divilife_edd_divimegapro_admin_notices' );


function divilife_divimegapro_get_home_url( $blog_id = null, $path = '', $scheme = null ) {
	
    global $pagenow;
 
    $orig_scheme = $scheme;
 
    if ( empty( $blog_id ) || ! is_multisite() ) {
        $url = get_option( 'home' );
    } else {
        switch_to_blog( $blog_id );
        $url = get_option( 'home' );
        restore_current_blog();
    }
 
    if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), true ) ) {
        if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow ) {
            $scheme = 'https';
        } else {
            $scheme = parse_url( $url, PHP_URL_SCHEME );
        }
    }
 
    $url = set_url_scheme( $url, $scheme );
 
    if ( $path && is_string( $path ) ) {
        $url .= '/' . ltrim( $path, '/' );
    }
	
	return $url;
}

function divilife_edd_divimegapro_check_license( $msg = FALSE ) {

	global $wp_version;

	$license = trim( get_option( 'divilife_edd_divimegapro_license_key' ) );

	$api_params = array(
		'edd_action' => 'check_license',
		'license' => $license,
		'item_name' => rawurlencode( DIVILIFE_EDD_DIVIMEGAPRO_NAME ),
		'url'       => divilife_divimegapro_get_home_url()
	);

	// Call the custom API.
	$response = wp_remote_post( DIVILIFE_EDD_DIVIMEGAPRO_URL, array( 'timeout' => 15, 'body' => $api_params ) );

	if ( is_wp_error( $response ) )
		return false;

	$license_data = json_decode( wp_remote_retrieve_body( $response ) );
	
	if ( $license_data->license == 'valid' ) {
		
		if ( $msg ) {
			
			return $license_data;
			
		} else {
		
			return TRUE;
		}
		
	} else {
		
		if ( $msg ) {
			
			return $license_data;
			
		} else {
		
			return FALSE;
		}
	}
}


/**
 * Displays an inactive notice when the plugin is inactive.
 */
function divilife_edd_divimegapro_inactive_notice() {
	
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended 
	if ( isset( $_GET[ 'page' ] ) && DIVILIFE_EDD_DIVIMEGAPRO_PAGE_SETTINGS === $_GET[ 'page' ] ) {
		return;
	}
	
	$status = get_option( 'divilife_edd_divimegapro_license_status' );
	if ( $status === '' || $status === false ) {
	
		?>
		<div class="notice notice-error">
			<p>
			<?php 
			
			printf(
				et_core_intentionally_unescaped( __( 'The <strong>%s</strong> API Key has not been activated, so the plugin is inactive! %sClick here%s to activate <strong>%s</strong>.', DIVILIFE_EDD_DIVIMEGAPRO_NAME ), 'fixed_string' ) , 
				esc_attr( DIVILIFE_EDD_DIVIMEGAPRO_NAME ), 
				'<a href="' . esc_url( admin_url( 'edit.php?post_type=divi_mega_pro&page=divimegapro-settings' ) ) . '">', 
				'</a>', esc_attr( DIVILIFE_EDD_DIVIMEGAPRO_NAME )
			);
			
			?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'divilife_edd_divimegapro_inactive_notice', 0 );


