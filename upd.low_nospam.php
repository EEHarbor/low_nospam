<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Low NoSpam UPD class
*
* @package			low-nospam-ee2_addon
* @version			2.0.0
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/
class Low_nospam_upd {
	
	/**
	* Version number
	*
	* @var	string
	*/
	var $version = '2.0.0';
	
	// --------------------------------------------------------------------

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Low_nospam_upd()
	{
		$this->__construct();
	}
	
	// --------------------------------------------------------------------
	
	/**
	* PHP 5 Constructor
	*
	* @return	void
	*/
	function __construct()
	{
		/** -------------------------------------
		/**  Get global instance
		/** -------------------------------------*/
		
		$this->EE =& get_instance();
		
		// set module name
		$this->name = str_replace('_upd', '', ucfirst(get_class($this)));
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Uninstall the module
	*
	* @return	bool
	*/
	function install()
	{
		$this->EE->db->insert('modules', array(
			'module_name'		=> $this->name,
			'module_version'	=> $this->version,
			'has_cp_backend'	=> 'y'
		));
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Uninstall the module
	*
	* @return	bool
	*/
	function uninstall()
	{
		// get module id
		$this->EE->db->select('module_id');
		$this->EE->db->from('exp_modules');
		$this->EE->db->where('module_name', $this->name);
		$query = $this->EE->db->get();

		// remove references from module_member_groups
		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		// remove references from modules
		$this->EE->db->where('module_name', $this->name);
		$this->EE->db->delete('modules');
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Update the module
	*
	* @return	bool
	*/
	function update($current = '')
	{
		return FALSE;
	}
	
}