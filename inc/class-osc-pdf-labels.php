<?php
if (!class_exists('OSC_PDF_Labels')) {
    class OSC_PDF_Labels {
        public $pdf_file_properties = array(
            'title' => 'Order Label',
            'author' => 'Samiullah Jamil',
            'creator' => 'Orian Shipping Carrier Plugin',
            'subject' => 'Orian Order Labels',
        );
        public function create_order_labels_pdf($orderid) {
            $order = wc_get_order($orderid);
            $data = array("orderid" => $orderid);
            $uploads_dir = wp_upload_dir();
            $labels_pdf_dir = $uploads_dir['basedir'] . '/orian-labels/';
            if(!is_dir($labels_pdf_dir)) {
                mkdir($labels_pdf_dir);
            }
            $filename = 'order-label-KST' . $orderid . '.pdf';
            $filepath = $labels_pdf_dir . '/order-label-KST' . $orderid . '.pdf';
            $barcodestyle = array(
                'position'=>'C',
                'text'=>true,
                'stretchtext'=>1,
                'fitwidth' => false,
            );
            $pdf = new TCPDF('p', 'mm', array(103,164), true, 'UTF-8', false);
            $pdf->SetCreator($this->$pdf_file_properties['creator']);
            $pdf->SetAuthor($this->$pdf_file_properties['author']);
            $pdf->SetTitle($this->$pdf_file_properties['title']);
            $pdf->SetSubject($this->$pdf_file_properties['subject']);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(5, 5, 5);
            $pdf->SetAutoPageBreak(FALSE);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $l = Array();
            $l['a_meta_charset'] = 'UTF-8';
            $l['a_meta_dir'] = 'rtl';
            $l['a_meta_language'] = 'he';
            $l['w_page'] = 'מקור:';
            $pdf->setLanguageArray($l);
            $pdf->setFontSubsetting(true);
            $pdf->SetFont('heebo', '', 12, '', false);
            $pdf->AddPage();
            $pdf->Image('/var/www/example-wordpress-dev.com/test-logo.png',2.5,0,'98','','');
            $pdf->SetLineStyle(array('width' => 0.7, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
            $pdf->RoundedRect(2.5, 2.5, $pdf->getPageWidth() - 5, $pdf->getPageHeight() - 5, 7, '1111');
            $html = "<hr>";
            $pdf->writeHTMLCell(0, 0, '', '30', $html, 0, 1, 0, true, '', true);
            $this->add_pdf_main_section($pdf, $data);
            $html = "<hr>";
            $pdf->writeHTMLCell(0, 0, '', '75', $html, 0, 1, 0, true, '', true);
            $this->add_pdf_note_section($pdf, $data);
            $html = "<hr>";
            $pdf->writeHTMLCell(0, 0, '', '106', $html, 0, 1, 0, true, '', true);
            $this->add_pdf_extra_section($pdf, $data);
            $html = "<hr>";
            $pdf->writeHTMLCell(0, 0, '', '134', $html, 0, 1, 0, true, '', true);
            $pdf->write1DBarcode('12346','C128','','140','55','12',0.7,$barcodestyle,'N');
            $pdf_string = $pdf->Output($filename, 'S');
            file_put_contents($filepath, $pdf_string);
        }
        public function add_pdf_main_section($pdf,$data = array()) {
            $pdf->SetFont('heebomedium', '', 15, '', false);
            $html = "<p>עבור: {SITENAME}</p>";
            $pdf->writeHTMLCell(0, 0, '', '34.5', $html, 0, 1, 0, true, '', true);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = "<p>רחוב: {STREET1}</p>";
            $pdf->writeHTMLCell(0, 0, '', '40.5', $html, 0, 1, 0, true, '', true);
            $html = "<p>עיר: {CITY}</p>";
            $pdf->writeHTMLCell(0, 0, '', '45.5', $html, 0, 1, 0, true, '', true);
            $html = "<p>איש קשר: {CONTACT1NAME} | טלפון: {CONTACT1PHONE}</p>";
            $pdf->writeHTMLCell(0, 0, '', '50.5', $html, 0, 1, 0, true, '', true);
            $html = "<p>קומה: {billing_floor} | דירה: {billing_apartmnt}</p>";
            $pdf->writeHTMLCell(0, 0, '', '60.5', $html, 0, 1, 0, true, '', true);
            $html = "<p>קוד לאינטרקום: {billing_intercom_code}</p>";
            $pdf->writeHTMLCell(0, 0, '', '65.5', $html, 0, 1, 0, true, '', true);
        }
        public function add_pdf_note_section($pdf,$data = array()) {
            $pdf->SetFont('heebomedium', '', 15, '', false);
            $html = "<p>הערות:</p>";
            $pdf->writeHTMLCell(0, 0, '', '77', $html, 0, 1, 0, true, '', true);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = "<p>{shipping_remarks max lengh = 100 letters}</p>";
            $pdf->writeHTMLCell(0, 0, '', '83', $html, 0, 1, 0, true, '', true);
        }
        public function add_pdf_extra_section($pdf,$data = array()) {
            $pdf->SetFont('heebomedium', '', 12, '', false);
            $html = "<p>חבילה 1 מתוך 1</p>";
            $pdf->writeHTMLCell(0, 0, '', '109.5', $html, 0, 1, 0, true, '', true);
            $pdf->SetFont('heebomedium', '', 12, '', false);
            $html = "סוג משלוח: ";
            $pdf->Write(1,$html);
            $html = "{delivery type}\n";
            $pdf->SetFont('heebo', '', 12, '', false);
            $pdf->Write(1,$html);
            $pdf->SetFont('heebomedium', '', 12, '', false);
            $html = "מס׳ הזמנה באתר: ";
            $pdf->Write(1,$html);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = "{REFERENCEORDER1}\n";
            $pdf->Write(1,$html);
            $pdf->SetFont('heebomedium', '', 12, '', false);
            $html = "מס׳ חשבונית: ";
            $pdf->Write(1,$html);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = "{REFERENCEORDER2}\n";
            $pdf->Write(1,$html);
            //$pdf->writeHTMLCell(0, 0, '', '114.5', $html, 0, 1, 0, true, '', true);
        }
    }
}