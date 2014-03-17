<?php

/**
 * Low NoSpam Config file
 *
 * @package        low_nospam
 * @author         Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-nospam
 * @license        http://creativecommons.org/licenses/by-sa/3.0/
 */

if ( ! defined('LOW_NOSPAM_NAME'))
{
	define('LOW_NOSPAM_NAME',    'Low NoSpam');
	define('LOW_NOSPAM_PACKAGE', 'low_nospam');
	define('LOW_NOSPAM_VERSION', '3.0.0');
	define('LOW_NOSPAM_DOCS',    'http://gotolow.com/addons/low-nospam');
}

/**
 * < EE 2.6.0 backward compat
 */
if ( ! function_exists('ee'))
{
	function ee()
	{
		static $EE;
		if ( ! $EE) $EE = get_instance();
		return $EE;
	}
}

/**
 * NSM Addon Updater
 */
$config['name']     = LOW_NOSPAM_NAME;
$config['version']  = LOW_NOSPAM_VERSION;
$config['nsm_addon_updater']['versions_xml']  = LOW_NOSPAM_DOCS.'/feed';