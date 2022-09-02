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
            wp_enqueue_script( 'pdf-generate', plugin_dir_url(OSC_PLUGIN_FILE) . 'assets/js/pdf-generate.js', array( 'jquery' ),'1.0.2' );
            wp_localize_script( 'pdf-generate', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        }
        function admin_order_list_top_bar_button( $which ) {
            global $typenow;

            if ( 'shop_order' === $typenow && 'top' === $which && in_array(substr($_GET['post_status'],3),$this->allowed_statuses) ) {
                ?>
                <div class="alignleft actions custom">
                    <button type="button" onclick="osc_pdf_generate_bulk(this)" name="generate_pdf" style="height:32px;" class="button" value=""><?php
                        _e( 'Generate PDF Labels', 'orian-shipping-carrier' ); ?></button>
                </div>
                <?php
            }
        }
        public function ajax_create_package_pdf() {
            //global $locale;
            //$locale = "he_IL";
            add_filter("plugin_locale",function($locale) {
                $locale = "he_IL";
                return $locale;
            });
            load_plugin_textdomain( 'orian-shipping-carrier', false, dirname( plugin_basename(OSC_PLUGIN_FILE) ) . '/i18n/languages' );
            $orders = array();
            if ($_POST['orderid']) {
                $orderid = intval($_POST['orderid']);
                $orders = array($orderid);
            } elseif ($_POST['orders']) {
                $orders = $_POST['orders'];
            }
            if (!empty($orders)) {
                $pdf_string = $this->create_orders_package_pdf_string($orders);
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
                update_post_meta($orderid, 'pdf_label_printed', 'yes');
            }
            $filename = 'pdf-labels.pdf';
            $pdf_string = $pdf->Output($filename, 'S');
            return $pdf_string;
        }
		public function limit_pdf_string_characters($string, $length) {
			//if (strlen($string) > $length)
			$string = mb_substr($string, 0, $length,'UTF-8');

			  return $string;
		}
        public function add_pdf_main_section($pdf,$data = array()) {
            $pdf->SetFont('heebomedium', '', 15, '', false);
            $html = "<p>עבור: ".$this->limit_pdf_string_characters($data['billing_business_name'], 29)."</p>";
            $pdf->writeHTMLCell(0, 0, '', '34.5', $html, 0, 1, 0, true, '', true);
            $pdf->SetFont('heebo', '', 12, '', false);
            $html = "<p>רחוב: ".$this->limit_pdf_string_characters($data['billing_address'], 37)."</p>";
            $pdf->writeHTMLCell(0, 0, '', '40.5', $html, 0, 1, 0, true, '', true);
            $html = "<p>עיר: ".$this->limit_pdf_string_characters($data['billing_city'], 37)."</p>";
            $pdf->writeHTMLCell(0, 0, '', '45.5', $html, 0, 1, 0, true, '', true);
            $html = "<p>איש קשר: ". $this->limit_pdf_string_characters($data['contact1name'], 11) ." | טלפון: ". $this->limit_pdf_string_characters($data['contact1phone'], 11) ."</p>";
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
            $html = "<p>".$this->limit_pdf_string_characters($data['shipping_remarks'], 100)."</p>";
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