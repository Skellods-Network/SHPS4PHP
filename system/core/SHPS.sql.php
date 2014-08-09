<?php

/**
 * SHPS SQL Abstraction<br>
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


define('SHPS_SQL_NULL',-1);
define('SHPS_SQL_MYSQL',0);
define('SHPS_SQL_MSSQL',10);
define('SHPS_SQL_POSTGRESQL',20);
    define('SHPS_SQL_POSTGRE',20);
define('SHPS_SQL_ORACLE',30);
define('SHPS_SQL_SQLITE',40);

define('SHPS_SQL_RELATION_EQUAL', '=');
define('SHPS_SQL_RELATION_NOT_EQUAL', '!=');
define('SHPS_SQL_RELATION_LIKE', 'LIKE');
define('SHPS_SQL_RELATION_LARGER', '>');
define('SHPS_SQL_RELATION_LARGER_EQUAL', '>=');
define('SHPS_SQL_RELATION_SMALLER', '<');
define('SHPS_SQL_RELATION_SMALLER_EQUAL', '<=');
define('SHPS_SQL_RELATION_BETWEEN', 'BETWEEN');
define('SHPS_SQL_RELATION_IN', 'IN');

define('SHPS_SQL_RELATION_AND', 'AND');
define('SHPS_SQL_RELATION_OR', 'OR');

define('SHPS_SQL_NO_TABLE','!SQL');
    
    
/**
 * SQL
 *
 * All functionalities to connect to SQL databases are bundled in the SQL class.
 * 
 *
 * @author Marco Alka <admin@skellods.de>
 * @version 1.2
 * 
 * 
 * TODO:
 * - Add other DBs
 * - Add support for great amounts of data (prepared insert)
 * - Add NoSQL support
 */
class SHPS_sql 
{
    /**
     * Total count of SQL queries
     * 
     * @var integer
     */
    private static $queryCount = 0;
    
    /**
     * Total time of all SQL queries
     * 
     * @var integer
     */
    private static $queryTime = 0;
    
    /**
     * Time last query needed to complete
     * 
     * @var integer
     */
    private static $lastQueryTime = 0;
    
    /**
     * Database type
     * 
     * @var integer
     */
    private $dbType = SHPS_SQL_MYSQL;
    
    /**
     * Database host
     * 
     * @var string
     */
    private $host = 'localhost';
    
    /**
     * Database server port
     * 
     * @var integer
     */
    private $port = 3306;
    
    /**
     * Database name
     * 
     * @var string
     */
    private $db = '';
    
    /**
     * Table prefix
     * 
     * @var string
     */
    private $prefix = 'HP_';
    
    /**
     * Database user
     * 
     * @var string
     */
    private $user = null;
    
    /**
     * Database password
     * 
     * @var string
     */
    private $passwd = null;
    
    /**
     * PDO link
     * 
     * @var PDO
     */
    private $connection = null;
    
    /**
     * Connection status
     * 
     * @var boolean
     */
    private $free = false;
    
    /**
     * Containes the last executed query
     * 
     * @var string
     */
    private $lastQuery = '';
    
    /**
     * Containes the last query's statement
     * 
     * @var PDOStatement
     */
    private $statement = null;
    
    /**
     * Contains Table/Col info : [INDEX][table,columne]
     * 
     * @var array of array of strings
     */
    private $tblInfo = array();
    
    /**
     * Index of next row to fetch
     * 
     * @var type 
     */
    private $fetchIndex = 0;
    
    /**
     * Server Type
     * 
     * @var string
     */
    private $serverType = '';
    
    /**
     * Tables to include in current query
     * 
     * @var array of string
     */
    private $includeTable = array();
    
    
    /**
     * SQL string determinators
     * 
     * @var array
     */
    private $stringdeterminator = array(
	SHPS_SQL_MYSQL => '\'',
	SHPS_SQL_MSSQL => '\'',
	SHPS_SQL_ORACLE => '\''
    );
    
    /**
     * SQL variable determinators
     * 
     * @var array
     */
    private $variabledeterminator = array(
	SHPS_SQL_MYSQL => array('`','`'),
	SHPS_SQL_MSSQL => array('[',']')
    );
    
    /**
     * Alias Connections
     * 
     * @var array
     */
    private static $alias_connections = array();
    
    /**
     * Memcached object
     * 
     * @var memcached
     */
    private $memcached = null;
    
