<?php
/**
* Interface for FluentDOM loaders
*
* @version $Id: FluentDOMLoader.php 300 2009-07-22 19:34:55Z subjective $
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
* @copyright Copyright (c) 2009 Bastian Feder, Thomas Weinert
*
* @package FluentDOM
* @subpackage Loaders
*/

/**
* Interface for FluentDOM loaders
*
* @package FluentDOM
* @subpackage Loaders
*/
interface FluentDOMLoader {

  /**
  * load FluentDOM document data from a source
  *
  * @param mixed $source
  * @param string $contentType 
  * @access public
  * @return mixed object DOMDocument | array(DOMDocument, array(DOMNode, ...))  
  */
  public function load($source, $contentType);
}

?>