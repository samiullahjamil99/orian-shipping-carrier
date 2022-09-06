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
            add_action( 'manage_posts_extra_tablenav', array($this,'admin_order_list_top_bar_button'), 20, 1 );
        }
        function admin_order_list_top_bar_button( $which ) {
            global $typenow;

            if ( 'shop_order' === $typenow && 'top' === $which && $_GET['post_status'] === "wc-processing" ) {
                ?>
                <div class="alignleft actions custom">
                    <button type="button" onclick="osc_send_order_bulk(this)" name="orian_send_orders" style="height:32px;" class="button" value=""><?php
                        _e( 'Send Orders to Carrier', 'orian-shipping-carrier' ); ?></button>
                </div>
                <?php
            }
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
            if ($pudo_shipping && $pudo_point) {
                $response = osc_api()->generate_pudo_transportation_order($order->get_id());
            } elseif (!$pudo_shipping) {
                $numberofpackages = get_post_meta($order->get_id(), 'number_of_packages', true);
                if ($numberofpackages)
                    $numberofpackages = intval($numberofpackages);
                else
                    $numberofpackages = 1;
                $response = osc_api()->generate_transportation_order($order->get_id(), $numberofpackages);
            }
            if ($response['status'] == 200 && $response['success'] === "true") {
                $order->update_status("wc-osc-new");
                $orian_statuses = array();
                for ($i = 1; $i <= $numberofpackages; $i++) {
                    $packagename = orian_shipping()->package_prefix . $order->get_id();
                    if ($i > 1)
                        $packagename = orian_shipping()->package_prefix . $order->get_id().'P'.$i;
                    $orian_statuses[$packagename] = 'osc-new';
                }
                update_post_meta($order->get_id(), '_osc_packages_statues',$orian_statuses);
            }
        }
        public function osc_bulk_actions( $bulk_actions ) {
            if ($_GET['post_status'] === 'wc-processing')
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