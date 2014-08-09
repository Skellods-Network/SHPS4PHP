<?php

/**
 * SHPS Main<br>
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

define('SHPS_',1);
define('SHPS_MAJOR_VERSION',3);
define('SHPS_MINOR_VERSION',0);
define('SHPS_VERSION', SHPS_MAJOR_VERSION . '.' . SHPS_MINOR_VERSION);
define('SHPS_INTERNAL_NAME','BYAKUEI');


require_once 'SHPS.secure.php';
require_once 'SHPS.SFFM.php';
require_once 'SHPS.error.php';
require_once 'SHPS.sql.php';
require_once 'SHPS.scheduler.php';
require_once 'SHPS.auth.php';
//require_once 'SHPS.nosql.php';
require_once 'SHPS.plugin.php';
require_once 'SHPS.client.php';
require_once 'SHPS.request.php';
//require_once 'SHPS.updater.php';
//require_once 'SHPS.api.php';
require_once 'SHPS.lang.php';
require_once 'SHPS.io.php';
require_once 'SHPS.css.php';
require_once 'SHPS.js.php';
require_once 'SHPS.optimize.php';
require_once 'SHPS.componentLib.php';

    
SHPS_main::getInstance();
SHPS_scheduler::getInstance();
SHPS_client::getInstance();


/**
 * MAIN
 *
 * This class contains the most important functionalities of SHPS, namely the 
 * templating engine
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 3.0 U3
 */
class SHPS_main
{

    /**
     * Should the system output the log to the browser?
     * 
     * @var boolean
     */
    private static $debug = false;
    
    /**
     * Contains the log
     * 
     * @var string
     */
    private static $log = '### SHPS has been successfully loaded and is now beginning it\'s mission! ###';
    
    /**
     * Name of content site
     * 
     * @var string
     */
    private static $site = 'index';
    
    /**
     * Subdomain + Domain
     * 
     * @var string
     */
    private static $domain = '';
    
    /**
     * First level domain
     * 
     * @var string
     */
    private static $fldomain = '';
    
    /**
     * Directory
     * 
     * @var array Array of strings
     */
    private static $directories = array();
    
    /**
     * Site content
     * 
     * @var string
     */
    private static $site_content = '';
    
    /**
     * Abstract singletons
     * 
     * @var array Array of SHPS_main
     */
    private static $instances = array();
    
    /**
     * Start time
     * 
     * @var integer
     */
    private static $startTime = 0;
    
    /**
     * Template cache
     * 
     * @var array
     */
    private static $templates = array();
    
    /**
     * Has the content already been sent
     * 
     * @var boolean
     */
    private static $contentSent = false;
    
    /**
     * Configuration
     * 
     * @var array
     */
    private static $config;
    
    /**
     * Should stats be suppressed no matter what
     * @var boolean
     */
    private static $suppressStats = false;
    
    /**
     * Contains global namespace or false
     * 
     * @var mixed
     */
    private static $namespace = false;
    

