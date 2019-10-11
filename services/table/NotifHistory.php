<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/../conf.php"));

class NotifHistory extends REST {

    private $mysqli = NULL;
    private $db = NULL;
    public $conf = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
        $this->conf = new CONF(); // Create config class
    }

    public function findAll() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $query = "SELECT DISTINCT * FROM notif_history h ORDER BY h.created_at DESC";
        $this->show_response($this->db->get_list($query));
    }

    public function allCount() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $q = "";
        if (isset($this->_request['q'])) $q = $this->_request['q'];
        if ($q != "") {
            $query = "SELECT COUNT(DISTINCT h.id) FROM notif_history h WHERE title REGEXP '$q' OR content REGEXP '$q' ";
        } else {
            $query = "SELECT COUNT(DISTINCT h.id) FROM notif_history h";
        }
        $this->show_response_plain($this->db->get_count($query));
    }

    public function allCountPlain() {
        $query = "SELECT COUNT(DISTINCT h.id) FROM notif_history h";
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
            $query = "SELECT DISTINCT * FROM notif_history h WHERE title REGEXP '$q' OR title REGEXP '$q' 
                      ORDER BY h.created_at DESC LIMIT $limit OFFSET $offset";
        } else {
            $query = "SELECT DISTINCT * FROM notif_history h ORDER BY h.created_at DESC LIMIT $limit OFFSET $offset";
        }
        $this->show_response($this->db->get_list($query));
    }

    public function insertOnePlain($data) {
        $now_time = round(microtime(true) * 1000);
        $data['type']   = isset($data['type']) ? $data['type'] : 'NORMAL';
        $data['image']  = isset($data['image']) ? $data['image'] : '';
        $data['link']   = isset($data['link']) ? $data['link'] : '';
        $data['target']   = isset($data['target']) ? $data['target'] : '';
        $data['created_at']   = isset($data['created_at']) ? $data['created_at'] : $now_time;

        $column_names = array('title', 'content', 'type', 'image', 'link', 'target', 'created_at');
        $table_name = 'notif_history';
        $pk = 'id';
        $this->db->post_one($data, $pk, $column_names, $table_name);
    }

}

?>