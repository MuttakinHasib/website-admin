<?php
require_once(realpath(dirname(__FILE__) . "/../conf.php"));

class DB {

    public $mysqli = NULL;
    public $conf = NULL;

    public function __construct() {
        $this->conf = new CONF(); // Create conf class
        $this->dbConnect(); // Initiate Database connection
    }

    /* Connect to Database */
    private function dbConnect() {
        //echo "dbConnect";
        $this->mysqli = new mysqli($this->conf->DB_SERVER, $this->conf->DB_USER, $this->conf->DB_PASSWORD, $this->conf->DB_NAME);
        $this->mysqli->query('SET CHARACTER SET utf8');
    }

    public function reConnect() {
        $this->dbConnect();
        return $this;
    }

    /* Api Checker */
    public function checkResponse_Impl() {
        $status = 'SUCCESS';
        if (!mysqli_ping($this->mysqli)) {
            $status = 'ERROR';
        }
        echo "DB Connection : ".$status."<br>";
    }

    /* String mysqli_real_escape_string */
    public function real_escape($s) {
        return mysqli_real_escape_string($this->mysqli, $s);
    }

    /* ==================================== Database utilities ========================================== */

    public function get_list($query) {
        $result = array();
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
        if ($r->num_rows > 0) {
            while ($row = $r->fetch_assoc()) {
                $result[] = $row;
			}
            mysqli_free_result($r);
        }
        return $result;
    }

    public function get_one($query) {
        $result = array();
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
        if ($r->num_rows > 0) {
            $result = $r->fetch_assoc();
            mysqli_free_result($r);
        }
        return $result;
    }

    public function get_count($query) {
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
        if ($r->num_rows > 0) {
            $result = $r->fetch_row();
            mysqli_free_result($r);
            return $result[0];
        }
        return 0;
    }

    public function post_one($obj, $pk, $column_names, $table_name) {
        $resp = array();
        $keys = array_keys($obj);
        $columns = '';
        $values = '';
        foreach ($column_names as $desired_key) {
            if (!in_array($desired_key, $keys)) {
                $$desired_key = '';
            } else {
                $$desired_key = $obj[$desired_key];
            }
            $columns = $columns . $desired_key . ',';
            $values = $values . "'" . $this->real_escape($$desired_key) . "',";
        }

        $query = "INSERT INTO " . $table_name . "(" . trim($columns, ',') . ") VALUES(" . trim($values, ',') . ")";
        if (!empty($obj)) {
            if ($this->mysqli->query($query)) {
                // retrive row after insert
                $last_id = $this->mysqli->insert_id;
                $get_query = "SELECT * FROM " . $table_name . " WHERE " . $pk . "=" . $last_id;
                $r = $this->mysqli->query($get_query) or die($this->mysqli->error . __LINE__);
                if ($r->num_rows > 0) {
                    $obj = $r->fetch_assoc();
                    mysqli_free_result($r);
                }
                $status = "success";
                $msg = $table_name . " created successfully";
            } else {
                $status = "failed";
                $msg = $this->mysqli->error . __LINE__;
            }
            $resp = array('status' => $status, "msg" => $msg, "data" => $obj);
        }
        return $resp;
    }

    public function post_one_multi_pk($pk_key, $obj, $table_name, $column_names) {
        $resp = array();
        $columns = '';
        $values = '';
        $pk = '';
        foreach ($obj as $key=>$value) {
            if (!in_array($key, $column_names)) continue;
            $columns = $columns . $key . ',';
            $values = $values . "'" . $this->real_escape($value) . "',";
        }
        $query = "INSERT INTO " . $table_name . "(" . trim($columns, ',') . ") VALUES(" . trim($values, ',') . ")";
        if (!empty($obj)) {
            if ($this->mysqli->query($query)) {
                foreach ($pk_key as $p) {
                    $pk = $pk . $p . "='" . $this->real_escape($obj[$p]) . "' AND ";
                }
                $get_query = "SELECT * FROM " . $table_name . " WHERE " . trim($pk, ' AND');
                $obj = $this->get_one($get_query);
                $status = "success";
                $msg = $table_name . " created successfully";
            } else {
                $status = "failed";
                $msg = $this->mysqli->error . __LINE__;
            }
            $resp = array('status' => $status, "msg" => $msg, "data" => $obj);
        }
        return $resp;
    }

