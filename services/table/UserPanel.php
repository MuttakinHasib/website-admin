<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/../conf.php"));

class UserPanel extends REST {

    private $mysqli = NULL;
    private $db = NULL;
    private $conf = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
        $this->conf = new CONF(); // Create conf class
    }

    public function checkAuthAdmin() {
        $this->checkAuth('ADMIN');
    }

    public function checkAuthWriter() {
        $this->checkAuth('WRITER');
    }

    // security for filter manipulate data
    public function checkAuth($role = null) {
        $resp = array("status" => 'Failed', "msg" => 'Unauthorized, press CTRL + F5');
        if (isset($this->_header['Token']) && !empty($this->_header['Token'])) {
            $token = $this->_header['Token'];
            if($role == null || $role == ''){
                $query = "SELECT id FROM user_panel WHERE password='$token' ";
            } else {
                $query = "SELECT id FROM user_panel WHERE password='$token' AND role='$role' ";
            }
            $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
            if ($r->num_rows < 1) {
                $this->show_response($resp);
            }
        } else {
            $this->show_response($resp);
        }
    }

    // security for filter manipulate data
    public function checkSecurity() {
        $resp = array("status" => 'Failed', "msg" => 'Unauthorized, press CTRL + F5');
        if (isset($this->_header['Security']) && !empty($this->_header['Security'])) {
            $security = $this->_header['Security'];
            if($security != $this->conf->SECURITY_CODE) $this->show_response($resp);
        } else {
            $this->show_response($resp);
        }
    }

    public function processLogin() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $customer = json_decode(file_get_contents("php://input"), true);
        $username = $customer["username"];
        $password = $customer["password"];
        if (!empty($username) and !empty($password)) { // empty checker
            $query = "SELECT * FROM user_panel WHERE password = '" . md5($password) . "' AND username = '$username' LIMIT 1";
            $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
            if ($r->num_rows > 0) {
                $result = $r->fetch_assoc();
                $resp = array('status' => "success", "user_panel" => $result);
                $this->show_response($resp);
            }
            $error = array('status' => "failed", "msg" => "Username or Password not found");
            $this->show_response($error);
        }
        $error = array('status' => "failed", "msg" => "Invalid username or Password");
        $this->show_response($error);
    }

    public function findOne() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $id = (int)$this->_request['id'];
        $query = "SELECT id, name, username, email, role, created_at, last_update FROM user_panel WHERE id=$id";
        $this->show_response($this->db->get_one($query));
    }

    public function findOneToken() {
        $query = "SELECT password FROM user_panel LIMIT 1";
        return $this->db->get_one($query)['password'];
    }

    public function updateOne() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
            $this->show_response($m);
        }
        $user = json_decode(file_get_contents("php://input"), true);
        if (!isset($user['id'])) $this->responseInvalidParam();
        $id = (int)$user['id'];
        $password = $user['user_panel']['password'];
        if ($password == '*****') {
            $column_names = array('id', 'name', 'username', 'email', 'role', 'created_at', 'last_update');
        } else {
            $user['user_panel']['password'] = md5($password);
            $column_names = array('id', 'name', 'username', 'email', 'password', 'role', 'created_at', 'last_update');
        }
        $table_name = 'user_panel';
        $pk = 'id';
        $resp = $this->db->post_update($id, $user, $pk, $column_names, $table_name);
        $this->show_response($resp);
    }

    public function insertOne() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
            $this->show_response($m);
        }
        $user = json_decode(file_get_contents("php://input"), true);
        $user['password'] = md5($user['password']);
        $column_names = array('name', 'username', 'email', 'password', 'role', 'created_at', 'last_update');
        $table_name = 'user_panel';
        $pk = 'id';
        $resp = $this->db->post_one($user, $pk, $column_names, $table_name);
        $this->show_response($resp);
    }

    public function allCountPlain($q) {
        $query = "SELECT COUNT(DISTINCT up.id) FROM user_panel up WHERE up.id > 1 ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(up.name REGEXP '$q') ";
        if ($q != "") $query = $query . "AND " . $keywordQuery;
        return $this->db->get_count($query);
    }

    public function allCount() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
        $this->show_response_plain($this->allCountPlain($q));
    }

    public function findAllByPagePlain($limit, $offset, $q) {
        $query = "SELECT up.* FROM user_panel up WHERE up.id > 0 ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(up.name REGEXP '$q') ";
        if ($q != "") $query = $query . "AND " . $keywordQuery;
        $query = $query . "ORDER BY up.id DESC ";
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

    public function deleteOne() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
            $this->show_response($m);
        }
        if (!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $table_name = 'user_panel';
        $pk = 'id';
        $this->show_response($this->db->delete_one($id, $pk, $table_name));
    }

}

?>