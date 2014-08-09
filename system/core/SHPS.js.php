<?php

/**
 * SHPS Javascript Generator<br>
 * This file is part of the Skellods Homepage System. It must not be distributed
 * without the licence file or without this header text.
 * 
 * 
 * @author Marco Alka <admin@skellods.de>
 * @copyright (c) 2013, Marco Alka
 * @license privat_Licence.txt Privat Licence
 * @link http://skellods.de Skellods
 */


// namespace \Skellods\SHPS;


/**
 * JS
 *
 * All functionalities to fetch JS from the database are bundled in the JS class
 * 
 *
 * @author Marco Alka <admin@skellods.de>
 * @version 1.3
 * 
 * @todo: make JS client counterpart
 * @todo: implement AsyncModules (see AMD)
 */
class SHPS_js
{
    /**
     * Files to include synchronously
     * 
     * @var array Array of string
     */
    private static $inc_sync = array();

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
     * Get content of an isolated piece of code
     * 
     * @param string $name
     * @param string|null $namespace //Default: null
     * @return string
     */
    public static function getSyncModule($name, $namespace = null)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('js');
        $cols = array(
            new SHPS_sql_colspec($tbl,'content'),
            new SHPS_sql_colspec($tbl,'evaluate')
        );
        
        if($namespace == null)
        {
            $namespace = 'default';
        }
        
        $nstbl = $sql->openTable('namespace');
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($nstbl,'ID'),
                        SHPS_SQL_RELATION_EQUAL,
                        new SHPS_sql_colspec($tbl,'namespace')
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($nstbl,'name'),
                                SHPS_SQL_RELATION_EQUAL,
                                $namespace
                                ),
                        SHPS_SQL_RELATION_AND,
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($tbl,'name'),
                                SHPS_SQL_RELATION_EQUAL,
                                $name
                                )
                        )
                );
        
        $sql->readTables($cols, $conditions);
        $r = '';
        if(($row = $sql->fetchRow()))
        {
            if(evalBool($row->getValue('evaluate')))
            {
                $r = eval($row->getValue('content'));
            }
            else
            {
                $r = $row->getValue('content');
            }
        }

        $sql->free();
        return $r;
    }

    /**
     * Include JS file as sync module
     * 
     * @param string $file
     */
    public static function includeFileAsSyncModule($file)
    {
        self::$inc_sync += $file;
    }
    
    /**
     * Create HTML5 valid JS include
     * 
     * @return string
     */
    public static function getJSLink()
    {
        $chainingChar = '';
        $a = SHPS_CL::getRawURL($chainingChar);
        $aCS = $a . $chainingChar .'cs=' . SHPS_main::getSite();
        $aJS = $a . $chainingChar .'js=' . SHPS_main::getSite();
        return '<script type="text/coffeescript" src="' . $aCS . '"></script>' .
               '<script type="text/javascript" src="' . $aJS . '"></script>';
    }
    
    /**
     * Handle JS requests
     * 
     * @return void
     * @todo Add 
     */
    public static function handleRequest()
    {
        if(issetSG(INPUT_GET, 'js'))
        {      
            SHPS_main::suppressStats();
            header('Content-type: text/javascript');
            header('X-Content-Type-Options: nosniff');
            exit(self::makeJSFile());
        }
        
        if(issetSG(INPUT_GET, 'cs'))
        {      
            SHPS_main::suppressStats();
            header('Content-type: text/coffeescript');
            header('X-Content-Type-Options: nosniff');
            exit(self::makeJSFile(true));
        }
        
        return;
    }

    /**
     * Get complete JS source
     * 
     * @param boolean $serveCoffee 
     * @return string
     */
    public static function makeJSFile($serveCoffee = false)
    {
        $sql = SHPS_sql::newSQL();
        if($serveCoffee)
        {
            $fType = 'cs';
        }
        else
        {
            $fType = 'js';
        }
        
        $jstbl = $sql->openTable($fType);
        $inctbl = $sql->openTable('include');
        $fttbl = $sql->openTable('fileType');
        $nstbl = $sql->openTable('namespace');
        
        $__r = '';
        $cols = array(
            new SHPS_sql_colspec($inctbl,'file')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($inctbl,'type'),
                        SHPS_SQL_RELATION_EQUAL,
                        new SHPS_sql_colspec($fttbl,'ID')
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($fttbl,'type'),
                        SHPS_SQL_RELATION_EQUAL,
                        $fType
                        )
                );
        
        $sql->readTables($cols, $conditions);
        while(($row = $sql->fetchRow()))
        {
            $__r .= file_get_contents(SHPS_main::getDir('pool') . $row->getValue('file')) . ' ';
        }
        
        foreach(self::$inc_sync as $inc)
        {
            if(file_exists(SHPS_main::getDir('pool') . $inc))
            {
                $__r .= file_get_contents(SHPS_main::getDir('pool') . $inc) . ' ';
            }
        }
        
        $cols = array(
            new SHPS_sql_colspec($jstbl,'content'),
            new SHPS_sql_colspec($jstbl,'evaluate')
        );
        
        if(!($namespace = SHPS_main::getNamespace()))
        {
            $namespace = 'default';
        }
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($nstbl,'ID'),
                        SHPS_SQL_RELATION_EQUAL,
                        new SHPS_sql_colspec($jstbl,'namespace')
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($nstbl,'name'),
                                SHPS_SQL_RELATION_EQUAL,
                                $namespace
                                ),
                                SHPS_SQL_RELATION_OR,
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($jstbl,'namespace'),
                                SHPS_SQL_RELATION_EQUAL,
                                0
                                )
                        )
                );
        
        $sql->readTables($cols, $conditions, new SHPS_sql_colspec($jstbl,'ID'),0,0,true,new SHPS_sql_grouping(array('ID')));        
        while(($row = $sql->fetchRow()))
        {
            if(evalBool($row->getValue('evaluate')))
            {
                $__r .= eval($row->getValue('content'));
            }
            else
            {
                $__r .= $row->getValue('content');
            }
            
            $__r .= ' ';
        }
        
        $sql->free();
        
        if($serveCoffee)
        {
            return $__r;
        }
        
        return SHPS_optimize::minifyJS_OTF($__r);
    }
}
