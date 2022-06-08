<?php
if (!class_exists('OSC_Woocommerce_Order_Sync')) {
    class OSC_Woocommerce_Order_Sync {
        public $sync_minutes = 0;
        public function __contruct() {
            $this->init();
        }
        public function init() {
            $orian_options = get_option('orian_main_setting');
            if (isset($orian_options) && array_key_exists('sync_time',$orian_options)) {
                $this->sync_minutes = intval($orian_options['sync_time']);
                if ($this->sync_minutes > 0) {
                    add_filter( 'cron_schedules', array($this, 'osc_add_cron_interval') );
                    add_action( 'osc_order_sync_hook', array($this, 'osc_order_sync_callback') );
                    if ( ! wp_next_scheduled( 'osc_order_sync_hook' ) ) {
                        wp_schedule_event( time(), 'custom_minutes', 'osc_order_sync_hook' );
                    }
                } else {
                    $timestamp = wp_next_scheduled( 'osc_order_sync_hook' );
                    wp_unschedule_event( $timestamp, 'osc_order_sync_hook' );
                }
            }
        }
        public function osc_add_cron_interval($schedules) {
            $schedules['custom_minutes'] = array(
                'interval' => 60 * $this->sync_minutes,
                'display'  => esc_html__( 'Every ' . $this->sync_minutes . ' Minutes' ),
            );
            return $schedules;
        }
        public function osc_order_sync_callback() {
            $orders = wc_get_orders(array(
                'limit' => -1,
                'status' => array('osc-new','osc-loaded','osc-offloaded','osc-pickedup'),
            ));
            foreach($orders as $order) {
                $carrier_response = orian_shipping()->api->get_transportation_order_status($order->get_id());
                if ($carrier_response['status'] == 200) {
                    $woocommerce_status = orian_shipping()->order_status->compare_carrier_order_status($carrier_response['package_status']);
                    if ($woocommerce_status && $woocommerce_status !== "wc-".$order->get_status())
                    $order->update_status($woocommerce_status);
                }
            }
        }
    }
}