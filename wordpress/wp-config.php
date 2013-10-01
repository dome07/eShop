<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'eshop');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '123456');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY',         'I*M8*lm?=U`}DoOZ#el[Qz:=A!YZmmk3x}DGxW9^PQTWo4yy^x*]Yb,-Q4;]1-}+');
define('SECURE_AUTH_KEY',  '>QELOs!vWg<u*{4e9Zdj|tJe>tC&7C `wxV(a4zt|y+#&bFEr3jCyFyE[:5duFV!');
define('LOGGED_IN_KEY',    '-)%}6}]x3d^/TNS~mSk%TFq`L%nK5Z&vP<3h<CNsObik*P9c/+yL}g;oqZ9%y,1-');
define('NONCE_KEY',        'hbIun1:q<cu^7?|8e&G=$3|L-Mrr+4&:P{./:,A|w7nSLH-$Q=yj_q_(xqf7|k&U');
define('AUTH_SALT',        'L7uenA#{oj>fR@%Mvaw`EPfMh|jfm2$mJ%q(|3eJ5*3e2Z`#S?_b` Hte_;{Q+jt');
define('SECURE_AUTH_SALT', 'p>4!<blh|kk-A01S,YP-(Mc+,i:Vv<r13YqrLD%U-*puOkIjj|y--Cc=mT$Y_qU3');
define('LOGGED_IN_SALT',   '_aSY(lt0rY:G&od`L];UG<xL:#9L=aN.9_/lP}Et=|G/ja||2SQJ,WbL4+q]&n5y');
define('NONCE_SALT',       'G;Y6|@B{mUA:-,8,6RkBT_zTRgm[`Wnb]@ekrwz`,!-1@{&B-(A;8R+]pKF^SDl,');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
