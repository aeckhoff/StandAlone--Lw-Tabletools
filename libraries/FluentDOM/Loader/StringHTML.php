<?php
/**
* Load FluentDOM from HTML string
*
* @version $Id: StringHTML.php 305 2009-07-24 18:03:59Z subjective $
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
* Load FluentDOM from HTML string
*
* @package FluentDOM
* @subpackage Loaders
*/
class FluentDOMLoaderStringHTML implements FluentDOMLoader {
  
  /**
  * load DOMDocument from html string
  *
  * @param string $source html string
  * @param string $contentType
  * @access public
  * @return object DOMDocument | FALSE
  */
  public function load($source, $contentType) {
    if (is_string($source) &&
        FALSE !== strpos($source, '<') &&
        $contentType == 'text/html') {
      $dom = new DOMDocument();
      $errorSetting = libxml_use_internal_errors(TRUE);
      libxml_clear_errors();
      $dom->loadHTML($source);
      libxml_clear_errors();
      libxml_use_internal_errors($errorSetting);
      return $dom;
    }
    return FALSE;
  }
}

?>