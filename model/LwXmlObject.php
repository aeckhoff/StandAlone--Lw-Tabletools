<?php
namespace lwTabletools\model;
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

class LwXmlObject 
{
    private $request;

    public function __construct($request) 
    {
        $this->request = $request;
    }
    
    public function setXml()
    {
        $file = $this->request->getFileData('xml');

        if($this->request->getRaw("xml_text")) {

            $_SESSION["lw_tabletools"]["xml"] = $this->request->getRaw("xml_text");

        } elseif ($file['tmp_name']) {

            $_SESSION["lw_tabletools"]["xml"] = \lwTabletools\libraries\LwIo::loadFile($file['tmp_name']); 
        } else {
            header( 'Location: ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']."?obj=".$this->request->getAlnum("obj")."&module=".$this->request->getAlnum("module") ) ;
        }
    }
    
    public function getXml()
    {
        return $_SESSION["lw_tabletools"]["xml"];
    }

    public function getXmlDownload($data)
    {
        if ($this->request->getAlnum("type") == 'data') {
            $filename = date('YmdHis')."_exportData.xml";
        }
        else {
            $filename = date('YmdHis')."_exportStructure.xml";
        }
        
        $mimeType = \lwTabletools\libraries\LwIo::getMimeType("xml");
        if (strlen($mimeType)<1) {
            $mimeType = "application/octet-stream";
        }            
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: ".$mimeType);
        header("Content-disposition: attachment; filename=\"".  $filename."\"");
        die($data);
        exit();
    }
}