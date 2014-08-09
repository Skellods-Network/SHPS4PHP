<?php

/**
 * SHPS Client Analysis<br>
 * This file is part of the Skellods Homepage System. It must not be distributed
 * without the licence file or without this header text.
 * 
 * 
 * @author Marco Alka <admin@skellods.de>
 * @copyright (c) 2013, Marco Alka
 * @license privat_Licence.txt Privat Licence
 * @link http://skellods.de Skellods
 */


require_once('SHPS.secure.php');
require_once('SHPS.SFFM.php');

// namespace \Skellods\SHPS;


/**
 * CLIENT
 *
 * All functionalities to analyse and gain insight about the client are bundled 
 * in the CLIENT class
 *
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 1.2
 */
class SHPS_client
{
    /**
     * User agent
     * 
     * @var string
     */
    private static $ua;
    
    /**
     * Contains UA related information:
     * browser<br>
     * browserVersion<br>
     * os<br>
     * osBit<br>
     * osVersion<br>
     * engine<br>
     * engineVersion<br>
     * mozilla(boolean)<br>
     * mozillaVersion<br>
     * addon<br>
     * addonVersion<br>
     * likeGekko(boolean)<br>
     * safariVersion
     * 
     * @var array
     */
    private static $uaInfo;
    
    /**
     * IP
     * 
     * @var string
     */
    private static $IP;
    
    /**
     * X-forwarded-for IP
     * 
     * @var string
     */
    private static $XForward;
    
    /**
     * Referer
     * 
     * @var string
     */
    private static $referer;
    
    /**
     * Country
     * 
     * @var integer
     */
    private static $country;
    
    /**
     * Client local time
     * 
     * @var integer
     */
    private static $time;
    
    /**
     * languages (short)
     * 
     * @var string
     */
    private static $languages;
    
    /**
     * Host ID
     * 
     * @var string 
     */
    private static $host;
    
    /**
     * Singelton
     * 
     * @var SHPS_client 
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
     * @return SHPS_pluginEngine
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
     * Get IP
     * 
     * @return string IPv4 or IPv6 or mixed
     */
    public static function getIP()
    {
        if(!isset(self::$IP))
        {
            self::$IP = getSG(INPUT_SERVER, 'REMOTE_ADDR');
            if(self::$IP === null)
            {
                self::$IP = '0.0.0.0';
            }
        }
        
        return self::$IP;
    }
    
