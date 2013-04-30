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

class LwImporter 
{
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
    }

    public function setDebug($debug) 
    {
        $this->debug = $debug;
    }
    
    /**
     * Collect the data for certain case, that the correct output can be build.
     * @param string $cmd
     */
    public function execute($cmd)
    {
        $this->event->setParameterByKey("connected", 1);
        
        if($cmd == "import") {
            $this->setEventData_import();
        } else {
            $this->setEventData_default();
        }
        
        $viewImporter = new \lwTabletools\views\LwViewImporterContent($this->event, $this->request);
        $this->output = $viewImporter->render();
    }
    
    /**
     * Default values will be set for the view object.
     */
    public function setEventData_default()
    {
        $this->event->setDataByKey("content", array(
                       "showImportFieldset"  => 1,
                       "showTable"           => 0
                       ) );
    }
    
    /**
     * Values will be set for the view object - case: import.
     */
    public function setEventData_import()
    {
        $this->xmlObject->setXml();
        $this->event->setDataByKey("content", array(
               "array"               => $this->import(),
               "showImportFieldset"  => 0,
               "showTable"           => 1,
               "debug"               => $this->debug
               ) );
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
     * The table from the xml will be created.
     * Tablestructre for the view object will  be returned.
     * @return array
     */
    public function import()
    {
        $transporter = new \lwTabletools\libraries\LwDbTransporter($this->db, $this->debug);
        return $transporter->importXML(trim($this->xmlObject->getXml()));
    }
}