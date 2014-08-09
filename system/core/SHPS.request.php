<?php

/**
 * SHPS Request Handler<br>
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
 * REQUEST
 *
 * All functionalities in concern with client requests are bundled in the
 * request class.
 *
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 1.2
 */
class SHPS_request
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
     * Handle direct query requests (AJAX)
     */
    public static function handleRequest()
    {
        $req = getSG(INPUT_REQUEST, 'request');
        if($req === null)
        {
            return;
        }

        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('request');
        $cols = array(
            new SHPS_sql_colspec($tbl,'script'),
            new SHPS_sql_colspec($tbl,'accessKey')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'name'),
                SHPS_SQL_RELATION_EQUAL,
                $req
                );
        
        $sql->readTables($cols, $conditions);
        if(!($row = $sql->fetchRow()))
        {
            $sql->free();
            SHPS_main::setSiteContent(json_encode(array(
                'status' => 'error',
                'message' => 'Request not found!'
            )));
            
            SHPS_main::sendPage();
            exit(0);
        }
        
        if(!SHPS_auth::hasAccessKey($row->getValue('accessKey')))
        {
            $sql->free();
            SHPS_main::setSiteContent(json_encode(array(
                'status' => 'error',
                'message' => 'No permission for this operation!'
            )));
            
            SHPS_main::sendPage();
            exit(0);
        }
        
        $sql->free();
        SHPS_main::setSiteContent(json_encode(array(
            'status' => 'ok',
            'result' => eval($row->getValue('script'))
        )));
        
        SHPS_main::sendPage();
        exit(0);
    }	
}
