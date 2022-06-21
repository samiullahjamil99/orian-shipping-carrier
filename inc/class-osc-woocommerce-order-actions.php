<?php
if (!class_exists('OSC_Woocommerce_Order_Actions')) {
    class OSC_Woocommerce_Order_Actions {
        public function __construct() {
            $this->init();
        }
        public function init() {
            add_action( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_actions' ) );
            add_action( 'woocommerce_order_action_osc_send_order_to_carrier', array($this, 'osc_process_carrier_order') );
            add_filter( 'bulk_actions-edit-shop_order', array($this, 'osc_bulk_actions') );
            add_action( 'admin_action_osc_send_orders', array($this,'bulk_process_send_orders') );
            add_action( 'add_meta_boxes', array($this, 'osc_add_meta_boxes') );
        }
        public function osc_add_meta_boxes() {
            add_meta_box( 'osc_packages_status', __('Packages Status','orian-shipping-carrier'), array($this,'osc_packages_status_cb'), 'shop_order', 'side', 'core' );
        }
        public function osc_packages_status_cb() {
            global $post;
            $orderid = $post->ID;
            $order = wc_get_order($orderid);
            $package_statuses = get_post_meta($orderid,'_osc_packages_statues',true);
            $wc_statuses = wc_get_order_statuses();
            ?>
            <table style="width:100%;text-align:left;">
            <thead>
                <tr><th><?php _e('Package Id','orian-shipping-carrier'); ?></th><th><?php _e('Status','orian-shipping-carrier'); ?></th></tr>
        </thead>
                <tr><td>KKO<?php echo $orderid; ?></td><td><?php echo $wc_statuses['wc-'.$order->get_status()]; ?></td></tr>
            <?php
            $packagenumber = 2;
            if ($package_statuses):
            foreach($package_statuses as $package_status):
                ?>
                <tr><td>KKO<?php echo $orderid; ?>P<?php echo $packagenumber; ?></td><td><?php echo $wc_statuses['wc-'.$package_status]; ?></td></tr>
                <?php
                $packagenumber++;
                endforeach;
            endif;
                ?>
                </table>
                <?php
        }
        public function add_order_meta_box_actions($actions) {
            global $theorder;
            $order_status = $theorder->get_status();
            if ($order_status === "processing")
            $actions['osc_send_order_to_carrier'] = __("Send Order to Carrier","orian-shipping-carrier");
            return $actions;
        }
        public function osc_process_carrier_order($order) {
            $pudo_shipping = false;
            foreach($order->get_items("shipping") as $item_key => $item) {
                if ($item->get_method_id() === orian_shipping()->pudo_method_id)
                $pudo_shipping = true;
            }
            $pudo_point = get_post_meta($order->get_id(),'pudo_point',true);
            $numberofpackages = 1;
            if ($pudo_shipping && $pudo_point) {
                $response = osc_api()->generate_pudo_transportation_order($order->get_id());
            } else {
            $numberofpackages = get_post_meta($order->get_id(), 'number_of_packages', true);
            if ($numberofpackages)
            $numberofpackages = intval($numberofpackages);
            $response = osc_api()->generate_transportation_order($order->get_id(), $numberofpackages);
            }
            if ($response['status'] == 200 && $response['success'] === "true")
            $order->update_status("wc-osc-new");
            if ($numberofpackages > 1) {
                $extra_statuses = array();
                for ($i = 1; $i < $numberofpackages; $i++) {
                    $extra_statuses[] = 'osc-new';
                }
                update_post_meta($order->get_id(), '_osc_packages_statues',$extra_statuses);
            }
        }
        public function osc_bulk_actions( $bulk_actions ) {
            $bulk_actions['osc_send_orders'] = __('Send Orders to Carrier','orian-shipping-carrier');
            return $bulk_actions;
        }
        public function bulk_process_send_orders() {
            if( !isset( $_REQUEST['post'] ) && !is_array( $_REQUEST['post'] ) )
            return;
            foreach($_REQUEST['post'] as $order_id) {
                $order = new WC_Order( $order_id );
                $this->osc_process_carrier_order($order);
            }
            $location = add_query_arg( array(
                'post_type' => 'shop_order',
                'marked_sent' => 1,
                'changed' => count( $_REQUEST['post'] ),
                'ids' => join( $_REQUEST['post'], ',' ),
                'post_status' => 'all',
                'paged' => $_REQUEST['paged'],
            ), 'edit.php' );
            wp_redirect( admin_url( $location ) );
            exit;
        }
    }
}