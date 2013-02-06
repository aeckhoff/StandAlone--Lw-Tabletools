<?php
namespace lwTabletools\libraries;
/**************************************************************************
*  Copyright notice
*
*  Copyright 2009-2010 Logic Works GmbH
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
***************************************************************************/

class LwView {
	
	public function __construct($file = false) {
		if ($file === false) throw new \Exception("No file to render ...");
		$this->_file = $file;
	   
	}
	
	public function render() {
		if (!is_file($this->_file)) throw new \Exception("No file to render ...");
		
		ob_start();
		$this->run($this->_file);
		return ob_get_clean();
	}
	
	private function run($file) {
		include $file;
	}
	
	protected function f($string) {
        return $this->lwStringClean($string);
	}
	
	public function getItemImagePreview($item, $function, $width, $height) {
		return lw_item::getInstance($item['id'])->getResizedImage($function, $width, $height);
	}
	
	public function isItemImage($item) {
		$array = array('jpg', 'jpeg', 'gif', 'png');
		if (in_array(strtolower($item['filetype']), $array)) return true;
		return false;
	}	
}