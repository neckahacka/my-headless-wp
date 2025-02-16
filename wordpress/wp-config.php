<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - LOCAL ONLY ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'headless_wp' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using:
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'D9+9M~:iNRE_Z>9Ab[O_sPj,l.8)HP.Q6O!4pEm29[~5g+_@%gHfa{|S]}b{4z.6');
define('SECURE_AUTH_KEY',  ',K2M22Yx?KX-#0`F=(5KNjo;`<X)B|88%0yG#+I.ai[`Ex$z{~FIoH3po^&B(b&A');
define('LOGGED_IN_KEY',    'q|V7GIP^aTE|IY_6SV+WQwF_i7z-r[huR?Yq8|x}+%#2v +I+{ObT(`0tBQbQ:Pt');
define('NONCE_KEY',        '84=XGng-+/|S;9Uj-|?8id*`I!m6w?*?g}g J/Bt0(bx.+*N[5Hi}x]Gw&FC^3yL');
define('AUTH_SALT',        'P:l`AZ})$ml8Wi9xS<{|_ky,gGZ(b<,7-q/MYbI(u>|vQX$#Y}+s!zZ3KEF7}tIi');
define('SECURE_AUTH_SALT', '-~c{&JaSThr8s&Dz@ kPARJAN:-W`ef^tZ_e):u.|<2m:3@MY.&2.-3]Pg>RbWnN');
define('LOGGED_IN_SALT',   '`,e|e]Oj<lKD+A35DU1xYt{9wX+n(mjO>k]qAdp}Oz>g 1gkIr%55b7PEKQC|(?l');
define('NONCE_SALT',       '[|mK#M$mA2cZZ2HMvBgluyZ.JCH/rwO /9@2s:9M&5>G61yeMh;4V%@7/*N,(~+^');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
@ini_set( 'display_errors', 1 );

/* Add any custom values between this line and the "stop editing" line. */

// JWT Authentication Secret Key (Loaded from .env file)
define('JWT_AUTH_SECRET_KEY', 'B):Ar%,<lXo,IFG"UrD5k0x?xZ8X#F Zq$^VVw%aaH`eTCkadcaBwnc>lj6I;');

// Enable JWT CORS setting
define('JWT_AUTH_CORS_ENABLE', true);

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
