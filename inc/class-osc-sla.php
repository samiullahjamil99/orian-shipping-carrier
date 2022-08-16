<?php
if (!class_exists("OSC_SLA")) {
    class OSC_SLA {
        public $timezone;
        public $nonbusiness_days;
        public $home_regular_sla;
        public $home_far_sla;
        public $pudo_sla;
        public $orian_cities;
        public $businessday_endtime;
        public function __construct() {
            $this->init();
        }
        public function init() {
            $this->timezone = wp_timezone();
            $orian_settings = get_option('orian_main_setting');
            if ($orian_settings) {
                if (array_key_exists('nonbusiness_days',$orian_settings))
                    $this->nonbusiness_days = array_map('trim', explode(",",$orian_settings['nonbusiness_days']));
                if (array_key_exists('delivery_sla',$orian_settings))
                    $this->home_regular_sla = $orian_settings['delivery_sla'];
                if (array_key_exists('delivery_far_sla',$orian_settings))
                    $this->home_far_sla = $orian_settings['delivery_far_sla'];
                if (array_key_exists('pickup_sla',$orian_settings))
                    $this->pudo_sla = $orian_settings['pickup_sla'];
                if (array_key_exists('businessday_end',$orian_settings))
                    $this->businessday_endtime = $orian_settings['businessday_end'];
            }
            $this->orian_cities = get_option('orian_cities');
            if ($this->orian_cities) {
                sort($this->orian_cities);
            add_filter( 'woocommerce_checkout_fields' , array($this,'custom_override_city_fields') );
            add_action( 'wp_footer',array($this,'custom_script_for_sla') );
            }
            add_filter( 'manage_edit-shop_order_columns', array($this, 'orders_sla_column'), 20);
            add_action( 'manage_shop_order_posts_custom_column', array($this, 'orders_sla_column_info') );
            if (is_admin())
            add_filter( 'post_class', array($this,'add_custom_class'), 10, 3 );
        }
        public function add_custom_class($classes, $class, $post_id ) {
            $currentScreen = get_current_screen();
            if( $currentScreen->id === "edit-shop_order" ) {
                $now = new DateTime("now",$this->timezone);
                $enddate = $this->get_sla_end_datetime($post_id);
                $diff = $now->diff($enddate);
                $order = wc_get_order($post_id);
                if ($order->get_status() !== "osc-delivered") {
                if ($now < $enddate && $diff->format("%d") === "0")
                    $classes[] = 'due-soon';
                elseif ($now > $enddate)
                    $classes[] = 'order-late';
                }
            }
            return $classes;
        }
        public function orders_sla_column($cols) {
            $cols['sla_details'] = __('SLA','orian-shipping-carrier');
            return $cols;
        }
        public function orders_sla_column_info($col) {
            global $post;
            if ('sla_details' === $col) {
                $now = new DateTime("now",$this->timezone);
                $enddate = $this->get_sla_end_datetime($post->ID);
                $diff = $now->diff($enddate);
                if ($now < $enddate)
                    echo '<p class="sla-time">'.$diff->format(__('%d days %h hours %i minutes Remaining','orian-shipping-carrier')).'</p>';
                else
                    echo '<p class="sla-time">'.$diff->format(__('%d days %h hours %i minutes Late','orian-shipping-carrier')).'</p>';
            }
        }
        public function get_sla_end_datetime($orderid) {
            $order = wc_get_order($orderid);
            $order_details = $order->get_data();
            $order_date = $order->get_date_created();
            $pudo_shipping = false;
            foreach($order->get_items("shipping") as $item_key => $item) {
                if ($item->get_method_id() === orian_shipping()->pudo_method_id)
                $pudo_shipping = true;
            }
            $sladays = get_post_meta($orderid,'sla',true);
            $sla = array();
            if (!$sladays) {
                if ($pudo_shipping) {
                    $sladays = $this->pudo_sla;
                } else {
                    $selected_city = $order_details['billing']['city'];
                    $selected_city_far = array($selected_city,"0");
                    if ($this->orian_cities) {
                        if (in_array($selected_city_far,$this->orian_cities)) {
                            $sladays = $this->home_far_sla;
                        } else {
                            $sladays = $this->home_regular_sla;
                        }
                    } else {
                        $sladays = $this->home_regular_sla;
                    }
                }
            }
            $sla = $this->business_days_to_date($sladays,$order_date);
            $enddate = $sla[2];
            return $enddate;
        }
        public function business_days_to_date($days, $orderdate = null) {
            $response = array();
            if ($orderdate === null)
            $now = new DateTime("now",$this->timezone);
            else
            $now = new DateTime($orderdate);
            $response[0] = $this->date_sla_format($now);
            $nextday = $now;
            $endtime = new DateTime($this->businessday_endtime, $this->timezone);
            $now_nonbusiness = false;
            if ($nextday->format('w') === "5" || $nextday->format('w') === "6" || in_array($nextday->format('d/m/Y'),$this->nonbusiness_days)) {
                $now_nonbusiness = true;
            }
            $firstday = 1;
            if ($now_nonbusiness) {
                $days++;
                $firstday = 2;
            }
            if ($now > $endtime) {
                $days++;
                $firstday = 2;
                if ($now_nonbusiness)
                    $firstday = 3;
            }
            for($i = 1; $i <= $days; $i++) {
                //$nextday = new DateTime("+$i days", $this->timezone);
                $nextday->modify("+1 days");
                if ($nextday->format('w') === "5" || $nextday->format('w') === "6" || in_array($nextday->format('d/m/Y'),$this->nonbusiness_days)) {
                    $days++;
                    $firstday++;
                }
                if ($i == $firstday)
                    $response[0] = $this->date_sla_format($nextday);
            }
            $response[1] = $this->date_sla_format($nextday);
            $response[2] = $nextday;
            return $response;
        }
        public function date_sla_format($date) {
            $weekdays = array(
                __('Sunday','orian-shipping-carrier'),
                __('Monday','orian-shipping-carrier'),
                __('Tuesday','orian-shipping-carrier'),
                __('Wednesday','orian-shipping-carrier'),
                __('Thursday','orian-shipping-carrier'),
                __('Friday','orian-shipping-carrier'),
                __('Saturday','orian-shipping-carrier')
            );
            $dayofweek = intval($date->format('w'));
            return $weekdays[$dayofweek] . ' ' . $date->format('d/m');
        }
        public function get_delivery_date($sla_type,$orderdate = null) {
            $delivery_dates = array();
            switch ($sla_type) {
                case 'home':
                    $numberofdays = $this->home_regular_sla;
                    break;
                case 'far':
                    $numberofdays = $this->home_far_sla;
                    break;
                case 'pudo':
                    $numberofdays = $this->pudo_sla;
                    break;
            }
            if (isset($numberofdays)) {
            $numberofdays = intval($numberofdays);
            $delivery_dates = $this->business_days_to_date($numberofdays, $orderdate);
            }
            return $delivery_dates;
        }
        public function custom_override_city_fields( $fields ) {
            $original_city_fields = $fields['billing']['billing_city'];
            $fields['billing']['billing_city'] = array(
               'label'      => $original_city_fields['label'],
               'type'       => 'select',
               'required'   => true,
               'class'      => array('form-row-wide'),
               'clear'      => true,
               'options'    => array(),
               'priority'   => $original_city_fields['priority'],
            );
            $options = array(''=>__('Select City','orian-shipping-carrier'));
            foreach ($this->orian_cities as $orian_city) {
                $options[$orian_city[0]] = $orian_city[0];
            }
            $fields['billing']['billing_city']['options'] = $options;
            return $fields;
        }
        public function custom_script_for_sla() {
            $my_orian_cities = array();
            foreach($this->orian_cities as $orian_city) {
                $my_orian_cities[$orian_city[0]] = $orian_city[1];
            }
            ?>
            <script>
                var sla_cities = <?php echo json_encode($my_orian_cities,JSON_UNESCAPED_UNICODE); ?>;
                jQuery(document).ready(function() {
                    jQuery("#billing_city_field").append('<div style="margin-top:5px;" id="billing_city_extra"></div>');
                    function sla_init() {
                        jQuery("#billing_city").select2();
                    }
                    function set_sla_message() {
                        if (sla_cities[jQuery("#billing_city").val()] == "0") {
                            jQuery("#billing_city_extra").html("<?php printf(__('City is far destination please except delivery time of up to %1$s Business Days.','orian-shipping-carrier'),$this->home_far_sla); ?>");
                        } else {
                            jQuery("#billing_city_extra").html("");
                        }
                    }
                    sla_init();
                    set_sla_message();
                    jQuery(document.body).on('updated_checkout',function() {
                        sla_init();
                    });
                    jQuery("#billing_city").on("change",function() {
                        set_sla_message();
                    });
                });
                </script>
            <?php
        }
        public function sla_city_text($field,$key) {
            if ($key === "billing_city") {
                $field .= '<div id="sla_city_text">test</div>';
            }
            return $field;
        }
    }
}