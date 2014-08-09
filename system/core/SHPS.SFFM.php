<?php

/**
 * SHPS Function Framework<br>
 * This file is part of the Skellods Homepage System. It must not be distributed
 * without the licence file or without this header text.
 * 
 * This file adds some functions which are useful in a PHP programmer's
 * everyday life.
 * 
 * 
 * @author Marco Alka <admin@skellods.de>
 * @copyright (c) 2013, Marco Alka
 * @license privat_Licence.txt Privat Licence
 * @link http://skellods.de Skellods
 * @version 1.3
 */

// Include Pear
$pearSystemFile = 'System.php';
if(stream_resolve_include_path($pearSystemFile) !== false)
{
    include_once $pearSystemFile;
}


define('SHPS_NL',PHP_EOL);
	define('NL',SHPS_NL);
define('SHPS_EOL',SHPS_NL);
	define('EOL',SHPS_NL);
        
define('SHPS_WEBSERVER_APACHE',0);
define('SHPS_WEBSERVER_NGINX',1);
define('SHPS_WEBSERVER_LIGHTTPD',2);
define('SHPS_WEBSERVER_LITESPEED',3);
define('SHPS_WEBSERVER_NODE',4);
define('SHPS_WEBSERVER_LUVIT',5);
define('SHPS_WEBSERVER_YAWS',6);


/**
 * Check if PEAR is installed
 * 
 * @return: boolean
 */
function isPearInstalled()
{
    return class_exists('System');
}

/**
 * Fetch content from URL with POST parameters
 * 
 * @param: string
 * @param: array
 * @param: string|false //Default: false
 * @param: integer (seconds) //Default: 10
 * 
 * @return: array(status:string,bstatus:bool,header:string or integer,content:string) or false
 */
function post($url, $data, $referer = false, $timeout = 10)
{
    if(isPearInstalled())
    {
	$r = HttpRequest($url, HttpRequest::METH_POST);
	$r->setOptions(array('timeout' => $timeout));
	$r->setOptions(array('useragent' => 'Skellods SFF'));
	if($referer !== false)
        {
	    $r->setOptions(array('referer' => $referer));
        }
	    
	$r->addPostFields($data);
	$ret = $r->send();
	$head = $ret->getHeaders();
	$header = '';
	foreach($head as $key => $value)
	{
	    $header .= $key . ': ' . $value . '\r\n';
	}
	
	$body = $ret->getBody();
	
	unset($r);
	return array(	'status' => 'OK',
			'bstatus' => true,
			'header' => $header,
			'content' => $body);
    }
    
    $data = http_build_query($data);
    $url = parse_url($url);
    if(isset($url['port']))
    {
	$port = $url['port'];
    }
    elseif($url['scheme'] == 'https')
    {
	$port = 443;
    }
    else
    {
	$port = 80;
    }
    
    $host = $url['host'];
    $path = $url['path'];
    
    $errno = 0;
    $errstr = '';
    $s = fsockopen($host, $port, $errno, $errstr, 10);
    if($s)
    {
	fputs($s, "POST $path HTTP/1.1\r\n");
	fputs($s, "Host: $host\r\n");
	if ($referer !== false)
        {
            fputs($s, "Referer: $referer\r\n");
        }
	
	fputs($s, "Content-type: application/x-www-form-urlencoded\r\n");
	fputs($s, "Content-length: " . strlen($data) . "\r\n");
	fputs($s, "Connection: close\r\n\r\n");
	fputs($s, $data);
	
	$result = '';
	while(!feof($s))
	{
	    $result .= fgets($s, 128);
	}
	
	fclose($s);
	$result = explode("\r\n\r\n", $result, 2);
	$header = isset($result[0]) ? $result[0] : '';
	$content = isset($result[1]) ? $result[1] : '';
	
	return array(	'status' => 'OK',
			'bstatus' => true,
			'header' => $header,
			'content' => $content);
    }
    
    return array(	'status' => 'ERROR',
			'bstatus' => false,
			'header' => $errno,
			'content' => $errstr);
}

