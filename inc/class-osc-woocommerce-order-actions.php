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
            add_filter( 'bulk_actions-edit-shop_order', array($this, 'osc_bulk_actions') );
            add_action( 'admin_action_osc_send_orders', array($this,'bulk_process_send_orders') );
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
        public function osc_bulk_actions( $bulk_actions ) {
            $bulk_actions['osc_send_orders'] = __('Send Orders to Carrier',$this->text_domain);
            return $bulk_actions;
        }
        public function bulk_process_send_orders() {
            if( !isset( $_REQUEST['post'] ) && !is_array( $_REQUEST['post'] ) )
            return;
            foreach($_REQUEST['post'] as $order_id) {
                $order = new WC_Order( $order_id );
                $this->osc_process_carrier_order($order);
            }
            $location = add_query_arg( array(
                'post_type' => 'shop_order',
                'marked_sent' => 1,
                'changed' => count( $_REQUEST['post'] ),
                'ids' => join( $_REQUEST['post'], ',' ),
                'post_status' => 'all',
                'paged' => $_REQUEST['paged'],
            ), 'edit.php' );
            wp_redirect( admin_url( $location ) );
            exit;
        }
    }
}