    /**
     * Condition Builder currently in use
     * 
     * @var SHPS_sql_conditionBuilder
     */
    private $conditionbuilder = null;
    
    
    /**
     * CONSTRUCTOR<br>
     * For SQLite, a new file will be created if the database file is missing
     * 
     * @param string $user
     * @param string $passwd
     * @param string $database
     * @param string $host
     * @param string $prefix
     * @param array $mcServers [[(Sting)'Host',(Integer)['Port']],[...]]
     */
    public function __construct($user,
                                $passwd,
                                $database,
                                $host = 'localhost',
                                $port = 3306,
                                $prefix = 'HP_',
                                $dbType = SHPS_SQL_MYSQL,
                                $mcServers = array())
    {
        if (!defined('PDO::ATTR_DRIVER_NAME'))
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_PDO_NOT_INSTALLED);
        }
        
        if(!($driver = self::getDriver($dbType)))
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_DATABASE_TYPE);
        }
        
        if($dbType == SHPS_SQL_SQLITE)
        {
            $h = '';
            $p = '';
        }
        else
        {
            $h = ';host=' . $host;
            $p = ';port=' . $port;
        }
        
        $dns = $driver . ':dbname=' . $database . $h . $p;        
        $options = array();
        switch($dbType)
        {
            case SHPS_SQL_MYSQL:
            {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES \'UTF8\';';
                break;
            }
        }
        
        try
        {
            $this->connection = new PDO($dns,$user,$passwd,$options);
        }
        catch(PDOException $pdoE)
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_CONNECTION, $pdoE);
        }
        
        $this->db = $database;
        $this->dbType = $dbType;
        $this->host = $host;
        $this->port = $port;
        $this->passwd = $passwd;
        $this->prefix = $prefix;
        $this->user = $user;
        
        if (is_string($this->dbType))
        {
            $this->dbType = constant($this->dbType);
        }
        
        if (count($mcServers) > 0 && class_exists('Memcached'))
        {
            $this->memcached = new Memcached();
            foreach($mcServers as $mc)
            {
                $this->memcached->addServer($mc['Host'], $mc['Port']);
            }
        }
    }
    
    /**
     * Create new managed SQL connection from alias (see config file)
     * 
     * @param string $alias //Default: 'default'
     * @return SHPS_sql
     */
    public static function newSQL($alias = 'default')
    {
        if(!isset(self::$alias_connections[$alias]))
        {
            self::$alias_connections[$alias] = array();
        }
        
        $c = SHPS_main::getHPConfig('Database_Config', $alias);
        if($c === null || !is_array($c))
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_ALIAS);
        }
        
        foreach(self::$alias_connections[$alias] as $ac)
        {
            if($ac->isFree())
            {
                return $ac;
            }
        }

        self::$alias_connections[$alias][] = new static($c['DB_User'],
                                                        $c['DB_Pass'],
                                                        $c['DB_Name'],
                                                        $c['DB_Host'],
                                                        $c['DB_Port'],
                                                        $c['DB_Pre'],
                                                        $c['DB_Type']);
        
        return end(self::$alias_connections[$alias]);
    }

    /**
     * Cleans integer
     * @deprecated since version 3.0 U2 Use ::cleanInt() instead
     * 
     * @param mixed $int
     * @return integer
     */
    public static function cleanInt($int)
    {
        return cleanInt($int);
    }
    
    /**
     * Cleans strings
     * @deprecated since version 3.0 U2 Use ::cleanStr() instead
     * 
     * @param string $str
     * @return string
     */
    public function cleanStr($str)
    {
        return cleanStr($str);
    }
    
    /**
     * Standardizes names in a SQL query by adding determinators
     * 
     * @param string $var
     * @return string
     */
    public function standardizeName($var)
    {
        $s = $this->variabledeterminator[$this->dbType][0];
        $e = $this->variabledeterminator[$this->dbType][1];
	if($var != '*'
           && substr($var,0,1) != $s
           && substr($var,-1) != $e)
        {
	    $var = $s . $this->cleanStr($var) . $e;
        }
        
	return $var;
    }
    
    /**
     * Standardizes strings in a SQL query by adding determinators
     * 
     * @param string $str
     * @return string
     */
    public function standardizeString($str)
    {
        $str = $this->cleanStr($str);
        $s = $this->stringdeterminator[$this->dbType];
        if(substr($str,0,1) != $s
           && substr($str,-1) != $s)
        {
            $str = $s . $str . $s;
        }
        
        return $str;
    }

    /**
     * Get query count
     * 
     * @return integer
     */
    public static function getQueryCount()
    {
        return self::$queryCount;
    }
    
    /**
     * Get overall query time
     * 
     * @return integer
     */
    public static function getQueryTime()
    {
        return self::$queryTime;
    }
    
    /**
     * Get time the last query needed to complete
     * 
     * @return integer
     */
    public static function getLastQueryTime()
    {
        return self::$lastQueryTime;
    }
    
    /**
     * Get connection count
     * 
     * @return integer
     */
    public static function getConnectionCount()
    {
        return count(self::$alias_connections, COUNT_NORMAL);
    }
    
    /**
     * Get driver name from type
     * PHP specific
     * 
     * @param integer $dbType
     * @return string
     */
    private static function getDriver($dbType)
    {
        switch($dbType)
        {            
            case SHPS_SQL_MYSQL:
            {
                return 'mysql';
            }
            
            case SHPS_SQL_MSSQL:
            {
                return 'mssql';
            }
            
            case SHPS_SQL_ORACLE:
            {
                return 'oci';
            }
            
            case SHPS_SQL_POSTGRE:
            {
                return 'pgsql';
            }
            
            case SHPS_SQL_SQLITE:
            {
                return 'sqlite';
            }
            
            default:
            {
                return;
            }
        }
    }
    
    /**
     * Test connection to database
     * PHP specific
     * 
     * @param string $user
     * @param string $passwd
     * @param string $database
     * @param string $host //Default: 'localhost'
     * @param integer $port //Default: 3306
     * @param string $dbType //Default: SHPS_SQL_MYSQL
     * @return boolean
     */
    public static function testConnection($user,
                                          $passwd,
                                          $database,
                                          $host = 'localhost',
                                          $port = 3306,
                                          $dbType = SHPS_SQL_MYSQL)
    {
        if(!($driver = self::getDriver($dbType)))
        {
            return false;
        }
        
        if($dbType == SHPS_SQL_SQLITE && !file_exists($database))
        {
            return false;
        }
        
        if($dbType == SHPS_SQL_SQLITE)
        {
            $h = '';
            $p = '';
        }
        else
        {
            $h = ';host=' . $host;
            $p = ';port=' . $port;
        }
        
        $dns = $driver . ':dbname=' . $database . $h . $p;
        
        try
        {
            $pdo = new PDO($dns,$user,$passwd);
        }
        catch(PDOException $pdoE)
        {
            return false;
        }
        
        $pdo = null;
        unset($pdo);
        return true;
    }
    
    /**
     * Create a custom Table and return table object
     * 
     * @param string $name
     * @param array $cols Array of SHPS_sql_col
     * @param boolean $ifNotExists Throws error if table exists //Default: true
     * @param boolean $temp If true table is only temporary (in memory) //Default: false
     * @return SHPS_sql_table
     */
    public function createTable($name, $cols, $ifNotExists = true, $temp = false)
    {
        $testTbl = '';
        $tmpTbl = '';
        
        if($ifNotExists)
        {
            $testTbl = 'IF NOT EXISTS ';
        }
        
        if($temp)
        {
            $tmpTbl = ' TEMP';
        }
        
        $sCols = ' (';
        foreach($cols as $col)
        {
            if(!$col instanceof SHPS_sql_col)
            {
                throw new SHPS_exception(SHPS_ERROR_SQL_CLASS);
            }
            
            if($col->getLength() <= 0)
            {
                $cLength = '';
            }
            else
            {
                $cLength = '(' . $col->getLength() . ')';
            }
            
            $sCols .= $col->getName() .  ' ' . $this->getType($col->getType()) . '(' . $cLength . ')';
            if(!$col->isNULL())
            {
                $sCols .= ' NOT NULL';
            }
            
            if($col->isPrimaryKey())
            {
                if($col->isAutoIncremented())
                {
                    $sCols .= ' PRIMARY KEY AUTOINCREMENT';
                }
                else
                {
                    $sCols .= ' PRIMARY KEY';
                }
            }
            elseif($col->isUnique())
            {
                $sCols .= ' UNIQUE';
            }
            
            $sCols .= ',';
        }
            
        if(substr($sCols, -1) == ',')
        {
            $sCols = substr($sCols, 0, -1);
        }

        $options = '';
        switch($this->dbType)
        {
            case SHPS_SQL_MYSQL:
            {
                if($this->getServerType() == 'MariaDB')
                {
                    $options = ' ENGINE=Aria
                                 DEFAULT CHARSET=utf8mb4
                                 COLLATE=utf8mb4_unicode_ci
                                 PAGE_CHECKSUM=0
                                 TRANSACTIONAL=1';
                }
                else
                {
                    $options = ' ENGINE=MyISAM
                                 DEFAULT CHARSET=utf8
                                 COLLATE=utf8_unicode_ci';
                }

                break;
            }
        }

        $sCols .= ')';
        if($this->query('CREATE' . $tmpTbl . ' TABLE '
           . $testTbl . $this->standardizeName($name)
           . '(' . $sCols . ')'
           . $options . ';'))
        {
            return $this->openTable($name);
        }

        return null;
    }
    
    /**
     * Get Server Type
     * 
     * @return string
     */
    public function getServerType()
    {
        if($this->serverType == '')
        {
            switch($this->dbType)
            {
                case SHPS_SQL_MYSQL:
                {
                    $this->serverType = 'MySQL';
                    $this->query('SELECT VERSION();');
                    $v = $this->statement->fetch();
                    if(strpos($v['VERSION()'], 'MariaDB') !== false)
                    {
                        $this->serverType = 'MariaDB';
                    }
                }
            }
        }
        
        $this->free();
        return $this->serverType;
    }
    
    /**
     * Get table prefix
     * 
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    
    /**
     * Return table object
     * 
     * @param string $name
     * @return SHPS_sql_table
     */
    public function openTable($name)
    {
        return new SHPS_sql_table($this,$name);
    }
    
    /**
     * Read value from one or more joined tables
     * 
     * @param array $cols Array of SHPS_sql_colspec
     * @param mixed $conditions //Default: null
     * @param SHPS_sql_colspec $orderBy Order results by a columne //Default: null
     * @param integer $offset //Default: 0
     * @param integer $maxCount //Default: 0
     * @param boolean $distinct //Default: false
     * @param SHPS_sql_group $grouping //Default: null
     * @return boolean
     */
    public function readTables($cols,
                               $conditions = null,
                               $orderBy = null,
                               $offset = 0,
                               $maxCount = 0,
                               $distinct = false,
                               $grouping = null)
    {
        $this->tblInfo = array();
        $sDist = '';
        if($distinct)
        {
            $sDist = 'DISTINCT ';
        }
        
        $sCols = array();
        $tables = array();
        foreach($cols as $col)
        {
            if(!$col instanceof SHPS_sql_colspec)
            {
                throw new SHPS_exception(SHPS_ERROR_SQL_CLASS);
            }
            
            $this->tblInfo[] = array('table' => $col->getTable()->getFullName(), 'columne' => $col->getColName());
            $s = $col->getTable()->getAbsoluteName() . '.' . $this->standardizeName($col->getColName());
            if(!in_array($s, $sCols))
            {
                $sCols[] = $s;
            }
            
            $s = $col->getTable()->getAbsoluteName();
            if(!in_array($s, $tables))
            {
                $tables[] = $s;
            }
        }

        if((!($conditions instanceof SHPS_sql_condition || $conditions instanceof SHPS_sql_conditionBuilder) && !$conditions === null)
          || ($orderBy !== null && !$orderBy instanceof SHPS_sql_colspec))
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_CLASS);
        }
        
        if($conditions !== null)
        {
            $sC = (string)$conditions;
            foreach($this->includeTable as $it)
            {
                if(!in_array($it, $tables))
                {
                    $tables[] = $it;
                }
            }
        }
        else
        {
            $sC = '';
        }
        
        if(!empty($sC))
        {
            $sC = ' WHERE ' . $sC;
        }
        
        $sGroup = '';
        if ($grouping !== null
          && $grouping instanceof SHPS_sql_group)
        {
            $sGroup = ' GROUP BY ';
            foreach($grouping->getGroupBy() as $g)
            {
                $sGroup .= $this->standardizeName($g) . ',';
            }
            
            if(substr($sGroup, -1) == ',')
            {
                $sGroup = substr($sGroup, 0, -1);
            }
            
            if($sGroup->getHaving() != '')
            {
                $sGroup .= ' HAVING ' . $sGroup->getHaving();
            }
        }
        
        $ob = '';
        if($orderBy !== null)
        {
            $ob = ' ORDER BY ' . (string)$orderBy . ' ';
        }
        
        $limit = '';
        if($maxCount > 0)
        {
            $limit = ' LIMIT ' . $this->cleanInt($maxCount);
        }
        
        $os = '';
        if($offset > 0)
        {
            $os = ' OFFSET ' . $this->cleanInt($offset);
        }
        
        $query = 'SELECT ' . $sDist . implode(',', $sCols) . ' FROM '
                 . implode(',', $tables) . $sC . $ob . $sGroup . $limit . $os
                 . ';';
        
        if ($this->memcached !== null)
        {
            $cr = $this->memcached->get(hash('crc32b',$query));
            // unserialize memcached string
        }
        else
        {
            $r = $this->query($query);
        }
        
        return $r;
    }
    
    /**
     * Return an object to create conditions with
     * 
     * @return \SHPS_sql_conditionBuilder
     */
    public function makeConditions()
    {
        $this->conditionbuilder = new SHPS_sql_conditionBuilder();
        return $this->conditionbuilder;
    }
    
    /**
     * Return an object to create a query with
     * 
     * @return \SHPS_sql_queryBuilder
     */
    public function makeQuery()
    {
        return new SHPS_sql_queryBuilder($this);
    }
    
    /**
     * Return current SQL Query as string
     * 
     * @return string
     */
    public function getQuery()
    {
        return $this->lastQuery;
    }
    
    /**
     * Include table for next query
     * 
     * @param mixed $table SHPS_table or string
     * @throws SHPS_exception
     */
    public function includeTable($table)
    {
        if(!$table instanceof SHPS_sql_table && !is_string($table))
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        if(is_string($table))
        {
            $table = $this->openTable($table);
        }
        
        $an = $table->getAbsoluteName();
        if(!in_array($an, $this->includeTable))
        {
            $this->includeTable[] = $an;
        }
    }
    
    /**
     * Just run the given query<br>
     * It is not recommended to use custom queries!
     * 
     * @param string $query
     * @param array|null $param //Default: null
     * @return boolean
     */
    public function query($query, $param = null)
    {
        $this->free = false;
        $this->lastQuery = $query;
        $this->fetchIndex = 0;
        self::$queryCount++;
        if($param === null)
        {
            $st = microtime(true);
            $this->statement = $this->connection->query($query);
            self::$queryTime += microtime(true) - $st;
            $return = ($this->statement != null);
        }
        else
        {
            $st = microtime(true);
            $this->statement = $this->connection->prepare($query);
            if(!is_array($param))
            {
                $param = null;
            }

            $return = $this->statement->execute($param);
            self::$lastQueryTime = microtime(true) - $st;
            self::$queryTime += self::$lastQueryTime;
        }
        
        if(!$this->statement)
        {
            SHPS_main::log('SQL[' . strtoupper($this->getDriver($this->dbType)) . '] (ERROR): ' . implode(' | ', $this->getLastError()));
            return false;
        }
        
        SHPS_main::log('SQL[' . strtoupper($this->getDriver($this->dbType)) . '] (' . ((int)($return != false)) . '|' . $this->statement->errorCode() . '): ' . $query);
        if($this->statement->errorCode() != '00000')
        {
            SHPS_main::log('SQL[' . strtoupper($this->getDriver($this->dbType)) . '] (ERROR): ' . implode(' || ', $this->statement->errorInfo()));
        }
        
        $this->includeTable = array();
        return $return;
    }
    
    /**
     * Return last error
     * 
     * @return string
     */
    public function getLastError()
    {
        return $this->connection->errorInfo();
    }
    
    /**
     * Get all results
     * 
     * @return array Array of SHPS_sql_resultrow
     */
    public function fetchResult()
    {
        $return = array();
        if(!$this->statement)
        {
            return $return;
        }
        
        $result = $this->statement->fetchAll();
        foreach($result as $r)
        {
            $return[] = new SHPS_sql_resultrow();
            $i = 0;
            while(1)
            {
                if(!isset($r[$i]))
                {
                    break;
                }
                
                if(!isset($this->tblInfo[$i]['columne']))
                {
                    break;
                }

                end($return)->setValue($this->tblInfo[$i]['columne'], $r[$i], $this->tblInfo[$i]['table']);                
                $i++;
            }
        }
        
        return $return;
    }
    
    /**
     * Get one result row
     * 
     * @return SHPS_sql_resultrow
     */
    public function fetchRow()
    {
        if(!$this->statement)
        {
            return;
        }
        
        $r = $this->statement->fetch();
        if(!$r)
        {
            return;
        }
        
        $i = 0;
        $return = new SHPS_sql_resultrow();
        
        if(!isset($this->tblInfo[0]))
        {
            $arrayKeys = array();
            foreach($r as $ri)
            {
                if(!is_numeric($ri))
                {
                    $arrayKeys[] = $ri;
                }
            }
        }
        
        while(isset($r[$i]))
        {
            if(!isset($this->tblInfo[0]))
            {
                $return->setValue($arrayKeys[$i], $r[$i]);
            }
            else
            {
                $return->setValue($this->tblInfo[$i]['columne'], $r[$i], $this->tblInfo[$i]['table']);
            }
            
            $i++;
        }
        
        $this->fetchIndex++;
        return $return;
    }
    
    /**
     * Get type name as string
     * 
     * @param type $type
     * @return string
     */
    private function getType($type)
    {
        switch($type)
        {
            case SHPS_SQL_COL_TYPE_INT:
            {
                return 'INTEGER';
            }
            
            case SHPS_SQL_COL_TYPE_BLOB:
            {
                return 'BLOB';
            }
            
            case SHPS_SQL_COL_TYPE_BOOL:
            {
                return 'BOOL';
            }
            
            case SHPS_SQL_COL_TYPE_FLOAT:
            {
                return 'FLOAT';
            }
            
            case SHPS_SQL_COL_TYPE_TEXT:
            {
                return 'TEXT';
            }
            
            case SHPS_SQL_COL_TYPE_VARCHAR:
            {
                return 'VARCHAR';
            }
            
            default:
            {
                throw new SHPS_exception(SHPS_ERROR_SQL_COL_TYPE);
            }
        }
    }
    
    /**
     * Free connection for reuse
     */
    public function free()
    {
        $this->statement = null;
        $this->tblInfo = array();
        $this->lastQuery = '';
        $this->fetchIndex = 0;
        $this->free = true;
    }
    
    /**
     * Return connection status
     * 
     * @return boolean
     */
    public function isFree()
    {
        return $this->free;
    }
    
    /**
     * Number of result rows from last query
     * 
     * @return integer
     */
    public function count()
    {
        if(!$this->statement)
        {
            return 0;
        }
        
        return $this->statement->rowCount();
    }
    
    /**
     * Get ID of last inserted row
     * 
     * @return integer
     */
    public function getLastID()
    {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Get the database name
     * 
     * @return string
     */
    public function getDB()
    {
        return $this->db;
    }
}

