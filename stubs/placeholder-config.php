<?php

/**
 * Placeholder WordPress configuration
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

define('WP_HOME', getenv('WP_HOME'));
define('WP_SITEURL', getenv('WP_SITEURL'));
