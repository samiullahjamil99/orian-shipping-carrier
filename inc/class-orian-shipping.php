<?php
if (!class_exists('Orian_Shipping')) {
    final class Orian_Shipping {
        protected static $_instance = null;
        public $api;
        public $order_status;
        public $order_actions;
        public $order_sync;
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
            $methods['orian_delivery_shipping'] = 'OSC_Delivery_Shipping';
            $methods['orian_pudo_shipping'] = 'OSC_Pudo_Shipping';
            return $methods;
        }
    }
}