<?php
defined( 'ABSPATH' ) || exit;

class OSC_API {
    protected $username;
    protected $password;
    public $data_available = false;
    public $api_url = "https://disapi.orian.com";
    public function __construct() {
        $orian_options = get_option('orian_main_setting');
        if (isset($orian_options)) {
            $this->username = $orian_options['username'];
            $this->password = $orian_options['password'];
            if (!empty($this->username) && !empty($this->password))
                $this->data_available = true;
        }
    }
    public function authorize_login() {
        $login_url = $this->api_url . "/Login"; 
        if ($this->data_available) {
            $args = array(
                'method' => 'POST',
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ),
                ),
            );
            $response = wp_remote_request($login_url, $args);
            return wp_remote_retrieve_header($response,'authtoken');
        }
        return false;
    }
}