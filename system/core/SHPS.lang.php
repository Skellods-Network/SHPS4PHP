<?php

/**
 * SHPS Language Manager<br>
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
require_once 'SHPS.SFFM.php';

// namespace \Skellods\SHPS;


/**
 * LANG
 *
 * All functionalities for language operations are bundled in the lang class
 * 
 *
 * @author Marco Alka <admin@skellods.de>
 * @version 1.2
 */
class SHPS_lang
{
    /**
     * Singelton
     * 
     * @var array Array of SHPS_lang 
     */
    private static $instances = array();
    
    /**
     * Which language is forces
     * 
     * @var string
     */
    private static $forceLang = null;
    

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
     * Get currently used language
     * 
     * @return string
     */
    public static function getLang()
    {
        if (isset(self::$forceLang))
        {
            return self::$forceLang;
        }
        
        $lang = getSG(INPUT_REQUEST, 'lang');
        if($lang === null)
        {
            $l = getSG(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE');
        }
        else
        {
            $l = getSG(INPUT_REQUEST, 'lang');
        }

        if($l !== null)
        {
            $l = substr($l, 0, 2);
        }
        
        return $l;
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
     * Force system to use a certain language
     * 
     * @param string $lang
     */
    public static function setLang($lang = 'en')
    {
        self::$forceLang = $lang;
        $_SESSION['SHPS_lang_forceLang'] = $lang;
    }
    
    /**
     * Get string in a certain language
     * 
     * @param string $group
     * @param string $key
     * @param string|NULL $namespace //Default: null
     * @param string|NULL $lang Language short //Default: null
     * @return string
     */
    public static function getString($group,$key,$namespace = null,$lang = null)
    {
        $na = '[N/A]';
        $default = false;
        if($lang !== null)
        {
            self::$forceLang = antiXSS($lang); 
        }
        else
        {
            $l = self::getLang();
            if($l !== null)
            {
                $l = substr($l, 0, 2);
                $tmp = self::getString($group,$key,$namespace,$l);
                if($tmp != $na)
                {
                    return $tmp;
                }
            }
            
            $default = true;
        }
        
        $sql = SHPS_sql::newSQL();
        $ltbl = $sql->openTable('language');
        $lgtbl = $sql->openTable('lang_group');
        $stbl = $sql->openTable('string');
        if(!$default)
        {
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_colspec($ltbl,'name'),
                    SHPS_SQL_RELATION_EQUAL,
                    $lang
                    );
        }

        if($namespace !== null)
        {
            $nstbl = $sql->openTable('namespace');
            $a = new SHPS_sql_condition(
                    new SHPS_sql_condition(
                            new SHPS_sql_colspec($nstbl,'ID'),
                            SHPS_SQL_RELATION_EQUAL,
                            new SHPS_sql_colspec($stbl,'namespace')
                            ),
                    SHPS_SQL_RELATION_AND,
                    new SHPS_sql_condition(
                            new SHPS_sql_colspec($nstbl,'name'),
                            SHPS_SQL_RELATION_EQUAL,
                            $namespace
                            )
                    );
            
            if(isset($conditions))
            {
                $conditions = new SHPS_sql_condition(
                        $conditions,
                        SHPS_SQL_RELATION_AND,
                        $a
                        );
            }
            else
            {
                $conditions = $a;
            }
        }
        
        $a = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_condition(
                                new SHPS_sql_condition(
                                        new SHPS_sql_colspec($stbl,'langID'),
                                        SHPS_SQL_RELATION_EQUAL,
                                        new SHPS_sql_colspec($ltbl,'ID')
                                        ),
                                SHPS_SQL_RELATION_AND,
                                new SHPS_sql_condition(
                                        new SHPS_sql_colspec($stbl,'group'),
                                        SHPS_SQL_RELATION_EQUAL,
                                        new SHPS_sql_colspec($lgtbl,'ID')
                                        )
                                ),
                        SHPS_SQL_RELATION_AND,
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($lgtbl,'name'),
                                SHPS_SQL_RELATION_EQUAL,
                                $group
                                )
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($stbl,'key'),
                        SHPS_SQL_RELATION_EQUAL,
                        $key
                        )
                );
        
        if(isset($conditions))
        {
            $conditions = new SHPS_sql_condition(
                    $conditions,
                    SHPS_SQL_RELATION_AND,
                    $a
                    );
        }
        else
        {
            $conditions = $a;
        }
        
        $cols = array(
            new SHPS_sql_colspec($stbl,'value')
        );
        
        $sql->readTables($cols, $conditions);
        if(($row = $sql->fetchRow()))
        {
            $r = $row->getValue('value');
        }
        else
        {
            $r = $na;
        }

        $sql->free();
        return ascii2entities($r);
    }
}
