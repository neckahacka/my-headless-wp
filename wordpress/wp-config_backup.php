<?php
define( 'WP_CACHE', true );
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
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'headless_wp' ); // ✅ Use the local database name
/** Database username */
define( 'DB_USER', 'root' ); // ✅ XAMPP default user
/** Database password */
define( 'DB_PASSWORD', '' ); // ✅ Leave empty (no password)
/** Database hostname */
define( 'DB_HOST', 'localhost' ); // ✅ Must be 'localhost'
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
define('AUTH_KEY',         '6-M[y|pz!B<@,k ;9yJ 1Z|)f+Dz}L%RHF(CkKqc:a&+}&otyj*JLp*B(v,G;5;o');
define('SECURE_AUTH_KEY',  '4%y~/wo4N;|V^P}o[,KQi&h,p&8vw:]YQ78dCAq`D+F.i4,r`O:o~HnZ%{L=lF3t');
define('LOGGED_IN_KEY',    '5#uOUzq_hT/M1%^:t^)2APV.$e$i:7#ZV~.|GKVZ!ER!Z+S9ji,dOqGI_.LS]kX7');
define('NONCE_KEY',        '/+,|](NO:7i_P-DwdGXYedttv>N7C{5J->W@_}(GGD5~,1mr2SX~;k3^=<tG*:Jf');
define('AUTH_SALT',        'l-o46rJ{;@:l2M(a]y9xo/^Q~Eb(ns/<N9ptonfVKp9BscapNDR^.A{B%6YRMQIN');
define('SECURE_AUTH_SALT', 'iQ0trr&KBkF3cxBrm^ =88c$FT5_1v~l^(;U?DOonYpfseZ.(`<avNB*s !==Nx)');
define('LOGGED_IN_SALT',   '*QupoaoZ%-%3?i)33b9xlT7_hz-:2y|+L0-d_IacQ=K7j^%Px>z8 e2%)QL{U*1?');
define('NONCE_SALT',       '``|p_vHCAH hRO+P6lnRTwCd.9[I;.PP< ihn33-+1HYB:8ym2G7J8j]l_`aAw0>');
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
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
@ini_set('display_errors', 1);
/* Add any custom values between this line and the "stop editing" line. */
require_once __DIR__ . '/vendor/autoload.php';  // Ensure you have autoload
Dotenv\Dotenv::createImmutable(__DIR__)->load();

// Use environment variables
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_HOST'));
define('DB_CHARSET', getenv('DB_CHARSET'));
define('DB_COLLATE', getenv('DB_COLLATE'));

// Example for accessing API credentials
$api_key = getenv('API_KEY');

define('HASHCATS_AI_KEY', getenv('HASHCATS_AI_KEY'));
define('JWT_AUTH_SECRET_KEY', getenv('JWT_AUTH_SECRET_KEY'));
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
// HashCats Studio Configuration
define('HASHCATS_STUDIO_URL', 'http://localhost:3000');

// Add JWT CORS setting
define('JWT_AUTH_CORS_ENABLE', true);