    public function post_array($obj_array, $column_names, $table_name) {
        $resp = array();
        $query = "";
        for ($i = 0; $i < count($obj_array); $i++) {
            $obj = $obj_array[$i];
            $keys = array_keys($obj);
            $columns = '';
            $values = '';
            foreach ($column_names as $desired_key) {
                if (!in_array($desired_key, $keys)) {
                    $$desired_key = '';
                } else {
                    $$desired_key = $obj[$desired_key];
                }
                $columns = $columns . $desired_key . ',';
                $values = $values . "'" . $this->real_escape($$desired_key) . "',";
            }
            $query .= "INSERT INTO " . $table_name . "(" . trim($columns, ',') . ") VALUES(" . trim($values, ',') . ");";
        }
        if (!empty($obj_array)) {
            if ($this->mysqli->multi_query($query)) {
                do {if ($result = $this->mysqli->store_result()) $result->free();
                } while ($this->mysqli->next_result());

                $status = "success";
                $msg = $table_name . " created successfully";
            } else {
                $status = "failed";
                $msg = $this->mysqli->error . __LINE__;
            }
            $resp = array('status' => $status, "msg" => $msg, "data" => $obj_array);
        }
        return $resp;
    }

    public function update_array($pk, $obj_array, $column_names, $table_name) {
        $resp = array();
        $query = "";
        for ($i = 0; $i < count($obj_array); $i++) {
            $obj = $obj_array[$i];
            $keys = array_keys($obj);
            $columns = '';
            $values = '';
            $pk_value = $obj[$pk];
            foreach ($column_names as $desired_key) {
                if (!in_array($desired_key, $keys)) {
                    $$desired_key = '';
                } else {
                    $$desired_key = $obj[$desired_key];
                }
                $columns = $columns . $desired_key . "='" . $this->real_escape($$desired_key) . "',";
            }
            $query .= "UPDATE " . $table_name . " SET " . trim($columns, ',') . " WHERE " . $pk . "=$pk_value ;";
        }

        if (!empty($obj_array)) {
            if ($this->mysqli->multi_query($query)) {
                do {if ($result = $this->mysqli->store_result()) $result->free();
                } while ($this->mysqli->next_result());

                $status = "success";
                $msg = $table_name . " update successfully";
            } else {
                $status = "failed";
                $msg = $this->mysqli->error . __LINE__;
            }
            $resp = array('status' => $status, "msg" => $msg, "data" => $obj_array);
        }
        return $resp;
    }

    public function update_array_pk_str($pk, $obj_array, $column_names, $table_name) {
        $resp = array();
        $query = "";
        for ($i = 0; $i < count($obj_array); $i++) {
            $obj = $obj_array[$i];
            $keys = array_keys($obj);
            $columns = '';
            $values = '';
            $pk_value = $obj[$pk];
            foreach ($column_names as $desired_key) {
                if (!in_array($desired_key, $keys)) {
                    $$desired_key = '';
                } else {
                    $$desired_key = $obj[$desired_key];
                }
                $columns = $columns . $desired_key . "='" . $this->real_escape($$desired_key) . "',";
            }
            $query .= "UPDATE " . $table_name . " SET " . trim($columns, ',') . " WHERE " . $pk . "='$pk_value' ;";
        }

        if (!empty($obj_array)) {
            if ($this->mysqli->multi_query($query)) {
                do { if ($result = $this->mysqli->store_result()) $result->free();
                } while ($this->mysqli->next_result());

                $status = "success";
                $msg = $table_name . " update successfully";
            } else {
                $status = "failed";
                $msg = $this->mysqli->error . __LINE__;
            }
            $resp = array('status' => $status, "msg" => $msg, "data" => $obj_array);
        }
        return $resp;
    }

