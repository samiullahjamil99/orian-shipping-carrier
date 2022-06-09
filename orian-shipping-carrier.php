<?php
/**
 * @package Orian_Shipping_Carrier
 * @version 1.0.0
 */
/*
Plugin Name: Orian Shipping Carrier
Description: This plugin is developed integrate Orian Shipping Carrier API to Woocommerce.
Author: Samiullah Jamil
Version: 1.0.0
Author URI: https://www.samiullahjaml.com/about-me/
*/

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'OSC_PLUGIN_FILE' ) ) {
	define( 'OSC_PLUGIN_FILE', __FILE__ );
}

include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-orian-shipping.php';
function orian_shipping() {
	return Orian_Shipping::instance();
} 
// Added the admin page for api options and other settings
include_once dirname(OSC_PLUGIN_FILE) . '/inc/osc-admin.php';

function osc_api() {
	return orian_shipping()->api;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
$GLOBALS['osc'] = orian_shipping();
}
add_filter('acf/settings/remove_wp_meta_box', '__return_false');