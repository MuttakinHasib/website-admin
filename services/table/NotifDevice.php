<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/../conf.php"));

class NotifDevice extends REST {

    private $mysqli = NULL;
    private $db = NULL;
    public $conf = NULL;
    private $MAX_PAGE = 1000;
    private $notif_history  = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
        $this->conf = new CONF(); // Create config class
        $this->notif_history = new NotifHistory($this->db);
    }

    public function findOneByDeviceId() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['device_id'])) $this->responseInvalidParam();
        $device_id = $this->_request['device_id'];
        $this->show_response($this->findByDeviceId($device_id));
    }

    public function findAll() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $query = "SELECT DISTINCT * FROM notif_device nd ORDER BY nd.last_update DESC";
        $this->show_response($this->db->get_list($query));
    }

    public function allCount() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $q = "";
        if (isset($this->_request['q'])) $q = $this->_request['q'];
        if ($q != "") {
            $query = "SELECT COUNT(DISTINCT nd.id) FROM notif_device nd "
                    ."WHERE device_name REGEXP '$q' OR device_id REGEXP '$q' OR app_version REGEXP '$q' OR os_version REGEXP '$q' ";
        } else {
            $query = "SELECT COUNT(DISTINCT nd.id) FROM notif_device nd";
        }
        $this->show_response_plain($this->db->get_count($query));
    }

    public function allCountPlain() {
        $query = "SELECT COUNT(DISTINCT nd.id) FROM notif_device nd";
        return $this->db->get_count($query);
    }

    public function findAllByPage() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['limit']) || !isset($this->_request['page'])) $this->responseInvalidParam();
        $limit = (int)$this->_request['limit'];
        $offset = ((int)$this->_request['page']) - 1;
        $q = "";
        if (isset($this->_request['q'])) $q = $this->_request['q'];
        if ($q != "") {
            $query = "SELECT DISTINCT * FROM notif_device nd "
                    ."WHERE device_name REGEXP '$q' OR device_id REGEXP '$q' OR app_version REGEXP '$q' OR os_version REGEXP '$q' "
                    ."ORDER BY nd.last_update DESC LIMIT $limit OFFSET $offset";
        } else {
            $query = "SELECT DISTINCT * FROM notif_device nd ORDER BY nd.last_update DESC LIMIT $limit OFFSET $offset";
        }
        $this->show_response($this->db->get_list($query));
    }

    public function findByDeviceId($device_id) {
        $query = "SELECT nd.* FROM notif_device nd WHERE nd.device_id='$device_id' LIMIT 1";
        return $this->db->get_one($query);
    }

    public function insertOne($fcm) {

        $device_id = $fcm['device_id'];
        $column_names = array('device_id', 'device_name', 'os_version', 'app_version', 'regid', 'created_at', 'last_update');
        $table_name = 'notif_device';
        $pk = 'id';

        $result = $this->findByDeviceId($device_id);
        $nowTime = round(microtime(true) * 1000);

        if (count($result) > 0) { // update
            $new_fcm['id'] = (int)$result['id'];
            $new_fcm['notif_device'] = $fcm;
            $new_fcm['notif_device']['created_at'] = (int)$result['created_at'];
            $new_fcm['notif_device']['last_update'] = $nowTime;
            $resp = $this->db->post_update($new_fcm['id'], $new_fcm, $pk, $column_names, $table_name);
        } else { // insert
            $fcm['created_at'] = $nowTime;
            $fcm['last_update'] = $nowTime;
            $resp = $this->db->post_one($fcm, $pk, $column_names, $table_name);
        }
        return $resp;
    }

    public function getAllRegId() {
        $query = "SELECT DISTINCT nd.regid FROM notif_device nd";
        return $this->db->get_list($query);
    }

    public function processNotification() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $body = json_decode(file_get_contents("php://input"), true);
        if (!isset($body['title']) || !isset($body['content'])) $this->responseInvalidParam();
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
            $this->show_response($m);
            return;
        }
        $reg_id = $body['reg_id'];
        $body['target'] = 'ALL';
        $to = $this->conf->FCM_TOPIC;
        $is_all = true;

        if ($reg_id != null && $reg_id != "") {
            $to = $reg_id;
            $body['target'] = 'SINGLE';
            $is_all = false;
        }
        $push_response = $this->sendPushNotification($to, $body);
        if (!$is_all && isset($push_response['results'][0]['error'])){
            $error = $push_response['results'][0]['error'];
            $this->show_response(array('status' => 'failed', 'msg' => $error));
        }
        $this->notif_history->insertOnePlain($body);

        $this->show_response(array('status' => 'success', 'msg' => 'Notification sent successfully'));
    }

    public function sendPushNotification($to, $data) {
        // Set POST variables
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array('to' => $to, 'data' => $data);
        $api_key = $this->conf->FCM_KEY;
        $headers = array('Authorization: key=' . $api_key, 'Content-Type: application/json');
        // Open connection
        $curl = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporary
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->json($fields));

        // Execute post
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

}

?>