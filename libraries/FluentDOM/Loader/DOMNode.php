<?php
/**
* Load FluentDOM from DOMNode
*
* @version $Id: DOMNode.php 303 2009-07-22 19:50:07Z subjective $
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
class FluentDOMLoaderDOMNode implements FluentDOMLoader {
  
  /**
  * attach existing DOMNode->ownerdocument and select the DOMNode
  *
  * @param object DOMNode $source
  * @param string $contentType
  * @access public
  * @return array | FALSE
  */
  public function load($source, $contentType) {
    if ($source instanceof DOMNode) {
      return array($source->ownerDocument, array($source));
    }
    return FALSE;
  }
}

?>