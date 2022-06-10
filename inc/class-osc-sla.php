<?php
if (!class_exists("OSC_SLA")) {
    class OSC_SLA {
        public $timezone = 'Asia/Jerusalem';
        public $nonbusiness_days;
        public function __construct() {
            $this->init();
        }
        public function init() {
            $orian_settings = get_option('orian_main_setting');
            if (isset($orian_settings) && array_key_exists('nonbusiness_days',$orian_settings))
            $this->nonbusiness_days = array_map('trim', explode(",",$orian_settings['nonbusiness_days']));
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
    }
}