<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Low NoSpam Accessory class
*
* @package			low-nospam-ee2_addon
* @version			2.1.0
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/
class Low_nospam_acc {

	var $name			= 'My Example Accessory';
	var $id				= 'example';
	var $version		= '1.0';
	var $description	= 'My accessory has a lovely description.';
	var $sections		= array();

	// --------------------------------------------------------------------

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Low_nospam_acc()
	{
		$this->__construct();
	}
	
	/**
	 * PHP5 Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}
}
// END CLASS