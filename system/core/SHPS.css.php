<?php

/**
 * SHPS CSS Generator<br>
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
 * CSS
 *
 * All functionalities to get CSS rules from the database are bundled in the CSS
 * class
 * 
 *
 * @author Marco Alka <admin@skellods.de>
 * @version 1.3
 */
class SHPS_css
{
    /**
     * Files to include
     * 
     * @var array Array of string
     */
    private static $inc = array();

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
     * Get content of single rule
     * 
     * @param string $name
     * @param string|null $namespace //Default: null
     * @return string
     */
    public static function getRule($name, $namespace = null)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('css');
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
     * Create HTML5 valid JS include
     * 
     * @return string
     */
    public static function getCSSLink()
    {
        $chainingChar = '';
        $a = SHPS_CL::getRawURL($chainingChar);
        $a .= $chainingChar .'css=' . SHPS_main::getSite();
        return '<link rel="stylesheet" href="' . $a . '">';
    }
    
    /**
     * Handle request for CSS rules
     * 
     * @return void
     */
    public static function handleRequest()
    {
        if(!issetSG(INPUT_GET, 'css'))
        {
            return;
        }
        
        SHPS_main::suppressStats();
        header('Content-type: text/css');
        header('X-Content-Type-Options: nosniff');
        exit(self::makeCSSFile());
    }

    /**
     * Include CSS file
     * 
     * @param string $file
     */
    public static function includeFile($file)
    {
        self::$inc[] = $file;
    }
    
    /**
     * Get mediaquery ID from name
     * 
     * @param string $queryName
     * @param string|null $namespace //Default: null
     * @return integer
     * 
     * @todo implement namespace support
     */
    private static function getQueryID($queryName, $namespace = null)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('mediaquery');
        $cols = array(
            new SHPS_sql_colspec($tbl,'ID')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl, 'name'),
                SHPS_SQL_RELATION_EQUAL,
                $queryName
                );
        
        $sql->readTables($cols, $conditions);
        if(($row = $sql->fetchRow()))
        {
            $r = $row->getValue('ID');
        }
        else
        {
            $r = 0;
        }
        
        $sql->free();
        return $r;
    }

    /**
     * Build media query
     * 
     * @param mixed $queryID query ID or name
     * @param string|null $namespace //Default: null
     * @return string
     */
    private static function buildMediaQuery($queryID, $namespace = null)
    {
        if(is_string($queryID))
        {
            $queryID = self::getQueryID($queryID, $namespace);
        }
        
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('mediaquery');
        $cols = array(
            new SHPS_sql_colspec($tbl,'query')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'ID'),
                SHPS_SQL_RELATION_EQUAL,
                $queryID
                );
        
        $sql->readTables($cols, $conditions);
        if(!($rq = $sql->fetchRow()))
        {
            $sql->free();
            return;
        }
        
        $tbl = $sql->openTable('css');
        $cols = array(
            new SHPS_sql_colspec($tbl,'content')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'mediaquery'),
                SHPS_SQL_RELATION_EQUAL,
                $queryID
                );
        
        $sql->readTables($cols, $conditions);
        $css = '';
        while($row = $sql->fetchRow())
        {
            $css .= $row->getValue('content');
        }
        
        $sql->free();
        return $rq->getValue('query') . '{' . $css . '}';
    }

    /**
     * Create CSS rules
     * 
     * @return string
     */
    public static function makeCSSFile()
    {
        SHPS_pluginEngine::callEvent('onBeforeMakeCSS');
        
        $sql = SHPS_sql::newSQL();
        $csstbl = $sql->openTable('css');
        $mqtbl = $sql->openTable('mediaquery');
        $inctbl = $sql->openTable('include');
        $fttbl = $sql->openTable('fileType');
        $nstbl = $sql->openTable('namespace');
        
        $r = '';
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
                        'css'
                        )
                );
        
        $sql->readTables($cols, $conditions);
        while(($row = $sql->fetchRow()))
        {
            $r .= file_get_contents(SHPS_main::getDir('pool') . $row->getValue('file')) . ' ';
        }
        
        foreach(self::$inc as $inc)
        {
            if(file_exists(SHPS_main::getDir('pool') . $inc))
            {
                $r .= file_get_contents(SHPS_main::getDir('pool') . $inc);
            }
        }
        
        $cols = array(
            new SHPS_sql_colspec($csstbl,'name'),
            new SHPS_sql_colspec($csstbl,'content'),
            new SHPS_sql_colspec($csstbl,'evaluate')
        );
        
        if(!($namespace = SHPS_main::getNamespace()))
        {
            $namespace = 'default';
        }
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($nstbl,'ID'),
                        SHPS_SQL_RELATION_EQUAL,
                        new SHPS_sql_colspec($csstbl,'namespace')
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_condition(
                                new SHPS_sql_condition(
                                        new SHPS_sql_colspec($nstbl,'name'),
                                        SHPS_SQL_RELATION_EQUAL,
                                        $namespace
                                        ),
                                SHPS_SQL_RELATION_OR,
                                new SHPS_sql_condition(
                                        new SHPS_sql_colspec($csstbl,'namespace'),
                                        SHPS_SQL_RELATION_EQUAL,
                                        0
                                        )
                                ),
                        SHPS_SQL_RELATION_AND,
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($csstbl,'mediaquery'),
                                SHPS_SQL_RELATION_EQUAL,
                                0
                                )
                        )
                );
        
        $sql->readTables($cols, $conditions, new SHPS_sql_colspec($csstbl,'ID'),0,0, true, new SHPS_sql_grouping(array('ID')));
        
        while(($row = $sql->fetchRow()))
        {
            if($row->getValue('evaluate'))
            {
                $r .= $row->getValue('name') . '{' . eval($row->getValue('content')) . '} ';
            }
            else
            {
                $r .= $row->getValue('name') . '{' . $row->getValue('content') . '} ';
            }
        }
        
        $cols = array(
            new SHPS_sql_colspec($mqtbl,'ID')
        );
        
        $sql->readTables($cols);
        while(($row = $sql->fetchRow()))
        {
            if((integer)$row->getValue('ID') == 0)
            {
                continue;
            }
            
            $r .= self::buildMediaQuery((integer)$row->getValue('ID')) . ' ';
        }
        
        $sql->free();
        return SHPS_optimize::minifyCSS_OTF($r);
    }
}
