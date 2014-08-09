<?php

/**
 * SHPS Upload/Download Managerr<br>
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
require_once 'SHPS.plugin.php';

// namespace \Skellods\SHPS;

    
/**
 * IO
 *
 * All functionalities concerning file upload and download are bundled in the IO
 * class
 *
 * @author Marco Alka <admin@skellods.de>
 * @version 1.2
 */
class SHPS_io
{    
    private $ssql = null;
    
    /**
     * Singelton
     * 
     * @var array Array of SHPS_io 
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
    }
    
    /**
     * Return singleton instance
     * 
     * @return SHPS_io
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
     * Check if a file is requested and deliver it
     * 
     * @return boolean
     */
    public static function handleRequest()
    {
        $f = getSG(INPUT_GET,'file');
	if($f !== null)
        {
            self::getInstance()->sendFile($f);
            return true;
        }
        
        return false;
    }
    
    /**
     * Set cache headers for file to send
     * 
     * @param string $mimetype
     * @param integer $cachetime
     */
    private static function setCacheHeaders($mimetype, $cachetime)
    {
	header('Content-type: ' . $mimetype);
	header('Cache-Control: private, max-age=10800, pre-check=10800');
	header('Pragma: private');
	header('Expires: ' . gmdate("D, d M Y H:i:s", (time() + $cachetime)) . ' GMT');
    }

    /**
     * Load file content from file in pool or database
     * 
     * @param string $name
     * @return mixed
     */
    public static function loadFile($name)
    {
        $self = self::getInstance();
	SHPS_pluginEngine::setBinarySwitch(true);
        $fcontent = '';
        
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('upload');
        $cols = array(
            new SHPS_sql_colspec($tbl,'filename'),
            new SHPS_sql_colspec($tbl,'file'),
            new SHPS_sql_colspec($tbl,'cache'),
            new SHPS_sql_colspec($tbl,'mimetype')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'name'),
                SHPS_SQL_RELATION_EQUAL,
                $name
                );
        
        $sql->readTables($cols, $conditions);
        if(($frow = $sql->fetchRow()))
        {
            $row = array(
                'filename' => $frow->getValue('filename'),
                'file' => $frow->getValue('file'),
                'cache' => $frow->getValue('cache'),
                'mimetype' => $frow->getValue('mimetype')
            );
            
            if(empty($row['file']))
            {
                $du = SHPS_main::getDir('upload');
                $d = SHPS_main::getDomain();
                $fld = SHPS_main::getFLDomain();
                if(file_exists($du . $d . '/') || file_exists($du . $fld . '/'))
                {
                    if(file_exists($du . $d . '/' . $row['filename']))
                    {
                        $row['file'] = file_get_contents($du . $d . '/' . $row['filename']);
                    }
                    elseif(file_exists($du . $fld . '/' . $row['filename']))
                    {
                        $row['file'] = file_get_contents($du . $fld . '/' . $row['filename']);
                    }
                }

                if(empty($row['file']))
                {
                    if(file_exists($du . $row['filename']))
                    {
                        $row['file'] = file_get_contents($du . $row['filename']);
                    }
                }
            }

            $disposition = 'inline';
            if(strstr($row['mimetype'],'image') === false && strstr($row['mimetype'],'text') === false)
            {
                $disposition = 'attachment';
            }
            
            header('Content-Disposition: ' . $disposition . '; filename="' . $row['filename'] . '"');
            
            $fcontent .= $row['file'];
            if($row['cache'] == true)
            {
                $self->setCacheHeaders($row['mimetype'], 3600 * 24 * 7);
            }
            else
            {
                header('Content-type: ' . $row['mimetype']);
            }
        }
        
        $sql->free();
        
