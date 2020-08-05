<?php

/**
 * Ymir WordPress configuration
 */

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST'));
}

if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME'));
}

if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER'));
}

if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', getenv('DB_PASSWORD'));
}

define('AUTH_KEY', getenv('AUTH_KEY'));
define('SECURE_AUTH_KEY', getenv('SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY', getenv('LOGGED_IN_KEY'));
define('NONCE_KEY', getenv('NONCE_KEY'));
define('AUTH_SALT', getenv('AUTH_SALT'));
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT', getenv('LOGGED_IN_SALT'));
define('NONCE_SALT', getenv('NONCE_SALT'));

define('DOMAIN_CURRENT_SITE', getenv('DOMAIN_CURRENT_SITE'));

define('WP_HOME', getenv('WP_HOME'));
define('WP_SITEURL', getenv('WP_SITEURL'));
