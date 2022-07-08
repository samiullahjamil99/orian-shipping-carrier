function osc_pdf_generate(clickel,orderid) {
    var data = {
        'action': 'osc_generate_pdf',
        'orderid': orderid,
    };
    jQuery(clickel).block({message:null,overlayCSS:{opacity:.6}});
    jQuery.post(ajax_object.ajax_url, data, function(response) {
        if (response) {
        var pdfWindow = window.open("");
        pdfWindow.document.write(
            "<iframe width='100%' height='100%' src='data:application/pdf;base64, "+response+"'></iframe>"
        );
        }
        jQuery(clickel).unblock();
    });
}
function osc_pdf_generate_bulk(clickel) {
    var orders = [];
    jQuery("input[name='post[]']").each(function (index, obj) {
        if (obj.checked)
            orders.push(obj.value);
    });
    var data = {
        'action': 'osc_generate_pdf',
        'orders': orders,
    };
    jQuery(clickel).block({message:null,overlayCSS:{opacity:.6}});
    jQuery.post(ajax_object.ajax_url, data, function(response) {
        if (response) {
        var pdfWindow = window.open("");
        pdfWindow.document.write(
            "<iframe width='100%' height='100%' src='data:application/pdf;base64, "+response+"'></iframe>"
        );
        }
        jQuery(clickel).unblock();
    });
}