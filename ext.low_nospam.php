<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Low NoSpam Extension class
*
* @package			low-nospam-ee2_addon
* @version			2.1.0
* @author			Lodewijk Schutte ~ Low <low@loweblog.com>
* @link				http://loweblog.com/software/low-nospam/
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/
class Low_nospam_ext
{
	/**
	* Extension settings
	*
	* @var	array
	*/
	var $settings = array();

	/**
	* Extension name
	*
	* @var	string
	*/
	var $name = 'Low NoSpam';

	/**
	* Extension version
	*
	* @var	string
	*/
	var $version = '2.1.0';

	/**
	* Extension description
	*
	* @var	string
	*/
	var $description = 'Fight spam on your site by using the Akismet or TypePad AntiSpam service';

	/**
	* Do settings exist?
	*
	* @var	bool
	*/
	var $settings_exist = TRUE;

	/**
	* Documentation link
	*
	* @var	string
	*/
	var $docs_url = 'http://loweblog.com/software/low-nospam/';

	/**
	* NSM Addon Updater link
	*
	* @var	string
	*/
	var $versions_xml = 'http://loweblog.com/software/low-nospam/feed/';	

	/**
	* Default settings
	*
	* @var	array
	*/
	var $default_settings = array(
		'service'	=> 'akismet',
		'api_key'	=> '',
		'check_members'	=> array(2, 3, 4),
		'check_comments' => 'y',
		'discard_comments' => 'n',
		'check_forum_posts' => 'n',
		'check_wiki_articles' => 'n',
		'moderate_if_unreachable' => 'y'
	);

	/**
	* Error message line
	*
	* @var	string
	*/
	var $error = '';

	// --------------------------------------------------------------------

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Low_nospam_ext($settings = FALSE)
	{
		$this->__construct($settings);
	}

	// --------------------------------------------------------------------

	/**
	* PHP 5 Constructor
	*
	* @param	$settings	mixed	Array with settings or FALSE
	* @return	void
	*/
	function __construct($settings = FALSE)
	{
		/** -------------------------------------
		/**  Get global instance
		/** -------------------------------------*/

		$this->EE =& get_instance();

		/** -------------------------------------
		/**  Load Low NoSpam Library
		/** -------------------------------------*/

		$this->EE->load->add_package_path(PATH_THIRD.'low_nospam/');
		$this->EE->load->library('low_nospam');

		// set settings
		$this->settings = $settings;
	}

	// --------------------------------------------------------------------

