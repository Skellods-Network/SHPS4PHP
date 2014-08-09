<?php

/**
 * SHPS Index site<br>
 * This file is part of the Skellods Homepage System. It must not be distributed
 * without the licence file or without this header text.
 * 
 * 
 * @author Marco Alka <admin@skellods.de>
 * @copyright (c) 2013, Marco Alka
 * @license privat_Licence.txt Privat Licence
 * @link http://skellods.de Skellods
 */


/** 
 * Uncommenting the following line will trigger debug mode which will send debug
 * info to the browser
 */
//define('DEBUG',true,true);

require_once 'SkellodsHPSys.php';

// namespace \Skellods\SHPS;


// Get wrapper instance
$hp = SKELLODS_HP::getInstance();

/** 
 * the following line allows you to manipulate the displayed page
 */
//$hp->loadPage('MyPage');

// Process the site
$hp->interpret();
$hp->displayPage(true);
