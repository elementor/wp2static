<?php
// phpcs:disable Generic.Files.LineLength.MaxExceeded
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'wordpress' );

/** MySQL database password */
define( 'DB_PASSWORD', '8BVMm2jqDE6iADNyfaVCxoCzr3eBY6Ep' );

/** MySQL hostname */
define( 'DB_HOST', ':' . dirname( __FILE__, 2 ) . '/mariadb/data/mysql.sock' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY', 'A6tr^0=N<QP++W-%/hv1yOZ4]f<3m`/}0(A/UFi6pmy|ZLT)=>e+raWRmgYCs>aK' );
define( 'SECURE_AUTH_KEY', 'Vj>>M=2uvzzWw-tqT?]H3RWsG%jTA9EhJKn~F6:8B<So+<A_},Y<RW-U)}/w-0Y+' );
define( 'LOGGED_IN_KEY', 'jJCaP}~YG-Se+<WK5g9.@K*^g7*v=_yLyX7+i?{Mc%CcJ|L54u=+*+rW_Uxa{95L' );
define( 'NONCE_KEY', '98.DYg|E,*CV]Rz&#Q{j]?n[!sQji*X9%`Ic_n>NExS<7Sn[SG:`P8)*CqC[G2NF' );
define( 'AUTH_SALT', '*KON9~cuX+lG,Kx6`^5d#kyu5oFt{^~O:[]pB]F745S<B2U*L0aHb;(pEn:kPggf' );
define( 'SECURE_AUTH_SALT', 'MV6l72,Yi+y8X`0wm5-T)6T#ZY~Sp;G+e3. ^CHdZ1W_*WY?;9>c}^|:[<j0FkpV' );
define( 'LOGGED_IN_SALT', 'Don!4M=(5=Y=*@.NI:bn$V[FZ*a~wyJ:s9p&l@XD{7WzqBDO.3+-#[H>79,rG)Q~' );
define( 'NONCE_SALT', 't={*XeC6q4LZ5:%wo*C3f-sr6g3#Wa}_EMf}Jh$8*P/%4SdK4=0hjjnVa&8yY#-F' );
define( 'WP_CACHE_KEY_SALT', ')O~B@EKC(tfdgDg6R8@6;ePxJJkXMpZ&.u?X{j##:@7-,/*YKvvl-l4}r^@2=Ha-' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

if ( defined( 'WP_CLI' ) ) {
    $_SERVER['HTTP_HOST'] = isset( $_ENV['HTTP_HOST'] ) ? $_ENV['HTTP_HOST'] : 'localhost:7000';
}

if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {
    define( 'WP_SITEURL', 'https://' . $_SERVER['HTTP_HOST'] . '/' );
    define( 'WP_HOME', 'https://' . $_SERVER['HTTP_HOST'] . '/' );
} else {
    define( 'WP_SITEURL', 'http://' . $_SERVER['HTTP_HOST'] . '/' );
    define( 'WP_HOME', 'http://' . $_SERVER['HTTP_HOST'] . '/' );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

define( 'WP_AUTO_UPDATE_CORE', false );

