<?php
if (!class_exists('Orian_Shipping')) {
    final class Orian_Shipping {
        protected static $_instance = null;
        public $api;
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
        }
        public function init() {
            $this->api = new OSC_API();
        }
    }
}