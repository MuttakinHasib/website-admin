<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class Topic extends REST {

    private $mysqli = NULL;
    private $db = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
    }

    public function findAll() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $query = "SELECT * FROM topic t ORDER BY t.priority ASC";
        $this->show_response($this->db->get_list($query));
    }

    public function findAllForClient() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $query = "SELECT t.id, t.name FROM topic t ORDER BY t.priority ASC";
        return $this->db->get_list($query);
    }

    public function findOnePlain($id) {
        $query = "SELECT * FROM topic t WHERE t.id=$id LIMIT 1";
        return $this->db->get_one($query);
    }

    public function findOne() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $this->show_response($this->findOnePlain($id));
    }

    public function allCountPlain($q, $client) {
        $query = "SELECT COUNT(DISTINCT t.id) FROM topic t WHERE t.id > 0 ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(t.name REGEXP '$q') ";
        if ($q != "") $query = $query . "AND " . $keywordQuery;
        if ($client != 0) $query = $query . " AND t.draft <> 1 ";
        return $this->db->get_count($query);
    }

    public function allCount() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
        $client = (isset($this->_request['client'])) ? ((int)$this->_request['client']) : 0;
        $this->show_response_plain($this->allCountPlain($q, $client));
    }

    public function findAllByPagePlain($limit, $offset, $q, $client) {
        $query = "SELECT t.* FROM topic t WHERE t.id > 0 ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(t.name REGEXP '$q') ";
        if ($q != "") $query = $query . "AND " . $keywordQuery;
        if ($client != 0) $query = $query . " AND t.draft <> 1 ";
        if ($client != 0) {
            $query = $query . "ORDER BY t.priority ASC ";
        } else {
            $query = $query . "ORDER BY t.id DESC ";
        }
        $query = $query . "LIMIT $limit OFFSET $offset ";
        return $this->db->get_list($query);
    }

    public function findAllByPage() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['limit']) || !isset($this->_request['page'])) $this->responseInvalidParam();
        $limit = (int)$this->_request['limit'];
        $offset = ((int)$this->_request['page']) - 1;
        $q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
        $client = (isset($this->_request['client'])) ? ((int)$this->_request['client']) : 0;
        $this->show_response($this->findAllByPagePlain($limit, $offset, $q, $client));
    }

    public function insertOne() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data)) $this->responseInvalidParam();
        $column_names = array('name', 'icon', 'color', 'draft', 'featured', 'priority', 'created_at', 'last_update');
        $table_name = 'topic';
        $pk = 'id';

        if ($data['featured'] == 1 && $data['draft'] == 0 && $this->isFeaturedExceed() == 1) {
            $msg = array('status' => "failed", "msg" => "Featured Topic exceed the maximum amount", "data" => null);
            $this->show_response($msg);
            return;
        }

        $resp = $this->db->post_one($data, $pk, $column_names, $table_name);
        $this->show_response($resp);
    }

    public function updateOne() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) $this->responseInvalidParam();
        $id = (int)$data['id'];
        $column_names = array('name', 'icon', 'color', 'draft', 'featured', 'priority', 'created_at', 'last_update');
        $table_name = 'topic';
        $pk = 'id';

        $old_data = $this->findOnePlain($id);
        if ($old_data['featured'] == 1) {
            if ($data[$table_name]['featured'] == 0 && $this->countFeaturedPlain() <= 1) {
                $m = array('status' => "failed", "msg" => "Ops, At least there is ONE Featured Topic", "data" => null);
                $this->show_response($m);
                return;
            }
        } else {
            if ($data[$table_name]['featured'] == 1 && $data[$table_name]['draft'] == 0 && $this->isFeaturedExceed() == 1) {
                $msg = array('status' => "failed", "msg" => "Featured Topic exceed the maximum amount", "data" => null);
                $this->show_response($msg);
                return;
            }
        }

        $this->show_response($this->db->post_update($id, $data, $pk, $column_names, $table_name));
    }

    public function deleteOne() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $table_name = 'topic';
        $pk = 'id';

        $data = $this->findOnePlain($id);
        if ($data['featured'] == 1 && $this->countFeaturedPlain() <= 1) {
            $m = array('status' => "failed", "msg" => "Ops, At least there is ONE Featured Topic", "data" => null);
            $this->show_response($m);
            return;
        }

        $this->show_response($this->db->delete_one($id, $pk, $table_name));
    }

    public function getAllByNewsIdPlain($news_id) {
        $query = "SELECT DISTINCT t.* FROM topic t WHERE t.id IN (SELECT nt.topic_id FROM news_topic nt WHERE nt.news_id=$news_id);";
        return $this->db->get_list($query);
    }

    public function getAllByNewsIdPlainClient($news_id) {
        $query = "SELECT DISTINCT t.* FROM topic t WHERE t.draft=0 AND t.id IN (SELECT nt.topic_id FROM news_topic nt WHERE nt.news_id=$news_id);";
        return $this->db->get_list($query);
    }

    public function getAllByNewsId() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['news_id'])) $this->responseInvalidParam();
        $news_id = (int)$this->_request['news_id'];
        $this->show_response($this->getAllByNewsIdPlain($news_id));
    }

    public function countByDraftPlain($i) {
        $query = "SELECT COUNT(DISTINCT t.id) FROM topic t WHERE t.draft=$i ";
        return $this->db->get_count($query);
    }

    public function deleteInsertAll() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $news_topic = json_decode(file_get_contents("php://input"), true);
        if (!isset($news_topic)) $this->responseInvalidParam();

        $column_names = array('news_id', 'topic_id');
        $table_name = 'news_topic';
        try {
            $query = "DELETE FROM " . $table_name . " WHERE news_id = " . $news_topic[0]['news_id'];
            $this->mysqli->query($query);
        } catch (Exception $e) {
        }
        $this->show_response($this->db->post_array($news_topic, $column_names, $table_name));
    }

    public function findAllFeatured() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $query = "SELECT * FROM topic t WHERE t.featured=1 AND t.draft=0 ORDER BY t.priority ASC";
        return $this->db->get_list($query);
    }

    public function countFeaturedPlain() {
        $query = "SELECT COUNT(DISTINCT t.id) FROM topic t WHERE t.featured=1 AND t.draft=0 ";
        return $this->db->get_count($query);
    }

    public function isFeaturedExceed() {
        $key_code = 'FEATURED_TOPIC';
        $query = "SELECT COUNT(counter) AS resp from (SELECT COUNT(id) AS counter FROM topic WHERE featured = 1 AND draft = 0) as N 
                  WHERE N.counter >= (SELECT value FROM config WHERE code = '$key_code')";
        return $this->db->get_one($query)['resp'];
    }
}

?>