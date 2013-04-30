<?php
namespace lwTabletools\controller;

require_once dirname(__FILE__) . '/../libraries/FluentDOM/FluentDOM.php';

class LwTablestructurecompare
{
    private $transporter;
    
    public function __construct($transporter) 
    {
        $this->transporter = $transporter;
    }
    
    
    /**
     * Checks if the table from the xml file is existing in the db,
     * If the table existing then a xml file of the table structure will be created
     * and compared with the file's xml structure
     * @param string $xml_file
     * @return array
     */
    public function execute($xml_file)
    {
        $tables = $this->transporter->getAllTables();
        $file_arr = $this->xmlToArray($xml_file);
        $tablename = array_keys($file_arr);
        
        if(in_array($tablename[0], $tables)) {
            $table = array($tablename[0] => 1);
            $db_xml = $this->transporter->exportTables($table);
            
            $result = $this->compare($this->xmlToArray($db_xml) , $file_arr);
            $result["tablename"] = $tablename[0];
            return $result;
        }else{
            return array("error" => "table does not exists");
        }
    }
    
    
    /**
     * The 2 table structues ( converted from xml to array ) will be compared
     * and a difference array returned
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    public function compare($arr1, $arr2) # db, file xml
    {
        $diff = array();
        $table1 = array_keys($arr1);
        $table2 = array_keys($arr2);
        
        if($table1 === $table2){
            $result = $this->checkAttributes($arr1, $arr2, $table1[0]);
            
            if(!empty($result)) {
                if(array_key_exists("db", $result["diff_attr"])) {
                    foreach($result["diff_attr"]["db"] as $k => $value) {
                        foreach($arr1[$table1[0]] as $key => $value2) {
                            if($value2["fieldname"] === $k) {
                                unset($arr1[$table1[0]][$key]);
                            }
                        }

                    }
                }
                if(array_key_exists("file", $result["diff_attr"])) {
                    foreach($result["diff_attr"]["file"] as $k => $value){

                        foreach($arr2[$table1[0]] as $key => $value2) {
                            if($value2["fieldname"] === $k) {
                                unset($arr2[$table1[0]][$key]);
                            }
                        }

                    }
                }
                $diff = $this->checkAttributeDetails($arr1, $arr2, $table1[0]);
                $diff["diff_attr"] = $result["diff_attr"];
            }else{
                $diff = $this->checkAttributeDetails($arr1, $arr2, $table1[0]);
            }
            
        }
        return $diff;
    }
    
    /**
     * The attributes of the table will be compared (names)
     * @param array $arr1
     * @param array $arr2
     * @param string $tablename
     * @return array
     */
    public function checkAttributes($arr1, $arr2, $tablename)
    {
        $return_diff = array();
        
        $key_list1 = array();
        $key_list2 = array();
        
        for($i = 0; $i <= count($arr1[$tablename]) - 1; $i++) {
            $key_list1[] = $arr1[$tablename][$i]["fieldname"];
        }
        
        for($i = 0; $i <= count($arr2[$tablename]) - 1; $i++) {
            $key_list2[] = $arr2[$tablename][$i]["fieldname"];
        }
        
        $diff1 = array();
        $diff2 = array();
        
        foreach ($key_list1 as  $value) {
            if(!in_array($value, $key_list2)) {
                foreach($arr1[$tablename] as $details) {
                    if($details["fieldname"] == $value) {
                        $diff1[$value] = array(
                                                "type"           => $details["type"],
                                                "size"           => $details["size"],
                                                "auto_increment" => $details["auto_increment"]);
                    }
                }
            }
        }
        
        foreach ($key_list2 as $value) {
            if(!in_array($value, $key_list1)) {
                foreach($arr2[$tablename] as $details) {
                    if($details["fieldname"] == $value) {
                        $diff2[$value] = array(
                                                "type"           => $details["type"],
                                                "size"           => $details["size"],
                                                "auto_increment" => $details["auto_increment"]);
                    }
                }
            }
        }
        
        if(!empty($diff1)) {
            $return_diff["diff_attr"]["db"] = $diff1;
        }
        
        if(!empty($diff2)) {
            $return_diff["diff_attr"]["file"] = $diff2;
        }
        
        return $return_diff;
    }
    
