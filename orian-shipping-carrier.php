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

function osc_pudo_fields_html() {
	if (is_checkout()):
	?>
	<div>
		<p class="form-row form-row-wide">
			<select id="pudocityselect">
				<option value="" disabled selected>Select City</option>
			</select>
		</p>
		<p class="form-row form-row-wide">
			<select name="pudo_point" id="pudopointselect">
				<option value="" disabled selected>Select Pudo</option>
			</select>
		</p>
	</div>
	<?php
	endif;
}

function osc_pudo_script() {
	if (is_checkout()):
		$pudo_details = orian_shipping()->api->get_pudo_points('פתח תקווה');
		$pudos = array();
		if ($pudo_details['status'] == 200)
		$pudos = $pudo_details['data'];
		$pudo_cities = array();
		foreach($pudos as $pudo) {
			if (!in_array($pudo['pudocity'], $pudo_cities))
			$pudo_cities[] = $pudo['pudocity'];
		}
		$pudo_ids = array();
		foreach($pudo_cities as $index => $pudo_city) {
			if (!array_key_exists($index,$pudo_ids)) {
				$pudo_ids['city'.$index] = array();
				foreach($pudos as $pudo) {
					if ($pudo['pudocity'] === $pudo_city)
					$pudo_ids['city'.$index][] = $pudo;
				}
			}
		}
	?>
	<script>
		var pudo_cities = <?php echo json_encode($pudo_cities); ?>;
		var pudo_ids = <?php echo json_encode($pudo_ids); ?>;
		//console.log(pudo_ids['city1']);
		//console.log(pudo_ids);
		jQuery(document.body).on('updated_checkout',function() {
			for(var i = 0; i < pudo_cities.length; i++) {
				jQuery("#pudocityselect").append('<option value="city'+i+'">'+pudo_cities[i]+'</option>');
			}
			jQuery("#pudocityselect").select2();
			jQuery("#pudopointselect").select2();
			jQuery("#pudocityselect").on('change',function() {
				var pudo_points = pudo_ids[jQuery(this).val()];
				jQuery("#pudopointselect").html('<option value="" disabled selected>Select Pudo</option>');
				for(var i = 0; i < pudo_points.length; i++) {
					jQuery("#pudopointselect").append('<option value="'+pudo_points[i]['contactid']+'">'+pudo_points[i]['pudoname']+'</option>');
				}
				jQuery("#pudopointselect").select2();
			});
		});
	</script>
	<?php
	endif;
}
add_action('wp_footer','osc_pudo_script');

add_action( 'woocommerce_checkout_update_order_meta', 'osc_pudo_update_meta' );
function osc_pudo_update_meta( $order_id ) {
    if ( ! empty( $_POST['pudo_point'] ) ) {
        update_post_meta( $order_id, 'pudo_point', sanitize_text_field( $_POST['pudo_point'] ) );
    }
}