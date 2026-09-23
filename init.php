<?php defined('SYSPATH') or die('No direct script access.');

defined('AUDITLOG_VERSION') OR define('AUDITLOG_VERSION', '1.0.1');

	
Kohana::$config->load('menu')
    ->set('audit', array(
        'title' => 'Аудит',
        'url' => 'auditlog',
        'icon' => 'fa-cog',
        'order' => 400,
		'disabled' => false, 
       
    ));