/**
 * SHPS_sql_table
 */
class SHPS_sql_table
{
    /**
     * Database
     * 
     * @var SHPS_sql
     */
    private $sql = null;
    
    /**
     * Table name
     * 
     * @var string
     */
    private $name = '';
    
    /**
     * CONSTRUCTOR
     * 
     * @param SHPS_sql $sql
     * @param string $name
     */
    public function __construct($sql, $name)
    {
        $this->sql = $sql;
        $this->name = $name;
    }
    
    /**
     * DB.Table formatted for the DB System
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->sql->standardizeName($this->sql->getDB())
               . '.' . $this->sql->standardizeName($this->getFullName());
    }
    
    /**
     * Get bound SQL object
     * 
     * @return SHPS_sql
     */
    public function getSQL()
    {
        return $this->sql;
    }
    
    /**
     * Get name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Get full table name with prefix
     * 
     * @return string
     */
    public function getFullName()
    {
        return $this->sql->getPrefix() . $this->name;
    }
    
    /**
     * Get absolute table name with database and prefix<br>
     * The returned name is already normalized
     * 
     * @return string
     */
    public function getAbsoluteName()
    {
        return $this->sql->standardizeName($this->sql->getDB()) . '.' .
               $this->sql->standardizeName($this->getFullName());
    }