	/**
	* Settings
	*
	* @return	array
	*/
	function settings()
	{
		/** -------------------------------------
		/**  Get Services from library file
		/** -------------------------------------*/

		$services = array();

		foreach(array_keys($this->EE->low_nospam->services) AS $key)
		{
			$services[$key] = $key;
		}

		/** -------------------------------------
		/**  Get member groups from DB
		/** -------------------------------------*/

		$this->EE->db->select('group_id, group_title');
		$this->EE->db->order_by('group_title', 'asc');
		$query = $this->EE->db->get('exp_member_groups');

		foreach($query->result() AS $row)
		{
			$groups[$row->group_id] = $row->group_title;
		}

		/** -------------------------------------
		/**  Compose settings array
		/** -------------------------------------*/

		$settings = array(
			'service'			=> array('s', $services, $this->default_settings['service']),
			'api_key'			=> $this->default_settings['api_key'],
			'check_members'		=> array('ms', $groups, $this->default_settings['check_members']),
			'check_comments'	=> array('r', array('y' => "yes", 'n' => "no"), $this->default_settings['check_comments']),
			'discard_comments'	=> array('r', array('y' => "yes", 'n' => "no"), $this->default_settings['discard_comments'])
		);

		/** -------------------------------------
		/**  Is Forum installed?
		/** -------------------------------------*/

		$this->EE->db->where('module_name', 'Forum');
		$this->EE->db->from('exp_modules');

		if ($this->EE->db->count_all_results())
		{
			$settings['check_forum_posts'] = array('r', array('y' => "yes", 'n' => "no"), $this->default_settings['check_forum_posts']);
		}

		/** -------------------------------------
		/**  Is wiki installed?
		/** -------------------------------------*/

		$this->EE->db->where('module_name', 'Wiki');
		$this->EE->db->from('exp_modules');

		if ($this->EE->db->count_all_results())
		{
			$settings['check_wiki_articles'] = array('r', array('y' => "yes", 'n' => "no"), $this->default_settings['check_wiki_articles']);
		}

		/** -------------------------------------
		/**  Final settings value(s)
		/** -------------------------------------*/

		$settings['moderate_if_unreachable'] = array('r', array('y' => "yes", 'n' => "no"), $this->default_settings['moderate_if_unreachable']);

		return $settings;
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
		/** -------------------------------------
		/**  check settings to see if comment needs to be verified
		/** -------------------------------------*/

		if ($this->settings['check_comments'] == 'y' AND $this->_check_user())
		{

			/** -------------------------------------
			/**  Array to check
			/** -------------------------------------*/

			$comment = array(
				'comment_author'		=> $data['name'],
				'comment_author_email'	=> $data['email'],
				'comment_author_url'	=> $data['url'],
				'comment_content'		=> $data['comment'],
				'user_ip'				=> $data['ip_address']
			);

			// Check it!
			if ($this->is_spam($comment))
			{
				/** -------------------------------------
				/**  discard message (exit without saving)
				/**  or save closed comment
				/** -------------------------------------*/

				if ($this->settings['discard_comments'] == 'y')
				{
					$this->error = 'input_discarded';
				}
				else
				{
					// set comment status to 'c' 
					$data['status'] = 'c';

					// insert closed comment to DB
					$this->EE->db->insert('exp_comments', $data);

					// Set error message if not already set
					if ($this->error == '')
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
	* Check incoming forum post, exit if it's spam
	*
	* @param	array
	* @return	array
	*/
	function forum_submit_post_start($obj)
	{
		// check settings to see if trackback needs to be verified
		if (isset($this->settings['check_forum_posts']) AND $this->settings['check_forum_posts'] == 'y' AND $this->_check_user())
		{
			// input array
			$this->input = array(
				'user_ip'				=> $this->EE->input->ip_address(),
				'user_agent'			=> $this->EE->session->userdata['user_agent'],
				'comment_author'		=> (strlen($this->EE->session->userdata['screen_name']) ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username']),
				'comment_author_email'	=> $this->EE->session->userdata['email'],
				'comment_author_url'	=> $this->EE->session->userdata['url'],
				'comment_content'		=> ($this->EE->input->post('title') ? $this->EE->input->post('title')."\n\n" : '').$this->EE->input->post('body')
			);

			// Check it!
			if ($this->is_spam())
			{
				// Set error message if not already set
				if ( ! $this->error )
				{
					$this->error = 'input_discarded';
				}

				// No forum post moderation, so just exit
				$this->abort();
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	* Check incoming wiki article, exit if it's spam
	*
	* @param	array
	* @return	array
	*/
	function edit_wiki_article_end($obj, $query)
	{
		// check settings to see if comment needs to be verified
		if (isset($this->settings['check_wiki_articles']) AND $this->settings['check_wiki_articles'] == 'y' AND $this->_check_user())
		{
			$this->input = array(
				'user_ip'				=> $this->EE->input->ip_address(),
				'user_agent'			=> $this->EE->session->userdata['user_agent'],
				'comment_author'		=> (strlen($this->EE->session->userdata['screen_name']) ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username']),
				'comment_author_email'	=> $this->EE->session->userdata['email'],
				'comment_author_url'	=> $this->EE->session->userdata['url'],
				'comment_content'		=> $this->EE->input->post('title').' '.$this->EE->input->post('article_content')
			);

			// Check it!
			if ($this->is_spam())
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
		}
	}

	// --------------------------------------------------------------------

	/**
	* Checks if given array is a spammy one
	*
	* @return	bool
	*/
	function is_spam($array)
	{
		// initiate nospam lib
		if ( ! $this->EE->low_nospam->set_service($this->settings['service'], $this->settings['api_key']) )
		{
			$this->error = 'service_not_found';
			$this->abort();
		}

		// check connectivity
		if ( ! $this->EE->low_nospam->is_available() )
		{
			$this->error = 'service_unreachable';
			return ($this->settings['moderate_if_unreachable'] == 'y');
		}

		// check api key
		if ( ! $this->EE->low_nospam->key_is_valid() )
		{
			$this->error = 'invalid_api_key';
			$this->abort();
		}

		// set data to check
		$this->EE->low_nospam->prep_data($array);

		// get verdict
		return $this->EE->low_nospam->is_spam();
	}

	// --------------------------------------------------------------------

	/**
	* Abort script and show user message
	*
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
	* Check if current user needs to be checked
	*
	* @return	bool
	*/
	function _check_user()
	{
		// Don't check if we don't have to check logged-in members
		if ($this->settings['check_members'] === 'n' AND $this->EE->session->userdata['member_id'] != 0)
		{
			$do_check = FALSE;
		}
		// Don't check if user is not in selected member groups
		elseif (is_array($this->settings['check_members']) AND !in_array($this->EE->session->userdata['group_id'], $this->settings['check_members']))
		{
			$do_check = FALSE;
		}
		// Every other case, perform check
		else
		{
			$do_check = TRUE;
		}

		return $do_check;
	}

	// --------------------------------------------------------------------

	/**
	* Activate extension
	*
	* @return	null
	*/
	function activate_extension()
	{
		// Hooks to insert
		$hooks = array(
			'insert_comment_insert_array',
			'forum_submit_post_start',
			'edit_wiki_article_end'
		);

		// insert hooks and methods
		foreach ($hooks AS $hook)
		{
			// data to insert
			$data = array(
				'class'		=> get_class($this),
				'method'	=> $hook,
				'hook'		=> $hook,
				'priority'	=> 1,
				'version'	=> $this->version,
				'enabled'	=> 'y',
				'settings'	=> serialize($this->default_settings)
			);

			// insert in database
			$this->EE->db->insert('exp_extensions', $data);
		}

	}

	// --------------------------------------------------------------------

	/**
	* Update extension
	*
	* @param	string	$current
	* @return	null
	*/
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		// init data array
		$data = array();

		// Add version to data array
		$data['version'] = $this->version;

		// Update records using data array
		$this->EE->db->where('class', get_class($this));
		$this->EE->db->update('exp_extensions', $data);
	}

	// --------------------------------------------------------------------

	/**
	* Disable extension
	*
	* @return	null
	*/
	function disable_extension()
	{
		// Delete records
		$this->EE->db->where('class', get_class($this));
		$this->EE->db->delete('exp_extensions');
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file ext.low_nospam.php */