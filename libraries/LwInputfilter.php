<?php
namespace lwTabletools\libraries;

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Filter
 * @subpackage Input
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/*

Code Sample:

$clean = array();
$filterGet = new Zend_Filter_Input($_GET);
$clean['foo'] = $filterGet->testInt('foo');

var_dump($clean['foo']); // string(1) "2"
var_dump($_GET['foo']);  //NULL


weiterhin:

$filterGET      = new Zend_Filter_Input($_GET);
$filterPost     = new Zend_Filter_Input($_POST);
$filterCookie   = new Zend_Filter_Input($_COOKIE);
$filterRequest  = new Zend_Filter_Input($_REQUEST);
$filterEnv      = new Zend_Filter_Input($_ENV);

Zend::register('filterGet',     $filterGet);
Zend::register('filterPost',    $filterPost);
Zend::register('filterCookie',  $filterCookie);
Zend::register('filterRequest', $filterRequest);
Zend::register('filterEnv',     $filterEnv);

*/


/**
 * Zend_Filter
 */


/**
 * @category   Zend
 * @package    Zend_Filter
 * @subpackage Input
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class LwInputfilter
{
    public $_source = NULL;

    public function __construct(&$source = NULL, $strict = TRUE)
    {
        $this->_source = $source;

        if ($strict) {
            $source = NULL;
        }
    }
    
    /**
     * Checks if a key exists
     *
     * @param mixed $key
     * @return bool
     */
    public function keyExists($key)
    {
		if (is_array($this->_source))
		{       
                
    		return array_key_exists($key, $this->_source);
		}
		else 
		{
			return false;
		}
    }    

    /**
     * returns all available keys
     *
     * @return array
     */
    public function getKeys()
    {
		foreach ($this->_source as $key => $value)
		{
			$dummy[] = $key;
		}
		return $dummy;
    }    

	function getAlpha($key, $array=false)
	{
		$value = $this->_source[$key];
        $pattern = '/[^a-zA-Z\s]/';
        return preg_replace($pattern, '', (string) $value);		
	}    
    
	function getAlnum($key, $array=false)
	{
        $value = $this->_source[$key];
        $pattern = '/[^a-zA-Z0-9\s]/';
        return preg_replace($pattern, '', (string) $value);		
	}    

    /**
     * Returns (int) value.
     *
     * @param mixed $key
     * @return int
     */
    public function getInt($key)
    {
        return (int) ((string) $this->_source[$key]);
    }
    
    /**
     * Returns value.
     *
     * @param string $key
     * @return mixed
     */
    public function getRaw($key)
    {
        return $this->_source[$key];
    }
    
    public function testRegex($key, $pattern = NULL)
    {
		$value = $this->_source[$key];
        if (!$value) { return false; }
        $status = @preg_match($pattern, $value);
        if (false === $status) 
        {
            //Internal error matching pattern '$pattern' against value '$value'");
        }
        if (!$status) 
        {
            return false;
        }
        return true;
    }     

    /**
     * Returns value if it is a valid email format, FALSE otherwise.
     *
     * @param mixed $key
     * @return mixed
     */
    public function testEmail($key)
    {
        if (!$this->keyExists($key)) {
            return false;
        }
        return eregi("^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4}$", $this->_source[$key]);
    }    
    
}

?>