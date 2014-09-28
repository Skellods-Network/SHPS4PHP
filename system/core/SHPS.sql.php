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

namespace SHPS {


    define('SQL_NULL',-1);
    define('SQL_MYSQL',0);
    define('SQL_MSSQL',10);
    define('SQL_POSTGRESQL',20);
        define('SQL_POSTGRE',20);
    define('SQL_ORACLE',30);
    define('SQL_SQLITE',40);

    define('SQL_NO_TABLE','!SQL_TABLE');


    /**
     * SQL
     *
     * All functionalities to connect to SQL databases are bundled in the SQL class.
     * 
     *
     * @author Marco Alka <admin@skellods.de>
     * @version 2.0
     * 
     * 
     * TODO:
     * - Add other DBs
     * - Add support for great amounts of data (prepared insert)
     * - Add NoSQL support
     * 
     * Changelog:
     * - deleted deprecated functions
     * - clarified function by changing name (deprecated old name)
     * - removed old SQL query system
     * - added more functions to new SQL query system (orderBy(), limit())
     * - added new function to table class (col())
     * - removed SHPS_ prefix
     * - added \SHPS namespace
     * - renamed sql_colspec to sql_col and vice versa
     * - deleted unneeded classes sql_condition, sql_queryConditionBuilder and sql_queryConditionChain
     * - breaking changes in DB (new tables, renamed tables, added cols, deleted cols, deleted tables, changed col types and attributes)
     */
    class sql 
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
        private $dbType = SQL_MYSQL;

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
         * @var \PDOStatement
         */
        private $statement = null;

        /**
         * Index of next row to fetch
         * 
         * @var integer 
         */
        private $fetchIndex = 0;

        /**
         * Server Type
         * 
         * @var string
         */
        private $serverType = '';

        /**
         * SQL string determinators
         * 
         * @var []
         */
        private $stringdeterminator = [
            SQL_MYSQL => '\'',
            SQL_MSSQL => '\'',
            SQL_ORACLE => '\''
        ];

        /**
         * SQL variable determinators
         * 
         * @var []
         */
        private $variabledeterminator = [
            SQL_MYSQL => ['`','`'],
            SQL_MSSQL => ['[',']']
        ];

        /**
         * Alias Connections
         * 
         * @var []
         */
        private static $alias_connections = [];

        /**
         * Memcached object
         * 
         * @var \memcached
         */
        private $memcached = null;
        
        /**
         * Alias
         * 
         * @var string
         */
        private $alias = '';
        
        /**
         * QueryBuilder column selection to perform an operation on
         * 
         * @var [0 => col, 1 => col, ...]
         */
        private $qbSELECTOR = [];
        
        /**
         * QueryBuilder buffer array filled with conditions and their chaining operators
         * 
         * @var [0 => [$left, $operator, $right], 1=> $chainOperator, ...]
         */
        private $qbConditionBuffer = [];
        
        /**
         * QueryBuilder operation mode
         * 0 - UNDEFINED
         * 1 - SELECT
         * 2 - INSERT
         * 3 - UPDATE
         * 4 - DELETE
         * 
         * @var integer
         */
        private $qbOperationMode = 0;


