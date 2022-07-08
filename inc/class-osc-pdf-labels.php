<?php
if (!class_exists('OSC_PDF_Labels')) {
    class OSC_PDF_Labels {
        public $allowed_statuses = array('processing','osc-new');
        public $pdf_file_properties = array(
            'title' => 'Order Label',
            'author' => 'Samiullah Jamil',
            'creator' => 'Orian Shipping Carrier Plugin',
            'subject' => 'Orian Order Labels',
        );
        public function __construct() {
            $this->init();
        }
        public function init() {
            add_action( 'wp_ajax_osc_generate_pdf', array($this,'ajax_create_package_pdf') );
            add_action( 'admin_enqueue_scripts',array($this,'admin_pdf_scripts') );
            add_action( 'manage_posts_extra_tablenav', array($this,'admin_order_list_top_bar_button'), 20, 1 );
        }
        public function admin_pdf_scripts() {
            wp_enqueue_script( 'pdf-generate', plugin_dir_url(OSC_PLUGIN_FILE) . 'assets/js/pdf-generate.js', array( 'jquery' ) );
            wp_localize_script( 'pdf-generate', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        }
        function admin_order_list_top_bar_button( $which ) {
            global $typenow;

            if ( 'shop_order' === $typenow && 'top' === $which ) {
                ?>
                <div class="alignleft actions custom">
                    <button type="button" onclick="osc_pdf_generate_bulk(this)" name="generate_pdf" style="height:32px;" class="button" value=""><?php
                        echo __( 'Generate PDF Labels', 'woocommerce' ); ?></button>
                </div>
                <?php
            }
        }
        public function create_order_labels($orderid) {
            $fileurls = array();
            $pudoorder = false;
            $pudo_point = get_post_meta($orderid,'pudo_point',true);
            if ($pudo_point)
                $pudoorder = true;
            $numberofpackages = get_post_meta($orderid,'number_of_packages',true);
            if (!$numberofpackages)
                $numberofpackages = "1";
            for($i = 1; $i <= intval($numberofpackages); $i++) {
                $fileurl = $this->create_order_package_pdf($orderid, $i, $pudoorder);
                if ($fileurl)
                    $fileurls[] = $fileurl;
            }
            update_post_meta($orderid, 'pdf_urls',$fileurls);
        }
        public function create_order_package_pdf($orderid, $pudoorder = false) {
            $uploads_dir = wp_upload_dir();
            $labels_pdf_dir = $uploads_dir['basedir'] . '/orian-labels/';
            if(!is_dir($labels_pdf_dir)) {
                mkdir($labels_pdf_dir);
            }
            $filename = 'order-label-' . $orderid . '.pdf';
            $filepath = $labels_pdf_dir . '/' . $filename;
            $fileurl = $uploads_dir['baseurl'] . '/orian-labels/' . $filename;
            $pdf_string = $this->create_order_package_pdf_string($orderid, $pudoorder);
            $filewriten = file_put_contents($filepath, $pdf_string);
            if ($filewriten) {
                return $fileurl;
            } else {
                return false;
            }
        }
        public function ajax_create_package_pdf() {
            if ($_POST['orderid']) {
                $orderid = intval($_POST['orderid']);
                $order = wc_get_order($orderid);
                $pudoorder = false;
                $pudo_point = get_post_meta($orderid,'pudo_point',true);
                if ($pudo_point)
                    $pudoorder = true;
                if (in_array($order->get_status(),$this->allowed_statuses)) {
                    $pdf_string = $this->create_order_package_pdf_string($orderid,$pudoorder);
                    echo base64_encode($pdf_string);
                }
            } elseif ($_POST['orders']) {
                $pdf_string = $this->create_orders_package_pdf_string($_POST['orders']);
                if ($pdf_string)
                echo base64_encode($pdf_string);
            }
            wp_die();
        }
        public function create_orders_package_pdf_string($orderids) {
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
            $pdf->SetAutoPageBreak(TRUE);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $l = Array();
            $l['a_meta_charset'] = 'UTF-8';
            $l['a_meta_dir'] = 'rtl';
            $l['a_meta_language'] = 'he';
            $l['w_page'] = 'מקור:';
            $pdf->setLanguageArray($l);
            $pdf->setFontSubsetting(true);
            $pdf->SetFont('heebo', '', 12, '', false);
            foreach($orderids as $orderid) {
                $orderid = intval($orderid);
                $pudoorder = false;
                $pudo_point = get_post_meta($orderid,'pudo_point',true);
                if ($pudo_point)
                   $pudoorder = true;
                $order = wc_get_order($orderid);
                if (in_array($order->get_status(),$this->allowed_statuses)):
                $order_details = $order->get_data();
                $packagenumber = 1;
                $pudodetails = false;
                if ($pudoorder) {
                    $pudodetailsstr = get_post_meta($orderid,'pudo_details',true);
                    if ($pudodetailsstr)
                        $pudodetails = json_decode($pudodetailsstr);
                }
                $orian_options = get_option('orian_main_setting');
                $ref2 = $orian_options['referenceorder2'];
                if ($ref2)
                $ref2data = get_post_meta($orderid,$ref2,true);
                $numberofpackages = get_post_meta($orderid,'number_of_packages',true);
                if (!$numberofpackages)
                $numberofpackages = '1';
                $billing_floor = get_post_meta($orderid,'billing_floor',true);
                $billing_apartment = get_post_meta($orderid,'billing_apartment',true);
                $billing_intercom_code = get_post_meta($orderid,'billing_intercom_code',true);
                $billing_business_name = get_post_meta($orderid,'billing_business_name',true);
                if ($pudodetails)
                    $billing_business_name = $pudodetails->pudoname;
                $shipping_remarks = get_post_meta($orderid,'shipping_remarks',true);
                for ($packagenumber = 1; $packagenumber <= intval($numberofpackages); $packagenumber++) {
                    $packageid = "KKO" . $orderid;
                    if ($packagenumber > 1)
                        $packageid .= "P".$packagenumber;
                    $pdf->AddPage();
                    $osc_options = get_option('orian_main_setting');
                    if ($osc_options) {
                        $top_image_id = $osc_options['label_logo'];
                        if ($top_image_id) {
                            $top_image_path = wp_get_original_image_path($top_image_id);
                            $pdf->Image($top_image_path,2.5,0,'98','','');
                        }
                    }
                    $pdf->SetLineStyle(array('width' => 0.7, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
                    $pdf->RoundedRect(2.5, 2.5, $pdf->getPageWidth() - 5, $pdf->getPageHeight() - 5, 7, '1111');
                    $html = "<hr>";
                    $pdf->writeHTMLCell(0, 0, '', '30', $html, 0, 1, 0, true, '', true);
                    $data = array(
                        "billing_address" => str_replace('﻿', '', $order_details['billing']['address_1'] ),
                        "billing_city" => str_replace('﻿', '', $order_details['billing']['city'] ),
                        "contact1name" => str_replace('﻿', '', $order_details['billing']['first_name'] . " " . $order_details['billing']['last_name'] ),
                        "contact1phone" => str_replace('﻿', '', $order_details['billing']['phone'] ),
                    );
                    if ($pudodetails) {
                        $data['billing_address'] = $pudodetails->pudoaddress;
                        $data['billing_city'] = $pudodetails->pudocity;
                        $data['pudocontactid'] = $pudodetails->contactid;
                    }
                    if ($billing_business_name)
                        $data['billing_business_name'] = str_replace('﻿', '', $billing_business_name);
                    else
                        $data['billing_business_name'] = $data['contact1name'];
                    if (!$pudoorder) {
                    if ($billing_floor)
                        $data['billing_floor'] = str_replace('﻿', '', $billing_floor);
                    else
                        $data['billing_floor'] = __("none","orian-shipping-carrier");
                    if ($billing_apartment)
                        $data['billing_apartment'] = str_replace('﻿', '', $billing_apartment);
                    else
                        $data['billing_apartment'] = __("none","orian-shipping-carrier");
                    if ($billing_intercom_code)
                        $data['billing_intercom_code'] = str_replace('﻿', '', $billing_intercom_code);
                    else
                        $data['billing_intercom_code'] = __("none","orian-shipping-carrier");
                    }
                    $this->add_pdf_main_section($pdf, $data);
                    $html = "<hr>";
                    $pdf->writeHTMLCell(0, 0, '', '75', $html, 0, 1, 0, true, '', true);
                    $data = array();
                    if ($shipping_remarks)
                        $data['shipping_remarks'] = $shipping_remarks;
                    else
                        $data['shipping_remarks'] = __("none","orian-shipping-carrier");
                    $this->add_pdf_note_section($pdf, $data);
                    $html = "<hr>";
                    $pdf->writeHTMLCell(0, 0, '', '106', $html, 0, 1, 0, true, '', true);
                    $data = array();
                    $data['delivery_type'] = __("Home Delivery","orian-shipping-carrier");
                    if ($pudoorder)
                        $data['delivery_type'] = __("Pudo","orian-shipping-carrier");
                    $data['ref1'] = $orderid;
                    if ($ref2data)
                        $data['ref2'] = $ref2data;
                    $data['numberofpackages'] = $numberofpackages;
                    $data['packagenumber'] = $packagenumber;
                    $this->add_pdf_extra_section($pdf, $data);
                    $html = "<hr>";
                    $pdf->writeHTMLCell(0, 0, '', '134', $html, 0, 1, 0, true, '', true);
                    $pdf->write1DBarcode($packageid,'C128','','140','55','12',0.7,$barcodestyle,'N');
                }
                endif;
            }
            $filename = 'pdf-labels.pdf';
            $pdf_string = $pdf->Output($filename, 'S');
            return $pdf_string;
        }
        public function create_order_package_pdf_string($orderid, $pudoorder = false) {
            $order = wc_get_order($orderid);
            $order_details = $order->get_data();
            $packagenumber = 1;
            $pudodetails = false;
            if ($pudoorder) {
                $pudodetailsstr = get_post_meta($orderid,'pudo_details',true);
                if ($pudodetailsstr)
                    $pudodetails = json_decode($pudodetailsstr);
            }
            $orian_options = get_option('orian_main_setting');
            $ref2 = $orian_options['referenceorder2'];
            if ($ref2)
            $ref2data = get_post_meta($orderid,$ref2,true);
            $numberofpackages = get_post_meta($orderid,'number_of_packages',true);
            if (!$numberofpackages)
            $numberofpackages = '1';
            $billing_floor = get_post_meta($orderid,'billing_floor',true);
            $billing_apartment = get_post_meta($orderid,'billing_apartment',true);
            $billing_intercom_code = get_post_meta($orderid,'billing_intercom_code',true);
            $billing_business_name = get_post_meta($orderid,'sitename_business_type_target',true);
            $billing_business_type_address = get_post_meta($orderid,'billing_business_type_address',true);
            if ($pudodetails)
                $pudo_name = $pudodetails->pudoname;
            $shipping_remarks = get_post_meta($orderid,'shipping_remarks',true);
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
            $pdf->SetAutoPageBreak(TRUE);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $l = Array();
            $l['a_meta_charset'] = 'UTF-8';
            $l['a_meta_dir'] = 'rtl';
            $l['a_meta_language'] = 'he';
            $l['w_page'] = 'מקור:';
            $pdf->setLanguageArray($l);
            $pdf->setFontSubsetting(true);
            $pdf->SetFont('heebo', '', 12, '', false);
            for ($packagenumber = 1; $packagenumber <= intval($numberofpackages); $packagenumber++) {
            $packageid = "KKO" . $orderid;
            if ($packagenumber > 1)
                $packageid .= "P".$packagenumber;
            $pdf->AddPage();
            $osc_options = get_option('orian_main_setting');
            if ($osc_options) {
            $top_image_id = $osc_options['label_logo'];
            if ($top_image_id) {
            $top_image_path = wp_get_original_image_path($top_image_id);
            $pdf->Image($top_image_path,2.5,0,'98','','');
            }
            }
            $pdf->SetLineStyle(array('width' => 0.7, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
            $pdf->RoundedRect(2.5, 2.5, $pdf->getPageWidth() - 5, $pdf->getPageHeight() - 5, 7, '1111');
            $html = "<hr>";
            $pdf->writeHTMLCell(0, 0, '', '30', $html, 0, 1, 0, true, '', true);
            $data = array(
                "billing_address" => str_replace('﻿', '', $order_details['billing']['address_1'] ),
                "billing_city" => str_replace('﻿', '', $order_details['billing']['city'] ),
                "contact1name" => str_replace('﻿', '', $order_details['billing']['first_name'] . " " . $order_details['billing']['last_name'] ),
                "contact1phone" => str_replace('﻿', '', $order_details['billing']['phone'] ),
            );
            if ($pudodetails) {
                $data['billing_address'] = $pudodetails->pudoaddress;
                $data['billing_city'] = $pudodetails->pudocity;
                $data['pudocontactid'] = $pudodetails->contactid;
            }
            if ($billing_business_type_address === "1" && !$pudoorder)
                $data['billing_business_name'] = str_replace('﻿', '', $billing_business_name);
            elseif ($pudoorder)
                $data['billing_business_name'] = str_replace('﻿', '', $pudo_name);
            else
                $data['billing_business_name'] = $data['contact1name'];
            if (!$pudoorder) {
            if ($billing_floor)
                $data['billing_floor'] = str_replace('﻿', '', $billing_floor);
            else
                $data['billing_floor'] = __("none","orian-shipping-carrier");
            if ($billing_apartment)
                $data['billing_apartment'] = str_replace('﻿', '', $billing_apartment);
            else
                $data['billing_apartment'] = __("none","orian-shipping-carrier");
            if ($billing_intercom_code)
                $data['billing_intercom_code'] = str_replace('﻿', '', $billing_intercom_code);
            else
                $data['billing_intercom_code'] = __("none","orian-shipping-carrier");
            }
            $this->add_pdf_main_section($pdf, $data);
            $html = "<hr>";
            $pdf->writeHTMLCell(0, 0, '', '75', $html, 0, 1, 0, true, '', true);
            $data = array();
            if ($shipping_remarks)
                $data['shipping_remarks'] = $shipping_remarks;
            else
                $data['shipping_remarks'] = __("none","orian-shipping-carrier");
            $this->add_pdf_note_section($pdf, $data);
            $html = "<hr>";
            $pdf->writeHTMLCell(0, 0, '', '106', $html, 0, 1, 0, true, '', true);
            $data = array();
            $data['delivery_type'] = __("Home Delivery","orian-shipping-carrier");
            if ($pudoorder)
                $data['delivery_type'] = __("Pudo","orian-shipping-carrier");
            $data['ref1'] = $orderid;
            if ($ref2data)
                $data['ref2'] = $ref2data;
            $data['numberofpackages'] = $numberofpackages;
            $data['packagenumber'] = $packagenumber;
            $this->add_pdf_extra_section($pdf, $data);
            $html = "<hr>";
            $pdf->writeHTMLCell(0, 0, '', '134', $html, 0, 1, 0, true, '', true);
            $pdf->write1DBarcode($packageid,'C128','','140','55','12',0.7,$barcodestyle,'N');
            }
            $filename = 'pdf-label.pdf';
            $pdf_string = $pdf->Output($filename, 'S');
            return $pdf_string;
        }
        public function add_pdf_main_section($pdf,$data = array()) {
            $pdf->SetFont('heebomedium', '', 15, '', false);
            $html = "<p>עבור: ".substr($data['billing_business_name'],0,29)."</p>";
            $pdf->writeHTMLCell(0, 0, '', '34.5', $html, 0, 1, 0, true, '', true);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = "<p>רחוב: ".substr($data['billing_address'],0,37)."</p>";
            $pdf->writeHTMLCell(0, 0, '', '40.5', $html, 0, 1, 0, true, '', true);
            $html = "<p>עיר: ".substr($data['billing_city'],0,37)."</p>";
            $pdf->writeHTMLCell(0, 0, '', '45.5', $html, 0, 1, 0, true, '', true);
            $html = "<p>איש קשר: ". substr($data['contact1name'],0,14) ." | טלפון: ". substr($data['contact1phone'],0,11) ."</p>";
            $pdf->writeHTMLCell(0, 0, '', '50.5', $html, 0, 1, 0, true, '', true);
            if ($data['pudocontactid']) {
            $html = "<p>מזהה נקודת איסוף: ". $data['pudocontactid'] ."</p>";
            $pdf->writeHTMLCell(0, 0, '', '55.5', $html, 0, 1, 0, true, '', true);
            }
            $html = "";
            if ($data['billing_floor'])
            $html = "קומה: ".$data['billing_floor'];
            if ($html !== "" && $data['billing_apartment'])
            $html .= " | דירה: " . $data['billing_apartment'];
            elseif ($data['billing_apartment'])
            $html = "דירה: " . $data['billing_apartment'];
            $pdf->writeHTMLCell(0, 0, '', '60.5', $html, 0, 1, 0, true, '', true);
            if ($data['billing_intercom_code']) {
            $html = "<p>קוד לאינטרקום: ".$data['billing_intercom_code']."</p>";
            $pdf->writeHTMLCell(0, 0, '', '65.5', $html, 0, 1, 0, true, '', true);
            }
        }
        public function add_pdf_note_section($pdf,$data = array()) {
            $pdf->SetFont('heebomedium', '', 15, '', false);
            $html = "<p>הערות:</p>";
            $pdf->writeHTMLCell(0, 0, '', '77', $html, 0, 1, 0, true, '', true);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = "<p>".substr($data['shipping_remarks'],0,100)."</p>";
            $pdf->writeHTMLCell(0, 0, '', '83', $html, 0, 1, 0, true, '', true);
        }
        public function add_pdf_extra_section($pdf,$data = array()) {
            $pdf->SetFont('heebomedium', '', 12, '', false);
            $html = "<p>חבילה ".$data['packagenumber']." מתוך ".$data['numberofpackages']."</p>";
            $pdf->writeHTMLCell(0, 0, '', '109.5', $html, 0, 1, 0, true, '', true);
            $pdf->SetFont('heebomedium', '', 12, '', false);
            $html = "סוג משלוח: ";
            $pdf->Write(1,$html);
            $html = $data["delivery_type"] . "\n";
            $pdf->SetFont('heebo', '', 12, '', false);
            $pdf->Write(1,$html);
            $pdf->SetFont('heebomedium', '', 12, '', false);
            $html = "מס׳ הזמנה באתר: ";
            $pdf->Write(1,$html);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = $data['ref1']."\n";
            $pdf->Write(1,$html);
            if ($data['ref2']) {
            $pdf->SetFont('heebomedium', '', 12, '', false);
            $html = "מס׳ חשבונית: ";
            $pdf->Write(1,$html);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = $data['ref2']."\n";
            $pdf->Write(1,$html);
            }
            //$pdf->writeHTMLCell(0, 0, '', '114.5', $html, 0, 1, 0, true, '', true);
        }
    }
}