    public function post_update($id, $obj, $pk, $column_names, $table_name) {
        $resp = array();
        $keys = array_keys($obj[$table_name]);
        $columns = '';
        foreach ($column_names as $desired_key) {
            if (!in_array($desired_key, $keys)) {
                $$desired_key = '';
            } else {
                $$desired_key = $obj[$table_name][$desired_key];
            }
            $columns = $columns . $desired_key . "='" . $this->real_escape($$desired_key) . "',";
        }
        $query = "UPDATE " . $table_name . " SET " . trim($columns, ',') . " WHERE " . $pk . "=$id";
        if (!empty($obj)) {
            if ($this->mysqli->query($query)) {
                $get_query = "SELECT * FROM " . $table_name . " WHERE " . $pk . "=" . $id;
                $r = $this->mysqli->query($get_query) or die($this->mysqli->error . __LINE__);
                if ($r->num_rows > 0) {
                    $obj[$table_name] = $r->fetch_assoc();
                    mysqli_free_result($r);
                }
                $status = "success";
                $msg = $table_name . " update successfully";
            } else {
                $status = "failed";
                $msg = $this->mysqli->error . __LINE__;
            }
            $resp = array('status' => $status, "msg" => $msg, "data" => $obj);
        }
        return $resp;
    }

    public function post_update_multi_pk($pk_key, $obj, $table_name, $column_names) {
        $resp = array();
        $columns = '';
        $pk = '';
        foreach ($obj as $key=>$value) {
            if (!in_array($key, $column_names)) continue;
            $columns = $columns . $key . "='" . $this->real_escape($value) . "',";
        }
        foreach ($pk_key as $p) {
            $pk = $pk . $p . "='" . $this->real_escape($obj[$p]) . "' AND ";
        }
        $query = "UPDATE " . $table_name . " SET " . trim($columns, ',') . " WHERE " . trim($pk, ' AND');
        if (!empty($obj)) {
            if ($this->mysqli->query($query)) {
                $status = "success";
                $msg = $table_name . " update successfully";
            } else {
                $status = "failed";
                $msg = $this->mysqli->error . __LINE__;
            }
            $resp = array('status' => $status, "msg" => $msg, "data" => $obj);
        }
        return $resp;
    }

    public function delete_one($id, $pk, $table_name) {
        $query = "DELETE FROM " . $table_name . " WHERE " . $pk . " = $id";
        if ($this->mysqli->query($query)) {
            $status = "success";
            $msg = "One record " . $table_name . " successfully deleted";
        } else {
            $status = "failed";
            $msg = $this->mysqli->error . __LINE__;
        }
        $resp = array('status' => $status, "msg" => $msg);
        return $resp;
    }

    public function delete_array($id_array, $pk, $table_name) {
        $param = implode( ",", $id_array);
        $query = "DELETE FROM " . $table_name . " WHERE " . $pk . " IN ($param) ;";
        if ($this->mysqli->query($query)) {
            $status = "success";
            $msg = count($id_array)." record(s) " . $table_name . " successfully deleted";
        } else {
            $status = "failed";
            $msg = $this->mysqli->error . __LINE__;
        }
        $resp = array('status' => $status, "msg" => $msg);
        return $resp;
    }

    public function delete_one_str($pkval, $pk, $table_name) {
        $query = "DELETE FROM " . $table_name . " WHERE " . $pk . " = '$pkval'";
        if ($this->mysqli->query($query)) {
            $status = "success";
            $msg = "One record " . $table_name . " successfully deleted";
        } else {
            $status = "failed";
            $msg = $this->mysqli->error . __LINE__;
        }
        $resp = array('status' => $status, "msg" => $msg);
        return $resp;
    }

    public function execute_query($query) {
        $r = $this->mysqli->query($query) or die($this->mysqli->error . __LINE__);
    }

    /* ==================================== End of database utilities ========================================== */
}

?>