/**
 * Transforms ascii special chars to entities so there are no problems with UTF-8
 * 
 * @param string $string
 * @return string
 */
function ascii2entities($string)
{
    $array = array(169, 196, 214, 220, 223, 228, 246, 252);
    $cc = count($array);
    for($i = 0; $i < $cc; $i++)
    {
	$entity = htmlentities(chr($array[$i]), ENT_QUOTES, 'cp1252');
	$temp = substr($entity, 0, 1);
	$temp .= substr($entity, -1, 1);
	if($temp != '&;')
        {
	    $string = str_replace(chr($array[$i]), '', $string);
        }
	else
        {
	    $string = str_replace(chr($array[$i]), $entity, $string);
        }
    }
    
    return $string;
}

/**
 * Get folder size - usable with all platforms
 * 
 * @param string $dir
 * @return integer
 */
function foldersize_universal($dir)
{
    if(substr($dir, -1) !== '/' && substr($dir, -1) !== '\\')
    {
	$dir .= '/';
    }

    $d = opendir($dir);

    if(!$d)
    {
	return -1;
    }

    while(($f = readdir($d)) !== false)
    {
	if($f == '.' || $f == '..')
        {
	    continue;
        }

        $size = 0;
	if(is_dir($dir . $f))
        {
	    $size += getfoldersize_universal($dir . $f . '/');
        }
	else
        {
	    $size += filesize($dir . $f);
        }
    }

    closedir($d);
    return $size;
}

/**
 * Get folder size - usable with Linux
 * 
 * @param string $dir
 * @return integer
 */
function foldersize_linux($dir)
{
    if(!is_callable('popen'))
    {
	return foldersize_universal($dir);
    }

    if(substr($dir, -1) !== '/')
    {
	$dir .= '/';
    }

    $d = popen('/usr/bin/du -sk ' . $dir, 'r');
    $size = fgets($d, 4096);
    $size = substr($size, 0, strpos($size, ' '));
    pclose($d);
    return $size;
}

/**
 * Get folder size - usable with Windows
 * 
 * @param string $dir
 * @return integer
 */
function foldersize_windows($dir)
{
    if(!class_exists('COM', false))
    {
	return foldersize_universal($dir);
    }

    if(substr($dir, -1) !== '\\')
    {
	$dir .= '\\';
    }

    $obj = new COM('scripting.filesystemobject');
    if(is_object($obj))
    {
	$ref = $obj->getfolder($dir);
	$size = $ref->size;
	$obj = null;
    }
    else
    {
	$size = -1;
    }

    return $size;
}

/**
 * Get folder size - parallel function to filesize()
 * 
 * @param string $foldername
 * @return integer
 */
function foldersize($foldername)
{
    return foldersize_universal($foldername);
}

/**
 * Send UTF-8 Mail
 * 
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $header
 * @param boolean $html
 * @return boolean
 */
function mail_utf8($to, $subject, $message, $header = '', $html = false)
{
    $utf8 = '=?UTF-8?B?';
    $utf8_end = '?=';    
    if($html)
    {
	$header_ = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
    }
    else
    {
	$header_ = "MIME-Version: 1.0\r\nContent-type: text/plain; charset=UTF-8\r\n";
    }

    return mail($to, $utf8 . base64_encode($subject) . $utf8_end, $message, $header_ . $header);
}

/**
 * Generate a random string
 * 
 * @param integer $length
 * @param string $chars //Default: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'
 * @return string
 */
