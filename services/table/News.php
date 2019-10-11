<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class News extends REST {

    private $mysqli = NULL;
    private $db = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
    }

    public function findAll() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $query = "SELECT * FROM news n ORDER BY n.id DESC";
        $this->show_response($this->db->get_list($query));
    }

    public function findOnePlain($id) {
        $query = "SELECT * FROM news n WHERE n.id=$id LIMIT 1";
        return $this->db->get_one($query);
    }

    public function findOnePlainForClient($id) {
        $query = "SELECT * FROM news n WHERE n.id=$id AND n.draft=0 LIMIT 1";
        return $this->db->get_one($query);
    }

    public function findOne() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $this->show_response($this->findOnePlain($id));
    }

    public function allCountPlain($q, $topic_id) {
        $query = "SELECT COUNT(DISTINCT n.id) FROM news n ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(n.title REGEXP '$q' OR n.content REGEXP '$q') ";
        if ($topic_id != -1) {
            $query = $query . ", news_topic nc WHERE nc.news_id=n.id AND nc.topic_id=$topic_id ";
            if ($q != "") $query = $query . "AND " . $keywordQuery;
        } else {
            if ($q != "") $query = $query . "WHERE " . $keywordQuery;
        }
        return $this->db->get_count($query);
    }

    public function allCountPlainForClient($q, $topic_id, $feat) {
        $query = "SELECT COUNT(DISTINCT n.id) FROM news n ";
        if ($topic_id != -1) $query = $query . ",news_topic nc ";

        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(n.title REGEXP '$q' OR n.content REGEXP '$q') ";
        $query = $query . "WHERE n.draft=0 ";
        if ($topic_id != -1) {
            $query = $query . "AND nc.news_id=n.id AND nc.topic_id=$topic_id ";
        }
        if ($feat != -1) {
            $query = $query . "AND n.featured=$feat ";
        }
        if ($q != "") $query = $query . "AND " . $keywordQuery;
        return $this->db->get_count($query);
    }

    public function allCount() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
        $topic_id = isset($this->_request['topic_id']) ? ((int)$this->_request['topic_id']) : -1;
        $this->show_response_plain($this->allCountPlain($q, $topic_id));
    }

    public function findAllByPagePlain($limit, $offset, $q, $topic_id, $column, $order) {
        $query = "SELECT DISTINCT n.* FROM news n ";

        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(n.title REGEXP '$q' OR n.content REGEXP '$q') ";
        if ($topic_id != -1) {
            $query = $query . ", news_topic nc WHERE nc.news_id=n.id AND nc.topic_id=$topic_id ";
            if ($q != "") $query = $query . "AND " . $keywordQuery;
        } else {
            if ($q != "") $query = $query . "WHERE " . $keywordQuery;
        }
        $query = $query . "ORDER BY n.".$column." ".$order." LIMIT $limit OFFSET $offset ";
        return $this->db->get_list($query);
    }

    public function findAllByPagePlainForClient($limit, $offset, $q, $topic_id, $column, $order, $feat) {
        $query = "SELECT DISTINCT n.* FROM news n ";
        if ($topic_id != -1) $query = $query . ",news_topic nc ";

        $query = $query . "WHERE n.draft=0 ";
        $q = str_replace(" ", "|", $q);
        $keywordQuery = "(n.title REGEXP '$q' OR n.content REGEXP '$q') ";
        if ($topic_id != -1) {
            $query = $query . "AND nc.news_id=n.id AND nc.topic_id=$topic_id ";
        }
        if ($feat != -1) {
            $query = $query . "AND n.featured=$feat ";
        }
        $column = $column == '' ? 'id' : $column;
        $order = $order == '' ? 'DESC' : $order;

        if ($q != "") $query = $query . "AND " . $keywordQuery;
        $query = $query . "ORDER BY n.".$column." ".$order." LIMIT $limit OFFSET $offset ";
        return $this->db->get_list($query);
    }

    public function findAllByPage() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['limit']) || !isset($this->_request['page'])) $this->responseInvalidParam();
        $limit = (int)$this->_request['limit'];
        $offset = ((int)$this->_request['page']) - 1;
        $q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
        $topic_id = isset($this->_request['topic_id']) ? ((int)$this->_request['topic_id']) : -1;
        $column = isset($this->_request['col']) ? $this->_request['col'] : 'id';
        $order = isset($this->_request['ord']) ? $this->_request['ord'] : 'DESC';
        $this->show_response($this->findAllByPagePlain($limit, $offset, $q, $topic_id, $column, $order));
    }

    public function insertOne() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data)) $this->responseInvalidParam();
        $column_names = array(
            'title', 'content', 'image', 'url', 'total_view', 'total_comment', 'date', 'type', 'draft', 'featured',
            'created_at', 'last_update'
        );
        $table_name = 'news';
        $pk = 'id';

        if ($data['featured'] == 1 && $data['draft'] == 0 && $this->isFeaturedExceed() == 1) {
            $msg = array('status' => "failed", "msg" => "Featured Wallpaper exceed the maximum amount", "data" => null);
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
        $column_names = array(
            'title', 'content', 'image', 'url', 'total_view', 'total_comment', 'date', 'type', 'draft', 'featured',
            'created_at', 'last_update'
        );
        $table_name = 'news';
        $pk = 'id';

        $old_data = $this->findOnePlain($id);
        if($old_data['featured'] == 1){
            if ($data[$table_name]['featured'] == 0 && $this->countFeaturedPlain() <= 1) {
                $m = array('status' => "failed", "msg" => "Ops, At least there is ONE Featured Wallpaper", "data" => null);
                $this->show_response($m);
                return;
            }
        } else {
            if ($data[$table_name]['featured'] == 1 && $data[$table_name]['draft'] == 0 && $this->isFeaturedExceed() == 1) {
                $msg = array('status' => "failed", "msg" => "Featured Wallpaper exceed the maximum amount", "data" => null);
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
        $table_name = 'news';
        $pk = 'id';

        $data = $this->findOnePlain($id);
        if ($data['featured'] == 1 && $this->countFeaturedPlain() <= 1) {
            $m = array('status' => "failed", "msg" => "Ops, At least there is ONE Featured Wallpaper", "data" => null);
            $this->show_response($m);
            return;
        }

        $this->show_response($this->db->delete_one($id, $pk, $table_name));
    }

    public function findAllFeatured() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $query = "SELECT * FROM news n WHERE n.featured=1 AND n.draft=0 ORDER BY n.id DESC";
        return $this->db->get_list($query);
    }

    public function countFeaturedPlain() {
        $query = "SELECT COUNT(DISTINCT n.id) FROM news n WHERE n.featured=1 AND n.draft=0 ";
        return $this->db->get_count($query);
    }

    public function isFeaturedExceed() {
        $key_code = 'FEATURED_WALLPAPER';
        $query = "SELECT COUNT(counter) AS resp from (SELECT COUNT(id) AS counter FROM news WHERE featured = 1 AND draft = 0) as N "
            . "WHERE N.counter >= (SELECT value FROM config WHERE code = '$key_code')";
        return $this->db->get_one($query)['resp'];
    }

    public function incrementTotalViewed($id) {
        $query = "UPDATE news SET total_view = total_view + 1 WHERE id = $id";
        $this->db->execute_query($query);
    }

    public function updateTotalComment($id) {
        $query = "UPDATE news SET total_comment=(SELECT COUNT(nc.id) FROM news_comment nc WHERE nc.news_id=$id) WHERE id = $id";
        $this->db->execute_query($query);
    }

    public function countByDraftPlain($i){
        $query = "SELECT COUNT(DISTINCT n.id) FROM news n WHERE n.draft=$i ";
        return $this->db->get_count($query);
    }

    public function countByTypePlain($type){
        $query = "SELECT COUNT(DISTINCT n.id) FROM news n WHERE n.type='$type' ";
        return $this->db->get_count($query);
    }

    public function countTotalViewPlain(){
        $query = "SELECT SUM(total_view) FROM news";
        return $this->db->get_count($query);
    }
}

?>