    /**
     * Get all columne specs of this table as array
     * 
     * @return array of SHPS_sql_colspec
     */
    public function getAllColumnes()
    {
        if($this->sql->isFree())
        {
            $sql = $this->sql;
        }
        else
        {
            $sql = SHPS_sql::newSQL();
        }
        
        $sql->query('SELECT `COLUMN_NAME`
                     FROM `INFORMATION_SCHEMA`.`COLUMNS` 
                     WHERE `TABLE_SCHEMA`=\'' . $this->sql->getDB() . '\' 
                     AND `TABLE_NAME`=\'' . $this->getFullName() . '\';');
        
        $rows = array();
        while(($row = $sql->fetchRow()))
        {
            $da = $row->getRawData();
            $da = array_keys($da[SHPS_SQL_NO_TABLE]);
            $rows[] = new SHPS_sql_colspec($this, $da[0]);
        }
        
        $sql->free();
        return $rows;
    }
    
    /**
     * Drop this table
     * ATTENTION! Data will be lost!
     */
    public function drop()
    {
        if($this->sql->isFree())
        {
            $sql = $this->sql;
        }
        else
        {
            $sql = SHPS_sql::newSQL();
        }
        
        $sql->query('DROP TABLE ' . $this->getAbsoluteName() . ';');
        $sql->free();
    }
    
