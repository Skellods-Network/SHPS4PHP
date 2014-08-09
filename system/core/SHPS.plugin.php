<?php

/**
 * SHPS Plugin Engine<br>
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
 * PLUGIN ENGINE
 *
 * All functionalities in connection with plugin management are bundled in the
 * plugin engine class
 *
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 1.2
 */
class SHPS_pluginEngine
{

    /**
     * Contains all plugin objects
     * 
     * @var array Array of SHPS_plugin
     */
    private $plugins = array();
    
    /**
     * Singelton
     * 
     * @var array Array of SHPS_pluginEngine 
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
        $this->loadPlugins();
    }
    
    /**
     * Load plugins
     */
    private function loadPlugins()
    {
        $dir_plugin = SHPS_main::getDir('plugin');
        $cdir = opendir($dir_plugin);
        $f = readdir($cdir);
        while($f !== false)
        {
            if(is_file($dir_plugin . $f) && substr($f,-3) == 'php')
            {
                $this->addPlugin($dir_plugin . $f);
            }
            
            $f = readdir($cdir);
        }
        
        closedir($cdir);
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
     * Register a plugin with the system
     * 
     * @param string $plugin
     * @param boolean $forceActive //Default: false
     * @param boolean $faStatus ForceActive status (enabled or disabled?) //Default: true
     * @return boolean
     */
    public static function addPlugin($plugin, $forceActive = false, $faStatus = true)
    {
        $self = self::getInstance();
        
        $pok = false;
        if(is_file($plugin))
        {
            if(isset($self->plugins[basename($plugin,'.php')]))
            {
                SHPS_main::log('Plugin name ' . basename($plugin,'.php') . ' already exists!');
                return false;
            }
            
            include $plugin;
            SHPS_main::log('Attempting to load ' . $plugin);
            $dc = basename($plugin,'.php');
            $self->plugins[$dc] = new $dc();
            SHPS_main::log('[INFO]
                           Author: ' . $self->plugins[$dc]->getAttribute('author') .'
                           Specification: ' . $self->plugins[$dc]->getAttribute('name') .'
                           Version: ' . $self->plugins[$dc]->getAttribute('version') .'
                           Homepage: ' . $self->plugins[$dc]->getAttribute('homepage'));
            
            $pok = true;
        }
        elseif(is_object($plugin))
        {
            SHPS_main::log('Attempting to add plugin ' . $plugin->getAttribute('name'));
            if(isset($self->plugins[$plugin->getAttribute('name')]))
            {
                SHPS_main::log('Plugin name ' . $plugin->getAttribute('name') . ' already exists!');
                return false;
            }

            $self->plugins[$plugin->getAttribute('name')] = $plugin;
            $dc = $plugin->getAttribute('name');
            $pok = true;
        }

        if($pok)
        {
            if($forceActive)
            {
                    $self->plugins[$dc]->setEnabled($faStatus);
            }
            else
            {
                $sql = SHPS_sql::newSQL();
                $pluginTable = $sql->openTable('plugin');
        
                $cols = array(
                    new SHPS_sql_colspec($pluginTable,'status')
                    );
                
                $conditions = new SHPS_sql_condition(
                        new SHPS_sql_colspec($sql->openTable('plugin'),'plugin'),
                        SHPS_SQL_RELATION_EQUAL,
                        $dc
                        );
                
                $sql->readTables($cols, $conditions);
                if(($status = $sql->fetchRow()))
                {
                    $status = $status->getValue('status');
                    if($status == 3)
                    {
                        $self->plugins[$dc]->setEnabled(true);
                    }
                    else
                    {
                        $self->plugins[$dc]->setEnabled(false);
                    }
                }
                else
                {
                    $pluginTable->insert(array(
                        'plugin' => $dc,
                        'status' => 1
                    ));
                }

                $sql->free();
            }
        }
        else
        {
            SHPS_main::log('Plugin could not be loaded!');
        }
        
        return $pok;
    }

    /**
     * Handle direct plugin calls
     * 
     * @return boolean
     */
    public static function parseGET_Trigger()
    {
        $self = self::getInstance();
        
        $r = false;
        $p = getSG(INPUT_GET, 'plugin');
        if($p !== null)
        {
            $r = true;
            if(isset($self->plugins[$p]))
            {
                if($self->plugins[$p]->getAttribute('enabled'))
                {
                    if(method_exists($self->plugins[$p],'run'))
                    {
                        SHPS_main::setContent($self->plugins[$p]->run());
                    }
                    else
                    {
                        SHPS_main::setContent('<span style="color:red;"><b>' . $p . ' can\'t be executed!</b></span>');                       
                    }
                }
                else
                {
                    SHPS_main::setContent('<span style="color:red;"><b>' . $p . ' is disabled!</b></span>');
                }
            }
            else
            {
                SHPS_main::setContent('<span style="color:red;"><b>' . $p . ' is not installed!</b></span>');
            }
        }
        
        return $r;
    }

    /**
     * Activate a plugin
     * 
     * @param string $name
     * @return boolean
     */
    public function activate($name)
    {
        $self = self::getInstance();
        
        $result = true;
        $self->plugins[$name]->setEnabled(true);
        if(method_exists($self->plugins[$name],'onActivate'))
        {
            if(!$self->plugins[$name]->onActivate())
            {
                SHPS_main::log('[ERROR] PLUGIN "' . $self->plugins[$name]->getAttribute('name') . '" encountered an error @onActivate() !');
                $result = false;	
            }
        }
        
        return $result;
    }

    /**
     * Deactivate a plugin
     * 
     * @param string $name
     * @return boolean
     */
    public function deactivate($name)
    {
        $self = self::getInstance();
        
        $result = true;
        $self->plugins[$name]->setEnabled(false);
        if(method_exists($self->plugins[$name],'onDeactivate'))
        {
            if(!$self->plugins[$name]->onDeactivate())
            {
                SHPS_main::log('[ERROR] PLUGIN "' . $self->plugins[$name]->getAttribute('name') . '" encountered an error @onDeactivate() !');
                $result = false;	
            }
        }
        
        return $result;
    }

    /**
     * Call an event. If one function returns false, call ends
     * 
     * @param string $event
     * @param pstring $queue //Default: ''
     * @param pmixed $param //Default: null
     * @return boolean
     */
    public static function callEvent($event, &$queue = '', &$param = NULL)
    {
        foreach(self::getInstance()->plugins as $p)
        {
            if($p->getAttribute('enabled')
               && method_exists($p, $event))
            {
                if(!$p->$event($param,$queue))
                {
                    return false;
                }                    
            }
        }
        
        return true;
    }

    /**
     * Set content type to text or to binary
     * @param boolean $bool
     */
    public static function setBinarySwitch($bool)
    {
        foreach(self::getInstance()->plugins as $p)
        {
            $p->setBinaryOutput($bool);
        }
    }
    
    public static function getPluginObject($name)
    {
        return self::getInstance()->plugins[$name];
    }
}

/**
 * PLUGIN
 * 
 * This class will implement all important things a plugin needs
 *
 * @author Marco Alka <admin@skellods.de>
 * @version 1.1
 * 
 * 
 * TODO:
 * - Add (minimum) SHPS version; see Node.JS modules
 * - Add dependencies; see Node.JS modules
 */
class SHPS_plugin
{
    /**
     * Is this plugin enabled or not
     * 
     * @var boolean 
     */
    private $enabled = false;
    
    /**
     * This plugin's name
     * 
     * @var name
     */
    private $name = "";
    
    /**
     * This plugin's author
     * 
     * @var string
     */
    private $author = "";
    
    /**
     * This plugin's version<br>
     * Format is 'x.x.x' => Major:Minor:Patch
     * 
     * @var string
     */
    private $version = "1.0.0";	
    
    /**
     * This plugin's homepage
     * 
     * @var string
     */
    private $homepage = "";
    
    /**
     * Is the content to send to the browser binary?
     * 
     * @var boolean
     */
    private $binary = false;
    
    /**
     * Plugin's GUID
     * 
     * @var string
     */
    private $GUID = '';
    
    /**
     * Array of GUIDs of dependencies
     * 
     * @var array of string
     */
    private $dependencies = array();
    
    
    /**
     * Get plugin's GUID
     * 
     * @return string
     */
    public function getGUID()
    {
        return $this->GUID;
    }
    
    /**
     * Get plugin's dependencies
     * 
     * @return array of string
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }
    
    /**
     * Set enabled
     * 
     * @param boolean $enabled //Default: true
     */
    public function setEnabled($enabled = true)
    {
        if($this->enabled != $enabled)
        {
            $this->enabled = $enabled;
        }
    }
    
    /**
     * Get one of the plugin's attributes
     * 
     * @param string $attribute
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        if(isset($this->$attribute))
        {
            return $this->$attribute;
        }
        else
        {
            return;
        }
    }
    
    /**
     * Set content type
     * 
     * @param boolean $binary
     */
    public function setBinaryOutput($binary)
    {
        if($this->binary != $binary)
        {
            $this->binary = $binary;
        }
    }
}