    /**
     * The deteiled infos of attributes which are existing in both structures will
     * be compared. ( size , type )
     * @param array $arr1
     * @param array $arr2
     * @param string $tablename
     * @return array
     */
    public function checkAttributeDetails($arr1, $arr2,  $tablename)
    {
        $diff = array(); 
        
        $sortArray = array();
        foreach($arr1[$tablename] as $key => $array) {
            $sortArray[$key] = $array["fieldname"];
        }
        array_multisort($sortArray, SORT_ASC, SORT_STRING, $arr1[$tablename]); 
        $sortArray2 = array();
        foreach($arr2[$tablename] as $key => $array) {
            $sortArray2[$key] = $array["fieldname"];
        }
        array_multisort($sortArray2, SORT_ASC, SORT_STRING, $arr2[$tablename]); 

        for($i = 0; $i <= count($arr1[$tablename]) - 1; $i++) {
            if($arr1[$tablename][$i] != $arr2[$tablename][$i]) {
                
                if($arr1[$tablename][$i]["fieldname"] === $arr2[$tablename][$i]["fieldname"]){

                    if($arr1[$tablename][$i]["type"] != $arr2[$tablename][$i]["type"]) {
                        $diff[$arr1[$tablename][$i]["fieldname"]]["type"] = array("db" => $arr1[$tablename][$i]["type"], "file" => $arr2[$tablename][$i]["type"]);
                    }
                    if( intval($arr1[$tablename][$i]["size"]) !== intval($arr2[$tablename][$i]["size"])) {
                        // ist die size aus dem file groeßer, dann ist "diff_db_to_file" entsprechen groeßer 0
                        $diff_db_to_file = intval($arr2[$tablename][$i]["size"]) - intval($arr1[$tablename][$i]["size"]);
                        
                        $diff[$arr1[$tablename][$i]["fieldname"]]["size"] = array("db" => $arr1[$tablename][$i]["size"], "file" => $arr2[$tablename][$i]["size"], "diff_db_to_file" => $diff_db_to_file);
                        $diff[$arr1[$tablename][$i]["fieldname"]]["size"]["db_type"] = $arr1[$tablename][$i]["type"] ;
                    }
                    if($arr1[$tablename][$i]["auto_increment"] != $arr2[$tablename][$i]["auto_increment"]) {
                        $diff[$arr1[$tablename][$i]["fieldname"]]["auto_increment"] = array("db" => $arr1[$tablename][$i]["auto_increment"], "file" => $arr2[$tablename][$i]["auto_increment"]);
                    }

                }

            }
        }
        return $diff;
    }
    
    /**
     * Xml will be converted to an array
     * @param string $xml
     * @return array
     */
    public function xmlToArray($xml)
    {
        $array = array();
        
        $xml = trim($xml);
        $dom = FluentDOM($xml);
        
        if (substr($xml, 0, strlen('<migration>')) == '<migration>') {
        
            $ctNodes = $dom->find('/migration/up/createTable');
            foreach ($ctNodes as $ctNode) {
                $ct = FluentDOM($ctNode);
                
                foreach ($ct->find('fields/field') as $fieldnode) {
                    $field = FluentDOM($fieldnode);
                    if($field->attr('special') == 'auto_increment') {
                        $ai = 1;
                    }else{
                        $ai = 0;
                    }
                    $array[$ct->attr('name')][] = array(
                                                        "fieldname"         => $field->attr('name'),
                                                        "type"              => $field->attr("type"),
                                                        "size"              => $field->attr("size"),
                                                        "auto_increment"    => $ai );
                }
                
            }
        }
        return $array;
    }
}