    /**
     * Insert one row into table
     * 
     * @param array $values Key => Value array
     * @return boolean
     */
    public function insert($values)
    {
        if(!is_array($values))
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        $cols = array();
        $vals = array();
        foreach($values as $col => $val)
        {
            $cols[] = $this->sql->standardizeName($col);
            if(is_string($val))
            {
                $vals[] = $this->sql->standardizeString($val);
            }
            elseif(is_bool($val))
            {
                $vals[] = $val ? 1 : 0;
            }
            else
            {
                $vals[] = $val;
            }
        }
        
        $cols = implode(',', $cols);
        $vals = implode(',', $vals);
        return $this->sql->query('INSERT INTO ' . (string)$this .
                                 '(' . $cols . ') VALUES (' . $vals . ');');
    }
    
    /**
     * Delete entries from the table
     * 
     * @todo Add orderBy and limit as prameters
     * 
     * @param mixed $conditions
     * @throws SHPS_exception
     * @return boolean
     */
    public function delete($conditions)
    {
        $where = '';
        if($conditions !== null)
        {
            if (!($conditions instanceof SHPS_sql_condition
            || $conditions instanceof SHPS_sql_conditionBuilder))
            {
                throw new SHPS_exception(SHPS_ERROR_SQL_CLASS);
            }
            
            $where = ' WHERE ' . (string)$conditions;
        }
        
        return $this->sql->query('DELETE FROM ' . (string)$this . $where .';');
    }
    
    /**
     * Update a row in the table
     * 
     * @param array $values Key => Value array
     * @param array $conditions Array of SHPS_sql_condition
     * @return boolean
     */
    public function update($values, $conditions)
    {
        if(!is_array($values))
        {
            throw  new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        if ($conditions !== null
        &&!($conditions instanceof SHPS_sql_condition
        || $conditions instanceof SHPS_sql_conditionBuilder))
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_CLASS);
        }
        
        $ins = '';
        $insa = array();
        foreach($values as $key => $val)
        {
            $ins .= $this->sql->standardizeName($key) .'=?,';
            $insa[] = $val;
        }
        
        if(substr($ins, -1) == ',')
        {
            $ins = substr($ins, 0, -1);
        }
        
        return $this->sql->query('UPDATE ' . (string)$this .
                                 ' SET ' . $ins . $conditions,$insa);
    }
}

/**
 * SHPS_sql_col
 */
class SHPS_sql_col
{
    /**
     * Columne name
     * 
     * @var string
     */
    private $name = '';
    
    /**
     * Default value
     * 
     * @var mixed
     */
    private $default = '';
    
