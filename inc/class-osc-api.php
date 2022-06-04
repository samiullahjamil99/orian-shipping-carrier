<?php
defined( 'ABSPATH' ) || exit;

class OSC_API {
    protected $orian_options;
    protected $authtoken;
    protected $firsttimecall;
    public $api_url = "https://disapi.orian.com";
    public function __construct() {
        $this->orian_options = get_option('orian_main_setting');
        $this->firsttimecall = true;
        if (isset($this->orian_options)) {
            $this->authtoken = $this->orian_options['authtoken'];
        }
    }
    public function authorize_login() {
        $this->firsttimecall = false;
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
            if (is_wp_error($response))
               return "error";
            $this->orian_options['authtoken'] = wp_remote_retrieve_header($response,'authtoken');
            update_option('orian_main_setting',$this->orian_options);
            $this->authtoken = $this->orian_options['authtoken'];
            return $this->authtoken;
        }
        return false;
    }
    public function delete_auth() {
        if ($this->logged_in()) {
            $this->authtoken = null;
            unset($this->orian_options['authtoken']);
            update_option('orian_main_setting',$this->orian_options);
        }
    }
    public function logout() {
        $logout_url = $this->api_url . "/Logout";
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'AuthToken' => $this->authtoken,
            ),
        );
        $response = wp_remote_request($logout_url, $args);
        //return $response;
        if (!is_wp_error($response)) {
            if ($response['response']['code'] == 200 || $response['response']['code'] == 401) {
                $this->delete_auth();
                return true;
            }
        }
        return false;
    }
    public function logged_in() {
        if (array_key_exists('authtoken',$this->orian_options)) {
            return true;
        }
        return false;
    }
    public function get_pudo_points($city,$address = "",$pudotype="",$distance="999999") {
        $return_response = array();
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
            if (is_wp_error($response)) {
              $return_response['status'] = 408;
            } else {
                $return_response['status'] = $response['response']['code'];
                if ( $response['response']['code'] == 200 ) {
                    $response_body = $response['body'];
                    $response_body = str_replace('\"', '"', $response_body);
                    $response_body = substr($response_body, 1, -1);
                    $xml = simplexml_load_string($response_body,null,LIBXML_NOCDATA);
                    $pudos = array();
                    foreach($xml->children() as $pudo) {
                        $mypudo = array();
                        $mypudo['pudoid'] = (string) $pudo->PUDOID;
                        $mypudo['pudoname'] = (string) $pudo->PUDONAME;
                        $mypudo['pudotype'] = (string) $pudo->PUDOTYPE;
                        $mypudo['accessibility'] = (string) $pudo->ACCESSIBILITY;
                        $mypudo['contactid'] = (string) $pudo->CONTACTID;
                        $mypudo['pudocity'] = (string) $pudo->PUDOCITY;
                        $mypudo['pudoaddress'] = (string) $pudo->PUDOADDRESS;
                        $pudos[] = $mypudo;
                    }
                    $return_response['data'] = $pudos;
                } elseif ( $response['response']['code'] == 401 && $this->firsttimecall) {
                    $this->delete_auth();
                    $return_response = $this->get_pudo_points($city,$address,$pudotype,$distance);
                }
            }
        }
        return $return_response;
    }
}