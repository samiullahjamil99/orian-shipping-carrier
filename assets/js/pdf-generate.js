function osc_pdf_generate(clickel,orderid) {
    var data = {
        'action': 'osc_generate_pdf',
        'orderid': orderid,
    };
    clickel.innerHTML = 'Generating..';
    jQuery.post(ajax_object.ajax_url, data, function(response) {
        var pdfWindow = window.open("");
        pdfWindow.document.write(
            "<iframe width='100%' height='100%' src='data:application/pdf;base64, "+response+"'></iframe>"
        );
        clickel.innerHTML = 'Regenerate PDF Labels';
    });
}