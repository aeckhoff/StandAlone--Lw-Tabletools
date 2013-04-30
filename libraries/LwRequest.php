<?php
namespace lwTabletools\libraries;
use \lwTabletools\libraries\LwInputfilter as LwInputfilter;
/**************************************************************************
*  Copyright notice
*
*  Copyright 1998-2009 Logic Works GmbH
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

/**
 * @package  Framework
 * @author   Dr. Andreas Eckhoff
 * @since    PHP 5.0
 */
class LwRequest
{
    private static $instance = null;
	protected static $_unicodeEnabled;
	var $_baseUrl = false;
	
	
	public function __construct()
	{
		$this->fGet 	= new LwInputfilter($this->stripvars($_GET));
		$this->fPost 	= new LwInputfilter($this->stripvars($_POST));
		$this->fCookie  = new LwInputfilter($this->stripvars($_COOKIE));
		$this->fEnv 	= new LwInputfilter($this->stripvars($_ENV));
		$this->fFiles 	= new LwInputfilter($this->stripvars($_FILES));
		//lw_object::emptyGlobals();
		
   	    if (null === self::$_unicodeEnabled) 
        {
            self::$_unicodeEnabled = (@preg_match('/\pL/u', 'a')) ? true : false;
        }
	}

    function stripvars($var)
    {
        if (get_magic_quotes_gpc() === 1) 
        {
            if (is_array($var))
            {
                return array_map(array($this, 'stripvars'), $var);
            } 
            else 
            {
                return stripslashes($var);
            }
        } 
        return $var;
    }

    public static function getInstance($useCollector = false, $flag = false)
    {
    	if(self::$instance == null)
        {
            self::$instance = new lw_request($useCollector, $flag);
        }
        return self::$instance;
    }	

    /*   
	*   URL Functions
 	*/
    
    public function getBasename()
    {
    	return basename($this->getServer('SCRIPT_NAME'));
    }

    public function getBasedir()
    {
    	return dirname($this->getServer('SCRIPT_NAME'))."/";
    }
    
    public function getHost()
    {
    	return $this->getServer('HTTP_HOST');
    }
    
    public function getProtocol()
    {
    	return $this->getServer('SERVER_PROTOCOL');
    }
    
    public function getRequestURL()
    {
    	if (!strstr($this->getHost(), "HTTPS"))
    	{
    		$url = "http://";
    	}
    	else
    	{
    		$url = "https://";
    	}
    	$url.=$this->getHost().$this->getBaseUrl();
    	return $url;
    }
    
    public function getRequestMethod()
    {
    	return $this->getServer('REQUEST_METHOD');
    }
    
    public function getReferrer()
    {
    	return $this->getServer('HTTP_REFERER');
    }

    public function getQuery()
    {
    	return $this->getServer('HTTP_QUERY');
    }
    
    public function isXmlHttpRequest()
    {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }    
    
	    /*   
		* taken from Zend Framework - START
		* modified by Logic Works GmbH (2009) 
	 	* @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
	 	* @license    http://framework.zend.com/license/new-bsd     New BSD License* 
	 	*/
	    public function getHeader($header)
	    {
	        // Try to get it from the $_SERVER array first
	        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
	        if (!empty($_SERVER[$temp])) {
	            return $_SERVER[$temp];
	        }
	
	        // This seems to be the only way to get the Authorization header on
	        // Apache
	        if (function_exists('apache_request_headers')) {
	            $headers = apache_request_headers();
	            if (!empty($headers[$header])) {
	                return $headers[$header];
	            }
	        }
	        return false;
	    }    
	    
	    public function getServer($key = null, $default = null)
	    {
	        if (null === $key) {
	            return $_SERVER;
	        }
	
	        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
	    }
	    
