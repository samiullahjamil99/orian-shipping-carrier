<?php
if (!class_exists('OSC_Woocommerce_Order_Actions')) {
    class OSC_Woocommerce_Order_Actions {
        public $text_domain = "osc";
        public function __construct() {
            $this->init();
        }
        public function init() {
            add_action( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_actions' ) );
            add_action( 'woocommerce_order_action_osc_send_order_to_carrier', array($this, 'osc_process_carrier_order') );
        }
        public function add_order_meta_box_actions($actions) {
            global $theorder;
            $order_status = $theorder->get_status();
            if ($order_status === "processing")
            $actions['osc_send_order_to_carrier'] = __("Send Order to Carrier",$this->text_domain);
            return $actions;
        }
        public function osc_process_carrier_order($order) {
            $response = osc_api()->generate_transportation_order($order->get_id());
            if ($response['status'] == 200 && $response['success'] === "true")
            $order->update_status("wc-osc-new");
        }
    }
}