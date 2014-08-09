<?php

/**
 * SHPS File Locker<br>
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
 * SECURE
 *
 * Prevents accidental output by system files through direct calls
 * 
 * 
 * @author Marco Alka <admin@skellods.de>
 * @version 1.0
 */
if(!defined('SHPS'))
{
    header('Status: 404 Not Found');
    exit(0);
}
