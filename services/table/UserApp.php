<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/../conf.php"));

class UserApp extends REST {

    private $mysqli = NULL;
    private $db = NULL;
    private $conf = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
        $this->conf = new CONF(); // Create conf class
    }

    public function findOne() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $id = (int)$this->_request['id'];
        $query = "SELECT id, name, email, image, status, notif_device, created_at, last_update FROM user_app WHERE id=$id";
        $this->show_response($this->db->get_one($query));
    }

    public function findOneByEmailPassword($email, $password) {
        $query = "SELECT * FROM user_app WHERE password='$password' AND email='$email' ";
        return $this->db->get_one($query);
    }

    public function findOneByEmail($email) {
        $query = "SELECT * FROM user_app WHERE email='$email' ";
        return $this->db->get_one($query);
    }

    public function findOneById($id) {
        $query = "SELECT * FROM user_app WHERE id='$id' ";
        return $this->db->get_one($query);
    }

    public function insertOne() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
            $this->show_response($m);
        }
        $user = json_decode(file_get_contents("php://input"), true);
        $resp = $this->insertOneObj($user);
        $this->show_response($resp);
    }

    public function insertOneObj($user) {
        $column_names = array('name', 'email', 'password', 'image', 'status', 'notif_device', 'created_at', 'last_update');
        $table_name = 'user_app';
        $pk = 'id';
        return $this->db->post_one($user, $pk, $column_names, $table_name);
    }

    public function updateOneObj($data) {
        $id = (int)$data['id'];
        $column_names = array('name', 'email', 'password', 'image', 'status', 'notif_device', 'created_at', 'last_update');
        $table_name = 'user_app';
        $pk = 'id';
        return $this->db->post_update($id, $data, $pk, $column_names, $table_name);
    }

    public function updateOneNotifDevice($id, $notif_device) {
        $now_time   = round(microtime(true) * 1000);
        $query = "UPDATE user_app SET notif_device='$notif_device', last_update=$now_time WHERE id=$id ";
        $this->db->execute_query($query);
    }

    public function allCountPlain($q) {
        $query = "SELECT COUNT(DISTINCT ua.id) FROM user_app ua WHERE ua.id > 1 ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(ua.name REGEXP '$q') OR (ua.email REGEXP '$q') ";
        if ($q != "") $query = $query . "AND " . $keywordQuery;
        return $this->db->get_count($query);
    }

    public function allCount() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
        $this->show_response_plain($this->allCountPlain($q));
    }

    public function findAllByPagePlain($limit, $offset, $q) {
        $query = "SELECT ua.id, ua.name, ua.email, ua.image, ua.status, ua.notif_device, ua.created_at, ua.last_update FROM user_app ua WHERE ua.id > 0 ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(ua.name REGEXP '$q') OR (ua.email REGEXP '$q') ";
        if ($q != "") $query = $query . "AND " . $keywordQuery;
        $query = $query . "ORDER BY ua.id DESC ";
        $query = $query . "LIMIT $limit OFFSET $offset ";
        return $this->db->get_list($query);
    }

    public function findAllByPage() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['limit']) || !isset($this->_request['page'])) $this->responseInvalidParam();
        $limit = (int)$this->_request['limit'];
        $offset = ((int)$this->_request['page']) - 1;
        $q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
        $this->show_response($this->findAllByPagePlain($limit, $offset, $q));
    }

    public function updateStatus() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
            $this->show_response($m);
        }
        if (!isset($this->_request['id']) || !isset($this->_request['status'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $status = $this->_request['status'];
        $query = "UPDATE user_app SET status='$status' WHERE id = $id";
        $this->db->execute_query($query);
        $this->show_response(array('status' => 'success'));
    }

}

?>