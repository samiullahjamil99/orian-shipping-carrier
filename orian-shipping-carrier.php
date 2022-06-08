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
function osc_shipping_init() {
	include_once dirname(OSC_PLUGIN_FILE) . '/inc/shipping-methods/class-osc-delivery-shipping.php';
	include_once dirname(OSC_PLUGIN_FILE) . '/inc/shipping-methods/class-osc-pudo-shipping.php';
}
add_action( 'woocommerce_shipping_init', 'osc_shipping_init' );

function osc_add_shipping( $methods ) {
	$methods['orian_delivery_shipping'] = 'OSC_Delivery_Shipping';
	$methods['orian_pudo_shipping'] = 'OSC_Pudo_Shipping';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'osc_add_shipping' );
include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-woocommerce-order-status.php';
include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-woocommerce-order-actions.php';
$osc_order_status = new OSC_Woocommerce_Order_Status();
$osc_order_actions = new OSC_Woocommerce_Order_Actions();
}
add_filter('acf/settings/remove_wp_meta_box', '__return_false');