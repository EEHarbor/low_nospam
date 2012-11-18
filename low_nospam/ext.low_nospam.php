<?php //if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_nospam/config.php');

/**
 * Low NoSpam Extension class
 *
 * @package			low_nospam
 * @author			Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link			http://gotolow.com/addons/low-nospam
 * @license			http://creativecommons.org/licenses/by-sa/3.0/
 */
class Low_nospam_ext {

	/**
	 * Extension settings
	 *
	 * @var	array
	 */
	public $settings = array();

	/**
	 * Extension name
	 *
	 * @var	string
	 */
	public $name = LOW_NOSPAM_NAME;

	/**
	 * Extension version
	 *
	 * @var	string
	 */
	public $version = LOW_NOSPAM_VERSION;

	/**
	 * Extension description
	 *
	 * @var	string
	 */
	public $description = 'Fight spam on your site by using the Akismet or TypePad AntiSpam service';

	/**
	 * Do settings exist?
	 *
	 * @var	bool
	 */
	public $settings_exist = TRUE;

	/**
	 * Documentation link
	 *
	 * @var	string
	 */
	public $docs_url = LOW_NOSPAM_DOCS;

	/**
	 * Default settings
	 *
	 * @var	array
	 */
	private $default_settings = array(
		'service'                    => 'akismet',
		'api_key'                    => '',
		'key_is_valid'               => FALSE,
		'check_members'              => array(2, 3, 4),
		'check_comments'             => 'y',
		'caught_comments'            => 'p',
		'check_forum_posts'          => 'n',
		'check_wiki_articles'        => 'n',
		'check_member_registrations' => 'n',
		'moderate_if_unreachable'    => 'y'
	);

	/**
	 * Error message line
	 *
	 * @var	string
	 */
	public $error = '';

	/**
	 * Package name
	 */
	private $package = LOW_NOSPAM_PACKAGE;

	/**
	 * Hooks used
	 */
	private $hooks = array(
		'sessions_start',
		'insert_comment_insert_array',
		'delete_comment_additional',
		'forum_submit_post_start',
		'edit_wiki_article_end',
		'member_member_register_start',
		'cp_js_end'
	);

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	$settings	mixed	Array with settings or FALSE
	 * @return	void
	 */
	public function __construct($settings = array())
	{
		//  Get global instance
		$this->EE =& get_instance();

		//  Load Low NoSpam Library
		$this->EE->load->add_package_path(PATH_THIRD.$this->package);
		$this->EE->load->library($this->package);

		// Set extension class name
		$this->class_name = ucfirst(get_class($this));

		// set settings
		$this->settings = array_merge($this->default_settings, $settings);
	}

	// --------------------------------------------------------------------

