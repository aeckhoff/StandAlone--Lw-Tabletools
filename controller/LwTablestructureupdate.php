<?php
namespace lwTabletools\controller;

class LwTablestructureupdate 
{
    private $db;
    private $updateOnlyWithoutErrors;
    private $errors = array();
    private $transport;
    private $columns;
    
    public function __construct($db) 
    {
        $this->db = $db;
        
        if ($this->db->getDBType() == 'oracle') {
            $this->transport = new \lwTabletools\libraries\LwDbTransportOracle();
        }
        if ($this->db->getDBType() == 'mysql' || $this->db->getDBType() == 'mysqli') {
            $this->transport = new \lwTabletools\libraries\LwDbTransportMysql();
        }
        $this->transport->setDB($this->db);
    }
    
    public function setUpdateOnlyWithoutErrors($bool)
    {
        if($bool) {
            $this->updateOnlyWithoutErrors = true;
        }else{
            $this->updateOnlyWithoutErrors = false;
        }
    }


    /**
     * Allowed changed will be executed to the table, status array will be returned
     * @param array $diff_array
     * @return array
     */
    public function execute($diff_array)
    {        
        if(array_key_exists("error", $diff_array)) {
            return $diff_array;
        }
        $return_array = array();
        $return_array["tablename"] = $diff_array["tablename"];
        $this->columns = $this->transport->getColumnsByTable($diff_array["tablename"]);
        
        $this->checkForErrors($diff_array);
         $return_array["not_allowed"] = $this->errors;
        
        foreach($diff_array as $key => $entry) {
            if($key != "tablename" && $key != "diff_attr") {
                if(!array_key_exists($key, $this->errors)) {
                    if($this->updateOnlyWithoutErrors == false) {
                        
                        foreach ($this->columns as $c) {
                            if($c["Field"] == $key) {
                                if($this->transport->setModifyStatement($diff_array["tablename"], $entry, $c)) {
                                    $return_array["done"]["change_attr"][$key] = array("size" => $entry["size"]) ;
                                }
                            }
                        }
                    }else {
                        $return_array["allowed"]["change_attr"][$key] = array("size" => $entry["size"]);
                    }
                }
            }
            
            if($key == "diff_attr") {
                foreach($entry as $k => $ent) {
                    if($k == "file") {
                        foreach($ent as $keyy => $v) {
                            if($this->updateOnlyWithoutErrors == false) {
                                $this->db->addField($diff_array["tablename"], $keyy, $v["type"], $v["size"]);
                                $return_array["done"]["add_attr"][$keyy] = array("type" => $v["type"], "size" => $v["size"]);
                            }else {
                                $return_array["allowed"]["add_attr"][$keyy] = array("type" => $v["type"], "size" => $v["size"]);
                            }
                        }
                    }
                }
            }
        }
        return $return_array;
    }
    
    /**
     * Array will be checked to prevent not allowed changed to the table
     * @param array $arr
     */
    public function checkForErrors($arr)
    {
        #print_r($arr);diE();
        foreach($arr as $key => $value) {
            if($key != "diff_attr" && $key != "tablename") {
                foreach($value as $k => $val) {
                    if($k == "size") {
                        if(intval($val["diff_db_to_file"]) < 0) {
                            $this->errors[$key]["size"] = $val["diff_db_to_file"];
                            $this->errors[$key]["targetsize"] = $val["file"];
                        }else {
                            foreach($this->columns as $c) {
                                if($c["Field"] == $key) {
                                    if(strpos($c["Type"], "(")) {
                                        $fieldtype = substr($c["Type"], 0, strpos($c["Type"], "("));
                                    }else {
                                        $fieldtype = $c["Type"];
                                    }
                                    if($this->transport->getTypeMaxSize($fieldtype) < $val["file"]) {
                                        $this->errors[$key]["size"] = $val["file"];
                                        $this->errors[$key]["type"] = $fieldtype;
                                    }
                                }
                            }
                        }
                    }
                    if($k == "type") {
                       $this->errors[$key]["type"] = $val["type"];
                    }
                }
            }
            if($key == "diff_attr") {
                if(array_key_exists("db", $value)) {
                    $this->errors["delete_attr_db"] = $value["db"];
                }
            }
        }
    }
  
}