    /**
     * CONSTRUCTOR
     * 
     * @param string $configfile Filepath to config file //Default: ''
     */
    public function __construct($configfile = '')
    {
	self::$startTime = microtime(true);
        
        header('Server: SHPS');
        $class = get_called_class();
        if(!empty(self::$instances[$class]))
        {
            throw new SHPS_exception(SHPS_ERROR_INSTANCE);
        }
        
        self::$instances[$class] = $this;        
        !defined('SHPS') AND define('SHPS', 1);
        
        self::$namespace = getSG(INPUT_GET, 'ns', self::$namespace);
	$url = parse_url(getSG(INPUT_SERVER, 'HTTP_HOST'));
	if(isset($url['host']))
        {
	    self::$domain = $url['host'];
        }
	elseif(isset($url['path']))
        {
	    self::$domain = $url['path'];
        }
	
	if(substr_count(self::$domain,'.') > 1)
        {
	    self::$fldomain = substr(self::$domain, (strpos(self::$domain, '.') + 1));
        }
	
        self::$directories['core'] = dirname(__FILE__);
	
        if(strtoupper(getOS()) == 'WINDOWS')
        {
            define('SHPS_DIRECTORY_SEPARATOR', '\\');
        }
        else
        {
            define('SHPS_DIRECTORY_SEPARATOR', '/');
        }
        
        if(substr(self::$directories['core'], -1) != DIRECTORY_SEPARATOR)
        {
	    self::$directories['core'] .= DIRECTORY_SEPARATOR;
        }
       
        self::$directories['system'] = realpath(substr(self::$directories['core'], 0, -5)) . DIRECTORY_SEPARATOR;
        self::$directories['root'] = realpath(substr(self::$directories['system'], 0, -7)) . DIRECTORY_SEPARATOR;
        self::$directories['plugin'] = realpath(self::$directories['system']  . 'plugins') . DIRECTORY_SEPARATOR;
        self::$directories['log'] = realpath(self::$directories['system']  . 'logs') . DIRECTORY_SEPARATOR;
        self::$directories['upload'] = realpath(self::$directories['root']  . 'uploads') . DIRECTORY_SEPARATOR;
        self::$directories['config'] = realpath(self::$directories['system']  . 'config') . DIRECTORY_SEPARATOR;
        self::$directories['pool'] = realpath(self::$directories['root']  . 'pool') . DIRECTORY_SEPARATOR;

        if(!empty($configfile))
        {
            self::$configfile = self::$directories['config'] . $configfile;
        }

        self::readConfig();
        
        date_default_timezone_set(self::getHPConfig('General_Config','timezone'));
        self::$site = self::getSite();
        
	self::includePHP();

	$plugin = SHPS_pluginEngine::getInstance();
        $plugin->callEvent('onStartup');
        
        if(SHPS_io::getInstance()->handleRequest())
        {
            exit('');
        }
        
        SHPS_request::handleRequest();
        SHPS_js::handleRequest();
        SHPS_css::handleRequest();
        if($plugin->parseGET_Trigger())
        {
            self::make();
            exit('');
        }
    }

    
    /**
     * Return singelton instance
     * 
     * @return SHPS_main
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
     * DESTRUCTOR
     */
    public function __destruct()
    {
        SHPS_scheduler::execute();

        SHPS_pluginEngine::callEvent('onShutdown');
	$sql = SHPS_sql::newSQL('logging');
        $tbl = $sql->openTable('log');
        $logC = self::getHPConfig('General_Config','log_count');
	if($logC > 0)
	{
            $cols = array(
                new SHPS_sql_colspec($tbl,'ID')
            );

            $sql->readTables($cols);
            $sqlC = $sql->count();
            if($sqlC >= $logC)
            {
                $sql->query('DELETE FROM HP_log ORDER BY ID LIMIT ' . ($sqlC - $logC + 1));
            }
        }

        $tbl->insert(array(
            'time' =>  time(),
            'entry' => self::$log
        ));
	
	if(!self::$suppressStats && self::getHPConfig('General_Config','display_stats'))
	{
	    echo '<!-- ';
            self::printStats();
            echo ' -->';
	    flush();
	}
    }

    
    /**
     * Suppress stats or not
     * 
     * @param boolean $onOff //Default: true
     */
    public static function suppressStats($onOff = true)
    {
        self::$suppressStats = $onOff;
    }

    /**
     * Includes the PHP functions from the DB
     */
    private static function includePHP()
    {
	$sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('php');
        $cols = array(
            new SHPS_sql_colspec($tbl,'head'),
            new SHPS_sql_colspec($tbl,'content')
        );
        
        $sql->readTables($cols); 
	while(($row = $sql->fetchRow()))
	{
	    eval($row->getValue('head') . '{' . $row->getValue('content') . '}');	
	}
        
	$sql->free();
    }

    /**
     * Set body content
     * 
     * @param string $content
     */
    public static function setSiteContent($content)
    {
	self::$site_content = $content;
    }
    
    public static function isContentSent()
    {
        return self::$contentSent;
    }

    /**
     * Get body content
     * 
     * @return string
     */
    public static function getSiteContent()
    {
	return self::$site_content;
    }

