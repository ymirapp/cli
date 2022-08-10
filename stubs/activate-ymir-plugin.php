<?php

function ymir_activate_plugin() {
    if (defined('WP_INSTALLING') && WP_INSTALLING) {
        return;
    } elseif (!function_exists('get_plugins')) {
        require_once ABSPATH.'wp-admin/includes/plugin.php';
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
add_action('plugins_loaded', 'ymir_activate_plugin');

/**
 * Ensures that the plugin is always the first one to be loaded per site.
 */
function ymir_ensure_plugin_loaded_first($active_plugins)
{
    if (!is_array($active_plugins)) {
        return $active_plugins;
    }

    foreach ($active_plugins as $key => $basename) {
        if (1 === preg_match('/ymir\.php$/', $basename)) {
            array_splice($active_plugins, $key, 1);
            array_unshift($active_plugins, $basename);
        }
    }

    return $active_plugins;
}
add_filter('pre_update_option_active_plugins', 'ymir_ensure_plugin_loaded_first', PHP_INT_MAX);

/**
 * Ensures that the plugin is always the first one to be loaded for the network.
 */
function ymir_ensure_plugin_loaded_first_on_network($active_plugins)
{
    if (!is_array($active_plugins)) {
        return $active_plugins;
    }

    $active_plugins = array_keys($active_plugins);

    foreach ($active_plugins as $index => $plugin) {
        if (1 === preg_match('/ymir\.php$/', $plugin)) {
            array_splice($active_plugins, $index, 1);
            array_unshift($active_plugins, $plugin);
        }
    }

    return array_fill_keys($active_plugins, time());
}
add_filter('pre_update_site_option_active_sitewide_plugins', 'ymir_ensure_plugin_loaded_first_on_network', PHP_INT_MAX);

/**
 * Removes the Ymir plugin from the list of active plugins to fool network installation.
 */
function ymir_remove_plugin_from_active_plugins_when_installing_network($value)
{
    // Return early if we're not installing the network or didn't deactivate all the other plugins.
    if (!defined('WP_INSTALLING_NETWORK') || !WP_INSTALLING_NETWORK || !is_array($value) || 1 !== count($value)) {
        return $value;
    }

    $backtrace = debug_backtrace();
    $backtrace = end($backtrace);

    return (!isset($backtrace['function']) || 'network_step1' !== $backtrace['function']) ? $value : [];
}
add_filter('option_active_plugins', 'ymir_remove_plugin_from_active_plugins_when_installing_network');

/**
 * Load Ymir plugin right away if it's in "mu-plugins".
 */
function ymir_maybe_load_mu_plugin()
{
    $paths = glob(WPMU_PLUGIN_DIR.'/*/ymir.php');

    foreach ($paths as $path) {
        include_once $path;
    }
}
ymir_maybe_load_mu_plugin();
