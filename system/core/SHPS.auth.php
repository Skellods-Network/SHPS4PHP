<?php

/**
 * SHPS User Authorization and Management<br>
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
require_once 'SHPS.scheduler.php';

// namespace \Skellods\SHPS;


define('SHPS_AUTH_HASH_MD5',0);
define('SHPS_AUTH_HASH_SHA256',1);
define('SHPS_AUTH_HASH_SHA512',2);
define('SHPS_AUTH_HASH_SHA3',3);
    

/**
 * AUTH
 *
 * All functionalities connected to user security and management are bundled
 * in the AUTH class.
 * 
 * @todo implement UAINFO handling
 * 
 * 
 * @package SHPS
 * @author Marco Alka <admin@skellods.de>
 * @version 1.4
 */
class SHPS_auth
{
    /**
     * Contains initialization status
     * 
     * @var boolean
     */
    private static $isInitialized = false;
    
    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        if(self::$isInitialized)
        {
            return;
        }
        
        self::endSession();        
        ini_set('session.gc-maxlifetime', SHPS_main::getHPConfig('General_Config', 'session_timeout'));
        session_name('SHPSSID');
        session_start();
        session_id(self::getSID());
        session_regenerate_id(true);
        $_COOKIE['SHPSSID'] = session_id();
        self::getBToken();

        /**
         * Delete expired security rules
         */
        SHPS_scheduler::addTask(function () {

            $sql = SHPS_sql::newSQL();
            $tbl = $sql->openTable('security');
            $tbl->delete(new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'to'),
                    SHPS_SQL_RELATION_SMALLER,
                    time()
                    ));
            
