<?php

/**
 * Plugin Name: Wishlist & Styling
 * Plugin URI: https://www.somethingborrowed.se/
 * Description: SomethinBorrowed wishlist & styling plugin.
 * version: 1.0.1
 * Author: AvedSoft
 * Author URI: http://avedsoft.com
 * Requires at least: 5.1
 * Tested up to: 5.2.4
 * Text Domain: sb-wishlist
 * Domain Path: /languages/
 * 
 * @package SB Wishlist
 */
if (!defined('ABSPATH')) {
    exit;
}

/* Constant version */
define('SBWS_VERSION', '1.0.1');

/* Constant path to plugin directory */
define('SBWS_SLUG', basename(plugin_dir_path(__FILE__)));

/* Constant path to the main file for activation call */
define('SBWS_CORE_FILE', __FILE__);

/* Constant path to plugin directory */
define('SBWS_PATH', trailingslashit(plugin_dir_path(__FILE__)));

/* Constant path to plugin directory */
define('SBWS_URI', trailingslashit(plugin_dir_url(__FILE__)));

if (!version_compare(PHP_VERSION, '5.4', '>=')) {
    add_action('admin_notices', 'sbws_fail_php_version');
} elseif (SBWS_SLUG !== 'sb-wishlist') {
    add_action('admin_notices', 'sbws_fail_installation_method');
} else {

    if (!function_exists('is_plugin_active_for_network')) {
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    }
    define('SBWS_ACTIVATED', is_plugin_active(SBWS_SLUG . '/sb-wishlist.php'));

    require_once SBWS_PATH . 'inc/class-sb-wishlist.php';

    if (!function_exists('sbws_init')) :
        function sbws_init() {
            return SB_Wishlist::instance();
        }
    endif;
    sbws_init();
    sbws_form();
}

if ( ! function_exists( 'sbws_fail_php_version' ) ) {
    function sbws_fail_php_version() {
        $message = esc_html__( 'SomethingBorrowed wishlist plugin requires PHP version 5.4+, plugin is currently NOT ACTIVE. Please contact the hosting provider to upgrade the version of PHP.', 'sb-wishlist' );
        $html_message = sprintf( '<div class="notice notice-error">%s</div>', wpautop($message) );
        echo wp_kses_post( $html_message );
    }
}

if ( ! function_exists( 'sbws_fail_installation_method' ) ) {
    function sbws_fail_installation_method() {
        $message = esc_html__( 'SomethingBorrowed wishlist plugin is not installed correctly.', 'sb-wishlist' );
        $html_message = sprintf( '<div class="notice notice-error">%s</div>', wpautop($message) );
        echo wp_kses_post( $html_message );
    }
}
