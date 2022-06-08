<?php
if (!class_exists('Orian_Shipping')) {
    final class Orian_Shipping {
        protected static $_instance = null;
        public $api;
        public $order_status;
        public $order_actions;
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
        }
        public function init() {
            $this->api = new OSC_API();
            $this->order_status = new OSC_Woocommerce_Order_Status();
            $this->order_actions = new OSC_Woocommerce_Order_Actions();
        }
    }
}