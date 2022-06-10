<?php
if (!class_exists("OSC_SLA")) {
    class OSC_SLA {
        public $timezone = 'Asia/Jerusalem';
        public function __construct() {
            $this->init();
        }
        public function init() {

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
                if ($nextday->format('w') === "5" || $nextday->format('w') === "6")
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