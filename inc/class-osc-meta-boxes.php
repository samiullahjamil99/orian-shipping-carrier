<?php
if (!class_exists('OSC_Meta_Boxes')) {
    class OSC_Meta_Boxes {
        public function __construct() {
            $this->init();
        }
        public function init() {
            add_action( 'add_meta_boxes', array($this, 'osc_add_meta_boxes') );
        }
        public function osc_add_meta_boxes() {
            add_meta_box( 'osc_orian_meta', __('Orian Shipping','orian-shipping-carrier'), array($this,'osc_orian_meta_cb'), 'shop_order', 'side', 'core' );
        }
        public function osc_orian_meta_cb() {
            global $post;
            $orderid = $post->ID;
            $order = wc_get_order($orderid);
            $package_statuses = get_post_meta($orderid,'_osc_packages_statues',true);
            $wc_statuses = wc_get_order_statuses();
            ?>
            <h3 style="text-decoration:underline;"><?php _e('Packages Status','orian-shipping-carrier'); ?></h3>
            <table style="width:100%;text-align:left;">
                <thead>
                    <tr>
                        <th><?php _e('Package Id','orian-shipping-carrier'); ?></th>
                        <th><?php _e('Status','orian-shipping-carrier'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>KKO<?php echo $orderid; ?></td>
                        <td><?php echo $wc_statuses['wc-'.$order->get_status()]; ?></td>
                    </tr>
                    <?php
                    $packagenumber = 2;
                    if ($package_statuses):
                        foreach($package_statuses as $package_status):
                        ?>
                        <tr>
                            <td>KKO<?php echo $orderid; ?>P<?php echo $packagenumber; ?></td>
                            <td><?php echo $wc_statuses['wc-'.$package_status]; ?></td>
                        </tr>
                        <?php
                        $packagenumber++;
                        endforeach;
                    endif;
                    ?>
                </tbody>
            </table>
            <a href="javascript:void(0)" onclick="osc_pdf_generate(this,<?php echo $orderid; ?>)">Generate PDF Labels</a>
            <?php
        }
    }
}