    /**
     * Get X-forwarded-for IP
     * 
     * @return string IPv4 or IPv6 or mixed
     */
    public static function getXForward()
    {
        if(!isset(self::$XForward))
        {
            self::$XForward = getSG(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR');
            if(self::$XForward === null)
            {
                self::$XForward = '0.0.0.0';
            }
        }
        
        return self::$XForward;
    }
    
    /**
     * Get Host
     * 
     * @return string
     */
    public static function getHost()
    {
        if(!isset(self::$host))
        {
            if(function_exists('gethostbyaddr'))
            {
                self::$host = gethostbyaddr(self::getIP());
            }
            else
            {
                self::$host = 'N/A';
            }
        }
        
        return self::$host;
    }
    
    /**
     * Get user agent
     * 
     * @return string
     */
    public static function getUserAgent()
    {
        self::$ua = getSG(INPUT_SERVER, 'HTTP_USER_AGENT');
        if(self::$ua === null)
        {
            self::$ua = 'N/A';
        }
        
        return self::$ua;
    }
    
    /**
     * Analyze the given user agent and give back information in array about:<br>
     * browser<br>
     * browserVersion<br>
     * os<br>
     * osBit<br>
     * osVersion<br>
     * engine<br>
     * engineVersion<br>
     * mozilla(boolean)<br>
     * mozillaVersion<br>
     * addon<br>
     * addonVersion<br>
     * likeGekko(boolean)<br>
     * safariVersion
     * 
     * @param string $ua
     * @return array
     */
    public static function analyzeUA($ua)
    {
        $r = array(
            'browser' => 'N/A',
            'browserVersion' => 'N/A',
            'os' => 'N/A',
            'osBit' => 32,
            'browserEngine' => 'N/A',
            'engineVersion' => 'N/A',
            'mozilla' => false,
            'mozillaVersion' => 'N/A',
            'addon' => 'N/A',
            'addonVersion' => 'N/A',
            'likeGecko' => false,
            'safariVersion' => 'N/A',
        );
        
        $sql = SHPS_sql::newSQL();
        $match = 0;
        if(stristr($ua, 'wow64')
           || stristr($ua, 'win64')
           || stristr($ua, 'x64'))
        {
            $r['osBit'] = 64;
        }
        else
        {
            $r['osBit'] = 32;
        }
        
        $bregex = '/\(([A-Za-z0-9,;\. ]+?)\)/';
        preg_match_all($bregex, $ua, $match);
        $b = $match[1];
        $ua = preg_replace($bregex, '', $ua);
        $a1 = explode(' ', $ua);
        foreach($b as $c)
        {
            $a = 0;
            preg_match('/(.*)[,;](.*)/', $c, $a);
            $a1 += $a;
        }
            
        $tbl = $sql->openTable('browser');
        $cols = array(
            new SHPS_sql_colspec($tbl,'name')
        );
        
        $sql->readTables($cols);
        $browsers = $sql->fetchResult();
        
        $tbl = $sql->openTable('browserEngine');
        $cols = array(
            new SHPS_sql_colspec($tbl,'name')
        );
        
        $sql->readTables($cols);
        $browserEngines = $sql->fetchResult();
        
        $tbl = $sql->openTable('operatingSystem');
        $cols = array(
            new SHPS_sql_colspec($tbl,'name')
        );
        
        $sql->readTables($cols);
        $OSs = $sql->fetchResult();
        
        $tbl = $sql->openTable('browserAddon');
        $cols = array(
            new SHPS_sql_colspec($tbl,'name')
        );

        $sql->readTables($cols);
        $browserAddons = $sql->fetchResult();
        
        foreach($a1 as $a)
        {
            if(preg_match('/^mozilla\/(.*)$/i',$a,$match) === 1)
            {
                $r['mozilla'] = true;
                $r['mozillaVersion'] = $match[1];
            }
            elseif(preg_match('/^like gecko$/i',$a) === 1)
            {
                $r['likeGecko'] = true;
            }
            elseif(preg_match('/^Safari\/(.*)$/i',$a,$match) === 1)
            {
                $r['safariVersion'] = $match[1];
            }
            else
            {
                foreach($browserEngines as $b)
                {
                    $bname = $b->getValue('name');
                    if(preg_match('/^' . $bname . '(.*)/i', $a, $match) === 1)
                    {
                        $r['engine'] = $bname;
                        $r['engineVersion'] = str_replace(' ', '', $match[1]);
                        break;
                    }
                }
                
                foreach($browsers as $b)
                {
                    $bname = $b->getValue('name');
                    if(preg_match('/^' . $bname . '(.*)/i', $a, $match) === 1)
                    {
                        $r['browser'] = $bname;
                        $r['browserVersion'] = str_replace(' ', '', $match[1]);
                        break;
                    }
                }
                
                foreach($browserAddons as $b)
                {
                    $bname = $b->getValue('name');
                    if(preg_match('/^' . $bname . '\/(.*)/i', $a, $match) === 1)
                    {
                        $r['addon'] = $bname;
                        $r['addonVersion'] = str_replace(' ', '', $match[1]);
                        break;
                    }
                }
                
                foreach($OSs as $b)
                {
                    $bname = $b->getValue('name');
                    if(preg_match('/^' . $bname . '(.*)/i', $a, $match) === 1)
                    {
                        $r['os'] = $bname;
                        $r['osVersion'] = str_replace(' ', '', $match[1]);
                        break;
                    }
                }
            }
        }
        
        $sql->free();
        return $r;
    }
    
    /**
     * Fill UA related info and cache it
     */
    private static function fillUAInfo()
    {
        self::$uaInfo = self::analyzeUA(self::getUserAgent());
        
        /**
         * Cache analysed UA info
         */
        SHPS_scheduler::addTask(function() {
         
            SHPS_client::writeInfoToDB();
        }, 'cacheUAInfo');
    }
    
    public static function writeInfoToDB()
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('browserInfoCache');

        $cols = array(
            new SHPS_sql_colspec($tbl,'timestamp'),
            new SHPS_sql_colspec($tbl,'browser')
        );

        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'btoken'),
                SHPS_SQL_RELATION_EQUAL,
                SHPS_auth::getBToken()
                );

        $sql->readTables($cols, $conditions);
        if(($row = $sql->fetchRow()))
        {
            if($row->getValue('browser') != self::$uaInfo['browser']
               || $row->getValue('timestamp') < time() - SHPS_main::getHPConfig('General_Config', 'clientInfoCacheTime'))
            {
                $tbl->update(self::$uaInfo, $conditions);
            }
        }
        else
        {
            $info = array(
                'btoken' => SHPS_auth::getBToken()
            );

            $info += self::$uaInfo;
            $tbl->insert($info);
        }

        $sql->free();
    }
    
    /**
     * Get Browser
     * 
     * @return string
     */
    public static function getBrowser()
    {
        if(!isset(self::$uaInfo))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['browser'];
    }
    
    /**
     * Get browser version
     * 
     * @return string
     */
    public static function getBrowserVersion()
    {
        if(!isset(self::$uaInfo))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['browserVersion'];
    }
    
    /**
     * Get operating system
     * 
     * @return string
     */
    public static function getOS()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['os'];
    }
    
    /**
     * Get type of operating system
     * 
     * @return Integer
     */
    public static function getOSBit()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['osBit'];
    }
    
    /**
     * Get operating system version
     * 
     * @return string
     */
    public static function getOSVersion()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['osVersion'];
    }
    
    /**
     * Get browser render engine
     * 
     * @return string
     */
    public static function getBrowserEngine()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['engine'];
    }
    
    /**
     * Get browser render engine version
     * 
     * @return string
     */
    public static function getBrowserEngineVersion()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['engineVersion'];
    }
    
    /**
     * Is browser mosaic killer
     * 
     * @return boolean
     */
    public static function isMozilla()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['mozilla'];
    }
    
    /**
     * Get mozilla version if available
     * 
     * @return string
     */
    public static function getMozillaVersion()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['mozillaVersion'];
    }
    
    /**
     * Get browser addon if installed (e.g. chromeframe, lolifox, ...)
     * 
     * @return string
     */
    public static function getBrowserAddon()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['addon'];
    }
    
    /**
     * Get browser addon version, if available
     * 
     * @return string
     */
    public static function getBrowserAddonVersion()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['addonVersion'];
    }
    
    /**
     * Is this browser's render engine behaving similar to Gekko
     * 
     * @return boolean
     */
    public static function isLikeGekko()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['likeGekko'];
    }
    
    /**
     * If this browser impersonates safari or is safari you can get
     * the compatible version with this function
     * 
     * @return string
     */
    public static function getSafariVersion()
    {
        if(!(isset(self::$uaInfo)))
        {
            self::fillUAInfo();
        }
        
        return self::$uaInfo['safariVersion'];
    }

    /**
     * Get referer
     * 
     * @return string
     */
    public static function getReferer()
    {
        if(!isset(self::$referer))
        {
            if(isset($_SERVER['HTTP_REFERER']))
            {
                self::$referer = $_SERVER['HTTP_REFERER'];
            }
            else
            {
                self::$referer = 'N/A';
            }
        }
        
        return self::$referer;
    }
}
