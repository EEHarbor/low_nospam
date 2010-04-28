<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Low NoSpam Accessory class
*
* @package			low-nospam-ee2_addon
* @version			2.1.0
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
* @since			2.1.0
*/
class Low_nospam_acc {

	var $name			= 'Low NoSpam';
	var $id				= 'low_nospam';
	var $version		= '2.1.0';
	var $description	= 'Accessory for the Low NoSpam add-on.';
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

	// --------------------------------------------------------------------

	/**
	 * PHP5 Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	* Set Sections
	*
	* Set content for the accessory
	*
	* @access	public
	* @return	void
	*/
	function set_sections()
	{
		$this->EE->lang->loadfile('low_nospam');

		// Get closed comments
		$this->EE->db->select('COUNT(*) AS num');
		$this->EE->db->from('exp_comments');
		$this->EE->db->where('status', 'c');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get();
		$result = $query->row_array();

		// Show accessory tab accordingly
		if ( $num = $result['num'] )
		{
			$this->name .= " ($num)";
			$heading = ($num == 1) ? $this->EE->lang->line('closed_comments_one') : sprintf($this->EE->lang->line('closed_comments_many'), $num);
			$content = '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_nospam">'.$this->EE->lang->line('go_moderate').'</a>';
		}
		else
		{
			$heading = $this->EE->lang->line('no_closed_comments');
			$content = '<a href="'.$this->EE->lang->line('donate_url').'">'.$this->EE->lang->line('donate_link').'</a>';
		}

		$this->sections[$heading] = $content;

	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file acc.low_nospam.php */