    /** 
     * Send page to client
     */
    public static function sendPage()
    {
	header('Content-Type: text/html; charset=utf-8');
        $b = strtolower(SHPS_client::getBrowser());
	if($b == 'internet explorer' || $b == 'ie')
        {
	    header('X-UA-Compatible: "IE=Edge,chrome=1" env=ie');
        }
	else
        {
	    header('X-UA-Compatible: chrome=1');
        }

	self::$site_content = preg_replace('/<head( .*?)?>/is', '
$0 <meta name="generator" content="Skellods Homepage System SHPS v3">
', self::$site_content);

        SHPS_auth::init();
	echo self::$site_content;
	flush();

        self::$contentSent = true;
	if(!self::$suppressStats && self::getHPConfig('General_Config','display_stats'))
	{
	    echo '<!-- ';
            self::printStats();
            echo ' -->';
            flush();
	}
    }
    
    /**
     * Print stats
     */
    public static function printStats()
    {
        $pu = -1;
        if(isEnabled('memory_get_peak_usage'))
        {
            $pu = memory_get_peak_usage(true) / 1024;
        }

        $stats = '

Execution Time:	             ' . (microtime(true) - self::$startTime * 1) . ' s
SQL Queries:                 ' . SHPS_sql::getQueryCount() . '
SQL Connections(Overall):    ' . SHPS_sql::getConnectionCount() . '
SQL Execution Time(Overall): ' . SHPS_sql::getQueryTime() . '
Memory Used:                 ' . $pu . ' KB

';
        self::log($stats);
        echo $stats;
        return $stats;
    }

    /** 
     * Get a non-core HP setting from the DB
     *
     * @param string $key Identifier for setting
     * @return string
     */
    public static function getHPSetting($key)
    {
	$sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('setting');
        $cols = array(
            new SHPS_sql_colspec($tbl,'value')
        );
        
        $conditions = new SHPS_sql_condition(
            new SHPS_sql_colspec($tbl,'setting'),
            SHPS_SQL_RELATION_EQUAL,
            $key
        );
        
        $sql->readTables($cols, $conditions);
	if(($row = $sql->fetchRow()))
        {
	    $r = $row->getValue('value');
            $sql->free();
            return $r;
        }
	else
        {
            $sql->free();
	    return;
        }
    }

    /**
     * Test system<br>
     * This Method is under construction<br>
     * It will always return true
     *
     * @return boolean
     */
    final public static function test()
    {
	return true;
    }

    /**
     * Output debug data
     *
     * @param string $string
     */
    public static function log($string)
    {
	$str = '(' . date('H-i-s', time()) . ') ' . $string;
        self::$log .= $str . SHPS_EOL;
	if(self::$debug || defined('DEBUG'))
	{
	    echo $str . '<br>' . SHPS_NL;
	}
    }

    /** 
     * Load page if available
     *
     * @param string|NULL Page // Default: NULL
     * @return bool
     */
    public static function loadPage($page = null)
    {
        self::getInstance();
        
	if(!isset($page) || $page === null)
        {
            $page = getSG(INPUT_GET, 'site', function(){
                
                return SHPS_main::getHPConfig('General_Config','index_content');
            });
        }

	if(!SHPS_pluginEngine::callEvent('onSiteChange', $page))
        {
            return;
        }
        
	self::$site = $page;

	self::log('Attempting to load ' . $page);
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('content');
        $nstbl = $sql->openTable('namespace');
        $cols = array(
            new SHPS_sql_colspec($tbl,'content'),
            new SHPS_sql_colspec($tbl,'eval')
        );
        
        if(self::$namespace != false)
        {
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_condition(
                            new SHPS_sql_colspec($tbl,'name'),
                            SHPS_SQL_RELATION_EQUAL,
                            $page
                            ),
                    SHPS_SQL_RELATION_AND,
                    new SHPS_sql_condition(
                            new SHPS_sql_condition(
                                    new SHPS_sql_colspec($tbl,'namespace'),
                                    SHPS_SQL_RELATION_EQUAL,
                                    new SHPS_sql_colspec($nstbl,'ID')
                                    ),
                            SHPS_SQL_RELATION_AND,
                            new SHPS_sql_condition(
                                    new SHPS_sql_colspec($nstbl,'name'),
                                    SHPS_SQL_RELATION_EQUAL,
                                    self::$namespace
                                    )
                            )
                    );
        }
        else
        {
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'name'),
                    SHPS_SQL_RELATION_EQUAL,
                    $page
                    );
        }

        $sql->readTables($cols, $conditions);

	if(($row = $sql->fetchRow()))
        {
            $c = $row->getValue('content', $tbl);
            if(evalBool($row->getValue('eval')))
            {
                $c = eval($c);
            }
            
	    self::$site_content = $c;
        }
	else
        {
	    self::$site_content = '<h1>ERROR: Requested site could not be found!</h1>';
        }
        
        $sql->free();
        SHPS_pluginEngine::callEvent('onContentChange', self::$site_content);	
	return true;
    }

    /**
     * Read config file
     * 
     * @throws SHPS_exception
     */
    private static function readConfig()
    {
        global $SHPS_config;
        $cf = '';
        
        $cdir = self::getDir('config');
        if(file_exists($cdir . 'config.json'))
        {
            $SHPS_config = json_decode(file_get_contents($cdir . 'config.json'), true);
        }
        elseif(file_exists($cdir . self::$domain . '.config.json'))
        {
            $SHPS_config =  json_decode(file_get_contents($cdir . self::$domain . '.config.json'), true);
        }
        elseif(file_exists($cdir . self::$fldomain . '.config.json'))
        {
            $SHPS_config =  json_decode(file_get_contents($cdir . self::$fldomain . '.config.json'), true);
        }

        if($SHPS_config === null)// PHP deprecated version
        {
            if(file_exists($cdir . 'config.php'))
            {
                require_once $cdir . 'config.php';
            }
            elseif(file_exists($cdir . self::$domain . '.config.php'))
            {
                require_once $cdir . self::$domain . '.config.php';
            }
            elseif(file_exists($cdir . self::$fldomain . '.config.php'))
            {
                require_once $cdir . self::$fldomain . '.config.php';
            }
        }
        
        if($SHPS_config === null)
        {
            throw new SHPS_exception(SHPS_ERROR_CONFIG_FILE);
        }

        self::transformHPConfig();
        
        SHPS_pluginEngine::callEvent('onReadConfig', $cf, $SHPS_config);
    }

    /**
     * Load a template and output it's lvl1 parsed content
     *
     * @param string $name 
     * @param string $namespace //Default: ''
     * @param mixed ... Arguments for Template
     * @return string
     * @todo namespace support!
     */
    public static function loadTemplate($name, $namespace = '' /* ,... */)
    {
        $numArgs = func_num_args();
        if(isset(self::$templates[$name]))
        {
            $r = self::$templates[$name];
            if($numArgs > 2)
            {
                $args = func_get_args();
                array_shift($args);
                array_shift($args);
                if(is_array($args[0]))
                {
                    $args = $args[0];
                }

                $r = self::insertTemplateArgs($r, $args);
            }
            
            return $r;
        }
        
	$sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('template');
        $nstbl = $sql->openTable('namespace');
        $cols = array(
            new SHPS_sql_colspec($tbl,'evaluate'),
            new SHPS_sql_colspec($tbl,'content'),
        );
        
        if($namespace != '')
        {
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_condition(
                            new SHPS_sql_condition(
                                    new SHPS_sql_colspec($nstbl,'name'),
                                    SHPS_SQL_RELATION_EQUAL,
                                    $namespace
                                    ),
                            SHPS_SQL_RELATION_AND,
                            new SHPS_sql_condition(
                                    new SHPS_sql_colspec($nstbl,'ID'),
                                    SHPS_SQL_RELATION_EQUAL,
                                    new SHPS_sql_colspec($tbl,'namespace')
                                    )
                            ),
                    SHPS_SQL_RELATION_AND,
                    new SHPS_sql_condition(
                            new SHPS_sql_colspec($tbl,'name'),
                            SHPS_SQL_RELATION_EQUAL,
                            $name
                    ));
        }
        else
        {
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'name'),
                    SHPS_SQL_RELATION_EQUAL,
                    $name
                    );
        }
        
        $sql->readTables($cols, $conditions);
	if(($row = $sql->fetchRow()))
        {
            $r = $row->getValue('content');
        }
        
        $sql->free();
	if(isset($r))
        {
            if($numArgs > 2)
            {
                $args = func_get_args();
                array_shift($args);
                array_shift($args);
                if(is_array($args[0]))
                {
                    $args = $args[0];
                }
                
                $r = self::insertTemplateArgs($r, $args);
            }
            
            if(evalBool($row->getValue('evaluate')))
            {
                $r = eval($r);
            }
            
	    if(SHPS_pluginEngine::callEvent('onLoadTemplate', $r))
            {
                if($numArgs <= 2)
                {
                    self::$templates[$name] = $r;
                }
            }
        }
	else
        {
            if($namespace != '')
            {
                $r = 'There is no template called ' . $namespace . ':' . $name . '!';
            }
            else
            {
                $r = 'There is no template called ' . $name . '!';
            }
            
            if($numArgs <= 2)
            {
                self::$templates[$name] = $r;
            }
        }
        
	return $r;
    }
    
    /**
     * Insert Arguments into Template
     * 
     * @param string $str
     * @param array $args Array of string
     * @return string
     */
    private static function insertTemplateArgs($str, $args)
    {
        $i = 0;

        foreach($args as $arg)
        {
            $str = str_replace('$' . $i, $arg, $str);
            $i++;
        }

        return $str;
    }

    /**
     * Set current site
     * @param string $site
     */
    public static function setCurrentSite($site)
    {
	if(self::$site != $site)
        {
	    self::$site = $site;
        }
    }

    /** 
     * Replaces all Template Variables with Content and Templates
     *
     * @param string HTM
     * @return string
     */
    public static function parseTemplateVars($str)
    {
        if(!SHPS_pluginEngine::callEvent('onBeforeParseTemplateVars', $str))
        {
            return $str;
        }

        do
        {
            $i = 0;
            $j = 0;
            $str = preg_replace_callback('/\{[ \t]*\$(\X+?)[ \t]*\}/u', function ($match)
                    use (&$j)
            {
                if($match[1] == 'body')
                {
                    $j++;
                    return $match[0];
                }
                
                $m = null;
                if(preg_match('/(.+):(.+)[ \t]*\((\X*)\)/u', $match[1], $m) === 1)
                {/* {$foo:bar(arg)} */
                    $args = explode(',', $m[3]);
                    return SHPS_main::loadTemplate($m[2], $m[1], $args);
                }
                elseif(preg_match('/(\X+):(\X+)/u', $match[1], $m) === 1)
                {/* {$foo:bar} */
                    return SHPS_main::loadTemplate($m[2], $m[1]);
                }
                elseif(preg_match('/(\X+)[ \t]*\((\X*)\)/u', $match[1], $m) === 1)
                {/* {$bar(arg)} */
                    $args = explode(',', $m[2]);
                    return SHPS_main::loadTemplate($m[1], '', $args);
                }
                else
                {/* {$bar} */
                    return SHPS_main::loadTemplate($match[1]);
                }
            }, $str, -1, $i);
        } while($i - $j > 0);

        SHPS_pluginEngine::callEvent('onAfterParseTemplateVars', $str);
	return $str;
    }

    /** 
     * Make Homepage from all parts
     *
     * @param string Which template to start with interpretation // Default: 'site'
     * @return string
     */
    public static function make($template2Start = 'site')
    {
	self::log('Starting to interpret the Homepage');
        SHPS_pluginEngine::callEvent('onBeforeMake', $template2Start);
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('template');
        $nstbl = $sql->openTable('namespace');
        $cols = array(
            new SHPS_sql_colspec($tbl,'content')
        );
        
        if(self::$namespace == false)
        {
            self::$namespace = 'default';
        }
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'name'),
                        SHPS_SQL_RELATION_EQUAL,
                        $template2Start
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($tbl,'namespace'),
                                SHPS_SQL_RELATION_EQUAL,
                                new SHPS_sql_colspec($nstbl,'ID')
                                ),
                        SHPS_SQL_RELATION_AND,
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($nstbl,'name'),
                                SHPS_SQL_RELATION_EQUAL,
                                self::$namespace
                                )
                        )
                );

        $sql->readTables($cols, $conditions);
        $body = '';
        if(($row = $sql->fetchRow()))
        {
            $body = self::parseTemplateVars($row->getValue('content'));
        }
        
	$replace = array(
	    '{$body}',
	    '</body>',
	    '</head>'
	);
        
	$replacements = array(
	    self::$site_content,
	    SHPS_js::getJSLink() . '</body>',
	    SHPS_css::getCSSLink() . '</head>'
	);
        
        $body = str_replace($replace, $replacements, $body);
        $body = self::parseTemplateVars($body);

        self::$site_content = $body;
	SHPS_optimize::optimize();
        SHPS_pluginEngine::callEvent('onAfterMake', $template2Start);
	return self::$site_content;
    }

    /**
     * Set a valid cookie
     * 
     * @param string $name
     * @param string $value
     * @param string $expire //Default: 0
     * @param string $secure //Default: true
     * @param string $httponly //Default: true
     */
    public static function setCookie($name, $value, $expire = 0, $secure = true, $httponly = true)
    {
	setCookie($name,
                $value,
                $expire,
                self::getHPConfig('General_Config','cookie_path'),
                self::getHPConfig('General_Config','cookie_domain'),
                $secure,
                $httponly
                );

        $_COOKIE[$name] = $value;
    }


    /**
     * Do tranformations on configuration to make it usable
     */
    private static function transformHPConfig()
    {
        global $SHPS_config;
        if(isset($SHPS_config['General_Config']['URL']))
        {
            if(substr($SHPS_config['General_Config']['URL'], -1) != '/')
            {
                $SHPS_config['General_Config']['URL'] .= '/';
            }
        }
        
        $i = 0;
	do
        {
	    $SHPS_config['General_Config']['file_rewrite'] = str_replace(
            array(
                '{$index}',
                '{$index_name}'
            ),
            array(
                $SHPS_config['General_Config']['index'],
                basename($SHPS_config['General_Config']['index'], '.php')),
            $SHPS_config['General_Config']['file_rewrite'],
            $i
            );
        }
	while($i != 0);

	do
        {
	    $SHPS_config['General_Config']['link_rewrite'] = str_replace(
            array(
                '{$index}',
		'{$index_name}'
            ),
            array(
		$SHPS_config['General_Config']['index'],
		basename($SHPS_config['General_Config']['index'], '.php')),
                $SHPS_config['General_Config']['link_rewrite'],
                $i
            );
        }
	while($i != 0);
    }
    
    /**
     * Get Configuration either from DB or from the file
     * 
     * @param string $group
     * @param string $key
     * 
     * @return mixed
     */
    public static function getHPConfig($group,$key)
    {
        global $SHPS_config;
        if(isset($SHPS_config))
        {
            if(isset($SHPS_config[$group][$key]))
            {
                return $SHPS_config[$group][$key];
            }
        }
        else
        {
            self::readConfig();

            if(isset($SHPS_config))
            {
                self::transformHPConfig();
            }
            
            if(isset($SHPS_config[$group][$key]))
            {
                return $SHPS_config[$group][$key];
            }
        }
        
        return;
    }
    
    /**
     * Set Debug behaviour<br>
     * If set to true, the system will output all debuginfo to the browser
     * 
     * @param Boolean $onOff //Default: true
     */
    public static function setDebug($onOff = true)
    {
        self::$debug = $onOff;
    }
    
    /**
     * Return Debug status
     * 
     * @return Boolean
     */
    public static function getDebug()
    {
        return self::$debug;
    }
    
    /**
     * Get directory path
     * 
     * @param string $var //Default: ''
     * @return string
     */
    public static function getDir($var = '')
    {
        if(isset(self::$directories[$var]))
        {
            return self::$directories[$var];
        }
        else
        {
            return;
        }
    }
    
    /**
     * Get full domain with subdomain
     * 
     * @return string
     */
    public static function getDomain()
    {
        return self::$domain;
    }
    
    /**
     * Get first level domain without subdomain
     * 
     * @return string
     */
    public static function getFLDomain()
    {
        return self::$fldomain;
    }
    
    /**
     * Get full version info
     * 
     * @return string
     */
    final public static function getFullVersion()
    {
        return SHPS_VERSION;
    }
    
    /**
     * Get major version
     * 
     * @return integer
     */
    final public static function getMajorVersion()
    {
        return SHPS_MAJOR_VERSION;
    }
    
    /**
     * Get minor version
     * 
     * @return integer
     */
    final public static function getMinorVersion()
    {
        return SHPS_MINOR_VERSION;
    }
    
    /**
     * Get internal name
     * 
     * @return string
     */
    final public static function getInternalName()
    {
        return SHPS_INTERNAL_NAME;
    }
    
    /**
     * Get name of current content site
     * 
     * @return string
     */
    public static function getSite()
    {
        $site = getSG(INPUT_GET, 'site');
        if($site === null)
        {
            $site = self::getHPConfig('General_Config', 'index_content');
        }
        
        return $site;
    }
    
    /**
     * Return current namespace or nothing
     * 
     * @return mixed
     */
    public static function getNamespace()
    {
        if(self::$namespace != false)
        {
            return self::$namespace;
        }
        
        return;
    }
}
