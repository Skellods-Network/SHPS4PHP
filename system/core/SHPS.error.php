<?php

/**
 * SHPS Error Handler<br>
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


define('SHPS_ERROR_UNKNOWN', 0);

define('SHPS_ERROR_INSTANCE', 10);
define('SHPS_ERROR_CLONE', 11);
define('SHPS_ERROR_CONFIG_FILE', 12);
define('SHPS_ERROR_NOT_IMPLEMENTED', 13);
define('SHPS_ERROR_PARAMETER', 14);
define('SHPS_ERROR_CALLING_CLASS', 15);

define('SHPS_ERROR_SQL_DATABASE_TYPE', 100);
define('SHPS_ERROR_SQL_CONNECTION', 101);
define('SHPS_ERROR_SQL_ALIAS', 102);
define('SHPS_ERROR_SQL_SANITIZER', 103);
define('SHPS_ERROR_SQL_COL_TYPE', 104);
define('SHPS_ERROR_SQL_CLASS', 105);
define('SHPS_ERROR_SQL_PDO_NOT_INSTALLED', 107);
define('SHPS_ERROR_SQL_UNKNOWN_OPERATION', 108);

define('SHPS_ERROR_PLUGIN_UNKNOWN', 200);

define('SHPS_ERROR_TASK_EXECUTABLE', 400);

define('SHPS_ERROR_AUTH_SESSION', 500);

define('SHPS_ERROR_SQL_MONGO_NOT_INSTALLED', 600);
define('SHPS_ERROR_NOSQL_CONNECTION',601);


/**
 * ERROR
 *
 * All functionalities handling errors will be bundled in the error class 
 * 
 * WARNING! This file is beta for a large part
 * .
 *
 * @author Marco Alka <admin@skellods.de>
 * @version 1.1
 */
class SHPS_error
{
    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        throw new SHPS_exception(SHPS_ERROR_NOT_IMPLEMENTED);
        
	ini_set('display_errors', 1);
	error_reporting(-1);
	set_error_handler(array('errorh', 'handleError'));
	set_exception_handler(array('errorh','handleException'));
    }
    
    /**
     * Handle error
     * 
     * @param integer $code
     * @param string $message
     * @param string $file
     * @param integer $line
     * @param string $context
     */
    public static function handleError($code, $message, $file, $line, $context)
    {
	$e = new ErrorException($message,$code,0,$file,$line);
	errorh::handleException($e);
	unset($e);
    }
    
    /**
     * Handle exception
     * 
     * @param string $e
     */
    public static function handleException($e)
    {
	exit($e);
    }
}

/**
 * Exception extension
 */
class SHPS_exception extends Exception
{
    /**
     *
     * @var array Holds all error messages
     */
    private static $errorMessage = array(
        
        0 => 'UNKNOWN',
        /** 1..9 reserved for future use */
        
        /** 10..99 reserved for main system */
        10 => 'Creating more than one instance is prohibited!',
        11 => 'Singletons must not be cloned!',
        12 => 'No Config File found!',
        13 => 'Feature not implemented yet!',
        14 => 'Invalid Parameter Type!',
        15 => 'Invalid calling class!',
        
        /** 100..199 reserved for SQL */
        100 => 'Invalid Database Type!',
        101 => 'Could not connect to Database!',
        102 => 'Alias not defined!',
        103 => 'Fatal Sanitizer Error!',
        104 => 'Invalid Columne Type!',
        105 => 'Invalid Class as Parameter!',
        107 => 'PDO not installed!',
        108 => 'No SQL operation has been selected; please use get() oder set()!',
        
        /** 200..299 reserved for Plugin System */
        200 => 'Unknown Plugin Error!',
        
        /** 300..399 reserved for IO */
        
        /** 400..499 reserved for Tasks */
        400 => 'Only executable functions can be accepted as tasks!',
        
        /** 500..599 reserved for Auth */
        500 => 'Sessions is not installed!',
        
        /** 600..699 reserved fot NoSQL */
        600 => 'PHP Mongo plugin not installed!',
        601 => 'Could not connect to the NoSQL server or database'
        
        /** 100000 and up are reserved for plugins */
    );
    
    /**
     * CONSTRUCTOR
     * 
     * @param integer $code
     * @param Exception $previous //Default: null
     * @param string|null $messageOverride //Default: null
     */
    public function __construct($code, $previous = null, $messageOverride = null)
    {
        if($messageOverride !== null)
        {
            $message = $messageOverride;
            $c = $code;
        }
        else
        {
            $message = self::$errorMessage[$code];
            $c = 471100000 + $code;
        }
        
        parent::__construct($message, $c, $previous);
    }
}
