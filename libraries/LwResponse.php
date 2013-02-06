<?php
namespace lwTabletools\libraries;
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

class LwResponse
{
    private $parameter;
    private $output;
    private $data;
    
    public function __construct()
    {
    }
    
    public static function getInstance()
    {
        return new Response();
    }
    
    public function setParameterByKey($key, $value)
    {
        $this->parameter[$key] = $value;
    }
    
    public function getParameterByKey($key)
    {
        return $this->parameter[$key];
    }
    
    public function getParameterArray()
    {
        return $this->parameter;
    }
    
    public function setDataByKey($key, $value)
    {
        $this->data[$key] = $value;
    }
    
    public function getDataByKey($key)
    {
        return $this->data[$key];
    }
    
    public function getDataArray()
    {
        return $this->data;
    }
    
    public function setOutputByKey($key, $output)
    {
        $this->output[$key] = $output;
    }
    
    public function getOutputByKey($key)
    {
        return $this->output[$key];
    }
}