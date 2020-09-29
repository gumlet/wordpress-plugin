<?php
/**
 * WordPress plugin for gumlet service.
 *
 * @package gumlet-wordpress
 * @author adityapatadia
 * @license BSD-2
 *
 * @wordpress-plugin
 *
 * Plugin Name: Gumlet
 * Plugin URI:  https://github.com/gumlet/wordpress-plugin
 * Description: A WordPress plugin to automatically load all your existing (and future) WordPress images via the <a href="http://www.gumlet.com" target="_blank">Gumlet</a> service for smaller, faster, and better looking images.
 * Version:     1.1.43
 * Author:      Gumlet
 * Author URI:  https://www.gumlet.com
 */

if (!defined('GUMLET_DEBUG')) {
    define('GUMLET_DEBUG', isset($_GET['GUMLET_DEBUG']) ? $_GET['GUMLET_DEBUG'] : false);
}
if (GUMLET_DEBUG & 2) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

include('includes/compability.php');
include('includes/logger.php');
include('includes/class-gumlet.php');
include('includes/options-page.php');


function gumlet_plugin_admin_action_links($links, $file)
{
    if ($file === plugin_basename(__FILE__)) {
        $settings_link = '<a href="options-general.php?page=gumlet-options">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

add_filter('plugin_action_links', 'gumlet_plugin_admin_action_links', 10, 2);

register_activation_hook(__FILE__, 'gumlet_plugin_activate');
function gumlet_plugin_activate()
{
    // plugin activation code here...
    if (!get_option('gumlet_settings')) {
        update_option('gumlet_settings', ["lazy_load" => 1, "original_images" => 1, "auto_compress"=> 1]);
    }
}
