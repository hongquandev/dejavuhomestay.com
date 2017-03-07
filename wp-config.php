<?php
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
define('DB_NAME', 'dejavu_db');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'SR~_~N}m{K4@ G?jL Ld87T]%i8C}m.+.- !5(tNyBa])Vmd._n^3-d6Hn*JkB|y');
define('SECURE_AUTH_KEY',  '6$0[(rZgH%K1a|d|z6^i.<KF-x6L i2et1TLNb/kFy_a%o%Ei.8t^|$ST@+(+fS}');
define('LOGGED_IN_KEY',    'w K<>4<rcS=;{+6a-m,8~eVey9 DMa5Xmyr_P62|>HIG>14NlF}QtY|&qerTn!Y+');
define('NONCE_KEY',        'nD3(Ra+]|%YG^4731av`2d-ymA|&B- %ib74&u(=@;qrV$zQL x$LGA>vV!MI2k6');
define('AUTH_SALT',        'b;`?3>A&BT[cinQ:%88<z,V?]|lj+U5v)K^Ihfby)*MZX-!Z<j}uBbZD8]2v7=?c');
define('SECURE_AUTH_SALT', '&4oG~IcOw-`x&idoW9{Ge?(qRkNHA^qX2)JpymW1(NXKk*|*~|h.)hG$GM#;`Rwf');
define('LOGGED_IN_SALT',   'r;/jfb~jyv>quyn+`f{;;qP|J42g56-8L6c+su5{q+P/Ek<n$GYO.op:666B{0-L');
define('NONCE_SALT',       'OJK-P;@_y^3~!_BCSn#UKwugI.x(<h7(MrVO5c=R3.+CZfRyxR 1&o|7ZaPjUK*R');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
