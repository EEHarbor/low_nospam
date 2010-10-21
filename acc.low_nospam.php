<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Include config file
require PATH_THIRD.'low_nospam/config'.EXT;

/**
* Low NoSpam Accessory class
*
* @package			low-nospam-ee2_addon
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
* @since			2.1.0
*/
class Low_nospam_acc {

	var $name			= LOW_NOSPAM_NAME;
	var $id				= LOW_NOSPAM_CLASS_NAME;
	var $version		= LOW_NOSPAM_VERSION;
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
		// What language are we speaking?
		$this->EE->lang->loadfile('low_nospam');

		// Get pending and closed comments
		$this->EE->db->select('status, COUNT(*) AS num');
		$this->EE->db->from('exp_comments');
		$this->EE->db->where_in('status', array('p','c'));
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->group_by('status');
		$query = $this->EE->db->get();

		// Initiate data
		$data = array(
			'pending_comments' => '0',
			'closed_comments' => '0'
		);
		
		// Set totals
		foreach ($query->result_array() AS $row)
		{
			switch ($row['status'])
			{
				case 'p':
					$data['pending_comments'] = $row['num'];
				break;

				case 'c':
					$data['closed_comments'] = $row['num'];
				break;
			}
		}
		
		// Set accessory heading
		if ($data['pending_comments'] && $data['closed_comments'])
		{
			$heading = $this->EE->lang->line('there_are_pending_and_closed_comments');
			$this->name .= ' *';
		}
		elseif ($data['pending_comments'] && ! $data['closed_comments'])
		{
			$heading = $this->EE->lang->line('there_are_pending_comments');
			$this->name .= ' *';
		}
		elseif ( ! $data['pending_comments'] && $data['closed_comments'])
		{
			$heading = $this->EE->lang->line('there_are_closed_comments');
			$this->name .= ' *';
		}
		else
		{
			$heading = $this->EE->lang->line('all_clear');
		}

		// Load accessory view
		$content = $this->EE->load->view('accessory', $data, TRUE);

		// Add JS if we're at the comment module page
		if ($this->EE->input->get('module') == 'comment')
		{
			$this->EE->cp->load_package_js('low_nospam');
			$content .= $this->_js();
		}

		// Set accessory
		$this->sections[$heading] = $content;
	}

	// --------------------------------------------------------------------

	private function _js()
	{
		$add_marker = ($this->EE->input->post('mark_as_spam')) ? 'true' : 'false';
		$lang_mark_as_spam = $this->EE->lang->line('mark_as_spam');
		$lang_mark_as_ham = $this->EE->lang->line('mark_as_ham');
		
		return <<<EOJS
			<script type="text/javascript">
			// <![CDATA[

				if (typeof LOW == 'undefined') {
					var LOW = new Object;
				}

				if (typeof LOW.NoSpam == 'undefined') {
					LOW.NoSpam = new Object;
				}
				
				LOW.NoSpam.lang = {
					"mark_as_spam" : "{$lang_mark_as_spam}",
					"mark_as_ham" : "{$lang_mark_as_ham}"
				};

				LOW.NoSpam.add_marker = {$add_marker};

			// ]]>
			</script>
EOJS;
	}

}
// END CLASS

/* End of file acc.low_nospam.php */