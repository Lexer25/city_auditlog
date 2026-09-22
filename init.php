<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Module meta.
 * Used by order module views at the bottom of the page.
 */

// Bump this value when you change the order module.
define('AUDITLOG_MODULE_VERSION', '1.0.1');

	
Kohana::$config->load('menu')
    ->set('audit', array(
        'title' => 'Аудит',
        'url' => 'auditlog',
        'icon' => 'fa-cog',
        'order' => 400,
		'disabled' => false, 
       
    ));