            $sql->free();			
            return 'Delete expired access keys [ok]';
        });
        
        self::$isInitialized = true;
    }
    
    /**
     * End session
     */
    private static function endSession()
    {
        if(function_exists('session_status'))
        {
            switch(session_status())
            {
                case PHP_SESSION_DISABLED:
                {
                    throw new SHPS_exception(SHPS_ERROR_AUTH_SESSION);
                }
                
                case PHP_SESSION_ACTIVE:
                {
                    session_unset();
                    session_destroy();
                    break;
                }
            }
        }
        else
        {
            if(session_is_active())
            {
                session_unset();
                session_destroy();
            }
        }
    }
    
    /**
     * DESTRUCTOR
     */
    function __destruct()
    {
        session_write_close();
    }

    public static function isValidSID($SID)
    {
        return preg_match('/^[a-zA-Z0-9]{26,40}$/', $SID);
    }
    
    /**
     * Get session ID from cookies or GET
     * 
     * @param string $key Key of variable which holds the SID
     * @return string|null
     */
    public static function getSID($key = 'SHPSSID')
    {
        $checkvalue = function($value){
            
            if(!SHPS_auth::isValidSID($value))
            {
                return null;
            }
            
            return $value;
        };
        
        $SIDfromPHP = function(){
            
            return session_id();
        };
        
        $SIDinGET = function() use ($key, $checkvalue, $SIDfromPHP) {
            
            return getSG(INPUT_GET, $key, $SIDfromPHP, $checkvalue);
        };
        
        $sid = getSG(INPUT_COOKIE, $key, $SIDinGET, $checkvalue);
        
        return $sid;
    }

    /**
     * Update password format
     * 
     * @param integer $uid
     * @param string $passwd
     * @return string|false
     */
    private static function updatePassword($uid, $passwd)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('user');
        
        $cols = array(
            new SHPS_sql_colspec($tbl, 'salt')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl, 'ID'),
                SHPS_SQL_RELATION_EQUAL,
                $uid
                );

        $sql->readTables($cols, $conditions);
        if(($row = $sql->fetchRow()))
        {
            $p = self::makeSecurePassword($passwd, $row->getValue('salt'));
            $cols = array(
                'pass' => $p
            );
            
            $tbl->update($cols, $conditions);
        }

        $sql->free();
        if(isset($p))
        {
            return $p;
        }
        
        return false;
    }

    /**
     * Make secure password from password
     * 
     * @param string $passwd
     * @param string $salt
     * @return string
     */
    private static function makeSecurePassword($passwd,$salt)
    {
        if(!($algo = SHPS_main::getHPConfig('Security_Config','Hash_Algo')))
        {
            $algo = -1;
        }

        if(!loadExtension('hash'))
        {
            $algo = SHPS_AUTH_HASH_MD5;
        }

        switch($algo)
        {                
            case SHPS_AUTH_HASH_MD5:
            {
                return md5(md5($salt) . $passwd);
            }

            case SHPS_AUTH_HASH_SHA256:
            {
                return hash('sha256', md5($salt) . $passwd);
            }

            /** @noinspection PhpMissingBreakStatementInspection */
            case SHPS_AUTH_HASH_SHA3:
            {
                if(function_exists('sha3')) /* strawbrary */
                {
                    return sha3(md5($salt) . $passwd);
                }
            }
            
            case SHPS_AUTH_HASH_SHA512:               
            default: /* SHA512 - will be changed to SHA3 asap */
            {
                return hash('sha512', md5($salt) . $passwd);
            }
        }
    }
    
    /**
     * Get browser token and make a new one if not existant
     * 
     * @return string
     */
    public static function getBToken()
    {
        self::init();
        
        $btoken = getSG(INPUT_COOKIE, 'SHPS_btoken');
        if($btoken === null)
        {
            $btoken = randomString(30);
            if(!SHPS_main::isContentSent())
            {
                SHPS_main::setCookie('SHPS_btoken', $btoken);
            }
        }
        
        if(self::isClientLoggedIn())
        {
            if($_SESSION['btoken'] != $btoken)
            {
                $_SESSION['btoken'] = $btoken;
                $sql = SHPS_sql::newSQL();
                $tbl = $sql->openTable('user');
                $tbl->update(array(
                    'btoken' => $btoken
                ), new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'ID'),
                        SHPS_SQL_RELATION_EQUAL,
                        $_SESSION['ID']
                        ));
                
                $sql->free();
            }
        }

        return $btoken;
    }

    /**
     * Check if password is correct
     * 
     * @param int $uid
     * @param string $passwd
     * @param NULL|string $validPasswd PW to test against //Default: null
     * @param NULL|string $validSalt Salt to test against //Default: null
     * @return boolean
     */
    private static function checkPassword($uid, $passwd, $validPasswd = NULL, $validSalt = NULL)
    {
        if($validPasswd == NULL || $validSalt == NULL)
        {
            $sql = SHPS_sql::newSQL();
            $tbl = $sql->openTable('user');
            $cols = array(
                new SHPS_sql_colspec($tbl,'salt'),
                new SHPS_sql_colspec($tbl,'pass')
            );
            
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'ID'),
                    SHPS_SQL_RELATION_EQUAL,
                    $uid
                    );
            
            $sql->readTables($cols, $conditions);
            if(!($row = $sql->fetchRow()))
            {
                $sql->free();
                return false;
            }
            
            $row = array(
                'salt' => $row->getValue('salt'),
                'pass' => $row->getValue('pass')
            );
            
            $sql->free();
        }
        else
        {
            $row = array(
                'salt' => $validSalt,
                'pass' => $validPasswd
            );
        }

        $h = loadExtension('hash');
        $r = false;
        if($row['pass'] == self::makeSecurePassword($passwd, $row['salt']))
        {
            $r = true;
        }
        elseif($h && $row['pass'] == hash('sha512', md5($row['salt']) . $passwd))
        {
            $r = true;
        }
        elseif($h && function_exists('sha3') && $row['pass'] == sha3(md5($row['salt']) . $passwd))
        {
            $r = true;
        }
        elseif($h && $row['pass'] == hash('sha256', md5($row['salt']) . $passwd))
        {
            $r = true;
        }
        elseif($h && $row['pass'] == md5(md5($row['salt']) . $passwd))
        {
            $r = true;
        }

        if($r)
        {
            self::updatePassword($uid, $passwd);                
        }

        return $r;
    }

    /**
     * Grant access key to a certain user
     * 
     * @param1: string|integer
     * @param2: string
     * @param3: integer
     * @param4: integer
     * @param5: boolean //Default: false
     * @result: boolean
     */
    public static function grantAccessKey($user,$key,$from,$to,$group = false)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('key');
        $cols = array(
            new SHPS_sql_colspec($tbl,'ID')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'accesskey'),
                SHPS_SQL_RELATION_EQUAL,
                $key
                );
        
        $sql->readTables($cols, $conditions);
        if(!($kid = $sql->fetchRow()))
        {
            $sql->free();
            return false;
        }

        $user = self::getIDFromUser($user);
        if($user === false)
        {
            $sql->free();
            return false;
        }
        
        if(!isset($_SESSION['ID']))
        {
            $authorizer = 0;
        }
        else
        {
            $authorizer = $_SESSION['ID'];
        }
        
        $r = $sql->openTable('security')->insert(array(
            'uid' => $user,
            'key' => $kid->getValue('ID'),
            'from' => $from,
            'to' => $to,
            'authorizer' => $authorizer,
            'isgroup' => $group
        ));
        
        $sql->free();

        return $r;
    }

    /**
     * Get user ID
     * 
     * @param string $name
     * @return integer
     */
    public static function getIDFromUser($name)
    {
        $sql = SHPS_sql::newSQL();
        if(!is_numeric($name))
        {
            $tbl = $sql->openTable('user');
            $cols = array(
                new SHPS_sql_colspec($tbl,'ID')
            );
            
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'user'),
                    SHPS_SQL_RELATION_EQUAL,
                    $name
                    );
            
            $sql->readTables($cols, $conditions);
            if(!($ID = $sql->fetchRow()))
            {
                $name = false;
            }
            else
            {
                $name = $ID->getValue('ID');
            }
        }
        else
        {
            $name = cleanInt($name);
        }

        $sql->free();
        return $name;
    }

    /**
     * Get user name
     * 
     * @param integer $id
     * @return string
     */
    public static function getUserFromID($id)
    {
        $sql = SHPS_sql::newSQL();
        if(is_numeric($id))
        {
            $tbl = $sql->openTable('user');
            $cols = array(
                new SHPS_sql_colspec($tbl,'user')
            );
            
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'ID'),
                    SHPS_SQL_RELATION_EQUAL,
                    $id
                    );
            
            $sql->readTables($cols, $conditions);
            if(!($ID = $sql->fetchRow()))
            {
                $name = false;
            }
            else
            {
                $name = $ID->getValue('user');
            }
        }
        else
        {
            $name = cleanStr($id);
        }

        $sql->free();
        return $name;
    }

    /**
     * Rstrict a user's rights
     * 
     * @param string|integer $user
     * @param string $key
     * @return boolean
     */
    public static function revokeAccessKey($user,$key)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('key');
        $cols = array(
            new SHPS_sql_colspec($tbl,'ID')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'accesskey'),
                SHPS_SQL_RELATION_EQUAL,
                $key
                );
        
        $sql->readTables($cols, $conditions);
        if(!($kid = $sql->fetchRow()))
        {
            $sql->free();
            return false;
        }

        $user = self::getIDFromUser($user);
        if($user === false)
        {
            $sql->free();
            return false;
        }

        $tbl = $sql->openTable('security');
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'key'),
                        SHPS_SQL_RELATION_EQUAL,
                        $kid->getValue('ID')
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'uid'),
                        SHPS_SQL_RELATION_EQUAL,
                        $user
                        )
                );
        
        $r = $tbl->delete($conditions);
        $sql->free();
        return $r;            
    }

    /**
     * Checks if user has the requested key<br>
     * If no user is specified (NULL) then the current logged-in user is evaluated
     * 
     * @param string $key
     * @param string $user //Default: null
     * @return boolean
     */
    public static function hasAccessKey($key, $user = null)
    {
        self::init();
        
        if($key == 'SYS_NULL')
        {
            return true;
        }

        if($user === null || $user == '')
        {
            if(!self::isClientLoggedIn() || !issetSG(INPUT_SESSION, 'user'))
            {
                return false;
            }
            
            $user = getSG(INPUT_SESSION, 'user');
        }

        if(!issetSG(INPUT_SESSION, 'ID'))
        {
            $uid = self::getIDFromUser($user);
            if (!$uid)
            {
                return false;
            }
        }
        else
        {
            $uid = getSG(INPUT_SESSION,'ID');
        }

        $sql = SHPS_sql::newSQL();
        $tbl_sec = $sql->openTable('security');
        $tbl_key = $sql->openTable('key');
        $tbl_usr = $sql->openTable('user');
        $tbl_gur = $sql->openTable('group_user');
        $cols = array(
            new SHPS_sql_colspec($tbl_sec,'uid')
        );
        
        $c1 = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl_sec,'isgroup'),
                SHPS_SQL_RELATION_EQUAL,
                0
                );
        
        $c2 = new SHPS_sql_condition(
                $c1,
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl_sec,'uid'),
                        SHPS_SQL_RELATION_EQUAL,
                        $uid
                        )
                );
        
        $c01 = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl_sec,'key'),
                SHPS_SQL_RELATION_EQUAL,
                new SHPS_sql_colspec($tbl_key,'ID')                        
                );
        
        $c02 = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl_usr,'ID'),
                SHPS_SQL_RELATION_EQUAL,
                new SHPS_sql_colspec($tbl_gur,'uid')
                );
        
        $c03 = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl_gur,'gid'),
                SHPS_SQL_RELATION_EQUAL,
                new SHPS_sql_colspec($tbl_sec,'uid')
                );
        
        $c04 = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl_key,'accesskey'),
                SHPS_SQL_RELATION_EQUAL,
                $key
                );
        
        $c05 = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        $c01,
                        SHPS_SQL_RELATION_AND,
                        $c02
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        $c03,
                        SHPS_SQL_RELATION_AND,
                        $c04
                        )
                );
        
        $c10 = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl_sec,'isgroup'),
                        SHPS_SQL_RELATION_EQUAL,
                        1
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl_sec,'key'),
                        SHPS_SQL_RELATION_EQUAL,
                        new SHPS_sql_colspec($tbl_key,'ID')
                        )
                );
        
        $c20 = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl_key,'accesskey'),
                        SHPS_SQL_RELATION_EQUAL,
                        $key
                        ),
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl_usr,'ID'),
                        SHPS_SQL_RELATION_EQUAL,
                        $uid
                        )
                );
        
        $c30 = new SHPS_sql_condition(
                $c10,
                SHPS_SQL_RELATION_AND,
                $c20
                );
        
        $conditions = new SHPS_sql_condition(
                $c05,
                SHPS_SQL_RELATION_AND,
                new SHPS_sql_condition(
                        $c2,
                        SHPS_SQL_RELATION_OR,
                        $c30
                        )
                );
        
        $sql->readTables($cols, $conditions);
        if(($row = $sql->fetchRow()))
        {
            $sql->free();
            return true;
        }

        if(($row = $sql->fetchRow()))
        {
            $sql->free();
            return true;
        }

        $sql->free();
        return false;
    }

    /**
     * Set last IP (as task)
     */
    private static function setLastIP()
    {
        self::init();
        
        SHPS_scheduler::addTask(function () {
            
            if(!isset($_SESSION['ID']))
            {
                return 'No user is logged in!';
            }
            
            $sql = SHPS_sql::newSQL();
            $tbl = $sql->openTable('user');
            $conditions = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'ID'),
                    SHPS_SQL_RELATION_EQUAL,
                    $_SESSION['ID']
                    );

            $tbl->update(array(
                'lastIP' => SHPS_client::getIP(),
                'lastActive' => time(),
                'host' => SHPS_client::getHost()
            ), $conditions);
            
            $sql->free();
            return 'Update user IP [ok]';
            }, 'lastIP');
    }

    /**
     * Delay Bruteforce attacks
     * 
     * @param integer $uid
     */
    private static function delayBruteForce($uid)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('loginQuery');
        $tbl->delete(new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'time'),
                        SHPS_SQL_RELATION_LARGER,
                        time() + (int)SHPS_main::getHPConfig('Security_Config', 'max_login_delay')
                        ),
                SHPS_SQL_RELATION_OR,
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'time'),
                        SHPS_SQL_RELATION_SMALLER,
                        time()
                        )
                ));
        
        $sql->readTables(array(
            new SHPS_sql_colspec($tbl,'uid')
        ), new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'uid'),
                SHPS_SQL_RELATION_EQUAL,
                $uid
                ));
        
        $c = $sql->count();        
        $tts = (int)SHPS_main::getHPConfig('Security_Config', 'login_delay');
        $tbl->insert(array(
            'uid' => $uid,
            'time' => time() + $c * $tts
        ));
        
        $sql->free();
        sleep($c * $tts);
    }
    
    /**
     * Login a user
     * If no parameters are given, the system tries an auto-login
     * 
     * @param string $user Username or eMail //Default: ''
     * @param string $pass //Default: ''
     * @param boolean $autologin //Default: false
     * @return boolean
     * 
     * @todo autologin depends on location and network
     */
    public static function login($user = '', $pass = '', $autologin = false)
    {
        self::init();
        
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('user');
        $btbl = $sql->openTable('browserInfoCache');
        
        $al = getSG(INPUT_COOKIE, 'SHPS_autoLogin');
        $id = getSG(INPUT_COOKIE, 'SHPS_id');
        $token = getSG(INPUT_COOKIE, 'SHPS_token');
        if($al !== null && $id !== null && $token !== null)
        {
            if(evalBool($al))
            {
                $cols = array(
                    new SHPS_sql_colspec($btbl,'browser'),
                    new SHPS_sql_colspec($btbl,'os'),
                    new SHPS_sql_colspec($btbl,'osBit'),
                    new SHPS_sql_colspec($btbl,'osVersion')
                );
                
                $conditions = new SHPS_sql_condition(
                        new SHPS_sql_condition(
                                new SHPS_sql_colspec($tbl,'btoken'),
                                SHPS_SQL_RELATION_EQUAL,
                                new SHPS_sql_colspec($btbl,'btoken')
                                ),
                        SHPS_SQL_RELATION_AND,
                        new SHPS_sql_condition(
                                new SHPS_sql_condition(
                                        new SHPS_sql_colspec($tbl,'user'),
                                        SHPS_SQL_RELATION_EQUAL,
                                        $user
                                        ),
                                SHPS_SQL_RELATION_OR,
                                new SHPS_sql_condition(
                                        new SHPS_sql_colspec($tbl,'mail'),
                                        SHPS_SQL_RELATION_EQUAL,
                                        $user
                                        )
                                )
                        );
                
                $sql->readTables($cols, $conditions);
                if(!($row = $sql->fetchRow()))
                {
                    $sql->free();
                    return false;
                }

                $cols = $tbl->getAllColumnes();
                
                $conditions = new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'ID'),
                        SHPS_SQL_RELATION_EQUAL,
                        $id
                        );
                
                $sql->readTables($cols, $conditions);
                if(($row = $sql->fetchRow()))
                {
                    if($token == $row->getValue('token'))
                    {
                        $_SESSION += $row->asArray();
                        SHPS_main::setCookie('SHPS_token', $token, time()+60*60*24*30);
                        SHPS_main::setCookie('SHPS_id', $id, time()+60*60*24*30);
                        SHPS_main::setCookie('SHPS_autoLogin', '1', time()+60*60*24*30);
                        self::setLastIP();
                        $sql->free();
                        self::updatePassword(self::getIDFromUser($user), $pass);
                        $queue = 'autoLogin';
                        return SHPS_pluginEngine::callEvent('onLogin', $queue, $row->asArray());
                    }
                }
            }
        }

        if(empty($user) || empty($pass))
        {
            $sql->free();
            return false;
        }
        
        if(self::isClientLoggedIn())
        {
            self::logout();
        }

        $cols = $tbl->getAllColumnes();
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'user'),
                        SHPS_SQL_RELATION_EQUAL,
                        $user
                        ),
                SHPS_SQL_RELATION_OR,
                new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'email'),
                        SHPS_SQL_RELATION_EQUAL,
                        $user
                        )
                );

        $sql->readTables($cols, $conditions);
        if(($row = $sql->fetchRow()))
        {
            if(self::checkPassword($row->getValue('ID'), $pass, $row->getValue('pass'), $row->getValue('salt')))
            {
                $rd = $row->getRawData();
                $_SESSION += $rd[$tbl->getFullName()];
                self::setLastIP();
                if($autologin)
                {
                    $token = randomString(30);
                    $tbl->update(array(
                        'token' => $token
                    ), new SHPS_sql_condition(
                            new SHPS_sql_colspec($tbl,'ID'),
                            SHPS_SQL_RELATION_EQUAL,
                            $row->getValue('ID')
                            ));
                    
                    SHPS_main::setCookie('SHPS_token',$token,time()+60*60*24*30);
                    SHPS_main::setCookie('SHPS_id',$row->getValue('ID'),time()+60*60*24*30);
                    SHPS_main::setCookie('SHPS_autoLogin','1',time()+60*60*24*30);
                }

                $sql->free();
                self::updatePassword(self::getIDFromUser($user), $pass);
                $paramQuery = '';
                return SHPS_pluginEngine::callEvent('onLogin', $paramQuery, $rd[$tbl->getFullName()]);
            }
        }

        $sql->free();
        if(isset($row))
        {
            self::delayBruteForce($row->getValue('ID'));
        }
        else
        {
            self::delayBruteForce(0);
        }
        
        return false;
    }
    
    /**
     * Make instance
     */
    public static function init()
    {
        if(!isset($_SESSION))
        {
            new SHPS_auth();
        }
    }
    
    /**
     * Checks if current client is logged in
     * 
     * @return boolean
     */
    public static function isClientLoggedIn()
    {
        self::init();
        return isset($_SESSION['user']);
    }
    
    /**
     * Checks if specified user is logged in
     *
     * @throws SHPS_exception
     * @param mixed $user
     * @return boolean
     */
    public static function isLoggedIn($user)
    {
        $ttl = SHPS_main::getHPConfig('General_Config', 'session_timeout');
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('user');
        if(is_numeric($user))
        {
            $condition = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'ID'),
                    SHPS_SQL_RELATION_EQUAL,
                    $user
                    );
        }
        elseif(is_string($user))
        {
            $condition = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'user'),
                    SHPS_SQL_RELATION_EQUAL,
                    $user
                    );
        }
        else
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        $sql->readTables(array(
            new SHPS_sql_colspec($tbl,'lastActive')
        ), $condition);
        
        if(($row = $sql->fetchRow()))
        {
            if((int)$row->getValue('lastActive') + ($ttl * 1000) <= time())
            {
                $sql->free();
                return true;
            }
        }
        
        $sql->free();
        return false;
    }
    
    /**
     * Checks if specified user is active
     *
     * @throws SHPS_exception
     * @param mixed $user
     * @return boolean
     */
    public static function isActive($user)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('user');
        if(is_numeric($user))
        {
            $condition = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'ID'),
                    SHPS_SQL_RELATION_EQUAL,
                    $user
                    );
        }
        elseif(is_string($user))
        {
            $condition = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'user'),
                    SHPS_SQL_RELATION_EQUAL,
                    $user
                    );
        }
        else
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        $sql->readTables(array(
            new SHPS_sql_colspec($tbl,'active')
        ), $condition);
        
        if(($row = $sql->fetchRow()))
        {
            if((int)$row->getValue('active') != 0)
            {
                $sql->free();
                return true;
            }
        }
        
        $sql->free();
        return false;
    }

    /**
     * Activates user account
     * 
     * @param mixed $user
     * @throws SHPS_exception
     */
    public static function activate($user)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('user');
        if(is_numeric($user))
        {
            $condition = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'ID'),
                    SHPS_SQL_RELATION_EQUAL,
                    $user
                    );
        }
        elseif(is_string($user))
        {
            $condition = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'user'),
                    SHPS_SQL_RELATION_EQUAL,
                    $user
                    );
        }
        else
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        $tbl->update(array(
            'active' => 1
        ), $condition);

        $sql->free();
    }

    /**
     * Logout the current user
     */
    public static function logout()
    {
        self::init();
        
        SHPS_pluginEngine::callEvent('onLogout');
        
        SHPS_main::setCookie('SHPS_token','',1, true, true);
        SHPS_main::setCookie('SHPS_id','',1, true, true);
        SHPS_main::setCookie('SHPS_autoLogin','1',1, true, true);
        session_unset();
        session_destroy();
    }

    /**
     * Check if name already exists in system
     * 
     * @param string $name
     * @return boolean
     */
    public static function nameExists($name)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('user');

        $sql->readTables(array(
            new SHPS_sql_colspec($tbl,'ID')
        ), new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'user'),
                SHPS_SQL_RELATION_EQUAL,
                $name
                ));

        if(($row = $sql->fetchRow()))
        {
            $r = true;
        }
        else
        {
            $r = false;
        }

        $sql->free();
        return $r;
    }

    /**
     * Check if email already exists in the system
     * 
     * @param string $email
     * @return boolean
     */
    public static function mailExists($email)
    {
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('user');

        $sql->readTables(array(
            new SHPS_sql_colspec($tbl,'ID')
        ), new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'email'),
                SHPS_SQL_RELATION_EQUAL,
                $email
                ));

        if(($row = $sql->fetchRow()))
        {
            $r = true;
        }
        else
        {
            $r = false;
        }

        $sql->free();
        
        return $r;
    }

    /**
     * Register new user
     * 
     * @param string $name
     * @param string $passwd
     * @param string $email
     * @param boolean $active //Default: true
     * @param array $usergroups Array of strings //Default: []
     * @return integer User ID
     */
    public static function register($name,
                             $passwd,
                             $email,
                             $active = true,
                             $usergroups = array())
    {
        if(self::nameExists($name) || self::mailExists($email))
        {
            return;
        }
		
        $salt = randomString(8);
        $passwd = self::makeSecurePassword($passwd,$salt);
        $paramArray = array(
            'name'      => $name,
            'passwd'    => $passwd,
            'email'     => $email,
            'active'    => $active,
            'groups'    => $usergroups
        );
        
        $paramQueue = '';
        if(!SHPS_pluginEngine::callEvent('onBeforeRegister', $paramQueue,$paramArray))
        {
            return;
        }
        
        $sql = SHPS_sql::newSQL();
        $sql->openTable('user')->insert(array(
            'user'      => $name,
            'email'     => $email,
            'pass'      => $passwd,
            'salt'      => $salt,
            'host'      => SHPS_client::getHost(),
            'regdate'   => time(),
            'lastActive'=> time(),
            'ip'        => SHPS_client::getIP(),
            'active'    => $active,
            'token'     => randomString(30),
            'btoken'    => self::getBToken(),
            'xforward'  => SHPS_client::getXForward(),
            'uaInfo'    => 0
        ));

        $id = $sql->getLastID();
        $tbl = $sql->openTable('groupUser');
        $gtbl = $sql->openTable('group');
        $gidcs = new SHPS_sql_colspec($gtbl,'ID');
        $gncs = new SHPS_sql_colspec($gtbl,'name');
        foreach($usergroups as $g)
        {
            $sql->readTables(array(
                $gidcs
            ), new SHPS_sql_condition(
                    $gncs,
                    SHPS_SQL_RELATION_EQUAL,
                    $g
                    ));
            
            if(!($row = $sql->fetchRow()))
            {
                continue;
            }
            
            $tbl->insert(array(
                'uid'   => $id,
                'gid'   => $row->getValue('ID'),
                'role'  => 1
            ));
        }
        
        SHPS_pluginEngine::callEvent('onRegister', $id, $paramArray);
        
        return $id;
    }

    /**
     * Change Password
     * 
     * @param mixed $user
     * @param string $newPW
     */
    public static function changePW($user,$newPW)
    {
        $sql = SHPS_sql::newSQL();		
        $tbl = $sql->openTable('user');        
        $salt = randomString(8);
        $passwd = self::makeSecurePassword($newPW, $salt);
        if(is_numeric($user))
        {
            $condition = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'ID'),
                    SHPS_SQL_RELATION_EQUAL,
                    $user
                    );
        }
        elseif(is_string($user))
        {
            $condition = new SHPS_sql_condition(
                    new SHPS_sql_colspec($tbl,'user'),
                    SHPS_SQL_RELATION_EQUAL,
                    $user
                    );
        }
        else
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        $tbl->update(array(
            'pass' => $passwd,
            'salt' => $salt
        ), $condition);

        $sql->free();
    }
}
