<?php

/**
 * SHPS Task Scheduler<br>
 * This file is part of the Skellods Homepage System. It must not be distributed
 * without the licence file or without this header text.
 * 
 * 
 * @author Marco Alka <admin@skellods.de>
 * @copyright (c) 2013, Marco Alka
 * @license privat_Licence.txt Privat Licence
 * @link http://skellods.de Skellods
 */


require_once 'SHPS.SFFM.php';
require_once 'SHPS.secure.php';

// namespace \Skellods\SHPS;


/**
 * SCHEDULER
 *
 * All functionalities in concern with scheduling tasks for optimal performance
 * are bundled in the scheduler class.
 *
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 1.2
 */
class SHPS_scheduler
{
    /**
     * Array of executable functions
     * 
     * @var array Array of functions
     */
    private static $functions = array();
    
    /**
     * Abstract singletons
     * 
     * @var array Array of SHPS_main
     */
    private static $instances = array();
    
    /**
     * Contains all identifiers so that no task is double
     * 
     * @var array
     */
    private static $identifiers = array();


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
     * DESTRUCTOR
     */
    public function __destruct()
    {
        self::execute();
    }
    
    /**
     * Return singelton instance
     * 
     * @return SHPS_scheduler
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
     * Add function to execute
     * 
     * @param Callable $task
     * @param mixed $identifier //Default: null
     * @throws SHPS_exception
     */
    public static function addTask($task, $identifier = null)
    {
        if(!isClosure($task))
        {
            throw new SHPS_exception(SHPS_ERROR_TASK_EXECUTABLE);
        }
        
        if($identifier !== null && isset(self::$identifiers[$identifier]))
        {
            return;
        }
        
        if($identifier !== null)
        {
            self::$identifiers[$identifier] = 1;
        }
        
        self::$functions[] = $task;
    }

    /**
     * Execute all Functions
     */
    public static function execute()
    {
        foreach(self::$functions as $f)
        {
            SHPS_main::log($f());
        }
    }	
}
