<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class NewsComment extends REST{

    private $mysqli = NULL;
    private $db = NULL;
    private $news = NULL;
    private $conf = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
        $this->news = new News($this->db);
        $this->conf = new CONF(); // Create conf class
    }

    public function findOnePlain($id) {
        $query = "SELECT * FROM news_comment nc WHERE nc.id=$id LIMIT 1";
        return $this->db->get_one($query);
    }

    public function findOnePlainDuplicate($news_id, $user_app_id, $comment) {
        $query = "SELECT * FROM news_comment nc WHERE nc.news_id=$news_id AND nc.user_app_id=$user_app_id AND nc.comment='$comment' LIMIT 1";
        return $this->db->get_one($query);
    }

    public function findOne() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $this->show_response($this->findOnePlain($id));
    }

    public function allCountPlain() {
        $query = "SELECT COUNT(DISTINCT nc.id) FROM news_comment nc WHERE nc.id > 0 ";
        return $this->db->get_count($query);
    }

    public function allCount() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $this->show_response_plain($this->allCountPlain());
    }

    public function findAllByPagePlain($limit, $offset, $q) {
        $query = "SELECT nc.* FROM news_comment nc WHERE nc.id > 0 ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(nc.comment REGEXP '$q') ";
        if ($q != "") $query = $query . "AND " . $keywordQuery;
        $query = $query . "ORDER BY nc.id DESC ";
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

    public function insertOne() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data)) $this->responseInvalidParam();
        $column_names = array('news_id', 'user_app_id', 'comment', 'status', 'created_at', 'last_update');
        $table_name = 'news_comment';
        $pk = 'id';
        $resp = $this->db->post_one($data, $pk, $column_names, $table_name);
        $this->show_response($resp);
    }

    public function insertOneObj($data) {
        $column_names = array('news_id', 'user_app_id', 'comment', 'status', 'created_at', 'last_update');
        $table_name = 'news_comment';
        $pk = 'id';
        return $this->db->post_one($data, $pk, $column_names, $table_name);
    }

    public function updateOne() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) $this->responseInvalidParam();
        $id = (int)$data['id'];
        $column_names = array('news_id', 'user_app_id', 'comment', 'status', 'created_at', 'last_update');
        $table_name = 'news_comment';
        $pk = 'id';
        $this->show_response($this->db->post_update($id, $data, $pk, $column_names, $table_name));
    }

    public function deleteOne() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
            $this->show_response($m);
        }
        if (!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $table_name = 'news_comment';
        $pk = 'id';
        $comment = $this->findOnePlain($id);
        $resp = $this->db->delete_one($id, $pk, $table_name);
        $this->news->updateTotalComment($comment['news_id']);
        $this->show_response($resp);
    }

    public function countAllByNewsId($news_id) {
        $query = "SELECT COUNT(DISTINCT nc.id) FROM news_comment nc WHERE nc.news_id = $news_id ";
        return $this->db->get_count($query);
    }

    public function findAllByNewsId($limit, $offset, $news_id) {
        $query = "SELECT nc.*, ua.name, ua.image ";
        $query .= "FROM news_comment nc, user_app ua ";
        $query .= "WHERE nc.news_id = $news_id AND nc.user_app_id = ua.id ";
        $query = $query . "ORDER BY nc.id DESC LIMIT $limit OFFSET $offset ";
        return $this->db->get_list($query);
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
        $query = "UPDATE news_comment SET status='$status' WHERE id = $id";
        $this->db->execute_query($query);
        $this->show_response(array('status' => 'success'));
    }
}
?>