function randomString($length, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
{
    $chars_length = (strlen($chars) - 1);
    $string = $chars{rand(0, $chars_length)};
    for($i = 1; $i < $length; $i = strlen($string))
    {
	$r = $chars{rand(0, $chars_length)};
	if($r != $string{$i - 1})
        {
	    $string .= $r;
        }
    }
    
    return $string;
}

/**
 * Ping IP @ port
 * 
 * @param string $ip
 * @param integer $port //Default: 22
 * @return boolean
 */
function ping($ip, $port = 22)
{
    $serror = 0;
    $serrorstr = 0;
    $ps = fsockopen($ip, $port, $serror, $serrorstr, 1);
    if(!$ps)
    {
	return false;
    }
    else
    {
	fclose($ps);
	return true;
    }
}

/**
 * Will deal with XSS injections
 * 
 * @param string $str
 * @return string
 */
function antiXSS($str)
{
    return htmlspecialchars($str);
}

/**
 * Will tell you if the function has been disabled in php.ini
 * 
 * @param string $function
 * @return boolean
 */
function isEnabled($function)
{
    static $dfs = array();
    
    if(isset($dfs))
    {
	return !in_array($function, $dfs);
    }
    
    $disabled = explode(',', ini_get('disable_functions'));
    foreach ($disabled as $disableFunction)
    {
        $dfs[] = trim($disableFunction);
    }

    return !in_array($function, $dfs);
}

/**
 * Iterate all files in a directory that match a certain regex
 * 
 * @param string $pattern
 * @param string $dir
 * @param boolean $recursive
 * @return array of string
 */
function iterateFiles($pattern, $dir, $recursive = false)
{
    $result = array();
    if($recursive)
    {
	$di = new RecursiveDirectoryIterator($dir);
    }
    else
    {
	$di = new DirectoryIterator($dir);
    }
    
    $i = new RecursiveIteratorIterator($di);
    $ri = new RegexIterator($i, $pattern);
    foreach($ri as $fn => $f)
    {
	$result[] = $fn;
    }
    
    return $result;
}

/**
 * Convert string to other Code Page
 * 
 * @param string $str
 * @param string $CP //Default: 'UTF-8'
 * @param string $oldCP //Default: null
 * @param boolean $optimize //Default: true
 * @return string
 */
function toCP($str, $CP = 'UTF-8', $oldCP = null, $optimize = true)
{
    if($optimize)
    {
	switch($CP)
	{
	    case 'ISO-2022-JP':
	    {
		$CP = 'ISO-2022-JP-MS';
		break;
	    }
	}
    }
    
    if(function_exists('mb_convert_encoding'))
    {
	return mb_convert_encoding($str, $CP, $oldCP);
    }
    
    return $str;
}

/**
 * Tries to load an extension. Returns true if loaded
 * 
 * @param string $name
 * @return boolean
 */
function loadExtension($name)
{
    // Let's make some Spaghetti code with Pasta - mmmmhhh!
    if(!extension_loaded($name))
    {
	if(function_exists('dl'))
	{
	    if(!dl($name . '.so'))
            {
		if(!dl($name . '.dll'))
                {
		    return false;
                }
            }
	}
	else
        {
	    return false;
        }
    }
    
    return true;
}

/**
 * Evaluate input and return a boolean<br>
 * This function considers mixed input<br>
 * meaning YES and NO are also considered boolean and TRUE and FALSE as strings, too<br>
 * empty strings are considered false, as well as number 0 as string<br>
 * if no rule is adequate, true is returned
 * 
 * @param mixed $var
 * @return boolean
 */
function evalBool($var)
{
    if($var === true || $var === false)
    {
        return $var;
    }
    
    if($var === null || $var === 0 || $var === '')
    {
        return false;
    }
    
    switch(strtoupper($var))
    {
        case 'TRUE':
        {
            return true;
        }
        
        case 'FALSE':
        {
            return false;
        }
        
        case 'YES':
        {
            return true;
        }
        
        case 'NO':
        {
            return false;
        }
        
        case '0':
        {
            return false;
        }
        
        default:
        {
            return true;
        }
    }
}

/**
 * Tests if a session is active
 * 
 * @return boolean
 * @throws UnexpectedValueException
 */
function session_is_active()
{
    $setting = 'session.use_trans_sid';
    $current = ini_get($setting);
    if($current === false)
    {
        throw new UnexpectedValueException('Setting ' . $setting . ' does not exists.');
    }

    $result = ini_set($setting, $current); 
    return $result !== $current;
}

/**
 * Get a string from the offset to the next occurance of needle
 * 
 * @param string $str
 * @param integer $offset
 * $param mixed $needle
 * @return string
 */
function getStringUntilNeedle($str, $offset = 0, $needle = ' ')
{
    if($offset > 0)
    {
        $str = substr($str, $offset);
    }
    
    $i = strstr($str, $needle);
    $tmp = strstr($str, PHP_EOL);
    if($i > $tmp && $tmp >= 0)
    {
        $i = $tmp;
    }
    
    if($i < 0)
    {
        $i = strlen($str) + 1;
    }
    
    return substr($str, 0, $i);
}

/**
 * Get operating system
 * 
 * @return string
 */
function getOS()
{
    return getStringUntilNeedle(php_uname('s'),0,' ');
}

/**
 * Check if parameter is a closure
 * 
 * @param Closure $t
 * @return boolean
 */
function isClosure($t)
{
    return is_object($t) && ($t instanceof Closure);
}

/**
 * Get super global in a safe way
 * The following values for TYPE are possible:
 * - INPUT_POST
 * - INPUT_GET
 * - INPUT_COOKIE
 * - INPUT_ENV
 * - INPUT_SERVER
 * - INPUT_SESSION
 * - INPUT_REQUEST
 * 
 * DEFAULT can also be a closure which will only be executed if needed
 * If no value could be found, $default will always be used
 * If null is returned, no value could be found
 * 
 * @param integer $type
 * @param string $key
 * @param mixed $default // Default is null
 * @param Closure $callback takes the value as param and has to return a mixed value. Will be called after getting a value
 * @param integer $filter filter for PHP filter functions; see http://www.w3schools.com/php/php_ref_filter.asp
 * @return mixed
 */
function getSG($type, $key, $default = null, $callback = null, $filter = FILTER_DEFAULT)
{
    switch($type)
    {
        case INPUT_SESSION:
        {
            if(issetSG($type, $key))
            {
                $value = filter_var($_SESSION[$key], $filter);
            }
            else
            {
                $value = null;
            }
            
            break;
        }
        
        case INPUT_REQUEST:
        {
            if(issetSG($type, $key))
            {
                $value = filter_var($_REQUEST[$key], $filter);
            }
            else
            {
                $value = null;
            }
            
            break;
        }
        
        default:
        {
            $value = filter_input($type, $key, $filter);
            break;
        }
    }   
    
    if(isClosure($callback))
    {
        $value = $callback($value);
    }
    
    if($value === null)
    {
        if(isClosure($default))
        {
            $value = $default();
        }
        else
        {
            $value = $default;
        }
    }
    
    return $value;
}

/**
 * Checks if super global contains key
 * The following values for TYPE are possible:
 * - INPUT_POST
 * - INPUT_GET
 * - INPUT_COOKIE
 * - INPUT_ENV
 * - INPUT_SERVER
 * - INPUT_SESSION
 * - INPUT_REQUEST
 * 
 * @param integer $type
 * @param string $key
 * @return boolean
 */
function issetSG($type, $key)
{
    switch($type)
    {
        case INPUT_SESSION:
        {
            $r = isset($_SESSION[$key]);
            break;
        }
        
        case INPUT_REQUEST:
        {
            $r = isset($_REQUEST[$key]);
            break;
        }
        
        default:
        {
            $r = filter_has_var($type, $key);
            break;
        }
    }
    
    return $r;
}

/**
 * Sanitize an integer
 * 
 * @param mixed $int
 * @return integer
 */
function cleanInt($int)
{
    return intval($int);
}

/**
 * Sanitize a string
 * 
 * @param mixed $str
 * @return string
 */
function cleanStr($str)
{
    return filter_var($str, FILTER_SANITIZE_MAGIC_QUOTES);
}

/**
 * Find out if current user uses SSL
 * 
 * @return boolean
 */
function isSSL()
{
    $https = getSG(INPUT_SERVER,'HTTPS');
    return $https != null && $https != 'off';
}