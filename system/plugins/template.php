<?php

/* Template File for Skellods Homepage System v3 Plugins */

/* Plugins are always classes */
class template extends SHPS_plugin
/* classname = filename */
{
    /**
     * Constructor with plugin info
     */
    function __construct()
    {
            /* Basic information about the plugin */
            $this->name = 'Template';                                           // Plugin name
            $this->author = 'Marco Alka';                                       // Developer name
            $this->version = '1.0';						// Version info - handy if you want to let the system's update script handle updates for you!
            $this->homepage = 'http://skellods.de';                             // URL to your projct homepage or normal homepage; else insert bbs link to your project
            $this->GUID = 'a2b21112-e803-4aae-9d0c-292e93dc15bd';               // Globally Unique IDentifier. Is set by the SHPS Team
    }


    /* You do not need to copy all of the events. Only use the ones you need. The system will know if the function exists or not ;) */
    
    /**
     * Event called before CSS file is generated
     * 
     * @param mixed $param unused
     * @param mixed $queue unused
     * @return boolean
     */
    public function onBeforeMakeCSS(&$param = NULL, &$queue = '')
    {
        return true;
    }
    
    /**
     * Event called before registration
     * 
     * @param mixed $param Array over name, passwd, email, active and groups
     * @param mixed $queue
     * @return boolean Registration is cancelled if false is returned
     */
    public function onBeforeRegister(&$param = NULL, &$queue = '')
    {
        return true;
    }
    
    /**
     * Event called after successful login
     * 
     * @param array $param Contains all system information about the user from DB
     * @param mixed $queue Contains 'autoLogin' if the user was logged in automatically
     * @return boolean Return true if login should be successful
     */
    public function onLogin(&$param = NULL, &$queue = '')
    {
        return true;
    }
    
    /**
     * Event called before all session info is destroyed
     * 
     * @param mixed $param Unused
     * @param mixed $queue Unused
     * @return boolean
     */
    public function onLogout(&$param = NULL, &$queue = '')
    {
        return true;
    }
    
    /**
     * Event called after registration
     * 
     * @param mixed $param Array over name, passwd, email, active and groups
     * @param mixed $queue Contains ID of new user
     * @return boolean
     */
    public function onRegister(&$param = NULL, &$queue = '')
    {
        return true;
    }
    
    /**
     * Event called directly before sending
     * 
     * @param mixed $param file content
     * @param mixed $queue unused
     * @return boolean
     */
    public function onSendFile(&$param = NULL, &$queue = '')
    {
        return true;
    }
}
