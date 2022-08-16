<?php
if (!class_exists('OSC_Meta_Boxes')) {
    class OSC_Meta_Boxes {
        public function __construct() {
            $this->init();
        }
        public function init() {
            add_action( 'add_meta_boxes', array($this, 'osc_add_meta_boxes') );
            add_action( 'wp_ajax_package_number_update', array($this, 'osc_ajax_update_packages_number') );
            add_filter( 'manage_edit-shop_order_columns', array($this, 'orders_no_of_pkg_column'), 20);
            add_action( 'manage_shop_order_posts_custom_column', array($this, 'orders_no_of_pkg_column_info') );
            add_filter( 'manage_edit-shop_order_columns', array($this, 'orders_orian_sent_status_column'), 20);
            add_action( 'manage_shop_order_posts_custom_column', array($this, 'orders_orian_sent_status_column_info') );
            add_filter( 'manage_edit-shop_order_columns', array($this, 'orders_pdf_printed_status_column'), 20);
            add_action( 'manage_shop_order_posts_custom_column', array($this, 'orders_pdf_printed_status_column_info') );
        }
        public function osc_add_meta_boxes() {
            add_meta_box( 'osc_orian_meta', __('Orian Shipping','orian-shipping-carrier'), array($this,'osc_orian_meta_cb'), 'shop_order', 'side', 'core' );
            add_meta_box( 'osc_orian_send_status', __('Order Creation in Orian','orian-shipping-carrier'), array($this,'osc_orian_status_cb'), 'shop_order', 'side', 'high' );
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
                <div style="<?php echo $box_styling; ?>background-color:grey;"><?php _e("Order wasn't opened at carrier yet","orian-shipping-carrier"); ?></div>
                <?php
            } else {
                if ($orian_status === "success") {
                    ?>
                <div style="<?php echo $box_styling; ?>background-color:green;"><?php _e("Order was opened successfully in carrier systems","orian-shipping-carrier"); ?></div>
                <?php
                } elseif ($orian_status === "failure") {
                    $orian_error = get_post_meta($orderid, 'Orian Error',true);
                    ?>
                <div style="<?php echo $box_styling; ?>background-color:red;"><?php _e("Error while trying open order in carier system:", "orian-shipping-carrier"); ?> <?php echo $orian_error; ?></div>
                <?php
                }
            }
        }
        public function osc_orian_meta_cb() {
            global $post;
            $orderid = $post->ID;
            $order = wc_get_order($orderid);
            $order_status = $order->get_status();
            $package_statuses = get_post_meta($orderid,'_osc_packages_statues',true);
            $wc_statuses = wc_get_order_statuses();
            $numberofpackages = get_post_meta($orderid,'number_of_packages',true);
            if (!$numberofpackages)
                $numberofpackages = 1;
            else
                $numberofpackages = intval($numberofpackages);
            $pudo_shipping = false;
            foreach($order->get_items("shipping") as $item_key => $item) {
                if ($item->get_method_id() === orian_shipping()->pudo_method_id)
                    $pudo_shipping = true;
            }
            ?>
            <?php if ($order_status === "processing"): ?>
            <div style="margin:10px auto;">
                <button type="button" class="button" onclick="osc_order_send()"><?php _e("Send Order to Carrier","orian-shipping-carrier"); ?></button>
            </div>
            <?php endif; ?>
            <?php if (!$pudo_shipping): ?>
            <div id="numberofpackagesbox" style="display:flex;justify-content:space-between;align-items:center;">
                <label for="numberofpackages"><?php _e("Number of Packages","orian-shipping-carrier"); ?></label>
                <?php if ($order_status === "processing"): ?>
                <select name="numberofpackages" id="numberofpackages" onchange="update_number_of_packages(this, <?php echo $orderid; ?>)">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $numberofpackages === $i ? 'selected="selected"':''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <?php else: ?>
                    <input id="numberofpackages" style="width:50px;" type="text" disabled value="<?php echo $numberofpackages; ?>">
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <h3 style="text-decoration:underline;"><?php _e('Packages Status','orian-shipping-carrier'); ?></h3>
            <table style="width:100%;text-align:left;">
                <thead>
                    <tr>
                        <th><?php _e('Package Id','orian-shipping-carrier'); ?></th>
                        <th><?php _e('Status','orian-shipping-carrier'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php for($i = 0; $i < $numberofpackages; $i++):
                        $package_status = $package_statuses[$i];
                        $packagenumber = $i + 1;
                        ?>
                        <tr>
                            <td>KKO<?php echo $packagenumber > 1 ? $orderid . 'P' . $packagenumber : $orderid; ?></td>
                            <td><?php echo $package_status ? $wc_statuses['wc-'.$package_status]: ''; ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <?php if (in_array($order_status,orian_shipping()->pdf_labels->allowed_statuses)): ?>
            <h3 style="text-decoration:underline;"><?php _e('PDF Labels','orian-shipping-carrier'); ?></h3>
            <div style="margin:10px auto;">
                <button type="button" onclick="osc_pdf_generate(this,<?php echo $orderid; ?>)" class="button"><?php _e("Generate PDF Labels","orian-shipping-carrier"); ?></button>
            </div>
            <?php
            endif;
            $pdf_printed = get_post_meta($orderid, 'pdf_label_printed',true);
            if ($pdf_printed && $pdf_printed === "yes"):
            ?>
            <p style="color:green"><?php _e("PDF Label is Printed","orian-shipping-carrier"); ?></p>
            <?php
            endif;
            ?>
            <h3 style="text-decoration:underline;"><?php _e('SLA','orian-shipping-carrier'); ?></h3>
            <?php
            $now = new DateTime("now",orian_shipping()->sla->timezone);
            $enddate = orian_shipping()->sla->get_sla_end_datetime($orderid);
            $diff = $now->diff($enddate);
            if ($now < $enddate)
                echo '<h4 class="sla-time">'.$diff->format(__('%d days %h hours %i minutes Remaining','orian-shipping-carrier')).'</h4>';
            else
                echo '<h4 class="sla-time">'.$diff->format(__('%d days %h hours %i minutes Late','orian-shipping-carrier')).'</h4>';
        }
        public function orders_no_of_pkg_column($cols) {
            $cols['packages_details'] = __('Number of Packages','orian-shipping-carrier');
            return $cols;
        }
        public function orders_no_of_pkg_column_info($col) {
            global $post;
            if ('packages_details' === $col) {
                $numberofpackages = get_post_meta($post->ID,'number_of_packages',true);
                if ($numberofpackages)
                    echo "<p>".$numberofpackages."</p>";
                else
                    echo "<p>1</p>";
            }
        }
        public function orders_orian_sent_status_column($cols) {
            $cols['orian_status'] = __('Orian Order Status','orian-shipping-carrier');
            return $cols;
        }
        public function orders_orian_sent_status_column_info($col) {
            global $post;
            if ('orian_status' === $col) {
                $orian_status = get_post_meta($post->ID, '_orian_sent',true);
                if ($orian_status) {
                if ($orian_status === "success")
                    echo "<div style='background-color:green;padding:10px;color:white;text-align:center;'>".__('Sent','orian-shipping-carrier')."</div>";
                else
                    echo "<div style='background-color:red;padding:10px;color:white;text-align:center;'>".__('Failed','orian-shipping-carrier')."</div>";
                } else {
                    echo "<div style='background-color:gray;padding:10px;color:white;text-align:center;'>".__('Not Sent','orian-shipping-carrier')."</div>";
                }
            }
        }
        public function orders_pdf_printed_status_column($cols) {
            $cols['pdf_status'] = __('Label Print Status','orian-shipping-carrier');
            return $cols;
        }
        public function orders_pdf_printed_status_column_info($col) {
            global $post;
            if ('pdf_status' === $col) {
                $pdf_status = get_post_meta($post->ID, 'pdf_label_printed',true);
                if ($pdf_status && $pdf_status === "yes")
                    echo "<div style='background-color:green;padding:10px;color:white;text-align:center;'>".__('Printed','orian-shipping-carrier')."</div>";
                else
                    echo "<div style='background-color:gray;padding:10px;color:white;text-align:center;'>".__('Not Sent','orian-shipping-carrier')."</div>";
            }
        }
    }
}