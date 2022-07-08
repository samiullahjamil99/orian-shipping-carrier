<?php
if (!class_exists('OSC_Woocommerce_Order_Status')) {
    class OSC_Woocommerce_Order_Status {
        public $statuses;
        public $orian_statuses = array(
            'new' => 'wc-osc-new',
            'loaded' => 'wc-osc-loaded',
            'delivered' => 'wc-osc-delivered',
            'lost' => 'wc-osc-lost',
            'offloaded' => 'wc-osc-offloaded',
            'pickedup' => 'wc-osc-pickedup',
            'canceled' => 'wc-osc-cancelled',
            'dropedatpudo' => 'wc-osc-dropedatpudo',
        );
        public function __construct() {
            $this->init();
        }
        public function init() {
            $this->statuses = array(
                'wc-osc-new' => "New",
                'wc-osc-loaded' => "Loaded",
                'wc-osc-delivered' => "Delivered",
                'wc-osc-lost' => "Lost",
                'wc-osc-offloaded' => "Offloaded",
                'wc-osc-pickedup' => "Picked Up",
                'wc-osc-dropedatpudo' => "Available at Pudo",
                'wc-osc-cancelled' => "Shipping Cancelled",
            );
            add_action( 'init', array($this,'register_order_statuses') );
            add_filter( 'wc_order_statuses', array($this,'add_order_statuses') );
        }
        public function register_order_statuses() {
            foreach($this->statuses as $status_key => $status_label) {
                register_post_status( $status_key, array(
                    'label'                     => __($status_label,'orian-shipping-carrier'),
                    'public'                    => true,
                    'show_in_admin_status_list' => true,
                    'show_in_admin_all_list'    => true,
                    'exclude_from_search'       => false,
                    'label_count'               => _n_noop( $status_label . ' <span class="count">(%s)</span>', $status_label . ' <span class="count">(%s)</span>','orian-shipping-carrier' )
                ) );
            }
        }
        public function add_order_statuses( $order_statuses) {
            $output_statuses = $order_statuses;
            foreach ( $this->statuses as $key => $status ) {
                $output_statuses[ $key ] = __($status,'orian-shipping-carrier');
            }
            unset( $output_statuses['wc-completed'] );
            return $output_statuses;
        }
        public function compare_carrier_order_status($carrier_order) {
            $lowercase_carrier_order = strtolower($carrier_order);
            if (array_key_exists($lowercase_carrier_order,$this->orian_statuses))
            return $this->orian_statuses[$lowercase_carrier_order];
            else
            return false;
        }
    }
}