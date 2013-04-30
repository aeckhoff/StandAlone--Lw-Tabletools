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

class LwExporter
{
    private $url;
    private $request;
    private $response;
    private $event;
    private $db;
    private $xmlObject;

    public function __construct($request,$response,$event,$db) 
    {
        $this->db = $db;
        $this->xmlObject = new \lwTabletools\model\LwXmlObject($request);
        $this->event = $event;
        $this->response = $response;
        $this->request = $request;
        (array)$_SESSION["lw_tabletools"]["lw"] = "logic-works";
        $this->url = $this->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']."?obj=".$this->request->getAlnum("obj")."&module=".$this->request->getAlnum("module");
    }
    
    /**
     * Collect the data for certain case, that the correct output can be build.
     * @param string $cmd
     * @param string $prefix_filter
     * @param array $selectedTables
     */
    public function execute($cmd, $prefix_filter, $selectedTables)
    {
        $this->event->setParameterByKey("connected", 1);

        $tables = $this->prefixFilter($this->getAllTables(), $prefix_filter);
        
        if($cmd == "export") {
            $this->setEventData_export($tables, $selectedTables);
        } else {
            $this->setEventData_default($tables, $selectedTables);
        }
        
        $viewExporter = new \lwTabletools\views\LwViewExporterContent($this->event);
        $this->output =  $viewExporter->render();
    }
    
    /**
     * Default values will be set for the view object.
     */
    public function setEventData_default($tables, $selectedTables)
    {
        $this->event->setDataByKey("content", array(
                        "showTable"     => 1,
                        "showXml"       => 0,
                        "url"           => $this->url,
                        "tables"        => $tables,
                        "filter"        => $this->request->getRaw("prefix_filter"),
                        "selectedTables"=> $selectedTables
                        ) );
    }
    
    /**
     * Values will be set for the view object - case: export.
     */
    public function setEventData_export($tables, $selectedTables)
    {
        if (count($tables)>0) {
            if ($this->request->getAlnum("type") == 'data') {
                $data = $this->export($this->request->getRaw("tables"), false);
            }
            else {
                $data = $this->export($this->request->getRaw("tables"), true);
            }

            if ($this->request->getInt("download") == 1) {
                $this->xmlObject->getXmlDownload($data);
            } else {
                $this->event->setDataByKey("content", array(
                    "showTable"     => 1,
                    "showXml"       => 1,
                    "url"           => $this->url,
                    "tables"        => $tables,
                    "filter"        => $this->request->getRaw("prefix_filter"),
                    "xml"           => htmlspecialchars($data),
                    "selectedTables"=> $selectedTables
                    ) );
                }
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
     * Returns all saved tables.
     * @return array
     */
    public function getAllTables()
    {
        $transporter = new \lwTabletools\libraries\LwDbTransporter($this->db);
        return $transporter->getAllTables();
    }
    
    
    /**
     * Xml will be build for export.
     * @param type $tables
     * @param type $structure
     * @return type
     */
    public function export($tables, $structure = false)
    {
        $transporter = new \lwTabletools\libraries\LwDbTransporter($this->db);
        if(!$structure) {
            return $transporter->exportData($tables);
        }else{
            return $transporter->exportTables($tables);
        }
    }
    
    /**
     * All saved tables will be filtered by a prefix.
     * @param array $tables
     * @param string $prefix_filter
     * @return array
     */
    public function prefixFilter($tables, $prefix_filter)
    {
        if($prefix_filter) {
            $temp = $tables;
            foreach($temp as $key => $value){
                $prefix = substr($value, 0, strlen($prefix_filter));
                if($prefix != $prefix_filter) {
                    unset($tables[$key]);
                }       
            }
        }    
        return $tables;
    }
}