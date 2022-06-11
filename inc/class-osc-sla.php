<?php
if (!class_exists("OSC_SLA")) {
    class OSC_SLA {
        public $timezone = 'Asia/Jerusalem';
        public $nonbusiness_days;
        public $home_regular_sla;
        public $home_far_sla;
        public $pudo_sla;
        public function __construct() {
            $this->init();
        }
        public function init() {
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
            }
        }
        public function business_days_to_date($days) {
            $response = array();
            $originaltimezone = date_default_timezone_get();
            date_default_timezone_set($this->timezone);
            $today = new DateTime("now");
            $response[0] = $this->date_sla_format($today);
            $nextday = $today;
            for($i = 1; $i <= $days; $i++) {
                $nextday = new DateTime("+$i days");
                if ($nextday->format('w') === "5" || $nextday->format('w') === "6" || in_array($nextday->format('d/m'),$this->nonbusiness_days))
                $days++;
            }
            $response[1] = $this->date_sla_format($nextday);
            date_default_timezone_set($originaltimezone);
            return $response;
        }
        public function date_sla_format($date) {
            $weekdays = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
            $dayofweek = intval($date->format('w'));
            return $weekdays[$dayofweek] . ' ' . $date->format('d/m');
        }
        public function get_delivery_date($sla_type) {
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
            $delivery_dates = $this->business_days_to_date($numberofdays);
            }
            return $delivery_dates;
        }
    }
}