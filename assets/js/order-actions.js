function update_number_of_packages(elem, orderid) {
	jQuery('#numberofpackagesbox').block({message:null,overlayCSS:{opacity:.6}});
    var data = {
        'action': 'package_number_update',
        'orderid': orderid,
        'numberofpackages': elem.value
    };
    jQuery.post(ajax_object.ajax_url, data, function(response) {
        jQuery('#numberofpackagesbox').unblock();
    });
}
function osc_order_send() {
    jQuery(".order_actions select[name=wc_order_action]").val("osc_send_order_to_carrier");
    jQuery(".order_actions .wc-reload").trigger("click");
}
function osc_send_order_bulk() {
    jQuery("#bulk-action-selector-top").val("osc_send_orders");
    jQuery("#doaction").trigger("click");
}