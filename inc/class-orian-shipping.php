<?php
if (!class_exists('Orian_Shipping')) {
    final class Orian_Shipping {
        protected static $_instance = null;
        public $api;
        public $order_status;
        public $order_actions;
        public $order_sync;
        public $home_method_id = "orian_delivery_shipping";
        public $pudo_method_id = "orian_pudo_shipping";
        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct() {
            $this->includes();
            $this->init();
        }
        public function includes() {
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-api.php';
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-woocommerce-order-status.php';
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-woocommerce-order-actions.php';
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-woocommerce-order-sync.php';
        }
        public function init() {
            add_action( 'woocommerce_shipping_init', array($this,'osc_shipping_init') );
            add_filter( 'woocommerce_shipping_methods', array($this,'osc_add_shipping') );
            add_action( 'woocommerce_after_shipping_rate', array($this,'shipping_input_fields'), 10, 2 );
            $this->api = new OSC_API();
            $this->order_status = new OSC_Woocommerce_Order_Status();
            $this->order_actions = new OSC_Woocommerce_Order_Actions();
            $this->order_sync = new OSC_Woocommerce_Order_Sync();
        }
        public function osc_shipping_init() {
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/shipping-methods/class-osc-delivery-shipping.php';
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/shipping-methods/class-osc-pudo-shipping.php';
        }
        public function osc_add_shipping( $methods ) {
            $methods[$this->home_method_id] = 'OSC_Delivery_Shipping';
            $methods[$this->pudo_method_id] = 'OSC_Pudo_Shipping';
            return $methods;
        }
        public function shipping_input_fields($method,$i) {
            $chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
            if ($method->method_id === $this->pudo_method_id && $method->id === $chosen_method)
            osc_pudo_fields_html();
        }
    }
}