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
Domain Path: /i18n/languages
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
			<select id="pudocityselect" style="width:100%">
				<option value="" disabled selected><?php echo __("Select City","orian-shipping-carrier"); ?></option>
			</select>
		</p>
		<p class="form-row form-row-wide">
			<select name="pudo_point" id="pudopointselect" style="width:100%">
				<option value="" disabled selected><?php echo __("Select Pudo","orian-shipping-carrier"); ?></option>
			</select>
		</p>
		<input type="hidden" name="pudo_details" id="pudo_details">
		<input type="hidden" name="pudo_shipping" value="yes">
		<div id="selectedpudodetails">
		</div>
	</div>
	<?php
	endif;
}
add_filter('woocommerce_checkout_posted_data','osc_add_pudo_data_to_validation');
function osc_add_pudo_data_to_validation($data) {
	$data["pudo_shipping"] = $_POST["pudo_shipping"];
	$data["pudo_point"] = $_POST["pudo_point"];
	return $data;
}
add_action('woocommerce_after_checkout_validation', 'osc_verify_pudo_fields',10,2);
add_action('woocommerce_goya_child_shipping_validation', 'osc_verify_pudo_fields',10,2);
function osc_verify_pudo_fields(&$data, &$errors) {
	if ($data["pudo_shipping"] && ! $data["pudo_point"] && ! $data["billing_validate"])
		$errors->add( 'pudo', __( 'Pudo Location not selected. Please select a location or choose a different shipping method.', 'orian-shipping-carrier' ) );
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
		sort($pudo_cities);
	?>
	<script>
		jQuery(document).on('select2:open', (e) => {
			setTimeout(function() {jQuery('.select2-search__field').focus();},500);
      	});
		var pudo_cities = <?php echo json_encode($pudo_cities,JSON_UNESCAPED_UNICODE); ?>;
		var pudo_ids = <?php echo json_encode($pudo_ids,JSON_UNESCAPED_UNICODE); ?>;
		var pudo_points = [];
		//console.log(pudo_ids['city1']);
		//console.log(pudo_ids);
		jQuery(document.body).on('updated_checkout',function() {
			for(var i = 0; i < pudo_cities.length; i++) {
				jQuery("#pudocityselect").append('<option value="city'+i+'">'+pudo_cities[i]+'</option>');
			}
			jQuery("#pudocityselect").select2();
			jQuery("#pudopointselect").select2();
			jQuery("#pudocityselect").on('change',function() {
				jQuery("#selectedpudodetails").html('');
				pudo_points = pudo_ids[jQuery(this).val()];
				jQuery("#pudopointselect").html('<option value="" disabled selected><?php echo __("Select Pudo","orian-shipping-carrier"); ?></option>');
				for(var i = 0; i < pudo_points.length; i++) {
					jQuery("#pudopointselect").append('<option value="'+pudo_points[i]['contactid']+'">'+pudo_points[i]['pudoname']+' - '+pudo_points[i]['pudoaddress']+'</option>');
				}
				jQuery("#pudopointselect").select2();
			});
			jQuery("#pudopointselect").on('change',function() {
				var pointval = jQuery(this).val();
				for(var i = 0; i < pudo_points.length; i++) {
					var pudocontactid = pudo_points[i]['contactid'];
					if (pointval == pudocontactid) {
						var pudotype = "";
						if (pudo_points[i]['pudotype'] == 1)
						pudotype = "Store";
						else if (pudo_points[i]['pudotype'] == 2)
						pudotype = "Locker";
						var accessibility = "";
						if (pudo_points[i]['accessibility'] === "True")
							accessibility = "Yes";
						else
							accessibility = "No";
						var pudo_details = '<p><?php _e("Pudo Name","orian-shipping-carrier"); ?>: '+pudo_points[i]['pudoname']+'</p>';
						pudo_details += '<p><?php _e("Pudo Address","orian-shipping-carrier"); ?>: '+pudo_points[i]['pudoaddress']+'</p>';
						pudo_details += '<p><?php _e("Pudo City","orian-shipping-carrier"); ?>: '+pudo_points[i]['pudocity']+'</p>';
						pudo_details += '<p><?php _e("Pudo Type","orian-shipping-carrier"); ?>: '+pudotype+'</p>';
						if (pudotype === "Store") {
							var workinghours = "";
							var workinghoursarr = pudo_points[i]['workinghours'];
							var days = [
								'<?php _e("Sunday","orian-shipping-carrier"); ?>',
								'<?php _e("Monday","orian-shipping-carrier"); ?>',
								'<?php _e("Tuesday","orian-shipping-carrier"); ?>',
								'<?php _e("Wednesday","orian-shipping-carrier"); ?>',
								'<?php _e("Thursday","orian-shipping-carrier"); ?>',
								'<?php _e("Friday","orian-shipping-carrier"); ?>',
								'<?php _e("Saturday","orian-shipping-carrier"); ?>',
							];
							for (var i = 0; i < workinghoursarr.length; i++) {
								workinghours += "<br>"+days[i] + ": " + workinghoursarr[i][0] + " - " + workinghours[i][1];
							}
							pudo_details += '<p><?php _e("Working Hours","orian-shipping-carrier"); ?>: '+workinghours+'</p>';
						} else {
							pudo_details += '<p><?php _e("Working Hours","orian-shipping-carrier"); ?>: 24/7</p>';
						}
						pudo_details += '<p><?php _e("Accessibility","orian-shipping-carrier"); ?>: '+accessibility+'</p>';
						jQuery("#selectedpudodetails").html(pudo_details);
						jQuery("#pudo_details").val(JSON.stringify(pudo_points[i]));
					}
				}
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
	if ( ! empty( $_POST['pudo_details'] ) ) {
		update_post_meta( $order_id, 'pudo_details', $_POST['pudo_details'] );
	}
	$pudo_shipping = false;
	$order = wc_get_order($order_id);
	$order_details = $order->get_data();
    foreach($order->get_items("shipping") as $item_key => $item) {
        if ($item->get_method_id() === orian_shipping()->pudo_method_id)
            $pudo_shipping = true;
    }
    $sla = 0;
    if ($pudo_shipping) {
        $sla = orian_shipping()->sla->pudo_sla;
    } else {
        $selected_city = $order_details['billing']['city'];
        $selected_city_far = array($selected_city,"0");
        if (orian_shipping()->sla->orian_cities) {
            if (in_array($selected_city_far,orian_shipping()->sla->orian_cities)) {
                $sla = orian_shipping()->sla->home_far_sla;
            } else {
				$sla = orian_shipping()->sla->home_regular_sla;
            }
        } else {
			$sla = orian_shipping()->sla->home_regular_sla;
        }
    }
	if ($sla !== 0) {
		update_post_meta($order_id, 'sla', $sla);
	}
}

add_action( 'init', 'osc_load_textdomain' );
function osc_load_textdomain() {
    load_plugin_textdomain( 'orian-shipping-carrier', false, dirname( plugin_basename(OSC_PLUGIN_FILE) ) . '/i18n/languages' ); 
}