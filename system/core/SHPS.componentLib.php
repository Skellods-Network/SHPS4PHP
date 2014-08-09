<?php

/**
 * SHPS Component Library<br>
 * This file is part of the Skellods Homepage System. It must not be distributed
 * without the licence file or without this header text.
 * 
 * 
 * @author Marco Alka <admin@skellods.de>
 * @copyright (c) 2013, Marco Alka
 * @license privat_Licence.txt Privat Licence
 * @link http://skellods.de Skellods
 */


require_once 'SHPS.secure.php';

// namespace \Skellods\SHPS;


/**
 * COMPONENT LIBRARY
 *
 * All functionalities to input premade HTML5 code which is wompliant with SHPS
 * are bundled in the CL class.
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 1.1
 */
class SHPS_CL
{
    
    /**
     * Singelton
     * 
     * @var array Array of SHPS_lang 
     */
    private static $instances = array();
    
    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        $class = get_called_class();
        if(!empty(self::$instances[$class]))
        {
            throw new SHPS_exception(SHPS_ERROR_INSTANCE);
        }
        
        self::$instances[$class] = $this;
    }
    
    /**
     * Return singelton instance
     * 
     * @return SHPS_lang
     */
    final public static function getInstance()
    {
        $class = get_called_class();
        if(empty(self::$instances[$class]))
        {
            $rc = new ReflectionClass($class);
            self::$instances[$class] = $rc->newInstanceArgs(func_get_args());
        }
        
        return self::$instances[$class];
    }
    
    /**
     * Cloning is prohibited
     * 
     * @throws SHPS_exception
     */
    final public function __clone()
    {
        throw new SHPS_exception(SHPS_ERROR_CLONE);
    }
    
    /**
     * Make a Hyperlink
     * 
     * @param string $ref Full URL or name of content page
     * @param string $description
     * @param mixed $basicAttributes SHPS_basicAttributes or string //Default: null
     * @param boolean $newTab //Default: false
     * @param string|null $namespace //Default: null
     * @param boolean $ssl Should SSL be used?
     * @return string
     * @throws SHPS_exception
     */
    public static function makeHyperlink($ref, $description, $basicAttributes = null, $newTab = false, $namespace = null, $ssl = false)
    {
        if(!is_string($basicAttributes)
           && !$basicAttributes instanceof SHPS_basicAttributes
           && $basicAttributes !== null)
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        $ba = '';
        if($basicAttributes !== null)
        {
            $ba = (string)$basicAttributes;
            if(substr($ba,0,1) != ' ')
            {
                $ba = ' ' . $ba;
            }
        }
        
        $rel = '';
        if(substr($ref,0,7) != 'http://' && substr($ref,0,7) != 'https:/' && substr($ref,0,1) != '#')
        {
            $ref = self::getContentURL($ref, $namespace, $ssl);
        }
        else
        {
            $rel = ' rel="nofollow"';
        }
        
        $nt = '';
        if($newTab)
        {
            $nt = ' target="_blank"';
        }
        
        return '<a href="' . $ref . '"' . $ba . $rel . $nt . '>' . $description . '</a>';
    }
    
    /**
     * Make a link to change the language
     * 
     * @param string $lang
     * @param string $description
     * @param mixed $basicAttributes SHPS_basicAttributes or string //Default: null
     * @return string
     * @throws SHPS_exception
     */
    public static function makeLangLink($lang, $description, $basicAttributes = null)
    {
        if(!is_string($basicAttributes)
           && !$basicAttributes instanceof SHPS_basicAttributes
           && $basicAttributes !== null)
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        $ba = '';
        if($basicAttributes !== null)
        {
            $ba = (string)$basicAttributes;
            if(substr($ba,0,1) != ' ')
            {
                $ba = ' ' . $ba;
            }
        }
        
        $link = SHPS_main::getHPConfig('General_Config','URL') .
                SHPS_main::getHPConfig('General_Config','index') .
                '?lang=' . antiXSS($lang);
        
        $sessID = SHPS_auth::getSID();
        if($sessID != '')
        {
            $link .= '&SHPSSID=' . $sessID;
        }
        
        foreach($_GET as $key => $value)
        {
            if($key == 'lang' || $key == 'SHPSSID')
            {
                continue;
            }
            
            $link .= '&' . $key . '=' . $value;
        }
        
        return '<a href="' . $link . '"' . $ba . '>' . $description . '</a>';
    }
    
    /**
     * Get raw URL<br>
     * This is handy, if just lang and sessID etc. is needed which will not
     * change the site
     * 
     * @param integer $paramChar outputs the char for param chaining
     * @param string|null $namespace //Default: null
     * @param boolean $ssl Should SSL be used?
     * @param boolean $resourceURL should the static resource URL be used?
     * @return string
     */
    public static function getRawURL(&$paramChar, $namespace = null, $ssl = false, $resourceURL = false)
    {
        if ($resourceURL)
        {
            $url = SHPS_main::getHPConfig('General_Config','static_resources_URL');
        }
        else
        {
            $url = SHPS_main::getHPConfig('General_Config','URL');
        }
        $https = getSG(INPUT_SERVER,'HTTPS');
        if (($https != null && $https != 'off') || $ssl)
        {
            if (substr($url,0,5) != 'https')
            {
                $url = 'https' . substr($url, 4);
            }
        }
        
        $link = $url . SHPS_main::getHPConfig('General_Config','index');
        
        $paramChar = '?';
        $lang = getSG(INPUT_REQUEST, 'lang');
        if($lang !== null)
        {
            $link .= $paramChar . 'lang=' . antiXSS($lang);
            $paramChar = '&';
        }
        
        $sessID = SHPS_auth::getSID();
        if($sessID != '')
        {
            $link .= $paramChar . 'SHPSSID=' . $sessID;
            $paramChar = '&';
        }
        
        if($namespace === null)
        {
            if(($ns = SHPS_main::getNamespace()))
            {
                $link .= $paramChar . 'ns=' . $ns;
                $paramChar = '&';
            }
        }
        else
        {
            $link .= $paramChar . 'ns=' . $namespace;
            $paramChar = '&';
        }
        
        return $link;
    }
    
    /**
     * Get URL to content
     * 
     * @param string $content
     * @param string|null $namespace //Default: null
     * @param boolean $ssl Should SSL be used?
     * @return string
     */
    public static function getContentURL($content, $namespace = null, $ssl = false)
    {
        $cp = '';
        $link = self::getRawURL($cp, $namespace, $ssl); 
        return $link . $cp . 'site=' . $content;
    }
    
    /**
     * Get URL to a resource
     * 
     * @param string $resource
     * @param string|null $namespace //Default: null
     * @param boolean $ssl Should SSL be used?
     * @return string
     */
    public static function getResourceURL($resource, $namespace = null, $ssl = false)
    {
        $cp = '';
        $link = self::getRawURL($cp, $namespace, $ssl, true); 
        return $link . $cp . 'file=' . $resource;
    }
}

/**
 * Container for basic attributes
 */
class SHPS_basicAttributes
{
    /**
     * Contains all link classes
     * @var string
     */
    public $classes = '';
    
    
    public function __toString()
    {
        $r = '';
        if($this->classes != '')
        {
            $r .= 'class="' . $this->classes . '"';
        }
        
        return $r;
    }
}
