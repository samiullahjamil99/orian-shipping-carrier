<?php
if (!class_exists('Orian_Shipping')) {
    final class Orian_Shipping {
        protected static $_instance = null;
        public $version = '2.0.1';
        public $api;
        public $order_status;
        public $order_actions;
        public $order_sync;
        public $sla;
        public $pdf_labels;
        public $meta_boxes;
        public $home_method_id = "orian_delivery_shipping";
        public $pudo_method_id = "orian_pudo_shipping";
        public $package_prefix = "KKO";
        public $legacy_package_prefix = "KKO";
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
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-sla.php';
            include_once dirname(OSC_PLUGIN_FILE) . '/lib/tcpdf/tcpdf.php';
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-pdf-labels.php';
            include_once dirname(OSC_PLUGIN_FILE) . '/inc/class-osc-meta-boxes.php';
        }
        public function init() {
            add_action( 'admin_enqueue_scripts',array($this,'admin_scripts') );
            add_action( 'woocommerce_shipping_init', array($this,'osc_shipping_init') );
            add_filter( 'woocommerce_shipping_methods', array($this,'osc_add_shipping') );
            add_action( 'woocommerce_after_shipping_rate', array($this,'shipping_input_fields'), 10, 2 );
            $this->api = new OSC_API();
            $this->order_status = new OSC_Woocommerce_Order_Status();
            $this->order_actions = new OSC_Woocommerce_Order_Actions();
            $this->order_sync = new OSC_Woocommerce_Order_Sync();
            $this->sla = new OSC_SLA();
            $this->pdf_labels = new OSC_PDF_Labels();
            $this->meta_boxes = new OSC_Meta_Boxes();
            $options = get_option('orian_main_setting');
            if ($options['packageprefix'])
                $this->package_prefix = $options['packageprefix'];
        }
        public function admin_scripts() {
            wp_enqueue_script( 'order-actions', plugin_dir_url(OSC_PLUGIN_FILE) . 'assets/js/order-actions.js', array( 'jquery' ),$this->version );
            wp_localize_script( 'order-actions', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
            wp_enqueue_style( 'orders-page', plugin_dir_url(OSC_PLUGIN_FILE) . 'assets/css/orders-page.css', array(),$this->version );
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
            $customer_session = WC()->session->get('customer');
            $selected_city = $customer_session['city'];
            $selected_city_far = array($selected_city,"0");
            $far_destination = false;
            if ($this->sla->orian_cities) {
                if (in_array($selected_city_far,$this->sla->orian_cities))
                $far_destination = true;
            }
            $chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
            if ($method->method_id === $this->pudo_method_id/* && $method->id === $chosen_method*/) {
                $delivery_dates = $this->sla->get_delivery_date('pudo');
                if ($delivery_dates[0] !== $delivery_dates[1]) {
                    echo '<p class="eta">';
                    printf(__('Available for pick up between %1$s to %2$s','orian-shipping-carrier'), $delivery_dates[0], $delivery_dates[1]);
                    echo '</p>';
                } else {
                    echo '<p class="eta">';
                    printf(__('Available for pick up on %1$s','orian-shipping-carrier'), $delivery_dates[0]);
                    echo '</p>';
                }
				if ($method->id === $chosen_method)
            		osc_pudo_fields_html();
            } elseif ($method->method_id === $this->home_method_id/* && $method->id === $chosen_method*/) {
                if ($far_destination) {
                    $delivery_dates = $this->sla->get_delivery_date('far');
                } else {
                    $delivery_dates = $this->sla->get_delivery_date('home');
                }
                if ($delivery_dates[0] !== $delivery_dates[1]) {
                    echo '<p class="eta">';
                    printf(__('Estimated Delivery Between %1$s and %2$s','orian-shipping-carrier'), $delivery_dates[0], $delivery_dates[1]);
                    echo '</p>';
                } else {
                    echo '<p class="eta">';
                    printf(__('Estimated Delivery On %1$s','orian-shipping-carrier'), $delivery_dates[0]);
                    echo '</p>';
                }
            }
        }
        public function isAssoc(array $arr) {
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        }
    }
}