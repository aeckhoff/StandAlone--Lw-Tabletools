<?php
namespace lwTabletools\views;
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

class LwViewConnectForm 
{
    private $request;
    private $dbConnObject;

    public function __construct($request) 
    {
        $this->request = $request;
        $this->dbConnObject = new \lwTabletools\model\LwDBConnectionObject();
        $this->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']."?obj=".$this->request->getAlnum("obj")."&module=".$this->request->getAlnum("module");
    }

    public function render()
    {
        $view = new \lwTabletools\libraries\LwView( dirname(__FILE__).'/templates/connectForm.tpl.phtml' );
        if(array_key_exists("connected", $_SESSION["lw_tabletools"]) && $_SESSION["lw_tabletools"]["connected"] === true ) {
            $view->connected = 1;
            $view->db_type           = $this->dbConnObject->getDbType();
            $view->connected_db      = $this->dbConnObject->getDbDatabase();
            $view->disconurl         = $this->url."&disconnect=1";                
        } else {
            $view->connected = 0;
            $view->url = $this->url;
        }
        return $view->render();
    }
}