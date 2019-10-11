<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class License extends REST {

    private $db = NULL;
    private $LICENSE_URL = 'http://dream-space.web.id/sub/purchase-check/checkWTN360';
    private $conf = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->conf = new CONF(); // Create conf class
    }

    public function checkLicense() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $query = "SELECT * FROM license LIMIT 1";
        if ($this->conf->DEMO_VERSION) $this->show_response_plain('ACTIVE');
        $license = $this->db->get_one($query);
        if(count($license) > 0){
            if (!preg_match("/^(\w{8})-((\w{4})-){3}(\w{12})$/", $license['purchase_code'])){
                $this->show_response_plain('INACTIVE');
            }
            $this->show_response_plain('ACTIVE');
        }
        $this->show_response_plain('INACTIVE');
    }

    public function updateLicense() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['code'])) $this->responseInvalidParam();
        $code = $this->_request['code'];
        $response = $this->executeCurl($code);
        if($response != null && $response['status'] == 'success'){
            $this->db->execute_query("DELETE FROM license WHERE id > 0");
            $this->db->execute_query("INSERT INTO license (purchase_code) VALUES ('$code')");
        }
        $this->show_response($response);
    }

    private function executeCurl($code) {
        if (!function_exists('curl_init')) return;
        $url = $this->LICENSE_URL;
        $curl = curl_init();

        $header = array();
        $header[] = 'Content-Type: application/json';
        $data = array( 'purchase_code' => $code);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->json($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

?>