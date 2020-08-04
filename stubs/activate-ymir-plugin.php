<?php

function activate_ymir_plugin() {
    if (defined('WP_INSTALLING') && WP_INSTALLING) {
        return;
    } elseif (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Important to not activate the plugin if it's already active. It's fine for normal sites, but
    // it breaks the backbone media uploader with multisite due to a weird edgecase with the "networkwide"
    // query value being set globally.
    foreach (get_plugins() as $file => $plugin) {
        if (1 !== preg_match('/ymir\.php$/', $file)) {
            continue;
        } elseif (defined('MULTISITE') && MULTISITE && !is_plugin_active_for_network($file)) {
            activate_plugin($file, '', true);
        } elseif ((!defined('MULTISITE') || !MULTISITE) && !is_plugin_active($file)) {
            activate_plugin($file);
        }
    }
}
add_action('plugins_loaded', 'activate_ymir_plugin');