        /**
         * CONSTRUCTOR<br>
         * For SQLite, a new file will be created if the database file is missing
         * 
         * @param string $user
         * @param string $passwd
         * @param string $database
         * @param string $host
         * @param string $prefix
         * @param [] $mcServers [[(Sting)'Host',(Integer)['Port']],[...]]
         */
        public function __construct($user,
                                    $passwd,
                                    $database,
                                    $host = 'localhost',
                                    $port = 3306,
                                    $prefix = 'HP_',
                                    $dbType = SQL_MYSQL,
                                    $mcServers = [])
        {
            if (!defined('PDO::ATTR_DRIVER_NAME'))
            {
                throw new \SHPS\exception(ERROR_SQL_PDO_NOT_INSTALLED);
            }

            if(!($driver = self::getDriver($dbType)))
            {
                throw new \SHPS\exception(ERROR_SQL_DATABASE_TYPE);
            }

            if($dbType == SQL_SQLITE)
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
            $options = [];
            switch($dbType)
            {
                case SQL_MYSQL:
                {
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES \'UTF8\';';
                    break;
                }
            }

            try
            {
                $this->connection = new \PDO($dns,$user,$passwd,$options);
            }
            catch(PDOException $pdoE)
            {
                throw new \SHPS\exception(ERROR_SQL_CONNECTION, $pdoE);
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
                $this->memcached = new \Memcached();
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
         * @return sql
         */
        public static function newSQL($alias = 'default')
        {
            if(!isset(self::$alias_connections[$alias]))
            {
                self::$alias_connections[$alias] = [];
            }

            $c = main::getHPConfig('Database_Config', $alias);
            if($c === null || !is_array($c))
            {
                throw new \SHPS\exception(ERROR_SQL_ALIAS);
            }

            foreach(self::$alias_connections[$alias] as $ac)
            {
                if($ac->isFree())
                {
                    return $ac;
                }
            }

            $nc = new static($c['DB_User'],
                             $c['DB_Pass'],
                             $c['DB_Name'],
                             $c['DB_Host'],
                             $c['DB_Port'],
                             $c['DB_Pre'],
                             $c['DB_Type']);
            
            self::$alias_connections[$alias][] = $nc;
            $nc->alias = $alias;

            return $nc;
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
                case SQL_MYSQL:
                {
                    return 'mysql';
                }

                case SQL_MSSQL:
                {
                    return 'mssql';
                }

                case SQL_ORACLE:
                {
                    return 'oci';
                }

                case SQL_POSTGRE:
                {
                    return 'pgsql';
                }

                case SQL_SQLITE:
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
         * @param string $dbType //Default: SQL_MYSQL
         * @return boolean
         */
        public static function testConnection($user,
                                              $passwd,
                                              $database,
                                              $host = 'localhost',
                                              $port = 3306,
                                              $dbType = SQL_MYSQL)
        {
            if(!($driver = self::getDriver($dbType)))
            {
                return false;
            }

            if($dbType == SQL_SQLITE && !file_exists($database))
            {
                return false;
            }

            if($dbType == SQL_SQLITE)
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
                $pdo = new \PDO($dns,$user,$passwd);
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
         * @param [] $cols Array of sql_colspec
         * @param boolean $ifNotExists Throws error if table exists //Default: true
         * @param boolean $temp If true table is only temporary (in memory) //Default: false
         * @return \SHPS\sql_table
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
                if(!$col instanceof sql_colspec)
                {
                    throw new \SHPS\exception(ERROR_SQL_CLASS);
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
                case SQL_MYSQL:
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
                    case SQL_MYSQL:
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
         * @return \SHPS\sql_table
         */
        public function openTable($name)
        {
            return new \SHPS\sql_table($this,$name);
        }

        /**
         * Return an object to create a query with
         * 
         * @deprecated since version 3.1.0, use sql::query() without arguments instead
         * @return \SHPS\sql_queryBuilder
         */
        public function makeQuery()
        {
            return new \SHPS\sql_queryBuilder($this);
        }

        /**
         * Return last SQL Query as string
         * 
         * @return string
         */
        public function getLastQuery()
        {
            return $this->lastQuery;
        }

        /**
         * Return last SQL Query as string
         * 
         * @deprecated since version 3.1.0, use sql::getLastQuery() instead
         * @return string
         */
        public function getQuery()
        {
            return $this->lastQuery;
        }

        /**
         * Just run the given query<br>
         * It is not recommended to use custom queries!
         * 
         * @param string $query
         * @param []|null $param //Default: null
         * @return boolean
         */
        public function query($query = '', $param = null)
        {
            $this->free = false;
            $this->fetchIndex = 0;
            if (func_num_args() == 0)
            {
                return new \SHPS\sql_queryBuilder($this);
            }

            $this->lastQuery = $query;
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
                main::log('SQL[' . strtoupper($this->getDriver($this->dbType)) . '] (ERROR): ' . implode(' | ', $this->getLastError()));
                return false;
            }

            main::log('SQL[' . strtoupper($this->getDriver($this->dbType)) . '] (' . ((int)($return != false)) . '|' . $this->statement->errorCode() . '): ' . $query);
            if($this->statement->errorCode() != '00000')
            {
                main::log('SQL[' . strtoupper($this->getDriver($this->dbType)) . '] (ERROR): ' . implode(' || ', $this->statement->errorInfo()));
            }

            $this->includeTable = [];
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
         * @return [] Array of \SHPS\sql_resultrow
         */
        public function fetchResult()
        {
            $return = [];
            if(!$this->statement)
            {
                return $return;
            }

            $result = $this->statement->fetchAll();
            foreach($result as $r)
            {
                $return[] = new \SHPS\sql_resultrow();
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
         * @return \SHPS\sql_resultrow
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
            $return = new \SHPS\sql_resultrow();

            if(!isset($this->tblInfo[0]))
            {
                $arrayKeys = [];
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
         * @param integer $type
         * @return string
         */
        private function getType($type)
        {
            switch($type)
            {
                case SQL_COL_TYPE_INT:
                {
                    return 'INTEGER';
                }

                case SQL_COL_TYPE_BLOB:
                {
                    return 'BLOB';
                }

                case SQL_COL_TYPE_BOOL:
                {
                    return 'BOOL';
                }

                case SQL_COL_TYPE_FLOAT:
                {
                    return 'FLOAT';
                }

                case SQL_COL_TYPE_TEXT:
                {
                    return 'TEXT';
                }

                case SQL_COL_TYPE_VARCHAR:
                {
                    return 'VARCHAR';
                }

                default:
                {
                    throw new \SHPS\exception(ERROR_SQL_COL_TYPE);
                }
            }
        }

        /**
         * Get alias used for this connection
         * 
         * @return string
         */
        public function getSelectedAlias()
        {
            return $this->alias;
        }
        
        /**
         * Free connection for reuse
         */
        public function free()
        {
            $this->statement = null;
            $this->tblInfo = [];
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
     * \SHPS\sql_table
     */
    class sql_table
    {
        /**
         * Database
         * 
         * @var sql
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
         * @param sql $sql
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
         * Get column of table
         * 
         * @param string $name
         * @return \SHPS\sql_col
         */
        public function col($name)
        {
            return new \SHPS\sql_col($this, $name);
        }

        /**
         * Get bound SQL object
         * 
         * @return \SHPS\sql
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
         * Get all columne specs of this table as []
         * 
         * @return [] of \SHPS\sql_col
         */
        public function getAllColumnes()
        {
            if($this->sql->isFree())
            {
                $sql = $this->sql;
            }
            else
            {
                $sql = sql::newSQL();
            }

            $sql->query('SELECT `COLUMN_NAME`
                         FROM `INFORMATION_SCHEMA`.`COLUMNS` 
                         WHERE `TABLE_SCHEMA`=\'' . $this->sql->getDB() . '\' 
                         AND `TABLE_NAME`=\'' . $this->getFullName() . '\';');

            $rows = [];
            while(($row = $sql->fetchRow()))
            {
                $da = $row->getRawData();
                $da = array_keys($da[SQL_NO_TABLE]);
                $rows[] = new \SHPS\sql_col($this, $da[0]);
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
                $sql = sql::newSQL();
            }

            $sql->query('DROP TABLE ' . $this->getAbsoluteName() . ';');
            $sql->free();
        }

        /**
         * Insert one row into table
         * 
         * @param [] $values Key => Value []
         * @return boolean
         */
        public function insert($values)
        {
            if(!is_array($values))
            {
                throw new \SHPS\exception(ERROR_PARAMETER);
            }

            $cols = [];
            $vals = [];
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
         * @param \SHPS\sql_conditionBuilder $conditions
         * @throws \SHPS\exception
         * @return boolean
         */
        public function delete($conditions)
        {
            $where = '';
            if($conditions !== null)
            {
                if (!($conditions instanceof sql_condition
                || $conditions instanceof sql_conditionBuilder))
                {
                    throw new \SHPS\exception(ERROR_SQL_CLASS);
                }

                $where = ' WHERE ' . (string)$conditions;
            }

            return $this->sql->query('DELETE FROM ' . (string)$this . $where .';');
        }

        /**
         * Update a row in the table
         * 
         * @param [] $values Key => Value []
         * @param \SHPS\sql_conditionBuilder $conditions
         * @return boolean
         */
        public function update($values, $conditions)
        {
            if(!is_array($values))
            {
                throw  new \SHPS\exception(ERROR_PARAMETER);
            }

            if ($conditions !== null
            &&!($conditions instanceof sql_condition
            || $conditions instanceof sql_conditionBuilder))
            {
                throw new \SHPS\exception(ERROR_SQL_CLASS);
            }

            $ins = '';
            $insa = [];
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
     * sql_colspec
     */
    class sql_colspec
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
         * @return boolean
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
     * sql_grouping
     */
    class sql_grouping
    {
        /**
         * Cols to group
         * 
         * @var [] Array of strings
         */
        private $groupBy = [];

        /**
         * Feature to have
         * 
         * @var string
         */
        private $having = '';

        /**
         * CONSTRUCTOR
         * 
         * @param [] $groupBy Array of strings
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
         * @return [] Array of strings
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
     * sql_col
     */
    class sql_col
    {
        /**
         * Table
         * 
         * @var \SHPS\sql_table 
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
         * @param \SHPS\sql_table $table
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
         * @return \SHPS\sql_table
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

        /**
         * Get parent table's sql object
         * 
         * @return \SHPS\sql_table
         */
        public function getSQL()
        {
            return $this->table->getSQL();
        }
    }

    /**
     * sql_resultrow
     */
    class sql_resultrow
    {
        /**
         * Result data
         * 
         * @var [] Array of Key => Value pairs indexed by table
         */
        private $data = [];

        /**
         * Retrive value of result row
         * 
         * @param string $key
         * @param mixed $table sql_table or string, NULL for first found //Default: null
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

            if($table === SQL_NO_TABLE)
            {
                if(isset($this->data[SQL_NO_TABLE][$key]))
                {
                    return $this->data[SQL_NO_TABLE][$key];
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
         * Get raw data []
         * 
         * @return []
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
         * @param mixed $table sql_table or string, NULL for default //Default: null
         */
        public function setValue($key, $value, $table = null)
        {
            $table = self::getTableName($table);            
            if(!isset($this->data[$table]))
            {
                $this->data[] = $table;
                $this->data[$table] = [];
            }

            $this->data[$table][$key] = $value;
        }

        /**
         * Returns tablename as string
         * 
         * @param mixed $table
         * @return string
         * @throws exception
         */
        private static function getTableName($table)
        {
            if($table === null)
            {
                $table = SQL_NO_TABLE;
            }

            if($table instanceof sql_table)
            {
                $table = $table->getName();
            }

            if(!is_string($table))
            {
                throw new \SHPS\exception(ERROR_PARAMETER);
            }

            return $table;
        }

        /**
         * Transform resultset into []<br>
         * If table is set to null, data might be overwritten
         * 
         * @param mixed $table string or \SHPS\sql_table //Default: null
         * @return []
         */
        public function asArray($table = null)
        {
            $r = [];
            $table = self::getTableName($table);
            foreach($this->data as $dk => $dv)
            {
                if($table == SQL_NO_TABLE || $dk == $table)
                {
                    $r += $dv;
                }
            }

            return $r;
        }
    }

    /**
     * Condition Builder<br>
     * At the moment, all conditions are chained with AND
     * 
     * @todo implement AND/OR between conditions
     */
    class sql_conditionBuilder
    {
        /**
         * Parent Query Builder
         * 
         * @var \SHPS\sql_queryBuilder
         */
        private $queryBuilder = null;
        
        /**
         * Conditions
         * 
         * @var string
         */
        private $conditions = '';
        
        /**
         * Is the current condition the first in the string?
         * 
         * @var boolean
         */
        private $firstCondition = true;

        /**
         * CONSTURCTOR
         * 
         * @param \SHPS\sql_queryBuilder $sqb
         */
        public function __construct($sqb)
        {
            $this->queryBuilder = $sqb;
        }

        public function __toString()
        {
            return $this->conditions;
        }
        
        /**
         * Turn cols, strings and integers into proper SQL queryable values
         * 
         * @param mixed $value
         * @return string
         */
        private function prepare($value)
        {
            if ($value instanceof \SHPS\sql_col)
            {
                $value = (string)$value;
            }
            elseif (is_numeric($value))
            {
                $value = (string)$value;
            }
            else
            {
                $value = '\'' . cleanStr($value) . '\'';
            }
            
            return $value;
        }

        /**
         * 
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function equal($left, $right)
        {
            if ($this->firstCondition)
            {
                $this->firstCondition = false;
            }
            else
            {
                $this->conditions .= 'AND ';
            }
            
            $this->conditions .= $this->prepare($left) . '=' . $this->prepare($right) . ' ';
            return $this;
        }

        /**
         * 
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function notEqual($left, $right)
        {
            if ($this->firstCondition)
            {
                $this->firstCondition = false;
            }
            else
            {
                $this->conditions .= 'AND ';
            }
            
            $this->conditions .= $this->prepare($left) . '!=' . $this->prepare($right) . ' ';
            return $this;
        }

        /**
         * 
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function different($left, $right)
        {
            return $this->notEqual($left, $right);
        }

        /**
         * Left larger than right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function larger($left, $right)
        {
            if ($this->firstCondition)
            {
                $this->firstCondition = false;
            }
            else
            {
                $this->conditions .= 'AND ';
            }
            
            $this->conditions .= $this->prepare($left) . '>' . $this->prepare($right) . ' ';
            return $this;
        }

        /**
         * Left greater than right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function greater($left, $right)
        {
            return $this->larger($left, $right);
        }

        /**
         * Left more than right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function more($left, $right)
        {
            return $this->larger($left, $right);
        }

        /**
         * Left smaller than right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function smaller($left, $right)
        {
            if ($this->firstCondition)
            {
                $this->firstCondition = false;
            }
            else
            {
                $this->conditions .= 'AND ';
            }
            
            $this->conditions .= $this->prepare($left) . '<' . $this->prepare($right) . ' ';
            return $this;
        }

        /**
         * Left less than right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function less($left, $right)
        {
            return $this->smaller($left, $right);
        }

        /**
         * Left larger or equal right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function largerEqual($left, $right)
        {
            if ($this->firstCondition)
            {
                $this->firstCondition = false;
            }
            else
            {
                $this->conditions .= 'AND ';
            }
            
            $this->conditions .= $this->prepare($left) . '>=' . $this->prepare($right) . ' ';
            return $this;
        }

        /**
         * Left greater or equal right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function greaterEqual($left, $right)
        {
            return $this->largerEqual($left, $right);
        }

        /**
         * Left more or equal right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function moreEqual($left, $right)
        {
            return $this->largerEqual($left, $right);
        }

        /**
         * 
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function like($left, $right)
        {
            if ($this->firstCondition)
            {
                $this->firstCondition = false;
            }
            else
            {
                $this->conditions .= 'AND ';
            }
            
            $this->conditions .= $this->prepare($left) . ' LIKE ' . $this->prepare($right) . ' ';
            return $this;
        }

        /**
         * 
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function similar($left, $right)
        {
            return $this->like($left, $right);
        }

        /**
         * Left smaller or equal right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function smallerEqual($left, $right)
        {
            if ($this->firstCondition)
            {
                $this->firstCondition = false;
            }
            else
            {
                $this->conditions .= 'AND ';
            }
            
            $this->conditions .= $this->prepare($left) . '<=' . $this->prepare($right) . ' ';
            return $this;
        }

        /**
         * Left less or equal right
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right
         * @return \SHPS\sql_conditionBuilder
         */
        public function lessEqual($left, $right)
        {
            return $this->smallerEqual($left, $right);
        }

        /**
         * 
         * 
         * @param \SHPS\sql_col|string|integer $left
         * @param \SHPS\sql_col|string|integer $right0
         * @param \SHPS\sql_col|string|integer $right1
         * @return \SHPS\sql_conditionBuilder
         */
        public function between($left, $right0, $right1)
        {
            if ($this->firstCondition)
            {
                $this->firstCondition = false;
            }
            else
            {
                $this->conditions .= 'AND ';
            }
            
            $this->conditions .= $this->prepare($left) . ' BETWEEN ' . $this->prepare($right0) . ' AND ' . $this->prepare($right1);

            return $this;
        }

        /**
         * Execute query
         * 
         * @return \SHPS\sql_queryBuilder
         */
        public function execute()
        {
            $this->queryBuilder->execute();

            return $this->queryBuilder->execute();
        }

        /**
         * INTERNAL add conditions
         * 
         * @param string $str
         * @return \SHPS\sql_conditionBuilder
         * @throws exception
         */
        public function _add($str)
        {
            $gcc = get_called_class();
            if ($gcc != 'sql_conditionBuilder'
            && $gcc != 'sql_conditionChain'
            && get_parent_class($gcc) != 'plugin')
            {
                throw new \SHPS\exception(ERROR_CALLING_CLASS);
            }

            $this->conditions .= $str . ' ';

            return $this;
        }
    }

    /**
     * For creating queries easily
     */
    class sql_queryBuilder
    {
        /**
         * Connection to use
         * 
         * @var sql
         */
        private $sql = null;

        /**
         * Contains type of operation
         * 0 = UNDEFINED
         * 1 = GET
         * 2 = INSERT
         * 3 = ALTER
         * 4 = DELETE
         * 
         * @var int
         */
        private $operation = 0;

        /**
         * Data to work with
         * GET: cols to get
         * SET: col=>value to set
         * 
         * @var [] of sql_col
         */
        private $buf = [];

        /**
         * Table to use for set or delete operations
         * 
         * @var \SHPS\sql_table
         */
        private $table = null;
        
        /**
         * Column to order by
         * 
         * @var \SHPS\sql_col
         */
        private $orderBy = null;
        
        /**
         * Order by ascending?
         * 
         * @var boolean
         */
        private $obAscending = true;
        
        /**
         * Limit number of result rows
         * @var integer
         */
        private $limit = 0;

        /**
         * Reset Query Builder
         */
        private function reset()
        {
            $this->operation = 0;
            $this->buf = [];
        }

        public function __construct($sql)
        {
            $this->sql = $sql;
        }

        /**
         * Fetch from the DB
         * 
         * @param \SHPS\sql_col
         * @param ... several colspecs can be given, each as new parameter or as [](s)
         * @return \SHPS\sql_queryBuilder
         */
        public function get(/* ... */)
        {
            $this->reset();
            $this->sql-> operation = 1;

            foreach(func_get_args() as $arg)
            {
                if(is_array($arg))
                {
                    foreach($arg as $a)
                    {
                        $this->buf[] = $a;
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
         * @todo Find a performant way to decide between insert and alter
         * @ignore
         * @param \SHPS\sql_table $table Table to work with
         * @param [] $data (col => value) pairs
         * @return \SHPS\sql_queryBuilder
         */
        public function set($table, $data)
        {
            throw new \SHPS\exception(SHPS_ERROR_NOT_IMPLEMENTED);
            $this->reset();
            $this->operation = 2;

            $this->table = $table;
            $this->buf = $data;

            return $this;
        }
        
        /**
         * Add values to DB
         * 
         * @param \SHPS\sql_table $table
         * @param [] $data (col => value) pairs
         * @return \SHPS\sql_queryBuilder
         */
        public function insert($table, $data)
        {
            $this->reset();
            $this->operation = 2;
            
            $this->table = $table;
            $this->buf = $data;
            
            return $this;
        }
        
        /**
         * Alter values in DB
         * 
         * @param \SHPS\sql_table $table
         * @param [] $data (col => value) pairs
         * @return \SHPS\sql_queryBuilder
         */
        public function alter($table, $data)
        {
            $this->reset();
            $this->operation = 3;
            
            $this->table = $table;
            $this->buf = $data;
            
            return $this;
        }

        /**
         * Delete data from DB
         * 
         * @param \SHPS\sql_table $table table to delete or delete from
         * @return \SHPS\sql_queryBuilder
         */
        public function delete($table)
        {
            $this->reset();
            $this->operation = 4;

            $this->table = $table;

            return $this;
        }
        
        /**
         * Order result by column
         * 
         * @param \SHPS\sql_col $col
         * @param boolean $ascending Order ascending or not
         * @return \SHPS\sql_queryBuilder
         */
        public function orderBy($col, $ascending = true)
        {
            $this->orderBy = $col;
            $this->obAscending = $ascending;
            
            return $this;
        }
        
        /**
         * Set max number of result rows
         * 
         * @param integer $num
         */
        public function rowLimit($num)
        {
            $this->limit = \cleanInt($num);
        }

        /**
         * Add conditions to query
         * 
         * @return \SHPS\sql_conditionBuilder
         */
        public function fulfilling()
        {
            if ($this->operation === 0)
            {
                throw new \SHPS\exception(ERROR_SQL_UNKNOWN_OPERATION);
            }

            return new \SHPS\sql_conditionBuilder($this);
        }

        /**
         * Execute the query
         * 
         * @param mixed $conditions optional
         * @return \SHPS\sql_queryBuilder
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
                case 1:// SELECT
                    $query = 'SELECT ';
                    $st = $this->sql->getServerType();
                    if ($st == 'MSSQL' && $this->limit > 0)
                    {
                        $query .= 'TOP ' . $this->limit . ' ';
                    }
                    
                    $colCount = count($this->buf);
                    $tables = [];
                    $i = 0;
                    foreach ($this->buf as $buf)
                    {
                        $i++;
                        $query .= (string)$buf;
                        $tmp = (string)$buf->getTable();
                        if (!in_array($tmp, $tables))
                        {
                            $tables[] = $tmp;
                        }
                        
                        if ($colCount == $i)
                        {
                            $query .= ' ';
                        }
                        else
                        {
                            $query .= ',';
                        }
                    }
                    
                    $query .= 'FROM ';
                    $i = 0;
                    $tblCount = count($tables);
                    foreach ($tables as $table)
                    {
                        $i++;
                        $query .= $table;
                        
                        if ($tblCount == $i)
                        {
                            $query .= ' ';
                        }
                        else
                        {
                            $query .= ',';
                        }
                    }
                    
                    $query .= 'WHERE ' . (string)$conditions . ' ';
                    
                    if ($this->orderBy !== null)
                    {
                        $query .= 'ORDER BY ' . (string)$this->orderBy . ' ' . $this->obAscending ? 'ASC ' : 'DESC ';
                    }
                    
                    if (($st == 'MySQL' || $st == 'MariaDB') && $this->limit > 0)
                    {
                        $query .= 'LIMIT ' . $this->limit . ' ';
                    }
                    
                    $query .= ';';
                    $this->sql->query($query);
                    
                    break;

                case 2:// INSERT
                    $this->table->insert($this->buf);
                    
                    break;
                    
                case 3:// ALTER
                    
                    $this->table->update($this->buf, $conditions);
                    break;

                case 4:// DELETE
                    if ($conditions === null)
                    {// Drop table
                        $this->table->drop();
                    }
                    else
                    {// Drop rows
                        $this->table->delete($conditions);
                    }

                    break;
                    
                default:// Dipshit happened
                    
                    throw new \SHPS\exception(ERROR_SQL_UNKNOWN_OPERATION);
            }

            return $this;
        }

        /**
         * Get parent sql
         * 
         * @return \SHPS\sql
         */
        public function getSQL()
        {
            return $this->sql;
        }
    }
}