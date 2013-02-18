<?php
namespace lwTabletools\libraries;
/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 1998-2009 Logic Works GmbH
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *  
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 * ************************************************************************* */

class LwDbTransportMysql implements \lwTabletools\libraries\LwVendorDBTransport
{

    public function __construct()
    {
        
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function setDB(\lwTabletools\libraries\LwDb $db)
    {
        $this->db = $db;
    }

    public function getAllTables()
    {
        $result = $this->db->select("show tables");
        foreach ($result as $single) {
            foreach ($single as $key => $table) {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    public function getPrimaryKey($table)
    {
        $table = str_replace("'","",$table);
        $pk = "";
        $columns = $this->getColumnsByTable($table);
        foreach ($columns as $column) {
            if ($column['Key'] == "PRI") {
                if (strlen(trim($pk)) > 0)
                    $pk.=',';
                $pk.= $column['Field'];
            }
        }
        return $pk;
    }

    public function getColumnsByTable($table)
    {
        return $this->db->getTableStructure($table);
    }

    public function getColumnName($column)
    {
        return $column['Field'];
    }

    public function parseColumn($column)
    {
        $size = "";
        $value = $column['Type'];
        $parts = explode('(', $value);
        $mtype = $parts[0];
        if(array_key_exists("1", $parts)) {
            $msize = intval(str_replace(')', '', $parts[1]));
        }else{
            $msize = "";
        }
        
        if (in_array($mtype, array('int', 'bigint', 'tinyint'))) {
            $type = 'type="number"';
        }
        elseif (in_array($mtype, array('longtext')) || in_array($mtype, array('mediumtext'))) {
            $type = 'type="clob"';
        }
        else {
            $type = 'type="text"';
        }
        if ($msize > 0) {
            $size = 'size="' . $msize . '"';
        }
        elseif ($mtype == 'text') {
            $size = 'size="3999"';
        }
        return ' ' . $type . ' ' . $size . ' ';
    }

    public function hasAutoincrement($table, $column)
    {
        if ($column['Extra'] == "auto_increment") {
            return true;
        }
        return false;
    }

    function getAllIndexes($table)
    {
        return false;
    }

    function getAutoincrement($table)
    {
        $autoincrement = array("field" => "", "value" => "");
        $columns = $this->getColumnsByTable($table);
        foreach ($columns as $column) {
            if ($column['Extra'] == "auto_increment") {
                $autoincrement['field'] = $column['Field'];
                $data = $this->db->select1("SELECT max(" . $autoincrement['field'] . ") as maxauto FROM " . $table);
                $autoincrement['value'] = intval($data['maxauto']);
            }
        }
        return $autoincrement;
    }

    function getAllDataByTable($table)
    {
        return $this->db->select("SELECT * FROM " . $table);
    }

    function createTable($ctNode)
    {
        $main = "";
        $foot = "";
        $this->preparedArray = array();
        
        $ct = FluentDOM($ctNode);
        $head = "CREATE TABLE IF NOT EXISTS " . $this->db->getPrefix() . str_replace("'", "", $ct->attr('name')) . " (\n";
        $this->tablename = $ct->attr('name');
        $this->preparedArray[$this->tablename] = array();
        
        foreach ($ct->find('fields/field') as $fieldnode) {
            $field = FluentDOM($fieldnode);
            $main.= $this->_buildField($field);
        }

        $pk = $ct->find('fields/pk')->text();
        if (strlen($pk) > 0) {
            $foot = "	PRIMARY KEY (" . $pk . ")\n";
        }
        else {
            $main = substr($main, 0, -2) . "\n";
        }
        $foot.= ")\n";
        if ($this->debug != 1) {
            $this->db->dbquery($head . $main . $foot);
        } else {
            $this->preparedArray[$this->tablename]["sql"] = $head . $main . $foot;
        }
        
        return $this->preparedArray;
    }

    private function _buildField($field)
    {
        $out = "";
        $out.= '    ' . $field->attr('name');
        switch ($field->attr('type')) {
            case "number":
                if ($field->attr('size') > 11) {
                    $out.=" bigint(" . $field->attr('size') . ") ";
                    if($this->debug != 1) {
                        $this->preparedArray[$this->tablename][$field->attr('name')]["type"] = "bigint";
                        $this->preparedArray[$this->tablename][$field->attr('name')]["size"] = $field->attr('size');
                    }
                }
                else {
                    $out.= " int(" . $field->attr('size') . ") ";
                    $this->preparedArray[$this->tablename][$field->attr('name')]["type"] = "int";
                    $this->preparedArray[$this->tablename][$field->attr('name')]["size"] = $field->attr('size');
                }
                break;

            case "text":
                if ($field->attr('size') > 255) {
                    $out.= " text ";
                    if($this->debug != 1) {
                        $this->preparedArray[$this->tablename][$field->attr('name')]["type"] = "text";
                        $this->preparedArray[$this->tablename][$field->attr('name')]["size"] = "";
                    }
                }
                else {
                    $out.= " varchar(" . $field->attr('size') . ") ";
                    if($this->debug != 1) {
                        $this->preparedArray[$this->tablename][$field->attr('name')]["type"] = "varchar";
                        $this->preparedArray[$this->tablename][$field->attr('name')]["size"] = $field->attr('size');
                    }
                }
                break;

            case "clob":
                $out.= " longtext ";
                if($this->debug != 1) {
                    $this->preparedArray[$this->tablename][$field->attr('name')]["type"] = "longtext";
                    $this->preparedArray[$this->tablename][$field->attr('name')]["size"] = "";
                }
                break;

            case "bool":
                $out.= " int(1) ";
                if($this->debug != 1) {
                    $this->preparedArray[$this->tablename][$field->attr('name')]["type"] = "int";
                    $this->preparedArray[$this->tablename][$field->attr('name')]["size"] = "1";
                }
                break;

            default:
                die("field not available");
        }
        if ($field->attr('special') == 'auto_increment') {
            $out.=' auto_increment ';
            if($this->debug != 1) {
                $this->preparedArray[$this->tablename][$field->attr('name')]["ai"] = "auto_increment";
            }
        }
        return $out . ",\n";
    }

    public function setAutoincrement($table, $value)
    {
        $sql = "ALTER TABLE " . $table . " AUTO_INCREMENT = " . $value;
        if (!$this->debug)
             $this->db->dbquery($sql);
        #echo $ok . ": " . $sql . "<br>";
    }
    
    /**
     * Attribute modify statement will be created
     * @param string $table
     * @param array $modification
     * @param array $actual
     * @return string
     */
    public function setModifyStatement($table, $modification, $actual)
    {   
        if(strpos($actual["Type"], "(")) {
            $fieldtype = substr($actual["Type"], 0, strpos($actual["Type"], "("));
        }else {
            $fieldtype = $actual["Type"];
        }

        switch ($fieldtype) {
            case "varchar" :
                    $type = "varchar(" . intval($modification["size"]["file"]) . ")";
                break;

            case "text": 
                $type = "text";
                break;

            case "longtext": 
                $type = "longtext";
                break;

            case "int" :
                    $type = "int(" . intval($modification["size"]["file"]) . ")";
                break;

            case "bigint" :
                $type = "bigint(" . intval($modification["size"]["file"]) . ")";
                break;

            default:
                die("field not available");
        }

        $this->db->setStatement("ALTER TABLE t:".$table." MODIFY ". $actual["Field"] ." ". $type ." ");
        return $this->db->pdbquery();
    }
    
    public function getTypeMaxSize($fieldtype)
    {
        switch ($fieldtype) {
            case "int":
                return 11;
                break;
            case "bigint":
                return 20;
                break;
            case "varchar":
                return 255;
                break;
            case "text":
                return 65535;
                break;
            case "longtext":
                return 4294967295;
                break;

            default:
                die("field not available");
                break;
        }
    }

}
