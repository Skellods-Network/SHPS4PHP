<?php

/**
 * SHPS Minimal Wrapper<br>
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


/* We are using SHPS - and everyone should know it! Also this is a safety measurement */
define('SHPS',1);


require_once 'system/core/SHPS.main.php';


/**
 * WRAPPER
 *
 * This is a small wrapper to make setting up the index file less confusing.
 *
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 1.1
 */
class SKELLODS_HP
{
    /**
     * SHPS main object
     * 
     * @access private
     * @var main
     */
    private $main = NULL;
    
    /**
     * Singleton instance object
     * 
     * @static Singleton
     * @access private
     * @var SKELLODS_HP 
     */
    private static $instance = null;


    /**
     * CONSTRUCTOR
     */
    function __construct()
    {
        /** Set singleton */
        if(self::$instance === null)
        {
            self::$instance = $this;
        }
        
        /** Get the SHPS instance */
        $this->main = SHPS_main::getInstance();
        
        /** Set debug behaviour */
        $this->main->setDebug(defined('DEBUG'));			

        /** Log absolute core dir */
        $this->main->log('Current working dir is ' . $this->main->getDir('core'));
        
        /** Initialize interpretation */
        $this->main->loadPage();			
    }
    
    /**
     * Return singelton instance of SKELLODS_HP object
     * 
     * @static
     * @return SKELLODS_HP
     */
    public static function getInstance()
    {
        /** Set singleton */
        if(self::$instance === null)
        {
            self::$instance = new self();
        }
        
        /** return instance */
        return self::$instance;
    }

    /**
     * Load page from DB
     * 
     * @param string $page Name of content page to load //Default: ''
     * @return bool
     */
    public function loadPage($page = '')
    {
        /** Initialize interpretation with a certain page */
        return $this->main->loadPage($page);
    }

    /**
     * Evaluate everything
     * 
     * @return bool
     */
    public function interpret()
    {
        /** Interpret the homepage */
        return $this->main->make();
    }

    /**
     * Send page to browser
     * 
     * @param bool $display_stats Should SHPS add stats to the bottom of the page //Default: false
     * @return bool
     */
    public function displayPage($display_stats = false)
    {
        /** Debug output */
        $this->main->log('[PAGE] Display it!');
        
        /** Send interpreted HTM to client and do other stuff */
        return $this->main->sendPage($display_stats);
    }

}
