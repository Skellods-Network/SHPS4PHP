<?php

global $SHPS_config;

$SHPS_config = array(
    
    /**
     * General Configuration
     */
    'General_Config'
    => array(

        // Your domain goes here
        'URL'			=> 'http://example.com',

        // Your cookie path
        'cookie_path'		=> '/',

        // Your cookie domain
        'cookie_domain'		=> '.example.com',

        // Your base file which executes the system.
        // Default is index.php
        // You can also leave this empty to create domains like http://your-domain.com/?site=index
        'index'			=> 'index.php',

        // This name will be used for example for eMails
        'hp_name'		=> 'My Homepage',

        // Uploads will be put and read from this directory
        'dir_upload'		=> 'uploads',

        // Admin name
        'admin_name'		=> 'John Doe',

        // Admin mail
        'admin_mail'		=> 'admin@example.de',

        // No-reply mail
        'mail'			=> 'support@example.de',

        // Should CSS be minified?
        'minify_css'		=> true,

        // Should JS be minified?
        'minify_js'		=> false,

        // Should the system optimize the load-time by minifying the complete homepage?
        'minify_hp'		=> false,

        // Number of log entries stored in DB
        'log_count'		=> 50,

        // Insert your timezone here. Acceptable are for example 'UTC', 'Europe/Berlin', ...
        'timezone'		=> 'Europe/Berlin',

        // If you want to use the rewrite engine to edit links (SEO), please edit the following setting
        // Else you can just leave them
        // {$index} is replaced with the complete name of the index file (for example 'index.php')
        // {$index_name} is replaced with the name of the index file without file extension (for example 'index')
        'file_rewrite'		=> '{$index}?request=',
        'link_rewrite'		=> '{$index}?site=',

        // Here you can set the upload quota for this particular site. The system will not upload any files that exceed the quota
        // Quota is in MB
        // Set 0 for no limit
        'upload_quota'		=> 0,
        
        /**
         * Should minimal statistics be output as HTML
         */
        'display_stats'         => true,
        
        /**
         * Name of content in DB, which should be used as index
         */
        'index_content'         => 'index',
        
        /**
         * After what time should a session time out
         */
        'session_timeout'       => 1800,
        
        /**
         * How long should client info be cached
         */
        'client_info_cache_time'=> 30 * 24 * 60 * 1000
        
    ),
    
    
    /**
     * Database Configuration
     * 
     * MC prefix is for MemCached server.
     */
    'Database_Config' => array(
        'default' => array(
            'DB_Host'		=> 'localhost',
            'DB_Port'           => 3306,
            'DB_Name'		=> '',
            'DB_User'		=> '',
            'DB_Pass'		=> '',
            'DB_Pre'		=> 'HP_',
            'DB_Type'		=> SHPS_SQL_MYSQL,
            
            'MC_Enable'         => false,
            'MC_Servers'        => array(
                array(
                    'Host'      => '127.0.0.1',
                    'Port'      => 11211
                )
            )
        ),
        'logging' => array(
            'DB_Host'		=> 'localhost',
            'DB_Port'           => 3306,
            'DB_Name'		=> '',
            'DB_User'		=> '',
            'DB_Pass'		=> '',
            'DB_Pre'		=> 'HP_',
            'DB_Type'		=> SHPS_SQL_MYSQL,
            
            'MC_Enable'         => false,
            'MC_Servers'        => array(
                array(
                    'Host'      => '127.0.0.1',
                    'Port'      => 11211
                )
            )
        )
    ),
    
    
    /**
     * Security Configuration
     */
    'Security_Config'
    => array(
        'login_delay'           => 1,
        'max_login_delay'       => 3600
    )
);