        return $fcontent;
    }
    
    /**
     * Send file
     * @deprecated since update 2
     *
     * @throws SHPS_exception
     * @param string $name
     */
    public static function sendFile($name)
    {
        $fcontent = self::loadFile($name);
        if(SHPS_pluginEngine::callEvent('onSendFile', $fcontent))
        {
            echo $fcontent;
        }
        else
        {
            throw new SHPS_exception(SHPS_ERROR_PLUGIN_UNKNOWN);
        }
    }

    /**
     * Check if file exists (checks filename)
     * 
     * @param string $file
     * @return string|false
     */
    private static function checkIfExists($file)
    {
	$sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('upload');
        $cols = array(
            new SHPS_sql_colspec($tbl,'ID')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'filename'),
                SHPS_SQL_RELATION_EQUAL,
                $file
                );
        
        $sql->readTables($cols, $conditions);
	if(($row = $sql->fetchRow()))
        {
	    $r = $row->getValue('ID');
        }
	else
        {
	    $r = false;
        }

	$sql->free();
	return $r;
    }

    /**
     * Upload files to system, if upload is available
     * 
     * @param string $fieldName Name of input element/array containing the files to handle
     * @param integer $saveGlobal If this is 0, the system makes the uploaded files available for all homepages.<br>
     *                            If this is 1, the system makes the uploaded files available for all subdomains.<br>
     *                            By default (all other numbers), the files are only available for this subdomain.
     *                            //Default: 2
     * @param array|null $uploadName //Default: null
     * @param array|null $fileName //Default: null
     * @return array
     */
    public static function handleUpload($fieldName = 'images' ,$saveGlobal = 2, $uploadName = NULL, $fileName = NULL)
    {
        $quota = SHPS_main::getHPConfig('General_Config','upload_quota');
        $du = SHPS_main::getDir('upload');
        
	if($quota
           && $quota != 0
           && foldersize($du) >= $quota)
        {
            return;
        }

	if(empty($_FILES))
	{
            return;
        }
        
        $result = array();
        $sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('upload');
        $endloop = false;
        $counter = 0;
        do //foreach($_FILES[$fieldName]['tmp_name'] as $i => $tempFile)
        {
            if(is_array($_FILES[$fieldName]['tmp_name']))
            {
                if($counter == 0)
                {
                    $ak = array_keys($_FILES[$fieldName]['tmp_name']);
                    $f = $_FILES[$fieldName]['tmp_name'];
                }
                
                if(!isset($ak[$counter]))
                {
                    break;
                }
                
                $i = $ak[$counter];
                $tempFile = $f[$i];
                $name = $_FILES[$fieldName]['name'][$i];
                $counter++;
            }
            else
            {
                $tempFile = $_FILES[$fieldName]['tmp_name'];
                $name = $_FILES[$fieldName]['name'];
                $endloop = true;
                $i = 0;
            }
            
            $seed = randomString(10);
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if($fileName === NULL)
            {
                $fname = $seed . '_' . $name;
            }
            elseif($fileName[$i] === NULL)
            {
                $fname = $seed . '_' . $name;
            }
            else
            {
                $fname = $fileName[$i] . '.' . $ext;
            }

            if($uploadName === NULL)
            {
                $uname = $seed . '_' . basename($fname, '.' . $ext);
            }
            elseif($uploadName[$i] === NULL)
            {
                $uname = $seed . '_' . basename($fname, '.' . $ext);
            }
            else
            {
                $uname = $uploadName[$i];
            }

            $c = -2;
            do
            {
                $c++;
                if($c == -1)
                {
                    $tname = $uname;
                }
                else
                {
                    $tname = $c . '_' . $uname;
                }
                
                $cols = array(
                    new SHPS_sql_colspec($tbl,'ID')
                );

                $conditions = new SHPS_sql_condition(
                        new SHPS_sql_colspec($tbl,'name'),
                        SHPS_SQL_RELATION_EQUAL,
                        $tname
                        );

                $sql->readTables($cols, $conditions);
            }
            while($sql->count() > 0 && self::checkIfExists($du . $tname));

            if($c >= 0)
            {
                $fname = $c . '_' . $fname;
                $uname = $tname;
            }

            $result[$i] = $uname;
            if($saveGlobal == 0)
            {
                $targetFile = $du . $fname;
            }
            elseif($saveGlobal == 1)
            {
                $targetFile = $du . SHPS_main::getFLDomain() . SHPS_DIRECTORY_SEPARATOR . $fname;
            }
            else
            {
                $targetFile = $du . SHPS_main::getDomain() . SHPS_DIRECTORY_SEPARATOR . $fname;
            }

            $mime = mime_content_type($tempFile);                
            move_uploaded_file($tempFile, $targetFile);
            
            $tbl->insert(array('name' => $uname,
                               'filename' => $fname,
                               'upload_time' => time(),
                               'mimetype' => $mime
                    ));
        }
        while(!$endloop);
            
        $sql->free();
        return $result;	
    }

    /**
     * Return ID of first uploaded file containing a string
     * 
     * @param string $str
     * @return integer
     */
    public function getFirstContaining($str)
    {
        if(self::getInstance()->ssql !== null)
        {
            self::getInstance()->ssql->free();
        }
        
	$sql = SHPS_sql::newSQL();
        $tbl = $sql->openTable('upload');
        $cols = array(
            new SHPS_sql_colspec($tbl,'ID')
        );
        
        $conditions = new SHPS_sql_condition(
                new SHPS_sql_colspec($tbl,'name'),
                SHPS_SQL_RELATION_LIKE,
                '%' . $str . '%'
                );
        
        $sql->readTables($cols, $conditions);
	if(($row = $this->ssql->fetchRow()))
        {
            self::getInstance()->ssql = $sql;
	    return $row['ID'];
        }

	return;
    }

    /**
     * Return ID of next uploaded file containing a string
     * 
     * @return Integer
     */
    public function getNextContaining()
    {
	if(self::getInstance()->ssql === null)
        {
	    return;
        }
        
	if(($row = self::getInstance()->ssql->fetchRow()))
        {
	    return $row['ID'];
        }

	return;
    }

    /**
     * End previous search
     */
    public function closeSearch()
    {
	if(self::getInstance()->ssql !== null)
        {
	    self::getInstance()->ssql->free();
        }
        
	self::getInstance()->ssql = null;
    }
    
    /**
     * Get the file's internal name from the ID
     * 
     * @param integer $id
     * @return string
     */
    public static function getNameFromID($id)
    {
	$sql = SHPS_sql::newSQL();
	$sql->readTable('upload','name','ID=' . $sql->cleanInt($id));
	if(($row = $sql->fetchRow()))
        {
	    $r = $row['name'];
        }
	else
        {
	    $r = null;
        }
	
	$sql->free();
	return $r;
    }
}
