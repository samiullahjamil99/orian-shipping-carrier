<?php
if (!class_exists('OSC_Pudo_Shipping')) {
    class OSC_Pudo_Shipping extends WC_Shipping_Flat_Rate {
        public function __construct($instance_id = 0) {
            $this->id                 = 'orian_pudo_shipping';
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = __( 'Orian Pudo Shipping' );
            $this->method_description = __( 'Setup Shipping with Orian Pudo' );
            $this->supports           = array(
                        'shipping-zones',
                        'instance-settings',
                        'instance-settings-modal',
            );
            $this->init();
        }
    }
}