    /**
     * Columne type
     * 
     * @var integer
     */
    private $type = 0;
    
    /**
     * Auto increment
     * 
     * @var boolean
     */
    private $ai = false;
    
    /**
     * Allow NULL
     * 
     * @var boolean
     */
    private $null = false;
    
    /**
     * Unique values
     * 
     * @var boolean
     */
    private $unique = false;
    
    /**
     * Primary key columne
     * 
     * @var boolean
     */
    private $pk = false;
    
    /**
     * Maximum length
     * 
     * @var integer
     */
    private $length = 0;
    
    /**
     * CONSTRUCTOR
     * 
     * @param string $name
     * @param integer $type See constants
     * @param mixed $default //Default: ''
     * @param integer $length //Default: 0
     * @param boolean $notNULL
     */
    public function __construct($name,
                                $type,
                                $default = '',
                                $length = 0,
                                $notNULL = true)
    {
        $this->name = $name;
        $this->type = $type;
        $this->default = $default;
        $this->length = $length;
        $this->null = !$notNULL;
    }
    
    public function __toString()
    {
        return $this->name;
    }
    
    /**
     * Get Length
     * 
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }
    
    /**
     * Get Name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Get Type
     * 
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Is NULL
     * 
     * @return boolean
     */
    public function isNULL()
    {
        return $this->null;
    }

    /**
     * Is Primary Key
     * 
     * @return boolean
     */
    public function isPrimaryKey()
    {
        return $this->pk;
    }
    
    /**
     * Is Unique
     * 
     * @return boolean
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * Make INT col increment automatically
     * 
     * @param boolean $ai //Default: true
     */
    public function setAutoIncrement($ai = true)
    {
        $this->ai = $ai;
    }

    /**
     * Is auto increment used
     *
     * @return bool
     */
    public function isAutoIncremented()
    {
        return $this->ai;
    }
    
    /**
     * Values in this columne are unique
     * 
     * @param boolean $unique //Default: true
     */
    public function setUnique($unique = true)
    {
        $this->unique = $unique;
        if($unique)
        {
            $this->pk = false;
        }
    }
    
    /**
     * This columne contains the primary key
     * 
     * @param boolean $pk //Default: true
     */
    public function setPrimaryKey($pk = true)
    {
        $this->pk = $pk;
        if($pk)
        {
            $this->unique = false;
        }
    }
}

/**
 * SHPS_sql_condition
 */
class SHPS_sql_condition
{
    /**
     * Left side of relation
     * 
     * @var mixed SHPS_sql_colspec or SHPS_sql_condition (for chaining)
     */
    private $left = null;
    
    /**
     * Right side of relation
     * 
     * @var mixed SHPS_sql_colspec or SHPS_sql_condition (for chaining) or mixed value
     */
    private $right = null;
    
    /**
     * Relation type
     * 
     * @var integer
     */
    private $rel = 0;
    
    /**
     * CONSTRUCTOR
     * 
     * @param mixed $left SHPS_sql_colspec or SHPS_sql_condition (for chaining)
     * @param integer $rel Realtion of left to right (see constants)
     * @param mixed $right SHPS_sql_colspec or SHPS_sql_condition (for chaining) or mixed value
     */
    public function __construct($left, $rel, $right)
    {
        $this->left = $left;
        $this->rel = $rel;
        $this->right = $right;
    }

    /**
     * Relation as SQL string
     */
    public function __toString()
    {
        $r = '';
        if($this->left instanceof SHPS_sql_condition)
        {
            $r = (string)$this->left;
        }
        elseif($this->left instanceof SHPS_sql_colspec)
        {
            $r = (string)$this->left;
            $this->getSQL()->includeTable($this->left->getTable());
        }
        else
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_CLASS);
        }
        
        $r .= ' ' . $this->rel . ' ';
        if($this->right instanceof SHPS_sql_condition)
        {
            $r .= (string)$this->right;
        }
        elseif($this->right instanceof SHPS_sql_colspec)
        {
            $r .= (string)$this->right;
            $this->getSQL()->includeTable($this->right->getTable());
        }
        elseif((is_string($this->right) || is_int($this->right)) && $this->right !== null)
        {
            try
            {
                if(is_numeric($this->right) || is_bool($this->right))
                {
                    $r .= SHPS_sql::cleanInt($this->right);
                }
                else
                {
                    $r .= $this->left->getSQL()->standardizeString($this->left->getSQL()->cleanStr($this->right));
                }
            }
            catch(Exception $e)
            {
                throw new SHPS_exception(SHPS_ERROR_PARAMETER, $e);
            }
                
        }
 
        return (string)$r;
    }
    
    public function getSQL()
    {
        return $this->left->getSQL();
    }
}

/**
 * SHPS_sql_grouping
 */
class SHPS_sql_grouping
{
    /**
     * Cols to group
     * 
     * @var array Array of strings
     */
    private $groupBy = array();
    
    /**
     * Feature to have
     * 
     * @var string
     */
    private $having = '';
    
    /**
     * CONSTRUCTOR
     * 
     * @param array $groupBy Array of strings
     * @param string $having //Default: ''
     */
    public function __construct($groupBy, $having = '')
    {
        $this->groupBy = $groupBy;
        $this->having = $having;
    }
    
    /**
     * Get groupBy
     * 
     * @return array Array of strings
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }
    
    /**
     * Get Having
     * 
     * @return string
     */
    public function getHaving()
    {
        return $this->having;
    }
}

/**
 * SHPS_sql_colspec
 */
class SHPS_sql_colspec
{
    /**
     * Table
     * 
     * @var SHPS_sql_table 
     */
    private $table = null;
    
    /**
     * Columne name
     * 
     * @var string
     */
    private $col = '';
    
    /**
     * CONSTRUCTOR
     * 
     * @param SHPS_sql_table $table
     * @param string $col
     */
    public function __construct($table, $col)
    {
        $this->table = $table;
        $this->col = $col;
    }
    
