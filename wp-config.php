<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'ba-web' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         't]UXl_4n`M/*&O!wu9hDp$rDlQZ=_m_*W/eh-a,Y6V:aWBjAWMEYF9aN;*oP)4zb' );
define( 'SECURE_AUTH_KEY',  'cT1@}JgRw!dqv ,CS`{hp/Pe)1eLLV`*A#h2JpqO(U)JHk+TU/P,tvSU5eCJ#CL5' );
define( 'LOGGED_IN_KEY',    'w&)b63#:n75!cFtD;@Iaym14<uOGV;<[01.J^SlSjj$V=)6t{tcju=2X,-_3@ wC' );
define( 'NONCE_KEY',        '5N/p.#-Py|&zJv;k2:C#iC5BK0}9qI*i}*gzVCW t1>0UrArZr&WDpiY?QJX]-])' );
define( 'AUTH_SALT',        'Hio[!(u:`q bJW-Nfh<TS6Tyti-]J>K>j|[5|1= G#:C!%g{~ K%@zzNDPKmLK@n' );
define( 'SECURE_AUTH_SALT', '<5OsjpbOnVcZ}YHIp-FX9G&N@J1;-cthQ^NN1ep3-A`0_Du0UOWx79v_LYs,w6Ur' );
define( 'LOGGED_IN_SALT',   '@89H13wy={sj>_{lLG2G.jP{FB=6:3<#9;c0zFYzxt7~K}]qJV4mfD*,^|*+oy>]' );
define( 'NONCE_SALT',       '1[F4O}qDoS aCBeu#vOj}U#ZCdJxl~l:4@tZ_-5D2Z-h5N4CC|XAHR(JsXoPe@b]' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

define('WP_ALLOW_MULTISITE', true);

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
