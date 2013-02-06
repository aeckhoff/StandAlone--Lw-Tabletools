<?php
namespace lwTabletools\libraries;
/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 1998-2013 Logic Works GmbH
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

class LwDbTransporter
{

    function __construct($db)
    {
        $this->db = $db;
        $this->debug = false;
        $this->setVendorDBTransport($this->db->getDBType());
    }

    function setDebug($bool)
    {
        if ($bool) {
            $this->debug = true;
        }
        else {
            $this->debug = false;
        }
    }

    function setVendorDBTransport($db_type)
    {
        if ($db_type == 'oracle') {
            $this->transport = new \lwTabletools\libraries\LwDbTransportOracle();
        }
        if ($db_type == 'mysql' || $db_type == 'mysqli') {
            $this->transport = new \lwTabletools\libraries\LwDbTransportMysql();
        }
        $this->transport->setDB($this->db);
        $this->transport->setDebug($this->debug);
    }

    public function getAllTables()
    {
        return $this->transport->getAllTables();
    }

    public function exportData($tables)
    {
        $xml = "<dbdata>\n\n";
        if (is_array($tables)) {
            foreach ($tables as $table => $value) {
                $table = str_replace("'", "", $table);
                $autoincrement = $this->transport->getAutoincrement($table);

                $xml.='<table name="' . str_replace($this->db->getPrefix(), '', strtolower($table)) . '"';
                if ($autoincrement['value'] > 0) {
                    $xml.=' aifield="' . $autoincrement['field'] . '" aivalue="' . $autoincrement['value'] . '" ';
                }
                $xml.='>' . "\n";
                $data = $this->transport->getAllDataByTable($table);
                
                foreach ($data as $line) {
                    $xml.='    <entry>' . "\n";
                    $xml.="        <fields>\n";
                    foreach ($line as $key => $value) {
                        if ($value) {
                            if (strval(intval($value)) == strval($value)) {
                                $value = intval($value);
                                $xml.= '            <field name="' . $this->fieldToTag($key) . '" type="int">' . "\n";
                            }
                            else {
                                $xml.= '            <field name="' . $this->fieldToTag($key) . '" type="string">' . "\n";
                                $value = base64_encode($value);
                            }
                            $xml.= '                <![CDATA[' . ($value) . ']]>' . PHP_EOL;
                            $xml.= '            </field>' . "\n";
                        }
                    }
                    $xml.="       </fields>\n";
                    $xml.='    </entry>' . "\n";
                }
                $xml.="</table>\n\n";
            }
        }
        $xml.="</dbdata>\n\n";
        return $xml;
    }

    public function fieldToTag($name)
    {
        $out = str_replace(' ', '_', $name);
        $out = strtolower($out);
        return $out;
    }

    public function exportTables($tables)
    {
        $xml ="<migration>\n";
        $xml.="<version>1</version>\n";
        $xml.="<up>\n";
        if (is_array($tables)) {
            foreach ($tables as $table => $value) {
                $table = str_replace("'", "", $table);
                if ($value == 1) {
                    $xml.='<createTable name="' . str_replace($this->db->getPrefix(), "", strtolower($table)) . '">' . "\n";
                    $xml.="    <fields>\n";

                    $xml.= $this->_buildTableStructure($table);

                    $pk = $this->transport->getPrimaryKey($table);
                    if ($pk)
                        $xml.= '        <pk>' . $pk . '</pk>' . "\n";
                    $pk = false;

                    $xml.="    </fields>\n";
                    $xml.="</createTable>\n\n";
                }
            }
        }
        $xml.="</up>\n\n";
        $xml.="</migration>\n\n";
        return $xml;
    }

    private function _buildTablestructure($table)
    {
        $table = str_replace("'", "", $table);
        $xml = "";
        $columns = $this->transport->getColumnsByTable($table);
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $xml.= '        <field name="' . $this->transport->getColumnName($column) . '"';
                $xml.= $this->transport->parseColumn($column);
                if ($this->transport->hasAutoincrement($table, $column)) {
                    $xml.='special="auto_increment" ';
                }
                $xml.= '/>' . "\n";
            }
        }
        return $xml;
    }

    public function importXML($xml)
    {
        $array = array();
        $xml = trim($xml);
        include_once('FluentDOM/FluentDOM.php');
        $dom = FluentDOM($xml);
        if (substr($xml, 0, strlen('<migration>')) == '<migration>') {
            $ctNodes = $dom->find('/migration/up/createTable');
            foreach ($ctNodes as $ctNode) {
                $array["structure"] = $this->transport->createTable($ctNode);
            }
            #print_r($array);die();
            #die("imported!<br/><a href='index.php'>go back</a>");
        }
        elseif (substr($xml, 0, strlen('<dbdata>')) == '<dbdata>') {
            $array["data"] = $this->importData($dom);
        }

        return $array;
        #die("ung&uuml;ltiges XML!<br/><a href='index.php'>go back</a>");
    }

    function importData($dom)
    {
        $array = array();
        
        $tableNodes = $dom->find('/dbdata/table');
        foreach ($tableNodes as $tableNode) {
            $table = FluentDOM($tableNode);
            $entryNodes = $table->find('entry');
            $sql = "DELETE FROM "  . $table->attr('name');
            
            if (!$this->debug)
                $this->db->dbquery($sql);
            #echo $ok . ": " . $sql . "<br>";
            
            $array[$table->attr("name")] = array();
            
            foreach ($entryNodes as $entryNode) {
                $field_names = "";
                $values = "";
                $clobs = array();
                $id = false;
                $entry = FluentDOM($entryNode);
                $fieldNodes = $entry->find('fields/field');
                foreach ($fieldNodes as $fieldNode) {
                    $field = FluentDOM($fieldNode);
                    if (strlen($field->text()) > 4000) {
                        $clobs[$field->attr('name')] = $this->db->quote(base64_decode($field->text()));
                    }
                    else {
                        if ($field_names) {
                            $field_names.= ", ";
                            $values.= ", ";
                        }
                        $field_names.= $field->attr('name') . " ";
                        if ($field->attr('type') == "string") {
                            $value = $this->db->quote(base64_decode($field->text()));
                        }
                        else {
                            $value = intval($field->text());
                        }
                        $values.= "'" . $value . "' ";
                        if ($field->attr('name') == "id")
                            $id = intval($field->text());
                    }
                }

                $sql = "INSERT INTO "  . $table->attr('name') . " (" . $field_names . ") VALUES (" . $values . ")";
                if (!$this->debug)
                     $this->db->dbquery($sql);
                #echo $ok . ": " . $sql . "<br>";

                foreach ($clobs as $field => $data) {
                    if (!$this->debug)
                        $this->db->saveClob($this->prefix . $table->attr('name'), $field, $data, $id);
                }

                $arr_fields = explode(",", $field_names);
                $arr_values = explode(",", $values);
                #print_r(array($arr_fields,$arr_values));die(count($arr_fields)."");
                $temp_arr = array();
                for($i = 0; $i <= count($arr_values) - 1 ; $i++){
                    $temp_arr[trim(@$arr_fields[$i])] = @$arr_values[$i];
                }
                $array[$table->attr("name")][] = $temp_arr;
                
                unset($sql);
                unset($field_names);
                unset($values);
                unset($clob);
                unset($id);
                unset($arr_fields);
                unset($arr_values);
            }

            if (strlen(trim($table->attr('aifield'))) > 0) {
                $this->transport->setAutoincrement($table->attr('name'), ($table->attr('aivalue') + 1));
            }
        }
        return $array;
        #die("imported!<br/><a href='index.php'>go back</a>");
    }

}
