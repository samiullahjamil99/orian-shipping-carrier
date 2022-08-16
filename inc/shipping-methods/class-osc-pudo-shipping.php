<?php
if (!class_exists('OSC_Pudo_Shipping')) {
    class OSC_Pudo_Shipping extends WC_Shipping_Method {
        public function __construct($instance_id = 0) {
            $this->id                 = orian_shipping()->pudo_method_id;
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = __( 'Orian Pudo Shipping' );
            $this->method_description = __( 'Setup Shipping with Orian Pudo' );
            $this->supports           = array(
                        'shipping-zones',
                        'instance-settings',
                        'instance-settings-modal',
            );
            $this->init();
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }
        public function init() {
            $this->instance_form_fields = array(
                'title'      => array(
                    'title'       => __( 'Method title', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default'     => __( 'Pickup from Location', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'cost'       => array(
                    'title'             => __( 'Cost', 'woocommerce' ),
                    'type'              => 'text',
                    'placeholder'       => '',
                    'default'           => '0',
                    'sanitize_callback' => array( $this, 'sanitize_cost' ),
                ),
                'dynamic_cost' => array(
                    'title' => __( 'Dynamic Cost', 'orian-shipping-method'),
                    'type' => 'text',
                    'placeholder' => '',
                    'default' => '',
                    'description' => __( 'The format goes like 0-10|20-50|30-100. In this the conditions are separated by "|" and the cart cost range starts from the number before hyphen (-). The number after hyphen (-) is the shipping cost for this range.','orian-shipping-carrier' ),
                    'desc_tip' => true,
                )
            );
            $this->title                = $this->get_option( 'title' );
            $this->cost                 = $this->get_option( 'cost' );
            $this->dynamic_cost         = $this->get_option( 'dynamic_cost' );
            $this->type                 = $this->get_option( 'type', 'class' );
        }
        public function calculate_shipping( $package = array() ) {
            $rate = array(
                'id' => $this->get_rate_id(),
                'label' => $this->title,
                'cost' => 0,
                'packages' => $package,
            );
            $has_costs = false;
		    $cost = $this->get_option( 'cost' );
            $dynamic_cost = $this->get_option( 'dynamic_cost' );
            if ( '' !== $cost ) {
                $has_costs    = true;
                $rate['cost'] = $cost;
            }
            if ( '' !== $dynamic_cost ) {
                $dynamic_cost = trim($dynamic_cost);
                $conditions = explode('|',$dynamic_cost);
                foreach($conditions as $condition) {
                    $condition_arr = explode('-',$condition);
                    $range = floatval($condition_arr[0]);
                    $mycost = floatval($condition_arr[1]);
                    $contents_cost = floatval($package['contents_cost']);
                    if ($contents_cost > $range) {
                        $rate['cost'] = $mycost;
                    }
                }
            }
            if ($has_costs) {
                $this->add_rate( $rate );
            }
        }
    }
}