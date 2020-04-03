<?php

function activate_placeholder_plugin() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    foreach (get_plugins() as $file => $plugin) {
        if (preg_match('/placeholder\.php$/', $file)) {
            activate_plugin($file);
        }
    }
}
add_action('plugins_loaded', 'activate_placeholder_plugin');
