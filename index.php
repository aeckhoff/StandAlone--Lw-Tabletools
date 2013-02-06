<?php
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

if (strnatcmp(phpversion(), '5.3.0') >= 0) {
    error_reporting(E_ALL & ~E_DEPRECATED);
} else {
    error_reporting(E_ALL);
}
session_start();
(array)$_SESSION["lw_tabletools"]["lw"] = "logic-works";

require_once dirname(__FILE__) . '/services/Autoloader.php';
$autoloader = new lwTabletools\services\Autoloader();

$response = new lwTabletools\libraries\LwResponse();
$event = new lwTabletools\libraries\LwEvent();
$request = new lwTabletools\libraries\LwRequest();
$dbConnObject = new lwTabletools\model\LwDBConnectionObject();

if($request->getInt("sent") == 1) {
    if($request->getAlnum("db_type") != "" && $request->getRaw("user") != "" && $request->getRaw("host") != "" && $request->getRaw("db") != "") {
        $dbConnObject->setDbType($request->getAlnum("db_type"));
        $dbConnObject->setDbUser($request->getRaw("user"));
        $dbConnObject->setDbPass($request->getRaw("pass"));
        $dbConnObject->setDbHost($request->getRaw("host"));
        $dbConnObject->setDbDatabase($request->getRaw("db"));
        $db = $dbConnObject->connect();
    }
}


if($request->getInt("disconnect") == 1) {
    session_destroy();
    $_SESSION["lw_tabletools"] = array();
    header( 'Location: '.'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']."?obj=".$request->getAlnum("obj")."&module=".$request->getAlnum("module") ) ;
}

if(isset($_SESSION["lw_tabletools"]["connected"])) {
    if($_SESSION["lw_tabletools"]["connected"] === true) {
        $db = $dbConnObject->connect();
        
        switch ($request->getAlnum("obj")) {
            case "tabletools":
                switch ($request->getAlnum("module")) {
                    case "updater":
                        $cmd = "";
                        $updater = new lwTabletools\controller\LwUpdater($request,$response,$event,$db);
                        
                        if($request->getInt("upload") == 1) {
                            $cmd = "upload";
                        }
                        if($request->getInt("update") == 1) {
                            $cmd = "update";
                        }
                        
                        $updater->execute($cmd);
                        $output = $updater->getOutput();
                        break;

                    case "importer":
                        $cmd = "";
                        $importer = new lwTabletools\controller\LwImporter($request,$response,$event,$db);
                        
                        if($request->getInt("import") == 1) {
                            $cmd = "import";
                        }
                        
                        $importer->execute($cmd);
                        $output = $importer->getOutput();
                        break;

                    case "exporter":
                        $cmd = "";
                        $exporter = new lwTabletools\controller\LwExporter($request,$response,$event,$db);
                        
                        if($request->getInt("export") == 1) {
                            $cmd = "export";
                        }
                        
                        $exporter->execute($cmd, $request->getRaw("prefix_filter"), $request->getRaw("tables"));
                        $output = $exporter->getOutput();
                        break;
                }
                break;

            default:
                header( 'Location: '.'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']."?obj=tabletools&module=importer" ) ;
                break;
        }
    }
} else {
    $output = "";
}

$connectForm = new \lwTabletools\views\LwViewConnectForm($request);
        
$view = new \lwTabletools\libraries\LwView( dirname(__FILE__).'/views/templates/main.tpl.phtml' );
$view->leftContent = $connectForm->render();
$view->content = $output;
$view->url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
$view->modules = array("Importer","Exporter","Updater");
$view->obj = "tabletools";
$view->module = $request->getAlnum("module");
$view->url_css = str_replace("index.php", "css/main.css", 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
$view->url_module_css = str_replace("index.php", "css/exporter.css", 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

die($view->render());