    /**
     * Columne as SQL string
     */
    public function __toString()
    {
        return $this->table->getSQL()->standardizeName($this->table->getSQL()->getDB()) .
               '.' . $this->table->getSQL()->standardizeName($this->table->getFullName()) .
               '.' . $this->table->getSQL()->standardizeName($this->col);
    }
    
    /**
     * Get table
     * 
     * @return SHPS_sql_table
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * Get Columne name
     * 
     * @return string
     */
    public function getColName()
    {
        return $this->col;
    }
    
    public function getSQL()
    {
        return $this->table->getSQL();
    }
}

/**
 * SHPS_sql_resultrow
 */
class SHPS_sql_resultrow
{
    /**
     * Result data
     * 
     * @var array Array of Key => Value pairs indexed by table
     */
    private $data = array();
    
    /**
     * Retrive value of result row
     * 
     * @param string $key
     * @param mixed $table SHPS_sql_table or string, NULL for first found //Default: null
     * @return mixed
     */
    public function getValue($key, $table = null)
    {
        $table = self::getTableName($table);
        if(isset($this->data[$table][$key]))
        {
            return $this->data[$table][$key];
        }
        
        if(isset($this->data[$this->data[0]][$key]))
        {
            return $this->data[$this->data[0]][$key];
        }
            
        if($table === SHPS_SQL_NO_TABLE)
        {
            if(isset($this->data[SHPS_SQL_NO_TABLE][$key]))
            {
                return $this->data[SHPS_SQL_NO_TABLE][$key];
            }
            
            foreach($this->data as $vv)
            {
                if(isset($vv[$key]))
                {
                    return $vv[$key];
                }
            }
        }
        
        return '';
    }
    
    /**
     * Get raw data array
     * 
     * @return array
     */
    public function getRawData()
    {
        return $this->data;
    }
    
    /**
     * Add (or edit if already existent) a value pair
     * 
     * @param string $key
     * @param mixed $value
     * @param mixed $table SHPS_sql_table or string, NULL for default //Default: null
     */
    public function setValue($key, $value, $table = null)
    {
        $table = self::getTableName($table);            
        if(!isset($this->data[$table]))
        {
            $this->data[] = $table;
            $this->data[$table] = array();
        }
        
        $this->data[$table][$key] = $value;
    }
    
    /**
     * Returns tablename as string
     * 
     * @param mixed $table
     * @return string
     * @throws SHPS_exception
     */
    private static function getTableName($table)
    {
        if($table === null)
        {
            $table = SHPS_SQL_NO_TABLE;
        }
        
        if($table instanceof SHPS_sql_table)
        {
            $table = $table->getName();
        }
        
        if(!is_string($table))
        {
            throw new SHPS_exception(SHPS_ERROR_PARAMETER);
        }
        
        return $table;
    }
    
    /**
     * Transform resultset into array<br>
     * If table is set to null, data might be overwritten
     * 
     * @param mixed $table string or SHPS_sql_table //Default: null
     * @return array
     */
    public function asArray($table = null)
    {
        $r = array();
        $table = self::getTableName($table);
        foreach($this->data as $dk => $dv)
        {
            if($table == SHPS_SQL_NO_TABLE || $dk == $table)
            {
                $r += $dv;
            }
        }
        
        return $r;
    }
}

/**
 * Condition Builder
 */
class SHPS_sql_conditionBuilder
{
    private $conditions = '';
    
    protected $conditionChain = null;
    
    public function __construct()
    {
        $this->conditionChain = new SHPS_sql_conditionChain($this);
    }
    
    public function __toString()
    {
        return $this->conditions;
    }
    
    public function equal($left, $right)
    {
        $this->conditions .= (string)new SHPS_sql_condition($left, SHPS_SQL_RELATION_EQUAL, $right) . ' ';
        return $this->conditionChain;
    }
    
    public function notEqual($left, $right)
    {
        $this->conditions .= (string)new SHPS_sql_condition($left, SHPS_SQL_RELATION_NOT_EQUAL, $right) . ' ';
        return $this->conditionChain;
    }
    
    public function different($left, $right)
    {
        return $this->notEqual($left, $right);
    }
    
    public function larger($left, $right)
    {
        $this->conditions .= (string)new SHPS_sql_condition($left, SHPS_SQL_RELATION_LARGER, $right) . ' ';
        return $this->conditionChain;
    }
    
    public function greater($left, $right)
    {
        return $this->larger($left, $right);
    }
    
    public function more($left, $right)
    {
        return $this->larger($left, $right);
    }
    
    public function smaller($left, $right)
    {
        $this->conditions .= (string)new SHPS_sql_condition($left, SHPS_SQL_RELATION_SMALLER, $right) . ' ';
        return $this->conditionChain;
    }
    
    public function less($left, $right)
    {
        return $this->smaller($left, $right);
    }
    
    public function largerEqual($left, $right)
    {
        $this->conditions .= (string)new SHPS_sql_condition($left, SHPS_SQL_RELATION_LARGER_EQUAL, $right) . ' ';
        return $this->conditionChain;
    }
    
    public function greaterEqual($left, $right)
    {
        return $this->largerEqual($left, $right);
    }
    
    public function moreEqual($left, $right)
    {
        return $this->largerEqual($left, $right);
    }
    
    public function like($left, $right)
    {
        $this->conditions .= (string)new SHPS_sql_condition($left, SHPS_SQL_RELATION_LIKE, $right) . ' ';
        return $this->conditionChain;
    }
    
    public function similar($left, $right)
    {
        return $this->like($left, $right);
    }
    
    public function smallerEqual($left, $right)
    {
        $this->conditions .= (string)new SHPS_sql_condition($left, SHPS_SQL_RELATION_SMALLER_EQUAL, $right) . ' ';
        return $this->conditionChain;
    }
    
    public function lessEqual($left, $right)
    {
        return $this->smallerEqual($left, $right);
    }
    
