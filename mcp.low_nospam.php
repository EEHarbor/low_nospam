<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Low NoSpam MCP class
*
* @package			low-nospam-ee2_addon
* @version			2.1.2
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/
class Low_nospam_mcp {

	var $settings	= FALSE;
	var $perpage	= 50;

	// --------------------------------------------------------------------

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Low_nospam_mcp()
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

		// module url homepage
		$this->mod_url = $this->data['mod_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_nospam';
	}

	// --------------------------------------------------------------------

	/**
	* Home screen for module
	*
	* @return	string
	*/
	function index()
	{
		// Load stuff
		$this->EE->load->library('javascript');
		$this->EE->load->library('low_nospam');
		$this->EE->cp->load_package_js('low_nospam');

		// Set page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('low_nospam_module_name'));

		// Get pagination
		if ( ! $rownum = $this->EE->input->get_post('rownum'))
		{
			$rownum = 0;
		}

		/** -------------------------------------
		/**  Get closed comments
		/** -------------------------------------*/

		$this->EE->db->select('c.comment_id, c.name, c.email, c.url, c.ip_address, c.comment, t.title, t.channel_id, t.entry_id');
		$this->EE->db->from('exp_comments c');
		$this->EE->db->join('exp_channel_titles t', 'c.entry_id = t.entry_id');
		$this->EE->db->where('c.status', 'c');
		$this->EE->db->where('c.site_id', $this->EE->config->item('site_id'));
		$this->EE->db->limit($this->perpage, $rownum);
		$this->EE->db->order_by('c.comment_date', 'desc');
		$query = $this->EE->db->get();

		$this->data['comments'] = $query->result_array();
		$this->data['total_results'] = $query->num_rows();
		
		// All comments
		$this->EE->db->where('c.status', 'c');
		$this->EE->db->where('c.site_id', $this->EE->config->item('site_id'));
		$total = $this->EE->db->count_all('comments');

		// if there are closed comments, start building output
		if ( ! empty($this->data['comments']) )
		{
			// Load some libs/helpers/language files
			$this->EE->load->library('table');
			$this->EE->load->helper('form');

			// toggle stuff
			$this->EE->javascript->output(array(
					'$("input#togglebox").toggle(
						function(){
							$("table.mainTable tbody input[type=checkbox]").each(function() {
								this.checked = true;
							});
						}, function (){
							$("table.mainTable tbody input[type=checkbox]").each(function() {
								this.checked = false;
							});
						}
					);')
			);

			$js_lang =<<<EOJS
				if (typeof $.LOW == 'undefined') $.LOW = new Object;

				$.LOW.Lang = {
					line: function(str) {
						return this.lines[str] || str;
					},
					lines: {
						no_comments: "%s",
						marking_as_spam: "%s",
						finishing_spam: "%s",
						marking_as_ham: "%s",
						finishing_ham: "%s",
						done: "%s"
					}
				};
EOJS;

			$this->EE->javascript->output(array(sprintf($js_lang,
				$this->EE->lang->line('no_comments'),
				$this->EE->lang->line('marking_as_spam'),
				$this->EE->lang->line('deleting'),
				$this->EE->lang->line('marking_as_ham'),
				$this->EE->lang->line('opening'),
				$this->EE->lang->line('done')
			)));

			$this->EE->javascript->compile();
			
			// Pagination
			$this->EE->db->where('status', 'c');
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$total = $this->EE->db->count_all('comments');

			// Pass the relevant data to the paginate class so it can display the "next page" links
			$this->EE->load->library('pagination');
			$p_config = $this->pagination_config('index', $total);

			$this->EE->pagination->initialize($p_config);

			$this->data['pagination'] = $this->EE->pagination->create_links();
		}

		return $this->EE->load->view('index', $this->data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	* Return pagination config, borrowed from dev docs
	*
	* @param	string
	* @param	int
	* @return	array
	*/
	function pagination_config($method, $total_rows)
	{
		// Pass the relevant data to the paginate class
		$config['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_nospam'.AMP.'method='.$method;
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->perpage;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'rownum';
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="<" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt=">" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="< <" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="> >" />';

		return $config;
	}


	// --------------------------------------------------------------------

	/**
	* Mark closed comments as Spam or Ham, using the service stored in the extension settings
	*
	* @return	void
	*/
	function mark()
	{
		// Get settings fist, so we know what service to call
		$this->_get_extension_settings();

		// Initiate library class
		$this->EE->load->library('low_nospam');

		// Set service and api key
		if ( !$this->EE->low_nospam->set_service($this->settings['service'], $this->settings['api_key']))
		{
			// Show user error: service not found
			die('Service not found');
		}

		// Spam or Ham?
		$as = $this->EE->input->post('mark_as');

		// Get selected comments
		$comments = $this->EE->db->escape_str($this->EE->input->post('toggle', array()));

		// Comment count
		$i = 0;

		if ( empty($comments) )
		{
			$this->EE->functions->redirect(BASE.AMP.$this->mod_url);
		}

		// compose where part of query
		$sql_where = "comment_id IN ('".str_replace('c', '', implode("','", $comments))."')";

		// Compose query, service-friendy
		$sql = "SELECT
				ip_address	AS user_ip,
				name		AS comment_author,
				email		AS comment_author_email,
				url			AS comment_author_url,
				comment		AS comment_content
			FROM
				`exp_comments`
			WHERE
				{$sql_where}
		";
		$query = $this->EE->db->query($sql);

		// Ham or Spam?
		$method = ($as == 'spam') ? 'mark_as_spam' : 'mark_as_ham';

		// send each one to service
		foreach ($query->result_array() AS $row)
		{
			if ($this->EE->low_nospam->{$method}($row))
			{
				$i++;
			}
		}

		// then return if Ajax-request
		die ( (string) $i );
	}

	// --------------------------------------------------------------------

	/**
	* Retrieve and store the Extension settings
	*
	* @return	void
	*/
	function _get_extension_settings()
	{
		$this->EE->db->select('settings');
		$this->EE->db->from('extensions');
		$this->EE->db->where('class', 'Low_nospam_ext');
		$this->EE->db->limit(1);
		$query = $this->EE->db->get();

		if ($query->num_rows)
		{
			$row = $query->row_array();
			$this->settings = unserialize($row['settings']);
		}
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file mcp.low_nospam.php */