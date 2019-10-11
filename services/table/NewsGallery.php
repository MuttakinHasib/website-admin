<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class NewsGallery extends REST{

	private $mysqli = NULL;
	private $db = NULL;
	private $upload_path = NULL;
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
		$this->upload_path = dirname(__FILE__) . "../../../uploads/product/";
    }

	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$query="SELECT * FROM news_gallery;";
		$this->show_response($this->db->get_list($query));
	}
	
	public function findAllByNewsIdPlain($news_id){
		$query="SELECT DISTINCT * FROM news_gallery i WHERE i.news_id=$news_id";
		return $this->db->get_list($query);
	}

	public function findAllByNewsId(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['news_id']))$this->responseInvalidParam();
		$news_id = (int)$this->_request['news_id'];
		$this->show_response($this->findAllByNewsIdPlain($news_id));
	}

	public function insertAll(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$news_gallery = json_decode(file_get_contents("php://input"),true);
		if(!isset($news_gallery))$this->responseInvalidParam();
		$column_names = array('news_id', 'name');
		$table_name = 'news_gallery';
		try {
			$query="DELETE FROM ".$table_name." WHERE news_id = ".$news_gallery[0]['news_id'];
			$this->mysqli->query($query);
		} catch(Exception $e) {}
		$resp = $this->db->post_array($news_gallery, $column_names, $table_name);
		$this->show_response($resp);
	}

	public function delete(){
		if($this->get_request_method() != "DELETE") $this->response('',406);
		if(!isset($this->_request['name']))$this->responseInvalidParam();
		$_name = $this->_request['name'];
		$table_name = 'news_gallery';
		$pk = 'name';
		$target_file = $this->upload_path . $_name;
		if(file_exists($target_file)){
			unlink($target_file);
		}
		$resp = $this->db->delete_one_str($_name, $pk, $table_name);
		$this->show_response($resp);
	}
	
	public function findAllByNewsId_arr($news_id){
		$query = "SELECT * FROM news_gallery i WHERE i.news_id=".$news_id;
		return $this->db->get_list($query);
	}
	
}	
?>