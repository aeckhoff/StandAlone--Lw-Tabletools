<?php
/**
* Load FluentDOM from DOMDocument
*
* @version $Id: DOMDocument.php 281 2009-07-13 09:38:52Z subjective $
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
* @copyright Copyright (c) 2009 Bastian Feder, Thomas Weinert
*
* @package FluentDOM
* @subpackage Loaders
*/

/**
* include interface
*/
require_once dirname(__FILE__).'/../FluentDOMLoader.php';

/**
* Load FluentDOM from DOMDocument
*
* @package FluentDOM
* @subpackage Loaders
*/
class FluentDOMLoaderDOMDocument implements FluentDOMLoader {
  
  /**
  * attach existing DOMDocument 
  *
  * @param object DOMDocument $source
  * @param string $contentType
  * @access public
  * @return object DOMDocument | FALSE
  */
  public function load($source, $contentType) {
    if ($source instanceof DOMDocument) { 
      return $source;
    }
    return FALSE;
  }
}

?>