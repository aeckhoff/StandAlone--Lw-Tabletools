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

class LwDBConnectionObject 
{
    public function __construct() 
    {
    }
    
    public function setDbUser($user)
    {
        $_SESSION["lw_tabletools"]["user"] = $user;
    }
    
    public function setDbPass($pass)
    {
        $_SESSION["lw_tabletools"]["pass"] = $pass;
    }
    
    public function setDbHost($host)
    {
        $_SESSION["lw_tabletools"]["host"] = $host;
    }
    
    public function setDbDatabase($connected_db)
    {
        $_SESSION["lw_tabletools"]["connected_db"] = $connected_db;
    }
    
    public function setDbType($db_type)
    {
        $_SESSION["lw_tabletools"]["db_type"] = $db_type;
    }
    
    public function getDbUser()
    {
        return $_SESSION["lw_tabletools"]["user"];
    }
    
    public function getDbPass()
    {
        return $_SESSION["lw_tabletools"]["pass"];
    }
    
    public function getDbHost()
    {
        return $_SESSION["lw_tabletools"]["host"];
    }
    
    public function getDbDatabase()
    {
        return $_SESSION["lw_tabletools"]["connected_db"];
    }
    
    public function getDbType()
    {
        return $_SESSION["lw_tabletools"]["db_type"];
    }
    
    public function connect()
    {
        if($_SESSION["lw_tabletools"]["db_type"] == "mysql") {
            
            $db = new \lwTabletools\libraries\LwDbMysqli($this->getDbUser(), $this->getDbPass(), $this->getDbHost(), $this->getDbDatabase());
            $ok = $db->connect();
            if($ok){
                $_SESSION["lw_tabletools"]["connected"] = true;
                return $db;
            }
            
        } elseif ($_SESSION["lw_tabletools"]["db_type"] == "oracle") {
            $db = new \lwTabletools\libraries\LwDbOracle($this->getDbUser(), $this->getDbPass(), $this->getDbHost(), $this->getDbDatabase());
            $ok = $db->connect();
            if($ok) {
                $_SESSION["lw_tabletools"]["connected"] = true;
                return $db;
            }
        }
    }
}