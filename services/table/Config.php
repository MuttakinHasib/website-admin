<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class Config extends REST {

    private $mysqli = NULL;
    private $db = NULL;
    private $conf = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
        $this->conf = new CONF(); // Create conf class
    }

    public function findAll() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $this->show_response($this->findAllPlain());
    }

    public function findAllPlain() {
        $query = "SELECT * FROM config cn";
        return $this->db->get_list($query);
    }

    public function findValueByCode($code) {
        $query = "SELECT cn.value FROM config cn WHERE cn.code = '".$code."'";
        return $this->db->get_one($query)['value'];
    }

    public function updateAll() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $config = json_decode(file_get_contents("php://input"), true);
        if (!isset($config)) $this->responseInvalidParam();
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
            $this->show_response($m);
        }
        $column_names = array('code', 'value');
        $table_name = 'config';
        $pk = 'code';
        $resp = $this->db->update_array_pk_str($pk, $config, $column_names, $table_name);
        $this->show_response($resp);
    }

}

?>