<?php
defined( 'ABSPATH' ) || exit;

final class OSC_API {
    protected $orian_options;
    protected $authtoken;
    protected $firsttimecall;
    public $api_url = "https://disapi.orian.com";
    public function __construct() {
        $this->orian_options = get_option('orian_main_setting');
        $this->firsttimecall = true;
        $this->authtoken = get_option('orian_login_authtoken');
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
            $this->authtoken = wp_remote_retrieve_header($response,'authtoken');
            update_option('orian_login_authtoken',$this->authtoken);
            return $this->authtoken;
        }
        return false;
    }
    public function delete_auth() {
        if ($this->logged_in()) {
            $this->authtoken = null;
            delete_option('orian_login_authtoken');
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
        if (isset($this->authtoken)) {
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
                    if ($response_body[0] === '"')
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
    public function get_transportation_order_status($orderid,$packagenumber=1) {
        $return_response = array();
        $order = wc_get_order($orderid);
        if (!$this->logged_in()) {
            $this->authorize_login();
        }
        if ($this->logged_in() && $order) {
            $consignee = $this->orian_options['consignee'];
            $packageid = "KKO" . $orderid;
            if ($packagenumber > 1)
            $packageid .= "P" . $packagenumber;
            $status_url = $this->api_url . '/GetPackageStatus?consignee=' . $consignee . '&package=' . $packageid;
            $args = array(
                'method' => 'GET',
                'timeout' => 30,
                'headers' => array(
                    'AuthToken' => $this->authtoken,
                ),
            );
            $response = wp_remote_request($status_url, $args);
            if (is_wp_error($response)) {
                $return_response['status'] = 408;
              } else {
                  $return_response['status'] = $response['response']['code'];
                  if ( $response['response']['code'] == 200 ) {
                    $response_body = $response['body'];
                    $response_body = str_replace('\"', '"', $response_body);
                    if ($response_body[0] === '"')
                    $response_body = substr($response_body, 1, -1);
                    $xml = simplexml_load_string($response_body,null,LIBXML_NOCDATA);
                    $pkg_status = (string) $xml->DATA->PACKAGESTATUS;
                    $return_response['package_status'] = $pkg_status;
                  } elseif ( $response['response']['code'] == 401 && $this->firsttimecall) {
                      $this->delete_auth();
                      $return_response = $this->get_transportation_order_status($orderid,$packagenumber);
                  } else {
                    $return_response['data'] = $response['body'];
                  }
              }
        }
        return $return_response;
    }
    public function generate_transportation_order($orderid,$numberofpackages=1) {
        $return_response = array();
        $order = wc_get_order($orderid);
        if (!$this->logged_in()) {
            $this->authorize_login();
        }
        if ($this->logged_in() && $order) {
            $order_details = $order->get_data();
        $consignee = $this->orian_options['consignee'];
        $ref2 = $this->orian_options['referenceorder2'];
        $source_street1 = $this->orian_options['source_street1'];
        $source_sitename = $this->orian_options['source_sitename'];
        $source_city = $this->orian_options['source_city'];
        $source_contact1name = $this->orian_options['source_contact1name'];
        $source_contact1phone = $this->orian_options['source_contact1phone'];

        $billing_business_name_invoice = get_post_meta($orderid,'billing_business_name_invoice',true);
        $billing_business_name = get_post_meta($orderid,'billing_business_name',true);
        $billing_business_type_address = get_post_meta($orderid,'billing_business_type_address',true);
        $billing_apartment = get_post_meta($orderid,'billing_apartment',true);
        $billing_floor = get_post_meta($orderid,'billing_floor',true);
        $billing_intercom_code = get_post_meta($orderid,'billing_intercom_code',true);
        $shipping_remarks = get_post_meta($orderid,'shipping_remarks',true);
        $sitename = $order_details['billing']['first_name'] . ' ' . $order_details['billing']['last_name'];
        if ($billing_business_name_invoice === '1')
        $sitename = $billing_business_name;
        $addresstype = "03";
        if ($billing_business_type_address === '1')
        $addresstype = "02";
        $deliveryremarks = "";
        if ($billing_floor && !empty($billing_floor))
        $deliveryremarks .= "קומה:" . $billing_floor . ",";
        if ($billing_apartment && !empty($billing_apartment))
        $deliveryremarks .= "דירה:" . $billing_apartment . ",";
        if ($billing_intercom_code && !empty($billing_intercom_code))
        $deliveryremarks .= "קוד לאינטרקום:". $billing_intercom_code . ",";
        if (!empty($shipping_remarks))
        $deliveryremarks .= $shipping_remarks;
        $packages_xml = '<PACKAGE>
        <PACKAGEID>KKO'.$orderid.'</PACKAGEID>
        <PACKAGEREFID></PACKAGEREFID>
        <PACKAGETYPE>02</PACKAGETYPE>
        <DOCUMENTTYPE>TRANSPORTATION</DOCUMENTTYPE>
        <CONSIGNEE>'.$consignee.'</CONSIGNEE>
        <DOCUMENTID/>
        </PACKAGE>';
        if ($numberofpackages > 1) {
            for ($i = 2; $i <= $numberofpackages; $i++) {
                $packageid = 'KKO' . $orderid . 'P' . $i;
                $packages_xml .= '<PACKAGE>
        <PACKAGEID>'.$packageid.'</PACKAGEID>
        <PACKAGEREFID></PACKAGEREFID>
        <PACKAGETYPE>02</PACKAGETYPE>
        <DOCUMENTTYPE>TRANSPORTATION</DOCUMENTTYPE>
        <CONSIGNEE>'.$consignee.'</CONSIGNEE>
        <DOCUMENTID/>
        </PACKAGE>';
            }
        }
        $generate_order_xml = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
        <DATACOLLECTION>
            <DATA>
            <TABLENAME>TRANSPORTATIONORDER</TABLENAME>
            <CONSIGNEE>$consignee</CONSIGNEE>
            <TRANSPORTATIONORDERID/>
            <ORDERTYPE>REGULAR</ORDERTYPE>
            <STATUS/>
            <PAYINGCUSTOMER/>
            <SOURCECOMPANY/>
            <SOURCECOMPANYTYPE/>
            <SOURCECONTACTID/>
            <TARGETCOMPANY/>
            <TARGETCOMPANYTYPE/>
            <TARGETCONTACTID/>
            <PICKUPBRANCH/>
            <DELIVERYBRANCH/>
            <PICKUPDEPOT/>
            <DELIVERYDEPOT/>
            <DRAFTCREATEDATE/>
            <CREATEDATE/>
            <REQUESTEDPICKUPDATE/>
            <REQPICKUPENDDATE/>
            <REQUESTEDDELIVERYDATE/>
            <REQDELENDDATE/>
            <REQUESTEDORIGINALDATE/>
            <SCHEDULEDDATE/>
            <STATUSDATE/>
            <COMPLETIONDATE/>
            <HOSTORDERID/>
            <REFERENCEORDER>$orderid</REFERENCEORDER>
            <REFERENCEORDER2>$ref2</REFERENCEORDER2>
            <DELIVERYNOTE/>
            <INTERNALDELIVERYNOTE/>
            <CONTAINERNUMBER/>
            <REFCOMPANYCODE/>
            <REFCOMPANYNAME/>
            <REFCOMPANYCONTACT/>
            <PACKAGETYPE>02</PACKAGETYPE>
            <UNITS>$numberofpackages</UNITS>
            <ORIGINALUNITS>0</ORIGINALUNITS>
            <ORDERWEIGHT>0</ORDERWEIGHT>
            <ORDERVOLUME>0</ORDERVOLUME>
            <ORDERVALUE>0</ORDERVALUE>
            <TRANSPORTATIONTYPE>DOMESTIC</TRANSPORTATIONTYPE>
            <SERVICETYPE>NEXTDAY</SERVICETYPE>
            <TRANSPORTATIONCLASS/>
            <HAZARDCLASS/>
            <HAZARDCOMMENTS/>
            <CARGOTYPE/>
            <LOADTYPE/>
            <SECURITYCLASS/>
            <STORAGELOCATION/>
            <NOTES/>
            <PICKUPCOMMENTS/>
            <DELIVERYCOMMENTS>$deliveryremarks</DELIVERYCOMMENTS>
            <CHARGECOMMENTS/>
            <ORDERPRICE>0</ORDERPRICE>
            <CALCULATEDPRICE>0</CALCULATEDPRICE>
            <PRICECALCULATIONDATE/>
            <CHARGEID/>
            <AGREEMENTCODE/>
            <CHARGED>0</CHARGED>
            <CARRIERCREDITED>0</CARRIERCREDITED>
            <ORDERCOST>0</ORDERCOST>
            <CALCULATEDCOST>0</CALCULATEDCOST>
            <COSTCALCULATIONDATE/>
            <COSTCHARGEID/>
            <PAYMENTTYPE>CREDIT</PAYMENTTYPE>
            <ORIGINALORDERID/>
            <COLLECTNEEDED>0</COLLECTNEEDED>
            <COLLECTSUM>0</COLLECTSUM>
            <COLLECTCHEQUE1>0</COLLECTCHEQUE1>
            <COLLECTCHEQUE1DATE/>
            <COLLECTCHEQUE2>0</COLLECTCHEQUE2>
            <COLLECTCHEQUE2DATE/>
            <COLLECTCHEQUE3>0</COLLECTCHEQUE3>
            <COLLECTCHEQUE3DATE/>
            <COLLECTRECEIPT/>
            <RETURNPACKAGE>0</RETURNPACKAGE>
            <RETURNPACKAGETYPE/>
            <SIGNEDDOC>0</SIGNEDDOC>
            <CONFDOC>0</CONFDOC>
            <ORDERPRIORITY>0</ORDERPRIORITY>
            <DELIVERYCONFIRMATIONTYPE/>
            <IDPIC/>
            <ACTIVITYSTATUS/>
            <SHORTAGE>0</SHORTAGE>
            <UNKNOWNPACKAGES>0</UNKNOWNPACKAGES>
            <ROUTINGSET/>
            <CHKPNT/>
            <DELIVERYPOINT/>
            <MANUALHANDLING>0</MANUALHANDLING>
            <SCHEDULINGSTATUS/>
            <SCHEDULINGFAILCODE/>
            <SCHEDULINGFAILNOTES/>
            <SCHEDULINGSTATUSDATE/>
            <DELIVERYLOCATION/>
            <DELIVERYRECIPIENT/>
            <ADDDATE/>
            <ADDUSER/>
            <EDITDATE/>
            <EDITUSER/>
            <SOURCECONTACT>
                <CONTACTTYPE>PICKUP</CONTACTTYPE>
                <CONTACTID/>
                <STREET1>$source_street1</STREET1>
                <STREET2/>
                <FLOOR/>
                <CITY>$source_city</CITY>
                <ORIGINALADDRESS/>
                <SITENAME>$source_sitename</SITENAME>
                <CONTACT1NAME>$source_contact1name</CONTACT1NAME>
                <CONTACT1PHONE>$source_contact1phone</CONTACT1PHONE>
                <ADDRESSTYPE>02</ADDRESSTYPE>
                <CONTACT2PHONE/>
                <CONTACTIDNUMBE/>
                <CONTACT1EMAIL/>
            </SOURCECONTACT>
            <TARGETCONTACT>
                <CONTACTTYPE>DELIVERY</CONTACTTYPE>
                <CONTACTID/>
                <STREET1>" . $order_details['billing']['address_1'] . "</STREET1>
                <STREET2/>
                <FLOOR/>
                <CITY>" . $order_details['billing']['city'] . "</CITY>
                <ORIGINALADDRESS/>
                <SITENAME>$sitename</SITENAME>
                <ORDERWEIGHT/>
                <CONTACT1NAME>" . $order_details['billing']['first_name'] . " " . $order_details['billing']['last_name'] ."</CONTACT1NAME>
                <CONTACT1PHONE>" . $order_details['billing']['phone'] . "</CONTACT1PHONE>
                <CONTACT2PHONE/>
                <CONTACT1EMAIL/>
                <CONTACTIDNUMBE/>
                <ADDRESSTYPE>$addresstype</ADDRESSTYPE>
            </TARGETCONTACT>
            <PACKAGES>
            $packages_xml
            </PACKAGES>
            </DATA>
        </DATACOLLECTION>";
        //$return_response['sentheader'] = $generate_order_xml;
        $order_url = $this->api_url . '/CreateTransportationOrder';
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'AuthToken' => $this->authtoken,
                'Content-Type' => "application/xml",
            ),
            'body' => $generate_order_xml,
        );
        $response = wp_remote_request($order_url, $args);
        if (is_wp_error($response)) {
            $return_response['status'] = 408;
          } else {
              $return_response['status'] = $response['response']['code'];
              if ( $response['response']['code'] == 200 ) {
                $response_body = $response['body'];
                $response_body = str_replace('\"', '"', $response_body);
                if ($response_body[0] === '"')
                $response_body = substr($response_body, 1, -1);
                $xml = simplexml_load_string($response_body);
                $success = (string) $xml->RESPONSE->SUCCESS;
                $return_response['success'] = $success;
                if ($success === "false") {
                    $error = (string) $xml->RESPONSE->RESPONSEERROR;
                    $return_response['error'] = $error;
                    update_post_meta($orderid,'Orian Error', $error);
                }
              } elseif ( $response['response']['code'] == 401 && $this->firsttimecall) {
                  $this->delete_auth();
                  $return_response = $this->generate_transportation_order($orderid,$numberofpackages);
              } else {
                $return_response['data'] = $response['body'];
              }
          }
        }
        return $return_response;
    }
    public function generate_pudo_transportation_order($orderid) {
        $return_response = array();
        $order = wc_get_order($orderid);
        if (!$this->logged_in()) {
            $this->authorize_login();
        }
        if ($this->logged_in() && $order) {
            $order_details = $order->get_data();
            $consignee = $this->orian_options['consignee'];
            $ref2 = $this->orian_options['referenceorder2'];
            $source_street1 = $this->orian_options['source_street1'];
            $source_sitename = $this->orian_options['source_sitename'];
            $source_city = $this->orian_options['source_city'];
            $source_contact1name = $this->orian_options['source_contact1name'];
            $source_contact1phone = $this->orian_options['source_contact1phone'];
            $target_contactid = get_post_meta($orderid, 'pudo_point',true);
            $packages_xml = '<PACKAGE>
            <PACKAGEID>KKO'.$orderid.'</PACKAGEID>
            <PACKAGEREFID></PACKAGEREFID>
            <PACKAGETYPE>02</PACKAGETYPE>
            <DOCUMENTTYPE>TRANSPORTATION</DOCUMENTTYPE>
            <CONSIGNEE>'.$consignee.'</CONSIGNEE>
            <DOCUMENTID/>
            </PACKAGE>';
            $generate_order_xml = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
        <DATACOLLECTION>
            <DATA>
            <TABLENAME>TRANSPORTATIONORDER</TABLENAME>
            <CONSIGNEE>$consignee</CONSIGNEE>
            <TRANSPORTATIONORDERID/>
            <ORDERTYPE>REGULAR</ORDERTYPE>
            <STATUS/>
            <PAYINGCUSTOMER/>
            <SOURCECOMPANY/>
            <SOURCECOMPANYTYPE/>
            <SOURCECONTACTID/>
            <TARGETCOMPANY/>
            <TARGETCOMPANYTYPE/>
            <TARGETCONTACTID>$target_contactid</TARGETCONTACTID>
            <PICKUPBRANCH/>
            <DELIVERYBRANCH/>
            <PICKUPDEPOT/>
            <DELIVERYDEPOT/>
            <DRAFTCREATEDATE/>
            <CREATEDATE/>
            <REQUESTEDPICKUPDATE/>
            <REQPICKUPENDDATE/>
            <REQUESTEDDELIVERYDATE/>
            <REQDELENDDATE/>
            <REQUESTEDORIGINALDATE/>
            <SCHEDULEDDATE/>
            <STATUSDATE/>
            <COMPLETIONDATE/>
            <TARGETPUDONAME>" . $order_details['billing']['first_name'] . " " . $order_details['billing']['last_name'] ."</TARGETPUDONAME>
            <TARGETPUDOPHONE>" . $order_details['billing']['phone'] . "</TARGETPUDOPHONE>
            <HOSTORDERID/>
            <REFERENCEORDER>$orderid</REFERENCEORDER>
            <REFERENCEORDER2>$ref2</REFERENCEORDER2>
            <DELIVERYNOTE/>
            <INTERNALDELIVERYNOTE/>
            <CONTAINERNUMBER/>
            <REFCOMPANYCODE/>
            <REFCOMPANYNAME/>
            <REFCOMPANYCONTACT/>
            <PACKAGETYPE>02</PACKAGETYPE>
            <UNITS>1</UNITS>
            <ORIGINALUNITS>0</ORIGINALUNITS>
            <ORDERWEIGHT>0</ORDERWEIGHT>
            <ORDERVOLUME>0</ORDERVOLUME>
            <ORDERVALUE>0</ORDERVALUE>
            <TRANSPORTATIONTYPE>DOMESTIC</TRANSPORTATIONTYPE>
            <SERVICETYPE>NEXTDAY</SERVICETYPE>
            <TRANSPORTATIONCLASS/>
            <HAZARDCLASS/>
            <HAZARDCOMMENTS/>
            <CARGOTYPE/>
            <LOADTYPE/>
            <SECURITYCLASS/>
            <STORAGELOCATION/>
            <NOTES/>
            <PICKUPCOMMENTS/>
            <DELIVERYCOMMENTS/>
            <CHARGECOMMENTS/>
            <ORDERPRICE>0</ORDERPRICE>
            <CALCULATEDPRICE>0</CALCULATEDPRICE>
            <PRICECALCULATIONDATE/>
            <CHARGEID/>
            <AGREEMENTCODE/>
            <CHARGED>0</CHARGED>
            <CARRIERCREDITED>0</CARRIERCREDITED>
            <ORDERCOST>0</ORDERCOST>
            <CALCULATEDCOST>0</CALCULATEDCOST>
            <COSTCALCULATIONDATE/>
            <COSTCHARGEID/>
            <PAYMENTTYPE>CREDIT</PAYMENTTYPE>
            <ORIGINALORDERID/>
            <COLLECTNEEDED>0</COLLECTNEEDED>
            <COLLECTSUM>0</COLLECTSUM>
            <COLLECTCHEQUE1>0</COLLECTCHEQUE1>
            <COLLECTCHEQUE1DATE/>
            <COLLECTCHEQUE2>0</COLLECTCHEQUE2>
            <COLLECTCHEQUE2DATE/>
            <COLLECTCHEQUE3>0</COLLECTCHEQUE3>
            <COLLECTCHEQUE3DATE/>
            <COLLECTRECEIPT/>
            <RETURNPACKAGE>0</RETURNPACKAGE>
            <RETURNPACKAGETYPE/>
            <SIGNEDDOC>0</SIGNEDDOC>
            <CONFDOC>0</CONFDOC>
            <ORDERPRIORITY>0</ORDERPRIORITY>
            <DELIVERYCONFIRMATIONTYPE/>
            <IDPIC/>
            <ACTIVITYSTATUS/>
            <SHORTAGE>0</SHORTAGE>
            <UNKNOWNPACKAGES>0</UNKNOWNPACKAGES>
            <ROUTINGSET/>
            <CHKPNT/>
            <DELIVERYPOINT/>
            <MANUALHANDLING>0</MANUALHANDLING>
            <SCHEDULINGSTATUS/>
            <SCHEDULINGFAILCODE/>
            <SCHEDULINGFAILNOTES/>
            <SCHEDULINGSTATUSDATE/>
            <DELIVERYLOCATION/>
            <DELIVERYRECIPIENT/>
            <ADDDATE/>
            <ADDUSER/>
            <EDITDATE/>
            <EDITUSER/>
            <SOURCECONTACT>
                <CONTACTTYPE>PICKUP</CONTACTTYPE>
                <CONTACTID/>
                <STREET1>$source_street1</STREET1>
                <STREET2/>
                <FLOOR/>
                <CITY>$source_city</CITY>
                <ORIGINALADDRESS/>
                <SITENAME>$source_sitename</SITENAME>
                <CONTACT1NAME>$source_contact1name</CONTACT1NAME>
                <CONTACT1PHONE>$source_contact1phone</CONTACT1PHONE>
                <ADDRESSTYPE>02</ADDRESSTYPE>
                <CONTACT2PHONE/>
                <CONTACTIDNUMBE/>
                <CONTACT1EMAIL/>
            </SOURCECONTACT>
            <TARGETCONTACT>
                <CONTACTTYPE>DELIVERY</CONTACTTYPE>
                <CONTACTID/>
                <STREET1/>
                <STREET2/>
                <FLOOR/>
                <CITY/>
                <ORIGINALADDRESS/>
                <SITENAME/>
                <ORDERWEIGHT/>
                <CONTACT1NAME/>
                <CONTACT1PHONE/>
                <CONTACT2PHONE/>
                <CONTACT1EMAIL/>
                <CONTACTIDNUMBE/>
                <ADDRESSTYPE/>
            </TARGETCONTACT>
            <PACKAGES>
            $packages_xml
            </PACKAGES>
            </DATA>
        </DATACOLLECTION>";
        $order_url = $this->api_url . '/CreateTransportationOrder';
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'AuthToken' => $this->authtoken,
                'Content-Type' => "application/xml",
            ),
            'body' => $generate_order_xml,
        );
        $response = wp_remote_request($order_url, $args);
        if (is_wp_error($response)) {
            $return_response['status'] = 408;
          } else {
              $return_response['status'] = $response['response']['code'];
              if ( $response['response']['code'] == 200 ) {
                $response_body = $response['body'];
                $response_body = str_replace('\"', '"', $response_body);
                if ($response_body[0] === '"')
                $response_body = substr($response_body, 1, -1);
                $xml = simplexml_load_string($response_body);
                $success = (string) $xml->RESPONSE->SUCCESS;
                $return_response['success'] = $success;
                if ($success === "false") {
                    $error = (string) $xml->RESPONSE->RESPONSEERROR;
                    $return_response['error'] = $error;
                    update_post_meta($orderid,'Orian Error', $error);
                }
              } elseif ( $response['response']['code'] == 401 && $this->firsttimecall) {
                  $this->delete_auth();
                  $return_response = $this->generate_transportation_order($orderid,$numberofpackages);
              } else {
                $return_response['data'] = $response['body'];
              }
          }
        }
        return $return_response;
    }
}