<?php
namespace lwTabletools\controller;
/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 2013 Logic Works GmbH
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

class LwUpdater
{
    private $request;
    private $url;
    private $db;
    private $response;
    private $event;
    private $xmlObject;

    public function __construct($request,$response,$event,$db) 
    {
        $this->db = $db;
        $this->xmlObject = new \lwTabletools\model\LwXmlObject($request);
        $this->event = $event;
        $this->response = $response;
        $this->request = $request;
        $this->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']."?obj=".$this->request->getAlnum("obj")."&module=".$this->request->getAlnum("module");
        (array)$_SESSION["lw_tabletools"]["lw"] = "logic-works";
    }

    /**
     * Collect the data for certain case, that the correct output can be build.
     * @param string $cmd
     */
    public function execute($cmd)
    {
        $this->event->setParameterByKey("connected", 1);
        
        
        switch ($cmd) {
            case "upload":
                $this->xmlObject->setXml();
            case "update":    
                $this->setEventData_upload_update();
                break;
            default:
                $this->setEventData_default();
                break;
        }
        
        $viewImporter = new \lwTabletools\views\LwViewUpdaterContent($this->event);
        $this->output =  $viewImporter->render();
    }
    
    /**
     * Default values will be set for the view object.
     */
    public function setEventData_default()
    {
        $this->event->setDataByKey("content", array(
                "showXml"               => 0,
                "showTable"             => 0,
                "showUploadFieldset"    => 1,
                "error"                 => 0,
                "url"                   => $this->url."&upload=1"
                ) );              
    }   
    
    /**
     * Values will be set for the view object - case: upload/update.
     */
    public function setEventData_upload_update()
    {
        $this->event->setDataByKey("content", array(
                "showXml"               => 1,
                "showTable"             => 1,
                "showUploadFieldset"    => 0,
                "error"                 => 0,
                "xml"                   => $this->xmlObject->getXml()
                ) );  

        $result = $this->buildRows();
        if(is_array($result)) {
            $this->event->setDataByKey("content", array(
                    "showXml"               => 1,
                    "showTable"             => 1,
                    "showUploadFieldset"    => 0,
                    "xml"                   => $this->xmlObject->getXml(),
                    "error"                 => 0,
                    "tablename"             => $this->tablename,
                    "preparedArray"         => $result,
                    "url"                   => $this->url."&update=1"
                    ) );  
        }  elseif ($result == "error") {
            $this->event->setDataByKey("content", array(
                    "showXml"               => 1,
                    "showTable"             => 0,
                    "showUploadFieldset"    => 0,
                    "error"                 => 1,
                    "xml"                   => $this->xmlObject->getXml()
                    ) );  
        }
    }
    
    /**
     * Returns the constructed main content output.
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }
    
    /**
     * Compares the structure of the xml-table with the db-table and returns and array
     * with allowed changes  andor  not allowed changes.
     * @return array
     */
    public function getCompareResult()
    {      
        if($this->request->getInt("update") && $this->request->getInt("update") == 1) {
            $bool = false;
        }else{
            $bool = true;
        }

        $transporter =new \lwTabletools\libraries\LwDbTransporter($this->db);
        $tableStructureCompare = new \lwTabletools\controller\LwTablestructurecompare($transporter);
        $tableStructureUpdate = new \lwTabletools\controller\LwTablestructureupdate($this->db);
        $tableStructureUpdate->setUpdateOnlyWithoutErrors($bool);
        return $tableStructureUpdate->execute($tableStructureCompare->execute(trim($this->xmlObject->getXml())));
    }
    
    /**
     * PreparedArray will be constructed for the output-table.
     * Comparison between db and file.
     * @return array
     */
    public function buildRows()
    {
        $this->getCompareResult();
        $result = $this->getCompareResult();

        if(array_key_exists("error", $result)) {
            return "error";
        }
        
        if ($this->db->getDBType() == 'oracle') {
            $transport = new \lwTabletools\libraries\LwDbTransportOracle();
        }
        if ($this->db->getDBType() == 'mysql' || $this->db->getDBType() == 'mysqli') {
            $transport = new \lwTabletools\libraries\LwDbTransportMysql();
        }
        $transport->setDB($this->db);
        
        $tablestructure = $transport->getColumnsByTable($result["tablename"]);

        $this->tablename = $result["tablename"];
        $preparedArray = array();
        
        foreach($tablestructure as $field) {
            
            $array = array(
                "source" => "",
                "attr" => "",
                "fieldtype" => "",
                "fieldsize" => "",
                "modus" => "",
                "type" => "",
                "targetsize" => "",
                "status" => ""
            );
            
            $array["source"] =  "DB";
            $array["attr"] = $field["Field"];
            
            $parts = explode('(', $field["Type"]);
            if(array_key_exists("1", $parts)) {
                $msize = intval(str_replace(')', '', $parts[1]));
            }else{
                $msize = "";
            }
            
            $array["fieldtype"]  = $parts[0];
            $array["fieldsize"] = $msize;
            $array["status"] = "";
            $array["targetsize"] = "";
            $array["type"] = "";
            $array["modus"] = "";

            foreach($result as $key => $value) {
    
                switch ($key) {
                        case "not_allowed":
                            foreach($value as $k1 => $v1) {
                                if($k1 == $field["Field"]) {
                                    $array["attr"] = $k1;
                                    $array["status"] = "red";
                                    foreach($v1 as $k2 => $v2) {
                                        if($k2 == "targetsize") {
                                            $array["modus"] = "resize";
                                            if(array_key_exists("type", $v1)) {
                                                $array["type"] = $v1["type"];
                                                $array["targetsize"] = $v2;
                                            } else {
                                                $array["targetsize"] = $v2;
                                            }
                                        }
                                    }
                                } elseif ($key == "delete_attr_db") {
                                    foreach($v1 as $k2 => $v2) {
                                        $array["attr"] = $k1;
                                        $array["status"] = "red";
                                        $array["modus"] = "delete";
                                    }
                                }
                            }
                        break;
                        
                        case "allowed":
                        case "done":
                            foreach($value as $k1 => $v1) {
                                    switch ($k1) {
                                        case "change_attr":
                                            foreach($v1 as $k2 => $v2) {
                                                if($k2 == $field["Field"]) {
                                                    $array["status"] = "green";
                                                    $array["modus"] = "resize";
                                                    $array["targetsize"] = $v2["size"]["file"];
                                                    $array["fieldtype"] = "&nbsp;";
                                                    $array["fieldsize"] = "&nbsp;";
                                                }
                                            }
                                        break;

                                    }
                                }
                        break;
                }        
            }
            
            $preparedArray[] = $array;
        }
        
        foreach($result as $key => $value) {

                switch ($key) {
                        case "allowed":
                        case "done":
                            if(!$this->request->getInt("update")) {
                                $array["status"] = "green";
                                foreach($value as $k1 => $v1) {
                                    switch ($k1) {
                                        case "add_attr":
                                            $array["modus"] = "add";
                                            foreach($v1 as $k2 => $v2) {
                                                $array["source"] = "FILE";
                                                $array["attr"] = $k2;
                                                $array["fieldsize"] = "&nbsp;";
                                                $array["fieldtype"] = "&nbsp;";
                                                $array["type"] = $v2["type"];
                                                $array["targetsize"] = $v2["size"];
                                                $preparedArray[] = $array;
                                            }
                                        break;
                                    }
                                }
                            }
                        break;
                }        
            }
        return $preparedArray;
    }
}