    public function between($left, $right0, $right1)
    {
        $this->conditions .= (string)new SHPS_sql_condition(
            $left,
            SHPS_SQL_RELATION_BETWEEN,
            new SHPS_sql_condition(
                $right0,
                SHPS_SQL_RELATION_AND,
                $right1
            )
        ) . ' ';
        
        return $this->conditionChain;
    }
    
    /**
     * 
     * @param type $str
     * @return \SHPS_sql_conditionBuilder
     * @throws SHPS_exception
     */
    public function _add($str)
    {
        $gcc = get_called_class();
        if ($gcc != 'SHPS_sql_conditionBuilder'
        && $gcc != 'SHPS_sql_conditionChain'
        && get_parent_class($gcc) != 'SHPS_plugin')
        {
            throw new SHPS_exception(SHPS_ERROR_CALLING_CLASS);
        }
        
        $this->conditions .= $str . ' ';
        
        return $this;
    }
}

class SHPS_sql_queryConditionBuilder extends SHPS_sql_conditionBuilder
{
    private $queryBuilder = null;
    
    public function __construct($queryBuilder)
    {
        parent::__construct();
        $this->conditionChain = new SHPS_sql_queryConditionChain($this);
        
        if (!$queryBuilder instanceof SHPS_sql_queryBuilder)
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_CLASS);
        }
        
        $this->queryBuilder = $queryBuilder;
    }
    
    public function execute()
    {
        $this->queryBuilder->execute($this);
    }
}

/**
 * For creating queries easily
 */
class SHPS_sql_queryBuilder
{
    /**
     * Connection to use
     * 
     * @var SHPS_sql
     */
    private $sql = null;
    
    /**
     * Contains type of operation
     * 0 = UNDEFINED
     * 1 = GET
     * 2 = ADD/ALTER
     * 3 = DELETE
     * 
     * @var int
     */
    private $operation = 0;
    
    /**
     * Data to work with
     * GET: cols to get
     * SET: col=>value to set
     * 
     * @var array of SHPS_sql_colspec
     */
    private $buf = array();
    
    /**
     * Table to use for set or delete operations
     * 
     * @var \SHPS_sql_table
     */
    private $table = null;
    
    /**
     * Reset Query Builder
     */
    private function reset()
    {
        $this->operation = 0;
        $this->buf = array();
    }
    
    public function __construct($sql)
    {
        $this->sql = $sql;
    }
    
    /**
     * Fetch from the DB
     * 
     * @param \SHPS_sql_colspec
     * @param ... several colspecs can be given, each as new parameter or as array(s)
     * @return \SHPS_sql_queryBuilder
     */
    public function get(/* ... */)
    {
        $this->reset();
        $this->operation = 1;
        
        foreach(func_get_args() as $arg)
        {
            if(is_array($arg))
            {
                foreach($arg as $a)
                {
                    $this->buf += $a;
                }
            }
            else
            {
                $this->buf[] = $arg;
            }
        }
        
        return $this;
    }
    
    /**
     * Add values to DB or alter data
     * 
     * @param \SHPS_sql_table $table Table to work with
     * @param array $data (col => value) pairs
     * @return \SHPS_sql_queryBuilder
     */
    public function set($table, $data)
    {
        $this->reset();
        $this->operation = 2;
        
        $this->table = $table;
        $this->buf = $data;
        
        return $this;
    }
    
    /**
     * Delete data from DB
     * 
     * @param \SHPS_sql_table $table table to delete or delete from
     * @return \SHPS_sql_queryBuilder
     */
    public function delete($table)
    {
        $this->reset();
        $this->operation = 3;
        
        $this->table = $table;
        
        return $this;
    }
    
    /**
     * Add conditions to query
     * 
     * @return \SHPS_sql_queryConditionBuilder
     */
    public function fulfilling()
    {
        if ($this->operation === 0)
        {
            throw new SHPS_exception(SHPS_ERROR_SQL_UNKNOWN_OPERATION);
        }
        
        return new SHPS_sql_queryConditionBuilder($this);
    }
    
    /**
     * Execute the query
     * 
     * @param mixed $conditions optional
     * @return \SHPS_sql_queryBuilder
     */
    public function execute()
    {
        if (func_num_args() > 0)
        {
            $conditions = func_get_arg(0);
        }
        else
        {
            $conditions = null;
        }
        
        switch ($this->operation)
        {
            case 0:
                throw new SHPS_exception(SHPS_ERROR_SQL_UNKNOWN_OPERATION);
                
            case 1:
                $this->sql->readTables($this->buf, $conditions);
                break;
                
            case 2:
                if ($conditions === null)
                {// Add row
                    $this->table->insert($this->buf);
                }
                else
                {// Alter row
                    $this->table->update($this->buf, $conditions);
                }
                break;
                
            case 3:
                if ($conditions === null)
                {// Drop table
                    $this->table->drop();
                }
                else
                {// Drop rows
                    $this->table->delete($conditions);
                }
                
                break;
        }
        
        return $this;
    }
}

/**
 * For chaining conditions together logically
 */
class SHPS_sql_conditionChain
{
    /**
     *
     * @var SHPS_sql_conditionBuilder
     */
    protected $conditionBuilder = null;
    
    public function __construct($conditionBuilder)
    {
        $this->conditionBuilder = $conditionBuilder;
    }
    
    /**
     * 
     * @return SHPS_sql_conditionBuilder
     */
    public function and_()
    {
        return $this->conditionBuilder->_add(SHPS_SQL_RELATION_AND);
    }
    
    public function or_()
    {
        return $this->conditionBuilder->_add(SHPS_SQL_RELATION_OR);
    }
}

class SHPS_sql_queryConditionChain extends SHPS_sql_conditionChain
{
    public function __construct($conditionBuilder)
    {
        parent::__construct($conditionBuilder);
    }
    
    public function execute()
    {
        $this->conditionBuilder->execute();
    }
}