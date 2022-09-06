<?php
if (!class_exists('OSC_Woocommerce_Order_Status')) {
    class OSC_Woocommerce_Order_Status {
        public $orian_statuses = array(
            'new' => 'wc-osc-new',
            'loaded' => 'wc-osc-loaded',
            'delivered' => array('home'=>'wc-osc-delivered','pudo'=>'wc-osc-picked'),
            'lost' => 'wc-osc-lost',
            'offloaded' => 'wc-osc-offloaded',
            'pickedup' => array('home'=>'wc-osc-pickedup-home','pudo' =>'wc-osc-pickedup-pudo'),
            'canceled' => 'wc-osc-cancelled',
            'dropedatpudo' => 'wc-osc-dropedatpudo',
        );
        public function __construct() {
            $this->init();
        }
        public function init() {
            add_action( 'init', array($this,'register_order_statuses') );
            add_filter( 'wc_order_statuses', array($this,'add_order_statuses') );
        }
        public function register_order_statuses() {
            register_post_status( 'wc-osc-new', array(
                'label'                     => __("New",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'New <span class="count">(%s)</span>', 'New <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-loaded', array(
                'label'                     => __("Loaded",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Loaded <span class="count">(%s)</span>', 'Loaded <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-delivered', array(
                'label'                     => __("Delivered to Home",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Delivered to Home <span class="count">(%s)</span>', 'Delivered to Home <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-picked', array(
                'label'                     => __("Picked from Pudo",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Picked from Pudo <span class="count">(%s)</span>', 'Picked from Pudo <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-lost', array(
                'label'                     => __("Lost",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Lost <span class="count">(%s)</span>', 'Lost <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-offloaded', array(
                'label'                     => __("Offloaded",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Offloaded <span class="count">(%s)</span>', 'Offloaded <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-pickedup-pudo', array(
                'label'                     => __("Picked Up (Pudo)",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Picked Up (Pudo) <span class="count">(%s)</span>', 'Picked Up (Pudo) <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-pickedup-home', array(
                'label'                     => __("Picked Up (Home Delivery)",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Picked Up (Home Delivery) <span class="count">(%s)</span>', 'Picked Up (Home Delivery) <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-dropedatpudo', array(
                'label'                     => __("Available at Pudo",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Available at Pudo <span class="count">(%s)</span>', 'Available at Pudo <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
            register_post_status( 'wc-osc-cancelled', array(
                'label'                     => __("Shipping Cancelled",'orian-shipping-carrier'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop( 'Shipping Cancelled <span class="count">(%s)</span>', 'Shipping Cancelled <span class="count">(%s)</span>','orian-shipping-carrier' )
            ) );
        }
        public function add_order_statuses( $order_statuses) {
            $output_statuses = $order_statuses;
            $output_statuses['wc-osc-new'] = __('New','orian-shipping-carrier');
            $output_statuses['wc-osc-loaded'] = __('Loaded','orian-shipping-carrier');
            $output_statuses['wc-osc-delivered'] = __('Delivered to Home','orian-shipping-carrier');
            $output_statuses['wc-osc-picked'] = __('Picked from Pudo','orian-shipping-carrier');
            $output_statuses['wc-osc-lost'] = __('Lost','orian-shipping-carrier');
            $output_statuses['wc-osc-offloaded'] = __('Offloaded','orian-shipping-carrier');
            $output_statuses['wc-osc-pickedup-home'] = __('Picked Up (Home Delivery)','orian-shipping-carrier');
            $output_statuses['wc-osc-pickedup-pudo'] = __('Picked Up (Pudo)','orian-shipping-carrier');
            $output_statuses['wc-osc-dropedatpudo'] = __('Available at Pudo','orian-shipping-carrier');
            $output_statuses['wc-osc-cancelled'] = __('Shipping Cancelled','orian-shipping-carrier');
            unset( $output_statuses['wc-completed'] );
            return $output_statuses;
        }
        public function compare_carrier_order_status($carrier_order,$pudo = false) {
            $lowercase_carrier_order = strtolower($carrier_order);
            if (array_key_exists($lowercase_carrier_order,$this->orian_statuses)) {
                if ($lowercase_carrier_order === 'delivered' || $lowercase_carrier_order === 'pickedup') {
                    if ($pudo)
                        return $this->orian_statuses[$lowercase_carrier_order]['pudo'];
                    else
                        return $this->orian_statuses[$lowercase_carrier_order]['home'];
                } else {
                    return $this->orian_statuses[$lowercase_carrier_order];
                }
            }
            else {
                return false;
            }
        }
    }
}