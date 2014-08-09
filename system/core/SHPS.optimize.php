<?php

/**
 * SHPS Optimizer<br>
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
 * OPTIMIZE
 *
 * All functionalities to optimize the size, performance and resource usage are
 * bundled in the optimize class
 *
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 1.1
 */
class SHPS_optimize
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
     * Minify CSS on the fly
     * 
     * @param string $str
     * @return string
     */
    public static function minifyCSS_OTF($str)
    {
        $str = str_replace(array('\r', '\n', '\t'), ' ', $str);
        $str = preg_replace('!/\*.*?\*/!s', ' ', $str);
        $i = 0;
        do
        {
            $str = str_replace('  ',' ',$str,$i);
        }
        while($i > 0);

        $sea = array(' { ',' } ',': ','; ',', ');
        $rep = array('{','}',':',';',',');
        $str = str_replace($sea, $rep, $str);
        return $str;
    }
    
    /**
     * Minify JS on the fly
     * 
     * @param string $str
     * @return string
     * @todo Make string and regex aware
     */
    public static function minifyJS_OTF($str)
    {
        // ATM this does make trouble.
//        $str = str_replace(array('\r', '\n', '\t'), ' ', $str);
//        $str = preg_replace('!/\*.*?\*/!s', ' ', $str);
//        $i = 0;
//        do
//        {
//            $str = str_replace('  ',' ',$str,$i);
//        }
//        while($i > 0);
//
//        $sea = array(' { ',' } ','; ');
//        $rep = array('{','}',';');
//        $str = str_replace($sea, $rep, $str);
        return $str;
    }
    
    /**
     * Minify HTML on the fly
     * 
     * @param string $str
     * @return string
     */
    public static function minifyHTML_OTF($str)
    {
        return $str;
    }
    
    /**
     * Optimize Body
     * 
     * @param string $str
     * @return string
     */
    public static function optimize()
    {
        $html = SHPS_main::getSiteContent();
        // translate to HTON
        // optimize
        // translate to HTML
        SHPS_main::setSiteContent($html);
    }
}