	   public function setRequestUri()
	    {
			if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
			    $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
			} elseif (isset($_SERVER['REQUEST_URI'])) {
			    $requestUri = $_SERVER['REQUEST_URI'];
			} elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
			    $requestUri = $_SERVER['ORIG_PATH_INFO'];
			    if (!empty($_SERVER['QUERY_STRING'])) {
			        $requestUri .= '?' . $_SERVER['QUERY_STRING'];
			    }
			} else {
			    return $this;
			}
	        $this->_requestUri = $requestUri;
	        return $this;
	    }
	
	    /**
	     * Returns the REQUEST_URI taking into account
	     * platform differences between Apache and IIS
	     *
	     * @return string
	     */
	    public function getRequestUri()
	    {
	        if (empty($this->_requestUri)) {
	            $this->setRequestUri();
	        }
	        return $this->_requestUri;
	    }    
	    
	    /**
	     * Set the base URL of the request; i.e., the segment leading to the script name
	     *
	     * E.g.:
	     * - /admin
	     * - /myapp
	     * - /subdir/index.php
	     *
	     * Do not use the full URI when providing the base. The following are
	     * examples of what not to use:
	     * - http://example.com/admin (should be just /admin)
	     * - http://example.com/subdir/index.php (should be just /subdir/index.php)
	     *
	     * If no $baseUrl is provided, attempts to determine the base URL from the
	     * environment, using SCRIPT_FILENAME, SCRIPT_NAME, PHP_SELF, and
	     * ORIG_SCRIPT_NAME in its determination.
	     *
	     * @param mixed $baseUrl
	     * @return Zend_Controller_Request_Http
	     */
	    public function setBaseUrl($baseUrl = null)
	    {
	        if ((null !== $baseUrl) && !is_string($baseUrl)) {
	            return $this;
	        }
	
	        if ($baseUrl === null) {
	            $filename = basename($_SERVER['SCRIPT_FILENAME']);
	
	            if (basename($_SERVER['SCRIPT_NAME']) === $filename) {
	                $baseUrl = $_SERVER['SCRIPT_NAME'];
	            } elseif (basename($_SERVER['PHP_SELF']) === $filename) {
	                $baseUrl = $_SERVER['PHP_SELF'];
	            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
	                $baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
	            } else {
	                // Backtrack up the script_filename to find the portion matching
	                // php_self
	                $path    = $_SERVER['PHP_SELF'];
	                $segs    = explode('/', trim($_SERVER['SCRIPT_FILENAME'], '/'));
	                $segs    = array_reverse($segs);
	                $index   = 0;
	                $last    = count($segs);
	                $baseUrl = '';
	                do {
	                    $seg     = $segs[$index];
	                    $baseUrl = '/' . $seg . $baseUrl;
	                    ++$index;
	                } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
	            }
	
	            // Does the baseUrl have anything in common with the request_uri?
	            $requestUri = $this->getRequestUri();
	
	            if (0 === strpos($requestUri, $baseUrl)) {
	                // full $baseUrl matches
	                $this->_baseUrl = $baseUrl;
	                return $this;
	            }
	
	            if (0 === strpos($requestUri, dirname($baseUrl))) {
	                // directory portion of $baseUrl matches
	                $this->_baseUrl = rtrim(dirname($baseUrl), '/');
	                return $this;
	            }
	
	            if (!strpos($requestUri, basename($baseUrl))) {
	                // no match whatsoever; set it blank
	                $this->_baseUrl = '';
	                return $this;
	            }
	
	            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
	            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
	            // from PATH_INFO or QUERY_STRING
	            if ((strlen($requestUri) >= strlen($baseUrl))
	                && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0)))
	            {
	                $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
	            }
	        }
	
	        $this->_baseUrl = rtrim($baseUrl, '/');
	        return $this;
	    }
	
	    /**
	     * Everything in REQUEST_URI before PATH_INFO
	     * <form action="<?=$baseUrl?>/news/submit" method="POST"/>
	     *
	     * @return string
	     */
	    public function getBaseUrl()
	    {
	        if (!$this->_baseUrl) {
	            $this->setBaseUrl();
	        }
	
	        return $this->_baseUrl;
	    }    
	    
	    /*   
		* taken from Zend Framework - STOP
	    */
    
    /*   
	*   /URL Functions
 	*/
    
    /*   
	*   Request Parameter Functions
 	*/
    
    public function getFileData($name)
    {
        return $this->fFiles->getRaw($name);
    }
    
    public function getPostArray()
    {
        return $this->fPost->_source;
    }
    
    public function getGetArray()
    {
        return $this->fGet->_source;
    }
    
    private function get($key)
    {
    	if ($key === null) return null;
    	if ($this->fGet->keyExists($key)) 		return $this->fGet->getRaw($key);
		if ($this->fPost->keyExists($key)) 		return $this->fPost->getRaw($key);
		if ($this->fCookie->keyExists($key)) 	return $this->fCookie->getRaw($key);
		if ($this->fEnv->keyExists($key)) 		return $this->fEnv->getRaw($key);
		return null;
    }
    
	function getRaw($key, $array=false)
	{
		$value = $this->get($key);
		if (is_array($array))
		{
			$value = $this->filterChain($value, $array);
		}
		return $value; 
	}
	
	function filterChain($value, $array)
	{
		if (in_array("htmlentities", $array))
		{
			$value = htmlentities($value);
		}
		if (in_array("stripTags", $array))
		{
			$value = striptags($value);
		}
		if (in_array("stripNewlines", $array))
		{
			$value = preg_replace("/\\\$/", "$", 	$value);
			$value = preg_replace("/\r/", 	"", 	$value);
			$value = str_replace("!", 		"!", 	$value);
			$value = str_replace("'", 		"'", 	$value);
			$value = str_replace("\n", 		"", 	$value);
		}
		if (in_array("toLower", $array))
		{
			$value = strtolower($value);
		}
		if (in_array("toUpper", $array))
		{
			$value = strtoupper($value);
		}
		if (in_array("trim", $array))
		{
			$value = trim($value);
		}
		$value = str_replace(" ", " ", $value);
		$value = str_replace(chr(0xCA), "", $value);
		return $value;
	}
	
	function getInt($key, $array=false)
	{
		$value = (int) ((string) $this->get($key));
		if (is_array($array))
		{
			$value = $this->filterChain($value, $array);
		}
		return $value; 
	}
	
	function getAlnum($key, $array=false)
	{
        $value = $this->get($key);
		if (!self::$_unicodeEnabled) {
            // POSIX named classes are not supported, use alternative a-zA-Z0-9 match
            $pattern = '/[^a-zA-Z0-9\s]/';
        } else {
            // Unicode safe filter for the value
            $pattern = '/[^\p{L}\p{N}\s]/u';
        }
        $value = preg_replace($pattern, '', (string) $value);		
		if (is_array($array))
		{
			$value = $this->filterChain($value, $array);
		}
		return $value; 
	}

	function getAlpha($key, $array=false)
	{
		$value = $this->get($key);
        if (!self::$_unicodeEnabled) {
            // POSIX named classes are not supported, use alternative a-zA-Z match
            $pattern = '/[^a-zA-Z\s]/';
        } else {
            $pattern = '/[^\p{L}\s]/u';
        }

        $value = preg_replace($pattern, '', (string) $value);		
		if (is_array($array))
		{
			$value = $this->filterChain($value, $array);
		}
		return $value; 		
	}
	
	function getEmail($key)
	{
		$value = $this->get($key);
		if (!eregi("^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4}$", $value)) return false;
		return $value; 		
	}
	
    public function testRegex($key, $pattern = NULL)
    {
		$value = $this->get($key);
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
	
    /*   
	*   /Request Parameter Functions
 	*/
}
