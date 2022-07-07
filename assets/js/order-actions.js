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