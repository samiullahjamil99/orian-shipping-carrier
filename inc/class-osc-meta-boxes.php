<?php
if (!class_exists('OSC_Meta_Boxes')) {
    class OSC_Meta_Boxes {
        public function __construct() {
            $this->init();
        }
        public function init() {
            add_action( 'add_meta_boxes', array($this, 'osc_add_meta_boxes') );
            add_action( 'wp_ajax_package_number_update', array($this, 'osc_ajax_update_packages_number') );
        }
        public function osc_add_meta_boxes() {
            add_meta_box( 'osc_orian_meta', __('Orian Shipping','orian-shipping-carrier'), array($this,'osc_orian_meta_cb'), 'shop_order', 'side', 'core' );
            add_meta_box( 'osc_orian_meta', __('Orian Order Status','orian-shipping-carrier'), array($this,'osc_orian_status_cb'), 'shop_order', 'side', 'high' );
        }
        public function osc_ajax_update_packages_number() {
            $orderid = intval($_POST['orderid']);
            $numberofpackages = $_POST['numberofpackages'];
            update_post_meta($orderid, 'number_of_packages',$numberofpackages);
            wp_die();
        }
        public function osc_orian_status_cb() {
            global $post;
            $orderid = $post->ID;
            $order = wc_get_order($orderid);
            $orian_status = get_post_meta($orderid, '_orian_sent',true);
            $box_styling = "color:white;padding:30px;text-align:center;font-size:20pt;";
            if (!$orian_status) {
                ?>
                <div style="<?php echo $box_styling; ?>background-color:grey;">Not Sent</div>
                <?php
            } else {
                if ($orian_status === "success") {
                    ?>
                <div style="<?php echo $box_styling; ?>background-color:green;">Sent Success</div>
                <?php
                } elseif ($orian_status === "failure") {
                    ?>
                <div style="<?php echo $box_styling; ?>background-color:red;">Failed to Send</div>
                <?php
                }
            }
        }
        public function osc_orian_meta_cb() {
            global $post;
            $orderid = $post->ID;
            $order = wc_get_order($orderid);
            $package_statuses = get_post_meta($orderid,'_osc_packages_statues',true);
            $wc_statuses = wc_get_order_statuses();
            $numberofpackages = get_post_meta($orderid,'number_of_packages',true);
            if (!$numberofpackages)
                $numberofpackages = 1;
            else
                $numberofpackages = intval($numberofpackages);
            ?>
            <div id="numberofpackagesbox" style="display:flex;justify-content:space-between;align-items:center;">
                <label for="numberofpackages">Number of Packages</label>
                <select name="numberofpackages" id="numberofpackages" onchange="update_number_of_packages(this, <?php echo $orderid; ?>)">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $numberofpackages === $i ? 'selected="selected"':''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
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
            <div style="margin-top:10px;">
                <a href="javascript:void(0)" onclick="osc_pdf_generate(this,<?php echo $orderid; ?>)">Generate PDF Labels</a>
            </div>
            <?php
        }
    }
}