	/**
	 * Extension settings form
	 *
	 * @param	array
	 * @return	string
	 */
	public function settings_form($current)
	{
		$this->EE->cp->load_package_js($this->package);

		// -------------------------------------
		//  Get Services from library file
		// -------------------------------------

		$services = array();

		foreach(array_keys($this->EE->low_nospam->get_services()) AS $key)
		{
			$services[$key] = $key;
		}

		// -------------------------------------
		// Get member groups
		// -------------------------------------

		$query = $this->EE->db->select('group_id, group_title')
		       ->from('member_groups')
		       ->order_by('group_title')
		       ->get();

		// -------------------------------------
		// Initiate member groups array
		// -------------------------------------

		$groups = array();

		// -------------------------------------
		// Populate member groups array
		// -------------------------------------

		foreach ($query->result_array() AS $row)
		{
			$groups[$row['group_id']] = $row['group_title'];
		}

		// -------------------------------------
		// Get list of installed modules
		// -------------------------------------

		$installed = $this->EE->cp->get_installed_modules();

		// -------------------------------------
		// Define settings array for display
		// -------------------------------------

		$data = array();
		$data['settings'] = array_merge($this->default_settings, $current);
		$data['member_groups'] = $groups;
		$data['name']      = str_replace('_ext', '', $this->class_name);
		$data['services']  = $services;
		$data['has_forum'] = in_array('forum', $installed);
		$data['has_wiki']  = in_array('wiki', $installed);

		// -------------------------------------
		// Build output
		// -------------------------------------

		$this->EE->cp->set_breadcrumb('#', LOW_NOSPAM_NAME);

		// -------------------------------------
		// Load view
		// -------------------------------------

		return $this->EE->load->view('settings', $data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Save extension settings
	 */
	public function save_settings()
	{
		// Initiate settings array
		$settings = array();

		// Loop through default settings
		foreach ($this->default_settings AS $setting => $default_value)
		{
			// Check posted values, fallback to default value
			if (($value = $this->EE->input->post($setting)) === FALSE)
			{
				$value = $default_value;
			}

			// Populate new settings
			$settings[$setting] = $value;
		}

		// Set service
		$this->EE->low_nospam->set_service(
			$settings['service'],
			$settings['api_key']
		);

		// Check key validity
		$settings['key_is_valid'] = $this->EE->low_nospam->key_is_valid();

		// Update new settings
		$this->EE->db->where('class', $this->class_name);
		$this->EE->db->update('extensions', array('settings' => serialize($settings)));

		$this->EE->functions->redirect($_SERVER['HTTP_REFERER']);
	}

	// --------------------------------------------------------------------

	/**
	 * Prep the Low_nospam library for use
	 *
	 * @access     public
	 * @param      object
	 * @return     object
	 */
	public function sessions_start(&$session)
	{
		// -------------------------------------
		//  Initiate NoSpam Service
		// -------------------------------------

		$this->EE->low_nospam->set_service(
			$this->settings['service'],
			$this->settings['api_key']
		);

		// Set member groups so others can get to it easily
		$this->EE->low_nospam->set_member_groups($this->settings['check_members']);

		// -------------------------------------
		//  Mark as ham only if opening comments and checking the box
		// -------------------------------------

		if ((REQ == 'CP') &&
			($this->EE->input->get('method') == 'modify_comments') &&
			($this->EE->input->post('action') == 'open') &&
			($this->EE->input->post('mark_as_ham') == 'y') &&
			($this->EE->input->post('toggle') !== FALSE))
		{
			$this->_mark($this->EE->input->post('toggle'), 'ham');
		}

		return $session;
	}

	/**
	 * Add JS to CP for marking as spam/ham
	 */
	public function cp_js_end()
	{
		$js = $this->_get_last_call();

		return $js;
	}

	// --------------------------------------------------------------------

	/**
	 * Check incoming comment, exits if it's spam
	 *
	 * @param	array
	 * @return	array
	 */
	function insert_comment_insert_array($data)
	{
		// -------------------------------------
		//  Make sure we're dealing with the latest
		// -------------------------------------

		$data = $this->_get_last_call($data);

		// -------------------------------------
		//  check settings to see if comment needs to be verified
		// -------------------------------------

		if ($this->_check_prerequisites('check_comments'))
		{
			// Set data to check
			$this->EE->low_nospam->set_data(array(
				'comment_author'       => $data['name'],
				'comment_author_email' => $data['email'],
				'comment_author_url'   => $data['url'],
				'comment_content'      => $data['comment'],
				'user_ip'              => $data['ip_address'],
				'comment_type'         => 'comment'
			));

			// Check if service is available
			// if ( ! $this->EE->low_nospam->is_available()) return FALSE;

			// Check it!
			if ($this->EE->low_nospam->is_spam())
			{
				// -------------------------------------
				//  discard message (exit without saving)
				//  or save closed comment
				// -------------------------------------

				if ($this->settings['caught_comments'] == 'x')
				{
					$this->error = 'input_discarded';
				}
				else
				{
					// set comment status to 'c' or 'p'
					$data['status'] = $this->settings['caught_comments'];

					// insert closed comment to DB
					$this->EE->db->insert('comments', $data);

					// Set error message if not already set
					if (empty($this->error))
					{
						$this->error = 'input_is_spam';
					}
				}

				// abort script
				$this->abort();
			}
		}

		// return data as if nothing happened...
		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Mark given comments as spam, if so desired
	 */
	function delete_comment_additional($comment_ids)
	{
		if ($this->EE->input->post('mark_as_spam') == 'y')
		{
			$this->_mark($comment_ids, 'spam');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Check incoming forum post, exit if it's spam
	 *
	 * @param	object
	 * @return	void
	 */
	function forum_submit_post_start($obj)
	{
		$obj = $this->_get_last_call($obj);

		// Bail out if prerequisites aren't met
		if ( ! $this->_check_prerequisites('check_forum_posts')) return $obj;

		// input array
		$this->EE->low_nospam->set_data(array(
			'user_ip'				=> $this->EE->input->ip_address(),
			'user_agent'			=> $this->EE->session->userdata['user_agent'],
			'comment_author'		=> (strlen($this->EE->session->userdata['screen_name']) ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username']),
			'comment_author_email'	=> $this->EE->session->userdata['email'],
			'comment_author_url'	=> $this->EE->session->userdata['url'],
			'comment_content'		=> ($this->EE->input->post('title') ? $this->EE->input->post('title')."\n\n" : '').$this->EE->input->post('body')
		));

		// Check it!
		if ($this->EE->low_nospam->is_spam())
		{
			// Set error message if not already set
			if ( ! $this->error )
			{
				$this->error = 'input_discarded';
			}

			// No forum post moderation, so just exit
			$this->abort();
		}

		return $obj;
	}

	// --------------------------------------------------------------------

	/**
	 * Check incoming wiki article, exit if it's spam
	 */
	function edit_wiki_article_end($obj, $query)
	{
		$query = $this->_get_last_call($query);

		// Bail out?
		if ( ! $this->_check_prerequisites('check_wiki_articles')) return $query;

		$this->EE->low_nospam->set_data(array(
			'user_ip'				=> $this->EE->input->ip_address(),
			'user_agent'			=> $this->EE->session->userdata['user_agent'],
			'comment_author'		=> (strlen($this->EE->session->userdata['screen_name']) ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username']),
			'comment_author_email'	=> $this->EE->session->userdata['email'],
			'comment_author_url'	=> $this->EE->session->userdata['url'],
			'comment_content'		=> $this->EE->input->post('title').' '.$this->EE->input->post('article_content')
		));

		// Check it!
		if ($this->EE->low_nospam->is_spam())
		{
			// HANDLE WIKI ARTICLE SPAM
			$wiki_id = $obj->wiki_id;
			$page_id = $this->EE->db->escape_str($query->row['page_id']);

			// get real last revision id
			$query  = $this->EE->db->query("SELECT last_revision_id FROM exp_wiki_page WHERE wiki_id = {$wiki_id} AND page_id = {$page_id}");
			$row    = $query->row_array();
			$rev_id = $row['last_revision_id'];

			// close revision
			$this->EE->db->query("UPDATE exp_wiki_revisions SET revision_status = 'closed' WHERE wiki_id = {$wiki_id} AND page_id = {$page_id} AND revision_id = {$rev_id}");

			$this->abort();
		}

		return $query;
	}

	// --------------------------------------------------------------------

	/**
	 * Check incoming wiki article, exit if it's spam
	 *
	 * @return	void
	 */
	function member_member_register_start()
	{
		// Bail out if prerequisites aren't met
		if ( ! $this->_check_prerequisites()) return;

		// Don't send these values to the service
		$ignore = array('password', 'password_confirm', 'rules', 'email' , 'url', 'username',
						'XID', 'ACT', 'RET', 'FROM', 'site_id', 'accept_terms');

		// Init content var
		$content = '';

		// Loop through posted data, add to content var
		foreach ($_POST AS $key => $val)
		{
			if (in_array($key, $ignore)) continue;

			$content .= $val . "\n";
		}

		$this->EE->low_nospam->set_data(array(
			'user_ip'				=> $this->EE->input->ip_address(),
			'user_agent'			=> $this->EE->session->userdata['user_agent'],
			'comment_author'		=> $this->EE->input->post('username'),
			'comment_author_email'	=> $this->EE->input->post('email'),
			'comment_author_url'	=> $this->EE->input->post('url'),
			'comment_content'		=> $content
		));

		// Check it!
		if ($this->EE->low_nospam->is_spam())
		{
			// Set error message if not already set
			if (empty($this->error))
			{
				$this->error = 'input_discarded';
			}

			// Exit if spam
			$this->abort();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * See if we're checking
	 */
	private function _check_prerequisites($which)
	{
		// Valid key?
		if ( ! $this->settings['key_is_valid']) return FALSE;

		// Check member?
		$member_group = $this->EE->session->userdata['group_id'];
		if ($member_group && in_array($member_group, $this->settings['check_members'])) return FALSE;

		// Check settings
		if (@$this->settings[$which] != 'y') return FALSE;

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Abort script and show user message
	 *
	 * @param	string
	 * @return	void
	 */
	function abort($msg = '')
	{
		$this->EE->extensions->end_script = TRUE;
		$this->EE->lang->loadfile('low_nospam');

		// get error msg
		$line = ($msg) ? $msg : $this->error;

		// show error message
		$this->EE->output->show_user_error('submission', $this->EE->lang->line($line));
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Mark given comments as either spam or ham
	 */
	private function _mark($comment_ids = array(), $as = 'spam')
	{
		$select = array(
			'ip_address AS user_ip',
			'name       AS comment_author',
			'email      AS comment_author_email',
			'url        AS comment_author_url',
			'comment    AS comment_content'
		);

		// Compose query, service-friendy
		$query = $this->EE->db->select($select)
		       ->from('comments')
		       ->where_in('comment_id', $comment_ids)
		       ->get();

		// Determine method
		$method = ($as == 'spam') ? 'mark_as_spam' : 'mark_as_ham';

		// send each one to service
		foreach ($query->result_array() AS $row)
		{
			$this->EE->low_nospam->$method($row);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Activate extension
	 */
	function activate_extension()
	{
		foreach ($this->hooks AS $hook)
		{
			$this->_add_hook($hook);
		}
	}

	/**
	 * Disable extension
	 */
	function disable_extension()
	{
		// Delete records
		$this->EE->db->where('class', $this->class_name);
		$this->EE->db->delete('extensions');
	}

	/**
	 * Update extension
	 */
	function update_extension($current = '')
	{
		// Bail out if we're good
		if ($current == '' OR version_compare($current, $this->version) === 0)
		{
			return FALSE;
		}

		// Get current settings
		$this->settings = array_filter(array_merge(
			$this->default_settings,
			$this->settings
		));

		// Data to update
		$data = array();

		// Update to 3.0.0
		if (version_compare($current, '3.0.0', '<'))
		{
			// Radical update!
			$this->disable_extension();
			$this->enable_extension();
		}

		// General update stuff
		if ($data)
		{
			// Update records using data array
			$this->EE->db->where('class', $this->class_name);
			$this->EE->db->update('extensions', $data);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Add hook to extensions table
	 */
	private function _add_hook($hook)
	{
		$this->EE->db->insert('extensions', array(
			'class'    => $this->class_name,
			'method'   => $hook,
			'hook'     => $hook,
			'settings' => serialize($this->settings),
			'priority' => 1,
			'version'  => $this->version,
			'enabled'  => 'y'
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Get last call and return it
	 */
	private function _get_last_call($arg = NULL)
	{
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$arg = $this->EE->extensions->last_call;
		}

		return $arg;
	}

}
// END CLASS

/* End of file ext.low_nospam.php */