<?php
defined( 'ABSPATH' ) || exit;

class OSC_API {
    protected $orian_options;
    protected $authtoken;
    public $api_url = "https://disapi.orian.com";
    public function __construct() {
        $this->orian_options = get_option('orian_main_setting');
        if (isset($this->orian_options)) {
            $this->authtoken = $this->orian_options['authtoken'];
        }
    }
    public function authorize_login() {
        $login_url = $this->api_url . "/Login";
        $username = $this->orian_options['username'];
        $password = $this->orian_options['password'];
        if (!empty($username) && !empty($password)) {
            $args = array(
                'method' => 'POST',
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
                ),
            );
            $response = wp_remote_request($login_url, $args);
            $this->orian_options['authtoken'] = wp_remote_retrieve_header($response,'authtoken');
            update_option('orian_main_setting',$this->orian_options);
            $this->authtoken = $this->orian_options['authtoken'];
            return $this->authtoken;
        }
        return false;
    }
    public function logout() {
        if ($this->logged_in()) {
            $this->authtoken = null;
            unset($this->orian_options['authtoken']);
            update_option('orian_main_setting',$this->orian_options);
        }
    }
    public function logged_in() {
        if (array_key_exists('authtoken',$this->orian_options)) {
            return true;
        }
        return false;
    }
    public function get_pudo_points($city,$address = "",$pudotype="",$distance="999999") {
        if (!$this->logged_in()) {
            $this->authorize_login();
        }
        if ($this->logged_in()) {
            $pudos_url = $this->api_url . "/pudo/GetPudos?city=" . $city . "&address=" . $address . "&pudotype=" . $pudotype . "&distance=" . $distance;
            $args = array(
                'method' => 'GET',
                'timeout' => 30,
                'headers' => array(
                    'AuthToken' => $this->authtoken,
                ),
            );
            $response = wp_remote_request($pudos_url, $args);
            $response_body = $response['body'];
            $response_body = str_replace('\"', "'", $response_body);
            $response_body = str_replace('"', "", $response_body);
            return $response_body;
            return wp_remote_retrieve_body($response);
        }
        return false;
    }
}