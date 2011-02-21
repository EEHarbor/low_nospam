<?php

/**
* Low NoSpam Config file
*
* @package			low-nospam-ee2_addon
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/

if ( ! defined('LOW_NOSPAM_NAME'))
{
	define('LOW_NOSPAM_NAME',       'Low NoSpam');
	define('LOW_NOSPAM_CLASS_NAME', 'Low_nospam');
	define('LOW_NOSPAM_VERSION',    '2.2.1');
	define('LOW_NOSPAM_DOCS',       'http://loweblog.com/software/low-nospam/');
}

$config['name']     = LOW_NOSPAM_NAME;
$config['version']  = LOW_NOSPAM_VERSION;
$config['nsm_addon_updater']['versions_xml']  = LOW_NOSPAM_DOCS.'feed/';