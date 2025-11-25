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
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'fk23uJ$]9uXRfx~FRg!a[8&{= HBXt(I-^RoEte@LnMYsIt5#C@@mNVK%l<WOQQ*' );
define( 'SECURE_AUTH_KEY',   '?)3kp@Xr@U^7Exu*]xlHh` ~!KFog4hIu~@|c!EreAdQ*;|m4vM4}|l#!Zv#yFfY' );
define( 'LOGGED_IN_KEY',     '9PAYK1Oa;&s|6XesMQO]$1lql=ECF%,K*0mEqCKB1PdUxffL]s3OSynOHH(({t`P' );
define( 'NONCE_KEY',         '7f}4-J@GaAMS[Ue?o0vVTlljW_B:tB4?^dZ*52,(iR;GSgF;ViGY99,|8ha@T#I9' );
define( 'AUTH_SALT',         'B=W<U*JpodiiJ}k`u #>RuH[_a5*4NWvGCYiRuTBL@eIGE@3-WV*vsU&x62e@-Xz' );
define( 'SECURE_AUTH_SALT',  'gq-@iV65NS2:B*6#mR<B>GE`Fv)U&w1E{GTk:Lb$mSz^%c|85lV$T%)larT*]?ty' );
define( 'LOGGED_IN_SALT',    'z.1KXX3ZD9b|(# {l2,DGgU$AV%MTIv:GzWRKuK2A9F6MG6<2-0GqeH[z]:`/|!7' );
define( 'NONCE_SALT',        'f;~c:t(u5*A5,)EA2F+.xUR5JWcj8^pmM3$ H[zH{LvQo<V;TSsw}(<PP_HC^}^B' );
define( 'WP_CACHE_KEY_SALT', '4ejt%z$rx4}%y(Tc]v*5:7d/ /~]BU}n,Lf(-$L o(s&PU*wdQaQoUs=q$.W ,1-' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
