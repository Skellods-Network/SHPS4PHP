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


require_once 'SHPS.sql.php';
require_once 'SHPS.secure.php';
require_once 'SHPS.SFFM.php';
require_once 'SHPS.scheduler.php';

namespace SHPS {


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
     * @version 1.5
     * 
     * Changelog:
     * - objectized return values
     * - implemented custom session handler
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
            self::getToken();

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
            $sql = \SHPS\sql::newSQL();
            $tbl = $sql->openTable('user');
            $sql->query()
                    ->get($tbl->col('ID'))
                    ->fulfilling()
                    ->equal($tbl->col('ID'), $uid)
                    ->execute();

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
        public static function getToken()
        {
            self::init();

            $token = getSG(INPUT_COOKIE, 'SHPS_token');
            if($token === null)
            {
                $token = randomString(30);
                if(!SHPS_main::isContentSent())
                {
                    SHPS_main::setCookie('SHPS_token', $token);
                }
            }

            if(self::isClientLoggedIn())
            {
                if($_SESSION['token'] != $token)
                {
                    $_SESSION['token'] = $token;
                    $sql = \SHPS\sql::newSQL();
                    $tbl = $sql->openTable('user');
                    $sql->query()
                            ->alter($tbl, [
                                'token' => $token
                            ])
                            ->fulfilling()
                            ->equal($tbl->col('ID'), \getSG(INPUT_SESSION, 'ID', FILTER_SANITIZE_NUMBER_INT))
                            ->execute();

                    $sql->free();
                }
            }

            return $token;
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
                $sql = \SHPS\sql::newSQL();
                $tbl = $sql->openTable('user');
                $sql->query()
                        ->get([
                            $tbl->col('salt'),
                            $tbl->col('pass')
                        ])
                        ->fulfilling()
                        ->equal($tbl->col('ID'), $uid)
                        ->execute();
                
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
         * @param string|integer $user user or group
         * @param string $key
         * @param integer $from
         * @param integer $to
         * @param boolean $isGroup //Default: false
         * @result boolean
         */
        public static function grantAccessKey($user,$key,$from,$to,$isGroup = false)
        {
            $sql = \SHPS\sql::newSQL();
            $ak = self::getIDFromAccessKey($key);
            if (self::isClientLoggedIn())
            {
                $authorizer = \getSG(INPUT_SESSION, 'ID');
            }
            else
            {
                $authorizer = 0;
            }
            
            if ($isGroup)
            {
                $group = self::getIDFromGroup($user);
                $r = $sql->openTable('groupSecurity')->insert([
                            'gid' => $group,
                            'key' => $ak,
                            'from' => $from,
                            'to' => $to,
                            'authorizer' => $authorizer
                        ]);
            }
            else
            {
                $user = self::getIDFromUser($user);
                $r = $sql->openTable('userSecurity')->insert([
                            'uid' => $user,
                            'key' => $ak,
                            'from' => $from,
                            'to' => $to,
                            'authorizer' => $authorizer
                        ]);
            }

            $sql->free();
            return $r;  
        }
        
        /**
         * Fetch ID from table by comparing the value of a col against a reference
         * 
         * @param string $table
         * @param string $refCol
         * @param mixed $refColValue
         * @return integer|null
         */
        private static function getIDFromTable($table, $refCol, $refColValue)
        {
            $sql = \SHPS\sql::newSQL();
            if(!is_numeric($refColValue))
            {
                $tbl = $sql->openTable($table);
                $sql->query()
                        ->get($tbl->col('ID'))
                        ->fulfilling()
                        ->equal($tbl->col($refCol), $refColValue)
                        ->execute();

                if(!($ID = $sql->fetchRow()))
                {
                    $name = null;
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
         * Get access key ID
         * 
         * @param string $name
         * @return integer|null
         */
        public static function getIDFromAccessKey($name)
        {
            return self::getIDFromTable('accessKey', 'name', $name);
        }
        
        /**
         * Get group ID
         * 
         * @param string $name
         * @return integer|null
         */
        public static function getIDFromGroup($name)
        {
            return self::getIDFromTable('group', 'name', $name);
        }

        /**
         * Get user ID
         * 
         * @param string $name
         * @return integer|null
         */
        public static function getIDFromUser($name)
        {
            return self::getIDFromTable('user', 'user', $name);
        }

        /**
         * Get user name
         * 
         * @param integer $id
         * @return string
         */
        public static function getUserFromID($id)
        {
            $sql = \SHPS\sql::newSQL();
            if(is_numeric($id))
            {
                $tbl = $sql->openTable('user');
                $sql->query()
                        ->get($tbl->col('user'))
                        ->fulfilling()
                        ->equal($tbl->col('ID'), $id)
                        ->execute();

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
         * @param string|integer $user user or group
         * @param string $key
         * @param boolean $isGroup is group
         * @return boolean
         */
        public static function revokeAccessKey($user, $key, $isGroup = false)
        {
            $sql = \SHPS\sql::newSQL();
            $ak = $sql->openTable('accessKey');
            if ($isGroup)
            {
                $gs = $sql->openTable('groupSecurity');
                $tblG = $sql->openTable('group');
                $r = $sql->query()
                        ->delete($gs)
                        ->fulfilling()
                        ->equal($ak->col('ID'), $gs->col('key'))
                        ->equal($gs->col('gid'), $tblG->col('ID'))
                        ->equal($ak->col('name'), $key)
                        ->equal($tblG->col('name'), $user)
                        ->execute();
            }
            else
            {
                $gs = $sql->openTable('userSecurity');
                $tblG = $sql->openTable('user');
                $r = $sql->query()
                        ->delete($gs)
                        ->fulfilling()
                        ->equal($ak->col('ID'), $gs->col('key'))
                        ->equal($gs->col('uid'), $tblG->col('ID'))
                        ->equal($ak->col('name'), $key)
                        ->equal($tblG->col('user'), $user)
                        ->execute();
            }

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
            
            $sql = \SHPS\sql::newSQL();
            $ak = $sql->openTable('accessKey');
            $us = $sql->openTable('userSecurity');
            $time = time();
            $sql->query()
                    ->get($sql->openTable('user')->col('ID'))
                    ->fulfilling()
                    ->equal($us->col('key'), $sql->openTable('accessKey')->col('ID'))
                    ->equal($ak->col('name'), $key)
                    ->equal($sql->openTable('user')->col('ID'), $us->col('uid'))
                    ->equal($us->col('user'), $user)
                    ->greaterEqual($us->col('to'), $time)
                    ->smallerEqual($us->col('from'), $time)
                    ->execute();
            
            if (($row = $sql->fetchRow()))
            {
                $sql->free();
                return true;
            }
            
            $gu = $sql->openTable('groupUser');
            $gs = $sql->openTable('groupSecurity');
            $sql->query()
                    ->get($sql->openTable('group')->col('ID'))
                    ->fulfilling()
                    ->equal($sql->openTable('user')->col('user'), $user)
                    ->equal($gu->col('uid'), $sql->openTable('user')->col('ID'))
                    ->greaterEqual($gu->col('to'), $time)
                    ->smallerEqual($gu->col('from'), $time)
                    ->equal($gu->col('gid'),$sql->openTable('groupSecurity')->col('gid'))
                    ->equal($gs->col('key'), $sql->openTable('accessKey')->col('ID'))
                    ->equal($ak->col('name'), $key)
                    ->greaterEqual($gs->col('to'), $time)
                    ->smallerEqual($gs->col('from'), $time)
                    ->execute();
            
            if (($row = $sql->fetchRow()))
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

                $sql = \SHPS\sql::newSQL();
                $tbl = $sql->openTable('user');
                $sql->query()
                        ->alter($tbl, [
                            'lastIP' => \SHPS\client::getIP(),
                            'lastActive' => time(),
                            'host' => \SHPS\client::getHost()
                        ])
                        ->fulfilling()
                        ->equal($tbl->col('ID'), \getSG(INPUT_SESSION, 'ID', FILTER_SANITIZE_NUMBER_INT))
                        ->execute();

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
            $sql = \SHPS\sql::newSQL();
            $tbl = $sql->openTable('loginQuery');
            $sql->query()
                    ->get($tbl->col('time'))
                    ->fulfilling()
                    ->equal($tbl->col('uid'), $uid)
                    ->execute();
            
            if(($row = $sql->fetchRow()))
            {
                $ct = $row->getValue('time');
            }
            else
            {
                $ct = 0;
            }
            
            $ctt = $ct + (int)SHPS_main::getHPConfig('Security_Config', 'login_delay');
            
            $sql->query()
                    ->alter($tbl, [
                        'time' => $ctt
                    ])
                    ->fulfilling()
                    ->equal($tbl->col('uid'), $uid)
                    ->execute();
            
            $sql->free();
            sleep($ct);
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
                                    new SHPS_sql_colspec($tbl,'token'),
                                    SHPS_SQL_RELATION_EQUAL,
                                    new SHPS_sql_colspec($btbl,'token')
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
                'token'     => '',// @todo
                'btoken'    => self::getToken(),
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
}