<?php
if (!class_exists('OSC_Delivery_Shipping')) {
    class OSC_Delivery_Shipping extends WC_Shipping_Flat_Rate {
        public function __construct($instance_id = 0) {
            $this->id                 = 'orian_delivery_shipping';
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = __( 'Orian Delivery Shipping' );
            $this->method_description = __( 'Setup Shipping with Orian Delivery' );
            $this->supports           = array(
                        'shipping-zones',
                        'instance-settings',
                        'instance-settings-modal',
            );
            $this->init();
        }
    }
}